---
spec: 046-navegacion-interna
plan: plan.md
status: draft
---

# Tareas · 046 Navegación interna

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- **Corrección tras `/analyze` r1 (2026-11-12):** `/tasks --fix` editó en su lugar T010–T013, T020–T023, T025, T026, T028–T031, T091 y T098 y añadió T032 (A1–A7, A9, A10, A12, A14–A16). No se renumeró ninguna.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

## Constitution Check
Contra `docs/constitution.md` 1.3.0; el análisis completo está en `plan.md`.

| Principio | Resultado | En las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Ninguna tarea de código antes de aprobar este archivo; T001 espera a la 045 (plan D7) |
| P2 Test que falla antes y pasa después | ✅ | T010–T013 se escriben antes de T020–T030 (cada tarea de implementación depende del test que hace pasar) y fallan por la razón esperada; las caracterizaciones (CA6 en nueve formularios en T012, TM3 en T013) se comprueban rompiendo el código que protegen y se anota en la tarea |
| P3 Autorización en el servidor | ✅ | Ninguna tarea toca rutas, middleware ni Gates; T013 lo caracteriza |
| P4 Entrada validada y SQL con bindings | ✅ | T020 pinta con salida escapada; T013 lo prueba; semgrep en T031 |
| P5 Nada sensible en el repositorio | ✅ | Datos sintéticos en T010; `gitleaks` en T031 |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas; `composer audit` y `npm audit` en T031 |
| P7 Auditoría | ➖ | Sin eventos nuevos (plan) |
| P8 Correlación y logs | ➖ | Sin logs nuevos (plan) |
| P9 Solo el sistema de diseño | ✅ | T002 actualiza el sistema antes de cualquier tarea de interfaz; T011 prueba reglas y contraste; ninguna tarea añade `style=""` |
| P10 Rutas indexables | ➖ | Sin rutas públicas (plan) |

## Preparación
- [ ] T001 Traer a la rama `main` con la spec 045 ya fusionada (plan D7) — sin archivos propios (fusión de `origin/main` en `feature/046-navegacion-interna`) — hecho cuando: `git log` de la rama contiene la fusión de la 045 y `php artisan test` pasa completo
- [ ] T002 Actualizar «Migas de pan» y las reglas «dónde se usan las migas» y «título de pantalla» del acta (`design.md` → «Cambios a incorporar al sistema»), con la versión siguiente a la vigente y su fila en Decisiones — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `system.md` describe la navegación con lista, el enlace en `--p600` subrayado, el elemento actual en `--text` y el camino que pasa de línea, `system.html` lo muestra y `python .ai/bin/aidd.py validate` no da errores — cubre: CA1, CA3, CA4, CA10 — depende: T001

