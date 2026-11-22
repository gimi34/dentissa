---
spec: 014-autorizacion-alcance-clinico
plan: plan.md
status: draft
---

# Tareas · 014 Autorización por alcance clínico

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Alcance:** CA1–CA12 cambian comportamiento; solo el 403 del asistente al editar fichas y expedientes ya
  existe y se caracteriza (plan, Estrategia de pruebas).
- **Commits:** `AGENTS.md` no admite commits con tests en rojo, así que cada tarea de test va en el mismo commit
  que la implementación que la pone en verde, y cada implementación va en el mismo commit que el ajuste de los tests
  existentes que rompe (T030–T033, plan D13): el commit no se hace hasta que la suite completa está en verde
  (`/analyze` A6).
- **Caracterizaciones** (plan, P2): los tests que pasan desde el principio se comprueban con la rotura que indica
  su tarea y se anota; el 403 del asistente al editar se rompe quitando el `role:` de la ruta, porque el Gate no se
  alcanza por HTTP (`/analyze` A7, A14).
- **Correcciones tras `/analyze` r1 (2026-11-22):** `/tasks --fix` editó en su lugar T013, T014, T015, T016, T017,
  T019, T022–T025, T027, T029–T033 y T098 (A3, A6–A9, A12–A14, A16, A18–A20, CA13, CA14). No se renumeró ninguna.
- **Escenario:** `EscenarioAlcanceClinico` (T001) sobre `EscenarioEstadosCita`: admin, D1, D2, asistente A1 de D1,
  P1 de D1, P2 de D2, P3 de D2 con cita confirmada con D1, P4 de D2 con cita cancelada con D1, P5 sin dentista ni
  citas; expedientes E1 (P1, firmado por D1), E2 (P1, firmado por D2) y E3 (P2, firmado por D2).
- **Auditoría en los tests:** cada denegación comprueba un `acceso_denegado` con su `resultado` (`denegado_404` o
  `denegado`), `recurso_tipo` (`pacientes` o `expedientes`) y `recurso_id`; cada lectura, el evento de lectura.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados (plan rehecho el 2026-11-22). Ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Cada tarea de implementación (T020–T029) depende de su tarea de test (T010–T019), que se escribe antes y falla por la razón esperada; las caracterizaciones (403 del asistente) se comprueban rompiendo, con su nota. | test: T010–T019 |
| P3 Autorización en servidor | ✅ | T021 registra los seis Gates y T022–T029 los aplican en cada acción; los tests de T011–T019 prueban cada criterio por rol, permitido y denegado. | test: T011–T019 |
| P4 Entrada validada y SQL con bindings | ✅ | T020 usa Query Builder (`whereIn`, `whereExists`); T024 `Rule::in`; T027 `Rule::exists(...)->where(...)`. | lint: semgrep en T034 |
| P5 Nada sensible en el repo | ✅ | Datos sintéticos en transacción (T001). | lint: gitleaks en T034 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea toca dependencias. | lint: `composer audit --locked` y `auditar-npm.mjs` en T034 |
| P7 Auditoría de datos sensibles | ✅ | CA11 en cada test de denegación (T013, T016, T017, T019) y CA12 en T013 y T016, con el modo crítico probado. | test: T013, T016, T017, T019 |
| P8 Correlación y logs limpios | ✅ | Ninguna tarea añade `Log::`; el evento de lectura no lleva datos del paciente (T013). | test: T013 · manual: diff sin `Log::` en T034 |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T025 y T028 solo añaden contenido con clases existentes; T015 y T018 prueban que los elementos nuevos no llevan `style=""`; verificación manual en T025 y T028. | test: T015, T018 · manual: T025, T028 |
| P10 Rutas indexables descubribles | ➖ | Sin rutas indexables (plan). | — |

## Preparación
- [ ] T001 Crear el escenario `EscenarioAlcanceClinico` sobre `EscenarioEstadosCita`, con P3, P4, P5, sus citas y los expedientes E1, E2 y E3 descritos en la cabecera, más ayudas para leer el último `acceso_denegado` y el último evento de lectura de un recurso — tests/Feature/Concerns/EscenarioAlcanceClinico.php — hecho cuando: crea los datos dentro de la transacción del test y T010 lo usa

