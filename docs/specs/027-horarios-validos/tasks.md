---
spec: 027-horarios-validos
plan: plan.md
status: approved
impl_base: ebe3bc1
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
| P2 Test que falla antes y pasa después | ✅ | T010–T012 se escriben antes de implementar, y cada tarea de implementación depende de ellas; T003 (tokens y componentes en `app.css`) también depende de T012, para que los tests de tokens y de contraste se vean fallar antes (A21). CA1–CA8 fallan hoy. CA9 es de caracterización: T010 lo comprueba quitando un momento `role:admin` (constitución 1.2.0). | test: notas de T010–T012 |
| P3 Autorización en servidor | ✅ | T021 crea el Gate `definir-horario`; T022 lo aplica en `saveHorarios` y en `show`. T010 prueba por rol el caso permitido y el denegado al guardar, y T012 en la ficha, que ahora muestra datos de pacientes (A7). | test: T010, T012 |
| P4 Entrada validada y SQL con bindings | ✅ | T022 valida `dias` con claves 1–7, campos permitidos, `date_format:H:i`, `required_with` y la regla `HoraFinPosterior` (no `after:`, que da 500 con listas, A5). T020 usa Query Builder. T040 ejecuta semgrep. | test: T010 · lint: semgrep en T040 |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. | lint: gitleaks en T040 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. | lint: composer audit y npm audit en T040 |
| P7 Auditoría de datos sensibles | ➖ | El horario es un dato operativo (plan, P7). | — |
| P8 Correlación y logs limpios | ✅ | Ninguna tarea añade llamadas a `Log::`. | manual: T040 revisa el diff |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 incorpora al sistema lo que falta antes del código (incluidos grosores, borde de control y tamaño del aviso); T003 lo lleva a `app.css`; T023 construye las piezas nuevas o modificadas solo con tokens y clases del sistema, y quita los estilos en línea de la sección de horario (A24). Los umbrales de contraste los comprueba un test, no solo la revisión a mano (A8). | test: T012 (`test_las_piezas_nuevas_solo_usan_tokens`, `test_los_pares_de_contraste_cumplen_aa`) · manual: verificación manual de T023 (foco con teclado y aspecto) |

## Preparación
- [x] T001 Incorporar al sistema de diseño los "Cambios a incorporar al sistema" del plan: escala `--space-1` … `--space-8` (4, 6, 8, 10, 14, 16, 20 y 28 px); grosores `--border-width` (1px), `--focus-ring-width` (2px) y `--focus-ring-offset` (2px); `--text-body` (13.5px) y `--text-small` (11.5px), los nombres que ya usa `system.html` (A25); `--field-time-width` (110px); borde de control `--border-control` (`--p400`, 3.93:1 sobre `--white` y 3.67:1 sobre `--p50`, en esta spec solo para los campos de hora del horario); `.field-hint` y `.field-hint-error` como clases comunes; el componente "Lista de citas" (`.lista-citas`), el componente "Día del horario" (`.horario-dia`, `--activo`, `__nombre`, `__horas`, `__nota` y su campo de hora, con los valores del plan → D13) y el anillo `--focus-ring` (`--p600`, 7.00:1). `system.md` pasa a la versión 1.1.0 con su fila en "Decisiones" (tipo `diseño`, fuente `usuario`), y DS2, DS4 y DS5 anotan qué queda atendido. `system.html` muestra la escala, el campo con el borde de control, un día del horario activo e inactivo y la lista con el anillo de foco — docs/design/system.md, docs/design/system.html — hecho cuando: el validador da 0 errores en `docs/design` y `system.html` sigue sin recursos externos (0 `http`)
  - nota: A9 — un solo token de foco: el rol `--color-focus` pasa a `--focus-ring` (`--p600`, 7.00:1) en `system.md` y `system.html`; `--p300` queda como "foco antiguo" del código previo (DS2), sin uso en pantallas nuevas.
  - nota: A17 — `system.md` → Espaciado justifica la escala: valores más usados, más 4px (ayuda de campo) y 28px (página); 12 y 18px fuera para no tener pasos de 2px.
  - nota: A34 — `--weight-regular` y `--weight-strong` pasan a `system.md` (Tipografía); en `system.html` ya existían.
  - nota: A35 — `system.md` → Tipografía aclara que `--text-body` y `--text-small` son tamaños y `--text`, `--text-muted` y `--text-light` colores.
  - nota: `system.html`: tokens `--color-border-control`, `--color-border-active` (`--p200`), `--color-focus-legacy`, `--focus-ring-width/offset` y `--field-time-width`; demos de "Día del horario" y "Lista de citas"; 7 pares de contraste nuevos. `aidd.py validate` sin avisos de diseño y 0 `http`.
