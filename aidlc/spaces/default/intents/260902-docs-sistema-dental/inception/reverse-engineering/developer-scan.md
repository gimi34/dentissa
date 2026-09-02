## Developer Code Scan Results

**Repositorio**: `C:\Users\gerar\Desktop\dentissa\dentissa\dentissa` (repositorio único en la raíz del proyecto; no hay submódulos ni monorepo, por lo que ninguna ruta lleva calificador de repositorio).
**Commit analizado**: `9fcff4c635d5134d9f252b6158812c4dd15e2657` — `2026-09-02 12:33:57 -0600`, autor `gimi34`, rama `main`.
**Estado del árbol de trabajo**: limpio para todo el código de aplicación (`app/`, `resources/`, `routes/`, `database/`, `config/`, `public/`, `composer.json`, `package.json`). Los únicos cambios sin confirmar son `.gitignore`, `README.md` y los directorios del propio workflow (`.claude/`, `aidlc/`).
**Alcance**: RESCAN COMPLETO del repositorio (`./`). **Profundidad**: Comprehensive.
**Tamaño real**: 132 archivos versionados; 82 archivos `.php` versionados (45 de código y 37 plantillas Blade); 2.545 líneas en `app/`, 5.851 líneas en `resources/views/`, unas 11.371 líneas versionadas sumando PHP, CSS y JS. En disco hay 7.859 archivos `.php` porque `vendor/` está instalado pero no versionado.
**Historia**: 25 commits, 4 autores (`carlos-laurel` 14, `gimi34` 7, `abound64` 2, `guintosalmeron8@gmail.com` 2). El archivo con más rotación es `routes/web.php`, tocado en 7 commits.

### Scan Coverage

