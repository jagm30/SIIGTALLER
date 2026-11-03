<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\EstadoOrden;
use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Cuándo se pueden agregar o quitar trabajos y refacciones de una orden. */
trait ValidaOrdenEditable
{
    protected function validarOrdenEditable(User $user, OrdenServicio $orden): void
    {
        Gate::forUser($user)->authorize('ver-orden', $orden);

        if ($orden->estaCerrada()) {
            throw ValidationException::withMessages(['orden' => 'La orden está cerrada y ya no se puede modificar.']);
        }

        // Primero se completa la recepción; después se diagnostica y se trabaja.
        if ($orden->estado === EstadoOrden::Recibido) {
            throw ValidationException::withMessages(['orden' => 'Primero completa la recepción y pasa la orden a diagnóstico.']);
        }
    }
}
