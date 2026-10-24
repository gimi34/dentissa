---
spec: 033-movil-paciente
verdict: changes_requested
round: 1
date: 2026-10-12
base: be400bb
head: 15a7a28
human_signoff: no requerido
---

# Review · 033 Móvil para el paciente

## Resumen
`changes_requested`. No hay bloqueantes: los 8 criterios tienen test y la verificación automática está en verde. El usuario decide corregir antes de liberar los dos importantes:
- **R1:** palabras largas sin espacios en filas y rejillas flexibles (CA6).
- **R2:** el comportamiento de `menu.js` solo lo comprueba `--medir`, que la CI no ejecuta (CA3).

También se corrigen ahora cuatro menores (R3–R5, R7, R8 y R10). El resto va al roadmap.

Insumo: cuatro subagentes independientes (spec; constitución y calidad; seguridad; accesibilidad) sobre el paquete de `aidd.py review-pack` (73 KB de código). Cada hallazgo se comprobó en el código. Uno de los importantes del revisor de calidad se rebajó a menor (R10, ver la fila).

Conteo: bloqueante 0 · importante 2 · menor 17

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 285 en verde (2881 aserciones); línea base de T002: 256 + 29 nuevos |
| `php artisan test` de `MovilPacienteTest`, `ReglasCssTest` y `ReprogramarCitaVistasTest`, dos veces | ✅ 54 y 54 en verde (1036 aserciones), sin intermitencias |
| `vendor/bin/pint --test` (los 4 PHP tocados) | ✅ |
| `npm run build` | ➖ la spec no toca `resources/js` ni Vite (`menu.js` y `movil.css` se sirven desde `public/`) |
| `python .ai/bin/aidd.py validate docs/specs/033-movil-paciente` | ✅ 0 errores |
| `node docs/design/capturar.mjs --medir` | ✅ «todo en orden» el 2026-10-11 (T040); no se repite: no hay cambios de código desde entonces |
| Lector de pantalla (NVDA, 4 roles) | ✅ verificación manual del usuario, 2026-10-12 (T023) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `MovilPacienteTest::test_movil_css_adapta_el_marco_y_las_rejillas`, `test_los_bloques_tocados_no_tienen_estilos_en_linea`, `test_la_pagina_tiene_viewport` · `--medir` | ✅ | |
| CA2 | `test_cada_rol_tiene_el_boton_menu`, `test_el_menu_cerrado_no_ocupa_espacio`, `test_el_menu_del_movil_tiene_las_mismas_secciones`, `test_la_seccion_actual_lleva_aria_current`, `test_sin_js_el_menu_queda_en_el_flujo` · `--medir` | ✅ | R11: `aria-current` solo se prueba en el panel de cada rol |
| CA3 | `test_menu_js_cumple_su_contrato`, `test_los_controles_del_menu_tienen_foco_visible` · `--medir` · NVDA | ⚠ | R2: el test de PHPUnit solo comprueba texto. R3–R5: casos límite del foco |
| CA4 | `test_los_controles_tienen_toque_minimo` · `--medir` (cajas a 390) | ✅ | |
| CA5 | `test_movil_css_adapta_el_marco_y_las_rejillas`, `test_los_campos_conservan_su_tipo` · `--medir` | ✅ | Teclados del móvil en T097 |
| CA6 | `test_los_textos_largos_parten_de_linea` · `--medir` con textos largos | ⚠ | R1: `break-word` no parte una palabra sin espacios dentro de una fila o rejilla flexible |
| CA7 | `test_el_menu_se_recorre_con_poca_altura`, `test_el_menu_se_abre_visible_y_se_desplaza_entero` · `--medir` a 844 × 390 | ✅ | |
| CA8 | `test_fuera_del_movil_solo_hay_las_reglas_previstas`, `test_las_reglas_nuevas_de_app_css_solo_usan_tokens` · capturas a 1440 frente a la línea base | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` no tiene archivos sin tarea; T001, T002, T014 y T028 solo tocan documentación, configuración o capturas, que quedan fuera de `code.diff`.
- Partes del plan sin implementar: ninguna. Las vistas «solo si `--medir` las señala» no hicieron falta (nota de T026). Lo comprobé además con un script: los 19 enlaces del menú usan el mismo patrón de `request()->is()` para `.active` y para `aria-current`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1–P4, P6–P8 (datos, permisos, errores, dependencias) | ➖ | No toca controladores, rutas, datos ni dependencias (`deps.stat` vacío) |
| P2 Test que falla antes y pasa después | ✅ | Tests de T010–T013 escritos antes (notas de las tareas); T029 y T030, comprobados rompiendo |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas; las capturas salen de la base desechable `dentissa_capturas` |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ con notas | Revisé a mano `movil.css` y el bloque 033 de `app.css`: solo tokens, salvo `85vw`, `100dvh` y `±100%`, documentados en `system.md:242`. La constitución fija `/review` como verificación y deja el lint pendiente (DS4). R10: el detector `literales()` y el comentario de `movil.css` |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 `--log-opts=be400bb..HEAD` | ✅ 23 commits, sin fugas |
| semgrep 1.99.0 `p/php` (los 4 PHP tocados, todos tests) | ✅ 0 hallazgos |
| `composer audit` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ para la spec. Único aviso: `vendor/mockery/mockery/docs/requirements.txt`, que son los requisitos Python de la documentación de una dependencia de desarrollo. Está fuera de git y fuera de la imagen (`.dockerignore` excluye `vendor`). Descartado como falso positivo para el proyecto |

| Tema OWASP (Top 10:2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ➖ | El `<aside>` de cada rol mantiene sus ramas `@if`; el menú del móvil es el mismo nodo |
| A04 Criptografía y datos sensibles | ➖ | No toca datos sensibles |
| A05 Inyección / XSS | ✅ | Sin `{!! !!}` nuevos; clases y `aria-current` salen de booleanos; `menu.js` sin `innerHTML` ni `eval` (lo exige el test) |
| A06 Diseño inseguro | ✅ | El modelo de amenazas «no aplica» se sostiene: script propio, del mismo origen, sin dependencias |
| A02 Configuración (CSP) | ✅ | Sin scripts, `on*` ni `style=` nuevos en línea; `menu.js` encaja con `script-src 'self'`. R18: el código en línea anterior a la spec |
| A03 Componentes | ✅ | Sin dependencias nuevas |
| A07 Autenticación · A08 Integridad · A09 Registro · SSRF | ➖ | Sin cambios |
| A10 Errores | ✅ | `menu.js` sale sin hacer nada si falta un nodo; sin JS el menú queda en el flujo |
| Proceso (rutas protegidas, instrucciones a agentes) | ✅ | El rango no toca rutas protegidas; sin instrucciones sospechosas |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica. La spec no toca datos sensibles, autenticación ni permisos.
- Datos sensibles en logs del código tocado: ninguno; no añade logs.
- `request_id` presente en los logs nuevos: no aplica, porque no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `public/css/movil.css:19` | `overflow-wrap: break-word` no reduce el ancho mínimo de un hijo de fila o rejilla flexible. Pasa con el nombre del dentista en la rejilla de 2 columnas del detalle (`citas/cliente-show.blade.php:49`, ~80 px por celda a 320) y con el servicio en `flex:1` del panel (`dashboard/cliente.blade.php:37`). Ahí una palabra larga sin espacios ensancha la página (CA6). `--medir` pasó porque los datos de prueba tienen espacios, y el test solo comprueba la declaración | `overflow-wrap: anywhere` en `.content` (como ya hace `.user-name`), con su test antes |
| R2 | importante | `tests/Feature/MovilPacienteTest.php:361-380` · `.github/workflows/ai-dd.yml:79` | `test_menu_js_cumple_su_contrato` busca cadenas. Una lógica rota con las mismas palabras (Escape que no cierra, `inert` que no se quita) pasa la CI, que no ejecuta `--medir` | Anotar en `AGENTS.md` que `--medir` es obligatorio en las specs que tocan el layout, `menu.js` o `movil.css`, y decir en el test que solo comprueba texto (el job de CI va a R19) |
| R3 | menor | `public/js/menu.js:51-54` | Al cruzar los 900 px, el foco puede quedar en un elemento que se oculta y pasa a `<body>`: (a) con el menú abierto y el foco en «Cerrar menú», al pasar al escritorio; (b) en el escritorio, con el foco en un enlace del menú, al ampliar el zoom por debajo de 900 px | Al cambiar de modo, si el foco queda en un elemento que deja de verse, llevarlo al enlace actual (escritorio) o a «Menú» (móvil) |
| R4 | menor | `public/js/menu.js:29` | El foco aplazado de `abrir()` no se cancela: con Intro y Escape seguidos, el foco acaba en un enlace del panel que se cierra | Guardar el id del `requestAnimationFrame` y cancelarlo en `cerrar()` |
| R5 | menor | `public/js/menu.js:8-55` | Sin `pageshow`, al volver con «Atrás» la página puede salir de la caché del historial con el menú abierto y `<main>` inerte | Con `pageshow` y `persisted`, cerrar sin mover el foco |
| R6 | menor | `public/css/app.css:546-547` · `citas/cliente-agendar.blade.php:9-12` · `citas/cliente-show.blade.php:28-31` | En las migas, el enlace no se distingue del texto actual (`--text-muted` = `--text` = `#000000`, sin subrayado); no van en un `<nav>` con nombre ni llevan `aria-current`. Mismos valores que antes de la spec | Envolverlas en un `<nav aria-label>`, marcar `aria-current` y distinguir el enlace; cambia `system.md` («Migas de pan») |
| R7 | menor | `public/css/app.css:541,546` | `.pestanas-citas a` y `.migas a` usan el anillo de foco del navegador, no el del sistema (`--focus-ring`). Cumple 2.4.7, pero no es coherente con `system.md` («Foco») | Reglas `:focus-visible` con los tokens de foco |
| R8 | menor | `layouts/app.blade.php:29-193` | El `<nav>` del panel no tiene nombre, y los SVG decorativos de los enlaces y de «Cerrar sesión» no llevan `aria-hidden`; la maqueta elegida (`design/opcion-a.html:81`) sí. TalkBack o VoiceOver pueden anunciarlos como imagen sin nombre | `aria-label="Secciones"` en el `<nav>` y `aria-hidden="true"` en esos SVG |
| R9 | menor | `layouts/app.blade.php:210` · `public/js/menu.js:23-25` | «Menú» lleva `aria-expanded`, pero está dentro de `<main>`, que queda inerte al abrir: el estado «expandido» no se percibe con el menú abierto. NVDA lo dio por bueno (T023) | Decisión de diseño de la spec (D: `inert` en `<main>`); revisarla si un lector la echa en falta |
| R10 | menor | `public/css/movil.css:3-4` · `tests/Feature/Concerns/ReglasCss.php:63-81` | El comentario dice que «el único literal es el punto de corte», pero hay `85vw`, `100dvh` y `±100%`. `literales()` no detecta colores con nombre, `hsl()`, `rem`/`em` ni `vw`/`vh` (el revisor lo dio como importante; rebajado porque la constitución fija `/review` como verificación de P9, el lint está pendiente en DS4 y el código no tiene esas violaciones) | Corregir el comentario ahora; ampliar `literales()` con DS4 |
| R11 | menor | `tests/Feature/MovilPacienteTest.php:45-50,103-115` · `layouts/app.blade.php:32-170` | `aria-current` solo se prueba en el panel de cada rol, y el layout repite la condición de `request()->is()` en cada uno de los 19 enlaces. Hoy coinciden todas (comprobado), pero una errata en otra sección no la detectaría ningún test | Calcular la condición una vez por enlace y recorrer en el test las 7 pantallas del paciente |
| R12 | menor | `tests/Feature/Concerns/ReglasCss.php:23-46` | El analizador no maneja `@import`/`@charset` sin bloque, llaves dentro de cadenas, CSS anidado ni comas dentro de `:is()`/`:not()`. Hoy no se usan | Documentar las limitaciones y fallar ante una sentencia `@` sin bloque |
| R13 | menor | `tests/Feature/MovilPacienteTest.php:495-507` · `ReglasCss.php:49-50` | `movil()` repite la búsqueda de `declaraciones()`, y el docblock de esta dice «en `app.css`», aunque recibe el archivo | Parámetro de condiciones en `declaraciones()` y docblock corregido |
| R14 | menor | `tests/Feature/MovilPacienteTest.php:533-540,414-416` | `bloque033()` llega hasta el final de `app.css`: lo que añadan specs futuras quedaría sujeto a los tests de la 033. Los selectores repetidos se pisan sin aviso | Comentario de cierre del bloque y fallo ante selectores repetidos |
| R15 | menor | `tests/Feature/MovilPacienteTest.php:212-213` | El orden «globales antes de la media query» se comprueba con `strpos` sobre el CSS con comentarios | Usar el orden de `reglasCss()` |
| R16 | menor | `tests/Feature/MovilPacienteTest.php:222` | La comprobación de que la casilla no se estira solo mira las reglas de la media query del móvil | Recorrer todas las reglas de `movil.css` |
| R17 | menor | `tests/Unit/ReglasCssTest.php:6` | Un test unitario depende de un trait de `Tests\Feature\Concerns` | Mover el trait a `tests/Concerns/` |
| R18 | menor | `layouts/app.blade.php:227,260` (anterior a la spec, fuera del rango) | El layout tiene un `<script>` en línea y un `onclick` que mete `{{ $pg['password'] }}` en una cadena JS escapada para HTML, no para JS. Por eso la CSP necesita todavía `'unsafe-inline'` en `script-src` | Pasarlos a un JS externo con `data-*` y `@js()`, y quitar `'unsafe-inline'` (RS8.d) |
| R19 | menor | `.github/workflows/ai-dd.yml:79` | Parte de R2: la CI no ejecuta `capturar.mjs --medir` | Job de CI con Playwright y la base de prueba |

