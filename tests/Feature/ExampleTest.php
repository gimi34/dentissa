<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz de la aplicación es la página de inicio pública (spec 038, CA1).
     *
     * Hasta la spec 038, `GET /` redirigía siempre (al panel del rol o a
     * `/login`, spec 001 CA4). Desde la 038, el visitante sin sesión ve la
     * presentación de la clínica; con sesión, cada rol sigue yendo a su panel
     * (lo prueba `PaginaInicioTest`).
     */
    public function test_el_visitante_sin_sesion_ve_la_pagina_de_inicio(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('publico.inicio');
    }
}