## Tests e implementación por área

### La regla y los Gates
- [ ] T010 Escribir `AlcanceClinicoReglaTest`: P1 y P3 en el alcance de D1; P2, P4 y P5 fuera; A1 con el alcance de D1; el admin sin restricción; P5 solo para el admin; `aplicarAPacientes` devuelve exactamente esos pacientes por rol; y `test_reasignar_saca_del_alcance_en_el_acto` (P1 reasignado a D2 sale del alcance de D1 y entra en el de D2 en la siguiente consulta) — tests/Feature/AlcanceClinicoReglaTest.php — hecho cuando: falla por la razón esperada (los métodos aún no existen) — cubre: CA1, CA2, CA10 — depende: T001
- [ ] T011 Añadir a `AlcanceClinicoReglaTest` los casos de los seis Gates por rol con `Gate::forUser(...)->inspect(...)`: dentro del alcance → permitido; fuera → denegado como 404 (`denyAsNotFound`); dentro sin permiso → 403 (asistente al editar ficha o expediente; dentista que no firmó al editar un expediente); admin → permitido en todo — tests/Feature/AlcanceClinicoReglaTest.php — hecho cuando: falla por la razón esperada (los Gates no existen) — cubre: CA3, CA5, CA6, CA7, CA9 — depende: T010
- [ ] T020 Añadir a `AlcanceClinico` `aplicarAPacientes(Builder $consulta, Usuario $usuario): Builder` y `pacienteEnAlcance(Usuario $usuario, string $pacienteId): bool` con la regla del plan (asignado o con cita no cancelada; admin sin restricción), con Query Builder y sin caché (plan D11) — app/Autorizacion/AlcanceClinico.php — hecho cuando: pasan los casos de T010, siguen en verde los tests de la 026 que usan `dentistasDelAlcance`, y quitar un momento la condición de las citas o la exclusión de las canceladas hace fallar T010 — cubre: CA1, CA2, CA10 — depende: T010
- [ ] T021 Crear `AccesoClinico` con `verPaciente`, `editarPaciente`, `verExpediente`, `editarExpediente`, `crearExpediente` y `agendarParaPaciente` (fuera del alcance `denyAsNotFound()`; dentro sin permiso `deny(<mensaje>)`, p. ej. «Solo quien firmó el expediente puede modificarlo.»; admin `allow()`) y registrar los seis Gates en `AppServiceProvider` (plan D1, D2) — app/Autorizacion/AccesoClinico.php, app/Providers/AppServiceProvider.php — hecho cuando: pasan los casos de T011 y cambiar un momento un `denyAsNotFound()` por `deny()` hace fallar su caso — cubre: CA3, CA5, CA6, CA7, CA9 — depende: T011, T020

