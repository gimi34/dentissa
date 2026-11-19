---
spec: 050-hora-clinica
plan: plan.md
status: approved
impl_base: 3e02d57
---

# Tareas · 050 La hora de la clínica en todo el panel

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.

Escritas sobre `main` con la 049 ya fusionada (v0.21.0; merge `674f90c` en la rama), como pide el plan (D7).
Reloj de los tests: `Carbon::setTestNow(Carbon::parse('2026-11-18 03:50:00', 'UTC'))` (21:50 del 17 en la
clínica) salvo donde se indique. Tests con `docker run --rm` sobre este worktree (`dentissa_app` monta otro
clon); suite completa al cerrar cada fase; Pint sobre lo tocado.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ningún código antes de aprobar este `tasks.md`.
- ✅ P2: cada tarea de implementación depende de su tarea de test (T010–T015), escrita antes y en rojo con el
  código actual; el test de la agenda (T012) es de caracterización y se comprueba rompiendo la zona de la 049.
- ✅ P4: las reglas de fecha siguen en el servidor (T023, T024, T027, T028) y su test (T013) envía las fechas a
  mano, sin el formulario; comparaciones con bindings.
- ✅ P6: sin dependencias nuevas; `composer audit` y `npm audit --audit-level=high` en la CI y en T092 (las altas de
  `picomatch`, cubiertas por EX5 hasta el 2026-12-31).
- ✅ P9: ningún cambio de marcado, clase ni estilo; los tests de vistas existentes siguen en verde (T092).
- ✅ P10: `PaginasPublicasTest` y `PaginaInicioTest` en verde tras T029.
- ❌ aceptado P3 y P7 (plan D9; aprobado por el usuario el 2026-11-18, /analyze A1, A2 y A22): los endpoints cuya
  vista o regla de fecha cambian T021–T032 conservan su autorización y su falta de auditoría actuales (RS1 → 014,
  RS2 → 015, OB1.d/e → 022); ninguna tarea añade ni quita permisos ni eventos; T098 anota la deuda.
- ➖ P5, P8: sin datos sensibles nuevos ni logs (plan).

## Preparación
Ninguna: sin dependencias, configuración, flags ni migraciones (plan, «Contratos y datos» y «Rollout»).

## Tests (antes de implementar)
- [x] T010 Escribir `RelojClinicaTest` y la firma vacía de `RelojClinica` (cada método lanza «sin implementar»): `fechaHoy()` a las 17:59, 18:00 y 23:59 del 17 de la clínica da `2026-11-17` y a las 00:00 del 18 da `2026-11-18`; `momentoLocal()` a las 03:50 UTC del 18 da `2026-11-17 21:50:00`; `hoy()` es el inicio del 17 en la zona de la clínica; `delSistema('2026-11-18 03:50:00')` y `delSistema(Carbon UTC)` dan las 21:50 del 17 y `delSistema(null)` da `null`; `deCita('2026-11-17 23:00:00')` es posterior a `ahora()`; `fechaDe('2026-11-18')` da esa fecha en la zona de la clínica y `fechaDe` de `null`, `['x']`, `'ayer'`, `'2026-02-30'` y `'9999-12-31'` da `null` (A27); `test_la_zona_sale_de_la_configuracion` (con `clinica.zona_horaria` = `America/Bogota`, `fechaHoy()` la sigue) — `tests/Unit/RelojClinicaTest.php`, `app/Agenda/RelojClinica.php` (firma) — hecho cuando: fallan por «sin implementar» — cubre: CA8
  - nota: falla por «sin implementar» (7 casos).
