---
spec: 048-citas-panel-detalle
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-14
---

# Plan · 048 Abrir las citas del panel de inicio

## Enfoque técnico
Son cambios de marcado y de CSS en las cuatro vistas de panel; no cambian rutas, controladores,
consultas, datos ni permisos. Cada cita recibe **un solo enlace** a su detalle, puesto en el dato
principal que ya existe: la hora (administración y asistente), la fecha (historial del paciente) o
el ojo «Ver detalle» (dentista). Un pseudoelemento `::after` del enlace, posicionado sobre la fila,
tarjeta o elemento (`position: relative` en el contenedor), hace que pulsar en cualquier parte lo
abra sin enlaces anidados ni JavaScript, y sin añadir celdas a las tablas (la 034 exige una celda
por columna). El aspecto al pasar el puntero y el foco salen de tokens que ya existen. El detalle
es el de cada rol (`<rol>.citas.show`), con la autorización de hoy.

## Constitution Check
| Principio | Resultado | Cómo se cumple | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada | ✅ | Spec 048 `approved` (2026-11-14); el código empieza con `tasks.md` aprobado | manual: `aidd.py status` |
| P2 Test que falla antes | ✅ | Los enlaces de administración, asistente e historial no existen hoy: sus tests fallan antes. El acceso denegado al detalle (CA8) ya se cumple: tests de caracterización comprobados rompiendo `autorizadoParaCita()` y el filtro por paciente | test: T010–T013 |
| P3 Autorización en servidor | ✅ | Sin endpoints nuevos ni modificados; el detalle conserva su comprobación (`CitasController::show` con `autorizadoParaCita`, `ClienteCitasController::show` filtrando por paciente). El enlace es solo navegación | test: T013 (dentista y asistente con cita ajena → 403; paciente con cita ajena → 404; desde la `href` del panel) |
| P4 Entrada validada, bindings | ✅ | Sin entradas nuevas; la `href` se construye con `route()` y el identificador de la cita; el `aria-label` lleva el nombre que escribe el paciente y se imprime con el eco escapado de Blade (P4) | test: T012 (nombre con comillas y `<` escapado en el atributo) |
| P5 Nada sensible en el repo | ✅ | Sin datos nuevos; los tests usan el escenario sintético `EscenarioEstadosCita` | lint: gitleaks en T092 |
| P6 Sin vulnerabilidades altas | ✅ | Sin dependencias nuevas | test: `composer audit` y `npm audit` en `/review` y `/release` |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas; abrir el detalle ya existe y sus denegaciones ya se auditan (spec 020) | — |
| P8 Correlación y logs | ➖ | Sin logs nuevos | — |
| P9 Solo el sistema de diseño | ✅ | Reglas nuevas en un bloque `/* Spec 048 */` de `app.css`, solo con tokens; ningún `style=""` nuevo; el componente «Cita que abre su detalle» entra antes en `system.md` (T001); WCAG AA (foco ≥ 3:1, toque ≥ 44 px) | test: T014 (bloque sin literales según `ReglasCss::literales()`, contraste de `--focus-ring` ≥ 3:1 sobre `--white` y `--p50`, sin fondo en la tarjeta del móvil), T012 (sin `style=""` en `.fila-cita` ni `.cita-enlace`); manual: T020–T023 en pantalla y `design/medir.mjs` (T024) |
| P10 Rutas indexables | ➖ | Los paneles exigen sesión y no se indexan | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `docs/design/system.md`, `docs/design/system.html` | Componente «Cita que abre su detalle» en «Lista, tabla y tarjeta»; versión siguiente a la vigente al fusionar (1.17.0 si nadie la toma) | bajo |
| `public/css/app.css` | Bloque `/* Spec 048 */`: `.fila-cita { position: relative }`; `.cita-enlace` de texto en reposo con el color de su celda (`color: inherit`), sin subrayado, también `:visited` (el ojo de la dentista conserva su `.btn-ghost`); `.cita-enlace::after { position: absolute; inset: 0; cursor: pointer }`; el fondo al pasar por encima es, en el escritorio, el `tr:hover td` de hoy en las tablas y `--p50` en el `.timeline-item.fila-cita` de la dentista (que hoy no tiene), dentro de `@media (min-width: 900px)`; en el móvil no se añade ninguno (A57 de la 034 sigue en `movil.css`); el `:focus-visible` del enlace no dibuja anillo propio (sin tocar el bloque 034) y el `::after` lo dibuja por dentro (`outline-offset` negativo), con `--radius` en la tarjeta (dentro de `@media (max-width: 899px)` del mismo bloque, sin tocar `movil.css`) | medio: `position: relative` en `<tr>` (ver Riesgos) |
| `resources/views/dashboard/admin.blade.php` | En «Citas de hoy», `<tr class="fila-cita">` y la hora dentro de `<a class="cita-enlace">` a `admin.citas.show` (marcado en «Contratos y datos») | bajo |
| `resources/views/dashboard/asistente.blade.php` | Igual con `asistente.citas.show` | bajo |
| `resources/views/dashboard/dentista.blade.php` | `.timeline-item.fila-cita`; el ojo existente lleva `cita-enlace`, el nombre nuevo y su `href` con `route('dentista.citas.show')` (hoy relativa, escrita a mano); «Próximo paciente» no cambia | bajo |
| `resources/views/dashboard/cliente.blade.php` | En «Historial», `<tr class="fila-cita">` y la fecha dentro del enlace a `cliente.citas.show` | bajo |
| `tests/Feature/CitasPanelDetalleTest.php` (nuevo) | Enlaces por rol, nombre accesible, una parada por cita, estado vacío, acceso denegado, CSS con tokens | — |
| `tests/Feature/MovilPersonalTest.php` | `test_el_panel_de_la_dentista_se_adapta_al_movil`: el ojo se llama ahora «HH:MM, paciente, ver detalle» y se busca por la `href` de `route()` (034 → 048) | bajo |
| `docs/specs/048-citas-panel-detalle/design/medir.mjs` (nuevo) | Medición en navegador (Playwright) de los cuatro paneles a 320, 390 y 1440 px | — |

