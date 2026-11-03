<?php

namespace App\Enums;

/**
 * Estado de la inspección.
 */
enum EstadoInspeccion: string
{
    case Borrador = 'borrador';
    case Completada = 'completada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Completada => 'Completada',
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
