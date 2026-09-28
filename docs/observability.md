---
status: inferred
updated: 2026-09-28
---

# Observabilidad y auditoría de Dentissa

Estado de cada capacidad: **presente** (instalada o declarada) · **configurada** (configurada para
este proyecto) · **en uso** (el código o la operación la ejercitan). Solo "en uso" cuenta como
cubierta.

## Resumen
| Capacidad | Herramienta | Estado | Evidencia |
|---|---|---|---|
| Logs de aplicación | Monolog (Laravel), canal `stack` → `single` | presente (config de fábrica); el código no escribe ni un log: 0 llamadas a `Log::`/`logger()`/`report()` | `config/logging.php`, grep en `app/` |
| Agregación de logs | ninguna | — | `laravel.log` en el directorio del proyecto montado; logs de nginx dentro del contenedor sin volumen |
| Correlación por petición | ninguna | — | `bootstrap/app.php` sin middleware ni `Context::add` |
| Registro de auditoría | ninguno (`historial_citas` es historial de negocio de estados de cita) | — | `database/dentissa.sql` (trigger `trg_historial_cita`), `CitasController::updateEstado` |
| Métricas | ninguna | — | `composer.json` sin Pulse/Telescope/Prometheus |
| Trazas distribuidas | ninguna | — | — |
| Alertas | ninguna (canal `slack` definido sin webhook) | presente | `config/logging.php` |
| Health check | `/up` de Laravel (no comprueba BD) | en uso | `bootstrap/app.php` |
| Visor de logs en local | `laravel/pail` | presente (require-dev), no se usa | `composer.json` |

## Logs
- Formato: texto de línea de Monolog (no JSON). Objetivo: estructurado JSON con campos
  obligatorios `timestamp`, `level`, `request_id`, `actor_id` (si hay sesión), `message`, `context`.
- Nivel por entorno: local `debug` (confirmado por el usuario, 2026-09-28: igual que
  `.env.example`) · staging `info` · producción `info` (decisión del usuario, 2026-09-28).
- Destino y retención: local `storage/logs/laravel.log` sin rotación. Staging/prod: TODO(init)
  según plataforma (propuesto `stderr` si es PaaS o `daily` con 14 días en VPS).
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
Sin staging ni producción todavía; umbrales propuestos para cuando existan:

| Métrica / alerta | Umbral | Canal |
|---|---|---|
| Tasa de errores 5xx | > 1 % en 5 min | TODO(init) (correo del equipo, gratuito) |
| Logins fallidos por IP o cuenta | > 10 en 5 min | TODO(init) |
| Latencia p95 | > 1 s en 10 min | TODO(init) |
| Cola de trabajos atascada | no aplica (no hay jobs) | — |

## Brechas
<!-- Cada brecha con ID `OB<n>`, prioridad, correcciones `OB<n>.<letra>` y enlace al roadmap. -->

### OB1 · Alta — No existe registro de auditoría
Ningún login, 403, lectura o cambio de datos clínicos, cambio de rol ni cambio de cuenta queda
registrado. `ExpedientesController@update` sobrescribe diagnóstico y receta sin guardar quién ni
cuándo. → roadmap objetivo 3.

Correcciones:
- OB1.a Tabla de auditoría solo-anexado con actor, acción, recurso, resultado, fecha, IP y request_id — estado: pendiente
- OB1.b Listeners de eventos de autenticación (Login, Failed, Logout) incluido el acceso con Google — estado: pendiente
- OB1.c Registrar los accesos denegados (403) de middleware, controladores y Policies — estado: pendiente
- OB1.d Registrar lecturas de ficha de paciente y de expedientes — estado: pendiente
- OB1.e Registrar cambios de pacientes, expedientes, notas y estado de citas (campos, sin valores) — estado: pendiente
- OB1.f Registrar altas, cambios de rol, activación/desactivación y cambios de contraseña — estado: pendiente
- OB1.g Retención de 5 años con purga programada (`routes/console.php`) — estado: pendiente
- OB1.h Pantalla de consulta solo para admin que audita su propia lectura — estado: pendiente

