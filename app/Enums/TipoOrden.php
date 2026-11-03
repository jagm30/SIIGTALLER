<?php

namespace App\Enums;

/**
 * Tipo de orden de servicio.
 */
enum TipoOrden: string
{
    case Servicio = 'servicio';
    case Reparacion = 'reparacion';
    case Diagnostico = 'diagnostico';
    case Garantia = 'garantia';

    public function label(): string
    {
        return match ($this) {
            self::Servicio => 'Servicio',
            self::Reparacion => 'Reparación',
            self::Diagnostico => 'Diagnóstico',
            self::Garantia => 'Garantía',
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
