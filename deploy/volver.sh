#!/usr/bin/env bash
#
# Spec 030 (CA7, D17): vuelve a la versión anterior.
#
#     bash deploy/volver.sh <tag o commit anterior> [copia]
#
# Si la versión que se abandona migró la base, indica la copia que dejó
# deploy/desplegar.sh: se restaura con app y web parados y antes de levantar la
# versión anterior. Nunca arranca ni para cloudflared.
#
# Todo el cuerpo va en main(), que se invoca en la última línea: el checkout no
# cambia el script a mitad.

set -euo pipefail

error() {
    echo "ERROR: $*" >&2
    exit 1
}

paso() {
    echo
    echo "==> $*"
}

esperar_db() {
    local id estado
    id="$("${compose[@]}" ps -q db)"
    for _ in $(seq 1 60); do
        estado="$(docker inspect -f '{{.State.Health.Status}}' "${id}" 2>/dev/null || true)"
        [[ "${estado}" == "healthy" ]] && return 0
        sleep 5
    done
    error "la base no está sana tras 5 minutos (docker compose -f docker-compose.prod.yml logs db)"
}

main() {
    local ref="${1:-}" copia="${2:-}"
    [[ -n "${ref}" ]] || error "indica la versión anterior: bash deploy/volver.sh <tag o commit> [copia]"
    if [[ -n "${copia}" ]]; then
        copia="$(readlink -f "${copia}")"
        [[ -f "${copia}" ]] || error "no existe la copia ${copia}"
    fi

    cd "$(dirname "$(readlink -f "$0")")/.."
    compose=(docker compose -f docker-compose.prod.yml)
    SECONDS=0

    paso "Obteniendo ${ref}"
    git fetch --tags origin
    git -c advice.detachedHead=false checkout "${ref}"

    paso "Construyendo las imágenes"
    "${compose[@]}" build app web

    if [[ -n "${copia}" ]]; then
        paso "Restaurando la base (app y web parados)"
        "${compose[@]}" stop app web
        "${compose[@]}" up -d db
        esperar_db
        bash deploy/restaurar.sh "${copia}"
    fi

    paso "Levantando app y web"
    "${compose[@]}" up -d app web

    paso "Cachés"
    "${compose[@]}" exec -T -u www-data app php artisan config:cache
    "${compose[@]}" exec -T -u www-data app php artisan route:cache

    echo
    echo "Vuelta a ${ref} en ${SECONDS} s (sin la verificación)."

    paso "Verificación interna"
    bash deploy/verificar.sh --interna
}

main "$@"