## Contratos y datos
Sin cambios de rutas, respuestas, esquema ni migraciones.

**Marcado de cada cita** (la misma forma en las tres tablas):
- la fila: `<tr class="fila-cita">`;
- en su `td.celda-cab` (que conserva `data-label` y sus clases), el texto de la hora dentro de
  `<a class="cita-enlace">`, con `href` = `route('<rol>.citas.show', $cita->id)` y `aria-label` =
  «<hora>, <paciente>, ver detalle»;
- en el historial del paciente, la fecha y «<fecha>, <servicio o el mismo «—» de la celda>, ver detalle»;
- en la dentista, el enlace es el ojo (`btn-icono`) con el mismo nombre que las tablas, que empieza
  por la hora de su `.timeline-time`;
- las cuatro vistas construyen la `href` con `route()` (A7).

**Pantallas y estados:**
| Pantalla | Estado | Qué se ve |
|---|---|---|
| Cada panel | con citas | lo mismo que hoy; puntero y foco sobre la fila o tarjeta |
| Cada panel | sin citas | el estado vacío de hoy, sin enlaces nuevos (CA7) |
| Detalle | cita de otro | la respuesta de hoy: 403 al personal sin alcance, 404 al paciente (CA8) |

**Qué ve cada rol:** no cambia; cada uno abre solo lo que abre hoy desde su «Citas».

## Estrategia de pruebas
- **Feature (PHPUnit, `CitasPanelDetalleTest`)** con el escenario de `EscenarioEstadosCita` y el reloj
  fijo (como `MovilPersonalTest`): por rol, una cita de hoy (o pasada para el paciente) aparece con
  exactamente un `<a>` dentro de su `<tr>`/`.timeline-item`, con la `href` de su detalle, la clase
  `cita-enlace`, el nombre accesible que empieza por el texto visible, y la fila con `fila-cita`;
  ningún `<a>` dentro de otro; la tabla conserva una celda por columna; sin citas, ningún
  `cita-enlace`; seguir la `href` del panel da 200 con el detalle de esa cita; la misma ruta con una
  cita ajena da 403 (dentista, asistente) o 404 (paciente).
- **CSS (PHPUnit, trait `ReglasCss`)**: el bloque `/* Spec 048 */` existe, `literales()` no encuentra
  valores literales (con control positivo), y tiene el contenedor con `position: relative`, el
  `::after` con `inset: 0` y cursor, el reposo sin subrayado y con color heredado, el foco en el
  `::after` con `--focus-ring` (y `--radius` por debajo de 900 px) y sin anillo propio en el enlace;
  ninguna regla 048 da fondo a la fila por debajo de 900 px; contraste de `--focus-ring` ≥ 3:1
  sobre `--white` y `--p50` con `contraste()`.
