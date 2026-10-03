<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Versiones mínimas corregidas de las dependencias con avisos de `composer audit`
 * y `npm audit` del 2026-09-29 (spec 024, TM4) y de `composer audit` del
 * 2026-10-03 (spec 029: `laravel/framework`, `league/flysystem` y
 * `phpseclib/phpseclib`). Impiden que vuelva un lockfile vulnerable. Casi todas corrigen avisos críticos o altos; `follow-redirects` se
 * incluye a propósito aunque su aviso era moderado (tabla del plan).
 *
 * No abren sesión ni tocan la base de datos: leen `composer.lock` y
 * `package-lock.json`, no lo instalado en `vendor` o `node_modules`.
 */
class DependenciasSegurasTest extends TestCase
{
    /**
     * Mínima corregida de cada paquete del servidor, por rama mayor.
     */
    private const MINIMAS_SERVIDOR = [
        'guzzlehttp/guzzle' => [7 => '7.15.2'],
        'league/commonmark' => [2 => '2.10.0'],
        // Spec 029: CVE-2026-102279 (baja), CVE-2026-102601 (baja) y CVE-2026-84308 (media).
        'laravel/framework' => [12 => '12.69.0'],
        'league/flysystem' => [3 => '3.35.3'],
        'phpseclib/phpseclib' => [3 => '3.0.57'],
    ];

    /**
     * Mínima corregida de cada paquete del frontend, por rama mayor.
     */
    private const MINIMAS_FRONTEND = [
        'axios' => [1 => '1.17.1'],
        'concurrently' => [9 => '9.2.2'],
        'vite' => [8 => '8.0.16'],
        'follow-redirects' => [1 => '1.15.12'],
        'form-data' => [4 => '4.0.6'],
        'nanoid' => [3 => '3.3.18'],
        'picomatch' => [2 => '2.3.2', 4 => '4.0.4'],
        'postcss' => [8 => '8.5.23'],
        'shell-quote' => [1 => '1.8.5'],
    ];

    public function test_las_dependencias_del_servidor_estan_en_versiones_corregidas(): void
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);
        $versiones = array_column($lock['packages'], 'version', 'name');
        $fallos = [];

        foreach (self::MINIMAS_SERVIDOR as $paquete => $minimasPorRama) {
            if (! isset($versiones[$paquete])) {
                $fallos[] = "{$paquete}: no aparece en composer.lock";

                continue;
            }

            $version = ltrim($versiones[$paquete], 'v');
            $rama = (int) explode('.', $version)[0];

            if (! isset($minimasPorRama[$rama])) {
                $fallos[] = "{$paquete} {$version}: la rama {$rama} no tiene mínima definida";
            } elseif (version_compare($version, $minimasPorRama[$rama], '<')) {
                $fallos[] = "{$paquete} {$version} < {$minimasPorRama[$rama]}";
            }
        }

        $this->assertSame([], $fallos, 'Dependencias del servidor por debajo de la versión corregida');
    }

    public function test_las_dependencias_del_frontend_estan_en_versiones_corregidas(): void
    {
        $lock = json_decode(file_get_contents(base_path('package-lock.json')), true, flags: JSON_THROW_ON_ERROR);
        $fallos = [];
        $encontrados = [];

        // Toda instancia del paquete, también las anidadas (…/node_modules/x/node_modules/<paquete>).
        foreach ($lock['packages'] as $ruta => $datos) {
            foreach (self::MINIMAS_FRONTEND as $paquete => $minimasPorRama) {
                if (! str_ends_with('/'.$ruta, '/node_modules/'.$paquete)) {
                    continue;
                }

                $encontrados[$paquete] = true;
                $version = $datos['version'];
                $rama = (int) explode('.', $version)[0];

                if (! isset($minimasPorRama[$rama])) {
                    $fallos[] = "{$ruta} {$version}: la rama {$rama} no tiene mínima definida";
                } elseif (version_compare($version, $minimasPorRama[$rama], '<')) {
                    $fallos[] = "{$ruta} {$version} < {$minimasPorRama[$rama]}";
                }
            }
        }

        foreach (array_keys(self::MINIMAS_FRONTEND) as $paquete) {
            if (! isset($encontrados[$paquete])) {
                $fallos[] = "{$paquete}: no aparece en package-lock.json";
            }
        }

        $this->assertSame([], $fallos, 'Dependencias del frontend por debajo de la versión corregida');
    }
}
