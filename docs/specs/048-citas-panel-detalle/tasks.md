---
spec: 048-citas-panel-detalle
plan: plan.md
impl_base: a69c10b
status: approved
---

# Tareas · 048 Abrir las citas del panel de inicio

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza.

Tests con `docker compose exec app php artisan test --filter <Clase>` (suite completa al cerrar cada
fase) y Pint sobre los archivos tocados. Ningún test toca `DashboardController`, rutas ni datos.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ninguna tarea de código antes de aprobar este `tasks.md`.
- ✅ P2: cada tarea de vista depende de su test (T010–T012), que falla antes; T013 es de
  caracterización y se comprueba rompiendo el control; T014 falla antes de T019.
- ✅ P3: ninguna tarea cambia rutas, controladores ni autorización; T013 fija la respuesta de hoy.
- ✅ P5: gitleaks sobre el diff en T092; los tests usan el escenario sintético.
- ✅ P9: T001 (sistema) va antes que el CSS (T019) y las vistas (T020–T023); T014 exige solo
  tokens; ninguna tarea añade `style=""`; T024 mide toque, foco y desbordes.
- ➖ P4, P6, P7, P8, P10: sin entradas, dependencias, auditoría, logs ni rutas indexables nuevas (plan).

## Preparación
- [x] T001 Añadir el componente «Cita que abre su detalle» (reposo, puntero `--p50`, foco con `--focus-ring` por dentro, nombre accesible «texto visible, paciente o servicio, ver detalle», una sola parada) en «Lista, tabla y tarjeta», con la versión siguiente a la vigente y su fila en el historial de versiones — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los dos documentos describen el componente del acta («Cambios a incorporar al sistema») y `python .ai/bin/aidd.py validate` no da errores
  - nota: 2026-11-14 — `system.md` 1.17.0 (componente en «Lista, tabla y tarjeta» y su fila en Decisiones) y `system.html` con una muestra viva (`#cita-048`, estilos `componentes-048` con los tokens de `.opt`); validate del repo sin errores. Si la 046 se fusiona antes y toma la 1.17.0, esta pasa a la siguiente

## Tests (antes de implementar)
- [x] T010 Escribir `test_el_administrador_abre_la_cita_desde_el_panel` y `test_la_asistente_abre_la_cita_desde_el_panel` (reloj fijo y `EscenarioEstadosCita`, como `MovilPersonalTest`): la fila de la cita de hoy lleva `fila-cita` y exactamente un `<a class="cita-enlace">` dentro de su `td.celda-cab`, con `href` a `<rol>.citas.show` de esa cita; seguir esa `href` da 200 con el detalle de la cita — `tests/Feature/CitasPanelDetalleTest.php` — hecho cuando: los dos fallan por la ausencia del enlace — cubre: CA1, CA2
  - nota: 2026-11-14 — fallan los dos por «la fila de la cita tiene un enlace a su detalle» (no hay enlace). Línea base de la suite antes de empezar: 1001 en verde (15 342 aserciones)
- [x] T011 Escribir `test_la_dentista_abre_la_cita_desde_la_agenda` (el `.timeline-item` de la cita lleva `fila-cita`, su único `<a>` es el ojo con `cita-enlace` y `href` al detalle; «Próximo paciente» conserva su enlace a la misma cita) y actualizar en `test_el_panel_de_la_dentista_se_adapta_al_movil` el nombre esperado del ojo a «HH:MM, paciente, ver detalle» y su búsqueda por la `href` de `route('dentista.citas.show')` (plan D6, A7) — `tests/Feature/CitasPanelDetalleTest.php`, `tests/Feature/MovilPersonalTest.php` — hecho cuando: los dos fallan por la clase, la `href` y el nombre del ojo — cubre: CA3, CA5 — depende: T010
  - nota: 2026-11-14 — fallan por la ausencia del enlace: el nuevo («La cita de hoy está en la agenda con un enlace a su detalle») y el de la 034, que ahora busca la `href` de `route()` y el nombre «10:00, paciente, ver detalle» (A7)
