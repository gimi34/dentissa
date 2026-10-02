---
spec: 026-estados-cita
plan: plan.md
status: approved
impl_base: 7aa05ac
---

# Tareas · 026 Estados de la cita y quién puede cambiarlos

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 026 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T011–T015) se escriben antes de cualquier implementación, y cada tarea de implementación depende de los tests de sus criterios. Cada CA tiene al menos un test que falla hoy. Los casos que ya se cumplen dentro de un CA se comprueban rompiendo el código actual un momento: el estado inexistente en T011; en T012, el personal fuera de alcance (también con un estado inválido), el paciente con una cita en otro estado, el paciente en la ruta del personal, "eliminar" por otro rol y el alcance del listado y del detalle. Todo con `DatabaseTransactions`; la segunda conexión de T014 borra sus datos en `tearDown`. |
| P3 Autorización en servidor | ❌ aceptado: tres endpoints GET se modifican solo para pasar datos a la vista y conservan su autorización actual. `GET /{rol}/citas/{id}` (pasa `$disponibles`, `$empezada` e `$inicio`) verifica el rol con middleware y el alcance con `autorizadoParaCita`, sin Gate → spec 014. `GET /cliente/citas` y `GET /cliente/citas/{id}` (pasan `puede_cancelar`) siguen en `/cliente` sin `role:cliente`, con el alcance en la consulta por `paciente_id` → spec 015 — aprobado por el usuario el 2026-10-02 | Los tres endpoints que cambian estado sí cumplen. T022 registra los Gates `gestionar-cita` (alcance) y `cambiar-estado-cita` (alcance y matriz por rol). T024 y T026 los aplican, con el alcance antes de validar, y T026 añade `role:cliente`. T012 prueba por rol y acción el caso permitido y el denegado, también en "eliminar". T024 hace que los helpers de alcance de `CitasController` deleguen en `AlcanceClinico`. T041 anota la excepción en el roadmap. |
| P4 Entrada validada y SQL con bindings | ✅ | T024 valida `estado` y `motivo` y deja de leer `notas_internas`. T023 usa Query Builder con bindings. T021 usa DDL fijo. T040 corre semgrep sobre los archivos de `app/` y `database/` tocados. |
| P5 Nada sensible en el repo | ✅ | T020 solo añade `CLINICA_ZONA_HORARIA` con su valor por defecto público; T040 corre gitleaks. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. |
| P7 Auditoría de datos sensibles | ❌ aceptado: el cambio de estado y su motivo quedan en el historial de la cita con autor, pero sin evento de auditoría formal en el almacén solo-anexado (→ spec 022, como ya fija la spec); los 403 del Gate `cambiar-estado-cita` no se registran (registro central de accesos denegados → spec 020) — aprobado por el usuario el 2026-10-02 | T041 anota los dos residuos en el roadmap. |
| P8 Correlación y logs limpios | ❌ aceptado: si el `insert` del historial o el `update` de la cita fallan con una `QueryException` distinta de 1205/1213, el reporte por defecto de Laravel registra el SQL con sus bindings, motivo incluido; la redacción de bindings para toda la aplicación la hace la spec 023 — aprobado por el usuario el 2026-10-02 | Ninguna tarea añade llamadas a `Log::`. T013 comprueba que ni el cambio ni el rechazo escriben datos en logs. T041 anota el residuo. |

## Preparación
- [x] T001 Registrar la línea base en `feature/026-estados-cita`, con cuatro datos: el recuento de `php artisan test` en verde; que hoy un dentista que envía `estado=cancelada` en `PATCH /dentista/citas/{id}/estado` lo consigue; que un cambio con motivo deja dos filas en `historial_citas`, una sin autor; y que `SHOW TRIGGERS` en `dentissa_test` lista `trg_historial_cita` — docs/specs/026-estados-cita/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cuatro datos
  - nota: 2026-10-02, rama `feature/026-estados-cita` en 7aa05ac. (1) `php artisan test` → 77 passed (404 assertions). (2) Con un test temporal no versionado (usa la fixture de T010): un dentista que envía `estado=cancelada` en `PATCH /dentista/citas/{id}/estado` recibe 302 y la cita queda `cancelada`. (3) Un cambio del admin con motivo deja 2 filas en `historial_citas`: una sin `cambiado_por` (trigger) y otra sin `estado_anterior` (controlador). (4) `SHOW TRIGGERS LIKE 'citas'` en `dentissa_test` lista `trg_historial_cita`. Recuentos de `dentissa_test`: 8 usuarios, 2 citas, 1 fila de historial, 1 fila de `personal`.
