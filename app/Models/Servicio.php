<?php

namespace App\Models;

use App\Enums\TipoCobro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Catálogo de mano de obra. El precio incluye IVA y es NULL si el taller opera sin precios. */
class Servicio extends Model
{
    use SoftDeletes;

    protected $table = 'servicios';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'categoria',
        'tipo_cobro',
        'precio',
        'tiempo_estimado_min',
        'intervalo_meses',
        'intervalo_km',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cobro' => TipoCobro::class,
            'precio' => 'decimal:2',
            'tiempo_estimado_min' => 'integer',
            'intervalo_meses' => 'integer',
            'intervalo_km' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /** Refacciones sugeridas al agregar este servicio a una orden. */
    public function refaccionesSugeridas(): BelongsToMany
    {
        return $this->belongsToMany(Refaccion::class, 'servicio_refacciones')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function trabajos(): HasMany
    {
        return $this->hasMany(OrdenServicioItem::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }

    public function tieneIntervaloPropio(): bool
    {
        return $this->intervalo_meses !== null || $this->intervalo_km !== null;
    }

    /**
     * Cada cuánto se repite: el intervalo propio del servicio o, si no tiene, el general
     * de Preferencias (recordatorio_meses_default / recordatorio_km_default).
     * Un valor general en 0 o vacío significa "sin límite por ese lado".
     *
     * @return array{0: ?int, 1: ?int} [meses, km]
     */
    public function intervaloRecordatorio(?int $sucursalId = null): array
    {
        if ($this->tieneIntervaloPropio()) {
            return [$this->intervalo_meses, $this->intervalo_km];
        }

        return [
            PreferenciaOperacion::int('recordatorio_meses_default', $sucursalId) ?: null,
            PreferenciaOperacion::int('recordatorio_km_default', $sucursalId) ?: null,
        ];
    }
}
