---
result: fail
round: 1
mode: full
constitution_version: 1.0.0
date: 2026-09-30
spec_sha: 81974c16a911
plan_sha: a4fa97d5b2ce
tasks_sha: 70cf776c0fcf
---

# Análisis · 025 Regla de agenda única sin doble reserva

## Resumen
Resultado **fail**, con 3 hallazgos CRÍTICOS de constitución: en P3 faltan un Gate o Policy y los
tests por rol, y en P7 no se audita el alta con notas de la cita, sin que haya excepción
registrada. Hay además 3 hallazgos ALTOS, que obligarían a rehacer trabajo:
- el test de concurrencia no funciona tal como está diseñado;
- el criterio "sin `whereRaw`" no puede cumplirse;
- Pint ya falla hoy en los dos controladores.

Conteo: CRÍTICA 3 · ALTA 3 · MEDIA 10 · BAJA 6. El usuario ya decidió todo lo que era suyo.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (con huecos: fecha mal formada sin cubrir, A9; diferencias por flujo sin test, A16) |
| Amenazas con control y test | 5 / 5 (TM1 solo probada por el camino del bloqueo agotado, A5; falta la amenaza de saturación, A18) |
| Principios de la constitución evaluados | 8 / 8 (P3 y P7 con violaciones sin excepción: A1, A2, A3) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P3) | CRÍTICA | `plan.md` y `tasks.md` Constitution Check P3 | Los dos endpoints modificados siguen sin Policy ni Gate (usan `autorizadoParaCita`). `store` no limita el paciente al alcance del dentista o asistente (→ 014) y `/cliente` no exige `role:cliente` (→ 015). La excepción registrada solo cubre "sin test denegado en `/cliente/agendar`". | Decidido: excepción acotada `❌ aceptado` en P3 (sin Gate hasta la 014; alcance del paciente → 014; rol de `/cliente` → 015), con motivo y fecha, en plan y tareas. Comprobación: las filas P3 del plan y de las tareas nombran las tres partes. → `/plan --fix`, `/tasks --fix` |
| A2 | Constitución (P3) / Cobertura | CRÍTICA | `plan.md` "Roles (P3)"; T013 | Faltan tests por rol, permitido y denegado, para dentista y asistente en `POST /dentista/citas` y `/asistente/citas`. | Decidido: `AltaCitasRolesTest` cubre dentista (su dentista → permitido; otro dentista → 403) y asistente (dentista asignado → permitido; no asignado → 403), con `asistente_dentista` creado en el test. → `/plan --fix`, `/tasks --fix` |
| A3 | Constitución (P7) | CRÍTICA | `plan.md` y `tasks.md` P7 | El alta que se modifica escribe `notas_paciente` y `notas_internas`, que son datos de salud (`docs/security.md`). El ➖ del plan dice que no cambia datos clínicos, lo que no es cierto. | Decidido: `❌ aceptado: la auditoría del alta con notas → spec 022; la 025 no cambia qué notas se guardan ni quién — aprobado por el usuario el 2026-09-30`, más una viñeta en el roadmap. → `/plan --fix`, `/tasks --fix` |
| A4 | Inconsistencia / viabilidad | ALTA | T011; `plan.md` "Concurrencia (CA8)" y P2 | `citas.dentista_id` y `horarios_dentista.personal_id` tienen clave foránea a `personal`, así que un insert hijo pide un bloqueo compartido sobre el dentista. Mientras otra conexión lo tiene con `FOR UPDATE`, el insert de hoy espera 50 s y acaba en un error 500; no se guarda sin esperar. Si el test crea el horario con la conexión de la app, se bloquea a sí mismo. En el CI el host es `127.0.0.1`. | La conexión de la app no escribe filas que referencien al dentista bloqueado: el horario lo crea y confirma la segunda conexión, que lo borra en `tearDown`. La espera queda acotada en ambas conexiones, y la segunda hereda la configuración de la conexión por defecto. Hay que anotar la razón real por la que falla hoy (500 por bloqueo agotado sin manejar). Comprobación: T011 falla antes de T020 en pocos segundos y sin colgarse. → `/plan --fix`, `/tasks --fix` |
| A5 | Cobertura | MEDIA | CA8; TM1; T011 | Solo se prueba el bloqueo agotado. No se prueba que las citas candidatas se lean **después** de obtener el bloqueo; si se leen antes, se vuelve a la doble reserva en REPEATABLE READ. | Debe cumplirse que el bloqueo del dentista se toma antes de leer horario y citas. Test: registrar las consultas del servicio y comprobar que el `for update` sobre `personal` precede a la lectura de `citas`. → `/plan --fix`, `/tasks --fix` |
| A6 | Inconsistencia | ALTA | T020 "`CitasController` no contiene `whereRaw`" | `CitasController@index` (líneas 103–104) usa `whereRaw` en el buscador, que queda fuera del alcance. | El criterio se limita a `store` y a la regla retirada. Comprobación: búsqueda acotada al método. → `/tasks --fix` |
| A7 | Inconsistencia / viabilidad | ALTA | T020, T021, T040 (`pint --test`); CI | `pint --test` ya falla hoy en los dos controladores por métodos que no se tocan. | Decidido: tarea previa solo de formato con `pint` sobre los dos controladores, en un commit aparte, sin lógica y con la suite en verde. Declararlo en el plan. → `/plan --fix`, `/tasks --fix` |
| A8 | Cobertura (RNF) | MEDIA | spec RNF; `plan.md` "Errores de validación nuevos"; T010, T012 | `APP_LOCALE=en` y no existe `lang/`, así que `date_format` y `exists` responderían en inglés. Ninguna tarea añade los mensajes en español ni prueba que el formulario conserve los datos. | Los errores de `hora`, `fecha` y `dentista_id` salen con los textos exactos en español del plan, y la sesión conserva `fecha`, `hora` y `dentista_id` en ambos flujos. Test en T012/T010. → `/plan --fix`, `/tasks --fix` |
| A9 | Seguridad | MEDIA | CA9/TM3; `fecha` en ambos controladores | La regla `date` acepta "2026-10-05 10:00", y al concatenar con `hora` salta una excepción y un error 500. | Decidido: CA9 cubre también la fecha. Una `fecha` mal formada da error de validación y nunca 500, con test por flujo. → `/specify --edit 025`, `/plan --fix`, `/tasks --fix` |
| A10 | Ambigüedad | MEDIA | CA10; `plan.md` P4 | No está decidido si un dentista cuyo usuario está desactivado es válido. Con una petición manipulada se podría agendar con él. | Decidido: se rechaza, igual que en los formularios. Test de CA10 en ambos flujos. → `/specify --edit 025`, `/plan --fix`, `/tasks --fix` |
| A11 | Duplicación y conflicto (014) | MEDIA | `plan.md` D7; plan 014 (`Gate agendar-para-paciente`, `denyAsNotFound`); T013 | Tras la 014, un dentista con un paciente fuera de su alcance recibirá 404, lo que rompería el test de "otro dentista". La 014 no menciona a la 025. | Los tests de rol usan pacientes dentro del alcance, para que el 403 dependa solo de `autorizadoParaCita`. Anotar en el roadmap el ajuste de `/plan 014 --fix`. → `/plan --fix`, `/tasks --fix` |
| A12 | Duplicación y conflicto (013) | MEDIA | `plan.md` D1, D2; 013 CA3, CA10 | La 013 pondrá una prueba de arquitectura por módulo y cambiará el volcado por un seeder. `ReservaCitas` bloquea `personal` y lee `horarios_dentista`, que aún no tienen módulo dueño, y T011 depende del dentista del volcado. | Anotar en el plan el dueño de `personal` y `horarios_dentista` y el acceso previsto. T011 obtiene un dentista confirmado sin depender del id del volcado. → `/plan --fix`, `/tasks --fix` |
| A13 | Cobertura de riesgos (RS12) | MEDIA | `plan.md` D5 y "Riesgos"; T091 | RS12.c es una sola corrección, una parte dentro y otra fuera. El estado "en curso (spec 025: agendado; resto → 013)" no está en el vocabulario, y marcarla mitigada en `/release` dejaría fuera la otra parte. | Dividir RS12.c en `security.md` en dos correcciones con ID propio (agendado → 025; pacientes, expedientes y asistentes → 013), cada una con un estado válido. `/release 025` solo mitiga RS12.a y la del agendado. → `/plan --fix`, `/tasks --fix` |
| A14 | Ambigüedad | MEDIA | T020 | T010 y T011 prueban los dos flujos en el mismo método, así que el criterio de T020 (casos del personal) no se puede observar mientras el flujo del paciente no esté cambiado. | Separar los casos por flujo (métodos o data providers con nombre), para poder filtrar solo los del personal. → `/tasks --fix` |
| A15 | Ambigüedad | MEDIA | `plan.md` ("llama a `ReservaCitas::reservar`"); T011, T020, T021 | T011 da por hecho que el servicio sale del contenedor, pero ninguna tarea lo exige. | Los dos controladores resuelven `ReservaCitas` por inyección desde el contenedor. Comprobación: T011 pasa con la espera de 1 s enlazada. → `/plan --fix`, `/tasks --fix` |
| A16 | Cobertura | MEDIA | spec "Fuera de alcance" (se mantienen las diferencias por flujo); T020, T021 | El insert pasa a `ReservaCitas` y ningún test comprueba las columnas guardadas ni las diferencias por flujo: `creado_por`, notas, duración elegida frente a la del servicio, servicio obligatorio y fechas pasadas. | Tests por flujo de las columnas guardadas y de cada diferencia (fecha pasada permitida al personal y rechazada al paciente; duración del servicio). → `/tasks --fix` |
| A17 | Cobertura | BAJA | `plan.md` TM2, P8, Observabilidad; T040 | No se prueba la conversión de 1213 ni que la reserva y el bloqueo agotado no escriben logs. | Test de 1213 (excepción simulada) y comprobación de que no se escriben logs, en T011 o T040. → `/plan --fix`, `/tasks --fix` |
| A18 | Seguridad | BAJA | `plan.md` Modelo de amenazas; D3 | No está modelada la saturación de workers por la espera en serie ni la falta de límite de peticiones en `/cliente/agendar`. | Decidido: amenaza nueva fuera de alcance, con destino a una spec de límite de peticiones, y viñeta en el roadmap. → `/plan --fix` |
| A19 | Inconsistencia (redacción) | BAJA | spec "Notas para /plan" | La nota dice que la 013 se editará, pero ya está editada (25ebe76). | Aceptado sin cambio (decisión del usuario, 2026-09-30). |
| A20 | Inconsistencia (documentación) | BAJA | T090; `plan.md` Impacto en arquitectura | Faltan el "Resumen" y el paso 4 del "Flujo principal" de `architecture.md`. | T090 y el plan incluyen también esas dos secciones. → `/plan --fix`, `/tasks --fix` |
| A21 | Ambigüedad | BAJA | `plan.md` Rollout (consulta de vigilancia) | La consulta no excluye `no_asistio` ni usa solape estricto, y no aclara que las reactivaciones son de la 026. | Alinear la consulta con la regla y anotar lo de la 026. → `/plan --fix` |
| A22 | Constitución (P3) | BAJA | `plan.md` y `tasks.md` P3 | El alcance del paciente en `/cliente/agendar` sí se puede probar hoy: un `paciente_id` inyectado se ignora. | Decidido: test `test_un_cliente_no_puede_agendar_para_otro_paciente`, con la excepción limitada al rechazo por rol (→ 015). → `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Resueltas en esta ronda (decisión del usuario, 2026-09-30):
- **A1, A2, A22:** excepción acotada de P3 (sin Gate hasta la 014; alcance del paciente → 014; rol de `/cliente` → 015) y tests de rol completos, incluido el de otro paciente.
- **A3:** excepción de P7 → spec 022.
- **A7:** tarea previa solo de formato con Pint sobre los dos controladores.
- **A9, A10:** la spec incluye la fecha mal formada en CA9 y el dentista desactivado en CA10.
- **A18:** amenaza modelada, fuera de alcance → roadmap.
- **A19:** sin cambio.

## Aceptados
Aceptados por el usuario el 2026-09-30:
- **A19** → spec: la nota de "Notas para /plan" sobre la 013 queda como está; el Historial de la 013 ya registra la edición.
