---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-18
spec_sha: e92f5b7bf3c7
plan_sha: d3f9beab25d1
tasks_sha: 7d9078d45ee8
---

# Análisis · 050 La hora de la clínica en todo el panel

## Resumen
`fail`: dos críticos de la constitución. La 050 modifica endpoints que no cumplen P3 (Gate por recurso, deuda
RS1) ni P7 (auditoría de la ficha y de los cambios de pacientes y expedientes, OB1.d/e), y el plan los da por
«➖» sin excepción, cuando la constitución exige ambos a todo endpoint modificado (precedente: plan de la 026).
Además: la próxima visita del expediente se guarda también por `update`, sin regla (A4); TM1 no prueba las
reglas de nacimiento del perfil, la edición y el registro (A5); `fecha` de «Agendar cita» del paciente llega sin
validar (A3); CA4 describe la agenda anterior a la 049 (A6); y el `max` de nacimiento cambiaba sin pedirlo (A7).
Análisis completo con un subagente independiente; cada hallazgo se verificó abriendo el archivo citado.
Conteo: CRÍTICA 2 · ALTA 5 · MEDIA 8 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA4 de caracterización y CA9 de regresión; CA10 parcial: A4, A5) |
| Amenazas con control y test | 1 / 1 (TM1 parcial: A4, A5; falta la de A3) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P3) | CRÍTICA | plan P3 (➖); tasks Constitution Check | Endpoints modificados sin Gate de recurso (`PacientesController::show/store/update`, `ExpedientesController::store/update`, `CitasController::create`, `PersonalController::show`, `ClienteCitasController::index/agendarForm/agendarStore`); RS1.a–d en curso → 014; el plan dice «no cambia quién ve qué» sin excepción | Decisión del usuario (2026-11-18): «❌ aceptado» con la lista de endpoints, RS1 → 014 (y RS2.a del grupo cliente) y la aprobación con fecha → `/plan --fix` |
| A2 | Constitución (P7) | CRÍTICA | plan P7 (➖) | Lectura de la ficha y cambios de pacientes y expedientes sin auditoría (OB1.d, OB1.e pendientes) en endpoints que la 050 modifica | Decisión del usuario (2026-11-18): «❌ aceptado» con OB1.d/e → su spec y la aprobación con fecha → `/plan --fix` |
| A3 | Seguridad (P4) | ALTA | `ClienteCitasController.php:298`; T023; modelo de amenazas | `fecha` de `GET /cliente/agendar` llega sin validar a `old('fecha', $fechaSugerida)`: con `?fecha[]=x`, 500 | Nueva amenaza: una `fecha` sugerida manipulada o no escalar se ignora y la página responde 200 (test en T013) → `/plan --fix`, `/tasks --fix` |
| A4 | Cobertura (CA10/TM1) | ALTA | `ExpedientesController.php:201` (`update`: `nullable|date`); plan; T027; T013 | La ficha guarda la próxima visita por `PUT update`, sin `after`: tras la 050 seguiría aceptando fechas pasadas | El servidor rechaza una próxima visita del 17 por el alta y por la ficha (test: POST y PUT en T013) → `/plan --fix`, `/tasks --fix` |
| A5 | Cobertura (TM1/P2) | ALTA | T013; T028; plan TM1 | Las reglas de nacimiento de la edición de paciente, el perfil y los dos registros cambian sin test que falle antes | T013 acepta el 16 y rechaza el 17, enviados a mano, en la edición de paciente, el perfil y los dos registros → `/tasks --fix` |
| A6 | Inconsistencia (CA4/049) | ALTA | spec CA4, «Fuera de alcance», Decisión de la 049, Notas; plan Estrategia y Trazabilidad; T012; Cobertura | CA4 pide el filtro con fecha, «Hoy», «Esta semana» y la vista de próximos días, que la 049 retiró | Decisión del usuario (2026-11-18): CA4 reescrito sobre el calendario de la 049 → `/specify --edit`, `/plan --fix`, `/tasks --fix` (CA4 = caracterización) |
| A7 | Inconsistencia | ALTA | `register.blade.php:241`, `registro-contacto.blade.php:86`, `cliente/perfil.blade.php:61`, `pacientes/create.blade.php:80`; plan; T013, T024, T028, T029 | El `max` de nacimiento («hace un año») pasaba a «ayer» sin pedirlo | Decisión del usuario (2026-11-18): se mantiene «hace un año», contado desde el día de la clínica (test: `max` 2025-11-17) → `/plan --fix`, `/tasks --fix` |
| A8 | Inconsistencia (extends) | MEDIA | spec `extends` | Faltan 010 (alta del usuario), 038 (año del pie), 039 (consentimiento) y 049 (agenda y «Nueva cita») | → `/specify --edit` |
| A9 | Inconsistencia (plan > spec) | MEDIA | plan D6; T014, T032; spec CA7 y Diseño | La galería entra por el plan sin estar en la spec | CA7 y Diseño con el consentimiento de la galería; fila en Decisiones → `/specify --edit` |
| A10 | Cobertura | MEDIA | `pacientes/show.blade.php:56` (`->age`) | La edad se calcula con `now()` UTC | Decisión del usuario (2026-11-18): entra; la edad se calcula con el día de la clínica (test en T011) → `/plan --fix`, `/tasks --fix` |
| A11 | Constitución (P2) | MEDIA | T013; `expedientes/show.blade.php:152` | El `min` de la ficha del expediente a las 21:50 ya es el 18 con el código actual: no falla antes | Comprobarlo también a una hora diurna de la clínica (10:00) → `/tasks --fix` |
| A12 | Constitución (P2) | MEDIA | T022, T023, T025 | Consejo del día, fecha sugerida desde la próxima visita y fecha de la tarjeta de agenda de la dentista cambian sin test | Un caso de test para cada uno; T025 compara la próxima visita con «posterior a hoy» → `/tasks --fix` |
| A13 | Cobertura (CA5) | MEDIA | `expedientes/show.blade.php:168` (`diffInDays` con signo, Carbon 3); T013, T027 | Hoy toda próxima visita futura sale «Próxima semana»; el test solo prueba una cercana | Una del 25 (8 días) no sale cercana (test en T013) → `/tasks --fix` |
| A14 | Cobertura (RNF) | MEDIA | plan RNF; T092 | El grep no ve `date(`, `isPast/isFuture/isToday`, `->age` ni `parse($x->creado_en)`; excepciones incompletas | Patrón ampliado y excepciones enumeradas (escrituras de `creado_en`/`actualizado_en`, vencimiento del enlace de recuperación) → `/plan --fix`, `/tasks --fix` |
| A15 | Inconsistencia (P6/Rollout) | MEDIA | plan P6 y Rollout; tasks Constitution Check, T092 | Las 6 altas de `picomatch` quedaron como EX5 (vence 2026-12-31) y la 049 ya está en v0.21.0; T092 no lista los audits | P6 con EX5; Rollout actualizado; `composer audit` y `npm audit` en T092 → `/plan --fix`, `/tasks --fix` |
| A16 | Ambigüedad | BAJA | spec CA1; `dashboard/admin.blade.php:26` | El panel de la administración muestra «17 Nov», no «martes 17 de noviembre» | CA1: la fecha del encabezado en el formato de cada panel → `/specify --edit` |
| A17 | Ambigüedad | BAJA | T027; `expedientes/show.blade.php:25, 186` | Dos fechas de alta en la ficha del expediente | Nombrar las dos → `/tasks --fix` |
| A18 | Inconsistencia | BAJA | T028 (`Registro*Test`) | El registro se prueba en `LimiteIntentosTest`, `TerminosPrivacidadTest` y `ContrasenaDistintaTest` | Nombrar los tests reales → `/tasks --fix` |
| A19 | Cobertura (CA9) | BAJA | T016, T092; plan Estrategia | Faltan `AvisoProduccionTest` y `ContrasenaDistintaTest` (correos) en la línea base | Añadirlos → `/plan --fix`, `/tasks --fix` |
| A20 | Redacción | BAJA | spec Notas (`HistorialCitaTest.php:88`), plan (`EstadosCitaVistasTest.php:119`); `config/clinica.php`; `docs/deployment.md:226`; T012 | Referencias de línea desfasadas; el comentario de la configuración y deployment.md describen la zona solo para la 026; T012 no dice qué aserciones distinguen la zona | Referencias corregidas; T090/T091 incluyen el comentario y la fila; T012 nombra las aserciones que fallan con UTC → `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Resueltas el 2026-11-18: A1 y A2 (excepciones aceptadas, como en la 026), A7 (se mantiene «hace un año»), A10 (la
edad entra) y A6, A8, A9 (se corrige la spec).

## Aceptados
Ninguno: todos se corrigen con `/specify --edit`, `/plan --fix` y `/tasks --fix` antes de la ronda 2.
