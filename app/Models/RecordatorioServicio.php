<?php

namespace App\Models;

use App\Enums\EstadoRecordatorio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Próximo servicio de un vehículo, por fecha y/o kilometraje (lo que ocurra primero). */
class RecordatorioServicio extends Model
{
    protected $table = 'recordatorios_servicio';

    protected $fillable = [
        'vehiculo_id',
        'orden_origen_id',
        'servicio_id',
        'descripcion',
        'fecha_programada',
        'km_programado',
        'estado',
        'orden_cumplimiento_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoRecordatorio::class,
            'fecha_programada' => 'date',
            'km_programado' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RecordatorioServicio $r) {
            if ($r->fecha_programada === null && $r->km_programado === null) {
                throw new \InvalidArgumentException('El recordatorio necesita fecha o kilometraje.');
            }
        });
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class)->withTrashed();
    }

    public function ordenOrigen(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_origen_id');
    }

    public function ordenCumplimiento(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_cumplimiento_id');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', EstadoRecordatorio::Pendiente->value);
    }

    /** Pendientes cuya fecha vence dentro de $dias días (para el tablero). */
    public function scopePorVencer(Builder $query, int $dias = 15): Builder
    {
        return $query->pendientes()
            ->whereNotNull('fecha_programada')
            ->whereDate('fecha_programada', '<=', now()->addDays($dias))
            ->orderBy('fecha_programada');
    }
}
