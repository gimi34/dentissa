<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Spec 030 (CA2, CA3, CA5, CA8, CA10; TM1, TM4, TM6, TM7, TM9): comprobaciones
 * estáticas de la configuración de despliegue.
 *
 * Lee los archivos como texto, sin dependencias nuevas: los Compose de local y
 * producción, los dos Dockerfiles, la conf de nginx de producción,
 * `.dockerignore` y `.gitignore`. Lo que solo existe en el servidor (red,
 * puertos abiertos, contenido real de la imagen) se comprueba en el ensayo
 * (T040–T046).
 */
class ConfiguracionProduccionTest extends TestCase
{
    private const SUBRED = '172.28.0.0/24';

    private const RUTA_CLON = '/opt/dentissa/app';

    private function leer(string $ruta): string
    {
        $this->assertFileExists(base_path($ruta), "No existe {$ruta}.");

        return str_replace("\r\n", "\n", file_get_contents(base_path($ruta)));
    }

    /**
     * Bloques de `services:` de un Compose: nombre → texto del bloque.
     *
     * @return array<string, string>
     */
    private function servicios(string $compose): array
    {
        $this->assertSame(1, preg_match('/^services:\n(.*?)(?=^\S|\z)/ms', $compose, $m), 'El Compose no tiene services:.');

        preg_match_all('/^  ([\w-]+):[^\S\n]*\n(.*?)(?=^  [\w-]+:[^\S\n]*\n|\z)/ms', $m[1], $bloques);

        return array_combine($bloques[1], $bloques[2]);
    }

    /** @return list<string> entradas de `ports:` de un bloque de servicio */
    private function puertos(string $bloque): array
    {
        if (! preg_match('/^    ports:\n((?:      - .*\n?)+)/m', $bloque, $m)) {
            return [];
        }
        preg_match_all('/^      - ["\']?([^"\'\n]+)["\']?/m', $m[1], $entradas);

        return $entradas[1];
    }

    /**
     * Imágenes de un Compose (`image:`) o de un Dockerfile (`FROM` y
     * `COPY --from=` que no son etapas propias).
     *
     * @return list<string>
     */
    private function imagenes(string $texto): array
    {
        preg_match_all('/^\s*image:\s*["\']?([^"\'\s]+)/m', $texto, $compose);
        preg_match_all('/^FROM\s+(\S+)(?:\s+AS\s+(\S+))?/mi', $texto, $from);
        preg_match_all('/^COPY\s+--from=(\S+)/mi', $texto, $copias);

        $etapas = array_filter($from[2]);
        $copiasDeImagen = array_filter($copias[1], fn ($origen) => ! in_array($origen, $etapas, true));

        return array_values(array_merge($compose[1], $from[1], $copiasDeImagen));
    }

    /** @return array<string, string> repositorio → etiqueta */
    private function etiquetas(array $imagenes): array
    {
        $etiquetas = [];
        foreach ($imagenes as $imagen) {
            [$repositorio, $etiqueta] = array_pad(explode(':', $imagen, 2), 2, '');
            $etiquetas[$repositorio] = $etiqueta;
        }

        return $etiquetas;
    }

    public function test_produccion_no_publica_puertos_ni_tiene_phpmyadmin(): void
    {
        $compose = $this->leer('docker-compose.prod.yml');
        $servicios = $this->servicios($compose);

        $this->assertNotEmpty($servicios);
        foreach ($servicios as $nombre => $bloque) {
            $this->assertStringNotContainsString('ports:', $bloque, "El servicio {$nombre} de producción publica puertos.");
        }
        $this->assertStringNotContainsStringIgnoringCase('phpmyadmin', $compose);
    }

    public function test_local_publica_los_puertos_solo_en_localhost(): void
    {
        $servicios = $this->servicios($this->leer('docker-compose.yml'));

        $todos = [];
        foreach ($servicios as $nombre => $bloque) {
            foreach ($this->puertos($bloque) as $puerto) {
                $todos[] = $puerto;
                $this->assertStringStartsWith('127.0.0.1:', $puerto, "El servicio {$nombre} de local publica {$puerto} a toda la red.");
            }
        }
        $this->assertNotEmpty($todos, 'No se encontró ningún puerto en el Compose de local.');
    }

    public function test_cada_servicio_de_produccion_lee_solo_su_archivo_de_entorno(): void
    {
        $servicios = $this->servicios($this->leer('docker-compose.prod.yml'));
        $esperado = ['app' => 'app.env', 'db' => 'db.env', 'cloudflared' => 'tunnel.env', 'web' => null];

        foreach ($esperado as $nombre => $propio) {
            $this->assertArrayHasKey($nombre, $servicios, "Falta el servicio {$nombre}.");
            preg_match_all('/([\w-]+\.env)\b/', $servicios[$nombre], $m);
            $leidos = array_values(array_unique($m[1]));
            $this->assertSame($propio === null ? [] : [$propio], $leidos, "El servicio {$nombre} lee otros archivos de entorno.");
        }

        // /analyze A23: la app no recibe la contraseña de root y la base declara un usuario propio.
        $this->assertStringNotContainsString('MARIADB_ROOT_PASSWORD', $servicios['app']);
        $this->assertSame(1, preg_match('/MARIADB_USER:\s*["\']?([\w-]+)/', $servicios['db'], $usuario), 'db no declara MARIADB_USER.');
        $this->assertNotSame('root', $usuario[1]);
    }

