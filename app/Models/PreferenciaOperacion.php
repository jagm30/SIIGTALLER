<?php

namespace App\Models;

use App\Enums\TipoPreferencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cómo opera el taller (modo con o sin precios, reglas de recepción, etc.).
 * Solo el administrador las modifica; actualizado_por_id guarda quién hizo el último cambio.
 *
 * Lectura (la sucursal tiene prioridad sobre el valor general):
 *     PreferenciaOperacion::bool('usar_precios', $sucursalId, true)
 *
 * Si una preferencia no existe en la tabla, se usa el valor por defecto que se pasa al leerla.
 */
class PreferenciaOperacion extends Model
{
    protected $table = 'preferencias_operacion';

    protected $fillable = [
        'sucursal_id',
        'grupo',
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'actualizado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPreferencia::class,
        ];
    }

    /** @var array<string, mixed> valores ya leídos en esta petición */
    private static array $memo = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::olvidar());
        static::deleted(fn () => static::olvidar());
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    // ── Lectura de preferencias ─────────────────────────────────

    public static function valor(string $clave, ?int $sucursalId = null, mixed $default = null): mixed
    {
        $llave = ($sucursalId ?? 'general') . '|' . $clave;

        if (! array_key_exists($llave, static::$memo)) {
            $registro = static::query()
                ->where('clave', $clave)
                ->where(function ($q) use ($sucursalId) {
                    $q->whereNull('sucursal_id');
                    if ($sucursalId !== null) {
                        $q->orWhere('sucursal_id', $sucursalId);
                    }
                })
                ->orderByRaw('sucursal_id IS NULL') // primero la de la sucursal
                ->first();

            static::$memo[$llave] = $registro?->valorTipado();
        }

        return static::$memo[$llave] ?? $default;
    }

    public static function bool(string $clave, ?int $sucursalId = null, bool $default = false): bool
    {
        return (bool) static::valor($clave, $sucursalId, $default);
    }

    public static function int(string $clave, ?int $sucursalId = null, int $default = 0): int
    {
        return (int) static::valor($clave, $sucursalId, $default);
    }

    public static function decimal(string $clave, ?int $sucursalId = null, float $default = 0.0): float
    {
        return (float) static::valor($clave, $sucursalId, $default);
    }

    public static function olvidar(): void
    {
        static::$memo = [];
    }

    /** Convierte el texto guardado al tipo declarado. */
    public function valorTipado(): mixed
    {
        if ($this->valor === null) {
            return null;
        }

        return match ($this->tipo) {
            TipoPreferencia::Bool => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            TipoPreferencia::Int => (int) $this->valor,
            TipoPreferencia::Decimal => (float) $this->valor,
            TipoPreferencia::Json => json_decode($this->valor, true),
            TipoPreferencia::String, TipoPreferencia::Text => $this->valor,
        };
    }
}
