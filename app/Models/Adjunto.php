<?php

namespace App\Models;

use App\Enums\TipoAdjunto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;

/**
 * Foto, firma o documento de una inspección, un daño o una orden.
 * Créalo con Adjunto::desdeArchivo() para calcular el hash de evidencia.
 */
class Adjunto extends Model
{
    protected $table = 'adjuntos';

    protected $fillable = [
        'adjuntable_type',
        'adjuntable_id',
        'tipo',
        'angulo_foto_id',
        'disk',
        'ruta',
        'mime',
        'tamano_bytes',
        'hash_sha256',
        'tomada_at',
        'descripcion',
        'subido_por_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoAdjunto::class,
            'tamano_bytes' => 'integer',
            'tomada_at' => 'datetime',
        ];
    }

    public function adjuntable(): MorphTo
    {
        return $this->morphTo();
    }

    public function anguloFoto(): BelongsTo
    {
        return $this->belongsTo(AnguloFoto::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por_id');
    }

    /**
     * Guarda el archivo en disco y crea el registro ligado a $dueno (Inspeccion, InspeccionDanio u OrdenServicio).
     */
    public static function desdeArchivo(
        Model $dueno,
        UploadedFile $archivo,
        TipoAdjunto $tipo,
        ?User $usuario = null,
        ?AnguloFoto $angulo = null,
        string $disk = 'local',
    ): self {
        $carpeta = 'evidencias/' . now()->format('Y/m') . '/' . class_basename($dueno) . '-' . $dueno->getKey();
        $ruta = $archivo->store($carpeta, $disk);

        return $dueno->morphMany(self::class, 'adjuntable')->create([
            'tipo' => $tipo,
            'angulo_foto_id' => $angulo?->id,
            'disk' => $disk,
            'ruta' => $ruta,
            'mime' => $archivo->getMimeType() ?? 'application/octet-stream',
            'tamano_bytes' => $archivo->getSize(),
            'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
            'tomada_at' => now(),
            'subido_por_id' => $usuario?->id,
        ]);
    }
}
