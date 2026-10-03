---
spec: 027-horarios-validos
plan: plan.md
status: approved
---

# Tareas · 027 Horario válido del dentista y aviso de citas afectadas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

## Constitution Check
| Principio | Resultado | Justificación | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 027 `approved`; estas tareas se aprueban y se analizan antes de `/implement`. | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | T010–T012 se escriben antes de implementar, y cada tarea de implementación depende de ellas. CA1–CA8 fallan hoy. CA9 es de caracterización: T010 lo comprueba quitando un momento `role:admin` (constitución 1.2.0). | test: notas de T010–T012 |
| P3 Autorización en servidor | ✅ | T021 crea el Gate `definir-horario`; T022 lo aplica en `saveHorarios` y en `show`. T010 prueba por rol el caso permitido y el denegado. | test: T010 |
| P4 Entrada validada y SQL con bindings | ✅ | T022 valida `dias` con claves 1–7, campos permitidos, `date_format:H:i`, `required_with` y `after`. T020 usa Query Builder. T040 ejecuta semgrep. | test: T010 · lint: semgrep en T040 |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. | lint: gitleaks en T040 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. | lint: composer audit y npm audit en T040 |
| P7 Auditoría de datos sensibles | ➖ | El horario es un dato operativo (plan, P7). | — |
| P8 Correlación y logs limpios | ✅ | Ninguna tarea añade llamadas a `Log::`. | manual: T040 revisa el diff |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 incorpora al sistema lo que falta antes del código; T003 lo lleva a `app.css`; T023 construye las piezas nuevas solo con tokens y clases del sistema. | test: T012 (`test_las_piezas_nuevas_solo_usan_tokens`) · manual: verificación manual de T023 |

## Preparación
- [ ] T001 Incorporar al sistema de diseño los "Cambios a incorporar al sistema" del plan: escala `--space-1` … `--space-8` (4, 6, 8, 10, 14, 16, 20 y 28 px), `.field-hint` y `.field-hint-error` como clases comunes, el componente "Lista de citas" (`.lista-citas`) y el anillo `--focus-ring` (`--p600`, 7.00:1); `system.md` pasa a la versión 1.1.0 con su fila en "Decisiones" (tipo `diseño`, fuente `usuario`), y DS2, DS4 y DS5 anotan qué queda atendido; `system.html` muestra la escala y la lista con el anillo de foco — docs/design/system.md, docs/design/system.html — hecho cuando: el validador da 0 errores en `docs/design` y `system.html` sigue sin recursos externos (0 `http`)
- [ ] T002 Registrar la línea base en `feature/027-horarios-validos`: recuento de `php artisan test`; que hoy un día marcado sin horas se guarda como 09:00–18:00; que hoy se guarda un horario para un asistente; y que `pint --test` falla en `PersonalController.php` — docs/specs/027-horarios-validos/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cuatro datos
- [ ] T003 Llevar a `app.css` los cambios de T001: tokens `--space-*` y `--focus-ring` en `:root`, `.field-hint` y `.field-hint-error` movidas desde `citas.css` con los mismos valores, `.lista-citas` y `:focus-visible` con `--focus-ring` para `.lista-citas a` y `.horario-dia input`, todo solo con tokens; quitar esas dos clases de `citas.css` — public/css/app.css, public/css/citas.css — hecho cuando: ningún valor existente de `:root` cambia (diff de `app.css` solo con añadidos) y la suite da la línea base de T002 · verificación manual: una vista de citas (`citas/create` con un error de validación) se ve igual que antes a 1440 px — depende: T001, T002
- [ ] T004 Formatear con Pint `PersonalController.php` sin cambiar la lógica — app/Http/Controllers/PersonalController.php — hecho cuando: `pint --test` pasa en ese archivo y la suite da la línea base de T002 — depende: T002

