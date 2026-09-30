---
result: fail
round: 2
mode: full
constitution_version: 1.0.0
date: 2026-09-30
spec_sha: 3ca194bd9bf0
plan_sha: e64f1d11895b
tasks_sha: eaf2b9fa8b24
---

# Análisis · 025 Regla de agenda única sin doble reserva

## Resumen
Resultado **fail**. La ronda es completa porque las tareas se regeneraron con `--redo`. Motivos:
- **A23 (CRÍTICO):** el 403 del alta del personal no se audita y no tiene excepción P7.
- **A24 (ALTO):** con `DatabaseTransactions`, Laravel convierte 1205 y 1213 en `DeadlockException`.
  Un servicio que solo capture `QueryException` respondería 500.

De la ronda 1 quedan resueltos 16 hallazgos. A1, A4, A8, A12 y A17 se resolvieron pero
abrieron hallazgos nuevos, y A14 sigue abierto en parte.

Hallazgos nuevos: CRÍTICA 1 · ALTA 1 · MEDIA 2 · BAJA 6. El usuario decidió las excepciones y
aceptó A27–A30 como notas de tarea.

Ronda anterior: [history/analysis.r1.md](../history/analysis.r1.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 5 / 5 (TM6 fuera de alcance, D13; el control "espera de 5 s" de TM2 sin test: A29) |
| Principios de la constitución evaluados | 8 / 8 (P7 con una parte sin excepción: A23) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A26) | La fila P3 del plan y la de las tareas nombran las tres partes, pero el Gate de `/cliente/agendar` no tiene destino real. |
| A2 | CRÍTICA | resuelto | T013 cubre admin, dentista, asistente y cliente, cada uno con permitido y denegado. `asistente_dentista` existe (`id` char(36) sin valor por defecto; el test debe darlo). |
| A3 | CRÍTICA | resuelto | P7 aparece como `❌ aceptado … → 022` en el plan y en las tareas, y T041 lo lleva al roadmap. Otra parte de P7 queda sin cubrir: A23. |
| A4 | ALTA | resuelto con efecto nuevo (→ A24, A25) | T011 recoge la segunda conexión, la espera de 1 s y la razón real (comprobado: hoy falla en ~1 s con un 500). |
| A5 | MEDIA | resuelto | Test de orden bloqueo → lectura con `DB::listen`; T020 exige "bloquea primero". |
| A6 | ALTA | resuelto | T020 limita el criterio al método `store`. |
| A7 | ALTA | resuelto | T002 y D10. Pint solo aplica reglas de formato, y `fully_qualified_strict_types` cambia `\Illuminate\Support\Str` por un `use`, sin efecto en la lógica. |
| A8 | MEDIA | resuelto con efecto nuevo (→ A28) | Hay mensajes exactos y entrada conservada en T012, pero no en los rechazos de la regla. |
| A9 | MEDIA | resuelto | Spec CA9, D12, T012. |
| A10 | MEDIA | resuelto | Spec CA10, D12, T012. |
| A11 | MEDIA | resuelto | Pacientes dentro del alcance; D7 y T041. |
| A12 | MEDIA | resuelto con efecto nuevo (→ A27) | D11 fija el dueño de cada tabla, pero la condición no llega a la 013. |
| A13 | MEDIA | resuelto | RS12.c sigue "pendiente" con nota, un estado válido; ninguna corrección queda "mitigada" con una parte fuera. |
| A14 | MEDIA | abierto (parcial) | T010 separa por flujo, pero T011 y T012 no exigen nombres filtrables. T020 necesita filtrar los casos del personal de T011 y T012, y no está definido a qué flujo pertenecen los tests de orden y de logs. |
| A15 | MEDIA | resuelto | Inyección desde el contenedor en el plan, T020 y T021. |
| A16 | MEDIA | resuelto | T014. Condición: el test del personal envía `servicio_id` vacío, porque si falta la clave da 500. |
| A17 | BAJA | resuelto con efecto nuevo (→ A24) | El interbloqueo simulado es viable con la firma del plan, pero solo prueba una forma del error. |
| A18 | BAJA | resuelto | TM6 y D13, con viñeta en T041. |
| A20 | BAJA | resuelto | T090 cubre las cuatro secciones. |
| A21 | BAJA | resuelto | La consulta de vigilancia está alineada con la regla. |
| A22 | BAJA | resuelto | T013, cliente con otro paciente. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A23 | Constitución (P7) | CRÍTICA | Filas P7 del plan y de las tareas; `CitasController@store` (`abort(403)`) | P7 obliga a auditar los 403. El `store` modificado devuelve un 403 que no se registra, y el registro central de 403 es de la 020, que no está en esta rama. | Decidido: la excepción P7 nombra también el 403 del alta del personal, con destino a la 020 (registro central de accesos denegados), motivo y fecha, y se anota en el roadmap. Comprobación: las filas P7 del plan y de las tareas nombran las dos partes. → `/plan --fix`, `/tasks --fix` |
| A24 | Viabilidad / Inconsistencia | ALTA | Plan D3 y "Cambios por módulo"; T011, T020 | Con la transacción anidada (tests), Laravel 12 convierte 1205 y 1213 en `Illuminate\Database\DeadlockException`, que extiende `PDOException` y no trae `errorInfo`. En producción (nivel 1) llega una `QueryException`. Capturar solo `QueryException` 1205/1213 da 500 en los tests después de T020, y el camino de producción queda sin probar. | 1205 y 1213 se convierten en `CitaNoAgendable`, sin logs, tanto si llegan como `QueryException` como si llegan como `DeadlockException`. Test: el bloqueo agotado por HTTP (anidado), más la conexión simulada con `QueryException` 1205, `QueryException` 1213 y `DeadlockException`. → `/plan --fix`, `/tasks --fix` |
| A25 | Viabilidad | MEDIA | T011; plan "Concurrencia" y "Riesgos"; P2 | Cuatro problemas: (a) `pacientes.dentista_id` tiene clave foránea a `personal` (migración `2026_07_20`), así que el plan se equivoca al decir que los pacientes no referencian al dentista; (b) las reservas normales de los tests de orden y de logs, con el dentista confirmado, retienen bloqueos hasta el rollback de la app y la limpieza de la segunda conexión espera y falla; (c) la segunda conexión debe confirmar antes de la primera lectura de la app; (d) P2 no declara las escrituras confirmadas fuera de `DatabaseTransactions`. | La app nunca escribe filas que referencien al dentista retenido por la segunda conexión: pacientes con `dentista_id` nulo, y reservas normales con un dentista creado en la transacción de la app. La segunda conexión confirma antes de la primera lectura de la app, la limpieza nunca espera a un bloqueo, y P2 declara esas escrituras. Test: `ReservaConcurrenteTest` ejecutado dos veces seguidas termina sin errores en `tearDown` y deja dentistas, usuarios y horarios como en la línea base de T001. → `/plan --fix`, `/tasks --fix` |
| A26 | Constitución (P3) | MEDIA | Excepción P3 del plan y de las tareas | La excepción dice que la 014 introduce los Gates, pero la 014 solo añade `agendar-para-paciente` en `CitasController@store`. El Gate de `/cliente/agendar` no tiene destino real. | Decidido: destino por endpoint. Alta del personal → 014 y `/cliente/agendar` → 015, anotados en el roadmap. Comprobación: filas P3 del plan y de las tareas, y viñeta del roadmap. → `/plan --fix`, `/tasks --fix` |
| A27 | Duplicación y conflicto (013) | BAJA | Plan D11; T041; spec 013 CA10 | La condición de D11 (la prueba de arquitectura de la 013 permite el acceso de `ReservaCitas` a `personal`, o lo migra) no llega a la 013. | Aceptado como nota de T041. |
| A28 | Cobertura (RNF) | BAJA | Spec RNF; T010 | No se prueba que un rechazo de la regla (`CitaNoAgendable` con `withInput()`) conserve la entrada. | Aceptado como nota de T010. |
| A29 | Cobertura (TM2) | BAJA | Plan TM2 y D3; T011 | El control "espera de 5 s por defecto" no tiene test: todos los tests enlazan 1 s. | Aceptado como nota de T011. |
| A30 | Ambigüedad | BAJA | T012; plan P2 (CA9) | Hoy la fecha "abc" no da 500: la rechaza la regla `date` con un mensaje en inglés. Solo "2026-10-05 10:00" da 500. | Aceptado como nota de T012. |
| A31 | Inconsistencia | BAJA | Plan, fila P1 | Cita la aprobación del 2026-09-29, pero la vigente es la del 2026-09-30. | La fila P1 cita la aprobación vigente. → `/plan --fix` |
| A32 | Cobertura de riesgos (OB3/OB4) | BAJA | Plan P8 y Observabilidad; T011 (test sin logs) | P8 no declara el residuo OB4.a: una `QueryException` distinta de 1205/1213 en el insert se registra con SQL y bindings, notas incluidas. Además, "ninguna llamada a `Log`" choca con OB3.b (registrar con IDs los conflictos de agenda). | P8 declara el residuo OB4.a (→ objetivo 3). El test de logs comprueba que no se registra SQL, bindings ni datos de la cita, en lugar de "ninguna llamada". → `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Resueltas en esta ronda (decisión del usuario, 2026-09-30):
- **A23:** la excepción P7 se amplía al 403 del alta del personal → 020.
- **A26:** la excepción P3 nombra el destino por endpoint (alta del personal → 014; `/cliente/agendar` → 015).
- **A27–A30:** se aceptan como notas de tarea.

## Aceptados
Aceptados por el usuario el 2026-09-30:
- **A27** → nota de T041: añadir en Pendientes del roadmap que la prueba de arquitectura de la 013 debe permitir el acceso de `ReservaCitas` a `personal` o migrarlo a una interfaz de Identidad (D11).
- **A28** → nota de T010: al menos un rechazo de la regla por flujo (día, franja, solape o bloqueo agotado) vuelve con `fecha`, `hora` y `dentista_id` en la sesión (`assertSessionHasInput`).
- **A29** → nota de T011: un test comprueba con `DB::listen` que `ReservaCitas`, resuelto del contenedor sin sustituir, fija la espera a 5 s antes del `for update`.
- **A30** → nota de T012: anotar la razón esperada de cada caso; "abc" falla por el texto del mensaje y "2026-10-05 10:00" por el error 500.
