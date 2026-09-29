---
id: 020
slug: auditoria-base
status: approved
created: 2026-09-28
extends: []
---

# 020 · Registro de auditoría base y correlación por petición

## Problema
Hoy el sistema no deja rastro de nada: ni de quién abre una ficha clínica, ni de quién intenta
entrar donde no debe, ni de quién cambia un rol. Con datos de salud, eso impide responder "quién
hizo qué, sobre qué, cuándo y con qué resultado", que es lo que exige la constitución (P7) y el
marco de referencia del expediente clínico. Tampoco hay forma de relacionar los registros técnicos
de una misma petición. Esta spec crea el registro de auditoría común, inmodificable y consultable
solo por el admin, y el identificador de petición que lo enlaza con los registros técnicos; otras
specs (014, 021, 022) lo usan para registrar sus eventos. Atiende OB1 **parcialmente** (los
eventos concretos de autenticación, cuentas y datos clínicos van en 021 y 022) y OB2 por completo.

## Historias de usuario
- Como admin, quiero consultar quién hizo qué y cuándo, para investigar un acceso indebido.
- Como responsable del proyecto, quiero que nadie pueda alterar ni borrar ese registro, para que sea evidencia fiable.
- Como integrante del equipo, quiero que todos los registros de una misma petición compartan un identificador, para reconstruir lo que pasó.
- Como paciente, quiero que nadie pueda ver mis datos clínicos sin dejar rastro.

## Criterios de aceptación
- [ ] CA1 · Dada una acción auditable, cuando ocurre, entonces queda un evento con: actor (o el identificador intentado si no hay sesión), acción, tipo e identificador del recurso, resultado, fecha y hora, dirección de origen e identificador de la petición.
- [ ] CA2 (abuso) · Dado cualquier usuario, incluido un admin, cuando intenta modificar o borrar un evento, entonces la aplicación no ofrece ninguna forma de hacerlo y el almacén rechaza la modificación o el borrado de cualquier evento, de uno en uno o de muchos a la vez, venga de donde venga, incluidos los intentos hechos con las credenciales con que opera la propia aplicación, salvo la purga por retención (CA5). Solo quedan fuera de este criterio las operaciones hechas con privilegios totales sobre el almacén que lo vacían entero de una vez, desactivan su protección o cambian su estructura (ver Supuestos).
- [ ] CA3 · Dado cualquier evento, cuando se registra, entonces no contiene datos personales ni de salud en claro, salvo la dirección de origen y el identificador intentado de CA1 (ver Supuestos): solo identificadores y, en los cambios, los nombres de los campos cambiados sin sus valores.
- [ ] CA4 · Dado un usuario autenticado, cuando el sistema le deniega una acción (por ejemplo, entrar al área de otro rol), entonces queda un evento de acceso denegado con la acción y el recurso solicitados.
- [ ] CA5 · Dados eventos de más de 5 años, cuando se ejecuta la purga periódica, entonces se eliminan solo esos y los más recientes se conservan intactos.
- [ ] CA6 · Dado un admin, cuando consulta el registro, entonces puede filtrar por actor, acción, tipo de recurso, identificador de recurso y rango de fechas, con resultados paginados del más reciente al más antiguo.
- [ ] CA7 (abuso) · Dado un usuario que no es admin, cuando intenta consultar el registro, entonces se le deniega y no ve ningún evento.
- [ ] CA8 · Dada cualquier petición, cuando se responde, entonces la respuesta lleva un identificador de petición: si el cliente envió uno con formato válido se reutiliza; si no, se genera uno nuevo.
- [ ] CA9 (abuso) · Dado un cliente que envía un identificador de petición malformado o excesivamente largo, cuando se procesa la petición, entonces se descarta y se usa uno generado; el valor enviado nunca se refleja ni se registra tal cual.
- [ ] CA10 · Dada una petición que produce registros técnicos o eventos de auditoría, cuando se consultan, entonces todos llevan el mismo identificador de petición y, si hay sesión, el usuario.
- [ ] CA11 · Dada una acción sobre datos clínicos o una consulta del registro de auditoría, cuando su evento no puede escribirse, entonces la acción no se realiza y el usuario ve un error genérico sin detalles internos.
- [ ] CA13 (abuso) · Dado un cliente que envía un identificador de petición válido ya usado en otra petición (por ejemplo, copiado de otra respuesta), cuando se procesa la petición, entonces se acepta, pero cada evento conserva su propia identidad, actor, fecha y dirección de origen, y el identificador repetido nunca hace que un evento reciba el actor ni el acceso de la otra petición.

