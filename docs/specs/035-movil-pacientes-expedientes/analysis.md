---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-10-16
spec_sha: 74ecbbe8e222
plan_sha: 9fad03e795cf
tasks_sha: b0779f0c0a51
---

# Análisis · 035 Móvil para pacientes y expedientes del personal

## Resumen
`pass`. Los 19 hallazgos abiertos de la ronda 1 quedan resueltos con `/plan --fix`, `/tasks --fix` y
las ediciones de `design.md` y de la spec. Tres de ellos tuvieron un efecto nuevo (A11, A12 y A17),
que se recoge en A21–A23.

Los hallazgos nuevos (A21–A27) son 2 MEDIOS y 5 BAJOS que no cambian el diseño. El usuario los
acepta como notas de tarea, y A8 y A26 se corrigen en el plan antes de cerrar.

Delta hecho por un subagente de contexto limpio sobre `aidd.py changes` (63 KB, más que el límite
para hacerlo sin subagente). Cada hallazgo se verificó en el código: `paciente_id` oculto en
`expedientes/create.blade.php:33`, migas de tres elementos en `expedientes/show` y `create`, y
`.migas` sin `flex-wrap` en `app.css:554`.

Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 0 / 0 (sin amenazas) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | D12 y «Cambios a incorporar»: `--perfil-iniciales-text` y `--weight-bold`; el resto del `style=""` del avatar sale con tokens existentes; T001, T012, T020 |
| A2 | ALTA | resuelto | D12: `.perfil-cab` con dos hijos (avatar y `.perfil-texto`); T010 y T014 |
| A3 | ALTA | resuelto | D7: `.etiqueta-campo { display: block }`; T011, T012, T020, T024 |
| A4 | ALTA | resuelto | D14 y T010, T022, T024–T026; `.migas` reproduce el `style=""` de hoy (`app.css:554-556`) y su toque ya está en `movil.css` |
| A5 | ALTA | resuelto | R1 y D10: comprobación WebKit con control positivo en T022 |
| A6 | ALTA | resuelto | D13 (especificidad 0,3,0 frente a 0,2,0 del `<style>` de las vistas); T012, T014, T020, T024–T026 |
| A7 | MEDIA | resuelto | D11 y T041 cubren las tres vistas de la 014 |
| A8 | MEDIA | resuelto | D11, Decisiones y T041; el Rollout («Orden respecto a otras specs») se corrigió al cerrar esta ronda |
| A9 | MEDIA | resuelto | `design.md`, «Cambios a incorporar», con los nueve tokens |
| A10 | MEDIA | resuelto | D12 (`aria-hidden` en las iniciales) y T010 |
| A11 | MEDIA | resuelto con efecto nuevo (→ A22) | D7 y T011, T024–T026 coinciden con las reglas de los controladores |
| A12 | MEDIA | resuelto con efecto nuevo (→ A21) | D6 y T012/T014 cubren `.topbar-title` y `.migas-actual` |
| A13 | MEDIA | resuelto | P2 y Trazabilidad de CA9 con el comando `--medir` |
| A14 | MEDIA | resuelto | Supuestos con fuente confirmada en la spec y en `design.md` |
| A15 | MEDIA | resuelto | T013 fija `.topbar { background: var(--white) }` |
| A16 | BAJA | aceptado (ronda 1) | Nota de T014 |
| A17 | BAJA | resuelto con efecto nuevo (→ A23) | El plan dice en todos los sitios que `MovilPersonalTest` no cambia |
| A18 | BAJA | resuelto | T002 incluye `trivy fs` |
| A19 | BAJA | resuelto | La fila Tests cita 034 CA9 y el botón de cerrar de la 033 |
| A20 | BAJA | resuelto | D12 en `movil.css` (17 px y 16 px); T012, T021 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A21 | Cobertura | MEDIA | plan D6, D14; T012, T014, T024, T026 | En las migas de la ficha y del alta de expediente, el nombre del paciente es un enlace intermedio que no parte de línea (`inline-flex`), y `.migas` no tiene `flex-wrap`. Una palabra de 30 letras puede ensanchar la página a 320 | En el móvil, las migas pasan de línea y sus enlaces parten (`flex-wrap`, y `overflow-wrap: anywhere` con `min-width: 0` en `.migas a`). Test: la regla en T012 y la palabra larga en el nombre de las dos pantallas en `--medir` (T014) → notas de tarea |
| A22 | Cobertura / ambigüedad | MEDIA | plan D7; T011, T024, T026 | «Cada campo validado» incluye `paciente_id`, que es oculto. La fecha sugerida de la ficha no usa `old()`. No se dice si los campos nuevos con error llevan `.field-error` | Se excluyen los campos ocultos (`paciente_id` y las copias ocultas del formulario de fecha). La fecha sugerida usa `old('proxima_visita', …)`. Cada campo con error lleva `.field-error`, como hoy. Test: en T011 → notas de tarea |
| A23 | Inconsistencia | BAJA | tasks.md, Constitution Check P9 y tabla de módulos | Dice «cuatro tokens» (son nueve) y pone `MovilPersonalTest` en la fila de Tests, aunque no cambia | Se corrige al hacer T020 → nota de tarea |
| A24 | Inconsistencia | BAJA | `design.md`, «Cambios a incorporar» | Falta la fila del foco del sistema en los formularios (D13) | Se añade al aplicar el sistema en T001 → nota de tarea |
| A25 | Inconsistencia | BAJA | plan D14; T010 | D14 dice `<nav aria-label>` y el componente de la 033 y la 034 es `<div id="migas" class="migas">` | Se usa el `<div id="migas" class="migas">` de la 033; el `<nav>` con nombre sigue como deuda 033/R6 → notas de tarea |
| A26 | Inconsistencia | BAJA | plan D14 | La viñeta de prueba del perfil quedó bajo D14 y D14 no decía cómo se prueban las migas | Corregido al cerrar esta ronda: la viñeta vuelve a D12 y D14 tiene su línea de prueba → plan |
| A27 | Ambigüedad | BAJA | plan D6 | La regla de `.topbar-title` y `.migas-actual` es de todo el móvil y alcanza a las pantallas de la 033 y la 034 | `--medir` comprueba que esas pantallas siguen sin fallos (T027) → nota de tarea |

