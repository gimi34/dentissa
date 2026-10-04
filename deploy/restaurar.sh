#!/usr/bin/env bash
#
# Spec 030 (CA6, TM8): restaura una copia de deploy/respaldar.sh en la base de
# producción. Borra la base actual y la recrea desde la copia, así que queda
# exactamente como estaba al copiarla (también desaparecen las tablas creadas
# después).
#
#     bash deploy/restaurar.sh /var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz
#
# Pide escribir el nombre de la base para confirmar. Conviene parar antes app y
# web (deploy/volver.sh lo hace) para que nadie use la base a medio restaurar.

set -euo pipefail

error() {
    echo "ERROR: $*" >&2
    exit 1
}

main() {
    local copia="${1:-}"
    [[ -n "${copia}" ]] || error "indica la copia: bash deploy/restaurar.sh <archivo .sql.gz>"
    copia="$(readlink -f "${copia}")"
    [[ -f "${copia}" ]] || error "no existe la copia ${copia}"
    gzip -t "${copia}" || error "la copia ${copia} está dañada"

    cd "$(dirname "$(readlink -f "$0")")/.."
    local compose=(docker compose -f docker-compose.prod.yml)
    [[ -n "$("${compose[@]}" ps -q db)" ]] || error "el servicio db no está en marcha"

    local base
    base="$("${compose[@]}" exec -T db printenv MARIADB_DATABASE)"

    echo "Se va a BORRAR la base '${base}' y recrearla desde:"
    echo "    ${copia}"
    read -r -p "Escribe el nombre de la base para confirmar: " respuesta
    [[ "${respuesta}" == "${base}" ]] || error "confirmación incorrecta; no se ha tocado nada"

    echo "Recreando la base…"
    "${compose[@]}" exec -T db sh -c \
        'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot -e "DROP DATABASE \`$MARIADB_DATABASE\`; CREATE DATABASE \`$MARIADB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'

    echo "Cargando la copia…"
    gzip -cd "${copia}" | "${compose[@]}" exec -T db sh -c \
        'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$MARIADB_DATABASE"' \
        || error "la carga falló y la base '${base}' quedó vacía o a medias (/review R8).
La copia sigue intacta; repite la restauración con ella antes de levantar app y web:
    bash deploy/restaurar.sh ${copia}"

    echo "Restaurada '${base}' desde ${copia}."
}

main "$@"
