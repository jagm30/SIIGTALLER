<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * Los catálogos se pueden correr varias veces sin duplicar.
     * Los datos de prueba solo se crean en ambiente local (APP_ENV=local).
     */
    public function run(): void
    {
        $this->call([
            PreferenciasOperacionSeeder::class,
            MarcasYModelosSeeder::class,
            ChecklistRecepcionSeeder::class,
            AngulosFotoSeeder::class,
            ZonasVehiculoSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call(DatosDePruebaSeeder::class);
        }
    }
}
