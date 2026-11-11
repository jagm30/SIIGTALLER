<?php

namespace App\Models;

use App\Enums\TipoAdjunto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    /**
     * La imagen como data URI para incrustarla en un PDF. Con $ancho se reduce a una miniatura
     * JPEG 4:3 (la foto completa, sin recortar) para que el PDF no cargue fotos de varios MB.
     * Null si el archivo ya no está en disco o no se puede leer.
     */
    public function dataUri(?int $ancho = null): ?string
    {
        $disco = Storage::disk($this->disk);
        if (! $disco->exists($this->ruta)) {
            return null;
        }

        $contenido = $disco->get($this->ruta);
        if ($ancho === null) {
            return 'data:' . $this->mime . ';base64,' . base64_encode($contenido);
        }

        $imagen = self::sinAvisosDeFormato(fn () => imagecreatefromstring($contenido));
        if ($imagen === false) {
            return null; // archivo dañado: la hoja muestra "Archivo no disponible"
        }
        $imagen = $this->enderezar($imagen, $contenido);

        // La foto completa, sin recortar, centrada en un recuadro blanco 4:3:
        // así todas las miniaturas miden lo mismo en la hoja aunque haya fotos verticales.
        $alto = (int) round($ancho * 3 / 4);
        [$w, $h] = [imagesx($imagen), imagesy($imagen)];
        $escala = min($ancho / $w, $alto / $h, 1);
        [$nuevoW, $nuevoH] = [max(1, (int) round($w * $escala)), max(1, (int) round($h * $escala))];

        $miniatura = imagecreatetruecolor($ancho, $alto);
        imagefill($miniatura, 0, 0, imagecolorallocate($miniatura, 255, 255, 255));
        imagecopyresampled($miniatura, $imagen, intdiv($ancho - $nuevoW, 2), intdiv($alto - $nuevoH, 2), 0, 0, $nuevoW, $nuevoH, $w, $h);

        ob_start();
        imagejpeg($miniatura, null, 75);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    /** Las fotos del celular guardan su giro en EXIF; GD no lo aplica solo. */
    private function enderezar(\GdImage $imagen, string $contenido): \GdImage
    {
        if ($this->mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $imagen;
        }

        $exif = self::sinAvisosDeFormato(fn () => exif_read_data('data://image/jpeg;base64,' . base64_encode($contenido)));

        return match ($exif['Orientation'] ?? 1) {
            3 => imagerotate($imagen, 180, 0),
            6 => imagerotate($imagen, -90, 0),
            8 => imagerotate($imagen, 90, 0),
            default => $imagen,
        };
    }

    /**
     * Muchas fotos traen metadatos imperfectos (p. ej. "libpng warning: iCCP: known incorrect
     * sRGB profile" o un EXIF mal cerrado). GD y EXIF las leen bien, pero avisan con un warning
     * que Laravel convierte en excepción y tumbaría todo el PDF. Solo durante esta lectura se
     * ignoran esos avisos; un archivo que de verdad no se puede leer sigue devolviendo false.
     */
    private static function sinAvisosDeFormato(callable $leer): mixed
    {
        set_error_handler(fn () => true, E_WARNING | E_NOTICE);

        try {
            return $leer();
        } finally {
            restore_error_handler();
        }
    }

    /** Borra el archivo del disco junto con su registro. */
    public function eliminar(): void
    {
        Storage::disk($this->disk)->delete($this->ruta);
        $this->delete();
    }
}
