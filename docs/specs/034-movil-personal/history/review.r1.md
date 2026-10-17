---
spec: 034-movil-personal
verdict: changes_requested
round: 1
date: 2026-10-16
base: 5a802e6
head: d9518cf
human_signoff: no requerido
---

# Review · 034 Móvil para el personal y listas en tarjetas

## Resumen
La implementación cumple los 11 criterios y la verificación automática sale en verde (306 tests,
`--medir` sin fallos, herramientas de seguridad limpias), pero quedan dos importantes nuevos (R6: el
borde de 3:1 de los campos del filtro se pierde con el foco del ratón; R7: una aserción de
`MovilPersonalTest` pasaría con las columnas ocultas rotas) y cinco hallazgos de `/code-review` que
el usuario decidió corregir (R1–R5). Se corrigen con T042–T046; el resto de menores va al roadmap.
Conteo: bloqueante 0 · importante 4 · menor 18

Revisores: A (spec), C+D (constitución y calidad), S (seguridad) y accesibilidad, en subagentes de
contexto limpio sobre el paquete de `aidd.py review-pack` (5a802e6..d9518cf, `code.diff` 130 KB). Se
incorporan, verificados, los hallazgos de `/code-review 034` del 2026-10-16. El plugin `design` de
Anthropic no está instalado en esta sesión: la revisión de accesibilidad aplica la lista propia de la
skill.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 306 tests, 4988 aserciones, 0 fallos (línea base T002: 287) |
| `php artisan test --filter='MovilPersonalTest\|MovilPacienteTest'` (tests nuevos de UI, dos veces) | ✅ 46 y 46, sin intermitencias |
| `vendor/bin/pint --test` (PHP tocados: `MovilPacienteTest`, `MovilPersonalTest`) | ✅ |
| `node docs/design/capturar.mjs --medir` (AGENTS.md; línea base de v0.7.0 en la misma sesión, 46 capturas) | ✅ código 0, «medir: todo en orden» |
| `python .ai/bin/aidd.py validate docs/specs/034-movil-personal` | ✅ 0 errores |
| build | ➖ sin build: CSS y JS se sirven tal cual (`npm run build` solo para `welcome.blade.php`) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `MovilPersonalTest::test_las_tablas_incluidas_se_marcan_como_tarjetas`, `test_las_clases_de_celda_siguen_la_tabla_d2`, `test_movil_css_convierte_las_tablas_en_tarjetas` · `--medir` | ✅ | WebKit en T024; VoiceOver en T097 (plan) |
| CA2 | `test_las_tablas_incluidas_se_marcan_como_tarjetas`, `test_la_cita_de_hoy_se_distingue_sin_fondo_en_linea`, `test_el_panel_de_la_dentista_se_adapta_al_movil` · `--medir` | ✅ | R4 (svg sin `aria-hidden`) |
| CA3 | `test_cada_celda_con_etiqueta_tiene_un_solo_valor`, `test_el_nombre_de_la_tarjeta_de_pacientes_parte_de_linea`, `test_movil_css_convierte_las_tablas_en_tarjetas` | ⚠ | R7: la aserción de `.solo-escritorio` no fallaría con la regla rota |
| CA4 | `test_las_tablas_incluidas_se_marcan_como_tarjetas`, `test_los_botones_de_icono_tienen_nombre_y_texto_en_el_movil` | ⚠ | R1: las tarjetas de servicios quedan centradas; R7 |
| CA5 | `test_el_filtro_de_citas_tiene_el_orden_y_las_clases_del_plan`, `test_el_boton_filtros_indica_los_filtros_aplicados`, `test_filtros_js_cumple_su_contrato` · `--medir` | ⚠ | R2 (buscar sin botón visible), R3 (contador), R6 (borde con foco) |
| CA6 | `test_el_detalle_de_cita_sigue_el_orden_de_la_spec` · `--medir` | ✅ | R11: el test PHP no fija `#estado` antes de `#acciones` (sí `--medir`) |
| CA7 | `MovilPacienteTest` (textos), `test_movil_css_adapta_filtro_detalle_y_panel_de_la_dentista` · `--medir` (palabras de 30 letras) | ✅ | R8 (fuera de las pantallas incluidas) |
| CA8 | `--medir` (anchos, toque, solapes, sin JS) | ✅ | |
| CA9 | `test_movil_css_cambia_el_boton_menu_y_la_marca`, `MovilPacienteTest` | ✅ | R9 (`focusout`) |
| CA10 | `test_movil_css_cambia_el_boton_menu_y_la_marca`, `test_los_pares_nuevos_cumplen_aa` | ✅ | R5 (comentario), R15 (colores forzados) |
| CA11 | `test_el_bloque_034_de_app_css_reproduce_los_estilos_con_tokens` · `--medir` (escritorio contra línea base) | ⚠ | R6 (A36 con foco del ratón), R10 (bloque 034 sin cierre) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). T001, T015, T033 y T041 no salen
  en `code.diff` porque solo tocan documentación, `capturar.mjs` o capturas generadas (T033: 64
  archivos por comando).
