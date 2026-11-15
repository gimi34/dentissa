---
spec: 046-navegacion-interna
plan: plan.md
status: approved
impl_base: e6c2bbd
---

# Tareas · 046 Navegación interna

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- **Corrección tras `/analyze` r1 (2026-11-12):** `/tasks --fix` editó en su lugar T010–T013, T020–T023, T025, T026, T028–T031, T091 y T098 y añadió T032 (A1–A7, A9, A10, A12, A14–A16). No se renumeró ninguna.
- **Corrección tras `/analyze` r2 (2026-11-14):** `/tasks --fix` editó en su lugar T010, T012, T013, T021–T032 y T091 (A17–A21, A24–A26). T032 deja de tomar una línea base nueva y pasa a excluir zonas en `capturar.mjs` (decisión del usuario sobre A17). No se renumeró ninguna.
- **Corrección tras `/analyze` r3 (2026-11-14):** `/tasks --fix` editó en su lugar T030, T031, T032 y T091 (A27–A30). No se renumeró ninguna.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

## Constitution Check
Contra `docs/constitution.md` 1.3.0; el análisis completo está en `plan.md`.

| Principio | Resultado | En las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Ninguna tarea de código antes de aprobar este archivo; T001 espera a la 045 (plan D7) |
| P2 Test que falla antes y pasa después | ✅ | T010–T013 se escriben antes de T020–T030 (cada tarea de implementación depende del test que hace pasar) y fallan por la razón esperada; los tests que recorren pantallas llevan un caso por pantalla y rol, y cada tarea se comprueba con el filtro de sus casos; las caracterizaciones (CA6 en nueve formularios en T012, TM3 en T013) se comprueban rompiendo el código que protegen y se anota en la tarea |
| P3 Autorización en el servidor | ✅ | Ninguna tarea toca rutas, middleware ni Gates; T013 lo caracteriza |
| P4 Entrada validada y SQL con bindings | ✅ | T020 pinta con salida escapada; T013 lo prueba; semgrep en T031 |
| P5 Nada sensible en el repositorio | ✅ | Datos sintéticos en T010; `gitleaks` en T031 |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas; `composer audit` y `npm audit` en T031 |
| P7 Auditoría | ➖ | Sin eventos nuevos (plan) |
| P8 Correlación y logs | ➖ | Sin logs nuevos (plan) |
| P9 Solo el sistema de diseño | ✅ | T002 actualiza el sistema antes de cualquier tarea de interfaz; T011 prueba reglas y contraste; ninguna tarea añade `style=""` |
| P10 Rutas indexables | ➖ | Sin rutas públicas (plan) |

## Preparación
- [x] T001 Traer a la rama `main` con la spec 045 ya fusionada (plan D7) — sin archivos propios (fusión de `origin/main` en `feature/046-navegacion-interna`) — hecho cuando: `git log` de la rama contiene la fusión de la 045 y `php artisan test` pasa completo
  - nota: fusión e6c2bbd (un conflicto en docs/specs/README.md, resuelto dejando las dos filas); suite 1001 en verde; sin fallos de línea base. El sistema de diseño queda en 1.16.0: la 046 toma la 1.17.0.
- [x] T002 Actualizar «Migas de pan» y las reglas «dónde se usan las migas» y «título de pantalla» del acta (`design.md` → «Cambios a incorporar al sistema»), con la versión siguiente a la vigente y su fila en Decisiones — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `system.md` describe la navegación con lista, el enlace en `--p600` subrayado, el elemento actual en `--text` y el camino que pasa de línea, `system.html` lo muestra y `python .ai/bin/aidd.py validate` no da errores — cubre: CA1, CA3, CA4, CA10 — depende: T001
  - nota: versión 1.17.0 (la 045 tomó la 1.16.0); en system.html, la muestra de migas con la estructura nueva y el par de contraste del enlace. La tabla de deuda (DS15) se actualiza en T091.

