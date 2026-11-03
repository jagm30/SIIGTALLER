<?php

namespace App\Models;

use App\Enums\TipoCarroceria;
use App\Models\Concerns\FiltraPorCarroceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnguloFoto extends Model
{
    use FiltraPorCarroceria;

    protected $table = 'angulos_foto';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'obligatorio',
        'tipos_carroceria',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipos_carroceria' => 'array',
            'obligatorio' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public static function obligatoriosPara(TipoCarroceria|string $carroceria): Collection
    {
        return new Collection(
            static::filtrarPorCarroceria(static::query()->activos()->where('obligatorio', true)->get(), $carroceria)->all()
        );
    }
}
