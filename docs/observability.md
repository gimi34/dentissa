---
status: inferred
updated: 2026-10-18
---

# Observabilidad y auditoría de Dentissa

Estado de cada capacidad: **presente** (instalada o declarada) · **configurada** (configurada para
este proyecto) · **en uso** (el código o la operación la ejercitan). Solo "en uso" cuenta como
cubierta.

## Resumen
| Capacidad | Herramienta | Estado | Evidencia |
|---|---|---|---|
| Logs de aplicación | Monolog (Laravel), canal `stack` → `single`, y canal `seguridad` (`daily`, JSON, 14 días; spec 032) | en uso solo para los avisos (spec 032): el canal `seguridad` con los fallos de acceso, `Log::warning('aviso no enviado')` (campo `tipo`) y `Log::warning('lectura fallida')` (campo `fuente`: `acceso` o `seguridad`, sin el mensaje de la excepción); el resto del código no escribe logs | `config/logging.php`, `app/Listeners/RegistrarFalloAcceso.php`, `app/Avisos/Vigilante.php` |
| Agregación de logs | ninguna | — | local: `laravel.log` en el directorio del proyecto montado. Producción (spec 030): `laravel.log` en el volumen `storage` y `dentissa-access.log`/`dentissa-error.log` de nginx en el volumen `nginx_logs`, que sobreviven a reinicios y a recrear los contenedores |
| Correlación por petición | ninguna | — | `bootstrap/app.php` sin middleware ni `Context::add` |
| Registro de auditoría | ninguno (`historial_citas` es historial de negocio de estados y reprogramaciones de cita, con autor, sin IP ni request_id) | — | `app/Agenda/EstadosCita.php` (una fila con autor por cambio; el trigger `trg_historial_cita` se retira por migración, spec 026), `app/Agenda/ReprogramacionCitas.php` (fecha anterior y nueva, spec 028) |
| Eventos de autenticación | `Login`, `Failed`, `Logout` y `Lockout` de Laravel | `Failed` y `Lockout`, en uso para alertar (spec 032): `RegistrarFalloAcceso` escribe una línea con huellas en el canal `seguridad`; no es auditoría (`Login` y `Logout` siguen sin registrarse) | `app/Http/Controllers/AuthController.php`, `app/Providers/AppServiceProvider.php` (limitadores), `app/Listeners/RegistrarFalloAcceso.php` |
| Métricas | ninguna | — | `composer.json` sin Pulse/Telescope/Prometheus |
| Trazas distribuidas | ninguna | — | — |
| Alertas | vigilante propio por correo (`dentissa:vigilar`, cada minuto) y UptimeRobot (spec 032, ADR 0007) | en uso en producción | `app/Avisos/`, `deploy/preparar-vigilancia.sh`, `deploy/README.md` §12 |
| Health check | `/up` de Laravel (no comprueba BD) | en uso | `bootstrap/app.php`; `deploy/verificar.sh` lo comprueba tras cada despliegue, junto a la configuración aplicada, las cabeceras y la red privada (spec 031) |
| Visor de logs en local | `laravel/pail` | presente (require-dev), no se usa | `composer.json` |

## Logs
- Formato: texto de línea de Monolog (no JSON). Objetivo: estructurado JSON con campos
  obligatorios `timestamp`, `level`, `request_id`, `actor_id` (si hay sesión), `message`, `context`.
- Nivel por entorno: local `debug` (confirmado por el usuario, 2026-09-28: igual que
  `.env.example`) · staging `info` · producción `info` (decisión del usuario, 2026-09-28).
- Destino y retención: local `storage/logs/laravel.log` sin rotación. Producción (spec 030):
  `storage/logs/laravel.log` en el volumen `storage` y los logs de nginx en el volumen
  `nginx_logs` (`dentissa-access.log`, `dentissa-error.log`), sin rotación todavía; nivel `info`
  y `APP_DEBUG=false`, comprobados por `deploy/verificar.sh` en cada despliegue. Staging: no existe.