- **Analyzed deeply**:
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/CitasController.php`
- `app/Http/Controllers/ClienteCitasController.php`
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ExpedientesController.php`
- `app/Http/Controllers/GoogleAuthController.php`
- `app/Http/Controllers/PacientesController.php`
- `app/Http/Controllers/PerfilController.php`
- `app/Http/Controllers/PersonalController.php`
- `app/Http/Controllers/RegisterController.php`
- `app/Http/Controllers/ServiciosController.php`
- `app/Http/Controllers/UsuarioController.php`
- `app/Http/Middleware/PreventBackHistory.php`
- `app/Http/Middleware/RoleMiddleware.php`
- `app/Models/Servicio.php`
- `app/Models/User.php`
- `app/Models/Usuario.php`
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`
- `routes/console.php`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `database/dentissa.sql` (671 líneas, leído íntegro: 17 tablas, 2 vistas, 1 trigger, índices, claves foráneas y datos de ejemplo)
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/0001_01_01_000001_create_cache_table.php`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`
- `database/migrations/2026_07_18_195924_add_google_id_to_usuarios_table.php`
- `database/migrations/2026_07_20_000000_create_asistente_dentista_table.php`
- `database/migrations/2026_07_20_000001_add_dentista_id_to_pacientes_table.php`
- `database/seeders/DatabaseSeeder.php`
- `database/factories/UserFactory.php`
- `tests/TestCase.php`
- `tests/Unit/ExampleTest.php`
- `tests/Feature/ExampleTest.php`
- `composer.json`
- `composer.lock` (versiones resueltas extraídas paquete por paquete; 325 entradas)
- `package.json`
- `package-lock.json` (versiones resueltas extraídas)
- `vite.config.js`
- `phpunit.xml`
- `config/auth.php`
- `config/services.php`
- `config/database.php` (bloques `default`, `mysql`, `mariadb`)
- `config/session.php` (resolución del `driver`)
- `.env.example`
- `Dockerfile`
- `docker-compose.yml`
- `docker/nginx/dentissa.conf`
- `docker/nginx/php/local.ini`
- `.dockerignore`
- `.editorconfig`
- `.gitattributes`
- `.gitignore`
- `README.md`
- `public/index.php`
- `resources/js/app.js`
- `resources/js/bootstrap.js`
- `resources/css/app.css`
- `resources/views/layouts/app.blade.php` (260 líneas, íntegro)
- `resources/views/partials/badge-estado.blade.php`
- `resources/views/placeholder.blade.php`
- `resources/views/pacientes/edit.blade.php`
- `resources/views/personal/edit.blade.php`
- `resources/views/usuarios/edit.blade.php`
- `resources/views/` — barrido estructural verificado sobre las 37 plantillas: cada `@extends`, `@section`, `@include`, `@push`, `@yield`, cada llamada a `route()`, cada `<form>` con su método y su `@csrf`/`@method`, cada bloque `<script>` leído íntegro, y búsqueda exhaustiva de salida sin escapar (`{!! !!}`) y de llamadas AJAX.
- `resources/views/citas/show.blade.php`, `resources/views/pacientes/show.blade.php`, `resources/views/pacientes/index.blade.php`, `resources/views/expedientes/show.blade.php` — además del barrido, lectura línea a línea de los bloques que generan rutas dependientes del rol, para verificar sus guardas.
- `resources/views/pacientes/create.blade.php`, `resources/views/personal/create.blade.php`, `resources/views/usuarios/create.blade.php` — cabecera y formulario leídos completos (primeras 30 líneas) además del barrido estructural.

- **Skimmed only**:
- `config/app.php`, `config/cache.php`, `config/filesystems.php`, `config/logging.php`, `config/mail.php`, `config/queue.php` — sólo inventariados; son los archivos por defecto de Laravel 12 y no se leyeron línea a línea, por lo que no puedo afirmar que estén intactos.
- `resources/views/` (las 30 plantillas restantes) — cubiertas por el barrido estructural descrito arriba, pero no leídas íntegramente; su maquetación y sus textos no se verificaron línea a línea.
- `public/css/app.css`, `public/css/auth.css`, `public/css/citas.css` — 1.072 líneas de CSS artesanal; se verificó su existencia, su tamaño y el hecho de que el layout las carga, pero no el detalle de las reglas.
- `public/.htaccess`, `public/robots.txt`, `public/favicon.ico`, `public/images/` — sólo inventariados.
- `storage/` — sólo contiene archivos `.gitignore` de estructura; no hay código.
- `artisan` — sólo inventariado.
- `vendor/`, `node_modules/` — dependencias de terceros instaladas pero no versionadas; no analizadas. Sus versiones se leyeron desde los lockfiles.
- `aidlc/`, `.claude/` — artefactos y herramientas del propio workflow AI-DLC; no forman parte del sistema dental.

### Packages Found

El repositorio es un **monolito de un solo paquete**: un proyecto Laravel con un único `composer.json` y un único `package.json`. No hay workspaces, submódulos ni librerías internas. Los elementos siguientes son módulos lógicos dentro de ese único artefacto desplegable, identificados por namespace y responsabilidad.

- `App\Http\Controllers` — módulo de aplicación — PHP — 12 controladores concretos más una clase base; concentra prácticamente toda la lógica de negocio y todo el acceso a datos del sistema.
- `App\Http\Middleware` — módulo de infraestructura HTTP — PHP — `RoleMiddleware` (autorización por rol, alias `role`) y `PreventBackHistory` (cabeceras anti-caché, alias `no-cache`, aplicado a todo el grupo `web`).
- `App\Models` — módulo de dominio parcial — PHP — 3 modelos Eloquent: `Usuario` (autenticación, tabla `usuarios`), `Servicio` (tabla `servicios`) y `User` (esqueleto de Laravel, tabla `users`, **sin ninguna referencia en el código de la aplicación**).
- `App\Providers` — módulo de arranque — PHP — `AppServiceProvider` con `register()` y `boot()` vacíos.
- `routes` — módulo de enrutado — PHP — `web.php` (161 líneas, toda la superficie HTTP) y `console.php` (sólo el comando de ejemplo `inspire`).
- `resources/views` — módulo de presentación — Blade — 37 plantillas agrupadas en `auth/`, `citas/`, `cliente/`, `dashboard/`, `expedientes/`, `layouts/`, `pacientes/`, `partials/`, `personal/`, `servicios/` y `usuarios/`.
- `resources/css` y `resources/js` — módulo de assets Vite — CSS y JavaScript — cadena Tailwind 4 más axios que **sólo consume `resources/views/welcome.blade.php`**, una plantilla que ninguna ruta renderiza.
- `public/css` — módulo de assets servidos directamente — CSS — 1.072 líneas de CSS artesanal (`app.css`, `auth.css`, `citas.css`) que constituyen el estilo real de toda la aplicación.
- `database` — módulo de esquema y datos — SQL y PHP — `dentissa.sql` (volcado phpMyAdmin que es la línea base real del esquema), 6 migraciones, 1 seeder y 1 factory.
- `docker` junto con `Dockerfile` y `docker-compose.yml` — módulo de entorno local — Docker, Nginx e INI — cuatro servicios: `app` (PHP-FPM 8.4), `nginx`, `db` (MariaDB 10.11) y `phpmyadmin`.
- `tests` — módulo de pruebas — PHP — únicamente el andamiaje de Laravel (`ExampleTest` en `Unit` y en `Feature`).

### Build System

- **Type**: doble cadena de construcción — **Composer** para PHP (autoritativa) y **npm con Vite** para el frontend (prácticamente inerte, ver señal TD-9).
- **Config Files**:
- `composer.json` y `composer.lock` (325 paquetes resueltos; plataforma `php: ^8.2`)
- `package.json` y `package-lock.json` (`"type": "module"`, `private: true`)
- `vite.config.js` (plugins `laravel-vite-plugin` y `@tailwindcss/vite`; entradas `resources/css/app.css` y `resources/js/app.js`)
- `phpunit.xml` (suites `Unit` y `Feature`; `source.include` apunta a `app`; entorno de prueba con `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:`)
- `Dockerfile`, `docker-compose.yml`, `docker/nginx/dentissa.conf`, `docker/nginx/php/local.ini`
- `.editorconfig` (UTF-8, fin de línea LF, indentación de 4 espacios y 2 en YAML)
- **Build Dependencies** (relación entre pasos; no hay dependencias entre paquetes porque sólo existe uno):
- `composer setup` encadena `composer install`, copia de `.env.example` a `.env`, `artisan key:generate`, `artisan migrate --force`, `npm install` y `npm run build`. **Este guion falla contra una base de datos vacía** (señal TD-1).
- `composer dev` levanta con `npx concurrently` tres procesos en paralelo: `php artisan serve`, `php artisan queue:listen --tries=1` y `npm run dev`.
- `composer test` ejecuta `artisan config:clear` y después `artisan test` (PHPUnit).
- `npm run build` invoca `vite build` y escribe en `public/build` (ignorado por git). `npm run dev` arranca el servidor de Vite.
- Imagen Docker: `composer install --no-scripts --no-autoloader --ignore-platform-reqs` sobre `composer.json` y `composer.lock`, después copia del proyecto y `composer dump-autoload --optimize`. La imagen expone el puerto `9000` (PHP-FPM); `nginx` publica el `8000`, `phpmyadmin` el `8080` y MariaDB el `3306`.
- **Incoherencia de plataforma**: el `Dockerfile` fija `php:8.4-fpm` mientras que `composer.json` declara `php: ^8.2` y el volcado SQL se generó con PHP 8.2.12. No es un conflicto de resolución (8.4 satisface `^8.2`), pero el entorno de ejecución y el de desarrollo no están fijados a la misma versión.

### APIs Discovered

No existe ninguna API programática: **no hay `routes/api.php`, ni controladores que devuelvan JSON, ni un solo `fetch()`, `axios` o `XMLHttpRequest` en las 37 plantillas**. La aplicación es una MPA renderizada íntegramente en el servidor; toda interacción viaja por formularios HTML con `@csrf`. Las únicas superficies son HTTP-HTML internas más una integración OAuth saliente.

- **HTTP (Blade, server-rendered)** — `routes/web.php` — **61 rutas registradas** (57 efectivas, ver señales TD-11 y TD-12), organizadas en cinco grupos.
- **OAuth 2.0 saliente** — `app/Http/Controllers/GoogleAuthController.php` mediante `laravel/socialite` — 2 endpoints locales (`/auth/google`, `/auth/google/callback`) contra el proveedor `google`. Credenciales en `config/services.php` desde `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI`.
- **Health check** — `bootstrap/app.php` declara `health: '/up'` (endpoint estándar de Laravel).
- **Consola** — `routes/console.php` — 1 comando (`inspire`), sin valor de negocio. No hay tareas programadas (`Schedule::`) ni jobs en cola.

#### Superficie HTTP completa

**Públicas / autenticación** (sin middleware `auth`):

| Método | URI | Controlador y acción | Nombre |
| --- | --- | --- | --- |
| GET | `/login` | `AuthController@loginForm` | `login` |
| POST | `/login` | `AuthController@login` | — |
| POST | `/logout` | `AuthController@logout` | `logout` |
| GET | `/registro` | `RegisterController@showForm` | `register` |
| POST | `/registro` | `RegisterController@register` | — |
| GET | `/registro/contacto` | `RegisterController@contactoForm` | `register.contacto` |
| POST | `/registro/contacto` | `RegisterController@contactoStore` | — |
| GET | `/auth/google` | `GoogleAuthController@redirect` | `auth.google` |
| GET | `/auth/google/callback` | `GoogleAuthController@callback` | `auth.google.callback` |
| GET | `/` | closure de despacho por rol | — |

**Grupo `admin`** — middleware `['auth','role:admin']`, prefijo `admin`, nombres `admin.*`:

| Método | URI | Controlador y acción | Nombre |
| --- | --- | --- | --- |
| GET | `/admin` | `DashboardController@admin` | `admin.dashboard` |
| — | `/admin/servicios` | `Route::resource` sobre `ServiciosController` (7 rutas: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) | `admin.servicios.*` |
| GET | `/admin/citas` | `CitasController@index` | `admin.citas` |
| GET | `/admin/citas/crear` | `CitasController@create` | `admin.citas.create` |
| POST | `/admin/citas` | `CitasController@store` | `admin.citas.store` |
| GET | `/admin/citas/{id}` | `CitasController@show` | `admin.citas.show` |
| PATCH | `/admin/citas/{id}/estado` | `CitasController@updateEstado` | `admin.citas.estado` |
| PATCH | `/admin/citas/{id}/notas` | `CitasController@updateNotas` | `admin.citas.notas` |
| DELETE | `/admin/citas/{id}` | `CitasController@destroy` | `admin.citas.destroy` |
| GET | `/admin/pacientes` | `PacientesController@index` | `admin.pacientes` |
| GET | `/admin/pacientes/crear` | `PacientesController@create` | `admin.pacientes.create` |
| POST | `/admin/pacientes` | `PacientesController@store` | `admin.pacientes.store` |
| GET | `/admin/pacientes/{id}` | `PacientesController@show` | `admin.pacientes.show` |
| GET | `/admin/pacientes/{id}/edit` | `PacientesController@edit` | `admin.pacientes.edit` |
| PUT | `/admin/pacientes/{id}` | `PacientesController@update` | `admin.pacientes.update` |
| PATCH | `/admin/pacientes/{id}/toggle` | `PacientesController@toggleActivo` | `admin.pacientes.toggle` |
| GET | `/admin/expedientes/crear` | `ExpedientesController@create` | `admin.expedientes.create` |
| POST | `/admin/expedientes` | `ExpedientesController@store` | `admin.expedientes.store` |
| GET | `/admin/expedientes/{id}` | `ExpedientesController@show` | `admin.expedientes.show` |
| PUT | `/admin/expedientes/{id}` | `ExpedientesController@update` | `admin.expedientes.update` |
| GET | `/admin/personal` | `PersonalController@index` | `admin.personal` |
| GET | `/admin/personal/crear` | `PersonalController@create` | `admin.personal.create` |
| POST | `/admin/personal` | `PersonalController@store` | `admin.personal.store` |
| GET | `/admin/personal/{id}` | `PersonalController@show` | `admin.personal.show` |
| GET | `/admin/personal/{id}/edit` | `PersonalController@edit` | `admin.personal.edit` |
| PUT | `/admin/personal/{id}` | `PersonalController@update` | `admin.personal.update` |
| PATCH | `/admin/personal/{id}/toggle` | `PersonalController@toggleActivo` | `admin.personal.toggle` |
| POST | `/admin/personal/{id}/horarios` | `PersonalController@saveHorarios` | `admin.personal.horarios` |
| GET | `/admin/reportes` | closure que devuelve `placeholder` | `admin.reportes` |
| GET | `/admin/usuarios` | closure `placeholder` (línea 88) y `UsuarioController@index` (línea 90) | `admin.usuarios` y `admin.usuarios.index` |
| GET | `/admin/usuarios/{id}` | `UsuarioController@show` | `admin.usuarios.show` |
| GET | `/admin/usuarios/crear` | `UsuarioController@create` | `admin.usuarios.create` |
| POST | `/admin/usuarios` | `UsuarioController@store` | `admin.usuarios.store` |
| GET | `/admin/usuarios/{id}/editar` | `UsuarioController@edit` | `admin.usuarios.edit` |
| PUT | `/admin/usuarios/{id}` | `UsuarioController@update` | `admin.usuarios.update` |
| DELETE | `/admin/usuarios/{id}` | `UsuarioController@destroy` | `admin.usuarios.destroy` |

**Grupo `dentista`** — middleware `['auth','role:dentista']`, prefijo `dentista`: dashboard, `citas` (`index`, `create`, `store`, `show`, `updateEstado`), `pacientes` (`index`, `show`, `edit`, `update`) y `expedientes` (`index`, `create`, `store`, `show`, `update`). 15 rutas. Nótese que el dentista es el **único** rol con `expedientes` de índice (`dentista.expedientes`); no existe `admin.expedientes`.

**Grupo `asistente`** — middleware `['auth','role:asistente']`, prefijo `asistente`: dashboard, `doctores` (`PersonalController@misDoctores`), `citas` (`index`, `create`, `store`, `show`, `updateEstado`), `pacientes` (`index`, `create`, `store`, `show`) y `expedientes.show`. 12 rutas. El asistente **no** tiene `edit`/`update` de pacientes ni de expedientes; los controladores lo refuerzan con `abort(403)`.

**Grupo `cliente`** — middleware **sólo `auth`** (sin `role:cliente`), prefijo `cliente`: dashboard, `citas` (`index`, `show`, `cancelar`), `agendar` (`agendarForm`, `agendarStore`) y `perfil` (`edit`, `update`, `updatePassword`). 9 rutas. La ausencia de `role:cliente` es una debilidad de autorización real (señal TD-6).

### Frameworks & Libraries

La detección previa que reportaba "Vite" como framework es **incorrecta**. La pila real es la siguiente (versiones resueltas leídas de `composer.lock` y `package-lock.json`, no de los rangos declarados).

Backend (PHP):

- `laravel/framework` — `v12.62.0` — framework MVC completo: enrutado, Eloquent, Blade, validación, sesiones, colas.
- `php` — plataforma `^8.2` declarada; `php:8.4-fpm` en la imagen Docker — lenguaje.
- `laravel/socialite` — `v5.28.0` — OAuth con Google (único proveedor configurado).
- `league/oauth1-client` — `v1.11.0` — dependencia transitiva de Socialite.
- `guzzlehttp/guzzle` — `7.13.2` — cliente HTTP usado por Socialite para el intercambio de tokens.
- `nesbot/carbon` — `3.13.0` — aritmética de fechas; es la pieza central de la lógica de agenda (`isoWeekday()`, `addMinutes()`, comparaciones de solape).
- `laravel/tinker` — `v3.0.2` — REPL de desarrollo.

Herramientas de desarrollo (PHP):

- `phpunit/phpunit` — `12.5.30` — framework de pruebas.
- `laravel/pint` — `v1.29.3` — formateador PHP (sin `pint.json`, por tanto con el preset `laravel` por defecto).
- `mockery/mockery` — `1.6.12` — dobles de prueba (sin uso en el código actual).
- `nunomaduro/collision` — `v8.9.4` — informes de error en consola.
- `fakerphp/faker` — `v1.24.1` — datos falsos para el `UserFactory`.
- `laravel/pail` — `v1.2.7` — visor de logs en vivo.

Frontend (npm, todas declaradas como `devDependencies`):

- `vite` — `8.0.1` — empaquetador; **su salida no la consume ninguna vista alcanzable**.
- `tailwindcss` y `@tailwindcss/vite` — ambas `4.2.2` — CSS utilitario; sólo se aplica a `welcome.blade.php`.
- `laravel-vite-plugin` — `3.0.0` — integración de Vite con Blade mediante la directiva `@vite`.
- `axios` — `1.13.6` — registrado como `window.axios` en `resources/js/bootstrap.js`; **jamás se invoca** desde ninguna plantilla.
- `concurrently` — `9.2.1` — orquestación de procesos para `composer dev`.

Frontend real, sin gestor de paquetes:

- CSS artesanal servido desde `public/css/` (1.072 líneas). El layout lo enlaza con `asset('css/app.css')` y una cadena de caché `?v={{ filemtime(public_path('css/app.css')) }}`.
- Tipografía `Roboto` cargada desde `fonts.googleapis.com` en el layout — dependencia externa en tiempo de ejecución.
- Iconografía SVG en línea dentro de las plantillas; sin librería de iconos.
- JavaScript en línea (8 plantillas con bloques `<script>`), todo vanilla: alternar visibilidad de contraseña, asistente de registro en dos pasos, modales de confirmación de borrado, cálculo de duración de servicio y activación de días de horario.

Infraestructura:

- MariaDB `10.4.32` (origen del volcado) y `mariadb:10.11` (imagen de `docker-compose.yml`) — motor de datos, `utf8mb4_unicode_ci`, InnoDB.
- `nginx:alpine` — servidor web.
- `phpmyadmin:latest` — administración de base de datos (etiqueta flotante, no reproducible).

### Test Coverage

- **Test Directories**: `tests/Unit/`, `tests/Feature/`. Sólo contienen los tres archivos del andamiaje: `tests/TestCase.php`, `tests/Unit/ExampleTest.php` y `tests/Feature/ExampleTest.php`.
- **Test Frameworks**: PHPUnit `12.5.30`, configurado en `phpunit.xml` con las suites `Unit` y `Feature`. `mockery/mockery` está instalado pero no se usa. No hay Pest, ni Dusk, ni pruebas de frontend.
- **Coverage Config**: **ausente**. `phpunit.xml` declara `<source><include><directory>app</directory></include></source>`, que delimita el ámbito de la instrumentación, pero no hay ningún elemento `<coverage>`, ningún umbral y ninguna extensión de cobertura (Xdebug o PCOV) declarada. No se genera ni se publica informe alguno.
- **Cobertura efectiva sobre el dominio: cero.** Ninguna de las 2.545 líneas de `app/` está ejercitada por una prueba: los dos tests son el ejemplo por defecto de Laravel.
- **La suite está en rojo hoy.** `tests/Feature/ExampleTest.php` afirma que `GET /` responde `200`, pero la closure de `routes/web.php:31-41` devuelve siempre una redirección (`302`): a `/login` si no hay sesión, o al dashboard del rol si la hay. El criterio de aprobación es determinista y no se cumple. Ver señal TD-13.
- **La suite tampoco puede tocar el dominio aunque se escribieran pruebas hoy**: `phpunit.xml` fija `DB_CONNECTION=sqlite` con `DB_DATABASE=:memory:`, pero el esquema de dominio sólo existe en `database/dentissa.sql`, que es un volcado MariaDB con `DEFAULT uuid()`, `enum(...)` y un trigger — sintaxis que SQLite no acepta. Sin migraciones que reconstruyan el esquema, no hay forma de levantar una base de datos de prueba. Ver señal TD-1.

### Code Quality Indicators

- **Linting**: `laravel/pint` `v1.29.3` está instalado como dependencia de desarrollo, **pero no hay `pint.json`** (se aplicaría el preset `laravel` por defecto) y **no hay ningún guion en `composer.json` que lo invoque**. No hay ESLint, Prettier, PHPStan, Psalm ni Larastan. El único control de estilo automatizable presente es `.editorconfig` (UTF-8, LF, 4 espacios).
- **Evidencia de que el formateo no se aplica**: `CitasController.php:236-274` y `PacientesController.php:84-163` contienen bloques enteros con la indentación colapsada a la columna 0 dentro del cuerpo del método; `AuthController.php:22-37` presenta la misma anomalía. Ejecutar Pint hoy reformatearía una parte sustancial del código.
- **CI/CD**: **inexistente**. No hay `.github/`, ni `.gitlab-ci.yml`, ni `Jenkinsfile`, ni `.circleci/`, ni `bitbucket-pipelines.yml`. El único YAML del repositorio es `docker-compose.yml`. No hay hooks de pre-commit, ni badge de build real (el `README.md` conserva los badges del framework Laravel, que apuntan al repositorio de Laravel, no a este proyecto). No existe entorno de staging ni de producción declarado en ninguna parte.
- **Documentation**: el `README.md` es **el README genérico de Laravel sin personalizar** — describe el framework, no el sistema dental. No documenta el dominio, ni los roles, ni cómo levantar el proyecto con Docker, ni el hecho crítico de que el esquema vive en `database/dentissa.sql`. Al final del archivo, después de la sección de licencia, hay tres credenciales de acceso en texto plano (contraseñas de las cuentas de prueba de dentista, usuario y asistente). No existe ningún otro documento: ni `CONTRIBUTING.md`, ni `CHANGELOG.md`, ni ADRs, ni diagramas, ni carpeta `docs/`.
- **Comentarios en el código**: la densidad es moderada y de calidad desigual. Los controladores usan separadores decorativos por sección (`// ── INDEX ───`) que son ruido, pero también contienen comentarios explicativos genuinamente útiles, por ejemplo `RegisterController.php:167` («Asigna el dentista activo con menos pacientes a su cargo (balanceo simple)»), `CitasController.php:117-118` (por qué el resumen se calcula antes del filtro de estado) y `CitasController.php:357-359` (que documenta la interacción con el trigger de MySQL). En cambio `UsuarioController.php` está comentado en un registro didáctico y numerado («1. Empezamos la consulta, pero no la ejecutamos todavía») que contrasta con el resto del código. No hay bloques PHPDoc con tipos en ningún controlador, ni tipos de retorno declarados salvo en los middleware y en `RegisterController::asignarDentistaAutomatico(): ?string`.
- **Consistencia de idioma**: el dominio está íntegramente en español (tablas, columnas, rutas, variables, mensajes de validación, vistas), mientras que el andamiaje del framework permanece en inglés (`users`, `User`, `password`). La frontera entre ambos mundos es exactamente el par `users`/`usuarios` (señal TD-3).
- **Higiene de secretos**: `.env` está correctamente listado en `.gitignore` y no está versionado. Sin embargo hay dos filtraciones reales por otras vías: las credenciales en texto plano al final de `README.md` y el conjunto de datos personales con hashes de contraseña dentro de `database/dentissa.sql` (señal TD-2).

