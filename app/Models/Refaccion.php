<?php

namespace App\Models;

use App\Enums\UnidadRefaccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refaccion extends Model
{
    use SoftDeletes;

    protected $table = 'refacciones';

    protected $fillable = [
        'numero_parte',
        'descripcion',
        'marca',
        'categoria',
        'unidad',
        'costo',
        'precio_venta',
        'activo',
    ];

    /** El costo de compra solo se expone con el permiso costos.ver. */
    protected $hidden = ['costo'];

    protected function casts(): array
    {
        return [
            'unidad' => UnidadRefaccion::class,
            'costo' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'servicio_refacciones')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function usos(): HasMany
    {
        return $this->hasMany(OrdenRefaccion::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('descripcion');
    }

    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        return $query->where(function ($q) use ($texto) {
            $q->where('descripcion', 'like', "%{$texto}%")
                ->orWhere('numero_parte', 'like', "{$texto}%");
        });
    }
}
