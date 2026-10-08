---
spec: 028-reprogramar-citas
plan: plan.md
status: draft
---

# Tareas · 028 Reprogramar citas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.
- Horas de los tests: "ahora" con `Carbon::setTestNow` en UTC; la clínica en
  `America/Mexico_City` (UTC−6), como en la 026. El 2026-10-05 es lunes.
- Anchos de la verificación manual: 390 y 1440 px (`.ai/project.yaml → design.widths`). El plan
  dice 375 y 1280; prevalece la configuración del proyecto.

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 028 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T015) se escriben antes que el código que prueban, y cada tarea de implementación depende de los tests de sus criterios. Todos los CA fallan hoy: no hay rutas de reprogramar (404) y el paciente cancela hasta el inicio. Los casos que ya se cumplen dentro de un CA (cita ajena del paciente → 404, el paciente cancela con ≥ 24 h, la cancelación del personal) se comprueban rompiendo el código un momento, anotado y restaurado. Los tests de la 026 que asertan la regla anterior se cambian en la misma tarea que cambia la regla (D6): T020 (`vuelta`), T021 (cancelación del paciente) y T028 (panel del paciente). La refactorización de T004 no cambia comportamiento y la prueban los tests de la 025. |
| P3 Autorización en servidor | ❌ aceptado: `GET /{rol}/citas/{id}`, `GET /cliente/citas` y `GET /cliente/citas/{id}` solo ganan `puede_reprogramar` y el aviso de plazo, y conservan su control actual → spec 014 y spec 015, como en la 026 — aprobado por el usuario el 2026-10-07 | Las rutas nuevas tienen `role:` en su grupo (T025, T027) y el Gate `reprogramar-cita` (T023); ninguna en el grupo del dentista. T012 prueba por rol el caso permitido y el denegado, en GET y PATCH. T041 deja la excepción en el roadmap. |
| P4 Entrada validada y SQL con bindings | ✅ | T025 y T027 validan solo `fecha`, `hora` y `motivo` con `bail` y leen solo las claves validadas; T027 añade `motivo` a la cancelación del paciente. T003 es DDL fijo; T022 usa Query Builder. T040 corre semgrep sobre lo tocado en `app/` y `database/`. |
| P5 Nada sensible en el repo | ✅ | Sin variables de entorno ni secretos nuevos; T040 corre gitleaks. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. T040 corre `composer audit --locked` y `npm audit` y anota la línea base (los avisos de npm de `main` se corrigen con la 031). |
| P7 Auditoría de datos sensibles | ❌ aceptado: historial de la cita con autor, fechas y motivo, sin evento formal (→ 022) y sin registro de los 403 del Gate nuevo (→ 020) — aprobado por el usuario el 2026-10-07 | T003 y T022 escriben la entrada de historial; T011 la prueba; T041 anota los residuos. |
| P8 Correlación y logs limpios | ❌ aceptado: una `QueryException` distinta de 1205/1213 registraría el SQL con el motivo; la redacción la hace la 023 — aprobado por el usuario el 2026-10-07 | Ninguna tarea añade `Log::`. T014 prueba que ni la reprogramación ni su rechazo escriben en los logs; T040 busca `Log::` nuevos; T041 anota el residuo. |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 incorpora `.btn-danger` y la pantalla «Reprogramar cita» al sistema antes de cualquier vista; T024, T026, T028 y T029 dependen de él. T015 prueba que lo nuevo no tiene `style=` ni colores literales y que los errores van asociados a su campo. Verificación manual en T025 y T029. |

