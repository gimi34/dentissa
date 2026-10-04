#!/usr/bin/env bash
#
# Spec 030 (CA6, TM8, TM11): copia de seguridad de la base de producción.
#
#     bash deploy/respaldar.sh
#
# Deja /var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz (permisos 600) y
# conserva las 14 más recientes. El volcado se hace dentro del contenedor db con
# las credenciales de su propio entorno: la contraseña nunca pasa por la línea
# de comandos del servidor. Imprime la ruta de la copia en la última línea.

set -euo pipefail

DESTINO="${DENTISSA_BACKUP_DIR:-/var/backups/dentissa}"
CONSERVAR=14

error() {
    echo "ERROR: $*" >&2
    exit 1
}

main() {
    cd "$(dirname "$(readlink -f "$0")")/.."
    local compose=(docker compose -f docker-compose.prod.yml)

    [[ -d "${DESTINO}" ]] || error "no existe ${DESTINO} (lo crea deploy/preparar-servidor.sh)"
    [[ -n "$("${compose[@]}" ps -q db)" ]] || error "el servicio db no está en marcha"

    umask 077
    local copia
    copia="${DESTINO}/dentissa-$(date +%Y%m%d-%H%M%S).sql.gz"

    echo "Copiando la base a ${copia}…"
    "${compose[@]}" exec -T db sh -c \
        'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --single-transaction --routines --triggers --events "$MARIADB_DATABASE"' \
        | gzip > "${copia}" \
        || { rm -f "${copia}"; error "falló el volcado de la base"; }

    # mariadb-dump termina con "-- Dump completed": sin esa línea la copia está cortada.
    if ! gzip -cd "${copia}" | tail -n 1 | grep -q -- '-- Dump completed'; then
        rm -f "${copia}"
        error "la copia quedó vacía o incompleta"
    fi
    chmod 600 "${copia}"

    # Conserva solo las más recientes.
    find "${DESTINO}" -maxdepth 1 -name 'dentissa-*.sql.gz' -printf '%T@ %p\n' \
        | sort -rn | tail -n +$((CONSERVAR + 1)) | cut -d' ' -f2- \
        | xargs -r rm -f --

    ls -l "${copia}"
    echo "${copia}"
}

main "$@"