### Pacientes
- [ ] T012 Escribir en `AlcancePacientesTest` los tests del directorio: `test_el_directorio_del_dentista_es_su_alcance` (D1 ve P1 y P3, no P2, P4 ni P5; sin pacientes, la lista vacía), `test_el_directorio_del_asistente_es_el_de_sus_dentistas`, `test_el_admin_ve_a_todos` y `test_el_total_y_las_ciudades_son_del_alcance` (TM12) — tests/Feature/AlcancePacientesTest.php — hecho cuando: fallan por la razón esperada (hoy se ve a todos) salvo el del admin, que pasa — cubre: CA1, CA2 — depende: T001
- [ ] T013 Añadir a `AlcancePacientesTest`: `test_la_ficha_fuera_del_alcance_es_un_404_auditado` (dentista y asistente; sin el nombre del paciente en la respuesta), `test_editar_una_ficha_fuera_del_alcance_es_un_404`, `test_guardar_una_ficha_fuera_del_alcance_no_cambia_nada`, `test_un_paciente_ajeno_y_uno_inexistente_responden_igual` (TM9), `test_el_asistente_no_edita_fichas` (caracterización del 403; rotura: quitar el `role:` de la ruta), `test_leer_una_ficha_queda_registrado` (admin, dentista y asistente; el evento sin datos del paciente), `test_abrir_la_edicion_de_la_ficha_queda_registrado`, `test_sin_auditoria_no_se_muestra_la_ficha` (almacén de auditoría que falla → página de error, sin datos), `test_el_dentista_que_reasigna_fuera_vuelve_al_directorio` (CA10), `test_asignar_un_asistente_como_doctor_se_rechaza` (422, plan D15) y, en `test_un_paciente_ajeno_y_uno_inexistente_responden_igual`, también el `PUT` con datos inválidos (plan D17) — tests/Feature/AlcancePacientesTest.php — hecho cuando: fallan por la razón esperada salvo el del asistente, que pasa (caracterización) — cubre: CA3, CA10, CA11, CA12 — depende: T012
- [ ] T014 Añadir a `AlcancePacientesTest` `test_el_asistente_asigna_uno_de_sus_dentistas` (sin doctor → 422 sin crear nada; con un doctor que no apoya → 422 sin crear nada; con uno de los suyos → se crea asignado a él; el admin sigue pudiendo dejarlo sin doctor) y `test_el_aviso_de_duplicado_solo_enlaza_lo_que_se_puede_ver` (el asistente ve el nombre de un duplicado fuera de su alcance sin enlace; el admin, con enlace; CA14) — tests/Feature/AlcancePacientesTest.php — hecho cuando: fallan por la razón esperada los dos rechazos y el aviso sin enlace (hoy se crea y se enlaza), y pasan los otros (caracterización del caso válido: rotura, rechazar a un dentista propio) — cubre: CA8, CA14 — depende: T013
- [ ] T022 En `PacientesController::index`, aplicar `aplicarAPacientes` al listado, al total y a las ciudades, y limitar los dentistas del filtro al alcance del rol (todos para el admin, sus dentistas para el asistente, ninguno para el dentista) — app/Http/Controllers/PacientesController.php — hecho cuando: pasan los tests de T012 y siguen en verde `CiudadListaCerradaTest` y `PacienteSinCorreoTest`, con el ajuste de T033 en el mismo commit si alguno se rompe — cubre: CA1, CA2 — depende: T012, T020
- [ ] T023 En `PacientesController`, `show` y `edit` con su Gate (`ver-paciente`, `editar-paciente`) y el evento `paciente_leido` crítico tras el Gate; `update` con `Gate::authorize('editar-paciente')` **antes** de validar en lugar del `abort(403)`, y `dentista_id` de un dentista activo; tras guardar, si el paciente salió del alcance del dentista, volver al directorio con «El paciente quedó asignado a otro dentista.» (plan D3, D10, D15, D17) — app/Http/Controllers/PacientesController.php — hecho cuando: pasan los tests de T013, quitar un momento el Gate de `show` hace fallar el 404, quitar el evento de `edit` hace fallar su lectura registrada, y los tests existentes que rompa quedan en verde con su ajuste de T030–T033 en el mismo commit — cubre: CA3, CA10, CA11, CA12 — depende: T013, T021, T022
- [ ] T024 En el alta de pacientes del asistente, ofrecer solo sus dentistas y exigir uno (`required` y `Rule::in(<sus dentistas>)`; el admin sigue con el campo opcional y los dentistas activos, plan D15), con la ayuda «Asigna uno de tus dentistas» en el formulario; en `avisoDeDuplicados`, el enlace a la ficha solo si `ver-paciente` lo permite, el nombre siempre (CA14, plan D20) — app/Http/Controllers/PacientesController.php, resources/views/pacientes/create.blade.php — hecho cuando: pasa T014, `PacienteSinCorreoTest` y `CiudadListaCerradaTest` quedan en verde (con el ajuste de `test_los_enlaces_del_aviso_usan_la_ruta_del_rol` de T033 en el mismo commit), y quitar un momento el `Rule::in` o la condición del enlace hace fallar su test — cubre: CA8, CA14 — depende: T014, T023

