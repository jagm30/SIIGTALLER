<?php

namespace App\Models;

use App\Enums\Combustible;
use App\Enums\EstadoOrden;
use App\Enums\Transmision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Vehiculo extends Model
{
    use SoftDeletes;

    protected $table = 'vehiculos';

    protected $fillable = [
        'cliente_id',
        'marca_id',
        'modelo_vehiculo_id',
        'anio',
        'version',
        'color',
        'placas',
        'vin',
        'numero_motor',
        'combustible',
        'transmision',
        'km_actual',
        'qr_activo',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'km_actual' => 'integer',
            'qr_activo' => 'boolean',
            'combustible' => Combustible::class,
            'transmision' => Transmision::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Vehiculo $v) {
            $v->qr_token ??= (string) Str::ulid();
        });
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloVehiculo::class, 'modelo_vehiculo_id');
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class)->latest('fecha_ingreso');
    }

    /** La orden con la que el vehículo está dentro del taller, si la hay. */
    public function ordenAbierta(): HasOne
    {
        return $this->hasOne(OrdenServicio::class)
            ->whereIn('estado', EstadoOrden::abiertos())
            ->latestOfMany('fecha_ingreso');
    }

    public function recordatorios(): HasMany
    {
        return $this->hasMany(RecordatorioServicio::class);
    }

    public function propietarios(): HasMany
    {
        return $this->hasMany(VehiculoPropietario::class)->latest('desde');
    }

    public function qrConsultas(): HasMany
    {
        return $this->hasMany(QrConsulta::class);
    }

    // ── Atributos ────────────────────────────────────────────────

    protected function placas(): Attribute
    {
        return Attribute::set(fn (?string $valor) => [
            'placas' => $valor ? strtoupper(trim($valor)) : null,
            'placas_normalizadas' => self::normalizarPlacas($valor),
        ]);
    }

    protected function vin(): Attribute
    {
        return Attribute::set(fn (?string $valor) => $valor ? strtoupper(preg_replace('/\s+/', '', $valor)) : null);
    }

    public static function normalizarPlacas(?string $placas): ?string
    {
        if ($placas === null || trim($placas) === '') {
            return null;
        }

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placas));
    }

    // ── Scopes ───────────────────────────────────────────────────

    /** Busca por placas sin importar guiones, espacios o mayúsculas. */
    public function scopePorPlacas(Builder $query, string $placas): Builder
    {
        return $query->where('placas_normalizadas', self::normalizarPlacas($placas));
    }

    public function scopePorVin(Builder $query, string $vin): Builder
    {
        return $query->where('vin', strtoupper(trim($vin)));
    }

    // ── Operaciones ──────────────────────────────────────────────

    /** El QR anterior deja de funcionar. */
    public function regenerarQr(): void
    {
        $this->forceFill(['qr_token' => (string) Str::ulid(), 'qr_activo' => true])->save();
    }

    /** Cierra el registro del dueño actual y abre uno nuevo, en una transacción. */
    public function cambiarPropietario(Cliente $nuevo, ?\DateTimeInterface $desde = null): void
    {
        $desde ??= now();

        DB::transaction(function () use ($nuevo, $desde) {
            $this->propietarios()->whereNull('hasta')->update(['hasta' => $desde->format('Y-m-d')]);
            $this->propietarios()->create([
                'cliente_id' => $nuevo->id,
                'desde' => $desde->format('Y-m-d'),
            ]);
            $this->update(['cliente_id' => $nuevo->id]);
        });
    }
}
