<?php

namespace App\Enums;

/**
 * Estado de un recordatorio de próximo servicio.
 */
enum EstadoRecordatorio: string
{
    case Pendiente = 'pendiente';
    case Realizado = 'realizado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Realizado => 'Realizado',
            self::Cancelado => 'Cancelado',
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
