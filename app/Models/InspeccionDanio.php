<?php

namespace App\Models;

use App\Enums\SeveridadDanio;
use App\Enums\TipoDanio;
use App\Enums\VistaVehiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InspeccionDanio extends Model
{
    protected $table = 'inspeccion_danios';

    protected $fillable = [
        'inspeccion_id',
        'zona_vehiculo_id',
        'vista',
        'pos_x',
        'pos_y',
        'tipo',
        'severidad',
        'medida_cm',
        'descripcion',
        'danio_origen_id',
        'es_nuevo',
    ];

    protected function casts(): array
    {
        return [
            'vista' => VistaVehiculo::class,
            'tipo' => TipoDanio::class,
            'severidad' => SeveridadDanio::class,
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'medida_cm' => 'integer',
            'es_nuevo' => 'boolean',
        ];
    }

    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(Inspeccion::class);
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaVehiculo::class, 'zona_vehiculo_id');
    }

    /** En la entrega: el mismo daño que se registró al recibir. */
    public function danioOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'danio_origen_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(self::class, 'danio_origen_id');
    }

    public function fotos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }
}
