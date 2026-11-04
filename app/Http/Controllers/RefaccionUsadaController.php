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

        // En el formulario, "Otra refacción" llega como refaccion_id = "otra": es una pieza fuera de catálogo.
        if ($request->input('refaccion_id') === 'otra') {
            $request->merge(['refaccion_id' => null]);
        }

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

        $datos['proporcionada_por_cliente'] = $request->boolean('proporcionada_por_cliente');

        // Si la misma pieza ya está en la orden, sumamos la cantidad en ese renglón
        // (4 bujías + 1 bujía = 5 bujías) en lugar de crear uno nuevo.
        $existente = $this->renglonParaSumar($orden, $datos);

        if ($existente) {
            $existente->update(['cantidad' => (float) $existente->cantidad + (float) $datos['cantidad']]);
            $mensaje = "Se sumó a «{$existente->descripcion}», que ya estaba en la orden. Ahora son " . (float) $existente->cantidad . '.';
        } else {
            $orden->refacciones()->create($datos);
            $mensaje = 'Refacción agregada.';
        }

        $orden->recalcularTotales();

        return back()->with('ok', $mensaje);
    }

    /**
     * Pone el precio a una pieza que quedó sin precio (por ejemplo, una fuera de catálogo
     * que agregó el mecánico, que no ve precios). Solo quien ve precios.
     */
    public function precio(Request $request, OrdenRefaccion $refaccionUsada): RedirectResponse
    {
        $orden = $refaccionUsada->orden;
        $this->validarOrdenEditable($request->user(), $orden);
        abort_unless($request->user()->can('precios.ver', $orden), 403);

        $datos = $request->validate(['precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999']], [], ['precio_unitario' => 'precio']);

        $refaccionUsada->update($datos);
        $orden->recalcularTotales();

        return back()->with('ok', 'Precio guardado.');
    }

    public function destroy(Request $request, OrdenRefaccion $refaccionUsada): RedirectResponse
    {
        $orden = $refaccionUsada->orden;
        $this->validarOrdenEditable($request->user(), $orden);

        $refaccionUsada->delete();
        $orden->recalcularTotales();

        return back()->with('ok', 'Refacción eliminada.');
    }

    // ── Apoyo ────────────────────────────────────────────────────

    /**
     * Busca el renglón de la orden donde conviene sumar la pieza. Solo se suma cuando es
     * exactamente lo mismo: la misma refacción del catálogo, para el mismo trabajo, con el
     * mismo origen (del taller o la trajo el cliente) y sin un precio distinto.
     * Si no eligieron trabajo y la pieza está en un solo renglón, se suma ahí.
     */
    private function renglonParaSumar(OrdenServicio $orden, array $datos): ?OrdenRefaccion
    {
        if (empty($datos['refaccion_id'])) {
            return null; // las piezas fuera de catálogo siempre van en su propio renglón
        }

        $candidatos = $orden->refacciones()
            ->where('refaccion_id', $datos['refaccion_id'])
            ->where('proporcionada_por_cliente', $datos['proporcionada_por_cliente'])
            ->when(! empty($datos['orden_servicio_item_id']), fn ($q) => $q->where('orden_servicio_item_id', $datos['orden_servicio_item_id']))
            ->get();

        // Sin trabajo elegido y la pieza aparece en varios trabajos: no adivinamos, va en renglón aparte.
        if ($candidatos->count() !== 1) {
            return null;
        }

        $renglon = $candidatos->first();

        // Si capturaron otro precio, es una pieza distinta para efectos de cobro.
        if (isset($datos['precio_unitario']) && (float) $datos['precio_unitario'] !== (float) $renglon->precio_unitario) {
            return null;
        }

        return $renglon;
    }
}
