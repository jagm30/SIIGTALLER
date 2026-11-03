<?php

namespace App\Enums;

/**
 * Momento de la inspección.
 */
enum TipoInspeccion: string
{
    case Recepcion = 'recepcion';
    case Entrega = 'entrega';

    public function label(): string
    {
        return match ($this) {
            self::Recepcion => 'Recepción',
            self::Entrega => 'Entrega',
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
