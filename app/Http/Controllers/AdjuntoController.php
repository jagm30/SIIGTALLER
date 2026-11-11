<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AutorizaInspeccion;
use App\Models\Adjunto;
use App\Models\Inspeccion;
use App\Models\InspeccionDanio;
use App\Models\OrdenServicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Subir, ver y borrar fotos y firmas. Las fotos se suben solas al tomarlas (storeFotos);
 * la firma viaja con el formulario de la inspección (InspeccionController::update).
 *
 * Los archivos se guardan en el disco "local" (storage/app/private), que no es público:
 * solo se ven a través de show(), que revisa que el usuario pueda ver la orden.
 */
class AdjuntoController extends Controller
{
    use AutorizaInspeccion;

    /**
     * Sube fotos por ángulo (fotos[<id del ángulo>] = archivo) en cuanto se toman,
     * sin guardar el resto del formulario. La pantalla la llama con JavaScript.
     */
    public function storeFotos(Request $request, Inspeccion $inspeccion): RedirectResponse|JsonResponse
    {
        $this->autorizarCaptura($request->user(), $inspeccion);

        $request->validate([
            'fotos' => ['required', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10 MB
        ], ['fotos.required' => 'Selecciona al menos una foto.'], ['fotos.*' => 'foto']);

        $fotos = $inspeccion->guardarFotos($request->file('fotos'), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['fotos' => collect($fotos)->map(fn (Adjunto $f) => [
                'angulo_foto_id' => $f->angulo_foto_id,
                'url' => route('adjuntos.show', $f),
            ])]);
        }

        return back()->with('ok', 'Fotos guardadas.');
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
        $adjunto->eliminar();

        return back()->with('ok', 'Archivo eliminado.');
    }

    // ── Apoyo ────────────────────────────────────────────────────

    private function ordenDe(Adjunto $adjunto): OrdenServicio
    {
        return match (true) {
            $adjunto->adjuntable instanceof OrdenServicio => $adjunto->adjuntable,
            $adjunto->adjuntable instanceof Inspeccion => $adjunto->adjuntable->orden,
            $adjunto->adjuntable instanceof InspeccionDanio => $adjunto->adjuntable->inspeccion->orden,
        };
    }
}
