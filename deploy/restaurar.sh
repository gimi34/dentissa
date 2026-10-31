#!/usr/bin/env bash
#
# Spec 032 (CA8, CA9; TM3, TM4, TM11; D3, decisión del usuario 2026-10-25):
# restaura en producción una copia cifrada de deploy/respaldar.sh. SE EJECUTA EN
# EL PC del responsable (Git Bash, que trae gpg y ssh), donde está la clave
# secreta: ni la clave ni su frase llegan nunca al servidor. Por SSH solo viaja
# el volcado ya descifrado, que el servidor carga en la base sin escribirlo en
# el disco.
#
#     bash deploy/restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor>
#     bash deploy/restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor> --volver <tag o commit>
#     bash deploy/restaurar.sh <copia.sql.gz.gpg> --solo-comprobar
#
# La copia se baja antes del servidor (cifrada); ver deploy/README.md §7.
#
#   1. Comprueba la copia entera en el PC: descifra y descomprime hacia ningún
#      sitio y exige la línea final del volcado. Una copia para otra clave, una
#      frase incorrecta o cancelada, una copia alterada o cortada y un volcado
#      incompleto se rechazan, cada uno con su mensaje, sin tocar nada.
#   2. Pide escribir el nombre de la base para confirmar.
#   3. Con --volver, vuelve el servidor a esa versión con volver.sh
#      --sin-levantar; sin él, para app y web.
#   4. Descifra otra vez y envía el volcado por SSH. El servidor no toca la base
#      hasta recibir la primera línea: si gpg falla aquí (la caché de la frase
#      caducó y se cancela), la base queda como estaba.
#   5. Levanta app y web, rehace las cachés y ejecuta la verificación interna.
#
# Las órdenes del servidor van en este script y existen desde la spec 030, así
# que sirve con cualquier versión instalada. El clon del servidor es
# DENTISSA_APP (por defecto /opt/dentissa/app).

set -euo pipefail

APP="${DENTISSA_APP:-/opt/dentissa/app}"

# Carga en el servidor (bash -c, con el clon como $1). Lee el volcado
# descomprimido de la entrada y no hace DROP hasta recibir su primera línea
# (/analyze A29). Sale con 3 si no llegó nada y con 4 si lo cargado no termina
# en la línea final del volcado (/analyze A51). La marca se borra también si la
# carga falla (/review R6) y mariadb carga en modo sandbox, sin órdenes del
# sistema aunque vinieran en una copia fabricada con la clave pública (R8).
read -r -d '' CARGA <<'REMOTO' || true
set -euo pipefail
cd "$1"
compose=(docker compose -f docker-compose.prod.yml)
gzip -cd | {
    IFS= read -r primera || exit 3
    "${compose[@]}" exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot -e "DROP DATABASE \`$MARIADB_DATABASE\`; CREATE DATABASE \`$MARIADB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"' < /dev/null
    marca="$(mktemp -p /dev/shm dentissa-marca.XXXXXX)"
    trap 'rm -f -- "${marca}"' EXIT
    { printf '%s\n' "${primera}"; cat; } \
        | awk -v marca="${marca}" '{ print } END { print > marca }' \
        | "${compose[@]}" exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb --sandbox -uroot "$MARIADB_DATABASE"'
    grep -q -- '-- Dump completed' "${marca}" || { rm -f "${marca}"; exit 4; }
    rm -f "${marca}"
}
REMOTO

error() {
    echo "ERROR: $*" >&2
    exit 1
}

rechazar() {
    echo "ERROR: $*. No se ha tocado nada." >&2
    exit 2
}

paso() {
    echo
    echo "==> $*"
}

# Orden en el clon del servidor, con cada argumento citado y sin entrada
# (/analyze A43): nada de lo que se escribe aquí llega a la base.
en_servidor() {
    local orden
    orden="cd $(printf '%q' "${APP}") && $(printf '%q ' "$@")"
    ssh -- "${servidor}" "${orden}" < /dev/null
}

