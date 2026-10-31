---
spec: 020-auditoria-base
status: approved   # draft | approved | blocked — aprobado por el usuario el 2026-10-29 (tras /plan 020 --fix A66–A78)
constitution_version: 1.3.0   # versión con la que se evaluó el Constitution Check (/plan 020 --redo)
created: 2026-10-28
---

# Plan · 020 Registro de auditoría base y correlación por petición

Versión 2: `--redo` del plan del 2026-09-28 ([history/plan.v1.md](history/plan.v1.md)) tras `/analyze`
ronda 5 (A44–A65), la spec editada el 2026-10-28 y el diseño aprobado ([design.md](design.md), `c4`).
Se reconstruye sobre `main` en la rama `feature/020-auditoria`. Corregido el 2026-10-29 con
`--fix` de los hallazgos A66–A70, A73, A74 y A76–A78 de `/analyze` ronda 6.

## Enfoque técnico
Un módulo nuevo `app/Auditoria/` expone un único punto de registro, el servicio `Auditoria`. Toma el
actor de la sesión (o de un `Usuario` comprobado que le pasa el servidor), y la IP y el `request_id`
del contexto de la petición, nunca de la entrada. Escribe en una tabla `auditoria` de MariaDB, que es
solo de anexado: la aplicación no tiene rutas de modificación, y dos triggers rechazan siempre
`UPDATE` y rechazan `DELETE` salvo en la purga y solo para eventos de más de 5 años. Un middleware
global asigna el `X-Request-Id` (reutiliza el que llega si es un UUID válido), lo pone en el
`Context` de Laravel, de donde pasa a los logs, y lo devuelve en la respuesta. Las denegaciones se
registran de forma central en `withExceptions`: los 403 y los 404 que vienen de un Gate
(`denyAsNotFound`), sin confundirlos con un 404 real; el controlador que oculta con su propio 404 una
cita ajena que existe lo registra con `denegar()` (A66). El modo crítico, con fallo cerrado, protege
las acciones sobre datos clínicos y la consulta del registro, que el admin hace en `/admin/auditoria`
según el diseño aprobado. La purga mensual corre en un servicio `scheduler` nuevo, en local y en
producción, y el vigilante de la 032 avisa por correo cuando un evento no se puede escribir.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 020 aprobada (2026-10-28) y `design.md` aprobado (2026-10-28, commit 0c1c66f). `/implement` empieza cuando se aprueben este plan y `tasks.md` | manual: `python .ai/bin/aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA y cada TM tienen su test en Trazabilidad. Tres criterios o controles ya se cumplen hoy y se marcan **de caracterización**, con su rotura a comprobar (A55): sin rutas de modificación, un visitante sin sesión no genera evento, y un `request_id` de admin reutilizado no da acceso | test: Trazabilidad (marca «caracterización: comprobado rompiendo <qué>»); manual: nota de cada tarea con el fallo previo o la rotura |
| P3 Autorización en servidor, por rol y recurso | ✅ | `/admin/auditoria` va bajo `auth` + `role:admin`. El admin tiene alcance global sobre el registro, así que no hay recurso que acotar con un Gate; no hay rutas de modificación. Exención: la vista previa del diseño (`/_disenio/020/*`) no lleva `auth` ni `role` y pinta el layout del admin; se acepta porque solo existe con `APP_ENV=local`, usa datos inventados y no lee la base (D19) — aprobado por el usuario el 2026-10-28 (A73) | test: `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` (dentista, asistente y cliente → 403 con su evento) y `test_el_admin_consulta_el_registro`; `DisenioAuditoriaTest::test_la_vista_previa_no_existe_fuera_de_local` y `test_la_vista_previa_no_consulta_la_base` |
| P4 Entrada validada y SQL con bindings | ✅ | Filtros validados con `$request->validate()`: `actor` y `recurso_id` con formato de identificador, `accion` y `recurso_tipo` con `^[a-z_]{1,60}$` (aunque lleguen de un selector), fechas solo `AAAA-MM-DD`, y `page` entero. El texto libre se rechaza y no llega al registro (TM12). `X-Request-Id` solo si es UUID. Consultas con Query Builder; los triggers son SQL estático de la migración, sin entrada del usuario. Blade solo con la salida escapada (sin `{!! !!}`) | test: `ConsultaAuditoriaTest::test_filtros_invalidos_se_rechazan`, `test_un_filtro_con_texto_libre_se_rechaza_y_no_se_registra`; lint: `semgrep scan --config p/php` del CI sobre lo tocado |
| P5 Nada sensible en el repo | ✅ | Sin secretos ni datos nuevos en el repo. Los tests crean eventos sintéticos; la vista previa usa datos inventados de prueba (D19) | lint: `gitleaks detect --no-banner` (CI y `/implement`) |
| P6 Sin vulnerabilidades críticas/altas en producción | ✅ | No añade paquetes. Copia la fuente Roboto que ya verificó la 041 (D18), sin dependencia. Línea base del 2026-10-28: `composer audit` con 0 críticas y 0 altas (1 media, `symfony/mime` CVE-2026-45070, publicada ese día) y `npm audit --audit-level=high` con 0. El `scheduler` usa la misma imagen que `app` | lint: `composer audit --locked --ignore-severity=low --ignore-severity=medium`, `npm audit --audit-level=high` y `trivy fs` del CI; manual: los dos audit en la tarea de liberación (A57) |
| P7 Auditoría de datos sensibles | ✅ | Es la infraestructura que exige P7: almacén solo de anexado, retención de 5 años, lectura solo del admin y auditada. Registra los accesos denegados, también los de los Gates de la 026–028 que responden 404 (A46), y cierra las excepciones P7 de la 025, la 026 y la 028 sobre esos 403 y 404 (A65). Los logins y logouts son de la 021 | test: `DenegacionesAuditadasTest` (Trazabilidad CA4) y `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros` |
| P8 Correlación y logs limpios | ❌ aceptado: si falla la consulta del registro o el borrado de la purga, el reporte por defecto de la `QueryException` deja el SQL con sus bindings (identificadores y fechas) en `laravel.log` del servidor. Es el residuo de OB4.a, que corrige la 023. Se acepta con la condición vigente de la 030: datos ficticios, logs solo en el servidor y la 023 antes de tener datos reales — aprobado por el usuario el 2026-10-28 (A44) | Lo demás cumple. `X-Request-Id` en cada respuesta y en el `Context` de logs y eventos; fuera de HTTP, un `request_id` por ejecución. Los logs nuevos (`auditoria.fallo` y `auditoria.purga`) no llevan datos personales, y un fallo al escribir un evento nunca deja SQL, bindings ni valores del evento en el log (TM13) | test: `IdentificadorPeticionTest` (Trazabilidad CA8–CA10), `RegistroAuditoriaTest::test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log` y `test_el_log_de_fallo_solo_lleva_las_claves_previstas`; manual: revisión de cada `Log::` nuevo en `/review` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | Las pantallas siguen `design.md` (`c4`). La primera tarea añade al sistema los tokens y componentes de «Cambios a incorporar al sistema». Vistas nuevas sin `style=""` ni scripts en línea y CSS sin literales. WCAG 2.1 AA en lo nuevo. La barra lateral del escritorio sigue como deuda DS1 por decisión del usuario (2026-10-28): esta spec solo le añade una entrada con el estilo de las demás | test: `AuditoriaVistasTest::test_las_vistas_nuevas_no_tienen_estilos_en_linea`, `test_el_css_nuevo_solo_usa_tokens` y `test_los_pares_de_color_cumplen_el_contraste` (trait `ReglasCss`); manual: `design/medir.mjs` sobre la vista previa local (D19) y `node docs/design/capturar.mjs --medir` (A49) |
| P10 Las rutas indexables son descubribles | ➖ | La spec no crea ni cambia rutas indexables: `/admin/auditoria` exige sesión y la página 503 es de error. El RNF de descubribilidad se cumple con `noindex` en la página 503 | — (RNF en Trazabilidad: `ErrorAuditoriaTest::test_la_pagina_de_error_no_se_indexa`) |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `database/migrations/<fecha de la implementación>_000000_create_auditoria_table.php` (nuevo) | Tabla `auditoria` y triggers `auditoria_sin_update` y `auditoria_sin_delete`. `down()` los elimina (solo para desarrollo). La fecha del nombre es la del día en que se implementa, posterior a `2026_10_07` (A63) | Medio: permisos para crear triggers con `dentissa_app` (A54) |
| `app/Auditoria/Auditoria.php` (nuevo) | `registrar(...)` y `denegar(...)` (contrato en Contratos); valida formatos y vocabulario; actor y rol del `actor:` o de `Auth::user()` al llamar (D15); genera `request_id` si el `Context` no lo tiene; captura los fallos del almacén sin encadenarlos ni registrar su mensaje y escribe `auditoria.fallo` en el canal `seguridad` (D16) | Medio: es el contrato que usan 014, 021, 022 y 041 |
| `app/Auditoria/AlmacenAuditoria.php` (interfaz) y `AlmacenAuditoriaBaseDatos.php` (nuevos) | Escritura; consulta paginada con filtros (25 por página, D17); valores registrados de `accion` y `recurso_tipo` para los selectores; `purgar(corte): int`, que activa `@auditoria_purga`, borra lo anterior al corte, devuelve el recuento y desactiva el indicador en `finally`. La interfaz permite simular fallos en los tests | Bajo |
| `app/Auditoria/AuditoriaNoDisponible.php` (nuevo) | Excepción del modo crítico, sin excepción previa | Bajo |
| `app/Auditoria/AccesoDenegadoAuditado.php` (nuevo) | `HttpException` (403 o 404) que lanza `denegar()`; el registro central la omite porque ya está registrada | Bajo |
| `app/Auditoria/RegistroCentralDenegaciones.php` (nuevo) | Lo llama el callback `respond` de `withExceptions`: decide si una respuesta es una denegación (D4) y la registra con la forma de Contratos | Medio: toca todas las respuestas 403 y 404 |
| `app/Autorizacion/GestionPersonal.php` | `definirHorario` responde `denyAsNotFound(code: 'recurso_inexistente')` cuando el personal no es dentista: el mismo 404, ahora sin evento (D4) | Bajo: `HorarioDentistaVistasTest` sigue en verde |
| `app/Http/Controllers/ClienteCitasController.php` | `show` y `citaDelPaciente` (reprogramar, GET y PATCH): si la cita no es del paciente, distinguen una cita ajena que existe, que se deniega con `denegar(<nombre de la ruta>, 'citas', $id, 'denegado_404')`, de un id que no existe, que sigue con `abort(404)` sin evento. Las dos respuestas son el mismo 404. `cancelar` no cambia: ya pasa por el Gate con `denyAsNotFound`, que registra el registro central (A66, D4) | Medio: `ReprogramarCitaRolesTest` y `EstadosCitaRolesTest` siguen en verde |
| `app/Providers/AppServiceProvider.php` | Registrar `AlmacenAuditoria` → `AlmacenAuditoriaBaseDatos` y `Auditoria` como singleton | Bajo |
| `app/Listeners/ActualizarActorDelContexto.php` (nuevo) | `handleLogin(Login)` pone `actor_id` en el `Context` y `handleLogout(Logout)` lo quita. Se registra con el descubrimiento automático de `app/Listeners`, como `RegistrarFalloAcceso`, y no en el proveedor (A62) | Bajo |
| `app/Http/Middleware/IdentificadorPeticion.php` (nuevo) | Acepta `X-Request-Id` si es un UUID de 36 caracteres; si no, genera uno. `Context::add('request_id')` y cabecera en la respuesta | Bajo |
| `app/Http/Middleware/ContextoActor.php` (nuevo) | Al final del grupo `web`: `Context::add('actor_id')` si hay sesión y `Context::forget('actor_id')` si no la hay, para que una petición anónima nunca herede el actor de otra | Bajo |
| `bootstrap/app.php` | `prepend` global de `IdentificadorPeticion`; `appendToGroup('web', ContextoActor)`. En `withExceptions`: (a) render de `AuditoriaNoDisponible` → vista 503 genérica aunque `APP_DEBUG=true`, y `dontReport` de esa excepción; (b) callback `respond` → `RegistroCentralDenegaciones` | Medio: manejo global de errores |
| `app/Http/Controllers/AuditoriaController.php` (nuevo) | `index`: valida los filtros, registra `auditoria_consultada` en modo crítico con los filtros validados, pagina 25 eventos del más reciente al más antiguo y pasa a la vista los valores de los selectores | Bajo |
| `app/Http/Controllers/DisenioAuditoriaController.php` (nuevo) y su ruta, solo con `APP_ENV=local` | Vista previa de las pantallas del diseño con datos inventados y sin leer la base, para medirlas con `medir.mjs` sin sesión real: renderiza las vistas reales como si la petición fuera a `/admin/auditoria`, la pantalla `menu` con el menú del teléfono abierto y `?sin-barra=1` con la barra fuera de la medición (D19, A70, A73) | Bajo: no existe fuera de local (test) |
| `routes/web.php` | `GET /admin/auditoria` → `admin.auditoria.index` dentro del grupo admin; `/_disenio/020/{pantalla}` solo en local | Bajo |
| `routes/console.php` | Comando `auditoria:purgar`: calcula el corte (D13) y, dentro de `DB::transaction`, llama a `purgar()` y registra `auditoria_purgada` en modo crítico; si el registro falla, la transacción revierte el borrado. `Schedule::command('auditoria:purgar')->monthlyOn(1, '03:00')->withoutOverlapping()`. Es la única tarea programada: `dentissa:vigilar` sigue en su temporizador hasta su spec propia | Bajo |
| `resources/views/auditoria/index.blade.php` (nuevo) | Consulta según `design.md`: introducción, filtro plegable (`.filtro-citas` y `filtros.js` de la 034, sin cambios) dentro de `.formulario-accesible`, lista `tabla-tarjetas` con estado vacío, error junto al campo y paginación; `@push('styles')` con `auditoria.css` | Bajo |
| `resources/views/errors/auditoria-no-disponible.blade.php` (nuevo) | Página 503 genérica según `design.md`: documento propio, sin el layout autenticado (A52); `noindex`; `request_id` del `Context`; el teléfono de `config('clinica.contacto.telefono')` con su enlace `telefono_enlace` (D23); solo recursos propios (`app.css`, `errores.css` y la fuente Roboto de D18) | Bajo |
| `config/clinica.php` | Clave `contacto` con la forma de la 038 y la 041: `telefono` (`753 162 5220`), `telefono_enlace` (`tel:+527531625220`) y `horario`, con los datos reales de la página de inicio (D23, A77) | Bajo: punto de fusión con la 041 y la 038 |
| `resources/views/layouts/app.blade.php` | Entrada «Auditoría» al final de la sección «Análisis» del menú del admin, tras «Usuarios» y como última entrada de la barra (`design.md`, `c4`), con `.active` y `aria-current="page"` en `admin/auditoria*` (patrón de la 033) | Bajo |
| `docs/design/capturar.mjs` | `--medir` excluye de la comparación con la línea base solo el rectángulo del enlace «Auditoría» en las pantallas del admin, como ya excluye los campos del filtro. Al ser la última entrada, no desplaza nada; el resto de cada pantalla se sigue comparando (D24, A69) | Bajo: es el test del menú de la 033–035 |
| `docs/specs/020-auditoria-base/design/medir.mjs` y `criterios.json` | Cada pantalla gana su `url` en la vista previa (`consulta`, `vacia`, `filtro`, `menu`, `error`). Sobre una URL, la excepción de la barra lateral se comprueba repitiendo la medición con `?sin-barra=1`, como ya se hace con una copia en las carpetas. Umbrales, KPIs y puertas no cambian (D19, A70) | Bajo |
| `public/css/app.css` | Tokens nuevos de `design.md` en `:root` (`--leading-publico`, `--text-acceso-titulo`, `--text-acceso`, `--acceso-form-max`, `--sombra-tarjeta-alta`) y el componente `.paginacion` | Bajo |
| `public/css/auditoria.css` (nuevo) | `.filtro-auditoria`, `.tabla-auditoria`, error de campo, aviso del filtro y estado vacío de la consulta (`design.md` → Componentes) | Bajo |
| `public/css/errores.css` (nuevo) y `public/fonts/roboto/` (nuevo, D18) | Carcasa del error genérico y `@font-face` de la Roboto propia | Bajo |
| `app/Avisos/Vigilante.php`, `config/avisos.php`, `app/Mail/AvisoProduccion.php`, `resources/views/correos/aviso.blade.php` | Aviso nuevo `auditoria`: el vigilante distingue las líneas del canal `seguridad` por su mensaje (`acceso_fallido` o `auditoria.fallo`), y con al menos un `auditoria.fallo` nuevo envía un correo con el recuento y la ventana, sin datos del evento. Mismo silencio de 30 minutos por tipo (CA14, D16) | Medio: cambia cómo el vigilante lee un canal que ya usa |
| `docker-compose.yml` (**ruta protegida**) | Servicio `scheduler` con el mismo `build:` que `app` (misma imagen, de la caché), `php artisan schedule:work`, `restart: unless-stopped` y los mismos montajes que `app`, `local.ini` incluido (decisión del usuario, 2026-09-28; A67) | Bajo; aprobación explícita en la tarea |
| `docker-compose.prod.yml` | Servicio `scheduler`: misma imagen que `app` (`image:` local explícito en los dos, sin `build:` en el `scheduler`, `pull_policy: never`), `restart: unless-stopped`, `user: www-data`, `php artisan schedule:work`, solo `app.env`, el volumen `storage` y el mismo `local.ini` que `app`, red `produccion`, sin puertos y `depends_on` de `db` sana (A45, A67, D2) | Medio: servicio nuevo en producción |
| `deploy/desplegar.sh`, `deploy/volver.sh`, `deploy/restaurar.sh`, `deploy/verificar.sh`, `deploy/aplicar-entorno.sh` | Los scripts nombran el `scheduler` solo si el Compose de la versión de destino lo tiene (`config --services`), para valer hacia versiones con y sin él (D22, A68). `desplegar.sh` y `volver.sh` lo levantan con `app` y `web`, con `--remove-orphans`. `volver.sh` lo para **antes** del checkout, y `--sin-levantar` deja parado todo lo que escribe en la base y quita el `scheduler` huérfano (`up -d --remove-orphans db`). `restaurar.sh` para `app`, `web` y, si existe, el `scheduler` antes de cargar la copia, y los levanta al final. `aplicar-entorno.sh app` recrea también el `scheduler`, que lee el mismo `app.env` (A74). `verificar.sh` comprueba que está en marcha y que `schedule:list` tiene `auditoria:purgar` | Medio: scripts de producción (tests de la 030–032) |
| `docs/deployment.md`, `docs/observability.md`, `docs/architecture.md`, `docs/roadmap.md`, `docs/security.md`, `deploy/README.md`, `AGENTS.md` (**ruta protegida**) | Documentación al implementar (Impacto en arquitectura; A53, A65) | Bajo |
| `docs/design/system.md` y `system.html` | Lo de «Cambios a incorporar al sistema» de `design.md`, en la primera tarea | Bajo |
| `tests/Feature/Auditoria/*Test.php` (nuevos), `tests/Support/AlmacenAuditoriaQueFalla.php` y `tests/Support/CapturaLogs.php` (nuevos) | Tests de la Trazabilidad; el doble decora un almacén real y solo al escribir lanza una `QueryException` real con SQL y bindings de prueba; `CapturaLogs` es un `TestHandler` de Monolog | — |
| `tests/Feature/MovilPacienteTest.php`, `tests/Feature/ConfiguracionProduccionTest.php`, `tests/Feature/VigilanteTest.php`, `tests/Feature/AvisoProduccionTest.php` | Se actualizan sin debilitarse: «Auditoría» en `SECCIONES['admin']`; el aviso `auditoria` y las líneas del canal por mensaje; en el Compose, la versión exacta se sigue exigiendo a toda imagen que se descarga (solo queda fuera la que construye el propio Compose para `app`, que el `scheduler` reutiliza sin descargarla) y el `local.ini` se comprueba por servicio PHP (`app` y `scheduler`: una vez cada uno, el mismo origen y un archivo); en los scripts de la 032 (`stop app web`, `up -d app web`, `--sin-levantar`), el contrato de D22 con el `scheduler` condicional y `--remove-orphans`. `ReprogramarCitaRolesTest` y `EstadosCitaRolesTest` no cambian (A66, A67, A68) | Medio: tests de la 030–033 |
| `phpunit.xml` | Excluir el grupo `rnf` de la suite por defecto (`php artisan test --group=rnf` lo ejecuta) | Bajo |

La base de pruebas se migra con el paso que ya documenta `docs/deployment.md` y que el CI ya ejecuta:
`bootstrap-test-db.sh` no cambia (A59; se retira la antigua D8).

## Contratos y datos
**Tabla `auditoria`** (migración nueva; solo expansión):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint autoincrement | orden de inserción (D6) |
| `ocurrido_en` | datetime(6) | índice; UTC (`app.timezone`) |
| `actor_id` | char(36) null | sin FK: el evento sobrevive al usuario |
| `actor_rol` | varchar(20) null | rol en el momento del evento |
| `identificador_intentado` | varchar(255) null | solo sin sesión y sin `actor:` (lo usará la 021) |
| `accion` | varchar(60) | índice; `^[a-z_]{1,60}$` |
| `recurso_tipo` | varchar(60) null | índice compuesto con `recurso_id`; `^[a-z_]{1,60}$` |
| `recurso_id` | varchar(64) null | `^[A-Za-z0-9_-]{1,64}$` |
| `resultado` | varchar(30) | vocabulario cerrado: `exito`, `denegado` (403), `denegado_404` (denegación mostrada como 404), `error` (D12) |
| `campos` | json null | nombres de campo cambiados, nunca valores |
| `detalle` | json null | mapa de enteros, booleanos o cadenas `^[A-Za-z0-9_.:-]{1,64}$`: filtros validados, recuento de purga, acción solicitada; nunca texto libre |
| `ip` | varchar(45) null | |
| `request_id` | char(36) NOT NULL | índice |

Triggers (SQL estático):
```sql
CREATE TRIGGER auditoria_sin_update BEFORE UPDATE ON auditoria FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: solo anexado';
CREATE TRIGGER auditoria_sin_delete BEFORE DELETE ON auditoria FOR EACH ROW
  IF COALESCE(@auditoria_purga, 0) <> 1 OR OLD.ocurrido_en >= UTC_TIMESTAMP() - INTERVAL 5 YEAR THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: borrado no permitido';
  END IF;
```

**API de registro** (contrato para 014, 021, 022 y 041):
```php
// App\Auditoria\Auditoria
public function registrar(
    string $accion,              // ^[a-z_]{1,60}$
    string $resultado,           // exito | denegado | denegado_404 | error
    ?string $recursoTipo = null, // ^[a-z_]{1,60}$
    ?string $recursoId = null,   // ^[A-Za-z0-9_-]{1,64}$
    array $campos = [],          // list<string> de nombres de campo (^[a-z_]+$)
    array $detalle = [],         // array<string, int|bool|string>; cadenas ^[A-Za-z0-9_.:-]{1,64}$
    bool $critico = false,       // true → lanza AuditoriaNoDisponible si no puede escribir
    ?string $identificadorIntentado = null,
    ?Usuario $actor = null,      // cuenta comprobada por otra credencial (D15); null → Auth::user()
): void;
/** Registra `acceso_denegado` (modo no crítico) y lanza AccesoDenegadoAuditado (403, o 404 con denegado_404). */
public function denegar(string $accionSolicitada, ?string $recursoTipo, ?string $recursoId, string $resultado = 'denegado'): never;
```
- **Cómo se obtiene:** por inyección (constructor o método) o `app(Auditoria::class)`; no hay
  fachada. En otros planes, `Auditoria::registrar(...)` denota una llamada al servicio inyectado.
- **Actor (CA1, CA10):**
  - sin `actor:`, el actor y su rol salen de `Auth::user()` en el momento de la llamada: un evento
    registrado tras autenticarse en la misma petición lleva el nuevo actor;
  - con `actor:` (D15), salen de ese `Usuario`, aunque haya otra sesión. Solo se admite un `Usuario`
    guardado; uno sin guardar lanza `InvalidArgumentException` sin registrar nada. El tipo impide
    pasar un identificador;
  - `actor:` e `identificadorIntentado` a la vez son un error de programación:
    `InvalidArgumentException`, sin registrar nada (A64);
  - sin sesión y sin `actor:`, se guarda el identificador intentado si se pasa;
  - el `Context` de los logs no cambia con `actor:`: los registros técnicos llevan el usuario de la
    sesión, como pide CA10.
- **Obligación de quien llama con `actor:`** (A51; residuo de TM10): la 020 no puede comprobar que
  ese `Usuario` se verificó con una credencial. Quien lo pasa responde de haberlo comprobado (en la
  041, el usuario que entrega el broker tras validar el token), y cada spec que use `actor:` lleva un
  test que envía el identificador de otra cuenta en la petición y comprueba que el evento lleva la
  cuenta verificada. `/review` revisa cada llamada con `actor:`.
- **IP y `request_id`:** la IP sale de la petición (`REMOTE_ADDR`, que nginx fija desde
  `CF-Connecting-IP`, spec 030); null fuera de HTTP. El `request_id` sale del `Context`; si no hay
  (consola, colas), `Auditoria` genera un UUID, lo añade al `Context` y lo reutiliza en esa ejecución.
- **Valores inválidos** (formato o vocabulario) → `InvalidArgumentException`: es un error de
  programación, no una entrada del usuario.
- **Fallos del almacén:** `Auditoria` captura cualquier excepción del almacén y nunca la encadena ni
  registra su mensaje (una `QueryException` lleva SQL y bindings). Escribe en el canal `seguridad`
  la línea `auditoria.fallo` (nivel `error`) con exactamente las claves `accion`, `critico` y `error`
  (clase corta y SQLSTATE si lo hay); `request_id` y `actor_id` llegan por el `Context` (en `extra`).
  En modo no crítico la acción continúa; en modo crítico lanza `AuditoriaNoDisponible` sin
  excepción previa.
- **Garantía del modo crítico:** en una lectura, el evento se registra antes de mostrar el recurso.
  En una escritura, el llamador hace el cambio y registra el evento dentro de la misma transacción;
  si el registro falla, `AuditoriaNoDisponible` revierte el cambio. La purga y la 041 (D5) siguen
  esta regla.

**Forma del evento de denegación** (la usan esta spec y la 014):

| Campo | Valor |
|---|---|
| `accion` | `acceso_denegado` |
| `resultado` | `denegado` (403) o `denegado_404` (un Gate que responde «no encontrado», o `denegar()` con ese resultado) |
| `detalle.accion_solicitada` | la que pase `denegar()`; en el registro central, el nombre de la ruta (`admin.auditoria.index`) o `ruta_sin_nombre` |
| `recurso_tipo` | el que pase `denegar()`; en el registro central, el segundo segmento del nombre de la ruta (`<rol>.<recurso>[.<acción>]` → `auditoria`), o null |
| `recurso_id` | el que pase `denegar()`; en el registro central, el parámetro de ruta `id` (o el primer parámetro de ruta) solo si tiene formato de identificador; si no, null |

**Qué es una denegación en el registro central** (D4; comprobado leyendo
`Illuminate\Foundation\Exceptions\Handler::render` y `prepareException` de Laravel 12, 2026-10-28):
el callback `respond` recibe la excepción ya preparada. Se registra si hay un usuario autenticado, la
excepción no es `AccesoDenegadoAuditado` y:
- la respuesta es 403 (`abort(403)`, `RoleMiddleware`, o un Gate denegado sin estado) → `denegado`;
- la respuesta es 404 y la excepción previa es una `AuthorizationException` (un Gate con
  `denyAsNotFound`, como el alcance de las citas de la 026–028) → `denegado_404`, salvo que su código
  sea `recurso_inexistente`.

Un Gate que responde 404 porque el recurso no existe, y no para ocultarlo, lo declara con
`Response::denyAsNotFound(code: 'recurso_inexistente')`. Hoy es el caso de `definir-horario` (027):
para quien no es dentista, el horario no existe. El estado sigue siendo 404 y su test sigue en verde.

Un 404 real (`abort(404)` porque el recurso no existe, ruta inexistente o `ModelNotFoundException`)
no tiene esa excepción previa y no genera evento.

Un controlador que oculta un recurso ajeno con su propio 404, antes de llegar a un Gate, no lo hace
con `abort(404)`: llama a `denegar(..., 'denegado_404')`, que deja el evento y responde el mismo 404
(A66). Hoy es el caso de `ClienteCitasController`: el detalle y reprogramar (GET y PATCH) filtran
la cita por `paciente_id`. Si no la encuentran, comprueban si el id existe:

| Caso | Respuesta | Evento |
|---|---|---|
| Cita de otro paciente que existe (`show`, `reprogramarForm`, `reprogramar`) | 404 | `acceso_denegado`, `denegado_404`, `detalle.accion_solicitada` = nombre de la ruta (`cliente.citas.show`, `cliente.citas.reprogramar`, `cliente.citas.reprogramar.update`), `recurso_tipo` = `citas`, `recurso_id` = el id |
| Cita de otro paciente que existe (`cancelar`) | 404 (Gate `cambiar-estado-cita` con `denyAsNotFound`) | el mismo, desde el registro central (`cliente.citas.cancelar`) |
| Id que no existe, en las cuatro rutas | 404 | ninguno |

Las dos respuestas 404 son iguales (estado y cuerpo), así que el paciente no puede saber si la cita
existe. Sin duplicados: solo `denegar()` y el registro
central registran, y el central omite exactamente la excepción de `denegar()`. Una comprobación que
no rechaza (`@can`, `Gate::allows`) no registra nada.

**Rutas:**
| Ruta | Rol | Respuesta |
|---|---|---|
| `GET /admin/auditoria?actor=&accion=&recurso_tipo=&recurso_id=&desde=&hasta=&page=` | admin | 200 con 25 eventos del más reciente al más antiguo; `actor` y `recurso_id` con formato de identificador, `accion` y `recurso_tipo` con `^[a-z_]{1,60}$`, `desde` y `hasta` solo `AAAA-MM-DD` con `hasta >= desde` (el rango incluye el día `hasta` completo), `page` entero ≥ 1; filtros inválidos → vuelve con los errores junto a su campo, sin 500 y sin registrarse; 503 genérico si no puede auditar la consulta |
| `GET /admin/auditoria` | dentista, asistente, cliente | 403; evento `acceso_denegado` con `detalle.accion_solicitada` = `admin.auditoria.index` y `recurso_tipo` = `auditoria` |
| `GET /_disenio/020/{consulta,vacia,filtro,menu,error}[?sin-barra=1]` | solo con `APP_ENV=local` | vista previa con datos inventados, sin consultas a la base y con «Auditoría» marcada en el menú; `menu` es la consulta con el menú del teléfono abierto; `?sin-barra=1`, la barra lateral con `aria-hidden="true"` (D19); fuera de local, 404 (no existe) |
| Todas las respuestas | — | cabecera `X-Request-Id` |

**Pantallas** (`design.md`, `c4`; criterios en `design/criterios.json`):

| Pantalla y estado | Qué ve el admin | Accesibilidad medible |
|---|---|---|
| Consulta con eventos | introducción; filtro (actor con su ayuda, acción y tipo de recurso como selectores con «Todas» y los valores registrados, identificador de recurso, desde y hasta, «Filtrar» y «Limpiar»); lista «Eventos» con recuento y página, y por evento hora y fecha, acción y su detalle, actor (rol e identificador), recurso (tipo e identificador), resultado, origen y petición; paginación al pie | K1, K2, K5, K8–K10; G-contraste fuera de la barra lateral (DS1), G-toque ≥ 44 px, G-foco y G-desborde a 320 px |
| Consulta vacía | «No hay eventos con estos filtros» y «Quitar los filtros»; «Filtros, n aplicados» en el teléfono | K3; `role="status"` |
| Filtro no válido | aviso «Revisa el filtro marcado: no se buscó nada.» (`role="alert"`) y el error bajo su campo (`aria-invalid`, `aria-describedby`); sin lista | K4 |
| Menú | «Auditoría» en «Análisis», marcada como la sección actual, también en el menú del teléfono | K7 y K7b |
| Error genérico (503) | «No se realizó la acción», una frase sin detalles internos, el identificador de la petición para soporte, «Volver al inicio» y el teléfono de la consulta (`config('clinica.contacto')`, D23); igual con y sin sesión | K6; `noindex` |

El detalle de cada evento se muestra con textos fijos por acción: «Solicitó <acción solicitada>»
(`acceso_denegado`), «Filtros: <nombres de los filtros>» o «Sin filtros» (`auditoria_consultada`), y
«Purgó <n> eventos hasta <fecha>» (`auditoria_purgada`).

**Qué ve cada rol**

| Pantalla | Admin | Dentista | Asistente | Cliente | Test |
|---|---|---|---|---|---|
| Menú: entrada «Auditoría» | sí, en «Análisis» | no | no | no | `ConsultaAuditoriaTest::test_el_menu_de_auditoria_solo_para_admin` |
| `/admin/auditoria` | la consulta | 403 | 403 | 403 | `test_un_no_admin_no_consulta_el_registro` |
| Error de auditoría no disponible | página 503 genérica | ídem | ídem | ídem, también sin sesión | `ErrorAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico`, `test_un_fallo_critico_sin_sesion_muestra_el_503` |

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, `dentissa_test` con migraciones):**
  `RegistroAuditoriaTest`, `InmutabilidadAuditoriaTest`, `IdentificadorPeticionTest`,
  `DenegacionesAuditadasTest`, `ConsultaAuditoriaTest`, `ErrorAuditoriaTest`, `AuditoriaVistasTest`,
  `DisenioAuditoriaTest`, más los cambios en `VigilanteTest`, `AvisoProduccionTest`,
  `ConfiguracionProduccionTest` y `MovilPacienteTest`.
- **Fallos simulados:** `AlmacenAuditoriaQueFalla` sustituye al almacén en el contenedor. Decora un
  almacén real: delega la consulta y la purga, y solo al escribir lanza una `QueryException` real
  con SQL y bindings de prueba (IP e identificador ficticios), para comprobar que no llegan al log.
  En la purga fallida, el `DELETE` real se ejecuta y solo falla el registro.
- **Denegaciones con rutas reales** (A46, A66): un paciente con la cita de otro, en las cuatro rutas
  de `/cliente/citas/{id}` (detalle, reprogramar GET y PATCH, cancelar) → 404 y un `denegado_404`
  con el nombre de esa ruta; el mismo paciente con un id que no existe en esas rutas → el mismo 404
  sin evento; un dentista fuera de alcance y entrar al área de otro rol → `denegado`; el horario
  de quien no es dentista (404 de recurso inexistente de la 027) → sin evento; `abort(404)` de un
  recurso que no existe y una ruta inexistente → sin evento. Para `denegar()`, la no duplicación y
  un 403 posterior a un `@can` denegado, rutas y Gate de prueba en `setUp` (grupo `web` + `auth`).
- **Logs:** `CapturaLogs` comprueba que los registros técnicos llevan `request_id` y `actor_id` en
  sus metadatos de contexto, y que `auditoria.fallo` va al canal `seguridad` con sus claves fijas.
- **Triggers:** contra MariaDB real (la base de pruebas) y con la conexión de la propia
  aplicación, no con dobles. Además, una comprobación en el Compose de producción ensayado en local
  (`dentissa-prueba`): la migración crea los triggers con `dentissa_app`, sin root, y ese usuario
  recibe el rechazo de `UPDATE` y `DELETE` (A54; verificación manual).
- **Purga** (A56; notas aceptadas en la ronda 4): el corte se calcula sin desbordar años y
  truncado al segundo (con `Carbon::setTestNow('2028-02-29 00:00:00.500')`, el corte es
  `2023-02-28 00:00:00`), y el test del corte fija en su sesión `SET time_zone = '-06:00'` para que
  falle contra un trigger que use `NOW()`.
- **Interfaz (P9):** tests sobre el HTML y el CSS con el trait `ReglasCss` (sin `style=""` en las
  vistas nuevas, sin literales en el CSS nuevo, cada `var(--x)` con su token en `system.md` y pares
  de color con su contraste). Medición con `design/medir.mjs` sobre la vista previa local (D19),
  con Docker Playwright: se acepta la salida con código 0, en la que solo las pantallas con
  `barra_lateral` pueden pasar con la nota «solo en la barra lateral» (G-contraste a 900 px o más,
  deuda DS1; G-aparece y G-reducido por debajo de 900 px, menú cerrado). K7 y K7b se miden sobre el
  menú real: `menu` con el menú del teléfono abierto y `menu-escritorio` sobre la consulta (A70).
  Además, `node docs/design/capturar.mjs --medir` porque se toca el layout (A49): sale con 0, con
  «Auditoría» fuera de la comparación con la línea base (D24, A69).
- **Producción:** `ConfiguracionProduccionTest` lee el Compose y los scripts, como en la 030–032.
  `php artisan schedule:list` debe dar solo `auditoria:purgar`, mensual. En el Compose (local y
  producción), el `scheduler` se reinicia solo y monta el mismo `local.ini` que `app` (A67). En los
  scripts, el contrato de D22: el `scheduler` se nombra solo tras comprobar que el Compose de destino
  lo tiene, `volver.sh` lo para antes del checkout, `--sin-levantar` y `restaurar.sh` paran todo lo
  que escribe antes de cargar y no dejan un `scheduler` huérfano, con un caso de destino sin
  `scheduler` (A68); `aplicar-entorno.sh app` recrea también el `scheduler` (A74). Verificación
  manual en el ensayo `dentissa-prueba`: volver con copia a la versión instalada hoy (sin
  `scheduler`) y comprobar con `docker compose ps -a` que no queda ninguno.
- **Rendimiento (RNF):** `AuditoriaRendimientoTest`, en el grupo `rnf`, fuera de la suite por
  defecto (`php artisan test --group=rnf`). Los 100.000 eventos sintéticos se insertan dentro de la
  transacción del test, que se revierte al final. Mide el p95 de 200 llamadas a `registrar`
  (< 50 ms) y el de 20 consultas a `/admin/auditoria` con filtros distintos, incluidos los
  selectores (< 2 s). Las mediciones se anotan en la tarea.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: modificar o borrar eventos, uno o muchos, desde la app o con sus credenciales | A09:2025 | tabla `auditoria` | sin rutas de escritura; triggers que rechazan `UPDATE` y `DELETE` fila a fila. Vaciar la tabla, eliminar los triggers o cambiar su estructura con privilegios totales queda fuera (limitación aceptada en la spec) | `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_un_borrado_masivo_se_rechaza` |
| TM2 | Tampering: abusar de la purga para borrar eventos recientes | A09:2025 | trigger `auditoria_sin_delete` | solo borra con el indicador de purga **y** más de 5 años, medidos con `UTC_TIMESTAMP()` | `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `test_la_purga_respeta_el_corte_al_segundo` |
| TM3 | Injection: `X-Request-Id` manipulado (logs, cabeceras) | A05:2025 | `IdentificadorPeticion` | solo UUID de 36 caracteres; si no, uno generado | `IdentificadorPeticionTest::test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye`, `test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria` |
| TM4 | Information disclosure: personal no admin lee el registro | A01:2025 | `/admin/auditoria` | `role:admin` | `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` |
| TM5 | Information disclosure: datos personales o de salud dentro de un evento | A09:2025 | `Auditoria::registrar` | `campos` solo nombres; `detalle` solo enteros, booleanos o cadenas con formato de identificador o fecha; revisión de cada llamada | `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares`, `test_el_detalle_rechaza_texto_libre` |
| TM6 | Repudiation: acceder a datos clínicos sin dejar rastro provocando fallos del registro | A09:2025 | modo crítico; vigilante | `AuditoriaNoDisponible` detiene la acción y, en escrituras, revierte el cambio; en producción, aviso por correo (CA14) | `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_critico_una_escritura_fallida_no_deja_cambios`, `VigilanteTest::test_un_fallo_de_auditoria_avisa_por_correo` |
| TM7 | Information disclosure: la página de error revela detalles internos | A10:2025 | `withExceptions`, vista 503 | vista genérica aunque `APP_DEBUG=true`, sin layout autenticado ni recursos externos | `ErrorAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico`, `test_un_fallo_critico_sin_sesion_muestra_el_503` |
| TM8 | DoS: filtros costosos o malformados | A06:2025 | `AuditoriaController@index` | validación (fechas solo `AAAA-MM-DD`, `page` entero), índices, paginación fija de 25 | `test_filtros_invalidos_se_rechazan`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`; RNF `AuditoriaRendimientoTest` |
| TM9 | Repudiation: purga sin rastro | A09:2025 | `auditoria:purgar` | evento `auditoria_purgada` con recuento y fecha de corte, en la misma transacción que el borrado y en modo crítico | `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada`, `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` (el `DELETE` real se ejecuta, el comando termina con error y el recuento de eventos antiguos no cambia; debe fallar contra una purga que registre en modo no crítico) |
| TM10 | Spoofing: un evento atribuido a otro actor | A09:2025 | `Auditoria` | actor de `Auth::user()` al registrar o, con `actor:`, de un `Usuario` guardado que el servidor comprobó con otra credencial (D15); nunca un identificador de la petición; `actor_id` y `actor_rol` del mismo usuario. Residuo: la 020 no puede comprobar que quien pasa `actor:` lo verificó; es obligación de quien llama, con su test en cada spec (A51) | `RegistroAuditoriaTest::test_el_actor_es_el_usuario_autenticado`, `test_un_evento_tras_autenticarse_en_la_misma_peticion_lleva_ese_actor`, `test_el_actor_explicito_guarda_su_id_y_su_rol`, `test_con_actor_explicito_el_evento_lleva_esa_cuenta_y_los_logs_la_sesion`, `test_un_actor_sin_guardar_se_rechaza_sin_registrar`, `test_actor_e_identificador_intentado_a_la_vez_se_rechazan` |
| TM11 | Spoofing / Repudiation: reenviar un `X-Request-Id` válido para confundir la trazabilidad o heredar el actor o el acceso de otra petición | A09:2025 | `IdentificadorPeticion`, `Auditoria` | el `request_id` solo correlaciona: el actor sale de la sesión o del `actor:` comprobado (D15), el acceso de `auth` y `role`, y cada evento tiene su propio `id`, fecha e IP | `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor`, `test_un_identificador_de_admin_reutilizado_no_da_acceso` |
| TM12 | Information disclosure: texto libre del usuario (filtros, segmentos de URL) guardado en el registro | A09:2025 | `AuditoriaController`, registro central, `Auditoria` | solo valores con formato de identificador o fecha llegan a `recurso_id` y `detalle`; los filtros inválidos se rechazan | `ConsultaAuditoriaTest::test_un_filtro_con_texto_libre_se_rechaza_y_no_se_registra`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`, `DenegacionesAuditadasTest::test_un_id_de_ruta_con_formato_invalido_no_se_guarda`, `RegistroAuditoriaTest::test_el_detalle_rechaza_texto_libre` |
| TM13 | Information disclosure: SQL, bindings o valores del evento en el log cuando falla una escritura de auditoría | A09:2025 | `Auditoria` | la excepción del almacén nunca se encadena ni se registra; `auditoria.fallo` con claves fijas | `RegistroAuditoriaTest::test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`, `test_el_log_de_fallo_solo_lleva_las_claves_previstas` |
| TM14 | Elevation / Information disclosure: el `scheduler` de producción con más permisos o secretos de los que necesita | A02:2025 | `docker-compose.prod.yml` | misma imagen que `app`, `www-data`, solo `app.env`, sin puertos, red `produccion`; la misma configuración de PHP que `app` (`local.ini`: sin `expose_php` ni argumentos en las trazas, A67); solo programa la purga | `ConfiguracionProduccionTest::test_el_scheduler_de_produccion_usa_la_imagen_de_app_y_solo_app_env`, `test_el_scheduler_se_reinicia_solo_y_monta_el_local_ini_de_app`, `test_produccion_no_publica_puertos_ni_tiene_phpmyadmin` (ya existe; cubre el servicio nuevo), `test_la_programacion_solo_tiene_la_purga` |
| TM15 | Information disclosure: la vista previa del diseño expuesta en producción | A05:2025 | `/_disenio/020/*` | la ruta solo se registra con `APP_ENV=local`; usa datos inventados, no la base; exención de P3 aceptada (A73) | `DisenioAuditoriaTest::test_la_vista_previa_no_existe_fuera_de_local`, `test_la_vista_previa_no_consulta_la_base` |
| TM16 | Information disclosure: el aviso por correo revela datos del evento o de la persona | A09:2025 | vigilante, `AvisoProduccion` | la línea `auditoria.fallo` no lleva datos del evento (claves fijas) y el correo solo lleva el tipo, el recuento y las horas | `VigilanteTest::test_el_aviso_de_auditoria_no_lleva_datos_del_evento`, `AvisoProduccionTest::test_el_aviso_de_auditoria_solo_lleva_tipo_recuento_y_horas` |
| TM17 | DoS: un usuario autenticado provoca 403 y 404 en masa y la tabla crece sin límite | A06:2025 | registro central, tabla `auditoria` | solo generan evento los usuarios autenticados; un visitante sin sesión no escribe nada. Residuo aceptado: no hay límite por usuario, porque el volumen es académico y la tabla se vigila (Rollout → Métricas) — decisión del usuario, 2026-10-28 (A78) | `DenegacionesAuditadasTest::test_un_visitante_sin_sesion_no_genera_evento` (el límite que sí existe); el residuo, sin test |
| TM18 | Spoofing / Tampering: insertar eventos falsos con las credenciales de la aplicación | A09:2025 | tabla `auditoria` | la aplicación solo inserta desde `Auditoria`, que valida formato y vocabulario y toma el actor del servidor. Residuo aceptado: un `INSERT` directo con las credenciales de la app no se puede impedir, el mismo límite que la limitación aceptada de CA2 — decisión del usuario, 2026-10-28 (A78) | `RegistroAuditoriaTest::test_un_resultado_fuera_del_vocabulario_se_rechaza`, `test_el_actor_es_el_usuario_autenticado` (lo que controla la app); el residuo, sin test |

