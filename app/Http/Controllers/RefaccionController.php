<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefaccionRequest;
use App\Models\Refaccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Catálogo de piezas y consumibles. */
class RefaccionController extends Controller
{
    public function index(Request $request): View
    {
        $refacciones = Refaccion::query()
            ->when($request->filled('buscar'), fn ($q) => $q->buscar($request->query('buscar')))
            ->orderBy('descripcion')
            ->paginate(25)
            ->withQueryString();

        return view('refacciones.index', compact('refacciones'));
    }

    public function create(): View
    {
        return view('refacciones.create', ['refaccion' => new Refaccion(['unidad' => 'pieza', 'activo' => true])]);
    }

    public function store(RefaccionRequest $request): RedirectResponse
    {
        Refaccion::create($request->validated() + ['activo' => $request->boolean('activo', true)]);

        return redirect()->route('refacciones.index')->with('ok', 'Refacción agregada al catálogo.');
    }

    public function edit(Refaccion $refaccion): View
    {
        return view('refacciones.edit', compact('refaccion'));
    }

    public function update(RefaccionRequest $request, Refaccion $refaccion): RedirectResponse
    {
        $refaccion->update($request->validated() + ['activo' => $request->boolean('activo')]);

        return redirect()->route('refacciones.index')->with('ok', 'Refacción actualizada.');
    }

    /** Si ya se usó en alguna orden, solo se desactiva para no perder el historial. */
    public function destroy(Refaccion $refaccion): RedirectResponse
    {
        if ($refaccion->usos()->exists()) {
            $refaccion->update(['activo' => false]);

            return back()->with('ok', 'La refacción ya se usó en órdenes, así que se desactivó en lugar de borrarse.');
        }

        $refaccion->delete();

        return back()->with('ok', 'Refacción eliminada.');
    }
}
