#!/usr/bin/env bash
#
# Spec 030 (CA7, D17) y spec 032 (CA9, TM11; D4): vuelve a la versión anterior.
#
#     bash deploy/volver.sh <tag o commit anterior>
#     bash deploy/volver.sh <tag o commit anterior> --sin-levantar
#
# Si la versión que se abandona migró la base, la vuelta va con la copia que
# dejó deploy/desplegar.sh, y desde la spec 032 esa copia está cifrada: la dirige
# deploy/restaurar.sh desde el PC del responsable, que llama a este script con
# --sin-levantar (deja app y web parados y db sana) y luego carga la copia y
# levanta. Este script ya no recibe la copia. Nunca arranca ni para cloudflared.
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
    local ref="${1:-}" opcion="${2:-}"
    [[ -n "${ref}" ]] || error "indica la versión anterior: bash deploy/volver.sh <tag o commit> [--sin-levantar]"
    # También se ejecuta a mano: la versión no puede llegar a git como una opción (/review R29).
    [[ "${ref}" =~ ^[A-Za-z0-9._/][A-Za-z0-9._/-]*$ ]] || error "la versión solo admite letras, números, . _ / y -, y no puede empezar por -"
    # La forma de la 030 (volver.sh <ref> <copia>) ya no restaura: la copia está
    # cifrada y su clave no está en el servidor (/analyze A17, A40).
    if [[ $# -gt 2 || ( -n "${opcion}" && "${opcion}" != "--sin-levantar" ) ]]; then
        error "volver.sh ya no restaura copias (spec 032). Para volver con la copia, desde el PC: bash deploy/restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor> --volver ${ref}"
    fi

    cd "$(dirname "$(readlink -f "$0")")/.."
    compose=(docker compose -f docker-compose.prod.yml)
    SECONDS=0

    paso "Obteniendo ${ref}"
    git fetch --tags origin
    git -c advice.detachedHead=false checkout "${ref}"

    # Una versión anterior a la 032 no tiene el vigilante: el temporizador
    # fallaría cada minuto.
    if [[ ! -f app/Console/Commands/Vigilar.php ]]; then
        echo "AVISO: ${ref} no tiene el vigilante de avisos (spec 032). Para su temporizador:"
        echo "    sudo systemctl disable --now dentissa-vigilar.timer"
    fi

    paso "Construyendo las imágenes"
    "${compose[@]}" build app web

    if [[ "${opcion}" == "--sin-levantar" ]]; then
        paso "Parando app y web; db queda lista para cargar la copia (restaurar.sh)"
        "${compose[@]}" stop app web
        "${compose[@]}" up -d db
        esperar_db
        return 0
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