## Fuera de alcance
- Eventos de login, logout, acceso con Google, altas de cuentas, cambios de rol y de contraseña (spec 021).
- Eventos de lectura y cambio de pacientes, expedientes y citas, y el historial de citas completo (spec 022; las lecturas de la 014 ya usan este registro).
- Formato de los logs técnicos por entorno, redacción de errores de base de datos y health check (spec 023).
- Exportar el registro, enviarlo a un servicio externo y alertas.

## Seguridad y privacidad
- Datos sensibles involucrados: el propio registro es información restringida. Contiene identificadores de personas y recursos clínicos, la dirección de origen y, sin sesión, el identificador intentado (ambos datos personales), pero ningún dato de salud (CA3).
- Quién puede hacer qué: solo el admin consulta el registro. Nadie lo modifica ni lo borra, salvo la purga automática por retención y la limitación aceptada de CA2 (ver Supuestos).
- Casos de abuso:
  - Como admin malintencionado, intento borrar el rastro de un acceso → no hay forma en la aplicación y el almacén lo rechaza (CA2), salvo vaciarlo entero, desactivar su protección o cambiar su estructura con privilegios totales sobre el almacén (limitación aceptada, ver Supuestos).
  - Como personal sin permiso, intento leer el registro para ver qué pacientes consultan otros → se deniega y queda registrado (CA7, CA4).
  - Como atacante, envío identificadores de petición malformados o demasiado largos para inyectar contenido → se descartan (CA9).
  - Como atacante, repito un identificador de petición válido (copiado de otra respuesta) para confundir la trazabilidad → se acepta como riesgo residual: el identificador solo sirve para correlacionar; cada evento conserva su identidad, actor, fecha y dirección de origen, y nunca recibe el actor ni el acceso de otra petición (CA13).
  - Como atacante, provoco fallos del registro para acceder a datos clínicos sin dejar rastro → la acción se deniega (CA11).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| OB1.a — almacén solo-anexado con actor, acción, recurso, resultado, fecha, IP y request_id | dentro | CA1, CA2, CA3 |
| OB1.b — eventos de autenticación y acceso con Google | fuera | eventos concretos de acceso → spec 021 |
| OB1.c — accesos denegados | dentro | CA4 |
| OB1.d — lecturas de ficha y expedientes | fuera | spec 014 (CA12) y spec 022 |
| OB1.e — cambios de pacientes, expedientes y citas | fuera | spec 022 |
| OB1.f — altas, roles, activación y contraseñas | fuera | spec 021 |
| OB1.g — retención de 5 años con purga programada | dentro | CA5, CA12 |
| OB1.h — consulta solo admin que audita su propia lectura | dentro | CA6, CA7, CA12 |
| OB2.a — identificador de petición aceptado o generado y devuelto | dentro | CA8, CA9, CA13 |
| OB2.b — identificador y actor en el contexto de logs y auditoría | dentro | CA10 |

## Auditoría
- [ ] CA12 · Dada una consulta del registro por un admin o una ejecución de la purga, cuando ocurre, entonces queda registrado quién consultó y con qué filtros, o cuántos eventos purgó la purga y hasta qué fecha.

## Requisitos no funcionales
- Registrar un evento no añade más de 50 ms al tiempo de respuesta de la acción en local.
- La consulta del registro con filtros responde en menos de 2 s con 100.000 eventos.

## Preguntas abiertas