### Vistas de pacientes
- [ ] T015 Escribir en `AlcanceVistasTest`: `test_el_filtro_de_doctor_segun_el_rol` (admin todos, asistente sus dentistas, dentista sin filtro), `test_el_directorio_vacio_segun_el_rol` (dentista sin alcance: «No tienes pacientes asignados» y sin botón de alta; con `buscar` sin coincidencias sale el mensaje de siempre, `/analyze` A18), `test_editar_en_la_ficha_segun_el_rol` (caracterización: rotura, quitar la condición de rol del botón) y `test_los_elementos_nuevos_no_llevan_estilos_en_linea` (para las piezas de pacientes) — tests/Feature/AlcanceVistasTest.php — hecho cuando: fallan por la razón esperada los que dependen del cambio — cubre: CA1, CA2, CA3 — depende: T001
- [ ] T025 En `pacientes/index`, el filtro «Doctor» según el rol y el estado vacío del dentista sin botón de alta; en `pacientes/show`, «Editar» con `@can('editar-paciente')`; sin `style=""` ni literales nuevos — resources/views/pacientes/index.blade.php, resources/views/pacientes/show.blade.php — hecho cuando: pasan los tests de T015 y `PacientesBotonAltaTest`, `NavegacionInternaTest` y `MovilPacientesExpedientesTest` quedan en verde (con sus ajustes de T032 y T033 en el mismo commit) · verificación manual: directorio y ficha a 390 y 1440 px con un dentista y un asistente, contra la pantalla actual — cubre: CA1, CA2, CA3 — depende: T015, T022, T023

### Expedientes
- [ ] T016 Escribir `AlcanceExpedientesTest`: `test_el_dentista_lee_el_expediente_de_otro_autor` (D1 abre E2), `test_la_ficha_lista_expedientes_de_otros_autores`, `test_el_asistente_lee_en_solo_lectura`, `test_el_expediente_fuera_del_alcance_es_un_404_auditado` (D1 y A1 con E3, sin el contenido), `test_modificar_el_expediente_de_otro_autor_se_rechaza` (403 auditado, E2 sin cambios), `test_el_autor_modifica_su_expediente`, `test_el_admin_modifica_cualquier_expediente`, `test_el_asistente_no_modifica_expedientes` (caracterización; rotura: quitar el `role:` de la ruta), `test_leer_un_expediente_queda_registrado`, `test_sin_auditoria_no_se_muestra_el_expediente`, `test_un_expediente_ajeno_y_uno_inexistente_responden_igual` y el índice del dentista con solo los que firmó de su alcance (plan D7) — tests/Feature/AlcanceExpedientesTest.php — hecho cuando: fallan por la razón esperada salvo los de caracterización (D1 lee E2 y la ficha lista expedientes de otros autores: rotura, filtrar por autor; el autor y el admin modifican: rotura, rechazar al autor; el asistente), que pasan — cubre: CA4, CA5, CA6, CA11, CA12 — depende: T001
- [ ] T017 Añadir a `AlcanceExpedientesTest` el alta: `test_abrir_el_alta_fuera_del_alcance_es_un_404` (auditado con el paciente), `test_el_alta_de_expediente_firma_el_propio_dentista`, `test_el_alta_con_otro_autor_se_rechaza` (403 auditado con `recurso_tipo` `pacientes` y el id del paciente, sin expediente nuevo; `/analyze` A19), `test_el_alta_con_una_cita_de_otro_paciente_se_rechaza` (422), un autor que no es dentista (422) `test_el_alta_para_un_paciente_fuera_del_alcance_es_un_404` (auditado con el paciente) y `test_el_alta_de_expediente_con_paciente_mal_formado_es_un_404` (consulta y cuerpo: texto que no es un UUID, ausente o en array → 404 sin 500; TM13) — tests/Feature/AlcanceExpedientesTest.php — hecho cuando: fallan por la razón esperada (hoy se acepta todo o da 500) salvo el alta válida, que pasa (caracterización; rotura: rechazar al propio autor) — cubre: CA7, CA11 — depende: T016
- [ ] T026 En `ExpedientesController`, `show` con `Gate::authorize('ver-expediente')` y el evento `expediente_leido` crítico; `update` con `Gate::authorize('editar-expediente')` en lugar del `abort(403)`; `index` del dentista limitado a los que firmó de pacientes de su alcance (plan D7) — app/Http/Controllers/ExpedientesController.php — hecho cuando: pasan los tests de T016 y quitar un momento el Gate de `update` hace fallar el rechazo del otro autor — cubre: CA4, CA5, CA6, CA11, CA12 — depende: T016, T021
- [ ] T027 En `ExpedientesController`, `create` y `store` con `crear-expediente` sobre el paciente de la consulta o del cuerpo (si se deniega, `Auditoria::denegar()` con el id del paciente); `paciente_id` con formato de UUID antes del Gate (si no, 404 sin `denegar()`, plan D16); en `store`, el dentista solo puede firmarse a sí mismo (otro autor → `denegar()` 403 con el paciente como recurso, plan D19), `dentista_id` de tipo dentista y `cita_id` del paciente con `Rule::exists(...)->where('paciente_id', …)` (422) (plan D4, D5) — app/Http/Controllers/ExpedientesController.php — hecho cuando: pasan los tests de T017, `AltaExpedienteSinCitaTest` queda en verde (con su ajuste de T030 en el mismo commit), y quitar un momento la comprobación del autor hace fallar su test — cubre: CA7, CA11 — depende: T017, T026

