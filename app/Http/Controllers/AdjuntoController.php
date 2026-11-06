<?php

namespace App\Http\Controllers;

use App\Enums\TipoAdjunto;
use App\Http\Controllers\Concerns\AutorizaInspeccion;
use App\Models\Adjunto;
use App\Models\AnguloFoto;
use App\Models\Inspeccion;
use App\Models\InspeccionDanio;
use App\Models\OrdenServicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fotos y firma de las inspecciones.
 *
 * Los archivos se guardan en el disco "local" (storage/app/private), que no es público:
 * solo se ven a través de show(), que revisa que el usuario pueda ver la orden.
 */
class AdjuntoController extends Controller
{
    use AutorizaInspeccion;

    /** Sube una o varias fotos por ángulo: fotos[<id del ángulo>] = archivo. */
    public function storeFotos(Request $request, Inspeccion $inspeccion): RedirectResponse
    {
        $this->autorizarCaptura($request->user(), $inspeccion);

        if ($inspeccion->firma()->exists()) {
            return back()->with('error', 'El cliente ya firmó. No se pueden agregar fotos después de la firma.');
        }

        $request->validate([
            'fotos' => ['required', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10 MB
        ], ['fotos.required' => 'Selecciona al menos una foto.'], ['fotos.*' => 'foto']);

        $angulos = AnguloFoto::whereIn('id', array_keys($request->file('fotos')))->get()->keyBy('id');

        foreach ($request->file('fotos') as $anguloId => $archivo) {
            Adjunto::desdeArchivo($inspeccion, $archivo, TipoAdjunto::Foto, $request->user(), $angulos->get($anguloId));
        }

        return back()->with('ok', 'Fotos guardadas.');
    }

    /**
     * Guarda la firma dibujada en la pantalla. Llega como imagen PNG en base64
     * (data:image/png;base64,...) desde el canvas del formulario.
     */
    public function storeFirma(Request $request, Inspeccion $inspeccion): RedirectResponse
    {
        $this->autorizarCaptura($request->user(), $inspeccion);

        $request->validate([
            'firma' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:700000'],
        ], ['firma.required' => 'Pide al cliente que firme en el recuadro.']);

        $png = base64_decode(substr($request->input('firma'), strlen('data:image/png;base64,')), true);
        if ($png === false || ! str_starts_with($png, "\x89PNG")) {
            return back()->withErrors(['firma' => 'La firma no se pudo leer. Intenta de nuevo.']);
        }

        // Si vuelven a firmar mientras la inspección es borrador, reemplazamos la anterior.
        foreach ($inspeccion->adjuntos()->where('tipo', TipoAdjunto::Firma->value)->get() as $anterior) {
            $this->borrarArchivo($anterior);
        }

        // Pasamos el PNG por un archivo temporal para usar el mismo camino que las fotos (ruta + huella SHA-256).
        $temporal = tempnam(sys_get_temp_dir(), 'firma');
        file_put_contents($temporal, $png);
        Adjunto::desdeArchivo($inspeccion, new UploadedFile($temporal, 'firma.png', 'image/png', null, true), TipoAdjunto::Firma, $request->user());
        @unlink($temporal);

        return back()->with('ok', 'Firma guardada.');
    }

    /** Muestra el archivo (foto o firma) solo a quien puede ver la orden. */
    public function show(Adjunto $adjunto): StreamedResponse
    {
        Gate::authorize('ver-orden', $this->ordenDe($adjunto));

        abort_unless(Storage::disk($adjunto->disk)->exists($adjunto->ruta), 404);

        return Storage::disk($adjunto->disk)->response($adjunto->ruta);
    }

    /** Borra una foto o firma mientras la inspección siga en borrador. */
    public function destroy(Request $request, Adjunto $adjunto): RedirectResponse
    {
        $inspeccion = match (true) {
            $adjunto->adjuntable instanceof Inspeccion => $adjunto->adjuntable,
            $adjunto->adjuntable instanceof InspeccionDanio => $adjunto->adjuntable->inspeccion,
            default => abort(403, 'Este archivo no se puede borrar desde aquí.'),
        };

        $this->autorizarCaptura($request->user(), $inspeccion);
        $this->borrarArchivo($adjunto);

        return back()->with('ok', 'Archivo eliminado.');
    }

    // ── Apoyo ────────────────────────────────────────────────────

    private function borrarArchivo(Adjunto $adjunto): void
    {
        Storage::disk($adjunto->disk)->delete($adjunto->ruta);
        $adjunto->delete();
    }

    private function ordenDe(Adjunto $adjunto): OrdenServicio
    {
        return match (true) {
            $adjunto->adjuntable instanceof OrdenServicio => $adjunto->adjuntable,
            $adjunto->adjuntable instanceof Inspeccion => $adjunto->adjuntable->orden,
            $adjunto->adjuntable instanceof InspeccionDanio => $adjunto->adjuntable->inspeccion->orden,
        };
    }
}
