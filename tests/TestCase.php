<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Los tests usan RefreshDatabase, que borra la base completa. Si la configuración está
     * en caché (php artisan optimize / config:cache), Laravel ignora phpunit.xml y apuntaría
     * a la base real; por eso se detiene antes de tocar nada.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $conexion = $app['config']->get('database.default');
        $base = $app['config']->get("database.connections.{$conexion}.database");

        if ($conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                "Los tests deben usar SQLite en memoria, pero apuntan a [{$conexion}: {$base}]. "
                . 'Ejecuta "php artisan optimize:clear" y vuelve a correrlos.'
            );
        }

        return $app;
    }
}
