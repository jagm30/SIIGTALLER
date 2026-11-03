<?php

namespace App\Enums;

/**
 * Tipo de archivo adjunto.
 */
enum TipoAdjunto: string
{
    case Foto = 'foto';
    case Firma = 'firma';
    case Documento = 'documento';

    public function label(): string
    {
        return match ($this) {
            self::Foto => 'Foto',
            self::Firma => 'Firma',
            self::Documento => 'Documento',
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