limpiar() {
    rm -f -- "${estado:-}" "${final:-}"
}

# Pasada 1, en el PC. Las líneas de estado de gpg distinguen los rechazos (prueba
# de concepto del 2026-10-25, gpg 2.4.9): la copia para otra clave da NO_SECKEY y
# el código 17 (No secret key); una frase incorrecta o cancelada, NO_SECKEY con
# otro código (11, 99); una alterada o cortada falla sin NO_SECKEY.
comprobar() {
    estado="$(mktemp)"
    final="$(mktemp)"
    set +e
    # Del volcado solo se guarda si termina bien, nunca su última línea (/review R7).
    gpg --status-fd 3 --decrypt -- "${copia}" 3> "${estado}" 2> /dev/null | gzip -cd 2> /dev/null | awk 'END { print (index($0, "-- Dump completed") == 1 ? "completo" : "incompleto") }' > "${final}"
    local resultado=("${PIPESTATUS[@]}")
    set -e

    if [[ "${resultado[0]}" -ne 0 ]]; then
        if grep -q '^\[GNUPG:\] NO_SECKEY' "${estado}"; then
            local codigo
            codigo="$(grep -oE '^\[GNUPG:\] ERROR pkdecrypt_failed [0-9]+' "${estado}" | grep -oE '[0-9]+$' | head -n 1 || true)"
            [[ -n "${codigo}" && $(( codigo & 65535 )) -ne 17 ]] && rechazar "no se pudo usar la clave (frase incorrecta o cancelada)"
            rechazar "la copia no es para tu clave"
        fi
        rechazar "la copia está alterada o cortada (falla la comprobación de integridad)"
    fi
    [[ "${resultado[1]}" -eq 0 ]] && grep -qx completo "${final}" || rechazar "el volcado está incompleto (falta -- Dump completed)"
}

