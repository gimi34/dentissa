---
result: pass
round: 3
mode: delta
constitution_version: 1.3.0
date: 2026-11-17
spec_sha: 05b8b6fe0247
plan_sha: bdfd0f82c39c
tasks_sha: 656c82aea7b3
---

# Análisis · 049 Calendario de citas

## Resumen
`pass`: los seis hallazgos de la ronda 2 (A27–A32) están resueltos y no hay hallazgos nuevos abiertos.
Análisis delta sin subagente (57 líneas cambiadas, unos 25 KB de diff, sin cambio estructural ni criterios
nuevos; solo se reformula el CA7 por decisión del usuario). Se releyeron desde cero las secciones afectadas.
Al releer las correcciones apareció un efecto de A28 sobre la «consulta única» del plan, y se corrigió en la
misma pasada de `--fix` (ver Seguimiento).
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 3 / 3 |
| Principios de la constitución evaluados | 10 / 10 (delta: P2, P3, P4 afectados por los cambios) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A27 | MEDIA | resuelto | T022: `index` incluye el mes y la lista solo cuando existen sus parciales; su «hecho cuando» exige de CA8 solo la tira. T023 cierra el día y el título del móvil y cubre CA8; Cobertura de CA8 con T023 |
| A28 | MEDIA | resuelto | Spec CA7 y fila de Decisiones (usuario, 2026-11-17); plan «Huecos», Enfoque técnico, P3, «Cambios por módulo», TM1, D2 y Trazabilidad del RNF: una segunda consulta de la semana con el alcance y el filtro de dentista, sin `buscar` ni `estado`, da la ocupación de los huecos. Tests: T011 (unitario), T014 (`estado=cancelada` y `buscar`), T015 (una cita ajena al alcance no quita huecos); implementación en T020 y T021 |
| A29 | MEDIA | resuelto | Plan P4, «Cambios por módulo» y TM3 con `paciente_id`; T014 con `?dentista[]=x` y `?paciente_id[]=x`; T029 lo implementa |
| A30 | BAJA | resuelto | CA7 («un tramo que empieza justo ahora ya no se ofrece»); plan «Huecos» (tramos que empiezan estrictamente después de «ahora»); T011 con «ahora» a las 11:30:00 |
| A31 | BAJA | resuelto | T011: sin hueco en 10:00 ni 10:30 con una cita de 10:15 y 45 min |
| A32 | BAJA | resuelto | Plan P1 sin fecha de aprobación obsoleta; `design.md` aclara que el `h2` «Hoy, …» es del candidato y que la build titula el periodo (refinado 5) |

Efecto revisado de A28: la corrección de la spec y de «Huecos» dejaba al plan diciendo que todo sale de una
sola consulta con los filtros, de modo que los huecos se habrían calculado sobre la lista filtrada. Se
corrigió antes de cerrar esta ronda (Enfoque técnico, P3, «Cambios por módulo», TM1, D2, RNF y T021), con el
alcance aplicado también a la consulta de ocupación (T015). No queda abierto.

## Hallazgos nuevos
Ninguno.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Ninguno.
