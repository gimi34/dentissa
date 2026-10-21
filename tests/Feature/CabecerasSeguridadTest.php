<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Spec 031 (CA1, CA2, CA3, CA4; TM1–TM5): cabeceras de seguridad y versiones
 * ocultas en nginx y PHP.
 *
 * Lee la configuración como texto, igual que `ConfiguracionProduccionTest`:
 * los fragmentos de `docker/nginx/seguridad/`, las dos configuraciones de
 * nginx, `local.ini`, el Dockerfile de producción y el Compose de local. La
 * respuesta real de nginx la comprueban `deploy/verificar.sh` y `curl` en local.
 */
class CabecerasSeguridadTest extends TestCase
{
    /** Carpeta de los fragmentos dentro del contenedor de nginx. */
    private const RUTA_FRAGMENTOS = '/etc/nginx/snippets/dentissa';

    /** Únicos orígenes externos que admite la política de contenido (fuentes de Google). */
    private const ORIGENES_PERMITIDOS = ['https://fonts.googleapis.com', 'https://fonts.gstatic.com'];

    /** 180 días en segundos (spec 031 CA3). */
    private const HSTS_MINIMO = 15552000;

    private function leer(string $ruta): string
    {
        $this->assertFileExists(base_path($ruta), "No existe {$ruta}.");

        return str_replace("\r\n", "\n", file_get_contents(base_path($ruta)));
    }

    /** Valor de una cabecera declarada con `add_header <nombre> "<valor>" always;`. */
    private function cabecera(string $conf, string $nombre): ?string
    {
        if (! preg_match('/^\s*add_header\s+'.preg_quote($nombre, '/').'\s+"([^"]*)"\s+always\s*;/mi', $conf, $m)) {
            return null;
        }

        return $m[1];
    }

    /**
     * Directivas de la política de contenido: nombre → lista de fuentes.
     *
     * @return array<string, list<string>>
     */
    private function directivas(string $csp): array
    {
        $directivas = [];
        foreach (array_filter(array_map('trim', explode(';', $csp))) as $parte) {
            $tokens = preg_split('/\s+/', $parte);
            $directivas[strtolower(array_shift($tokens))] = $tokens;
        }

        return $directivas;
    }

    /**
     * Bloques `location` de una configuración (cuerpo de cada uno) y el resto
     * del `server` sin ellos.
     *
     * @return array{0: list<string>, 1: string}
     */
    private function bloques(string $conf): array
    {
        preg_match_all('/^    location\s[^{]*\{\n(.*?)^    \}/ms', $conf, $m);
        $resto = preg_replace('/^    location\s[^{]*\{\n.*?^    \}/ms', '', $conf);

        return [$m[1], $resto];
    }