### Vistas de expedientes
- [ ] T018 Añadir a `AlcanceVistasTest` `test_editar_el_expediente_solo_el_autor` (autor y admin con formulario; otro dentista y el asistente con «Firmado por …» en solo lectura) y `test_el_autor_del_expediente_segun_el_rol` (dentista: él mismo, fijo; admin: selector), más sus piezas en el test de estilos en línea — tests/Feature/AlcanceVistasTest.php — hecho cuando: fallan por la razón esperada — cubre: CA4, CA6, CA7 — depende: T015
- [ ] T028 En `expedientes/show`, los formularios de edición y de próxima visita con `@can('editar-expediente')` y si no, solo lectura con «Firmado por …»; en `expedientes/create`, el autor fijo para el dentista (texto y campo oculto); sin `style=""` ni literales nuevos — resources/views/expedientes/show.blade.php, resources/views/expedientes/create.blade.php — hecho cuando: pasan los tests de T018 y `MovilPacientesExpedientesTest` y `HoraClinicaFormulariosTest` quedan en verde (con sus ajustes de T031 y T032 en el mismo commit) · verificación manual: expediente propio y ajeno a 390 y 1440 px con un dentista, y el alta de expediente, contra la pantalla actual — cubre: CA4, CA6, CA7 — depende: T018, T026, T027

### Citas
- [ ] T019 Escribir `AlcanceCitasTest`: `test_la_lista_de_pacientes_es_la_del_alcance` (dentista y asistente), `test_la_preseleccion_ajena_se_ignora` (`?paciente_id=` de P2 como D1: sin dentista ni próxima visita sugerida), `test_agendar_para_un_paciente_ajeno_es_un_404_auditado` (dentista y asistente; sin cita creada; evento con el paciente), `test_un_paciente_sin_dentista_solo_lo_agenda_el_admin` (P5), `test_un_paciente_ajeno_y_uno_inexistente_responden_igual_al_agendar`, un `paciente_id` mal formado → 404 sin 500 (TM13), `test_el_admin_agenda_para_cualquiera`, `test_el_detalle_de_una_cita_ajena_se_deniega_por_gate` (dentista y asistente: 403 auditado), `test_agendar_con_un_dentista_ajeno_se_deniega` (403), `test_el_detalle_de_una_cita_propia_se_ve` y que el detalle de la cita cancelada de P4 no enlaza la ficha para D1 (plan D14) — tests/Feature/AlcanceCitasTest.php — hecho cuando: fallan por la razón esperada los que cambian comportamiento; pasan los de caracterización (el admin agenda: rotura, filtrar también al admin; el 403 del detalle y del dentista ajeno, que ya da `autorizadoParaCita`: rotura, quitar la comprobación) — cubre: CA9, CA11, CA13 — depende: T001
- [ ] T029 En `CitasController`, la lista de pacientes de `create` con `aplicarAPacientes` e ignorar un `paciente_id` preseleccionado fuera del alcance; en `store`, `paciente_id` con formato de UUID (plan D16), el Gate `gestionar-cita` sobre el dentista elegido en lugar de `autorizadoParaCita` y `agendar-para-paciente` antes de la regla de agenda, con `Auditoria::denegar()` 404 con el paciente si se deniega (plan D6); en `show`, `Gate::authorize('gestionar-cita', $cita)`; `autorizadoParaCita` desaparece y `dentistaIdsAsistente` compara de forma estricta (plan D18); en `citas/show`, «Ver expediente del paciente» solo con `@can('ver-paciente', …)` (plan D14) — app/Http/Controllers/CitasController.php, resources/views/citas/show.blade.php — hecho cuando: pasan los tests de T019, `CalendarioCitasTest`, `CalendarioCitasAlcanceTest` y `EstadosCitaRolesTest` quedan en verde (con sus ajustes de T030 y T031 en el mismo commit), y quitar un momento el Gate de `store` o de `show` hace fallar su test — cubre: CA9, CA11, CA13 — depende: T019, T021