- [x] T002 Formatear con Pint `routes/web.php`, sin cambiar comportamiento (hoy falla `pint --test`; T026 toca una línea) — routes/web.php — hecho cuando: `vendor/bin/pint --test routes/web.php` pasa, `php artisan route:list` da las mismas rutas que antes y la suite da la línea base de T001
  - nota: 2026-10-02. `vendor/bin/pint routes/web.php` (function_declaration, method_argument_space, fully_qualified_strict_types…); `pint --test` pasa. `route:list --json` antes y después: las 91 rutas coinciden en método, URI, nombre, acción y middleware (solo cambia el orden del JSON). Suite: 77 passed.

## Tests (antes de implementar)
- [ ] T010 Crear la fixture `EscenarioEstadosCita` con admin, dentistas D1 y D2, asistente A1 asignado a D1, pacientes P1 y P2 con usuario cliente, y los helpers `cita(dentista, paciente, estado, inicio)`, `cambiar(usuario, cita, estado, motivo, extra)`, `entradasDeHistorial(cita)` y `seccion(html, id)` (XPath por `id`), según el plan — tests/Feature/Concerns/EscenarioEstadosCita.php — hecho cuando: el trait existe, crea los datos dentro de la transacción del test y `pint --test` pasa
- [ ] T011 [P] Escribir `EstadosCitaTransicionesTest`, con los tests del plan: `test_solo_se_aceptan_las_transiciones_de_la_tabla` (36 parejas como admin), `test_una_cita_cerrada_no_cambia_de_estado`, `test_una_transicion_no_permitida_indica_desde_que_estados_se_permite` (mensaje exacto), `test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza`, `test_a_la_hora_exacta_de_inicio_ya_se_puede_iniciar`, `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica` (solo personal, "iniciar" como dentista; 10:00 local: 15:30 UTC aún no, 16:00 UTC sí), `test_un_estado_inexistente_se_rechaza_sin_error_500`, `test_reabrir_una_cita_cancelada_se_rechaza` (admin: cancelada → agendada y cancelada → confirmada reciben el mensaje `cerrada`), `test_volver_a_agendada_nunca_se_permite` (admin: confirmada → agendada recibe el mensaje `vuelta`) y `test_las_notas_internas_enviadas_con_el_estado_no_cambian` (admin, dentista y asistente). Ninguno usa la ruta del paciente — tests/Feature/EstadosCitaTransicionesTest.php — hecho cuando: todos fallan por la razón esperada (hoy se acepta todo y se guardan las notas), salvo el del estado inexistente, que pasa y, rompiendo un momento la regla `in:` de `updateEstado`, falla (anotado y restaurado) — cubre: CA1, CA2, CA3, CA8, CA11 — depende: T010
- [ ] T012 [P] Escribir `EstadosCitaRolesTest`, con los 18 tests de roles del plan: dentista (confirma/inicia/completa/no asistió, no cancela, no toca la cita de otro dentista); asistente (confirma/cancela/no asistió, no inicia ni completa, no toca dentistas no asignados); `test_fuera_de_alcance_con_estado_invalido_responde_403`; `test_un_rol_sin_la_transicion_recibe_403_aunque_la_cita_este_cerrada` (dentista con una completada pide `cancelada`); `test_el_alcance_del_listado_y_del_detalle_no_cambia`; "eliminar" como cancelación, 404 si no existe y `test_solo_el_admin_puede_eliminar_una_cita`; paciente (cancela antes de que empiece, no después, con la zona horaria: 10:00 local con 16:30 UTC se rechaza y con 15:30 UTC se cancela; no en otro estado; no la de otro → 404); personal en la ruta del paciente → 403; paciente en la ruta del personal → 403. Cada denegación comprueba que la cita no cambió — tests/Feature/EstadosCitaRolesTest.php — hecho cuando: fallan por la razón esperada los del dentista que cancela, el asistente que inicia o completa, el rol sin la transición sobre una cita cerrada, "eliminar" una completada, el paciente con la cita ya empezada, el 404 de "eliminar" y el personal en la ruta del paciente (hoy 404). Los que ya se cumplen (otro dentista, dentista no asignado, fuera de alcance con estado inválido, alcance del listado y del detalle, "eliminar" por otro rol, paciente en otro estado, paciente con la cita de otro, paciente en la ruta del personal) se comprueban rompiendo un momento `autorizadoParaCita`, `aplicarAlcanceRol`, el orden de alcance y validación, el `in_array` de `cancelar`, la consulta por `paciente_id` o el middleware de rol (anotado y restaurado) — cubre: CA4, CA5, CA6, CA7 — depende: T010
- [ ] T013 [P] Escribir `HistorialCitaTest`: `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor` (cada rol y "eliminar", con y sin motivo; autor, anterior, nuevo y motivo), `test_un_rechazo_no_deja_entrada_en_el_historial`, `test_el_historial_muestra_autor_estados_y_motivo` (sobre la sección `historial`), `test_el_motivo_se_muestra_escapado` y `test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` (`MessageLogged`, con un control que sí registra) — tests/Feature/HistorialCitaTest.php — hecho cuando: fallan por la razón esperada (hoy sin autor, dos filas con motivo, rechazos que no existen y una sección `historial` que no tiene `id`). El de logs pasa, y se comprueba añadiendo un momento un `Log::info` con el motivo en `updateEstado` (anotado y restaurado) — cubre: CA9 — depende: T010
- [ ] T014 [P] Escribir `CambioEstadoConcurrenteTest`, con la segunda conexión `segunda_conexion` que confirma sus propios datos y los borra en `tearDown` con `finally`: `test_un_cambio_se_evalua_contra_el_estado_ya_guardado_por_otro`, `test_el_bloqueo_de_la_cita_se_toma_antes_de_leer_su_estado` (`DB::listen`), `test_mientras_otro_tiene_la_cita_bloqueada_no_hay_error_500` (`innodb_lock_wait_timeout = 1` en la conexión de la app), `test_un_interbloqueo_se_convierte_en_mensaje` (conexión simulada con 1205, 1213 y `DeadlockException`, llamando a `EstadosCita::cambiar`) y `test_otro_error_de_base_de_datos_no_se_oculta` (1452) — tests/Feature/CambioEstadoConcurrenteTest.php — hecho cuando: los tres primeros fallan por la razón esperada (sin bloqueo ni regla de estado) y los dos de la conexión simulada fallan porque `App\Agenda\EstadosCita` no existe; tras ejecutarlo, `dentissa_test` conserva los recuentos de usuarios, citas e historial de la línea base — cubre: CA10 — depende: T010
- [ ] T015 [P] Escribir `EstadosCitaVistasTest`, una prueba por fila de "Qué ve cada rol" del plan: `test_el_formulario_solo_ofrece_las_transiciones_del_rol_y_del_estado`, `test_una_cita_cerrada_no_ofrece_cambios_y_explica_como_seguir`, `test_antes_de_la_hora_se_explica_desde_cuando_se_puede_iniciar`, `test_el_asistente_ve_quien_puede_completar_una_cita_en_curso`, `test_el_boton_cancelar_del_admin_solo_aparece_en_citas_abiertas` y `test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo`, con aserciones sobre la sección por `id` y no sobre toda la página — tests/Feature/EstadosCitaVistasTest.php — hecho cuando: todos fallan por la razón esperada (hoy se ofrecen los 6 estados, no hay avisos y el botón depende solo del estado) — cubre: CA2, CA6, CA7, CA8 — depende: T010