    public function test_los_fragmentos_comunes_declaran_las_cuatro_cabeceras(): void
    {
        $conf = $this->leer('docker/nginx/seguridad/comunes.conf');

        $this->assertSame('DENY', $this->cabecera($conf, 'X-Frame-Options'));
        $this->assertSame('nosniff', $this->cabecera($conf, 'X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $this->cabecera($conf, 'Referrer-Policy'));

        $csp = $this->cabecera($conf, 'Content-Security-Policy');
        $this->assertNotNull($csp, 'Falta la política de contenido con always.');
        $directivas = $this->directivas($csp);

        $this->assertSame(["'self'"], $directivas['default-src'] ?? null);
        $this->assertSame(["'none'"], $directivas['frame-ancestors'] ?? null, 'Otra web no debe poder incrustar la app (CA2).');
        $this->assertSame(["'none'"], $directivas['object-src'] ?? null);
        $this->assertSame(["'self'"], $directivas['base-uri'] ?? null);
        $this->assertSame(["'self'"], $directivas['form-action'] ?? null);

        $externos = array_values(array_unique(array_filter(
            array_merge(...array_values($directivas)),
            fn (string $fuente) => str_contains($fuente, '://') || str_ends_with($fuente, ':') && $fuente !== 'data:',
        )));
        sort($externos);
        $this->assertSame(self::ORIGENES_PERMITIDOS, $externos, 'La política solo admite las fuentes de Google como origen externo.');
    }

    public function test_la_conexion_segura_obligatoria_solo_va_en_produccion(): void
    {
        $produccion = $this->leer('docker/nginx/seguridad/produccion.conf');

        $this->assertMatchesRegularExpression(
            '#^\s*include\s+'.preg_quote(self::RUTA_FRAGMENTOS.'/comunes.conf', '#').'\s*;#m',
            $produccion,
            'produccion.conf debe incluir comunes.conf.',
        );

        $hsts = $this->cabecera($produccion, 'Strict-Transport-Security');
        $this->assertNotNull($hsts, 'Falta HSTS con always en produccion.conf.');
        $this->assertSame(1, preg_match('/max-age=(\d+)/', $hsts, $m));
        $this->assertGreaterThanOrEqual(self::HSTS_MINIMO, (int) $m[1], 'HSTS debe durar al menos 180 días.');
        $this->assertStringContainsString('includeSubDomains', $hsts);

        foreach (['docker/nginx/seguridad/comunes.conf', 'docker/nginx/dentissa.conf'] as $ruta) {
            $this->assertStringNotContainsStringIgnoringCase('Strict-Transport-Security', $this->leer($ruta), "{$ruta} no debe enviar HSTS (local no tiene conexión segura).");
        }
    }

    public function test_cada_configuracion_incluye_su_fragmento_en_el_server_y_en_cada_location_con_cabeceras(): void
    {
        $casos = [
            'docker/nginx/dentissa.conf' => 'comunes.conf',
            'docker/nginx/dentissa.prod.conf' => 'produccion.conf',
        ];

        foreach ($casos as $ruta => $fragmento) {
            $conf = $this->leer($ruta);
            $include = '#^\s*include\s+'.preg_quote(self::RUTA_FRAGMENTOS.'/'.$fragmento, '#').'\s*;#m';
            [$locations, $resto] = $this->bloques($conf);

            $this->assertNotEmpty($locations, "{$ruta} no tiene bloques location.");
            $this->assertMatchesRegularExpression($include, $resto, "{$ruta} debe incluir {$fragmento} en el server.");

            foreach ($locations as $cuerpo) {
                if (preg_match('/^\s*add_header\s/m', $cuerpo)) {
                    $this->assertMatchesRegularExpression($include, $cuerpo, "Un location de {$ruta} con add_header propio no incluye {$fragmento}: nginx no heredaría las cabeceras.");
                }
            }
        }
    }

    public function test_nginx_no_muestra_su_version(): void
    {
        foreach (['docker/nginx/dentissa.conf', 'docker/nginx/dentissa.prod.conf'] as $ruta) {
            $this->assertMatchesRegularExpression('/^\s*server_tokens\s+off\s*;/m', $this->leer($ruta), "{$ruta} debe tener server_tokens off.");
        }
    }

    public function test_php_no_anuncia_su_version(): void
    {
        $this->assertMatchesRegularExpression('/^expose_php\s*=\s*(Off|0)\s*$/mi', $this->leer('docker/nginx/php/local.ini'));
    }

    /** /review R6: las trazas que deja report() no llevan los argumentos de las llamadas. */
    public function test_las_trazas_no_llevan_argumentos(): void
    {
        $this->assertMatchesRegularExpression('/^zend\.exception_ignore_args\s*=\s*(On|1)\s*$/mi', $this->leer('docker/nginx/php/local.ini'));
    }

    public function test_los_fragmentos_llegan_a_nginx_en_local_y_en_produccion(): void
    {
        $this->assertMatchesRegularExpression(
            '#^COPY\s+docker/nginx/seguridad/?\s+'.preg_quote(self::RUTA_FRAGMENTOS, '#').'/?\s*$#m',
            $this->leer('docker/prod/Dockerfile'),
            'La imagen web de producción debe llevar los fragmentos.',
        );

        $this->assertMatchesRegularExpression(
            '#^\s+- \./docker/nginx/seguridad:'.preg_quote(self::RUTA_FRAGMENTOS, '#').':ro\s*$#m',
            $this->leer('docker-compose.yml'),
            'El nginx de local debe montar los fragmentos de solo lectura.',
        );
    }

    /** Spec 038 (D5; CA7): los marcos que admite cada ruta salen del `map` de `marcos.conf`. */
    public function test_la_politica_toma_frame_src_del_mapa_de_marcos(): void
    {
        $directivas = $this->directivas((string) $this->cabecera($this->leer('docker/nginx/seguridad/comunes.conf'), 'Content-Security-Policy'));

        $this->assertSame(['$dentissa_frame_src'], $directivas['frame-src'] ?? null);
        $this->assertSame(["'none'"], $directivas['frame-ancestors'] ?? null, 'Nadie puede incrustar la app: no cambia.');
    }

    /**
     * Spec 038 (TM3): el `map` de `marcos.conf` abre el marco de Google solo en `/` (con o sin
     * consulta). Un error en la expresión abriría marcos en más rutas.
     */
    public function test_solo_la_raiz_admite_el_mapa(): void
    {
        $conf = $this->leer('docker/nginx/seguridad/marcos.conf');
        $this->assertSame(1, preg_match('/^map\s+\$request_uri\s+\$dentissa_frame_src\s*\{\n(.*?)^\}/ms', $conf, $mapa), 'marcos.conf define el map sobre la ruta original de la petición.');

        $entradas = [];
        foreach (explode("\n", $mapa[1]) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }
            $this->assertSame(1, preg_match('/^(default|"[^"]*")\s+"([^"]*)"\s*;$/', $linea, $m), "Entrada del map con su valor: {$linea}");
            $entradas[trim($m[1], '"')] = $m[2];
        }

        $this->assertSame("'self'", $entradas['default'] ?? null, 'Por defecto, solo marcos propios.');
        $reglas = array_diff_key($entradas, ['default' => true]);
        $this->assertCount(1, $reglas, 'Una sola regla.');
        $expresion = array_key_first($reglas);
        $this->assertSame("'self' https://www.google.com", $reglas[$expresion], 'Un solo origen externo.');
        $this->assertStringStartsWith('~^', $expresion, 'Expresión anclada y con mayúsculas exactas.');
        $this->assertStringEndsWith('$', $expresion);

        $patron = '#'.str_replace('#', '\#', substr($expresion, 1)).'#';
        foreach (['/', '/?utm=x', '/?utm_source=google&x=1'] as $ruta) {
            $this->assertSame(1, preg_match($patron, $ruta), "{$ruta} admite el mapa.");
        }
        foreach (['/servicios', '/admin', '//evil', '/x/', '/servicios?x=/', '/login', '/%2F', ''] as $ruta) {
            $this->assertSame(0, preg_match($patron, $ruta), "{$ruta} no admite el mapa.");
        }
    }

    /** Spec 038 (D5): `map` va en el contexto `http`, antes del `server` de cada configuración. */
    public function test_las_dos_configuraciones_incluyen_los_marcos_antes_del_server(): void
    {
        foreach (['docker/nginx/dentissa.conf', 'docker/nginx/dentissa.prod.conf'] as $ruta) {
            $conf = $this->leer($ruta);
            $this->assertSame(1, preg_match('#^include\s+'.preg_quote(self::RUTA_FRAGMENTOS.'/marcos.conf', '#').'\s*;#m', $conf, $m, PREG_OFFSET_CAPTURE), "{$ruta} incluye marcos.conf fuera del server.");
            $this->assertLessThan(strpos($conf, "\nserver {") ?: strpos($conf, 'server {'), $m[0][1], "{$ruta}: marcos.conf antes del server.");
        }
    }

    /**
     * Spec 038 (D10; CA7): con la página de inicio, `/` ya no redirige; `verificar.sh` comprueba
     * la redirección a https con `/admin`, que `/` responde 200 con el marco de Google y que
     * `/servicios` no lo admite.
     */
    public function test_verificar_comprueba_la_raiz_y_los_marcos(): void
    {
        $script = $this->leer('deploy/verificar.sh');
        $inicio = strpos($script, 'echo "== Dirección pública"');
        $fin = strpos($script, 'if [[ "${FALLOS}" -eq 0 ]]');
        $this->assertNotFalse($inicio, 'verificar.sh tiene el bloque público.');
        $this->assertNotFalse($fin);
        $publico = substr($script, $inicio, $fin - $inicio);

        $this->assertMatchesRegularExpression('/^[^#\n]*curl -sI "\$\{url\}\/admin" \| grep -iqE \'\^location: https:\/\/\'/m', $publico, 'La redirección a https se comprueba con /admin.');
        $this->assertDoesNotMatchRegularExpression('/curl -sI "\$\{url\}\/"/', $publico, '/ ya no redirige.');
        $this->assertMatchesRegularExpression('/^[^#\n]*curl -s -o \/dev\/null -w \'%\{http_code\}\' "\$\{url\}\/"\)/m', $publico, '/ responde 200.');
        $this->assertMatchesRegularExpression('/^[^#\n]*curl [^\n]*"\$\{url\}\/"(?:(?!curl )[\s\S]){0,300}?frame-src \'self\' https:\/\/www\\\\?\.google\\\\?\.com;/m', $publico, '/ admite el marco de Google.');
        $this->assertMatchesRegularExpression('/^[^#\n]*curl [^\n]*"\$\{url\}\/servicios"(?:(?!curl )[\s\S]){0,300}?frame-src \'self\';/m', $publico, '/servicios solo admite marcos propios.');
    }
}
