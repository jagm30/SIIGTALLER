<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoPropietario extends Model
{
    protected $table = 'vehiculo_propietarios';

    protected $fillable = [
        'vehiculo_id',
        'cliente_id',
        'desde',
        'hasta',
    ];

    protected function casts(): array
    {
        return [
            'desde' => 'date',
            'hasta' => 'date',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function esActual(): bool
    {
        return $this->hasta === null;
    }
}
