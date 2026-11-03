<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\ModeloVehiculo;
use Illuminate\Database\Seeder;

/**
 * Marcas y modelos comunes en México, con su tipo de carrocería
 * (define el diagrama de daños en la recepción). El taller puede agregar más.
 */
class MarcasYModelosSeeder extends Seeder
{
    public const CATALOGO = [
        'Nissan' => ['Versa' => 'sedan', 'Sentra' => 'sedan', 'Tsuru' => 'sedan', 'March' => 'hatchback',
            'Kicks' => 'suv', 'X-Trail' => 'suv', 'NP300' => 'pickup', 'Frontier' => 'pickup', 'Urvan' => 'van'],
        'Chevrolet' => ['Aveo' => 'sedan', 'Onix' => 'sedan', 'Spark' => 'hatchback', 'Beat' => 'hatchback',
            'Trax' => 'suv', 'Tracker' => 'suv', 'Equinox' => 'suv', 'Captiva' => 'suv',
            'Tornado' => 'pickup', 'S10' => 'pickup', 'Silverado' => 'pickup'],
        'Volkswagen' => ['Jetta' => 'sedan', 'Vento' => 'sedan', 'Virtus' => 'sedan', 'Polo' => 'hatchback',
            'Gol' => 'hatchback', 'T-Cross' => 'suv', 'Taos' => 'suv', 'Tiguan' => 'suv',
            'Saveiro' => 'pickup', 'Amarok' => 'pickup'],
        'Toyota' => ['Corolla' => 'sedan', 'Yaris' => 'sedan', 'Camry' => 'sedan', 'RAV4' => 'suv',
            'Hilux' => 'pickup', 'Tacoma' => 'pickup', 'Avanza' => 'van', 'Hiace' => 'van'],
        'Honda' => ['City' => 'sedan', 'Civic' => 'sedan', 'Accord' => 'sedan', 'HR-V' => 'suv',
            'BR-V' => 'suv', 'CR-V' => 'suv'],
        'Kia' => ['Rio' => 'sedan', 'Forte' => 'sedan', 'Soul' => 'hatchback', 'Seltos' => 'suv', 'Sportage' => 'suv'],
        'Mazda' => ['Mazda 2' => 'hatchback', 'Mazda 3' => 'sedan', 'CX-3' => 'suv', 'CX-30' => 'suv', 'CX-5' => 'suv'],
        'Ford' => ['Figo' => 'sedan', 'Escape' => 'suv', 'Territory' => 'suv', 'Explorer' => 'suv',
            'Ranger' => 'pickup', 'Maverick' => 'pickup', 'Lobo' => 'pickup', 'Transit' => 'van'],
        'Hyundai' => ['Grand i10' => 'hatchback', 'Accent' => 'sedan', 'Creta' => 'suv', 'Tucson' => 'suv'],
        'Renault' => ['Kwid' => 'hatchback', 'Stepway' => 'hatchback', 'Duster' => 'suv', 'Oroch' => 'pickup'],
        'Suzuki' => ['Swift' => 'hatchback', 'Dzire' => 'sedan', 'Vitara' => 'suv', 'Ertiga' => 'van'],
        'Mitsubishi' => ['Mirage' => 'hatchback', 'L200' => 'pickup', 'Outlander' => 'suv'],
        'MG' => ['MG5' => 'sedan', 'ZS' => 'suv', 'HS' => 'suv'],
        'Jeep' => ['Compass' => 'suv', 'Wrangler' => 'suv', 'Grand Cherokee' => 'suv'],
        'RAM' => ['700' => 'pickup', '1500' => 'pickup', 'ProMaster' => 'van'],
        'Dodge' => ['Attitude' => 'sedan', 'Journey' => 'suv'],
        'Peugeot' => ['208' => 'hatchback', '2008' => 'suv', 'Partner' => 'van'],
        'Italika' => ['FT150' => 'moto', 'DM200' => 'moto', 'WS150' => 'moto'],
    ];

    public function run(): void
    {
        foreach (self::CATALOGO as $nombreMarca => $modelos) {
            $marca = Marca::firstOrCreate(['nombre' => $nombreMarca], ['verificado' => true]);

            foreach ($modelos as $nombreModelo => $carroceria) {
                ModeloVehiculo::firstOrCreate(
                    ['marca_id' => $marca->id, 'nombre' => $nombreModelo],
                    ['tipo_carroceria' => $carroceria, 'verificado' => true],
                );
            }
        }
    }
}
