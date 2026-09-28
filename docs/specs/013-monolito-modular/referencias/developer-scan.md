# Escaneo de código — eslabón desarrollador

> Escaneo **enfocado** sobre un almacén de conocimiento `STALE`. Repositorio
> `dentissa`, rama `main`, commit `4a24b5e26cfc2b98ed2f4bb84d019b4ce77a0427`
> («se solucionaron diversas inconsistecias», 2026-09-19). Instantánea de origen
> previa al escaneo: `git:84e58ec6d591f634282dcdf9750663c0ca43d88b`.
> Almacén previo: construido por `260902-docs-sistema-dental` en el commit
> `9fcff4c`, `kind: full`.
>
> Trabajo en modo solo lectura: no se ejecutó la suite de pruebas ni ningún
> comando que escriba en el repositorio.

## Developer Code Scan Results

### Scan Coverage

- **Analyzed deeply**:
  - `app/` — los 19 archivos leídos íntegros (13 controladores, 2 middleware, 3 modelos, 1 provider)
  - `app/Http/Controllers/`
  - `app/Http/Middleware/`
  - `app/Models/`
  - `app/Providers/`
  - `routes/web.php` — leído íntegro: las 4 agrupaciones y los 81 registros de verbo único
  - `routes/console.php`
  - `database/migrations/` — las 6 migraciones leídas íntegras
  - `database/dentissa.sql` — leído íntegro: DDL de las 20 tablas, el disparador, las 2 vistas, las listas de columnas de cada `INSERT` y el recuento exacto de filas por tabla. Los **valores** de los datos personales se contabilizaron por tipo de columna; no se transcriben en ningún artefacto.
  - `database/seeders/DatabaseSeeder.php`
  - `database/factories/UserFactory.php`
  - `database/testing/bootstrap-test-db.sh` — leído íntegro, incluidas sus comprobaciones previas y su verificación de tablas
  - `phpunit.xml`
  - `composer.json`
  - `composer.lock` — versiones resueltas extraídas del lockfile (82 paquetes de producción, 31 de desarrollo)
  - `bootstrap/app.php`
  - `bootstrap/providers.php`
  - `config/auth.php` — proveedor del guard y modelo enlazado
  - `config/database.php` — conexión por defecto y definiciones `mysql`/`mariadb`/`sqlite`
  - `config/services.php` — leído íntegro
  - `tests/TestCase.php`, `tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php` — leídos íntegros
  - `tests/Feature/AdminUsuariosRutasTest.php`, `tests/Feature/AdminVistasSmokeTest.php`, `tests/Feature/ServiciosPrecioTest.php`, `tests/Feature/UsuariosAdminTest.php` — leídos para: los 22 métodos de prueba y su nombre, los trait de aislamiento, los ayudantes de fixture con su cuerpo, las tablas que tocan, las vistas SQL que consultan y las rutas que ejercitan por HTTP. **Cota declarada**: no se leyeron aserción por aserción; lo que este documento afirma sobre ellas es su cobertura y su andamiaje, no la exactitud de cada aserción individual.
  - `resources/views/citas/create.blade.php`, `resources/views/citas/cliente-agendar.blade.php` — leídas en sus secciones de agenda (campos de fecha, hora y duración, y el bloque `@push('scripts')`), por ser el tercer sitio donde se expresa la ventana de atención

- **Skimmed only**:
  - `resources/views/` — barrido estructural de las 37 plantillas: recuento de formularios por método, cobertura de `@csrf` y `@method`, bloques `@php`, condicionales por rol, llamadas a `route()`, salida sin escapar `{!! !!}` y presencia de lógica de consulta. **No se afirma nada** sobre maquetación, accesibilidad ni consistencia visual de las 35 plantillas no leídas íntegras.
  - `config/app.php`, `config/cache.php`, `config/filesystems.php`, `config/logging.php`, `config/mail.php`, `config/queue.php`, `config/session.php` — inventariados; no se verificó si están intactos respecto al esqueleto de Laravel 12.
  - `README.md` — solo estructura de encabezados y presencia de etiquetas de credencial al final.
  - `docker/`, `public/`, `resources/js/`, `resources/css/`, `vite.config.js`, `package.json`, `package-lock.json`, `Dockerfile`, `docker-compose.yml` — **fuera del foco del escaneo**. Se nombran a granularidad de directorio: `docker/` (2 archivos: `nginx`, `php`), `public/` (13 archivos: `build`, `css`, `images`, `index.php`, `favicon.ico`, `robots.txt`, `storage`), `resources/js/` (2), `resources/css/` (1). `package.json` se leyó solo para confirmar la cadena de assets inerte. Ninguna afirmación de este documento depende de leerlos en profundidad.
  - `vendor/`, `node_modules/`, `storage/` — no analizados. Las versiones vienen de los lockfiles.

### Packages Found

Un solo paquete: la aplicación es un monolito Laravel sin monorepo, sin
submódulos y sin subpaquetes internos.

- `laravel/laravel` (nombre en `composer.json`) — aplicación monolítica — PHP `^8.2` — sistema de gestión de clínica dental con 4 roles (`admin`, `dentista`, `asistente`, `cliente`)

El árbol `app/` contiene exactamente **4 subdirectorios**: `Http/Controllers`,
`Http/Middleware`, `Models`, `Providers`. No hay ninguna otra agrupación de
código propia.

**Tamaño verificado** (líneas en disco, commit `4a24b5e`):

| Área | Líneas |
| --- | --- |
| `app/` | 2.631 |
| `resources/views/` | 5.958 |
| `routes/` | 167 |
| **Suma `app/` + `resources/views/` + `routes/`** | **8.756** |
| `config/` | 1.293 |
| `tests/` | 1.110 |
| `database/dentissa.sql` | 671 |
| `database/migrations/` | 239 |
| `database/testing/` | 136 |
| `database/seeders/` + `database/factories/` | 70 |
| `bootstrap/` | 35 |

La cifra de **8.756 líneas** que afirma la descripción del intent corresponde
exactamente a `app/` + `resources/views/` + `routes/` en este commit.
**Coincide.**

Archivos versionados no-`vendor`/no-`aidlc`: 129, 24.617 líneas (incluye
`composer.lock`).

### Build System

- **Type**: Composer (PHP) como sistema principal; npm/Vite para una cadena de assets que la aplicación **no consume** (el layout carga hojas de estilo estáticas de `public/css/`, fuera del foco)
- **Config Files**: `composer.json`, `composer.lock`, `phpunit.xml`, `bootstrap/app.php`, `bootstrap/providers.php`, `package.json` (fuera del foco), `vite.config.js` (fuera del foco)
- **Build Dependencies**: no hay relaciones paquete → paquete internas. El arranque es `public/index.php` → `bootstrap/app.php` → `routes/web.php`, con un único provider propio (`AppServiceProvider`, cuyos métodos `register()` y `boot()` están **vacíos**).

**Defecto verificado en el procedimiento de arranque declarado.**
`composer.json` → `scripts.setup` ejecuta, en orden:

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

Sobre un clon limpio, `php artisan migrate --force` crea únicamente las 8 tablas
de infraestructura de Laravel y después **falla**: la migración
`2026_07_18_195924_add_google_id_to_usuarios_table.php` hace
`Schema::table('usuarios', …)` y ninguna migración crea `usuarios`. El
procedimiento de arranque que el propio repositorio declara **no funciona**. No
es una inferencia: es la consecuencia directa del inventario de migraciones de la
sección siguiente.

`scripts.post-create-project-cmd` todavía hace
`touch('database/database.sqlite')` y `php artisan migrate --graceful`:
andamiaje del esqueleto que no describe este proyecto. `config/database.php`
conserva `'default' => env('DB_CONNECTION', 'sqlite')`.

`README.md` es el README de serie de Laravel (encabezados *About Laravel*,
*Learning Laravel*, *Agentic Development*, *Contributing*, *Code of Conduct*,
*Security Vulnerabilities*, *License*) más dos líneas finales que llevan
etiquetas de credencial. **No contiene ningún procedimiento de arranque de este
proyecto.** El único procedimiento ejecutable y correcto que existe en el
repositorio es `database/testing/bootstrap-test-db.sh`, y solo cubre la base de
**pruebas**.

### El esquema del dominio — bloqueo TD-1, verificado

#### Qué crean las 6 migraciones

| Migración | Qué hace | Tabla base que necesita |
| --- | --- | --- |
| `0001_01_01_000000_create_users_table.php` | crea `users`, `password_reset_tokens`, `sessions` | — |
| `0001_01_01_000001_create_cache_table.php` | crea `cache`, `cache_locks` | — |
| `0001_01_01_000002_create_jobs_table.php` | crea `jobs`, `job_batches`, `failed_jobs` | — |
| `2026_07_18_195924_add_google_id_to_usuarios_table.php` | **altera** `usuarios`: añade `google_id` y ejecuta `ALTER TABLE usuarios MODIFY password_hash TEXT NULL` | `usuarios` — **nadie la crea** |
| `2026_07_20_000000_create_asistente_dentista_table.php` | **crea** `asistente_dentista` con 2 claves foráneas a `personal(id)` | `personal` — **nadie la crea** |
| `2026_07_20_000001_add_dentista_id_to_pacientes_table.php` | **altera** `pacientes`: añade `dentista_id` con clave foránea a `personal(id)` | `pacientes` y `personal` — **nadie las crea** |