## Preparación
- [ ] T001 Incorporar al sistema de diseño los "Cambios a incorporar al sistema" del plan: el componente `.btn-danger` (texto `var(--red)` sobre `var(--red-bg)`, 6.13:1; borde `var(--border-width)` `var(--red)`; foco con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`) en Componentes → Botón, y la pantalla «Reprogramar cita» (resumen en `.card`, campos de fecha, hora y motivo, `.btn btn-primary` «Guardar nueva fecha» y la sección aparte «No reagendar» con `.btn-danger` «Cancelar la cita definitivamente» y el aviso de que no se puede deshacer) en Pantallas principales. `system.md` sube a 1.3.0 con su fila en "Decisiones" (tipo `diseño`, fuente `usuario`). `system.html` muestra el botón en reposo y con foco, y la pantalla — docs/design/system.md, docs/design/system.html — hecho cuando: el validador da 0 errores en `docs/design`, `system.md` está en 1.3.0 y `system.html` sigue sin recursos externos (0 `http`) — cubre: CA6
- [ ] T002 Registrar la línea base en `feature/028-reprogramar-citas`: recuento de `php artisan test`; que hoy `GET /admin/citas/{id}/reprogramar` da 404; que hoy un paciente cancela una cita agendada que empieza dentro de 2 horas; qué archivos de los que se van a tocar pasan `pint --test` (`ReservaCitas.php`, `EstadosCita.php`, `CambioDeEstadoNoPermitido.php`, `CitaNoAgendable.php`, `CitasController.php`, `ClienteCitasController.php`, `AppServiceProvider.php`, `routes/web.php`); y los recuentos de usuarios, citas, historial y personal de `dentissa_test` — docs/specs/028-reprogramar-citas/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
- [ ] T003 Crear la migración que añade a `historial_citas` `fecha_hora_anterior` y `fecha_hora_nueva` (`DATETIME NULL`; `down()` las quita) y aplicarla en la base de desarrollo y en `dentissa_test` (`-e DB_DATABASE=dentissa_test … migrate --force`) — database/migrations/2026_10_07_000000_reprogramacion_en_historial_citas.php — hecho cuando: `SHOW COLUMNS FROM historial_citas` lista las dos columnas con nulo en las dos bases; en `dentissa_test`, `migrate:rollback --step=1` las quita y otro `migrate` las vuelve a añadir; la suite da la línea base de T002 y `pint --test` pasa — cubre: CA7 — depende: T002
- [ ] T004 Refactorizar `ReservaCitas` sin cambiar comportamiento: extraer de `reservarConBloqueo` los métodos públicos `bloquearDentista(string $id)` (espera de bloqueo de la sesión y `FOR UPDATE` del dentista; `noDisponible()` si ya no existe) y `comprobarHueco(string $dentistaId, Carbon $inicio, Carbon $fin)` (horario del día, franja y solape), con un docblock que dice que se llaman dentro de una transacción ya abierta; `reservarConBloqueo` los usa. La exclusión de la propia cita llega en T022 — app/Agenda/ReservaCitas.php — hecho cuando: `ReglaAgendaTest`, `ReservaConcurrenteTest`, `AltaCitasFlujosTest`, `AltaCitasRolesTest` y `AltaCitasValidacionTest` pasan sin cambios, la suite da la línea base de T002 y `pint --test` pasa — cubre: CA4, CA10 — depende: T002

## Tests (antes de implementar)
- [ ] T010 Ampliar la fixture `EscenarioEstadosCita` sin cambiar lo que ya crea: `conHorarios()` (D1 y D2 con horario de lunes a viernes de 09:00 a 18:00, solo para los tests que lo llaman), `reprogramar(Usuario $usuario, string $cita, string $fecha, string $hora, ?string $motivo = null, array $extra = [])` (PATCH a la ruta de reprogramar del grupo del usuario) y `fechaHoraDe(string $cita)` — tests/Feature/Concerns/EscenarioEstadosCita.php — hecho cuando: los métodos existen, los tests de la 026 que usan la fixture pasan igual que en la línea base y `pint --test` pasa
- [ ] T011 [P] Escribir `ReprogramarCitaTest` (admin y asistente), con los tests del plan: `test_la_administracion_reprograma_cualquier_cita`, `test_el_asistente_reprograma_citas_de_sus_dentistas`, `test_solo_cambian_fecha_y_hora_y_el_hueco_anterior_queda_libre` (mismo paciente, dentista, servicio y duración; otra cita cabe después en el hueco anterior), `test_aplica_la_regla_de_agenda_al_nuevo_hueco` (sin horario, fuera de franja, solape, citas seguidas aceptadas, canceladas y no asistidas no cuentan; mensajes exactos de `CitaNoAgendable`), `test_la_propia_cita_no_cuenta_como_conflicto` (moverla 30 min dentro de su propio hueco), `test_una_fecha_pasada_o_igual_se_rechaza` (mensajes exactos del plan), `test_una_confirmada_vuelve_a_agendada`, `test_una_cita_cerrada_o_empezada_no_se_reprograma` (completada, cancelada y no asistió con el mensaje `cerrada`; en curso y agendada ya empezada con «Esta cita ya empezó y no se puede reprogramar.»), `test_la_reprogramacion_deja_una_sola_entrada_de_historial` (autor, estado anterior y nuevo, `fecha_hora_anterior`, `fecha_hora_nueva` y motivo), `test_un_rechazo_no_deja_historial`, `test_los_campos_anadidos_a_mano_no_cambian_la_cita` (`dentista_id`, `servicio_id`, `duracion_min`, `paciente_id` y `estado` enviados a mano), `test_una_fecha_u_hora_mal_formada_da_error_de_validacion` (formatos erróneos y listas en `fecha` y `hora`; error del campo, nunca 500) y `test_el_hueco_ocupado_no_revela_la_otra_cita`. Cada rechazo comprueba que la fecha, la hora y el estado no cambiaron — tests/Feature/ReprogramarCitaTest.php — hecho cuando: todos fallan por la razón esperada (la ruta no existe: 404) — cubre: CA1, CA4, CA5, CA7, CA9 — depende: T010
- [ ] T012 [P] Escribir `ReprogramarCitaRolesTest`, en GET y PATCH: `test_la_administracion_y_el_asistente_abren_el_formulario` (200), `test_el_dentista_no_puede_reprogramar` (403 en las rutas de admin, asistente y cliente; en `/dentista/citas/{id}/reprogramar` no hay ruta), `test_el_asistente_no_reprograma_citas_de_dentistas_no_asignados` (403), `test_el_paciente_no_encuentra_la_cita_de_otro` (404, junto con su cita propia → 200), `test_el_personal_no_puede_usar_la_ruta_del_paciente` (403), `test_el_paciente_no_puede_usar_la_ruta_del_personal` (403) y `test_fuera_de_alcance_tampoco_cancela_definitivamente` (asistente fuera de alcance con `estado=cancelada` → 403; paciente con la cita de otro en `cancelar` → 404). Cada denegación comprueba que la cita no cambió — tests/Feature/ReprogramarCitaRolesTest.php — hecho cuando: fallan por la razón esperada los que esperan 200 o 403 en las rutas nuevas (hoy 404). Los 404 que hoy pasan porque la ruta no existe van en el mismo test que su caso permitido, que falla. `test_fuera_de_alcance_tampoco_cancela_definitivamente` pasa hoy (026) y se comprueba rompiendo un momento `CambiarEstadoCita::alcance` (anotado y restaurado) — cubre: CA8 — depende: T010
- [ ] T013 [P] Escribir `ReprogramarCitaPacienteTest`, con la cita de D1 del 2026-10-06 a las 10:00 locales: `test_el_paciente_reprograma_con_24_horas_o_mas` (ahora 2026-10-05 16:00 UTC, borde exacto de 24 h, aceptado), `test_con_menos_de_24_horas_se_rechaza_con_el_mensaje_de_plazo` (16:01 UTC, mensaje exacto del plan, la cita no cambia), `test_el_panel_oculta_reprogramar_fuera_de_plazo_y_explica_por_que` (detalle y listado: dentro de plazo, «Reprogramar» y «Cancelar»; fuera, ninguno de los dos y el mensaje de plazo), `test_el_paciente_cancela_con_24_horas_o_mas`, `test_el_paciente_no_cancela_con_menos_de_24_horas` (mensaje de plazo), `test_el_paciente_cancela_con_motivo_desde_la_pantalla` (el motivo queda en el historial) y `test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano` (PATCH directo de reprogramar y de cancelar) — tests/Feature/ReprogramarCitaPacienteTest.php — hecho cuando: fallan por la razón esperada (sin rutas de reprogramar, cancelación hasta el inicio, el motivo no se guarda). `test_el_paciente_cancela_con_24_horas_o_mas` pasa hoy y se comprueba haciendo que `cancelar` rechace siempre un momento (anotado y restaurado) — cubre: CA2, CA3, CA6, CA9 — depende: T010
- [ ] T014 [P] Escribir `ReprogramarConcurrenteTest`, con la conexión `segunda_conexion` que confirma sus propios datos y los borra en `tearDown` con `finally`, como `ReservaConcurrenteTest`: `test_la_reprogramacion_espera_al_bloqueo_del_dentista_y_responde_no_disponible`, `test_reprogramar_y_agendar_el_mismo_hueco_solo_guarda_uno` (la que llega segunda recibe el mensaje de `noDisponible()` y la cita que no se movió conserva su fecha), `test_un_interbloqueo_se_convierte_en_no_disponible` (conexión simulada con 1205, 1213 y `DeadlockException`, llamando a `ReprogramacionCitas::reprogramar`) y `test_ni_la_reprogramacion_ni_su_rechazo_escriben_en_los_logs` (`MessageLogged`, con un control que sí registra) — tests/Feature/ReprogramarConcurrenteTest.php — hecho cuando: fallan por la razón esperada (la ruta no existe y `App\Agenda\ReprogramacionCitas` tampoco); tras ejecutarlo, `dentissa_test` conserva los recuentos de T002. El de logs, si pasa hoy, se comprueba añadiendo un momento un `Log::info` con el motivo en el punto de reprogramar cuando exista (T022; anotado y restaurado) — cubre: CA10 — depende: T010
- [ ] T015 [P] Escribir `ReprogramarCitaVistasTest`, con aserciones sobre la sección por `id` y no sobre toda la página: `test_el_detalle_ofrece_reprogramar_solo_si_se_puede` (admin y asistente con una agendada; sin botón en una cerrada o empezada; el dentista nunca), `test_la_seccion_no_reagendar_cancela_con_motivo` (formulario `PATCH …/estado` con `estado=cancelada` y campo `motivo`; al enviarlo, la cita queda cancelada con el motivo en el historial), `test_el_dentista_no_ve_la_opcion`, `test_el_historial_muestra_la_reprogramacion` («Reprogramada del … al …» y el cambio de estado si lo hubo), `test_el_motivo_de_la_reprogramacion_se_muestra_escapado`, `test_lo_nuevo_solo_usa_el_sistema` (sin `style=` ni hex, `rgb(` o `px` literales en el parcial y las dos pantallas nuevas, con un control positivo que sí los detecta) y `test_los_errores_van_asociados_a_su_campo` (`aria-describedby` y `aria-invalid` en fecha y hora tras un error) — tests/Feature/ReprogramarCitaVistasTest.php — hecho cuando: todos fallan por la razón esperada (no hay botón, pantalla ni entrada de reprogramación; los archivos de las vistas no existen) — cubre: CA1, CA6, CA7 — depende: T010

## Implementación
- [ ] T020 Añadir los mensajes del plan: `CitaNoAgendable::enElPasado()` («Elige una fecha y hora que aún no hayan pasado.») y `mismaFechaYHora()` («La cita ya está en esa fecha y hora. Elige otra.»); `CambioDeEstadoNoPermitido::fueraDePlazo()` («Faltan menos de 24 horas para tu cita: ya no puedes cambiarla ni cancelarla desde aquí. Llama a la clínica.») y `yaEmpezoPersonal()` («Esta cita ya empezó y no se puede reprogramar.»), y el texto nuevo de `vuelta()` («Una cita no puede volver a «Agendada». Si hay que moverla, usa «Reprogramar».»). En la misma tarea, `EstadosCitaTransicionesTest` aserta el texto nuevo de `vuelta()` (D6) — app/Agenda/CitaNoAgendable.php, app/Agenda/CambioDeEstadoNoPermitido.php, tests/Feature/EstadosCitaTransicionesTest.php — hecho cuando: `EstadosCitaTransicionesTest` pasa con el texto nuevo (y falla con el anterior antes del cambio), la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa en los tres archivos — cubre: CA2, CA3, CA4, CA5 — depende: T011, T013
- [ ] T021 Cambiar `EstadosCita`: `ANTELACION_PACIENTE_HORAS = 24`; `dentroDelPlazoDelPaciente(object $cita): bool` (inicio en la zona de la clínica − ahora ≥ 24 h); `comprobarReprogramable(object $cita, Usuario $actor)` (cerrada → `cerrada()`; en curso o ya empezada → `yaEmpezoPersonal()` para el personal; paciente fuera de plazo → `fueraDePlazo()`); en `comprobar()`, la cancelación del paciente pasa de `yaEmpezo()` a `fueraDePlazo()` cuando no está dentro del plazo. En la misma tarea (D6), `EstadosCitaRolesTest` cambia `test_el_paciente_cancela_su_cita_antes_de_que_empiece` y `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` al plazo de 24 h y al mensaje de plazo, y `HistorialCitaTest` mueve el caso del paciente de `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor` a una cita con 24 h o más — app/Agenda/EstadosCita.php, tests/Feature/EstadosCitaRolesTest.php, tests/Feature/HistorialCitaTest.php — hecho cuando: pasan `EstadosCitaRolesTest`, `HistorialCitaTest`, `test_el_paciente_cancela_con_24_horas_o_mas` y `test_el_paciente_no_cancela_con_menos_de_24_horas` de T013; la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa — cubre: CA2, CA3, CA5 — depende: T013, T020
- [ ] T022 Crear `ReprogramacionCitas::reprogramar(string $citaId, string $fecha, string $hora, Usuario $actor, ?string $motivo): void` y añadir a `ReservaCitas::comprobarHueco` el parámetro `?string $excluirCitaId = null` (`where('id', '!=', …)` en la consulta de solape). En una transacción: lee el dentista de la cita, `bloquearDentista`, bloquea la cita (`FOR UPDATE`; 404 si desapareció), `comprobarReprogramable`, nueva fecha pasada → `enElPasado()`, igual → `mismaFechaYHora()`, `comprobarHueco` excluyendo la cita; actualiza `fecha_hora`, `estado` (confirmada → agendada) y `actualizado_en`, e inserta una sola entrada de historial con `estado_anterior`, `estado_nuevo` = `agendada`, las dos fechas, `motivo`, `cambiado_por` y `fecha`. 1205, 1213 y `DeadlockException` → `CitaNoAgendable::noDisponible()`; docblock sobre no llamarlo dentro de otra transacción (D3) — app/Agenda/ReprogramacionCitas.php, app/Agenda/ReservaCitas.php — hecho cuando: `test_un_interbloqueo_se_convierte_en_no_disponible` de T014 pasa; `ReglaAgendaTest`, `ReservaConcurrenteTest` y `AltaCitas*` siguen en verde y `pint --test` pasa en los dos archivos — cubre: CA1, CA4, CA5, CA7, CA10 — depende: T003, T004, T011, T014, T021
- [ ] T023 Crear `ReprogramarCita::autorizar(Usuario $usuario, object $cita): Response` (primero el alcance de `CambiarEstadoCita::alcance()`: 403 al personal fuera de alcance, 404 al paciente con una cita ajena; después el rol: `admin`, `asistente` o `cliente`; el dentista se deniega con mensaje) y registrar `Gate::define('reprogramar-cita', …)` en `AppServiceProvider::boot()` — app/Autorizacion/ReprogramarCita.php, app/Providers/AppServiceProvider.php — hecho cuando: `Gate::has('reprogramar-cita')` es verdadero, la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa en los dos archivos — cubre: CA8 — depende: T012
- [ ] T024 Crear `.btn-danger` en `app.css` con los valores de T001 (solo tokens), el parcial `citas/partials/reprogramar.blade.php` (resumen con badge de estado; formulario de fecha, hora y motivo con `.field`, `.field-label` y `.field-hint-error` asociados con `aria-describedby` y `aria-invalid`; «Guardar nueva fecha»; sección aparte `id="no-reagendar"` con motivo y `.btn-danger` «Cancelar la cita definitivamente» y el aviso de que no se puede deshacer; si la cita ya no se puede mover, el motivo en lugar del formulario) y la pantalla del personal `citas/reprogramar.blade.php`, que recibe del controlador las rutas de guardar y de cancelar y oculta «No reagendar» al dentista — public/css/app.css, resources/views/citas/partials/reprogramar.blade.php, resources/views/citas/reprogramar.blade.php — hecho cuando: las vistas compilan (`php artisan view:cache` sin errores), la parte de `test_lo_nuevo_solo_usa_el_sistema` sobre estos archivos pasa y en `app.css` solo hay añadidos — cubre: CA1, CA6 — depende: T001, T015
- [ ] T025 Añadir a `CitasController` `reprogramarForm(string $id)` (GET: busca la cita con dentista, paciente y servicio, 404 si no existe, `Gate::authorize('reprogramar-cita')`, pasa si se puede mover o el motivo) y `reprogramar(Request, string $id, ReprogramacionCitas)` (PATCH: 404 → Gate → validación de `fecha`, `hora` y `motivo` con `ReservaCitas::reglasDeEntrada()`/`mensajesDeEntrada()` y `bail`, sin leer otras claves → servicio; éxito al detalle con mensaje; `CitaNoAgendable` y `CambioDeEstadoNoPermitido` vuelven al formulario con `withInput()` y el error en `fecha`), y `puede_reprogramar` en `show`; declarar `GET` y `PATCH /{admin,asistente}/citas/{id}/reprogramar` (`citas.reprogramar`, `citas.reprogramar.update`) solo en esos dos grupos — app/Http/Controllers/CitasController.php, routes/web.php — hecho cuando: pasan todo `ReprogramarCitaTest`, `ReprogramarConcurrenteTest`, los casos del personal de `ReprogramarCitaRolesTest` y la parte de `test_lo_nuevo_solo_usa_el_sistema` y `test_los_errores_van_asociados_a_su_campo` del personal; `php artisan route:list` no lista reprogramar bajo `/dentista`; `pint --test` pasa en los dos archivos · verificación manual: la pantalla de reprogramar del personal a 390 y 1440 px contra `docs/design/system.html` (sección «No reagendar» separada y marcada como irreversible, foco visible con teclado, errores leídos junto a su campo, contraste de `.btn-danger`) — cubre: CA1, CA4, CA5, CA7, CA8, CA9, CA10 — depende: T022, T023, T024
- [ ] T026 Cambiar `citas/show.blade.php`: botón `.btn btn-outline` «Reprogramar» junto al cambio de estado cuando `puede_reprogramar`; en `id="historial"`, las entradas con `fecha_hora_anterior` se muestran como «Reprogramada del … al …», con el cambio de estado si lo hubo y el motivo escapado — resources/views/citas/show.blade.php — hecho cuando: pasan `test_el_detalle_ofrece_reprogramar_solo_si_se_puede`, `test_la_seccion_no_reagendar_cancela_con_motivo`, `test_el_dentista_no_ve_la_opcion`, `test_el_historial_muestra_la_reprogramacion` y `test_el_motivo_de_la_reprogramacion_se_muestra_escapado`, y siguen en verde `EstadosCitaVistasTest` y `HistorialCitaTest` — cubre: CA1, CA6, CA7 — depende: T025
- [ ] T027 Añadir a `ClienteCitasController` `reprogramarForm` y `reprogramar` (mismo esquema que T025, con la cita buscada por el paciente del usuario y los mensajes del paciente sin estados internos), y en `cancelar` el `motivo` opcional (`nullable|string|max:500`) que pasa a `EstadosCita::cambiar`; crear `citas/cliente-reprogramar.blade.php` con el parcial y las rutas del paciente; declarar `GET` y `PATCH /cliente/citas/{id}/reprogramar` con `->middleware('role:cliente')` — app/Http/Controllers/ClienteCitasController.php, resources/views/citas/cliente-reprogramar.blade.php, routes/web.php — hecho cuando: pasan los tests de `ReprogramarCitaPacienteTest` salvo `test_el_panel_oculta_reprogramar_fuera_de_plazo_y_explica_por_que`, los casos del paciente de `ReprogramarCitaRolesTest` y `test_lo_nuevo_solo_usa_el_sistema` completo; `pint --test` pasa en el controlador y en `routes/web.php` — cubre: CA2, CA3, CA6, CA8, CA9 — depende: T021, T022, T023, T024, T025
- [ ] T028 En `ClienteCitasController`, `conPuedeCancelar` pasa a usar `dentroDelPlazoDelPaciente` (agendada o confirmada y dentro de plazo) y añade `puede_reprogramar`; `show` pasa `aviso_plazo` (mensaje de `fueraDePlazo()` cuando la cita está agendada o confirmada y fuera de plazo) en lugar de `aviso_cancelar`. `citas/cliente-show.blade.php` muestra «Reprogramar» y «Cancelar» solo dentro de plazo y, fuera, el aviso de plazo en su lugar. En la misma tarea (D6), `EstadosCitaVistasTest` mueve la cita «pendiente» de `test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo` a 24 h o más y `test_el_paciente_ve_por_que_no_puede_cancelar_una_cita_que_ya_empezo` aserta el mensaje de plazo — app/Http/Controllers/ClienteCitasController.php, resources/views/citas/cliente-show.blade.php, tests/Feature/EstadosCitaVistasTest.php — hecho cuando: pasa `EstadosCitaVistasTest` y la parte del detalle de `test_el_panel_oculta_reprogramar_fuera_de_plazo_y_explica_por_que`; `pint --test` pasa en el controlador — cubre: CA2, CA3 — depende: T027
- [ ] T029 Mostrar en `citas/cliente-index.blade.php` el enlace «Reprogramar» solo cuando `puede_reprogramar` (el botón «Cancelar» ya depende de `puede_cancelar`) — resources/views/citas/cliente-index.blade.php — hecho cuando: pasa `test_el_panel_oculta_reprogramar_fuera_de_plazo_y_explica_por_que` y la suite completa está en verde · verificación manual: el detalle y la lista del paciente dentro y fuera de plazo, y su pantalla de reprogramar, a 390 y 1440 px contra `docs/design/system.html` (foco visible, aviso de plazo legible, errores junto a su campo) — cubre: CA2 — depende: T028

## Integración y documentación
- [ ] T040 Hacer la verificación final — (sin archivos) — hecho cuando:
  - `php artisan test` completo da la línea base de T002 más los tests de T011–T015, todos en verde;
  - `vendor/bin/pint --test` pasa sobre todos los archivos PHP tocados;
  - `semgrep scan --config p/php --error` sobre los archivos de `app/` y `database/migrations/` tocados no da hallazgos;
  - `gitleaks detect --no-banner` no da hallazgos;
  - `composer audit --locked` no da avisos y `npm audit` queda anotado frente a la línea base del plan (P6);
  - `git diff <impl_base> -- app/ | grep "Log::"` no muestra llamadas nuevas;
  - `php artisan route:list` no lista rutas de reprogramar en el grupo `/dentista`;
  - `dentissa_test` conserva los recuentos de T002.

  — cubre: CA8, CA9, CA10
- [ ] T041 Añadir en `docs/roadmap.md → Pendientes` cuatro viñetas que citan la spec 028 — docs/roadmap.md — hecho cuando: existen las viñetas de:
  - excepción P3: `GET /{rol}/citas/{id}` (con `puede_reprogramar`) recibe su Gate en la 014; "Mis citas" y el detalle del paciente, `role:cliente` en la 015;
  - `/plan 014 --fix` debe partir del Gate `reprogramar-cita` en `AppServiceProvider` y de `puede_reprogramar` en `CitasController@show`;
  - residuo P7: sin evento formal de la reprogramación → spec 022, y sin registro de los 403 del Gate `reprogramar-cita` → spec 020;
  - residuo P8: `QueryException` con el motivo en el log → spec 023.
- [ ] T090 Actualizar `docs/architecture.md`:
  - Módulos: la fila Agenda menciona `app/Agenda/ReprogramacionCitas.php` y `ReservaCitas::comprobarHueco`; `app/Autorizacion/ReprogramarCita.php`.
  - Gates: `reprogramar-cita`.
  - "Esquema": `historial_citas` con `fecha_hora_anterior` y `fecha_hora_nueva`.
- [ ] T091 Actualizar la documentación de seguridad, observabilidad y despliegue:
  - `docs/security.md → Dónde se aplica`: el Gate `reprogramar-cita` y las rutas de reprogramar por rol (sin ruta en el grupo del dentista; `role:cliente` en las del paciente). Ningún riesgo cambia de estado.
  - `docs/observability.md`: sin cambios (sin logs, eventos ni métricas nuevos; el historial ya registra reprogramaciones y cancelaciones).
  - `docs/deployment.md`: la migración de la 028 solo añade columnas con nulo y va en el mismo paso que el código; la vuelta atrás no necesita revertirla.
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - No hay staging (030): la verificación de Observabilidad se hace en producción en T097.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
  - Reprogramar una cita ficticia como administración y comprobar en su detalle la entrada «Reprogramada del … al …»; devolverla a su hora y comprobar la segunda entrada.
  - Sin errores 500 en `laravel.log` ni en el log de nginx durante 15 minutos.
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T015 | T022, T024, T025, T026 |
| CA2 | T013 | T020, T021, T027, T028, T029 |
| CA3 | T013, T021 | T020, T021, T027, T028 |
| CA4 | T011 | T004, T020, T022, T025 |
| CA5 | T011 | T020, T021, T022, T025 |
| CA6 | T013, T015 | T001, T024, T026, T027 |
| CA7 | T011, T015 | T003, T022, T025, T026 |
| CA8 | T012, T040 | T023, T025, T027 |
| CA9 | T011, T013, T040 | T025, T027 |
| CA10 | T014, T040 | T004, T022, T025 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T023, T025 | T012 |
| TM2 | T023, T027 | T012 |
| TM3 | T025, T027 | T011 |
| TM4 | T021, T022, T027 | T013 |
| TM5 | T025, T027 | T011 |
| TM6 | T004, T022 | T014 |
| TM7 | T022 | T014 |
| TM8 | T022 | T011 |
| TM9 | T003, T022 | T011 |
| TM10 | T021, T022 | T011 |
| TM11 | T026 | T015 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Agenda/ReservaCitas.php | T004, T022 |
| app/Agenda/ReprogramacionCitas.php | T022 |
| app/Agenda/EstadosCita.php | T021 |
| app/Agenda/CambioDeEstadoNoPermitido.php | T020 |
| app/Agenda/CitaNoAgendable.php | T020 |
| app/Autorizacion/ReprogramarCita.php | T023 |
| app/Providers/AppServiceProvider.php | T023 |
| app/Http/Controllers/CitasController.php | T025 |
| app/Http/Controllers/ClienteCitasController.php | T027, T028 |
| routes/web.php | T025, T027 |
| database/migrations/2026_10_07_000000_reprogramacion_en_historial_citas.php | T003 |
| resources/views/citas/partials/reprogramar.blade.php | T024 |
| resources/views/citas/reprogramar.blade.php, citas/cliente-reprogramar.blade.php | T024, T027 |
| resources/views/citas/show.blade.php | T026 |
| resources/views/citas/cliente-show.blade.php, cliente-index.blade.php | T028, T029 |
| public/css/app.css | T024 |
| docs/design/system.md, system.html | T001 |
| tests/Feature/ (nuevos y modificados) | T010, T011, T012, T013, T014, T015, T020, T021, T028 |
| docs/architecture.md | T090 |
| docs/security.md, docs/observability.md, docs/deployment.md | T091 |
| docs/roadmap.md | T041 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Mensajes de rechazo en español que dicen qué hacer | T011, T013 (texto exacto), T020 |
| WCAG 2.1 AA en lo nuevo | T015 · verificación manual en T025 y T029 |
| Ninguna entrada produce un error del servidor | T011, T014, T040 |
