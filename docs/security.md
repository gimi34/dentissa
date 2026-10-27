---
status: inferred
updated: 2026-10-18
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
| Sesiones (IP, user agent, payload) | confidencial | `storage/framework/sessions` (driver `file` en local, confirmado por el usuario, 2026-09-28; también en producción, plantilla de `app.env` en `deploy/README.md`) | sin cifrar (`SESSION_ENCRYPT=false`; en producción, RS10.c) |
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
  `app/Http/Middleware/RoleMiddleware.php`) y comprobaciones inline en controladores. Los cambios
  de estado de las citas (`PATCH /{rol}/citas/{id}/estado`, `DELETE /admin/citas/{id}` y
  `PATCH /cliente/citas/{id}/cancelar`) usan los Gates `gestionar-cita` (alcance, antes de validar)
  y `cambiar-estado-cita` (alcance y estados que cada rol puede pedir), en
  `app/Autorizacion/CambiarEstadoCita.php`; la cancelación del paciente exige además `role:cliente`
  (spec 026). El horario del dentista (`POST /admin/personal/{id}/horarios`) y su sección en la
  ficha usan, además de `role:admin`, el Gate `definir-horario` (`app/Autorizacion/GestionPersonal.php`):
  solo la administración y solo sobre un dentista; otro tipo de personal → 404 (spec 027). La
  reprogramación (`GET` y `PATCH /{admin,asistente,cliente}/citas/{id}/reprogramar`) exige el rol
  del grupo con middleware (`role:admin`, `role:asistente`, `role:cliente`; no hay ruta en el grupo
  del dentista) y el Gate `reprogramar-cita` (`app/Autorizacion/ReprogramarCita.php`): primero el
  alcance de la 026 (personal fuera de alcance → 403; cita de otro paciente → 404) y después el
  rol (el dentista no reprograma); el paciente busca además la cita entre las suyas (spec 028). No
  hay Policies.

## Superficie de ataque
| Punto de entrada | Tipo | Autenticado | Notas |
|---|---|---|---|
| `GET/POST /login` | HTTP público | no | 5 fallos por minuto por correo e IP (spec 030) |
| `GET/POST /registro`, `/registro/contacto` | HTTP público | no | los `POST` de las dos rutas, 5 por minuto por IP (spec 030); los `GET`, sin límite; sin captcha |
| `GET /auth/google`, `/auth/google/callback` | HTTP público | no | vincula cuentas por email; la vuelta, 5 por minuto por IP (spec 030) |
| `POST /logout` | HTTP | no exige `auth` | invalida sesión |
| `GET /up` | HTTP público | no | health check de Laravel |
| `/admin/*` | HTTP | sí, `role:admin` | |
| `/dentista/*` | HTTP | sí, `role:dentista` | pacientes y expedientes sin filtro de alcance |
| `/asistente/*` | HTTP | sí, `role:asistente` | pacientes y expedientes sin filtro de alcance |
| `/cliente/*` | HTTP | sí, solo `auth` | falta `role:cliente`, salvo en cancelar (spec 026) y reprogramar (spec 028) |
| phpMyAdmin `:8080`, MariaDB `:3306` | Docker local | root | solo en `127.0.0.1` del equipo de desarrollo (spec 030); no existen en producción |
| Túnel de Cloudflare → nginx de producción | HTTPS público (borde de Cloudflare) | según ruta | única entrada a producción; sin puertos abiertos en el router ni publicados en el servidor; nginx toma la IP real de `CF-Connecting-IP` solo desde la red de Docker y redirige `http://` a `https://` (spec 030) |
| SSH del servidor de producción | red local y red privada (Tailscale) | clave | desde la red local (`ufw`) y por la interfaz `tailscale0` solo el 22 (ACL del tailnet), sin contraseña ni root (specs 030 y 031, ADR 0006) |
| Temporizador `dentissa-vigilar` (systemd, cada minuto) | tarea programada | cuenta de despliegue | `php artisan dentissa:vigilar` en `app` como `www-data`: lee el log de acceso de nginx (montado en solo lectura) y el canal `seguridad`, y envía avisos por correo; sin entrada externa propia (spec 032, ADR 0007) |
| Salida SMTP a Gmail (`smtp.gmail.com:465`) | saliente, TLS implícito | contraseña de aplicación | desde `app`, con una cuenta de Gmail solo para enviar los avisos; los correos no llevan datos personales ni secretos (spec 032, TM7, TM13) |

