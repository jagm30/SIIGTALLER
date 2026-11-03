<?php

namespace App\Enums;

/**
 * Unidad de medida de una refacción.
 */
enum UnidadRefaccion: string
{
    case Pieza = 'pieza';
    case Litro = 'litro';
    case Juego = 'juego';
    case Metro = 'metro';

    public function label(): string
    {
        return match ($this) {
            self::Pieza => 'Pieza',
            self::Litro => 'Litro',
            self::Juego => 'Juego',
            self::Metro => 'Metro',
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
