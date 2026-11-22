---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-22
spec_sha: 3cdd017b4a32
plan_sha: 06cbc7f1697e
tasks_sha: 8b616d620124
---

# Análisis · 014 Autorización por alcance clínico

## Resumen
`pass`: las correcciones de la ronda 1 resuelven A1–A20; dos quedaron en parte (A7, A9) y aparecieron 8 hallazgos
nuevos (5 medios y 3 bajos, ninguno crítico ni alto), todos corregidos en la misma ronda y comprobados en el texto de
spec, plan y tareas.
Delta con un subagente de contexto limpio (perfil `standard`, `sonnet`), sobre el diff de spec y plan desde el plan
aprobado (`6fd4366f`) y `tasks.md` entero (reescrito en su lugar). N1 se comprobó en el código
(`AltaCitasValidacionTest.php:128-145`).
Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 5 (corregidos) · BAJA 3 (corregidos)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 14 / 14 |
| Amenazas con control y test | 15 / 15 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | `spec.md` «Cobertura de riesgos»: OB1.a–h declaradas; OB1.d dentro (ficha, edición, expediente) y listados → 022. El plan deja OB1.d «sigue pendiente» con nota, y T098 la escribe (N6) |
| A2 | ALTA | resuelto | CA13, D18, TM15, T019, T029; el encaje del Gate en `store`, precisado (N2) |
| A3 | ALTA | resuelto | CA14, D20, TM14, T014, T024, T033 |
| A4 | ALTA | resuelto | Fila `contradicción` con fuente `usuario`, que ahora nombra P3 y RS1.e (N8) |
| A5 | ALTA | resuelto | CA12 con la edición; T013 y T023 |
| A6 | ALTA | resuelto | Ajustes de D13 en el commit de la tarea que los rompe; dependencias de T030–T033 corregidas (N4) |
| A7 | ALTA | resuelto | La caracterización del asistente es defensa en profundidad, con rotura en las dos capas; la prueba del Gate, en T011 (N5) |
| A8 | ALTA | resuelto | D16, TM13, T017, T019, T027, T029; acotado al personal con alcance (N1) |
| A9 | MEDIA | resuelto | D17 con el orden de cada alta; test de igualdad en `expedientes.store` (N3) y en `citas.store` |
| A10–A20 | — | resueltos | ver la ronda 1 (`history/analysis.r1.md`) y su «Aceptados» |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| N1 | Inconsistencia (tests existentes) | MEDIA | plan D16; T027, T029; `AltaCitasValidacionTest.php:128-145` | El 404 por formato también afectaba al admin, y un test existente espera 422 para un id en array en el flujo del admin. | **Corregido**: D16 se acota al dentista y al asistente; el admin conserva la validación (422). T029 exige que `AltaCitasValidacionTest` siga en verde. |
| N2 | Ambigüedad (plan frente a código) | MEDIA | plan, fila de `CitasController`, D17, D18; T029 | `gestionar-cita` recibe una cita y en `store` no la hay; el orden de los Gates y la validación no estaba fijado. | **Corregido**: orden UUID → `agendar-para-paciente` → validación → `gestionar-cita` con un objeto sintético del dentista elegido (plan y T029). |
| N3 | Cobertura | MEDIA | T017; tabla TM9 | Faltaba el test «ajeno e inexistente responden igual» en el alta de expediente. | **Corregido**: `test_un_paciente_ajeno_y_uno_inexistente_responden_igual_al_crear_expediente` en T017 (`create` y `store`); TM9 cita T016, T017 y T019. |
| N4 | Inconsistencia (orden de tareas) | MEDIA | T030–T033 | Dependían de T020 y T021, que no rompen nada; T033 no tenía tope; el commit conjunto superaba 3 archivos sin decirlo. | **Corregido**: cada T03x depende de las implementaciones que rompen sus tests; T033 con lista cerrada (un test fuera de ella pide una tarea nueva); nota de que el límite vale por tarea y el commit conjunto es la excepción de A6. |
| N5 | Constitución (P2) | MEDIA | plan P2 y Estrategia; T013, T016 | Quitar solo el `role:` no hacía fallar el test del asistente, porque el controlador también lo rechaza. | **Corregido**: defensa en profundidad, rotura de las dos capas a la vez; la prueba del Gate, en T011. |
| N6 | Cobertura de riesgos / tareas | MEDIA | plan Impacto; T098 | RS1.a–e mitigadas y la nota de OB1.d no estaban en ninguna tarea. | **Corregido**: T098 lo incluye, con sus archivos listados (tarea fija de cierre, como en las releases anteriores). |
| N7 | Cobertura (tablas) | BAJA | `tasks.md` P3, T012, T017, Cobertura | Fila P3 imprecisa, TM10 sin T016, RNF sin T016–T019, T012 sin rotura, T017 con redacción rota. | **Corregido** en las tres partes. |
| N8 | Inconsistencia documental | BAJA | `spec.md` Notas y Decisiones; plan D18 | La spec seguía con «Policies»; la contradicción no nombraba P3 ni RS1.e; D18 hablaba de una comparación estricta inexistente. | **Corregido**: nota a Gates (ADR 0003) con su fila de Decisiones; la contradicción nombra P3 y RS1.e; D18 dice que la comparación estricta ya está en `CambiarEstadoCita::alcance`. |

## Decisiones pendientes del usuario
Ninguna. Spec, plan y tareas están en `draft` y hay que aprobarlos.

## Aceptados
Los de la ronda 1 (`history/analysis.r1.md`), ya incorporados:
- **A16** → nota de T098: 007 CA1 entra en la lista de criterios cambiados
- **A17** → plan: «11 archivos»
- **A18** → nota de T015: el estado vacío del dentista solo sin alcance
- **A19** → nota de T017, T027: el 403 por otro autor se registra con el paciente
- **A20** → nota de T098: RS1.a y RS1.b pasan a Gates; la nota caducada de la spec se quitó
