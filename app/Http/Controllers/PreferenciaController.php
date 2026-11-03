<?php

namespace App\Http\Controllers;

use App\Enums\TipoPreferencia;
use App\Models\PreferenciaOperacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Preferencias de operación del taller (modo con o sin precios, reglas de recepción…).
 * Esta pantalla edita los valores generales; las excepciones por sucursal se pueden
 * agregar después en la misma tabla.
 */
class PreferenciaController extends Controller
{
    public function edit(): View
    {
        $preferencias = PreferenciaOperacion::whereNull('sucursal_id')
            ->orderBy('grupo')->orderBy('id')->get()
            ->groupBy('grupo');

        return view('preferencias.edit', compact('preferencias'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['valores' => ['array']]);
        $valores = $request->input('valores', []);
        $errores = [];

        $preferencias = PreferenciaOperacion::whereNull('sucursal_id')->get();

        // Primero validamos todo; si algo está mal no guardamos nada.
        foreach ($preferencias as $p) {
            $nuevo = $valores[$p->clave] ?? null;

            $valido = match ($p->tipo) {
                TipoPreferencia::Bool => true, // casilla: marcada = 1, sin marcar = no llega
                TipoPreferencia::Int => $nuevo === null || $nuevo === '' || filter_var($nuevo, FILTER_VALIDATE_INT) !== false,
                TipoPreferencia::Decimal => $nuevo === null || $nuevo === '' || is_numeric($nuevo),
                TipoPreferencia::Json => $nuevo === null || $nuevo === '' || json_decode($nuevo) !== null,
                default => true,
            };

            if (! $valido) {
                $errores["valores.{$p->clave}"] = "El valor de «{$p->descripcion}» no es válido.";
            }
        }

        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        DB::transaction(function () use ($preferencias, $valores, $request) {
            foreach ($preferencias as $p) {
                $nuevo = $p->tipo === TipoPreferencia::Bool
                    ? (isset($valores[$p->clave]) ? '1' : '0')
                    : (($valores[$p->clave] ?? '') === '' ? null : (string) $valores[$p->clave]);

                // Solo guardamos lo que cambió, para que actualizado_por_id diga quién lo cambió de verdad.
                if ($nuevo !== $p->valor) {
                    $p->update(['valor' => $nuevo, 'actualizado_por_id' => $request->user()->id]);
                }
            }

            // Si cambió el texto de los términos, sube la versión para saber qué firmó cada cliente.
            $terminos = $preferencias->firstWhere('clave', 'recepcion_terminos');
            if ($terminos?->wasChanged('valor')) {
                $version = $preferencias->firstWhere('clave', 'recepcion_terminos_version');
                $version?->update(['valor' => (string) ((int) $version->valor + 1), 'actualizado_por_id' => $request->user()->id]);
            }
        });

        return back()->with('ok', 'Preferencias guardadas.');
    }
}