main() {
    copia="" servidor=""
    local ref="" solo=""
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --volver)
                [[ $# -ge 2 ]] || error "falta la versión: --volver <tag o commit>"
                ref="$2"
                shift 2
                ;;
            --solo-comprobar)
                solo="si"
                shift
                ;;
            -*) error "opción desconocida: $1" ;;
            *)
                if [[ -z "${copia}" ]]; then
                    copia="$1"
                elif [[ -z "${servidor}" ]]; then
                    servidor="$1"
                else
                    error "sobra el argumento $1"
                fi
                shift
                ;;
        esac
    done

    [[ -n "${copia}" ]] || error "uso: bash deploy/restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor> [--volver <tag o commit>] [--solo-comprobar]"
    [[ "${copia}" == *.sql.gz.gpg ]] || error "solo se restauran copias cifradas (.sql.gz.gpg)"
    [[ -f "${copia}" ]] || error "no existe la copia ${copia}"
    [[ "${APP}" =~ ^/[A-Za-z0-9._/-]+$ ]] || error "DENTISSA_APP tiene que ser una ruta absoluta sin espacios"
    if [[ -n "${ref}" ]]; then
        [[ "${ref}" =~ ^[A-Za-z0-9._/][A-Za-z0-9._/-]*$ ]] || error "la versión de --volver solo admite letras, números, . _ / y -, y no puede empezar por -"
    fi
    command -v gpg > /dev/null || error "falta gpg (viene con Git para Windows)"
    trap limpiar EXIT

    paso "Comprobando la copia en este equipo (pide la frase de la clave)"
    comprobar
    [[ -n "${solo}" ]] && return 0
    echo "La copia está completa."
    [[ -n "${servidor}" ]] || error "indica el servidor: bash deploy/restaurar.sh <copia> <cuenta@servidor>"

    paso "Confirmación"
    local base respuesta
    base="$(en_servidor docker compose -f docker-compose.prod.yml exec -T db printenv MARIADB_DATABASE < /dev/null | tr -d '\r')" \
        || error "no se pudo leer el nombre de la base en ${servidor} (¿está db en marcha?)"
    echo "Se va a BORRAR la base '${base}' de ${servidor} y recrearla desde:"
    echo "    ${copia}"
    read -r -p "Escribe el nombre de la base para confirmar: " respuesta
    [[ "${respuesta}" == "${base}" ]] || error "confirmación incorrecta; no se ha tocado nada"

    local servicios
    if [[ -n "${ref}" ]]; then
        paso "Volviendo a ${ref} (app y web quedan parados)"
        en_servidor bash deploy/volver.sh "${ref}" --sin-levantar < /dev/null
    else
        # Spec 020 (D22, A68; /review R4): nada escribe en la base mientras se carga la copia. La
        # lista de servicios del servidor se lee antes de parar nada: si no se puede leer, no se toca.
        servicios="$(en_servidor docker compose -f docker-compose.prod.yml config --services < /dev/null)" || error "no se pudo leer docker-compose.prod.yml en el servidor; no se ha tocado nada"
        paso "Parando app, web y el scheduler"
        en_servidor docker compose -f docker-compose.prod.yml stop app web < /dev/null
        if grep -qxF scheduler <<< "${servicios}"; then
            en_servidor docker compose -f docker-compose.prod.yml stop scheduler < /dev/null
        fi
    fi

    paso "Cargando la copia (si la frase caducó, gpg la vuelve a pedir)"
    set +e
    gpg --decrypt -- "${copia}" 2> /dev/null | ssh -- "${servidor}" "bash -c $(printf '%q' "${CARGA}") carga $(printf '%q' "${APP}")"
    resultado=("${PIPESTATUS[@]}")
    set -e
    if [[ "${resultado[1]}" -eq 3 ]]; then
        if [[ -n "${ref}" ]]; then
            error "no llegó ningún dato descifrado (¿frase cancelada?): la base no se ha tocado, y app, web y el scheduler (si la versión lo tiene) siguen parados en ${ref}. Repite la orden, o levántalos con la versión que quieras."
        fi
        # La lista se lee donde se usa, ya sabido cómo terminó la carga (/review R4, R36).
        servicios="$(en_servidor docker compose -f docker-compose.prod.yml config --services < /dev/null)" || error "no llegó ningún dato descifrado y la base no se ha tocado, pero no se pudo leer docker-compose.prod.yml en el servidor: levanta app, web y el scheduler a mano"
        en_servidor docker compose -f docker-compose.prod.yml up -d app web < /dev/null
        # A83: también vuelve el scheduler, que estaba parado para la carga.
        if grep -qxF scheduler <<< "${servicios}"; then
            en_servidor docker compose -f docker-compose.prod.yml up -d scheduler < /dev/null
        fi
        error "no llegó ningún dato descifrado (¿frase cancelada?): la base no se ha tocado y app y web vuelven a estar en marcha."
    fi
    if [[ "${resultado[0]}" -ne 0 || "${resultado[1]}" -ne 0 ]]; then
        error "la carga quedó incompleta (gpg ${resultado[0]}, servidor ${resultado[1]}) y la base '${base}' está vacía o a medias. La copia sigue intacta: repite la orden antes de levantar app y web."
    fi

    # La versión que queda instalada (con --volver, la de destino; /review R4, R36).
    servicios="$(en_servidor docker compose -f docker-compose.prod.yml config --services < /dev/null)" || error "la copia se cargó, pero no se pudo leer docker-compose.prod.yml en el servidor: levanta app, web y el scheduler a mano"
    paso "Levantando app, web y el scheduler"
    en_servidor docker compose -f docker-compose.prod.yml up -d app web < /dev/null
    if grep -qxF scheduler <<< "${servicios}"; then
        en_servidor docker compose -f docker-compose.prod.yml up -d scheduler < /dev/null
    fi
    en_servidor docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan config:cache < /dev/null
    en_servidor docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan route:cache < /dev/null

    echo
    echo "Restaurada '${base}' desde ${copia}."

    paso "Verificación interna"
    en_servidor bash deploy/verificar.sh --interna < /dev/null || error "la verificación interna dio FALLO (la base ya está restaurada)"
}

main "$@"
