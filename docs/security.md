---
status: inferred
updated: 2026-09-28
---

# Seguridad de Dentissa

## Referencias
- OWASP Top 10: edición 2025 (la vigente al inicializar; actualizar al cambiar de edición)
- Nivel ASVS objetivo: 2 (decisión del usuario, 2026-09-28)
- Adicionales: ninguno (no hay API pública)
- Compliance: marco de referencia LFPDPPP y NOM-024-SSA3 (proyecto académico con datos ficticios)

## Clasificación de datos
| Dato | Clasificación | Dónde vive | Protección |
|---|---|---|---|
| Diagnóstico, tratamiento, receta, observaciones | restringida (salud) | `expedientes` | ninguna a nivel de aplicación; sin cifrado en reposo |
| Alergias, tipo de sangre, enfermedades crónicas, medicamentos, notas médicas | restringida (salud) | `pacientes` | ninguna; sin cifrado en reposo |
| Notas del paciente y notas internas de cita | restringida (salud) | `citas.notas_paciente`, `citas.notas_internas` | notas internas solo visibles para admin en la vista |
| Nombre, email, teléfono, fecha de nacimiento, dirección, contacto de emergencia | confidencial (personal) | `usuarios`, `pacientes` | acceso por rol |
| Hash de contraseña | restringida (credencial) | `usuarios.password_hash` | bcrypt (`Hash::make`) |
| `google_id` | confidencial | `usuarios.google_id` | acceso por rol |
| Sesiones (IP, user agent, payload) | confidencial | `storage/framework/sessions` (driver `file` en local, confirmado por el usuario, 2026-09-28) | sin cifrar (`SESSION_ENCRYPT=false`) |
| Credenciales OAuth y de BD | restringida (secreto) | `.env` (no versionado) | fuera del repo |

## Autenticación y autorización
- Mecanismo de autenticación: sesión propia de Laravel (guard `web`, driver `session`) sobre
  `App\Models\Usuario`; `Auth::attempt` con `activo=1` y `session()->regenerate()`; logout por
  POST con invalidación. Google OAuth con Socialite (flujo con `state`). Registro público crea
  siempre rol `cliente`. No hay recuperación de contraseña ni verificación de correo.
- Modelo de autorización: rol único por usuario (`usuarios.rol`: cliente, asistente, dentista,
  admin). Regla de negocio deseada: dentista → sus pacientes asignados o con cita con él;
  asistente → los pacientes de sus dentistas asignados; solo el dentista autor (o admin) edita un
  expediente; cliente → solo lo suyo (decisión del usuario, 2026-09-28).
- Dónde se aplica: middleware `role:` por grupo (`bootstrap/app.php`,
  `app/Http/Middleware/RoleMiddleware.php`) y comprobaciones inline en controladores. No hay
  Policies ni Gates.

## Superficie de ataque
| Punto de entrada | Tipo | Autenticado | Notas |
|---|---|---|---|
| `GET/POST /login` | HTTP público | no | sin rate limiting |
| `GET/POST /registro`, `/registro/contacto` | HTTP público | no | sin rate limiting ni captcha |
| `GET /auth/google`, `/auth/google/callback` | HTTP público | no | vincula cuentas por email |
| `POST /logout` | HTTP | no exige `auth` | invalida sesión |
| `GET /up` | HTTP público | no | health check de Laravel |
| `/admin/*` | HTTP | sí, `role:admin` | |
| `/dentista/*` | HTTP | sí, `role:dentista` | pacientes y expedientes sin filtro de alcance |
| `/asistente/*` | HTTP | sí, `role:asistente` | pacientes y expedientes sin filtro de alcance |
| `/cliente/*` | HTTP | sí, solo `auth` | falta `role:cliente` |
| phpMyAdmin `:8080`, MariaDB `:3306` | red (Docker local) | root | publicados en todas las interfaces |

No hay rutas API, webhooks, colas ni tareas programadas.