## Implementación
- [ ] T020 Crear `config/clinica.php` con `'zona_horaria' => env('CLINICA_ZONA_HORARIA', 'America/Mexico_City')` y documentar la variable en `.env.example` — config/clinica.php, .env.example — hecho cuando: `php artisan tinker --execute="echo config('clinica.zona_horaria');"` imprime `America/Mexico_City` y la suite no tiene fallos nuevos fuera de T011–T015 — cubre: CA7, CA8 — depende: T011
- [ ] T021 Crear la migración que retira `trg_historial_cita` (`up`: `DROP TRIGGER IF EXISTS`; `down`: lo recrea con el cuerpo de `database/dentissa.sql`) y aplicarla en la base de desarrollo y en `dentissa_test` (`-e DB_DATABASE=dentissa_test … migrate --force`) — database/migrations/2026_10_02_000000_retirar_trigger_historial_cita.php — hecho cuando: `SHOW TRIGGERS` no lista `trg_historial_cita` en ninguna de las dos bases; en `dentissa_test`, `migrate:rollback --step=1` lo vuelve a listar y otro `migrate` lo retira; `pint --test` pasa — cubre: CA9 — depende: T013
- [ ] T022 Crear `AlcanceClinico` (solo `personalId` y `dentistasDelAlcance`, firma del plan de la 014) y `CambiarEstadoCita` con `alcance` (solo alcance: 403 al personal, 404 al paciente) y `autorizar` (alcance y matriz por rol con los mensajes del plan; 403 aunque la cita esté cerrada; si el destino no está en `TRANSICIONES`, es decir `agendada`, solo comprueba el alcance), y registrar los Gates `gestionar-cita` y `cambiar-estado-cita` en `AppServiceProvider::boot()` — app/Autorizacion/AlcanceClinico.php, app/Autorizacion/CambiarEstadoCita.php, app/Providers/AppServiceProvider.php — hecho cuando: `Gate::has('gestionar-cita')` y `Gate::has('cambiar-estado-cita')` son verdaderos, `pint --test` pasa en los tres archivos y la suite no tiene fallos nuevos fuera de T011–T015 — cubre: CA4, CA5, CA6, CA7 — depende: T012
- [ ] T023 Crear `EstadosCita` (constructor con `ConnectionInterface`, `TRANSICIONES`, `CERRADOS`, `REQUIEREN_INICIO`, `cambiar` con transacción y `lockForUpdate()` antes de leer el estado, `disponibles`, `haEmpezado` en `config('clinica.zona_horaria')` con el borde incluido, escritura de una fila de historial con autor, conversión de 1205, 1213 y `DeadlockException` en `ocupada`, y docblock sobre no llamarlo dentro de otra transacción) y `CambioDeEstadoNoPermitido` (`$tipo` con `cerrada`, `vuelta`, `transicion`, `antesDeTiempo`, `yaEmpezo` y `ocupada`, y los mensajes del plan; el destino `agendada` da `cerrada` con la cita cerrada y `vuelta` si no) — app/Agenda/EstadosCita.php, app/Agenda/CambioDeEstadoNoPermitido.php — hecho cuando: `test_un_interbloqueo_se_convierte_en_mensaje` y `test_otro_error_de_base_de_datos_no_se_oculta` de T014 pasan y `pint --test` pasa en los dos archivos — cubre: CA1, CA2, CA3, CA7, CA8, CA9, CA10 — depende: T011, T013, T014, T020, T021, T022
- [ ] T024 Cambiar `CitasController`: `updateEstado(Request, EstadosCita)` carga la cita (404), aplica `gestionar-cita` (403) antes de validar, valida `estado` (`in:` de los 6, "Elige un estado válido.") y `motivo` sin `notas_internas`, aplica `cambiar-estado-cita` y llama a `cambiar`, y los rechazos van a `back()->withErrors(['estado' => …])`; `destroy(string, EstadosCita)` con 404, los dos Gates con `cancelada`, el mismo servicio y el mismo manejo; `personalIdPropio` y `dentistaIdsAsistente` delegan en `AlcanceClinico`. `show` no cambia en esta tarea (va en T025) — app/Http/Controllers/CitasController.php — hecho cuando: pasan todo `EstadosCitaTransicionesTest`, los tests del personal de `EstadosCitaRolesTest` (incluido el del alcance del listado y del detalle), `AltaCitasRolesTest` de la 025, `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor` (salvo el caso del paciente), `test_un_rechazo_no_deja_entrada_en_el_historial`, `test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` y los tres primeros de `CambioEstadoConcurrenteTest`, sin que ninguno de ellos dependa de cambios del detalle; `pint --test` pasa — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA8, CA9, CA10, CA11 — depende: T023
- [ ] T025 Cambiar `CitasController::show` (pasa `$disponibles`, `$empezada` e `$inicio` en lugar de `$estados`) y `citas/show.blade.php` según "Qué ve cada rol", revisándolo antes con la skill `design`: en "Cambiar estado", solo las opciones de `$disponibles` sin el estado actual, en `fieldset` con `legend` y con `@error('estado')` visible; avisos de cita cerrada, de "aún no empieza" y del asistente cuando no hay opciones; botón "Cancelar cita" del admin solo si `cancelada` está disponible; `id="historial"` con autor, anterior → nuevo, motivo y fecha — app/Http/Controllers/CitasController.php, resources/views/citas/show.blade.php — hecho cuando: pasan los cinco tests del personal de `EstadosCitaVistasTest`, `test_el_historial_muestra_autor_estados_y_motivo`, `test_el_motivo_se_muestra_escapado` y `test_el_alcance_del_listado_y_del_detalle_no_cambia`; `pint --test` pasa en el controlador; la nota recoge la revisión de `design` — cubre: CA2, CA6, CA8, CA9 — depende: T024
- [ ] T026 Cambiar `ClienteCitasController::cancelar` (Gate `cambiar-estado-cita` con `cancelada`; `cambiar` con el paciente como actor; `cerrada`, `vuelta` y `transicion` → "Esta cita ya no se puede cancelar. Si necesitas ayuda, llama a la clínica."; los demás tipos con su mensaje) y pasar `puede_cancelar` en `index` y `show`; añadir `->middleware('role:cliente')` a la ruta `cliente.citas.cancelar` — app/Http/Controllers/ClienteCitasController.php, routes/web.php — hecho cuando: pasan todos los tests del paciente y del personal en la ruta del paciente de `EstadosCitaRolesTest`, y el caso del paciente de `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`; `pint --test` pasa en los dos archivos — cubre: CA7, CA9 — depende: T002, T012, T023
- [ ] T027 Mostrar el botón "Cancelar" del paciente solo cuando `puede_cancelar`, en el detalle y en el listado, revisándolo antes con la skill `design` — resources/views/citas/cliente-show.blade.php, resources/views/citas/cliente-index.blade.php — hecho cuando: pasa `test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo`; la nota recoge la revisión de `design` — cubre: CA7 — depende: T026

