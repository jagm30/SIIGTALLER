<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServicioRequest;
use App\Models\Refaccion;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Catálogo de mano de obra (afinación, frenos, cambio de aceite…). */
class ServicioController extends Controller
{
    public function index(Request $request): View
    {
        $servicios = Servicio::query()
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%' . $request->query('buscar') . '%'))
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('servicios.index', compact('servicios'));
    }

    public function create(): View
    {
        return view('servicios.create', ['servicio' => new Servicio(['tipo_cobro' => 'fijo', 'activo' => true])]);
    }

    public function store(ServicioRequest $request): RedirectResponse
    {
        Servicio::create($request->validated() + ['activo' => $request->boolean('activo', true)]);

        return redirect()->route('servicios.index')->with('ok', 'Servicio agregado al catálogo.');
    }

    public function edit(Servicio $servicio): View
    {
        $servicio->load('refaccionesSugeridas');

        return view('servicios.edit', [
            'servicio' => $servicio,
            // Para el selector de "agregar refacción": solo activas y que aún no estén en la lista.
            'refacciones' => Refaccion::activas()
                ->whereNotIn('id', $servicio->refaccionesSugeridas->pluck('id'))
                ->get(),
        ]);
    }

    public function update(ServicioRequest $request, Servicio $servicio): RedirectResponse
    {
        // Cambiar el precio aquí no afecta órdenes anteriores: cada orden guardó su propio precio.
        $servicio->update($request->validated() + ['activo' => $request->boolean('activo')]);

        return redirect()->route('servicios.index')->with('ok', 'Servicio actualizado.');
    }

    /** Si ya se usó en alguna orden, solo se desactiva para no perder el historial. */
    public function destroy(Servicio $servicio): RedirectResponse
    {
        if ($servicio->trabajos()->exists()) {
            $servicio->update(['activo' => false]);

            return back()->with('ok', 'El servicio ya se usó en órdenes, así que se desactivó en lugar de borrarse.');
        }

        $servicio->delete();

        return back()->with('ok', 'Servicio eliminado.');
    }

    // ── Refacciones que lleva el servicio ────────────────────────
    // Son las que se proponen al agregar el servicio a una orden (ej. afinación mayor →
    // bujías, filtros, aceite). El mecánico puede quitarlas o cambiarlas en la orden.

    /** Agrega una refacción a la lista, o actualiza su cantidad si ya estaba. */
    public function agregarRefaccion(Request $request, Servicio $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'refaccion_id' => ['required', 'exists:refacciones,id'],
            'cantidad' => ['required', 'numeric', 'min:0.001', 'max:999'],
        ], ['refaccion_id.required' => 'Elige una refacción.'], ['refaccion_id' => 'refacción']);

        $servicio->refaccionesSugeridas()->syncWithoutDetaching([
            $datos['refaccion_id'] => ['cantidad' => $datos['cantidad']],
        ]);

        return back()->with('ok', 'Refacción agregada al servicio.');
    }

    /** Quita una refacción de la lista. No afecta órdenes que ya la usaron. */
    public function quitarRefaccion(Servicio $servicio, Refaccion $refaccion): RedirectResponse
    {
        $servicio->refaccionesSugeridas()->detach($refaccion->id);

        return back()->with('ok', 'Refacción quitada del servicio.');
    }
}