## Manejo de secretos
- Dónde se guardan: `.env` local no versionado (en `.gitignore`). Credenciales de BD de desarrollo
  escritas en `docker-compose.yml` y `phpunit.xml`. Para staging y producción: TODO(init)
  (depende de la plataforma, ver [deployment.md](deployment.md)).
- Rotación: no hay procedimiento.
- `README.md` contiene contraseñas de cuentas de prueba y `database/dentissa.sql` datos ficticios
  con hashes (datos ficticios y repo privado, confirmado por el usuario, 2026-09-28).

## Herramientas
Adoptadas en la entrevista (decisión del usuario, 2026-09-28); **ninguna está instalada todavía**.

| Tipo | Herramienta | Comando | Cuándo corre |
|---|---|---|---|
| Secretos | gitleaks | `gitleaks detect --no-banner` | /implement, CI |
| SAST | semgrep | `semgrep scan --config p/php --error` | /implement (archivos tocados), /review |
| SCA (dependencias) | composer audit + npm audit | `composer audit --locked --ignore-severity=low --ignore-severity=medium && npm audit --audit-level=high` | /review, /release |
| Contenedores / IaC | trivy | `trivy fs --severity HIGH,CRITICAL .` | /review, /release |
| DAST | ninguna | — | — |

## Permisos del agente
Reglas base en `shared/agent-security.md` del plugin. Específicas de este proyecto:
- Antigüedad mínima de paquetes nuevos: 7 días
- Rutas protegidas adicionales: `AIDLC.md`, `aidlc/`, `docker-compose.yml`, `Dockerfile`,
  `database/dentissa.sql`
- Servicios externos aprobados para enviar código o datos: ninguno
- Dominios de red que el agente necesita: packagist.org, registry.npmjs.org, github.com

## Excepciones aceptadas
| ID | Hallazgo | Severidad | Motivo | Aprobado por | Vence |
|---|---|---|---|---|---|
| EX1 | `laravel/framework` 12.62.0 (CVE-2026-102279, XSS en la página de depuración; corregido en 12.69.0) y `league/flysystem` 3.35.1 (CVE-2026-102601, normalización de rutas; corregido en 3.35.3) | Baja | Publicados el 2026-09-29, después del plan de la spec 024. Actualizar Laravel arrastra 42 paquetes, y la 12.69.3 aún no cumple los 7 días (spec 024, D10). No pertenece a RS16 (severidad baja, fuera de P6). Al vencer, el usuario decide si se actualizan en una spec propia o se renueva EX1 con fecha y aprobación nuevas; lo comprueba el primer `/plan` o `/release` posterior al vencimiento | el usuario, 2026-09-29 | 2026-10-31 |

## Riesgos conocidos
<!-- Cada riesgo con ID `RS<n>` y cada corrección con ID `RS<n>.<letra>`. Estados de una
     corrección: pendiente · en curso (spec NNN) · mitigada (vX.Y.Z) · aceptada (excepción EX<n>).
     Un riesgo solo está mitigado cuando todas sus correcciones están mitigadas o aceptadas. -->

### RS1 · Alta — Acceso horizontal del personal a pacientes y expedientes (A01 Broken Access Control)
`PacientesController::index/show/edit/update` no filtran por dentista asignado ni por la relación
asistente↔dentista: cualquier dentista o asistente lista y ve la ficha clínica de todos los
pacientes, y cualquier dentista la edita. `ExpedientesController::show` y `update` no comprueban
propiedad: un dentista puede leer y sobrescribir el expediente de otro; un asistente ve cualquier
expediente por su id. `ExpedientesController::store` acepta `dentista_id`, `paciente_id` y
`cita_id` arbitrarios. `CitasController::create` expone todos los pacientes con teléfono y lee
`proxima_visita` de cualquier paciente. Contradice la regla de negocio confirmada. Evidencia:
`PacientesController.php`, `ExpedientesController.php`, `CitasController.php`; specs 003, 006, 007.