## Tests (antes de implementar)
- [ ] T010 [P] Escribir `HorarioDentistaTest` con los 9 tests del plan: `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` (guarda, vuelve a enviar el formulario tal como lo muestra la ficha y sigue igual), `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` (18:00–09:00 y 22:00–02:00, mensaje exacto bajo el día, horario anterior intacto), `test_un_horario_manipulado_se_rechaza_sin_error_500` (día 0 y 8, `25:99`, texto, listas), `test_un_dia_marcado_sin_horas_se_rechaza`, `test_un_horario_sin_dias_se_guarda_con_aviso` (mensaje exacto), `test_no_se_guarda_horario_para_quien_no_es_dentista` (asistente, administrador, id inexistente → 404 sin filas), `test_solo_la_administracion_guarda_horarios` (dentista, asistente y paciente → 403), `test_el_horario_bloquea_al_dentista_antes_de_borrar` (`DB::listen`) y `test_un_bloqueo_agotado_no_da_error_500` (conexión simulada con 1205/1213 sobre `HorarioDentista::guardar`) — tests/Feature/HorarioDentistaTest.php — hecho cuando: fallan por la razón esperada (hoy se guarda sin validar, con horas por defecto y para cualquier personal, sin bloqueo, y `HorarioDentista` no existe); `test_solo_la_administracion_guarda_horarios` pasa y se comprueba quitando un momento `role:admin` del grupo de rutas (falla; restaurado y anotado) — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA9 — depende: T002
- [ ] T011 [P] Escribir `CitasFueraDelHorarioTest` con los 7 tests del plan: `test_la_ficha_lista_las_citas_futuras_fuera_del_horario` (una en un día que se quita, otra que empieza antes y otra que termina después: paciente, fecha, hora, estado y enlace en la sección `citas-fuera`, y las citas sin cambios), `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera`, `test_sin_citas_fuera_no_hay_lista`, `test_las_citas_pasadas_o_cerradas_no_aparecen`, `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` (mismos bordes que `ReglaAgendaTest`), `test_solo_aparecen_citas_del_dentista` y `test_el_nombre_del_paciente_se_muestra_escapado`; reloj en UTC con `Carbon::setTestNow` y citas a hora local — tests/Feature/CitasFueraDelHorarioTest.php — hecho cuando: fallan por la razón esperada (hoy no hay sección `citas-fuera` ni recuento en el mensaje); `test_sin_citas_fuera_no_hay_lista` pasa hoy y se comprueba haciendo que la ficha muestre un momento la sección vacía (falla; restaurado y anotado) — cubre: CA7, CA8 — depende: T002
- [ ] T012 [P] Escribir `HorarioDentistaVistasTest`: `test_la_ficha_solo_ofrece_horario_a_los_dentistas` (sección `horario` en la ficha de un dentista; ausente en la de un asistente y la de un administrador), `test_los_errores_de_un_dia_se_asocian_a_sus_campos` (tras un error, el campo tiene `aria-invalid="true"` y `aria-describedby` apunta al `id` del mensaje de ese día, y hay un resumen con `role="alert"`) y `test_las_piezas_nuevas_solo_usan_tokens` (el parcial `personal/partials/citas-fuera.blade.php` no tiene `style=`, y las reglas `.lista-citas` y `:focus-visible` nuevas de `app.css` no tienen colores hex/rgb ni px) — tests/Feature/HorarioDentistaVistasTest.php — hecho cuando: fallan por la razón esperada (hoy la ficha de un asistente tiene horario, los errores no existen y el parcial no existe) — cubre: CA6, CA2 — depende: T002

## Implementación
- [ ] T020 Crear `HorarioDentista` (constructor con `ConnectionInterface`; `guardar` en una transacción con `lockForUpdate()` de `personal`, después borrar e insertar con `disponible = 1`, devolviendo el número de citas fuera; 1205/1213 y `DeadlockException` → `HorarioNoGuardado::ocupado()`, el resto se relanza; `citasFuera` con la regla y los bordes de `ReservaCitas`, solo `agendada`/`confirmada` que aún no empiezan en `clinica.zona_horaria`, con paciente, fecha, hora y estado, ordenadas) y `HorarioNoGuardado` con su mensaje — app/Agenda/HorarioDentista.php, app/Agenda/HorarioNoGuardado.php — hecho cuando: pasan `test_el_horario_bloquea_al_dentista_antes_de_borrar` y `test_un_bloqueo_agotado_no_da_error_500` llamando al servicio directamente, y `pint --test` pasa en los dos archivos — cubre: CA1, CA7, CA8 — depende: T010, T011
- [ ] T021 Crear el Gate `definir-horario` (`GestionPersonal::definirHorario`: admin y tipo dentista → permitir; admin con otro tipo → `denyAsNotFound`; otro rol → `deny`) y registrarlo en `AppServiceProvider::boot()` — app/Autorizacion/GestionPersonal.php, app/Providers/AppServiceProvider.php — hecho cuando: `Gate::has('definir-horario')` es verdadero y `pint --test` pasa en los dos archivos — cubre: CA6, CA9 — depende: T010
- [ ] T022 Cambiar `PersonalController`: `saveHorarios(Request, string $id, HorarioDentista)` con 404 → Gate `definir-horario` → validación con las reglas y mensajes de Contratos → franjas solo de los días con `activo` → `guardar` → mensaje de Contratos (con recuento o aviso de horario vacío), y `HorarioNoGuardado` → `back()->withInput()->with('error', …)`; `show` pasa `$puedeDefinirHorario` (`Gate::allows`) y `$citasFuera` solo en ese caso — app/Http/Controllers/PersonalController.php — hecho cuando: pasa todo `HorarioDentistaTest` y los tests de `CitasFueraDelHorarioTest` que no miran el HTML (`test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera` y el estado sin cambios de las citas), y `pint --test` pasa — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA9 — depende: T004, T020, T021
- [ ] T023 Cambiar la ficha del personal, revisándola antes a mano contra `system.html` (la skill `design` no está instalada): sección "Horarios de atención" solo con `$puedeDefinirHorario`; horas en `H:i` con `old()`; por día, `@error` de `hora_inicio` y `hora_fin` con `field-hint field-hint-error` e `id`, `aria-describedby` y `aria-invalid` en los campos y clase `horario-dia` en la fila; resumen `role="alert"` si hay errores en `dias`; `@include` del parcial nuevo `citas-fuera` con `.lista-citas`, `partials/badge-estado` y el enlace "Ver cita" `.btn btn-outline btn-sm` a `admin.citas.show`, sin `style=` — resources/views/personal/show.blade.php, resources/views/personal/partials/citas-fuera.blade.php — hecho cuando: pasan todo `CitasFueraDelHorarioTest` y `HorarioDentistaVistasTest`, y la suite completa no tiene fallos nuevos · verificación manual: ficha de un dentista a 1440 px con un error de franja y con citas fuera, contra `docs/design/system.html` (contraste de los pares usados y foco visible con teclado en los campos y en "Ver cita") — cubre: CA1, CA2, CA4, CA6, CA7, CA8 — depende: T003, T022