No hay rutas API, webhooks ni colas. La única tarea programada es el vigilante de avisos (spec 032).

## Manejo de secretos
- Dónde se guardan: `.env` local no versionado (en `.gitignore`). Credenciales de BD de desarrollo
  escritas en `docker-compose.yml` y `phpunit.xml`. En producción (spec 030): `app.env`, `db.env` y
  `tunnel.env` en `/opt/dentissa/entorno` del servidor (carpeta 700, archivos 600, fuera del clon,
  excluidos de git y de la imagen); cada servicio lee solo el suyo (ver [deployment.md](deployment.md)).
- Avisos de producción (spec 032): `MAIL_USERNAME` y `MAIL_PASSWORD` de `app.env` son la contraseña
  de aplicación de una **cuenta de Gmail solo para enviar**, sin otros datos ni reenvíos (TM13): una
  contraseña de aplicación da acceso a todo el buzón de su cuenta. `verificar.sh` comprueba que
  están definidas, sin el texto de ejemplo y con TLS (puerto 465), sin mostrar los valores.
- Copias de la base (spec 032, ADR 0007): cifradas (`.sql.gz.gpg`) para la clave del responsable. En
  el servidor solo está la **clave pública** (`/opt/dentissa/entorno/copias.pub.asc`, 600). La
  **clave secreta**, con su frase (al menos 5 palabras al azar o 20 caracteres), está solo en el PC
  del responsable y en una segunda copia fuera del PC; nunca llega al servidor (se restaura desde el
  PC, `deploy/restaurar.sh`), ni al repositorio ni al paquete (`*.asc` y `*.gpg` en `.gitignore` y
  `.dockerignore`). Si se pierden las dos copias de la clave, las copias no se recuperan.
- Rotación: las contraseñas de las cuentas del volcado se rotan en producción con
  `php artisan dentissa:rotar-credenciales --confirmar` (spec 030). La contraseña de aplicación de
  Gmail se revoca y se crea otra en la cuenta de envío, y se aplica con `aplicar-entorno.sh app`
  (spec 032). La clave de las copias no caduca ni se rota; el resto, sin procedimiento.
- `README.md` contiene contraseñas de cuentas de prueba y `database/dentissa.sql` datos ficticios
  con hashes (datos ficticios y repo privado, confirmado por el usuario, 2026-09-28).

## Herramientas
Adoptadas en la entrevista (decisión del usuario, 2026-09-28). Desde la spec 024 corren en el CI
(`.github/workflows/ai-dd.yml`) y en local con sus imágenes Docker (`zricethezav/gitleaks:v8.21.2`,
`semgrep/semgrep:1.99.0`, `aquasec/trivy:0.57.1`); estado `en-ci` (decisión del usuario, 2026-10-02).

| Tipo | Herramienta | Comando | Estado | Cuándo corre |
|---|---|---|---|---|
| Secretos | gitleaks | `gitleaks detect --no-banner` | en-ci | /implement, /review; CI: en cada PR sobre sus commits (bloquea) y en el job semanal sobre todo el repo (no bloquea) |
| SAST | semgrep | `semgrep scan --config p/php --metrics=off --error` | en-ci | /implement (archivos tocados), /review; CI: en cada PR solo los hallazgos nuevos (`--baseline-commit`, bloquea) y en el job semanal (no bloquea) |
| SCA (dependencias) | composer audit + npm audit | `composer audit --locked --ignore-severity=low --ignore-severity=medium && npm audit --audit-level=high` | en-ci | /review, /release; CI: en un PR solo si cambia un lockfile (bloquea) y en el job semanal (no bloquea) |
| Contenedores / IaC | trivy | `trivy fs --severity HIGH,CRITICAL .` | en-ci | /review, /release; CI: solo en el job semanal o manual, sin bloquear (`continue-on-error`) |