- Qué **nunca** se registra: datos personales (nombre, email, teléfono, dirección, fecha de
  nacimiento), datos de salud (alergias, diagnóstico, tratamiento, receta, notas), contraseñas y
  contraseñas generadas, tokens de sesión y OAuth, secretos, cuerpos completos de petición y
  valores enlazados de consultas SQL. Cómo se enmascara: TODO(init) (propuesto: registrar solo
  IDs de recurso y redactar los bindings de `QueryException` en `withExceptions`).

## Correlación
- Identificador por petición: cabecera `X-Request-Id`; se acepta del cliente si es un UUID válido
  o se genera; se devuelve en la respuesta y se añade con `Context::add('request_id', …)` al
  contexto de todos los logs y eventos de auditoría de la petición (y de los trabajos en cola, si
  llega a haberlos). Hoy no existe.

## Registro de auditoría
Separado de los logs técnicos: responde **quién hizo qué, sobre qué, cuándo y con qué resultado**.
Eventos obligatorios (decisión del usuario, 2026-09-28):

| Evento | Obligatorio | Campos |
|---|---|---|
| Login exitoso y fallido (correo y Google) | sí | actor (o email intentado), método, IP, resultado, fecha, request_id |
| Logout | sí | actor, fecha |
| Acceso denegado (403) | sí | actor, acción, recurso, fecha |
| Lectura de datos sensibles (ficha de paciente, expediente) | sí | actor, recurso, fecha |
| Cambio de datos sensibles (paciente, expediente, notas de cita, estado de cita) | sí | actor, recurso, campos cambiados (sin valores), fecha |
| Cambio de permisos o roles | sí | actor, sujeto, antes → después, fecha |
| Alta, activación/desactivación de cuentas y cambio de contraseña | sí | actor, sujeto, acción, fecha |
| Lectura del propio registro de auditoría | sí | actor, filtro consultado, fecha |

- Almacenamiento: tabla propia en MariaDB (propuesta `auditoria`), **solo anexado**: la
  aplicación solo inserta; nadie, tampoco un administrador, modifica ni borra entradas desde la
  aplicación. Sin `ON DELETE CASCADE` desde tablas de negocio.
- Retención: 5 años (decisión del usuario, 2026-09-28; base: conservación mínima del expediente
  clínico según NOM-004-SSA3 como referencia académica). Purga programada de lo que supere la
  retención.
- Quién puede leerlo: solo rol admin (decisión del usuario, 2026-09-28); cada lectura del
  registro también se audita.

## Métricas y alertas
Producción tiene alertas por correo desde la spec 032 (OB6.b); sin métricas ni paneles. Staging no
existe (OB6.c). Como mucho un correo por tipo de aviso cada 30 minutos, con lo acumulado; los correos
no llevan datos personales, IPs, correos de cuentas ni secretos.

| Métrica / alerta | Umbral | Canal |
|---|---|---|
| Tasa de errores 5xx (log de acceso de nginx) | > 1 % en 5 min | correo a `AVISOS_CORREO` desde la cuenta de envío (spec 032) |
| Logins fallidos por origen o cuenta (canal `seguridad`) | > 10 en 5 min | correo (spec 032) |
| Log de acceso con otro formato | ≥ 20 líneas en 5 min con más de la mitad sin el formato, o 5 seguidas sin ninguna buena | correo (spec 032) |
| Caída vista desde fuera (`/up`) | 5 min sin responder, también con el servidor apagado; y la vuelta | correo de UptimeRobot (spec 032) |
| Latencia p95 | > 1 s en 10 min | pendiente (roadmap) |
| Cola de trabajos atascada | no aplica (no hay jobs) | — |

El vigilante lee el log de acceso en formato `combined`: si un cambio de `log_format` (OB8.a) lo
cambia, hay que ajustar `App\Avisos\Vigilante`; mientras tanto, el aviso de formato lo detecta.

## Brechas
<!-- Cada brecha con ID `OB<n>`, prioridad, correcciones `OB<n>.<letra>` y enlace al roadmap. -->

### OB1 · Alta — No existe registro de auditoría
Ningún login, 403, lectura o cambio de datos clínicos, cambio de rol ni cambio de cuenta queda
registrado. `ExpedientesController@update` sobrescribe diagnóstico y receta sin guardar quién ni
cuándo. → roadmap objetivo 3.