## Integración y documentación
- [ ] T040 Hacer la verificación final — (sin archivos) — hecho cuando:
  - `php artisan test` completo da la línea base de T001 más los tests de T011–T015, todos en verde;
  - `vendor/bin/pint --test` pasa sobre todos los archivos PHP tocados;
  - `php artisan test --filter='EstadosCitaTransicionesTest|EstadosCitaRolesTest|HistorialCitaTest|CambioEstadoConcurrenteTest|EstadosCitaVistasTest'` dura menos de 10 s;
  - `semgrep scan --config p/php --error` sobre los archivos de `app/` y `database/migrations/` tocados no da hallazgos;
  - `gitleaks detect --no-banner` no da hallazgos;
  - `dentissa_test` conserva los recuentos de la línea base.

  — cubre: CA1, CA9, CA10
- [ ] T041 Añadir en `docs/roadmap.md → Pendientes` seis viñetas que citan la spec 026 y su decisión — docs/roadmap.md — hecho cuando: existen las viñetas de:
  - comparaciones de fecha que siguen en UTC: paneles, próximas citas del paciente, `after_or_equal:today` y fechas del historial (D4);
  - `/plan 014 --fix` debe partir de `AlcanceClinico` ya creado, de los Gates `gestionar-cita` y `cambiar-estado-cita` en `AppServiceProvider` y de que `CitasController` ya delega en él su alcance (D3);
  - excepción P3 de los tres GET: el detalle de cita del personal recibe su Gate en la 014, y "Mis citas" y el detalle del paciente, `role:cliente` en la 015 (D11);
  - la pregunta de la 013 sobre el trigger, resuelta: la escritura pasa a la aplicación (D2);
  - residuo P7: sin evento formal del cambio de estado → spec 022, y sin registro de los 403 del Gate → spec 020;
  - residuo P8: `QueryException` con el motivo en el log → spec 023.