- Partes del plan sin implementar: ninguna.
- T037 se añadió durante `/implement` (decisión del usuario, 2026-10-15) y dejó el análisis
  «desactualizado» respecto a las tareas; no cambia el diseño (toques de 44 × 44 que el plan ya
  exigía), por lo que no hace falta otra ronda de `/analyze`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | `impl_base` 5a802e6 = aprobación de spec, plan y tareas |
| P2 Test que falla antes y pasa después | ✅ | Notas de T010–T015 y T037 (fallaron antes); R7 y R11: aserciones que no protegen del todo |
| P3 Autorización en el servidor | ➖ | `git diff --stat 5a802e6..HEAD -- routes app database` vacío; `#control-interno` solo admin (`MovilPersonalTest.php:388`) |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas ni consultas nuevas |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas; capturas con la base de prueba anonimizada |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas; audits limpios |
| P7 Auditoría de accesos | ➖ | No toca autenticación ni permisos |
| P8 Correlación y logs sin datos sensibles | ➖ | Sin logs nuevos |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ⚠ | Tokens comprobados en tests; R6 (1.4.11 con foco del ratón), R1 (aspecto distinto a la opción A); deuda DS1 decidida (A59) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`5a802e6..HEAD`, 29 commits) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (`--error`) | ✅ código 0 |
| composer audit --locked · npm audit --audit-level=high | ✅ crít: 0 · alta: 0 |
| trivy fs 0.57.1 HIGH,CRITICAL | ✅ solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` (fuera de git y de la imagen), igual que en la 033 |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | Sin cambios en rutas, controladores ni middleware; el control interno sigue en `@if($rp === 'admin')` y su test lo prueba |
| A01/A02 Datos ocultos solo por CSS | ➖ | `solo-escritorio` oculta columnas que el mismo rol ya veía; el plan lo declara presentación, no control |
| A02 Configuración (CSP) | ✅ | `filtros.js` externo del mismo origen; sin scripts ni manejadores en línea nuevos |
| A03 Cadena de suministro | ✅ | `deps.stat` vacío |
| A05 Inyección / XSS | ✅ | Sin `{!! !!}` nuevos; `data-label` literales; `filtros.js` sin `innerHTML` ni HTML desde cadenas (`test_filtros_js_cumple_su_contrato`) |
| A06 Diseño inseguro | ✅ | Plan sin amenazas; supuestos probados |
| A07 Autenticación | ➖ | No se toca |
| A08 Integridad / CSRF | ✅ | El formulario PATCH movido conserva `@csrf` y `@method` |
| A09 Registro | ➖ | Sin logs ni eventos |
| A10 SSRF | ➖ | Sin URLs del usuario |
| Proceso | ✅ | Sin dependencias nuevas, rutas protegidas sin tocar, sin instrucciones dirigidas a agentes |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (el plan no define eventos).
- Datos sensibles en logs del código tocado: ninguno (sin logs nuevos).
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | resources/views/servicios/index.blade.php:63-75 | Las celdas de servicios conservan `text-align:center` en línea, que gana a `movil.css`: en las tarjetas, el nombre, las etiquetas y los valores quedan centrados, a diferencia de citas y pacientes (`servicios-index-390.png`) | `text-align` de las celdas a una clase `celda-centrada` en el bloque 034 de `app.css` y `.tabla-tarjetas td { text-align: start }` en `movil.css` |
| R2 | importante | resources/views/citas/index.blade.php:59-73 | En el móvil con JS, «Filtrar» queda en el panel plegado: el buscador visible no tiene botón para enviarse (solo Intro/Ir del teclado) | Botón «Buscar» (`type="submit"`, `solo-movil`) junto al buscador |
| R3 | menor | resources/views/citas/index.blade.php:49-54 | El contador no cuadra con el filtrado: con «Desde» = hoy enviado no cuenta (y el controlador quita el límite de 7 días); «Esta semana» cuenta 1 el lunes y 2 el resto de días | Las fechas cuentan como un solo filtro, aplicado si `fecha_desde` o `fecha_hasta` vienen rellenos |
| R4 | menor | resources/views/dashboard/dentista.blade.php:101 | El `<svg>` del enlace «Ver detalle» de la línea de tiempo no lleva `aria-hidden="true"`, a diferencia del resto de iconos de la spec | Añadir `aria-hidden="true"` |
| R5 | menor | resources/views/layouts/app.blade.php:20 | El comentario dice «marca de texto», pero desde D10 es el logo con máscara | Corregir el comentario |
| R6 | importante | public/css/citas.css:29 frente a public/css/app.css:575 | `.field:focus { border-color: var(--p300) }` (≈ 2,2:1) gana a `.filtro-citas .field` (misma especificidad, `citas.css` carga después): al enfocar un campo del filtro con el ratón, sin `:focus-visible`, el borde baja de 3:1 (A36, RNF 1.4.11). Ningún test ni `--medir` mira el borde con foco | `.filtro-citas .field:focus { border-color: var(--border-control); box-shadow: none; }` en el bloque 034, como `.formulario-accesible .field:focus` (app.css:512), con su aserción |
| R7 | importante | tests/Feature/MovilPersonalTest.php:458 / public/css/movil.css:202 | `assertDeclara('.solo-escritorio', 'display: none')` pasa aunque se borre `.tabla-tarjetas .solo-escritorio`, y sin ese selector `.tabla-tarjetas td { display: grid }` (0,1,1) vuelve a mostrar las columnas ocultas | Afirmar también `.tabla-tarjetas .solo-escritorio` |
| R8 | menor | public/css/movil.css:30 | Con `.content` en `break-word` (D8), las pantallas del personal fuera de la 034 pierden la protección de la 033/R1 ante palabras largas; hoy ya se desbordan por el resto de DS8 | Roadmap: la spec del resto del móvil del personal aplica `texto-partible` o `anywhere` en sus filas flexibles |
| R9 | menor | public/js/menu.js:74 | El `focusout` de T023 también olvida `ultimoFoco` cuando la ventana pierde el foco (cambio de app o pestaña); al volver tras cruzar el punto de corte, el foco no se reubica (R3 de la 033) | Roadmap: ignorar el `focusout` cuando `document.hasFocus()` es falso |
| R10 | menor | public/css/app.css:561 / MovilPersonalTest `bloque034()` | El bloque 034 no tiene cierre `/* Fin spec 034 */`: la próxima spec que añada reglas al final entrará en las comprobaciones de la 034 | Añadir el cierre y cortar `bloque034()` en él, como `bloque033()` |
| R11 | menor | tests/Feature/MovilPersonalTest.php:384-386 | El test de D7 comprueba que existen `#estado` y `#acciones`, no su orden (CA6) | XPath con `following::` |
| R12 | menor | tests/Feature/MovilPacienteTest.php:216-221 | «Las reglas globales van antes de la media query» usa `strpos` del primer selector en todo el CSS: un selector repetido o un `false` dan falso positivo | Roadmap: posición por regla desde `reglasCss()` y `assertNotFalse` |
| R13 | menor | resources/views/citas/cliente-index.blade.php:58-62 / public/css/movil.css:188 | En «Mis citas», «Cancelar» (dentro de `form.form-en-linea`) no se estira como «Ver» y «Reprogramar» | Roadmap: estirar `.celda-acciones .form-en-linea` y su botón |
| R14 | menor | citas/index:181; pacientes/index:163,172; servicios/index:85,92 | «Ver detalle», «Ver perfil», «Editar» y «Eliminar» se repiten en cada tarjeta sin decir de qué elemento son (2.4.4/2.4.9) | Roadmap: contexto en el nombre accesible, empezando por el texto visible |
| R15 | menor | public/css/movil.css:61-68 | Con colores forzados (alto contraste de Windows), el logo pintado con `background` y `mask` desaparece o queda como un bloque | Roadmap: `@media (forced-colors: active) { .sidebar-marca { forced-color-adjust: none; } }` |
| R16 | menor | resources/views/citas/show.blade.php:92,142,187-198 | En el detalle (bloques movidos, no nuevos): «Cambiar estado», «Información» y «Control interno» no son encabezados; la `label` de las notas internas no tiene `for`; el `svg` del control interno no tiene `aria-hidden` | Roadmap: `<h2 class="section-title">`, `for`/`id` y `aria-hidden` |
| R17 | menor | resources/views/citas/show.blade.php:53 | `.detalle-datos-rejilla` conserva `style="display:grid;gap:12px"` (D10b lo permite: no impide el móvil) | Roadmap: a la clase con un token |
| R18 | menor | tests/Feature/MovilPersonalTest.php, MovilPacienteTest.php:593-650 | Ayudantes (`xpath`, `movil`, `assertDeclara`) duplicados entre los dos tests y aserciones de `.sidebar-marca` y del par `--p600`/`--white` repetidas | Roadmap: al trait `Concerns/ReglasCss` o uno nuevo |
| R19 | menor | resources/views/citas/show.blade.php:39-180 | Sangría incoherente: tres hijos de `.detalle-cita` a 8 espacios y `detalle-lateral` a 4 | Roadmap: reindentar |
| R20 | menor | resources/views/citas/index.blade.php:59-90 | Los `id` de los campos (`filtro-buscar`…) repiten las clases de sus contenedores | Roadmap: `campo-buscar`, `campo-desde`… |
| R21 | menor | resources/views/servicios/index.blade.php:93 (anterior a la 034) | `onclick="abrirModalEliminar('…', '{{ addslashes(…) }}')"`: hoy seguro, pero exigirá `unsafe-inline` con la CSP de la 031 y no sigue P4 (`@js`) | Roadmap: `data-*` y script propio en la spec de la CSP |
| R22 | menor | public/css/movil.css:157-175 | Al cambiar el `display` de la tabla, VoiceOver (WebKit) puede perder la semántica de tabla y fila; el `thead` oculto saca los `th` del árbol | Ya previsto: verificación con VoiceOver o TalkBack en T097 (plan, WebKit); si se pierde, roles explícitos en una spec siguiente |