Las 3 primeras migraciones crean **8 tablas de infraestructura de Laravel** y
**ninguna tabla de dominio**. De las 3 restantes, **dos alteran tablas que nadie
crea** (confirma la descripción del intent) y **la tercera crea una tabla cuyas
claves foráneas apuntan a una tabla que nadie crea**, así que tampoco puede
ejecutarse sobre una base vacía.

**Consecuencia precisa, más fuerte que la que afirma el intent**: no es que 8 de
las 9 tablas de dominio carezcan de migración — es que **ninguna de las 9 es
reproducible desde `database/migrations/`**. `asistente_dentista` tiene
migración, pero esa migración no puede ejecutarse sobre un esquema construido
solo con migraciones.

#### Qué contiene `database/dentissa.sql`

Volcado de phpMyAdmin 5.2.1 contra MariaDB 10.4.32, generado el 2026-07-05.
Versionado en git. 671 líneas. Contiene:

**9 tablas de dominio** (todas con clave primaria `char(36) DEFAULT uuid()`):

| Tabla | Columnas notables | Claves foráneas |
| --- | --- | --- |
| `usuarios` | `rol enum('cliente','asistente','dentista','admin')`, `password_hash text NOT NULL`, `activo`, `foto_url` | — |
| `personal` | `tipo enum('dentista','asistente','administrador')`, `especialidad`, `numero_cedula`, `universidad`, `anios_experiencia` | `usuario_id` → `usuarios` (CASCADE), único |
| `pacientes` | `fecha_nacimiento`, `genero`, `direccion`, `ciudad`, `contacto_emergencia`, `telefono_emergencia`, `tipo_sangre`, `alergias`, `enfermedades_cronicas`, `medicamentos_actuales`, `notas_medicas` | `usuario_id` → `usuarios` (CASCADE), único |
| `citas` | `fecha_hora datetime`, `duracion_min int DEFAULT 30`, `estado enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio')`, `notas_paciente`, `notas_internas`, `recordatorio_enviado` | `paciente_id` → `pacientes`, `dentista_id` → `personal`, `servicio_id` → `servicios` (SET NULL), `creado_por` → `usuarios` (SET NULL) |
| `servicios` | `nombre`, `categoria`, `duracion_min int DEFAULT 30`, `precio decimal(10,2)`, `color_agenda varchar(7) DEFAULT '#F4C0D1'`, `activo` | — |
| `expedientes` | `diagnostico text NOT NULL`, `tratamiento`, `receta`, `archivos_url longtext CHECK (json_valid(...))`, `observaciones`, `proxima_visita date` | `paciente_id` → `pacientes`, `cita_id` → `citas` (SET NULL), `dentista_id` → `personal` |
| `historial_citas` | `estado_anterior`, `estado_nuevo` (mismo enum que `citas.estado`), `motivo`, `fecha` | `cita_id` → `citas` (CASCADE), `cambiado_por` → `usuarios` (SET NULL) |
| `horarios_dentista` | `dia_semana tinyint` (comentario: `1=Lun 7=Dom`), `hora_inicio time`, `hora_fin time`, `disponible`; **sin `ENGINE=InnoDB` declarado**; único `(personal_id, dia_semana)` | `personal_id` → `personal` (CASCADE) |
| `asistente_dentista` | **no está en el volcado** — existe solo como migración | — |

**2 vistas SQL**, ambas con `ALGORITHM=UNDEFINED DEFINER='root'@'localhost'
SQL SECURITY DEFINER`:

- `v_citas_hoy` — 5 cruces (`citas` → `pacientes` → `usuarios` como paciente; `citas` → `personal` → `usuarios` como dentista; `LEFT JOIN servicios`), filtrada por `CAST(c.fecha_hora AS DATE) = CURDATE()`, ordenada ascendente. Proyecta 12 columnas, entre ellas `notas_internas`.
- `v_historial_paciente` — el mismo espinazo de 5 cruces más `LEFT JOIN expedientes ON e.cita_id = c.id`, sin filtro, ordenada descendente. Proyecta 10 columnas, entre ellas `diagnostico` y `tratamiento`.

El `DEFINER='root'@'localhost'` es un acoplamiento a un usuario concreto de
MariaDB: al recrear las vistas en migraciones hay que decidir explícitamente el
contexto de seguridad, porque `SQL SECURITY DEFINER` hace que la vista lea con
los privilegios de `root` y no del usuario que consulta. Ninguna de las dos
vistas aplica filtro por rol ni por paciente: toda la restricción de visibilidad
vive en el código PHP que las consulta.

**1 disparador, que la descripción del intent no menciona y el alcance del
trabajo no recoge**:

```sql
CREATE TRIGGER `trg_historial_cita` AFTER UPDATE ON `citas` FOR EACH ROW BEGIN
  IF OLD.estado <> NEW.estado THEN
    INSERT INTO historial_citas (id, cita_id, estado_anterior, estado_nuevo)
    VALUES (UUID(), NEW.id, OLD.estado, NEW.estado);
  END IF;
END
```

Ese disparador es una **regla de negocio implementada en la base de datos**: la
bitácora de cambios de estado de una cita. `CitasController::updateEstado()`
(líneas 357-368) lo documenta en un comentario y **añade una segunda escritura**
a `historial_citas` cuando el formulario trae un `motivo`, de modo que un cambio
de estado con motivo produce **dos filas**: una del disparador (sin
`cambiado_por` ni `motivo`) y otra del controlador (sin `estado_anterior`).
Cualquier recreación del esquema en migraciones tiene que decidir si el
disparador sobrevive, y la capa de dominio tiene que decidir dónde vive esa
regla.

**11 tablas de infraestructura** también en el volcado: `cache`, `cache_locks`,
`failed_jobs`, `jobs`, `job_batches`, `migrations`, `password_reset_tokens`,
`sessions`, `users`.

#### El volcado está desfasado respecto a las migraciones

La tabla `migrations` del volcado contiene **3 filas** y su `AUTO_INCREMENT` es
4: cuando se generó el volcado solo se habían aplicado las 3 migraciones de
infraestructura. Por eso el volcado **no tiene**:

- la tabla `asistente_dentista`
- la columna `pacientes.dentista_id`
- la columna `usuarios.google_id`
- `usuarios.password_hash` como `NULL`-able (en el volcado es `text NOT NULL`)

**El esquema efectivo es «el volcado más las tres migraciones de julio de
2026»**, y **ninguna de las dos fuentes por separado lo produce**: el volcado
está incompleto y las migraciones no se pueden ejecutar. El almacén previo ya
había registrado esta corrección; sigue siendo cierta y es el núcleo del
bloqueo TD-1.

Consecuencia operativa que la etapa siguiente debe preservar:
`database/testing/bootstrap-test-db.sh` carga **solo el volcado**, así que la
base `dentissa_test` que usan las 22 pruebas **no tiene** `asistente_dentista`
ni `pacientes.dentista_id`. Cualquier prueba nueva que toque la asignación
asistente→dentista o el dentista asignado a un paciente falla hoy contra el
arnés existente.

#### Datos sembrados por el volcado — recuento exacto de filas

| Tabla | Filas |
| --- | --- |
| `usuarios` | 8 — 1 `admin`, 1 `dentista`, 6 `cliente` (uno con `activo=0`), **0 `asistente`** |
| `servicios` | 15 |
| `pacientes` | 6 |
| `citas` | 2 |
| `personal` | **1** (el único dentista) |
| `historial_citas` | 1 |
| `expedientes` | **0** |
| `horarios_dentista` | **0** |
| `asistente_dentista` | tabla inexistente en el volcado |
| `sessions` | 1 (una carga de sesión real) |
| `migrations` | 3 |

**`horarios_dentista` con 0 filas es un bloqueo funcional inmediato**: las dos
implementaciones de la regla de agenda consultan esa tabla y, si no encuentran
fila para el día de la semana pedido, devuelven «El dentista no tiene horario
disponible ese día de la semana». Tras un arranque limpio desde el volcado
**no puede agendarse ninguna cita**, ni por la interfaz ni desde una prueba.
El seeder de datos sintéticos que pide el alcance del trabajo **tiene que
sembrar `horarios_dentista`**, o las pruebas de la regla de agenda —la pieza que
el intent llama más crítica— no pueden llegar a ejecutarse.

Con `personal` a 1 fila y 0 asistentes, tampoco hay base con la que ejercitar el
alcance por rol de asistente ni la asignación asistente→dentista.

### APIs Discovered

No hay API HTTP para consumo de terceros: no hay `routes/api.php`, ni rutas
JSON, ni controladores de recurso que devuelvan datos. Toda la superficie es
**HTML renderizado por servidor** con formularios.

