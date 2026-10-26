#!/usr/bin/env bash
#
# Spec 032 (CA10; D5, decisión del usuario 2026-10-23): cifra las copias en
# claro de antes de la 032 y borra las originales, para que no quede ninguna en
# el servidor. Se ejecuta una vez, en la liberación de la 032 (deploy/README.md).
#
#     bash deploy/cifrar-copias-antiguas.sh
#
# Cada dentissa-*.sql.gz de /var/backups/dentissa y de la carpeta personal (que
# también revisa verificar.sh; /review R5) se comprueba (gzip válido y
# línea final del volcado) y se cifra para la clave pública del responsable:
#   - la buena pasa a dentissa-…sql.gz.gpg, con su fecha;
#   - una dañada también se cifra, como dentissa-…sql.gz.danada.gpg, para que no
#     quede nada en claro; el responsable decide luego si la borra (/analyze A16).
# Solo después de cifrar se borra la original con shred -u. En ext4 con journal,
# shred no garantiza el borrado físico: es el residuo del disco sin cifrar
# aceptado en EX3 (RS15.c).

set -euo pipefail

DESTINO="${DENTISSA_BACKUP_DIR:-/var/backups/dentissa}"
CLAVE_PUBLICA="${DENTISSA_CLAVE_PUBLICA:-/opt/dentissa/entorno/copias.pub.asc}"

error() {
    echo "ERROR: $*" >&2
    exit 1
}

main() {
    [[ -d "${DESTINO}" ]] || error "no existe ${DESTINO}"
    command -v gpg > /dev/null || error "falta gpg (sudo apt install gnupg)"
    [[ -r "${CLAVE_PUBLICA}" ]] || error "no se puede leer la clave pública ${CLAVE_PUBLICA} (deploy/README.md §7)"
    local claves
    claves="$(gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}" 2> /dev/null)" || error "${CLAVE_PUBLICA} no es una clave OpenPGP"
    grep -q '^sec:' <<< "${claves}" && error "${CLAVE_PUBLICA} contiene una clave SECRETA: deja solo la pública"
    grep -q '^pub:' <<< "${claves}" || error "${CLAVE_PUBLICA} no tiene una clave pública"

    umask 077
    shopt -s nullglob
    local cifradas=0 danadas=0 original destino
    for original in "${DESTINO}"/dentissa-*.sql.gz "${HOME}"/dentissa-*.sql.gz; do
        if gzip -t -- "${original}" 2> /dev/null && gzip -cd -- "${original}" | tail -n 1 | grep -q -- '-- Dump completed'; then
            destino="${original}.gpg"
            cifradas=$((cifradas + 1))
        else
            destino="${original%.sql.gz}.sql.gz.danada.gpg"
            danadas=$((danadas + 1))
            echo "AVISO: ${original} está dañada; se cifra como ${destino##*/}"
        fi
        gpg --batch --yes --quiet --trust-model always --recipient-file "${CLAVE_PUBLICA}" --output "${destino}.part" --encrypt -- "${original}"
        mv -- "${destino}.part" "${destino}"
        touch -r "${original}" -- "${destino}"
        chmod 600 -- "${destino}"
        shred -u -- "${original}"
        echo "Cifrada ${destino##*/}"
    done

    echo
    echo "Copias en claro cifradas: ${cifradas}; dañadas, cifradas aparte: ${danadas}."
    if [[ "${danadas}" -gt 0 ]]; then
        echo "Las .danada.gpg no se pueden restaurar (el volcado está cortado o el gzip roto); bórralas cuando ya no las quieras."
    fi
}

main "$@"
