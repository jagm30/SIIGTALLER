<?php

namespace App\Enums;

/**
 * Vista del diagrama del vehículo.
 */
enum VistaVehiculo: string
{
    case Frontal = 'frontal';
    case Izquierda = 'izquierda';
    case Derecha = 'derecha';
    case Trasera = 'trasera';
    case Superior = 'superior';
    case Interior = 'interior';

    public function label(): string
    {
        return match ($this) {
            self::Frontal => 'Frente',
            self::Izquierda => 'Lado izquierdo',
            self::Derecha => 'Lado derecho',
            self::Trasera => 'Trasera',
            self::Superior => 'Superior',
            self::Interior => 'Interior',
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
