---
result: fail
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-18
spec_sha: 221ae7cec958
plan_sha: ef0f0dafc0e7
tasks_sha: 1e5b46837215
---

# Análisis · 050 La hora de la clínica en todo el panel

## Resumen
`fail` por decisión del usuario: los 20 hallazgos de la ronda 1 están resueltos (A1, A2, A4 y A14 con efecto
nuevo), sin críticos ni altos. Quedan dos medios que el usuario decide corregir antes de aprobar: la próxima
visita ya guardada que no se toca (A21) y la excepción de P3 y P7, que nombraba solo una parte de los endpoints
(A22); y cinco bajos que se corrigen en la misma pasada. Delta con un subagente independiente (perfil `standard`;
134 líneas cambiadas, 56 KB, sin cambio estructural).
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA4 de caracterización, CA9 de regresión) |
| Amenazas con control y test | 2 / 2 |
| Principios de la constitución evaluados | 10 / 10 (delta: P2, P3, P6, P7) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A22, A26) | Plan P3 con endpoints, RS1.a–e → 014, RS2.a y aprobación del 2026-11-18 |
| A2 | CRÍTICA | resuelto con efecto nuevo (→ A22) | Plan P7 con OB1.d/e → 022 y aprobación |
| A3 | ALTA | resuelto | Plan TM2; T023; `test_agendar_ignora_una_fecha_manipulada` en T013 |
| A4 | ALTA | resuelto con efecto nuevo (→ A21) | Plan y T027 con `update`; T013 con POST y PUT |
| A5 | ALTA | resuelto | T013: nacimiento 16/17 en la edición, el perfil y los dos registros |
| A6 | ALTA | resuelto | Spec CA4, Decisiones y Fuera de alcance; plan; T012 (clases reales de la 049) |
| A7 | ALTA | resuelto | Spec CA5; plan; T013 (`max` 2025-11-17), T024, T028, T029 |
| A8 | MEDIA | resuelto | `extends` con 010, 038, 039 y 049 |
| A9 | MEDIA | resuelto | Spec CA7, Diseño y Decisiones |
| A10 | MEDIA | resuelto | Spec CA3; plan; T011 y T031 |
| A11 | MEDIA | resuelto | T013 con el reloj también a las 10:00 de la clínica |
| A12 | MEDIA | resuelto | T011, T013 y T025 |
| A13 | MEDIA | resuelto | T013 (24 cercana, 25 no) y T027 |
| A14 | MEDIA | resuelto con efecto nuevo (→ A25) | Plan RNF y T092 con patrón ampliado |
| A15 | MEDIA | resuelto | Plan P6 con EX5; Rollout; T092 con los audits |
| A16 | BAJA | resuelto (resto en el plan → A24) | Spec CA1 y T011 |
| A17 | BAJA | resuelto | T014 y T027 |
| A18 | BAJA | resuelto | T028 con los tests reales |
| A19 | BAJA | resuelto | Plan y T016 con `AvisoProduccionTest` y `ContrasenaDistintaTest` |
| A20 | BAJA | resuelto | Referencias corregidas; T090, T091 y T012 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A21 | Cobertura / ambigüedad | MEDIA | plan (`ExpedientesController`); T027; T013; `expedientes/show.blade.php:150-168` | Con `after:hoy` en `update`, guardar la ficha sin tocar una próxima visita ya vencida se rechazaría | Decisión del usuario (2026-11-18): solo se exige «posterior a hoy» si la fecha cambia; una fecha guardada sin cambios se acepta (test en T013: PUT con la fecha anterior sin cambiar, aceptado; cambiada a una pasada, rechazado) → `/plan --fix`, `/tasks --fix` |
| A22 | Constitución (P3/P7) | MEDIA | plan P3 y P7; tasks Constitution Check | La excepción nombra solo parte de los endpoints que la 050 modifica (perfil del paciente en `/cliente`, listas y fichas, detalle de la cita, usuario, galería, paneles; lectura del expediente en P7) | Decisión del usuario (2026-11-18): excepción ampliada a todos los endpoints cuya vista o regla de fecha cambia la 050, con la lista completa → `/plan --fix`; la spec nombra también la 015 (RS2.a) |
| A23 | Inconsistencia | BAJA | spec Decisiones y Notas; plan Enfoque, P1, Riesgos y `CitasController` | Texto de cuando la 049 no estaba fusionada; el plan omite el `isFuture` de `CitasController:277` | Textos marcados como superados; `:277` en la fila → `/specify --edit`, `/plan --fix` |
| A24 | Inconsistencia | BAJA | plan Estrategia (CA1) | El plan espera «martes, 17 de noviembre» en los tres paneles | El test de CA1 comprueba el formato de cada panel → `/plan --fix` |
| A25 | Cobertura (RNF) | BAJA | plan RNF; T092 | El patrón `date(` y `today` da falsos positivos y las llamadas con zona no son excepción | Patrón sin ambigüedad (`\bdate\(`, reglas `before:`/`after:` con `fechaHoy()`) y las llamadas con la zona de la clínica como excepción → `/plan --fix`, `/tasks --fix` |
| A26 | Constitución | BAJA | plan Decisiones; T098 | Las excepciones P3 y P7 no están como decisiones ni como deuda en el roadmap | Decisión en el plan y T098 anota la deuda → `/plan --fix`, `/tasks --fix` |
| A27 | Duplicación | BAJA | T023; `CitasController.php:194` | T023 duplicaría la validación `AAAA-MM-DD` de `CitasController::fechaDe` | T023 reutiliza esa validación (sigue probada por T013) → `/tasks --fix` |

## Decisiones pendientes del usuario
Resueltas el 2026-11-18: A21 (se acepta la próxima visita guardada sin cambios) y A22 (excepción ampliada, con
la lista completa, como decisión y como deuda en el roadmap).

## Aceptados
Ninguno: se corrigen todos antes de la ronda 3.
