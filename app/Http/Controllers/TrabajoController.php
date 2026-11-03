<?php

namespace App\Http\Controllers;

use App\Enums\EstadoTrabajo;
use App\Enums\Rol;
use App\Enums\TipoCobro;
use App\Http\Controllers\Concerns\ValidaOrdenEditable;
use App\Models\OrdenServicio;
use App\Models\OrdenServicioItem;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Trabajos realizados en una orden (tabla orden_servicios, modelo OrdenServicioItem).
 * El precio se copia del catálogo automáticamente; si la orden es sin precios, queda vacío.
 */
class TrabajoController extends Controller
{
    use ValidaOrdenEditable;

    public function store(Request $request, OrdenServicio $orden): RedirectResponse
    {
        $this->validarOrdenEditable($request->user(), $orden);

        $datos = $request->validate([
            'servicio_id' => ['nullable', 'exists:servicios,id'],
            'descripcion' => ['nullable', 'required_without:servicio_id', 'string', 'max:200'],
            // En cobro por hora: horas trabajadas. En cobro fijo: normalmente 1.
            'cantidad' => ['nullable', 'numeric', 'min:0.01', 'max:999'],
            'tipo_cobro' => ['nullable', Rule::enum(TipoCobro::class)], // solo para trabajos fuera de catálogo
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'mecanicos' => ['nullable', 'array'],
            'mecanicos.*' => [Rule::exists('users', 'id')->where('rol', Rol::Mecanico->value)],
            'agregar_sugeridas' => ['boolean'],
        ], ['descripcion.required_without' => 'Elige un servicio del catálogo o describe el trabajo.']);

        $servicio = isset($datos['servicio_id']) ? Servicio::find($datos['servicio_id']) : null;

        DB::transaction(function () use ($request, $orden, $datos, $servicio) {
            $trabajo = $orden->trabajos()->create([
                'servicio_id' => $servicio?->id,
                'descripcion' => $datos['descripcion'] ?? null,
                'tipo_cobro' => $servicio?->tipo_cobro ?? TipoCobro::tryFrom($datos['tipo_cobro'] ?? '') ?? TipoCobro::Fijo,
                'cantidad' => $datos['cantidad'] ?? 1,
                // Solo quien ve precios puede cambiar el precio del catálogo.
                'precio_unitario' => $request->user()->can('precios.ver', $orden) ? ($datos['precio_unitario'] ?? null) : null,
                'notas' => $datos['notas'] ?? null,
                'estado' => EstadoTrabajo::Pendiente,
            ]);

            // Si quien lo registra es mecánico y no eligió a nadie, se asigna a sí mismo.
            $mecanicos = $datos['mecanicos'] ?? ($request->user()->esMecanico() ? [$request->user()->id] : []);
            $trabajo->mecanicos()->sync($mecanicos);

            // Agrega las piezas que normalmente lleva el servicio (ej. afinación → bujías y filtros).
            if ($servicio && $request->boolean('agregar_sugeridas')) {
                foreach ($servicio->refaccionesSugeridas as $refaccion) {
                    $orden->refacciones()->create([
                        'orden_servicio_item_id' => $trabajo->id,
                        'refaccion_id' => $refaccion->id,
                        'cantidad' => $refaccion->pivot->cantidad,
                    ]);
                }
            }

            $orden->recalcularTotales();
        });

        return back()->with('ok', 'Trabajo agregado.');
    }

    /** Avance del trabajo y horas del mecánico. */
    public function update(Request $request, OrdenServicioItem $trabajo): RedirectResponse
    {
        $orden = $trabajo->orden;
        $this->validarOrdenEditable($request->user(), $orden);

        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoTrabajo::class)],
            'horas' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $trabajo, $orden, $datos) {
            $trabajo->update(['estado' => $datos['estado'], 'notas' => $datos['notas'] ?? $trabajo->notas]);

            // El mecánico registra sus horas; en cobro por hora eso se vuelve la cantidad a cobrar.
            if (isset($datos['horas']) && $request->user()->esMecanico()) {
                $trabajo->mecanicos()->syncWithoutDetaching([$request->user()->id => ['horas' => $datos['horas']]]);
                $trabajo->sincronizarHorasDeMecanicos();
                $orden->recalcularTotales();
            }
        });

        return back()->with('ok', 'Trabajo actualizado.');
    }

    public function destroy(Request $request, OrdenServicioItem $trabajo): RedirectResponse
    {
        $orden = $trabajo->orden;
        $this->validarOrdenEditable($request->user(), $orden);

        DB::transaction(function () use ($trabajo, $orden) {
            // Las piezas del trabajo se quedan en la orden (la llave foránea las deja "sin trabajo").
            $trabajo->delete();
            $orden->recalcularTotales();
        });

        return back()->with('ok', 'Trabajo eliminado.');
    }
}