Descartado: «Contacto» en la cabecera frente a «Teléfono» en el `data-label` de pacientes es la
excepción decidida en T010 (plan D2).

## Preparación para release
- Rollback factible: sí; solo vistas, CSS, JS y tests, sin migraciones: volver a v0.7.0 restaura todo.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide.
- Docs actualizadas: `architecture.md` (T090); `deployment.md` y `observability.md` sin cambios (T091, no aplica); roadmap (T041).
- SCA sin críticas ni altas.
- Cobertura de riesgos: la spec no cita riesgos `RS`, `OB` ni `RD`; DS8 queda «parcial (033, 034)» en `system.md`.

## Tareas añadidas
- T042 ← R2, R3, R4, R11 (tests de las vistas)
- T043 ← R1, R6, R7, R10 (tests de estilos)
- T044 ← R1, R2, R3, R4 (vistas)
- T045 ← R1, R5, R6, R10 (estilos y comentario del layout)
- T046 ← verificación final de la ronda (`--medir` obligatorio por tocar `movil.css` y el layout)

## Aceptados sin tarea
- **R8** → roadmap: `.content` con `break-word` (D8) deja sin `anywhere` las pantallas del personal fuera de la 034; la spec del resto del móvil del personal lo resuelve en sus filas flexibles (decisión del usuario, 2026-10-16).
- **R9** → roadmap: el `focusout` de `menu.js` olvida `ultimoFoco` al cambiar de app o pestaña; ignorarlo con `document.hasFocus()` falso (decisión del usuario, 2026-10-16).
- **R12** → roadmap: el test de orden de reglas globales de `MovilPacienteTest` usa `strpos` del primer selector.
- **R13** → roadmap: «Cancelar» no se estira en las tarjetas de «Mis citas».
- **R14** → roadmap: nombres accesibles de las acciones de tarjeta sin contexto del elemento.
- **R15** → roadmap: logo con máscara invisible con colores forzados.
- **R16** → roadmap: encabezados, `label` sin `for` y `svg` sin `aria-hidden` en los bloques movidos del detalle de cita.
- **R17** → roadmap: `style=""` de `.detalle-datos-rejilla` a la clase con un token.
- **R18** → roadmap: ayudantes y aserciones duplicados entre `MovilPersonalTest` y `MovilPacienteTest`.
- **R19** → roadmap: sangría de `citas/show.blade.php`.
- **R20** → roadmap: `id` de los campos del filtro iguales a clases de sus contenedores.
- **R21** → roadmap: `onclick` con `addslashes` en servicios, a `data-*` en la spec de la CSP.
- **R22** → roadmap: semántica de tabla en VoiceOver; se comprueba en T097 y, si se pierde, roles explícitos en una spec siguiente.