- **Navegador (`design/medir.mjs`, Playwright 1.63.0 en la imagen de `mcr.microsoft.com/playwright`
  ya usada por la 045)**: en los cuatro paneles a 320, 390 y 1440 px, pulsar en la celda «Servicio»
  (o en el nombre, en la dentista) lleva al detalle; en el móvil cada cita mide ≥ 44 px de alto;
  sin desplazamiento lateral; Tab da una parada por cita con un solo anillo; el color y el subrayado
  de la hora son los de antes. A 1440 px también con WebKit y Firefox. No sustituye a la CI: se
  ejecuta en `/implement` (T024) y en `/review`.
- **Manual**: el usuario compara los paneles con los de producción (CA9) en T020–T023.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Punto de entrada | Control | Test |
|---|---|---|---|---|
| TM1 | Divulgación: alguien cambia el identificador de la `href` del panel para ver una cita que no le toca (OWASP A01) | `GET /{rol}/citas/{id}` | El de hoy, sin cambios: `autorizadoParaCita()` para el personal (403) y el filtro por paciente para el cliente (404) | T013 (caracterización, comprobada rompiendo el control) |

La 048 no añade entradas: el resto de STRIDE no aplica (sin escritura, sin datos nuevos, sin
cambios de identidad).

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: administración abre la cita desde «Citas de hoy» | `dashboard/admin`, `app.css` | `CitasPanelDetalleTest::test_el_administrador_abre_la_cita_desde_el_panel` · navegador: `medir.mjs` (pulsar «Servicio») · verificación manual: panel a 320, 390 y 1440 px contra producción (T020) |
| CA2: asistente abre la cita desde «Agenda de hoy» | `dashboard/asistente`, `app.css` | `…::test_la_asistente_abre_la_cita_desde_el_panel` · `medir.mjs` · verificación manual (T021) |
| CA3: dentista abre la cita desde cualquier parte; «Próximo paciente» igual | `dashboard/dentista`, `app.css`, `MovilPersonalTest` | `…::test_la_dentista_abre_la_cita_desde_la_agenda` (incluye «Próximo paciente») · `medir.mjs` (pulsar el nombre) · verificación manual (T022) |
| CA4: paciente abre una cita del historial; «Tu próxima cita» igual | `dashboard/cliente`, `app.css` | `…::test_el_paciente_abre_una_cita_del_historial` (incluye «Ver detalles», «Reprogramar» y «Cancelar» de la próxima) · `medir.mjs` · verificación manual (T023) |
| CA5: una parada de foco, foco visible, nombre accesible, Intro abre | vistas, `app.css` | `…::test_cada_cita_tiene_un_solo_enlace_con_nombre_accesible` · `medir.mjs` (Tab y contraste del foco) |
| CA6: aspecto al pasar el puntero | `app.css` | `…::test_las_reglas_048_usan_tokens` (cursor; sin fondo en la tarjeta del móvil) y `MovilPersonalTest` (A57) · verificación manual (T020–T023) |
| CA7: estado vacío sin enlaces | vistas | `…::test_sin_citas_el_panel_no_tiene_enlaces_de_cita` |
| CA8 (abuso): cita ajena → respuesta de hoy | ninguno (control existente) | `…::test_una_cita_ajena_desde_la_href_del_panel_se_niega_como_hoy` (caracterización) |
| CA9: solo cambian puntero y foco; columnas y móvil iguales | vistas, `app.css` | `…::test_las_tablas_del_panel_conservan_sus_columnas` y `MovilPersonalTest` (sin cambios salvo el nombre del ojo) · `medir.mjs` (sin desplazamiento lateral) · verificación manual: comparar con producción (T020–T023) |
| TM1 | ninguno | ídem CA8 |
| RNF: WCAG 2.1 AA (foco, toque ≥ 44 px en el móvil, contraste) | `app.css` | `…::test_las_reglas_048_usan_tokens` (contraste del foco) · `medir.mjs` |
| RNF: sin JavaScript | vistas | `CitasPanelDetalleTest` (enlaces en el HTML del servidor; ningún `<script>` nuevo) |
| RNF: sin consultas nuevas | ninguno | manual: el diff no toca `DashboardController` (T092 y `/review`) |

## Observabilidad
No aplica: la 048 no añade logs, eventos de auditoría ni métricas; abrir el detalle ya existe y sus
denegaciones ya se registran (spec 020).

## Descubribilidad
No aplica: los cuatro paneles exigen sesión (sin ella redirigen al inicio de sesión), no son indexables y no cambian (`docs/seo.md`).

## Rollout
- Sin feature flag: cambio visual pequeño, sin datos ni permisos; el riesgo es bajo y se revierte con
  la versión anterior.
