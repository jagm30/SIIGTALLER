<?php

namespace App\Enums;

/**
 * Tipo de combustible del vehículo.
 */
enum Combustible: string
{
    case Gasolina = 'gasolina';
    case Diesel = 'diesel';
    case Hibrido = 'hibrido';
    case Electrico = 'electrico';
    case Gas = 'gas';

    public function label(): string
    {
        return match ($this) {
            self::Gasolina => 'Gasolina',
            self::Diesel => 'Diésel',
            self::Hibrido => 'Híbrido',
            self::Electrico => 'Eléctrico',
            self::Gas => 'Gas',
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
