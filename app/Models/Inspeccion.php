<?php

namespace App\Models;

use App\Enums\EstadoInspeccion;
use App\Enums\GrupoChecklist;
use App\Enums\TipoAdjunto;
use App\Enums\TipoCarroceria;
use App\Enums\TipoInspeccion;
use App\Enums\TipoRespuesta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Acta de cómo llegó (recepción) o salió (entrega) el vehículo.
 * Se completa con completar(); una vez completada es de solo lectura.
 */
class Inspeccion extends Model
{

    protected $table = 'inspecciones';

    protected $fillable = [
        'orden_servicio_id',
        'tipo',
        'estado',
        'realizada_por_id',
        'iniciada_at',
        'completada_at',
        'tipo_carroceria',
        'km',
        'km_justificacion',
        'nivel_combustible',
        'placas',
        'sin_placas',
        'persona_nombre',
        'persona_telefono',
        'persona_es_cliente',
        'sin_danios',
        'objetos_personales',
        'cliente_retiro_valores',
        'observaciones',
        'acepta_terminos',
        'terminos_version',
        'firmante_nombre',
        'firmado_at',
        'dispositivo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoInspeccion::class,
            'estado' => EstadoInspeccion::class,
            'tipo_carroceria' => TipoCarroceria::class,
            'iniciada_at' => 'datetime',
            'completada_at' => 'datetime',
            'firmado_at' => 'datetime',
            'km' => 'integer',
            'nivel_combustible' => 'integer',
            'terminos_version' => 'integer',
            'sin_placas' => 'boolean',
            'persona_es_cliente' => 'boolean',
            'sin_danios' => 'boolean',
            'cliente_retiro_valores' => 'boolean',
            'acepta_terminos' => 'boolean',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function realizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizada_por_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(InspeccionRespuesta::class);
    }