Correcciones:
- RS1.a Policy de paciente que limite `index/show/edit/update` al alcance del rol (dentista asignado o con cita; asistente → pacientes de sus dentistas) — estado: en curso (spec 014)
- RS1.b Policy de expediente en `show/update`: lectura por alcance, edición solo del dentista autor o admin — estado: en curso (spec 014)
- RS1.c `ExpedientesController::store` fuerza `dentista_id` propio para el rol dentista, valida que la cita pertenezca al paciente y que `dentista_id` sea de tipo dentista — estado: en curso (spec 014)
- RS1.d `CitasController::create` limita pacientes y `proxima_visita` al alcance del rol — estado: en curso (spec 014)
- RS1.e Tests Feature por rol (permitido y 403) para pacientes, expedientes y alta de citas — estado: en curso (spec 014)

### RS2 · Alta — Grupo `/cliente` sin control de rol (A01 Broken Access Control)
El grupo `/cliente/*` solo exige `auth`; admin, dentista y asistente pueden usarlo, y
`PerfilController::update` crea una fila en `pacientes` para cualquier usuario que no la tenga.
Evidencia: `routes/web.php:147`, `PerfilController.php`; spec 011.

Correcciones:
- RS2.a Añadir `role:cliente` al grupo `/cliente` — estado: pendiente
- RS2.b `PerfilController::update` no crea ficha de paciente para usuarios que no son cliente — estado: pendiente

### RS3 · Alta — Usuarios desactivados conservan su sesión (A07 Authentication Failures)
`activo` solo se comprueba al hacer login; desactivar un usuario o personal no invalida sus
sesiones abiertas. Evidencia: `RoleMiddleware.php`, `UsuarioController::toggleActivo`,
`PersonalController::toggleActivo`, `PacientesController::toggleActivo`; specs 001, 006, 008, 010.

Correcciones:
- RS3.a Comprobar `activo` en cada petición autenticada (middleware) y cerrar la sesión si es falso — estado: pendiente
- RS3.b Borrar las sesiones del usuario al desactivarlo — estado: pendiente

### RS4 · Alta — Sin límite de intentos en login, registro y OAuth (A07 Authentication Failures)
No hay `throttle` ni `RateLimiter` en `POST /login`, `POST /registro`, `/registro/contacto` ni
en el callback de Google. Permite fuerza bruta y altas masivas (cada alta asigna un dentista).
Evidencia: `routes/web.php:17-27`; specs 001, 002.

Correcciones:
- RS4.a Throttle por IP + email en `POST /login` — estado: pendiente
- RS4.b Throttle por IP en `POST /registro` y `/registro/contacto` — estado: pendiente
- RS4.c Throttle en `/auth/google/callback` — estado: pendiente

### RS5 · Media — Vinculación automática de Google por email (A07 Authentication Failures)
`GoogleAuthController` vincula una cuenta existente de cualquier rol (incluido admin) si el email
coincide, sin comprobar `email_verified` ni pedir confirmación. Evidencia:
`GoogleAuthController.php:20-28`; spec 001.

Correcciones:
- RS5.a Exigir el claim `email_verified` antes de vincular — estado: pendiente
- RS5.b No autovincular cuentas de personal o admin; solo clientes — estado: pendiente
- RS5.c Pedir la contraseña actual para vincular Google a una cuenta con contraseña — estado: pendiente

### RS6 · Media — Credenciales y datos con apariencia real versionados (A04 Cryptographic Failures / P5)
`README.md:61-67` contiene contraseñas en claro de cuentas por rol; `database/dentissa.sql` trae
datos personales y de salud (ficticios) y 8 hashes bcrypt, y es la semilla de Docker y de los
tests. Datos ficticios y repo privado (confirmado por el usuario, 2026-09-28), por eso Media.

Correcciones:
- RS6.a Quitar las contraseñas de `README.md` y documentar cómo crear cuentas de prueba — estado: pendiente
- RS6.b Sustituir el volcado por migraciones + seeder con datos sintéticos (Faker) — estado: pendiente
- RS6.c Cambiar las contraseñas de las cuentas del volcado en cualquier entorno compartido antes de desplegar — estado: pendiente
- RS6.d Purgar del historial de git el volcado con datos y las contraseñas del README, coordinado con el equipo (reescritura de historial y force-push) (decisión del usuario, 2026-09-28) — estado: pendiente