### Technical Debt Signals

**TD-1 — El esquema de dominio no es reproducible por migraciones. Riesgo: alto.**
El código consulta 9 tablas de dominio (`usuarios`, `pacientes`, `personal`, `citas`, `servicios`, `expedientes`, `historial_citas`, `horarios_dentista`, `asistente_dentista`), pero las migraciones de `database/migrations/` sólo **crean** el andamiaje de Laravel (`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`) más la tabla puente `asistente_dentista`. Las dos migraciones restantes **alteran** tablas que ninguna migración crea: `2026_07_18_195924_add_google_id_to_usuarios_table.php` hace `Schema::table('usuarios', ...)` y `2026_07_20_000001_add_dentista_id_to_pacientes_table.php` hace `Schema::table('pacientes', ...)`. Sobre una base de datos vacía, `php artisan migrate` aborta: `create_asistente_dentista_table` declara claves foráneas contra `personal`, que no existe. Por tanto **`composer setup` no funciona**: instala, genera clave y ejecuta `migrate --force` sin haber cargado nunca el volcado.
La única ruta de arranque que sí funciona es la de Docker, y funciona por una coincidencia no documentada: `docker-compose.yml:47` monta `./database/dentissa.sql` en `/docker-entrypoint-initdb.d/`, de modo que MariaDB crea el esquema completo al inicializar el volumen; y como el volcado incluye la tabla `migrations` ya poblada con las tres migraciones de andamiaje (`dentissa.sql:216-220`), un `artisan migrate` posterior las salta y aplica sólo las tres de 2026-07. Nada en el repositorio explica esta secuencia.

