<?php

namespace App\Enums;

/**
 * Forma de responder un punto del checklist.
 */
enum TipoRespuesta: string
{
    case SiNo = 'si_no';
    case Estado = 'estado';
    case Nivel = 'nivel';
    case Numero = 'numero';
    case Texto = 'texto';

    public function label(): string
    {
        return match ($this) {
            self::SiNo => 'Sí / No',
            self::Estado => 'Estado',
            self::Nivel => 'Nivel',
            self::Numero => 'Número',
            self::Texto => 'Texto',
        };
    }

    /**
     * Respuestas fijas de este tipo de punto (valor => etiqueta). Número y texto se capturan libres.
     *
     * @return array<string, string>
     */
    public function valores(): array
    {
        return match ($this) {
            self::SiNo => ['si' => 'Sí', 'no' => 'No'],
            self::Estado => ['bueno' => 'Bueno', 'regular' => 'Regular', 'malo' => 'Malo', 'no_aplica' => 'N/A'],
            self::Nivel => ['lleno' => 'Lleno', 'medio' => 'Medio', 'bajo' => 'Bajo'],
            self::Numero, self::Texto => [],
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