Cuándo bloquea cada una en la CI: descrito tal como está en `.github/workflows/ai-dd.yml` (decisión del usuario, 2026-10-18).
Cambiar el workflow para que trivy o SCA bloqueen en cada PR sería una tarea aparte (`/chore`).
| DAST | ninguna | — | no-aplica: no hay staging (`docs/deployment.md`, objetivo 5); se decide al crearlo | — |

`--metrics=off`: semgrep descarga `p/php` de semgrep.dev, y sin esa opción enviaría métricas de uso
a ese servicio (upgrade a AI-DD 1.11.0, decisión del usuario, 2026-10-02).

## Permisos del agente
Reglas base en `shared/agent-security.md` del plugin. Específicas de este proyecto:
- Antigüedad mínima de paquetes nuevos: 7 días
- Rutas protegidas adicionales: `AIDLC.md`, `aidlc/`, `docker-compose.yml`, `Dockerfile`,
  `database/dentissa.sql`
- Servicios externos aprobados para enviar código o datos: ninguno
- Dominios de red que el agente necesita: packagist.org, registry.npmjs.org, github.com, hub.docker.com (solo para consultar la fecha de publicación de las etiquetas de imagen al fijar versiones; aprobado por el usuario el 2026-10-03, spec 030, /analyze A28)

## Excepciones aceptadas
Vigentes: EX2 y EX3 (aprobada el 2026-10-27 en la v0.11.0, spec 032). EX1 (avisos bajos de
`laravel/framework` y `league/flysystem`, aprobada el 2026-09-29) se retiró en v0.4.1 (spec 029,
2026-10-03): sus avisos quedaron corregidos.

| ID | Hallazgo | Severidad | Motivo | Aprobado por | Vence |
|---|---|---|---|---|---|
| EX2 | Avisos sin corrección aplicable en las imágenes de producción (`trivy image`; 2026-10-04, spec 030 T033/T035; repetido el 2026-10-19, spec 031 T031, y el 2026-10-27, ficha 2026-10-27-chore-imagenes-031-r4, RS18). `app` (`php:8.4.26-fpm`, Debian 13, construida sin caché y con `apt-get upgrade` el 2026-10-27): 122 altas y 4 críticas, ninguna con corrección publicada (118 altas y las 4 críticas «affected», 4 altas «fix_deferred»; `linux-libc-dev` 60, libxml2, util-linux, login, systemd, perl). `mariadb:10.11.19`: 25 altas y 1 crítica en el Go `stdlib` con que se compiló `gosu`; las reconstrucciones de la etiqueta del 2026-10-17 y del 2026-10-27 las tienen igual, sin imagen corregida. `cloudflare/cloudflared:2026.10.2`: 1 alta (`github.com/klauspost/compress`), corregida en la 2026.10.4, que cumple 7 días el 2026-11-02. `web` (nginx 1.30.5) construida sin caché: sin avisos. La imagen `composer` no llega a producción (solo se copia su binario, que se borra) | Alta y crítica | Debian no publica corrección para lo que queda en `app`; casi la mitad es `linux-libc-dev`, cabeceras del kernel que no se ejecutan en el contenedor (el kernel es el del servidor). `gosu` solo se ejecuta al arrancar `db` para bajar de privilegios, sin red. La de `cloudflared` se corrige al refrescarla cuando cumpla 7 días (RS18.a) y volver a escanear antes del vencimiento. Sin puertos publicados, la única entrada es el túnel hacia nginx | usuario, 2026-10-04; renovada por el usuario el 2026-10-19 y el 2026-10-27 | 2026-12-31 |
| EX3 | Disco del servidor de producción sin cifrar (RS15.c; spec 032 CA10) | Media | Cifrarlo exige reinstalar el servidor. Los datos de hoy son ficticios; las copias ya van cifradas (RS15.b) y la clave secreta no está en el servidor. Residuo: un archivo borrado puede quedar en el disco (`shred` no garantiza el borrado en ext4 con journal), por ejemplo las copias en claro de antes de la 032 | el responsable (gimi34), 2026-10-27, en la liberación de la v0.11.0 (spec 032, T096) | al cargar el primer dato real de pacientes |

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
- RS2.a Añadir `role:cliente` al grupo `/cliente` — estado: pendiente (cancelación del paciente con `role:cliente`: spec 026; reprogramar del paciente, `GET` y `PATCH /cliente/citas/{id}/reprogramar`, con `role:cliente`: spec 028; resto del grupo → spec 015)
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
**Mitigado en v0.5.0 (spec 030, 2026-10-04):** RS4.a, RS4.b y RS4.c mitigadas; comprobado en
producción con la IP real del cliente detrás del túnel.
Evidencia: `routes/web.php:17-27`; specs 001, 002.

