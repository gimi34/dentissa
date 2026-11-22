---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-22
spec_sha: a8133d4ccddf
plan_sha: 8225e3bed6e2
tasks_sha: b2a94530a6ea
---

# Análisis · 014 Autorización por alcance clínico

## Resumen
`fail`: el plan declara cubierta una corrección de riesgo que no lo está (A1). Además quedan siete hallazgos altos:
compromisos que otras specs dejaron a la 014 y que el plan no recoge (el Gate del detalle de cita y el aviso de
duplicado de la 054), la contradicción 403/404 sin registrar en la spec, la lectura de la ficha al editarla sin
evento, un orden de tareas que no permite commits en verde, una caracterización que no se puede comprobar como dice
y un `paciente_id` mal formado que daría un 500. Lo que el plan da por hecho de la auditoría se cumple: el registro
central audita los 404 de `denyAsNotFound` con el tipo y el id de la ruta, `denegar()` funciona en un controlador y
un evento crítico que falla muestra la página genérica.
Análisis independiente con un subagente de contexto limpio (perfil `critical`, `opus`); los hallazgos se comprobaron
en su archivo (roadmap, 020, 054, código).
Conteo: CRÍTICA 1 · ALTA 7 · MEDIA 7 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 nominal; 11 / 12 completos (CA12 sin la lectura de la edición, A5) |
| Amenazas con control y test | 12 / 12 nominal; 10 / 12 completas (TM9 y TM10, A13); faltan amenazas para A3, A8, A9 y A10 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda del plan rehecho.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Cobertura de riesgos | CRÍTICA | `plan.md` Impacto; `spec.md` Cobertura de riesgos; spec 020 (OB1.d → 014 CA12 y 022); `roadmap.md:600-603` | El plan declara mitigada en `/release` la brecha de lecturas de ficha y expedientes, pero la spec no la declara y la 020 la reparte entre la 014 y la 022; quedan lecturas de datos clínicos sin evento (la edición de la ficha, el directorio con alergias y tipo de sangre, el índice de expedientes con el diagnóstico). | `/specify --edit 014`: la corrección en «Cobertura de riesgos» con lo que entra y el destino del resto (decisión del usuario). Mitigada solo si cada lectura de ficha o expediente deja su evento. |
| A2 | Constitución (P3) / conflicto con lo liberado | ALTA | `roadmap.md:477-478` (026/R15), `515-519`, `535-542`; planes de la 026, 028 y 050; `CitasController.php:91, 296, 339` | Tres excepciones P3 aceptadas tienen como destino la 014: el Gate del detalle de cita del personal (`GET /{rol}/citas/{id}`) y pasar `autorizadoParaCita` (show y store, `in_array` no estricto) al Gate `gestionar-cita`. El plan no las recoge. | Decisión del usuario: dentro de la 014 (criterio, Gate y test por rol en `citas.show` y `store`) o cambio de destino en el roadmap y `security.md`. |
| A3 | Inconsistencia / conflicto con la 054 | ALTA | 054 `spec.md:159`; `PacientesController.php:109-134`; `PacienteSinCorreoTest.php:278-290, 857-867` | La 054 dejó decidido que el aviso de duplicado muestra siempre el nombre, pero el enlace a la ficha solo si se puede ver, y que la 014 lo acepta como excepción. El plan no lo aplica: tras la 014 el enlace daría un 404 auditado, y `test_los_enlaces_del_aviso_usan_la_ruta_del_rol` (asistente) se rompe sin estar en D13. | Plan y tareas: enlace solo con permiso para ver la ficha; el nombre, excepción registrada con el usuario como fuente. Test: el asistente ve el nombre de un duplicado ajeno sin enlace; el admin, con enlace. |
| A4 | Constitución / Decisiones | ALTA | 006 `spec.md:26, 28`; `plan.md` D2; `spec.md` (sin «Decisiones»); constitución P3; `security.md` RS1.e | La contradicción entre la 006 CA6 («403») y el 404 de la 014 no está registrada en la spec 014, que no tiene sección «Decisiones». | `/specify --edit 014`: fila `contradicción` con fuente `usuario` (decisión del 2026-11-22): el 404 fuera del alcance reemplaza el 403 de 006 CA6, 007 CA7, RS1.e y P3 para estas rutas. |
| A5 | Constitución (P7) / Cobertura | ALTA | `spec.md` CA12; `plan.md`; `tasks.md` T023 | `edit` de la ficha muestra la ficha clínica completa y no deja `paciente_leido`. | Toda lectura permitida de la ficha, también la edición, deja su evento (o exclusión con decisión del usuario). Test: `test_abrir_la_edicion_de_la_ficha_queda_registrado`. |
| A6 | Inconsistencia (tareas) | ALTA | `tasks.md` T022, T024, T025, T027–T029 frente a T030–T033 | Las tareas de implementación exigen que sigan en verde tests existentes que solo se ajustan en T030–T033, que dependen de ellas: los commits intermedios irían en rojo. | `/tasks --fix`: cada ajuste de D13 va en la tarea que rompe el test; T030–T033 quedan para lo que descubra la suite completa. Se comprueba con la suite en verde en cada commit. |
| A7 | Inconsistencia (plan vs código) | ALTA | `plan.md` Estrategia; `tasks.md` T013, T016; `routes/web.php:176-197` | El asistente no tiene rutas `edit`/`update` de pacientes ni `update` de expedientes: el 403 lo pone `RoleMiddleware` antes de llegar al Gate, así que «rompiendo el Gate» no hace fallar el test HTTP. | Plan y tareas: la caracterización HTTP se rompe en el middleware de rol; el Gate, con `Gate::forUser()->inspect` (T011). |
| A8 | Seguridad / P4 | ALTA | `ExpedientesController.php:63`; `Auditoria.php:96-102, 126`; `plan.md` D4; `tasks.md` T027 | En el alta de expediente, un `paciente_id` mal formado hace que `denegar()` lance `InvalidArgumentException` (500); ausente o en array, `TypeError` en el Gate tipado. Hoy dan 404. | Un `paciente_id` mal formado, ausente o en array da 404 sin 500 en `expedientes.create` y `store` (y en `citas.store`). Test: `test_el_alta_de_expediente_con_paciente_mal_formado_es_un_404`; amenaza nueva. |
| A9 | Seguridad (TM9) / RNF | MEDIA | `CitasController.php:286`; `ExpedientesController.php:114`; `PacientesController.php:338-371` | `exists:pacientes,id` da 422 a un id inexistente y el Gate 404 a uno fuera del alcance: se distinguen en las altas; en `update` de la ficha, si el Gate va tras validar, un PUT fuera del alcance con datos inválidos da 422. | Un paciente inexistente y uno fuera del alcance responden igual en `citas.store`, `expedientes.store` y `pacientes.update` (Gate antes de validar). Variante del test de TM9 para cada uno. |
| A10 | Inconsistencia (otras pantallas) | MEDIA | `CitasController.php:23-50, 98-175, 332-361`; `citas/show.blade.php:165`; `DashboardController.php:84-101, 151-169` | Calendario, detalle de cita y paneles siguen el alcance por cita de la 026 (canceladas incluidas): un paciente fuera del alcance de la 014 sigue saliendo con sus datos, y «Ver expediente del paciente» daría un 404 auditado (ruido en la métrica del Rollout). | Plan: esas pantallas conservan el alcance por cita (026) y el enlace a la ficha solo aparece con permiso para verla. Test: el detalle de la cita cancelada de P4 no enlaza a la ficha para D1. |
| A11 | Constitución (P3) | MEDIA | `plan.md` P3; `tasks.md` P3 | «Seis Gates en cada acción» no es exacto: el alta del asistente se controla con `Rule::in`, `citas.create` y los índices con filtro de consulta (que ADR 0003 permite). | Corregir la fila P3: qué acciones llevan Gate y cuáles filtro o validación, con su fuente. |
| A12 | Constitución (P4) | MEDIA | `PacientesController.php:159, 364`; `plan.md` D10 | `dentista_id` de la ficha se valida con `exists:personal,id` sin el tipo: se puede asignar a un asistente y el paciente queda fuera del alcance de todos. | `dentista_id` de un dentista activo en el alta y en la edición. Test: asignar un `personal` de tipo asistente da 422. |
| A13 | Cobertura (tests) | MEDIA | `plan.md` Estrategia; `tasks.md` T016, T019 | TM10 solo se prueba en la ficha; TM9 y el RNF solo en pacientes; falta el permitido de CA6 (el autor modifica su expediente); el 404 de agendar no se pide con los dos roles. | Añadir esos casos. |
| A14 | Constitución (P2) | MEDIA | `plan.md` P2; `tasks.md` T012, T014, T016, T017, T019 | Varios tests pasan desde el principio (admin ve a todos, admin agenda, el dentista lee E2, la ficha lista expedientes de otros autores, el alta válida, «Editar» según el rol) sin declararse caracterización ni decir qué se rompe. | Marcar cada uno como caracterización con su rotura en la tarea. |
| A15 | Ambigüedad | MEDIA | `spec.md` Supuestos; `RegisterController.php`; `PacientesController.php:203` | La spec no dice quién agenda la primera cita de un paciente sin dentista (alta de la 054) o asignado a otro dentista: con la regla, ni D1 ni su asistente pueden; solo el admin o el propio paciente. | Decisión del usuario en la spec. Test: D1 no puede agendar a P5 (404) y el admin sí. |
| A16 | Inconsistencia | BAJA | `plan.md` D7; `tasks.md` T098 | D7 cambia 007 CA1 (índice de expedientes), que no está en la lista de criterios reemplazados. | Aceptado → nota de T098. |
| A17 | Inconsistencia | BAJA | `plan.md` Cambios por módulo | Habla de «8 archivos» de tests existentes y enumera 11. | Aceptado → plan (se corrige en el `--fix`). |
| A18 | Ambigüedad | BAJA | `plan.md` Qué ve cada rol; `tasks.md` T015 | El estado vacío «No tienes pacientes asignados» no distingue «sin alcance» de «sin resultados por búsqueda». | Aceptado → nota de T015 y T025: sale solo sin alcance; con `buscar` sin coincidencias, el mensaje de siempre. |
| A19 | Ambigüedad | BAJA | `plan.md` D5; `tasks.md` T017, T027 | En el 403 por «otro autor» no se dice qué recurso se registra. | Aceptado → nota de T017 y T027: `pacientes` con el id del paciente. |
| A20 | Inconsistencia | BAJA | `security.md:194-195`; `spec.md` Notas para /plan | RS1.a y RS1.b hablan de «Policy» y la spec mantiene la nota caducada de las tablas de la base de tests. | Aceptado → nota de T098 (`security.md` a Gates) y spec (se quita en el `--edit`). |

## Decisiones pendientes del usuario
Resueltas el 2026-11-22:
- A1 y A5: la 014 audita abrir la ficha, su edición y un expediente; los listados van a la 022 (recomendada).
- A2: el Gate del detalle de cita del personal entra en la 014 como CA13 (recomendada).
- A3: el aviso de duplicado enlaza la ficha solo si se puede ver; el nombre, siempre (CA14) (recomendada).
- A15: la primera cita de un paciente sin dentista o de otro dentista solo la agenda el admin (recomendada).
- A9–A14 y A16–A20 se corrigen en esta vuelta (recomendada).

## Aceptados
Corregidos en el `--edit` de la spec y el `--fix` de plan y tareas del 2026-11-22 (sin notas pendientes):
- **A16** → nota de T098: 007 CA1 entra en la lista de criterios cambiados
- **A17** → plan: «11 archivos»
- **A18** → nota de T015: el estado vacío del dentista solo sin alcance
- **A19** → nota de T017, T027: el 403 por otro autor se registra con el paciente
- **A20** → nota de T098: RS1.a y RS1.b pasan a Gates; la nota caducada de la spec se quitó
