<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrConsulta extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'qr_consultas';

    protected $fillable = [
        'vehiculo_id',
        'ip',
        'user_agent',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
