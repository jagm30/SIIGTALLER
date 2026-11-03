<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidaOrdenEditable;
use App\Models\OrdenRefaccion;
use App\Models\OrdenServicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Refacciones usadas en una orden (tabla orden_refacciones).
 * La descripción y el precio se copian del catálogo al momento de agregarla.
 */
class RefaccionUsadaController extends Controller
{
    use ValidaOrdenEditable;

    public function store(Request $request, OrdenServicio $orden): RedirectResponse
    {
        $this->validarOrdenEditable($request->user(), $orden);

        $datos = $request->validate([
            'refaccion_id' => ['nullable', 'exists:refacciones,id'],
            'descripcion' => ['nullable', 'required_without:refaccion_id', 'string', 'max:200'],
            'numero_parte' => ['nullable', 'string', 'max:50'],
            'cantidad' => ['required', 'numeric', 'min:0.001', 'max:9999'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            // El trabajo debe ser de esta misma orden.
            'orden_servicio_item_id' => ['nullable', Rule::exists('orden_servicios', 'id')->where('orden_servicio_id', $orden->id)],
            'proporcionada_por_cliente' => ['boolean'],
            'garantia_dias' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ], ['descripcion.required_without' => 'Elige una refacción del catálogo o describe la pieza.']);

        if (! $request->user()->can('precios.ver', $orden)) {
            unset($datos['precio_unitario']);
        }

        $orden->refacciones()->create($datos + ['proporcionada_por_cliente' => $request->boolean('proporcionada_por_cliente')]);
        $orden->recalcularTotales();

        return back()->with('ok', 'Refacción agregada.');
    }

    public function destroy(Request $request, OrdenRefaccion $refaccionUsada): RedirectResponse
    {
        $orden = $refaccionUsada->orden;
        $this->validarOrdenEditable($request->user(), $orden);

        $refaccionUsada->delete();
        $orden->recalcularTotales();

        return back()->with('ok', 'Refacción eliminada.');
    }
}
