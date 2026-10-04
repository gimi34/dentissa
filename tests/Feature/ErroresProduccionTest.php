<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Spec 030, CA4 (TM2, OB4.b): en producción (`APP_DEBUG=false`) un error
 * inesperado muestra una página genérica, sin el mensaje de la excepción, sin
 * rutas de archivos y sin traza.
 *
 * Es de caracterización: Laravel ya lo hace con la depuración apagada. Fija el
 * comportamiento del que dependen `deploy/verificar.sh` (comprueba
 * `APP_DEBUG=false` en cada despliegue) y el checklist de producción.
 */
class ErroresProduccionTest extends TestCase
{
    private const DETALLE = 'detalle interno: SELECT * FROM usuarios WHERE email = ?';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_prueba/error', function () {
            throw new RuntimeException(self::DETALLE);
        });
    }

    public function test_un_error_inesperado_muestra_una_pagina_generica_sin_detalles(): void
    {
        config(['app.debug' => false]);

        $respuesta = $this->get('/_prueba/error');

        $respuesta->assertStatus(500);
        $respuesta->assertDontSee(self::DETALLE, false);
        $respuesta->assertDontSee('RuntimeException', false);
        $respuesta->assertDontSee(base_path(), false);
        $respuesta->assertDontSee(__FILE__, false);
        $respuesta->assertDontSee('#0 ', false);
    }
}