### OB2 · Media — Sin identificador de correlación por petición
No hay `X-Request-Id` ni contexto compartido en los logs. → roadmap objetivo 3.

Correcciones:
- OB2.a Middleware que acepta o genera `X-Request-Id` y lo devuelve en la respuesta — estado: pendiente
- OB2.b Añadir `request_id` y `actor_id` al contexto de logs y auditoría (`Context::add`) — estado: pendiente

### OB3 · Media — Logs sin uso y sin configuración por entorno
El código no registra nada propio; la configuración es la de fábrica (texto, `single`, sin
rotación). → roadmap objetivo 3.

Correcciones:
- OB3.a Configuración por entorno: formato JSON, nivel `info` y canal `daily`/`stderr` en staging y prod — estado: pendiente
- OB3.b Registrar con contexto (IDs, sin PII) los errores de negocio relevantes: conflicto de agenda, fallos de OAuth, transacciones fallidas — estado: pendiente

### OB4 · Media — Riesgo de datos sensibles en logs por excepciones de BD
Una `QueryException` no capturada incluye el SQL con los valores enlazados; en los inserts y
updates de pacientes, expedientes y usuarios eso volcaría datos clínicos y hashes en
`laravel.log`. `withExceptions` está vacío. → roadmap objetivos 3 y 5.

Correcciones:
- OB4.a Redactar los bindings de `QueryException` (y campos sensibles) en `bootstrap/app.php → withExceptions` — estado: pendiente
- OB4.b `APP_DEBUG=false` y `APP_ENV=production` verificados en el checklist de despliegue de staging y prod — estado: pendiente

### OB5 · Media — `historial_citas` incompleto y duplicado
El trigger registra cambios de estado sin actor; con motivo, el controlador inserta una segunda
fila sin `estado_anterior`. Las cancelaciones (admin y cliente) y los cambios de notas no dejan
actor, y `ON DELETE CASCADE` borra el historial con la cita. → roadmap objetivo 3.

Correcciones:
- OB5.a Una sola escritura por cambio, desde la aplicación, con actor, estado anterior, nuevo y motivo (retirar o ajustar el trigger) — estado: pendiente
- OB5.b Registrar cancelaciones y cambios de notas internas con actor — estado: pendiente
- OB5.c Impedir el borrado en cascada del historial — estado: pendiente

### OB6 · Baja — Health check superficial y sin alertas
`/up` solo comprueba que la app arranca, no la base de datos; no hay métricas ni alertas. →
roadmap objetivos 3 y 5.

Correcciones:
- OB6.a Health check que compruebe la conexión a MariaDB — estado: pendiente
- OB6.b Alertas mínimas gratuitas (5xx, logins fallidos) en staging y prod — estado: pendiente

### OB7 · Baja — Logs de nginx se pierden al recrear el contenedor
nginx escribe en `/var/log/nginx/` dentro del contenedor, sin volumen ni stdout. → roadmap objetivo 5.

Correcciones:
- OB7.a Enviar los logs de nginx a stdout/stderr o a un volumen en staging y prod — estado: pendiente

## Brechas por confirmar
- `laravel.log` crecerá sin rotación en staging/prod — depende de: `LOG_CHANNEL`/`LOG_STACK` de esos
  entornos, que aún no existen — cómo confirmarla: al definir la plataforma (roadmap objetivo 5).
- La base de producción podría no tener el trigger `trg_historial_cita` — depende de: cómo se cree
  el esquema en esos entornos — cómo confirmarla: al migrar el esquema (roadmap objetivo 1).

## No determinado
- Obligación legal real de registro de accesos: el proyecto es académico con datos ficticios; se
  adopta NOM-024 como referencia (decisión del usuario, 2026-09-28).