- [x] T011 Escribir `HoraClinicaPanelesTest` con citas el 17 a las 21:00 y a las 23:00 y el 18 a las 10:00: `test_los_paneles_del_personal_muestran_el_dia_de_la_clinica` (encabezado «martes, 17 de noviembre» o su formato en cada panel, y la lista y las cuentas de hoy con las del 17 y sin la del 18; administración, dentista y asistente) (CA1); `test_mis_doctores_cuenta_las_citas_de_hoy_de_la_clinica` (CA1); `test_el_paciente_ve_como_proxima_su_cita_de_esta_noche` (la de las 23:00 en «Tu próxima cita» y en las próximas de «Mis citas»; la de las 21:00 en las pasadas y en el historial del panel) (CA2); `test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche` (CA3), con un paciente que cumple años el 18: a las 21:50 del 17 la ficha muestra aún la edad anterior (CA3, A10); la fecha de la tarjeta de agenda del panel de la dentista es el 17/11/2026 (A12); `test_las_cuentas_del_mes_usan_el_mes_de_la_clinica` («citas este mes» a las 20:00 del 30 de noviembre de la clínica y pacientes atendidos del panel de la dentista a las 20:00 del 31 de diciembre) (CA6); `test_los_bordes_del_dia_de_la_clinica` (el panel del personal a las 17:59, 18:00 y 23:59 del 17 y a las 00:00 del 18) (CA8) — `tests/Feature/HoraClinicaPanelesTest.php` — hecho cuando: fallan por mostrar el día, la edad o el mes UTC — cubre: CA1, CA2, CA3, CA6, CA8 — depende: T010
  - nota: 6 casos en rojo por el día, la edad o el mes UTC.
- [x] T012 Escribir `test_la_agenda_usa_el_dia_de_la_clinica` sobre el calendario de la 049 (CA4): a las 21:50 del 17, `/admin/citas` abre la semana del 16 al 21; el 17 va marcado como hoy en la cabecera de la semana (`cal-dia-cab--hoy`), en la tira (`tira-dia--hoy`) y en la lista («Hoy» en el grupo del 17), y ningún otro día lo va; y «Hoy» enlaza a `fecha=2026-11-16` — `tests/Feature/HoraClinicaPanelesTest.php` — hecho cuando: pasa (la 049 ya usa la hora de la clínica) y se comprueba cambiando un momento la zona de `CitasController::index` a `UTC`: fallan las marcas de hoy, que pasarían al 18 (la semana y el enlace «Hoy» no distinguen las dos horas); restaurado — cubre: CA4 — depende: T011
  - nota: comprobado rompiendo: con la zona de CitasController::index en UTC falla «Cabecera: solo el 17 es hoy»; restaurado.
- [x] T013 Escribir `HoraClinicaFormulariosTest`: `test_los_formularios_proponen_fechas_del_dia_de_la_clinica` (`min` 2026-11-17 en «Nueva cita» del personal y del paciente; `min` 2026-11-18 en la próxima visita del alta y de la ficha del expediente, también con el reloj a las 10:00 del 17 de la clínica (A11); fecha sugerida 2026-11-18 en las dos altas de cita, también la que sale de una próxima visita del 18 en «Nueva cita» con `paciente_id` (A12); `max` de nacimiento 2025-11-17, hace un año, en el alta de paciente, el perfil y los dos registros (A7); el año del pie de `/` el de la clínica, también a las 20:00 del 31 de diciembre; y el consejo del día del panel del paciente, el del martes) (CA5); `test_el_servidor_acepta_las_fechas_validas_segun_el_dia_de_la_clinica` (cita del paciente el 17 a las 23:30 con un horario de prueba de la dentista que la cubre, próxima visita el 18 por el alta y por la ficha, y en la ficha una próxima visita ya guardada y vencida que llega sin cambios (A21), nacimiento el 16 en el alta y la edición de paciente, el perfil y los dos registros) (CA5); `test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica` (enviadas a mano: cita del paciente el 16; próxima visita el 17 por el alta, `POST`, y por la ficha, `PUT`, también cuando cambia una ya guardada (A4, A21); nacimiento el 17 en el alta y la edición de paciente, el perfil y los dos registros (A5); con «La fecha de la cita no puede ser anterior a hoy.», «La próxima visita debe ser posterior a hoy.» y, en pacientes, «La fecha de nacimiento debe ser anterior a hoy.») (CA10, TM1); `test_agendar_ignora_una_fecha_manipulada` (`GET /cliente/agendar?fecha[]=x` y `?fecha=ayer` responden 200 con la fecha sugerida de siempre) (TM2, A3); `test_el_aviso_de_proxima_visita_cuenta_los_dias_de_la_clinica` (una del 17 no sale vencida a las 21:50 del 17, una del 24 sale cercana y una del 25 no (A13)) (CA5) — `tests/Feature/HoraClinicaFormulariosTest.php` — hecho cuando: fallan por el día UTC, por los mensajes en inglés, por la regla que falta en la ficha, por la `fecha` manipulada (500) o por el aviso con signo — cubre: CA5, CA10 — depende: T010
  - nota: 5 casos en rojo: día UTC, mensajes en inglés, regla que falta en la ficha, 500 de la fecha manipulada y aviso con signo. El consejo del día usa date('N'), que no sigue el reloj de los tests: en rojo salvo si la suite corre en martes.