    public function danios(): HasMany
    {
        return $this->hasMany(InspeccionDanio::class);
    }

    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }

    public function fotos(): MorphMany
    {
        return $this->adjuntos()->where('tipo', TipoAdjunto::Foto->value);
    }

    public function firma(): MorphOne
    {
        return $this->morphOne(Adjunto::class, 'adjuntable')
            ->where('tipo', TipoAdjunto::Firma->value)
            ->latestOfMany();
    }

    // ── Estado ───────────────────────────────────────────────────

    public function estaCompletada(): bool
    {
        return $this->estado === EstadoInspeccion::Completada;
    }

    public function esRecepcion(): bool
    {
        return $this->tipo === TipoInspeccion::Recepcion;
    }

    /** Nivel de combustible como fracción legible: 0/8 … 8/8. */
    public function combustibleTexto(): ?string
    {
        return $this->nivel_combustible === null ? null : "{$this->nivel_combustible}/8";
    }

    /**
     * Respuestas agrupadas como en la captura: por grupo (Accesorios, Tablero, …) en el orden
     * del enum y, dentro de cada grupo, en el orden del catálogo.
     *
     * @return Collection<string, Collection<int, InspeccionRespuesta>>
     */
    public function checklistPorGrupo(): Collection
    {
        return $this->respuestas->loadMissing('checklistItem')
            ->sortBy(fn ($r) => [
                $r->checklistItem ? array_search($r->checklistItem->grupo, GrupoChecklist::cases()) : 99,
                $r->checklistItem?->orden,
            ])
            ->groupBy(fn ($r) => $r->checklistItem?->grupo->label() ?? 'Otros puntos');
    }

    // ── Fotos y firma ────────────────────────────────────────────

    /**
     * Guarda las fotos por ángulo: [<id del ángulo> => archivo].
     * Después de la firma ya no se aceptan: el cliente firmó lo que vio.
     *
     * @param  array<int, UploadedFile>  $archivos
     * @return list<Adjunto> las fotos creadas
     */
    public function guardarFotos(array $archivos, User $usuario): array
    {
        if ($archivos === []) {
            return [];
        }

        $angulos = AnguloFoto::whereIn('id', array_keys($archivos))->get()->keyBy('id');

        $fotos = [];
        foreach ($archivos as $anguloId => $archivo) {
            $fotos[] = Adjunto::desdeArchivo($this, $archivo, TipoAdjunto::Foto, $usuario, $angulos->get($anguloId));
        }

        return $fotos;
    }

    /**
     * Guarda la firma dibujada en el canvas (data:image/png;base64,...).
     * Si ya había una, la reemplaza: mientras sea borrador se puede volver a firmar.
     */
    public function guardarFirma(string $dataUrl, User $usuario): void
    {
        $png = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);

        if ($png === false || ! str_starts_with($png, "\x89PNG")) {
            throw ValidationException::withMessages(['firma' => 'La firma no se pudo leer. Intenta de nuevo.']);
        }

        $this->adjuntos()->where('tipo', TipoAdjunto::Firma->value)->get()->each->eliminar();

        // Pasa por un archivo temporal para usar el mismo camino que las fotos (ruta + huella SHA-256).
        $temporal = tempnam(sys_get_temp_dir(), 'firma');
        file_put_contents($temporal, $png);

        try {
            Adjunto::desdeArchivo($this, new UploadedFile($temporal, 'firma.png', 'image/png', null, true), TipoAdjunto::Firma, $usuario);
        } finally {
            unlink($temporal);
        }
    }

    // ── Completar la inspección ──────────────────────────────────
    //
    //   $faltantes = $inspeccion->faltantes();   // para mostrar en pantalla qué falta
    //   $inspeccion->completar();                // lanza ValidationException con la lista si falta algo

    /** @return list<string> lo que falta, en lenguaje de recepción. Vacío = se puede completar. */
    public function faltantes(): array
    {
        // La entrega no repite el checklist: el estado del vehículo quedó en la recepción y
        // solo se agregan observaciones finales, que son opcionales.
        if (! $this->esRecepcion()) {
            $f = [];
            if (blank($this->firmante_nombre)) {
                $f[] = 'Captura el nombre de quien recibe el vehículo.';
            }
            if (! $this->adjuntos()->where('tipo', TipoAdjunto::Firma->value)->exists()) {
                $f[] = 'Falta la firma de conformidad del cliente.';
            }
            return $f;
        }

        $f = [];
        $orden = $this->orden;
        $vehiculo = $orden->vehiculo;
        $sucursal = $orden->sucursal_id;

        // 1. Kilometraje y gasolina
        if ($this->km === null) {
            $f[] = 'Captura el kilometraje.';
        } elseif ($this->esRecepcion() && $this->km < $vehiculo->km_actual) {
            if (! PreferenciaOperacion::bool('recepcion_permite_km_menor', $sucursal, true)) {
                $f[] = "El kilometraje ({$this->km}) es menor al registrado ({$vehiculo->km_actual}).";
            } elseif (blank($this->km_justificacion)) {
                $f[] = "El kilometraje es menor al anterior ({$vehiculo->km_actual}); escribe la justificación.";
            }
        }
        if ($this->nivel_combustible === null) {
            $f[] = 'Indica el nivel de gasolina.';
        } elseif ($this->nivel_combustible < 0 || $this->nivel_combustible > 8) {
            $f[] = 'El nivel de gasolina debe estar entre 0 y 8 octavos.';
        }

        // 2. Placas y persona
        if (blank($this->placas) && ! $this->sin_placas) {
            $f[] = 'Captura las placas o marca que el vehículo llegó sin placas.';
        }
        if (blank($this->persona_nombre)) {
            $f[] = $this->esRecepcion() ? 'Indica quién entrega el vehículo.' : 'Indica quién recoge el vehículo.';
        }

        // 3. Checklist: todos los obligatorios que aplican
        $respuestas = $this->respuestas()->get()->keyBy('checklist_item_id');
        foreach (ChecklistItem::aplicables($this->tipo, $this->tipo_carroceria) as $item) {
            $r = $respuestas->get($item->id);
            if ($r === null || blank($r->valor)) {
                if ($item->obligatorio) {
                    $f[] = "Checklist: falta «{$item->nombre}».";
                }
                continue;
            }
            $permitidos = $item->valoresPermitidos();
            if ($permitidos !== null && ! in_array($r->valor, $permitidos, true)) {
                $f[] = "Checklist: «{$item->nombre}» tiene un valor no válido ({$r->valor}).";
            }
            if ($item->tipo_respuesta === TipoRespuesta::Numero && ! is_numeric($r->valor)) {
                $f[] = "Checklist: «{$item->nombre}» debe ser un número.";
            }
        }

        // 4. Fotos por ángulo
        if (PreferenciaOperacion::bool('recepcion_fotos_obligatorias', $sucursal, true)) {
            $angulosConFoto = $this->fotos()->whereNotNull('angulo_foto_id')->pluck('angulo_foto_id')->all();
            foreach (AnguloFoto::obligatoriosPara($this->tipo_carroceria) as $angulo) {
                if (! in_array($angulo->id, $angulosConFoto)) {
                    $f[] = "Falta la foto: {$angulo->nombre}.";
                }
            }
        }

        // 5. Daños: declaración explícita y foto por daño
        $danios = $this->danios()->withCount('fotos')->get();
        if ($this->sin_danios === null) {
            $f[] = 'Registra los daños o marca «Sin daños visibles».';
        } elseif ($this->sin_danios && $danios->isNotEmpty()) {
            $f[] = 'Marcaste «Sin daños visibles» pero hay daños registrados.';
        } elseif (! $this->sin_danios && $danios->isEmpty()) {
            $f[] = 'Indicaste que hay daños pero no registraste ninguno.';
        }
        if (PreferenciaOperacion::bool('recepcion_foto_por_danio', $sucursal, true)) {
            foreach ($danios->where('fotos_count', 0) as $d) {
                $f[] = "El daño «{$d->tipo->label()}» en la vista {$d->vista->label()} no tiene foto.";
            }
        }

        // 6. Objetos de valor: siempre se declara
        if ($this->cliente_retiro_valores === null) {
            $f[] = 'Pregunta al cliente si retiró sus objetos de valor.';
        }

        // 7. Términos y firma
        if (PreferenciaOperacion::bool('recepcion_firma_obligatoria', $sucursal, true)) {
            if (! $this->acepta_terminos) {
                $f[] = 'El cliente debe aceptar los términos.';
            }
            if (blank($this->firmante_nombre)) {
                $f[] = 'Captura el nombre de quien firma.';
            }
            if (! $this->adjuntos()->where('tipo', TipoAdjunto::Firma->value)->exists()) {
                $f[] = 'Falta la firma.';
            }
        }

        return $f;
    }

    public function completar(): self
    {
        if ($this->estaCompletada()) {
            throw ValidationException::withMessages(['inspeccion' => 'La inspección ya está completada.']);
        }

        $faltantes = $this->faltantes();
        if ($faltantes !== []) {
            throw ValidationException::withMessages(['inspeccion' => $faltantes]);
        }

        return DB::transaction(function () {
            $sucursal = $this->orden->sucursal_id;

            $this->update(['estado' => EstadoInspeccion::Completada, 'completada_at' => now()]
                // Solo la recepción lleva firma y términos.
                + ($this->esRecepcion() ? [
                    'firmado_at' => $this->firmado_at ?? now(),
                    'terminos_version' => $this->terminos_version
                        ?? PreferenciaOperacion::int('recepcion_terminos_version', $sucursal, 1),
                ] : []));

            // La recepción actualiza los datos vivos del vehículo.
            if ($this->esRecepcion()) {
                $vehiculo = $this->orden->vehiculo;
                $cambios = [];
                if ($this->km > $vehiculo->km_actual || filled($this->km_justificacion)) {
                    $cambios['km_actual'] = $this->km;
                }
                if (filled($this->placas) && $this->placas !== $vehiculo->placas) {
                    $cambios['placas'] = $this->placas;
                }
                if ($cambios !== []) {
                    $vehiculo->update($cambios);
                }
            }

            return $this->refresh();
        });
    }
}