## Supuestos
- La dirección de origen se guarda porque la exige el evento de acceso; es dato personal y se protege como el resto del registro.
- El fallo cerrado (CA11) aplica a acciones sobre datos clínicos y a la consulta del registro (decisión del usuario, 2026-09-28). Para los demás eventos (denegaciones, accesos), si el registro falla la acción sigue su curso y el fallo queda en el log técnico.
- La inmutabilidad se garantiza en la aplicación y en el almacén (decisión del usuario, 2026-09-28).
- Limitación aceptada de CA2: quien tenga privilegios totales sobre el almacén, incluidas las credenciales con que opera la aplicación, puede vaciarlo entero o desactivar su protección. CA2 cubre la modificación y el borrado de eventos, no esa operación; es coherente con el ADR 0004 (decisión del usuario, 2026-09-28). CA2 incluye los intentos hechos con las credenciales de la aplicación y el borrado de muchos eventos a la vez; solo quedan fuera vaciar el almacén entero de una vez o desactivar su protección (decisión del usuario, 2026-09-28). La exclusión abarca también cualquier cambio de estructura del almacén hecho con esos privilegios, por ejemplo renombrarlo o modificar sus campos (decisión del usuario, 2026-09-28).
- Reutilizar un identificador de petición válido enviado por el cliente se acepta como riesgo residual: solo correlaciona registros y no es prueba de identidad (decisión del usuario, 2026-09-28). Va en su propio criterio (CA13), y "distinguibles" significa que cada evento conserva su identidad, actor, fecha y dirección de origen y nunca recibe el actor ni el acceso de otra petición (decisión del usuario, 2026-09-28).
- El identificador intentado (por ejemplo, el correo escrito en un inicio de sesión fallido) y la dirección de origen son datos personales: son las únicas excepciones a CA3 y se protegen como el resto del registro. El tratamiento concreto del identificador intentado lo define la spec 021.
- El filtro por actor de CA6 es por identificador del usuario, no por nombre ni correo, para que la consulta registrada en CA12 no guarde datos personales (decisión del usuario, 2026-09-28).
- La purga se ejecuta una vez al mes; requiere que cada entorno ejecute las tareas programadas.
- El objetivo 3 se divide en las specs 020–023 y esta se crea primero (decisión del usuario, 2026-09-28).

## Notas para /plan
- La spec 014 consume este registro para CA11 (accesos denegados desde sus Gates, vía `Gate::after`) y CA12 (lecturas en `PacientesController@show` y `ExpedientesController@show`); su plan asume un punto de registro único, algo como `Auditoria::registrar(accion, recurso_tipo, recurso_id, resultado, contexto)`. Si la API difiere, corregir con `/plan 014 --fix`.
- Almacén propuesto en `docs/observability.md`: tabla propia en MariaDB (`auditoria`), sin claves foráneas en cascada hacia tablas de negocio. Inmutabilidad en la base: trigger que rechace `UPDATE`/`DELETE` salvo la purga, o un usuario de BD sin esos permisos; decidir en el plan.
- Formato válido del identificador de petición propuesto: UUID.
- Correlación: middleware en `bootstrap/app.php` con `Context::add('request_id', …)` y cabecera `X-Request-Id` en la respuesta.
- Denegaciones generales: `RoleMiddleware` (`abort(403)`) y los `abort(403)` sueltos de los controladores.
- Purga: comando programado en `routes/console.php`; hoy no hay scheduler en Docker Compose.
- La tabla nueva se crea con migración aunque el resto del esquema siga en el volcado (spec 013).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Creación | /specify, objetivo 3 del roadmap (OB1, OB2) |
| 2026-09-28 | Aprobada por el usuario | /specify |
| 2026-09-28 | CA2 acota la inmutabilidad y los Supuestos declaran su limitación; CA3 nombra sus excepciones (dirección de origen, identificador intentado); CA9 y los casos de abuso aceptan como residual la repetición de un identificador válido; el caso de abuso de consulta cita CA4; supuesto del filtro por actor. Vuelve a `draft`. | /analyze 020 ronda 1: A01, A10, A11, A25, A27 (decisión del usuario, 2026-09-28) |
| 2026-09-28 | Aprobada de nuevo por el usuario | /specify --edit |
| 2026-09-28 | CA2 cubre también los intentos con las credenciales de la aplicación y el borrado masivo; solo quedan fuera vaciar el almacén o desactivar su protección. El identificador válido repetido pasa de CA9 a un criterio propio, CA13 (abuso), con "distinguibles" concretado. "Quién puede hacer qué" remite a la limitación y "Datos sensibles" nombra el identificador intentado. Vuelve a `draft`. | /analyze 020 ronda 2: A29, A30, A31 (decisión del usuario, 2026-09-28) |
| 2026-09-28 | Aprobada de nuevo por el usuario | /specify --edit |
| 2026-09-28 | La exclusión de CA2 abarca también los cambios de estructura del almacén hechos con privilegios totales; el caso de abuso del admin y los Supuestos lo recogen. Vuelve a `draft`. | /analyze 020 ronda 3: A39 (decisión del usuario, 2026-09-28) |
| 2026-09-28 | Aprobada de nuevo por el usuario | /specify --edit |