- [x] T014 Escribir `HoraClinicaFechasSistemaTest`: `test_agendada_el_y_el_historial_se_muestran_en_hora_de_la_clinica` (cita creada a las 03:50 UTC del 18 y cambiada de estado a las 04:10 UTC: el detalle del personal y el del paciente dicen «17/11/2026 21:50» y el historial «17/11 22:10»); `test_las_fechas_de_alta_se_muestran_en_hora_de_la_clinica` (expediente, paciente, usuario y consentimiento de la galería creados a las 03:50 UTC del 18 se muestran con el 17 en sus listas y fichas; en la ficha del expediente, la fecha larga del encabezado y la de «d/m/Y H:i» (A17)); `test_las_fechas_de_reprogramacion_no_se_convierten` («Reprogramada del … al …» muestra las horas locales tal cual) — `tests/Feature/HoraClinicaFechasSistemaTest.php` — hecho cuando: fallan los dos primeros por la hora UTC; el de reprogramación pasa y se comprueba convirtiendo un momento `fecha_hora_anterior` con `delSistema` en la vista (falla); restaurado — cubre: CA7 — depende: T010
  - nota: 2 casos en rojo por la hora UTC; comprobado rompiendo: convirtiendo fecha_hora_anterior en la vista falla el de reprogramación; restaurado.
- [x] T015 Corregir los tests de la deuda 026/R6: `HistorialCitaTest.php` espera la fecha del historial en hora de la clínica (`05/10 11:00` para las 17:00 UTC) y `EstadosCitaVistasTest.php` pierde el comentario de la deuda D4 — `tests/Feature/HistorialCitaTest.php`, `tests/Feature/EstadosCitaVistasTest.php` — hecho cuando: el de `HistorialCitaTest` falla con la hora UTC de hoy — cubre: CA7
  - nota: HistorialCitaTest en rojo con la hora UTC de hoy (espera 05/10 11:00).

- [x] T016 Ejecutar antes de cualquier cambio las suites de lo que ya usa la hora de la clínica (`EstadosCita*Test`, `ReprogramarCita*Test`, `HorarioDentista*Test`, `CitasFueraDelHorarioTest`, `tests/Feature/Reportes/`, `tests/Feature/Auditoria/`, `CorreosRestablecimientoTest`, `RecuperarContrasena*Test`, `AvisoProduccionTest`, `ContrasenaDistintaTest` (A19)) y anotar su resultado como línea base de CA9 — sin archivos (solo ejecución) — hecho cuando: el resultado y el número de tests quedan en la nota de la tarea; T092 las repite sin que ninguna haya cambiado ni falle — cubre: CA9
  - nota: línea base de CA9 (2026-11-18): 400 tests, 5223 aserciones, todos en verde. Suite completa antes de implementar: 1348 en verde; solo fallan los 20 tests nuevos de esta spec.

