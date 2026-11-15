---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-14
spec_sha: ee9e130163f4
plan_sha: bc3ef1a05fd1
tasks_sha: 591623def972
---

# Análisis · 048 Abrir las citas del panel de inicio

## Resumen
`pass`: los cuatro altos y los cinco medios de la ronda 1 quedan resueltos en la spec, el acta, el
plan y las tareas, con las decisiones del usuario (A1, A4, A14). Ronda delta sin subagente (el
diff de `aidd.py changes` ronda los 30 KB y no hay cambio estructural), releyendo desde cero las
secciones tocadas. La corrección de A1 dejó un efecto (A16) que se corrigió en el mismo `--fix`.
Ronda anterior: [history/analysis.r1.md](history/analysis.r1.md).
Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 1 (resuelto) · BAJA 1 (resuelto)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 9 / 9 |
| Amenazas con control y test | 1 / 1 |
| Principios de la constitución evaluados | 10 / 10 (P4 pasa a ✅ con su test, A15) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A16) | spec CA6 y Decisiones; design «Puntero encima»; plan fila `app.css` y D7; T014 (sin fondo por debajo de 900 px), T019 |
| A2 | ALTA | resuelto | plan fila `app.css` (reposo con `color: inherit`, sin subrayado, `:visited`); T014; T024 (color y `text-decoration` calculados) |
| A3 | ALTA | resuelto | plan P9; T014 `test_el_foco_de_la_cita_contrasta` con `contraste()` sobre `--white` y `--p50` |
| A4 | ALTA | resuelto | spec K1 y RNF (44 px solo en el móvil), Decisiones; plan D8; T024 mide la altura a 320 y 390 px |
| A5 | MEDIA | resuelto | design «Foco»; plan fila `app.css` (`--radius` en la tarjeta); T014, T019; T024 (captura con foco) |
| A6 | MEDIA | resuelto | design «Foco» (un solo anillo); plan fila `app.css`; T014 (el enlace sin anillo propio), T019 (sin tocar el bloque 034); T024 |
| A7 | MEDIA | resuelto | plan «Contratos y datos» (`route()` en las cuatro vistas), filas de `dentista` y `MovilPersonalTest`; T011, T022 |
| A8 | MEDIA | resuelto | design «Nombre accesible»; plan «Contratos y datos»; T012 (el ojo empieza por `.timeline-time`) |
| A9 | MEDIA | resuelto | T014 con `ReglasCss::literales()` y control positivo |
| A10–A15 | BAJA | aceptados (r1) | notas de T012, T022, T023 y T024; plan (A12, A15) |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A16 | Inconsistencia | MEDIA | plan fila `app.css`; T019 (primera versión del `--fix`) | «Sin fondo nuevo, el del escritorio es el `tr:hover td` de hoy» dejaba sin fondo al pasar el puntero la agenda de la dentista, que es `.timeline` y no tabla: CA6 no se cumplía en su panel | Resuelto en el mismo `--fix`: fondo `--p50` en `.timeline-item.fila-cita:hover` solo desde 900 px (plan, T014, T019, design) |
| A17 | Inconsistencia | BAJA | plan fila `app.css`; T014 | `color: inherit` en todo `.cita-enlace` recolorearía el ojo de la dentista (`.btn-ghost`); T022 no repetía la `href` con `route()` | Resuelto en el mismo `--fix`: el reposo solo para el enlace de texto; T022 cita `route('dentista.citas.show')` |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Siguen los de la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)):
- **A10** → nota de T012: ninguna `.fila-cita` ni `.cita-enlace` con `style=""`; el panel sin `<script>` nuevos respecto a hoy
- **A11** → nota de T012, T023: el nombre accesible usa el mismo respaldo que la celda visible («—» → sin servicio); T012 lo prueba con una cita sin servicio
- **A12** → plan: redacción de `.table-wrap`/`.card-0`, de los paneles sin sesión y del RNF de consultas
- **A13** → nota de T024: `medir.mjs` también con WebKit y Firefox a 1440 px
- **A14** → nota de T022: el `:hover` del ojo al pasar por el elemento se valida en pantalla
- **A15** → nota de T012: un nombre de paciente con comillas y `<` sale escapado en el `aria-label`
