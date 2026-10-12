---
spec: 033-movil-paciente
verdict: approved
round: 2
date: 2026-10-12
base: 15a7a28
head: f4e40bb
human_signoff: no requerido
---

# Review · 033 Móvil para el paciente

## Resumen
`approved`. Los hallazgos de la ronda 1 que el usuario decidió corregir están corregidos: R1, R2, R3–R5, R7, R8 y R10 (T060–T067). Durante la implementación salieron y se corrigieron, con decisión del usuario, otros dos problemas:
- **T068–T069:** una regresión de T061 en las tablas del personal.
- **T070–T072:** un defecto intermitente del foco al abrir el menú, que venía de T030.

En esta ronda solo hay 3 menores nuevos (R20–R22), aceptados para el roadmap.

Ronda `--rerun` sobre `15a7a28..f4e40bb` (código 24,9 KB). Como el diff es pequeño, la revisión la hice yo, releyendo el diff y los archivos desde cero, sin subagentes.

Conteo: bloqueante 0 · importante 0 · menor 3

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 287 en verde (2949 aserciones): 285 de la ronda 1 + 2 de T065 |
| `MovilPacienteTest` y `ReglasCssTest`, dos veces | ✅ 31 y 31 en verde (657 aserciones) |
| `vendor/bin/pint --test tests/Feature/MovilPacienteTest.php` | ✅ |
| `node --check` de `public/js/menu.js` y `docs/design/capturar.mjs` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/033-movil-paciente` | ✅ 0 errores |
| `node docs/design/capturar.mjs --medir` (obligatorio por `AGENTS.md`) | ✅ «todo en orden» dos veces seguidas en T067, con el mismo código de `head` (después solo cambiaron documentos) |
| Foco al abrir el menú, 120 aperturas con clic (T072) | ✅ 0 fallos (antes de T072: 8 de 60) |
| `npm run build` | ➖ la spec no toca Vite |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `MovilPacienteTest` (marco, rejillas, bloques) · `--medir` | ✅ | |
| CA2 | `MovilPacienteTest` (botón, secciones, `aria-current`, sin JS) · `--medir` | ✅ | |
| CA3 | `test_menu_js_cumple_su_contrato` (`enfocar`, `cancelAnimationFrame`, `pageshow`, `resize`), `test_el_nav_del_menu_tiene_nombre_y_sus_iconos_son_decorativos`, `test_los_controles_del_menu_tienen_foco_visible` · `--medir` · NVDA (T023, T066) | ✅ | R21 |
| CA4 | `test_los_controles_tienen_toque_minimo` · `--medir` | ✅ | |
| CA5 | `MovilPacienteTest` · `--medir` | ✅ | Teclados del móvil en T097 |
| CA6 | `test_los_textos_largos_parten_de_linea` (`anywhere` en `.content`, `break-word` en sus tablas) · navegador (T061, T069) | ✅ | R20 |
| CA7 | `test_el_menu_se_recorre_con_poca_altura` · `--medir` | ✅ | |
| CA8 | `test_fuera_del_movil_solo_hay_las_reglas_previstas`, bloque 033 · capturas (T069) | ✅ | La regla de foco nueva solo cambia el estado con foco |

## Seguimiento de la ronda 1
| Hallazgo | Estado | Evidencia |
|---|---|---|
| R1 (CA6) | ✅ corregido | `movil.css:20-23`; en el navegador a 320, detalle y panel a 320 px con palabras de 30 letras (control `break-word`: 398 y 349). R20, para las tablas |
| R2 (CA3) | ✅ corregido | `AGENTS.md:57-60`; docblock de `test_menu_js_cumple_su_contrato`. Job de CI → R19 (roadmap) |
| R3, R4, R5 | ✅ corregidos | `menu.js` (`enfocar`, `cancelAnimationFrame`, `pageshow`, `resize` con `ultimoFoco`); casos (a)–(d) en el navegador, con control del `menu.js` anterior |
| R7, R8 | ✅ corregidos | `app.css` (bloque 033), `layouts/app.blade.php` (20 SVG y `<nav aria-label="Secciones">`); NVDA OK (usuario) |
| R10 | ✅ corregido (comentario) | `movil.css:3-5`; ampliar `literales()` sigue en el roadmap |
| R6, R9, R11–R19 | aceptados (ronda 1) | sin cambios; el código nuevo no los empeora |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md`: 5 archivos de código, todos citados por tareas (T060–T072).
- Partes del plan sin implementar: ninguna. `enfocar()` (T072) sustituye al doble `requestAnimationFrame` del plan («Script `menu.js`», T030), con el mismo objetivo (foco en la sección actual al abrir) y medido.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes | ✅ | T060, T063, T065, T068 y T071 fallaron antes de su cambio (notas en `tasks.md`); `--medir` comprobado rompiendo en T070 |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas; la base de prueba y la carpeta temporal se borraron (T067) |
| P9 Solo tokens · WCAG 2.1 AA | ✅ | Regla nueva de `app.css` y de `movil.css` sin literales; anillo `--focus-ring` (p600, 7:1) |
| Resto | ➖ | Sin datos, permisos ni dependencias |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 `--log-opts=15a7a28..HEAD` | ✅ 14 commits, sin fugas |
| semgrep 1.99.0 `p/php` (`MovilPacienteTest.php`) | ✅ 0 hallazgos |
| `composer audit` · `npm audit` | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy 0.57.1 `fs` HIGH/CRITICAL | ✅ solo `vendor/mockery/mockery/docs/requirements.txt`, ya descartado en la ronda 1 (fuera de git y de la imagen) |