Correcciones:
- RS4.a Throttle por IP + email en `POST /login` — estado: mitigada (v0.5.0)
- RS4.b Throttle por IP en `POST /registro` y `/registro/contacto` — estado: mitigada (v0.5.0)
- RS4.c Throttle en `/auth/google/callback` — estado: mitigada (v0.5.0)

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
- RS6.c Cambiar las contraseñas de las cuentas del volcado en cualquier entorno compartido antes de desplegar — estado: mitigada (v0.5.0)
- RS6.d Purgar del historial de git el volcado con datos y las contraseñas del README, coordinado con el equipo (reescritura de historial y force-push) (decisión del usuario, 2026-09-28) — estado: pendiente

### RS7 · Media — Configuración Docker insegura si se reutiliza fuera de local (A02 Security Misconfiguration)
Root de MariaDB con contraseña trivial y `MYSQL_ALLOW_EMPTY_PASSWORD`, puertos 3306 y 8080
(phpMyAdmin) publicados en todas las interfaces, `.dockerignore` con una sola línea (la imagen
copiaría `.env`, `.git` y el volcado), `composer:latest` y `phpmyadmin:latest` sin fijar.
Evidencia: `docker-compose.yml`, `Dockerfile`, `.dockerignore`. La spec 030 corrige local y crea
una configuración de producción aparte; lo que pide staging queda pendiente (/analyze 030 A1:
RS7.a se divide en producción y staging).

Correcciones:
- RS7.a Configuración de producción separada, con credenciales por variables o secretos y usuario de BD no root — estado: mitigada (v0.5.0)
- RS7.b Publicar puertos solo en `127.0.0.1` y dejar phpMyAdmin exclusivamente en local — estado: mitigada (v0.5.0)
- RS7.c Ampliar `.dockerignore` (`.env*`, `.git`, `node_modules`, `vendor`, `database/*.sql`, `storage`) — estado: mitigada (v0.5.0)
- RS7.d Fijar las imágenes por versión (composer, phpmyadmin, nginx) — estado: mitigada (v0.5.0)
- RS7.e Configuración de staging separada, con los mismos requisitos que RS7.a — estado: pendiente (→ objetivo 5, cuando exista staging)

### RS8 · Media — Faltan cabeceras de seguridad (A02 Security Misconfiguration)
Ni la app ni nginx envían CSP, X-Frame-Options/frame-ancestors, X-Content-Type-Options,
Referrer-Policy ni HSTS. Evidencia: `PreventBackHistory.php`, `docker/nginx/dentissa.conf`.