### RS7 · Media — Configuración Docker insegura si se reutiliza fuera de local (A02 Security Misconfiguration)
Root de MariaDB con contraseña trivial y `MYSQL_ALLOW_EMPTY_PASSWORD`, puertos 3306 y 8080
(phpMyAdmin) publicados en todas las interfaces, `.dockerignore` con una sola línea (la imagen
copiaría `.env`, `.git` y el volcado), `composer:latest` y `phpmyadmin:latest` sin fijar.
Evidencia: `docker-compose.yml`, `Dockerfile`, `.dockerignore`.

Correcciones:
- RS7.a Configuración de staging/prod separada, con credenciales por variables o secretos y usuario de BD no root — estado: pendiente
- RS7.b Publicar puertos solo en `127.0.0.1` y dejar phpMyAdmin exclusivamente en local — estado: pendiente
- RS7.c Ampliar `.dockerignore` (`.env*`, `.git`, `node_modules`, `vendor`, `database/*.sql`, `storage`) — estado: pendiente
- RS7.d Fijar las imágenes por versión (composer, phpmyadmin, nginx) — estado: pendiente

### RS8 · Media — Faltan cabeceras de seguridad (A02 Security Misconfiguration)
Ni la app ni nginx envían CSP, X-Frame-Options/frame-ancestors, X-Content-Type-Options,
Referrer-Policy ni HSTS. Evidencia: `PreventBackHistory.php`, `docker/nginx/dentissa.conf`.

Correcciones:
- RS8.a Middleware o `add_header` en nginx con CSP, X-Frame-Options, X-Content-Type-Options y Referrer-Policy — estado: pendiente
- RS8.b HSTS en staging/prod detrás de TLS — estado: pendiente

### RS9 · Media — Sesión y email no protegidos al cambiar credenciales (A07 Authentication Failures)
Cambiar la contraseña no invalida otras sesiones ni regenera la actual; cambiar el email del
perfil no pide reautenticación ni verificación. Evidencia: `PerfilController.php`; spec 011.

Correcciones:
- RS9.a `Auth::logoutOtherDevices()` (o borrado de sesiones) y regeneración de sesión al cambiar la contraseña — estado: pendiente
- RS9.b Pedir la contraseña actual para cambiar el email — estado: pendiente

### RS10 · Media — Contraseña generada viaja en claro en la sesión (A04 Cryptographic Failures)
Al dar de alta pacientes o personal sin contraseña, la generada se guarda en el flash de sesión
(driver `file`, `SESSION_ENCRYPT=false`) y se interpola en un atributo `onclick` con `{{ }}`.
Evidencia: `PacientesController.php`, `PersonalController.php`, `layouts/app.blade.php`; specs 006, 008, 010.

Correcciones:
- RS10.a No persistir la contraseña en sesión: sustituir por enlace de establecimiento de contraseña o mostrarla solo en la respuesta inmediata — estado: pendiente
- RS10.b Usar `@js()` para cualquier valor que se pase a JavaScript — estado: pendiente

### RS11 · Baja — Política de contraseñas inconsistente (A07 Authentication Failures)
Mínimo 6 caracteres en altas hechas por el personal frente a `Password::min(8)` en registro y
perfil. Evidencia: `PacientesController.php`, `PersonalController.php`, `UsuarioController.php`, `RegisterController.php`.

Correcciones:
- RS11.a Unificar con `Password::defaults()` en todas las altas y cambios — estado: pendiente

### RS12 · Baja — Validación de entrada débil (A05 Injection / P4)
`hora` solo es `required` (entrada inválida → 500 en `Carbon::parse`), `saveHorarios` no valida
`dias[*]`, varios `exists:personal,id` no restringen `tipo=dentista`. Evidencia:
`CitasController.php`, `ClienteCitasController.php`, `PersonalController.php`, `PacientesController.php`.