**TD-2 — Datos personales sensibles y credenciales versionados. Riesgo: alto.**
`database/dentissa.sql` no es un esquema: es un volcado con datos. Contiene 8 filas de `usuarios` con nombres y apellidos reales, direcciones de correo reales, teléfonos y **hashes bcrypt de contraseña** (`$2y$12$...`); 6 filas de `pacientes` con fecha de nacimiento, género, domicilio, ciudad, contacto de emergencia, **tipo de sangre y alergias** (datos de salud); y una fila de `sessions` con la carga útil de sesión de un navegador identificable. Además, `README.md` termina con tres contraseñas en texto plano para las cuentas de dentista, usuario y asistente. Todo ello está en la historia de git y es recuperable aunque se borre en un commit futuro.

**TD-3 — Dos tablas de usuarios coexistentes. Riesgo: medio.**
`config/auth.php:64-68` apunta el provider `users` al modelo `App\Models\Usuario` (tabla `usuarios`, PK `char(36)` UUID, columna de contraseña `password_hash` expuesta vía `getAuthPassword()`). Simultáneamente sobreviven la tabla `users` (PK `bigint` autoincremental, columna `password`), el modelo `App\Models\User`, `database/factories/UserFactory.php` y `database/seeders/DatabaseSeeder.php`, que siembra un `User` de prueba. Verifiqué por búsqueda que **ningún archivo de `app/`, `routes/`, `config/` ni `resources/` referencia `App\Models\User`**: es andamiaje muerto que induce a error a cualquiera que llegue nuevo al proyecto.

**TD-4 — Defecto latente en el almacenamiento de sesiones. Riesgo: medio.**
`config/session.php:21` resuelve `env('SESSION_DRIVER', 'database')`, es decir, **el valor por defecto es `database`**. `.env.example` lo fija a `file`, lo que hoy enmascara el problema. Si alguien despliega sin esa variable, Laravel escribirá en la tabla `sessions`, cuya columna `user_id` es `bigint(20) UNSIGNED` (heredada del andamiaje) mientras que `usuarios.id` es un `char(36)` UUID. Con `'strict' => true` en la conexión MySQL de `config/database.php:60`, la escritura fallará.

**TD-5 — Acceso a datos por consultas crudas dentro de los controladores. Riesgo: medio.**
El acceso a datos está construido casi por completo con el query builder dentro de los controladores: conté **111 invocaciones de `DB::table(...)`** repartidas así — `citas` 31, `personal` 22, `pacientes` 20, `usuarios` 13, `asistente_dentista` 9, `expedientes` 6, `horarios_dentista` 5, `servicios` 3, `historial_citas` 2 — más 85 `join`/`leftJoin`. Sólo dos modelos Eloquent participan (`Usuario` con 12 usos y `Servicio` con 7). No hay repositorios, ni servicios de dominio, ni Form Requests, ni Policies, ni eventos, ni jobs: `app/` sólo contiene `Http/`, `Models/` y `Providers/`. Las consecuencias medibles: la consulta base de citas (`CitasController::queryBase()`, líneas 12-40, cinco joins y dos `DB::raw`) está duplicada casi íntegra en `DashboardController::admin()` (líneas 27-44), en `DashboardController::asistente()` (líneas 149-167) y parcialmente en otros cinco lugares; y el mismo helper `dentistasDisponibles()` está copiado literalmente en `PacientesController` (líneas 327-336) y en `PersonalController` (líneas 398-408).

