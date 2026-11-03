<?php

namespace App\Enums;

/**
 * Tipo de daño visible.
 */
enum TipoDanio: string
{
    case Rayon = 'rayon';
    case Golpe = 'golpe';
    case Abolladura = 'abolladura';
    case Roto = 'roto';
    case Estrellado = 'estrellado';
    case Faltante = 'faltante';
    case Despintado = 'despintado';
    case Oxido = 'oxido';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Rayon => 'Rayón',
            self::Golpe => 'Golpe',
            self::Abolladura => 'Abolladura',
            self::Roto => 'Roto',
            self::Estrellado => 'Estrellado',
            self::Faltante => 'Faltante',
            self::Despintado => 'Despintado',
            self::Oxido => 'Óxido',
            self::Otro => 'Otro',
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
