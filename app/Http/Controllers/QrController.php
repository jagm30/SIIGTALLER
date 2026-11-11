<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOrden;
use App\Models\OrdenServicio;
use App\Models\PreferenciaOperacion;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Página pública que ve el cliente al escanear su QR, y la hoja para imprimirlo.
 *
 * No pide inicio de sesión, así que solo muestra datos del vehículo y sus servicios:
 * nunca el nombre, teléfono o correo del dueño.
 */
class QrController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $vehiculo = Vehiculo::with(['marca', 'modelo'])
            ->where('qr_token', $token)
            ->where('qr_activo', true)
            ->firstOrFail(); // token inválido o QR desactivado → 404

        $vehiculo->qrConsultas()->create([
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        // Solo servicios terminados: lo que está en proceso todavía puede cambiar.
        $ordenes = $vehiculo->ordenes()
            ->where('estado', EstadoOrden::Entregado)
            ->with(['trabajos', 'refacciones', 'recepcion', 'entrega'])
            ->limit(20)
            ->get();

        return view('qr.show', [
            'vehiculo' => $vehiculo,
            'ordenes' => $ordenes,
            'proximo' => $vehiculo->recordatorios()->pendientes()->orderBy('fecha_programada')->first(),
            'enTaller' => $vehiculo->ordenAbierta?->load(['trabajos', 'refacciones']),
            'mostrarPrecios' => PreferenciaOperacion::bool('mostrar_precios_en_qr')
                && PreferenciaOperacion::bool('usar_precios', null, true),
        ]);
    }

    /** Hoja para imprimir el QR y entregarlo al cliente al terminar la recepción. */
    public function imprimir(OrdenServicio $orden): View
    {
        Gate::authorize('ver-orden', $orden);

        $orden->load(['vehiculo.marca', 'vehiculo.modelo']);

        return view('qr.imprimir', ['orden' => $orden, 'vehiculo' => $orden->vehiculo]);
    }
}