**TD-6 — El grupo `cliente` no verifica el rol. Riesgo: medio.**
`routes/web.php:148` declara `Route::middleware('auth')` sin `role:cliente`, a diferencia de los otros tres grupos. Cualquier usuario autenticado —incluido un `admin`, un `dentista` o un `asistente`— puede entrar a `/cliente/*`. El impacto concreto no es lectura indebida (los controladores filtran por `pacientes.usuario_id = auth()->id()` y devuelven `404` si no hay paciente), sino escritura: `PerfilController::update()` (líneas 61-69) hace un `insert` en `pacientes` cuando no encuentra fila para el usuario actual, de modo que un miembro del personal que guarde `/cliente/perfil` **crea un registro de paciente espurio a su propio nombre**.

**TD-7 — Doble escritura del historial de citas. Riesgo: medio.**
El trigger `trg_historial_cita` (`dentissa.sql:80-88`) inserta una fila en `historial_citas` con `estado_anterior` y `estado_nuevo` en cada `UPDATE` que cambie el estado. Adicionalmente, `CitasController::updateEstado()` (líneas 359-368) inserta **otra** fila cuando el usuario aporta un `motivo`, y esa segunda fila lleva `estado_anterior` en `NULL` y sí lleva `cambiado_por`. Resultado: cada cambio de estado con motivo produce dos filas de historial, ninguna de las cuales es completa por sí sola —la del trigger tiene la transición pero no el autor ni el motivo; la del controlador tiene el autor y el motivo pero no el estado anterior—. La vista `citas/show.blade.php:128-139` renderiza ambas. El propio código reconoce la duplicidad en un comentario (líneas 357-359).

**TD-8 — `ServiciosController` destruye el precio en cada escritura. Riesgo: medio.**
`ServiciosController::store()` línea 33 y `update()` línea 58 fijan incondicionalmente `$data['precio'] = 0`. La columna `servicios.precio` es `decimal(10,2)` y el volcado trae 15 servicios con precios reales (de 200,00 a 18.000,00). Cualquier edición de un servicio desde la interfaz de administración pone su precio a cero de forma silenciosa. Las dos vistas SQL `v_citas_hoy` y `v_historial_paciente` exponen `s.precio`, de modo que la pérdida se propaga a cualquier consumidor futuro de esas vistas. En la misma línea, `store()` también fuerza `color_agenda` al valor por defecto `'#F4C0D1'`, anulando la paleta por servicio que el volcado sí diferencia.

**TD-9 — Cadena de assets muerta. Riesgo: bajo, pero desorienta el diagnóstico.**
`@vite([...])` aparece **una sola vez en las 37 plantillas**, en `resources/views/welcome.blade.php:15`, y ninguna ruta renderiza `welcome`: la closure de `/` siempre redirige. Toda la interfaz real se estiliza con los 1.072 líneas de CSS artesanal de `public/css/`, enlazadas desde `layouts/app.blade.php:8` y desde siete plantillas más. Por tanto Vite 8, Tailwind 4 y axios están instalados, versionados y declarados en `vite.config.js` sin producir nada que la aplicación consuma — y son la razón por la que la detección automática de framework reportó "Vite" en lugar de Laravel.

**TD-10 — Uso invertido de `@include` entre vistas de edición y creación. Riesgo: bajo.**
`pacientes/edit.blade.php`, `personal/edit.blade.php` y `usuarios/edit.blade.php` son plantillas de 5 líneas que hacen `@extends('layouts.app')` y, dentro de `@section('content')`, un `@include` de la vista `create` correspondiente. Pero esas vistas `create` **también** hacen `@extends('layouts.app')` y definen su propia `@section('content')`. Es una anidación de layouts: el formulario compartido debería ser un partial sin `@extends`.

**TD-11 — Orden de rutas que rompe la creación de usuarios. Riesgo: medio.**
En `routes/web.php`, `GET /admin/usuarios/{id}` se registra en la línea 92 y `GET /admin/usuarios/crear` en la línea 94. Laravel resuelve por orden de registro y el parámetro `{id}` no tiene restricción (`whereUuid` o similar), de modo que la petición a `/admin/usuarios/crear` cae en `UsuarioController@show('crear')`, que ejecuta `Usuario::findOrFail('crear')` y responde `404`. El botón "Nuevo Usuario" de `usuarios/index.blade.php:5` apunta a `route('admin.usuarios.create')`, es decir, exactamente a esa URI. **El alta de usuarios desde la interfaz de administración está rota.** El resto de módulos no sufre esto porque registran `crear` antes que `{id}` (por ejemplo `citas` en las líneas 54 y 56).

**TD-12 — Ruta duplicada y closure muerta. Riesgo: bajo.**
`GET /admin/usuarios` se registra dos veces: la línea 88 como closure que devuelve la vista `placeholder` con el nombre `admin.usuarios`, y la línea 90 apuntando a `UsuarioController@index` con el nombre `admin.usuarios.index`. El registro posterior gana en el enrutado, así que la funcionalidad correcta es la que se sirve, pero el nombre `admin.usuarios` sigue registrado apuntando a la closure muerta. La otra closure de la línea 87 (`admin.reportes`) sí es un módulo declaradamente pendiente que devuelve `placeholder`.

**TD-13 — La única prueba existente falla. Riesgo: bajo en impacto, alto en señal.**
Descrita en `Test Coverage`: `tests/Feature/ExampleTest.php` espera `200` en `GET /` y la aplicación devuelve `302`. Como no hay CI, nadie lo detecta.

**TD-14 — Montaje de configuración PHP roto en Docker. Riesgo: bajo.**
`docker-compose.yml:15` monta `./docker/php/local.ini` dentro del contenedor, pero el archivo real está en `docker/nginx/php/local.ini`. Docker crea un directorio vacío en su lugar, de modo que los ajustes previstos (`upload_max_filesize = 100M`, `memory_limit = 256M`, `date.timezone = America/Mexico_City`) **no se aplican nunca**. La zona horaria es especialmente relevante para un sistema de agenda.

**TD-15 — Credenciales de base de datos incoherentes entre `.env.example` y Docker. Riesgo: bajo.**
`.env.example` declara `DB_USERNAME=dentissa` y `DB_PASSWORD=` (vacío), pero el servicio `db` de `docker-compose.yml` sólo define `MYSQL_DATABASE: dentissa`, `MYSQL_ROOT_PASSWORD: root` y `MYSQL_ALLOW_EMPTY_PASSWORD: "yes"`; no crea ningún usuario `dentissa`. Además, `MYSQL_ALLOW_EMPTY_PASSWORD` junto con el puerto `3306` publicado al host y phpMyAdmin expuesto en el `8080` sin restricción de red hacen que el entorno local sea inseguro por defecto.

**TD-16 — `UsuarioController` sin validación y con borrado destructivo. Riesgo: medio.**
Es el único controlador que **no valida nada**: `store()` (líneas 60-76) y `update()` (líneas 87-106) leen `$request->nombre`, `$request->email`, `$request->rol`, etc. directamente, sin reglas de validación, sin `unique:usuarios,email` y sin `in:` sobre `rol` —siendo `rol` un campo `fillable` del modelo `Usuario`—. `store()` además asigna una contraseña genérica en código, `bcrypt('password123')` (línea 71). Y `destroy()` (líneas 108-116) hace un borrado físico de la fila de `usuarios`: por las claves foráneas del volcado, eso propaga `ON DELETE CASCADE` a `pacientes` y a `personal` (perdiendo el expediente asociado), mientras que `citas.paciente_id` no declara acción de borrado —es decir, `RESTRICT`—, por lo que **el borrado de un paciente con citas lanza una excepción SQL no capturada y devuelve un error 500** en lugar de un mensaje. El resto de módulos usa desactivación lógica (`toggleActivo`), que es el patrón correcto ya presente en el proyecto.