- **Rutas web** — `routes/web.php` — **88 rutas registradas**: 81 registros de verbo único más 7 que genera la única llamada a `Route::resource('servicios', ServiciosController::class)`. (El almacén previo contaba 89 en el commit `9fcff4c`: 82 de verbo único más 7. La diferencia es el cambio que hizo el intent `260903-fix-admin-crud` en el módulo de usuarios, que retiró el borrado físico y añadió la alternancia y el alta.)
- **Ruta de salud** — `bootstrap/app.php` declara `health: '/up'`.
- **Integración OAuth saliente** — `GoogleAuthController` sobre `laravel/socialite` v5.28.0: `GET /auth/google` (redirección) y `GET /auth/google/callback`. Credenciales por entorno en `config/services.php` → bloque `google` (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`). No hay credenciales en el código.
- **Contratos internos**: ninguno explícito. No existe interfaz, contrato, servicio ni repositorio. El «contrato» entre capas es el conjunto de columnas que cada controlador proyecta en su `select()` y el nombre con que la plantilla Blade lo lee.

Las 4 agrupaciones de rutas y su middleware:

| Prefijo | Nombre | Middleware | Controladores que sirve |
| --- | --- | --- | --- |
| `/admin` | `admin.` | `['auth','role:admin']` | `DashboardController`, `ServiciosController` (resource), `CitasController` (7 rutas), `PacientesController` (7), `ExpedientesController` (4), `PersonalController` (8), `UsuarioController` (7), 1 placeholder |
| `/dentista` | `dentista.` | `['auth','role:dentista']` | `DashboardController`, `CitasController` (5), `PacientesController` (4), `ExpedientesController` (5) |
| `/asistente` | `asistente.` | `['auth','role:asistente']` | `DashboardController`, `PersonalController::misDoctores`, `CitasController` (5), `PacientesController` (4), `ExpedientesController` (1) |
| `/cliente` | `cliente.` | **`'auth'` a secas — sin `role:cliente`** | `DashboardController`, `ClienteCitasController` (5), `PerfilController` (3) |

La ausencia de `role:cliente` en la cuarta agrupación es un hallazgo de
autorización: cualquier usuario autenticado, de cualquier rol, alcanza
`/cliente/*`. Ver «Technical Debt Signals».

### Frameworks & Libraries

Versiones **resueltas de `composer.lock`**, no las restricciones declaradas.

| Nombre | Versión | Propósito |
| --- | --- | --- |
| `php` | `^8.2` (plataforma declarada) | tiempo de ejecución |
| `laravel/framework` | `v12.62.0` | framework de aplicación |
| `laravel/socialite` | `v5.28.0` | OAuth con Google |
| `laravel/tinker` | `v3.0.2` | REPL |
| `nesbot/carbon` | `3.13.0` | fechas y horas — usado por la regla de agenda |
| `guzzlehttp/guzzle` | `7.13.2` | cliente HTTP (transitivo de Socialite) |
| `league/oauth1-client` | `v1.11.0` | transitivo de Socialite |
| `laravel/prompts` | `v0.3.21` | transitivo |
| `symfony/console` | `v7.4.14` | transitivo |
| `phpunit/phpunit` | `12.5.30` | pruebas (dev) |
| `laravel/pint` | `v1.29.3` | formateador (dev) — **nunca ejecutado aquí** |
| `nunomaduro/collision` | `v8.9.4` | informe de errores en consola (dev) |
| `mockery/mockery` | `1.6.12` | dobles de prueba (dev) |
| `fakerphp/faker` | `v1.24.1` | datos falsos (dev) |
| `laravel/pail` | `v1.2.7` | seguimiento de registros (dev) |

Totales: **82 paquetes** de producción, **31** de desarrollo.

Base de datos: **MariaDB**. `config/database.php` define las conexiones
`sqlite`, `mysql`, `mariadb`, `pgsql`, `sqlsrv`; la que se usa es `mariadb`
(`phpunit.xml` la fija para pruebas). El volcado se generó contra MariaDB
10.4.32 y el arnés de pruebas apunta al contenedor `mariadb:10.11` que declara
`docker-compose.yml` (fuera del foco; lo nombra el propio script de arranque).

Cadena de assets: Vite 8, Tailwind 4 y axios declarados en `package.json`
(fuera del foco) e **inertes** respecto al foco: ninguna plantilla de las
analizadas los consume.

**No hay** ORM adicional, ni contenedor de inyección de dependencias propio, ni
biblioteca de autorización, ni cola en uso, ni caché en uso, ni paquete de
eventos, ni biblioteca de pruebas de arquitectura.

### La capa de dominio que no existe

Directorios convencionales de Laravel para lógica de dominio, **todos ausentes**:

`app/Http/Requests`, `app/Policies`, `app/Services`, `app/Actions`,
`app/Repositories`, `app/Events`, `app/Jobs`, `app/Listeners`, `app/Observers`,
`app/Rules`, `app/Console`, `app/Exceptions`, `app/View`, `app/Enums`,
`app/DataTransferObjects`, `app/Support`.

Búsqueda sobre todo `app/` de `Gate::`, `->authorize(`, `extends FormRequest`,
`dispatch(`, `event(`, `ShouldQueue` y `Policy`: **ninguna coincidencia**.
`App\Http\Controllers\Controller` declara `use AuthorizesRequests,
ValidatesRequests` pero ningún controlador llama a `authorize()`.

#### Modelos Eloquent — recuento corregido

Hay **3 archivos de modelo**, pero solo **2 corresponden a tablas de dominio**:

| Modelo | Tabla | Estado |
| --- | --- | --- |
| `App\Models\Usuario` | `usuarios` | **modelo de dominio activo**. `HasUuids`, `$incrementing=false`, `$keyType='string'`, `$timestamps=false`, 8 campos asignables, `password_hash` oculto, `getAuthPassword()` sobrescrito. `config/auth.php` línea 67 enlaza el proveedor del guard `web` a esta clase, así que el usuario autenticado **es** un `Usuario`. |
| `App\Models\Servicio` | `servicios` | **modelo de dominio activo**. `HasUuids`, `$timestamps=false`, 9 campos asignables incluido `id`. |
| `App\Models\User` | `users` | **residuo del andamiaje**. Tabla de infraestructura, no de dominio. Referenciado solo por `database/factories/UserFactory.php` y `database/seeders/DatabaseSeeder.php`. `config/auth.php` lo importa en la línea 3 (`use App\Models\User;`) y **no lo usa**: el proveedor apunta a `Usuario`. Ningún controlador ni ruta lo toca. |

La descripción del intent dice «solo 3 modelos Eloquent para 10 tablas de
dominio». Lo verificado es: **2 modelos de dominio para 9 tablas de dominio y 2
vistas SQL**; el tercer archivo de modelo cubre una tabla de infraestructura y es
código muerto. Y **ningún modelo declara ninguna relación**: cero `belongsTo`,
cero `hasMany`, cero `hasOne`. Los cruces que el ORM haría están todos escritos
a mano con el constructor de consultas.

Tablas de dominio **sin modelo**: `personal`, `pacientes`, `citas`,
`expedientes`, `historial_citas`, `horarios_dentista`, `asistente_dentista`
(7 de 9).

#### Los 13 controladores y las tablas que toca cada uno

Líneas verificadas en el commit `4a24b5e`; el total coincide exactamente con las
**2.468** que afirma la descripción del intent.

| Controlador | Líneas | Tablas de dominio que toca | Modelos que usa |
| --- | --- | --- | --- |
| `PersonalController` | 422 | `asistente_dentista`, `citas`, `horarios_dentista`, `pacientes`, `personal`, `servicios`, `usuarios` (7) | — |
| `CitasController` | 398 | `asistente_dentista`, `citas`, `expedientes`, `historial_citas`, `horarios_dentista`, `pacientes`, `personal`, `servicios`, `usuarios` (**las 9**) | — |
| `PacientesController` | 337 | `citas`, `expedientes`, `pacientes`, `personal`, `servicios`, `usuarios` (6) | — |
| `DashboardController` | 232 | `asistente_dentista`, `citas`, `pacientes`, `personal`, `servicios`, `usuarios` (6) | — |
| `ClienteCitasController` | 204 | `citas`, `horarios_dentista`, `pacientes`, `personal`, `servicios`, `usuarios` (6) | — |
| `ExpedientesController` | 197 | `citas`, `expedientes`, `pacientes`, `personal`, `servicios`, `usuarios` (6) | — |
| `RegisterController` | 179 | `pacientes`, `personal`, `usuarios` (3) | `Usuario` |
| `UsuarioController` | 176 | `usuarios` (1) | `Usuario` |
| `PerfilController` | 102 | `pacientes`, `personal`, `usuarios` (3) | — |
| `ServiciosController` | 97 | — (solo vía modelo) | `Servicio` |
| `GoogleAuthController` | 63 | — (solo vía modelo) | `Usuario` |
| `AuthController` | 50 | — (vía el guard) | — |
| `Controller` (base) | 11 | — | — |
| **Total** | **2.468** | | |

**Frecuencia inversa** — cuántos controladores tocan cada tabla:

| Tabla | Controladores |
| --- | --- |
| `usuarios` | 9 |
| `personal` | 7 |
| `pacientes` | 7 |
| `citas` | 6 |
| `servicios` | 6 |
| `expedientes` | 3 |
| `asistente_dentista` | 3 |
| `horarios_dentista` | 3 |
| `historial_citas` | 1 |

Esto es la medida directa del problema de fronteras que el intent quiere
resolver. `usuarios` y `personal` son concentradores: **todo controlador de
dominio entra directamente en ellas** para proyectar `CONCAT(nombre, ' ',
apellido)`. La agrupación que propone el alcance del trabajo (Agenda, Pacientes,
Clínica, Identidad) implica que 9 controladores dejen de consultar `usuarios`
directamente y 7 dejen de consultar `personal`; eso es la mayor parte del
trabajo de extracción, y `CitasController` —que toca las 9 tablas— es el caso
límite: cruza las cuatro fronteras propuestas a la vez.

#### Uso del constructor de consultas — cifras verificadas

Sobre `app/Http/Controllers/`, commit `4a24b5e`:

| Medida | HEAD (`4a24b5e`) | `9fcff4c` (almacén previo) |
| --- | --- | --- |
| `DB::table(` | **113** | **111** |
| `->join(` + `->leftJoin(` | **85** | **85** |
| `DB::` (todas las formas) | 159 | — |
| `DB::raw(` | 39 | — |
| `CONCAT(` | 44 | — |
| `whereRaw` / `orWhereRaw` / `orderByRaw` | 9 | — |
| de los 85 cruces, `leftJoin` | 25 | — |

**Discrepancia resuelta.** La descripción del intent afirma «111 invocaciones de
query builder y 85 cruces». El recuento de cruces **coincide exactamente** y
sigue vigente. El de invocaciones era la cifra del commit `9fcff4c`: se verificó
recontando sobre los blobs de ese commit (`git show 9fcff4c:<archivo>`), que dan
111. Las **2 invocaciones nuevas** las añadió `UsuarioController::toggleActivo()`
en el intent `260903-fix-admin-crud` (líneas 165 y 168 del archivo actual). La
cifra vigente es **113**.

Distribución por archivo:

| Controlador | `DB::table(` | cruces |
| --- | --- | --- |
| `PersonalController` | 28 | 13 |
| `PacientesController` | 19 | 18 |
| `CitasController` | 18 | 9 |
| `DashboardController` | 15 | 20 |
| `ClienteCitasController` | 11 | 7 |
| `ExpedientesController` | 9 | 14 |
| `PerfilController` | 6 | 2 |
| `RegisterController` | 5 | 2 |
| `UsuarioController` | 2 | 0 |

### La duplicación medida

#### 1. La regla de agenda — duplicada, y con entradas divergentes

Las dos implementaciones:

- `app/Http/Controllers/CitasController.php`, método `store()`, **líneas 242-280**
- `app/Http/Controllers/ClienteCitasController.php`, método `agendarStore()`, **líneas 149-185**

Normalizando la sangría y comparando con `diff`, los dos bloques son **idénticos
salvo**: un comentario, tres comas finales, dos espacios de alineación, el
espaciado de `! $horario` frente a `!$horario`, y **una sola diferencia
semántica**: el mensaje de error del conflicto («El dentista ya tiene una cita en
ese horario. Por favor elige otro horario.» frente a «Ese horario ya no está
disponible. Por favor elige otro.»).

La regla que ambas implementan tiene **tres predicados**:

1. **Existe horario ese día**: consulta `horarios_dentista` por `(personal_id, dia_semana)` con `dia_semana = $fechaHora->isoWeekday()` (1=lunes … 7=domingo, coherente con el comentario de la columna). Si no hay fila o `disponible` es falso → error.
2. **La cita cabe en la ventana de atención**: `$fechaHora >= hora_inicio` y `$fechaHora + duracion <= hora_fin`, ambos construidos con `Carbon::parse($fecha . ' ' . $hora_*)`.
3. **No hay solapamiento**: sobre `citas` del mismo `dentista_id`, excluyendo `estado` en `('cancelada','no_asistio')`, con el predicado compuesto:

```php
$q->whereBetween('fecha_hora', [$fechaHora, $horaFinCita])
  ->orWhereRaw('DATE_ADD(fecha_hora, INTERVAL duracion_min MINUTE) > ? AND fecha_hora < ?',
      [$fechaHora, $horaFinCita]);
```

**Las entradas de la regla divergen entre las dos llamadas**, y eso es más
peligroso que la duplicación misma, porque extraerla a un único sitio exige
decidir qué contrato gana:

| Entrada | `CitasController::store` (admin/dentista/asistente) | `ClienteCitasController::agendarStore` (cliente) |
| --- | --- | --- |
| Duración | `$data['duracion_min']` del formulario, validado `required\|integer\|min:10\|max:300` | derivada: `$servicio->duracion_min ?? 30`; el cliente no la elige |
| Fecha | `required\|date` — **acepta fechas pasadas** | `required\|date\|after_or_equal:today` |
| `servicio_id` | `nullable\|exists:servicios,id` | `required\|exists:servicios,id` |
| `notas_internas` | se escribe | no se escribe |
| Autorización de fila | `autorizadoParaCita($data['dentista_id'])` antes de todo | implícita: la cita se ata a `pacienteActual()` |
| Destino tras el alta | `route("$base.citas", …)` según rol | `route('cliente.dashboard')` |

**Un tercer sitio expresa la misma ventana de atención**, y la descripción del
intent no lo recoge: `resources/views/citas/cliente-agendar.blade.php`, línea 84,
fija el campo de hora como `<input type="time" … min="08:00" max="20:00"
step="900">`. Esa ventana 08:00-20:00 con pasos de 15 minutos está **escrita a
mano en HTML** y nada en `horarios_dentista` la garantiza: un dentista con
horario de 07:00 a 21:00 no puede ser reservado por un cliente fuera de esa
franja, y uno con horario de 09:00 a 13:00 recibe peticiones que el servidor
rechazará. `resources/views/citas/create.blade.php` hace lo propio con la
duración (`min="10" max="300" step="5"`, línea 95, espejo del rango del servidor)
y su bloque `@push('scripts')` (líneas 190-205) rellena la duración desde el
`data-duracion` del servicio elegido pero **deja que el usuario la sobrescriba**
— de ahí que el camino del personal acepte una duración que no es la del
servicio.

**Dos defectos que viajan dentro de la copia**, presentes de forma idéntica en
ambos sitios:

- **Ventana de concurrencia.** Entre la comprobación de solapamiento y el `insert()` no hay transacción, ni bloqueo de fila, ni restricción única en `citas` sobre `(dentista_id, fecha_hora)` — los índices del volcado son todos no únicos. Dos peticiones simultáneas pueden pasar las dos la comprobación y crear la doble reserva que la regla existe para impedir. El almacén previo lo registró como `TD-19`; **sigue vigente**. Extraer la regla a un único sitio no cierra por sí solo esta ventana: hay que añadir la transacción y, preferiblemente, una restricción en el almacén.
- **Predicado de solapamiento sobre-inclusivo.** La mitad `orWhereRaw` es el predicado de intervalos semiabierto correcto (`fin_existente > inicio_nuevo AND inicio_existente < fin_nuevo`). La mitad `whereBetween('fecha_hora', [$fechaHora, $horaFinCita])` es **redundante** frente a ella y además **inclusiva en el extremo superior**: una cita que empieza exactamente cuando la nueva termina se marca como conflicto. Al escribir la prueba propia de la regla hay que decidir si ese comportamiento es el pretendido; hoy no está documentado en ninguna parte.

**Cobertura de prueba actual de esta regla: ninguna.** Ver «Test Coverage».

#### 2. El ayudante de dentistas disponibles — copia literal confirmada

Dos métodos privados **idénticos byte a byte** (11 líneas cada uno, verificado
con `diff`):

- `app/Http/Controllers/PacientesController.php`, líneas **327-337**
- `app/Http/Controllers/PersonalController.php`, líneas **398-408**

```php
private function dentistasDisponibles()
{
    return DB::table('personal')
        ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
        ->where('personal.tipo', 'dentista')
        ->where('usuarios.activo', 1)
        ->select('personal.id', 'personal.especialidad',
            DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
        ->orderBy('usuarios.nombre')
        ->get();
}
```

Esas son las «2 copias literales» que afirma la descripción del intent:
**confirmado exactamente**. Pero el mismo predicado (`personal.tipo = 'dentista'`
más `usuarios.activo = 1` sobre el cruce `personal`→`usuarios`) aparece en
**7 sitios en total**, los otros 5 en línea dentro del método que los necesita y
con proyecciones distintas:

| Sitio | Alias de la columna de nombre | Columnas extra |
| --- | --- | --- |
| `PacientesController.php:331` (ayudante privado) | `nombre_completo` | `especialidad` |
| `PersonalController.php:402` (ayudante privado, copia literal) | `nombre_completo` | `especialidad` |
| `CitasController.php:136` (en `index()`) | `nombre` | — |
| `CitasController.php:162` (en `create()`) | `nombre` | — |
| `ClienteCitasController.php:110` (en `agendarForm()`) | `nombre` | `especialidad` |
| `ExpedientesController.php:84` (en `create()`) | `nombre` | — |
| `RegisterController.php:173` (en `asignarDentistaAutomatico()`) | — (devuelve `personal.id`) | añade `leftJoin('pacientes')`, `groupBy` y `orderByRaw('COUNT(pacientes.id) ASC')` para balancear carga |

Las dos ocurrencias de `CitasController` (líneas 134-139 y 160-165) son también
idénticas entre sí. Los dos alias distintos (`nombre` frente a
`nombre_completo`) son un contrato implícito con las plantillas Blade: al
unificar el ayudante hay que unificar también el nombre de la columna en las
vistas que lo consumen.

#### 3. La consulta base de citas — el espinazo de cruces reproducido

El espinazo completo de 5 cruces (`citas` → `pacientes` → `usuarios` como
paciente; `citas` → `personal` → `usuarios` como dentista; `LEFT JOIN servicios`)
aparece en **5 sitios**: 3 en PHP y 2 en SQL.

| Sitio | Proyección |
| --- | --- |
| `CitasController::queryBase()` — líneas 12-40 | 18 columnas, la más completa; incluye `notas_internas`, `color_agenda`, `email_paciente` |
| `DashboardController::admin()` — líneas 27-44 | 7 columnas, filtrada por `whereDate(fecha_hora, hoy)` |
| `DashboardController::asistente()` — líneas 149-167 | 7 columnas, filtrada por `whereIn(dentista_id, …)` y día |
| vista SQL `v_citas_hoy` | 12 columnas, filtrada por `CURDATE()` |
| vista SQL `v_historial_paciente` | 10 columnas, con `LEFT JOIN expedientes` en lugar del filtro de fecha |

Esas son las «4 reproducciones» que afirma la descripción del intent, si se
cuentan solo las de PHP más una de las vistas. El recuento verificado es **5**, y
el matiz importa: **dos de las cinco son vistas SQL**, de modo que unificar la
consulta base en la capa de dominio deja dos definiciones del mismo cruce
viviendo en el almacén, con el `DEFINER='root'@'localhost'` ya señalado. El
alcance del trabajo pide recrear esas vistas en migraciones; conviene decidir a
la vez si siguen siendo la fuente de esas lecturas o si el código pasa a
consultarlas en exclusiva.

Además hay **9 reproducciones parciales** del mismo espinazo en PHP, cada una
omitiendo el par de cruces que su caso no necesita:

- sin el par `pacientes`→`usuarios as up`: `ClienteCitasController::index` (25-38), `::show` (60-77), `DashboardController::cliente` ×2 (191-207, 209-224), `PacientesController::show` citas (198-210)
- sin el par `personal`→`usuarios as ud`: `DashboardController::dentista` (82-99), `PersonalController::show` citas recientes (188-200), `ExpedientesController::create` citas (408-416), `PacientesController::show` próxima cita (215-222)

Recuento de los pares de cruce sueltos en todo `app/Http/Controllers/`:

- `->join('usuarios as ud', 'personal.usuario_id', …)` — **14 sitios**
- `->join('usuarios as up', 'pacientes.usuario_id', …)` — **7 sitios**
- `CONCAT(` (casi siempre `CONCAT(nombre, ' ', apellido)`) — **44 sitios**

El nombre completo de una persona se compone a mano 44 veces. Esa es la
proyección que un accesor de modelo o un objeto de valor resuelve una vez.

#### 4. Autorización de fila — dispersa y sin Policy

No hay ninguna Policy. La autorización vive en dos planos:

- **Por rol, a nivel de ruta**: `RoleMiddleware` (alias `role`), 28 líneas, comprueba `auth()->check()` y `in_array(auth()->user()->rol, $roles)`. Es el **único** mecanismo de autorización declarativo del sistema.
- **Por fila, a mano dentro de los controladores**:
  - `CitasController` — 4 métodos privados: `personalIdPropio()` (43-46), `dentistaIdsAsistente()` (48-57), `aplicarAlcanceRol($query)` (60-71), `autorizadoParaCita($dentistaId)` (74-83). Los usan `index`, `create`, `store`, `show` y `updateEstado`.
  - `ClienteCitasController` — `pacienteActual()` (12-15), usado por `index`, `show`, `cancelar` y `agendarStore`.
  - `PacientesController` — `if (auth()->user()->rol === 'asistente') abort(403);` en línea dentro de `edit` (234) y `update` (253).
  - `ExpedientesController` — el mismo patrón en línea en `create` (395), `store` (441) y `update` (521); y un filtro por dentista propietario dentro de `index` (367-370).
  - `PersonalController::misDoctores` y los cuatro métodos de `DashboardController` — resuelven su propio alcance consultando `personal` por `usuario_id`.

Cinco formas distintas de responder a la misma pregunta. Y la dispersión produce
huecos, listados en la sección siguiente.

### Test Coverage

- **Test Directories**: `tests/Unit/` (1 archivo), `tests/Feature/` (5 archivos), más `tests/TestCase.php`. 1.110 líneas en total.
- **Test Frameworks**: PHPUnit `12.5.30`; `mockery/mockery` 1.6.12 y `fakerphp/faker` v1.24.1 disponibles pero **sin uso en las pruebas existentes**. No hay Pest (aunque `composer.json` deja `pestphp/pest-plugin` en `allow-plugins`).
- **Coverage Config**: **ausente**. `phpunit.xml` declara `<source><include><directory>app</directory></include></source>`, que es el origen de cobertura de PHPUnit 10+, pero **no hay elemento `<coverage>`**, ni informe, ni umbral, ni `<report>`. El alcance `classic` impone un suelo del 80 % de cobertura de líneas y ejecución en CI; hoy **no existe forma de medir ese suelo** con la configuración presente, y tampoco hay CI. Es el primer hueco de tooling que la etapa siguiente debe cerrar.

**Arnés de base de datos** — `database/testing/bootstrap-test-db.sh`, 136 líneas,
idempotente, con mensajes de error accionables en cada borde:

1. verifica `docker` en el PATH y `docker compose` v2;
2. verifica que existe `database/dentissa.sql`;
3. verifica que el servicio `db` está en marcha y espera hasta 30 intentos a que MariaDB acepte conexiones (`mariadb-admin ping`);
4. `DROP DATABASE IF EXISTS dentissa_test; CREATE DATABASE …` con `utf8mb4_unicode_ci`;
5. carga el volcado redirigiéndolo a `mariadb … dentissa_test`;
6. **verifica** que existen `usuarios servicios pacientes citas personal expedientes` y falla si falta alguna.

`phpunit.xml` fija `DB_CONNECTION=mariadb`, `DB_HOST=db`,
`DB_DATABASE=dentissa_test`, `DB_USERNAME=root`, `DB_PASSWORD=root`, más
`CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`,
`MAIL_MAILER=array` y `BCRYPT_ROUNDS=4`. Credenciales del entorno de desarrollo
local ya declaradas en `docker-compose.yml`; ninguna nueva.

**22 métodos de prueba** — coincide exactamente con las «22 pruebas en verde» que
afirma la descripción del intent. Desglose:

| Archivo | Pruebas | Aislamiento | Qué cubre |
| --- | --- | --- | --- |
| `AdminVistasSmokeTest.php` (394 líneas) | 7 | `DatabaseTransactions` | renderizado de las 4 pantallas de usuarios, filtros por texto y tipo, filtro sin resultados, 404 en usuario inexistente, campos no tocados en servicios, propagación del precio a **`v_citas_hoy` y `v_historial_paciente`** |
| `UsuariosAdminTest.php` (323 líneas) | 5 | `DatabaseTransactions` | rol `asistente` en edición parcial, correo duplicado, alta sin contraseña, baja lógica conservando el expediente, reactivación |
| `ServiciosPrecioTest.php` (233 líneas) | 4 | `DatabaseTransactions` | precio y color en el alta, precio en edición parcial, precio negativo rechazado, color inválido rechazado |
| `AdminUsuariosRutasTest.php` (106 líneas) | 4 | ninguno (no toca la base) | resolución de la tabla de rutas: `create` antes de `{id}`, restricción UUID, ausencia del borrado y presencia de la alternancia |
| `ExampleTest.php` (Feature, 28 líneas) | 1 | ninguno | `GET /` redirige a `/login` para el visitante anónimo |
| `ExampleTest.php` (Unit, 16 líneas) | 1 | ninguno; extiende `PHPUnit\Framework\TestCase` | `assertTrue(true)` — andamiaje |

**Ninguna prueba usa `RefreshDatabase`**, y las cuatro que tocan la base lo
documentan: `RefreshDatabase` ejecutaría las migraciones de Laravel y dejaría la
base sin las tablas del dominio. Es la consecuencia directa de TD-1 sobre la
estrategia de pruebas, y **desaparece en cuanto existan las migraciones del
dominio**: escribirlas habilita `RefreshDatabase` y, con él, base limpia por
prueba en lugar de transacciones sobre un volcado compartido. Ese es el «de paso
desbloquea las pruebas» de la descripción del intent, y está confirmado.

**Rutas ejercitadas por HTTP** — el conjunto completo:
`admin.usuarios.index` (10 llamadas), `admin.servicios.edit` (9),
`admin.servicios.update` (7), `admin.usuarios.edit` (5),
`admin.usuarios.create` (5), `admin.usuarios.toggle` (3),
`admin.usuarios.store` (3), `admin.usuarios.show` (2),
`admin.servicios.index` (2), `admin.servicios.create` (2),
`admin.usuarios.update` (1), `admin.servicios.store` (1), `GET /` (1).

Es decir: **2 de los 13 controladores** (`UsuarioController` y
`ServiciosController`) tienen prueba por HTTP — exactamente los dos que tocó el
intent anterior. **Los otros 11 no tienen ninguna.**

**Cobertura por tabla de dominio**:

| Tabla | Cómo aparece en las pruebas |
| --- | --- |
| `usuarios` | sujeto de prueba (`UsuariosAdminTest`, `AdminVistasSmokeTest`) |
| `servicios` | sujeto de prueba (`ServiciosPrecioTest`, `AdminVistasSmokeTest`) |
| `pacientes`, `citas`, `expedientes`, `personal` | **solo como fixture**, para verificar que la baja lógica conserva el expediente y que el precio llega a las vistas SQL |
| `horarios_dentista` | **no aparece en ninguna prueba** |
| `historial_citas` | **no aparece en ninguna prueba** |
| `asistente_dentista` | **no aparece** — y no podría: la tabla no existe en la base de pruebas |

**La regla de agenda no tiene ninguna prueba.** Búsqueda en todo `tests/` de
`horarios_dentista`, `agendar`, `solap` y `conflicto`: ninguna coincidencia
funcional; los únicos aciertos de `duracion_min` son valores de fixture de
servicios y citas. Ni la disponibilidad semanal, ni la ventana de atención, ni el
solapamiento están verificados por nada. La pieza que la descripción del intent
llama «la más crítica porque impide la doble reserva» es hoy **la menos
verificada del sistema**.

**Andamiaje reutilizable** que la etapa siguiente puede aprovechar en lugar de
reinventar:

- `crearUsuario(string $rol, array $valores = []): Usuario` — `AdminVistasSmokeTest:43`, `UsuariosAdminTest:44`. Usa `Usuario::create()`; funciona como sujeto de `actingAs()` porque `config/auth.php` enlaza el guard `web` a `Usuario`.
- `crearAdmin(): Usuario` — `ServiciosPrecioTest:38`.
- `crearServicio(array $valores = []): string` — `AdminVistasSmokeTest:60`, `ServiciosPrecioTest:55`. Inserta con `DB::table()` a propósito: el fixture no debe pasar por el controlador bajo prueba. Patrón a conservar.
- `payloadFormulario(array $valores = []): array` — `ServiciosPrecioTest:77`, `UsuariosAdminTest:60`.
- `crearHistorialClinico(string $usuarioClienteId): array` — `UsuariosAdminTest:81`. **La pieza más cercana a un fixture de agenda que existe**: construye la cadena completa `usuario(dentista)` → `personal` → `pacientes` → `citas` → `expedientes` y devuelve los tres identificadores. **Le falta exactamente una cosa** para servir a la regla de agenda: no inserta ninguna fila en `horarios_dentista`.
- `accionDe(string $uri, string $metodo): string` — `AdminUsuariosRutasTest:29`. Interroga al enrutador sin sesión, sin middleware y sin base de datos. Es el patrón que puede sostener la prueba de arquitectura que pide el alcance del trabajo.

### Code Quality Indicators

- **Linting**: `laravel/pint` v1.29.3 está en `require-dev` y **no hay `pint.json`**. Tampoco `.php-cs-fixer.php`, `phpstan.neon`, `psalm.xml` ni `rector.php`. No hay análisis estático de ningún tipo. `.editorconfig` sí existe (252 bytes) y es lo único que fija estilo de archivo.
- **CI/CD**: **ausente por completo**. No hay `.github/`, ni `.gitlab-ci.yml`, ni `Jenkinsfile`, ni `Makefile`. Nada ejecuta las 22 pruebas automáticamente; su ejecución es manual y requiere Docker en marcha más el script de arranque. El alcance `classic` exige ejecución en CI antes de fusionar; hoy no existe el sitio donde eso ocurriría.
- **Documentation**: `README.md` es el de serie de Laravel sin adaptar (ver «Build System»). Los comentarios de código, en cambio, son **desigualmente buenos**: los archivos que tocó el intent anterior (`UsuarioController`, `ServiciosController`, `phpunit.xml`, `database/testing/bootstrap-test-db.sh`, las 4 pruebas nuevas) llevan docblocks que explican el *por qué*, citan identificadores de requisito (`FR3.1`, `NFR1`, `TD-8`, `TD-16`) y advierten de decisiones no obvias; los controladores originales llevan solo comentarios de sección decorativos (`// ── INDEX ───`). Hay dos registros de convención dentro del código que conviene preservar: el comentario de `routes/web.php` líneas 90-92 explica que el orden de registro importa y por qué `{id}` está restringido a UUID, y el de `UsuarioController` líneas 149-151 explica que `actualizado_en` se escribe directamente porque el modelo declara `$timestamps = false`.
- **Marcadores de deuda en comentarios**: **cero**. Ni un `TODO`, `FIXME`, `HACK` ni `XXX` en `app/`, `routes/`, `database/`, `tests/` ni `resources/views/`. La deuda de este proyecto no está anotada en el código; hay que leerla en la estructura.
- **Capa de vistas** (barrido estructural, 37 plantillas): 33 etiquetas `<form>` — 6 con `method="GET"` (filtros de búsqueda) y 27 no-GET, frente a **27 `@csrf`**: la correspondencia es exacta, **no hay hueco de CSRF**. 18 `@method`. **Cero `{!! !!}`**: no hay salida sin escapar. 60 llamadas a `route()`. Ninguna plantilla usa `DB::`.
- **Fuga de lógica a la vista**: 27 bloques `@php` y **21 lecturas de `auth()->user()->rol`** repartidas en 10 plantillas (4 de ellas en `layouts/app.blade.php`). La decisión de qué ve cada rol se toma también en la capa de presentación, no solo en el middleware y los controladores. Eso es un tercer plano de autorización que la frontera del módulo Identidad tendrá que absorber o declarar explícitamente.
- **Deriva de formato PSR-12**: cuatro bloques de método con el cuerpo pegado a la columna 0, rompiendo la sangría del archivo que los contiene: `CitasController::store` líneas **236-274** (justo el bloque de la regla de agenda), `PacientesController::store` líneas **84-163**, `AuthController::login` líneas **22-37**, `RoleMiddleware::handle` líneas **16-27**. Coherente con la regla de estilo ya aprendida en este proyecto: la deriva es preexistente y generalizada, `pint` nunca se ha ejecutado aquí y no hay línea base formateada.

### Technical Debt Signals

Ordenadas por su relación con el trabajo que pide el intent.

1. **`TD-1` — El esquema del dominio no es reproducible.** Ninguna de las 9 tablas de dominio, ninguna de las 2 vistas SQL ni el disparador `trg_historial_cita` pueden recrearse desde `database/migrations/`. Dos migraciones alteran tablas que nadie crea y una crea una tabla con claves foráneas a una tabla que nadie crea. Evidencia: las 6 migraciones, enumeradas arriba. **Bloquea todo lo demás**, y de forma comprobable: rompe el `scripts.setup` del propio repositorio y obliga a las pruebas a renunciar a `RefreshDatabase`.
2. **`TD-1b` — El volcado está desfasado respecto a las migraciones.** `database/dentissa.sql` no tiene `asistente_dentista`, ni `pacientes.dentista_id`, ni `usuarios.google_id`, y declara `password_hash text NOT NULL`. Su tabla `migrations` registra 3 filas. El esquema efectivo es «volcado + 3 migraciones» y **ninguna fuente por separado lo produce**. Consecuencia medible: la base `dentissa_test` que usan las 22 pruebas carece de `asistente_dentista` y de `pacientes.dentista_id`.
3. **`TD-20` — La línea base no permite agendar.** `horarios_dentista` tiene **0 filas** en el volcado y `personal` tiene **1**, con **0 usuarios de rol `asistente`**. Tras un arranque limpio no puede crearse ninguna cita, ni por interfaz ni por prueba. Cualquier seeder sintético debe sembrar horarios, al menos 2 dentistas y al menos 1 asistente con su asignación, o la regla de agenda seguirá sin poder verificarse.
4. **`TD-5` — Sin capa de dominio.** 113 invocaciones de `DB::table(` y 85 cruces escritos a mano dentro de 2.468 líneas de controlador; 2 modelos Eloquent de dominio para 9 tablas y 2 vistas; **cero** relaciones declaradas; **cero** Form Requests, Policies, servicios, acciones, repositorios, eventos y jobs. `usuarios` la tocan 9 de 13 controladores; `personal`, 7. Cuatro directorios en `app/`.
5. **`TD-19` — Ventana de concurrencia en la reserva.** Ni transacción, ni bloqueo, ni restricción única entre la comprobación de solapamiento y el `insert()`, en **ambas** copias de la regla. Los índices de `citas` en el volcado son todos no únicos. La doble reserva es posible hoy.
6. **Predicado de solapamiento sobre-inclusivo y redundante.** La mitad `whereBetween` es inclusiva en el extremo superior y redundante frente a la mitad `orWhereRaw`, que es el predicado correcto. Idéntico en ambas copias; sin documentar y sin prueba, así que no se sabe si el comportamiento es pretendido.
7. **La ventana de atención expresada en tres lugares con tres valores.** Filas de `horarios_dentista` (servidor), las dos copias de la regla, y `min="08:00" max="20:00" step="900"` escrito a mano en `resources/views/citas/cliente-agendar.blade.php:84`.
8. **Hueco de autorización: la agrupación `/cliente` no comprueba el rol.** `routes/web.php:147` declara `Route::middleware('auth')` sin `role:cliente`, a diferencia de las otras tres agrupaciones. Cualquier usuario autenticado alcanza `/cliente/*`. La mayoría de acciones acaba en 404 porque `pacienteActual()` no encuentra fila, pero `ClienteCitasController::agendarForm` (106-124) no comprueba paciente y `PerfilController::update` (300-345) **crea una fila en `pacientes`** para cualquier usuario autenticado que no la tenga — incluido un admin o un dentista.
9. **Hueco de autorización: expedientes sin comprobación de propiedad.** `ExpedientesController::index` (367-370) sí restringe al dentista a los expedientes que él registró, pero `::show` (484-516) y `::update` (519-534) **no comprueban nada**: un dentista puede leer y modificar el expediente clínico de cualquier paciente de cualquier colega. `::update` además pasa el array validado entero a `update($data)` sin filtrar.
10. **Autorización de fila incoherente en citas.** `CitasController::updateNotas` (374-386) y `::destroy` (389-398) **no llaman a `autorizadoParaCita()`**, mientras `store`, `show` y `updateEstado` sí. Hoy el daño está acotado porque ambas rutas solo existen en la agrupación `admin`, pero el patrón es exactamente el que una Policy eliminaría. `::destroy` además redirige siempre a `route('admin.citas')` sin mirar el rol, y cancela sin comprobar el estado actual — a diferencia de `ClienteCitasController::cancelar` (85-103), que sí exige que la cita esté en `agendada` o `confirmada`.
11. **Validación sin Form Request, repetida.** 13 llamadas a `$request->validate()` dispersas por los controladores. Dos controladores ya extrajeron las reglas a métodos privados —`ServiciosController::reglasValidacion()`/`::mensajesValidacion()` y `UsuarioController::mensajesValidacion()`— que son la forma embrionaria del Form Request y el patrón a generalizar. Y hay reglas contradictorias para el mismo campo entre caminos: `fecha` en `citas` acepta pasado por un camino y no por el otro.
12. **`DEF-3` — Ruta que resuelve a un método inexistente.** `Route::resource('servicios', …)` (`routes/web.php:49`) registra `admin.servicios.show`, pero `ServiciosController` no tiene método `show()` y no existe `resources/views/servicios/show.blade.php`. Vigente desde el almacén previo.
13. **`App\Models\User` es código muerto.** Cubre `users`, una tabla de infraestructura. `config/auth.php` lo importa en la línea 3 y no lo usa (el proveedor apunta a `Usuario`). Sus únicos consumidores son `UserFactory` y `DatabaseSeeder`, que a su vez no siembra nada de dominio: crea un solo `User` con `test@example.com`. Es la razón por la que el recuento de «3 modelos» sobreestima la cobertura real del ORM.
14. **`SEC-1` — Datos personales sensibles versionados.** `database/dentissa.sql` está en git y contiene 8 filas de `usuarios` con nombre, apellido, correo real, teléfono y hash bcrypt; 6 filas de `pacientes` cuyas columnas pobladas incluyen `direccion`, `contacto_emergencia`, `telefono_emergencia`, `tipo_sangre`, `alergias`, `enfermedades_cronicas`, `medicamentos_actuales` y `notas_medicas`; y 1 fila de `sessions` con una carga de sesión real. Además `README.md` conserva dos líneas con etiquetas de credencial al final. Todo está en la historia de git, así que sustituir el volcado por un seeder sintético **reduce la exposición futura pero no borra el pasado**: eso hay que decirlo en el plan. **Este documento no reproduce ningún valor**: se nombra el archivo, la tabla y el tipo de columna, nunca el dato. `.env` **no está versionado** (`.gitignore:3`) y no se abrió.
15. **`SQL SECURITY DEFINER` con `DEFINER='root'@'localhost'`** en ambas vistas: leen con los privilegios de `root` y no del usuario que consulta, y ninguna filtra por rol ni por paciente. `v_historial_paciente` expone `diagnostico` y `tratamiento`; `v_citas_hoy` expone `notas_internas`. Al recrearlas en migraciones hay que elegir el contexto de seguridad de forma explícita.
16. **Regla de negocio implementada en la base de datos, y duplicada en el código.** El disparador `trg_historial_cita` escribe en `historial_citas` al cambiar `citas.estado`, y `CitasController::updateEstado` añade una segunda escritura cuando hay `motivo`: un cambio con motivo produce dos filas incompletas y complementarias. Decidir dónde vive esa regla es parte del trabajo de la capa de dominio.
17. **Sin CI, sin análisis estático, sin configuración de cobertura, sin `pint.json`.** El alcance `classic` exige un suelo del 80 % de líneas y ejecución en CI; hoy no existe ni el medidor ni el sitio donde ejecutarlo.
18. **Sin prueba de arquitectura.** No hay ninguna biblioteca ni prueba que vigile fronteras. El alcance del trabajo pide una; `AdminUsuariosRutasTest::accionDe()` muestra el patrón —interrogar al framework sin base de datos— sobre el que puede construirse.
19. **`horarios_dentista` sin `ENGINE=InnoDB` declarado** en el volcado, a diferencia de las otras 8 tablas de dominio. Depende del motor por defecto del servidor; con MariaDB moderno será InnoDB, pero al escribir la migración conviene fijarlo explícitamente en lugar de heredar el comportamiento.
20. **`config/database.php` conserva `'default' => env('DB_CONNECTION', 'sqlite')`** y `composer.json` sigue tocando `database/database.sqlite` en `post-create-project-cmd`: andamiaje que contradice la pila real y que puede llevar a un clon nuevo a una conexión SQLite sin tablas de dominio.
21. **Cadena de assets Vite inerte.** Vite 8, Tailwind 4 y axios declarados y no consumidos por las plantillas analizadas; el layout carga hojas estáticas de `public/css/` (fuera del foco). El estado del workflow todavía registra `Frameworks: Vite`, corrección que el almacén previo ya había hecho y que sigue siendo necesaria: la pila es **Laravel v12.62.0 sobre PHP ^8.2 con MariaDB**.

## Handoff Summary

- **Intent-relevant finding**: El bloqueo TD-1 es **más profundo de lo que afirma la descripción del intent, y comprobablemente roto**. No es que 8 de las 9 tablas de dominio carezcan de migración: **ninguna de las 9 es reproducible** desde `database/migrations/`, porque la única migración que crea una tabla de dominio (`2026_07_20_000000_create_asistente_dentista_table.php`) declara dos claves foráneas a `personal(id)` y nada crea `personal`. Además el volcado —la otra mitad de la verdad— está **desfasado respecto a las tres migraciones de julio de 2026**: su tabla `migrations` registra 3 filas, y le faltan `asistente_dentista`, `pacientes.dentista_id`, `usuarios.google_id` y el `password_hash` anulable. El esquema efectivo es «volcado + 3 migraciones» y **ninguna de las dos fuentes por separado lo produce**. La prueba de que esto no es teórico: `composer.json` → `scripts.setup` ejecuta `php artisan migrate --force` y **falla sobre un clon limpio** en `2026_07_18_195924_add_google_id_to_usuarios_table.php` (`Schema::table('usuarios', …)`), de modo que el procedimiento de arranque que el propio repositorio declara no funciona; y la base `dentissa_test` que sostiene las 22 pruebas carece de `asistente_dentista` y de `pacientes.dentista_id`, así que cualquier prueba nueva sobre la asignación asistente→dentista o sobre el dentista asignado a un paciente falla hoy contra el arnés existente. El primer paso que el intent declara innegociable lo es, y la migración de `asistente_dentista` tiene que **reordenarse**, no solo recibir compañeras.

- **Risks / follow-up**:
  - **La regla de agenda no tiene ninguna prueba, y la línea base no permite escribirla.** Búsqueda en todo `tests/` de `horarios_dentista`, `agendar`, `solap` y `conflicto`: ninguna coincidencia funcional. Y `horarios_dentista` tiene **0 filas** en el volcado, con `personal` a **1 fila** y **0 usuarios de rol `asistente`**, así que tras un arranque limpio **no puede agendarse ninguna cita**. El seeder sintético que pide el alcance del trabajo es **prerrequisito** de la prueba propia de la regla, no un entregable paralelo: debe sembrar horarios, al menos 2 dentistas y al menos 1 asistente con su asignación. El fixture `UsuariosAdminTest::crearHistorialClinico()` (línea 81) ya construye la cadena `usuario`→`personal`→`pacientes`→`citas`→`expedientes`; le falta exactamente la fila de `horarios_dentista`.
  - **Extraer la regla de agenda a un único sitio exige decidir un contrato, no solo mover código.** Los dos bloques (`CitasController.php:242-280` y `ClienteCitasController.php:149-185`) son idénticos salvo espaciado, tres comas y **un** mensaje de error; pero sus **entradas divergen**: la duración viene del formulario con rango 10-300 en un camino y se deriva del servicio con respaldo 30 en el otro; `fecha` es `required|date` (acepta pasado) en uno y `after_or_equal:today` en el otro; `servicio_id` es `nullable` en uno y `required` en el otro. Hay además un **tercer sitio** que la descripción del intent no recoge: `resources/views/citas/cliente-agendar.blade.php:84` fija la ventana de atención a mano en HTML (`min="08:00" max="20:00" step="900"`), valor que nada en `horarios_dentista` garantiza.
  - **Dos defectos viajan dentro de la copia y sobreviven a una extracción ingenua.** (a) No hay transacción, bloqueo ni restricción única entre la comprobación de solapamiento y el `insert()` en **ninguna** de las dos copias, y los índices de `citas` son todos no únicos: la doble reserva es posible hoy (`TD-19` del almacén previo, vigente). Unificar la regla **no cierra** esa ventana; hay que añadir la transacción y, preferiblemente, una restricción en el almacén al escribir la migración de `citas`. (b) El predicado usa `whereBetween('fecha_hora', [$inicio, $fin])` **además** del `orWhereRaw` correcto; ese `whereBetween` es redundante e **inclusivo en el extremo superior**, así que una cita que empieza exactamente cuando la nueva termina cuenta como conflicto. Sin prueba ni documentación no se sabe si es pretendido: hay que decidirlo explícitamente antes de fijarlo en una prueba.
  - **Tres cifras de la descripción del intent verificadas, una corregida.** Coinciden exactamente: **2.468** líneas en 13 controladores, **85** cruces escritos a mano, **8.756** líneas (`app/` + `resources/views/` + `routes/`) y **22** pruebas. Corregida: las invocaciones de query builder son **113**, no 111; la cifra de 111 es la del commit `9fcff4c` que construyó el almacén previo (verificado recontando sobre los blobs de ese commit) y las 2 nuevas las añadió `UsuarioController::toggleActivo()` en el intent anterior.
  - **El recuento de modelos sobreestima la cobertura del ORM.** Hay 3 archivos de modelo pero solo **2 cubren tablas de dominio** (`Usuario`→`usuarios`, `Servicio`→`servicios`). `App\Models\User`→`users` es una tabla de infraestructura: `config/auth.php` lo importa en la línea 3 y **no lo usa** (el proveedor del guard `web` apunta a `Usuario`, línea 67), y sus únicos consumidores son `UserFactory` y `DatabaseSeeder`. Así que el punto de partida real es **2 modelos para 9 tablas y 2 vistas**, y **ningún modelo declara ninguna relación**. Los 10 modelos que pide el alcance del trabajo son 7 nuevos más 2 que hay que dotar de relaciones, no 7 sobre una base de 3.
  - **La agrupación de rutas de cliente no comprueba el rol** (`routes/web.php:147`: `Route::middleware('auth')` sin `role:cliente`, a diferencia de las otras tres). `PerfilController::update` llega a **crear una fila en `pacientes`** para cualquier usuario autenticado que no la tenga. Y la autorización de fila está en cinco formas distintas, con dos huecos concretos: `ExpedientesController::show`/`::update` no comprueban propiedad (un dentista lee y modifica el expediente de cualquier colega) y `CitasController::updateNotas`/`::destroy` no llaman a `autorizadoParaCita()`. Las Policies del alcance del trabajo tienen que **cubrir** esto, no solo formalizar lo que ya funciona.
  - **La agrupación por módulos choca de frente con dos concentradores.** `usuarios` la tocan **9 de 13** controladores y `personal`, **7**, siempre para proyectar `CONCAT(nombre, ' ', apellido)` — 44 veces en total. `CitasController` toca **las 9** tablas de dominio y por tanto cruza las cuatro fronteras propuestas a la vez: es el caso límite de la prueba de arquitectura. Y la decisión de qué ve cada rol vive también en la capa de presentación: **21** lecturas de `auth()->user()->rol` en 10 plantillas Blade, 4 de ellas en `layouts/app.blade.php`. La frontera de Identidad tiene que absorber ese tercer plano o declararlo explícitamente como excepción.
  - **Dos de las cinco reproducciones de la consulta base de citas son vistas SQL**, no PHP: `v_citas_hoy` y `v_historial_paciente`. Recrearlas en migraciones —como pide el alcance— deja dos definiciones del mismo espinazo de cruces viviendo en el almacén, ambas con `SQL SECURITY DEFINER` y `DEFINER='root'@'localhost'`, ambas sin filtro por rol ni por paciente, y exponiendo `notas_internas`, `diagnostico` y `tratamiento`. Conviene decidir a la vez el contexto de seguridad y si el código pasa a leer de las vistas en exclusiva o deja de usarlas.
  - **Hay una regla de negocio implementada en la base de datos que el alcance del trabajo no menciona**: el disparador `trg_historial_cita` sobre `citas` escribe en `historial_citas` al cambiar de estado, y `CitasController::updateEstado` (líneas 357-368) **añade una segunda escritura** cuando el formulario trae `motivo`, de modo que un cambio con motivo produce **dos filas incompletas y complementarias** (la del disparador sin `cambiado_por` ni `motivo`; la del controlador sin `estado_anterior`). Recrear el esquema obliga a decidir si el disparador sobrevive; la capa de dominio, a decidir dónde vive la regla.
  - **El alcance `classic` pide un suelo del 80 % de cobertura de líneas y ejecución en CI, y hoy falta la infraestructura para ambos.** `phpunit.xml` declara `<source>` pero **no tiene elemento `<coverage>`**: no hay informe, ni umbral, ni forma de medir el suelo. Y no hay CI de ningún tipo (sin `.github/`, sin `.gitlab-ci.yml`, sin `Jenkinsfile`, sin `Makefile`), así que nada ejecuta las 22 pruebas automáticamente. Tampoco hay `pint.json`, phpstan, psalm ni rector. Además, solo **2 de los 13 controladores** tienen prueba por HTTP (`UsuarioController` y `ServiciosController`, los dos que tocó el intent anterior): medir cobertura hoy daría una cifra muy por debajo del 80 % y el plan debe contar con ello.
  - **Datos personales sensibles versionados, y la sustitución no borra el pasado.** `database/dentissa.sql` está en git con 8 filas de `usuarios` (correo real, teléfono, hash bcrypt), 6 de `pacientes` con `direccion`, `contacto_emergencia`, `tipo_sangre`, `alergias`, `enfermedades_cronicas`, `medicamentos_actuales` y `notas_medicas`, y 1 carga de sesión real; `README.md` conserva dos líneas con etiquetas de credencial. Sustituirlo por un seeder sintético reduce la exposición futura pero **todo sigue en la historia de git**, y el plan debe decirlo en lugar de dar el problema por cerrado. `.env` no está versionado y no se abrió. Ningún artefacto debe reproducir ningún valor.
  - **Deriva de formato preexistente y generalizada**, coherente con la regla de estilo ya aprendida en este proyecto: `pint` está instalado y **nunca se ha ejecutado aquí** (no hay `pint.json` ni línea base formateada), y cuatro bloques tienen el cuerpo pegado a la columna 0 — `CitasController::store:236-274` (precisamente la regla de agenda), `PacientesController::store:84-163`, `AuthController::login:22-37`, `RoleMiddleware::handle:16-27`. Formatear el árbol dentro de este intent convertiría el diff en uno de decenas de archivos. Queda registrada como tal.
  - **Ninguna ruta fuera del foco resultó imprescindible**, así que no hace falta ampliar la instantánea ni repetir el escaneo. Dos dependencias legítimas quedan nombradas a granularidad de directorio y verificadas desde archivos **dentro** del foco: `database/testing/bootstrap-test-db.sh` depende del servicio `db` (`mariadb:10.11`) que declara `docker-compose.yml`, y el layout carga hojas de estilo estáticas de `public/css/` en lugar de la cadena Vite. Si una etapa posterior necesita afirmar algo sobre la capa visual, el contenedor o los assets, requiere una instantánea ampliada y una segunda pasada.
  - **Cota de cobertura de este escaneo, para que no se sobrestime.** Las 4 pruebas sustantivas se leyeron para su estructura, sus fixtures, las tablas que tocan y las rutas que ejercitan, **no aserción por aserción**. De las 37 plantillas Blade solo 2 se leyeron en sus secciones de agenda; las demás se cubrieron por barrido estructural, así que **nada de lo escrito aquí afirma algo sobre maquetación, accesibilidad ni consistencia visual**. Los 7 archivos de `config/` no leídos se inventariaron sin verificar si están intactos respecto al esqueleto de Laravel 12.