## Tests (antes de implementar)
- [x] T010 Crear `NavegacionInternaTest` con el escenario (cuentas de los cuatro roles, paciente, cita reprogramable y no reprogramable, expediente, servicio, como en `MovilPersonalTest`), la tabla `[rol, dirección, camino esperado]` de las 18 pantallas, solo con las combinaciones de rol y dirección que existen en `routes/web.php` y con el primer elemento igual al texto del menú de cada rol, y los tests `test_cada_pantalla_de_segundo_nivel_lleva_sus_migas`, `test_las_pantallas_del_menu_no_llevan_migas` y `test_los_enlaces_de_las_migas_llevan_a_destinos_fijos`; los tres usan un proveedor de datos con un caso con nombre por pantalla y rol (p. ej. `admin · nueva cita`; el proveedor da la clave y la dirección se resuelve con el escenario), para que cada caso pase o falle por separado — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: los casos fallan porque faltan la `nav#migas` o sus caminos, o sobran las migas de «Agendar cita», y `php artisan test --filter` con el nombre de un caso ejecuta solo ese — cubre: CA1, CA2 — depende: T001
  - nota: 30 casos por test (18 pantallas con los roles que las abren) y 21 pantallas del menú. Fallan por falta de nav#migas y de sus caminos; de las del menú falla solo «/cliente/agendar». El filtro por caso funciona: --filter "NavegacionInternaTest.*admin · nueva cita".
- [x] T011 Añadir `test_las_migas_son_una_navegacion_accesible`, `test_las_reglas_de_las_migas_distinguen_el_enlace`, `test_el_contraste_de_las_migas_es_aa` y `test_las_migas_pueden_pasar_de_linea` (con `Concerns/ReglasCss`); el de navegación comprueba además que todos los hijos del `ol` son `li` y que hay n−1 separadores con `aria-hidden`; el de salto de línea mira las reglas de `app.css` — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: fallan por la estructura y las reglas actuales (sin `aria-label`, enlace sin subrayar en `--text-muted`, salto de línea solo en `movil.css`); el de contraste pasa ya si `--p600` sobre `--bg` da ≥ 4,5:1 y se anota el valor medido — cubre: CA3, CA4, CA10 — depende: T010
  - nota: fallan el de navegación (30 casos), el de reglas y el de salto de línea; el de contraste pasa ya: --p600, --p800 y --text sobre --bg dan al menos 4,5:1.
- [x] T012 Añadir `test_reprogramar_lleva_migas_se_pueda_o_no`, `test_cancelar_lleva_al_mismo_sitio_que_las_migas`, `test_el_expediente_ya_no_tiene_volver_al_paciente`, `test_ninguna_pantalla_usa_el_titulo_por_defecto` y `test_el_menu_marca_la_seccion_de_la_pantalla`, con un caso por pantalla y rol como en T010 (el de reprogramar, un caso por rol y estado); el de «Cancelar» lleva un caso por cada uno de los diez formularios de CA6, sin contar «Cancelar la cita definitivamente», y compara el destino de «Cancelar» con el destino fijo del penúltimo elemento según la tabla de la spec, no con las migas pintadas; el del menú compara texto y destino de la entrada marcada con el primer elemento de las migas, por rol, y el destino por la ruta, sin esquema ni dominio (el menú usa direcciones relativas) — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: fallan por la razón esperada (reprogramar sin migas, botón presente, título «Dashboard» en servicios, expedientes sin «Pacientes» marcado, «Cancelar» de «Editar usuario» al listado) y, en el de «Cancelar», falla solo el caso de «Editar usuario» y pasan los otros nueve, que son de caracterización: se anota en T031 `comprobado rompiendo` el destino de uno de ellos — cubre: CA5, CA6, CA7, CA8, CA9 — depende: T011
  - nota: reprogramar: 6 casos (admin, asistente y paciente; se puede y no), los 6 fallan por falta de migas y los 6 llegan a esa aserción (la cita empezada abre la pantalla sin formulario de fecha). «Cancelar»: falla solo «editar usuario» (va a /admin/usuarios); los otros 9 pasan (caracterización, rotura en T031). Títulos: fallan los 2 de servicios. Menú: fallan los 5 casos de expedientes.