- [x] T002 Registrar la línea base en `feature/027-horarios-validos`: recuento de `php artisan test`; que hoy un día marcado sin horas se guarda como 09:00–18:00; que hoy se guarda un horario para un asistente; y que `pint --test` falla en `PersonalController.php` — docs/specs/027-horarios-validos/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cuatro datos
  - nota: línea base del 2026-10-03 (`impl_base` ebe3bc1): `php artisan test` → 127 tests en verde (788 aserciones, ~30 s).
  - nota: hoy un día marcado sin horas se guarda como 09:00–18:00 (`PersonalController::saveHorarios`, `?? '09:00'` y `?? '18:00'`), y no se valida nada de `dias`.
  - nota: hoy se guarda un horario para cualquier persona de `personal` (solo se comprueba que el id exista; no se mira `tipo`).
  - nota: `pint --test app/Http/Controllers/PersonalController.php` falla: 1 problema (`single_space_around_construct`).
- [x] T003 Llevar a `app.css` los cambios de T001: en `:root`, `--space-*`, `--border-width`, `--focus-ring`, `--focus-ring-width`, `--focus-ring-offset`, `--border-control`, `--text-body`, `--text-small` y `--field-time-width`; `.field-hint` (`font-size: var(--text-small)`, `margin-top: var(--space-1)`) y `.field-hint-error` movidas desde `citas.css` con los mismos valores; `.lista-citas` con borde `var(--border-width) solid var(--border)`; el componente "Día del horario" con las reglas de la fila `app.css` del plan (`.horario-dia`, `.horario-dia--activo`, `__nombre`, `__horas`, `__nota`, la casilla y `.horario-dia .field` con `--field-time-width` y `border-color: var(--border-control)`); `:focus-visible` con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset` para `.lista-citas a` y `.horario-dia input`. Ninguna regla nueva o movida usa px, hex ni rgb fuera de `:root`. Quitar esas dos clases de `citas.css` — public/css/app.css, public/css/citas.css — hecho cuando: ningún valor existente de `:root` cambia (diff de `app.css` solo con añadidos), la parte CSS de `test_las_piezas_nuevas_solo_usan_tokens` y `test_los_pares_de_contraste_cumplen_aa`, que fallaban en T012, pasan, y la suite da la línea base de T002 · verificación manual: una vista de citas (`citas/create` con un error de validación) se ve igual que antes a 1440 px — depende: T001, T002, T012
  - nota: `:root` solo gana líneas (diff sin `-`); los tokens nuevos llevan su valor del plan, y `--text-body`/`--text-small` se comentan como tamaños (A35).
  - nota: A9 — un solo token de foco en `app.css`: `--focus-ring` (`--p600`); no se añade `--color-focus` (es el nombre de rol de `system.md`/`system.html`).
  - nota: A31 — clases para cada parte de la fila: `.horario-dia__dia` (etiqueta con la casilla, `min-width: var(--field-time-width)`), `.horario-dia__nota` para el separador "a", la duración y "No disponible" (sin el margen de 4px); los mensajes de error ocupan su propia línea en la fila (`flex-basis: 100%`).
  - nota: A32 — `test_las_reglas_css_nuevas_solo_usan_tokens` y `test_los_pares_de_contraste_cumplen_aa` pasan; suite: 131 en verde (127 de la línea base + 4 de la 027) y 19 fallos, todos de tests nuevos de la 027 que dependen de T020–T023.
  - nota: A18 — la comparación con la línea base excluye los tests nuevos de la 027: los 127 tests previos siguen en verde.
  - nota: A34 — `--weight-regular`/`--weight-strong` en `:root` y en `.horario-dia__nombre`.
  - nota: verificación manual: comprobada por código y aceptada por el usuario el 2026-10-03 — `.field-hint`/`.field-hint-error` tienen las mismas declaraciones, `app.css` carga antes que `citas.css` y ninguna otra regla las sobrescribe; las vistas que usan la clase cargan `citas.css` o la redefinen en línea con los mismos valores, y `auth/register` no usa `app.css`.
- [x] T004 Formatear con Pint `PersonalController.php` sin cambiar la lógica — app/Http/Controllers/PersonalController.php — hecho cuando: `pint --test` pasa en ese archivo y la suite da la línea base de T002 — depende: T002
  - nota: solo formato (espacios, llaves de los `if` de una línea, orden de `use`); `git diff -w` no muestra cambios de lógica. `pint --test` pasa en el archivo.
  - nota: A18 — suite tras el formateo: 127 en verde, igual que la línea base de T002 (aún no hay tests nuevos de la 027).

## Tests (antes de implementar)
- [x] T010 [P] Escribir `HorarioDentistaTest` con los tests del plan: `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` (guarda 2 días, la ficha muestra `value="09:00"` y no `09:00:00`, y al reenviar el formulario tal como lo muestra la ficha sigue igual), `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` (18:00–09:00 y 22:00–02:00, mensaje exacto bajo el día, horario anterior intacto), `test_un_horario_manipulado_se_rechaza_sin_error_500` (día 0 y 8, `25:99`, texto, listas; de forma explícita, una lista en `hora_inicio` con `hora_fin` válida (A5) y `hora_inicio = "25:99"` con `hora_fin` válida, que solo da el error de formato; siguiendo la redirección, "El horario enviado no es válido." dentro del resumen `role="alert"` (A10); en **cada** variante se sigue la redirección y la ficha responde 200, también con listas en `hora_inicio`, `hora_fin` y `activo` del día 1 (A22)), `test_un_dia_marcado_sin_horas_se_rechaza`, `test_un_horario_sin_dias_se_guarda_con_aviso` (texto exacto, y una variante con una cita futura que añade "1 cita quedó fuera del horario: revísala en «Citas fuera del horario»." (A11)), `test_no_se_guarda_horario_para_quien_no_es_dentista` (asistente, administrador, id inexistente → 404 sin filas), `test_solo_la_administracion_guarda_horarios` (dentista, asistente y paciente → 403), y para TM5, a nivel de servicio (llamando a `HorarioDentista::guardar`), `test_el_horario_bloquea_al_dentista_antes_de_borrar` (`DB::listen`) y `test_un_bloqueo_agotado_no_da_error_500` (conexión simulada con 1205/1213), más, por HTTP, `test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje` (servicio simulado que lanza `HorarioNoGuardado` en `guardar` y devuelve una colección vacía en `citasFuera`: 302 con el mensaje en `error` y horario sin cambios; siguiendo la redirección, la ficha muestra lo enviado, p. ej. `value="10:00"` en un día marcado, y no el horario guardado) (A13, A23) — tests/Feature/HorarioDentistaTest.php — hecho cuando: fallan por la razón esperada (hoy se guarda sin validar, con horas por defecto y para cualquier personal, sin bloqueo, la ficha muestra `HH:MM:SS`, y `HorarioDentista` no existe); `test_solo_la_administracion_guarda_horarios` pasa y se comprueba quitando un momento `role:admin` del grupo de rutas (falla; restaurado y anotado) — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA9 — depende: T002
  - nota: fallan 9 de 10 por la razón esperada: hoy no se valida (día 8 se guarda, sin errores), los mensajes son otros, un asistente recibe horario (302 en vez de 404) y `HorarioDentista`/`HorarioNoGuardado` no existen.
  - nota: `test_solo_la_administracion_guarda_horarios` pasa hoy (caracterización): comprobado quitando `role:admin` del grupo `admin` de `routes/web.php` → 302 en vez de 403; restaurado (sin diff) y vuelve a pasar.
  - nota: A4 — el test de CA1 exige `value="09:00"` (hoy la ficha pinta `09:00:00`), así que falla por la razón esperada; los de rechazo comprueban el horario anterior intacto, no una ausencia.
  - nota: A29 — CA3 incluye `dias=texto` y `dias[1]=texto`, y en cada variante se sigue la redirección y se exige 200.
  - nota: A30 — todos los POST envían `formulario=horario` como el formulario real (helper `guardar`); el CA1 reenvía los campos que pinta la ficha.
  - nota: añade al plan el caso "fin igual al inicio" (10:00–10:00) en CA2 y "campo desconocido" en CA3; usa el escenario común `EscenarioEstadosCita` de la 026 sin cambiarlo.
- [x] T011 [P] Escribir `CitasFueraDelHorarioTest` con los 7 tests del plan: `test_la_ficha_lista_las_citas_futuras_fuera_del_horario` (una en un día que se quita, otra que empieza antes y otra que termina después: paciente, fecha, hora, estado y enlace en la sección `citas-fuera`, y las citas sin cambios), `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera` (texto exacto con N = 1, en singular, y con N = 2 (A11)), `test_sin_citas_fuera_no_hay_lista`, `test_las_citas_pasadas_o_cerradas_no_aparecen`, `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` (mismos bordes que `ReglaAgendaTest`), `test_solo_aparecen_citas_del_dentista` y `test_el_nombre_del_paciente_se_muestra_escapado`; reloj en UTC con `Carbon::setTestNow` y citas a hora local — tests/Feature/CitasFueraDelHorarioTest.php — hecho cuando: fallan por la razón esperada (hoy no hay sección `citas-fuera` ni recuento en el mensaje); `test_sin_citas_fuera_no_hay_lista` pasa hoy y se comprueba haciendo que la ficha muestre un momento la sección vacía (falla; restaurado y anotado) — cubre: CA7, CA8 — depende: T002
  - nota: fallan los 7 por la razón esperada: no existe la sección `citas-fuera` y el mensaje de guardado es "Horarios guardados correctamente.".
  - nota: A4 — `test_las_citas_pasadas_o_cerradas_no_aparecen`, el de bordes y el de otro dentista llevan una cita de control que sí debe aparecer; `test_sin_citas_fuera_no_hay_lista` falla hoy por el mensaje, y su parte de ausencia se comprobará rompiendo la vista en T023.
  - nota: A12 — la variante con `disponible = 0` está en `test_la_ficha_lista_las_citas_futuras_fuera_del_horario` (dentista D2: viernes no disponible → la cita del viernes sale en la lista).
- [x] T012 [P] Escribir `HorarioDentistaVistasTest`: `test_la_ficha_solo_ofrece_horario_a_los_dentistas` (sección `horario` en la ficha de un dentista; ausente en la de un asistente y la de un administrador); `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` (dentista, asistente y paciente → 403 en `GET /admin/personal/{id}` de un dentista con citas fuera, sin el nombre del paciente; TM8, A7); `test_los_errores_de_un_dia_se_asocian_a_sus_campos` (tras un error, el campo tiene `aria-invalid="true"`, `aria-describedby` apunta al `id` del mensaje de ese día y el resumen `role="alert"` dice "Revisa el horario de: <día>."; un día que no tenía horario, marcado sin horas, vuelve con la casilla marcada, la fila sin `hidden-row` ni `disabled` y su error visible, y un día desmarcado en el envío sigue desmarcado (A6)); `test_las_piezas_nuevas_solo_usan_tokens` (el parcial `personal/partials/citas-fuera.blade.php` no tiene `style=`; en la sección `horario`, los mensajes `field-hint` y el resumen `role="alert"` no tienen `style=`; las reglas `.lista-citas`, `.horario-dia` (y sus partes), `:focus-visible`, `.field-hint` y `.field-hint-error` de `app.css` no tienen colores hex/rgb ni px, `.field-hint` usa `--text-small` y la de los campos de hora usa `--border-control` (A1, A2, A8, A25); en la sección `horario`, ninguna fila `horario-dia` ni su contenido tiene `style=`, cada campo de hora está dentro de una fila `horario-dia` y lleva `field`, y `toggleDia` no asigna `.style` (A24, A26)); y `test_los_pares_de_contraste_cumplen_aa` (lee los tokens de `:root` de `app.css` resolviendo `var()`, y exige ≥ 4.5:1 en los pares de texto (incluido `--text-muted` del nombre del día y las notas) y ≥ 3:1 en `--border-control` y `--focus-ring`, sobre `--white` y `--p50`, con los pares del Constitution Check del plan (A8)) — tests/Feature/HorarioDentistaVistasTest.php — hecho cuando: fallan por la razón esperada (hoy la ficha de un asistente tiene horario, los errores no existen, el parcial no existe, la sección tiene estilos en línea y faltan los tokens, porque T003 aún no se ha hecho (A21)); `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` pasa hoy y se comprueba quitando un momento `role:admin` del grupo de rutas (falla; restaurado y anotado) — cubre: CA2, CA4, CA6 — depende: T002
  - nota: fallan 5 de 6 por la razón esperada: la ficha de un asistente tiene el formulario, no hay errores por día, no existe el parcial, faltan las reglas de `app.css` y los tokens `--border-control`/`--focus-ring` (T003 aún no se ha hecho, A21).
  - nota: `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` pasa hoy (caracterización, TM8): comprobado quitando `role:admin` → 200 en vez de 403; restaurado y vuelve a pasar.
  - nota: A30 — los POST envían `formulario=horario`; el caso sin ese campo (ficha recién cargada) es el de `test_la_ficha_solo_ofrece_horario_a_los_dentistas` y el CA1 de T010.
  - nota: A32 — las comprobaciones de CSS van en un test propio, `test_las_reglas_css_nuevas_solo_usan_tokens`, separado del de HTML; exige además que cada regla nueva exista, para no pasar en vacío.
  - nota: A34 — el test de reglas exige `var(--weight-*)` en todo `font-weight` de las reglas nuevas.
  - nota: A35 — el test de contraste solo toma tokens de color (valores hex tras resolver `var()`), y el de reglas rechaza `color:` con `--text-body`/`--text-small`.
- [ ] T013 [P] Añadir a `CitasFueraDelHorarioTest` el límite de cita "futura" en la hora de la clínica: con el reloj a las 17:00 UTC (11:00 en la clínica), una cita agendada hoy a las 14:00 locales que queda fuera del horario nuevo aparece en `citas-fuera` y cuenta en el recuento del mensaje — tests/Feature/CitasFueraDelHorarioTest.php — hecho cuando: el caso pasa con el código actual y se comprueba de caracterización cambiando un momento en `HorarioDentista::citasFuera` la hora de la clínica por `Carbon::now()` en UTC (falla; restaurado y anotado) — cubre: CA7, CA8 — depende: T023
  - nota: añadida por /review: R1
- [ ] T014 [P] Precisar en `HorarioDentistaTest::test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` lo que muestra la ficha: dentro de `fila-1`, `value="09:00"` y `value="13:00"` con la casilla `checked`; dentro de `fila-3`, `value="10:00"` y `value="18:00"` con `checked`; `fila-5` (horas sin marcar) sin `checked` — tests/Feature/HorarioDentistaTest.php — hecho cuando: el test pasa y se comprueba rompiendo un momento el recorte `H:i` de la vista (`substr(…, 0, 5)`) (falla; restaurado y anotado) — cubre: CA1 — depende: T023
  - nota: añadida por /review: R5
- [ ] T015 [P] Ampliar `HorarioDentistaVistasTest`: (a) tras el rechazo de un día marcado sin horas, sus dos campos salen vacíos (`value=""`); (b) test del Gate `definir-horario` con `Gate::forUser()->inspect()`: admin sobre un dentista → permitido, admin sobre un asistente → denegado 404, dentista sobre un dentista → denegado 403; (c) `test_las_reglas_css_nuevas_solo_usan_tokens` exige una regla `.horario-dia .field:focus` con `border-color: var(--border-control)` y que `.horario-dia .field` fije `border-width: var(--border-width)`; (d) `test_los_pares_de_contraste_cumplen_aa` saca los pares de las propias reglas nuevas: todo token de `color:` (salvo `.field-hint`, que en las piezas nuevas siempre va con `.field-hint-error`) cumple ≥ 4.5:1 sobre `--white` y `--p50`, y todo token de borde de `.horario-dia .field` y de `outline` cumple ≥ 3:1; (e) `toggleDia` alterna `hidden` en los `.field-hint-error` de la fila — tests/Feature/HorarioDentistaVistasTest.php — hecho cuando: (c) y (e) fallan hoy por la razón esperada (falta la regla de foco y `toggleDia` no toca los mensajes); (a), (b) y (d) pasan y se comprueban rompiendo lo que protegen (defaults 09:00/18:00 en la vista, la rama de rol del Gate, `--text-light` en `.horario-dia__nota`) (fallan; restaurado y anotado) — cubre: CA4, CA6, CA9 — depende: T023
  - nota: añadida por /review: R2, R3, R6, R7, R10

## Implementación
- [x] T020 Crear `HorarioDentista` (constructor con `ConnectionInterface`; `guardar` en una transacción con `lockForUpdate()` de `personal`, después borrar e insertar con `disponible = 1`, devolviendo el número de citas fuera; 1205/1213 y `DeadlockException` → `HorarioNoGuardado::ocupado()`, el resto se relanza; `citasFuera` con la regla y los bordes de `ReservaCitas`, solo `agendada`/`confirmada` que aún no empiezan en `clinica.zona_horaria`, con paciente, fecha, hora y estado, ordenadas) y `HorarioNoGuardado` con su mensaje — app/Agenda/HorarioDentista.php, app/Agenda/HorarioNoGuardado.php — hecho cuando: pasan los dos tests de servicio de TM5 de T010 (`test_el_horario_bloquea_al_dentista_antes_de_borrar` y `test_un_bloqueo_agotado_no_da_error_500`), y `pint --test` pasa en los dos archivos — cubre: CA1, CA7, CA8 — depende: T010, T011
  - nota: pasan `test_el_horario_bloquea_al_dentista_antes_de_borrar` y `test_un_bloqueo_agotado_no_da_error_500` llamando al servicio; `pint --test` pasa en los dos archivos.
  - nota: control de TM5 comprobado rompiéndolo: sin `lockForUpdate()` falla el test del orden (no hay `select … for update`); sin la conversión de 1205/1213/`DeadlockException` falla el del bloqueo agotado. Restaurado.
  - nota: A12 — `citasFuera` trata `disponible = 0` como día sin horario (igual que `ReservaCitas`) y evalúa la franja en PHP, sin `DB::raw`/`whereRaw`: solo Query Builder con bindings (el "aún no empieza" es `fecha_hora > ahora` en `clinica.zona_horaria`).
  - nota: `citasFuera` devuelve objetos con `id`, `paciente` (nombre y apellido), `fecha_hora`, `duracion_min` y `estado`, ordenados por fecha.
- [x] T021 Crear el Gate `definir-horario` (`GestionPersonal::definirHorario`: admin y tipo dentista → permitir; admin con otro tipo → `denyAsNotFound`; otro rol → `deny`) y registrarlo en `AppServiceProvider::boot()` — app/Autorizacion/GestionPersonal.php, app/Providers/AppServiceProvider.php — hecho cuando: `Gate::has('definir-horario')` es verdadero y `pint --test` pasa en los dos archivos — cubre: CA6, CA9 — depende: T010
  - nota: `Gate::has('definir-horario')` es verdadero (tinker) y `pint --test` pasa en los dos archivos. El rol se comprueba primero: otro rol → 403 aunque el recurso no sea un dentista.
- [x] T022 Crear la regla `HoraFinPosterior` (`ValidationRule` + `DataAwareRule`: lee la `hora_inicio` del mismo día y solo compara si las dos son textos `H:i` válidos; fin igual o anterior → "La hora de fin debe ser posterior a la de inicio, el mismo día.") y cambiar `PersonalController`: `saveHorarios(Request, string $id, HorarioDentista)` con 404 → Gate `definir-horario` → validación con las reglas y mensajes de Contratos (sin `after:`) → franjas solo de los días con `activo` → `guardar` → mensaje de "Mensajes de éxito" (base con o sin días, más el recuento en singular o plural si N > 0), y `HorarioNoGuardado` → `back()->withInput()->with('error', …)`; `show` pasa `$puedeDefinirHorario` (`Gate::allows`) y `$citasFuera` solo en ese caso — app/Rules/HoraFinPosterior.php, app/Http/Controllers/PersonalController.php — hecho cuando: pasan los tests que no dependen de la vista (plan → Estrategia de pruebas, A3): de `HorarioDentistaTest`, `test_un_dia_marcado_sin_horas_se_rechaza`, `test_un_horario_sin_dias_se_guarda_con_aviso`, `test_no_se_guarda_horario_para_quien_no_es_dentista`, `test_solo_la_administracion_guarda_horarios`, y los dos de servicio de TM5; de `CitasFueraDelHorarioTest`, `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera`; un POST con una lista en `hora_inicio` da 302, no 500; y, con el servicio simulado lanzando `HorarioNoGuardado`, el POST da 302 con el mensaje en `error` (la parte de la ficha de `test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje` se cumple en T023). `pint --test` pasa en los dos archivos — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA9 — depende: T004, T020, T021
  - nota: pasan los tests sin vista que nombra la tarea: `test_un_dia_marcado_sin_horas_se_rechaza`, `test_un_horario_sin_dias_se_guarda_con_aviso`, `test_no_se_guarda_horario_para_quien_no_es_dentista`, `test_solo_la_administracion_guarda_horarios`, los dos de servicio de TM5 y `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera`. `pint --test` pasa en los dos archivos.
  - nota: con las reglas del controlador, una lista en `hora_inicio` o en `hora_fin`, `25:99`, `dias=texto` y `dias[1]=texto` dan error de validación (302), sin excepción; `25:99` solo da el error de formato del inicio. Con el servicio simulado lanzando `HorarioNoGuardado`, el POST da 302 con el mensaje en `error` (`test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje` solo falla en la parte de la ficha, que es de T023).
  - nota: control de TM3 comprobado rompiéndolo: sin `Gate::authorize('definir-horario')` el asistente recibe 302 en vez de 404. Restaurado.
  - nota: `show` recibe `HorarioDentista` por inyección de método, así que el `$this->mock` del test lo sustituye; `$citasFuera` es una colección vacía si el Gate no lo permite.
- [x] T023 Cambiar la ficha del personal, revisándola antes a mano contra `system.html` (la skill `design` no está instalada): sección "Horarios de atención" solo con `$puedeDefinirHorario`; horas en `H:i`; campo oculto `formulario=horario`; tras cualquier rechazo que vuelve con los datos (`old('formulario') === 'horario'`: validación o bloqueo agotado), cada día toma de `old()` la casilla (marcada solo si `activo` es el texto `"1"`), las horas (solo si son texto; si no, vacías y nunca impresas) y la visibilidad y habilitación de su fila (sin `hidden-row` ni `disabled` si se envió marcado), y si no todo sale del horario guardado (D11, A22, A23); filas y su contenido con las clases del componente "Día del horario" (`horario-dia`, `horario-dia--activo`, `__nombre`, `__horas`, `__nota`; campos de hora con `field` dentro de su fila) y sin `style=`, "No disponible" con `__nota`, y `toggleDia` solo cambia `horario-dia--activo`, `hidden-row`, `hidden` y `disabled`, sin `.style` (D13, A24, A26); por día, `@error` de `hora_inicio` y `hora_fin` con `field-hint field-hint-error` e `id`, y `aria-describedby` y `aria-invalid` en los campos; resumen `role="alert"` con "El horario enviado no es válido." (errores en `dias`, `dias.N`, `dias.N.activo` o en las horas de un día fuera de 1–7) y "Revisa el horario de: <días>." (A10); los elementos nuevos sin `style=`; `@include` del parcial nuevo `citas-fuera` con `.lista-citas`, `partials/badge-estado` y el enlace "Ver cita" `.btn btn-outline btn-sm` a `admin.citas.show`, sin `style=` — resources/views/personal/show.blade.php, resources/views/personal/partials/citas-fuera.blade.php — hecho cuando: pasan todo `HorarioDentistaTest`, `CitasFueraDelHorarioTest` y `HorarioDentistaVistasTest`, y la suite completa no tiene fallos nuevos · verificación manual: ficha de un dentista a 1440 px con un error de franja y con citas fuera, contra `docs/design/system.html` (borde de los campos de hora, foco visible con teclado en los campos y en "Ver cita", alternar un día con la casilla, y aspecto de la sección sin estilos en línea frente a antes) — cubre: CA1, CA2, CA3, CA4, CA6, CA7, CA8 — depende: T003, T022
  - nota: pasan los 23 tests de `HorarioDentistaTest`, `CitasFueraDelHorarioTest` y `HorarioDentistaVistasTest` (tres ejecuciones) y la suite completa: 150 en verde.
  - nota: controles comprobados rompiéndolos (y restaurados): sección siempre visible → falla `test_sin_citas_fuera_no_hay_lista`; nombre sin escapar → falla TM7; sin filtro de dentista → falla TM6; `after:` en lugar de `HoraFinPosterior` → 500 en CA3; regla que no compara → falla CA2; `old()` sin filtrar listas → 500 al mostrar la ficha (A22); `disponible = 0` como horario → falla A12.
  - nota: A16 — "Citas fuera del horario (N)" va antes de "Horarios de atención", como `<section>` con encabezado `<h2 class="section-title">` y `aria-labelledby`.
  - nota: A29 — la vista lee lo enviado campo a campo (`old("dias.N.campo")`) y solo usa textos; `dias=texto` y `dias[1]=texto` muestran la ficha con 200.
  - nota: A31 — cada parte de la fila tiene su clase (`horario-dia__dia`, `__nombre`, `__horas`, `__nota`); "No disponible" se pinta siempre y se oculta con el atributo HTML `hidden` (antes solo existía en los días sin horario, así que al desmarcar un día guardado no aparecía); `toggleDia` solo cambia clases, `hidden` y `disabled`. Los campos de hora llevan además `aria-label` ("Hora de inicio del lunes").
  - nota: A33 — Rollout (complemento del plan; no se edita `plan.md` para no invalidar el análisis): el formulario envía además el campo oculto `formulario=horario`, que la versión anterior ignora; la sección de horario cambia de aspecto (espaciado ±2 px, letra 13.5 px, notas en `--text-muted` en vez de `--text-light`, borde rosa intenso en los campos de hora y anillo de foco). Se recogerá en el CHANGELOG de la release.
  - nota: los envoltorios de la lista de días y del botón conservan sus estilos en línea: no los modifica la spec (deuda DS4 del resto de la ficha).
  - nota: verificación manual OK 2026-10-03 (usuario): ficha de un dentista a 1440 px — borde de las horas, foco con teclado en campos y en «Ver cita», alternar un día, error de franja con resumen y lista de citas fuera.
- [ ] T024 Fijar en `app.css` el borde del campo de hora con y sin foco: `.horario-dia .field` con `border-width: var(--border-width)` y una regla `.horario-dia .field:focus { border-color: var(--border-control); }`, que gana a la regla `.field:focus` del bloque `<style>` previo de la vista — public/css/app.css — hecho cuando: pasa la parte (c) de T015 y la suite completa no tiene fallos nuevos · verificación manual: con Tab, el borde del campo de hora sigue en `--border-control` (rosa intenso) con el anillo de foco — cubre: CA1 — depende: T015
  - nota: añadida por /review: R2
- [ ] T025 Hacer que `toggleDia` alterne también `hidden` en los mensajes `.field-hint-error` de la fila del día (se ocultan al desmarcarlo y vuelven al marcarlo) — resources/views/personal/show.blade.php — hecho cuando: pasa la parte (e) de T015 y la suite completa no tiene fallos nuevos · verificación manual: desmarcar y volver a marcar un día con error en la ficha — cubre: CA2, CA4 — depende: T015
  - nota: añadida por /review: R10

## Integración y documentación
- [x] T040 Verificación final — (sin archivos) — hecho cuando:
  - `php artisan test` completo da la línea base de T002 más los tests de T010–T012, todos en verde (incluidos `ReglaAgendaTest` y `ReservaConcurrenteTest`);
  - `vendor/bin/pint --test` pasa sobre todos los archivos PHP tocados;
  - semgrep `p/php --metrics=off` sobre los archivos de `app/` tocados no da hallazgos;
  - gitleaks sobre el rango de la spec no da hallazgos;
  - composer audit `--locked` y npm audit dan 0 altas o críticas;
  - el diff no añade llamadas a `Log::`.
  - nota: 2026-10-03: `php artisan test` → 150 en verde (127 de la línea base de T002 + 23 de T010–T012, incluidos `ReglaAgendaTest` y `ReservaConcurrenteTest`), ~33 s.
  - nota: `pint --test` pasa en los 9 archivos PHP tocados; semgrep `p/php --metrics=off` sobre los 6 archivos de `app/` tocados: 0 hallazgos; gitleaks sobre `ebe3bc1..HEAD`: sin secretos.
  - nota: composer audit `--locked`: 0 altas o críticas. Dos bajas cubiertas por EX1 (laravel/framework CVE-2026-102279 y league/flysystem CVE-2026-102601) y una **media nueva**: phpseclib/phpseclib CVE-2026-84308 (X25519 no constante; afecta a < 3.0.57). Fuera de P6 (solo bloquean críticas y altas); queda para que `/review` y `/release` decidan si se registra.
  - nota: npm audit: 0 vulnerabilidades. El diff de `app/` no añade llamadas a `Log::`.
- [x] T041 Anotar en `docs/roadmap.md → Pendientes y deuda` que la deuda 025/R12 (franjas que cruzan la medianoche) queda resuelta por la 027 (CA2), y añadir la viñeta "extraer la regla de franja común a `ReservaCitas` y `HorarioDentista::citasFuera` (spec 013)" — docs/roadmap.md — hecho cuando: las dos viñetas existen y citan la spec 027
  - nota: 025/R12 marcada como resuelta por la 027 (CA2) y nueva viñeta "Deuda de la spec 027" con la extracción de la regla de franja común → spec 013.
- [ ] T042 Corregir `docs/design/system.md`: en "Campo de formulario", solo `.field`, `.field-label` y `.field-error` siguen en `citas.css`; referencias de línea de `app.css` actualizadas (`:root` y tamaños); DS4 dice que la sección de horario no tiene estilos en línea en filas y campos (los envoltorios previos siguen); DS1 cita `.field-hint` en `app.css` — docs/design/system.md — hecho cuando: `aidd.py validate` sin avisos de diseño y las referencias apuntan a las líneas actuales
  - nota: añadida por /review: R15
- [ ] T043 Registrar en `docs/security.md` la vulnerabilidad media de `phpseclib/phpseclib` 3.0.55 (CVE-2026-84308, corregida en 3.0.57), detectada el 2026-10-03 en la review de la 027, con destino "spec de actualización de dependencias, antes del vencimiento de EX1 (2026-10-31), respetando los 7 días de antigüedad" (decisión del usuario, 2026-10-03) — docs/security.md — hecho cuando: la entrada existe con fecha, severidad, versión corregida y destino
  - nota: añadida por /review: R4
- [x] T090 Actualizar `docs/architecture.md`:
  - fila "Agenda (horario)" con `app/Agenda/HorarioDentista.php` y la regla `app/Rules/HoraFinPosterior.php`;
  - fila "Autorización de personal" (nueva, junto a la de citas) con `GestionPersonal` y el Gate `definir-horario`;
  - en "Personal", que el horario lo guarda el servicio.
  - nota: fila "Agenda (horario)" con `HorarioDentista`, `HorarioNoGuardado` y `HoraFinPosterior`; fila nueva "Autorización de personal" (junto a la de citas) con `GestionPersonal` y `definir-horario`; la fila "Personal" dice que el horario lo valida el controlador y lo guarda el servicio; el párrafo de piezas de servicio cita `HorarioDentista.php` y `app/Rules/`.
- [x] T091 Actualizar `docs/security.md`, por corrección:
  - RS12.b → "en curso (spec 027)";
  - RS12.c sigue "pendiente", y su nota añade "horario de dentista: spec 027";
  - "Dónde se aplica" gana el Gate `definir-horario`.
  - nota: por corrección: RS12.b → "en curso (spec 027)" (pasará a mitigada en la release); RS12.c sigue pendiente con "horario de dentista: spec 027"; "Dónde se aplica" gana el Gate `definir-horario`. `deployment.md` y `observability.md` sin cambios (sin variables, migraciones ni logs nuevos).

  `docs/deployment.md` y `docs/observability.md` no cambian.
- [ ] T092 Marcar spec como `implemented`
  - nota: spec 027 → `implemented` (2026-10-03) con CA1–CA9 marcados: sus tests pasan; `docs/specs/README.md` actualizado.

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T014 | T020, T022, T023, T024 |
| CA2 | T010, T012 | T022, T023, T025 |
| CA3 | T010 | T022, T023 |
| CA4 | T010, T012, T015 | T022, T023, T025 |
| CA5 | T010 | T022 |
| CA6 | T010, T012, T015 | T021, T022, T023 |
| CA7 | T011, T013 | T020, T022, T023 |
| CA8 | T011, T013 | T020, T022, T023 |
| CA9 | T010, T015 | T021, T022 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T022 | T010 |
| TM2 | T021, T022 | T010 |
| TM3 | T021, T022 | T010 |
| TM4 | T022 | T010 |
| TM5 | T020, T022 | T010 |
| TM6 | T020 | T011 |
| TM7 | T023 | T011 |
| TM8 | T022 | T012 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Agenda/HorarioDentista.php | T020 |
| app/Agenda/HorarioNoGuardado.php | T020 |
| app/Rules/HoraFinPosterior.php | T022 |
| app/Autorizacion/GestionPersonal.php | T021 |
| app/Providers/AppServiceProvider.php | T021 |
| app/Http/Controllers/PersonalController.php | T004, T022 |
| resources/views/personal/show.blade.php | T023, T025 |
| resources/views/personal/partials/citas-fuera.blade.php | T023 |
| public/css/app.css | T003, T024 |
| public/css/citas.css | T003 |
| docs/design/system.md, docs/design/system.html | T001, T042 |
| tests/Feature/HorarioDentistaTest.php | T010 |
| tests/Feature/CitasFueraDelHorarioTest.php | T011 |
| tests/Feature/HorarioDentistaVistasTest.php | T012 |
| docs/architecture.md | T090 |
| docs/security.md | T091, T043 |
| docs/roadmap.md | T041 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Mensajes en español que dicen qué corregir | T010 (texto exacto de CA2, CA3, CA4 y CA5), T011 (recuento) |
| WCAG 2.1 AA en lo nuevo o modificado | T012, T015 (errores asociados, solo tokens y pares de contraste sacados de las reglas) · verificación manual de T023, T024 y T025 |
