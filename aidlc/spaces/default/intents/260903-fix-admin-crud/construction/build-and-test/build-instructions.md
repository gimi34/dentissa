# Instrucciones de construcción — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: build-and-test (Construction) · **Alcance**: bugfix
**Fecha**: 2026-09-19 (revisión tras el bucle de corrección 1)

---

## Prerrequisitos

El proyecto es Laravel 12 + MariaDB y **se construye y ejecuta dentro de Docker**.
Esto no es una preferencia: el PHP del host es 8.2.12 y `vendor/` está instalado
para PHP >= 8.4 (`Dockerfile` usa `php:8.4-fpm`), así que ni `composer`, ni
`artisan`, ni `phpunit` arrancan fuera del contenedor.

- Docker Desktop en marcha.
- Node.js con npm en el host (la construcción de front-end sí corre en el host).
- Servicios levantados: `docker compose up -d`. Cuatro contenedores:
  `dentissa_app` (PHP-FPM), `dentissa_nginx`, `dentissa_db` (MariaDB) y
  `dentissa_phpmyadmin`.

## Instalación de dependencias

```bash
docker compose exec app composer install
npm install
```

`composer install` y `npm install` ya están resueltos en este repositorio; solo
se repiten tras cambiar `composer.json` o `package.json`.

## Configuración del entorno

- `.env` se deriva de `.env.example` (`composer setup` lo copia si falta).
- Clave de aplicación: `docker compose exec app php artisan key:generate`.
- El esquema del dominio **no** está en migraciones: vive en
  `database/dentissa.sql` y se carga en MariaDB por fuera de Laravel. Es la
  restricción **C1** de los requisitos.
- **Base de datos de pruebas** (añadida por el bucle de corrección 1). Las
  pruebas ya no corren contra SQLite en memoria sino contra `dentissa_test` en
  el mismo contenedor MariaDB, cargada desde el volcado:
  ```bash
  bash database/testing/bootstrap-test-db.sh
  ```
  El script es idempotente: crea la base si falta y recarga el esquema limpio.
  Basta ejecutarlo una vez, y de nuevo cuando `database/dentissa.sql` cambie.
  Sin este paso, las pruebas de dominio y de vistas fallan al conectar.
- `public/storage` debe ser una **unión de Windows**, no un enlace creado dentro
  del contenedor:
  ```
  cmd /c mklink /J public\storage storage\app\public
  ```
  Un enlace creado con `php artisan storage:link` dentro del contenedor apunta a
  `/var/www/storage/app/public`, ruta que Windows no resuelve. Ver
  `project.md` § Corrections.

## Comandos de construcción

| Paso | Comando | Dónde |
|---|---|---|
| Front-end | `npm run build` | host |
| Autoload PHP | `docker compose exec app composer dump-autoload` | contenedor |
| Limpiar cachés | `docker compose exec app php artisan view:clear && docker compose exec app php artisan route:clear` | contenedor |

La construcción de front-end es Vite (`vite build`), declarada en
`package.json` → `scripts.build`. No hay paso de compilación para PHP.

**Limpiar la caché de vistas es obligatorio tras esta corrección**: las vistas
compiladas en `storage/framework/views/` todavía referencian la ruta
`admin.usuarios.destroy`, que esta corrección elimina.

## Verificación de la construcción

```bash
npm run build                                    # debe terminar con "built in ..." y salida 0
docker compose exec app php -l routes/web.php    # sin errores de sintaxis
docker compose exec app php artisan route:list --path=admin/usuarios
bash database/testing/bootstrap-test-db.sh       # salida 0
docker compose exec app vendor/bin/phpunit       # suite completa en verde
```

La tercera comprobación debe mostrar 7 rutas de usuarios, con
`admin.usuarios.create` **antes** que `admin.usuarios.show`, sin
`admin.usuarios.destroy` y con `admin.usuarios.toggle`.

Artefactos que deja la construcción de front-end en `public/build/`:
`manifest.json`, `assets/app-*.css`, `assets/app-*.js`.

## Resolución de problemas frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| `composer`/`phpunit`/`artisan` fallan con error de versión de PHP | Se están ejecutando en el host (PHP 8.2) en vez del contenedor (PHP 8.4) | Prefijar con `docker compose exec app` |
| Error de ruta `admin.usuarios.destroy` no definida al cargar el listado | Vistas compiladas en caché de antes de la corrección | `php artisan view:clear` |
| `docker compose exec` responde que el contenedor no existe | Servicios parados | `docker compose up -d` |
| Las pruebas de dominio o de vistas fallan al conectar con la base | `dentissa_test` no existe todavía | `bash database/testing/bootstrap-test-db.sh` |
| `docker compose` no conecta con el demonio | Docker Desktop no está arrancado | Arrancar Docker Desktop y esperar a que los cuatro servicios estén en `running` |
| La huella del código fuente del flujo de trabajo falla | `public/storage` es un enlace de estilo Linux | Recrearlo como unión de Windows (ver arriba) |