## Integración y documentación
- [ ] T040 Verificación final — (sin archivos) — hecho cuando:
  - `php artisan test` completo da la línea base de T002 más los tests de T010–T012, todos en verde (incluidos `ReglaAgendaTest` y `ReservaConcurrenteTest`);
  - `vendor/bin/pint --test` pasa sobre todos los archivos PHP tocados;
  - semgrep `p/php --metrics=off` sobre los archivos de `app/` tocados no da hallazgos;
  - gitleaks sobre el rango de la spec no da hallazgos;
  - composer audit `--locked` y npm audit dan 0 altas o críticas;
  - el diff no añade llamadas a `Log::`.

  — cubre: CA1, CA7
- [ ] T041 Anotar en `docs/roadmap.md → Pendientes y deuda` que la deuda 025/R12 (franjas que cruzan la medianoche) queda resuelta por la 027 (CA2), y añadir la viñeta "extraer la regla de franja común a `ReservaCitas` y `HorarioDentista::citasFuera` (spec 013)" — docs/roadmap.md — hecho cuando: las dos viñetas existen y citan la spec 027
- [ ] T090 Actualizar `docs/architecture.md`:
  - fila "Agenda (horario)" con `app/Agenda/HorarioDentista.php`;
  - fila "Autorización de citas" con `GestionPersonal` y el Gate `definir-horario`;
  - en "Personal", que el horario lo guarda el servicio.
- [ ] T091 Actualizar `docs/security.md`, por corrección:
  - RS12.b → "en curso (spec 027)";
  - RS12.c sigue "pendiente", y su nota añade "horario de dentista: spec 027";
  - "Dónde se aplica" gana el Gate `definir-horario`.

  `docs/deployment.md` y `docs/observability.md` no cambian.
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T040 | T020, T022, T023 |
| CA2 | T010, T012 | T022, T023 |
| CA3 | T010 | T022 |
| CA4 | T010 | T022, T023 |
| CA5 | T010 | T022 |
| CA6 | T010, T012 | T021, T022, T023 |
| CA7 | T011, T040 | T020, T022, T023 |
| CA8 | T011 | T020, T023 |
| CA9 | T010 | T021, T022 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T022 | T010 |
| TM2 | T021, T022 | T010 |
| TM3 | T021, T022 | T010 |
| TM4 | T022 | T010 |
| TM5 | T020 | T010 |
| TM6 | T020 | T011 |
| TM7 | T023 | T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Agenda/HorarioDentista.php | T020 |
| app/Agenda/HorarioNoGuardado.php | T020 |
| app/Autorizacion/GestionPersonal.php | T021 |
| app/Providers/AppServiceProvider.php | T021 |
| app/Http/Controllers/PersonalController.php | T004, T022 |
| resources/views/personal/show.blade.php | T023 |
| resources/views/personal/partials/citas-fuera.blade.php | T023 |
| public/css/app.css | T003 |
| public/css/citas.css | T003 |
| docs/design/system.md, docs/design/system.html | T001 |
| tests/Feature/HorarioDentistaTest.php | T010 |
| tests/Feature/CitasFueraDelHorarioTest.php | T011 |
| tests/Feature/HorarioDentistaVistasTest.php | T012 |
| docs/architecture.md | T090 |
| docs/security.md | T091 |
| docs/roadmap.md | T041 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Mensajes en español que dicen qué corregir | T010 (texto exacto de CA2, CA3, CA4 y CA5) |
| WCAG 2.1 AA en lo nuevo o modificado | T012 (errores asociados y solo tokens) · verificación manual de T023 |
