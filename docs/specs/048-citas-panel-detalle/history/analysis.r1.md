---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-14
spec_sha: f2d8105a45bb
plan_sha: 10c2213d9c8b
tasks_sha: 4a9bcda28b6c
---

# Análisis · 048 Abrir las citas del panel de inicio

## Resumen
`fail`: cuatro hallazgos altos, todos de diseño fino y no de alcance. El fondo al pasar el puntero en
la tarjeta del móvil revierte A57 de la 034 sin declararlo (A1); el enlace en reposo no se vería
igual que el texto de hoy (A2); el contraste del foco solo se mide a mano (A3); y los 44 px en el
escritorio chocan con CA9 y con el sistema (A4). Análisis completo con un subagente independiente;
cada hallazgo se verificó abriendo el archivo citado.
Conteo: CRÍTICA 0 · ALTA 4 · MEDIA 5 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 9 / 9 |
| Amenazas con control y test | 1 / 1 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | ALTA | plan «Cambios por módulo» (`app.css`); design.md «Puntero encima»; T019 | El fondo `--p50` al pasar el puntero también en la tarjeta del móvil revierte A57 de la 034 (`movil.css:203`, `MovilPersonalTest:524`): `--p50` es el `--bg` del `tbody` y la tarjeta deja de verse | Decisión del usuario (2026-11-14): se mantiene A57; por debajo de 900 px la tarjeta no cambia de fondo (cursor y foco sí). Test en T014: ninguna regla 048 da fondo a la fila por debajo de 900 px y `MovilPersonalTest` sigue en verde → `/specify --edit` (CA6), `/plan --fix`, `/tasks --fix` |
| A2 | Inconsistencia | ALTA | plan «Contratos y datos»; T014, T019 | Sin regla global para `a`, la hora o fecha dentro del enlace toma color, subrayado y `:visited` del navegador: rompe CA9 y el «reposo igual que hoy» | En reposo el enlace se ve como el texto de hoy (color heredado, sin subrayado, también visitado). Test en T014 (declaraciones del reposo) y en T024 (color y `text-decoration` calculados iguales a los de antes) → `/plan --fix`, `/tasks --fix` |
| A3 | Constitución (P9) | ALTA | plan P9; T024 | El contraste ≥ 3:1 del foco solo se comprueba con `medir.mjs` (a mano); `ReglasCss::contraste()` lo permite en PHPUnit | Test PHPUnit del contraste de `--focus-ring` sobre `--white` y sobre `--p50` en T014 → `/plan --fix`, `/tasks --fix` |
| A4 | Ambigüedad | ALTA | spec K1 y RNF; system.md «Toque mínimo en el escritorio: sin aplicar» (DS6) | Una fila de una línea mide ~42 px en el escritorio; ninguna regla da 44 px y CA9 no deja cambiar la altura | Decisión del usuario (2026-11-14): 44 px solo por debajo de 900 px, como el sistema; en el escritorio la zona pulsable es la fila entera. T024 mide la altura solo en 320 y 390 px → `/specify --edit` (K1, RNF), `/tasks --fix` |
| A5 | Inconsistencia | MEDIA | plan (`border-radius: inherit`); T019; design.md | El `::after` hereda el radio del `<a>`, no del `<tr>`: en la tarjeta del móvil el anillo saldría con esquinas rectas | El anillo de foco de la tarjeta sigue `--radius` por debajo de 900 px; aserción en T014 y captura con foco en T024 → `/plan --fix`, `/tasks --fix` |
| A6 | Inconsistencia | MEDIA | `app.css:658-662` (bloque 034); T014, T019 | `.tabla-tarjetas a:focus-visible` y `.btn-icono:focus-visible` dibujarían un segundo anillo alrededor de la hora y del ojo | Un solo anillo visible por cita (el de la fila), sin modificar el bloque 034; aserción en T014 y Tab en T024 → `/plan --fix`, `/tasks --fix` |
| A7 | Inconsistencia | MEDIA | plan «Contratos y datos»; T011, T022; `MovilPersonalTest:194` | El test de la 034 busca el ojo por `@href='/dentista/citas/{id}'` relativa; con `route()` la `href` pasa a absoluta y el test rompe por eso | El plan fija `route()` para las cuatro vistas; T011 actualiza también la aserción de la `href` del test de la 034 → `/plan --fix`, `/tasks --fix` |
| A8 | Ambigüedad | MEDIA | T012; plan D3 | En el ojo (sin texto) la aserción «empieza por el texto visible» no comprueba nada | En T012, el `aria-label` del ojo empieza por el texto de `.timeline-time` de su elemento → `/tasks --fix` |
| A9 | Ambigüedad | MEDIA | T014 | «Solo `var()`, `0` y `calc()`» contradice `position`, `cursor`, `content`, `inherit` que la misma tarea exige | Criterio: `ReglasCss::literales()` vacío (px, colores, pesos, familias), con control positivo → `/tasks --fix` |
| A10 | Cobertura | BAJA | plan P9 y RNF sin JS; T012, T014 | Ninguna tarea comprueba sin `style=""` en `.cita-enlace`/`.fila-cita` ni sin `<script>` nuevo | → nota de T012 |
| A11 | Ambigüedad | BAJA | design.md, plan («servicio o Consulta»); `cliente.blade.php:117` | Sin servicio, el nombre diría «Consulta» y la celda «—» | → nota de T012, T023: el respaldo del nombre es el mismo texto que la celda visible; T012 prueba una cita sin servicio |
| A12 | Inconsistencia | BAJA | plan Riesgos y Descubribilidad; tasks Cobertura RNF | `.tabla-wrap` es `.table-wrap` (y `.card-0` también recorta); los paneles no llevan `noindex`, redirigen sin sesión; el RNF de consultas se verifica en T092 (tasks) y en `/review` (plan) | → plan (redacción) |
| A13 | Riesgos | BAJA | plan Riesgos (`position: relative` en `<tr>`) | La mitigación solo se mide en Chromium | → nota de T024: `medir.mjs` también con WebKit y Firefox a 1440 px |
| A14 | Ambigüedad | BAJA | spec CA6; plan D1 | El `::after` impide seleccionar el teléfono o el nombre; en la dentista el puntero sobre el elemento activa `.btn-ghost:hover` del ojo | Decisión del usuario (2026-11-14): se acepta; se copian desde el detalle → spec (Decisiones); nota de T022 sobre el `:hover` del ojo |
| A15 | Seguridad | BAJA | plan P4 y modelo de amenazas | El `aria-label` lleva el nombre que escribe el paciente | → plan (P4: atributo con `{{ }}`); nota de T012: probar un nombre con comillas y `<` |

## Decisiones pendientes del usuario
Resueltas el 2026-11-14: A1 (se mantiene A57 de la 034), A4 (44 px solo en el móvil) y A14 (se
acepta no poder seleccionar texto de la fila).

## Aceptados
Aceptados por el usuario el 2026-11-14 (con las decisiones de A1, A4 y A14):
- **A10** → nota de T012: ninguna `.fila-cita` ni `.cita-enlace` con `style=""`; el panel sin `<script>` nuevos respecto a hoy
- **A11** → nota de T012, T023: el nombre accesible usa el mismo respaldo que la celda visible («—» → sin servicio); T012 lo prueba con una cita sin servicio
- **A12** → plan: redacción de `.table-wrap`/`.card-0`, de los paneles sin sesión y del RNF de consultas
- **A13** → nota de T024: `medir.mjs` también con WebKit y Firefox a 1440 px
- **A14** → nota de T022: el `:hover` del ojo al pasar por el elemento se valida en pantalla
- **A15** → nota de T012: un nombre de paciente con comillas y `<` sale escapado en el `aria-label`
