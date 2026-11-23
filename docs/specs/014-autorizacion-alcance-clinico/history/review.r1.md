---
spec: 014-autorizacion-alcance-clinico
verdict: changes_requested
round: 1
date: 2026-11-22
base: 1a783965
head: ee81669d
human_signoff: no requerido
---

# Review · 014 Autorización por alcance clínico

## Resumen
`changes_requested`. Hay un bloqueante: CA11 no se cumple al agendar con un dentista ajeno, porque el 403 se audita
sin el paciente (R1). Hay seis importantes: un test inestable por la zona horaria (R2), dos reglas sin test que las
proteja (R3, R4), la reasignación del doctor por quien solo llega por una cita (R5, que pide editar la spec) y dos
de accesibilidad (R6, R7). El usuario decide corregir todos los importantes y los menores baratos (R8–R12) antes de
liberar. Los demás menores (R13–R25) van como deuda al roadmap.
Conteo: bloqueante 1 · importante 6 · menor 18

Revisores: A (Spec) y S (Seguridad) con Opus; C+D (Constitución y Calidad) y Accesibilidad con Sonnet. Cada uno
recibió solo lo suyo del paquete `review-pack` (`code.diff` de 98.8 KB). B (Plan y alcance) lo hizo quien
consolida, con `scope.md`. Todos los hallazgos se verificaron abriendo el código, y se descartaron dos sin acción
(deuda previa de `pacientes/index`: `label` sin `for` en los filtros y estado vacío sin `role`).

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor efímero sobre `dentissa_051_test`) | ✅ 1705 en verde, 20 774 aserciones (línea base de `/implement`: 1645) |
| `vendor/bin/pint --test` sobre los 19 PHP tocados | ✅ (T034, sin cambios de PHP después) |
| `python .ai/bin/aidd.py validate` sin ruta | ✅ 0 errores |
| Build | ➖ no aplica (Blade en servidor; `npm run build` solo afecta a `welcome.blade.php`, sin tocar) |

Los tests nuevos no son de UI en el navegador ni asíncronos. Se ejecutaron en cuatro suites completas seguidas
(T034, verificación de expedientes, de citas y esta review), siempre en verde. Aun así, R2 muestra que uno depende de
la hora del día.

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `AlcancePacientesTest::test_el_directorio_del_dentista_es_su_alcance`, `AlcanceClinicoReglaTest::test_el_alcance_de_cada_rol` | ✅ | |
| CA2 | `AlcancePacientesTest::test_el_directorio_del_asistente_es_el_de_sus_dentistas`, `::test_el_admin_ve_a_todos` | ✅ | |
| CA3 | `AlcancePacientesTest::test_la_ficha_fuera_del_alcance_es_un_404_auditado`, `::test_editar_…`, `::test_guardar_…` | ✅ | |
| CA4 | `AlcanceExpedientesTest::test_el_dentista_lee_el_expediente_de_otro_autor`, `::test_la_ficha_lista_…`, `::test_el_asistente_lee_en_solo_lectura` | ✅ | |
| CA5 | `AlcanceExpedientesTest::test_el_expediente_fuera_del_alcance_es_un_404_auditado` | ✅ | |
| CA6 | `AlcanceExpedientesTest::test_modificar_el_expediente_de_otro_autor_se_rechaza`, `AlcanceVistasTest::test_editar_el_expediente_solo_el_autor` | ✅ | |
| CA7 | `AlcanceExpedientesTest::test_el_alta_con_otro_autor_se_rechaza`, `::test_el_alta_con_una_cita_de_otro_paciente_se_rechaza` | ⚠ | R3: falta el caso de una cita del propio paciente |
| CA8 | `AlcancePacientesTest::test_el_asistente_asigna_uno_de_sus_dentistas` | ✅ | R4: sin test del dentista desactivado (D15) |
| CA9 | `AlcanceCitasTest::test_agendar_para_un_paciente_ajeno_es_un_404_auditado`, `::test_un_paciente_sin_dentista_solo_lo_agenda_el_admin` | ⚠ | R10: P5 y la preselección solo se prueban con el dentista |
| CA10 | `AlcancePacientesTest::test_el_dentista_que_reasigna_fuera_vuelve_al_directorio`, `AlcanceClinicoReglaTest::test_reasignar_saca_del_alcance_en_el_acto` | ⚠ | R9: que el nuevo dentista vea la ficha no se prueba por HTTP |
| CA11 | `assertDenegado` en cada test de denegación | ❌ | R1: al agendar con un dentista ajeno, el evento sale sin el paciente |
| CA12 | `AlcancePacientesTest::test_leer_una_ficha_queda_registrado`, `::test_abrir_la_edicion_…`, `::test_sin_auditoria_no_se_muestra_la_ficha`, `AlcanceExpedientesTest::test_leer_un_expediente_…`, `::test_sin_auditoria_…` | ⚠ | R11: sin test del 503 al abrir la edición |
| CA13 | `AlcanceCitasTest::test_el_detalle_de_una_cita_ajena_se_deniega_por_gate`, `::test_agendar_con_un_dentista_ajeno_se_deniega` | ⚠ | R1: agendar con un dentista ajeno, sin auditoría comprobada ni caso del asistente |
| CA14 | `AlcancePacientesTest::test_el_aviso_de_duplicado_solo_enlaza_lo_que_se_puede_ver` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). Los 19 archivos coinciden con «Cambios por
  módulo».