Correcciones:
- RS8.a `add_header` en nginx con CSP, X-Frame-Options/frame-ancestors, X-Content-Type-Options y Referrer-Policy (fragmento `docker/nginx/seguridad/comunes.conf`) — estado: mitigada (v0.10.0)
- RS8.b HSTS en producción detrás de TLS (fragmento `produccion.conf`) — estado: mitigada (v0.10.0)
- RS8.c HSTS en staging — estado: pendiente (→ objetivo 5, cuando exista staging)
- RS8.d CSP estricta, sin `'unsafe-inline'`: sacar de las vistas los scripts incrustados, los manejadores `on…` y los `style=""`, por pantallas — estado: pendiente (→ roadmap, decisión del usuario, 2026-10-05)

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
- RS10.b Usar `@js()` para cualquier valor que se pase a JavaScript (también el nombre del servicio en `servicios/index.blade.php`, hoy con `addslashes`) — estado: pendiente
- RS10.c En producción, sesiones cifradas (`SESSION_ENCRYPT=true` en la plantilla de `app.env` y comprobado por `deploy/verificar.sh`) — estado: pendiente (→ objetivo 2; re-sincronización 2026-10-18)

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
- RS12.a Validar `hora` con `date_format:H:i` — estado: mitigada (v0.2.0)
- RS12.b Validar cada elemento de `dias[*]` (día, horas y orden) en `saveHorarios` — estado: mitigada (v0.4.0)
- RS12.c `exists` con restricción `tipo=dentista` donde se espera un dentista — estado: pendiente (agendado de citas: hecho en la spec 025; horario de dentista: spec 027; pacientes, expedientes y asignación de asistentes: spec 013)

### RS13 · Baja — Enumeración de cuentas en el registro (A07 Authentication Failures)
El registro público responde con un mensaje explícito de "correo ya registrado". Evidencia:
`RegisterController.php`; spec 002. La spec 030 añade el límite de intentos en el alta; el mensaje
genérico queda pendiente (/analyze 030 A34: RS13.a se divide).

Correcciones:
- RS13.a Throttle en el alta (ver RS4.b) — estado: mitigada (v0.5.0)
- RS13.b Mensaje genérico en el alta, que no revele si el correo ya tiene cuenta — estado: pendiente (→ objetivo 2)

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
- RS15.b Cifrado de las copias de la base en producción — estado: mitigada (v0.11.0)
- RS15.c Cifrado del disco del servidor de producción — estado: aceptada como excepción EX3 (2026-10-27, v0.11.0)
- RS15.d Cifrado del disco y de las copias en staging — estado: pendiente (→ roadmap, objetivo 5)

### RS16 · Alta — Dependencias con vulnerabilidades conocidas (A03 Software Supply Chain Failures)
**Mitigado en v0.1.0 (spec 024, 2026-10-02):** RS16.a, RS16.b y RS16.c mitigadas. Los dos avisos
bajos posteriores, cubiertos por la excepción EX1, se corrigieron en v0.4.1 (spec 029), y EX1 se retiró.

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
fijación. El 2026-10-20 (`docs/changes/2026-10-20-chore-vite-8.3.4.md`) la fijación pasa a
`rolldown` 1.2.13 y se añade `postcss` 8.5.30, las más recientes con 7 días que pide `vite` 8.3.4.

Correcciones:
- RS16.a Actualizar `league/commonmark` y `guzzlehttp/guzzle` a versiones corregidas dentro de las restricciones de `composer.json` — estado: mitigada (v0.1.0)
- RS16.b Aplicar las correcciones de npm (`npm audit fix`, revisando saltos de versión mayor) — estado: mitigada (v0.1.0)
- RS16.c Volver a ejecutar `composer audit` y `npm audit --audit-level=high` sin hallazgos críticos ni altos — estado: mitigada (v0.1.0)

### RS17 · Media — Vulnerabilidad media en `phpseclib/phpseclib` (A03 Software Supply Chain Failures)
**Mitigado en v0.4.1 (spec 029, 2026-10-03):** `phpseclib/phpseclib` 3.0.57.

Detectada el 2026-10-03 en la review de la spec 027 (`composer audit --locked`): `phpseclib/phpseclib`
3.0.55, dependencia transitiva, tiene CVE-2026-84308 (multiplicación escalar X25519 sin tiempo
constante, que permite recuperar la clave privada), corregida en 3.0.57. Es media: queda fuera de
P6, que solo bloquea críticas y altas, y el comando SCA del CI ignora las medias. La spec 027 no la
introduce (no cambia `composer.lock`).

