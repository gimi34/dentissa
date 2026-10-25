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

    /** Script que restaurar.sh ejecuta en el servidor para cargar la copia (heredoc CARGA). */
    private function cargaRemota(): string
    {
        $this->assertSame(1, preg_match("/^read -r -d '' CARGA <<'REMOTO' \|\| true\n(.*?)\nREMOTO$/ms", $this->leer('deploy/restaurar.sh'), $m), 'restaurar.sh define la carga remota en el heredoc CARGA.');

        return $m[1];
    }

    /**
     * Spec 032 (CA9, TM3; D3): restaurar.sh corre en el PC. Antes de pedir la confirmación y
     * de tocar nada en el servidor, descifra la copia entera hacia ningún sitio y comprueba
     * su línea final.
     */
    public function test_restaurar_comprueba_antes_de_borrar(): void
    {
        $ordenes = $this->ordenes('deploy/restaurar.sh');

        $this->assertMatchesRegularExpression('/^\[\[ "\$\{copia\}" == \*\.sql\.gz\.gpg \]\] \|\| error /m', $ordenes, 'Solo acepta copias cifradas.');
        $this->assertMatchesRegularExpression('/^gpg --status-fd 3 --decrypt -- "\$\{copia\}" 3> "\$\{estado\}" 2> \/dev\/null \| gzip -cd 2> \/dev\/null \| tail -n 1 > "\$\{final\}"$/m', $ordenes, 'La pasada de comprobación descifra y descomprime entera.');
        $this->assertMatchesRegularExpression('/^comprobar\n\[\[ -n "\$\{solo\}" \]\] && return 0$/m', $ordenes, '--solo-comprobar termina tras la comprobación.');

        $main = $this->posicion($ordenes, "\nmain() {", 'Falta main().');
        $comprobar = $this->posicion(substr($ordenes, $main), "\ncomprobar\n", 'main() no llama a comprobar.');
        $confirmar = $this->posicion(substr($ordenes, $main), 'read -r -p ', 'main() no pide la confirmación.');
        $this->assertLessThan($confirmar, $comprobar, 'Comprueba antes de pedir la confirmación.');
        foreach (['bash deploy/volver.sh', 'stop app web', '| ssh '] as $cambio) {
            $this->assertGreaterThan($confirmar, $this->posicion(substr($ordenes, $main), $cambio, "main() no hace «{$cambio}»."), "«{$cambio}» va después de la confirmación.");
        }
        $this->assertMatchesRegularExpression('/^\[\[ "\$\{respuesta\}" == "\$\{base\}" \]\] \|\| error "confirmación incorrecta; no se ha tocado nada"$/m', $ordenes);
    }

    /**
     * Spec 032 (CA9; /analyze A13, A34): cada rechazo dice por qué. gpg sale con 2 en todos;
     * se distinguen por sus líneas de estado: NO_SECKEY con el código 17 (No secret key) es una
     * copia para otra clave, y con otro código (frase incorrecta, cancelada) es la clave que no
     * se pudo usar; sin NO_SECKEY es la integridad (alterada o cortada).
     */
    public function test_restaurar_distingue_los_rechazos(): void
    {
        $ordenes = $this->ordenes('deploy/restaurar.sh');

        foreach ([
            'la copia no es para tu clave',
            'no se pudo usar la clave (frase incorrecta o cancelada)',
            'la copia está alterada o cortada (falla la comprobación de integridad)',
            'el volcado está incompleto (falta -- Dump completed)',
        ] as $mensaje) {
            $this->assertMatchesRegularExpression('/^(?:[^\n]*\b(?:then|&&|\|\|) )?rechazar "'.preg_quote($mensaje, '/').'"/m', $ordenes, "Falta el rechazo «{$mensaje}».");
        }
        $this->assertMatchesRegularExpression('/grep -q \'\^\\\\\[GNUPG:\\\\\] NO_SECKEY\' "\$\{estado\}"/', $ordenes);
        $this->assertMatchesRegularExpression('/ERROR pkdecrypt_failed/', $ordenes);
        $this->assertMatchesRegularExpression('/\$\(\( codigo & 65535 \)\) -ne 17/', $ordenes, 'El código 17 (No secret key) separa la clave ajena de la frase.');
    }

    /**
     * Spec 032 (CA9; /analyze A29, A51): la base no se toca hasta que llegan datos
     * descifrados. Si gpg falla en la segunda pasada (la caché de la frase caducó y se
     * cancela), la orden remota no recibe ninguna línea y termina sin DROP. El éxito exige
     * código 0 a los dos lados y la línea final del volcado en lo cargado.
     */
    public function test_restaurar_no_toca_la_base_sin_datos_descifrados(): void
    {
        $carga = $this->cargaRemota();
        $ordenes = $this->ordenes('deploy/restaurar.sh');

        $this->assertMatchesRegularExpression('/^set -euo pipefail$/m', $carga, 'La orden remota también usa pipefail.');
        $leer = $this->posicion($carga, 'IFS= read -r primera || exit 3', 'La orden remota espera la primera línea.');
        $borrar = $this->posicion($carga, 'DROP DATABASE', 'La orden remota recrea la base.');
        $this->assertLessThan($borrar, $leer, 'DROP solo después de recibir la primera línea.');
        $this->assertMatchesRegularExpression('/^\s*"\$\{compose\[@\]\}" exec -T db sh -c [^\n]*DROP DATABASE[^\n]*< \/dev\/null$/m', $carga, 'El DROP no consume la entrada con los datos.');
        $this->assertMatchesRegularExpression('/\{ printf \'%s\\\\n\' "\$\{primera\}"; cat; \} \\\\\n\s*\| awk -v marca="\$\{marca\}" \'\{ print \} END \{ print > marca \}\' \\\\\n\s*\| "\$\{compose\[@\]\}" exec -T db /', $carga, 'Carga la primera línea y el resto, guardando la última.');
        $this->assertMatchesRegularExpression('/grep -q -- \'-- Dump completed\' "\$\{marca\}" \|\| \{ rm -f "\$\{marca\}"; exit 4; \}/', $carga, 'Sin la línea final, la carga quedó incompleta.');

        $this->assertMatchesRegularExpression('/^set -euo pipefail$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^gpg --decrypt -- "\$\{copia\}" 2> \/dev\/null \| ssh -- "\$\{servidor\}" [^\n]*CARGA[^\n]*$\nresultado=\("\$\{PIPESTATUS\[@\]\}"\)$/m', $ordenes, 'Guarda el código de gpg y el de la carga remota.');
        $this->assertStringContainsString('la carga quedó incompleta', $ordenes);
    }

    /**
     * Spec 032 (CA8, TM4; /analyze A5): la clave secreta y su frase nunca llegan al servidor.
     * Por SSH solo viaja la salida de gpg --decrypt, y ningún script que corre en el servidor
     * descifra ni importa claves.
     */
    public function test_la_clave_nunca_llega_al_servidor(): void
    {
        $ordenes = $this->ordenes('deploy/restaurar.sh');

        foreach (['--import', '--export-secret-keys', '--pinentry-mode', '--passphrase', 'scp '] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $ordenes, "restaurar.sh no usa {$prohibido}.");
        }
        $entradas = preg_grep('/\|\s*ssh\b/', explode("\n", $ordenes));
        $this->assertCount(1, $entradas, 'Solo la carga envía datos por SSH.');
        $this->assertStringStartsWith('gpg --decrypt -- "${copia}"', (string) current($entradas));
        $this->assertMatchesRegularExpression('/^ssh -- "\$\{servidor\}" "\$\{orden\}" < \/dev\/null$/m', $ordenes, 'Las demás órdenes remotas no reciben entrada.');

        foreach (glob(base_path('deploy/*.sh')) as $script) {
            if (basename($script) === 'restaurar.sh') {
                continue;
            }
            $texto = $this->ordenes('deploy/'.basename($script));
            $this->assertStringNotContainsString('--decrypt', $texto, basename($script).' no descifra en el servidor.');
            $this->assertStringNotContainsString('--import', $texto, basename($script).' no importa claves en el servidor.');
        }
    }

    /**
     * Spec 032 (/analyze A2, A43): restaurar.sh no depende de su ruta ni de la versión
     * instalada; el clon del servidor es DENTISSA_APP, y lo que viaja a la orden remota se
     * valida y se cita.
     */
    public function test_restaurar_no_depende_de_su_ruta_y_cita_lo_remoto(): void
    {
        $ordenes = $this->ordenes('deploy/restaurar.sh');

        $this->assertMatchesRegularExpression('/^APP="\$\{DENTISSA_APP:-\/opt\/dentissa\/app\}"$/m', $ordenes);
        $this->assertStringNotContainsString('dirname', $ordenes);
        $this->assertMatchesRegularExpression('/^\[\[ "\$\{APP\}" =~ \^\/\[A-Za-z0-9\._\/-\]\+\$ \]\] \|\| error /m', $ordenes, 'DENTISSA_APP es una ruta absoluta sin espacios.');
        $this->assertMatchesRegularExpression('/^\[\[ "\$\{ref\}" =~ \^\[A-Za-z0-9\._\/-\]\+\$ \]\] \|\| error /m', $ordenes, 'La versión de --volver no lleva espacios ni ;.');
        $this->assertMatchesRegularExpression('/^orden="cd \$\(printf \'%q\' "\$\{APP\}"\) && \$\(printf \'%q \' "\$@"\)"$/m', $ordenes, 'Cada argumento de la orden remota va citado.');
    }

    /**
     * Spec 032 (CA9, TM11; D4, /analyze A1, A17, A40): la vuelta con copia la dirige
     * restaurar.sh desde el PC. volver.sh ya no recibe la copia: con --sin-levantar deja app y
     * web parados y db sana, y restaurar.sh carga y levanta con sus propias órdenes, que
     * existen desde la 030, así que vale para cualquier versión de destino.
     */
    public function test_volver_con_copia_no_depende_de_la_version_de_destino(): void
    {
        $volver = $this->ordenes('deploy/volver.sh');
        $checkout = $this->posicion($volver, 'git -c advice.detachedHead=false checkout "${ref}"', 'volver.sh hace el checkout.');

        $rechazo = $this->posicion($volver, 'if [[ $# -gt 2 || ( -n "${opcion}" && "${opcion}" != "--sin-levantar" ) ]]; then', 'volver.sh rechaza cualquier otro argumento (A40).');
        $this->assertLessThan($checkout, $rechazo, 'El rechazo va antes de cambiar nada.');
        $this->assertMatchesRegularExpression('/^if \[\[ \$# -gt 2[^\n]*\nerror "[^"\n]*restaurar\.sh [^"\n]*--volver \$\{ref\}"$/m', $volver, 'El rechazo da la orden de restaurar.sh --volver.');
        $this->assertStringNotContainsString('deploy/restaurar.sh', $volver, 'volver.sh ya no restaura.');

        $sinLevantar = $this->posicion($volver, 'if [[ "${opcion}" == "--sin-levantar" ]]; then', 'volver.sh admite --sin-levantar.');
        $this->assertMatchesRegularExpression('/^if \[\[ "\$\{opcion\}" == "--sin-levantar" \]\]; then\n(?:paso "[^"\n]*"\n)?"\$\{compose\[@\]\}" stop app web\n"\$\{compose\[@\]\}" up -d db\nesperar_db\nreturn 0\nfi$/m', $volver);
        $this->assertLessThan($this->posicion($volver, '"${compose[@]}" up -d app web', 'volver.sh levanta app y web.'), $sinLevantar, '--sin-levantar sale antes de levantar app y web.');

        $aviso = $this->posicion($volver, 'if [[ ! -f app/Console/Commands/Vigilar.php ]]; then', 'volver.sh avisa del temporizador.');
        $this->assertGreaterThan($checkout, $aviso, 'El aviso mira la versión de destino.');
        $this->assertStringContainsString('sudo systemctl disable --now dentissa-vigilar.timer', $this->leer('deploy/volver.sh'));

        $restaurar = $this->ordenes('deploy/restaurar.sh');
        $preparar = $this->posicion($restaurar, 'en_servidor bash deploy/volver.sh "${ref}" --sin-levantar < /dev/null', 'restaurar.sh --volver usa volver.sh --sin-levantar.');
        $cargar = $this->posicion($restaurar, '| ssh -- "${servidor}"', 'restaurar.sh carga la copia.');
        $this->assertLessThan($cargar, $preparar, 'Primero la versión de destino, después la carga.');
        foreach ([
            'en_servidor docker compose -f docker-compose.prod.yml up -d app web < /dev/null',
            'en_servidor docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan config:cache < /dev/null',
            'en_servidor docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan route:cache < /dev/null',
            'en_servidor bash deploy/verificar.sh --interna < /dev/null',
        ] as $orden) {
            $this->assertGreaterThan($cargar, $this->posicion($restaurar, $orden, "restaurar.sh no hace «{$orden}»."), "«{$orden}» va después de la carga.");
        }
    }

    /**
     * Spec 032 (D4; /analyze A17, A32, A50): si la verificación falla, desplegar.sh propone
     * la vuelta sin copia solo si sabe que no hubo migraciones (migrate:status --pending dijo
     * «No pending migrations» y salió con 0); en la duda, la vuelta con la copia desde el PC.
     * Sin copia (primer despliegue) no propone ninguna vuelta.
     */
    public function test_desplegar_imprime_la_vuelta_del_contrato(): void
    {
        $script = $this->leer('deploy/desplegar.sh');
        $ordenes = $this->ordenes('deploy/desplegar.sh');

        $this->assertSame(1, preg_match('/^sin_migraciones_pendientes\(\) \{\n.*?^\}$/ms', $script, $funcion), 'desplegar.sh define sin_migraciones_pendientes().');
        foreach ([
            ['INFO  No pending migrations.', 0, 'si'],
            ["Migration name ........ Batch / Status\n2026_10_25_000000_nueva ... Pending", 1, 'no'],
            ['Illuminate\\Database\\QueryException: SQLSTATE[HY000] [2002] Connection refused', 1, 'no'],
            ['INFO  No pending migrations.', 1, 'no'],
        ] as [$salida, $codigo, $esperado]) {
            $prueba = 'falso() { printf "%s\n" "$SALIDA"; return "$CODIGO"; }; compose=(falso); '.$funcion[0]."\n".'if sin_migraciones_pendientes; then echo si; else echo no; fi';
            $resultado = trim((string) shell_exec('SALIDA='.escapeshellarg($salida).' CODIGO='.$codigo.' bash -c '.escapeshellarg($prueba)));
            $this->assertSame($esperado, $resultado, "Con «{$salida}» y código {$codigo}.");
        }
        $this->assertMatchesRegularExpression('/^salida="\$\("\$\{compose\[@\]\}" run --rm -u www-data app php artisan migrate:status --pending --no-ansi 2>&1\)" \|\| return 1$/m', $ordenes);

        $this->assertMatchesRegularExpression('/^local sin_migraciones="no"$/m', $ordenes);
        $deteccion = $this->posicion($ordenes, 'sin_migraciones_pendientes && sin_migraciones="si"', 'desplegar.sh cuenta las migraciones pendientes.');
        $this->assertLessThan($this->posicion($ordenes, 'php artisan migrate --force', 'desplegar.sh migra.'), $deteccion, 'Se cuentan antes de migrar.');

        $this->assertMatchesRegularExpression(
            '/^\s*if \[\[ "\$\{sin_migraciones\}" == "si" \]\]; then\n(?:\s*echo "[^\n]*"\n)*?\s*echo "    bash deploy\/volver\.sh \$\{anterior\}"\n\s*else\n(?:\s*echo "[^\n]*"\n)*?\s*echo "    scp [^"\n]*:\$\{copia\} \."\n\s*echo "    bash deploy\/restaurar\.sh \$\{copia##\*\/\} [^"\n]* --volver \$\{anterior\}"\n\s*fi$/m',
            $script,
            'Sin migraciones, volver.sh; si no, la vuelta con la copia desde el PC.',
        );
        $this->assertStringNotContainsString('volver.sh ${anterior} ${copia}', $script, 'La forma de la 030 ya no existe.');

        $this->assertSame(1, preg_match('/^\s*if \[\[ -z "\$\{copia\}" \]\]; then\n(.*?)^\s*else$/ms', $script, $primerDespliegue), 'desplegar.sh distingue el primer despliegue.');
        $this->assertStringNotContainsString('volver.sh', $primerDespliegue[1], 'Sin copia no se propone ninguna vuelta.');
        $this->assertStringNotContainsString('restaurar.sh', $primerDespliegue[1], 'Sin copia no se propone ninguna vuelta.');
    }

    /**
     * Spec 032 (CA10; D5, /analyze A16): las copias en claro de antes se cifran y solo
     * entonces se borran las originales. Una copia dañada también se cifra, con otro nombre,
     * para que no quede nada en claro.
     */
    public function test_cifrar_copias_antiguas_cifra_antes_de_borrar(): void
    {
        $ordenes = $this->ordenes('deploy/cifrar-copias-antiguas.sh');

        $this->assertMatchesRegularExpression('/^set -euo pipefail$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^for original in "\$\{DESTINO\}"\/dentissa-\*\.sql\.gz; do$/m', $ordenes);
        $this->assertMatchesRegularExpression('/^destino="\$\{original\}\.gpg"$/m', $ordenes, 'Una copia buena pasa a .sql.gz.gpg.');
        $this->assertMatchesRegularExpression('/^destino="\$\{original%\.sql\.gz\}\.sql\.gz\.danada\.gpg"$/m', $ordenes, 'Una dañada pasa a .sql.gz.danada.gpg.');

        $comprobacion = $this->posicion($ordenes, 'if gzip -t -- "${original}" 2> /dev/null && gzip -cd -- "${original}" | tail -n 1 | grep -q -- \'-- Dump completed\'; then', 'Comprueba cada copia antes de cifrarla.');
        $cifrado = $this->posicion($ordenes, 'gpg --batch --yes --quiet --trust-model always --recipient-file "${CLAVE_PUBLICA}" --output "${destino}.part" --encrypt -- "${original}"', 'Cifra hacia un archivo parcial.');
        $mover = $this->posicion($ordenes, 'mv -- "${destino}.part" "${destino}"', 'Pasa el parcial a la copia cifrada.');
        $fecha = $this->posicion($ordenes, 'touch -r "${original}" -- "${destino}"', 'Conserva la fecha de la original.');
        $borrado = $this->posicion($ordenes, 'shred -u -- "${original}"', 'Borra la original con shred.');
        $this->assertTrue($comprobacion < $cifrado && $cifrado < $mover && $mover < $fecha && $fecha < $borrado, 'Comprobar → cifrar → mover → fecha → borrar.');
        $this->assertSame(1, substr_count($ordenes, 'shred -u'), 'Solo se borra tras cifrar.');
        $this->assertDoesNotMatchRegularExpression('/^rm\b[^\n]*original/m', $ordenes);
    }

    /**
     * Spec 032 (CA1, CA2, TM10; D11): el temporizador de systemd lanza el vigilante cada
     * minuto con la cuenta de despliegue, dentro de app como www-data.
     */
    public function test_preparar_vigilancia_instala_el_temporizador(): void
    {
        $script = $this->leer('deploy/preparar-vigilancia.sh');
        $ordenes = $this->ordenes('deploy/preparar-vigilancia.sh');

        $this->assertMatchesRegularExpression('/^\[\[ "\$\{EUID\}" -eq 0 \]\] \|\| error /m', $ordenes, 'Exige root.');
        $this->assertMatchesRegularExpression('/^\[\[ -n "\$\{cuenta\}" && "\$\{cuenta\}" != "root" \]\] \|\| error /m', $ordenes, 'Usa la cuenta de despliegue que llama con sudo.');
        $this->assertSame(1, preg_match('/^cat > \/etc\/systemd\/system\/dentissa-vigilar\.service <<UNIDAD\n(.*?)\nUNIDAD$/ms', $ordenes, $servicio), 'Instala el servicio.');
        $this->assertMatchesRegularExpression('/^Type=oneshot$/m', $servicio[1]);
        $this->assertMatchesRegularExpression('/^User=\$\{cuenta\}$/m', $servicio[1]);
        $this->assertMatchesRegularExpression('#^ExecStart=/usr/bin/docker compose -f /opt/dentissa/app/docker-compose\.prod\.yml exec -T -u www-data app php artisan dentissa:vigilar$#m', $servicio[1]);
        $this->assertSame(1, preg_match('/^cat > \/etc\/systemd\/system\/dentissa-vigilar\.timer <<UNIDAD\n(.*?)\nUNIDAD$/ms', $ordenes, $temporizador), 'Instala el temporizador.');
        $this->assertMatchesRegularExpression('/^OnCalendar=minutely$/m', $temporizador[1]);
        $this->assertMatchesRegularExpression('/^WantedBy=timers\.target$/m', $temporizador[1]);
        $this->assertMatchesRegularExpression('/^systemctl daemon-reload\nsystemctl enable --now dentissa-vigilar\.timer$/m', $ordenes);
        $this->assertStringContainsString('gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}"', $script, 'Comprueba antes la clave pública.');
    }

    /** Código PHP del bloque «Configuración de producción» de verificar.sh. */
    private function configuracionDeVerificar(): string
    {
        $this->assertSame(1, preg_match('/^    config="\$\("\$\{php\[@\]\}" -r "\$\{ARRANQUE\}"\'\n(.*?)\n    \'\)"$/ms', $this->leer('deploy/verificar.sh'), $m), 'verificar.sh tiene el bloque de configuración.');

        return $m[1];
    }

    /**
     * Spec 032 (CA6; D12, /analyze A28, A42): sin el envío de avisos configurado, o con el
     * texto de ejemplo de la guía, la verificación falla. El envío exige TLS (puerto 465 con
     * TLS implícito: la contraseña de aplicación nunca viaja sin cifrar).
     */
    public function test_verificar_comprueba_el_envio_de_avisos(): void
    {
        $php = $this->configuracionDeVerificar();
        $script = $this->leer('deploy/verificar.sh');

        preg_match_all('/^\s*\$c\(/m', $php, $comprobaciones);
        $this->assertCount(16, $comprobaciones[0], 'El bloque tiene 16 comprobaciones.');
        $this->assertMatchesRegularExpression('/"\$\{lineas\}" -ne 16 \]\]/', $script);
        $this->assertStringContainsString('de 16 comprobaciones', $script);

        $this->assertMatchesRegularExpression('/^\s*\$c\(config\("mail\.default"\) === "smtp", "MAIL_MAILER=smtp"\);$/m', $php);
        $this->assertMatchesRegularExpression('/^\s*\$c\([^\n]*config\("mail\.mailers\.smtp\.host"\)[^\n]*\(int\) config\("mail\.mailers\.smtp\.port"\) === 465[^\n]*"MAIL_HOST definido y MAIL_PORT=465 \(TLS obligatorio\)"\);$/m', $php, 'Exige el host y TLS implícito (A42).');
        $this->assertMatchesRegularExpression('/^\s*\$c\([^\n]*config\("mail\.mailers\.smtp\.username"\)[^\n]*config\("mail\.mailers\.smtp\.password"\)[^\n]*\[<>\\\\\\\\s\][^\n]*"MAIL_USERNAME y MAIL_PASSWORD definidos y sin el texto de ejemplo de la guía"\);$/m', $php);
        $this->assertMatchesRegularExpression('/^\s*\$c\([^\n]*filter_var\(\(string\) config\("mail\.from\.address"\), FILTER_VALIDATE_EMAIL\)[^\n]*filter_var\(\(string\) config\("avisos\.destino"\), FILTER_VALIDATE_EMAIL\)[^\n]*"MAIL_FROM_ADDRESS y AVISOS_CORREO con formato de correo"\);$/m', $php, 'Con el remitente (A28).');
    }

    /** Spec 032 (CA6, TM9): ninguna comprobación imprime un valor de la configuración. */
    public function test_verificar_no_imprime_valores_del_correo(): void
    {
        $php = $this->configuracionDeVerificar();
        $script = $this->leer('deploy/verificar.sh');

        preg_match_all('/^\s*\$c\((.*), ("[^"\n]*")\);$/m', $php, $comprobaciones);
        $this->assertCount(16, $comprobaciones[2]);
        foreach ($comprobaciones[2] as $texto) {
            $this->assertStringNotContainsString('config(', $texto, "El texto {$texto} no lleva valores.");
            $this->assertStringNotContainsString('$', $texto, "El texto {$texto} no lleva variables.");
        }
        $this->assertDoesNotMatchRegularExpression('/\becho\b[^\n]*config\("(mail|avisos)/', $php);
        $this->assertDoesNotMatchRegularExpression('/printenv\s+(MAIL_|AVISOS_)/', $script);
    }

    /**
     * Spec 032 (CA8, CA10, TM1, TM4, TM10; D12, /analyze A3): la sección «Avisos y copias»
     * solo corre sin --interna, para que desplegar.sh y volver.sh pasen antes de instalar el
     * temporizador y de cifrar las copias antiguas.
     */
    public function test_verificar_comprueba_avisos_y_copias_fuera_de_la_interna(): void
    {
        $script = $this->leer('deploy/verificar.sh');

        $this->assertSame(1, preg_match('/^    if \[\[ "\$\{modo\}" != "--interna" \]\]; then\n        echo "== Avisos y copias \(spec 032\)"\n(.*?)^    fi$/ms', $script, $seccion), 'La sección es un bloque que no corre con --interna.');
        foreach ([
            'systemctl is-active --quiet dentissa-vigilar.timer' => 'temporizador activo',
            'gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}"' => 'clave pública válida',
            "grep -q '^sec:'" => 'sin clave secreta en el archivo',
            'gpg --list-secret-keys --with-colons' => 'llavero sin claves secretas',
            '"${compose[@]}" exec -T -u www-data app test -r /var/log/nginx/dentissa-access.log' => 'app lee el log de acceso',
        ] as $orden => $que) {
            $this->assertStringContainsString($orden, $seccion[1], "La sección comprueba: {$que}.");
        }
    }

    /** Spec 032 (CA10; /analyze A16): una copia en claro en el servidor es un FALLO. */
    public function test_verificar_falla_con_copias_en_claro(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\s*claras="\$\(find "\$\{DESTINO\}" "\$\{HOME\}" -maxdepth 1 -name \'dentissa-\*\.sql\.gz\' 2> \/dev\/null \| wc -l\)"\n\s*if \[\[ "\$\{claras\}" -eq 0 \]\]; then ok [^\n]*\n\s*else fallo [^\n]*copias en claro[^\n]*$/m',
            $this->leer('deploy/verificar.sh'),
        );
    }
}