## Tests (antes de implementar)
- [ ] T010 Crear `NavegacionInternaTest` con el escenario (cuentas de los cuatro roles, paciente, cita reprogramable y no reprogramable, expediente, servicio, como en `MovilPersonalTest`), la tabla `[rol, dirección, camino esperado]` de las 18 pantallas, solo con las combinaciones de rol y dirección que existen en `routes/web.php` y con el primer elemento igual al texto del menú de cada rol, y los tests `test_cada_pantalla_de_segundo_nivel_lleva_sus_migas`, `test_las_pantallas_del_menu_no_llevan_migas` y `test_los_enlaces_de_las_migas_llevan_a_destinos_fijos` — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: los tres fallan porque faltan la `nav#migas`, sus caminos o sobran las migas de «Agendar cita» — cubre: CA1, CA2 — depende: T001
- [ ] T011 Añadir `test_las_migas_son_una_navegacion_accesible`, `test_las_reglas_de_las_migas_distinguen_el_enlace`, `test_el_contraste_de_las_migas_es_aa` y `test_las_migas_pueden_pasar_de_linea` (con `Concerns/ReglasCss`); el de navegación comprueba además que todos los hijos del `ol` son `li` y que hay n−1 separadores con `aria-hidden`; el de salto de línea mira las reglas de `app.css` — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: fallan por la estructura y las reglas actuales (sin `aria-label`, enlace sin subrayar en `--text-muted`, salto de línea solo en `movil.css`); el de contraste pasa ya si `--p600` sobre `--bg` da ≥ 4,5:1 y se anota el valor medido — cubre: CA3, CA4, CA10 — depende: T010
- [ ] T012 Añadir `test_reprogramar_lleva_migas_se_pueda_o_no`, `test_cancelar_lleva_al_mismo_sitio_que_las_migas`, `test_el_expediente_ya_no_tiene_volver_al_paciente`, `test_ninguna_pantalla_usa_el_titulo_por_defecto` y `test_el_menu_marca_la_seccion_de_la_pantalla`; el de «Cancelar» recorre los diez formularios de CA6 sin contar «Cancelar la cita definitivamente»; el del menú compara destino y texto de la entrada marcada con el primer elemento de las migas, por rol — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: fallan por la razón esperada (reprogramar sin migas, botón presente, título «Dashboard» en servicios, expedientes sin «Pacientes» marcado, «Cancelar» de «Editar usuario» al listado); el de «Cancelar» es de caracterización en los otros nueve formularios y se anota en T031 `comprobado rompiendo` el destino de uno de ellos — cubre: CA5, CA6, CA7, CA8, CA9 — depende: T011
- [ ] T013 Añadir los tests de abuso `test_las_migas_ignoran_un_destino_de_vuelta_en_la_direccion` (TM1), `test_el_nombre_con_codigo_se_pinta_como_texto` (TM2) y `test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` (TM3, caracterización; incluye a un miembro del personal abriendo el detalle de cita del paciente: «no encontrada», sin migas ni nombres) — `tests/Feature/NavegacionInternaTest.php` — hecho cuando: TM1 y TM2 fallan porque aún no hay `nav#migas` que inspeccionar en todas las pantallas, y TM3 pasa; su comprobación rompiendo el middleware de rol se anota en T031 — cubre: CA11 — depende: T012
- [ ] T014 Dejar de fijar el elemento `div` en el test de migas de la 035: `//div[@id='migas']…` pasa a `//*[@id='migas']…` (el elemento lo fija ahora T010) — `tests/Feature/MovilPacientesExpedientesTest.php` — hecho cuando: `MovilPacientesExpedientesTest` pasa antes y después de cambiar el marcado — depende: T001

