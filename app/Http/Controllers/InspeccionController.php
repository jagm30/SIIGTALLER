<?php

namespace App\Http\Controllers;

use App\Enums\GrupoChecklist;
use App\Http\Controllers\Concerns\AutorizaInspeccion;
use App\Http\Requests\InspeccionRequest;
use App\Models\AnguloFoto;
use App\Models\ChecklistItem;
use App\Models\Inspeccion;
use App\Models\PreferenciaOperacion;
use App\Models\ZonaVehiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Captura de la recepción y de la entrega del vehículo.
 *
 * Flujo en la tableta:
 *   1. edit()      muestra el formulario con lo que ya se capturó y lo que falta.
 *   2. update()    guarda avance, incluidas fotos y firma (se puede salir y regresar después).
 *   3. completar() revisa que no falte nada obligatorio y cierra el acta.
 *   4. pdf()       exporta el acta completada para imprimirla o enviarla.
 * Los daños se registran con InspeccionDanioController.
 */
class InspeccionController extends Controller
{
    use AutorizaInspeccion;

    public function edit(Request $request, Inspeccion $inspeccion): View|RedirectResponse
    {
        $this->autorizarInspeccion($request->user(), $inspeccion);

        if ($inspeccion->estaCompletada()) {
            return redirect()->route('ordenes.show', $inspeccion->orden_servicio_id)
                ->with('ok', 'Esta inspección ya está completada y no se puede modificar.');
        }

        $inspeccion->load(['orden.vehiculo.marca', 'orden.vehiculo.modelo', 'orden.cliente',
            'respuestas', 'danios.zona', 'danios.fotos', 'fotos.anguloFoto', 'firma']);

        $carroceria = $inspeccion->tipo_carroceria;
        $sucursal = $inspeccion->orden->sucursal_id;

        return view('inspecciones.edit', [
            'inspeccion' => $inspeccion,
            // Puntos del checklist agrupados (Accesorios, Tablero, …) en el orden del enum
            'grupos' => ChecklistItem::aplicables($inspeccion->tipo, $carroceria)
                ->sortBy(fn ($i) => array_search($i->grupo, GrupoChecklist::cases()))
                ->groupBy(fn ($i) => $i->grupo->label()),
            'respuestas' => $inspeccion->respuestas->keyBy('checklist_item_id'),
            'angulos' => AnguloFoto::filtrarPorCarroceria(AnguloFoto::activos()->get(), $carroceria),
            'zonas' => ZonaVehiculo::filtrarPorCarroceria(ZonaVehiculo::orderBy('orden')->get(), $carroceria),
            'faltantes' => $inspeccion->faltantes(),
            'kmAnterior' => $inspeccion->orden->vehiculo->km_actual,
            'terminos' => PreferenciaOperacion::valor('recepcion_terminos', $sucursal),
        ]);
    }

    public function update(InspeccionRequest $request, Inspeccion $inspeccion): RedirectResponse|JsonResponse
    {
        $this->autorizarCaptura($request->user(), $inspeccion);

        DB::transaction(function () use ($request, $inspeccion) {
            $inspeccion->update($request->safe()->except(['respuestas', 'fotos', 'firma']) + [
                // Las casillas que no se marcan no llegan en el formulario: las ponemos en false.
                'sin_placas' => $request->boolean('sin_placas'),
                'persona_es_cliente' => $request->boolean('persona_es_cliente'),
                'acepta_terminos' => $request->boolean('acepta_terminos'),
            ]);

            $this->guardarRespuestas($inspeccion, $request->input('respuestas', []));

            // Primero las fotos y luego la firma: si se mandan juntas, el cliente firma con las fotos ya tomadas.
            $inspeccion->guardarFotos($request->file('fotos', []), $request->user());
            if ($request->filled('firma')) {
                $inspeccion->guardarFirma($request->input('firma'), $request->user());
            }
        });

        // Guardado automático desde la pantalla (antes de registrar un daño).
        if ($request->expectsJson()) {
            return response()->json(['guardado' => true]);
        }

        // "Guardar y completar" usa el mismo formulario: si lo pidieron, intentamos cerrar el acta.
        if ($request->boolean('completar')) {
            return $this->completar($request, $inspeccion);
        }

        return back()->with('ok', 'Avance guardado.');
    }

    public function completar(Request $request, Inspeccion $inspeccion): RedirectResponse
    {
        $this->autorizarInspeccion($request->user(), $inspeccion);

        // Si falta algo, completar() lanza ValidationException y Laravel regresa al
        // formulario con la lista exacta de lo que falta en $errors->get('inspeccion').
        $inspeccion->completar();

        // Al terminar la recepción se imprime el QR que se le entrega al cliente.
        if ($inspeccion->esRecepcion()) {
            return redirect()->route('ordenes.qr', $inspeccion->orden_servicio_id)
                ->with('ok', 'Recepción completada. Imprime el QR y entrégalo al cliente.');
        }

        return redirect()->route('ordenes.show', $inspeccion->orden_servicio_id)
            ->with('ok', 'Inspección de entrega completada. Ya puedes marcar la orden como entregada.');
    }

    /** Acta en PDF tamaño carta: hoja 1 con datos y checklist; hoja 2 con fotos, daños y firma. */
    public function pdf(Inspeccion $inspeccion): Response|RedirectResponse
    {
        Gate::authorize('ver-orden', $inspeccion->orden);

        if (! $inspeccion->estaCompletada()) {
            return back()->with('error', 'El PDF se genera cuando la inspección está completada.');
        }

        $inspeccion->load(['orden.vehiculo.marca', 'orden.vehiculo.modelo', 'orden.cliente', 'orden.sucursal',
            'respuestas.checklistItem', 'danios.zona', 'danios.fotos', 'fotos.anguloFoto', 'firma', 'realizadaPor']);

        $nombre = ($inspeccion->esRecepcion() ? 'recepcion-' : 'entrega-') . $inspeccion->orden->folio . '.pdf';

        return Pdf::loadView('inspecciones.pdf', [
            'inspeccion' => $inspeccion,
            'orden' => $inspeccion->orden,
            'checklist' => $inspeccion->checklistPorGrupo(),
            'terminos' => PreferenciaOperacion::valor('recepcion_terminos', $inspeccion->orden->sucursal_id),
        ])->setPaper('letter')->stream($nombre);
    }

    // ── Apoyo ────────────────────────────────────────────────────

    /**
     * Guarda las respuestas del checklist. Solo acepta puntos que aplican a esta
     * inspección; una respuesta vacía borra la anterior.
     */
    private function guardarRespuestas(Inspeccion $inspeccion, array $respuestas): void
    {
        $aplicables = ChecklistItem::aplicables($inspeccion->tipo, $inspeccion->tipo_carroceria)->keyBy('id');

        foreach ($respuestas as $itemId => $respuesta) {
            $item = $aplicables->get((int) $itemId);
            if ($item === null) {
                continue;
            }

            $valor = trim((string) ($respuesta['valor'] ?? ''));

            if ($valor === '') {
                $inspeccion->respuestas()->where('checklist_item_id', $item->id)->delete();
                continue;
            }

            $inspeccion->respuestas()->updateOrCreate(
                ['checklist_item_id' => $item->id],
                ['item_nombre' => $item->nombre, 'valor' => $valor, 'comentario' => $respuesta['comentario'] ?? null],
            );
        }
    }
}
