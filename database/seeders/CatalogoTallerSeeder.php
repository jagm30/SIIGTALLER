<?php

namespace Database\Seeders;

use App\Enums\TipoCobro;
use App\Enums\UnidadRefaccion;
use App\Models\Refaccion;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

/**
 * Catálogo real de servicios y refacciones del taller.
 * Se puede ejecutar varias veces sin crear duplicados.
 *
 *   php artisan db:seed --class=CatalogoTallerSeeder
 */
class CatalogoTallerSeeder extends Seeder
{
    /**
     * Cada entrada define un servicio y las refacciones que típicamente requiere.
     * Las refacciones se crean (o se reutilizan si ya existen) y se vinculan
     * al servicio mediante la tabla pivote servicio_refacciones.
     *
     * Formato de refacción: ['descripcion', 'categoria', UnidadRefaccion, cantidad]
     */
    private function catalogo(): array
    {
        $p = UnidadRefaccion::Pieza;
        $l = UnidadRefaccion::Litro;
        $j = UnidadRefaccion::Juego;

        return [

            // ── 1. AFINACIÓN ──────────────────────────────────────────────────
            [
                'nombre'    => 'Servicio de Afinación',
                'categoria' => 'Motor',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Aceite de motor',   'Motor',   $l, 1],
                    ['Filtro de aire',    'Motor',   $p, 1],
                    ['Filtro de aceite',  'Motor',   $p, 1],
                    ['Bujías',            'Motor',   $j, 1],
                ],
            ],

            // ── 2. FRENOS ─────────────────────────────────────────────────────
            [
                'nombre'    => 'Servicio de Frenos',
                'categoria' => 'Frenos',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Balatas delanteras y traseras',  'Frenos', $j, 1],
                    ['Discos delanteros y traseros',   'Frenos', $j, 1],
                    ['Cilindros de frenos',            'Frenos', $p, 1],
                    ['Pistones de calipers',           'Frenos', $j, 1],
                    ['Herrajes de frenos',             'Frenos', $j, 1],
                    ['Mordazas de frenos',             'Frenos', $p, 1],
                    ['Chicote de frenos',              'Frenos', $p, 1],
                    ['Sensor ABS',                     'Frenos', $p, 1],
                    ['Tubo de frenos',                 'Frenos', $p, 1],
                    ['Válvula booster',                'Frenos', $p, 1],
                    ['Bomba de frenos',                'Frenos', $p, 1],
                    ['Booster de frenos',              'Frenos', $p, 1],
                    ['Balatas de freno de mano',       'Frenos', $j, 1],
                ],
            ],