## Preparación para release
- Rollback factible: sí. `deploy/volver.sh <tag anterior>`, sin copia de la base: no hay migraciones y los estáticos van en la imagen.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno; el escritorio no cambia (CA8).
- Docs actualizadas: sí. `architecture.md` lo actualizó T090; T091 «no aplica» (sin variables, pasos ni logs).
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: la spec atiende DS6, DS8 y DS10 **parcialmente**, como declara. `system.md` los deja «en curso»; ninguno figura como mitigado.

## Tareas añadidas
- T060 ← R1 (test)
- T061 ← R1, R10
- T062 ← R2
- T063 ← R3, R4, R5 (test)
- T064 ← R3, R4, R5
- T065 ← R7, R8 (test)
- T066 ← R7, R8

## Aceptados sin tarea
- **R6** → roadmap: migas de pan sin `<nav>` con nombre ni `aria-current`, con el enlace indistinguible del texto actual (cambia `system.md`).
- **R9** → roadmap: «Menú» con `aria-expanded` dentro de `<main>` inerte; el estado abierto no se percibe en el botón.
- **R11** → roadmap: condición de `request()->is()` repetida en los 19 enlaces del menú; `aria-current` solo probado en el panel.
- **R12** → roadmap: límites del analizador de CSS de `ReglasCss` (sentencias `@` sin bloque, cadenas, anidado, `:is()`).
- **R13** → roadmap: `movil()` duplica `declaraciones()`; docblock de esta.
- **R14** → roadmap: `bloque033()` hasta el final de `app.css` y selectores repetidos que se pisan.
- **R15** → roadmap: orden de `movil.css` comprobado con `strpos` sobre el CSS crudo.
- **R16** → roadmap: la comprobación de la casilla solo mira la media query del móvil.
- **R17** → roadmap: `ReglasCss` de `Tests\Feature\Concerns` usado desde un test unitario.
- **R18** → roadmap: `<script>` en línea y `onclick` con la contraseña generada en el layout (bloquean quitar `'unsafe-inline'`, RS8.d).
- **R19** → roadmap: ejecutar `capturar.mjs --medir` en la CI con Playwright.
- **R10** (parte) → roadmap: ampliar `literales()` a colores con nombre, `hsl()` y unidades relativas, con DS4.
