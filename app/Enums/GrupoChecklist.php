<?php

namespace App\Enums;

/**
 * Grupo del checklist de recepción.
 */
enum GrupoChecklist: string
{
    case Accesorios = 'accesorios';
    case Tablero = 'tablero';
    case Funcionamiento = 'funcionamiento';
    case Exterior = 'exterior';
    case Interior = 'interior';
    case Llantas = 'llantas';
    case Motor = 'motor';
    case Documentos = 'documentos';

    public function label(): string
    {
        return match ($this) {
            self::Accesorios => 'Accesorios',
            self::Tablero => 'Tablero',
            self::Funcionamiento => 'Funcionamiento',
            self::Exterior => 'Exterior',
            self::Interior => 'Interior',
            self::Llantas => 'Llantas',
            self::Motor => 'Niveles del motor',
            self::Documentos => 'Documentos',
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