- Partes del plan sin implementar: ninguna. T030–T032 no tienen diff porque los tests existentes pasaron sin
  ajustes, y así consta en sus notas.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | `1a783965` aprueba spec, plan y tareas antes del primer commit de código (`4d004a06`) |
| P2 Test que falla antes | ✅ | Las notas de cada tarea registran el fallo previo y una rotura comprobada |
| P3 Autorización en servidor, por rol y recurso | ⚠ | Gates de `AccesoClinico` en cada ficha, expediente y alta; R5: el doctor asignado lo puede cambiar quien llega por una cita |
| P4 Entrada validada y SQL con bindings | ⚠ | UUID antes del Gate, `Rule::exists`/`Rule::in`; R17: `DB::raw(1)` nuevo en `AlcanceClinico.php:125`, literal pero prohibido por la letra de P4 (se corrige en R8–R12) |
| P5 Nada sensible en el repositorio | ✅ | gitleaks sin fugas; datos de tests ficticios (`@example.test`) |
| P6 Sin vulnerabilidades críticas o altas | ✅ | `composer audit`: 1 aviso bajo (ver Seguridad) |
| P7 Auditoría de accesos y cambios | ⚠ | `paciente_leido` y `expediente_leido` críticos; R1: un acceso denegado sin el paciente; la reasignación sin evento queda para la 022 (OB1.e) |
| P8 Correlación y logs sin datos sensibles | ✅ | 0 `Log::` nuevos; los eventos van por `Auditoria` con `request_id` |
| P9 Solo el sistema de diseño | ⚠ | Sin `style=""` nuevos; R7: «Firmado por» con `--text-light` (DS1, no llega a AA); R12: clases nuevas sin CSS |
| P10 Rutas indexables | ➖ | Solo rutas del panel, no indexables |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`stdin` sobre `git diff 1a783965 HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` sobre los 19 PHP tocados | ✅ 0 hallazgos (23 reglas) |
| `composer audit --locked` | crít: 0 · alta: 0 · media: 0 · baja: 1 (GHSA-63hm-g96r-2m2q, `laravel/framework` 12.69.2 → 12.71.0, `Concurrency`, que la app no usa; R25) |
| `node .github/scripts/auditar-npm.mjs` | ✅ sin altas ni críticas sin excepción |
| Contenedores (trivy) | ➖ sin cambios de imagen en esta spec |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso / IDOR | ✅ | Ficha, edición, expedientes, altas y detalle de cita pasan por los Gates antes de leer; los listados usan `aplicarAPacientes`; no hay otros endpoints del personal que lean por id sin Gate |
| A01 Gates saltables por tipo de dato | ✅ | `is_string` + `Str::isUuid` antes del Gate; probado con texto, ausente y array |
| A01 Orden autorización/validación (D17) | ✅ | Gate antes de `validate()`; probado con datos inválidos → 404 |
| A01 Enumeración (TM9) | ⚠ | En HTML no se distinguen; en JSON sí (R13) |
| A05 Inyección SQL | ✅ | Bindings en todas las consultas nuevas; semgrep limpio |
| A05 XSS | ✅ | Solo `{{ }}` en las vistas tocadas; la URL del aviso sale de `route()` |
| A09 Auditoría y logs | ❌ | R1 (denegación sin paciente); reasignación sin evento → spec 022 |
| Divulgación | ✅ | Los 404 no muestran el nombre; los enlaces a la ficha dependen de `ver-paciente` |
| Proceso | ✅ | Sin cambios en lockfiles, `.github/`, `.claude/` ni hooks; sin instrucciones a agentes en el diff |