Correcciones:
- RS17.a Actualizar `phpseclib/phpseclib` a 3.0.57 o posterior — estado: mitigada (v0.4.1)

### RS18 · Alta — Imágenes base de producción con avisos sin corrección (A03 Software Supply Chain Failures)
Detectado el 2026-10-04 al escanear con `trivy image` las imágenes de la spec 030 (T033): la base
Debian 13 de `php:8.4.25-fpm` arrastra avisos altos y críticos sin corrección publicada, y
`mariadb:10.11.18` y `cloudflare/cloudflared:2026.9.3` tienen avisos ya corregidos en imágenes más
nuevas. Las correcciones publicadas de los paquetes del sistema de `app` y `web` se aplican al
construir (T035); el resto queda cubierto por la excepción EX2 hasta el 2026-12-31 (renovada el
2026-10-19, spec 031 T031, y el 2026-10-27, ficha 2026-10-27-chore-imagenes-031-r4). El 2026-10-19 se vio además que `desplegar.sh` construye con la caché
de Docker y la capa de `apt-get upgrade` / `apk upgrade` no se renueva: el `web` sin caché sale sin
avisos y con caché, con 3 (→ spec 031, T054–T055, dentro de RS18.a).

Correcciones:
- RS18.a Refrescar las imágenes fijadas (`php`, `mariadb`, `cloudflared` y las demás) a las más nuevas con ≥ 7 días y volver a escanear con `trivy image` antes de que venza EX2; renovar o retirar EX2 según el resultado. Incluye construir sin caché en cada despliegue, para que la capa de actualización del sistema se renueve (spec 031, T054–T055) — estado: mitigada (v0.10.0): imágenes refrescadas y escaneadas, construcción sin caché, EX2 renovada hasta el 2026-11-30. El 2026-10-27 (ficha 2026-10-27-chore-imagenes-031-r4): `cloudflared` 2026.10.0 → 2026.10.2 (de 7 altas a 1), `mariadb:10.11.19` sin cambios (las dos reconstrucciones tienen los mismos avisos de `gosu`), todas escaneadas otra vez y EX2 renovada hasta el 2026-12-31; queda subir `cloudflared` a la 2026.10.4 o posterior y volver a escanear antes de ese vencimiento (roadmap)

### RS19 · Media — Un admin puede dejar la aplicación sin administradores (A01 Broken Access Control)
Detectado en la re-sincronización del 2026-10-18. `UsuarioController@update` toma `rol` y `activo` del
formulario sin excepción para la propia cuenta: un admin que guarda su ficha sin marcar «Activo», o
con otro rol, se desactiva o se degrada a sí mismo, y nada impide desactivar o degradar al último
admin activo. Además, el cambio de rol no crea ni retira la ficha de `personal` o `pacientes` que
el nuevo rol necesita. Evidencia: `app/Http/Controllers/UsuarioController.php` (`update`,
`toggleActivo`); spec 010.

Correcciones:
- RS19.a Un admin no puede quitarse el rol ni desactivarse a sí mismo — estado: pendiente (→ objetivo 2)
- RS19.b No se puede desactivar ni quitar el rol al último admin activo — estado: pendiente (→ objetivo 2)
- RS19.c Al cambiar el rol, crear o retirar la ficha de `personal` o `pacientes` que corresponde (o impedir el cambio si no se puede) — estado: pendiente (→ objetivo 2)

## No determinado
- Valores de `APP_DEBUG`, `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT` y `GOOGLE_REDIRECT_URI`: en
  local coinciden con `.env.example` (`APP_DEBUG=true`), confirmado por el usuario, 2026-09-28.
  Staging no existe. Producción (desde v0.5.0): la plantilla de `app.env` (`deploy/README.md`) fija
  `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` y `GOOGLE_REDIRECT_URI` en https, y
  `deploy/verificar.sh` los comprueba en cada despliegue; `SESSION_ENCRYPT` no se fija (vale
  `false`, RS10.c). Lo declarado en la plantilla no garantiza el valor real de `app.env`.
- Restricción de dominios de la app de Google OAuth.