    public function test_la_ip_real_solo_se_acepta_desde_la_subred_de_produccion(): void
    {
        $compose = $this->leer('docker-compose.prod.yml');
        $conf = $this->leer('docker/nginx/dentissa.prod.conf');

        $this->assertMatchesRegularExpression('/subnet:\s*["\']?'.preg_quote(self::SUBRED, '/').'/', $compose);
        preg_match_all('/set_real_ip_from\s+([^;\s]+)\s*;/', $conf, $origenes);
        $this->assertSame([self::SUBRED], $origenes[1]);
        $this->assertMatchesRegularExpression('/real_ip_header\s+CF-Connecting-IP\s*;/', $conf);
    }

    public function test_produccion_redirige_a_https_lo_que_llega_por_http(): void
    {
        $conf = $this->leer('docker/nginx/dentissa.prod.conf');

        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$http_x_forwarded_proto\s*=\s*["\']?http["\']?\s*\)\s*\{\s*return\s+301\s+https:\/\/\$host\$request_uri\s*;\s*\}/',
            $conf,
        );
    }

    public function test_los_archivos_de_entorno_quedan_fuera_del_paquete_y_del_repo(): void
    {
        $dockerignore = array_map('trim', explode("\n", $this->leer('.dockerignore')));
        $gitignore = array_map('trim', explode("\n", $this->leer('.gitignore')));
        $compose = $this->leer('docker-compose.prod.yml');

        foreach (['.env*', '**/*.env', '.git', 'node_modules', 'vendor', 'database/*.sql', 'storage/*', 'tests', 'docs', '.ai', 'aidlc', '.claude', '.github'] as $exclusion) {
            $this->assertContains($exclusion, $dockerignore, ".dockerignore no excluye {$exclusion}.");
        }
        $this->assertContains('*.env', $gitignore, '.gitignore no ignora *.env.');

        $this->assertSame(1, preg_match('/\$\{DENTISSA_ENV_DIR:-([^}]+)\}/', $compose, $m), 'El Compose no usa DENTISSA_ENV_DIR.');
        $this->assertSame('/opt/dentissa/entorno', $m[1]);
        $this->assertStringStartsNotWith(self::RUTA_CLON.'/', rtrim($m[1], '/').'/');
    }

    private function assertVersionExacta(array $imagenes): void
    {
        $this->assertNotEmpty($imagenes);
        foreach ($imagenes as $imagen) {
            $this->assertMatchesRegularExpression('/^[\w.\/-]+:\d+\.\d+\.\d+[\w.-]*$/', $imagen, "{$imagen} no tiene versión exacta.");
            $this->assertStringNotContainsString('latest', $imagen);
        }
    }

    /** @return list<string> */
    private function imagenesDeLocal(): array
    {
        return array_merge(
            $this->imagenes($this->leer('docker-compose.yml')),
            $this->imagenes($this->leer('Dockerfile')),
        );
    }

    private function assertMontaLocalIni(string $archivo): void
    {
        $compose = $this->leer($archivo);
        $this->assertSame(1, preg_match_all('/-\s*["\']?\.\/([^:"\'\s]+):[^"\'\s]*local\.ini/', $compose, $m), "{$archivo} no monta local.ini una vez.");
        // is_file y no file_exists: si el origen no existe, Docker crea en su lugar una carpeta vacía.
        $this->assertTrue(is_file(base_path($m[1][0])), "{$archivo} monta {$m[1][0]}, que no es un archivo.");
    }

    public function test_las_imagenes_de_local_tienen_version_exacta(): void
    {
        $this->assertVersionExacta($this->imagenesDeLocal());
    }

    public function test_las_imagenes_de_produccion_tienen_version_exacta_e_iguales_a_local(): void
    {
        $produccion = array_merge(
            $this->imagenes($this->leer('docker-compose.prod.yml')),
            $this->imagenes($this->leer('docker/prod/Dockerfile')),
        );
        $this->assertVersionExacta($produccion);

        $etiquetasLocal = $this->etiquetas($this->imagenesDeLocal());
        $etiquetasProduccion = $this->etiquetas($produccion);
        $compartidas = array_intersect_key($etiquetasLocal, $etiquetasProduccion);
        $this->assertNotEmpty($compartidas);
        foreach ($compartidas as $repositorio => $etiqueta) {
            $this->assertSame($etiqueta, $etiquetasProduccion[$repositorio], "{$repositorio} tiene otra versión en producción.");
        }
    }

    public function test_el_compose_de_local_monta_un_local_ini_que_existe(): void
    {
        $this->assertMontaLocalIni('docker-compose.yml');
    }

    public function test_el_compose_de_produccion_monta_un_local_ini_que_existe(): void
    {
        $this->assertMontaLocalIni('docker-compose.prod.yml');
    }
}
