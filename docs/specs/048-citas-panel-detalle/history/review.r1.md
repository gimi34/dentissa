---
spec: 048-citas-panel-detalle
verdict: changes_requested
round: 1
date: 2026-11-14
base: a69c10b
head: f794c12
human_signoff: pending
---

# Review · 048 Abrir las citas del panel de inicio

## Resumen
`changes_requested`: sin bloqueantes; dos importantes, los dos de tests que no prueban todo lo que
dicen (R1: «Tu próxima cita» solo comprueba «Ver detalles»; R2: el nombre accesible del historial
solo con una cita sin servicio). El usuario decide corregirlos ahora junto con los menores baratos
(R3–R6, R12, R16–R18); los demás menores van al roadmap. Cuatro revisores independientes (spec,
constitución y calidad, seguridad, accesibilidad); sin revisor de SEO (paneles privados). Las
skills `design:accessibility-review` y `design:design-critique` no están en la sesión: el revisor
de accesibilidad aplicó la lista de `/review`.
Conteo: bloqueante 0 · importante 2 · menor 17

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, código de `f794c12`) | ✅ 1011 en verde (15 532 aserciones); línea base 1001 + 10 de la 048 |
| `CitasPanelDetalleTest` (nuevo) | ✅ en verde en varias pasadas durante `/implement` |
| `vendor/bin/pint --test` (tests tocados) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/048-citas-panel-detalle` | ✅ 0 errores |
| `design/medir.mjs` (Chromium 320/390/1440, WebKit y Firefox 1440, sin JS) | ✅ 186 comprobaciones |

## Criterios de aceptación
| Criterio | Test | Estado |
|---|---|---|
| CA1, CA2 | `test_el_administrador_abre_…`, `test_la_asistente_abre_…`, `medir.mjs` | ✅ |
| CA3 | `test_la_dentista_abre_la_cita_desde_la_agenda`, `MovilPersonalTest`, `medir.mjs` | ✅ |
| CA4 | `test_el_paciente_abre_una_cita_del_historial` | ⚠️ R1: no prueba «Reprogramar» ni «Cancelar» |
| CA5 | `test_cada_cita_tiene_un_solo_enlace_con_nombre_accesible`, `medir.mjs` | ⚠️ R2 (historial con servicio sin probar), R6 (Intro) |
| CA6 | `test_las_reglas_048_usan_tokens`, `medir.mjs` (dentista) | ⚠️ R5 (el `tr:hover` de las tablas sin test) |
| CA7 | `test_sin_citas_el_panel_no_tiene_enlaces_de_cita` | ⚠️ R3 (el vacío del historial sin comprobar) |
| CA8 | `test_una_cita_ajena_desde_la_href_del_panel_se_niega_como_hoy` | ✅ (caracterización, comprobada rompiendo los dos controles) |
| CA9 | `test_las_tablas_del_panel_conservan_sus_columnas`, `MovilPersonalTest`, `medir.mjs`, verificación manual | ✅ |

## Conformidad con el plan
- Alcance: 7 archivos de código, todos con tarea; T001 y T024 son documentación (`system.md`,
  `system.html`, `medir.mjs`, fuera de `code.diff`). Ningún cambio en `app/`, `routes/` ni `database/`.
- Desvíos anotados en las tareas: `@php … @endphp` en bloque en las cuatro vistas (T022), nombre
  «sin servicio» en el historial (A11, T023), `literales()` sobre los cuerpos de las reglas (T019).
- R4: el radio del anillo por debajo de 900 px también se aplica a la agenda de la dentista, que no
  es tarjeta (el acta lo pide solo para la tarjeta).

## Constitution Check (sobre el código)
P1–P8 y P10 sin hallazgos (P4: `aria-label` con eco escapado, probado con comillas y `<`; P7 y P8:
sin auditoría ni logs nuevos). P9: el bloque 048 solo usa tokens (`--focus-ring*`, `--radius`,
`--p50`), sin `style=""` nuevos; el toque y el foco reales los mide `medir.mjs`, que no corre en la
CI (declarado en el plan). Los puntos de corte de las `@media` son literales como en el resto de
`app.css` (R19).

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (13 archivos del diff) | ✅ sin hallazgos |
| semgrep 1.99.0 `p/php` (vistas y tests) | ✅ 0 hallazgos |
| `composer audit` | ✅ sin críticos ni altos; un aviso bajo previo de `laravel/framework` (CVE-2026-128083), ajeno a la 048 |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |

OWASP sobre el diff: A01 ✅ (TM1: el control de hoy no cambia; su test lo prueba con control
positivo y comprobado rompiendo `autorizadoParaCita()` y el filtro por paciente), A03 ✅ (atributos
con `{{ }}`, sin `{!! !!}` ni `<script>` nuevos), divulgación ✅ (el `aria-label` repite lo que la
fila ya muestra), superposición del `::after` ✅ (sin otros controles en la fila); CSRF, A02, A04–A08,
A10 ➖; A06 ➖ (sin dependencias nuevas). Proceso: sin instrucciones dirigidas a agentes ni
caracteres invisibles en el diff. RS2.a (grupo `/cliente` sin `role:cliente`) sigue pendiente en
`security.md` y la 048 no lo cambia.

## Observabilidad
No aplica: la 048 no añade logs, eventos de auditoría ni métricas; las denegaciones del detalle ya se
registran (spec 020).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Feature/CitasPanelDetalleTest.php:94-96` | CA4: el test solo comprueba «Ver detalles» de «Tu próxima cita»; la próxima cita del escenario está a menos de 24 h, así que «Reprogramar» y «Cancelar» no se pintan y su ausencia no haría fallar nada | Una cita del paciente a más de 24 h que sea la próxima y comprobar los tres controles de `#acciones-proxima` hacia esa cita |
| R2 | importante | `tests/Feature/CitasPanelDetalleTest.php:113-114`; `cliente.blade.php:117` | El nombre del historial solo se prueba con una cita sin servicio (siempre «sin servicio»): si la vista ignorara el servicio, pasaría | Una cita pasada con servicio y su nombre «dd/mm/aaaa, <servicio>, ver detalle» |
| R3 | menor | `tests/Feature/CitasPanelDetalleTest.php:155` | CA7: el panel del paciente sin historial no comprueba su estado vacío («Aún no tienes citas anteriores») | Comprobar ese texto |
| R4 | menor | `public/css/app.css:880` | El radio del anillo por debajo de 900 px se aplica también al `.timeline-item` de la dentista, que no es tarjeta | Limitar la regla a la tarjeta (`.tabla-tarjetas`) |
| R5 | menor | `CitasPanelDetalleTest.php:216-253`; `medir.mjs` | CA6 en las tablas depende del `tr:hover td` de antes y nada lo comprueba | Test de que `tr:hover td` declara `background: var(--p50)` |
| R6 | menor | `design/medir.mjs:136-148` | CA5 «Intro la abre» no se mide | Pulsar Intro con el foco en la cita y comprobar el destino |
| R7 | menor | `app.css:876`; `dentista.blade.php:70` | En el escritorio, el fondo y el anillo de la agenda de la dentista quedan 20 px hacia dentro (el `.timeline` tiene `padding` en línea) | → roadmap |
| R8 | menor | `cliente.blade.php:115` | Dos citas pasadas del mismo día y servicio tendrían el mismo nombre con destinos distintos (2.4.4, en el límite) | → roadmap (añadir el dentista al nombre) |
| R9 | menor | `admin.blade.php:71`, `asistente.blade.php:72`, `cliente.blade.php:115` | En la navegación por celdas, la celda «Hora» se lee con el paciente, que repite la columna siguiente | → roadmap (coste aceptado de un enlace por cita) |
| R10 | menor | `app.css:862-866` | El `::after` depende de que nada de la fila esté posicionado; una pieza posicionada futura quedaría encima | → roadmap (regla en `system.md` o `z-index` con token) |
| R11 | menor | `CitasPanelDetalleTest.php:320-334` | `xpath()` y `clases()` copiados de `MovilPersonalTest` (y `xpath()` en otros 7 tests) | → roadmap (trait `Concerns/ConsultaDom`) |
| R12 | menor | `CitasPanelDetalleTest.php:296-318` | `assertAbreLaCitaDesdeLaTabla` devuelve un valor que nadie usa | `void` |
| R13 | menor | `CitasPanelDetalleTest.php:326-333` | `libxml_use_internal_errors(true)` sin restaurar (patrón de todo el repo) | → roadmap (con R11) |
| R14 | menor | `CitasPanelDetalleTest.php:228-245` | Se comprueba que el bloque declara cada propiedad, no que gane en la cascada | → roadmap (lo mide `medir.mjs`, fuera de la CI) |
| R15 | menor | `CitasPanelDetalleTest.php:217-254` | Un solo test con unas 20 aserciones de grupos distintos | → roadmap (dividirlo) |
| R16 | menor | `CitasPanelDetalleTest.php:141` | Línea de ~250 caracteres con un cierre anidado | Sacar `$etiquetas` a una variable |
| R17 | menor | `CitasPanelDetalleTest.php:252` | La comprobación de que cada regla nombra `fila-cita` no dice por qué | Mensaje con el motivo (plan: reglas limitadas a `.fila-cita`) |
| R18 | menor | `dentista.blade.php:85,87` | Un `@php` suelto para `$hora` junto al bloque `@php` de `$dotColor` del mismo `@foreach` | Unirlos |
| R19 | menor | `app.css` (bloque 048) | Los puntos de corte `900px`/`899px` son literales, como en el resto de `app.css` | → roadmap (documentar el punto de corte en `system.md`) |

