---
result: pass
round: 4
mode: delta
constitution_version: 1.0.0
date: 2026-10-01
spec_sha: 3ca194bd9bf0
plan_sha: 14a5169fc7fc
tasks_sha: 92b8f8cca780
---

# Análisis · 025 Regla de agenda única sin doble reserva

## Resumen
Resultado **pass**. De la ronda 3, A36 y A37 quedan resueltos, y A33 también, aunque abre un
hallazgo nuevo (A38).

Esta ronda aporta 2 hallazgos: CRÍTICA 0 · ALTA 0 · MEDIA 1 · BAJA 1. El usuario los acepta.
- A38 precisa el alcance de la excepción de P8, que ya se había decidido para todo el alta.
- A39 es solo de redacción.

Las filas P8 del plan y de las tareas son idénticas y tienen 3 celdas. T041 sigue en una línea,
con siete viñetas.

Ronda anterior: [history/analysis.r3.md](history/analysis.r3.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 5 / 5 (TM6 fuera de alcance, D13) |
| Principios de la constitución evaluados | 8 / 8 (P2, P3, P7 y P8 con excepciones aceptadas) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A33 | CRÍTICA | resuelto con efecto nuevo (→ A38) | `plan.md:43` y `tasks.md:25` con `❌ aceptado … — aprobado por el usuario el 2026-09-30`, con el mismo texto y 3 celdas. Viñeta de la spec 023 en T041, D9 y el roadmap. |
| A36 | BAJA | resuelto | D9 da los mismos destinos que las filas P3 (014/015) y P7 (022/020), más P8 → 023, en línea con T041. |
| A37 | BAJA | resuelto | Hay líneas en blanco antes de "Verificación: …" y de "Ejecutar el archivo dos veces…". |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A38 | Constitución (P8) / Inconsistencia | MEDIA | Filas P8 de `plan.md:43` y `tasks.md:25` frente a D9, Observabilidad y T041 | La excepción nombra solo "una `QueryException` del insert". `ReservaCitas` hace además el bloqueo, la lectura del horario y la de las citas, que también registrarían SQL con valores ante otro error. D9, Observabilidad y T041 ya hablan de "el alta". | Aceptado: la decisión de A33 fue "residuo OB4.a en el alta de citas" y cubre cualquier consulta de `ReservaCitas` distinta de 1205/1213. → nota de T041 |
| A39 | Inconsistencia | BAJA | `plan.md`, Observabilidad, "Residuos declarados" | La lista nombra la parte del 403 de P7, pero no la de las notas (→ 022). | Aceptado sin cambio: la fila P7 y D9 tienen las dos partes. → plan |

## Decisiones pendientes del usuario
Ninguna. A38 y A39 se aceptan (decisión del usuario, 2026-10-01).

## Aceptados
Aceptados por el usuario el 2026-10-01:
- **A38** → nota de T041: la viñeta del residuo P8 dice que cubre cualquier `QueryException` de `ReservaCitas` (bloqueo, lecturas e insert) distinta de 1205/1213, conforme a la decisión de A33 ("residuo OB4.a en el alta de citas").
- **A39** → plan: sin cambio; la fila P7 y D9 ya nombran las dos partes (notas → 022, 403 → 020).