## Decisiones pendientes del usuario
- Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-16:
- **A16** → nota de T014: tolerancia de 2 px para «centrado» y «alineado».
- **A21** → nota de T012, T014, T024, T026: en el móvil, `.migas` con `flex-wrap` y `.migas a` con `overflow-wrap: anywhere` y `min-width: 0`; palabra de 30 letras en el nombre del paciente de las migas de la ficha y del alta de expediente en `--medir`.
- **A22** → nota de T011, T024, T026: se excluyen los campos ocultos (`paciente_id` y las copias del formulario de fecha); la fecha sugerida usa `old('proxima_visita', …)`; cada campo con error lleva `.field-error`, como hoy.
- **A23** → nota de T020: el Constitution Check de `tasks.md` dice nueve tokens y la tabla de módulos no lista `MovilPersonalTest` como test que cambia.
- **A24** → nota de T001: `design.md`, en «Cambios a incorporar», gana la fila del foco del sistema en los formularios (D13).
- **A25** → nota de T010, T022: las migas usan el `<div id="migas" class="migas">` de la 033, no `<nav>` (deuda 033/R6).
- **A27** → nota de T027: `--medir` sin fallos también en las pantallas de la 033 y la 034 tras la regla global de `.topbar-title` y `.migas-actual`.
- **A8** → plan: el Rollout dice que la 035 sale antes que la 020 (corregido).
- **A26** → plan: la viñeta de prueba del perfil está en D12 y D14 tiene la suya (corregido).
