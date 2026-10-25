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
     * Órdenes de un script de shell, una por línea y sin sangría: sin comentarios, líneas en
     * blanco ni `echo`, para que un texto que imita la orden no cuente como la orden.
     */
    private function ordenes(string $ruta): string
    {
        $lineas = array_map('trim', explode("\n", $this->leer($ruta)));

        return implode("\n", array_filter($lineas, fn (string $l) => $l !== '' && ! str_starts_with($l, '#') && ! str_starts_with($l, 'echo ')));
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

    /**
     * Añadido durante /implement (T015): `horarios_dentista` no declara
     * colación y hereda la de la base; si no es la del volcado
     * (`utf8mb4_unicode_ci`), su clave foránea no se crea y la carga del
     * volcado se corta en una base nueva.
     */
    public function test_la_base_arranca_con_la_colacion_del_volcado(): void
    {
        foreach (['docker-compose.yml', 'docker-compose.prod.yml'] as $archivo) {
            $db = $this->servicios($this->leer($archivo))['db'] ?? '';

            $this->assertMatchesRegularExpression(
                '/^    command:.*--character-set-server=utf8mb4\s.*--collation-server=utf8mb4_unicode_ci\s*$/m',
                $db,
                "{$archivo}: db no arranca con la colación del volcado.",
            );
        }
    }

    /**
     * RS18.a (spec 031, T054): cada despliegue construye app y web sin caché y bajando las
     * imágenes base, para que la capa de apt-get/apk upgrade se renueve (con caché, el web de
     * producción conservaba avisos ya corregidos).
     */
    public function test_el_despliegue_construye_sin_cache(): void
    {
        $script = $this->leer('deploy/desplegar.sh');
        // Anclado a la orden (/review R8): un echo o un paso "build …" no cuentan.
        $construcciones = preg_grep('/^\s*"\$\{compose\[@\]\}" build\b/', explode("\n", $script));
        $this->assertNotEmpty($construcciones, 'desplegar.sh construye las imágenes.');
        foreach ($construcciones as $linea) {
            $this->assertStringContainsString('--no-cache', $linea, 'La construcción no usa la caché.');
            $this->assertStringContainsString('--pull', $linea, 'La construcción baja las imágenes base.');
            $this->assertMatchesRegularExpression('/\bapp web\b/', $linea, 'Se construyen app y web.');
        }
    }

    /**
     * RS18.a (spec 031, T056): un argumento que cambia en cada despliegue obliga a rehacer la capa
     * de apt-get/apk upgrade de cada etapa aunque el constructor la tenga en caché (en el servidor,
     * --no-cache no lo garantizó para web).
     */
    public function test_cada_despliegue_rehace_la_actualizacion_del_sistema(): void
    {
        $dockerfile = $this->leer('docker/prod/Dockerfile');
        $etapas = preg_split('/^FROM\s/m', $dockerfile);
        $actualizaciones = ['app' => 'apt-get upgrade', 'web' => 'apk upgrade'];
        foreach ($actualizaciones as $etapa => $orden) {
            $cuerpo = current(array_filter($etapas, fn ($e) => preg_match('/^\S+\s+AS\s+'.$etapa.'\s*$/m', $e)));
            $this->assertIsString($cuerpo, "docker/prod/Dockerfile tiene la etapa {$etapa}.");
            $arg = strpos($cuerpo, "\nARG REFRESCO_SISTEMA");
            $run = strpos($cuerpo, $orden);
            $this->assertNotFalse($arg, "La etapa {$etapa} declara ARG REFRESCO_SISTEMA.");
            $this->assertNotFalse($run, "La etapa {$etapa} actualiza el sistema con {$orden}.");
            $this->assertLessThan($run, $arg, "En {$etapa}, el ARG va antes de {$orden}.");
            $inicio = strrpos(substr($cuerpo, 0, $run), "\nRUN ");
            $this->assertStringContainsString('REFRESCO_SISTEMA', substr($cuerpo, $inicio, $run - $inicio), "En {$etapa}, el RUN de {$orden} usa el argumento.");
        }

        $script = $this->leer('deploy/desplegar.sh');
        $this->assertMatchesRegularExpression('/^\s*"\$\{compose\[@\]\}" build\b[^\n]*--build-arg REFRESCO_SISTEMA=[^\n]*\bapp web\b/m', $script, 'desplegar.sh pasa REFRESCO_SISTEMA al construir app y web.');
    }

    /**
     * Spec 031 (CA7; /review R1): «Entorno aplicado» de verificar.sh no puede dar OK sin
     * comparar todos los servicios. Un error al comparar uno es su FALLO, python3 declara
     * cuántos servicios hay y el shell falla si faltan líneas o python3 no termina bien.
     */
    public function test_el_entorno_aplicado_no_da_ok_sin_comparar_todos_los_servicios(): void
    {
        $script = $this->leer('deploy/verificar.sh');
        $this->assertSame(1, preg_match('/^    echo "== Entorno aplicado.*?\n(.*?)^    echo "== /ms', $script, $bloque), 'verificar.sh tiene el bloque «Entorno aplicado».');
        $this->assertSame(1, preg_match("/<<'PY'\n(.*?)\nPY\n(.*)/s", $bloque[1], $partes), 'El bloque compara con python3.');
        [, $python, $shell] = $partes;

        $this->assertSame(1, preg_match('/^for nombre, servicio in [^\n]*:\n((?:    [^\n]*(?:\n|\z)|\n)+)/m', $python, $bucle), 'python3 recorre los servicios del Compose.');
        $this->assertMatchesRegularExpression('/^    try:\n/m', $bucle[1], 'Cada servicio se compara dentro de su propio try.');
        $this->assertMatchesRegularExpression('/^    except Exception\b[^\n]*:\n        print\(f[\'"]FALLO \{nombre\}: no se pudo comparar/m', $bucle[1], 'Un error al comparar un servicio es su FALLO.');
        $this->assertMatchesRegularExpression('/^print\(f[\'"]SERVICIOS \{len\(config\[[\'"]services[\'"]\]\)\}[\'"]\)/m', $python, 'python3 declara cuántos servicios compara.');

        $this->assertMatchesRegularExpression('/^\)"\n    codigo=\$\?\n/', $shell, 'El shell guarda el código de salida de python3.');
        $this->assertMatchesRegularExpression('/^[^#\n]*\bgrep\b[^\n]*\^SERVICIOS /m', $shell, 'El shell lee el número de servicios.');
        $this->assertMatchesRegularExpression('/^[^#\n]*"\$\{codigo\}" -ne 0 \|\|[^\n]*-ne "\$\{servicios\}"/m', $shell, 'El shell falla si python3 termina mal o faltan líneas.');
    }

    /**
     * Spec 031 (CA7, D8; /review R2, R5): `aplicar-entorno.sh` aplica el archivo de entorno de
     * un servicio recreándolo. Rechaza `db`, acepta solo un servicio que exista tal cual en el
     * Compose (sin expresiones regulares ni opciones), recrea sin tocar los demás, rehace las
     * cachés de `app` como `www-data` y termina con la verificación interna.
     */
    public function test_aplicar_entorno_recrea_el_servicio_y_termina_verificando(): void
    {
        $ordenes = $this->ordenes('deploy/aplicar-entorno.sh');
        $compose = preg_quote('"${compose[@]}"', '/');

        $this->assertMatchesRegularExpression('/^set -euo pipefail$/m', $ordenes, 'El script se detiene ante cualquier error.');
        $this->assertMatchesRegularExpression('/^if \[\[ "\$\{servicio\}" == "db" \]\]; then\nerror /m', $ordenes, 'Rechaza db: db.env solo se aplica al crear la base.');
        $this->assertMatchesRegularExpression('/^servicios="\$\('.$compose.' config --services\)"/m', $ordenes, 'Lee los servicios del Compose de producción.');
        $this->assertMatchesRegularExpression('/^grep -qxF -- "\$\{servicio\}" <<< "\$\{servicios\}" \|\| error /m', $ordenes, 'El servicio se compara como texto fijo y nunca como opción (R5).');
        $this->assertMatchesRegularExpression('/^'.$compose.' up -d --force-recreate --no-deps "\$\{servicio\}"$/m', $ordenes, 'Recrea solo ese servicio para que relea su archivo de entorno.');
        $this->assertMatchesRegularExpression(
            '/^if \[\[ "\$\{servicio\}" == "app" \]\]; then\n(?:paso "[^"\n]*"\n)?'.$compose.' exec -T -u www-data app php artisan config:cache\n'.$compose.' exec -T -u www-data app php artisan route:cache\nfi$/m',
            $ordenes,
            'En app rehace las cachés de configuración y rutas como www-data.',
        );
        $this->assertMatchesRegularExpression('/^bash deploy\/verificar\.sh --interna\n\}\nmain "\$@"$/', substr($ordenes, strrpos($ordenes, 'bash deploy/') ?: 0), 'Termina con la verificación interna.');
    }

    /**
     * Spec 031 (CA4, CA7; /review R7): `verificar.sh` nombra bien lo que sobra en las
     * cabeceras (X-Powered-By o la versión de Server están presentes, no «faltan») y la ruta
     * del bucle de cabeceras es local a la función.
     */
    public function test_verificar_nombra_las_cabeceras_que_sobran_y_la_ruta_es_local(): void
    {
        $script = $this->leer('deploy/verificar.sh');

        $this->assertMatchesRegularExpression('/^\s*grep -iqE \'\^x-powered-by:\' <<< "\$\{cabeceras\}" && faltan\+=\("X-Powered-By presente"\)$/m', $script);
        $this->assertMatchesRegularExpression('/^\s*grep -iqE \'\^server:\.\*\[0-9\]\' <<< "\$\{cabeceras\}" && faltan\+=\("Server con versión"\)$/m', $script);

        $bucle = strpos($script, 'for ruta in ');
        $this->assertNotFalse($bucle, 'verificar.sh recorre las rutas de las cabeceras.');
        $this->assertMatchesRegularExpression('/^\s*local\b[^\n]*\bruta\b/m', substr($script, 0, $bucle), 'La variable ruta se declara local antes del bucle.');
    }

    /**
     * Spec 031 (CA9; TM9, TM11, TM12): la red privada de administración se
     * instala desde el repositorio oficial firmado, sin ejecutar scripts
     * descargados, con el SSH propio de Tailscale desactivado (se sigue usando
     * OpenSSH con clave) y con su origen en las actualizaciones automáticas.
     */
    public function test_el_acceso_remoto_usa_el_repositorio_firmado_y_openssh(): void
    {
        $script = $this->leer('deploy/preparar-acceso-remoto.sh');

        $this->assertMatchesRegularExpression('#^[^\#\n]*signed-by=\S+[^\n]*https://pkgs\.tailscale\.com/stable/ubuntu#m', $script, 'El repositorio de Tailscale debe declararse con signed-by.');
        $this->assertDoesNotMatchRegularExpression('/\|\s*(sudo\s+)?(ba)?sh\b/', $script, 'No se ejecuta ningún script descargado.');
        // Anclados a la orden real (/review R8): un echo que la imita no cuenta.
        $this->assertMatchesRegularExpression('#^\s*curl -fsSL "https://pkgs\.tailscale\.com/stable/ubuntu/\$\{\w+\}\.noarmor\.gpg" -o "\$\{\w+\}"$#m', $script, 'La clave del repositorio se descarga a un archivo, sin ejecutarla.');
        $this->assertMatchesRegularExpression('/^\s*tailscale up\b[^\n]*--ssh=false/m', $script, 'tailscale up debe desactivar el SSH de Tailscale.');
        // El repositorio de Tailscale no publica "a=" (archive), solo "o=" y "n=":
        // Allowed-Origins ("origen:archivo") no lo cubre, Origins-Pattern sí (T041).
        // El patrón va dentro del archivo de actualizaciones que escribe el script.
        $this->assertMatchesRegularExpression(
            '/^\s*cat > "\$\{ACTUALIZACIONES\}" <<CONF\n(?:(?!CONF\n)[^\n]*\n)*?Unattended-Upgrade::Origins-Pattern \{\n\s*"origin=\$\{\w+\},codename=\$\{\w+\}";\n\};\nCONF$/m',
            $script,
            'El origen de Tailscale (origen y nombre de la versión) debe entrar en las actualizaciones automáticas.',
        );
        $this->assertMatchesRegularExpression('/^\s*ufw allow in on tailscale0 to any port 22 proto tcp$/m', $script);
    }

    /**
     * Spec 032 (CA1, D10): app lee el log de acceso de nginx, en solo lectura, para que el
     * vigilante cuente las respuestas 5xx (también las 502/504 que no pasan por Laravel).
     */
    public function test_app_monta_el_log_de_nginx_en_solo_lectura(): void
    {
        $servicios = $this->servicios($this->leer('docker-compose.prod.yml'));

        $this->assertMatchesRegularExpression('/^      - nginx_logs:\/var\/log\/nginx:ro$/m', $servicios['app'], 'app monta nginx_logs en solo lectura.');
        $this->assertMatchesRegularExpression('/^      - nginx_logs:\/var\/log\/nginx$/m', $servicios['web'], 'web sigue escribiendo en nginx_logs.');
    }

    /**
     * Spec 032 (CA8, TM1, TM9; /analyze A18): ninguna clave (`*.asc`) ni copia cifrada (`*.gpg`)
     * entra en el repo ni en el paquete, en ninguna carpeta.
     */
    public function test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo(): void
    {
        $dockerignore = array_map('trim', explode("\n", $this->leer('.dockerignore')));
        $gitignore = array_map('trim', explode("\n", $this->leer('.gitignore')));

        foreach (['**/*.asc', '**/*.gpg'] as $exclusion) {
            $this->assertContains($exclusion, $dockerignore, ".dockerignore no excluye {$exclusion}.");
        }
        foreach (['*.asc', '*.gpg'] as $exclusion) {
            $this->assertContains($exclusion, $gitignore, ".gitignore no ignora {$exclusion}.");
        }
    }

    /** Posición de `$aguja` en `$texto`; falla si no está. */
    private function posicion(string $texto, string $aguja, string $mensaje): int
    {
        $posicion = strpos($texto, $aguja);
        $this->assertNotFalse($posicion, $mensaje);

        return $posicion;
    }

    /**
     * Spec 032 (CA7, CA8, TM1; D2): la copia se cifra para la clave pública del responsable.
     * El servidor solo tiene esa clave: puede cifrar, pero no descifrar.
     */
    public function test_respaldar_cifra_con_la_clave_publica(): void
    {
        $ordenes = $this->ordenes('deploy/respaldar.sh');

        $this->assertMatchesRegularExpression('/^CLAVE_PUBLICA="\$\{DENTISSA_CLAVE_PUBLICA:-\/opt\/dentissa\/entorno\/copias\.pub\.asc\}"$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^\| gpg [^\n]*--trust-model always --recipient-file "\$\{CLAVE_PUBLICA\}" --output "\$\{parte\}" --encrypt\b/m', $ordenes, 'Cifra para la clave pública, hacia el archivo parcial.');
        $this->assertDoesNotMatchRegularExpression('/--symmetric|--passphrase|\bgpg\b[^\n]*\s-c\b/', $ordenes, 'Nunca con una contraseña simétrica, que tendría que estar en el servidor.');
    }

    /**
     * Spec 032 (CA7, TM2; D2, /analyze A8): el volcado no existe en claro en el disco. La
     * tubería va de mariadb-dump a gpg; la línea final la guarda un miembro de la tubería
     * (awk), que el script ya ha esperado al terminar, a diferencia de una sustitución de
     * procesos `>(…)`.
     */
    public function test_respaldar_no_escribe_en_claro(): void
    {
        $ordenes = $this->ordenes('deploy/respaldar.sh');

        $this->assertMatchesRegularExpression('/^set -euo pipefail$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^copia="\$\{DESTINO\}\/dentissa-\$\(date \+%Y%m%d-%H%M%S\)\.sql\.gz\.gpg"$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^parte="\$\{copia\}\.part"$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^marca="\$\(mktemp -p \/dev\/shm dentissa-marca\.XXXXXX\)"$/m', $ordenes, 'La marca vive en memoria.');
        $this->assertMatchesRegularExpression(
            '/mariadb-dump[^\n]*\\\\\n\| awk -v marca="\$\{marca\}" \'\{ print \} END \{ print > marca \}\' \\\\\n\| gzip \\\\\n\| gpg /',
            $ordenes,
            'mariadb-dump | awk | gzip | gpg, sin archivos intermedios.',
        );
        $this->assertStringNotContainsString('>(', $ordenes, 'Sin sustitución de procesos (/analyze A8).');
        $this->assertDoesNotMatchRegularExpression('/>\s*"?\$\{?(copia|parte)\b/', $ordenes, 'Nada se redirige a la copia: la escribe gpg.');
        $this->assertStringNotContainsString('gzip -cd', $ordenes, 'La copia no se vuelve a leer en claro.');

        $tuberia = $this->posicion($ordenes, '| gpg ', 'Falta la tubería de cifrado.');
        $comprobacion = $this->posicion($ordenes, 'grep -q -- \'-- Dump completed\' "${marca}"', 'Falta la comprobación de la línea final.');
        $mover = $this->posicion($ordenes, 'mv -- "${parte}" "${copia}"', 'Falta el paso del archivo parcial a la copia.');
        $this->assertGreaterThan($tuberia, $comprobacion, 'La línea final se comprueba después de la tubería.');
        $this->assertGreaterThan($comprobacion, $mover, 'La copia solo se da por buena tras comprobarla.');
    }

    /** Spec 032 (CA7; D2): sin gpg o sin clave pública válida no se vuelca nada. */
    public function test_respaldar_se_detiene_sin_gpg_o_sin_clave(): void
    {
        $ordenes = $this->ordenes('deploy/respaldar.sh');
        $volcado = $this->posicion($ordenes, 'mariadb-dump', 'Falta el volcado.');

        foreach ([
            'command -v gpg > /dev/null || error ',
            '[[ -r "${CLAVE_PUBLICA}" ]] || error ',
            'gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}"',
        ] as $comprobacion) {
            $this->assertLessThan($volcado, $this->posicion($ordenes, $comprobacion, "Falta «{$comprobacion}»."), "«{$comprobacion}» va antes del volcado.");
        }
        $this->assertMatchesRegularExpression('/grep -q \'\^pub:\'[^\n]*\|\| error /', $ordenes, 'Exige una clave pública.');
        $this->assertMatchesRegularExpression('/grep -q \'\^sec:\'[^\n]*&& error /', $ordenes, 'Rechaza un archivo con clave secreta (CA8).');
    }

    /** Spec 032 (D2): se conservan las 14 copias cifradas más recientes. */
    public function test_respaldar_conserva_14_copias_cifradas(): void
    {
        $ordenes = $this->ordenes('deploy/respaldar.sh');

        $this->assertMatchesRegularExpression('/^CONSERVAR=14$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^find "\$\{DESTINO\}" -maxdepth 1 -name \'dentissa-\*\.sql\.gz\.gpg\' /m', $ordenes);
        $this->assertMatchesRegularExpression('/^echo "\$\{copia\}"\n\}\nmain "\$@"$/', substr($ordenes, strrpos($ordenes, 'echo "${copia}"') ?: 0), 'La última línea es la ruta de la copia.');
    }
}
