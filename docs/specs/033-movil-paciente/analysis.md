---
result: fail
round: 3
mode: delta
constitution_version: 1.2.1
date: 2026-10-11
spec_sha: 80f7408c1dd2
plan_sha: ab31cec599aa
tasks_sha: c40d0328a718
---

# Análisis · 033 Móvil para el paciente

## Resumen
`fail`: hay un ALTO que el usuario decide corregir. El plan muestra «Menú» y «Cerrar menú» en el
móvil sin exigir JavaScript, así que sin JS se verían sin funcionar, contra D5 (A40). Los 13
hallazgos de la ronda 2 están resueltos; dos correcciones abren efectos nuevos (A40, A41).

Análisis delta con un subagente (diff de 59 KB); cada hallazgo se ha comprobado en el código.
Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 1 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 |
| Amenazas con control y test | 0 / 0 |
| Principios de la constitución evaluados | 9 / 9 (en lo cambiado) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A27 | MEDIA | resuelto | «Bloques tocados» con todas las declaraciones, comparadas con los `style=""` reales; T013 por selector |
| A28 | ALTA | resuelto con efecto nuevo (→ A40) | `html.menu-js .menu-btn/.menu-cerrar` (0,2,1) gana a la global; T011, `--medir`, rotura en T023 |
| A29 | MEDIA | resuelto | `html.menu-js .sidebar { min-height: 0 }` anula `app.css:63`; T011, T097 |
| A30 | MEDIA | resuelto | `min-width` e `inline-flex` en pestañas y migas; T011 |
| A31 | MEDIA | resuelto | Línea base del escritorio con el personal a 1024 y 1440; T028 |
| A32 | ALTA | resuelto | Tests de señal no cromática, colores del bloque 033 (19,59:1; 5,06:1; 3,67:1) y `style=""` del marcado nuevo |
| A33 | MEDIA | resuelto con efecto nuevo (→ A41) | `--medir` mide el `<label>`; su `display:flex` pierde por especificidad |
| A34 | MEDIA | resuelto | `flex-wrap` en el banner; recortes en `--medir` |
| A35 | MEDIA | resuelto con efecto nuevo (→ A40) | Aspecto con tokens; `--border-control` 3,93:1 sobre `--white`, 3,40:1 sobre `--p100` |
| A36 | BAJA | resuelto | Trazabilidad con los tres tests; T010 cubre CA1 |
| A37 | BAJA | resuelto | «dentro del `<aside>`» |
| A38 | BAJA | resuelto | Patrón en Rollout, T010 y T090 |
| A39 | BAJA | resuelto | T002 y T003 sin `[P]`; T003 depende de T002 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A40 | Conflicto (CA2, D5) | ALTA | plan «Botones nuevos», «Toque» y «Sin JS»; T011 | «Botones nuevos» y «Toque» ponen `display:inline-flex` sin condición a `.menu-btn` y `.menu-cerrar`, con la misma especificidad que la regla global `display:none`: según el orden, sin JS se ven los dos botones sin funcionar (contra D5), o la regla global oculta también `.sidebar-marca` | Sin JS y por debajo de 900 px, ni «Menú» ni «Cerrar menú» se muestran y la marca sí; con JS se ven los tres (T011 y la pasada sin JS de `--medir` sobre el `display` calculado) |
| A41 | Cobertura (CA4, CA5) | MEDIA | plan «Toque»; `app.css:485`; `reprogramar.blade.php:114`; T011 | `.campo-casilla label` (0,1,1) pierde ante `.formulario-accesible .field-label` (0,2,0): la etiqueta queda en bloque, con el texto arriba de una caja de 44 px y desalineado de la casilla | A 390, la etiqueta mide ≥ 44 px y su texto queda centrado en vertical con la casilla (`--medir` y captura de reprogramar) |
| A42 | Inconsistencia | BAJA | T011; plan Trazabilidad y P9 | `test_el_aspecto_de_los_botones_nuevos_usa_tokens` no figura en el plan | Cada test de las tareas, en la Trazabilidad y en P9 |
| A43 | P2 | BAJA | T013 «hecho cuando» | Hay cuatro tests que no son de caracterización, pero el criterio dice «los tres primeros fallan» | Los cuatro fallan antes de implementar, cada uno por su razón |
| A44 | Inconsistencia | BAJA | T040; fila P5 de tasks | La limpieza habla de «la línea base de 1440», pero ahora incluye las capturas a 1024 | T040 borra toda la línea base del escritorio |

## Decisiones pendientes del usuario
Respondida el 2026-10-11: se corrigen los 5 hallazgos.

## Aceptados
(Ninguno.)
