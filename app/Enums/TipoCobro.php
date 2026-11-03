<?php

namespace App\Enums;

/**
 * Forma de cobrar un servicio.
 */
enum TipoCobro: string
{
    case Fijo = 'fijo';
    case PorHora = 'por_hora';

    public function label(): string
    {
        return match ($this) {
            self::Fijo => 'Precio fijo',
            self::PorHora => 'Por hora',
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