            // ── 3. SUSPENSIÓN ─────────────────────────────────────────────────
            [
                'nombre'    => 'Servicio de Suspensión',
                'categoria' => 'Suspensión',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Amortiguadores delanteros y traseros',           'Suspensión', $j, 1],
                    ['Tornillos estabilizadores delanteros y traseros', 'Suspensión', $j, 1],
                    ['Terminales de dirección',                        'Suspensión', $p, 1],
                    ['Bieletas',                                       'Suspensión', $p, 1],
                    ['Rótulas',                                        'Suspensión', $j, 1],
                    ['Bujes de suspensión',                            'Suspensión', $j, 1],
                    ['Horquillas inferiores',                          'Suspensión', $p, 1],
                    ['Horquillas superiores',                          'Suspensión', $p, 1],
                    ['Bujes de puente',                                'Suspensión', $j, 1],
                    ['Tirantes traseros superiores',                   'Suspensión', $p, 1],
                    ['Tirantes traseros inferiores',                   'Suspensión', $p, 1],
                    ['Horquillas laterales',                           'Suspensión', $p, 1],
                    ['Tirantes curvos traseros',                       'Suspensión', $p, 1],
                    ['Barra de dirección',                             'Dirección',  $p, 1],
                    ['Gomas de barra estabilizadora delantera y trasera', 'Suspensión', $j, 1],
                    ['Bases de amortiguador delanteros y traseros',    'Suspensión', $j, 1],
                    ['Resortes',                                       'Suspensión', $j, 1],
                ],
            ],

            // ── 4. SOPORTES ───────────────────────────────────────────────────
            [
                'nombre'    => 'Cambio de Soportes de Motor',
                'categoria' => 'Motor',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Soporte de motor izquierdo', 'Motor', $p, 1],
                    ['Soporte de motor derecho',   'Motor', $p, 1],
                    ['Soporte de motor frontal',   'Motor', $p, 1],
                    ['Soporte de torsión',         'Motor', $p, 1],
                    ['Soporte tipo hueso',         'Motor', $p, 1],
                    ['Soporte tipo dona',          'Motor', $p, 1],
                ],
            ],

            // ── 5. MUELLES ────────────────────────────────────────────────────
            [
                'nombre'    => 'Servicio de Muelles',
                'categoria' => 'Suspensión',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Muelle maestra',         'Suspensión', $p, 1],
                    ['Muelle 2, 3, 4',         'Suspensión', $p, 1],
                    ['Tornillo de centro',     'Suspensión', $p, 1],
                    ['Bujes de muelles',       'Suspensión', $j, 1],
                    ['Abrazaderas de muelles', 'Suspensión', $j, 1],
                ],
            ],

            // ── 6. ESCAPE ─────────────────────────────────────────────────────
            [
                'nombre'    => 'Reparación de Escape',
                'categoria' => 'Escape',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Tubo de escape',       'Escape', $p, 1],
                    ['Catalizador',          'Escape', $p, 1],
                    ['Curva de escape',      'Escape', $p, 1],
                    ['Coples de escape',     'Escape', $j, 1],
                    ['Múltiple de escape',   'Escape', $p, 1],
                    ['Resonadores de escape','Escape', $p, 1],
                    ['Silenciador',          'Escape', $p, 1],
                    ['Soportes de escape',   'Escape', $j, 1],
                ],
            ],

            // ── 7. SISTEMA ELÉCTRICO ──────────────────────────────────────────
            [
                'nombre'    => 'Reparación de Sistema Eléctrico',
                'categoria' => 'Eléctrico',
                'cobro'     => TipoCobro::PorHora,
                'refacciones' => [
                    ['Alternador',                    'Eléctrico', $p, 1],
                    ['Armadura de marcha',            'Eléctrico', $p, 1],
                    ['Motor de arranque (marcha)',     'Eléctrico', $p, 1],
                    ['Arnés eléctrico',               'Eléctrico', $p, 1],
                    ['Batería',                       'Eléctrico', $p, 1],
                    ['Bendix',                        'Eléctrico', $p, 1],
                    ['Bobinas de encendido',          'Eléctrico', $p, 1],
                    ['Bomba de gasolina',             'Combustible',$p, 1],
                    ['Bombilla de luces',             'Eléctrico', $p, 1],
                    ['Calaveras',                     'Eléctrico', $p, 1],
                    ['Campana de marcha',             'Eléctrico', $p, 1],
                    ['Computadora (ECU)',              'Eléctrico', $p, 1],
                    ['Focos delanteros y traseros',   'Eléctrico', $j, 1],
                    ['Motoventilador',                'Eléctrico', $p, 1],
                    ['Motoventilador de radiador',    'Eléctrico', $p, 1],
                    ['Palanca de velocidades',        'Transmisión',$p, 1],
                    ['Plumas limpiaparabrisas',       'Eléctrico', $j, 1],
                    ['Polea de alternador',           'Eléctrico', $p, 1],
                    ['Polea loca',                    'Motor',     $p, 1],
                    ['Regulador de voltaje',          'Eléctrico', $p, 1],
                    ['Sensor de árbol de levas',      'Eléctrico', $p, 1],
                    ['Sensor de cigüeñal',            'Eléctrico', $p, 1],
                    ['Sensor de detonación',          'Eléctrico', $p, 1],
                    ['Sensor de oxígeno',             'Eléctrico', $p, 1],
                    ['Sensor de presión de aceite',   'Eléctrico', $p, 1],
                    ['Sensor de neumáticos (TPMS)',   'Eléctrico', $p, 1],
                    ['Sensor de presión de gasolina', 'Eléctrico', $p, 1],
                    ['Sensor de temperatura',         'Eléctrico', $p, 1],
                    ['Sensor de velocidad',           'Eléctrico', $p, 1],
                    ['Sensor MAP',                    'Eléctrico', $p, 1],
                    ['Sensor MAF',                    'Eléctrico', $p, 1],
                    ['Sensor MDS',                    'Eléctrico', $p, 1],
                    ['Tapón de gasolina',             'Combustible',$p, 1],
                    ['Luces delanteras',              'Eléctrico', $j, 1],
                ],
            ],

            // ── 8. CLIMA ──────────────────────────────────────────────────────
            [
                'nombre'    => 'Reparación de Clima',
                'categoria' => 'Clima',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Compresor de clima',          'Clima', $p, 1],
                    ['Condensador de clima',        'Clima', $p, 1],
                    ['Embrague de compresor',       'Clima', $p, 1],
                    ['Evaporador',                  'Clima', $p, 1],
                    ['Filtro de cabina',            'Clima', $p, 1],
                    ['Manguera de alta presión de clima', 'Clima', $p, 1],
                    ['Módulo de control de clima',  'Clima', $p, 1],
                    ['Radiador de clima',           'Clima', $p, 1],
                    ['Sensor de presión de clima',  'Clima', $p, 1],
                    ['Válvula de expansión',        'Clima', $p, 1],
                ],
            ],

            // ── 9. TRANSMISIÓN AUTOMÁTICA ─────────────────────────────────────
            [
                'nombre'    => 'Reparación de Transmisión Automática',
                'categoria' => 'Transmisión',
                'cobro'     => TipoCobro::PorHora,
                'refacciones' => [
                    ['Asiento de válvulas de transmisión', 'Transmisión', $j, 1],
                    ['Bomba de transmisión',               'Transmisión', $p, 1],
                    ['Chicote de cambios',                 'Transmisión', $p, 1],
                    ['Cuerpo de válvulas',                 'Transmisión', $p, 1],
                    ['Cuerpo de solenoides',               'Transmisión', $p, 1],
                    ['Engrane de bomba de transmisión',    'Transmisión', $p, 1],
                    ['Husillo de transmisión y bronces',   'Transmisión', $p, 1],
                    ['Kit de transmisión automática',      'Transmisión', $j, 1],
                    ['Módulo de cambios',                  'Transmisión', $p, 1],
                    ['Pistones de aluminio de transmisión','Transmisión', $j, 1],
                    ['Planetario delantero y trasero',     'Transmisión', $j, 1],
                    ['Retén de transmisión',               'Transmisión', $j, 1],
                    ['Seguros de transmisión',             'Transmisión', $j, 1],
                    ['Sensor gobernador',                  'Transmisión', $p, 1],
                    ['Tambor de avance',                   'Transmisión', $p, 1],
                    ['Tambor de reversa',                  'Transmisión', $p, 1],
                    ['Convertidor de transmisión automática','Transmisión',$p, 1],
                ],
            ],

            // ── 10. DIRECCIÓN ─────────────────────────────────────────────────
            [
                'nombre'    => 'Servicio de Dirección',
                'categoria' => 'Dirección',
                'cobro'     => TipoCobro::Fijo,
                'refacciones' => [
                    ['Caja de dirección asistida',    'Dirección', $p, 1],
                    ['Caja de dirección hidráulica',  'Dirección', $p, 1],
                    ['Bomba de dirección',            'Dirección', $p, 1],
                    ['Cremallera de dirección',       'Dirección', $p, 1],
                    ['Cruceta de dirección',          'Dirección', $p, 1],
                    ['Sin fin de dirección',          'Dirección', $p, 1],
                    ['Módulo de dirección asistida',  'Dirección', $p, 1],
                    ['Mecanismo de dirección',        'Dirección', $p, 1],
                ],
            ],

            // ── 11. DIFERENCIAL ───────────────────────────────────────────────
            [
                'nombre'    => 'Reparación de Diferencial',
                'categoria' => 'Transmisión',
                'cobro'     => TipoCobro::PorHora,
                'refacciones' => [
                    ['Balero de cola',                              'Diferencial', $p, 1],
                    ['Balero de piñón',                            'Diferencial', $p, 1],
                    ['Baleros de caja de satélite',                'Diferencial', $j, 1],
                    ['Baleros de funda de diferencial',            'Diferencial', $j, 1],
                    ['Baleros de piñón',                           'Diferencial', $j, 1],
                    ['Caja de satélite',                           'Diferencial', $p, 1],
                    ['Caja de dirección asistida (diferencial)',   'Diferencial', $p, 1],
                    ['Corona y piñón',                             'Diferencial', $j, 1],
                    ['Crucetas de flecha cardán',                  'Diferencial', $j, 1],
                    ['Diferencial completo',                       'Diferencial', $p, 1],
                    ['Flecha cardán',                              'Diferencial', $p, 1],
                    ['Flechas laterales delanteras 4x4',           'Diferencial', $j, 1],
                    ['Flechas laterales',                          'Diferencial', $j, 1],
                    ['Funda de diferencial',                       'Diferencial', $p, 1],
                    ['Retén de funda de diferencial',              'Diferencial', $p, 1],
                    ['Retén de funda 4x4',                         'Diferencial', $p, 1],
                    ['Retén de piñón',                             'Diferencial', $p, 1],
                    ['Retén de yugo',                              'Diferencial', $p, 1],
                    ['Retenes de flechas laterales traseras',      'Diferencial', $j, 1],
                    ['Satélite, boca flecha y perno',              'Diferencial', $j, 1],
                    ['Yugo de diferencial',                        'Diferencial', $p, 1],
                ],
            ],

            // ── 12. MOTOR ─────────────────────────────────────────────────────
            [
                'nombre'    => 'Reparación de Motor',
                'categoria' => 'Motor',
                'cobro'     => TipoCobro::PorHora,
                'refacciones' => [
                    ['Filtro de aceite',                        'Motor',      $p, 1],
                    ['Anillos de motor',                        'Motor',      $j, 1],
                    ['Árbol de levas',                          'Motor',      $p, 1],
                    ['Sellos de agua de monobloc',              'Motor',      $j, 1],
                    ['Inyectores de gasolina',                  'Combustible',$j, 1],
                    ['Arillo cremallera de volante motriz',     'Motor',      $p, 1],
                    ['Balancines',                              'Motor',      $j, 1],
                    ['Banda de distribución',                   'Motor',      $p, 1],
                    ['Barra estabilizadora',                    'Suspensión', $p, 1],
                    ['Bielas de motor',                         'Motor',      $j, 1],
                    ['Birlos',                                  'Motor',      $j, 1],
                    ['Bomba de agua',                           'Motor',      $p, 1],
                    ['Bomba de aceite',                         'Motor',      $p, 1],
                    ['Bujías',                                  'Motor',      $j, 1],
                    ['Bulbo de aceite',                         'Motor',      $p, 1],
                    ['Buzos hidráulicos',                       'Motor',      $j, 1],
                    ['Cabeza de motor',                         'Motor',      $p, 1],
                    ['Camisas de motor',                        'Motor',      $j, 1],
                    ['Cárter',                                  'Motor',      $p, 1],
                    ['Cigüeñal',                                'Motor',      $p, 1],
                    ['Cucharillas de balancines',               'Motor',      $j, 1],
                    ['Depósito de anticongelante',              'Motor',      $p, 1],
                    ['Fan clutch',                              'Motor',      $p, 1],
                    ['Filtro de aire',                          'Motor',      $p, 1],
                    ['Filtro de gasolina regulador',            'Combustible',$p, 1],
                    ['Filtro de tanque de gasolina',            'Combustible',$p, 1],
                    ['Guías de válvulas',                       'Motor',      $j, 1],
                    ['Junta de cárter',                         'Motor',      $p, 1],
                    ['Juntas de cabezas',                       'Motor',      $j, 1],
                    ['Juntas de motor',                         'Motor',      $j, 1],
                    ['Juntas de múltiple de admisión',          'Motor',      $j, 1],
                    ['Juntas de múltiple de escape',            'Motor',      $j, 1],
                    ['Juntas de plenum',                        'Motor',      $j, 1],
                    ['Juntas de tapa de puntería',              'Motor',      $j, 1],
                    ['Kit de distribución',                     'Motor',      $j, 1],
                    ['Manguera de alta presión hidráulica',     'Motor',      $p, 1],
                    ['Manguera de baja presión hidráulica',     'Motor',      $p, 1],
                    ['Manguera de llenado de tanque',           'Combustible',$p, 1],
                    ['Manguera de purificador de aire',         'Motor',      $p, 1],
                    ['Manguera inferior de radiador',           'Motor',      $p, 1],
                    ['Manguera superior de radiador',           'Motor',      $p, 1],
                    ['Metal de árbol',                          'Motor',      $j, 1],
                    ['Metal de biela',                          'Motor',      $j, 1],
                    ['Metal de centro',                         'Motor',      $j, 1],
                    ['Pistones de motor',                       'Motor',      $j, 1],
                    ['Polea auxiliar',                          'Motor',      $p, 1],
                    ['Polea de cigüeñal',                       'Motor',      $p, 1],
                    ['Polea tensora',                           'Motor',      $p, 1],
                    ['Radiador',                                'Motor',      $p, 1],
                    ['Retén de cigüeñal delantero',             'Motor',      $p, 1],
                    ['Retén de cigüeñal trasero',               'Motor',      $p, 1],
                    ['Riel de inyectores',                      'Combustible',$p, 1],
                    ['Sellos de válvulas',                      'Motor',      $j, 1],
                    ['Soporte de caja de velocidades',          'Transmisión',$p, 1],
                    ['Soporte de motor',                        'Motor',      $p, 1],
                    ['Tapa de distribución',                    'Motor',      $p, 1],
                    ['Tapón de depósito de anticongelante',     'Motor',      $p, 1],
                    ['Tapón de radiador',                       'Motor',      $p, 1],
                    ['Termostato',                              'Motor',      $p, 1],
                    ['Toma de agua',                            'Motor',      $p, 1],
                    ['Tornillos de cabeza de motor',            'Motor',      $j, 1],
                    ['Tuercas',                                 'Motor',      $j, 1],
                    ['Válvula EGR',                             'Motor',      $p, 1],
                    ['Válvula de purga de canister',            'Motor',      $p, 1],
                    ['Válvula PCV',                             'Motor',      $p, 1],
                    ['Válvulas de admisión y escape',           'Motor',      $j, 1],
                    ['Varillas de balancines',                  'Motor',      $j, 1],
                ],
            ],

        ];
    }

    public function run(): void
    {
        foreach ($this->catalogo() as $data) {
            $servicio = Servicio::firstOrCreate(
                ['nombre' => $data['nombre']],
                [
                    'categoria'  => $data['categoria'],
                    'tipo_cobro' => $data['cobro'],
                    'activo'     => true,
                ]
            );

            $pivotIds = [];
            foreach ($data['refacciones'] as [$descripcion, $categoria, $unidad, $cantidad]) {
                $refaccion = Refaccion::firstOrCreate(
                    ['descripcion' => $descripcion],
                    [
                        'categoria' => $categoria,
                        'unidad'    => $unidad,
                        'activo'    => true,
                    ]
                );
                $pivotIds[$refaccion->id] = ['cantidad' => $cantidad];
            }

            $servicio->refaccionesSugeridas()->syncWithoutDetaching($pivotIds);
        }
    }
}
