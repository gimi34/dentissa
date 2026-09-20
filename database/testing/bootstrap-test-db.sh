#!/usr/bin/env bash
#
# Arranque de la base de datos de pruebas `dentissa_test`.
#
# Crea la base `dentissa_test` dentro del contenedor `db` que ya define
# docker-compose.yml (mariadb:10.11) y la carga desde `database/dentissa.sql`,
# el mismo volcado que siembra la base de desarrollo. No se añade ningún
# servicio nuevo: el contenedor existente sirve las dos bases.
#
# El esquema del dominio (usuarios, servicios, pacientes, citas, personal,
# expedientes y las dos vistas) vive solo en ese volcado, no en migraciones de
# Laravel, y las pruebas de dominio necesitan sus claves foráneas reales. Por
# eso `phpunit.xml` apunta a esta base y las pruebas usan el trait
# `DatabaseTransactions` en lugar de `RefreshDatabase`.
#
# Es idempotente: cada ejecución deja `dentissa_test` recién recargada desde el
# volcado, así que puede ejecutarse tantas veces como haga falta.
#
# Uso (desde la raíz del repositorio, con el entorno Docker en marcha):
#
#     bash database/testing/bootstrap-test-db.sh
#
# Credenciales: son las del entorno de desarrollo local que ya están en
# docker-compose.yml y .env (root/root sobre el servicio `db`). No se introduce
# ninguna credencial nueva. Se pueden sobreescribir por variables de entorno.

set -euo pipefail

DB_SERVICE="${TEST_DB_SERVICE:-db}"
DB_NAME="${TEST_DB_NAME:-dentissa_test}"
DB_USER="${TEST_DB_USER:-root}"
DB_PASSWORD="${TEST_DB_PASSWORD:-root}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
DUMP_FILE="${REPO_ROOT}/database/dentissa.sql"

error() {
    echo "ERROR: $*" >&2
}

# --- Comprobaciones previas -------------------------------------------------
# Cada borde de integración falla aquí de forma ruidosa y con un mensaje que
# dice qué hacer, en vez de dejar que el error aflore como un fallo de conexión
# incomprensible a mitad de la suite de pruebas.

if ! command -v docker >/dev/null 2>&1; then
    error "No se encontró el ejecutable 'docker' en el PATH."
    error "Instala Docker Desktop (o Docker Engine) y vuelve a intentarlo."
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    error "'docker compose' no está disponible."
    error "Se necesita Docker Compose v2 (el subcomando 'docker compose', sin guion)."
    exit 1
fi

if [ ! -f "${DUMP_FILE}" ]; then
    error "No se encontró el volcado de esquema en: ${DUMP_FILE}"
    error "Ese archivo es la única fuente del esquema del dominio; sin él no hay base de pruebas."
    exit 1
fi

cd "${REPO_ROOT}"

if ! docker compose ps --status running --services 2>/dev/null | grep -qx "${DB_SERVICE}"; then
    error "El servicio '${DB_SERVICE}' no está en marcha."
    error "Levanta el entorno con:  docker compose up -d ${DB_SERVICE}"
    exit 1
fi

# El contenedor puede estar arrancado pero MariaDB todavía inicializándose.
# Se espera un tiempo acotado en vez de fallar en el primer intento.
echo "Esperando a que MariaDB acepte conexiones en el servicio '${DB_SERVICE}'..."
INTENTOS=30
until docker compose exec -T "${DB_SERVICE}" \
        mariadb-admin ping --silent -u"${DB_USER}" -p"${DB_PASSWORD}" >/dev/null 2>&1; do
    INTENTOS=$((INTENTOS - 1))
    if [ "${INTENTOS}" -le 0 ]; then
        error "MariaDB no respondió tras 30 intentos en el servicio '${DB_SERVICE}'."
        error "Revisa los registros con:  docker compose logs ${DB_SERVICE}"
        exit 1
    fi
    sleep 2
done

# --- Recarga limpia ---------------------------------------------------------
# DROP + CREATE garantiza que dos ejecuciones seguidas terminen igual: el
# volcado declara sus tablas sin IF NOT EXISTS, así que recargarlo sobre una
# base ya poblada fallaría.

echo "Recreando la base de datos '${DB_NAME}'..."
if ! docker compose exec -T "${DB_SERVICE}" \
        mariadb -u"${DB_USER}" -p"${DB_PASSWORD}" -e "
            DROP DATABASE IF EXISTS \`${DB_NAME}\`;
            CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        "; then
    error "No se pudo recrear la base de datos '${DB_NAME}'."
    error "Comprueba las credenciales (usuario '${DB_USER}') contra el servicio '${DB_SERVICE}'."
    exit 1
fi

# El volcado no trae CREATE DATABASE ni USE, así que la base de destino se
# selecciona en la línea de comandos.
echo "Cargando el esquema desde database/dentissa.sql..."
if ! docker compose exec -T "${DB_SERVICE}" \
        mariadb -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" < "${DUMP_FILE}"; then
    error "Falló la carga del volcado en '${DB_NAME}'."
    error "La base queda en un estado incompleto; corrige el error y vuelve a ejecutar este script."
    exit 1
fi

# --- Verificación -----------------------------------------------------------
# Una carga a medias es peor que un fallo limpio: se comprueba que las tablas
# que las pruebas necesitan existen de verdad antes de dar el visto bueno.

TABLAS_REQUERIDAS="usuarios servicios pacientes citas personal expedientes"
FALTANTES=""
for TABLA in ${TABLAS_REQUERIDAS}; do
    if ! docker compose exec -T "${DB_SERVICE}" \
            mariadb -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" \
            -e "SELECT 1 FROM \`${TABLA}\` LIMIT 1;" >/dev/null 2>&1; then
        FALTANTES="${FALTANTES} ${TABLA}"
    fi
done

if [ -n "${FALTANTES}" ]; then
    error "La base '${DB_NAME}' se cargó pero faltan tablas del dominio:${FALTANTES}"
    error "Revisa que database/dentissa.sql siga siendo un volcado completo."
    exit 1
fi

echo "Listo. La base de pruebas '${DB_NAME}' está cargada y lista."
echo "Ejecuta las pruebas con:  docker compose exec app vendor/bin/phpunit"