Correcciones:
- OB1.a Tabla de auditoría solo-anexado con actor, acción, recurso, resultado, fecha, IP y request_id — estado: en curso (spec 020)
- OB1.b Listeners de eventos de autenticación (Login, Failed, Logout) incluido el acceso con Google — estado: pendiente
- OB1.c Registrar los accesos denegados (403) de middleware, controladores y Policies — estado: en curso (spec 020)
- OB1.d Registrar lecturas de ficha de paciente y de expedientes — estado: pendiente
- OB1.e Registrar cambios de pacientes, expedientes, notas y estado de citas (campos, sin valores) — estado: pendiente
- OB1.f Registrar altas, cambios de rol, activación/desactivación y cambios de contraseña — estado: pendiente
- OB1.g Retención de 5 años con purga programada (`routes/console.php`) — estado: en curso (spec 020)
- OB1.h Pantalla de consulta solo para admin que audita su propia lectura — estado: en curso (spec 020)

### OB2 · Media — Sin identificador de correlación por petición
No hay `X-Request-Id` ni contexto compartido en los logs. → roadmap objetivo 3.

Correcciones:
- OB2.a Middleware que acepta o genera `X-Request-Id` y lo devuelve en la respuesta — estado: en curso (spec 020)
- OB2.b Añadir `request_id` y `actor_id` al contexto de logs y auditoría (`Context::add`) — estado: en curso (spec 020)

### OB3 · Media — Logs sin uso y sin configuración por entorno
El código no registra nada propio; la configuración es la de fábrica (texto, `single`, sin
rotación). → roadmap objetivo 3.

Correcciones:
- OB3.a Configuración por entorno: formato JSON, nivel `info` y canal `daily`/`stderr` en staging y prod — estado: pendiente
- OB3.b Registrar con contexto (IDs, sin PII) los errores de negocio relevantes: conflicto de agenda, fallos de OAuth, transacciones fallidas — estado: pendiente

### OB4 · Media — Riesgo de datos sensibles en logs por excepciones de BD
Una `QueryException` no capturada incluye el SQL con los valores enlazados; en los inserts y
updates de pacientes, expedientes y usuarios eso volcaría datos clínicos y hashes en
`laravel.log`. `withExceptions` está vacío. → roadmap objetivos 3 y 5. La spec 030 abre producción
con OB4.a pendiente (residuo aceptado por el usuario, /analyze 030 A26: datos ficticios y logs solo
en el servidor; la 023 antes de tener datos reales) y comprueba la configuración en cada despliegue;
lo que pide staging queda pendiente (/analyze 030 A1: OB4.b se divide).

Correcciones:
- OB4.a Redactar los bindings de `QueryException` (y campos sensibles) en `bootstrap/app.php → withExceptions` — estado: pendiente
- OB4.b `APP_DEBUG=false` y `APP_ENV=production` verificados en el checklist de despliegue de producción — estado: mitigada (v0.5.0)
- OB4.c Lo mismo en el checklist de despliegue de staging — estado: pendiente (→ objetivo 5, cuando exista staging)

### OB5 · Media — `historial_citas` incompleto y duplicado
El trigger registra cambios de estado sin actor; con motivo, el controlador inserta una segunda
fila sin `estado_anterior`. Las cancelaciones (admin y cliente) y los cambios de notas no dejan
actor, y `ON DELETE CASCADE` borra el historial con la cita. → roadmap objetivo 3.

Correcciones:
- OB5.a Una sola escritura por cambio, desde la aplicación, con actor, estado anterior, nuevo y motivo (retirar o ajustar el trigger) — estado: mitigada (v0.3.0)
- OB5.b Registrar cancelaciones y cambios de notas internas con actor — estado: pendiente (cancelaciones con autor: spec 026; notas internas → spec 022)
- OB5.c Impedir el borrado en cascada del historial — estado: pendiente

### OB6 · Baja — Health check superficial y sin alertas
`/up` solo comprueba que la app arranca, no la base de datos; no hay métricas ni alertas. →
roadmap objetivos 3 y 5.