## Observabilidad
- Eventos de auditoría emitidos y probados: sí. `paciente_leido` al abrir la ficha y su edición, y
  `expediente_leido` al abrir el expediente, en modo crítico (`test_leer_*`, `test_sin_auditoria_*` con
  `AlmacenAuditoriaQueFalla`). Las denegaciones se registran con actor, ruta y recurso (`assertDenegado`), salvo R1.
- Datos sensibles en logs del código tocado: ninguno (0 `Log::` nuevos; `Auditoria::validar` impide texto libre en
  `detalle`).
- `request_id` en los logs nuevos: no hay logs nuevos; los eventos de auditoría lo llevan.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | app/Http/Controllers/CitasController.php:298; app/Auditoria/RegistroCentralDenegaciones.php:52 | Agendar con un dentista ajeno (paciente del alcance) da el 403 de `Gate::authorize('gestionar-cita')`, que solo audita el registro central. `*.citas.store` no lleva `{id}`, así que el evento sale con `recurso_tipo` `citas` y sin `recurso_id`. CA11 pide «sobre qué paciente». El test (`AlcanceCitasTest.php:116`) no usa `assertDenegado` y no cubre al asistente | `Gate::inspect` y, si deniega, `Auditoria::denegar(<ruta>, 'pacientes', <paciente>)` (403), como en el alta de expediente; test con `assertDenegado` para dentista y asistente |
| R2 | importante | tests/Feature/AlcanceCitasTest.php:51 | `test_la_preseleccion_ajena_se_ignora` espera `Carbon::now()->addDay()` en UTC (`config/app.php` `timezone => UTC`). El controlador usa `RelojClinica::hoy()->addDay()` en la zona de la clínica. Entre las 18:00 y las 24:00 de la clínica el test falla aunque el código esté bien | Comparar con `RelojClinica::hoy()->addDay()->toDateString()` |
| R3 | importante | tests/Feature/AlcanceExpedientesTest.php; app/Http/Controllers/ExpedientesController.php (regla `cita_id`) | La regla `Rule::exists('citas','id')->where('paciente_id', …)` solo se prueba con una cita ajena y con `null`/vacío. Una regla que rechazara toda cita dejaría la suite en verde | Test de alta con una cita del propio paciente: sin errores y `cita_id` guardado |
| R4 | importante | tests/Feature/AlcancePacientesTest.php:151; app/Http/Controllers/PacientesController.php (`reglasDelDoctor`) | D15 pide un dentista activo, pero solo se prueba el tipo (un asistente). Quitar el filtro de usuarios activos no rompería ningún test | Test con un dentista desactivado en el alta (asistente) y en la edición (admin): error en `dentista_id` y sin cambios |
| R5 | importante | app/Http/Controllers/PacientesController.php (`update`), `AccesoClinico::editarPaciente` | Un dentista que tiene al paciente en su alcance solo por una cita puede cambiarle el doctor, también a sí mismo, y el dentista asignado pierde el acceso. La spec no lo prevé (CA3 da la edición de la ficha a quien la ve) | Decisión del usuario (2026-11-22): solo el admin o el dentista asignado cambian el doctor. Pide `/specify --edit 014` (CA15) y `/plan 014 --fix` antes de sus tareas |
| R6 | importante | resources/views/pacientes/create.blade.php:165-177 | En el alta del asistente, «Doctor asignado» es `required` pero la etiqueta no lleva el asterisco de los demás obligatorios. La ayuda «Asigna uno de tus dentistas.» no está en `aria-describedby`, y con un error el atributo solo apunta al mensaje | Asterisco con `$doctorObligatorio`; `id` en la ayuda y `aria-describedby` con la ayuda (y con el error si lo hay) |
| R7 | importante | resources/views/expedientes/show.blade.php:106; resources/views/expedientes/create.blade.php:47; public/css/app.css:518 | «Firmado por …» y la ayuda del autor fijo usan `.field-hint` (`--text-light`, 3.51:1 sobre blanco y 3.27:1 sobre `--bg`, DS1), por debajo de AA. «Firmado por» no es una ayuda de campo: es la autoría del expediente (CA6) | Clase propia con un color de texto que cumpla AA, definida con tokens en `app.css` (resuelve también R12) |
| R8 | menor | app/Http/Controllers/PacientesController.php:29-37 | Las constantes de la 014 se insertaron entre el docblock «Spec 054 (/review R4)» y `CORREO_DEL_PERSONAL`: el docblock queda huérfano | Mover las constantes de la 014 encima de ese docblock |
| R9 | menor | tests/Feature/AlcancePacientesTest.php:142-152 | CA10 dice que «el nuevo dentista la ve de inmediato». Por HTTP solo se prueba el 404 del anterior | Tras la reasignación, `actingAs(dentista2)->get(ficha)->assertOk()` |
| R10 | menor | tests/Feature/AlcanceCitasTest.php:43-52, 65-72 | La preselección ignorada y el paciente sin dentista (P5) solo se prueban con el dentista; el RNF pide cada rol implicado | Recorrer dentista y asistente en los dos tests |
| R11 | menor | app/Http/Controllers/PacientesController.php:347; tests/Feature/AlcancePacientesTest.php:134 | CA12 aplica el modo crítico al abrir la edición, pero solo hay test del 503 en `show` | Variante de `/dentista/pacientes/{p1}/edit` con `AlmacenAuditoriaQueFalla`: 503 sin datos |
| R12 | menor | resources/views/expedientes/show.blade.php, resources/views/expedientes/create.blade.php | `firma-expediente` y `autor-expediente` no existen en `public/css`: solo sirven de gancho de test | Se resuelve con R7 (estilo con tokens) |
| R13 | menor | app/Http/Controllers/ExpedientesController.php:187, 230, 255-262; tests «ajeno e inexistente responden igual» | Con `Accept: application/json`, un expediente inexistente (`abort(404)`, mensaje vacío) y uno ajeno (`denyAsNotFound`, «Not Found») dan cuerpos distintos. En HTML son iguales. Los tests solo comparan el estado | Mismo camino o mismo mensaje para los dos; comparar también el cuerpo |
| R14 | menor | app/Http/Controllers/ExpedientesController.php:139-141; PacientesController.php (`reglasDelDoctor`) | En las rutas del admin, `paciente_id`/`dentista_id` no exigen `string`: un array de ids válidos pasa `exists` y da 500 (previo, solo admin) | Añadir `string` a esas reglas |
| R15 | menor | PacientesController.php:67-68; ExpedientesController.php:46-47 | `buscar` como array da 500 («Array to string conversion»). Previo, solo personal autenticado | `is_string($request->buscar)` |
| R16 | menor | tests/Feature/AlcancePacientesTest.php:104; AlcanceExpedientesTest.php:86 | `test_el_asistente_no_edita_fichas` y `…_no_modifica_expedientes` piden rutas `/dentista/…`: el 403 lo da `role:` y nunca llegan al Gate. El nombre promete más | Renombrar a «la ruta del dentista rechaza al asistente» |
| R17 | menor | app/Autorizacion/AlcanceClinico.php:125 | `->select(DB::raw(1))` en el `whereExists`. Es un literal, pero P4 prohíbe `DB::raw` | `->select('citas.id')` |
| R18 | menor | CitasController.php:281-283; ExpedientesController.php (`accionSolicitada`) | El patrón «Gate y, si niega, `denegar()` con la ruta» está repetido en dos controladores; en Citas se usa `request()` teniendo `$request` | Helper común |
| R19 | menor | PacientesController.php y ExpedientesController.php (`index`) | `$alcance->aplicarAPacientes($query, …);` descarta el valor y depende de que el Builder se mute | `$query = $alcance->aplicarAPacientes($query, …)` |
| R20 | menor | resources/views/expedientes/show.blade.php; resources/views/citas/show.blade.php | `@can('editar-expediente')` se evalúa dos veces y `cannot` una tercera, cada una con consultas de alcance | Calcularlo una vez |
| R21 | menor | resources/views/pacientes/create.blade.php:163-174 | `$doctorObligatorio` se calcula en la vista con `isset($paciente)` (la vista se comparte con `edit`) | Pasarlo desde el controlador |
| R22 | menor | tests/Feature/AlcanceVistasTest.php (`test_los_elementos_nuevos_no_llevan_estilos_en_linea`) | La aserción suma `@style` de hijos y del contenedor en una línea difícil de leer, y no protege el `<p>` nuevo por separado | Dos aserciones |
| R23 | menor | tests/Feature/AlcancePacientesTest.php:164 | `test_el_asistente_asigna_uno_de_sus_dentistas` cubre cuatro comportamientos; un fallo no dice cuál | Dividirlo |
| R24 | menor | resources/views/expedientes/create.blade.php:43-48 | El autor fijo es un `span.field-label` y un `<p>` sin asociación programática | Un solo texto «Dentista responsable: …» o `role="group"` con `aria-labelledby` |
| R25 | menor | resources/views/pacientes/create.blade.php:66-71 | Si ningún duplicado se puede ver, el aviso queda sin explicación de por qué no hay ficha que abrir. Además, `composer audit` da un aviso bajo en `laravel/framework` (GHSA-63hm-g96r-2m2q, `Concurrency`, que no se usa) | Texto breve cuando no hay enlaces; actualizar Laravel a ≥ 12.71.0 en un `/chore` |