- [x] T012 Escribir `test_el_paciente_abre_una_cita_del_historial` (fila del historial con `fila-cita` y un `cita-enlace` en la fecha hacia `cliente.citas.show`; «Tu próxima cita» conserva «Ver detalles», «Reprogramar» y «Cancelar»), `test_cada_cita_tiene_un_solo_enlace_con_nombre_accesible` (en los cuatro paneles, un solo `<a>` y ningún `<button>` por cita, ningún `<a>` dentro de otro, `aria-label` que empieza por el texto visible del enlace —en el ojo de la dentista, por el texto de `.timeline-time` de su elemento (A8)— y termina en «ver detalle»), `test_sin_citas_el_panel_no_tiene_enlaces_de_cita` (los cuatro paneles sin citas: ningún `cita-enlace` y el estado vacío de hoy) y `test_las_tablas_del_panel_conservan_sus_columnas` (mismas cabeceras y una celda por columna con su `data-label`) — `tests/Feature/CitasPanelDetalleTest.php` — hecho cuando: los del paciente y del enlace único fallan por la ausencia del enlace; los de vacío y columnas pasan y se comprueban rompiendo un momento la vista (un `cita-enlace` fijo en el estado vacío; quitar una celda) — cubre: CA4, CA5, CA7, CA9 — depende: T011
  - nota: 2026-11-14 — el del historial y el del enlace único fallan por la ausencia del enlace; vacío y columnas pasan y se comprobaron rompiendo la vista de la administración (un `cita-enlace` fijo en el estado vacío y sin la celda «Servicio»): los dos fallan; restaurada
  - nota: A10 — el de enlace único comprueba sin `style=""` en `.fila-cita` ni en `.cita-enlace`, sin `onclick` y sin `<script>`, botones ni enlaces anidados dentro de la cita (el panel no gana JavaScript)
  - nota: A11 — la cita del historial no tiene servicio: el nombre esperado es «01/10/2026, sin servicio, ver detalle» (se lee mejor que «—»; la celda visible sigue con «—»)
  - nota: A15 — el nombre del paciente con comillas y `<b>`: la página no contiene `<b> ` sin escapar y el `aria-label` lo lleva literal
- [x] T013 Escribir `test_una_cita_ajena_desde_la_href_del_panel_se_niega_como_hoy`: con la `href` que el panel da a una cita propia, cambiando el identificador por el de una cita fuera de alcance, la dentista y la asistente reciben 403 y el paciente 404, sin datos de la cita en la respuesta — `tests/Feature/CitasPanelDetalleTest.php` — hecho cuando: pasa (caracterización) y se comprueba rompiendo un momento `autorizadoParaCita()` (personal) y el filtro por paciente de `ClienteCitasController::show` (nota `comprobado rompiendo …`) — cubre: CA8 — depende: T012
  - nota: 2026-11-14 — pasa (caracterización); comprobado rompiendo `autorizadoParaCita()` en `CitasController::show` (dentista y asistente: 200 en lugar de 403) y, aparte, el filtro por paciente de `ClienteCitasController::show` (200 en lugar de 404); restaurados
- [x] T014 Escribir `test_las_reglas_048_usan_tokens` (trait `ReglasCss`): `app.css` tiene un bloque `/* Spec 048 */` donde `literales()` no encuentra valores literales (con control positivo: una regla con px o un color hace fallar la comprobación) y que declara `.fila-cita { position: relative }`, `.cita-enlace` de texto (hora o fecha) en reposo con `color: inherit` y sin subrayado, también `:visited` (el ojo conserva su `.btn-ghost`), `.cita-enlace::after` con `position: absolute`, `inset: 0` y `cursor: pointer`, el `:focus-visible` del enlace sin anillo propio y el del `::after` con `--focus-ring-width`, `--focus-ring` y `outline-offset` negativo, con `--radius` por debajo de 900 px; el `.timeline-item.fila-cita:hover` lleva fondo `--p50` solo desde 900 px y ninguna regla del bloque da fondo a la fila por debajo de 900 px (A57 de la 034); y `test_el_foco_de_la_cita_contrasta` con `contraste()`: `--focus-ring` ≥ 3:1 sobre `--white` y sobre `--p50` — `tests/Feature/CitasPanelDetalleTest.php` — hecho cuando: el de las reglas falla porque el bloque no existe; el de contraste pasa y se comprueba cambiando un momento `--focus-ring` por un color claro — cubre: CA5, CA6 — depende: T013
  - nota: 2026-11-14 — el de reglas falla porque el bloque `/* Spec 048 */` no existe; el de contraste pasa (`--p600` sobre `--white` y `--p50`) y se comprobó cambiando un momento `--focus-ring` a `--p50`: falla; restaurado. Selectores fijados por el test: `.fila-cita`, `.fila-cita .cita-enlace:not(.btn)` (y `:visited`), `.fila-cita .cita-enlace::after`, `.fila-cita .cita-enlace:focus-visible` (y `::after`), `.timeline-item.fila-cita:hover` desde 900 px

