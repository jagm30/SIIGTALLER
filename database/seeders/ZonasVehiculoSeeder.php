<?php

namespace Database\Seeders;

use App\Models\ZonaVehiculo;
use Illuminate\Database\Seeder;

/** Zonas del diagrama de carrocería aprobadas por el taller. */
class ZonasVehiculoSeeder extends Seeder
{
    public function zonas(): array
    {
        $autos = ChecklistRecepcionSeeder::AUTOS;
        $sinPickup = array_values(array_diff($autos, ['pickup']));
        $conPuertaTrasera = array_values(array_diff($autos, ['van', 'coupe']));
        $moto = ['moto'];

        $zonas = [
            // Frente
            ['defensa_delantera', 'Defensa delantera', 'frontal', $autos],
            ['parrilla', 'Parrilla', 'frontal', $autos],
            ['cofre', 'Cofre', 'frontal', $autos],
            ['faro_izq', 'Faro izquierdo', 'frontal', $autos],
            ['faro_der', 'Faro derecho', 'frontal', $autos],
            ['parabrisas', 'Parabrisas', 'frontal', $autos],
            ['fascia_inferior', 'Fascia inferior', 'frontal', $autos],
        ];

        // Lado izquierdo y derecho
        foreach (['izq' => ['izquierda', 'izquierdo', 'izquierda'], 'der' => ['derecha', 'derecho', 'derecha']] as $s => [$vista, $m, $f]) {
            array_push($zonas,
                ["salpicadera_del_{$s}", "Salpicadera delantera {$f}", $vista, $autos],
                ["puerta_del_{$s}", "Puerta delantera {$f}", $vista, $autos],
                ["puerta_tras_{$s}", "Puerta trasera {$f}", $vista, $conPuertaTrasera],
                ["puerta_corrediza_{$s}", "Puerta corrediza {$f}", $vista, ['van']],
                ["costado_tras_{$s}", "Costado trasero {$m}", $vista, $autos],
                ["espejo_{$s}", "Espejo {$m}", $vista, $autos],
                ["estribo_{$s}", "Estribo {$m}", $vista, $autos],
                ["rin_del_{$s}", "Rin delantero {$m}", $vista, $autos],
                ["rin_tras_{$s}", "Rin trasero {$m}", $vista, $autos],
            );
        }

        array_push($zonas,
            // Trasera
            ['defensa_trasera', 'Defensa trasera', 'trasera', $autos],
            ['cajuela_porton', 'Cajuela o portón', 'trasera', $sinPickup],
            ['calavera_izq', 'Calavera izquierda', 'trasera', $autos],
            ['calavera_der', 'Calavera derecha', 'trasera', $autos],
            ['medallon', 'Medallón', 'trasera', $sinPickup],
            ['area_placa', 'Área de placa', 'trasera', $autos],
            ['compuerta_batea', 'Compuerta de batea', 'trasera', ['pickup']],
            ['cabina_trasera', 'Cabina trasera', 'trasera', ['pickup']],
            // Superior
            ['toldo', 'Toldo', 'superior', $autos],
            ['quemacocos', 'Quemacocos', 'superior', $autos],
            ['batea', 'Batea', 'superior', ['pickup']],
            // Interior
            ['asientos_delanteros', 'Asientos delanteros', 'interior', $autos],
            ['asientos_traseros', 'Asientos traseros', 'interior', $autos],
            ['tablero_interior', 'Tablero', 'interior', $autos],
            ['volante', 'Volante', 'interior', $autos],
            ['alfombra', 'Alfombra', 'interior', $autos],
            ['cielo', 'Cielo', 'interior', $autos],
            ['puertas_interior', 'Puertas por dentro', 'interior', $autos],
            // Motocicleta
            ['moto_tanque', 'Tanque', 'superior', $moto],
            ['moto_asiento', 'Asiento', 'superior', $moto],
            ['moto_carenado_izq', 'Carenado izquierdo', 'izquierda', $moto],
            ['moto_carenado_der', 'Carenado derecho', 'derecha', $moto],
            ['moto_manubrio', 'Manubrio', 'frontal', $moto],
            ['moto_espejos', 'Espejos', 'frontal', $moto],
            ['moto_salpicadera_del', 'Salpicadera delantera', 'frontal', $moto],
            ['moto_salpicadera_tras', 'Salpicadera trasera', 'trasera', $moto],
            ['moto_escape', 'Escape', 'derecha', $moto],
            ['moto_rin_del', 'Rin delantero', 'izquierda', $moto],
            ['moto_rin_tras', 'Rin trasero', 'izquierda', $moto],
        );

        return $zonas;
    }

    public function run(): void
    {
        foreach ($this->zonas() as $i => [$clave, $nombre, $vista, $carrocerias]) {
            ZonaVehiculo::firstOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'vista' => $vista, 'tipos_carroceria' => $carrocerias, 'orden' => ($i + 1) * 10],
            );
        }
    }
}
