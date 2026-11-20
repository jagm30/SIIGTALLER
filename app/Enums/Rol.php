<?php

namespace App\Enums;

/**
 * Roles del sistema y lo que puede hacer cada uno.
 *
 * Para cambiar qué hace un rol, edita permisos().
 * Se consulta con $user->tienePermiso('recepcion.realizar').
 */
enum Rol: string
{
    case Administrador = 'administrador';
    case Recepcionista = 'recepcionista';
    case Mecanico = 'mecanico';

    /** Todos los permisos que existen en el sistema. */
    public const PERMISOS = [
        'usuarios.gestionar',     // alta, baja y rol de usuarios
        'preferencias.editar',    // modo con/sin precios y demás preferencias
        'catalogos.gestionar',    // marcas, modelos, servicios, refacciones, checklist, zonas
        'catalogos.alta_rapida',  // crear marca o modelo no verificado durante la recepción
        'clientes.gestionar',     // clientes y vehículos
        'recepcion.realizar',     // inspección de recepción
        'entrega.realizar',       // inspección de entrega
        'ordenes.ver_todas',      // el mecánico solo ve las suyas
        'ordenes.trabajar',       // diagnóstico, trabajos y refacciones
        'ordenes.reabrir',        // reabrir una orden cerrada
        'precios.ver',            // además depende de la preferencia usar_precios (ver User::puedeVerPrecios)
        'costos.ver',             // costo de compra y utilidad
        'qr.regenerar',
        'reportes.ver',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Recepcionista => 'Recepcionista',
            self::Mecanico => 'Mecánico',
        };
    }

    /** @return list<string> */
    public function permisos(): array
    {
        return match ($this) {
            self::Administrador => self::PERMISOS,
            self::Recepcionista => [
                'catalogos.alta_rapida',
                'clientes.gestionar',
                'recepcion.realizar',
                'entrega.realizar',
                'ordenes.ver_todas',
                'precios.ver',
            ],
            self::Mecanico => [
                'ordenes.trabajar',
            ],
        };
    }

    public function puede(string $permiso): bool
    {
        return in_array($permiso, $this->permisos(), true);
    }

    /** @return array<string, string> valor => etiqueta, para selects */
    public static function opciones(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $c) => $c->label(), self::cases()),
        );
    }
}