- Orden: un solo despliegue (`deploy/desplegar.sh vX.Y.Z`), sin migraciones.
- Compatibilidad: no hay estado compartido entre versiones.
- Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia de base de datos.
- Métricas: `5xx` en `/admin`, `/asistente`, `/dentista` y `/cliente` la primera hora (no debería
  cambiar nada); `verificar.sh` como siempre.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Incógnita | Opciones | Decisión y motivo |
|---|---|---|---|
| D1 | Cómo hacer que toda la fila abra la cita | (a) `onclick` en la fila con JavaScript; (b) un enlace en cada celda; (c) un solo enlace con `::after` que cubre la fila | (c): funciona sin JavaScript (RNF), da una sola parada de foco (CA5) y no añade celdas a las tablas que la 034 fija (una celda por columna). (a) no es accesible con teclado sin más trabajo; (b) da varias paradas por cita |
| D2 | Dónde va el enlace | la hora / fecha; el nombre del paciente; una celda nueva | La hora (o fecha) del `celda-cab`: es el dato principal de la tarjeta del móvil, y el nombre del paciente ya tiene su propio marcado de dos líneas. En la dentista, el ojo que ya existe |
| D3 | Nombre accesible | solo el texto visible; `aria-label` completo | `aria-label` que empieza por el texto visible (WCAG 2.5.3) y sigue con paciente (o servicio) y «ver detalle» |
| D4 | Dónde va el CSS del móvil | `movil.css`; `app.css` dentro de `@media (max-width: 899px)` | `app.css`: todo el componente en un bloque; no se toca `movil.css`, así que no hace falta `capturar.mjs --medir` (AGENTS.md), y `medir.mjs` mide los paneles en el móvil |
| D5 | Versión del sistema de diseño | 1.17.0 fija; la siguiente al fusionar | La siguiente a la vigente al fusionar: la 046 (otra sesión) también la sube |
| D6 | Test del ojo de la 034 | conservar «Ver detalle»; cambiarlo | Cambiarlo: el nombre debe decir de qué cita es (CA5), y el test de la 034 lo asertaba literal, igual que su `href` relativa (A7) |
| D7 | Fondo al pasar el puntero en la tarjeta del móvil (/analyze A1) | `--p50`; ninguno; borde más marcado | Ninguno: se mantiene A57 de la 034 (decisión del usuario, 2026-11-14) |
| D8 | 44 px de alto en el escritorio (/analyze A4) | también en el escritorio; solo en el móvil | Solo en el móvil, como el sistema (D8 de la 033); CA9 no deja cambiar la altura de la fila (decisión del usuario, 2026-11-14) |
| D9 | Seleccionar texto de la fila (/analyze A14) | fila entera como enlace; solo hora y nombre | Fila entera; el teléfono y el nombre se copian desde el detalle (decisión del usuario, 2026-11-14) |

## Impacto en arquitectura
Ninguno en `docs/architecture.md` (sin módulos, rutas ni datos nuevos). `docs/design/system.md` y
`system.html` reciben el componente (T001). `AGENTS.md` no cambia.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| `position: relative` en un `<tr>` de tabla (escritorio) no posiciona el `::after` en algún navegador antiguo | Los navegadores de la constitución (Chromium, Firefox, Safari actuales) lo soportan; `medir.mjs` pulsa la celda más alejada del enlace en Chromium, WebKit y Firefox a 1440 px. Si fallara, la hora sigue siendo un enlace normal (degradación aceptable) |
| El `::after` tapa algo pulsable de la fila | Las filas del panel no tienen otros controles (comprobado en las cuatro vistas); el test asegura un solo `<a>` y ningún `<button>` por cita |
| `.table-wrap` (`overflow-x: auto`) y `.card-0` (`overflow: hidden`) recortan el anillo de foco | `outline-offset` negativo (por dentro) |
| Choque con la 046 en `system.md`, `system.html` y `app.css` | Bloques separados; la segunda en fusionarse renumera la versión del sistema |
| Línea base de dependencias (2026-11-14): `composer audit` da un aviso **bajo** de `laravel/framework` (CVE-2026-128083, corregido en 12.71.0; hoy 12.69.2), ajeno a la 048 | No bloquea `/release` (solo críticos y altos; la CI ignora bajos y medios); actualizarlo con un `/chore` aparte cuando la versión cumpla los 7 días |
| `.timeline` también es el historial de la cita (spec 028) | Las reglas se limitan a `.fila-cita`, que solo lleva la agenda del panel |
