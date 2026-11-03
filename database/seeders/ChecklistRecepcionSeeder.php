<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use Illuminate\Database\Seeder;

/**
 * Checklist de recepción aprobado por el taller (documento "Checklist de Recepción del Vehículo").
 * Columnas: grupo, nombre, tipo_respuesta, obligatorio, aplica_en, carrocerías (null = todas).
 */
class ChecklistRecepcionSeeder extends Seeder
{
    /** Todo menos motocicleta. */
    public const AUTOS = ['sedan', 'hatchback', 'suv', 'pickup', 'van', 'coupe', 'camion'];

    public function items(): array
    {
        $A = self::AUTOS;

        return [
            // Accesorios
            ['accesorios', 'Llaves entregadas (cantidad)', 'numero', true, 'ambas', null],
            ['accesorios', 'Llave con control o control de alarma', 'si_no', true, 'ambas', null],
            ['accesorios', 'Gato', 'si_no', true, 'ambas', $A],
            ['accesorios', 'Llave de cruz o de birlos', 'si_no', true, 'ambas', $A],
            ['accesorios', 'Llave de birlo de seguridad', 'si_no', false, 'ambas', $A],
            ['accesorios', 'Llanta de refacción', 'si_no', true, 'ambas', $A],
            ['accesorios', 'Triángulos de seguridad', 'si_no', false, 'ambas', $A],
            ['accesorios', 'Extintor', 'si_no', false, 'ambas', $A],
            ['accesorios', 'Cables pasacorriente', 'si_no', false, 'ambas', $A],
            ['accesorios', 'Herramienta', 'si_no', false, 'ambas', null],
            ['accesorios', 'Tapetes', 'si_no', true, 'ambas', $A],
            ['accesorios', 'Estéreo o pantalla', 'si_no', true, 'ambas', $A],
            ['accesorios', 'Antena', 'si_no', false, 'ambas', $A],
            ['accesorios', 'Tapón de gasolina', 'si_no', true, 'ambas', null],
            ['accesorios', 'Tapones de rin', 'si_no', false, 'ambas', $A],
            ['accesorios', 'Cubreequipaje o cortina de cajuela', 'si_no', false, 'ambas', ['suv', 'hatchback']],

            // Tablero: testigos encendidos al recibir
            ['tablero', 'El vehículo enciende', 'si_no', true, 'recepcion', null],
            ['tablero', 'Llegó en grúa', 'si_no', true, 'recepcion', null],
            ['tablero', 'Check engine', 'si_no', true, 'ambas', null],
            ['tablero', 'ABS', 'si_no', true, 'ambas', null],
            ['tablero', 'Bolsas de aire', 'si_no', true, 'ambas', $A],
            ['tablero', 'Presión de aceite', 'si_no', true, 'ambas', null],
            ['tablero', 'Batería o carga', 'si_no', true, 'ambas', null],
            ['tablero', 'Temperatura', 'si_no', true, 'ambas', null],
            ['tablero', 'Freno o freno de mano', 'si_no', true, 'ambas', null],
            ['tablero', 'Presión de llantas', 'si_no', false, 'ambas', $A],
            ['tablero', 'Otro testigo encendido', 'texto', false, 'ambas', null],

            // Funcionamiento
            ['funcionamiento', 'Luces delanteras (bajas y altas)', 'estado', true, 'ambas', null],
            ['funcionamiento', 'Luces traseras y de freno', 'estado', true, 'ambas', null],
            ['funcionamiento', 'Direccionales e intermitentes', 'estado', true, 'ambas', null],
            ['funcionamiento', 'Claxon', 'estado', false, 'ambas', null],
            ['funcionamiento', 'Limpiaparabrisas', 'estado', true, 'ambas', $A],
            ['funcionamiento', 'Aire acondicionado', 'estado', true, 'ambas', $A],
            ['funcionamiento', 'Elevadores de ventanas', 'estado', true, 'ambas', $A],
            ['funcionamiento', 'Seguros eléctricos', 'estado', false, 'ambas', $A],
            ['funcionamiento', 'Espejos eléctricos', 'estado', false, 'ambas', $A],
            ['funcionamiento', 'Estéreo funciona', 'estado', false, 'ambas', $A],

            // Exterior (los golpes concretos van en el diagrama de daños)
            ['exterior', 'Parabrisas', 'estado', true, 'ambas', $A],
            ['exterior', 'Cristales laterales', 'estado', true, 'ambas', $A],
            ['exterior', 'Medallón (cristal trasero)', 'estado', true, 'ambas', $A],
            ['exterior', 'Espejos laterales', 'estado', true, 'ambas', null],
            ['exterior', 'Faros', 'estado', true, 'ambas', null],
            ['exterior', 'Calaveras', 'estado', true, 'ambas', null],
            ['exterior', 'Rines', 'estado', true, 'ambas', null],
            ['exterior', 'Pintura en general', 'estado', true, 'ambas', null],

            // Interior
            ['interior', 'Asientos y vestiduras', 'estado', true, 'ambas', null],
            ['interior', 'Tablero', 'estado', true, 'ambas', null],
            ['interior', 'Volante', 'estado', false, 'ambas', $A],
            ['interior', 'Alfombra', 'estado', false, 'ambas', $A],
            ['interior', 'Cielo (toldo interior)', 'estado', false, 'ambas', $A],
            ['interior', 'Cinturones de seguridad', 'estado', false, 'ambas', $A],
            ['interior', 'Limpieza general', 'estado', false, 'ambas', null],

            // Llantas
            ['llantas', 'Delantera izquierda', 'estado', true, 'ambas', $A],
            ['llantas', 'Delantera derecha', 'estado', true, 'ambas', $A],
            ['llantas', 'Trasera izquierda', 'estado', true, 'ambas', $A],
            ['llantas', 'Trasera derecha', 'estado', true, 'ambas', $A],
            ['llantas', 'Refacción', 'estado', false, 'ambas', $A],
            ['llantas', 'Delantera', 'estado', true, 'ambas', ['moto']],
            ['llantas', 'Trasera', 'estado', true, 'ambas', ['moto']],

            // Niveles del motor (opcionales: requieren abrir el cofre)
            ['motor', 'Aceite de motor', 'nivel', false, 'ambas', null],
            ['motor', 'Anticongelante', 'nivel', false, 'ambas', $A],
            ['motor', 'Líquido de frenos', 'nivel', false, 'ambas', null],
            ['motor', 'Líquido limpiaparabrisas', 'nivel', false, 'ambas', $A],

            // Documentos
            ['documentos', 'Tarjeta de circulación', 'si_no', true, 'ambas', null],
            ['documentos', 'Póliza de seguro', 'si_no', false, 'ambas', null],
            ['documentos', 'Holograma o constancia de verificación', 'si_no', false, 'ambas', null],
            ['documentos', 'Manual del propietario', 'si_no', false, 'ambas', null],
        ];
    }

    public function run(): void
    {
        $orden = [];

        foreach ($this->items() as [$grupo, $nombre, $tipo, $obligatorio, $aplicaEn, $carrocerias]) {
            $orden[$grupo] = ($orden[$grupo] ?? 0) + 10;

            ChecklistItem::withTrashed()->firstOrCreate(
                ['grupo' => $grupo, 'nombre' => $nombre],
                [
                    'tipo_respuesta' => $tipo,
                    'obligatorio' => $obligatorio,
                    'aplica_en' => $aplicaEn,
                    'tipos_carroceria' => $carrocerias,
                    'orden' => $orden[$grupo],
                    'activo' => true,
                ],
            );
        }
    }
}
