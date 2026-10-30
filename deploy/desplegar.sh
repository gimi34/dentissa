#!/usr/bin/env bash
#
# Spec 030 (CA1, CA6, D17): despliega una versión en producción.
#
#     bash deploy/desplegar.sh v0.5.0      # en producción, siempre un tag
#     bash deploy/desplegar.sh 1a2b3c4     # un commit, solo en el ensayo (D13)
#
# Orden: árbol limpio → copia de la base (salvo en el primer despliegue) →
# checkout → construcción → db sana → migraciones con la imagen nueva →
# app y web con la versión nueva → cachés → verificación interna.
# Nunca arranca ni para cloudflared: el túnel se abre a mano en la liberación.
#
# Todo el cuerpo va en main(), que se invoca en la última línea: bash lee el
# script entero antes de ejecutar, así que el checkout no lo cambia a mitad.

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

# ¿Tiene datos la base? No basta con que exista el volumen: `run --rm` de app
# (p. ej. al generar APP_KEY) lo crea vacío. Se mira su contenido con la imagen
# y el volumen del propio servicio db, sin arrancar la base.
#
# Si el volumen existe pero no se puede mirar (falta db.env, Docker falla…), se
# detiene: nunca se despliega sin copia por no haber podido comprobarlo
# (/review R2).
base_con_datos() {
    docker volume inspect "${proyecto}_db_data" > /dev/null 2>&1 || return 1
    local contenido
    contenido="$("${compose[@]}" run --rm --no-deps -T --entrypoint ls db -A /var/lib/mysql)" \
        || error "no se pudo comprobar si la base tiene datos (docker compose run db); no se despliega"
    [[ -n "${contenido}" ]]
}

# Spec 032 (D4; /analyze A32, A50): ¿sabe con certeza que no hay migraciones
# pendientes? Solo si migrate:status --pending dice "No pending migrations" y
# sale con 0 (con pendientes sale con otro código). Si no se puede saber, no:
# entonces la vuelta propuesta es la de la copia, que es la segura.
sin_migraciones_pendientes() {
    local salida
    salida="$("${compose[@]}" run --rm -u www-data app php artisan migrate:status --pending --no-ansi 2>&1)" || return 1
    grep -q 'No pending migrations' <<< "${salida}"
}

main() {
    local ref="${1:-}"
    [[ -n "${ref}" ]] || error "indica la versión: bash deploy/desplegar.sh <tag o commit>"

    cd "$(dirname "$(readlink -f "$0")")/.."
    compose=(docker compose -f docker-compose.prod.yml)
    local proyecto="dentissa-prod"
    SECONDS=0

    paso "Comprobando el árbol de trabajo"
    [[ -z "$(git status --porcelain)" ]] || error "el clon tiene cambios sin confirmar; no se despliega"
    local anterior
    anterior="$(git describe --tags --exact-match 2>/dev/null || git rev-parse --short HEAD)"
    echo "Versión actual: ${anterior}"

    paso "Copia de la base"
    local copia=""
    if base_con_datos; then
        "${compose[@]}" up -d db
        esperar_db
        copia="$(bash deploy/respaldar.sh | tee /dev/stderr | tail -n 1)" || error "falló la copia de la base; no se despliega"
    else
        echo "Primer despliegue: la base aún no tiene datos, se sigue sin copia previa."
    fi

    paso "Obteniendo ${ref}"
    git fetch --tags origin
    git -c advice.detachedHead=false checkout "${ref}"

    paso "Construyendo las imágenes"
    # Sin caché, bajando las imágenes base y con REFRESCO_SISTEMA nuevo: la capa de apt-get/apk
    # upgrade se rehace en cada despliegue y las correcciones del sistema llegan a producción
    # (spec 031, T055 y T057; RS18.a). --no-cache solo no lo garantizó para web en el servidor.
    "${compose[@]}" build --no-cache --pull --build-arg REFRESCO_SISTEMA="$(date -u +%Y%m%dT%H%M%SZ)" app web

    paso "Base de datos"
    "${compose[@]}" up -d db
    esperar_db

    paso "Migraciones (con la imagen nueva, antes de cambiar el tráfico)"
    local sin_migraciones="no"
    sin_migraciones_pendientes && sin_migraciones="si"
    "${compose[@]}" run --rm -u www-data app php artisan migrate --force

    paso "Levantando app, web y el scheduler"
    # Spec 020 (D22): --remove-orphans quita lo que esta versión ya no tiene, y el scheduler solo
    # se nombra si su Compose lo tiene.
    "${compose[@]}" up -d --remove-orphans app web
    if "${compose[@]}" config --services | grep -qxF scheduler; then
        "${compose[@]}" up -d scheduler
    fi

    paso "Cachés"
    "${compose[@]}" exec -T -u www-data app php artisan config:cache
    "${compose[@]}" exec -T -u www-data app php artisan route:cache

    echo
    echo "Despliegue de ${ref} en ${SECONDS} s (sin la verificación). Anterior: ${anterior}. Copia: ${copia:-ninguna}"

    paso "Verificación interna"
    if ! bash deploy/verificar.sh --interna; then
        echo
        if [[ -z "${copia}" ]]; then
            # Primer despliegue (/review R4): no hay versión anterior a la que
            # volver, y las contraseñas del volcado aún no están rotadas, así que
            # "contraseñas del README" falla siempre en este punto.
            echo "Primer despliegue: si el único FALLO es \"contraseñas del README\", es lo esperado."
            echo "Rota ahora las credenciales del volcado y repite la verificación:"
            echo "    docker compose -f docker-compose.prod.yml exec -u www-data app php artisan dentissa:rotar-credenciales --confirmar"
            echo "    bash deploy/verificar.sh --interna"
            echo "Si falla algo más, para el despliegue: docker compose -f docker-compose.prod.yml down"
        else
            # Si la base pudo cambiar, se vuelve con la copia, cifrada, desde el PC
            # del responsable, donde está la clave (spec 032, D3).
            if [[ "${sin_migraciones}" == "si" ]]; then
                echo "La verificación falló y no había migraciones que aplicar. Para volver a la versión anterior:"
                echo "    bash deploy/volver.sh ${anterior}"
            else
                echo "La verificación falló y la base pudo cambiar. Para volver con la copia, desde el PC (Git Bash, en el clon):"
                echo "    scp <cuenta>@<servidor>:${copia} ."
                echo "    bash deploy/restaurar.sh ${copia##*/} <cuenta>@<servidor> --volver ${anterior}"
            fi
        fi
        exit 1
    fi
}

main "$@"
