<?php

namespace App\Enums;

/**
 * Avance de un trabajo dentro de la orden.
 */
enum EstadoTrabajo: string
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Terminado = 'terminado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En proceso',
            self::Terminado => 'Terminado',
        };
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
