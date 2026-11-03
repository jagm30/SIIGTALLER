<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marca extends Model
{
    use SoftDeletes;

    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'logo_path',
        'verificado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'verificado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function modelos(): HasMany
    {
        return $this->hasMany(ModeloVehiculo::class);
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }

    /** Altas rápidas de recepción pendientes de revisar por el administrador. */
    public function scopePendientesDeVerificar(Builder $query): Builder
    {
        return $query->where('verificado', false);
    }
}
