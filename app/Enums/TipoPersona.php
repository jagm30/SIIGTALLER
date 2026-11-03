<?php

namespace App\Enums;

/**
 * Tipo de persona fiscal del cliente.
 */
enum TipoPersona: string
{
    case Fisica = 'fisica';
    case Moral = 'moral';

    public function label(): string
    {
        return match ($this) {
            self::Fisica => 'Persona física',
            self::Moral => 'Persona moral',
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
