---
spec: 014-autorizacion-alcance-clinico
status: approved
constitution_version: 1.3.0
created: 2026-11-22
---

# Plan · 014 Autorización por alcance clínico

> Rehecho con `/plan --redo` el 2026-11-22 sobre el código de la v0.28.0. El plan de 2026-09-28 (constitución
> 1.0.0) está en `history/plan.v1.md`: daba por nueva `AlcanceClinico` (la creó la 026), usaba una API de
> auditoría que la 020 no tiene y no conocía los cambios de la 050, la 051 y la 054 en `PacientesController`.

## Enfoque técnico
La regla de alcance ya tiene casa: `App\Autorizacion\AlcanceClinico` (spec 026) resuelve hoy los dentistas del
alcance de cada usuario. Esta spec le añade las dos preguntas sobre pacientes: filtrar una consulta a los
pacientes del alcance (asignado a un dentista del alcance **o** con alguna cita no cancelada con él) y decidir si
un paciente concreto lo está. Sobre ella, una clase nueva `App\Autorizacion\AccesoClinico` respalda seis Gates
(ADR 0003), que devuelven `denyAsNotFound()` fuera del alcance (404: no se confirma que el paciente exista) y
`deny()` dentro del alcance cuando el rol no puede modificar (403). Los tres controladores sustituyen sus
`abort(403)` sueltos por esos Gates y filtran sus listados.