**TD-17 — Funcionalidad declarada en el esquema pero no implementada. Riesgo: bajo.**
Tres capacidades existen en la base de datos y no tienen ninguna escritura desde el código: `citas.recordatorio_enviado` sólo se lee (`CitasController.php:27` y `citas/show.blade.php:201`), nunca se escribe, y no hay ningún job, mailable, notification ni tarea programada en todo `app/` (`MAIL_MAILER=log`, `QUEUE_CONNECTION=database`, `Schedule::` no aparece en ninguna parte); `expedientes.archivos_url` se inserta siempre como `null` (`ExpedientesController.php:131`), es decir, la carga de radiografías y documentos está pendiente; y las dos vistas SQL `v_citas_hoy` y `v_historial_paciente`, definidas en el volcado, **no las consulta ninguna línea de PHP** —los controladores reconstruyen esos mismos joins a mano—.

**TD-18 — Detalles menores de higiene.**
`layouts/app.blade.php:20` cierra un `</div>` que nunca se abrió (HTML desbalanceado en el bloque del logo). El mismo layout invoca `filemtime(public_path(...))` en cada render, un acceso al sistema de ficheros por petición. `PreventBackHistory` se añade a **todo** el grupo `web` (`bootstrap/app.php`), incluidas las páginas públicas de login y registro, desactivando la caché del navegador de forma global. `public/storage` es un enlace simbólico a la ruta absoluta del contenedor `/var/www/storage/app/public`, inutilizable fuera de Docker. `.dockerignore` contiene una única línea (`public/storage`), de modo que la imagen copia `node_modules/` y `vendor/` del host si están presentes.

### Modelo de datos reconstruido

**Corrección importante respecto a la hipótesis de partida**: el esquema **no** vive únicamente en la base de datos en ejecución. Está versionado en `database/dentissa.sql` — un volcado de phpMyAdmin 5.2.1 contra MariaDB 10.4.32 generado el `05-07-2026`, de 671 líneas, con estructura completa, índices, claves foráneas, un trigger, dos vistas y datos de ejemplo. Ese archivo es la línea base real, y `docker-compose.yml` lo carga como script de inicialización. Lo que sí es cierto es que **ese volcado está desfasado respecto al código**: es anterior a las tres migraciones de julio de 2026, por lo que no incluye `usuarios.google_id`, ni `pacientes.dentista_id`, ni la tabla `asistente_dentista`. El esquema efectivo es, por tanto, «volcado más migraciones de 2026-07», y esa composición no está documentada en ninguna parte.

Todas las tablas de dominio usan `char(36)` con `DEFAULT uuid()` como clave primaria, motor InnoDB y cotejamiento `utf8mb4_unicode_ci`. Las marcas de tiempo son `creado_en`/`actualizado_en` (español), no las `created_at`/`updated_at` de Laravel; por eso los tres modelos Eloquent declaran `public $timestamps = false`.

**`usuarios`** — identidad y autenticación de todos los actores.
`id` char(36) PK · `nombre` varchar(100) NOT NULL · `apellido` varchar(100) NOT NULL · `email` varchar(255) NOT NULL UNIQUE (`uq_usuarios_email`) · `google_id` varchar UNIQUE NULL *(añadida por migración; ausente del volcado)* · `password_hash` text — NOT NULL en el volcado, relajada a NULL por la migración de Google · `rol` enum(`cliente`,`asistente`,`dentista`,`admin`) NOT NULL DEFAULT `cliente`, indexada (`idx_usuarios_rol`) · `telefono` varchar(20) NULL · `foto_url` text NULL · `activo` tinyint(1) NOT NULL DEFAULT 1 · `creado_en` datetime DEFAULT current_timestamp() · `actualizado_en` datetime con `ON UPDATE current_timestamp()`.
Modelo `App\Models\Usuario`; `getAuthPassword()` redirige la contraseña de Laravel a `password_hash`. El `enum` de `rol` es el eje de autorización de todo el sistema.

**`pacientes`** — perfil clínico y demográfico del cliente. Relación 1:1 con `usuarios`.
`id` char(36) PK · `usuario_id` char(36) NOT NULL UNIQUE (`uq_pacientes_usuario`), FK a `usuarios(id)` ON DELETE CASCADE · `dentista_id` char(36) NULL, FK a `personal(id)` ON DELETE SET NULL *(añadida por migración)* · `fecha_nacimiento` date NULL · `genero` varchar(20) NULL · `direccion` text NULL · `ciudad` varchar(100) NULL · `contacto_emergencia` varchar(200) NULL · `telefono_emergencia` varchar(20) NULL · `tipo_sangre` varchar(5) NULL · `alergias` text NULL · `enfermedades_cronicas` text NULL · `medicamentos_actuales` text NULL · `notas_medicas` text NULL · `creado_en` datetime.
Sin modelo Eloquent: se accede sólo por `DB::table('pacientes')` (20 usos).

**`personal`** — perfil profesional del staff. Relación 1:1 con `usuarios`.
`id` char(36) PK · `usuario_id` char(36) NOT NULL UNIQUE (`uq_personal_usuario`), FK a `usuarios(id)` ON DELETE CASCADE · `tipo` enum(`dentista`,`asistente`,`administrador`) NOT NULL · `especialidad` varchar(150) NULL · `numero_cedula` varchar(50) NULL · `universidad` varchar(150) NULL · `anios_experiencia` int NOT NULL DEFAULT 0 · `biografia` text NULL · `creado_en` datetime.
Sin modelo Eloquent (22 usos vía `DB::table`). Atención al desdoblamiento: `personal.tipo` usa el valor `administrador` mientras que `usuarios.rol` usa `admin`; `PersonalController` traduce entre ambos con un `match` en `store()` (líneas 95-99) y en `update()` (líneas 280-284). **Todas las relaciones de agenda apuntan a `personal.id`, no a `usuarios.id`** — es la fuente de confusión más probable al leer el código.

**`citas`** — la entidad central del sistema.
`id` char(36) PK · `paciente_id` char(36) NOT NULL, FK a `pacientes(id)` sin acción de borrado (RESTRICT), indexada · `dentista_id` char(36) NOT NULL, FK a `personal(id)` (RESTRICT), indexada · `servicio_id` char(36) NULL, FK a `servicios(id)` ON DELETE SET NULL · `fecha_hora` datetime NOT NULL, indexada · `duracion_min` int NOT NULL DEFAULT 30 · `estado` enum(`agendada`,`confirmada`,`en_curso`,`completada`,`cancelada`,`no_asistio`) NOT NULL DEFAULT `agendada`, indexada · `notas_paciente` text NULL · `notas_internas` text NULL · `recordatorio_enviado` tinyint(1) NOT NULL DEFAULT 0 · `creado_por` char(36) NULL, FK a `usuarios(id)` ON DELETE SET NULL · `creado_en`, `actualizado_en` datetime.
Índice compuesto `idx_citas_fecha_estado (fecha_hora, estado)`. Trigger `trg_historial_cita` AFTER UPDATE: si `OLD.estado <> NEW.estado`, inserta en `historial_citas`.
La distinción `notas_paciente` (visible para el cliente) frente a `notas_internas` (bloque "Control interno", sólo `admin`) es una regla de negocio real, verificada en `citas/show.blade.php:89`.

**`expedientes`** — registro clínico por consulta.
`id` char(36) PK · `paciente_id` char(36) NOT NULL, FK a `pacientes(id)`, indexada · `cita_id` char(36) NULL, FK a `citas(id)` ON DELETE SET NULL · `dentista_id` char(36) NOT NULL, FK a `personal(id)` · `diagnostico` text NOT NULL · `tratamiento` text NULL · `receta` text NULL · `archivos_url` longtext con `CHECK (json_valid(...))`, cotejamiento `utf8mb4_bin` — siempre `null` en la práctica · `observaciones` text NULL · `proxima_visita` date NULL · `creado_en` datetime.
`proxima_visita` alimenta una sugerencia real: `CitasController::create()` (líneas 196-206) precarga la fecha del expediente más reciente del paciente si aún es futura.

