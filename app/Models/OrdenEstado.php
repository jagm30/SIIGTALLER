<?php

namespace App\Models;

use App\Enums\EstadoOrden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora inmutable: solo se insertan registros. */
class OrdenEstado extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'orden_estados';

    protected $fillable = [
        'orden_servicio_id',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoOrden::class,
            'estado_nuevo' => EstadoOrden::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
