---
result: pass
round: 4
mode: delta
constitution_version: 1.0.0
date: 2026-09-28
spec_sha: d0b4451c216c
plan_sha: 290ffd61d5dc
tasks_sha: 2dc6b4e638b1
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **pass**. Las correcciones de la ronda 3 resuelven A32–A39. El delta deja 4 hallazgos
BAJOS (A40–A43), que el usuario acepta como notas para `/implement`. No hay cambio estructural:
ni módulo ni criterio nuevos; solo se aclara el contrato (`purgar(corte): int`, listeners de
sesión y una exclusión más en CA2, decidida por el usuario). Abiertos sin aceptar: CRÍTICA 0 ·
ALTA 0 · MEDIA 0 · BAJA 0; aceptados: 4.

Lo que falta de la 014 en A06, A09, A26 y A15 sigue abierto y no bloquea la 020; se corrige con
`/plan 014 --fix` y `/specify --edit 014` antes de `/tasks 014`.

Ronda anterior: [history/analysis.r3.md](history/analysis.r3.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 13 / 13 |
| Amenazas con control y test | 13 / 13 (el test de TM2 debe ejercitar la zona de sesión: A41, nota de T011) |
| Principios de la constitución evaluados | 8 / 8 (solo los afectados por el delta; ninguna violación) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A32 | ALTA | resuelto | T005: el doble "recibe por constructor un `AlmacenAuditoria` real y le delega `consultar()` y `purgar()`; solo `escribir()` lanza". T011: "el `DELETE` real se ejecuta … el recuento de eventos antiguos no cambia". T028 comprueba que el test falla con registro no crítico. |
| A33 | MEDIA | resuelto | Plan: `AlmacenAuditoriaBaseDatos` "es el único responsable del borrado y del indicador", y el comando solo abre la transacción y llama a `purgar()`. D13, T004, T021 y T028 coinciden. |
| A34 | MEDIA | resuelto | T012 usa rutas de prueba que escriben un log y un evento, "de modo que el test solo dependa de T022–T026". |
| A35 | MEDIA | resuelto | `desde` y `hasta` solo `AAAA-MM-DD`, con `hasta >= desde` y el día `hasta` incluido; "sin 500 y sin registrarse". P4, D7, TM8, TM12, T014 y T029 coinciden. |
| A36 | MEDIA | resuelto | `ContextoActor` hace `forget` sin sesión; los listeners de `Login` y `Logout` están en `AppServiceProvider` (plan y T023); los dos tests nuevos de CA10 están en T012. |
| A37 | BAJA | resuelto con efecto nuevo (→ A40, A41) | El trigger usa `UTC_TIMESTAMP()`; `ocurrido_en` se escribe en UTC (`config/app.php`); NTP en T091 y en Riesgos; test del corte al segundo en T011 y T031. |
| A38 | BAJA | resuelto | La fila P8 y Riesgos declaran el residuo con destino a la spec 023 (OB4.a), antes de producción. |
| A39 | BAJA | resuelto (redacción residual → A43) | CA2, el caso de abuso y Supuestos incluyen el cambio de estructura; TM1, la fila CA2 y Riesgos citan `ALTER`, `RENAME` y `DROP`. |
| A06, A09, A26 | — | 020: resuelto · 014: abierto → `/plan 014 --fix` | Fuera de los artefactos de la 020. |
| A15 | MEDIA | abierto → `/specify --edit 014` | Fuera de los artefactos de la 020. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A40 | Cobertura de riesgos (TM2, CA5) | BAJA | `plan.md` D13 y `routes/console.php` ("ahora en UTC − 5 años"); T028 | El corte de la app puede quedar después del umbral del trigger por dos motivos. Carbon resta años con desbordamiento (`2028-02-29 − 5 años` da `2023-03-01`, y MariaDB da `2023-02-28`). Además, `UTC_TIMESTAMP()` no tiene fracciones de segundo y el corte sí. Las filas entre los dos valores hacen abortar el `DELETE` entero. Falla de forma cerrada, pero la purga fallaría ese mes. | Aceptado como nota de T028 y T011. |
| A41 | Cobertura (TM2, P2) | BAJA | Fila TM2 del plan; T011 | La base de pruebas corre en UTC, así que `NOW()` y `UTC_TIMESTAMP()` coinciden. El test del corte al segundo pasaría también con un trigger que use `NOW()`. | Aceptado como nota de T011. |
| A42 | Inconsistencia (dependencias) | BAJA | T023 ("escuchar los eventos `Login` y `Logout`", depende: T022) | Los listeners de T023 solo los prueba T012, pero T023 no depende de T012, a diferencia de T024 y T025. | Aceptado como nota de T023. |
| A43 | Ambigüedad (redacción) | BAJA | `spec.md` Supuestos, "Limitación aceptada de CA2" | La frase dice "solo quedan fuera vaciar … o desactivar su protección" y la siguiente añade "La exclusión abarca también cualquier cambio de estructura": "solo" y "también" se contradicen. CA2 ya es correcto. | Aceptado → spec. |

Sin hallazgos en el resto del delta:
- Las tareas editadas tocan como mucho 3 archivos y ningún criterio de hecho cita una tarea posterior.
- Las tablas de Cobertura siguen coherentes y los nombres de los tests coinciden entre plan y tareas.
- `AppServiceProvider` se comparte con la 014: habrá conflicto de fusión, pero no de comportamiento.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-09-28:
- **A40** → nota de T028, T011: calcular el corte sin desbordamiento de años y truncado al segundo (o leerlo de la BD con `UTC_TIMESTAMP() - INTERVAL 5 YEAR`), de modo que nunca sea posterior al umbral del trigger. Test en `InmutabilidadAuditoriaTest`: con `Carbon::setTestNow('2028-02-29 00:00:00.500')`, el corte es `2023-02-28 00:00:00`.
- **A41** → nota de T011: `test_la_purga_respeta_el_corte_al_segundo` fija en su sesión una zona distinta de UTC (p. ej. `SET time_zone = '-06:00'`) antes de purgar, y así falla contra un trigger que use `NOW()`.
- **A42** → nota de T023: tratar T012 como dependencia (su test cubre los listeners) y comprobar al cerrar T023 que hay listeners registrados para `Login` y `Logout`.
- **A43** → spec: en la próxima edición de la spec 020, enumerar las tres exclusiones de CA2 en una sola frase de Supuestos (vaciar el almacén, desactivar su protección y cambiar su estructura). CA2 ya es correcto y manda.
