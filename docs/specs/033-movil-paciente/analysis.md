---
result: fail
round: 2
mode: full
constitution_version: 1.2.1
date: 2026-10-11
spec_sha: 80f7408c1dd2
plan_sha: da8f46ca5af3
tasks_sha: 5d2558497264
---

# Análisis · 033 Móvil para el paciente

## Resumen
`fail`: hay 2 hallazgos ALTOS que el usuario decide corregir. Ninguna regla muestra «Cerrar menú» en
el móvil, así que el panel se abriría sin él (A28). Tres comprobaciones automatizables de P9 no
tienen test (A32).

Los 26 hallazgos de la ronda 1 están resueltos; en 8 de ellos, la corrección abrió un hallazgo
nuevo (A27–A39). Análisis completo, por las 483 líneas cambiadas, con un subagente independiente;
cada hallazgo se ha comprobado en el código.
Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 7 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 |
| Amenazas con control y test | 0 / 0 (el plan no tiene amenazas) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A27) | Tabla «Bloques tocados», D11 y 8 tokens; T001, T020 |
| A2 | ALTA | resuelto | T013 `test_las_reglas_nuevas_de_app_css_solo_usan_tokens` |
| A3 | ALTA | resuelto | D5, «Sin JS»; T011 `test_sin_js_el_menu_queda_en_el_flujo`; `--medir` sin JS |
| A4 | ALTA | resuelto | `--duration-fast`, `--topbar-h`, `z-index` fuera de P9; T011 con `s`/`ms` |
| A5 | ALTA | resuelto con efecto nuevo (→ A32) | `aria-current` en T010 y `--medir`; la marca que no es color, sin test |
| A6 | ALTA | resuelto con efecto nuevo (→ A31) | `min-width: 0` global; `--medir` a 900 y 1024 |
| A7 | ALTA | resuelto con efecto nuevo (→ A38) | Rollout, nota de T010, T090 |
| A8 | MEDIA | resuelto | Línea base a 1440 en T014; T028 |
| A9 | MEDIA | resuelto | `.sidebar-marca`; T023 |
| A10 | MEDIA | resuelto con efecto nuevo (→ A29) | `overflow-y` en `.sidebar`, `100dvh` |
| A11 | MEDIA | resuelto | Menú a 320 y 390 |
| A12 | MEDIA | resuelto | Etiqueta a ≤ 16 px; caracterización con reprogramar |
| A13 | MEDIA | resuelto | `app.css` declarado en T023–T025 |
| A14 | MEDIA | resuelto | T013 antes de T024/T025; T026 con `bloqueo:` |
| A15 | MEDIA | resuelto | `--medir` como test de comportamiento; rotura en T023 |
| A16 | MEDIA | resuelto | `.btn-logout` y foco en T011 |
| A17 | MEDIA | resuelto | T003 con condiciones anidadas |
| A18 | MEDIA | resuelto | D7 y T014 |
| A19 | MEDIA | resuelto | Velo fuera de `<main>` |
| A20 | MEDIA | resuelto con efecto nuevo (→ A33) | Casillas excluidas; `.campo-casilla` no agranda la zona |
| A21 | MEDIA | resuelto | T002 y T013 |
| A22 | BAJA | resuelto | `getComputedStyle`; T012 prohíbe `max-width` |
| A23 | BAJA | resuelto | D9, Riesgos y CSP corregidos |
| A24 | BAJA | resuelto | `test_la_pagina_tiene_viewport` propio |
| A25 | BAJA | resuelto | T040 en una línea |
| A26 | BAJA | resuelto | Estados «en curso (033, …)» |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A27 | Inconsistencia (CA8) | MEDIA | plan «Bloques tocados»; T024, T025 | La columna de destino omite declaraciones de hoy: `display:flex` de `.pestanas-citas` y `.migas`, `align-items:center` de `.migas` y `text-decoration:none` de `.pestanas-citas a` | Cada clase nueva reproduce todas las declaraciones de su `style=""` (test de T013 con la lista completa por selector) |
| A28 | Cobertura (CA3) | ALTA | plan «Con JS»; T011; `--medir` | `.menu-cerrar` queda oculto por la regla global y nada lo muestra en el móvil. El panel se abriría sin «Cerrar menú», y con un lector o al tocar no hay Escape. Ningún test lo detecta | Con JS y el menú abierto, a 320, 390 y 844 × 390, «Cerrar menú» se ve, mide ≥ 44 × 44, tiene nombre, cierra y devuelve el foco (T011 y paso de `--medir`, comprobado rompiendo) |
| A29 | Cobertura de riesgos (CA7) | MEDIA | plan «Con JS»; `app.css:63`; T011, T097 | Con JS no se anula `min-height: 100vh` de `.sidebar`; en el móvil `100vh` es el alto mayor y «Cerrar sesión» puede quedar inalcanzable; Playwright no lo ve | El panel abierto no supera el alto visible (T011 sobre el `min-height` efectivo) y se comprueba en horizontal en un móvil real (T097) |
| A30 | Cobertura (CA4) | MEDIA | plan «Toque»; T025 | En `.migas a` solo se pide `min-height`: «Inicio» mide ≈31 px de ancho | Cada enlace de migas y pestañas mide ≥ 44 × 44 a 390 (T011 y `--medir`) |
| A31 | Cobertura de riesgos (escritorio) | MEDIA | plan «Fuera de toda media query»; T014 | `min-width: 0` global afecta a las vistas del personal desde 900 px, que no se comparan | Entre 900 y 1440 las vistas del personal se ven como antes (capturas a 1024 y 1440 de la galería del personal, antes y después) |
| A32 | Constitución (P9) | ALTA | plan P9 y Trazabilidad; T010, T011, T013 | Sin test: (1) la sección actual y la pestaña activa con una señal que no es color; (2) el contraste de los colores del bloque `/* Spec 033 */` de `app.css`; (3) que `.menu-btn` y `.menu-velo` no tengan `style=""` | Tests en T011 y T013 con control positivo para las tres |
| A33 | Cobertura (CA4) | MEDIA | plan «Toque»; `reprogramar.blade.php:111-115`; T026 | `.campo-casilla { min-height }` no agranda el `<label>` ni la casilla; `--medir` fallará en reprogramar y bloqueará T026 | La zona pulsable de la casilla (la etiqueta) mide ≥ 44 px de alto a 390 (`--medir` midiendo el `<label>` y T011) |
| A34 | Cobertura (CA1) | MEDIA | plan Estrategia «Anchos»; `.welcome-banner` con `overflow:hidden`; `dashboard/cliente.blade.php:7-15` | `--medir` no detecta controles recortados por un antecesor con `overflow:hidden`: a 320 el banner del panel recortaría «Mi perfil» | A 320 y 390, cada control y texto visible de `.main` queda entero dentro de la pantalla y de sus antecesores que recortan (`--medir` con control positivo) |
| A35 | Ambigüedad (P9) | MEDIA | plan «Hoja `movil.css`» y Layout; T021, T023 | No se define el aspecto de `.menu-btn`, `.menu-cerrar` ni `.sidebar-marca`, ni la colocación en `.sidebar-logo`; la maqueta usa valores sin token (marca de 16 px y peso 600, icono de 18 px) | Cada propiedad visual sale de un token (los que falten, en T001); sus pares de color entran en el test de contraste |
| A36 | Inconsistencia | BAJA | plan Trazabilidad frente a T010 | Tres tests de T010 no salen en Trazabilidad; el de viewport sostiene CA1 | Cada test en la Trazabilidad; T010 cubre CA1 |
| A37 | Ambigüedad | BAJA | T010 `test_la_seccion_actual_lleva_aria_current`; T024 | «Solo el enlace actual» sin ámbito: tras T024 la pestaña activa también lleva `aria-current` | La aserción se limita al `<aside>` |
| A38 | Conflicto (020) | BAJA | T090; Rollout | La nota para la 020 no dice que «Auditoría» siga el patrón nuevo (`aria-current`) | La nota del roadmap lo dice |
| A39 | Inconsistencia | BAJA | T002 [P], T003 [P]; T040 | La línea base de tests de T002 puede tomarse con o sin `ReglasCssTest` | La línea base se toma antes de cualquier test nuevo (T003 depende de T002) |

## Decisiones pendientes del usuario
Respondida el 2026-10-11: se corrigen los 13 hallazgos con `/plan --fix` y `/tasks --fix`, con dos
tokens más para A35 (icono y marca) y la comparación de las capturas del personal para A31.

## Aceptados
(Ninguno.)
