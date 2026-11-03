<?php

namespace App\Http\Controllers;

use App\Enums\SeveridadDanio;
use App\Enums\TipoAdjunto;
use App\Enums\TipoDanio;
use App\Enums\VistaVehiculo;
use App\Http\Controllers\Concerns\AutorizaInspeccion;
use App\Models\Adjunto;
use App\Models\Inspeccion;
use App\Models\InspeccionDanio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Golpes, rayones y demás daños que se marcan al recibir o entregar el vehículo. */
class InspeccionDanioController extends Controller
{
    use AutorizaInspeccion;

    public function store(Request $request, Inspeccion $inspeccion): RedirectResponse
    {
        $this->autorizarCaptura($request->user(), $inspeccion);

        $datos = $request->validate([
            'zona_vehiculo_id' => ['nullable', 'exists:zonas_vehiculo,id'],
            'vista' => ['required', Rule::enum(VistaVehiculo::class)],
            'tipo' => ['required', Rule::enum(TipoDanio::class)],
            'severidad' => ['required', Rule::enum(SeveridadDanio::class)],
            'medida_cm' => ['nullable', 'integer', 'min:1', 'max:500'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            // Posición en el diagrama (0 a 100 %). Si la pantalla no tiene diagrama, va al centro.
            'pos_x' => ['nullable', 'numeric', 'between:0,100'],
            'pos_y' => ['nullable', 'numeric', 'between:0,100'],
            // En la entrega se puede indicar que es el mismo daño que ya traía al llegar.
            'danio_origen_id' => ['nullable', 'exists:inspeccion_danios,id'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [], ['zona_vehiculo_id' => 'zona', 'medida_cm' => 'medida']);

        DB::transaction(function () use ($request, $inspeccion, $datos) {
            $danio = $inspeccion->danios()->create([
                ...collect($datos)->except('foto')->all(),
                'pos_x' => $datos['pos_x'] ?? 50,
                'pos_y' => $datos['pos_y'] ?? 50,
                // En la entrega, un daño sin origen es un daño nuevo.
                'es_nuevo' => ! $inspeccion->esRecepcion() && empty($datos['danio_origen_id']),
            ]);

            if ($request->hasFile('foto')) {
                Adjunto::desdeArchivo($danio, $request->file('foto'), TipoAdjunto::Foto, $request->user());
            }

            // Si había dicho "sin daños", ya no aplica.
            $inspeccion->update(['sin_danios' => false]);
        });

        return back()->with('ok', 'Daño registrado.');
    }

    public function destroy(Request $request, InspeccionDanio $danio): RedirectResponse
    {
        $this->autorizarCaptura($request->user(), $danio->inspeccion);

        DB::transaction(function () use ($danio) {
            foreach ($danio->fotos as $foto) {
                Storage::disk($foto->disk)->delete($foto->ruta);
                $foto->delete();
            }
            $danio->delete();
        });

        return back()->with('ok', 'Daño eliminado.');
    }
}