**`servicios`** — catálogo de tratamientos.
`id` char(36) PK · `nombre` varchar(150) NOT NULL · `descripcion` text NULL · `categoria` varchar(100) NULL · `duracion_min` int NOT NULL DEFAULT 30 · `precio` decimal(10,2) NOT NULL DEFAULT 0.00 · `color_agenda` varchar(7) NOT NULL DEFAULT `'#F4C0D1'` · `activo` tinyint(1) NOT NULL DEFAULT 1 · `creado_en` datetime.
Único modelo Eloquent de dominio (`App\Models\Servicio`, con `HasUuids`). El volcado trae 15 servicios sembrados en 7 categorías: Preventivo, Cirugía, Restauración, Endodoncia, Estética, Ortodoncia, Diagnóstico y Prostodoncia.

**`horarios_dentista`** — disponibilidad semanal recurrente.
`id` char(36) PK · `personal_id` char(36) NOT NULL, FK a `personal(id)` ON DELETE CASCADE · `dia_semana` tinyint NOT NULL con el comentario `'1=Lun 7=Dom'` (coincide con `Carbon::isoWeekday()`) · `hora_inicio` time NOT NULL · `hora_fin` time NOT NULL · `disponible` tinyint(1) NOT NULL DEFAULT 1. Restricción UNIQUE `(personal_id, dia_semana)`.
Es la tabla que gobierna si una cita puede agendarse. `PersonalController::saveHorarios()` la reescribe por completo (borra e inserta) en cada guardado, y siempre fija `disponible = 1`, por lo que el valor `0` sólo puede llegar por escritura manual en la base de datos.

**`historial_citas`** — auditoría de transiciones de estado.
`id` char(36) PK · `cita_id` char(36) NOT NULL, FK a `citas(id)` ON DELETE CASCADE, indexada · `cambiado_por` char(36) NULL, FK a `usuarios(id)` ON DELETE SET NULL · `estado_anterior` y `estado_nuevo`, ambos con el mismo `enum` de 6 valores que `citas.estado`, ambos NULL · `motivo` text NULL · `fecha` datetime DEFAULT current_timestamp().
Escrita por dos rutas distintas y descoordinadas; ver señal TD-7.

**`asistente_dentista`** — tabla puente que decide qué agenda ve cada asistente. *(Sólo en migración; ausente del volcado.)*
`id` uuid PK · `asistente_id` char(36) FK a `personal(id)` ON DELETE CASCADE · `dentista_id` char(36) FK a `personal(id)` ON DELETE CASCADE · `creado_en` timestamp con `useCurrent()`. UNIQUE `(asistente_id, dentista_id)` (`uq_asistente_dentista`).
Es la pieza de autorización más importante después de `usuarios.rol`: `CitasController::dentistaIdsAsistente()` (líneas 48-57) la consulta para acotar todas las consultas del asistente, y `PersonalController::sincronizarDoctoresAsignados()` (líneas 410-422) la mantiene borrando e insertando.

**Vistas SQL definidas y no utilizadas**: `v_citas_hoy` (citas de `curdate()` con paciente, dentista, servicio y precio resueltos) y `v_historial_paciente` (histórico por paciente con diagnóstico y tratamiento). Ambas están en el volcado y ninguna línea de PHP las consulta.

**Tablas de andamiaje Laravel presentes**: `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`. De ellas, la aplicación sólo usa de hecho `cache`, `sessions` (según configuración) y `migrations`.

### Flujos de dominio verificados

**Autenticación y despacho por rol.** `AuthController::login()` valida email y contraseña y llama a `Auth::attempt()` añadiendo `'activo' => 1` como condición extra, de modo que una cuenta desactivada no puede entrar. Tras `session()->regenerate()`, un `match` sobre `$user->rol` redirige a `/admin`, `/dentista`, `/asistente` o `/cliente`. La closure de `/` repite ese mismo `match` para usuarios ya autenticados. `RoleMiddleware` (alias `role`) redirige a `/login` si no hay sesión y hace `abort(403)` si el rol no está en la lista de roles permitidos.

**Registro directo.** `RegisterController::register()` valida 9 campos con mensajes en español (`Password::min(8)`, `unique:usuarios,email`, `fecha_nacimiento` `before:today`) y ejecuta una `DB::transaction` que inserta la fila de `usuarios` (rol `cliente`, contraseña con `Hash::make`) y la de `pacientes`. La asignación de dentista no es manual: `asignarDentistaAutomatico()` (líneas 168-179) elige el dentista activo con menos pacientes a su cargo mediante un `leftJoin` sobre `pacientes` agrupado por `personal.id` y ordenado por `COUNT(pacientes.id) ASC`, con desempate por antigüedad. Después inicia sesión automáticamente.

**Registro con Google.** `GoogleAuthController::callback()` busca primero por `google_id`; si no lo encuentra, busca por email y, si hay coincidencia, vincula la cuenta existente con `forceFill(['google_id' => ...])`. Si no existe ninguna cuenta, **no crea el usuario**: descompone el nombre en `given_name`/`family_name`, guarda los datos en la sesión bajo la clave `google_registro` y redirige a `register.contacto`, un segundo paso que exige teléfono, datos demográficos y aceptación de términos (`'terminos' => ['accepted']`). Sólo entonces `contactoStore()` inserta el usuario —con `password_hash` en `null`, que es precisamente lo que habilitó la migración `add_google_id_to_usuarios_table`— y su paciente, con el mismo balanceo automático de dentista. Una cuenta desactivada que intente entrar por Google recibe un mensaje de error explícito.

**Agenda: la regla de negocio más elaborada del sistema.** Está implementada **dos veces**, en `CitasController::store()` (líneas 236-280, para staff) y en `ClienteCitasController::agendarStore()` (líneas 146-185, para el cliente), con la misma secuencia de tres comprobaciones: (1) se calcula el día ISO de la semana con `Carbon::isoWeekday()` y se busca la fila de `horarios_dentista` correspondiente; si no existe o `disponible` es falso, se rechaza; (2) se comprueba que el intervalo `[fecha_hora, fecha_hora + duracion_min]` cabe dentro de `hora_inicio`–`hora_fin`, devolviendo un mensaje que incluye el horario real del dentista; (3) se detecta solape con citas existentes del mismo dentista excluyendo los estados `cancelada` y `no_asistio`, mediante `whereBetween` combinado con `orWhereRaw('DATE_ADD(fecha_hora, INTERVAL duracion_min MINUTE) > ? AND fecha_hora < ?')`. Las diferencias entre ambas copias son significativas: la del cliente **exige** `servicio_id` y toma la duración del servicio, mientras que la del staff lo acepta como opcional y recibe `duracion_min` del formulario (validado `min:10|max:300`); la del cliente exige `after_or_equal:today` y la del staff no impone fecha mínima. Ninguna de las dos usa bloqueo ni restricción de unicidad, por lo que dos peticiones concurrentes pueden producir un solape real.

**Alcance de visibilidad por rol en citas.** `CitasController` centraliza la regla en dos helpers: `aplicarAlcanceRol()` restringe el listado —el `dentista` sólo ve `citas.dentista_id = personalIdPropio()`, el `asistente` sólo ve las de los dentistas de su tabla puente, y el `admin` no se filtra—, y `autorizadoParaCita($dentistaId)` aplica la misma regla en las acciones puntuales con `abort(403)`. Es el control de acceso más maduro del proyecto y se aplica en `index`, `create`, `store` y `show`. Conviene señalar dos huecos: `updateNotas()` y `destroy()` no lo invocan (dependen sólo de que la ruta esté en el grupo `admin`).

**Ciclo de vida de la cita.** Los seis estados forman la máquina: `agendada` (inicial) → `confirmada` → `en_curso` → `completada`, con `cancelada` y `no_asistio` como salidas. `CitasController::updateEstado()` acepta cualquier transición (`'estado' => 'required|in:...'` sin grafo de transiciones válidas). El cliente sólo puede cancelar, y sólo desde `agendada` o `confirmada` (`ClienteCitasController::cancelar()`, líneas 93-95). `CitasController::destroy()` no borra: hace un `update` a `cancelada` (borrado lógico), a diferencia de `UsuarioController::destroy()`, que sí borra físicamente.

