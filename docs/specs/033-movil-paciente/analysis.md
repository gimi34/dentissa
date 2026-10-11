---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-10-11
spec_sha: 80f7408c1dd2
plan_sha: 23a666e48fb0
tasks_sha: 43c45f51a201
---

# Análisis · 033 Móvil para el paciente

## Resumen
`fail`: hay 7 hallazgos ALTOS que el usuario decide corregir. Los criterios tienen todos su tarea,
pero:
- quitar los `style=""` de los bloques tocados choca con «solo tokens» (P9) y con «escritorio sin
  cambios» (CA8), porque muchos valores en línea no tienen token (A1); y las reglas nuevas de
  `app.css` no tienen test (A2);
- el estado sin JS tapa la página (A3);
- faltan tokens de duración y de apilado (A4);
- la sección actual no se indica más que con color (A5);
- no se mide justo encima del punto de corte (A6);
- choca con la 020, que añade una sección al menú de admin (A7).

Ningún CRÍTICO. Revisión con un subagente independiente; cada hallazgo se ha comprobado en el código.
Conteo: CRÍTICA 0 · ALTA 7 · MEDIA 12 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 |
| Amenazas con control y test | 0 / 0 (el plan no tiene amenazas: sin datos, permisos ni entradas) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia / P9 | ALTA | plan Enfoque y Cambios por módulo (Tokens); T023–T027 | Las tareas añaden reglas nuevas en `app.css`, fuera de la media query (`.sidebar-logo img`, `.user-card-texto`, `.pestanas-citas` y las de T025–T027), y piden «a 1440, igual que hoy» con solo tokens. Muchos valores en línea no tienen token: logo `max-width:160px` (layout:18), subrayado de 2px de las pestañas (cliente-index:18,22), `gap:12px` del detalle (cliente-show:49; `.grid-2` tiene 16px), `border-left:3px` y `padding:36px 24px` / `16px 18px` (dashboard/cliente:31,75,188), y letras de 11, 14 y 20px. Solo caben un literal (incumple P9) o un cambio a 1440 (incumple CA8). El plan, además, dice que `app.css` solo gana tokens | Decisión del usuario (ver abajo); después, `/plan --fix A1` (valores de los bloques conocidos, texto sobre `app.css`) y `/tasks --fix A1` (T001 con los tokens que falten) |
| A2 | P9 / Cobertura | ALTA | T010, T011, T013; `AMBITO_NUEVO` de la 028 | La prohibición de literales solo tiene test en `movil.css` y `.pestanas-citas`; las reglas nuevas de `app.css` de T023–T027 no las comprueba ningún test | Toda regla que la 033 añada a `app.css` pasa `literales()` con control positivo (test en `MovilPacienteTest`, sobre una lista de selectores o un bloque marcado «spec 033») |
| A3 | Ambigüedad (D5) | ALTA | plan D5 y «Hoja `movil.css`»; T011, T021 | Sin JS queda la regla de `app.css:61-71`: barra fija de 242px, `z-index:100`, rosa. Como `.main` pasa a `margin-left:0`, el menú tapa la página a 320–390, al contrario de lo que dice D5 («como un bloque»). Conserva el contraste de 3.82:1 y 2.72:1, y `.menu-btn` se ve pero no hace nada | Sin JS, a 320 y 390: la página no se desborda, el menú no tapa contenido, se ve en el flujo con los colores del panel blanco y `.menu-btn` no se muestra (test en T011 y una pasada de `--medir` con JS desactivado) |
| A4 | Ambigüedad / P9 | ALTA | plan «Hoja `movil.css`» (transición, velo, `.topbar`); T001, T011 | La transición necesita una duración, y `system.md` no tiene tokens de duración. El velo y el panel necesitan un `z-index` sin token. El `min-height` de `.topbar` no tiene valor. No se dice si `s` y los números sin unidad cuentan como literales | El plan fija cada valor con un token de `system.md` o declara por escrito qué queda fuera de P9 (`z-index`); `test_movil_css_solo_usa_tokens` comprueba también las duraciones, con control positivo |
| A5 | Cobertura (CA2) / P9 | ALTA | CA2 «se indica en qué sección está»; maqueta A (`aria-current`); T010, T014 | La sección actual solo se indica con `.active`: en el panel blanco es `--p100` sobre `--white` (≈1.1:1) más el color del texto. Ni un test ni `--medir` la comprueban, y P9 pide estados que no dependan solo del color. La semántica de diálogo de la maqueta no está decidida | El enlace de la sección actual lleva `aria-current="page"` y una marca visible que no es solo color, por rol (test en T010 y en `--medir`); el plan decide la semántica del panel |
| A6 | Cobertura (CA1) | ALTA | CA1 «cualquier ancho entre 320 y 1440»; T014 | Solo se mide a 320, 390, 844 y 1440. El tramo con más riesgo es 900–1100: barra de 242px, ≈658px de contenido, rejillas a dos columnas y `.main` sin `min-width:0` | `--medir` mide también a 900 y 1024 las 7 vistas; si alguna se desborda, el plan decide el arreglo compatible con CA8 |
| A7 | Conflicto | ALTA | layout; T010; plan Rollout; plan 020 (Cambios por módulo y tabla «Qué ve cada rol») | La 020, con plan aprobado, añade «Auditoría» a la sección «Análisis» del menú de admin en el mismo layout, y T010 fija una lista exacta de 7. La que se fusione después rompe el test o tiene conflicto | Decisión del usuario sobre el orden (ver abajo); el Rollout cita la 020 y T010 lo prevé |
| A8 | Cobertura (CA8) | MEDIA | T014, T028 | T014 cambia los datos de prueba antes de capturar; la línea base de 1440 es de otros datos, así que no se distingue qué cambió por la spec | Línea base a 1440 con los datos nuevos tras T014 y antes de T020; T028 compara con ella |
| A9 | Ambigüedad (diseño) | MEDIA | T023 (`mix-blend-mode:screen` del logo); maqueta A | Sobre el panel blanco, `screen` hace desaparecer el logo; la maqueta muestra el texto «Melissa López N.» | El plan decide qué marca se ve en el panel del móvil, y se comprueba en la verificación manual de T023 |
| A10 | Inconsistencia (CA7) | MEDIA | plan Trazabilidad CA7 frente a T011 | `.sidebar-nav` ya tiene `overflow-y:auto` (`app.css:97-101`). Lo fijo del panel (logo, «Cerrar menú», tarjeta, «Cerrar sesión») puede llenar 390px de alto y dejar «Cerrar sesión» cortado | A 844 × 390 se alcanzan el último enlace y «Cerrar sesión» (test de estructura y `--medir`) |
| A11 | Cobertura (CA2) | MEDIA | CA2 «a 320 y 390»; T014 | El menú de cada rol solo se comprueba a 390 | `--medir` repite a 320 las comprobaciones del menú |
| A12 | Cobertura (CA5) | MEDIA | CA5; T013, T014 | Nada comprueba que cada etiqueta queda junto a su campo (las etiquetas no tienen `for`); la caracterización de tipos no incluye `fecha` y `hora` de reprogramar | `--medir` comprueba la etiqueta junto al campo a 320; la caracterización incluye reprogramar |
| A13 | Inconsistencia (archivos) | MEDIA | T024 [P], T025–T027 | T025–T027 escriben en `app.css` sin declararlo; T027 tocaría 4 archivos; T024 [P] y T025 editarían `app.css` a la vez | Declarar `app.css` donde se toque, ≤ 3 archivos por tarea y sin `[P]` entre tareas que compartan archivo |
| A14 | P2 | MEDIA | T013, T024–T027 | T024 y T025 pueden tocar bloques sin añadirlos al test, y en T026–T027 el test se amplía en la misma tarea, sin fallo previo anotado | Cada bloque tocado entra en el test antes del cambio, con el fallo previo anotado |
| A15 | P2 | MEDIA | plan P2 («comprobación declarada, 1.2.1»); T012, T014, T022 | Foco, Escape, `inert` y las medidas de CA4 y CA7 son comportamiento, y solo los comprueba de verdad `--medir`. El test de `menu.js` busca cadenas. La 1.2.1 reserva la comprobación declarada para lo que no es comportamiento | `--medir` se trata como verificación de comportamiento con «comprobado rompiendo» (p. ej. sin el manejo de Escape sale con 1), anotado en T022; corregir la cita |
| A16 | P9 (WCAG) | MEDIA | D9; T011; `app.css:156-172` | En el menú, `.btn-logout:hover` es `#FF0000` con `--p600` (1.75:1) y en el móvil se queda tras tocar; `.btn-logout` es blanco sin borde sobre blanco; `.nav-item` y `.btn-logout` no tienen `:focus-visible` con `--focus-ring` | En el móvil, los estados de `.btn-logout` y `.nav-item` cumplen AA y tienen foco visible (los tests de pares y de foco los incluyen) |
| A17 | Ambigüedad (tests) | MEDIA | T003; `reglasCss()` (`/([^{}]+)\{([^{}]*)\}/`) | El parser aplana `@media` y pierde la condición; «junto con su condición» no dice cómo se representan las `@media` anidadas | T003 fija el formato (todas las condiciones anidadas) y lo prueba con un CSS de ejemplo; el test de la 028 no cambia de resultado |
| A18 | Ambigüedad (CA6) | MEDIA | T014, plan D7 | No dice si el nombre largo es de `cliente1`, la cuenta con la que se entra, ni en qué citas va el servicio largo | El nombre largo aparece en el menú y en las pantallas medidas, y el servicio largo en la próxima cita, las pasadas, el detalle y reprogramar |
| A19 | Ambigüedad | MEDIA | plan Layout; T023 | No se dice dónde va `.menu-velo`; dentro de `<main>`, `inert` impediría cerrarlo y no taparía la barra superior | El velo queda fuera de `<main>` y por encima de la barra superior (comprobado en `--medir`: tocarlo cierra el menú) |
| A20 | Ambigüedad (CA4) | MEDIA | T011 (`input` en la lista de toque) | `input { min-height }` deformaría la casilla, de 16px de alto (`app.css:515`) | La casilla conserva su forma y su zona de toque de 44px la da la etiqueta (test) |
| A21 | Cobertura | MEDIA | T002, T001 | `aidd.py validate` no compara los anchos de `project.yaml` y `system.md`, así que el criterio de T002 se cumple siempre; en `system.md` la clave es `widths` | Comprobación explícita de los dos valores `[320, 390, 1440]` |
| A22 | Inconsistencia | BAJA | plan D6; T012, T022 | `menu.js` repite el punto de corte en `matchMedia` | Admitido por escrito en D6 y en `system.md`, o se detecta el modo sin repetir el valor |
| A23 | Inexactitud | BAJA | plan D9, Riesgos | El texto del menú rosa es blanco al 90% (3.82:1) y `.nav-label` al 65% (2.72:1). `100dvh` es Chrome 108+ e `inert` es Safari 15.5+. La CSP de la 031 es `script-src 'self' 'unsafe-inline'` | Corregir las cifras y la cita |
| A24 | P2 | BAJA | T010 | La caracterización del viewport va dentro de un test que falla al principio, sin «comprobado rompiendo» | Test propio, comprobado quitando el meta |
| A25 | Formato | BAJA | T040 | Sin `depende:` y con «Cubre:» fuera de la línea de la tarea | `— cubre: CA1…CA8 — depende: T028` en su línea |
| A26 | Redacción | BAJA | T001 | «en parte (033)» no es un estado de deuda de `system.md`; DS10 no se actualiza | «en curso: …» en DS6 y DS8; DS10 «en curso (033, solo el menú del móvil)» |

## Decisiones pendientes del usuario
- **A1:** en los bloques que se tocan, ¿se añaden al sistema los tokens que falten para conservar el
  aspecto exacto a 1440 (CA8 tal cual), o se ajustan al token más cercano (p. ej. `gap` 12 → 16px),
  aceptando cambios mínimos a 1440 (exige `/specify --edit 033` de CA8)?
- **A7:** ¿qué va antes, la 033 o la 020? La que vaya después actualiza la lista del menú de admin.

## Aceptados
(Ninguno todavía.)
