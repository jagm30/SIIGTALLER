<?php

namespace App\Enums;

/**
 * Tipo de dato de una preferencia de operación.
 */
enum TipoPreferencia: string
{
    case Bool = 'bool';
    case Int = 'int';
    case Decimal = 'decimal';
    case String = 'string';
    case Text = 'text';
    case Json = 'json';

    public function label(): string
    {
        return match ($this) {
            self::Bool => 'Sí / No',
            self::Int => 'Entero',
            self::Decimal => 'Decimal',
            self::String => 'Texto corto',
            self::Text => 'Texto largo',
            self::Json => 'JSON',
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
