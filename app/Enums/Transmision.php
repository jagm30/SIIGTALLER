<?php

namespace App\Enums;

/**
 * Tipo de transmisión del vehículo.
 */
enum Transmision: string
{
    case Manual = 'manual';
    case Automatica = 'automatica';
    case Cvt = 'cvt';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Automatica => 'Automática',
            self::Cvt => 'CVT',
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
