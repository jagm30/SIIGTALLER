<?php

namespace App\Models;

use App\Enums\VistaVehiculo;
use App\Models\Concerns\FiltraPorCarroceria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZonaVehiculo extends Model
{
    use FiltraPorCarroceria;

    protected $table = 'zonas_vehiculo';

    protected $fillable = [
        'clave',
        'nombre',
        'vista',
        'tipos_carroceria',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'vista' => VistaVehiculo::class,
            'tipos_carroceria' => 'array',
            'orden' => 'integer',
        ];
    }

    public function danios(): HasMany
    {
        return $this->hasMany(InspeccionDanio::class);
    }
}