La auditoría ya existe (spec 020): el registro central de denegaciones audita los 403 y los 404 de los Gates en
rutas con `{id}`, y donde el paciente llega en el cuerpo o en la consulta (alta de expediente y de cita) se usa
`Auditoria::denegar()` con el id del paciente (CA11). Cada lectura permitida de una ficha o de un expediente deja
un evento en modo crítico (CA12), también al abrir la edición de la ficha. Además, el detalle y el alta de cita del
personal pasan al Gate `gestionar-cita` de la 026 con comparación estricta (CA13), y el aviso de duplicado de la 054
solo enlaza la ficha a quien puede verla (CA14). Sin migraciones, rutas nuevas ni dependencias.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 014 aprobada (2026-09-28). La spec no tiene sección «Diseño»: los cambios de pantalla son de contenido según el rol (filtro, estado vacío, solo lectura) con los componentes que ya existen, sin `design.md`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | CA1–CA10, CA12–CA14 cambian comportamiento y sus tests fallan hoy. Son de caracterización, comprobados rompiendo lo que protegen (`/analyze` A14, A7): el admin ve a todos y agenda para cualquiera (rotura: aplicar el filtro también al admin); el dentista lee el expediente de otro autor y la ficha lista expedientes de otros autores (rotura: filtrar por autor); el alta válida de expediente (rotura: rechazar al propio autor); «Editar» según el rol en la ficha (rotura: quitar el `@can`); el 403 del asistente en editar ficha y expediente, defensa en profundidad (rotura: quitar a la vez el `role:` de la ruta y la comprobación del controlador o el Gate; la prueba del Gate, con `Gate::forUser()->inspect` en T011). | test: ver Trazabilidad |
| P3 Autorización en servidor, por rol y recurso | ✅ | Acciones sobre un recurso concreto con Gate: los seis de `AccesoClinico` (ficha, edición, expediente, alta de expediente, agendar para un paciente) y `gestionar-cita` (026) en el detalle y el alta de cita (CA13). Listados con filtro de consulta (`aplicarAPacientes`): directorio, nueva cita e índice de expedientes, como permite ADR 0003. El alta de paciente del asistente, con validación (`Rule::in` de sus dentistas, CA8). Cada criterio de acceso se prueba por rol en el caso permitido y el denegado (RNF, RS1.e); las vistas solo ocultan controles como complemento (`@can`) (`/analyze` A11). | test: `AlcancePacientesTest`, `AlcanceExpedientesTest`, `AlcanceCitasTest` (por rol, permitido y denegado) |
| P4 Entrada validada y SQL con bindings | ✅ | `dentista_id` del asistente con `Rule::in(<sus dentistas>)`; `cita_id` con `Rule::exists('citas','id')->where('paciente_id', …)`; `dentista_id` del expediente de tipo dentista; el filtro de alcance con Query Builder (`whereIn`, `whereExists`), sin `DB::raw` con entrada. | lint: semgrep `p/php` sobre los archivos tocados; test de cada regla |
| P5 Nada sensible en el repo | ✅ | Tests con datos sintéticos en transacción. | lint: gitleaks sobre el diff |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-11-21 (`/release` 052): `composer audit --locked` y `auditar-npm.mjs` sin altas ni críticas. | lint: `composer audit --locked` y `auditar-npm.mjs` en CI y `/release` |
| P7 Auditoría de datos sensibles | ✅ | CA11: cada denegación de la spec (también la del detalle de cita, CA13) queda como `acceso_denegado` (registro central o `denegar()`) con el paciente, el expediente o la cita. CA12: `paciente_leido` (ficha y su edición) y `expediente_leido`, en modo crítico (decisión del usuario, 2026-11-22). Los listados quedan para la 022 (spec, «Cobertura de riesgos»). | test: los de CA11 y CA12 en Trazabilidad |
| P8 Correlación y logs limpios | ✅ | Ningún `Log::` nuevo; los eventos llevan el `request_id` del `Context` (020) y solo identificadores, nunca datos clínicos. | test: el evento de lectura no lleva datos del paciente · manual: diff sin `Log::` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | Los cambios de vista son de contenido con componentes y clases existentes (filtro, estado vacío, «Firmado por …», texto de ayuda); ningún `style=""` ni literal nuevo. Los `style=""` previos de esas vistas son deuda DS y no se tocan. | test: los elementos nuevos no llevan `style=""` (`AlcanceVistasTest`) · manual: `/review` contra `system.html` |
| P10 Rutas indexables descubribles | ➖ | Solo rutas del panel con sesión, no indexables (`docs/seo.md`). | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Autorizacion/AlcanceClinico.php` | Añadir `aplicarAPacientes(Builder $consulta, Usuario $usuario): Builder` (sin filtro para el admin; para el resto, `pacientes.dentista_id IN (<dentistas>) OR EXISTS (cita no cancelada con un dentista del alcance)`) y `pacienteEnAlcance(Usuario, string $pacienteId): bool` sobre la misma regla (D11) | Medio: es la regla central; tests propios |
| `app/Autorizacion/AccesoClinico.php` (nuevo) | Seis métodos para los Gates (D1): `verPaciente`, `editarPaciente`, `verExpediente`, `editarExpediente`, `crearExpediente`, `agendarParaPaciente`. Fuera del alcance → `denyAsNotFound()`; dentro sin permiso → `deny(<mensaje>)`; admin → `allow()` | Medio |
| `app/Providers/AppServiceProvider.php` | Registrar los seis Gates (`ver-paciente`, `editar-paciente`, `ver-expediente`, `editar-expediente`, `crear-expediente`, `agendar-para-paciente`) | Bajo |
| `app/Http/Controllers/PacientesController.php` | `index`: `aplicarAPacientes` en el listado, el total y las ciudades; dentistas del filtro según el rol. `create`/`store`: para el asistente, solo sus dentistas y `dentista_id` obligatorio con `Rule::in` (CA8); `dentista_id` de un dentista activo también para el admin (D15). `avisoDeDuplicados`: el enlace a la ficha solo si `ver-paciente` lo permite; el nombre, siempre (CA14). `show`: `Gate::authorize('ver-paciente')` y evento `paciente_leido` crítico (CA12). `edit`: `Gate::authorize('editar-paciente')` y evento `paciente_leido` crítico (CA12). `update`: `Gate::authorize('editar-paciente')` **antes** de validar (D17), `dentista_id` de un dentista activo (D15); si el dentista reasigna al paciente fuera de su alcance, vuelve al directorio con un aviso (D10) | Medio: flujo principal del personal; convive con lo de la 054 |
| `app/Http/Controllers/ExpedientesController.php` | `index` (dentista): solo los que firmó **y** de pacientes de su alcance (D7). `create`: para el dentista, `paciente_id` con formato de UUID o 404 sin `denegar()` (D16); `crear-expediente` con el paciente de la consulta (`denegar()` con su id si no); para el dentista, autor fijo él mismo. `store`, en este orden (D17): para el dentista, formato de UUID (D16) y `crear-expediente` con el `paciente_id` del cuerpo; después la validación; dentista con otro autor → `denegar()` 403; `cita_id` del paciente y `dentista_id` de tipo dentista → 422 (D5). `show`: `ver-expediente` y evento `expediente_leido` crítico. `update`: `editar-expediente` (autor o admin) en lugar del `abort(403)` | Medio |
| `app/Http/Controllers/CitasController.php` | `create`: lista de pacientes con `aplicarAPacientes`; un `paciente_id` preseleccionado fuera del alcance se ignora (sin dentista ni próxima visita sugerida). `store`, en este orden (D17): para el dentista y el asistente, `paciente_id` con formato de UUID o 404 (D16); `agendar-para-paciente` con ese id, y si se deniega, `denegar()` 404 con el id del paciente (D6); después la validación de siempre; después `gestionar-cita` con un objeto sintético `(object) ['dentista_id' => …]` del dentista elegido, en lugar de `autorizadoParaCita` (403, CA13); el admin sigue con la validación de siempre (422 para un id mal formado). `show`: `Gate::authorize('gestionar-cita', $cita)` en lugar de `autorizadoParaCita` (403 auditado por el registro central, CA13); `autorizadoParaCita` desaparece (D18) | Bajo: no toca `ReservaCitas` |
| `resources/views/pacientes/index.blade.php` | Filtro de dentista: oculto para el dentista, sus dentistas para el asistente; estado vacío del dentista «No tienes pacientes asignados» sin botón de alta | Bajo |
| `resources/views/pacientes/create.blade.php` | Para el asistente, «Doctor asignado» obligatorio con sus dentistas y la ayuda «Asigna uno de tus dentistas» | Bajo: vista compartida con `edit` y con la 054 |
| `resources/views/pacientes/show.blade.php` | «Editar» solo con `@can('editar-paciente')` | Bajo |
| `resources/views/expedientes/show.blade.php` | Formularios de edición y de próxima visita solo con `@can('editar-expediente')`; si no, solo lectura con «Firmado por …» | Bajo |
| `resources/views/expedientes/create.blade.php` | Para el dentista, el autor es él (texto y campo oculto) en lugar del selector | Bajo |
| `resources/views/citas/show.blade.php` | «Ver expediente del paciente» solo con `@can('ver-paciente', $cita->paciente_id)` (D14) | Bajo |
| `tests/Feature/Concerns/EscenarioAlcanceClinico.php` (nuevo) | Sobre `EscenarioEstadosCita`: P3 (de D2, con cita confirmada con D1), P4 (de D2, con cita cancelada con D1), P5 (sin dentista ni citas); expedientes E1 (P1, firmado por D1), E2 (P1, firmado por D2) y E3 (P2, firmado por D2) | Bajo |
| `tests/Feature/AlcanceClinicoReglaTest.php`, `AlcancePacientesTest.php`, `AlcanceExpedientesTest.php`, `AlcanceCitasTest.php`, `AlcanceVistasTest.php` (nuevos) | Tests de la Trazabilidad | — |
| Tests existentes que abren pacientes o expedientes fuera del alcance del actor (11 archivos que usan rutas del personal: `AltaExpedienteSinCitaTest`, `CalendarioCitas*`, `EstadosCitaRolesTest`, `HoraClinica*`, `MovilPacientesExpedientesTest`, `MovilPersonalTest`, `NavegacionInternaTest`, `PacienteSinCorreoTest`, `PacientesBotonAltaTest`) | Ajustar el actor o los datos del escenario para que el acceso sea del alcance, sin debilitar lo que comprueban, en la misma tarea que rompe cada test (D13). En `PacienteSinCorreoTest`, `test_los_enlaces_del_aviso_usan_la_ruta_del_rol` pasa a esperar el enlace solo con permiso (CA14) | Medio: se descubre al correr la suite |

## Contratos y datos
**Rutas afectadas** (sin rutas nuevas; cambian las respuestas):

| Ruta | Rol | Hoy | Tras la 014 |
|---|---|---|---|
| `GET /{dentista,asistente}/pacientes` | dentista, asistente | todos los pacientes activos | solo los del alcance (filas, total y ciudades) |
| `GET /{dentista,asistente}/pacientes/{id}` | dentista, asistente | 200 para cualquiera | 200 en alcance · 404 fuera (auditado) |
| `GET/PUT /dentista/pacientes/{id}[/edit]` | dentista | 200 para cualquiera | 200 en alcance · 404 fuera (auditado) |
| `POST /asistente/pacientes` | asistente | `dentista_id` opcional, cualquiera | obligatorio y de sus dentistas · 422 si no, sin crear nada |
| `GET /{dentista,admin}/expedientes/crear?paciente_id=` | dentista | 200 para cualquiera | 200 en alcance · 404 fuera (auditado con el paciente) |
| `POST /{dentista,admin}/expedientes` | dentista | acepta cualquier autor, cita y paciente | 404 paciente fuera · 403 otro autor (auditados) · 422 cita de otro paciente o autor que no es dentista |
| `GET /{rol}/expedientes/{id}` | dentista, asistente | 200 para cualquiera | 200 si el paciente está en alcance · 404 fuera (auditado) |
| `PUT /{dentista,admin}/expedientes/{id}` | dentista | 200 para cualquiera | 200 autor · 403 en alcance sin ser autor · 404 fuera (auditados) |
| `GET /dentista/expedientes` | dentista | los que firmó | los que firmó de pacientes de su alcance |
| `GET /{dentista,asistente}/citas/crear` | dentista, asistente | todos los pacientes | solo los del alcance; preselección ajena ignorada |
| `POST /{dentista,asistente}/citas` | dentista, asistente | cualquier paciente; 403 con un dentista ajeno | 404 si el paciente está fuera del alcance (auditado con el paciente); 403 con un dentista ajeno (Gate `gestionar-cita`, auditado); 404 con un `paciente_id` mal formado |
| `GET /{dentista,asistente}/citas/{id}` | dentista, asistente | 403 fuera (`autorizadoParaCita`, comparación no estricta) | 403 fuera con el Gate `gestionar-cita` (estricto), auditado por el registro central; el enlace a la ficha solo con permiso |
| `GET /{admin,asistente}/pacientes/crear` tras el aviso de duplicado | admin, asistente | nombre y enlace de cada coincidencia | nombre siempre; enlace solo si puede ver la ficha |
| rutas `/admin/*` | admin | — | sin cambios de acceso; sus lecturas de ficha y expediente se registran (CA12) |

**Datos:** sin migraciones. Regla de alcance de un usuario con dentistas `D…`:
`pacientes.dentista_id IN (D…) OR EXISTS (SELECT 1 FROM citas WHERE citas.paciente_id = pacientes.id AND
citas.dentista_id IN (D…) AND citas.estado <> 'cancelada')`. Admin: sin restricción. Un paciente sin dentista ni
citas solo lo ve el admin (supuesto de la spec).

**Eventos de auditoría nuevos:** `paciente_leido` (recurso `pacientes`, id de la ficha; al abrir la ficha y su edición) y `expediente_leido`
(recurso `expedientes`, id del expediente), `resultado` `exito`, en modo crítico, sin `campos` ni `detalle`.
Denegaciones: `acceso_denegado` con `denegado_404` (fuera del alcance) o `denegado` (403), recurso `pacientes` o
`expedientes` y su id, y `detalle.accion_solicitada`.

**Qué ve cada rol**

| Pantalla | Admin | Dentista | Asistente | Test |
|---|---|---|---|---|
| Directorio: filas, total y ciudades | todos | su alcance | alcance de sus dentistas | `AlcancePacientesTest::test_el_directorio_*` |
| Directorio: filtro «Doctor» | todos | oculto | sus dentistas | `AlcanceVistasTest::test_el_filtro_de_doctor_segun_el_rol` |
| Directorio vacío | botón «Registrar paciente» | «No tienes pacientes asignados», sin botón | botón «Registrar paciente» | `AlcanceVistasTest::test_el_directorio_vacio_segun_el_rol` |
| Alta de paciente: doctor | opcional, todos | — (sin ruta) | obligatorio, sus dentistas, con ayuda | `AlcancePacientesTest::test_el_asistente_asigna_uno_de_sus_dentistas` |
| Ficha: «Editar» | sí | sí (en alcance) | no | `AlcanceVistasTest::test_editar_en_la_ficha_segun_el_rol` |
| Ficha: expedientes | todos | todos los del paciente, de cualquier autor | todos, solo lectura | `AlcanceExpedientesTest::test_la_ficha_lista_expedientes_de_otros_autores` |
| Expediente: edición y próxima visita | sí | solo si lo firmó; si no, «Firmado por …» | no (solo lectura) | `AlcanceVistasTest::test_editar_el_expediente_solo_el_autor` |
| Alta de expediente: autor | selector de dentistas | él mismo, fijo | — (sin acceso) | `AlcanceVistasTest::test_el_autor_del_expediente_segun_el_rol` |
| Nueva cita: pacientes | todos | su alcance | alcance de sus dentistas | `AlcanceCitasTest::test_la_lista_de_pacientes_es_la_del_alcance` |

## Estrategia de pruebas
PHPUnit Feature con `DatabaseTransactions` contra la base de tests, con el escenario nuevo
`EscenarioAlcanceClinico` (sobre `EscenarioEstadosCita`: admin, D1, D2, asistente A1 de D1, P1 de D1, P2 de D2).

- **La regla** (`AlcanceClinicoReglaTest`): P1 y P3 están en el alcance de D1 (asignado; cita confirmada); P2 no;
  P4 no (su cita con D1 está cancelada); P5 no lo ve nadie salvo el admin; A1 hereda el alcance de D1; reasignar a
  P1 a D2 lo saca del alcance de D1 en el acto (CA10).
- **Por rol, permitido y denegado** (RNF, RS1.e): cada criterio de acceso con admin, dentista y asistente donde
  aplique. El denegado comprueba el código (404 o 403), que la respuesta no lleva datos del paciente (ni su
  nombre) y el evento `acceso_denegado` con el recurso y su id (CA11).
- **404 sin enumerar** (RNF, TM9): un id de paciente inexistente y uno fuera del alcance dan la misma respuesta
  (código y cuerpo sin datos) en la ficha, el expediente, `pacientes.update` (Gate antes de validar),
  `expedientes.store` y `citas.store`; un `paciente_id` mal formado, ausente o en array da 404 sin 500 (TM13).
- **Lecturas** (CA12): abrir una ficha, su edición o un expediente deja `paciente_leido` / `expediente_leido` con
  actor, recurso e id; con el almacén de auditoría fallando, ni la ficha ni el expediente se muestran (modo crítico).
- **Citas** (CA13): el detalle y el alta de una cita de un dentista ajeno dan 403 con dentista y asistente; el
  agendar fuera del alcance da 404 con los dos roles; el detalle de la cita cancelada de P4 no enlaza la ficha.
- **Aviso de duplicado** (CA14): el asistente ve el nombre de un duplicado fuera de su alcance sin enlace; el admin,
  con enlace.
- **Expedientes** (CA6): el autor modifica su expediente (200), además del rechazo al que no lo firmó.
- **Caracterización** (ver P2): cada test que pasa desde el principio se comprueba con la rotura de la fila P2; el
  403 del asistente al editar es defensa en profundidad: por HTTP lo da el middleware de rol (el asistente no tiene esas
  rutas y el test usa las del dentista), así que su rotura es quitar a la vez el `role:` de la ruta y la comprobación del
  controlador o el Gate; la prueba del Gate es la de T011 con `Gate::forUser()->inspect` (`/analyze` N5).
- **Tests existentes:** cada tarea que cambia un controlador ajusta en la misma tarea los tests existentes que rompe
  (actor o escenario, nunca quitando la aserción), para que cada commit tenga la suite en verde (D13).
- Pint sobre los PHP tocados, gitleaks y semgrep `p/php` sobre el diff.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: un dentista abre la ficha de un paciente ajeno cambiando el id en la dirección | A01:2025 Control de acceso | `pacientes.show` | `ver-paciente` → 404 auditado | `AlcancePacientesTest::test_la_ficha_fuera_del_alcance_es_un_404_auditado` |
| TM2 | Information disclosure: un asistente lista o abre pacientes de dentistas que no apoya | A01:2025 | `pacientes.index`, `pacientes.show` | `aplicarAPacientes` en el listado; `ver-paciente` | `test_el_directorio_del_asistente_es_el_de_sus_dentistas`, `…_fuera_del_alcance_es_un_404_auditado` |
| TM3 | Tampering: un dentista guarda la ficha de un paciente ajeno con un PUT hecho a mano | A01:2025 | `pacientes.update` | `editar-paciente` → 404 auditado, sin cambios | `test_guardar_una_ficha_fuera_del_alcance_no_cambia_nada` |
| TM4 | Information disclosure: un dentista o asistente abre un expediente de un paciente ajeno | A01:2025 | `expedientes.show` | `ver-expediente` → 404 auditado | `AlcanceExpedientesTest::test_el_expediente_fuera_del_alcance_es_un_404_auditado` |
| TM5 | Tampering: un dentista corrige el diagnóstico que firmó otro | A01:2025 | `expedientes.update` | `editar-expediente` → 403 auditado, sin cambios | `test_modificar_el_expediente_de_otro_autor_se_rechaza` |
| TM6 | Spoofing/Tampering: un dentista firma un expediente a nombre de otro, sobre una cita ajena o para un paciente ajeno | A01:2025 | `expedientes.store` | otro autor → 403 auditado; paciente fuera → 404 auditado; cita de otro paciente → 422 | `test_el_alta_de_expediente_firma_el_propio_dentista` y sus casos |
| TM7 | Elevation of privilege: un asistente da de alta un paciente para un dentista que no apoya | A01:2025 | `pacientes.store` | `Rule::in(<sus dentistas>)` obligatorio → 422 sin crear nada | `AlcancePacientesTest::test_el_asistente_asigna_uno_de_sus_dentistas` |
| TM8 | Information disclosure/Tampering: el personal agenda para un paciente ajeno o ve su próxima visita con `?paciente_id=` | A01:2025 | `citas.create`, `citas.store` | lista filtrada; preselección ajena ignorada; `agendar-para-paciente` → 404 auditado | `AlcanceCitasTest::*` |
| TM9 | Information disclosure (enumeración): la respuesta distingue un paciente que existe fuera del alcance de uno que no existe | A01:2025 | todos los Gates de lectura | `denyAsNotFound()` igual que un 404 real, sin datos | `test_un_paciente_ajeno_y_uno_inexistente_responden_igual` |
| TM10 | Repudiation: una lectura de datos clínicos queda sin rastro porque la auditoría falla | A09:2025 Registro y alertas | `pacientes.show`, `expedientes.show` | evento crítico: sin registro no hay lectura | `test_sin_auditoria_no_se_muestra_la_ficha` |
| TM11 | Elevation of privilege: el dentista anterior conserva el acceso tras una reasignación | A01:2025 | `AlcanceClinico` | el alcance se evalúa en cada petición, sin caché | `AlcanceClinicoReglaTest::test_reasignar_saca_del_alcance_en_el_acto` |
| TM12 | Information disclosure: el total o las ciudades del directorio delatan pacientes fuera del alcance | A01:2025 | `pacientes.index` | el filtro se aplica también al total y a las ciudades | `test_el_total_y_las_ciudades_son_del_alcance` |
| TM13 | Denial of service / Information disclosure: un `paciente_id` mal formado, ausente o en array provoca un 500 en el alta de expediente o de cita | A05:2025 Inyección / validación | `expedientes.create`, `expedientes.store`, `citas.store` | formato de UUID comprobado antes del Gate y de `denegar()` → 404 (D16) | `test_el_alta_de_expediente_con_paciente_mal_formado_es_un_404`, su variante en `citas.store` |
| TM14 | Information disclosure: el aviso de duplicado enlaza la ficha de un paciente fuera del alcance | A01:2025 | `pacientes.create` (aviso) | enlace solo con `ver-paciente` (CA14); el nombre, excepción aceptada | `test_el_aviso_de_duplicado_solo_enlaza_lo_que_se_puede_ver` |
| TM15 | Information disclosure: el personal abre el detalle de una cita de un dentista que no apoya, con una comparación no estricta | A01:2025 | `citas.show`, `citas.store` | Gate `gestionar-cita` (026) con `in_array` estricto → 403 auditado (CA13) | `AlcanceCitasTest::test_el_detalle_de_una_cita_ajena_se_deniega_por_gate`, `…_agendar_con_un_dentista_ajeno_se_deniega` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: el dentista ve solo su alcance; vacío si no tiene | `AlcanceClinico`, `PacientesController::index`, `pacientes/index` | `AlcancePacientesTest::test_el_directorio_del_dentista_es_su_alcance`, `…_el_total_y_las_ciudades_son_del_alcance`; `AlcanceVistasTest::test_el_directorio_vacio_segun_el_rol` |
| CA2: el asistente ve el alcance de sus dentistas; el admin todo | ídem | `AlcancePacientesTest::test_el_directorio_del_asistente_es_el_de_sus_dentistas`, `…_el_admin_ve_a_todos`; `AlcanceVistasTest::test_el_filtro_de_doctor_segun_el_rol` |
| CA3 (abuso): ficha, edición y guardado fuera del alcance → denegado sin datos | `AccesoClinico::verPaciente`, `editarPaciente`; `PacientesController::show/edit/update` | `AlcancePacientesTest::test_la_ficha_fuera_del_alcance_es_un_404_auditado`, `…_editar_una_ficha_fuera_del_alcance_es_un_404`, `…_guardar_una_ficha_fuera_del_alcance_no_cambia_nada`, `…_un_paciente_ajeno_y_uno_inexistente_responden_igual`; `…_el_asistente_no_edita_fichas` (caracterización) |
| CA4: el dentista lee los expedientes de su alcance de cualquier autor; el asistente en solo lectura | `AccesoClinico::verExpediente`; `ExpedientesController::show`; `expedientes/show` | `AlcanceExpedientesTest::test_el_dentista_lee_el_expediente_de_otro_autor`, `…_la_ficha_lista_expedientes_de_otros_autores`, `…_el_asistente_lee_en_solo_lectura` |
| CA5 (abuso): expediente fuera del alcance → denegado sin contenido | `verExpediente` | `AlcanceExpedientesTest::test_el_expediente_fuera_del_alcance_es_un_404_auditado` |
| CA6 (abuso): modificar el expediente de otro autor → rechazado; el autor y el admin sí | `AccesoClinico::editarExpediente`; `ExpedientesController::update` | `test_modificar_el_expediente_de_otro_autor_se_rechaza`, `…_el_autor_modifica_su_expediente`, `…_el_admin_modifica_cualquier_expediente`, `…_el_asistente_no_modifica_expedientes` (caracterización) |
| CA7 (abuso): alta de expediente con otro autor, cita ajena o paciente fuera → rechazado; válido → firmado por él | `AccesoClinico::crearExpediente`; `ExpedientesController::create/store`; `expedientes/create` | `test_el_alta_de_expediente_firma_el_propio_dentista`, `…_con_otro_autor_se_rechaza`, `…_con_una_cita_de_otro_paciente_se_rechaza`, `…_para_un_paciente_fuera_del_alcance_es_un_404`, `…_abrir_el_alta_fuera_del_alcance_es_un_404` |
| CA8: el asistente asigna uno de sus dentistas; si no, rechazo sin crear nada | `PacientesController::create/store`; `pacientes/create` | `AlcancePacientesTest::test_el_asistente_asigna_uno_de_sus_dentistas` (sin doctor, doctor ajeno, doctor suyo) |
| CA9 (abuso): agendar solo pacientes del alcance; próxima visita solo de esos; paciente ajeno → rechazado; sin dentista o de otro dentista, solo el admin | `CitasController::create/store`; `AccesoClinico::agendarParaPaciente` | `AlcanceCitasTest::test_la_lista_de_pacientes_es_la_del_alcance`, `…_la_preseleccion_ajena_se_ignora`, `…_agendar_para_un_paciente_ajeno_es_un_404_auditado` (dentista y asistente), `…_un_paciente_sin_dentista_solo_lo_agenda_el_admin`, `…_el_admin_agenda_para_cualquiera`, `…_un_paciente_ajeno_y_uno_inexistente_responden_igual_al_agendar` |
| CA10: tras reasignar, el dentista anterior sin citas pierde el acceso y el nuevo lo gana | `AlcanceClinico` | `AlcanceClinicoReglaTest::test_reasignar_saca_del_alcance_en_el_acto`; `AlcancePacientesTest::test_el_dentista_que_reasigna_fuera_vuelve_al_directorio`, `…_asignar_un_asistente_como_doctor_se_rechaza` (D15) |
| CA11: cada denegación (CA3, CA5, CA6, CA7, CA9, CA13) queda registrada con quién, acción, paciente o expediente, resultado y fecha | registro central (020); `Auditoria::denegar()` en `ExpedientesController::create/store` y `CitasController::store` | aserción del evento en cada test de denegación de CA3, CA5, CA6, CA7 y CA9 |
| CA12: cada lectura permitida de ficha (también su edición) o expediente queda registrada; sin registro no se muestra | `PacientesController::show/edit`, `ExpedientesController::show` | `AlcancePacientesTest::test_leer_una_ficha_queda_registrado`, `…_abrir_la_edicion_de_la_ficha_queda_registrado`, `…_sin_auditoria_no_se_muestra_la_ficha`; `AlcanceExpedientesTest::test_leer_un_expediente_queda_registrado`, `…_sin_auditoria_no_se_muestra_el_expediente` |
| CA13 (abuso): detalle y alta de cita de un dentista ajeno → denegado; dentro del alcance, permitido | `CitasController::show/store` (Gate `gestionar-cita`) | `AlcanceCitasTest::test_el_detalle_de_una_cita_ajena_se_deniega_por_gate` (dentista y asistente, auditado), `…_agendar_con_un_dentista_ajeno_se_deniega`, `…_el_detalle_de_una_cita_propia_se_ve` |
| CA14: el aviso de duplicado enlaza la ficha solo si se puede ver | `PacientesController::avisoDeDuplicados` | `AlcancePacientesTest::test_el_aviso_de_duplicado_solo_enlaza_lo_que_se_puede_ver`; `PacienteSinCorreoTest::test_los_enlaces_del_aviso_usan_la_ruta_del_rol` (ajustado) |
| RNF: cada criterio por rol, permitido y denegado | todos | los de CA1–CA10, con admin, dentista y asistente donde aplique |
| RNF: denegar no expone datos ni confirma la existencia | `denyAsNotFound()`; Gate antes de validar (D17) | `test_un_paciente_ajeno_y_uno_inexistente_responden_igual` (ficha y `update`); `AlcanceExpedientesTest::test_un_expediente_ajeno_y_uno_inexistente_responden_igual`; la variante de `citas.store`; los 404 comprueban que no sale el nombre del paciente |
| TM1, TM2, TM3, TM9, TM12 | ver CA1–CA3 | ver CA1–CA3 |
| TM4 | ver CA5 | ver CA5 |
| TM5 | ver CA6 | ver CA6 |
| TM6 | ver CA7 | ver CA7 |
| TM7 | ver CA8 | ver CA8 |
| TM8 | ver CA9 | ver CA9 |
| TM10 | ver CA12 | `…_sin_auditoria_no_se_muestra_la_ficha` |
| TM11 | ver CA10 | ver CA10 |
| TM13 | `ExpedientesController::create/store`, `CitasController::store` | `test_el_alta_de_expediente_con_paciente_mal_formado_es_un_404` y su variante de cita |
| TM14 | ver CA14 | ver CA14 |
| TM15 | ver CA13 | ver CA13 |
| P9: vistas sin estilos en línea nuevos | vistas tocadas | `AlcanceVistasTest::test_los_elementos_nuevos_no_llevan_estilos_en_linea` · verificación manual: directorio, ficha y expediente a 390 y 1440 px con un dentista y un asistente, contra la pantalla actual |

## Observabilidad
- Logs nuevos: ninguno.
- Eventos de auditoría (`docs/observability.md`, «Lectura de datos sensibles»):
  - `paciente_leido` (en `show` y `edit` de la ficha) y `expediente_leido` (en `show`), en modo crítico, tras el Gate
    (CA12). Los listados no dejan evento: van a la spec 022.
  - `acceso_denegado` de cada denegación de la spec: el registro central (020) para las rutas con `{id}`;
    `Auditoria::denegar()` con el paciente en el alta de expediente y de cita (CA11).
- Métricas y alertas: sin cambios. Los 404 y 403 nuevos no cuentan como fallos de acceso del canal `seguridad`.
- Cómo se verifica tras el deploy: con una cuenta de dentista de prueba, abrir la ficha de un paciente de otro
  dentista da la página 404 y aparece en `/admin/auditoria` como `acceso_denegado`; abrir uno propio deja
  `paciente_leido`.

## Descubribilidad
No aplica: la spec solo cambia rutas del panel con sesión, que no son indexables (`docs/seo.md`).

## Rollout
- Feature flag: no (D8). Es un control de seguridad: un interruptor permitiría desactivarlo y dejar los datos
  clínicos expuestos. Volver atrás es el rollback.
- Orden de despliegue: `deploy/desplegar.sh vX.Y.Z` sin migraciones.
- Compatibilidad: total en datos; el personal deja de ver pacientes fuera de su alcance desde el despliegue. Los
  enlaces guardados a fichas ajenas dan 404.
- Rollback: `bash deploy/volver.sh <tag anterior>`, sin la copia (no hay cambios de esquema). Los eventos de
  lectura ya escritos se quedan en la auditoría.
- Métricas a vigilar: 5xx en `/dentista/*` y `/asistente/*` (aviso `dentissa-vigilar`), el volumen de
  `acceso_denegado` en `/admin/auditoria` (un pico indica un acceso legítimo que la regla no contempla) y que la
  comprobación de Observabilidad sale bien.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Pregunta | Opciones | Decisión |
|---|---|---|---|
| D1 | ¿Mecanismo de autorización? | Policies (lo que apuntaba la spec en 2026-09) · Gates sobre clases de `app/Autorizacion/` | **Gates sobre `AccesoClinico`**, la convención del proyecto desde ADR 0003 (no hay modelos Eloquent de paciente ni de expediente); `AlcanceClinico` sigue siendo la regla, que `CambiarEstadoCita` ya usa |
| D2 | ¿Qué responde el acceso fuera del alcance? | 404 · 403 | **404** (`denyAsNotFound()`), que no confirma que el paciente exista (RNF de la spec); dentro del alcance sin permiso, 403. La 006 CA6 («403») queda reemplazada por 014 CA3 al liberar. Las citas conservan su 403 de la 026 (decisión del usuario, 2026-11-22) |
| D3 | Si la auditoría falla al leer una ficha o un expediente | mostrar igual · no mostrar | **No mostrar** (modo crítico), como las altas de pacientes y la galería (decisión del usuario, 2026-11-22) |
| D4 | ¿Cómo se auditan las denegaciones? | todo explícito · registro central · mixto | **Mixto**: el registro central de la 020 ya audita los Gates en rutas con `{id}` (recurso y id del parámetro); donde el paciente va en el cuerpo o en la consulta (alta de expediente y de cita), `Auditoria::denegar()` con el id del paciente, para que CA11 sepa sobre qué paciente fue |
| D5 | Alta de expediente con datos incoherentes | todo 422 · todo denegación | **Otro autor** → 403 auditado (es suplantar la firma); **paciente fuera** → 404 auditado; **cita de otro paciente** o **autor que no es dentista** → 422 (integridad de datos, no acceso) |
| D6 | Agendar para un paciente fuera del alcance | 422 · 404 | **404 auditado** (`denegar()` con el paciente), igual que el resto de accesos fuera del alcance; la preselección ajena por la dirección se ignora sin aviso |
| D7 | ¿Qué lista el índice de expedientes del dentista? | los que firmó · los que firmó del alcance | **Los que firmó de pacientes de su alcance**: el índice no muestra un diagnóstico que el detalle denegaría |
| D8 | ¿Feature flag? | sí · no | **No**: un control de seguridad no se apaga con un interruptor; rollback con `volver.sh` |
| D9 | ¿Partir la spec (12 criterios)? | partir en pacientes, expedientes y citas · una sola | **Una sola** (decisión del usuario, 2026-11-21) |
| D10 | El dentista reasigna al paciente a otro dentista | prohibir · permitir y perder el acceso | **Permitir**; si el paciente sale de su alcance, vuelve al directorio con «El paciente quedó asignado a otro dentista.» |
| D11 | Regla de alcance | solo asignado · asignado o con cita | **Asignado o con cita no cancelada**, pasada o futura (decisión del usuario, 2026-09-28; supuestos de la spec); se evalúa en cada petición, sin caché (CA10) |
| D12 | Lecturas del admin | registrarlas · no | **Registrarlas**: CA12 dice «una lectura permitida» sin excluir roles |
| D13 | Tests existentes que acceden fuera del alcance | rehacerlos · ajustar actor o escenario | **Ajustar** el actor o los datos para que el acceso sea del alcance, sin quitar aserciones; cada ajuste se anota en su tarea |

| D14 | Calendario, detalle de cita y paneles (alcance por cita de la 026, canceladas incluidas) | filtrarlos con la regla de la 014 · conservar su alcance | **Conservan el alcance por cita de la 026**; el enlace del detalle a la ficha solo aparece con `ver-paciente`, para no llevar a un 404 auditado (`/analyze` A10) |
| D15 | `dentista_id` de la ficha | `exists:personal,id` · dentista activo | **Dentista activo** (`Rule::exists('personal','id')->where('tipo','dentista')` y cuenta activa) en el alta y en la edición: con un asistente, el paciente quedaría fuera del alcance de todos (`/analyze` A12) |
| D16 | `paciente_id` de la consulta o del cuerpo en las altas | al Gate tal cual · formato primero | **Formato de UUID primero, para el dentista y el asistente**: si no lo es (texto, ausente o en array), 404 sin llamar al Gate ni a `denegar()` (que no admite ids mal formados). El admin, que no tiene alcance, conserva la validación de siempre (422), que fija `AltaCitasValidacionTest::test_los_identificadores_enviados_como_array_se_rechazan_sin_error_500` (`/analyze` A8, N1) |
| D17 | Orden en `pacientes.update` y en las altas | validar y después autorizar · autorizar y después validar | **Autorizar sobre el paciente antes de validar**, para que un paciente fuera del alcance y uno inexistente respondan igual (404) en `pacientes.update`, `expedientes.store` y `citas.store`; el Gate de citas sobre el dentista elegido va después de validar, porque el dentista solo se conoce validado (`/analyze` A9, N2) |
| D18 | Comprobación de alcance de citas en `show` y `store` | `autorizadoParaCita` · Gate `gestionar-cita` | **Gate `gestionar-cita`** de la 026, cuya comparación ya es estricta (`CambiarEstadoCita::alcance`); en el alta recibe un objeto sintético con el dentista elegido, porque aún no hay cita; desaparece `autorizadoParaCita` (026/R15; decisión del usuario, 2026-11-22, `/analyze` A2) |
| D19 | Recurso del 403 por «otro autor» en el alta de expediente | ninguno · el paciente | **`pacientes` con el id del paciente**: el expediente aún no existe (`/analyze` A19) |
| D20 | Aviso de duplicado con un paciente fuera del alcance | ocultarlo · nombre sin enlace | **Nombre sin enlace** (excepción decidida en la 054; CA14) |

Ninguna decisión es arquitectónica: sin ADR (D1 y D18 siguen ADR 0003).

## Impacto en arquitectura
- `docs/architecture.md` (T090): la fila de `app/Autorizacion/` con `AccesoClinico` y los seis Gates, y la regla
  de alcance de `AlcanceClinico` sobre pacientes.
- `docs/observability.md` (T091): los eventos `paciente_leido` y `expediente_leido` en la tabla de eventos.
- En `/release` (T098): `docs/security.md` (RS1.a–RS1.e mitigadas, con el texto de RS1.a y RS1.b pasado de «Policy» a
  Gates); `docs/observability.md`: OB1.d **sigue pendiente**, con la nota de que la ficha, su edición y el expediente
  quedan cubiertos por la 014 y los listados van a la 022 (`/analyze` A1); las specs 003, 006, 007 y 054 anotan en su
  Historial los criterios que la 014 reemplaza o cambia (006 CA1, CA4, CA6, CA7; 007 CA1, CA3, CA4, CA6, CA7, CA8;
  003 CA10; el aviso de duplicado de la 054); el roadmap, objetivo 2, y las excepciones P3 de la 026, la 028 y la
  050 resueltas.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| Personal que hoy ve pacientes que necesita deja de verlos (p. ej. un paciente sin dentista asignado) | La regla incluye las citas; el admin sigue viendo todo y puede asignar o agendar. Se vigila el volumen de `acceso_denegado` tras el deploy |
| El filtro con `whereExists` sobre `citas` es lento con muchos datos | Volumen de un MVP; `citas` tiene `idx_citas_paciente` e `idx_citas_dentista` (volcado) y `pacientes.dentista_id` el índice de su clave foránea |
| La auditoría crítica bloquea la consulta de fichas si su almacén falla | Decisión del usuario (D3); el fallo se ve en el canal `seguridad` y en la página de error genérico |
| Muchos tests existentes asumen acceso a todo | D13: se ajustan sin debilitarlos en la tarea que los rompe, con la suite en verde en cada commit |
| La vista `pacientes/create` es compartida con `edit` y con la 054 | Los cambios del asistente van tras una condición de rol; los tests de la 054 siguen en verde |
