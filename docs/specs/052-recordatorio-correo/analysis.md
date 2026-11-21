---
result: pass
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-21
spec_sha: 69b212862b98
plan_sha: 61861c7457fa
tasks_sha: d732163cad13
---

# Análisis · 052 Recordatorio de cita por correo

## Resumen
`pass`: sin hallazgos críticos ni altos; ninguna violación de la constitución. Un hallazgo pidió una decisión
del usuario (A1, resuelta) y los demás no cambian el diseño: el usuario los acepta como notas obligatorias de
sus tareas (2026-11-21). El subagente independiente (perfil `critical`) se cortó por el límite de uso de la
cuenta; por decisión del usuario («continua») el análisis lo hizo el coordinador releyendo desde cero spec,
plan, tareas, constitución y el código citado (menos independiente: el coordinador escribió el plan).
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 3 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 8 / 8 |
| Principios de la constitución evaluados | 10 / 10 |

Comprobado en el código: la única escritura de `citas.fecha_hora` tras crear la cita es
`ReprogramacionCitas.php:123` (`ReservaCitas.php:159` la crea; `HorarioDentista.php:85` solo la lee en un
arreglo); `citas.actualizado_en` tiene `ON UPDATE current_timestamp()` y `recordatorio_enviado` es
`tinyint(1) NOT NULL DEFAULT 0` (`database/dentissa.sql`); `show.blade.php:148–153` ya muestra
«Recordatorio · Enviado» con la marca a 1; `route('cliente.citas')` existe dentro del grupo `auth`
(`routes/web.php:201`); `cache_locks` existe (`0001_01_01_000001_create_cache_table.php`) para
`withoutOverlapping`, y `CACHE_STORE=database` en producción (`docs/deployment.md:232`) para el `RateLimiter`;
existen `Tests\Support\CapturaLogs`, `RelojClinica::momentoLocal()` y `ahora()`. La 054 (rama sin fusionar)
no toca ningún archivo de código de la 052: solo coinciden `AGENTS.md`, `docs/*.md` y `docs/specs/README.md`
(choques triviales en T001); su migración `correo_opcional_en_usuarios` hace `usuarios.email` nulo, que la
selección de la 052 excluye.

Constitución, por principio: P1 ✅ (T001 antes del código); P2 ✅ (tests antes, caracterizaciones con rotura
anotada); P3 ➖ (sin endpoints); P4 ✅ (Query Builder; semgrep en T092); P5 ✅ (solo el nombre de la variable;
gitleaks); P6 ✅ (sin dependencias); P7 ➖ (spec: no aplica); P8 ✅ con A2 (registros con contadores; test en
T011); P9 ✅ (valores de tokens con test, como la 041, y `evaluate.mjs` en T022); P10 ➖.

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Ambigüedad | MEDIA | `design.md` (`c2`: «Dra. Melissa López»); `plan.md` Contratos; T012, T021 | El candidato escribe «Dra.», pero la app no guarda el género ni el título del personal: unas vistas ponen «Dr.» a todos (`pacientes/show.blade.php:103`, `expedientes/show.blade.php:32`) y la lista de citas, nombre y apellido sin título (`CitasController.php:45`). `/implement` tendría que decidirlo | Decisión del usuario: nombre y apellido del dentista, sin título (test: el correo dice «Con» + nombre y apellido y no contiene «Dr.» ni «Dra.») |
| A2 | Constitución (P8) / Seguridad | MEDIA | `plan.md` D2, TM8; T011, T023 | El `try/catch` por cita cubre el envío; si falla una consulta del reclamo o de la liberación (p. ej. un bloqueo 1213 con una reprogramación), la `QueryException` sin capturar se registraría con sus valores enlazados, entre ellos la `fecha_hora` de la cita | Todo error dentro del tratamiento de una cita (reclamo, envío, liberación) se captura y deja solo `recordatorio.correo_fallido` con la clase del error, y la revisión sigue con las demás (test: forzar un error en el reclamo y comprobar que el log no contiene la fecha ni el id de la cita) |
| A3 | Ambigüedad | MEDIA | T011 (`test_dos_revisiones_no_envian_dos_veces`) | No dice cómo se simula que otra revisión reclama la cita entre la lectura y el reclamo | El reclamo es una operación que se puede probar sola: con la cita ya reclamada por otra revisión (marca a 1 tras la lectura), el segundo reclamo no afecta a ninguna fila y no se envía correo (test en T011 sin hilos ni esperas) |
| A4 | Ambigüedad | BAJA | `plan.md` Observabilidad; T023 | `recordatorio.tope_diario` «una vez al día» sin decir cómo se recuerda que ya se avisó | Una sola línea por día de la clínica aunque la revisión corra cada minuto (test en T011: dos revisiones con el tope alcanzado dejan una sola línea) |
| A5 | Ambigüedad | BAJA | `plan.md` D11; T022 | No dice cómo se obtiene el HTML del correo real para `evaluate.mjs` | El HTML de `RecordatorioCita` renderizado con una cita de prueba (con y sin servicio) se guarda en `.ai/inspect/`, con el logo apuntando a `/public/images/logo-main.png` para `servir.mjs`, y se mide como los candidatos |
| A6 | Ambigüedad | BAJA | T095, T097 | La cita de prueba en producción y el estado del interruptor después de comprobarla no están fijados | La cita de prueba es de una cuenta de paciente de prueba con un correo del usuario, sin datos reales, y se cancela al terminar; tras la comprobación, el interruptor queda como decida el usuario y se anota |
| A7 | Proceso (agente) | BAJA | T090 | `AGENTS.md` es ruta protegida para el agente (`shared/agent-security.md` §4) | Se edita solo en T090, tarea visible de este `tasks.md` aprobado, y solo el «Mapa del repositorio» |

## Decisiones pendientes del usuario
Resueltas el 2026-11-21:
- A1: nombre y apellido del dentista, sin título.
- A2–A7: notas obligatorias de sus tareas, sin otra vuelta de plan y tareas.

## Aceptados
Aceptados por el usuario el 2026-11-21:
- **A1** → nota de T012, T021: el dentista se escribe con nombre y apellido, sin «Dr.» ni «Dra.» (decisión del usuario); test que lo comprueba.
- **A2** → nota de T011, T023: cualquier error al tratar una cita (reclamo, envío o liberación) se captura, deja solo `recordatorio.correo_fallido` con la clase y la revisión sigue; test que fuerza un error en el reclamo y comprueba que el log no lleva la fecha ni el id de la cita.
- **A3** → nota de T011: la carrera de CA3 se prueba con la cita ya reclamada tras la lectura; el segundo reclamo no afecta a ninguna fila y no sale correo.
- **A4** → nota de T011, T023: `recordatorio.tope_diario` una sola vez por día de la clínica (test con dos revisiones).
- **A5** → nota de T022: el HTML del Mailable renderizado y guardado en `.ai/inspect/`, con el logo en `/public/images/logo-main.png`, medido con `servir.mjs` y `evaluate.mjs`.
- **A6** → nota de T095, T097: cita de prueba de una cuenta de paciente de prueba con un correo del usuario, cancelada al terminar; el estado final del interruptor lo decide el usuario y se anota.
- **A7** → nota de T090: `AGENTS.md` (ruta protegida) solo en esta tarea visible y solo el «Mapa del repositorio».
