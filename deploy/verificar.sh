#!/usr/bin/env bash
#
# Spec 030 (CA1, CA3, CA4, CA5, CA10; TM2, TM9) y spec 031 (CA1, CA3, CA4, CA6–CA9;
# TM1–TM5, TM8, TM9): verificación posterior a cada despliegue en producción.
#
#     bash deploy/verificar.sh --interna   # por la red de Docker (túnel parado o abierto)
#     bash deploy/verificar.sh             # además, por la dirección pública (túnel abierto)
#
# Cada comprobación imprime OK o FALLO con su nombre; sale con código distinto
# de 0 si alguna falla. No imprime ningún valor secreto.
#
# Spec 032 (CA6, CA8, CA10; D12): el envío de avisos en la configuración y, sin
# --interna, la sección «Avisos y copias». Esa sección no corre con --interna
# para que desplegar.sh y volver.sh pasen antes de instalar el temporizador y de
# cifrar las copias antiguas (/analyze A3).

set -uo pipefail

FALLOS=0
DESTINO="${DENTISSA_BACKUP_DIR:-/var/backups/dentissa}"
CLAVE_PUBLICA="${DENTISSA_CLAVE_PUBLICA:-/opt/dentissa/entorno/copias.pub.asc}"

ok() { echo "OK    $*"; }
fallo() {
    echo "FALLO $*"
    FALLOS=$((FALLOS + 1))
}
# Spec 031 (CA1, CA3, CA4): cabeceras de seguridad de una respuesta. Recibe el
# nombre de la comprobación, las cabeceras (salida de curl -D -) y "hsts" si
# además debe llevar la conexión segura obligatoria de al menos 180 días.
revisar_cabeceras() {
    local nombre="$1" cabeceras="$2" hsts="${3:-}" faltan=()
    grep -iqE "^content-security-policy:.*frame-ancestors 'none'" <<< "${cabeceras}" || faltan+=("CSP")
    grep -iqE '^x-frame-options: *DENY' <<< "${cabeceras}" || faltan+=("X-Frame-Options")
    grep -iqE '^x-content-type-options: *nosniff' <<< "${cabeceras}" || faltan+=("X-Content-Type-Options")
    grep -iqE '^referrer-policy: *strict-origin-when-cross-origin' <<< "${cabeceras}" || faltan+=("Referrer-Policy")
    grep -iqE '^x-powered-by:' <<< "${cabeceras}" && faltan+=("X-Powered-By presente")
    grep -iqE '^server:.*[0-9]' <<< "${cabeceras}" && faltan+=("Server con versión")
    if [[ "${hsts}" == "hsts" ]]; then
        local edad
        edad="$(grep -ioE '^strict-transport-security:.*max-age=[0-9]+' <<< "${cabeceras}" | grep -oE '[0-9]+$' || true)"
        if [[ -z "${edad}" || "${edad}" -lt 15552000 ]] || ! grep -iqE '^strict-transport-security:.*includeSubDomains' <<< "${cabeceras}"; then
            faltan+=("HSTS de 180 días con subdominios")
        fi
    fi
    if [[ "${#faltan[@]}" -eq 0 ]]; then
        ok "cabeceras de ${nombre}"
    else
        fallo "cabeceras de ${nombre}: ${faltan[*]}"
    fi
}

# Código PHP que arranca la aplicación dentro del contenedor app.
ARRANQUE='require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c = function ($ok, $txt) { echo ($ok ? "OK    " : "FALLO ").$txt.PHP_EOL; };'