## Preparación para release
- Rollback factible: sí. `bash deploy/volver.sh <tag anterior>` sin copia; no hay cambios de esquema y los eventos de
  lectura ya escritos se quedan en la auditoría.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: ninguno, por diseño (plan D8: un interruptor permitiría desactivar un control de seguridad).
- Docs actualizadas: `docs/architecture.md` (T090) y `docs/observability.md` (T091); `docs/deployment.md` sin
  cambios (no hay variables, migraciones ni pasos nuevos).
- SCA: ninguna crítica ni alta.
- Cobertura de riesgos: RS1.a–RS1.e implementadas y probadas, salvo R1 (CA11 al agendar) y R5 (reasignación, RS1.a).
  OB1.d dentro para ficha, edición y expediente. El estado en `docs/security.md` lo cambia `/release` (T098).

## Tareas añadidas
- T035, T036 ← R1
- T037 ← R2
- T038 ← R3
- T039 ← R4
- T040, T041 ← R6
- T042, T043 ← R7, R12
- T044 ← R8, R17
- T045 ← R9, R10, R11
- T047, T048, T049 ← R5 (tras añadir CA15 a la spec y D21 y TM16 al plan, aprobados por el usuario el 2026-11-22)
- T046 ← repetir la verificación final tras las correcciones

