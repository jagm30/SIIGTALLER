<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenRefaccion extends Model
{
    protected $table = 'orden_refacciones';

    protected $fillable = [
        'orden_servicio_id',
        'orden_servicio_item_id',
        'refaccion_id',
        'numero_parte',
        'descripcion',
        'cantidad',
        'costo_unitario',
        'precio_unitario',
        'proporcionada_por_cliente',
        'garantia_dias',
    ];

    protected $hidden = ['costo_unitario'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'costo_unitario' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'importe' => 'decimal:2',
            'proporcionada_por_cliente' => 'boolean',
            'garantia_dias' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrdenRefaccion $r) {
            if ($r->refaccion_id && $r->refaccion) {
                $r->descripcion ??= $r->refaccion->descripcion;
                $r->numero_parte ??= $r->refaccion->numero_parte;
                $r->costo_unitario ??= $r->refaccion->costo;
                $r->precio_unitario ??= $r->refaccion->precio_venta;
            }

            if (! $r->orden->con_precios) {
                $r->costo_unitario = null;
                $r->precio_unitario = null;
                $r->importe = null;

                return;
            }

            if ($r->proporcionada_por_cliente) {
                $r->costo_unitario = 0;
                $r->precio_unitario = 0;
            }

            $r->importe = $r->precio_unitario === null
                ? null
                : round((float) $r->cantidad * (float) $r->precio_unitario, 2);
        });
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    /** Trabajo al que pertenece la pieza. */
    public function trabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenServicioItem::class, 'orden_servicio_item_id');
    }

    public function refaccion(): BelongsTo
    {
        return $this->belongsTo(Refaccion::class)->withTrashed();
    }
}