Casos de abuso de la spec → amenazas: CA2 → TM1, TM2, TM18; CA7 → TM4; CA9 → TM3; CA11 → TM6, TM7, TM13;
CA13 → TM11; fallos provocados (CA14) → TM6, TM16; identificador de otra cuenta (CA1) → TM10.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: evento con actor, acción, recurso, resultado, fecha, IP y request_id; actor de la sesión, de la cuenta comprobada o, sin ninguno, el identificador intentado; nunca de un identificador enviado | migración, `Auditoria`, `AlmacenAuditoriaBaseDatos` | Feature `RegistroAuditoriaTest::test_un_evento_guarda_todos_los_campos`, `test_sin_sesion_se_guarda_el_identificador_intentado`, `test_el_actor_explicito_guarda_su_id_y_su_rol`, `test_actor_e_identificador_intentado_a_la_vez_se_rechazan`, `test_un_actor_sin_guardar_se_rechaza_sin_registrar`, `test_un_resultado_fuera_del_vocabulario_se_rechaza`, `test_fuera_de_http_se_genera_un_request_id`. El identificador de otra cuenta enviado en la petición lo prueba cada spec que use `actor:` (A51) |
| CA2 (abuso): ningún evento se modifica ni se borra, uno o muchos, tampoco con las credenciales de la app, salvo la purga. Fuera del criterio: vaciar la tabla, eliminar los triggers o cambiar su estructura con privilegios totales, sin test | triggers, sin rutas de escritura | Feature `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_un_borrado_masivo_se_rechaza` (con la conexión de la app), `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `ConsultaAuditoriaTest::test_no_existen_rutas_de_modificacion` (caracterización: comprobado rompiendo `routes/web.php` con una ruta `DELETE` de prueba). verificación manual: triggers con `dentissa_app` en el Compose de producción ensayado en local (A54) |
| CA3: sin datos personales ni de salud, salvo IP e identificador intentado; campos sin valores | validación de `campos`, `detalle` y `recurso_id` | Feature `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares`, `test_el_detalle_rechaza_texto_libre`, `DenegacionesAuditadasTest::test_un_id_de_ruta_con_formato_invalido_no_se_guarda` |
| CA4: denegaciones de usuarios autenticados registradas con la acción y el recurso solicitados, también las que responden «no encontrado»; un recurso que no existe no genera evento | `RegistroCentralDenegaciones` (`respond`), `denegar()`, `AccesoDenegadoAuditado`, `ClienteCitasController` | Feature `DenegacionesAuditadasTest::test_entrar_al_area_de_otro_rol_queda_registrado` (aserta `accion`, `detalle.accion_solicitada`, `recurso_tipo` y `resultado` exactos), `test_una_cita_ajena_denegada_como_no_encontrada_queda_registrada` (un paciente con la cita de otro en las cuatro rutas reales: detalle, reprogramar GET y PATCH, y cancelar → 404 y un `denegado_404` con el nombre de la ruta, `citas` y el id; A66), `test_una_cita_que_no_existe_no_genera_evento` (las mismas cuatro rutas con un id inexistente → 404 sin evento), `test_la_cita_ajena_y_la_inexistente_responden_igual` (mismo estado y cuerpo); `ReprogramarCitaRolesTest` y `EstadosCitaRolesTest` en verde, `test_un_dentista_fuera_de_alcance_queda_registrado` (Gate `gestionar-cita` de la 026 → `denegado`), `test_el_horario_de_quien_no_es_dentista_no_genera_evento` (Gate `definir-horario` de la 027: 404 de recurso inexistente), `test_un_recurso_que_no_existe_no_genera_evento` (`abort(404)`), `test_una_ruta_inexistente_no_genera_evento`, `test_un_abort_403_de_controlador_queda_registrado` (aserta `recurso_id`), `test_una_denegacion_con_denegar_se_registra_una_sola_vez`, `test_una_comprobacion_denegada_que_no_rechaza_no_impide_registrar_el_403_posterior`, `test_un_visitante_sin_sesion_no_genera_evento` (caracterización: comprobado quitando la condición de usuario autenticado del registro central) |
| CA5: la purga elimina solo lo anterior a 5 años, también en producción | `auditoria:purgar`, trigger, `scheduler` local y de producción | Feature `InmutabilidadAuditoriaTest::test_la_purga_elimina_solo_eventos_de_mas_de_5_anios`, `test_la_purga_respeta_el_corte_al_segundo` (`time_zone '-06:00'`; corte − 1 s se purga y corte + 1 s se conserva), `test_el_corte_no_desborda_los_anios_bisiestos` (`2028-02-29 00:00:00.500` → `2023-02-28 00:00:00`), `test_la_purga_esta_programada_mensualmente`, `ConfiguracionProduccionTest::test_el_scheduler_de_produccion_usa_la_imagen_de_app_y_solo_app_env`, `test_el_scheduler_se_reinicia_solo_y_monta_el_local_ini_de_app` (local y producción; 030 CA9 y CA10, A67), `test_las_imagenes_de_produccion_tienen_version_exacta_e_iguales_a_local` y `test_el_compose_de_produccion_monta_un_local_ini_que_existe` (ajustados por servicio, A67), `test_los_scripts_levantan_y_paran_el_scheduler_con_app`, `test_volver_con_copia_no_depende_de_la_version_de_destino` (con destino con y sin `scheduler`, A68), `test_restaurar_para_todo_lo_que_escribe_antes_de_cargar` (A68), `test_aplicar_entorno_recrea_el_servicio_y_termina_verificando` (`app` recrea también el `scheduler`, A74); verificación manual: `deploy/verificar.sh` da OK en «scheduler en marcha», «`auditoria:purgar` programada» y «Entorno aplicado» tras el despliegue, y en el ensayo `dentissa-prueba` la vuelta con copia a una versión sin `scheduler` no deja ninguno (`docker compose ps -a`) |
| CA6: consulta con filtros, paginada del más reciente al más antiguo | `AuditoriaController`, vista, CSS, layout | Feature `ConsultaAuditoriaTest::test_el_admin_consulta_el_registro`, `test_el_admin_filtra_por_actor_accion_recurso_y_fechas`, `test_los_selectores_ofrecen_los_valores_registrados`, `test_el_filtro_por_actor_solo_admite_identificadores`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`, `test_el_rango_incluye_el_dia_hasta`, `test_los_resultados_se_paginan_de_25_del_mas_reciente_al_mas_antiguo`, `test_sin_resultados_se_muestra_el_estado_vacio`, `test_el_error_de_un_filtro_queda_junto_a_su_campo`, `test_el_menu_de_auditoria_solo_para_admin`, `test_auditoria_es_la_seccion_actual_del_menu` (`.active` y `aria-current="page"`), `MovilPacienteTest::test_el_menu_del_movil_tiene_las_mismas_secciones` (con «Auditoría»); `DisenioAuditoriaTest::test_la_vista_previa_marca_auditoria_en_el_menu` y `test_la_vista_previa_del_menu_lo_abre` (A70); verificación manual: `design/medir.mjs http://localhost:8000/_disenio/020` sale con 0 (solo las pantallas con `barra_lateral` pueden llevar la nota «solo en la barra lateral»; K7 y K7b sobre el menú real) y la página se ve como `design/candidatos/c4/`; `node docs/design/capturar.mjs --medir` sale con 0, con solo el enlace «Auditoría» fuera de la comparación (D24, A69) |
| CA7 (abuso): un no admin no consulta | `role:admin` | Feature `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` (dentista, asistente y cliente; aserta también su `acceso_denegado`) |
| CA8: request_id reutilizado si es válido o generado, y devuelto | `IdentificadorPeticion` | Feature `IdentificadorPeticionTest::test_se_reutiliza_un_identificador_valido`, `test_se_genera_uno_si_no_llega`, `test_las_respuestas_de_error_tambien_lo_llevan` |
| CA9 (abuso): identificador malformado o largo descartado, nunca reflejado ni registrado | `IdentificadorPeticion` | Feature `test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye`, `test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria` |
| CA10: logs y eventos de una petición comparten request_id; los logs llevan el usuario de la sesión y los eventos el actor de CA1 | `Context` en `IdentificadorPeticion` y `ContextoActor`; listener `ActualizarActorDelContexto` | Feature `IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor`, `test_tras_autenticarse_o_cerrar_sesion_logs_y_eventos_llevan_el_mismo_actor`, `test_una_peticion_anonima_no_hereda_el_actor_de_la_anterior`, `test_cada_listener_del_actor_se_ejecuta_una_vez` (A62), `RegistroAuditoriaTest::test_con_actor_explicito_el_evento_lleva_esa_cuenta_y_los_logs_la_sesion` |
| CA11: fallo cerrado en acciones clínicas y en la consulta, con error genérico | modo crítico, render 503, vista de error | Feature `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_critico_una_escritura_fallida_no_deja_cambios`, `test_en_modo_no_critico_la_accion_continua_y_se_registra_el_fallo`, `test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`, `ErrorAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico` (503 con «No se realizó la acción» y el `request_id`, aunque `APP_DEBUG=true`), `test_un_fallo_critico_sin_sesion_muestra_el_503` (ruta de prueba anónima: 503, nunca 500, sin el layout autenticado, A52), `test_el_503_muestra_el_telefono_configurado` (con un `clinica.contacto` de prueba: su teléfono y su enlace `tel:`, A77) |
| CA12: consulta y purga quedan registradas | `AuditoriaController`, `auditoria:purgar` | Feature `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros`, `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada` (comando real; aserta un `request_id` válido), `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` |
| CA13 (abuso): un identificador válido repetido se acepta sin transferir actor ni acceso | `IdentificadorPeticion`, `Auditoria` | Feature `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor`, `test_un_identificador_de_admin_reutilizado_no_da_acceso` (caracterización: comprobado haciendo que `ContextoActor` tome el actor de un `request_id` guardado) |
| CA14: aviso por correo de un evento que no puede escribirse en producción, sin datos y con el límite de frecuencia de la 032 | `Auditoria` (línea en `seguridad`), `Vigilante`, `AvisoProduccion`, vista del correo | Feature `RegistroAuditoriaTest::test_un_fallo_del_almacen_deja_una_linea_en_el_canal_de_seguridad`, `VigilanteTest::test_un_fallo_de_auditoria_avisa_por_correo`, `test_los_avisos_de_auditoria_respetan_el_silencio`, `test_un_fallo_de_auditoria_no_cuenta_como_fallo_de_acceso`, `test_el_aviso_de_auditoria_no_lleva_datos_del_evento`, `AvisoProduccionTest::test_el_aviso_de_auditoria_solo_lleva_tipo_recuento_y_horas`; verificación manual tras el despliegue: `php artisan dentissa:vigilar --prueba` sigue enviando (la 032) |
| TM1 | triggers | `test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_un_borrado_masivo_se_rechaza` |
| TM2 | trigger de borrado con `UTC_TIMESTAMP()` | `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `test_la_purga_respeta_el_corte_al_segundo` |
| TM3 | validación de la cabecera | `test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye`, `test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria` |
| TM4 | `role:admin` | `test_un_no_admin_no_consulta_el_registro` |
| TM5 | validación de `campos` y `detalle` | `test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares`, `test_el_detalle_rechaza_texto_libre` |
| TM6 | modo crítico; aviso | `test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_critico_una_escritura_fallida_no_deja_cambios`, `test_un_fallo_de_auditoria_avisa_por_correo` |
| TM7 | vista 503 genérica | `test_la_consulta_falla_cerrada_con_error_generico`, `test_un_fallo_critico_sin_sesion_muestra_el_503` |
| TM8 | validación de filtros y paginación fija | `test_filtros_invalidos_se_rechazan`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar` |
| TM9 | evento de purga en la transacción del borrado | `test_la_purga_queda_registrada`, `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` |
| TM10 | actor de la sesión o `Usuario` comprobado (D15) | `test_el_actor_es_el_usuario_autenticado`, `test_un_evento_tras_autenticarse_en_la_misma_peticion_lleva_ese_actor`, `test_el_actor_explicito_guarda_su_id_y_su_rol`, `test_con_actor_explicito_el_evento_lleva_esa_cuenta_y_los_logs_la_sesion`, `test_un_actor_sin_guardar_se_rechaza_sin_registrar`, `test_actor_e_identificador_intentado_a_la_vez_se_rechazan` |
| TM11 | `request_id` solo para correlación | `test_un_identificador_repetido_no_transfiere_el_actor`, `test_un_identificador_de_admin_reutilizado_no_da_acceso` |
| TM12 | solo valores con formato | `test_un_filtro_con_texto_libre_se_rechaza_y_no_se_registra`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`, `test_un_id_de_ruta_con_formato_invalido_no_se_guarda`, `test_el_detalle_rechaza_texto_libre` |
| TM13 | fallos del almacén saneados | `test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`, `test_el_log_de_fallo_solo_lleva_las_claves_previstas` |
| TM14 | `scheduler` acotado | `test_el_scheduler_de_produccion_usa_la_imagen_de_app_y_solo_app_env`, `test_el_scheduler_se_reinicia_solo_y_monta_el_local_ini_de_app`, `test_produccion_no_publica_puertos_ni_tiene_phpmyadmin`, `test_la_programacion_solo_tiene_la_purga` |
| TM15 | vista previa solo en local y sin la base | `test_la_vista_previa_no_existe_fuera_de_local`, `test_la_vista_previa_no_consulta_la_base` |
| TM16 | aviso sin datos | `test_el_aviso_de_auditoria_no_lleva_datos_del_evento`, `test_el_aviso_de_auditoria_solo_lleva_tipo_recuento_y_horas` |
| TM17 | solo los usuarios autenticados generan denegaciones; residuo aceptado (A78) | `test_un_visitante_sin_sesion_no_genera_evento` |
| TM18 | solo `Auditoria` inserta, con validación; residuo aceptado (A78) | `test_un_resultado_fuera_del_vocabulario_se_rechaza`, `test_el_actor_es_el_usuario_autenticado` |
| RNF: WCAG 2.1 AA a 320, 390 y 1440 px; estados y errores con texto | vistas, CSS | `AuditoriaVistasTest::test_los_pares_de_color_cumplen_el_contraste`, `test_las_vistas_nuevas_no_tienen_estilos_en_linea`, `test_el_css_nuevo_solo_usa_tokens`; verificación manual: `medir.mjs` (G-contraste, G-toque, G-foco, G-desborde) y `capturar.mjs --medir` |
| RNF: la consulta y el error genérico no aparecen en buscadores | vista 503; `/admin/auditoria` exige sesión | `ErrorAuditoriaTest::test_la_pagina_de_error_no_se_indexa` (`<meta name="robots" content="noindex">`), `ConsultaAuditoriaTest::test_sin_sesion_la_consulta_redirige_al_login` |
| RNF: registrar no añade más de 50 ms | `Auditoria` | `AuditoriaRendimientoTest::test_registrar_tarda_menos_de_50_ms_en_p95` (grupo `rnf`) |
| RNF: consulta con filtros < 2 s con 100.000 eventos | índices, consulta | `AuditoriaRendimientoTest::test_la_consulta_con_filtros_tarda_menos_de_2_s_en_p95` (grupo `rnf`) |

## Descubribilidad
No aplica: la spec no crea ni cambia rutas indexables. `/admin/auditoria` exige sesión y la página 503
lleva `noindex` (RNF en Trazabilidad); ninguna ruta de `docs/seo.md` cambia.

## Observabilidad
- **Logs nuevos** (sin datos personales; `request_id` y `actor_id` llegan por el `Context`):
  - `auditoria.fallo` → `error` → canal `seguridad` → exactamente `accion`, `critico` y `error`
    (clase corta y SQLSTATE), cuando no se puede escribir un evento →
    `RegistroAuditoriaTest::test_el_log_de_fallo_solo_lleva_las_claves_previstas`;
  - `auditoria.purga` → `info` → `eventos_purgados`, `fecha_corte`.
- **Eventos de auditoría:**
  - `acceso_denegado` (forma en Contratos) → `RegistroCentralDenegaciones` y `denegar()` →
    `DenegacionesAuditadasTest` (CA4);
  - `auditoria_consultada` (detalle: filtros validados) → `AuditoriaController@index` →
    `test_la_consulta_queda_registrada_con_sus_filtros` (CA12);
  - `auditoria_purgada` (detalle: recuento y fecha de corte; actor nulo = sistema; `request_id` por
    ejecución) → `auditoria:purgar` → `test_la_purga_queda_registrada` (CA12).
- **Alertas:** aviso nuevo `auditoria` del vigilante de la 032 (CA14): con al menos un
  `auditoria.fallo` nuevo en la ventana, correo al responsable con el recuento y las horas, y como
  mucho uno cada 30 minutos (A60).
- **Cómo se verifica tras el deploy:**
  - enviar una petición con `X-Request-Id` válido y comprobar la cabecera en la respuesta;
  - entrar como dentista a `/admin` y buscar en `/admin/auditoria` el `acceso_denegado` con ese
    `request_id` y `detalle.accion_solicitada` = `admin.dashboard`;
  - `deploy/verificar.sh` da OK en el `scheduler` y en `auditoria:purgar` programada;
  - `php artisan dentissa:vigilar --prueba` sigue enviando.

## Rollout
- **Feature flag:** no aplica. El registro de auditoría es un control de seguridad que no debe poder
  apagarse (mismo criterio que la D5 de la 014, aceptada por el usuario el 2026-09-28).
- **Antes de `/release 020`** (roadmap; A57): el merge de la 024 es ancestro del commit que se
  libera; `composer audit --ignore-severity=low --ignore-severity=medium` y
  `npm audit --audit-level=high` terminan con código 0. La rama vieja
  `feature/020-auditoria-base` no se usa: la 020 se reconstruye en `feature/020-auditoria` desde
  `main` (decisión del usuario, 2026-10-28).
- **Orden de despliegue** (`deploy/desplegar.sh`, que ya hace copia, migración y cambio de
  tráfico):
  1. migración (tabla y triggers, con `dentissa_app`);
  2. código (`app`, `web`);
  3. servicio `scheduler`, que se levanta con `app`.

  Antes de desplegar la 014 y la 041 (D1 de la 041). En esta liberación se sigue el orden de la
  primera liberación de la 032 (`deploy/README.md` §9), para que corra el `desplegar.sh` de la 020,
  que ya levanta el `scheduler` (A76):
  1. anotar la versión instalada (`git describe --tags`; hoy `v0.11.1`): es la versión a la que se
     vuelve si algo falla;
  2. `git fetch --tags origin` y el checkout del tag, a mano;
  3. `bash deploy/desplegar.sh <tag>`, que levanta `app`, `web` y el `scheduler` y termina con la
     verificación interna.

  Como el checkout ya está hecho, `desplegar.sh` informa como «versión anterior» el propio tag: la
  vuelta es a la versión anotada en el paso 1, nunca a la que dice el script.
- **Convivencia con el temporizador del vigilante** (A45): el `scheduler` solo programa la purga, y
  `dentissa:vigilar` sigue en `dentissa-vigilar.timer` hasta su spec propia. No hay dos mecanismos
  para la misma tarea (`test_la_programacion_solo_tiene_la_purga`).
- **Compatibilidad:** la migración solo añade; la versión anterior ignora la tabla. Las respuestas
  ganan una cabecera; ningún cliente depende de su ausencia.
- **Vuelta atrás** (A53): `bash deploy/volver.sh <tag anterior>`, **sin** revertir la migración y
  **sin** la copia. La tabla no molesta a la versión anterior, y los eventos escritos desde el
  despliegue son evidencia. `volver.sh` para el `scheduler` antes del checkout y lo quita si la
  versión anterior no lo tiene (`--remove-orphans`, D22). `desplegar.sh` propondrá volver con la
  copia porque hubo migración, pero con esta solo de expansión no hace falta: `docs/deployment.md` lo
  dice en la sección de la 020. Si la copia fuera inevitable (`restaurar.sh --volver`), el
  `scheduler` queda parado desde antes del checkout hasta el final de la carga, y ninguno de la 020
  sigue en marcha con la versión anterior (A68); se perderían los eventos escritos desde el
  despliegue, y así se declara. `down()` solo se usa en desarrollo.
- **Cambiar `app.env` en producción:** `bash deploy/aplicar-entorno.sh app` recrea `app` y el
  `scheduler`, los dos servicios que lo leen, y «Entorno aplicado» da OK en ambos (A74;
  `deploy/README.md` §10).
- **Métricas a vigilar tras el deploy:** avisos `auditoria` (deberían ser cero), número de
  `acceso_denegado` por día (un pico tras el deploy de la 014 indicaría un alcance mal calculado) y
  crecimiento de la tabla.

## Decisiones (→ ADR si son arquitectónicas)
- D0 · Tamaño: la spec se mantiene junta con sus 14 criterios; solo el traslado del vigilante sale
  a una spec propia (decisión del usuario, 2026-10-28).
- D1 · Almacén: tabla `auditoria` en la misma MariaDB, con triggers de inmutabilidad y purga solo
  de eventos de más de 5 años (decisión del usuario, 2026-09-28) →
  [ADR 0004](../../adr/0004-registro-auditoria-mariadb.md) (accepted). Cubre OB1.a y OB1.g.
- D2 · Purga: comando `auditoria:purgar` programado cada mes en un servicio `scheduler` con
  `schedule:work`, en local (decisión del usuario, 2026-09-28) y en producción (A45; decisión del
  usuario, 2026-10-28). En producción usa la imagen de `app` sin construir otra: `desplegar.sh` y
  `volver.sh` construyen solo `app` y `web`, y el `scheduler` se recrea con la imagen nueva al
  levantarlo. Como `app`, se reinicia solo (`restart: unless-stopped`, 030 CA9) y monta el mismo
  `local.ini` (030 CA10, 031), para que la purga vuelva tras reiniciar el servidor y corra con la
  misma configuración de PHP (A67). En local usa el mismo `build:` que `app`, que sale de la caché.
  Los tests del Compose de la 030 no se debilitan: la versión exacta se exige a toda imagen que se
  descarga, y el `local.ini`, a cada servicio PHP. Cubre OB1.g.
- D3 · API única `Auditoria::registrar` con modo crítico (fallo cerrado) y no crítico, por
  inyección y sin fachada. Fuera de HTTP, un `request_id` por ejecución. Los fallos del almacén
  nunca se encadenan ni se registran con su mensaje. En escrituras críticas, el evento va en la
  misma transacción que el cambio. La 014 usará el modo crítico en sus lecturas (su CA12).
- D4 · Denegaciones: quien rechaza con una comprobación propia usa `denegar()`, que registra y lanza
  `AccesoDenegadoAuditado`. El registro central, en el callback `respond` de `withExceptions`,
  registra el resto: 403 de usuario autenticado y 404 cuya excepción previa es una
  `AuthorizationException` (los Gates con `denyAsNotFound` de la 026–028, A46), y omite la excepción
  de `denegar()` y los 404 con código `recurso_inexistente` (el horario de quien no es dentista, 027).
  Se descartó mover esa comprobación de la 027 al controlador, porque cambia el contrato de su Gate,
  que prueba `HorarioDentistaVistasTest`. Se descartan la marca en el `Context` (valía para toda la petición) y
  `report` (Laravel no reporta los 403 ni los 404). Los controladores que ocultan un recurso ajeno
  con su propio 404 antes del Gate (`ClienteCitasController`: detalle y reprogramar) usan
  `denegar(..., 'denegado_404')`, y un id que no existe sigue con `abort(404)` sin evento (A66;
  decisión del usuario, 2026-10-28). Se descartó quitar el filtro por `paciente_id` para que decida
  el Gate: cambia el contrato de la 025 y la 028, que prueban `ReprogramarCitaRolesTest` y
  `EstadosCitaRolesTest`. Cubre OB1.c y cierra las excepciones P7 de la 025, la 026 y la 028 sobre
  sus 403 y 404.
- D5 · Correlación con el `Context` de Laravel, que añade `request_id` y `actor_id` a los
  metadatos de cada log. `ContextoActor` fija o quita el actor al empezar cada petición, y el
  listener `ActualizarActorDelContexto` lo actualiza en `Login` y `Logout`. Cubre OB2.a y OB2.b.
- D6 · `id` autoincremental en lugar de UUID (excepción a la convención): la tabla es un registro
  ordenado de solo inserción.
- D7 · La consulta audita sus filtros validados en `detalle` (CA12). El filtro por actor es solo
  por identificador (decisión del usuario, 2026-09-28).
- D9 · Sin feature flag (ver Rollout).
- D10 · Cobertura de riesgos: OB1.a, OB1.c, OB1.g, OB1.h, OB2.a y OB2.b dentro; OB1.b, OB1.d, OB1.e
  y OB1.f pendientes (specs 021, 022 y 014); OB4.a fuera, con el residuo aceptado de P8 (A44); los avisos de
  producción de la 032 ganan el aviso `auditoria`. Los estados se actualizan por corrección tras
  `/release`.
- D11 · Nombres `auditoria` (tabla) y `AuditoriaController` en singular, como excepción a la
  convención de `AGENTS.md`: «auditoría» nombra el registro en su conjunto, y así lo usan el ADR
  0004, `docs/observability.md` y el plan de la 014.
- D12 · Vocabulario cerrado de `resultado` (`exito`, `denegado`, `denegado_404`, `error`),
  validado por `registrar`; `denegado_403` no existe.
- D13 · Purga transaccional: borrado y evento `auditoria_purgada` (modo crítico) en la misma
  transacción; si el evento no se escribe, no se borra nada. El almacén (`purgar()`) es el único
  responsable del borrado y del indicador. El corte se mide en UTC, como `ocurrido_en` y el trigger:
  ahora en UTC menos 5 años, sin desbordar los años bisiestos y truncado al segundo (A40, A56).
- D14 · Requisitos no funcionales medidos con un test del grupo `rnf`, fuera de la suite por
  defecto, con datos sintéticos dentro de una transacción que se revierte.
- D15 · Actor explícito (decisión del usuario, 2026-10-27; plan 041, D2; spec CA1 y CA10 desde el
  2026-10-28): `registrar()` gana `?Usuario $actor`. Reglas en Contratos: un `Usuario` guardado y
  comprobado por el servidor, nunca un identificador; `actor_id` y `actor_rol` de ese usuario; el
  `Context` de los logs no cambia; incompatible con `identificadorIntentado` (A64); obligación y
  test de quien llama (A51). Alternativas descartadas: una sesión solo en memoria (frágil) o un actor
  vacío (no cumple el CA12 de la 041).
- D16 · El fallo de escritura va al canal `seguridad` (JSON, 14 días), que el vigilante ya lee: una
  sola línea `auditoria.fallo` con claves fijas, en lugar de una en `laravel.log` y otra para el
  vigilante. El vigilante distingue las líneas por su mensaje: hoy cuenta como fallo de acceso
  cualquier línea del canal, y una de auditoría inflaría ese recuento (`interpretarFallo`). El aviso
  `auditoria` salta con un solo fallo, porque cada uno es un evento perdido, y comparte el silencio
  de 30 minutos por tipo de la 032 (CA14; decisión del usuario sobre A60, 2026-10-28).
- D17 · 25 eventos por página y selectores con los valores registrados de `accion` y `recurso_tipo`
  (consultas `DISTINCT` sobre columnas con índice), según `design.md` (decisiones de diseño del
  2026-10-28: con 50, la página del teléfono medía unos 13 500 px; con texto libre había que saber
  los códigos). La validación sigue siendo la del texto (`^[a-z_]{1,60}$`): un selector se puede
  manipular.
- D18 · La página 503 no depende del layout autenticado ni de recursos externos (A52): documento
  propio con `app.css` (tokens), `errores.css` y la Roboto propia. Se copia el mismo archivo que
  verificó la 041 (D10 de la 041: `roboto-latin-wght-normal.woff2` de `@fontsource-variable/roboto`
  5.2.5, OFL 1.1, con `OFL.txt`, en `public/fonts/roboto/`, sin añadir la dependencia). La 020 se
  libera antes, así que el archivo y los tokens con nombre de la 041 (D13 de la 041) entran con la
  020, y la 041 los reutiliza al reconstruirse sobre `main`.
- D19 · Vista previa del diseño solo en local: `/_disenio/020/{pantalla}` renderiza las vistas
  reales con datos de prueba y un admin de prueba sin guardar, solo para esa petición, para medirlas
  con `design/medir.mjs` sin sesión. Es el patrón de la D11 de la 041. Fuera de local la ruta no
  existe (TM15). Para medir K7 y K7b sobre el menú real (A70):
  - renderiza con la petición de `/admin/auditoria` en lugar de la suya, así que el layout marca
    «Auditoría» (`.active` y `aria-current="page"`) con su propia regla, sin cambios en el layout;
  - `menu` es la consulta con el estado que pone `menu.js` al abrir el menú del teléfono
    (`menu-abierto` en `<html>` y `aria-expanded="true"` en `.menu-btn`), aplicado por el
    controlador sobre el HTML renderizado;
  - `?sin-barra=1` añade `aria-hidden="true"` a la barra lateral, como la copia que `medir.mjs` hace
    en las carpetas; así la excepción de la barra (DS1 y menú cerrado) también se comprueba sobre la
    URL y `medir.mjs` sale con 0 cuando solo falla la barra.

  Exención de P3: la ruta no lleva `auth` ni `role`; se acepta porque solo existe en local, usa datos
  inventados y no hace ninguna consulta a la base, con un test de cada cosa (decisión del usuario,
  2026-10-28; A73).
- D20 · Listeners por descubrimiento automático en `app/Listeners`, como `RegistrarFalloAcceso`, y
  no en el proveedor: un solo mecanismo, con un test de que cada listener se ejecuta una vez (A62).
  La 041 añade su listener de `Login` del mismo modo.
- D21 · Línea base de dependencias (2026-10-28): 0 críticas y 0 altas; 1 media en `symfony/mime`
  (CVE-2026-45070), que no bloquea P6. Se sugiere un `/chore` aparte, fuera de esta spec.
- D22 · Scripts de producción con un `scheduler` que puede no existir en la versión de destino
  (A68): `volver.sh` y `restaurar.sh` sirven para volver a versiones anteriores a la 020, cuyo
  Compose no lo tiene. Por eso:
  - se nombra el `scheduler` solo si `config --services` del Compose de destino lo lista
    (`grep -qxF scheduler`); nombrarlo sin más falla con «no such service»;
  - `volver.sh` lo para **antes** del checkout, con el Compose de la versión instalada, para que no
    escriba en la base mientras se cambia de versión;
  - `volver.sh --sin-levantar` para `app` y `web`, y levanta `db` con `--remove-orphans`, que quita
    el `scheduler` si el destino no lo tiene; si lo tiene, queda parado;
  - `restaurar.sh` para lo mismo antes de cargar la copia (también sin `--volver`) y al final
    levanta `app`, `web` y, si el destino lo tiene, el `scheduler`;
  - `desplegar.sh` y `volver.sh` (sin `--sin-levantar`) levantan con `--remove-orphans`.

  Se descartó parar todos los servicios menos `db`: pararía `cloudflared`, que los scripts nunca
  tocan (030).
- D23 · El teléfono del 503 sale de `config('clinica.contacto')`, la clave con la forma de la 038 y
  la 041 (D16 de la 041): `telefono` `753 162 5220`, `telefono_enlace` `tel:+527531625220` y
  `horario`, los datos reales de la página de inicio. La 020 se libera antes, así que la clave entra
  con ella y la 041 la reutiliza sin cambiarla (A77).
- D24 · `capturar.mjs --medir` compara el escritorio del admin con la línea base del tag de
  producción, que no tiene «Auditoría». Se excluye de la comparación solo el rectángulo de ese
  enlace, como ya se excluyen los campos del filtro (`EN_PAGINA.excluidos`). Es la última entrada
  de la barra, así que no desplaza nada; si algo más cambiara, la comparación seguiría fallando
  (decisión del usuario, 2026-10-28; A69).

Se retira la D8 del plan anterior (migrar en `bootstrap-test-db.sh`): la base de pruebas se migra con
el paso documentado y el CI ya lo hace (A59).

## Impacto en arquitectura
- `docs/architecture.md → Módulos`: «Auditoría» (`app/Auditoria/`, middlewares de correlación y
  contexto, listener del actor).
- `docs/architecture.md → Datos`: tabla `auditoria` y sus triggers.
- `docs/architecture.md → Backend → Observabilidad` y `docs/observability.md → Resumen`: la
  correlación y el registro de auditoría pasan a «en uso»; estados de OB1.a, OB1.c, OB1.g, OB1.h,
  OB2.a y OB2.b; aviso `auditoria` en los avisos de producción (A65).
- `docs/architecture.md → Frontend`: pantalla de auditoría y página 503 propia.
- `docs/deployment.md`: servicio `scheduler` (local y producción), permisos para crear triggers con
  `dentissa_app`, reloj sincronizado (NTP) y la vuelta atrás de la 020 sin copia (A53).
- `deploy/README.md`: el `scheduler` en el despliegue, la vuelta atrás y la restauración (D22);
  el orden de la primera liberación de la 020 (A76) y que `aplicar-entorno.sh app` recrea también
  el `scheduler` (§10, A74).
- `docs/roadmap.md`: cerrar las excepciones P7 de la 025, la 026 y la 028 y las notas de la 020;
  apuntar `/plan 014 --fix` con el contrato final (A58) y la spec propia del vigilante (A65).
- `docs/security.md`: TM del registro y del aviso; las vistas nuevas sin estilos ni scripts en línea.
- `AGENTS.md` (**ruta protegida**, con aprobación): `app/Auditoria/` en el mapa (A65).

## Riesgos y mitigaciones
- Crear triggers con `dentissa_app` → MariaDB 10.11 sin binlog no exige `SUPER`, y `dentissa_app`
  tiene `ALL` sobre su base. Se comprueba en el Compose de producción ensayado en local antes de
  liberar (A54) y se anota en `docs/deployment.md`.
- Con privilegios totales sobre la BD se puede vaciar la tabla, eliminar los triggers o cambiar su
  estructura → limitación aceptada en la spec y en el ADR 0004; fuera de CA2 y sin test.
- Registrar dos veces una denegación, o perder una → solo registran `denegar()` y el registro
  central, que omite exactamente la excepción de `denegar()`; tests de no duplicación y del 403
  posterior a un `@can` denegado.
- Confundir un 404 real con una denegación, o al revés → criterio de D4 (excepción previa
  `AuthorizationException`) con tests sobre rutas reales de los dos lados.
- El modo crítico convierte un fallo de BD en un 503 al abrir un expediente → comportamiento
  exigido por la spec: página genérica con el `request_id` y aviso al responsable.
- Crecimiento de la tabla en 5 años → índices por fecha, acción, recurso y `request_id`; volumen
  académico pequeño; RNF medido con 100.000 eventos.
- Relojes de la app y de la BD desincronizados → un evento dentro del desfase haría abortar el
  `DELETE` entero y la purga fallaría siempre; todo se mide en UTC y el requisito de NTP va en
  `docs/deployment.md`; test del corte al segundo.
- Residuo de OB4.a (P8 ❌ aceptado): la consulta o la purga fallidas pueden dejar identificadores y
  fechas en `laravel.log` del servidor, hasta la 023, que debe liberarse antes de tener datos reales
  (decisión del usuario, 2026-10-28).
- Cambiar cómo el vigilante lee el canal `seguridad` puede romper el aviso de accesos de la 032 →
  `VigilanteTest` sigue en verde, y un test comprueba que un fallo de auditoría no cuenta como fallo
  de acceso.
- El `desplegar.sh` instalado no conoce el `scheduler` → en esta liberación, checkout del tag a mano
  antes del script, para que corra el de la 020, y la vuelta a la versión anotada antes del
  checkout (Rollout, A76).
- `restaurar.sh` o `volver.sh --sin-levantar` con el `scheduler` en marcha podrían purgar o escribir
  durante la carga de la copia, y una versión sin `scheduler` no puede nombrarlo → contrato de D22
  (parar antes del checkout, nombrarlo solo si existe, `--remove-orphans`), con tests de los scripts
  y la vuelta con copia ensayada en `dentissa-prueba` (A68).
- Un `app.env` cambiado que solo relee `app` dejaría el `scheduler` con el entorno anterior (p. ej.
  la contraseña de la base sin rotar) → `aplicar-entorno.sh app` recrea los dos (A74).
- Conflictos con otras specs:
  - **041** (aprobada, espera a la 020): su contrato es el de D15; sus tokens y su Roboto ya
    estarán en `main` (D18), igual que la clave `clinica.contacto` de su D16 (D23), y su
    `layouts/acceso.blade.php` puede reutilizar la carcasa de la página 503 al reconstruirse. Revisar con `/plan 041 --fix` tras fusionar la 020 (T002 de la 041
    ya lo comprueba).
  - **014**: su plan sigue con `Auditoria::denegacion`, `denegado_403`, `Gate::after` y la marca en
    el `Context` → `/plan 014 --fix` con el contrato final antes de `/tasks 014` (A58; anotado en
    el roadmap).
  - **038** (en pausa): toca `app.css` y el sistema de diseño; la que se fusione después concilia
    los tokens con el mismo nombre y valor.
