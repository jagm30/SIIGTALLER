<?php

namespace App\Models;

use App\Enums\EstadoInspeccion;
use App\Enums\EstadoOrden;
use App\Enums\EstadoRecordatorio;
use App\Enums\TipoInspeccion;
use App\Enums\TipoOrden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdenServicio extends Model
{
    use SoftDeletes;

    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'sucursal_id',
        'folio',
        'vehiculo_id',
        'cliente_id',
        'tipo',
        'orden_garantia_de_id',
        'estado',
        'con_precios',
        'recibido_por_id',
        'mecanico_responsable_id',
        'entregado_por_id',
        'fecha_ingreso',
        'fecha_promesa',
        'fecha_entrega',
        'motivo_ingreso',
        'diagnostico',
        'observaciones',
        'motivo_cancelacion',
        'descuento',
        'descuento_porcentaje',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoOrden::class,
            'estado' => EstadoOrden::class,
            'con_precios' => 'boolean',
            'fecha_ingreso' => 'datetime',
            'fecha_promesa' => 'datetime',
            'fecha_entrega' => 'datetime',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por_id');
    }

    public function mecanicoResponsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mecanico_responsable_id');
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por_id');
    }

    /** Orden original cuando esta es una garantía. */
    public function ordenOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'orden_garantia_de_id');
    }

    public function garantias(): HasMany
    {
        return $this->hasMany(self::class, 'orden_garantia_de_id');
    }

    public function bitacoraEstados(): HasMany
    {
        return $this->hasMany(OrdenEstado::class)->orderBy('created_at')->orderBy('id');
    }

    public function inspecciones(): HasMany
    {
        return $this->hasMany(Inspeccion::class);
    }

    public function recepcion(): HasOne
    {
        return $this->hasOne(Inspeccion::class)->where('tipo', TipoInspeccion::Recepcion->value);
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(Inspeccion::class)->where('tipo', TipoInspeccion::Entrega->value);
    }

    /** Trabajos realizados (tabla orden_servicios). */
    public function trabajos(): HasMany
    {
        return $this->hasMany(OrdenServicioItem::class);
    }

    public function refacciones(): HasMany
    {
        return $this->hasMany(OrdenRefaccion::class);
    }

    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }

    public function recordatoriosGenerados(): HasMany
    {
        return $this->hasMany(RecordatorioServicio::class, 'orden_origen_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', EstadoOrden::abiertos());
    }

    public function scopeAsignadasA(Builder $query, User $mecanico): Builder
    {
        return $query->where(function ($q) use ($mecanico) {
            $q->where('mecanico_responsable_id', $mecanico->id)
                ->orWhereHas('trabajos.mecanicos', fn ($m) => $m->where('users.id', $mecanico->id));
        });
    }

    // ── Apertura ─────────────────────────────────────────────────

    /**
     * Abre una orden para un vehículo y crea su inspección de recepción en borrador.
     * La orden no podrá avanzar de "recibido" hasta completar esa inspección.
     */
    public static function abrir(
        Vehiculo $vehiculo,
        User $recepcionista,
        string $motivoIngreso,
        TipoOrden $tipo = TipoOrden::Servicio,
        ?self $garantiaDe = null,
        ?int $sucursalId = null,
    ): self {
        $sucursalId ??= $recepcionista->sucursal_id;

        if ($sucursalId === null) {
            throw ValidationException::withMessages(['sucursal_id' => 'Indica la sucursal que recibe el vehículo.']);
        }
        if ($vehiculo->ordenAbierta()->exists()) {
            throw ValidationException::withMessages(['vehiculo_id' => 'El vehículo ya tiene una orden abierta.']);
        }
        if ($tipo === TipoOrden::Garantia && $garantiaDe === null) {
            throw ValidationException::withMessages(['orden_garantia_de_id' => 'Una orden de garantía debe indicar la orden original.']);
        }

        return DB::transaction(function () use ($vehiculo, $recepcionista, $motivoIngreso, $tipo, $garantiaDe, $sucursalId) {
            $orden = static::create([
                'sucursal_id' => $sucursalId,
                'folio' => Sucursal::siguienteFolio($sucursalId),
                'vehiculo_id' => $vehiculo->id,
                'cliente_id' => $vehiculo->cliente_id,
                'tipo' => $tipo,
                'orden_garantia_de_id' => $garantiaDe?->id,
                'estado' => EstadoOrden::Recibido,
                // Se congela el modo del taller en este momento.
                'con_precios' => PreferenciaOperacion::bool('usar_precios', $sucursalId, true),
                'recibido_por_id' => $recepcionista->id,
                'fecha_ingreso' => now(),
                'motivo_ingreso' => $motivoIngreso,
            ]);

            $orden->bitacoraEstados()->create([
                'estado_anterior' => null,
                'estado_nuevo' => EstadoOrden::Recibido,
                'user_id' => $recepcionista->id,
            ]);

            $orden->inspecciones()->create([
                'tipo' => TipoInspeccion::Recepcion,
                'estado' => EstadoInspeccion::Borrador,
                'realizada_por_id' => $recepcionista->id,
                'iniciada_at' => now(),
                'tipo_carroceria' => $vehiculo->modelo->tipo_carroceria,
                'placas' => $vehiculo->placas,
                'sin_placas' => $vehiculo->placas === null,
            ]);

            return $orden;
        });
    }

    /** Crea la inspección de entrega en borrador (mismo checklist que la recepción). */
    public function iniciarEntrega(User $usuario): Inspeccion
    {
        return $this->entrega()->firstOrCreate([], [
            'tipo' => TipoInspeccion::Entrega,
            'estado' => EstadoInspeccion::Borrador,
            'realizada_por_id' => $usuario->id,
            'iniciada_at' => now(),
            'tipo_carroceria' => $this->recepcion?->tipo_carroceria ?? $this->vehiculo->modelo->tipo_carroceria,
            'placas' => $this->vehiculo->placas,
            'sin_placas' => $this->vehiculo->placas === null,
        ]);
    }

    // ── Reglas ───────────────────────────────────────────────────

    public function estaCerrada(): bool
    {
        return $this->estado->esCerrado();
    }

    /**
     * El diagnóstico solo se escribe una vez que el mecánico empieza a inspeccionar.
     * Mientras la orden está en "Recibido" aún no hay diagnóstico que registrar.
     */
    public function puedeEditarDiagnostico(): bool
    {
        return $this->estado !== EstadoOrden::Recibido && ! $this->estaCerrada();
    }

    /**
     * Cambia el estado validando el ciclo de vida, el rol y las reglas de recepción/entrega,
     * y deja el registro en la bitácora.
     *
     * Si algo no se cumple lanza ValidationException: en un controlador, Laravel regresa
     * al formulario con los mensajes en $errors->get('estado').
     */
    public function cambiarEstado(EstadoOrden $nuevo, User $usuario, ?string $comentario = null): void
    {
        $actual = $this->estado;
        $errores = [];

        if (! $actual->puedePasarA($nuevo)) {
            throw ValidationException::withMessages(['estado' => "No se puede pasar de «{$actual->label()}» a «{$nuevo->label()}»."]);
        }

        if (! $usuario->tieneRol(...$nuevo->rolesQuePuedenEntrar())) {
            throw ValidationException::withMessages(['estado' => "Tu rol no puede mover la orden a «{$nuevo->label()}»."]);
        }

        // Se consulta en vivo (no la relación en caché): la inspección pudo completarse hace un momento.
        if ($actual === EstadoOrden::Recibido && $nuevo !== EstadoOrden::Cancelado
            && $this->recepcion()->first()?->estado !== EstadoInspeccion::Completada) {
            $errores[] = 'La inspección de recepción no está completada.';
        }

        if ($nuevo === EstadoOrden::Entregado
            && PreferenciaOperacion::bool('entrega_inspeccion_obligatoria', $this->sucursal_id, true)
            && $this->entrega()->first()?->estado !== EstadoInspeccion::Completada) {
            $errores[] = 'La inspección de entrega no está completada.';
        }

        if ($nuevo === EstadoOrden::Cancelado && blank($comentario ?? $this->motivo_cancelacion)) {
            $errores[] = 'Indica el motivo de cancelación.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['estado' => $errores]);
        }

        DB::transaction(function () use ($actual, $nuevo, $usuario, $comentario) {
            $cambios = ['estado' => $nuevo];

            if ($nuevo === EstadoOrden::Cancelado) {
                $cambios['motivo_cancelacion'] = $comentario ?? $this->motivo_cancelacion;
            }
            if ($nuevo === EstadoOrden::Entregado) {
                $cambios['fecha_entrega'] = now();
                $cambios['entregado_por_id'] = $usuario->id;
            }

            $this->update($cambios);

            $this->bitacoraEstados()->create([
                'estado_anterior' => $actual,
                'estado_nuevo' => $nuevo,
                'user_id' => $usuario->id,
                'comentario' => $comentario,
            ]);

            if ($nuevo === EstadoOrden::Entregado) {
                $this->generarRecordatorios();
            }
        });
    }

    /**
     * Recalcula importes con IVA incluido.
     * subtotal = suma de importes; total = subtotal − descuento;
     * iva = IVA contenido en el total (solo para reportes internos).
     */
    public function recalcularTotales(): void
    {
        if (! $this->con_precios) {
            $this->forceFill(['subtotal' => null, 'descuento' => null, 'iva' => null, 'total' => null])->save();

            return;
        }

        $subtotal = (float) $this->trabajos()->sum('importe') + (float) $this->refacciones()->sum('importe');

        // Descuento en porcentaje: se vuelve a calcular cada vez, así sigue valiendo aunque
        // después se agreguen o quiten trabajos. Si es una cantidad fija, se usa tal cual.
        $descuento = $this->descuento_porcentaje !== null
            ? $subtotal * (float) $this->descuento_porcentaje / 100
            : (float) ($this->descuento ?? 0);
        $total = max($subtotal - $descuento, 0);
        $pct = PreferenciaOperacion::decimal('iva_porcentaje', $this->sucursal_id, 16.0);

        $this->forceFill([
            'subtotal' => round($subtotal, 2),
            'descuento' => round($descuento, 2),
            'total' => round($total, 2),
            'iva' => round($total - $total / (1 + $pct / 100), 2),
        ])->save();
    }

    /**
     * Se ejecuta al entregar: marca como realizados los recordatorios pendientes de los
     * servicios que se hicieron y programa el siguiente para cada servicio con intervalo.
     */
    public function generarRecordatorios(): void
    {
        if (! PreferenciaOperacion::bool('recordatorios_activos', $this->sucursal_id, true)) {
            return;
        }

        $kmSalida = $this->entrega()->first()?->km ?? $this->recepcion()->first()?->km ?? $this->vehiculo->km_actual;

        $trabajos = $this->trabajos()->with('servicio')->whereNotNull('servicio_id')->get();

        foreach ($trabajos as $trabajo) {
            $servicio = $trabajo->servicio;

            $this->vehiculo->recordatorios()
                ->pendientes()
                ->where('servicio_id', $servicio->id)
                ->update([
                    'estado' => EstadoRecordatorio::Realizado->value,
                    'orden_cumplimiento_id' => $this->id,
                ]);

            if (! $servicio->generaRecordatorio()) {
                continue;
            }

            $this->vehiculo->recordatorios()->create([
                'orden_origen_id' => $this->id,
                'servicio_id' => $servicio->id,
                'descripcion' => $servicio->nombre,
                'fecha_programada' => $servicio->intervalo_meses
                    ? now()->addMonths($servicio->intervalo_meses)->toDateString()
                    : null,
                'km_programado' => $servicio->intervalo_km ? $kmSalida + $servicio->intervalo_km : null,
                'estado' => EstadoRecordatorio::Pendiente,
            ]);
        }
    }
}