**Expediente clínico.** `ExpedientesController::create()` ofrece únicamente las citas del paciente en estado `completada`, `en_curso` o `confirmada` que **aún no tienen expediente** (`leftJoin` sobre `expedientes` con `whereNull('expedientes.id')`). El asistente queda excluido de `create`, `store` y `update` con `abort(403)` explícito, y de la interfaz con guardas `@if($base !== 'asistente')` en `expedientes/show.blade.php`. El dentista sólo ve en el índice los expedientes que él mismo firmó.

**Dashboards.** Cuatro métodos en `DashboardController`, uno por rol, todos con la misma forma: agregados del día más lista de citas de hoy. El de `admin` cuenta citas de hoy y total de clientes activos; el de `dentista` añade pacientes atendidos en el mes en curso y calcula la próxima cita pendiente filtrando la colección ya cargada; el de `asistente` resuelve primero sus dentistas por la tabla puente y devuelve colecciones vacías si no tiene ninguno asignado; el de `cliente` resuelve su fila de `pacientes` y devuelve próxima cita más las 5 últimas pasadas.

**Alta de personal con contraseña generada.** `PersonalController::store()` y `PacientesController::store()` comparten un patrón: si el formulario no trae contraseña, generan una con `Str::random(10)` y la devuelven en la sesión bajo la clave `password_generada`, que el layout renderiza en un panel con botón de copiado (`layouts/app.blade.php:226-254`) advirtiendo que no se volverá a mostrar. Es una decisión de producto deliberada y bien ejecutada en la interfaz.

## Handoff Summary

- **Intent-relevant finding**: la hipótesis de que «el esquema real sólo vive en la base de datos en ejecución» es **falsa y debe corregirse en toda la documentación aguas abajo**. El esquema está versionado en `database/dentissa.sql` — volcado de phpMyAdmin 5.2.1 contra MariaDB 10.4.32, 671 líneas, fechado `05-07-2026`, con las 17 tablas, 2 vistas SQL, 1 trigger, todos los índices, todas las claves foráneas y datos de ejemplo — y `docker-compose.yml:47` lo monta en `/docker-entrypoint-initdb.d/` como script de inicialización. Lo que sí es cierto es que ese volcado **está desfasado**: es anterior a las tres migraciones de julio de 2026, por lo que le faltan `usuarios.google_id` (`database/migrations/2026_07_18_195924_add_google_id_to_usuarios_table.php`), `pacientes.dentista_id` (`2026_07_20_000001`) y la tabla puente `asistente_dentista` (`2026_07_20_000000`). El esquema efectivo es «volcado más esas tres migraciones», una composición que no está documentada en ningún sitio y que sólo funciona porque el volcado incluye la tabla `migrations` ya poblada con las tres migraciones de andamiaje (`database/dentissa.sql:216-220`), lo que hace que `artisan migrate` las salte en lugar de fallar. El modelo de datos completo, columna por columna, con tipos, `enum`, índices, claves foráneas y acciones de borrado, queda reconstruido en la sección `Modelo de datos reconstruido` de este mismo archivo, y el arquitecto puede tomarlo tal cual para `business-overview.md`, `architecture.md` y `component-inventory.md` sin volver a leer el SQL.

- **Risks / follow-up**:
- **La pila declarada en el estado del workflow es incorrecta.** No es "Vite": es **Laravel `v12.62.0` sobre PHP `^8.2`** (imagen `php:8.4-fpm`) con **MariaDB**, Blade renderizado en servidor y `laravel/socialite v5.28.0` para Google OAuth. Vite `8.0.1`, Tailwind `4.2.2` y axios `1.13.6` están instalados pero muertos: `@vite` aparece una sola vez, en `resources/views/welcome.blade.php:15`, y ninguna ruta renderiza `welcome`. El estilo real son 1.072 líneas de CSS artesanal en `public/css/`. El `Build System` registrado como "npm (package.json)" debe corregirse a "Composer (autoritativo) más npm/Vite (inerte)".
- **Hay datos personales sensibles y credenciales en el repositorio y en la historia de git.** `database/dentissa.sql` contiene 8 usuarios reales con correos, teléfonos y **hashes bcrypt**, 6 pacientes con **tipo de sangre, alergias y domicilio** (datos de salud), y una fila de `sessions` con carga útil real; `README.md` termina con tres contraseñas en texto plano. Cualquier artefacto público derivado de este análisis no debe reproducir esos valores, y la remediación (rotación de credenciales, anonimización del volcado, reescritura de historia) debería aparecer como riesgo en la documentación de arquitectura. `.env` sí está correctamente ignorado y no se abrió.
- **Dos defectos funcionales confirmados que el arquitecto debe preservar como hallazgos, no como suposiciones**: (a) `routes/web.php` registra `GET /admin/usuarios/{id}` (línea 92) antes que `GET /admin/usuarios/crear` (línea 94), y sin restricción en `{id}`, de modo que el alta de usuarios desde la interfaz responde `404`; (b) `ServiciosController` fija `$data['precio'] = 0` incondicionalmente en `store()` (línea 33) y en `update()` (línea 58), por lo que editar cualquier servicio borra su precio en silencio, aunque la columna `servicios.precio` y las vistas SQL `v_citas_hoy` y `v_historial_paciente` sí lo exponen.
- **No existe ninguna red de seguridad.** Cobertura de dominio cero (los únicos dos tests son el andamiaje de Laravel), sin CI de ningún tipo, sin linter ejecutado (Pint instalado pero jamás invocado y sin `pint.json`), sin análisis estático y con el `README.md` genérico de Laravel. Además **la única prueba existente falla hoy**: `tests/Feature/ExampleTest.php` espera `200` en `GET /` y la aplicación devuelve `302`. Y aunque se escribieran pruebas, `phpunit.xml` fija `DB_CONNECTION=sqlite` con `:memory:`, un motor que no puede ejecutar el volcado MariaDB — es decir, restaurar la capacidad de probar el dominio pasa obligatoriamente por escribir las migraciones que faltan (señal TD-1).
- **La autorización descansa en dos piezas frágiles que conviene documentar juntas**: el `enum` `usuarios.rol` con `RoleMiddleware`, y la tabla puente `asistente_dentista`, que es lo único que acota la agenda visible para un asistente (`CitasController::dentistaIdsAsistente()`, líneas 48-57). El grupo `cliente` de `routes/web.php:148` es el único que **no** aplica `role:`, y esa omisión tiene un efecto de escritura real: `PerfilController::update()` (líneas 61-69) inserta una fila en `pacientes` si no la encuentra, de modo que un miembro del personal que guarde `/cliente/perfil` se crea a sí mismo un registro de paciente.
- **Punto de atención para el diseño de dominio**: todas las relaciones de agenda apuntan a `personal.id`, no a `usuarios.id`, y hay un desdoblamiento de vocabulario entre `personal.tipo` (valor `administrador`) y `usuarios.rol` (valor `admin`) que `PersonalController` traduce con un `match` en dos sitios. Es la confusión más probable al modelar el dominio a partir de este código.
- **Cobertura honesta de este escaneo**: leí íntegramente los 19 archivos de `app/`, ambos archivos de `routes/`, `bootstrap/`, las 6 migraciones, el volcado SQL completo, los 3 archivos de `tests/`, los cuatro manifiestos de construcción con sus lockfiles, la configuración de Docker y 4 de los 10 archivos de `config/`. De las 37 plantillas Blade leí 6 íntegras y verifiqué las 37 mediante un barrido estructural exhaustivo (rutas generadas, formularios, `@csrf`, bloques `<script>`, ausencia de `{!! !!}` y de AJAX). **No leí línea a línea** las 30 plantillas restantes, los 6 archivos de `config/` por defecto, las 1.072 líneas de `public/css/` ni `vendor/`/`node_modules/`. Si el arquitecto necesita afirmaciones sobre maquetación, accesibilidad o consistencia visual, esa parte requiere una segunda pasada.
