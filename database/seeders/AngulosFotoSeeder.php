<?php

namespace Database\Seeders;

use App\Models\AnguloFoto;
use Illuminate\Database\Seeder;

/** Fotos por ángulo aprobadas por el taller. */
class AngulosFotoSeeder extends Seeder
{
    public function run(): void
    {
        $A = ChecklistRecepcionSeeder::AUTOS;

        $angulos = [
            // clave, nombre, qué debe verse, obligatoria, carrocerías
            ['frente', 'Frente', 'Vehículo completo de frente, con placa', true, null],
            ['trasera', 'Trasera', 'Vehículo completo por detrás, con placa', true, null],
            ['lateral_izq', 'Lateral izquierdo', 'Costado completo del conductor', true, null],
            ['lateral_der', 'Lateral derecho', 'Costado completo del copiloto', true, null],
            ['odometro', 'Odómetro', 'Kilometraje legible', true, null],
            ['tablero', 'Tablero encendido', 'Testigos y nivel de gasolina', true, null],
            ['interior_delantero', 'Interior delantero', 'Asientos, tablero y consola', true, $A],
            ['interior_trasero', 'Interior trasero', 'Asientos traseros', false, $A],
            ['cajuela', 'Cajuela o batea', 'Interior de cajuela con accesorios', false, $A],
            ['motor', 'Motor', 'Compartimiento del motor', false, null],
            ['vin', 'Número de serie (VIN)', 'Placa del VIN en tablero o puerta', false, null],
            ['refaccion', 'Llanta de refacción', 'Refacción en su lugar', false, $A],
        ];

        foreach ($angulos as $i => [$clave, $nombre, $descripcion, $obligatorio, $carrocerias]) {
            AnguloFoto::firstOrCreate(
                ['clave' => $clave],
                [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'obligatorio' => $obligatorio,
                    'tipos_carroceria' => $carrocerias,
                    'orden' => ($i + 1) * 10,
                    'activo' => true,
                ],
            );
        }
    }
}
