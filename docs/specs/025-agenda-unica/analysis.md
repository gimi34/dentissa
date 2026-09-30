---
result: fail
round: 3
mode: delta
constitution_version: 1.0.0
date: 2026-09-30
spec_sha: 3ca194bd9bf0
plan_sha: 04167e18a4af
tasks_sha: b10353ff8b1c
---

# Análisis · 025 Regla de agenda única sin doble reserva

## Resumen
Resultado **fail** por un hallazgo CRÍTICO, A33. La fila P8 admite que una excepción inesperada
del insert de `ReservaCitas` se registre con el SQL y sus valores enlazados, y aun así queda en ➖,
sin excepción formal.

Los siete hallazgos abiertos de la ronda 2 quedan cerrados: A14, A24 y A31 resueltos; A23, A25,
A26 y A32 resueltos, aunque abren hallazgos nuevos. Esta ronda aporta: CRÍTICA 1 · ALTA 0 ·
MEDIA 1 · BAJA 3. El usuario decidió todos.

Se comprobó que el nuevo diseño de T011 es viable, en el código de Laravel y en el esquema:
- `DeadlockException` solo aparece en transacciones anidadas.
- La vista de REPEATABLE READ se crea en la primera lectura.
- `pacientes.dentista_id` admite nulo.
- La conexión guardada sobrevive a `parent::tearDown()`.
- Las tablas de plan y tareas tienen todas las filas con el mismo número de columnas.

Ronda anterior: [history/analysis.r2.md](history/analysis.r2.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 5 / 5 (TM6 fuera de alcance, D13) |
| Principios de la constitución evaluados | 8 / 8 (P8 con residuo sin excepción: A33) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A14 | MEDIA | resuelto | T011 y T012 nombran los casos `personal` y `paciente`; T020 exige los `personal` de T010–T012 y los casos sin flujo de T011. |
| A23 | CRÍTICA | resuelto con efecto nuevo (→ A36) | Las filas P7 del plan y de las tareas nombran las dos partes (022 y 020, OB1.c). D9 no se actualizó. |
| A24 | ALTA | resuelto | D3, `ReservaCitas`, T020 y T011, con tres casos. En vendor, `ManagesTransactions.php:93-101` lanza `DeadlockException` solo en transacciones anidadas; en nivel 1 llega la `QueryException`. |
| A25 | MEDIA | resuelto con efecto nuevo (→ A35) | Pacientes con `dentista_id` nulo, confirmación antes de la primera lectura, limpieza en orden y declaración en P2. Todo es viable. Falta el recuento de usuarios en T001. |
| A26 | MEDIA | resuelto con efecto nuevo (→ A36) | Las filas P3 dan destino por endpoint (014/015) y T041 tiene la viñeta de la 015. D9 no se actualizó. |
| A31 | BAJA | resuelto | P1 cita la aprobación del 2026-09-30. |
| A32 | BAJA | resuelto con efecto nuevo (→ A33, A34) | P8 declara OB4.a y OB3.b, y el test comprueba que no se registran SQL, bindings ni datos. El residuo queda sin excepción formal, y el rechazo del test no pasa por el manejador de excepciones. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A33 | Constitución (P8) | CRÍTICA | Filas P8 del plan y de las tareas | La fila admite que una `QueryException` del insert de `ReservaCitas`, código nuevo de esta spec, se registre con SQL y bindings, notas incluidas. P8 lo prohíbe, y la fila está en ➖ sin `❌ aceptado`. | Decidido: excepción formal en P8. El residuo OB4.a en el alta de citas queda para la spec 023, con la aprobación del usuario del 2026-09-30, igual en plan y tareas y con viñeta en el roadmap (T041). Comprobación: las filas P8 del plan y de las tareas, y T041. → `/plan --fix`, `/tasks --fix` |
| A34 | Inconsistencia / Cobertura (TM2, P2) | MEDIA | Plan, "Concurrencia"; T011 (b) | El test de logs usa un rechazo simulado que llama al servicio sin HTTP, así que no pasa por el manejador de excepciones, que es donde se registran el SQL y los bindings. Además, el plan dice "durante un bloqueo agotado", algo que sin la segunda conexión no se puede producir. | Aceptado como nota de T011. |
| A35 | Inconsistencia | BAJA | T011 "hecho cuando"; T001 | T011 compara el recuento de usuarios con T001, que no lo registra. | Aceptado como nota de T001. |
| A36 | Inconsistencia | BAJA | Plan D9 | D9 no recoge los destinos nuevos de P3 (`/cliente/agendar` → 015) ni de P7 (403 → 020), aunque la fila P7 remite a D9. | D9 enumera las mismas partes y destinos que las filas P3 y P7 y que T041. → `/plan --fix` |
| A37 | Ambigüedad (formato) | BAJA | Plan, Observabilidad y "Concurrencia" | Faltan líneas en blanco: "Verificación: …" queda pegada a la lista de residuos, y "Ejecutar el archivo dos veces…" al paso 3 de la limpieza. | Separar ambas frases de la lista a la que no pertenecen. Comprobación: vista renderizada. → `/plan --fix` |

## Decisiones pendientes del usuario
Resueltas en esta ronda (decisión del usuario, 2026-09-30):
- **A33:** excepción formal en P8, residuo OB4.a → spec 023.
- **A34, A35:** se aceptan como notas de tarea. A36 y A37 van en el `--fix` del plan.

## Aceptados
Aceptados por el usuario el 2026-09-30:
- **A34** → nota de T011: `Log::spy()` también durante un bloqueo agotado real por HTTP, con la segunda conexión y en al menos un flujo. Antes del cambio ese caso falla porque el 500 registra el SQL; después, ninguna llamada a `Log` lleva SQL, bindings ni datos de la cita.
- **A35** → nota de T001: la línea base registra también el recuento de usuarios, que T011 compara.
