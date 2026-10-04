#!/usr/bin/env bash
#
# Spec 030 (CA1, CA3, CA4, CA5, CA10; TM2, TM9): verificación posterior a cada
# despliegue en producción.
#
#     bash deploy/verificar.sh --interna   # por la red de Docker (túnel parado o abierto)
#     bash deploy/verificar.sh             # además, por la dirección pública (túnel abierto)
#
# Cada comprobación imprime OK o FALLO con su nombre; sale con código distinto
# de 0 si alguna falla. No imprime ningún valor secreto.

set -uo pipefail

FALLOS=0

ok() { echo "OK    $*"; }
fallo() {
    echo "FALLO $*"
    FALLOS=$((FALLOS + 1))
}
comprobar() {
    local nombre="$1"
    shift
    if "$@" > /dev/null 2>&1; then ok "${nombre}"; else fallo "${nombre}"; fi
}

# Código PHP que arranca la aplicación dentro del contenedor app.
ARRANQUE='require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c = function ($ok, $txt) { echo ($ok ? "OK    " : "FALLO ").$txt.PHP_EOL; };'

main() {
    local modo="${1:-}"
    cd "$(dirname "$(readlink -f "$0")")/.."
    local compose=(docker compose -f docker-compose.prod.yml)

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
    "${compose[@]}" exec -T app php -r "${ARRANQUE}"'
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
    ' | tee /tmp/verificar-config.$$ || true
    FALLOS=$((FALLOS + $(grep -c '^FALLO' /tmp/verificar-config.$$ || true)))
    grep -q . /tmp/verificar-config.$$ || fallo "no se pudo leer la configuración de app"
    rm -f /tmp/verificar-config.$$

    echo "== Contraseñas publicadas en el README (CA5)"
    # Candidatas: líneas del README que son solo una palabra alfanumérica de 8 a 20
    # caracteres, sola o seguida de "contraseña …". Se pasan por la entrada estándar.
    local resultado
    resultado="$(grep -oE '^[A-Za-z0-9]{8,20}( contraseña.*)?[[:space:]]*$' README.md \
        | awk '{print $1}' \
        | "${compose[@]}" exec -T app php -r "${ARRANQUE}"'
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
        # el DNS de systemd-resolved).
        abiertos="$(ss -Htln | awk '{print $4}' | grep -vE '^(127\.[0-9.]+|\[::1\]|::1)(%[^:]+)?:' | grep -vE ':22$' || true)"
        if [[ -z "${abiertos}" ]]; then
            ok "nada escucha fuera de localhost salvo SSH"
        else
            fallo "nada escucha fuera de localhost salvo SSH: $(tr '\n' ' ' <<< "${abiertos}")"
        fi
    else
        fallo "nada escucha fuera de localhost salvo SSH (falta el comando ss)"
    fi

    echo "== PHP con local.ini (CA10)"
    local clave esperado actual
    for clave in upload_max_filesize date.timezone; do
        esperado="$(grep -E "^${clave}[[:space:]]*=" docker/nginx/php/local.ini | head -n 1 | cut -d= -f2- | tr -d '[:space:]')"
        actual="$("${compose[@]}" exec -T app php -r "echo ini_get('${clave}');" 2>/dev/null | tr -d '[:space:]')"
        if [[ -n "${esperado}" && "${actual}" == "${esperado}" ]]; then
            ok "${clave} = ${esperado}"
        else
            fallo "${clave}: esperado '${esperado}', actual '${actual}'"
        fi
    done

    if [[ "${modo}" != "--interna" ]]; then
        echo "== Dirección pública"
        local url host
        url="$("${compose[@]}" exec -T app php -r "${ARRANQUE}"' echo config("app.url");' 2>/dev/null | tail -n 1)"
        host="${url#https://}"
        host="${host%%/*}"
        if [[ -z "${host}" || "${url}" != https://* ]]; then
            fallo "APP_URL en https para comprobar la dirección pública"
        else
            comprobar "${url}/up responde 200" \
                bash -c "[[ \$(curl -s -o /dev/null -w '%{http_code}' '${url}/up') == 200 ]]"
            comprobar "/ redirige a https" \
                bash -c "curl -sI '${url}/' | grep -iqE '^location: https://'"
            comprobar "http://${host}/login responde 301 a https" \
                bash -c "curl -sI 'http://${host}/login' | grep -qE '^HTTP/[0-9.]+ 301' && curl -sI 'http://${host}/login' | grep -iqE '^location: https://'"
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