## Preparación para release
- Rollback factible: `deploy/volver.sh <tag anterior>`, sin migraciones ni datos.
- Sin feature flags (plan: no hacen falta).
- `docs/architecture.md` y `docs/deployment.md` sin cambios (T090, T091).
- Sin vulnerabilidades críticas ni altas.

## Tareas añadidas
| Tarea | Hallazgo |
|---|---|
| T025 | R1 |
| T026 | R2 |
| T027 | R3, R5 |
| T028 | R4 (test) |
| T029 | R4 |
| T030 | R6 |
| T031 | R12, R16, R17, R18 |

## Aceptados sin tarea
- **R7** → roadmap: el fondo y el anillo de la agenda de la dentista llegan a los bordes de la tarjeta en el escritorio (hoy 20 px hacia dentro)
- **R8** → roadmap: el nombre accesible del historial distingue dos citas del mismo día y servicio (p. ej. con el dentista)
- **R9** → roadmap: la celda «Hora» no repite el paciente en la navegación por celdas (p. ej. `aria-describedby`)
- **R10** → roadmap: regla «nada posicionado dentro de `.fila-cita`» en `system.md` o `z-index` del `::after` con token
- **R11** → roadmap: trait `Concerns/ConsultaDom` con `xpath()` y `clases()` para los tests que los copian
- **R13** → roadmap: restaurar `libxml_use_internal_errors` en ese trait
- **R14** → roadmap: comprobar en la CI que las reglas 048 ganan en la cascada (hoy solo `medir.mjs`)
- **R15** → roadmap: dividir `test_las_reglas_048_usan_tokens` por grupos
- **R19** → roadmap: documentar en `system.md` el punto de corte de 900 px