### Tests existentes (plan D13)
- [ ] T030 Ejecutar `AltaExpedienteSinCitaTest`, `CalendarioCitasAlcanceTest` y `CalendarioCitasTest`; ajustar el actor o los datos de los casos que abran recursos fuera del alcance, sin quitar aserciones — tests/Feature/AltaExpedienteSinCitaTest.php, tests/Feature/CalendarioCitasAlcanceTest.php, tests/Feature/CalendarioCitasTest.php — hecho cuando: los tres pasan y cada ajuste queda anotado (o se anota que no hizo falta); se hace junto con la tarea de implementación que rompe cada test, en su commit — depende: T020, T021
- [ ] T031 Lo mismo con `EstadosCitaRolesTest`, `HoraClinicaFechasSistemaTest` y `HoraClinicaFormulariosTest` — tests/Feature/EstadosCitaRolesTest.php, tests/Feature/HoraClinicaFechasSistemaTest.php, tests/Feature/HoraClinicaFormulariosTest.php — hecho cuando: los tres pasan y cada ajuste queda anotado; se hace junto con la tarea de implementación que rompe cada test, en su commit — depende: T020, T021
- [ ] T032 Lo mismo con `MovilPacientesExpedientesTest`, `MovilPersonalTest` y `NavegacionInternaTest` — tests/Feature/MovilPacientesExpedientesTest.php, tests/Feature/MovilPersonalTest.php, tests/Feature/NavegacionInternaTest.php — hecho cuando: los tres pasan y cada ajuste queda anotado; se hace junto con la tarea de implementación que rompe cada test, en su commit — depende: T020, T021
- [ ] T033 Lo mismo con `PacienteSinCorreoTest` (incluido `test_los_enlaces_del_aviso_usan_la_ruta_del_rol`, que pasa a esperar el enlace solo con permiso, CA14) y `PacientesBotonAltaTest`, y con cualquier otro test que la suite completa descubra — tests/Feature/PacienteSinCorreoTest.php, tests/Feature/PacientesBotonAltaTest.php — hecho cuando: la suite completa pasa y cada ajuste queda anotado; se hace junto con la tarea de implementación que rompe cada test — depende: T020, T021