- [ ] T090 Actualizar `docs/architecture.md`:
  - Módulos: la fila Agenda (personal) menciona `app/Agenda/EstadosCita.php`; nueva carpeta `app/Autorizacion`.
  - El párrafo del trigger: el historial lo escribe la aplicación.
  - "Esquema": el trigger, retirado por migración.
  - Deuda técnica: se retira "dos filas por cambio con motivo".
- [ ] T091 Actualizar la documentación de seguridad, observabilidad y despliegue:
  - `docs/security.md → Dónde se aplica`: los Gates `gestionar-cita` y `cambiar-estado-cita` y `role:cliente` en la cancelación del paciente. RS2.a sigue "pendiente", con la nota "cancelación del paciente con `role:cliente`: spec 026; resto del grupo → spec 015"; RS2.b sin cambios.
  - `docs/observability.md`, por corrección:
    - OB5.a → "en curso (spec 026)";
    - OB5.b sigue "pendiente", con la nota "cancelaciones con autor: spec 026; notas internas → spec 022";
    - OB5.c sin cambios (→ 022);
    - la "Brecha por confirmar" del trigger en producción, marcada como resuelta porque el trigger se retira (spec 026).
  - `docs/deployment.md`: la migración de la 026 va en el mismo paso que el código, y la variable `CLINICA_ZONA_HORARIA`.
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T040 | T023, T024 |
| CA2 | T011, T012, T015 | T022, T023, T024, T025 |
| CA3 | T011 | T022, T023, T024 |
| CA4 | T012 | T022, T024 |
| CA5 | T012 | T022, T024 |
| CA6 | T012, T015 | T022, T024, T025 |
| CA7 | T012, T015 | T020, T022, T023, T026, T027 |
| CA8 | T011, T015 | T020, T023, T024, T025 |
| CA9 | T013, T040 | T021, T023, T024, T025, T026 |
| CA10 | T014, T040 | T023, T024 |
| CA11 | T011 | T023, T024 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T022, T024 | T012 |
| TM2 | T022, T024, T026 | T012 |
| TM3 | T023 | T011 |
| TM4 | T023 | T014 |
| TM5 | T023 | T014 |
| TM6 | T024 | T011 |
| TM7 | T021, T023 | T013 |
| TM8 | T020, T023 | T011, T012 |
| TM9 | T023, T024 | T013 |
| TM10 | T026 | T012 |
| TM11 | T025 | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Agenda/EstadosCita.php | T023 |
| app/Agenda/CambioDeEstadoNoPermitido.php | T023 |
| app/Autorizacion/AlcanceClinico.php | T022 |
| app/Autorizacion/CambiarEstadoCita.php | T022 |
| app/Providers/AppServiceProvider.php | T022 |
| app/Http/Controllers/CitasController.php | T024, T025 |
| app/Http/Controllers/ClienteCitasController.php | T026 |
| routes/web.php | T002, T026 |
| config/clinica.php, .env.example | T020 |
| database/migrations/2026_10_02_000000_retirar_trigger_historial_cita.php | T021 |
| resources/views/citas/show.blade.php | T025 |
| resources/views/citas/cliente-show.blade.php, cliente-index.blade.php | T027 |
| tests/Feature/Concerns/EscenarioEstadosCita.php | T010 |
| tests/Feature/EstadosCitaTransicionesTest.php | T011 |
| tests/Feature/EstadosCitaRolesTest.php | T012 |
| tests/Feature/HistorialCitaTest.php | T013 |
| tests/Feature/CambioEstadoConcurrenteTest.php | T014 |
| tests/Feature/EstadosCitaVistasTest.php | T015 |
| docs/architecture.md | T090 |
| docs/security.md, docs/observability.md, docs/deployment.md | T091 |
| docs/roadmap.md | T041 |
