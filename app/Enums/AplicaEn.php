<?php

namespace App\Enums;

/**
 * En qué inspección se revisa un punto.
 */
enum AplicaEn: string
{
    case Recepcion = 'recepcion';
    case Entrega = 'entrega';
    case Ambas = 'ambas';

    public function label(): string
    {
        return match ($this) {
            self::Recepcion => 'Recepción',
            self::Entrega => 'Entrega',
            self::Ambas => 'Ambas',
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
