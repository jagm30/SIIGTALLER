<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inspeccion;
use App\Models\User;

/**
 * Reglas para capturar una inspección, compartidas por los controladores de
 * inspecciones, fotos y daños.
 */
trait AutorizaInspeccion
{
    /** Recepción la hace quien tiene permiso de recepción; la entrega, quien tiene permiso de entrega. */
    protected function autorizarInspeccion(User $user, Inspeccion $inspeccion): void
    {
        $permiso = $inspeccion->esRecepcion() ? 'recepcion.realizar' : 'entrega.realizar';

        abort_unless($user->tienePermiso($permiso), 403);
    }

    /** Además, solo se modifica mientras sea borrador: completada ya es evidencia. */
    protected function autorizarCaptura(User $user, Inspeccion $inspeccion): void
    {
        $this->autorizarInspeccion($user, $inspeccion);

        abort_if($inspeccion->estaCompletada(), 409, 'La inspección ya está completada y no se puede modificar.');
    }
}