## Integración y documentación
- [ ] T034 Pasar la verificación final: suite completa, Pint sobre los PHP tocados, gitleaks sobre el diff, semgrep `p/php` sobre los archivos tocados, `composer audit --locked`, `node .github/scripts/auditar-npm.mjs`, `python .ai/bin/aidd.py validate` sin ruta y revisión de que el diff no tiene `Log::` nuevos — sin archivos — hecho cuando: todo en verde y sin altas ni críticas nuevas respecto a la línea base del plan — depende: T025, T028, T029, T030, T031, T032, T033
- [ ] T090 Actualizar `docs/architecture.md`: la fila de `app/Autorizacion/` con `AccesoClinico` y sus seis Gates, y la regla de alcance de `AlcanceClinico` sobre pacientes (plan, Impacto en arquitectura) — docs/architecture.md — hecho cuando: lo describe y `aidd.py validate` sigue sin errores
- [ ] T091 Actualizar `docs/observability.md` con los eventos `paciente_leido` y `expediente_leido` (modo crítico, recurso e id, sin datos) en la tabla de eventos; `docs/deployment.md` sin cambios (sin variables ni pasos nuevos); se anota en la tarea — docs/observability.md — hecho cuando: los eventos constan y `aidd.py validate` sigue sin errores
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097, con cuentas de prueba y sin escribir datos reales en ningún documento:
  - CA3 y CA11: con una cuenta de dentista de prueba, abrir la ficha de un paciente de otro dentista da la página 404 y aparece en `/admin/auditoria` como `acceso_denegado`
  - CA12: abrir la ficha de un paciente propio deja `paciente_leido` en `/admin/auditoria`
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; vigilar 5xx en `/dentista/*` y `/asistente/*` y el volumen de `acceso_denegado`. Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia
- [ ] T098 Marcar spec como `released`; en las specs 003, 006, 007 y 054 anotar en su Historial los criterios que la 014 reemplaza o cambia (006 CA1, CA4, CA6, CA7; 007 CA1, CA3, CA4, CA6, CA7, CA8; 003 CA10; el aviso de duplicado de la 054); en `docs/security.md`, el texto de RS1.a y RS1.b pasa de «Policy» a Gates; en el roadmap, las excepciones P3 de la 026, la 028 y la 050 quedan resueltas (`/analyze` A16, A20)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T015 | T020, T022, T025 |
| CA2 | T010, T012, T015 | T020, T022, T025 |
| CA3 | T011, T013, T015 | T021, T023, T025 |
| CA4 | T016, T018 | T026, T028 |
| CA5 | T011, T016 | T021, T026 |
| CA6 | T011, T016, T018 | T021, T026, T028 |
| CA7 | T011, T017, T018 | T021, T027, T028 |
| CA8 | T014 | T024 |
| CA9 | T011, T019 | T021, T029 |
| CA10 | T010, T013 | T020, T023 |
| CA11 | T013, T016, T017, T019 | T023, T026, T027, T029 |
| CA12 | T013, T016 | T023, T026 |
| CA13 | T019 | T029 |
| CA14 | T014 | T024 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021, T023 | T013 |
| TM2 | T020, T022, T023 | T012, T013 |
| TM3 | T023 | T013 |
| TM4 | T026 | T016 |
| TM5 | T026 | T016 |
| TM6 | T027 | T017 |
| TM7 | T024 | T014 |
| TM8 | T029 | T019 |
| TM9 | T021 | T013 |
| TM10 | T023, T026 | T013 |
| TM11 | T020 | T010 |
| TM12 | T022 | T012 |
| TM13 | T027, T029 | T017, T019 |
| TM14 | T024 | T014 |
| TM15 | T029 | T019 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Autorizacion/AlcanceClinico.php | T020 |
| app/Autorizacion/AccesoClinico.php | T021 |
| app/Providers/AppServiceProvider.php | T021 |
| app/Http/Controllers/PacientesController.php | T022, T023, T024 |
| app/Http/Controllers/ExpedientesController.php | T026, T027 |
| app/Http/Controllers/CitasController.php | T029 |
| resources/views/pacientes/index.blade.php | T025 |
| resources/views/pacientes/create.blade.php | T024 |
| resources/views/pacientes/show.blade.php | T025 |
| resources/views/expedientes/show.blade.php | T028 |
| resources/views/expedientes/create.blade.php | T028 |
| resources/views/citas/show.blade.php | T029 |
| tests/Feature/Concerns/EscenarioAlcanceClinico.php | T001 |
| tests/Feature/AlcanceClinicoReglaTest.php | T010, T011 |
| tests/Feature/AlcancePacientesTest.php | T012, T013, T014 |
| tests/Feature/AlcanceExpedientesTest.php | T016, T017 |
| tests/Feature/AlcanceCitasTest.php | T019 |
| tests/Feature/AlcanceVistasTest.php | T015, T018 |
| Tests existentes (D13) | T030, T031, T032, T033 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Cada criterio por rol, permitido y denegado | T010–T019 |
| Denegar no expone datos ni confirma la existencia | T013 (`…_un_paciente_ajeno_y_uno_inexistente_responden_igual`), T016 |