- [x] T013 Añadir los tests de abuso `test_las_migas_ignoran_un_destino_de_vuelta_en_la_direccion` (TM1), `test_el_nombre_con_codigo_se_pinta_como_texto` (TM2) y `test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` (TM3, caracterización; incluye a un miembro del personal abriendo el detalle de cita del paciente: «no encontrada», sin migas ni nombres) — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: TM1 y TM2, con un caso por pantalla y rol, fallan en las pantallas que aún no tienen `nav#migas`, y TM3 pasa; su comprobación rompiendo el middleware de rol se anota en T031 — cubre: CA11 — depende: T012
  - nota: TM1 (30 casos) y TM2 (10 casos) fallan por falta de nav#migas; TM3 (8 casos) pasa: 403 para otro rol y 404 en el detalle de cita del paciente. Rotura de TM3 en T031.
- [x] T014 Dejar de fijar el elemento `div` en el test de migas de la 035: `//div[@id='migas']…` pasa a `//*[@id='migas']…` (el elemento lo fija ahora T010) — `tests/Feature/MovilPacientesExpedientesTest.php` — hecho cuando: `MovilPacientesExpedientesTest` pasa antes y después de cambiar el marcado — depende: T001
  - nota: pasa antes de cambiar el marcado (18 tests).

## Implementación
- [ ] T020 Crear el parcial de migas: `nav#migas.migas` con `aria-label="Migas de pan"`, lista ordenada, enlaces, separadores `span.migas-sep` con `aria-hidden="true"` dentro de cada `li` menos el primero, y el último elemento `li.migas-actual` con `aria-current="page"`, todo con salida escapada (plan, «Contratos y datos») — `resources/views/partials/migas.blade.php` — hecho cuando: el parcial existe y se puede incluir con una lista de uno a tres elementos — cubre: CA2, CA3, CA11 — depende: T002, T010, T011, T013
- [ ] T021 Cambiar las reglas de las migas dentro del bloque `/* Spec 033 */` de `app.css` con las declaraciones exactas del plan (fila de `app.css`): `.migas` sin la fila flexible y en `--text`; reglas nuevas `.migas ol` (fila flexible sin viñetas ni sangría, `flex-wrap`, `gap: var(--space-3)`), `.migas li` (fila con su separador, `min-width: 0`, `overflow-wrap: anywhere`) y `.migas a:hover` (`--p800`); `.migas a` en `--p600` subrayado; `.migas-actual` y la regla de foco no cambian; `movil.css` no se toca (plan D6). En `MovilPacienteTest::test_las_reglas_nuevas_de_app_css_solo_usan_tokens`, las entradas `.migas` y `.migas a` de `$esperadas` pasan a esas declaraciones; `.migas-actual` se queda como está — `public/css/app.css`, `tests/Feature/MovilPacienteTest.php` — hecho cuando: los tests de T011 que dependen de las reglas pasan; `test_las_reglas_nuevas_de_app_css_solo_usan_tokens` pasa con las declaraciones nuevas y su bucle de solo tokens recorre también `.migas ol`, `.migas li` y `.migas a:hover` (están en el bloque de la 033); el resto de `MovilPacienteTest` sigue pasando en lo que no es «Agendar cita»; y `MovilPacientesExpedientesTest` pasa sin tocarlo — cubre: CA4, CA10 — depende: T011, T020
- [ ] T022 Usar el parcial en las pantallas de citas del personal: «Citas › Nueva cita», «Citas › *paciente*» y «Citas › *paciente* › Reprogramar» (antes del parcial de reprogramar; la vista calcula el prefijo del rol como `citas/show`), con «Mis Citas» como primer elemento para la dentista — `resources/views/citas/create.blade.php`, `resources/views/citas/show.blade.php`, `resources/views/citas/reprogramar.blade.php` — hecho cuando: los casos de esas pantallas en los tests de T010 y los del personal de `test_reprogramar_lleva_migas_se_pueda_o_no` pasan (filtro por el nombre del caso), y `MovilPersonalTest` sigue pasando — cubre: CA1, CA2, CA5 — depende: T012, T020, T021
- [ ] T023 [P] Usar el parcial en el detalle y en reprogramar del paciente: «Mis Citas › Detalle» y «Mis Citas › Detalle › Reprogramar» — `resources/views/citas/cliente-show.blade.php`, `resources/views/citas/cliente-reprogramar.blade.php` — hecho cuando: sus casos de T010 y los del paciente de `test_reprogramar_lleva_migas_se_pueda_o_no` pasan (filtro por el nombre del caso) — cubre: CA1, CA2, CA5 — depende: T012, T020, T021
- [ ] T024 Quitar las migas de «Agendar cita» y sacarla del bucle de pantallas con migas del test de la 033 — `resources/views/citas/cliente-agendar.blade.php`, `tests/Feature/MovilPacienteTest.php` — hecho cuando: el caso de «Agendar cita» de `test_las_pantallas_del_menu_no_llevan_migas` pasa y `MovilPacienteTest` pasa completo — cubre: CA1 — depende: T021, T023
- [ ] T025 [P] Usar el parcial en la ficha, el alta y la edición del paciente: «Pacientes › *paciente*», «Pacientes › Nuevo paciente» y «Pacientes › *paciente* › Editar» — `resources/views/pacientes/show.blade.php`, `resources/views/pacientes/create.blade.php` — hecho cuando: sus casos de T010 pasan en los roles que abren cada pantalla (filtro por el nombre del caso) y `MovilPacientesExpedientesTest` sigue pasando — cubre: CA1, CA2 — depende: T014, T020, T021
- [ ] T026 [P] Usar el parcial en el expediente y en el alta de expediente, y quitar el enlace «← Volver al paciente» de la cabecera del expediente — `resources/views/expedientes/create.blade.php`, `resources/views/expedientes/show.blade.php` — hecho cuando: sus casos de T010 y los de `test_el_expediente_ya_no_tiene_volver_al_paciente` pasan, y `MovilPacientesExpedientesTest` sigue pasando — cubre: CA1, CA2, CA7 — depende: T012, T014, T020, T021
- [ ] T027 [P] Sustituir las migas con `style=""` de personal por el parcial: «Personal › Nuevo miembro», «Personal › *miembro*» y «Personal › *miembro* › Editar» — `resources/views/personal/create.blade.php`, `resources/views/personal/show.blade.php` — hecho cuando: sus casos de T010 pasan (filtro por el nombre del caso) — cubre: CA1, CA2 — depende: T020, T021
- [ ] T028 [P] Sustituir las migas con `style=""` de usuarios por el parcial: «Usuarios › Nuevo usuario», «Usuarios › *usuario*» y «Usuarios › *usuario* › Editar»; en edición, «Cancelar» pasa a llevar a la ficha del usuario (en alta sigue en el listado) — `resources/views/usuarios/create.blade.php`, `resources/views/usuarios/show.blade.php` — hecho cuando: sus casos de T010 y el caso «Editar usuario» de `test_cancelar_lleva_al_mismo_sitio_que_las_migas` pasan (los otros nueve casos ya pasaban) — cubre: CA1, CA2, CA6 — depende: T012, T020, T021
- [ ] T029 [P] Dar título y migas a servicios: «Nuevo servicio» con «Servicios › Nuevo servicio» y «Editar servicio» con «Servicios › Editar servicio» — `resources/views/servicios/create.blade.php`, `resources/views/servicios/edit.blade.php` — hecho cuando: sus casos de T010 y los dos de servicios de `test_ninguna_pantalla_usa_el_titulo_por_defecto` pasan — cubre: CA1, CA2, CA8 — depende: T012, T020, T021
- [ ] T030 Marcar «Pacientes» en el menú también en `admin/expedientes*`, `asistente/expedientes*` y `dentista/expedientes/*`, y «Expedientes» de la dentista solo en su listado — `resources/views/layouts/app.blade.php` — hecho cuando: todos los casos de `test_el_menu_marca_la_seccion_de_la_pantalla` pasan, con una sola entrada marcada en cada una de las 18 pantallas, y `NavegacionInternaTest` pasa completo; la medición en el navegador se hace en T032 — cubre: CA9 — depende: T012, T022, T023, T024, T025, T026, T027, T028, T029
- [ ] T031 Verificación final: suite completa, `vendor/bin/pint --test` sobre lo tocado, `gitleaks detect --no-banner`, semgrep sobre lo tocado, `composer audit` y `npm audit --audit-level=high`; `NavegacionInternaTest` dos veces; `node docs/design/capturar.mjs --medir` completo con el procedimiento de su cabecera (línea base del tag en producción) y las zonas de T032; y las dos caracterizaciones comprobadas rompiendo un momento el destino de un «Cancelar» (CA6) y el middleware de rol (TM3) — sin archivos propios — hecho cuando: todo pasa, T013 queda en verde completo y se anotan aquí las dos roturas comprobadas · verificación manual: el usuario ve a 320, 390 y 1440 px, sin desplazamiento horizontal y con las migas visibles sin desplazarse, la muestra de seis pantallas del plan: «Nueva cita», la ficha del expediente, «Editar personal» y «Editar usuario» con un nombre largo, «Reprogramar» del personal y el detalle de una cita del personal justo después de reprogramarla con éxito (una reprogramación por ancho; antes de los 4 s en que el aviso «Cita reprogramada correctamente» se retira, el aviso arriba y las migas debajo; después, las migas suben y siguen visibles), contra `docs/design/system.html` — cubre: CA1, CA6, CA10, CA11 — depende: T013, T024, T030, T032
- [ ] T032 Excluir de la comparación del escritorio de `capturar.mjs` lo que la 046 cambia a propósito (CA12, plan D12): en `EN_PAGINA.excluidos`, el rectángulo de `#migas`, la cabecera entera de la ficha del expediente (`.expediente-cabecera`, que se recompone al quitar «← Volver al paciente»; antes se mide su alto en la línea base y en la rama a 1024 y 1440 px con los datos de captura y se anota aquí: si cambia en algún ancho, las tres pantallas `*-ficha-expediente` salen de `COMPARADAS` como las dos del paciente) y, en las direcciones de expedientes, las entradas «Pacientes» y «Expedientes» de la barra lateral; sacar `cliente-agendar` y `citas-reprogramar-cliente` de `COMPARADAS` con una lista propia y su comentario (vuelven cuando la 046 esté en producción; siguen en las demás comprobaciones); actualizar el texto de «034 escritorio» en `COMPROBACIONES` y la cabecera (paso 7b) — `docs/design/capturar.mjs` — hecho cuando: `node docs/design/capturar.mjs --seco --medir` lista el texto nuevo; `--medir`, con la línea base tomada en la misma sesión del tag en producción según la cabecera, pasa completo; y un cambio provisional fuera de las zonas (p. ej. el texto de un botón de la ficha del paciente) hace fallar «escritorio …» en esa pantalla (control positivo; se anota aquí y se revierte); si aparece la diferencia intermitente de las pantallas de la asistente (roadmap), se repite la pasada una vez y se anota, y si se repite es un fallo (plan D13) · verificación manual: el usuario compara en el escritorio las capturas de antes y de después de «Agendar cita» y «Reprogramar» del paciente, y de la ficha del expediente si salió de la comparación, y confirma que solo cambian las migas y la posición de lo que hay debajo — cubre: CA12 — depende: T024, T030

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: en «Frontend», el parcial `partials/migas` y qué pantallas lo llevan; en `AGENTS.md` → «Reglas y estilo», una línea sobre las migas de las pantallas de segundo nivel — depende: T031
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: sin cambios en los dos (plan, Observabilidad y Rollout). Sí cambian: `docs/roadmap.md` (033/R6 hecha; en 033/R11, que la 046 ya prueba la entrada marcada en las 18 pantallas de segundo nivel y queda pendiente la condición repetida; en la nota de la 014, que al retomarla parta del parcial de migas de `pacientes/create`, `expedientes/show` y `expedientes/create` y lo conserve; una nota nueva: devolver a la comparación de `capturar.mjs` las pantallas que T032 sacó cuando la 046 esté en producción; y, en los pendientes de la diferencia intermitente de la asistente y de 035/R22, que la 046 tocó el guion sin atenderlos (plan D13)) y la tabla de deuda de `docs/design/system.md` (DS15: títulos hechos por la 046, favicon hecho por la ficha `2026-11-12-chore-favicon`, isotipo pendiente) — depende: T031
- [ ] T092 Marcar spec como `implemented` — depende: T090, T091

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097: como administración, el detalle de una cita, «Reprogramar» y «Editar servicio» muestran sus migas y vuelven a su listado; como paciente, «Agendar cita» no tiene migas
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): sin migraciones; vigilar la alerta de 5xx. Rollback: la imagen anterior
- [ ] T098 Marcar spec como `released`; en cada spec extendida (008, 009, 010, 028, 033, 034 y 035), una línea en su Historial con lo que la 046 cambia en sus pantallas (migas; 033/R6; 035 CA7 sin «Volver al paciente»; 033 CA8, 034 CA11 y 035 CA10 por CA12; títulos de la 009; «Cancelar» de editar usuario en la 010)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | T002, T022, T023, T024, T025, T026, T027, T028, T029, T031 |
| CA2 | T010 | T020, T022, T023, T025, T026, T027, T028, T029 |
| CA3 | T011 | T002, T020 |
| CA4 | T011 | T002, T021 |
| CA5 | T012 | T022, T023 |
| CA6 | T012 | T028 («Editar usuario»), T031 (los otros nueve formularios: caracterización, se comprueba rompiendo un destino) |
| CA7 | T012 | T026 |
| CA8 | T012 | T029 |
| CA9 | T012 | T030 |
| CA10 | T011 | T002, T021, T031 |
| CA11 | T013 | T020, T031 |
| CA12 | T032 (comparación del escritorio contra producción con zonas excluidas y control positivo), T031 | T032 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020 (el parcial solo recibe direcciones de `route()`), T022–T029 | T013 |
| TM2 | T020 | T013 |
| TM3 | T031 (sin código nuevo: el middleware y los Gates no cambian; rotura comprobada) | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `resources/views/partials/migas.blade.php` | T020 |
| `citas/create`, `personal/create`, `personal/show`, `usuarios/create`, `usuarios/show` | T022, T027, T028 |
| `citas/show`, `citas/cliente-show`, `pacientes/show`, `pacientes/create`, `expedientes/create`, `expedientes/show` | T022, T023, T025, T026 |
| `citas/reprogramar`, `citas/cliente-reprogramar` | T022, T023 |
| `servicios/create`, `servicios/edit` | T029 |
| `citas/cliente-agendar` | T024 |
| `layouts/app.blade.php` | T030 |
| `public/css/app.css` | T021 |
| `public/css/movil.css` | ninguna: sin cambios (plan D6) |
| `docs/design/capturar.mjs` | T032 |
| `tests/Feature/NavegacionInternaTest.php` | T010–T013 |
| `MovilPacienteTest`, `MovilPacientesExpedientesTest` | T021, T024, T014 |
| `MovilPersonalTest` | ninguna: su aserción ya acepta cualquier elemento con `id="migas"`; T022 comprueba que sigue pasando |
| `docs/design/system.md`, `docs/design/system.html` | T002, T091 |
| `docs/roadmap.md`, `docs/architecture.md`, `AGENTS.md` | T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Accesibilidad (WCAG 2.1 AA: navegación con nombre, página actual, foco, contraste, toque) | T011, T021, T031 (muestra de seis pantallas y `capturar.mjs --medir`), T032 |
| Solo el sistema de diseño (sin `style=""` en las migas) | T010, T002 |
| Descubribilidad | no aplica (todas las pantallas exigen sesión) |