Correcciones:
- OB6.a Health check que compruebe la conexión a MariaDB — estado: pendiente
- OB6.b Alertas mínimas gratuitas (5xx, logins fallidos) en producción — estado: en curso (spec 032)
- OB6.c Alertas mínimas gratuitas en staging (creada por la 032 al dividir OB6.b) — estado: pendiente (→ objetivo 5, cuando exista staging)

### OB7 · Baja — Logs de nginx se pierden al recrear el contenedor
nginx escribe en `/var/log/nginx/` dentro del contenedor, sin volumen ni stdout. → roadmap objetivo 5.
En producción, la spec 030 los lleva al volumen `nginx_logs` (ensayado: se conservan tras reiniciar
el servidor); staging no existe (/analyze 030 A1: OB7.a se divide).

Correcciones:
- OB7.a Enviar los logs de nginx a stdout/stderr o a un volumen en producción — estado: mitigada (v0.5.0)
- OB7.b Lo mismo en staging — estado: pendiente (→ objetivo 5, cuando exista staging)

### OB8 · Media — Las búsquedas con datos personales y de salud quedan en el log de acceso de nginx
Detectado en la re-sincronización del 2026-10-18. Las búsquedas de pacientes (nombre, email, teléfono,
ciudad) y de expedientes (nombre, diagnóstico) son formularios `GET` con `?buscar=`. En producción,
nginx usa el formato `combined`, que guarda la línea de petición con su query string, en
`dentissa-access.log` (volumen `nginx_logs`) sin rotación: choca con la regla «Qué nunca se
registra». Evidencia: `docker/nginx/dentissa.prod.conf` (`access_log` sin `log_format` propio),
`PacientesController.php` y `ExpedientesController.php` (`index`).

Correcciones:
- OB8.a El log de acceso de producción no guarda el query string (o lo enmascara) — estado: pendiente (→ objetivo 3). Al hacerlo, ajustar `App\Avisos\Vigilante`, que lee el formato `combined` (spec 032, /analyze A36)
- OB8.b Rotación con retención definida de `dentissa-access.log`, `dentissa-error.log` y `laravel.log` en producción — estado: pendiente (→ objetivo 3)
- OB8.c Revisar en el servidor los registros ya guardados y purgar los que contengan búsquedas — estado: pendiente (la ejecuta el responsable en el servidor)

### OB9 · Baja — PHP de producción con la configuración por defecto y logs de php-fpm volátiles
Detectado en la re-sincronización del 2026-10-18. La imagen de producción no instala `php.ini-production`
(solo monta `local.ini`): con los valores por defecto, `zend.exception_ignore_args` está desactivado
y las trazas que guarda `laravel.log` pueden incluir valores de argumentos, otra vía de datos
sensibles además de OB4. Los logs de php-fpm van al stderr del contenedor `app`, sin volumen ni
límite de tamaño, y se pierden al recrearlo. Evidencia: `docker/prod/Dockerfile`,
`docker-compose.prod.yml`, `docker/nginx/php/local.ini`.

Correcciones:
- OB9.a La imagen de producción usa `php.ini-production` (`display_errors=Off`, `zend.exception_ignore_args=On`) — estado: pendiente (→ objetivo 5)
- OB9.b Los logs de php-fpm de producción se conservan al recrear el contenedor, con límite de tamaño — estado: pendiente (→ objetivo 5)

## Brechas por confirmar
- `laravel.log` y los logs de nginx crecerán sin rotación en producción (volúmenes `storage` y
  `nginx_logs`, spec 030) — depende de: el uso real — cómo confirmarla: vigilar el tamaño de los
  volúmenes tras la liberación y decidir `LOG_CHANNEL=daily` o rotación (roadmap objetivo 3).
- ~~La base de producción podría no tener el trigger `trg_historial_cita`~~ — resuelta: la spec 026
  retira el trigger con una migración idempotente y el historial lo escribe la aplicación.

## No determinado
- Obligación legal real de registro de accesos: el proyecto es académico con datos ficticios; se
  adopta NOM-024 como referencia (decisión del usuario, 2026-09-28).