## Implementación
- [x] T020 Implementar `RelojClinica` (`zona`, `ahora`, `hoy`, `fechaHoy`, `momentoLocal`, `deCita`, `delSistema`) con `config('clinica.zona_horaria')` — `app/Agenda/RelojClinica.php` — hecho cuando: T010 pasa — cubre: CA8 — depende: T010
  - nota: incluye fechaDe (plan, A27), que T010 prueba.
- [x] T021 Pasar `DashboardController` a la hora de la clínica: `fechaHoy()` en los paneles de administración, dentista y asistente, mes de la clínica en los pacientes atendidos del mes de la dentista, y `momentoLocal()` en la próxima cita y las pasadas del paciente — `app/Http/Controllers/DashboardController.php` — hecho cuando: los casos de los paneles de T011 que dependen de los datos pasan — cubre: CA1, CA2, CA6, CA8 — depende: T011, T020
- [x] T022 [P] Fecha del encabezado de los paneles y de la tarjeta de agenda de la dentista con `RelojClinica::ahora()` (en lugar de `now()`) — `resources/views/dashboard/admin.blade.php`, `resources/views/dashboard/dentista.blade.php`, `resources/views/dashboard/asistente.blade.php` — hecho cuando: `test_los_paneles_del_personal_muestran_el_dia_de_la_clinica`, `test_los_bordes_del_dia_de_la_clinica` y el caso de la tarjeta de agenda de T011 pasan — cubre: CA1, CA8 — depende: T021
- [x] T023 [P] Paciente: próximas y pasadas de «Mis citas» con `momentoLocal()`, fecha sugerida `hoy()->addDay()` y la `fecha` de la dirección propuesta solo si `RelojClinica::fechaDe` la acepta (TM2, A27), regla `after_or_equal:`.`fechaHoy()` con «La fecha de la cita no puede ser anterior a hoy.», `min` del formulario con `fechaHoy()` y el consejo del día del panel con el día de la clínica — `app/Http/Controllers/ClienteCitasController.php`, `resources/views/citas/cliente-agendar.blade.php`, `resources/views/dashboard/cliente.blade.php` — hecho cuando: `test_el_paciente_ve_como_proxima_su_cita_de_esta_noche`, los casos del paciente de T013 y `test_agendar_ignora_una_fecha_manipulada` pasan — cubre: CA2, CA5, CA10 — depende: T013, T021
  - nota: comprobado rompiendo (TM1, TM2): sin la regla after_or_equal y proponiendo la fecha de la dirección sin validar, sus tests fallan; restaurado. HoraClinicaPanelesTest: el historial se distingue por cita-enlace, porque la tarjeta de la próxima cita también enlaza a su detalle.
- [x] T024 [P] Pacientes: próxima cita de la ficha con `momentoLocal()`, `before:`.`fechaHoy()` en el alta y la edición con «La fecha de nacimiento debe ser anterior a hoy.» y `max` del alta con hace un año desde el día de la clínica (`hoy()->subYear()`, A7) — `app/Http/Controllers/PacientesController.php`, `resources/views/pacientes/create.blade.php` — hecho cuando: la parte de la próxima cita de `test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche` y los casos de nacimiento del alta y la edición de T013 pasan — cubre: CA3, CA5, CA10 — depende: T011, T013, T020
  - nota: comprobado rompiendo (TM1): sin la regla before en el alta y, por separado, en la edición, el test falla; restaurado.