## Implementación
- [x] T019 Añadir el bloque `/* Spec 048 */` del plan (contenedor relativo; enlace en reposo como el texto de hoy; `::after` que cubre la fila, tarjeta o elemento, con cursor; foco solo en el `::after`, por dentro, con `--radius` en la tarjeta dentro de `@media (max-width: 899px)` del propio bloque; al pasar el puntero, en el escritorio, el `tr:hover td` de hoy en las tablas y fondo `--p50` en el `.timeline-item.fila-cita` de la dentista dentro de `@media (min-width: 900px)`, y ningún fondo nuevo en el móvil; reglas limitadas a `.fila-cita`, sin tocar `.timeline` del historial de la cita, el bloque 034 de `app.css` ni `movil.css`) — `public/css/app.css` — hecho cuando: T014 pasa y `MovilPersonalTest` sigue en verde — cubre: CA5, CA6 — depende: T001, T014
  - nota: 2026-11-14 — bloque `/* Spec 048 */` … `/* Fin spec 048 */` al final de `app.css`, tras el de la 039; T014 y el contraste pasan; `MovilPersonalTest` y `MovilPacienteTest` en verde salvo `test_el_panel_de_la_dentista_se_adapta_al_movil`, que T011 cambió y espera a T022
  - nota: Corrección del test de T014: `literales()` se aplica a los cuerpos de las reglas (los `900px` de las `@media` contaban como literal); comprobado que detecta un `inset: 2px` en el bloque
- [x] T020 Enlazar las citas de «Citas de hoy» del panel de la administración (marcado de «Contratos y datos») — `resources/views/dashboard/admin.blade.php` — hecho cuando: T010 (administrador) y T012 pasan para este panel · verificación manual: `/admin` a 320, 390 y 1440 px comparado con producción (solo cambian puntero y foco; pulsar cualquier columna abre la cita) — cubre: CA1, CA5, CA9 — depende: T010, T012, T019
  - nota: 2026-11-14 — T010 y T012 pasan para `/admin`; `medir.mjs` en verde; verificación manual OK 2026-11-14 (usuario), en http://localhost:8000 con las cuentas de medición
- [x] T021 Enlazar las citas de «Agenda de hoy» del panel de la asistente — `resources/views/dashboard/asistente.blade.php` — hecho cuando: T010 (asistente) y T012 pasan para este panel · verificación manual: `/asistente` a 320, 390 y 1440 px comparado con producción — cubre: CA2, CA5, CA9 — depende: T010, T012, T019
  - nota: 2026-11-14 — T010 y T012 pasan para `/asistente`; `medir.mjs` en verde; verificación manual OK 2026-11-14 (usuario)
- [x] T022 Convertir en `fila-cita` cada elemento de «Agenda de hoy» de la dentista, con el ojo como `cita-enlace`, su nombre nuevo y su `href` con `route('dentista.citas.show')`; «Próximo paciente» sin cambios — `resources/views/dashboard/dentista.blade.php` — hecho cuando: T011 y T012 pasan para este panel y `MovilPersonalTest` sigue en verde · verificación manual: `/dentista` a 320, 390 y 1440 px comparado con producción (pulsar hora, nombre o servicio abre la cita) — cubre: CA3, CA5, CA9 — depende: T011, T012, T019
  - nota: 2026-11-14 — T011 y T012 pasan; `MovilPersonalTest` en verde (con el test de la 034 actualizado en T011); `medir.mjs` en verde, también el fondo al pasar el puntero en escritorio; verificación manual OK 2026-11-14 (usuario)
  - nota: A14 — al pasar el puntero por el elemento, el ojo toma su `.btn-ghost:hover`; validado en pantalla por el usuario, sin cambios
  - nota: Las cuatro vistas usan `@php … @endphp` en bloque: la forma corta `@php(...)` rompía la compilación del panel del paciente, que tiene otro bloque `@php`
- [x] T023 Enlazar las citas del «Historial» del panel del paciente (fecha como enlace, nombre «fecha, servicio o Consulta, ver detalle»); «Tu próxima cita» sin cambios — `resources/views/dashboard/cliente.blade.php` — hecho cuando: T012 pasa para este panel · verificación manual: `/cliente` a 320, 390 y 1440 px comparado con producción — cubre: CA4, CA5, CA9 — depende: T012, T019
  - nota: 2026-11-14 — T012 pasa; `medir.mjs` en verde; verificación manual OK 2026-11-14 (usuario)
  - nota: A11 — sin servicio, el nombre accesible dice «sin servicio» y la celda sigue con «—» (redacción ajustada en design.md y plan.md)
