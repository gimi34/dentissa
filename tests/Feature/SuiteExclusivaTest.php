<?php

namespace Tests\Feature;

use PDO;
use Tests\TestCase;

/**
 * Ficha docs/changes/2026-11-10-fix-tests-intermitentes-041.md: dos suites contra la misma base de
 * tests se bloquean entre sí. Un test con su transacción abierta deja candados en
 * `password_reset_tokens`, y el envío del enlace de la otra suite espera hasta el «Lock wait timeout»
 * (1205) o cae en un interbloqueo (1213). El trabajo tras la respuesta lo captura y el correo no sale.
 *
 * Mientras una suite corre, guarda el candado con nombre de su base: otra suite en otro clon o en
 * otro contenedor contra el mismo servidor no lo consigue y espera a que termine.
 */
class SuiteExclusivaTest extends TestCase
{
    public function test_mientras_corre_la_suite_otra_no_puede_usar_la_base_de_tests(): void
    {
        $base = config('database.connections.mariadb.database');
        $otra = new PDO(
            'mysql:host='.config('database.connections.mariadb.host').';port='.config('database.connections.mariadb.port').';dbname='.$base,
            config('database.connections.mariadb.username'),
            config('database.connections.mariadb.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $consulta = $otra->prepare('SELECT GET_LOCK(?, 0)');
        $consulta->execute(['dentissa:suite:'.$base]);

        $this->assertSame(0, (int) $consulta->fetchColumn(), 'Otra suite no consigue el candado de la base de tests mientras esta corre.');
    }
}
