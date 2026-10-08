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
- Anchos de la verificación manual: 390 y 1440 px (`.ai/project.yaml → design.widths`). A 390 px
  las vistas ya se desbordan (DS8): se comprueba que lo nuevo no añade desbordamiento propio.
- Las tareas de implementación se listan por orden de ejecución; T030 va entre T020 y T021.

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 028 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T015) se escriben antes que el código que prueban, y cada tarea de implementación depende de los tests de sus criterios. Todos los CA fallan hoy: no hay rutas de reprogramar (404) y el paciente cancela hasta el inicio. Los tests de caracterización que marca la Trazabilidad del plan (cita ajena del paciente → 404, el paciente cancela con ≥ 24 h, cancelación fuera de alcance, logs limpios) se comprueban rompiendo el código un momento, anotado y restaurado. Los tests de la 026 que asertan la regla anterior se cambian en la misma tarea que cambia la regla (D6): T030 (`vuelta` por rol), T021 (cancelación del paciente e historial) y T028 (panel del paciente). La refactorización de T004 no cambia comportamiento y la prueban los tests de la 025. |
| P3 Autorización en servidor | ❌ aceptado: `GET /{rol}/citas/{id}`, `GET /cliente/citas` y `GET /cliente/citas/{id}` solo ganan `puede_reprogramar` y los avisos de plazo, y conservan su control actual → spec 014 y spec 015, como en la 026 — aprobado por el usuario el 2026-10-07 | Las rutas nuevas tienen `role:` en su grupo (T025, T027) y el Gate `reprogramar-cita` (T023); ninguna en el grupo del dentista. T012 prueba por rol el caso permitido y el denegado, en GET y PATCH. T041 deja la excepción en el roadmap. |
| P4 Entrada validada y SQL con bindings | ✅ | T025 y T027 validan solo `fecha`, `hora` y `motivo` con `bail` y leen solo las claves validadas (el campo `formulario` solo lo lee la vista, D10); T022 define los mensajes en español (A8) y T027 añade `motivo` a la cancelación del paciente. T003 es DDL fijo; T022 usa Query Builder. T040 corre semgrep sobre lo tocado en `app/`, `database/` y `routes/web.php`. |
| P5 Nada sensible en el repo | ✅ | Sin variables de entorno ni secretos nuevos; T040 corre gitleaks. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. T040 corre `composer audit --locked`, `npm audit` y `trivy fs`, y anota el resultado frente a la línea base del plan y a EX2 (vence el 2026-10-31). |
| P7 Auditoría de datos sensibles | ❌ aceptado: historial de la cita con autor, fechas y motivo, sin evento formal (→ 022) y sin registro de los 403 del Gate nuevo (→ 020) — aprobado por el usuario el 2026-10-07 | T003 y T022 escriben la entrada de historial; T011 la prueba; T041 anota los residuos. |
| P8 Correlación y logs limpios | ❌ aceptado: una `QueryException` distinta de 1205/1213 registraría el SQL con el motivo; la redacción la hace la 023 — aprobado por el usuario el 2026-10-07 | Ninguna tarea añade `Log::`. T014 prueba que ni la reprogramación ni su rechazo escriben en los logs; T040 busca `Log::` nuevos; T041 anota el residuo. |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 incorpora `.btn-danger`, «Formulario accesible» y la pantalla «Reprogramar cita» al sistema antes de cualquier vista; T024, T026, T028 y T029 dependen de él. T015 prueba los pares de contraste del componente, el foco visible, que las pantallas nuevas y los bloques tocados no tienen `style=` ni colores literales, y que los errores van asociados a su campo, con un test por pantalla o bloque. Verificación manual en T025 y T029; capturas en T031. |