main() {
    local modo="${1:-}"
    cd "$(dirname "$(readlink -f "$0")")/.."
    local compose=(docker compose -f docker-compose.prod.yml)
    # PHP dentro de app siempre como www-data: nada de storage queda de root
    # (/review R3).
    local php=("${compose[@]}" exec -T -u www-data app php)

    echo "== Servicios"
    local enMarcha
    enMarcha="$("${compose[@]}" ps --status running --services 2>/dev/null)"
    for servicio in db app web; do
        if grep -qx "${servicio}" <<< "${enMarcha}"; then ok "${servicio} en marcha"; else fallo "${servicio} en marcha"; fi
    done

    echo "== Salud por la red interna"
    if "${compose[@]}" exec -T web wget -q -S -O /dev/null http://127.0.0.1/up 2>&1 | grep -q " 200 "; then
        ok "/up responde 200 dentro de web"
    else
        fallo "/up responde 200 dentro de web"
    fi

    echo "== Configuración de producción (sin mostrar valores secretos)"
    # Dieciséis comprobaciones; si php termina con error o faltan líneas, las que
    # no llegaron a ejecutarse cuentan como fallo (/review R9). Las cuatro últimas
    # son del envío de avisos (spec 032): TLS implícito en el puerto 465, para que
    # la contraseña de aplicación nunca viaje sin cifrar (/analyze A42).
    local config codigo lineas
    config="$("${php[@]}" -r "${ARRANQUE}"'
        $c(app()->environment() === "production", "APP_ENV=production");
        $c(config("app.debug") === false, "APP_DEBUG=false");
        $canales = config("logging.default") === "stack" ? config("logging.channels.stack.channels") : [config("logging.default")];
        $niveles = array_unique(array_map(fn ($x) => config("logging.channels.$x.level"), (array) $canales));
        $c($niveles === ["info"], "LOG_LEVEL=info");
        $c(! empty(config("app.key")), "APP_KEY definida");
        $c(config("cache.default") !== "array", "CACHE_STORE distinto de array");
        $c(config("session.secure") === true, "SESSION_SECURE_COOKIE=true");
        $c(str_starts_with((string) config("app.url"), "https://"), "APP_URL en https");
        $c(str_starts_with((string) config("services.google.redirect"), "https://"), "GOOGLE_REDIRECT_URI en https");
        $c(config("database.connections.".config("database.default").".username") !== "root", "usuario de la base distinto de root");
        $c(! str_starts_with((string) Illuminate\Support\Facades\DB::selectOne("SELECT CURRENT_USER() AS u")->u, "root@"), "la app no se conecta como root");
        $c((bool) preg_match("/^[0-9]+-[0-9a-z]+\\.apps\\.googleusercontent\\.com\$/", (string) config("services.google.client_id")), "GOOGLE_CLIENT_ID con formato de id de Google (no el de ejemplo de la guía)");
        $secreto = (string) config("services.google.client_secret");
        $c($secreto !== "" && ! preg_match("/[<>\\s]/", $secreto), "GOOGLE_CLIENT_SECRET definido y sin el texto de ejemplo de la guía");
        $c(config("mail.default") === "smtp", "MAIL_MAILER=smtp");
        $c((string) config("mail.mailers.smtp.host") !== "" && (int) config("mail.mailers.smtp.port") === 465, "MAIL_HOST definido y MAIL_PORT=465 (TLS obligatorio)");
        $c((string) config("mail.mailers.smtp.username") !== "" && (string) config("mail.mailers.smtp.password") !== "" && ! preg_match("/[<>\\s]/", config("mail.mailers.smtp.username").config("mail.mailers.smtp.password")), "MAIL_USERNAME y MAIL_PASSWORD definidos y sin el texto de ejemplo de la guía");
        $c(filter_var((string) config("mail.from.address"), FILTER_VALIDATE_EMAIL) !== false && filter_var((string) config("avisos.destino"), FILTER_VALIDATE_EMAIL) !== false, "MAIL_FROM_ADDRESS y AVISOS_CORREO con formato de correo");
    ')"
    codigo=$?
    [[ -n "${config}" ]] && echo "${config}"
    FALLOS=$((FALLOS + $(grep -c '^FALLO' <<< "${config}" || true)))
    lineas="$(grep -cE '^(OK|FALLO) ' <<< "${config}" || true)"
    if [[ "${codigo}" -ne 0 || "${lineas}" -ne 16 ]]; then
        fallo "configuración de app: php terminó con código ${codigo} y dio ${lineas} de 16 comprobaciones"
    fi

    echo "== Cabeceras por la red interna (spec 031)"
    # curl dentro de web y sin -L: la 301 se juzga por sus propias cabeceras, sin
    # seguir la redirección (/analyze A29).
    local cabeceras ruta
    for ruta in /login /css/app.css /no-existe-verificacion; do
        cabeceras="$("${compose[@]}" exec -T web curl -sS -o /dev/null -D - "http://127.0.0.1${ruta}" 2>/dev/null | tr -d '\r')"
        revisar_cabeceras "${ruta} (interna)" "${cabeceras}"
    done
    cabeceras="$("${compose[@]}" exec -T web curl -sS -o /dev/null -D - -H "X-Forwarded-Proto: http" "http://127.0.0.1/login" 2>/dev/null | tr -d '\r')"
    if grep -qE '^HTTP/[0-9.]+ 301' <<< "${cabeceras}" && grep -iqE '^location: https://' <<< "${cabeceras}"; then
        revisar_cabeceras "la 301 de nginx (interna)" "${cabeceras}"
    else
        fallo "la petición interna con X-Forwarded-Proto: http responde 301 a https"
    fi

    echo "== Contraseñas publicadas en el README (CA5)"
    # Candidatas: líneas del README que son solo una palabra alfanumérica de 8 a 20
    # caracteres, sola o seguida de "contraseña …". Se pasan por la entrada estándar.
    local resultado
    resultado="$(grep -oE '^[A-Za-z0-9]{8,20}( contraseña.*)?[[:space:]]*$' README.md \
        | awk '{print $1}' \
        | "${php[@]}" -r "${ARRANQUE}"'
            $candidatas = array_filter(array_map("trim", explode("\n", stream_get_contents(STDIN))));
            $hashes = App\Models\Usuario::whereNotNull("password_hash")->where("password_hash", "!=", "")->pluck("password_hash");
            $n = 0;
            foreach ($candidatas as $p) { foreach ($hashes as $h) { if (Illuminate\Support\Facades\Hash::check($p, $h)) { $n++; } } }
            echo count($candidatas)." ".$n;
        ' 2>/dev/null)"
    local candidatas="${resultado% *}" aciertos="${resultado#* }"
    if [[ "${resultado}" =~ ^[0-9]+\ [0-9]+$ && "${candidatas}" -gt 0 && "${aciertos}" -eq 0 ]]; then
        ok "ninguna de las ${candidatas} contraseñas del README autentica"
    else
        fallo "ninguna contraseña del README autentica (${resultado:-sin respuesta})"
    fi

    echo "== Puertos (CA3)"
    if docker ps --filter "label=com.docker.compose.project=dentissa-prod" --format '{{.Ports}}' | grep -q -- '->'; then
        fallo "ningún puerto publicado por Compose"
    else
        ok "ningún puerto publicado por Compose"
    fi
    if command -v ss > /dev/null; then
        local abiertos
        # Locales: 127.x y ::1, también con sufijo de interfaz (127.0.0.53%lo:53,
        # el DNS de systemd-resolved). Spec 031 (D11): también las direcciones de la
        # red privada (100.64.0.0/10 y fd7a:115c:a1e0::/48), donde escucha la API
        # entre nodos de tailscaled; desde fuera de la red privada no se alcanzan.
        abiertos="$(ss -Htln | awk '{print $4}' \
            | grep -vE '^(127\.[0-9.]+|\[::1\]|::1)(%[^:]+)?:' \
            | grep -vE '^(100\.(6[4-9]|[7-9][0-9]|1[01][0-9]|12[0-7])\.[0-9.]+|\[fd7a:115c:a1e0:[0-9a-f:]*\])(%[^:]+)?:' \
            | grep -vE ':22$' || true)"
        if [[ -z "${abiertos}" ]]; then
            ok "nada escucha fuera de localhost salvo SSH"
        else
            fallo "nada escucha fuera de localhost salvo SSH: $(tr '\n' ' ' <<< "${abiertos}")"
        fi
    else
        fallo "nada escucha fuera de localhost salvo SSH (falta el comando ss)"
    fi

    echo "== Entorno aplicado (spec 031, CA7)"
    # Cada servicio del Compose en marcha, sin lista fija (/analyze A10): su entorno
    # resuelto por "compose config" contra el del contenedor. Los valores no salen
    # del proceso de python3 ni pasan por argumentos (/analyze A15, T002).
    local entorno
    entorno="$(python3 - "${compose[@]}" 2>&1 <<'PY'
import json, subprocess, sys

compose = sys.argv[1:]


def salida(*args):
    return subprocess.run(list(args), capture_output=True, text=True, check=True).stdout


def entorno(lista):
    return dict(e.split('=', 1) for e in (lista or []))


try:
    config = json.loads(salida(*compose, 'config', '--format', 'json'))
    en_marcha = set(salida(*compose, 'ps', '--status', 'running', '--services').split())
except Exception as error:
    print(f'FALLO entorno aplicado: no se pudo leer el Compose ({type(error).__name__})')
    sys.exit(1)

# El shell comprueba que haya una línea OK o FALLO por cada servicio (/review R1).
print(f"SERVICIOS {len(config['services'])}")

for nombre, servicio in sorted(config['services'].items()):
    # Un error al comparar un servicio es su FALLO, nunca un OK que falta (/review R1).
    try:
        if nombre not in en_marcha:
            print(f'OK    {nombre} parado: no se compara')
            continue
        # compose config escapa el $ literal como $$ (T002).
        esperado = {k: ('' if v is None else str(v).replace('$$', '$')) for k, v in (servicio.get('environment') or {}).items()}
        contenedor = salida(*compose, 'ps', '-q', nombre).split()[0]
        info = json.loads(salida('docker', 'inspect', contenedor))[0]
        actual = entorno(info['Config']['Env'])
        de_imagen = entorno(json.loads(salida('docker', 'image', 'inspect', info['Image']))[0]['Config'].get('Env'))
        distintas = [k for k in esperado if actual.get(k) != esperado[k]]
        sobrantes = [k for k in actual if k not in esperado and actual[k] != de_imagen.get(k)]
        n = len(distintas) + len(sobrantes)
        if n == 0:
            print(f'OK    {nombre} usa la configuración guardada')
        elif nombre == 'db':
            print(f'FALLO db usa una configuración distinta de la guardada ({n} variables): db.env solo se aplica al crear la base (ver deploy/README.md)')
        else:
            print(f'FALLO {nombre} usa una configuración distinta de la guardada ({n} variables): bash deploy/aplicar-entorno.sh {nombre}')
    except Exception as error:
        print(f'FALLO {nombre}: no se pudo comparar ({type(error).__name__})')
PY
)"
    codigo=$?
    local servicios lineas
    servicios="$(grep -E '^SERVICIOS [0-9]+$' <<< "${entorno}" | cut -d' ' -f2)"
    lineas="$(grep -cE '^(OK|FALLO) ' <<< "${entorno}" || true)"
    grep -E '^(OK|FALLO) ' <<< "${entorno}" || true
    FALLOS=$((FALLOS + $(grep -c '^FALLO' <<< "${entorno}" || true)))
    # Sin el recuento, con python3 terminado en error o con servicios sin línea, no hay OK posible.
    if [[ -z "${servicios}" || "${codigo}" -ne 0 || "${lineas}" -ne "${servicios}" ]]; then
        fallo "entorno aplicado: no se compararon todos los servicios (python3 terminó con ${codigo}; ${lineas} líneas para ${servicios:-?} servicios)"
    fi

    echo "== Administración remota (spec 031, CA8, CA9)"
    # Desde la 031 la red privada es parte de la configuración esperada: sin
    # tailscaled en marcha, FALLO a propósito (/analyze A1).
    local estado_red
    if command -v tailscale > /dev/null; then
        estado_red="$(tailscale status --json 2>/dev/null | python3 -c 'import json, sys; print(json.load(sys.stdin).get("BackendState", ""))' 2>/dev/null || true)"
        if [[ "${estado_red}" == "Running" ]]; then
            ok "red privada (tailscaled) conectada"
        else
            fallo "red privada (tailscaled) conectada (estado: ${estado_red:-desconocido})"
        fi
    else
        fallo "red privada: Tailscale no está instalado (deploy/preparar-acceso-remoto.sh)"
    fi
    # SSH no ofrece contraseña: se pide la lista de métodos sin intentar ninguno y
    # sin guardar la clave de host. Sin lista, FALLO (/analyze A5).
    local metodos
    metodos="$(ssh -o BatchMode=yes -o PubkeyAuthentication=no -o PasswordAuthentication=no \
        -o KbdInteractiveAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
        -o ConnectTimeout=5 -o LogLevel=ERROR localhost true 2>&1 \
        | grep -oE 'Permission denied \([^)]*\)' | head -n 1 || true)"
    if [[ -z "${metodos}" ]]; then
        fallo "SSH sin contraseña: no se obtuvo la lista de métodos de autenticación"
    elif grep -qE 'password|keyboard-interactive' <<< "${metodos}"; then
        fallo "SSH sin contraseña: el servidor ofrece ${metodos#Permission denied }"
    else
        ok "SSH sin contraseña: solo ofrece ${metodos#Permission denied }"
    fi

    echo "== PHP con local.ini (CA10)"
    local clave esperado actual
    for clave in upload_max_filesize post_max_size date.timezone; do
        esperado="$(grep -E "^${clave}[[:space:]]*=" docker/nginx/php/local.ini | head -n 1 | cut -d= -f2- | tr -d '[:space:]')"
        actual="$("${php[@]}" -r "echo ini_get('${clave}');" 2>/dev/null | tr -d '[:space:]')"
        if [[ -n "${esperado}" && "${actual}" == "${esperado}" ]]; then
            ok "${clave} = ${esperado}"
        else
            fallo "${clave}: esperado '${esperado}', actual '${actual}'"
        fi
    done

    if [[ "${modo}" != "--interna" ]]; then
        echo "== Avisos y copias (spec 032)"
        if systemctl is-active --quiet dentissa-vigilar.timer; then
            ok "temporizador dentissa-vigilar activo"
        else
            fallo "temporizador dentissa-vigilar activo (sudo bash deploy/preparar-vigilancia.sh)"
        fi
        local claves_publicas
        claves_publicas="$(gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}" 2> /dev/null || true)"
        if grep -q '^pub:' <<< "${claves_publicas}" && ! grep -q '^sec:' <<< "${claves_publicas}"; then
            ok "clave pública de las copias válida y sin clave secreta"
        else
            fallo "clave pública de las copias válida y sin clave secreta (${CLAVE_PUBLICA}, deploy/README.md §7)"
        fi
        if gpg --list-secret-keys --with-colons 2> /dev/null | grep -q '^sec:'; then
            fallo "el llavero del servidor no tiene claves secretas: hay alguna (gpg --list-secret-keys)"
        else
            ok "el llavero del servidor no tiene claves secretas"
        fi
        local claras
        claras="$(find "${DESTINO}" "${HOME}" -maxdepth 1 -name 'dentissa-*.sql.gz' 2> /dev/null | wc -l)"
        if [[ "${claras}" -eq 0 ]]; then ok "ninguna copia en claro en ${DESTINO} ni en la carpeta personal"
        else fallo "hay ${claras} copias en claro en ${DESTINO} o en la carpeta personal (bash deploy/cifrar-copias-antiguas.sh)"; fi
        if "${compose[@]}" exec -T -u www-data app test -r /var/log/nginx/dentissa-access.log; then
            ok "app lee el log de acceso de nginx"
        else
            fallo "app lee el log de acceso de nginx (volumen nginx_logs)"
        fi
    fi

    if [[ "${modo}" != "--interna" ]]; then
        echo "== Dirección pública"
        local url host
        url="$("${php[@]}" -r "${ARRANQUE}"' echo config("app.url");' 2>/dev/null | tail -n 1)"
        host="${url#https://}"
        host="${host%%/*}"
        if [[ -z "${host}" || "${url}" != https://* ]]; then
            fallo "APP_URL en https para comprobar la dirección pública"
        else
            # curl directo, sin bash -c: APP_URL nunca se interpreta como código (/review R10).
            local estado cabeceras
            estado="$(curl -s -o /dev/null -w '%{http_code}' "${url}/up")"
            if [[ "${estado}" == 200 ]]; then ok "${url}/up responde 200"; else fallo "${url}/up responde 200 (dio ${estado})"; fi
            if curl -sI "${url}/" | grep -iqE '^location: https://'; then
                ok "/ redirige a https"
            else
                fallo "/ redirige a https"
            fi
            # La 301 pública la genera el borde de Cloudflare ("Always Use HTTPS"): no
            # es una respuesta de la aplicación y solo se comprueba su destino
            # (spec 031, /analyze A3).
            cabeceras="$(curl -sI "http://${host}/login")"
            if grep -qE '^HTTP/[0-9.]+ 301' <<< "${cabeceras}" && grep -iqE '^location: https://' <<< "${cabeceras}"; then
                ok "http://${host}/login responde 301 a https"
            else
                fallo "http://${host}/login responde 301 a https"
            fi
            cabeceras="$(curl -sS -o /dev/null -D - "${url}/login" 2>/dev/null | tr -d '\r')"
            revisar_cabeceras "${url}/login (pública)" "${cabeceras}" hsts
        fi
    fi

    echo
    if [[ "${FALLOS}" -eq 0 ]]; then
        echo "Verificación: todo OK"
    else
        echo "Verificación: ${FALLOS} fallo(s)"
        exit 1
    fi
}

main "$@"