- [x] T025 [P] «Nueva cita» del personal: fecha sugerida `hoy()->addDay()`, la próxima visita sugerida solo si es posterior a hoy de la clínica (en lugar de `isFuture()`, A12), `fechaDe` delegando en `RelojClinica::fechaDe` (A27), y `min` con `fechaHoy()` — `app/Http/Controllers/CitasController.php`, `resources/views/citas/create.blade.php` — hecho cuando: los casos de «Nueva cita» del personal de T013 pasan y `CalendarioCitas*Test` y `CalendarioCitasParametrosTest` siguen en verde — cubre: CA5 — depende: T013, T020
- [ ] T026 [P] Personal: «citas este mes» de la ficha de la dentista con el mes de la clínica y «Mis doctores» con `fechaHoy()` — `app/Http/Controllers/PersonalController.php` — hecho cuando: `test_las_cuentas_del_mes_usan_el_mes_de_la_clinica` (la parte de la ficha) y `test_mis_doctores_cuenta_las_citas_de_hoy_de_la_clinica` pasan — cubre: CA1, CA6 — depende: T011, T020
- [ ] T027 [P] Expedientes: `after:`.`fechaHoy()` en `proxima_visita` del alta (`store`) y de la ficha (`update`, que hoy no la valida; A4; en la ficha solo si la fecha cambia respecto de la guardada, A21) con «La próxima visita debe ser posterior a hoy.», `min` del alta y de la ficha con el día siguiente de la clínica, aviso de vencida o cercana comparando fechas con `hoy()` y la distancia en días sin signo (A13), y las dos fechas de alta de la ficha (encabezado y «d/m/Y H:i») con `delSistema` (A17) — `app/Http/Controllers/ExpedientesController.php`, `resources/views/expedientes/create.blade.php`, `resources/views/expedientes/show.blade.php` — hecho cuando: los casos de la próxima visita de T013 y la parte del detalle del expediente de T014 pasan — cubre: CA5, CA7, CA10 — depende: T013, T014, T020
- [ ] T028 [P] Perfil y registro: `before:`.`fechaHoy()` en `fecha_nacimiento` (sus mensajes en español no cambian) y `max` del perfil con hace un año desde el día de la clínica (`hoy()->subYear()`, A7) — `app/Http/Controllers/PerfilController.php`, `app/Http/Controllers/RegisterController.php`, `resources/views/cliente/perfil.blade.php` — hecho cuando: los casos del perfil y del registro de T013 pasan y los tests del perfil y del registro (`PerfilCambioCorreoTest`, `LimiteIntentosTest`, `TerminosPrivacidadTest`, `ContrasenaDistintaTest`; A18) siguen en verde — cubre: CA5, CA10 — depende: T013, T020
- [ ] T029 [P] Registro y páginas públicas: `max` de nacimiento de los dos formularios del registro con hace un año desde el día de la clínica (`hoy()->subYear()`, A7) y el año del pie con `RelojClinica::ahora()->year` — `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`, `resources/views/layouts/publico.blade.php` — hecho cuando: los casos del registro y del pie de T013 pasan y `PaginasPublicasTest` y `PaginaInicioTest` siguen en verde — cubre: CA5 — depende: T013, T020
- [ ] T030 [P] Fechas del sistema del detalle de la cita: «Agendada el» del personal y del paciente y la fecha de cada entrada del historial con `RelojClinica::delSistema(...)`; `fecha_hora_anterior` y `fecha_hora_nueva` sin convertir — `resources/views/citas/show.blade.php`, `resources/views/citas/cliente-show.blade.php` — hecho cuando: `test_agendada_el_y_el_historial_se_muestran_en_hora_de_la_clinica`, `test_las_fechas_de_reprogramacion_no_se_convierten` y T015 pasan · verificación manual: en local, el detalle de una cita agendada después de las 18:00 de la clínica dice la hora de la clínica — cubre: CA7 — depende: T014, T015, T020
- [ ] T031 [P] Fechas de alta en listas y fichas: expedientes y pacientes con `delSistema`; y la edad de la ficha del paciente con el día de la clínica (en lugar de `->age`, que usa la hora UTC; A10) — `resources/views/expedientes/index.blade.php`, `resources/views/pacientes/index.blade.php`, `resources/views/pacientes/show.blade.php` — hecho cuando: la parte de expedientes y pacientes de `test_las_fechas_de_alta_se_muestran_en_hora_de_la_clinica` y el caso de la edad de T011 pasan — cubre: CA3, CA7 — depende: T011, T014, T020
- [ ] T032 [P] Fechas de alta del usuario y del consentimiento de la galería con `delSistema` — `resources/views/usuarios/show.blade.php`, `resources/views/galeria/index.blade.php` — hecho cuando: `test_las_fechas_de_alta_se_muestran_en_hora_de_la_clinica` pasa entero — cubre: CA7 — depende: T014, T020

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` (convención de fechas: el sistema guarda en UTC, las citas en hora local, y toda referencia a hoy o ahora y toda fecha mostrada pasa por `RelojClinica`), el comentario de `zona_horaria` en `config/clinica.php` (A20) y el mapa de `AGENTS.md`, fila `app/Agenda/` (ruta protegida: con aprobación del usuario)
- [ ] T091 Actualizar `docs/deployment.md` (la fila de `CLINICA_ZONA_HORARIA` describe que decide toda referencia a hoy, ahora y las fechas mostradas; A20) y `docs/observability.md` si cambiaron logs, eventos de auditoría o métricas (el plan dice que no: dejar nota)
- [ ] T092 Marcar spec como `implemented`: suite completa (con las de regresión de CA9 sin tocar, las de T016), Pint, gitleaks y semgrep sobre lo tocado, `composer audit` y `npm audit --audit-level=high` (A15); el grep del plan (fila RNF «una sola zona») en `app/Http` y `resources/views` sin resultados fuera de sus excepciones (A14, A25); criterios `[x]` y `docs/specs/README.md`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones) y `verificar.sh`; comprobar solo leyendo que la base de producción está en UTC (`SELECT @@system_time_zone, NOW(), UTC_TIMESTAMP()` con el usuario de la aplicación; si no, se para y se revisa D3); después de las 18:00 de la clínica, el usuario abre el panel y comprueba que la fecha y las citas de hoy son las del día; vigilar los 5xx. Rollback: `bash deploy/volver.sh <tag anterior>`, sin la copia
- [ ] T098 Marcar spec como `released`; en `docs/roadmap.md`, cerrar la deuda D4 de la 026 y 026/R6 y anotar como deuda las excepciones P3 y P7 de la 050 (endpoints del plan → specs 014, 015 y 022; A26); en las specs de `extends`, una línea en su Historial

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011 | T021, T022, T026 |
| CA2 | T011 | T021, T023 |
| CA3 | T011 | T024, T031 |
| CA4 | T012 (caracterización) | — (la agenda de la 049 ya usa la hora de la clínica; el test la fija; A6) |
| CA5 | T013 | T023, T024, T025, T027, T028, T029 |
| CA6 | T011 | T021, T026 |
| CA7 | T014, T015 | T027, T030, T031, T032 |
| CA8 | T010, T011 | T020, T021, T022 |
| CA9 | T016 (línea base), T092 (las mismas suites al cerrar) | — (D4: no se toca lo que ya usa la hora de la clínica) |
| CA10 | T013 | T023, T024, T027, T028 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T023, T024, T027, T028 | T013 |
| TM2 | T023 | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `app/Agenda/RelojClinica.php` | T010, T020 |
| `DashboardController` | T021 |
| `ClienteCitasController` | T023 |
| `PacientesController` | T024 |
| `CitasController` | T025 |
| `PersonalController` | T026 |
| `ExpedientesController` | T027 |
| `PerfilController`, `RegisterController` | T028 |
| Vistas con «hoy» y «ahora» | T022, T023, T024, T025, T027, T028, T029 |
| Vistas con fechas del sistema | T027, T030, T031, T032 |
| `HistorialCitaTest`, `EstadosCitaVistasTest` (026/R6) | T015 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Comprobado en la franja de 18:00 a 23:59 y en los bordes | T010–T014 (reloj a las 21:50 del 17), T011 (`test_los_bordes_del_dia_de_la_clinica`) |
| Una sola zona, configurada en un solo lugar | T010 (`test_la_zona_sale_de_la_configuracion`), T092 (grep) |
