#!/usr/bin/env bash
#
# Spec 030 (CA6, TM8, TM11) y spec 032 (CA7, CA8; TM1, TM2; D2): copia de
# seguridad cifrada de la base de producción.
#
#     bash deploy/respaldar.sh
#
# Deja /var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz.gpg (permisos 600),
# cifrada para la clave pública del responsable, y conserva las 14 más
# recientes. El servidor solo tiene esa clave: puede cifrar, pero no descifrar
# (se restaura desde el PC con deploy/restaurar.sh).
#
# El volcado se hace dentro del contenedor db con las credenciales de su propio
# entorno y va por una tubería hasta gpg: nunca existe en claro en el disco. Sin
# gpg o sin una clave pública válida se detiene antes de volcar (y, desde
# desplegar.sh, antes de cambiar nada). Imprime la ruta de la copia en la última
# línea.

set -euo pipefail

DESTINO="${DENTISSA_BACKUP_DIR:-/var/backups/dentissa}"
CLAVE_PUBLICA="${DENTISSA_CLAVE_PUBLICA:-/opt/dentissa/entorno/copias.pub.asc}"
CONSERVAR=14

error() {
    echo "ERROR: $*" >&2
    exit 1
}

main() {
    cd "$(dirname "$(readlink -f "$0")")/.."
    local compose=(docker compose -f docker-compose.prod.yml)

    [[ -d "${DESTINO}" ]] || error "no existe ${DESTINO} (lo crea deploy/preparar-servidor.sh)"

    # Con qué cifrar, antes de volcar nada (CA7).
    command -v gpg > /dev/null || error "falta gpg: sin él la copia no se hace (sudo apt install gnupg)"
    [[ -r "${CLAVE_PUBLICA}" ]] || error "no se puede leer la clave pública ${CLAVE_PUBLICA}: la copia no se hace en claro (deploy/README.md §7)"
    local claves
    claves="$(gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}" 2> /dev/null)" || error "${CLAVE_PUBLICA} no es una clave OpenPGP"
    # La secreta nunca está en el servidor (CA8).
    grep -q '^sec:' <<< "${claves}" && error "${CLAVE_PUBLICA} contiene una clave SECRETA: quítala del servidor y deja solo la pública"
    grep -q '^pub:' <<< "${claves}" || error "${CLAVE_PUBLICA} no tiene una clave pública"

    [[ -n "$("${compose[@]}" ps -q db)" ]] || error "el servicio db no está en marcha"

    umask 077
    local copia
    copia="${DESTINO}/dentissa-$(date +%Y%m%d-%H%M%S).sql.gz.gpg"
    # parte y marca son globales: el trap se ejecuta al salir, cuando main() ya
    # no existe. La marca solo guarda la última línea del volcado (sin datos),
    # en memoria.
    parte="${copia}.part"
    marca="$(mktemp -p /dev/shm dentissa-marca.XXXXXX)"
    trap 'rm -f -- "${parte}" "${marca}"' EXIT

    echo "Copiando y cifrando la base en ${copia}…"
    # awk es un miembro de la tubería: cuando termina, la marca ya está escrita
    # (una sustitución de procesos no se puede esperar, /analyze A8).
    "${compose[@]}" exec -T db sh -c \
        'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --single-transaction --routines --triggers --events "$MARIADB_DATABASE"' \
        | awk -v marca="${marca}" '{ print } END { print > marca }' \
        | gzip \
        | gpg --batch --yes --quiet --trust-model always --recipient-file "${CLAVE_PUBLICA}" --output "${parte}" --encrypt \
        || error "falló el volcado o el cifrado de la base"

    # mariadb-dump termina con "-- Dump completed": sin esa línea la copia está cortada.
    grep -q -- '-- Dump completed' "${marca}" || error "la copia quedó vacía o incompleta"
    mv -- "${parte}" "${copia}"
    chmod 600 "${copia}"

    # Conserva solo las más recientes.
    find "${DESTINO}" -maxdepth 1 -name 'dentissa-*.sql.gz.gpg' -printf '%T@ %p\n' \
        | sort -rn | tail -n +$((CONSERVAR + 1)) | cut -d' ' -f2- \
        | xargs -r rm -f --

    ls -l "${copia}"
    echo "${copia}"
}

main "$@"