| Tema OWASP (Top 10:2025) | Resultado | Evidencia |
|---|---|---|
| A05 Inyección / XSS | ✅ | `menu.js` sigue sin `innerHTML`/`eval`; el layout solo añade atributos fijos |
| A02 Configuración (CSP) | ✅ | Sin scripts, `on*` ni `style=` nuevos en línea |
| A01, A03, A04, A06–A10 | ➖ | Sin cambios de permisos, datos, dependencias ni registro |
| Proceso | ✅ | `AGENTS.md` (ruta protegida) cambiado por T062, aprobado por el usuario el 2026-10-12 |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica.
- Datos sensibles en logs del código tocado: ninguno; no hay logs nuevos.
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R20 | menor | `public/css/movil.css:23` · `citas/cliente-index.blade.php:25-48` · `dashboard/cliente.blade.php:101-118` | Las tablas del paciente vuelven a `break-word` (T069): una palabra sin espacios muy larga en el servicio o en el dentista hace que la tabla se desplace dentro de `.table-wrap` (`overflow-x: auto`), en lugar de partirse como pide la letra de CA6. La página no se ensancha y es como estaba antes de T061 | Que la 034, al rehacer las tablas para el móvil, las pase a tarjetas o parta su texto |
| R21 | menor | `public/js/menu.js:69-84` | `ultimoFoco` no se borra al hacer clic en una zona sin foco. Al cruzar los 900 px, el foco puede ir a «Menú» o al enlace actual cuando el usuario ya no estaba en el menú | Borrar `ultimoFoco` en `focusout` cuando el foco no va a otro elemento, salvo que el elemento se oculte por el cambio de modo |
| R22 | menor | `docs/design/capturar.mjs:465` | El comentario dice que el foco entra «tras dos fotogramas»; desde T072, `menu.js` lo reintenta hasta diez | Actualizar el comentario |

## Preparación para release
- Rollback factible: sí, `deploy/volver.sh <tag anterior>`. No hay migraciones; los cambios son estáticos y de vista.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no hay (plan).
- Docs actualizadas: `architecture.md` (T090) sigue siendo válido. `deployment.md` no aplica (T091). `AGENTS.md` incluye la regla de `--medir`.
- SCA: sin críticas ni altas.
- Cobertura de riesgos: DS6, DS8 y DS10 se atienden **parcialmente**, como declara la spec; ninguno figura como mitigado.

## Tareas añadidas
- Ninguna en esta ronda.

## Aceptados sin tarea
- **R6** → roadmap: migas de pan sin `<nav>` con nombre ni `aria-current`, con el enlace indistinguible del texto actual (cambia `system.md`).
- **R9** → roadmap: «Menú» con `aria-expanded` dentro de `<main>` inerte; el estado abierto no se percibe en el botón.
- **R10** → roadmap: ampliar `literales()` a colores con nombre, `hsl()` y unidades relativas, con DS4 (el comentario de `movil.css` se corrigió en T061).
- **R11** → roadmap: condición de `request()->is()` repetida en los 19 enlaces del menú; `aria-current` solo probado en el panel.
- **R12** → roadmap: límites del analizador de CSS de `ReglasCss` (sentencias `@` sin bloque, cadenas, anidado, `:is()`).
- **R13** → roadmap: `movil()` duplica `declaraciones()`; docblock de esta.
- **R14** → roadmap: `bloque033()` hasta el final de `app.css` y selectores repetidos que se pisan.
- **R15** → roadmap: orden de `movil.css` comprobado con `strpos` sobre el CSS crudo.
- **R16** → roadmap: la comprobación de la casilla solo mira la media query del móvil.
- **R17** → roadmap: `ReglasCss` de `Tests\Feature\Concerns` usado desde un test unitario.
- **R18** → roadmap: `<script>` en línea y `onclick` con la contraseña generada en el layout (bloquean quitar `'unsafe-inline'`, RS8.d).
- **R19** → roadmap: ejecutar `capturar.mjs --medir` en la CI con Playwright.
- **R20** → roadmap: tablas del paciente con una palabra muy larga se desplazan por dentro en lugar de partirse (para la spec 034, móvil del personal y tablas).
- **R21** → roadmap: `ultimoFoco` de `menu.js` no se borra al perder el foco; salto de foco al cruzar los 900 px.
- **R22** → roadmap: comentario de `capturar.mjs` sobre «dos fotogramas» desactualizado.
