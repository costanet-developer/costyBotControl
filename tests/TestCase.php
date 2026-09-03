<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            throw new RuntimeException(
                'Pruebas bloqueadas: existe un caché de configuración productivo. '
                .'Ejecute optimize:clear antes de PHPUnit y vuelva a optimizar al finalizar.'
            );
        }

        parent::setUp();

        $conexion = config('database.default');
        $base = config("database.connections.{$conexion}.database");

        if (! app()->environment('testing') || $conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                'Pruebas bloqueadas: PHPUnit solo puede usar SQLite en memoria.'
            );
        }
    }
}