## Aceptados sin tarea
- **R13** → roadmap: el expediente ajeno y el inexistente dan cuerpos JSON distintos (`denyAsNotFound` frente a `abort(404)`); los tests solo comparan el estado
- **R14** → roadmap: `paciente_id`/`dentista_id` en array dan 500 en las altas y ediciones del admin (falta `string`)
- **R15** → roadmap: `buscar` en array da 500 en los directorios de pacientes y expedientes
- **R16** → roadmap: renombrar los tests del asistente que reciben el 403 del rol de la ruta, no del Gate
- **R18** → roadmap: helper común para «Gate y `denegar()` con la ruta» en Citas y Expedientes
- **R19** → roadmap: asignar el valor de `aplicarAPacientes` en vez de depender de la mutación del Builder
- **R20** → roadmap: evaluar una sola vez `editar-expediente` y `ver-paciente` en las vistas
- **R21** → roadmap: `$doctorObligatorio` desde el controlador, no con `isset($paciente)` en la vista
- **R22** → roadmap: separar la aserción de estilos en línea del estado vacío
- **R23** → roadmap: dividir `test_el_asistente_asigna_uno_de_sus_dentistas` en cuatro tests
- **R24** → roadmap: asociar programáticamente el autor fijo del alta de expediente
- **R25** → roadmap: explicar en el aviso de duplicado por qué no hay ficha que abrir; actualizar Laravel a ≥ 12.71.0 (GHSA-63hm-g96r-2m2q, bajo)