- [x] T024 Escribir y ejecutar la medición en navegador de los cuatro paneles a 320, 390 y 1440 px (Playwright 1.63.0 en la imagen `mcr.microsoft.com/playwright` con el volumen `dentissa_pw_node`; cuentas y citas temporales en `dentissa_test`, borradas al terminar): pulsar la celda «Servicio» (en la dentista, el nombre) lleva al detalle de esa cita; a 320 y 390 px cada cita mide ≥ 44 px de alto; `scrollWidth` ≤ ancho de la ventana; con Tab, una parada por cita y un solo anillo visible (captura con foco en la tarjeta del móvil); el color y `text-decoration` calculados de la hora son los de antes del cambio; sin JavaScript, el enlace funciona igual — `docs/specs/048-citas-panel-detalle/design/medir.mjs` — hecho cuando: el script termina sin fallos en los cuatro paneles y los tres anchos (informe en la nota) — cubre: CA1, CA2, CA3, CA4, CA5, CA9 — depende: T020, T021, T022, T023
  - nota: 2026-11-14 — `medir.mjs`: 186 comprobaciones, todas bien, en los cuatro paneles a 320, 390 y 1440 px en Chromium y a 1440 px en WebKit y Firefox; sin JavaScript, pulsar la cita la abre. Altura en el móvil ≥ 103 px; anillo `--focus-ring` a 7,00:1 sobre blanco; capturas con foco en la carpeta temporal de la sesión. Pulsa por coordenadas: la celda «Servicio» la cubre el `::after` del enlace, que es lo que se busca
  - nota: A13 — WebKit y Firefox a 1440 px: pulsar otra columna abre la cita (el `::after` cubre el `<tr>` en los tres motores)
  - nota: Datos: `.ai/inspect/048-cuentas.php crear|borrar` (no versionado) crea en la base local cuentas `@medicion-048.test` con citas de hoy y pasadas; servidor `php artisan serve` en el puerto 8048 con `SESSION_DOMAIN=null`. Al terminar: cuentas borradas y servidor parado

## Integración y documentación
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura (el plan dice que no: dejar nota)
  - nota: 2026-11-14 — sin cambios en `docs/architecture.md`: no hay módulos, rutas ni datos nuevos (plan, «Impacto en arquitectura»)
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas (el plan dice que no: dejar nota)
  - nota: 2026-11-14 — sin cambios en `docs/deployment.md` ni `docs/observability.md`: sin variables, pasos, logs, auditoría ni métricas nuevas
- [x] T092 Marcar spec como `implemented`: suite completa, Pint sobre lo tocado, gitleaks sobre el diff de la spec, criterios `[x]` y `docs/specs/README.md`
  - nota: 2026-11-14 — suite completa 1011 en verde (15 532 aserciones; línea base 1001 + 10 de la 048); Pint limpio en los tests tocados; gitleaks sin hallazgos en los 13 archivos del diff; semgrep `p/php` 0 hallazgos en las vistas y los tests; el diff no toca `app/`, `routes/` ni `database/` (RNF sin consultas nuevas); CA1–CA9 marcados; spec `implemented` y README

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097 (cada panel abre sus citas; sin citas, el estado vacío)
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; `5xx` de los cuatro paneles la primera hora. Rollback: `bash deploy/volver.sh <tag anterior>`
- [ ] T098 Marcar spec como `released`; en las specs 012 y 034 anotar en su Historial que las citas del panel se abren (048), y en la 034 el nombre nuevo del ojo de la dentista; recordar que la 012 es `inferred`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T024 | T020 |
| CA2 | T010, T024 | T021 |
| CA3 | T011, T024 | T022 |
| CA4 | T012, T024 | T023 |
| CA5 | T011, T012, T014, T024 | T019, T020, T021, T022, T023 |
| CA6 | T014 | T019 |
| CA7 | T012 | T020, T021, T022, T023 |
| CA8 | T013 | T020, T021, T022, T023 |
| CA9 | T012, T024 | T020, T021, T022, T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T021, T022, T023 (la `href` usa `route()` del detalle de cada rol; el control de acceso es el existente) | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `docs/design/system.md`, `docs/design/system.html` | T001 |
| `public/css/app.css` | T019 |
| `resources/views/dashboard/admin.blade.php` | T020 |
| `resources/views/dashboard/asistente.blade.php` | T021 |
| `resources/views/dashboard/dentista.blade.php` | T022 |
| `resources/views/dashboard/cliente.blade.php` | T023 |
| `tests/Feature/CitasPanelDetalleTest.php` | T010, T011, T012, T013, T014 |
| `tests/Feature/MovilPersonalTest.php` | T011 |
| `docs/specs/048-citas-panel-detalle/design/medir.mjs` | T024 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA (foco, toque ≥ 44 px en el móvil, contraste) | T014, T024 |
| Sin JavaScript | T012 (enlaces en el HTML), T024 |
| Sin consultas nuevas | T092 (el diff no toca `DashboardController`) |
