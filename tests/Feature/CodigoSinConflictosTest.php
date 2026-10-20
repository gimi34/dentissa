<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Ningún archivo de código lleva marcadores de conflicto de git. El merge
 * `f096e17` subió a `main` cinco controladores con `<<<<<<< Updated upstream`
 * de un `git stash pop` sin resolver y PHP dejó de poder analizarlos
 * (ficha docs/changes/2026-10-20-fix-marcadores-conflicto-main.md).
 *
 * No abre sesión ni toca la base de datos: lee los archivos del repositorio.
 */
class CodigoSinConflictosTest extends TestCase
{
    private const CARPETAS = ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'tests'];

    private const EXTENSIONES = ['php', 'js', 'css'];

    public function test_ningun_archivo_de_codigo_tiene_marcadores_de_conflicto(): void
    {
        // El patrón se arma con cuantificadores para que este archivo no se detecte a sí mismo.
        $marcador = '/^(<{7} |={7}\r?$|>{7} )/m';
        $fallos = [];

        foreach (self::CARPETAS as $carpeta) {
            foreach (File::allFiles(base_path($carpeta)) as $archivo) {
                if (! in_array($archivo->getExtension(), self::EXTENSIONES, true)
                    || str_contains($archivo->getPathname(), 'bootstrap'.DIRECTORY_SEPARATOR.'cache')) {
                    continue;
                }

                $lineas = preg_match_all($marcador, $archivo->getContents());
                if ($lineas > 0) {
                    $fallos[] = "{$carpeta}/{$archivo->getRelativePathname()}: {$lineas} marcadores";
                }
            }
        }

        $this->assertSame([], $fallos, 'Hay conflictos de git sin resolver: '.implode('; ', $fallos));
    }
}
