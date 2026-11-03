<?php

namespace App\Models;

use App\Enums\AplicaEn;
use App\Enums\GrupoChecklist;
use App\Enums\TipoCarroceria;
use App\Enums\TipoInspeccion;
use App\Enums\TipoRespuesta;
use App\Models\Concerns\FiltraPorCarroceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistItem extends Model
{
    use FiltraPorCarroceria, SoftDeletes;

    protected $table = 'checklist_items';

    protected $fillable = [
        'grupo',
        'nombre',
        'tipo_respuesta',
        'obligatorio',
        'aplica_en',
        'tipos_carroceria',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'grupo' => GrupoChecklist::class,
            'tipo_respuesta' => TipoRespuesta::class,
            'aplica_en' => AplicaEn::class,
            'tipos_carroceria' => 'array',
            'obligatorio' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(InspeccionRespuesta::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('grupo')->orderBy('orden');
    }

    public function scopeParaInspeccion(Builder $query, TipoInspeccion $tipo): Builder
    {
        return $query->activos()->whereIn('aplica_en', [$tipo->value, AplicaEn::Ambas->value]);
    }

    /** Puntos que se deben mostrar en una inspección según su tipo y la carrocería. */
    public static function aplicables(TipoInspeccion $tipo, TipoCarroceria|string $carroceria): Collection
    {
        return new Collection(
            static::filtrarPorCarroceria(static::query()->paraInspeccion($tipo)->get(), $carroceria)->all()
        );
    }

    /** Valores válidos para una respuesta, según el tipo de respuesta. NULL = libre. */
    public function valoresPermitidos(): ?array
    {
        return match ($this->tipo_respuesta) {
            TipoRespuesta::SiNo => ['si', 'no'],
            TipoRespuesta::Estado => ['bueno', 'regular', 'malo', 'no_aplica'],
            TipoRespuesta::Nivel => ['lleno', 'medio', 'bajo'],
            TipoRespuesta::Numero, TipoRespuesta::Texto => null,
        };
    }
}
