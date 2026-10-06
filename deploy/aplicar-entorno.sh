#!/usr/bin/env bash
#
# Spec 031 (CA7, D8): aplica un cambio en un archivo de /opt/dentissa/entorno a
# su servicio. Un "up -d" normal no relee el archivo: el contenedor se recrea.
#
#     bash deploy/aplicar-entorno.sh <servicio>
#
# Vale para cualquier servicio del Compose de producción salvo db: db.env solo se
# aplica al crear la base (ver deploy/README.md). En app vuelve a generar las
# cachés de configuración y rutas. Termina con la verificación interna, que
# compara el entorno de cada servicio con el guardado. No imprime valores.

set -euo pipefail

error() {
    echo "ERROR: $*" >&2
    exit 1
}

paso() {
    echo
    echo "==> $*"
}

main() {
    local servicio="${1:-}"
    cd "$(dirname "$(readlink -f "$0")")/.."
    local compose=(docker compose -f docker-compose.prod.yml)

    [[ -n "${servicio}" ]] || error "indica el servicio: bash deploy/aplicar-entorno.sh <servicio>"
    if [[ "${servicio}" == "db" ]]; then
        error "db.env solo se aplica al crear la base; para cambiar su contraseña sigue deploy/README.md (§10)"
    fi
    local servicios
    servicios="$("${compose[@]}" config --services)" \
        || error "no se pudo leer docker-compose.prod.yml (¿faltan archivos en /opt/dentissa/entorno?)"
    grep -qx "${servicio}" <<< "${servicios}" || error "no existe el servicio ${servicio} en docker-compose.prod.yml"

    paso "Recreando ${servicio} con su archivo de entorno"
    "${compose[@]}" up -d --force-recreate --no-deps "${servicio}"

    if [[ "${servicio}" == "app" ]]; then
        paso "Cachés de configuración y rutas"
        "${compose[@]}" exec -T -u www-data app php artisan config:cache
        "${compose[@]}" exec -T -u www-data app php artisan route:cache
    fi

    paso "Verificación interna"
    bash deploy/verificar.sh --interna
}

main "$@"
