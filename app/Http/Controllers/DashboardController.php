<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOrden;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
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

        // Conteo por estado con valor del enum para colores en la vista.
        $porEstado = collect(EstadoOrden::abiertos())
            ->map(fn (EstadoOrden $e) => [
                'label' => $e->label(),
                'value' => $e->value,
                'count' => $ordenes->where('estado', $e)->count(),
            ]);

        // Al mecánico no le sirven los recordatorios de clientes; solo a recepción y administración.
        $recordatorios = $user->tienePermiso('ordenes.ver_todas')
            ? RecordatorioServicio::porVencer(15)->with('vehiculo.cliente', 'vehiculo.modelo')->limit(20)->get()
            : collect();

        $verPrecios = PreferenciaOperacion::bool('usar_precios', $user->sucursal_id, true)
            && $user->puedeVerPrecios();

        // KPIs para administrador/recepcionista
        $kpis = null;
        if ($user->tienePermiso('ordenes.ver_todas')) {
            $inicioMes = now()->startOfMonth();

            $kpis = [
                'abiertas'       => $ordenes->count(),
                'terminadas'     => $ordenes->where('estado', EstadoOrden::Terminado)->count(),
                'atrasadas'      => $ordenes->filter(fn ($o) => $o->fecha_promesa?->isPast())->count(),
                'ingresos_mes'   => $verPrecios
                                        ? (float) OrdenServicio::where('estado', EstadoOrden::Entregado->value)
                                              ->whereNotNull('fecha_entrega')
                                              ->where('fecha_entrega', '>=', $inicioMes)
                                              ->sum('total')
                                        : null,
                'entregadas_mes' => $verPrecios
                                        ? OrdenServicio::where('estado', EstadoOrden::Entregado->value)
                                              ->whereNotNull('fecha_entrega')
                                              ->where('fecha_entrega', '>=', $inicioMes)
                                              ->count()
                                        : null,
            ];
        }

        return view('dashboard', compact('ordenes', 'porEstado', 'recordatorios', 'kpis', 'verPrecios'));
    }
}
