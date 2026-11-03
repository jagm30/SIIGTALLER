<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOrden;
use App\Models\OrdenServicio;
use App\Models\RecordatorioServicio;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de inicio: qué vehículos hay en el taller y qué servicios están por vencer.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $ordenes = OrdenServicio::abiertas()
            ->with(['vehiculo.marca', 'vehiculo.modelo', 'cliente', 'mecanicoResponsable'])
            ->when($user->esMecanico(), fn ($q) => $q->asignadasA($user))
            ->orderBy('fecha_ingreso')
            ->get();

        // Conteo por estado para las tarjetas de arriba, en el orden del ciclo de vida.
        $porEstado = collect(EstadoOrden::abiertos())
            ->mapWithKeys(fn (EstadoOrden $e) => [$e->label() => $ordenes->where('estado', $e)->count()]);

        // Al mecánico no le sirven los recordatorios de clientes; solo a recepción y administración.
        $recordatorios = $user->tienePermiso('ordenes.ver_todas')
            ? RecordatorioServicio::porVencer(15)->with('vehiculo.cliente', 'vehiculo.modelo')->limit(20)->get()
            : collect();

        return view('dashboard', compact('ordenes', 'porEstado', 'recordatorios'));
    }
}