Correcciones:
- RS12.a Validar `hora` con `date_format:H:i` — estado: en curso (spec 025)
- RS12.b Validar cada elemento de `dias[*]` (día, horas y orden) en `saveHorarios` — estado: pendiente
- RS12.c `exists` con restricción `tipo=dentista` donde se espera un dentista — estado: pendiente (agendado de citas: hecho en la spec 025; pacientes, expedientes y asignación de asistentes: spec 013)

### RS13 · Baja — Enumeración de cuentas en el registro (A07 Authentication Failures)
El registro público responde con un mensaje explícito de "correo ya registrado". Evidencia:
`RegisterController.php`; spec 002.

Correcciones:
- RS13.a Mensaje genérico y throttle (ver RS4.b) en el alta — estado: pendiente

### RS14 · Baja — Campos privilegiados asignables en masa (A01 Broken Access Control)
`Usuario::$fillable` incluye `rol`, `activo` y `password_hash`. Hoy siempre se usan arrays
explícitos validados, así que no es explotable, pero es frágil. Evidencia: `app/Models/Usuario.php`.

Correcciones:
- RS14.a Sacar `rol`, `activo` y `password_hash` de `$fillable` y asignarlos explícitamente — estado: pendiente

### RS15 · Media — Datos de salud sin cifrado en reposo (A04 Cryptographic Failures)
No hay casts `encrypted` ni `Crypt::`; los campos clínicos de `pacientes`, `expedientes` y las
notas de `citas` están en claro. Con Query Builder los casts de Eloquent no aplicarían.

Correcciones:
- RS15.a Cast `encrypted` en los campos clínicos al introducir los modelos Eloquent (spec 013) — estado: pendiente
- RS15.b Cifrado de disco y de backups en staging/prod — estado: pendiente

### RS16 · Alta — Dependencias con vulnerabilidades conocidas (A03 Software Supply Chain Failures)
**Mitigado en v0.1.0 (spec 024, 2026-10-02):** RS16.a, RS16.b y RS16.c mitigadas. Los dos avisos
bajos posteriores quedan cubiertos por la excepción EX1, que vence el 2026-10-31.

Línea base de `/plan 014` (2026-09-28): `composer audit` reporta 10 avisos altos y 6 medios en
`league/commonmark` 2.8.2 y `guzzlehttp/guzzle` (transitivas de Laravel); `npm audit` reporta 2
críticos, 6 altos y 1 moderado (`concurrently`, `shell-quote`, `axios`, `vite`, `form-data`,
`nanoid`, `picomatch`, `postcss`, `follow-redirects`). Todas tienen corrección disponible.
Bloquean `/release` (principio P6). Se atiende en una spec propia de actualización de
dependencias, antes de cualquier release (decisión del usuario, 2026-09-28).

Recuento de la spec 024 antes de actualizar (T010, 2026-09-29): `composer audit` reporta 9 altas y
7 medias (`guzzlehttp/guzzle` 1 alta y 5 medias; `league/commonmark` 8 altas y 2 medias), más 2
bajas ajenas a RS16 que cubre la excepción EX1. `npm audit` reporta 2 críticas, 6 altas y 1
moderada. `rolldown` queda fijado en 1.2.9 con `overrides` en `package.json` hasta que una versión
más reciente cumpla los 7 días de antigüedad; entonces una actualización futura puede retirar la
fijación.

Correcciones:
- RS16.a Actualizar `league/commonmark` y `guzzlehttp/guzzle` a versiones corregidas dentro de las restricciones de `composer.json` — estado: mitigada (v0.1.0)
- RS16.b Aplicar las correcciones de npm (`npm audit fix`, revisando saltos de versión mayor) — estado: mitigada (v0.1.0)
- RS16.c Volver a ejecutar `composer audit` y `npm audit --audit-level=high` sin hallazgos críticos ni altos — estado: mitigada (v0.1.0)

## No determinado
- Valores de `APP_DEBUG`, `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT` y `GOOGLE_REDIRECT_URI` en
  staging y producción: esos entornos aún no existen. En local coinciden con `.env.example`
  (`APP_DEBUG=true`), confirmado por el usuario, 2026-09-28.
- Restricción de dominios de la app de Google OAuth.
