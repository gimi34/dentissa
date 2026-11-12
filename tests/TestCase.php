<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;
use PDOException;

abstract class TestCase extends BaseTestCase
{
    /** Minutos que una suite espera a que termine otra contra la misma base antes de rendirse. */
    private const ESPERA_MAXIMA_MINUTOS = 20;

    /** Conexión propia que guarda el candado de la suite hasta que termina el proceso. */
    private static ?PDO $candadoDeLaSuite = null;

    /** Si la espera ya venció una vez, los tests siguientes fallan sin volver a esperar. */
    private static bool $esperaVencida = false;

    protected function setUp(): void
    {
        parent::setUp();
        self::reservarLaBaseDeTests();
    }

    /**
     * Ficha docs/changes/2026-11-10-fix-tests-intermitentes-041.md: dos suites contra la misma base de
     * tests (otro clon, otro contenedor) se bloquean entre sí, porque cada test guarda sus candados
     * hasta su rollback, y los tests fallan al azar con «Lock wait timeout» o interbloqueos. Una suite
     * a la vez: la primera guarda un candado con el nombre de la base y la otra espera a que termine.
     * El servidor lo suelta solo al cerrarse la conexión, también si la suite se interrumpe.
     */
    private static function reservarLaBaseDeTests(): void
    {
        if (self::$candadoDeLaSuite !== null) {
            return;
        }

        if (self::$esperaVencida) {
            self::fail('Otra suite sigue usando la base de tests.');
        }

        $conexion = config('database.connections.'.config('database.default'));
        if (($conexion['driver'] ?? null) !== 'mariadb' || empty($conexion['database'])) {
            return;
        }

        try {
            $pdo = new PDO(
                'mysql:host='.$conexion['host'].';port='.$conexion['port'].';dbname='.$conexion['database'],
                $conexion['username'],
                $conexion['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        } catch (PDOException) {
            return; // Sin base no hay con quién chocar; los tests que la usen fallarán por su cuenta.
        }

        $nombre = 'dentissa:suite:'.$conexion['database'];
        if (! self::pedirCandado($pdo, $nombre, 0)) {
            fwrite(STDERR, "\nOtra suite está usando {$conexion['database']}; esta espera a que termine.\n");

            if (! self::pedirCandado($pdo, $nombre, self::ESPERA_MAXIMA_MINUTOS * 60)) {
                self::$esperaVencida = true;
                self::fail("Otra suite sigue usando {$conexion['database']} tras ".self::ESPERA_MAXIMA_MINUTOS.' minutos.');
            }
        }

        self::$candadoDeLaSuite = $pdo;
    }

    private static function pedirCandado(PDO $pdo, string $nombre, int $segundos): bool
    {
        $consulta = $pdo->prepare('SELECT GET_LOCK(?, ?)');
        $consulta->execute([$nombre, $segundos]);

        return (int) $consulta->fetchColumn() === 1;
    }
}
