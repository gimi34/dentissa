---
spec: 014-autorizacion-alcance-clinico
status: approved   # draft | approved | blocked
created: 2026-09-28
---

# Plan · 014 Autorización por alcance clínico

## Enfoque técnico
Centralizar la regla de alcance clínico en una sola clase, `App\Autorizacion\AlcanceClinico`, que
responde dos preguntas: qué pacientes están en el alcance de un usuario (para filtrar consultas) y
si un recurso concreto lo está (para decidir un acceso). Sobre ella se registran Gates en
`AppServiceProvider` (`ver-paciente`, `editar-paciente`, `ver-expediente`, `editar-expediente`,
`crear-expediente`, `agendar-para-paciente`) que devuelven `Response::denyAsNotFound()` cuando el
recurso está fuera del alcance (404, no se confirma que exista) y `Response::deny()` cuando se ve
pero no se puede modificar (403). Los tres controladores afectados sustituyen sus comprobaciones
sueltas por `Gate::authorize(...)` y aplican el filtro de alcance a los listados. No hay cambios de
esquema ni modelos nuevos; la spec 013 podrá migrar estos Gates a Policies cuando existan los modelos.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 014 `approved` el 2026-09-28. |
| P2 Test que falla antes y pasa después | ✅ | Cada CA y cada TM tiene test Feature en Trazabilidad; todos fallan hoy (el código actual no filtra). |
| P3 Autorización en servidor, por rol y recurso | ✅ | Gates sobre `AlcanceClinico` en cada acción; tests por rol en caso permitido y denegado (RS1.e). Las vistas solo ocultan controles como complemento. |
| P4 Entrada validada y SQL con bindings | ✅ | `dentista_id` del asistente con `Rule::in(...)` de sus dentistas; `cita_id` con `Rule::exists(...)->where('paciente_id', …)`; filtros de alcance con Query Builder y `whereExists`, sin `DB::raw` con entrada. |
| P5 Nada sensible en el repo | ✅ | Los tests crean datos sintéticos en transacción; no se añaden secretos ni volcados. |
| P6 Sin vulnerabilidades críticas/altas en producción | ✅ | No añade dependencias. Las vulnerabilidades previas (RS16) se atienden en una spec propia antes de cualquier release (decisión del usuario, 2026-09-28). |
| P7 Auditoría de datos sensibles | ✅ | CA11 y CA12 emiten eventos con `App\Auditoria\Auditoria` (spec 020); las lecturas en modo crítico (fallo cerrado). Prerrequisito: spec 020 implementada antes (decisión del usuario, 2026-09-28). |
| P8 Correlación y logs limpios | ➖ | La spec no añade logs. La correlación (`X-Request-Id`) la aporta la spec 020; los eventos de CA11/CA12 la heredan del `Context`. |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Autorizacion/AlcanceClinico.php` (nuevo) | `personalId(Usuario)`, `dentistasDelAlcance(Usuario): ?array` (null = admin sin restricción; dentista → [propio]; asistente → asignados), `aplicarAPacientes(Builder $q, Usuario)` (paciente asignado a un dentista del alcance **o** con cita no cancelada con él, vía `whereExists`), `pacienteEnAlcance(Usuario, string $pacienteId): bool`, `esAutorDe(Usuario, object $expediente): bool` | Medio: es la regla central; cubierta por tests unitarios con BD |
| `app/Providers/AppServiceProvider.php` | Registrar los 6 Gates y un `Gate::after` que, para esas habilidades, llama a `Auditoria::denegacion(...)` con resultado `denegado_404` o `denegado_403` (CA11); la marca en el `Context` evita que `withExceptions` (spec 020) duplique el 403 | Bajo |
| `app/Http/Controllers/PacientesController.php` | `index`: filtro de alcance en listado, total y ciudades; desplegable de dentistas limitado al alcance. `create/store`: para asistente, `dentista_id` obligatorio y dentro de sus dentistas (CA8). `show`: `Gate::authorize('ver-paciente')` + evento de lectura (CA12); la lista de expedientes muestra todos los del paciente. `edit/update`: `Gate::authorize('editar-paciente')` (sustituye el `abort(403)` del asistente); si el dentista reasigna el paciente fuera de su alcance, redirige al listado | Medio: cambia el flujo principal del personal |
| `app/Http/Controllers/ExpedientesController.php` | `create`: `Gate::authorize('crear-expediente', $pacienteId)`; el dentista solo puede elegirse a sí mismo. `store`: mismo Gate; si el rol es dentista y `dentista_id` ≠ el propio → error de validación; `dentista_id` debe ser de tipo dentista; `cita_id` debe pertenecer al paciente (CA7). `show`: `Gate::authorize('ver-expediente')` + evento de lectura (CA12). `update`: `Gate::authorize('editar-expediente')` (autor o admin). `index` sin cambios | Medio |
| `app/Http/Controllers/CitasController.php` | `create`: lista de pacientes filtrada con `aplicarAPacientes`; si el `paciente_id` preseleccionado está fuera del alcance, se ignora (sin dentista ni próxima visita sugerida). `store`: `Gate::authorize('agendar-para-paciente', $pacienteId)` antes de la regla de agenda (CA9) | Bajo: no toca la regla de agenda |
| `resources/views/pacientes/index.blade.php` | Filtro de dentista según rol; botón del estado vacío con la ruta del rol y solo para admin/asistente | Bajo |
| `resources/views/pacientes/create.blade.php` | Para asistente: selector de dentista obligatorio con sus dentistas y texto de ayuda | Bajo |
| `resources/views/expedientes/show.blade.php` | Formularios de edición y de próxima visita solo si `@can('editar-expediente')`; si no, vista de solo lectura con "Firmado por …" | Bajo |
| `resources/views/expedientes/create.blade.php` | Para dentista, autor fijo (él mismo) en vez de selector | Bajo |
| `tests/Feature/Concerns/EscenarioAlcanceClinico.php` (nuevo) | Fixture: 2 dentistas con horario, 1 asistente asignado a D1, 4 pacientes (P1 de D1; P2 de D2; P3 de D2 con cita confirmada con D1; P4 de D2 con cita cancelada con D1), expedientes E1 (P1, D1) y E3 (P3, D2) | Bajo |
| `tests/Feature/AlcanceClinico/*Test.php` (nuevos) | Tests de la Trazabilidad | — |

## Contratos y datos
**Rutas afectadas** (sin rutas nuevas; cambian las respuestas):

| Ruta | Rol | Hoy | Tras la 014 |
|---|---|---|---|
| `GET /{dentista,asistente}/pacientes` | dentista, asistente | todos los pacientes activos | solo los del alcance |
| `GET /{rol}/pacientes/{id}` | dentista, asistente | 200 para cualquiera | 200 en alcance · 404 fuera |
| `GET/PUT /dentista/pacientes/{id}[/edit]` | dentista | 200 para cualquiera | 200 en alcance · 404 fuera |
| `GET/PUT /asistente/…/edit` | asistente | 403 | 403 (sin cambios) |
| `POST /asistente/pacientes` | asistente | `dentista_id` opcional, cualquiera | obligatorio y de sus dentistas · 422 si no |
| `GET /{rol}/expedientes/crear?paciente_id=` | dentista | 200 para cualquiera | 200 en alcance · 404 fuera |
| `POST /{rol}/expedientes` | dentista | acepta cualquier autor, cita y paciente | 404 paciente fuera · 422 autor ajeno, cita de otro paciente o autor que no es dentista |
| `GET /{rol}/expedientes/{id}` | dentista, asistente | 200 para cualquiera | 200 si el paciente está en alcance · 404 fuera |
| `PUT /{rol}/expedientes/{id}` | dentista | 200 para cualquiera | 200 autor · 403 en alcance sin ser autor · 404 fuera |
| `GET /{rol}/citas/crear` | dentista, asistente | todos los pacientes | solo los del alcance; preselección ajena ignorada |
| `POST /{rol}/citas` | dentista, asistente | cualquier paciente | 404 paciente fuera del alcance |
| rutas `/admin/*` | admin | — | sin cambios |

**Datos:** sin migraciones de esquema. Consulta de alcance (dentista con `personal.id = D`):
`pacientes.dentista_id IN (D…) OR EXISTS (SELECT 1 FROM citas WHERE citas.paciente_id = pacientes.id AND citas.dentista_id IN (D…) AND citas.estado <> 'cancelada')`.

**Qué ve cada rol**

| Pantalla | Admin | Dentista | Asistente | Test |
|---|---|---|---|---|
| Directorio de pacientes: filas y contador | todos | solo su alcance | alcance de sus dentistas | `AlcancePacientesTest::test_el_directorio_*` |
| Directorio: filtro "dentista" | todos los dentistas | oculto (solo él) | solo sus dentistas | `test_el_filtro_de_dentista_muestra_solo_el_alcance` |
| Directorio: estado vacío | botón "Registrar paciente" (admin) | sin botón; texto "No tienes pacientes asignados" | botón "Registrar paciente" (ruta de asistente) | `test_el_estado_vacio_depende_del_rol` |
| Alta de paciente: dentista | opcional, todos | — (sin acceso) | obligatorio, sus dentistas, ayuda "Asigna uno de tus dentistas" | `AltaPacienteAsistenteTest::*` |
| Ficha del paciente: botón "Editar" | sí | sí (en alcance) | no | `test_la_ficha_muestra_editar_segun_rol` |
| Ficha: lista de expedientes | todos | todos los del paciente (de cualquier autor) | todos, enlace de solo lectura | `AlcanceExpedientesTest::test_la_ficha_lista_expedientes_de_otros_autores` |
| Expediente: formulario de edición y próxima visita | sí | solo si es autor; si no, "Firmado por …" en solo lectura | no (solo lectura) | `test_el_formulario_de_edicion_solo_para_el_autor` |
| Alta de expediente: autor | selector de dentistas | fijo, él mismo | — (sin acceso) | `test_el_dentista_no_puede_elegir_otro_autor` |
| Alta de cita: selector de paciente | todos | su alcance | alcance de sus dentistas | `AlcanceAltaCitasTest::test_el_selector_*` |

Revisar con la skill `design` antes de `/implement` (textos de estado vacío y de solo lectura).

**Contrato entre capas:** formularios Blade ↔ validación del controlador; cambia el campo
`dentista_id` del alta de pacientes (obligatorio para asistente) y del alta de expedientes (fijo
para dentista).

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, base `dentissa_test`):** una clase por área, con el
  fixture `EscenarioAlcanceClinico`; cada acción se prueba como admin, dentista en alcance,
  dentista fuera de alcance y asistente (permitido/denegado), asertando código de respuesta y que
  el HTML no contiene datos del paciente ajeno (nombre y alergias del fixture).
- **Unit con BD:** `AlcanceClinicoTest` para la regla (asignado, cita no cancelada, cita cancelada,
  sin relación, admin, asistente, reasignación).
- **Auditoría:** tests que asertan el evento en la tabla `auditoria` de la spec 020 (acción,
  actor, recurso, resultado); las lecturas se prueban también con `AlmacenAuditoriaQueFalla`
  (spec 020) para comprobar el fallo cerrado.
- **Base de pruebas:** el arnés con migraciones lo aporta la spec 020 (crea `asistente_dentista`,
  `pacientes.dentista_id` y `auditoria` en `dentissa_test`).
- Comandos: `bash database/testing/bootstrap-test-db.sh` y `docker compose exec app php artisan test`.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: IDOR sobre la ficha cambiando el id | A01:2025 | `PacientesController@show/edit` | Gate `ver-paciente`/`editar-paciente` con `denyAsNotFound` | `AlcancePacientesTest::test_un_dentista_no_ve_la_ficha_de_un_paciente_ajeno` |
| TM2 | Tampering: modificar la ficha de un paciente ajeno | A01:2025 | `PacientesController@update` | Gate `editar-paciente` | `test_un_dentista_no_actualiza_un_paciente_ajeno` |
| TM3 | Tampering: editar el expediente de otro dentista | A01:2025 | `ExpedientesController@update` | Gate `editar-expediente` (autor o admin) | `AlcanceExpedientesTest::test_un_dentista_no_modifica_el_expediente_de_otro` |
| TM4 | Spoofing/Repudiation: firmar un expediente como otro dentista | A01:2025 | `ExpedientesController@store` | autor = dentista autenticado; `dentista_id` de tipo dentista | `test_el_dentista_no_puede_firmar_como_otro` |
| TM5 | Tampering: expediente ligado a una cita de otro paciente | A01:2025 | `ExpedientesController@store` | `Rule::exists('citas')->where('paciente_id', …)` | `test_la_cita_debe_ser_del_paciente` |
| TM6 | Information disclosure: listados con pacientes fuera de alcance | A01:2025 | `PacientesController@index`, `CitasController@create` | `aplicarAPacientes` antes de los filtros del usuario | `test_el_directorio_del_dentista_solo_muestra_su_alcance`, `AlcanceAltaCitasTest::test_el_selector_de_pacientes_respeta_el_alcance` |
| TM7 | Information disclosure: filtro `dentista_id` en la URL para listar pacientes de otro | A01:2025 | `PacientesController@index` | el filtro se aplica dentro del alcance | `test_el_filtro_por_otro_dentista_no_amplia_el_alcance` |
| TM8 | Information disclosure: próxima visita y dentista de un paciente ajeno vía `?paciente_id=` | A01:2025 | `CitasController@create` | preselección ignorada fuera del alcance | `test_la_preseleccion_de_un_paciente_ajeno_se_ignora` |
| TM9 | Information disclosure: nombre y citas de un paciente ajeno en el alta de expediente | A01:2025 | `ExpedientesController@create` | Gate `crear-expediente` | `test_el_alta_de_expediente_para_un_paciente_ajeno_responde_404` |
| TM10 | Elevation: agendar para un paciente fuera del alcance | A01:2025 | `CitasController@store` | Gate `agendar-para-paciente` | `test_agendar_para_un_paciente_ajeno_responde_404` |
| TM11 | Elevation: asistente da de alta un paciente con un dentista ajeno o sin dentista | A01:2025 | `PacientesController@store` | `required` + `Rule::in(dentistas del asistente)` | `AltaPacienteAsistenteTest::test_el_asistente_no_asigna_un_dentista_ajeno` |
| TM12 | Information disclosure: distinguir "existe pero es ajeno" de "no existe" | A01:2025 | todos los Gates | `denyAsNotFound` fuera de alcance | `test_un_paciente_ajeno_y_uno_inexistente_responden_igual` |
| TM13 | Elevation: acceso conservado tras reasignar el paciente | A01:2025 | `AlcanceClinico` | alcance calculado en cada petición | `test_tras_reasignar_el_dentista_anterior_pierde_el_acceso` |
| TM14 | Repudiation: intentos de acceso sin rastro | A09:2025 | `Gate::after` | evento de acceso denegado | `AuditoriaAlcanceTest::test_un_acceso_denegado_queda_registrado` |

Casos de abuso de la spec → amenazas: CA3 → TM1, TM2, TM12; CA5 → TM1, TM12; CA6 → TM3; CA7 → TM4, TM5, TM9; CA9 → TM6, TM8, TM10; alcance del asistente (CA2, CA8) → TM6, TM7, TM11; CA10 → TM13.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: directorio del dentista solo con su alcance; vacío si no tiene | `AlcanceClinico::aplicarAPacientes`, `PacientesController@index`, `pacientes/index` | Feature `AlcancePacientesTest::test_el_directorio_del_dentista_solo_muestra_su_alcance`, `test_el_estado_vacio_depende_del_rol` |
| CA2: directorio del asistente con el alcance de sus dentistas; admin ve todos | `PacientesController@index`, `pacientes/index` | Feature `test_el_directorio_del_asistente_muestra_el_alcance_de_sus_dentistas`, `test_el_admin_ve_todos_los_pacientes`, `test_el_filtro_de_dentista_muestra_solo_el_alcance` |
| CA3 (abuso): ficha ajena denegada sin revelar datos | Gates `ver-paciente`, `editar-paciente`; `PacientesController@show/edit/update` | Feature `test_un_dentista_no_ve_la_ficha_de_un_paciente_ajeno`, `test_un_asistente_no_ve_la_ficha_de_un_paciente_ajeno`, `test_un_dentista_no_actualiza_un_paciente_ajeno`, `test_un_paciente_ajeno_y_uno_inexistente_responden_igual` |
| CA4: dentista lee expedientes de otros autores de su paciente; asistente en solo lectura | Gate `ver-expediente`, `ExpedientesController@show`, `expedientes/show` | Feature `AlcanceExpedientesTest::test_el_dentista_lee_el_expediente_de_otro_autor_de_su_paciente`, `test_la_ficha_lista_expedientes_de_otros_autores`, `test_el_asistente_ve_el_expediente_sin_formulario` |
| CA5 (abuso): expediente de paciente ajeno denegado | Gate `ver-expediente` | Feature `test_un_dentista_no_ve_un_expediente_de_paciente_ajeno`, `test_un_asistente_no_ve_un_expediente_de_paciente_ajeno` |
| CA6 (abuso): no se modifica el expediente de otro; admin sí | Gate `editar-expediente`, `ExpedientesController@update`, `expedientes/show` | Feature `test_un_dentista_no_modifica_el_expediente_de_otro`, `test_el_admin_modifica_cualquier_expediente`, `test_el_formulario_de_edicion_solo_para_el_autor` |
| CA7 (abuso): alta de expediente con autor ajeno, cita de otro paciente o paciente fuera de alcance rechazada | Gate `crear-expediente`, validación en `ExpedientesController@store`, `expedientes/create` | Feature `test_el_dentista_no_puede_firmar_como_otro`, `test_la_cita_debe_ser_del_paciente`, `test_el_alta_de_expediente_para_un_paciente_ajeno_responde_404`, `test_el_dentista_no_puede_elegir_otro_autor`, `test_un_alta_valida_queda_firmada_por_el_dentista` |
| CA8: alta por asistente exige uno de sus dentistas | `PacientesController@store/create`, `pacientes/create` | Feature `AltaPacienteAsistenteTest::test_el_alta_sin_dentista_se_rechaza`, `test_el_asistente_no_asigna_un_dentista_ajeno`, `test_el_alta_con_su_dentista_se_crea` |
| CA9 (abuso): alta de citas solo con pacientes del alcance y su próxima visita | `CitasController@create/store`, Gate `agendar-para-paciente` | Feature `AlcanceAltaCitasTest::test_el_selector_de_pacientes_respeta_el_alcance`, `test_la_preseleccion_de_un_paciente_ajeno_se_ignora`, `test_agendar_para_un_paciente_ajeno_responde_404` |
| CA10: reasignación surte efecto inmediato | `AlcanceClinico` | Feature `AlcancePacientesTest::test_tras_reasignar_el_dentista_anterior_pierde_el_acceso`; Unit `AlcanceClinicoTest::test_la_cita_cancelada_no_da_alcance` |
| CA11: accesos denegados auditados | `Gate::after` en `AppServiceProvider` → `Auditoria::denegacion` (spec 020) | Feature `AuditoriaAlcanceTest::test_un_acceso_denegado_queda_registrado` (ficha, expediente, edición ajena, alta de cita), `test_una_denegacion_403_no_se_duplica` |
| CA12: lecturas de ficha y expediente auditadas | `PacientesController@show`, `ExpedientesController@show` → `Auditoria::registrar(..., critico: true)` (spec 020) | Feature `AuditoriaAlcanceTest::test_la_lectura_de_una_ficha_queda_registrada`, `test_la_lectura_de_un_expediente_queda_registrada`, `test_sin_auditoria_no_se_muestra_el_expediente` |
| TM1 | Gate `ver-paciente` | `test_un_dentista_no_ve_la_ficha_de_un_paciente_ajeno` |
| TM2 | Gate `editar-paciente` | `test_un_dentista_no_actualiza_un_paciente_ajeno` |
| TM3 | Gate `editar-expediente` | `test_un_dentista_no_modifica_el_expediente_de_otro` |
| TM4 | validación de autor | `test_el_dentista_no_puede_firmar_como_otro` |
| TM5 | validación de cita | `test_la_cita_debe_ser_del_paciente` |
| TM6 | `aplicarAPacientes` | `test_el_directorio_del_dentista_solo_muestra_su_alcance`, `test_el_selector_de_pacientes_respeta_el_alcance` |
| TM7 | filtro dentro del alcance | `test_el_filtro_por_otro_dentista_no_amplia_el_alcance` |
| TM8 | preselección ignorada | `test_la_preseleccion_de_un_paciente_ajeno_se_ignora` |
| TM9 | Gate `crear-expediente` | `test_el_alta_de_expediente_para_un_paciente_ajeno_responde_404` |
| TM10 | Gate `agendar-para-paciente` | `test_agendar_para_un_paciente_ajeno_responde_404` |
| TM11 | `Rule::in` | `test_el_asistente_no_asigna_un_dentista_ajeno` |
| TM12 | `denyAsNotFound` | `test_un_paciente_ajeno_y_uno_inexistente_responden_igual` |
| TM13 | alcance por petición | `test_tras_reasignar_el_dentista_anterior_pierde_el_acceso` |
| TM14 | `Gate::after` | `test_un_acceso_denegado_queda_registrado` |

## Observabilidad
- Logs nuevos: ninguno. No se registran datos de pacientes ni de expedientes.
- Eventos de auditoría:
  - `acceso_denegado` (actor, habilidad como acción, tipo y id del recurso, resultado `denegado_404` o `denegado_403`, fecha, request_id) → `Gate::after` en `AppServiceProvider` para los 6 Gates, con `Auditoria::denegacion` → `AuditoriaAlcanceTest::test_un_acceso_denegado_queda_registrado` (CA11, TM14).
  - `lectura_paciente` y `lectura_expediente` (actor, id del recurso, fecha, request_id) → `PacientesController@show`, `ExpedientesController@show`, después de autorizar y antes de renderizar, con `Auditoria::registrar(..., critico: true)`: si no puede registrarse, 503 genérico y no se muestra el recurso → `test_la_lectura_de_una_ficha_queda_registrada`, `test_la_lectura_de_un_expediente_queda_registrada`, `test_sin_auditoria_no_se_muestra_el_expediente` (CA12).
  - Prerrequisito: spec 020 implementada (tabla `auditoria`, `App\Auditoria\Auditoria`, correlación).
- Métricas y alertas: ninguna nueva (no hay infraestructura de métricas).
- Cómo se verifica tras el deploy: con un dentista de prueba, abrir la ficha de un paciente ajeno → 404, y consultar en el registro de auditoría el evento `acceso_denegado` con el `request_id` de la respuesta.

## Rollout
- Feature flag: no aplica. Un control de acceso no debe poder desactivarse, aún no hay
  producción y el rollback es el redeploy del tag anterior (decisión del usuario, 2026-09-28).
- Orden de despliegue: (1) spec 020 desplegada; (2) esta spec en un solo paso (código y vistas).
- Compatibilidad: sin migraciones ni cambios de datos; convive con la versión anterior (solo
  restringe accesos). Formularios abiertos antes del deploy pueden recibir 404/422 al enviarse
  si el paciente queda fuera del alcance: aceptable.
- Rollback: redeploy del tag anterior (procedimiento de `docs/deployment.md`); no hay datos que
  revertir. Hoy solo existe el entorno local (`deploy.strategy: manual`, staging por definir).
- Métricas a vigilar tras el deploy: número de eventos `acceso_denegado` por usuario del personal
  en la primera semana (un pico indica un alcance mal calculado para un rol legítimo) y quejas
  de "no veo a mi paciente".

## Decisiones (→ ADR si son arquitectónicas)
- D1 · Mecanismo de autorización: Gates registrados en `AppServiceProvider` sobre la clase
  `App\Autorizacion\AlcanceClinico`, en lugar de Policies con modelos mínimos (no existen modelos
  de paciente ni expediente) (decisión del usuario, 2026-09-28). → [ADR 0003](../../adr/0003-autorizacion-gates-alcance-clinico.md) (accepted). Cubre RS1.a, RS1.b, RS1.c y RS1.d.
- D2 · Respuesta fuera de alcance: 404 (`denyAsNotFound`), para no confirmar la existencia del
  recurso (requisito no funcional de la spec); 403 cuando el recurso se ve pero no se puede
  modificar. Derivada de la spec aprobada.
- D3 · Base de pruebas: `bootstrap-test-db.sh` aplica las migraciones tras cargar el volcado,
  como ya hace el CI (decisión del usuario, 2026-09-28). El cambio lo hace la spec 020 (su D8);
  esta spec solo lo usa. La spec 013 lo sustituirá.
- D4 · Vulnerabilidades previas (RS16): fuera de esta spec; spec propia de actualización de
  dependencias antes de cualquier release (decisión del usuario, 2026-09-28).
- D5 · Sin feature flag (ver Rollout) (decisión del usuario, 2026-09-28).
- D6 · Auditoría: los eventos de CA11/CA12 se emiten con `App\Auditoria\Auditoria` de la spec
  020, que se implementa antes (decisión del usuario, 2026-09-28 en `/specify`): denegaciones con
  `denegacion(...)` y lecturas con `registrar(..., critico: true)`, porque la spec 020 exige fallo
  cerrado en acciones sobre datos clínicos.
- D7 · `ExpedientesController@index` del dentista sigue listando solo los expedientes que firmó
  (spec 007 CA1 no se modifica); la lectura de expedientes de otros autores se hace desde la
  ficha del paciente.
- D8 · Cobertura de riesgos: RS1.a, RS1.b, RS1.c, RS1.d y RS1.e quedan dentro de esta spec; su
  estado pasa a "en curso (spec 014)" al aprobar el plan y a "mitigada" solo tras `/release`.

## Impacto en arquitectura
- `docs/architecture.md → Módulos`: añadir "Autorización" (`app/Autorizacion/AlcanceClinico.php`, Gates en `AppServiceProvider`).
- `docs/architecture.md → Backend → Autenticación y autorización`: sustituir "comprobaciones de fila en controladores" por la regla de alcance centralizada.
- `docs/security.md → Autenticación y autorización → Dónde se aplica` y estado de RS1.a–RS1.e.

## Riesgos y mitigaciones
- Alcance mal calculado bloquea a personal legítimo → tests unitarios de la regla con los 4
  casos de paciente y verificación manual con cada rol antes de cerrar.
- Rendimiento del `EXISTS` sobre `citas` en el directorio → volumen académico pequeño; `citas`
  tiene índices por `paciente_id` y `dentista_id`. Revisar si el directorio supera 1 s.
- Choque con la spec 013 (draft), que refactoriza los mismos controladores → la 014 se
  implementa antes; la 013 migrará los Gates a Policies y deberá conservar estos tests.
- Dependencia de la spec 020 (plan aprobado) → la 014 no puede pasar `/implement` hasta que la
  020 esté implementada.
- Fallo cerrado en las lecturas: un fallo del registro impide abrir fichas y expedientes (503)
  → comportamiento exigido por la spec 020; página genérica con `request_id`.
- El dentista que reasigna a su paciente a otro dentista pierde el acceso al guardar → se
  redirige al listado con un mensaje en lugar de mostrar un 404.