## Preparación
- [ ] T001 Incorporar al sistema de diseño los "Cambios a incorporar al sistema" del plan; `system.md` sube a 1.3.0 con su fila en "Decisiones" (tipo `diseño`, fuente `usuario`) — docs/design/system.md, docs/design/system.html — hecho cuando: el validador da 0 errores en `docs/design`, `system.md` está en 1.3.0 con lo que sigue y `system.html` sigue sin recursos externos (0 `http`) — cubre: CA6
  - el componente `.btn-danger` (texto `var(--red)` sobre `var(--red-bg)`, 6.13:1; borde `var(--border-width)` `var(--red)`; foco con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`) en Componentes → Botón;
  - el componente «Formulario accesible» (`.formulario-accesible`) en Componentes, con los valores y contrastes del plan (etiqueta en `--text`, campo completo con borde `--border-control`, `.btn-primary` sobre `--p500`, `:focus-visible` con `--focus-ring`);
  - la pantalla «Reprogramar cita» en Pantallas principales, sin capturas todavía (las añade T031);
  - DS1 y DS2 anotan que avanzan en la pantalla de reprogramar;
  - `system.html` muestra `.btn-danger` en reposo y con foco, y un campo con etiqueta, error y botón principal dentro de `.formulario-accesible`.
- [ ] T002 Registrar la línea base en `feature/028-reprogramar-citas`: recuento de `php artisan test`; que hoy `GET /admin/citas/{id}/reprogramar` da 404; que hoy un paciente cancela una cita agendada que empieza dentro de 2 horas; qué archivos de los que se van a tocar pasan `pint --test` (`ReservaCitas.php`, `EstadosCita.php`, `CambioDeEstadoNoPermitido.php`, `CitaNoAgendable.php`, `CitasController.php`, `ClienteCitasController.php`, `AppServiceProvider.php`, `routes/web.php`); y los recuentos de usuarios, citas, historial y personal de `dentissa_test` — docs/specs/028-reprogramar-citas/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
- [ ] T003 Crear la migración que añade a `historial_citas` `fecha_hora_anterior` y `fecha_hora_nueva` (`DATETIME NULL`; `down()` las quita) y aplicarla en la base de desarrollo y en `dentissa_test` (`-e DB_DATABASE=dentissa_test … migrate --force`) — database/migrations/2026_10_07_000000_reprogramacion_en_historial_citas.php — hecho cuando: `SHOW COLUMNS FROM historial_citas` lista las dos columnas con nulo en las dos bases; en `dentissa_test`, `migrate:rollback --step=1` las quita y otro `migrate` las vuelve a añadir; la suite da la línea base de T002 y `pint --test` pasa — cubre: CA7 — depende: T002
- [ ] T004 Refactorizar `ReservaCitas` sin cambiar comportamiento: extraer de `reservarConBloqueo` los métodos públicos `bloquearDentista(string $id)` (espera de bloqueo de la sesión y `FOR UPDATE` del dentista; `noDisponible()` si ya no existe) y `comprobarHueco(string $dentistaId, Carbon $inicio, Carbon $fin)` (horario del día, franja y solape), con un docblock que dice que se llaman dentro de una transacción ya abierta; `reservarConBloqueo` los usa. La exclusión de la propia cita llega en T022 — app/Agenda/ReservaCitas.php — hecho cuando: `ReglaAgendaTest`, `ReservaConcurrenteTest`, `AltaCitasFlujosTest`, `AltaCitasRolesTest` y `AltaCitasValidacionTest` pasan sin cambios, la suite da la línea base de T002 y `pint --test` pasa — cubre: CA4, CA10 — depende: T002

## Tests (antes de implementar)
- [ ] T010 Ampliar la fixture `EscenarioEstadosCita` sin cambiar lo que ya crea: `conHorarios()` (D1 y D2 con horario de lunes a viernes de 09:00 a 18:00, solo para los tests que lo llaman), `reprogramar(Usuario $usuario, string $cita, string $fecha, string $hora, ?string $motivo = null, array $extra = [])` (PATCH a la ruta de reprogramar del grupo del usuario) y `fechaHoraDe(string $cita)` — tests/Feature/Concerns/EscenarioEstadosCita.php — hecho cuando: los métodos existen, los tests de la 026 que usan la fixture pasan igual que en la línea base y `pint --test` pasa
- [ ] T011 [P] Escribir `ReprogramarCitaTest` (admin y asistente), con los tests del plan. Cada rechazo comprueba que la fecha, la hora y el estado no cambiaron — tests/Feature/ReprogramarCitaTest.php — hecho cuando: todos fallan por la razón esperada (la ruta no existe: 404) — cubre: CA1, CA4, CA5, CA7, CA9 — depende: T010
  - `test_la_administracion_reprograma_cualquier_cita` y `test_el_asistente_reprograma_citas_de_sus_dentistas`;
  - `test_solo_cambian_fecha_y_hora_y_el_hueco_anterior_queda_libre`: mismo paciente, dentista, servicio y duración; otra cita cabe después en el hueco anterior;
  - `test_aplica_la_regla_de_agenda_al_nuevo_hueco`: sin horario, fuera de franja, solape, citas seguidas aceptadas, canceladas y no asistidas no cuentan, empezar justo al inicio de la franja y terminar justo al final aceptados (025 CA3), y la cita de otro dentista a la misma hora no cuenta (025 CA7) (A15); mensajes exactos de `CitaNoAgendable`, en el campo `hora` (D14);
  - `test_la_propia_cita_no_cuenta_como_conflicto`: moverla 30 min dentro de su propio hueco;
  - `test_una_fecha_pasada_o_igual_se_rechaza`, con los mensajes exactos del plan en `hora`;
  - `test_la_nueva_hora_se_interpreta_en_la_zona_de_la_clinica` (A4): ahora = 2026-10-05 16:00 UTC (10:00 locales); mover a las 12:00 locales de ese día se acepta y a las 09:59 se rechaza con `enElPasado()`;
  - `test_una_confirmada_vuelve_a_agendada`;
  - `test_una_cita_cerrada_o_empezada_no_se_reprograma`: completada, cancelada y no asistió con el mensaje `cerrada`; en curso y agendada ya empezada con «Esta cita ya empezó y no se puede reprogramar.»;
  - `test_la_reprogramacion_deja_una_sola_entrada_de_historial` (autor, estado anterior y nuevo, `fecha_hora_anterior`, `fecha_hora_nueva` y motivo) y `test_un_rechazo_no_deja_historial`;
  - `test_los_campos_anadidos_a_mano_no_cambian_la_cita`: `dentista_id`, `servicio_id`, `duracion_min`, `paciente_id` y `estado` enviados a mano;
  - `test_una_fecha_u_hora_mal_formada_da_error_de_validacion`: formatos erróneos y listas en `fecha` y `hora`; error del campo, nunca 500;
  - `test_los_mensajes_de_validacion_estan_en_espanol` (A8): fecha u hora vacías («Elige una fecha.», «Elige una hora.»), motivo como lista y de 501 caracteres, con el texto exacto;
  - `test_el_hueco_ocupado_no_revela_la_otra_cita`.
- [ ] T012 [P] Escribir `ReprogramarCitaRolesTest`, en GET y PATCH. Cada denegación comprueba que la cita no cambió — tests/Feature/ReprogramarCitaRolesTest.php — hecho cuando: fallan por la razón esperada los que esperan 200 o 403 en las rutas nuevas (hoy 404); los 404 que hoy pasan porque la ruta no existe van en el mismo test que su caso permitido, que falla; `test_fuera_de_alcance_tampoco_cancela_definitivamente` pasa hoy (026) y se comprueba rompiendo un momento `CambiarEstadoCita::alcance` (anotado y restaurado) — cubre: CA5, CA8 — depende: T010
  - `test_la_administracion_y_el_asistente_abren_el_formulario` (200);
  - `test_el_dentista_no_puede_reprogramar`: 403 en las rutas de admin, asistente y cliente; en `/dentista/citas/{id}/reprogramar` no hay ruta;
  - `test_el_asistente_no_reprograma_citas_de_dentistas_no_asignados` (403);
  - `test_el_paciente_no_encuentra_la_cita_de_otro` (404, junto con su cita propia → 200);
  - `test_el_personal_no_puede_usar_la_ruta_del_paciente` y `test_el_paciente_no_puede_usar_la_ruta_del_personal` (403);
  - `test_fuera_de_alcance_tampoco_cancela_definitivamente`: asistente fuera de alcance con `estado=cancelada` → 403; paciente con la cita de otro en `cancelar` → 404;
  - `test_el_formulario_de_una_cita_que_no_se_puede_mover_muestra_el_motivo` (A6): admin y asistente con una cita completada, cancelada, no asistida, en curso y agendada ya empezada, y el paciente con una cita fuera de plazo: 200, el motivo, un enlace al detalle, y ni formulario de reprogramar ni `#no-reagendar`.
- [ ] T013 [P] Escribir `ReprogramarCitaPacienteTest`, con la cita de D1 del 2026-10-06 a las 10:00 locales — tests/Feature/ReprogramarCitaPacienteTest.php — hecho cuando: fallan por la razón esperada (sin rutas de reprogramar, cancelación hasta el inicio, el motivo no se guarda, mensajes de validación en inglés); `test_el_paciente_cancela_con_24_horas_o_mas` pasa hoy y se comprueba haciendo que `cancelar` rechace siempre un momento (anotado y restaurado) — cubre: CA2, CA3, CA5, CA6, CA9 — depende: T010
  - `test_el_paciente_reprograma_con_24_horas_o_mas`: ahora 2026-10-05 16:00 UTC, borde exacto de 24 h, aceptado;
  - `test_el_hueco_nuevo_puede_empezar_en_menos_de_24_horas` (A26): con la cita a ≥ 24 h, moverla a las 12:00 locales del mismo día se acepta;
  - `test_con_menos_de_24_horas_se_rechaza_con_el_mensaje_de_plazo`: 16:01 UTC, mensaje exacto del plan, la cita no cambia;
  - `test_el_detalle_oculta_reprogramar_fuera_de_plazo_y_explica_por_que`: dentro de plazo, «Reprogramar» y «Cancelar»; fuera, ninguno de los dos y el mensaje de plazo;
  - `test_la_lista_muestra_el_aviso_corto_fuera_de_plazo` (A13): dentro de plazo, «Reprogramar» y «Cancelar»; fuera, ninguno y «Faltan menos de 24 h: llama a la clínica para cambiarla.»;
  - `test_el_paciente_cancela_con_24_horas_o_mas` y `test_el_paciente_no_cancela_con_menos_de_24_horas` (mensaje de plazo);
  - `test_el_paciente_cancela_con_motivo_desde_la_pantalla` (A14): el GET de su pantalla de reprogramar tiene `#no-reagendar` con un formulario hacia `cliente.citas.cancelar` y campo `motivo`; al enviarlo, el motivo queda en el historial;
  - `test_el_paciente_no_ve_estados_internos` (A7): con una cita completada, no asistida o en curso, el GET y el PATCH dan «Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.» y nunca el texto de `cerrada()`;
  - `test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano`: PATCH directo de reprogramar y de cancelar;
  - `test_los_mensajes_de_validacion_de_la_cancelacion_estan_en_espanol` (A8): motivo como lista y de 501 caracteres, con los textos de la 026.
- [ ] T014 [P] Escribir `ReprogramarConcurrenteTest`, con la conexión `segunda_conexion` que confirma sus propios datos y los borra en `tearDown` con `finally`, como `ReservaConcurrenteTest` — tests/Feature/ReprogramarConcurrenteTest.php — hecho cuando: fallan por la razón esperada (la ruta no existe y `App\Agenda\ReprogramacionCitas` tampoco); tras ejecutarlo, `dentissa_test` conserva los recuentos de T002; el de logs, si pasa hoy, se comprueba en T022 añadiendo un momento un `Log::info` con el motivo en el punto de reprogramar (anotado y restaurado) — cubre: CA10 — depende: T010
  - `test_la_reprogramacion_espera_al_bloqueo_del_dentista_y_responde_no_disponible`;
  - `test_reprogramar_y_agendar_el_mismo_hueco_solo_guarda_uno`: la que llega segunda recibe el mensaje de `noDisponible()` y la cita que no se movió conserva su fecha;
  - `test_la_cita_bloqueada_por_un_cambio_de_estado_responde_ocupada` (A21): la segunda conexión retiene `FOR UPDATE` sobre la cita (no sobre el dentista); la reprogramación responde con el mensaje de `CambioDeEstadoNoPermitido::ocupada()`, sin 500;
  - `test_un_interbloqueo_se_convierte_en_no_disponible`: conexión simulada con 1205, 1213 y `DeadlockException`, con el servicio construido como fija el plan (`new ReprogramacionCitas($c, new ReservaCitas($c, 1), new EstadosCita($c))`; A9);
  - `test_ni_la_reprogramacion_ni_su_rechazo_escriben_en_los_logs` (`MessageLogged`, con un control que sí registra).
- [ ] T015 [P] Escribir `ReprogramarCitaVistasTest`, con aserciones sobre la sección por `id` y no sobre toda la página; un test por pantalla o bloque, para que cada tarea de vistas cierre con tests completos (A12) — tests/Feature/ReprogramarCitaVistasTest.php — hecho cuando: todos fallan por la razón esperada (no hay botón, pantalla, componente ni entrada de reprogramación; los archivos de las vistas no existen; los bloques que se van a tocar tienen `style=`) — cubre: CA1, CA2, CA6, CA7 — depende: T010
  - `test_el_detalle_ofrece_reprogramar_solo_si_se_puede`: admin y asistente con una agendada; sin botón en una cerrada o empezada;
  - `test_el_detalle_del_dentista_no_enlaza_a_reprogramar` (A20);
  - `test_la_seccion_no_reagendar_cancela_con_motivo`: formulario `PATCH …/estado` con `estado=cancelada`, `motivo` y `formulario=no-reagendar`; al enviarlo, la cita queda cancelada con el motivo en el historial;
  - `test_cada_formulario_muestra_sus_valores_y_errores_en_su_seccion` (A5): tras un rechazo de reprogramar con motivo, el motivo de `#no-reagendar` sale vacío y sin error; tras una cancelación rechazada (motivo de 501 caracteres; cita ya cerrada), el error sale dentro de `#no-reagendar`; sin `id` duplicados en la página;
  - `test_el_historial_muestra_la_reprogramacion` («Reprogramada del … al …» y el cambio de estado si lo hubo) y `test_el_motivo_de_la_reprogramacion_se_muestra_escapado`;
  - `test_los_pares_de_contraste_cumplen_aa` (A1): como `HorarioDentistaVistasTest`, los pares del plan (`--text`, `--white` sobre `--p500`, `--red` sobre `--red-bg` ≥ 4.5:1; `--border-control` y `--focus-ring` ≥ 3:1) y los colores que usan las reglas de `.formulario-accesible` y `.btn-danger` en `app.css`, con un mínimo de comprobaciones;
  - `test_los_controles_tienen_foco_visible` (A1): `app.css` tiene `:focus-visible` con `--focus-ring` para los campos, botones y enlaces de `.formulario-accesible` y para `.btn-danger`;
  - `test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_personal` y `…_en_la_pantalla_del_paciente`: sin `style=` ni hex, `rgb(` o `px` literales en el parcial y en cada pantalla nueva, con un control positivo que sí los detecta;
  - `test_lo_nuevo_solo_usa_el_sistema_en_el_detalle_del_personal`, `…_en_el_detalle_del_paciente` y `…_en_la_lista_del_paciente` (A2): sin `style=` en los bloques tocados, por `id` (acciones e `historial` del detalle; acciones y aviso del panel), y el «Cancelar» del paciente con `.btn-danger`;
  - `test_los_errores_del_personal_van_asociados_a_su_campo` y `test_los_errores_del_paciente_van_asociados_a_su_campo`: `aria-describedby` y `aria-invalid` en fecha y hora tras un error.

## Implementación
- [ ] T020 Añadir los mensajes nuevos del plan sin cambiar los existentes: `CitaNoAgendable::enElPasado()` («Elige una fecha y hora que aún no hayan pasado.») y `mismaFechaYHora()` («La cita ya está en esa fecha y hora. Elige otra.»); `CambioDeEstadoNoPermitido::fueraDePlazo()` («Faltan menos de 24 horas para tu cita: ya no puedes cambiarla ni cancelarla desde aquí. Llama a la clínica.») y `yaEmpezoPersonal()` («Esta cita ya empezó y no se puede reprogramar.») — app/Agenda/CitaNoAgendable.php, app/Agenda/CambioDeEstadoNoPermitido.php — hecho cuando: los cuatro métodos existen con el texto exacto, la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa en los dos archivos — cubre: CA2, CA3, CA4, CA5 — depende: T011, T013
- [ ] T030 Hacer que `vuelta()` reciba el rol (`vuelta(string $rol)`): admin y asistente, «Una cita no puede volver a «Agendada». Si hay que moverla, usa «Reprogramar».»; dentista, «Una cita no puede volver a «Agendada». Pide a tu asistente o a la administración que la reprogramen.»; `EstadosCita::comprobar` le pasa el rol del actor. En la misma tarea (D6, A18), `EstadosCitaTransicionesTest` aserta el texto nuevo y gana `test_el_mensaje_de_volver_a_agendada_depende_del_rol` (admin, asistente y dentista); `test_volver_a_agendada_nunca_se_permite` sigue rechazando la vuelta en `PATCH …/estado` (A3) — app/Agenda/CambioDeEstadoNoPermitido.php, app/Agenda/EstadosCita.php, tests/Feature/EstadosCitaTransicionesTest.php — hecho cuando: el test nuevo falla antes del cambio y `EstadosCitaTransicionesTest` pasa después; la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa en los tres archivos — cubre: CA5 — depende: T020
- [ ] T021 Cambiar `EstadosCita`: `ANTELACION_PACIENTE_HORAS = 24`; `dentroDelPlazoDelPaciente(object $cita): bool` (inicio en la zona de la clínica − ahora ≥ 24 h); `comprobarReprogramable(object $cita, Usuario $actor)` (cerrada → `cerrada()`; en curso o ya empezada → `yaEmpezoPersonal()` para el personal; paciente fuera de plazo → `fueraDePlazo()`); en `comprobar()`, la cancelación del paciente pasa de `yaEmpezo()` a `fueraDePlazo()` cuando no está dentro del plazo. En la misma tarea (D6), `EstadosCitaRolesTest` cambia `test_el_paciente_cancela_su_cita_antes_de_que_empiece` y `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` al plazo de 24 h y al mensaje de plazo, y `HistorialCitaTest` mueve el caso del paciente de `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor` a una cita con 24 h o más — app/Agenda/EstadosCita.php, tests/Feature/EstadosCitaRolesTest.php, tests/Feature/HistorialCitaTest.php — hecho cuando: pasan `EstadosCitaRolesTest`, `HistorialCitaTest`, `test_el_paciente_cancela_con_24_horas_o_mas` y `test_el_paciente_no_cancela_con_menos_de_24_horas` de T013; la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa — cubre: CA2, CA3, CA5 — depende: T013, T030
- [ ] T022 Crear `ReprogramacionCitas` y añadir a `ReservaCitas::comprobarHueco` el parámetro `?string $excluirCitaId = null` (`where('id', '!=', …)` en la consulta de solape) — app/Agenda/ReprogramacionCitas.php, app/Agenda/ReservaCitas.php — hecho cuando: `test_un_interbloqueo_se_convierte_en_no_disponible` de T014 pasa; `ReglaAgendaTest`, `ReservaConcurrenteTest` y `AltaCitas*` siguen en verde y `pint --test` pasa en los dos archivos — cubre: CA1, CA4, CA5, CA7, CA9, CA10 — depende: T003, T004, T011, T014, T021
  - Constructor `__construct(ConnectionInterface $db, ReservaCitas $reservas, EstadosCita $estados)` (A9).
  - `reglasDeEntrada()` y `mensajesDeEntrada()` estáticos (A8): los de `fecha` y `hora` de `ReservaCitas`, más `fecha.required` «Elige una fecha.», `hora.required` «Elige una hora.», `motivo` `nullable|string|max:500` con «El motivo debe ser un texto.» y «El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.».
  - `reprogramar(string $citaId, string $fecha, string $hora, Usuario $actor, ?string $motivo): void`, en una transacción:
    - lee el dentista de la cita, `bloquearDentista` y bloquea la cita (`FOR UPDATE`; 404 si desapareció);
    - si la espera se agota al bloquear la cita → `CambioDeEstadoNoPermitido::ocupada()` (A21, D15);
    - `comprobarReprogramable`;
    - interpreta la nueva fecha y hora en `config('clinica.zona_horaria')` (D13, A4): pasada → `enElPasado()`, igual → `mismaFechaYHora()`;
    - `comprobarHueco` excluyendo la cita;
    - actualiza `fecha_hora`, `estado` (confirmada → agendada) y `actualizado_en`, e inserta una sola entrada de historial con `estado_anterior`, `estado_nuevo` = `agendada`, las dos fechas, `motivo`, `cambiado_por` y `fecha`.
  - El resto de 1205, 1213 y `DeadlockException` → `CitaNoAgendable::noDisponible()`; docblock sobre no llamarlo dentro de otra transacción (D3).
- [ ] T023 Crear `ReprogramarCita::autorizar(Usuario $usuario, object $cita): Response` (primero el alcance de `CambiarEstadoCita::alcance()`: 403 al personal fuera de alcance, 404 al paciente con una cita ajena; después el rol: `admin`, `asistente` o `cliente`; el dentista se deniega con mensaje) y registrar `Gate::define('reprogramar-cita', …)` en `AppServiceProvider::boot()` — app/Autorizacion/ReprogramarCita.php, app/Providers/AppServiceProvider.php — hecho cuando: `Gate::has('reprogramar-cita')` es verdadero, la suite no tiene fallos nuevos fuera de T011–T015 y `pint --test` pasa en los dos archivos — cubre: CA8 — depende: T012
- [ ] T024 Crear en `app.css`, solo con añadidos, `.btn-danger` y el componente `.formulario-accesible` con los valores de T001 (campo completo, sin depender de `citas.css`), y las clases del historial que sustituyen a sus estilos en línea (ancho de la hora y color del punto, con tokens). Crear el parcial `citas/partials/reprogramar.blade.php` y la pantalla del personal `citas/reprogramar.blade.php` (no carga `citas.css`), según el plan — public/css/app.css, resources/views/citas/partials/reprogramar.blade.php, resources/views/citas/reprogramar.blade.php — hecho cuando: las vistas compilan (`php artisan view:cache` sin errores); pasan `test_los_pares_de_contraste_cumplen_aa`, `test_los_controles_tienen_foco_visible` y `test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_personal`; en `app.css` solo hay añadidos — cubre: CA1, CA6 — depende: T001, T015
  - El parcial va dentro de `.formulario-accesible`:
    - resumen con badge de estado;
    - formulario de fecha, hora y motivo con `formulario=reprogramar`, `id` propios (`reprogramar-motivo`…), `.field-hint-error` asociados con `aria-describedby` y `aria-invalid` y «Guardar nueva fecha»;
    - sección aparte `id="no-reagendar"` con `formulario=no-reagendar`, `cancelar-motivo`, `.btn-danger` «Cancelar la cita definitivamente» y el texto «No se puede deshacer», sin `confirm()` (D12).
  - `old()` y los errores (`motivo`, `estado`, mensaje `error`) solo se pintan en la sección cuyo `formulario` coincide con `old('formulario')` (D10).
  - Si la cita ya no se puede mover: el motivo y un enlace al detalle en lugar de los dos formularios (A6).
  - El parcial recibe del controlador las rutas de guardar y de cancelar.
- [ ] T025 Añadir a `CitasController` `reprogramarForm(string $id)` y `reprogramar(Request, string $id, ReprogramacionCitas)`, y `puede_reprogramar` en `show`; declarar `GET` y `PATCH /{admin,asistente}/citas/{id}/reprogramar` (`citas.reprogramar`, `citas.reprogramar.update`) solo en esos dos grupos — app/Http/Controllers/CitasController.php, routes/web.php — hecho cuando: pasan todo `ReprogramarCitaTest`, `ReprogramarConcurrenteTest`, los casos del personal de `ReprogramarCitaRolesTest`, `test_cada_formulario_muestra_sus_valores_y_errores_en_su_seccion` y `test_los_errores_del_personal_van_asociados_a_su_campo`; `php artisan route:list` no lista reprogramar bajo `/dentista`; `pint --test` pasa en los dos archivos · verificación manual: la pantalla de reprogramar del personal a 390 y 1440 px contra `docs/design/system.html` (sección «No reagendar» separada y marcada como irreversible, foco visible con teclado, errores leídos junto a su campo; a 390 px lo nuevo no añade desbordamiento propio) — cubre: CA1, CA4, CA5, CA7, CA8, CA9, CA10 — depende: T022, T023, T024
  - `reprogramarForm` (GET): busca la cita con dentista, paciente y servicio (404 si no existe), `Gate::authorize('reprogramar-cita')`, y pasa si se puede mover o el motivo.
  - `reprogramar` (PATCH): 404 → Gate → validación con `ReprogramacionCitas::reglasDeEntrada()` y `mensajesDeEntrada()`, sin leer otras claves → servicio; con éxito, al detalle con mensaje.
  - Rechazos (D14, A6, A22): los de `CitaNoAgendable` (hueco, pasada, igual) vuelven al formulario con `withInput()` y el error en `hora`; los de `CambioDeEstadoNoPermitido`, con el mensaje `error`, y el GET muestra el aviso.
- [ ] T026 Cambiar `citas/show.blade.php`: botón `.btn btn-outline` «Reprogramar» junto al cambio de estado cuando `puede_reprogramar`; en `id="historial"`, las entradas con `fecha_hora_anterior` se muestran como «Reprogramada del … al …», con el cambio de estado si lo hubo y el motivo escapado. Los dos bloques tocados (acciones e historial) quedan sin `style=`, dentro de `.formulario-accesible`, con las clases de T024 (A2) — resources/views/citas/show.blade.php — hecho cuando: pasan `test_el_detalle_ofrece_reprogramar_solo_si_se_puede`, `test_el_detalle_del_dentista_no_enlaza_a_reprogramar`, `test_la_seccion_no_reagendar_cancela_con_motivo`, `test_el_historial_muestra_la_reprogramacion`, `test_el_motivo_de_la_reprogramacion_se_muestra_escapado` y `test_lo_nuevo_solo_usa_el_sistema_en_el_detalle_del_personal`, y siguen en verde `EstadosCitaVistasTest` y `HistorialCitaTest` — cubre: CA1, CA6, CA7 — depende: T025
- [ ] T027 Añadir a `ClienteCitasController` `reprogramarForm` y `reprogramar`, ampliar `cancelar` y crear la pantalla del paciente; declarar `GET` y `PATCH /cliente/citas/{id}/reprogramar` con `->middleware('role:cliente')` — app/Http/Controllers/ClienteCitasController.php, resources/views/citas/cliente-reprogramar.blade.php, routes/web.php — hecho cuando: pasan los tests de `ReprogramarCitaPacienteTest` salvo `test_el_detalle_oculta_reprogramar_fuera_de_plazo_y_explica_por_que` y `test_la_lista_muestra_el_aviso_corto_fuera_de_plazo`, los casos del paciente de `ReprogramarCitaRolesTest`, `test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_paciente` y `test_los_errores_del_paciente_van_asociados_a_su_campo`; `pint --test` pasa en el controlador y en `routes/web.php` — cubre: CA2, CA3, CA5, CA6, CA8, CA9 — depende: T021, T022, T023, T024, T025
  - `reprogramarForm` y `reprogramar`: mismo esquema que T025, con la cita buscada por el paciente del usuario. Una cita cerrada o en curso da «Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.», nunca el texto de `cerrada()` (A7). Errores del hueco en `hora` (D14).
  - `cancelar`: `motivo` opcional (`nullable|string|max:500`, mensajes en español de la 026; A8) que pasa a `EstadosCita::cambiar`; los rechazos vuelven con `withInput()` (D10).
  - `citas/cliente-reprogramar.blade.php`: el parcial con las rutas del paciente.
- [ ] T028 En `ClienteCitasController`, `conPuedeCancelar` pasa a usar `dentroDelPlazoDelPaciente` (agendada o confirmada y dentro de plazo) y añade `puede_reprogramar`; `show` pasa `aviso_plazo` (mensaje de `fueraDePlazo()` cuando la cita está agendada o confirmada y fuera de plazo) en lugar de `aviso_cancelar`, e `index` pasa `aviso_plazo_corto` en las mismas condiciones (A13). `citas/cliente-show.blade.php` muestra «Reprogramar» y «Cancelar» (`.btn-danger`) solo dentro de plazo y, fuera, el aviso de plazo en su lugar; el bloque tocado queda sin `style=`, dentro de `.formulario-accesible` (A2). En la misma tarea (D6, A10), `EstadosCitaVistasTest` mueve la cita «pendiente» de `test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo` a 24 h o más (y cambia el `?filtro=pasadas` de su parte del listado, porque la cita deja de ser pasada) y `test_el_paciente_ve_por_que_no_puede_cancelar_una_cita_que_ya_empezo` aserta el mensaje de plazo — app/Http/Controllers/ClienteCitasController.php, resources/views/citas/cliente-show.blade.php, tests/Feature/EstadosCitaVistasTest.php — hecho cuando: pasan `EstadosCitaVistasTest`, `test_el_detalle_oculta_reprogramar_fuera_de_plazo_y_explica_por_que` y `test_lo_nuevo_solo_usa_el_sistema_en_el_detalle_del_paciente`; `pint --test` pasa en el controlador — cubre: CA2, CA3 — depende: T027
- [ ] T029 En `citas/cliente-index.blade.php`, mostrar «Reprogramar» y «Cancelar» (`.btn-danger`) solo dentro de plazo y, fuera, `aviso_plazo_corto` en su lugar; el bloque tocado queda sin `style=`, dentro de `.formulario-accesible` (A2, A13). Retirar `CambioDeEstadoNoPermitido::yaEmpezo()`, que deja de usarse (A29) — resources/views/citas/cliente-index.blade.php, app/Agenda/CambioDeEstadoNoPermitido.php — hecho cuando: pasan `test_la_lista_muestra_el_aviso_corto_fuera_de_plazo` y `test_lo_nuevo_solo_usa_el_sistema_en_la_lista_del_paciente`; `grep -rn "yaEmpezo()" app resources tests` no da resultados; la suite completa está en verde · verificación manual: el detalle y la lista del paciente dentro y fuera de plazo, y su pantalla de reprogramar, a 390 y 1440 px contra `docs/design/system.html` (foco visible, aviso de plazo legible, errores junto a su campo; a 390 px lo nuevo no añade desbordamiento propio) — cubre: CA2 — depende: T028
- [ ] T031 Añadir las capturas de la pantalla «Reprogramar cita» (personal y paciente) a 390 y 1440 px con `docs/design/capturar.mjs` y la base de capturas de la 1.2.0, y enlazarlas en `system.md` → Pantallas principales y en `system.html` (A23) — docs/design/capturas/ (generado por capturar.mjs), docs/design/system.md, docs/design/system.html — hecho cuando: existen las cuatro capturas, `system.md` y `system.html` las enlazan, el validador da 0 errores en `docs/design` y `system.html` sigue sin recursos externos — cubre: CA6 — depende: T029

## Integración y documentación
- [ ] T040 Hacer la verificación final — (sin archivos) — hecho cuando:
  - `php artisan test` completo da la línea base de T002 más los tests de T011–T015, todos en verde;
  - `vendor/bin/pint --test` pasa sobre todos los archivos PHP tocados;
  - `semgrep scan --config p/php --error` sobre los archivos de `app/` y `database/migrations/` tocados y `routes/web.php` no da hallazgos (A27);
  - `gitleaks detect --no-banner` no da hallazgos;
  - `composer audit --locked` no da avisos; `npm audit` y `trivy fs` quedan anotados frente a la línea base del plan y a EX2 (vence el 2026-10-31; si la liberación cae después, lo decide el usuario en `/release`) (A17);
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
  - `docs/security.md → Dónde se aplica`: el Gate `reprogramar-cita` y las rutas de reprogramar por rol (sin ruta en el grupo del dentista; `role:cliente` en las del paciente).
  - `docs/security.md`: la corrección pendiente que pide `role:cliente` en todo el grupo `/cliente` añade a su nota que las rutas de reprogramar del paciente ya lo tienen (spec 028; el resto del grupo → spec 015), sin cambiar de estado (A25). Ningún riesgo cambia de estado.
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
| CA2 | T013, T015 | T020, T021, T027, T028, T029 |
| CA3 | T013, T021 | T020, T021, T027, T028 |
| CA4 | T011 | T004, T020, T022, T025 |
| CA5 | T011, T012, T013, T030 | T020, T021, T022, T025, T027, T030 |
| CA6 | T013, T015 | T001, T024, T026, T027, T031 |
| CA7 | T011, T015 | T003, T022, T025, T026 |
| CA8 | T012, T040 | T023, T025, T027 |
| CA9 | T011, T013, T040 | T022, T025, T027 |
| CA10 | T014, T040 | T004, T022, T025 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T023, T025 | T012 |
| TM2 | T023, T027 | T012 |
| TM3 | T025, T027 | T011 |
| TM4 | T021, T022, T027 | T013 |
| TM5 | T022, T025, T027 | T011, T013 |
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
| app/Agenda/EstadosCita.php | T021, T030 |
| app/Agenda/CambioDeEstadoNoPermitido.php | T020, T029, T030 |
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
| docs/design/system.md, system.html | T001, T031 |
| tests/Feature/ (nuevos y modificados) | T010, T011, T012, T013, T014, T015, T021, T028, T030 |
| docs/architecture.md | T090 |
| docs/security.md, docs/observability.md, docs/deployment.md | T091 |
| docs/roadmap.md | T041 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Mensajes de rechazo en español que dicen qué hacer | T011, T013 (texto exacto y validación en español), T020, T022, T030 |
| WCAG 2.1 AA en lo nuevo | T015 (contraste, foco, sin `style=`, errores por campo) · verificación manual en T025 y T029 |
| Ninguna entrada produce un error del servidor | T011, T013, T014, T040 |
