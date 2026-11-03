<?php

namespace App\Enums;

/**
 * Severidad del daño.
 */
enum SeveridadDanio: string
{
    case Leve = 'leve';
    case Moderado = 'moderado';
    case Severo = 'severo';

    public function label(): string
    {
        return match ($this) {
            self::Leve => 'Leve',
            self::Moderado => 'Moderado',
            self::Severo => 'Severo',
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
