<?php

namespace App\Enums;

/**
 * Tipo de carrocería; define el diagrama de daños y qué puntos aplican.
 */
enum TipoCarroceria: string
{
    case Sedan = 'sedan';
    case Hatchback = 'hatchback';
    case Suv = 'suv';
    case Pickup = 'pickup';
    case Van = 'van';
    case Coupe = 'coupe';
    case Moto = 'moto';
    case Camion = 'camion';

    public function label(): string
    {
        return match ($this) {
            self::Sedan => 'Sedán',
            self::Hatchback => 'Hatchback',
            self::Suv => 'SUV',
            self::Pickup => 'Pickup',
            self::Van => 'Van',
            self::Coupe => 'Coupé',
            self::Moto => 'Motocicleta',
            self::Camion => 'Camión',
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