## Implementación
- [ ] T020 Crear el parcial de migas: `nav#migas.migas` con `aria-label="Migas de pan"`, lista ordenada, enlaces, separadores `span.migas-sep` con `aria-hidden="true"` dentro de cada `li` menos el primero, y el último elemento `li.migas-actual` con `aria-current="page"`, todo con salida escapada (plan, «Contratos y datos») — `resources/views/partials/migas.blade.php` — hecho cuando: el parcial existe y se puede incluir con una lista de uno a tres elementos — cubre: CA2, CA3, CA11 — depende: T002, T010, T011, T013
- [ ] T021 Cambiar las reglas de las migas en `app.css`: fila flexible en el `ol` sin viñetas ni sangría, con `flex-wrap` y `gap: var(--space-3)`; `li` en fila con su separador; enlace en `--p600` subrayado y `--p800` al pasar el puntero; actual en `--text` con `min-width: 0`; `overflow-wrap: anywhere`; `movil.css` no se toca (plan D6); y actualizar las aserciones de reglas de `.migas` y `.migas a` de la 033 — `public/css/app.css`, `tests/Feature/MovilPacienteTest.php` — hecho cuando: los tests de T011 que dependen de las reglas pasan, `MovilPacienteTest` sigue pasando en lo que no es «Agendar cita» y `MovilPacientesExpedientesTest` pasa sin tocarlo — cubre: CA4, CA10 — depende: T011, T020
- [ ] T022 Usar el parcial en las pantallas de citas del personal: «Citas › Nueva cita», «Citas › *paciente*» y «Citas › *paciente* › Reprogramar» (antes del parcial de reprogramar; la vista calcula el prefijo del rol como `citas/show`), con «Mis Citas» como primer elemento para la dentista — `resources/views/citas/create.blade.php`, `resources/views/citas/show.blade.php`, `resources/views/citas/reprogramar.blade.php` — hecho cuando: las filas de esas pantallas en los tests de T010 y el caso del personal de `test_reprogramar_lleva_migas_se_pueda_o_no` pasan, y `MovilPersonalTest` sigue pasando — cubre: CA1, CA2, CA5 — depende: T012, T020, T021
- [ ] T023 [P] Usar el parcial en el detalle y en reprogramar del paciente: «Mis Citas › Detalle» y «Mis Citas › Detalle › Reprogramar» — `resources/views/citas/cliente-show.blade.php`, `resources/views/citas/cliente-reprogramar.blade.php` — hecho cuando: sus filas de T010 y el caso del paciente de `test_reprogramar_lleva_migas_se_pueda_o_no` pasan — cubre: CA1, CA2, CA5 — depende: T012, T020, T021
- [ ] T024 Quitar las migas de «Agendar cita» y sacarla del bucle de pantallas con migas del test de la 033 — `resources/views/citas/cliente-agendar.blade.php`, `tests/Feature/MovilPacienteTest.php` — hecho cuando: `test_las_pantallas_del_menu_no_llevan_migas` pasa para «Agendar cita» y `MovilPacienteTest` pasa completo — cubre: CA1 — depende: T021, T023
- [ ] T025 [P] Usar el parcial en la ficha, el alta y la edición del paciente: «Pacientes › *paciente*», «Pacientes › Nuevo paciente» y «Pacientes › *paciente* › Editar» — `resources/views/pacientes/show.blade.php`, `resources/views/pacientes/create.blade.php` — hecho cuando: sus filas de T010 pasan en los roles que abren cada pantalla y `MovilPacientesExpedientesTest` sigue pasando — cubre: CA1, CA2 — depende: T014, T020, T021
- [ ] T026 [P] Usar el parcial en el expediente y en el alta de expediente, y quitar el enlace «← Volver al paciente» de la cabecera del expediente — `resources/views/expedientes/create.blade.php`, `resources/views/expedientes/show.blade.php` — hecho cuando: sus filas de T010 y `test_el_expediente_ya_no_tiene_volver_al_paciente` pasan, y `MovilPacientesExpedientesTest` sigue pasando — cubre: CA1, CA2, CA7 — depende: T012, T014, T020, T021
- [ ] T027 [P] Sustituir las migas con `style=""` de personal por el parcial: «Personal › Nuevo miembro», «Personal › *miembro*» y «Personal › *miembro* › Editar» — `resources/views/personal/create.blade.php`, `resources/views/personal/show.blade.php` — hecho cuando: sus filas de T010 pasan — cubre: CA1, CA2 — depende: T020, T021
- [ ] T028 [P] Sustituir las migas con `style=""` de usuarios por el parcial: «Usuarios › Nuevo usuario», «Usuarios › *usuario*» y «Usuarios › *usuario* › Editar»; en edición, «Cancelar» pasa a llevar a la ficha del usuario (en alta sigue en el listado) — `resources/views/usuarios/create.blade.php`, `resources/views/usuarios/show.blade.php` — hecho cuando: sus filas de T010 y `test_cancelar_lleva_al_mismo_sitio_que_las_migas` pasan — cubre: CA1, CA2, CA6 — depende: T012, T020, T021
- [ ] T029 [P] Dar título y migas a servicios: «Nuevo servicio» con «Servicios › Nuevo servicio» y «Editar servicio» con «Servicios › Editar servicio» — `resources/views/servicios/create.blade.php`, `resources/views/servicios/edit.blade.php` — hecho cuando: sus filas de T010 y `test_ninguna_pantalla_usa_el_titulo_por_defecto` pasan — cubre: CA1, CA2, CA8 — depende: T012, T020, T021
- [ ] T030 Marcar «Pacientes» en el menú también en `admin/expedientes*`, `asistente/expedientes*` y `dentista/expedientes/*`, y «Expedientes» de la dentista solo en su listado — `resources/views/layouts/app.blade.php` — hecho cuando: `test_el_menu_marca_la_seccion_de_la_pantalla` pasa con una sola entrada marcada en las 18 pantallas; la medición en el navegador se hace en T032 — cubre: CA9 — depende: T012, T022, T023, T025, T026, T027, T028, T029
- [ ] T031 Verificación final: suite completa, `vendor/bin/pint --test` sobre lo tocado, `gitleaks detect --no-banner`, semgrep sobre lo tocado, `composer audit` y `npm audit --audit-level=high`; `NavegacionInternaTest` dos veces; `node docs/design/capturar.mjs --medir` completo con la línea base de T032; y las dos caracterizaciones comprobadas rompiendo un momento el destino de un «Cancelar» (CA6) y el middleware de rol (TM3) — sin archivos propios — hecho cuando: todo pasa, T013 queda en verde completo y se anotan aquí las dos roturas comprobadas · verificación manual: el usuario ve a 320, 390 y 1440 px, sin desplazamiento horizontal y con las migas visibles sin desplazarse, la muestra del plan: «Nueva cita», la ficha del expediente, «Editar personal» y «Editar usuario» con un nombre largo y «Reprogramar» del personal después de un rechazo (con aviso arriba), contra `docs/design/system.html` — cubre: CA1, CA6, CA10, CA11 — depende: T013, T024, T030, T032
- [ ] T032 Tomar la línea base nueva de `capturar.mjs` (CA12): ejecutar `node docs/design/capturar.mjs --medir` contra la línea base anterior y anotar aquí qué pantallas fallan en la comparación de escritorio; deben ser solo las que tienen migas, el expediente y «Agendar cita», y solo por las migas, el botón «Volver al paciente» y la entrada marcada; después, `--linea-base` con la interfaz de la 046 — sin archivos del repositorio (la línea base vive fuera, procedimiento en la cabecera de `docs/design/capturar.mjs`) — hecho cuando: la lista anotada no tiene ninguna diferencia ajena a esas tres y `--medir` pasa completo con la línea base nueva · verificación manual: el usuario valida las capturas nuevas de escritorio antes de sustituir la línea base — cubre: CA12 — depende: T024, T030

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: en «Frontend», el parcial `partials/migas` y qué pantallas lo llevan; en `AGENTS.md` → «Reglas y estilo», una línea sobre las migas de las pantallas de segundo nivel — depende: T031
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: sin cambios en los dos (plan, Observabilidad y Rollout). Sí cambian: `docs/roadmap.md` (033/R6 hecha) y la tabla de deuda de `docs/design/system.md` (DS15: títulos hechos por la 046, favicon hecho por la ficha `2026-11-12-chore-favicon`, isotipo pendiente) — depende: T031
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
| CA12 | T032 (comparación contra la línea base anterior) | T032 |

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
| Línea base de `docs/design/capturar.mjs` | T032 |
| `tests/Feature/NavegacionInternaTest.php` | T010–T013 |
| `MovilPacienteTest`, `MovilPacientesExpedientesTest` | T021, T024, T014 |
| `MovilPersonalTest` | ninguna: su aserción ya acepta cualquier elemento con `id="migas"`; T022 comprueba que sigue pasando |
| `docs/design/system.md`, `docs/design/system.html` | T002, T091 |
| `docs/roadmap.md`, `docs/architecture.md`, `AGENTS.md` | T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Accesibilidad (WCAG 2.1 AA: navegación con nombre, página actual, foco, contraste, toque) | T011, T021, T031 (muestra de cinco pantallas y `capturar.mjs --medir`), T032 |
| Solo el sistema de diseño (sin `style=""` en las migas) | T010, T002 |
| Descubribilidad | no aplica (todas las pantallas exigen sesión) |
