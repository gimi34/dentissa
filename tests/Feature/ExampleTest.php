<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz de la aplicación no sirve contenido: siempre redirige.
     *
     * Esta prueba llegó con el andamiaje de Laravel afirmando que `GET /`
     * devolvía 200, y llevaba fallando desde que se escribió la ruta raíz. En
     * este proyecto esa ruta (routes/web.php, línea 31) redirige siempre: al
     * panel del rol si hay sesión iniciada, y a `/login` si no la hay. La
     * aserción de 200 nunca describió esta aplicación.
     *
     * La aserción nueva no relaja nada: describe el comportamiento real y
     * verificado, y falla si la aplicación deja de redirigir al visitante
     * anónimo hacia el inicio de sesión.
     */
    public function test_anonymous_visitor_is_redirected_to_landing_index(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertViewIs('landing.index');
    }
}
