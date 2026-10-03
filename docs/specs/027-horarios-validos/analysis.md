---
result: fail
round: 1
mode: full
constitution_version: 1.2.0
date: 2026-10-02
spec_sha: 4d3dea63f447
plan_sha: 921ec021355d
tasks_sha: 4ee80c9261e0
---

# Análisis · 027 Horario válido del dentista y aviso de citas afectadas

## Resumen
`fail`: hay dos hallazgos críticos.
- **A5:** las reglas de validación del contrato dan un 500 con una lista en `hora_inicio`. Se reprodujo en el contenedor: `TypeError` en `checkDateTimeOrder`.
- **A1:** los campos de hora que se modifican tienen un borde de 1.31:1, y P9 exige 3:1.

Los altos son de orden de tareas, de tokens que faltan para cumplir P9 y de tests de P3 y P9.
Análisis completo hecho por un subagente independiente; cada hallazgo se verificó contra los
archivos.

Conteo: CRÍTICA 2 · ALTA 5 · MEDIA 8 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 9 / 9 |
| Amenazas con control y test | 7 / 7 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P9) | CRÍTICA | plan → P9 y `show.blade.php`; T023 | T023 modifica los campos de hora (`H:i`, `old()`, `aria-*`, `horario-dia`), pero siguen con `.field`: borde `1.5px solid var(--border)`, 1.31:1 sobre blanco (`show.blade.php:307`). P9 exige ≥ 3:1 en el borde de un control, la lista de pares del plan no lo recoge y no hay excepción. Se descarta la parte del enlace «Ver cita»: el texto (`--p600`, 7:1) lo identifica sin depender del borde. | Los campos de hora del horario tienen un borde ≥ 3:1 con un token nuevo del sistema (solo ellos; el resto de `.field` sigue en DS2). Se comprueba con un test que verifique en `app.css` que la regla de `.horario-dia` para los campos usa ese token, y que el valor del token da ≥ 3:1 sobre `--white` y `--p50`. → `/plan --fix`, `/tasks --fix` |
| A5 | Seguridad / Inconsistencia | CRÍTICA | plan → "Validación y mensajes", TM4; T010, T022 | Con las reglas del contrato, `dias[1][hora_inicio]` como lista y `hora_fin` válida llega a `after` y lanza `TypeError` (500). Reproducido en el contenedor el 2026-10-02. Con `hora_inicio = "25:99"`, `after` añade además un error engañoso en `hora_fin`. | Una lista o un texto en cualquier campo de cualquier día da 302 con error de validación, nunca 500. Con `hora_inicio` inválida solo sale el error de formato. Caso explícito en `test_un_horario_manipulado_se_rechaza_sin_error_500`: lista en `hora_inicio` con `hora_fin` válida. → `/plan --fix`, `/tasks --fix` |
| A2 | Constitución (P9) / Inconsistencia | ALTA | T003, T012; plan → "Cambios a incorporar al sistema" | `test_las_piezas_nuevas_solo_usan_tokens` prohíbe px en `.lista-citas` y `:focus-visible`, pero el borde de las filas y el anillo de foco necesitan grosor y desplazamiento, y T001/T003 no incorporan esos tokens (`--border-width` solo existe en `system.html`). T003 no puede cumplir su criterio sin literales. | El sistema y `:root` incluyen tokens de grosor de borde, de anillo y de desplazamiento del anillo antes de T003. Lo comprueba el test de T012: 0 px fuera de `:root` en esas reglas. → `/plan --fix`, `/tasks --fix` |
| A3 | Inconsistencia | ALTA | T022 → "hecho cuando" | T022 va antes de T023 pero exige todo `HorarioDentistaTest`. (1) El test de horario válido reenvía lo que pinta la ficha, que hoy es `HH:MM:SS`, y falla con `date_format:H:i` hasta T023. (2) El test de franja invertida mira el mensaje bajo el día, que solo existe tras T023. (3) "El estado sin cambios de las citas" vive en un test que mira el HTML. | Cada test que nombra el criterio de T022 pasa con el árbol tal como queda al cerrar T022; los que dependen de la vista pasan a T023. → `/tasks --fix` |
| A6 | Ambigüedad | ALTA | plan → `show.blade.php` ("`old()` tras un error"); T023 | La casilla, `hidden-row` y `disabled` de cada día salen de `$horarios` (BD). Si falla un día que no tenía horario, la fila vuelve desmarcada y oculta, y su error no se ve. Los días que se desmarcaron reaparecen marcados. | Tras un error, cada día conserva lo enviado (marcado y horas), y su mensaje y sus campos se ven y están habilitados. Se comprueba en `test_los_errores_de_un_dia_se_asocian_a_sus_campos` con un día nuevo sin horas: casilla marcada y fila sin `hidden-row` ni `disabled`. → `/plan --fix`, `/tasks --fix` |
| A7 | Constitución (P3) / Seguridad | ALTA | plan → P3, "Qué ve cada rol", modelo de amenazas | `GET /admin/personal/{id}` se modifica y ahora muestra nombres de pacientes y fechas de citas. P3 pide un test del caso denegado por rol en todo endpoint modificado, y no lo hay: la fila "Dentista, asistente, paciente" cita un test que solo cubre al admin. Tampoco hay amenaza de divulgación de la lista a través de la ficha. | Dentista, asistente y paciente reciben 403 en la ficha de un dentista con citas fuera. Test por rol de caracterización, comprobado quitando `role:admin` un momento, y una TM de divulgación con ese test. → `/plan --fix`, `/tasks --fix` |
| A8 | Constitución (P9) | ALTA | plan → P9 "Cómo se verifica"; T003, T012, T023 | Los umbrales de contraste solo se verifican a mano. El test de tokens no cubre los elementos nuevos de `show.blade.php` (mensajes por día, resumen `role="alert"`) ni las reglas movidas: `.field-hint` lleva `11.5px` y `4px` literales, lo que contradice "todo solo con tokens" de T003. | Los elementos nuevos de `#horario` no tienen `style=`; las reglas nuevas o movidas de `app.css` usan solo tokens (o la regla movida queda fuera del test y se dice); un test comprueba los pares de contraste a partir de los valores de los tokens usados (≥ 4.5:1 en texto, ≥ 3:1 en bordes y foco). → `/plan --fix`, `/tasks --fix` |
| A4 | Constitución (P2) | MEDIA | plan → "Estrategia de pruebas"; T010, T011 | `test_las_citas_pasadas_o_cerradas_no_aparecen`, `test_una_cita_justo_en_el_borde…` y `test_solo_aparecen_citas_del_dentista` afirman ausencias y pasan hoy; el de horario válido también, salvo que exija `value="09:00"`. | Cada uno falla hoy por la razón esperada (con una cita de control que sí aparece en `#citas-fuera`, o exigiendo `H:i`), o se declara de caracterización y se anota su rotura. → nota de T010, T011 |
| A9 | Inconsistencia (nombres) | MEDIA | plan → anillo de foco; `system.md` (`--color-focus` = `--p300`, DS2); `system.html` | El plan crea `--focus-ring` (`--p600`), pero el sistema ya llama al rol de foco `--color-focus` (2.23:1) y DS2 propone corregir ese mismo token. Quedarían dos tokens de foco. | Un solo token de foco, ≥ 3:1, con el mismo nombre en `system.md`, `system.html` y `app.css`. → nota de T001, T003 |
| A10 | Ambigüedad | MEDIA | plan → Endpoint y resumen `role="alert"`; T023 | Un día 0 u 8, o un `dias` que no es array, deja el error en `dias`, no en `dias.N.*`: no puede ir junto a un día, y no se dice si el resumen lo muestra. | Todo error de `dias` y `dias.*` aparece con texto visible dentro del resumen `role="alert"`. Se comprueba en `test_un_horario_manipulado_se_rechaza_sin_error_500` siguiendo la redirección. → `/plan --fix`, `/tasks --fix` |
| A11 | Ambigüedad | MEDIA | plan → mensajes de éxito | Falta el texto para N = 1 (singular) y para un horario sin días que deja citas fuera. | Texto exacto para N = 1, N > 1 y "sin días con N > 0". Se comprueba en `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera` (N = 1 y N = 2) y en `test_un_horario_sin_dias_se_guarda_con_aviso` (con una cita futura). → `/plan --fix`, `/tasks --fix` |
| A12 | Ambigüedad (P4) | MEDIA | plan → `HorarioDentista::citasFuera` | No se dice si un horario con `disponible = 0` cuenta como día sin horario, como hace `ReservaCitas` (línea 116), ni que la regla se evalúe sin SQL crudo. | `disponible = 0` se trata igual que en `ReservaCitas`, sin `DB::raw`/`whereRaw`. Caso `disponible = 0` en `test_la_ficha_lista_las_citas_futuras_fuera_del_horario`. → nota de T011, T020 |
| A13 | Inconsistencia | MEDIA | T010, T020, T022 (TM5) | T020 exige los dos tests de TM5 "llamando al servicio directamente", pero T010 no lo dice. La parte HTTP (`HorarioNoGuardado` → 302 con `error` y `withInput`) queda sin test. | Un test de servicio para el orden del bloqueo y otro HTTP, con el servicio simulado lanzando `HorarioNoGuardado`, que compruebe el 302, el mensaje y el horario sin cambios. Los dos nombrados en T010 y en el criterio de T020 y T022. → `/tasks --fix` |
| A14 | Duplicación y conflicto | MEDIA | plan → `AppServiceProvider`; planes de la 014 (`Gate::after`) y la 020 (D4) | Las tres specs editan `AppServiceProvider::boot()`. La denegación 404 de `definir-horario` no queda auditada por el contrato de la 014 y la 020. | Al rehacer la 014 y la 020 sobre `main`, decidir si `definir-horario` entra en el registro de accesos denegados, con su test. → roadmap |
| A15 | Ambigüedad | MEDIA | spec → Decisiones (supuesto "¿Se puede guardar un horario sin días?", fuente `/specify`) | Supuesto de comportamiento que fija CA5 sin fuente `usuario`. | Confirmado por el usuario el 2026-10-02: la fila pasa a fuente `usuario`. → `/specify --edit 027` |
| A16 | Ambigüedad | BAJA | spec → Diseño; plan → D3 y `show.blade.php` | No se dice dónde va "Citas fuera del horario" en la ficha ni si lleva un encabezado real. D3 es decisión del usuario solo en el plan. | La sección va antes de "Horarios de atención", con un encabezado de sección real. → nota de T023 |
| A17 | Inconsistencia | BAJA | plan → escala de espaciado; `system.md` | La escala omite 12 y 18 px (muy usados) e incluye 4 px; no se justifica. | La fila de Decisiones de `system.md` 1.1.0 justifica la escala elegida. → nota de T001 |
| A18 | Inconsistencia | BAJA | T003, T004 frente a T010–T012 | Si los tests nuevos se escriben antes, la suite ya no da la línea base de T002. | El criterio de T003 y T004 excluye los tests nuevos de la 027. → nota de T003, T004 |
| A19 | Inconsistencia | BAJA | T040, T012, tabla de Cobertura | T040 es una verificación y no cubre criterios. T012 también cubre CA4. En la tabla, CA8 no incluye T022. | Ajustar `cubre` y la tabla. → `/tasks --fix` |
| A20 | Inconsistencia (nombres) | BAJA | T090 frente a `docs/architecture.md` | T090 pone `GestionPersonal` en la fila "Autorización de citas", pero el Gate es de personal. | Una fila coherente con el plan. → `/tasks --fix` |

## Decisiones pendientes del usuario
Resueltas el 2026-10-02:
- **A1:** se corrige en la 027 con un token de borde de control ≥ 3:1, aplicado solo a los campos de hora del horario. El resto de `.field` sigue en DS2 y el objetivo 6.
- **A15:** el horario sin días se confirma tal como dice CA5.
- **Resto:** los hallazgos que tocan contratos se corrigen con `--fix`; los demás se aceptan como notas.

## Aceptados
Aceptados por el usuario el 2026-10-02:
- **A4** → nota de T010, T011: cada test que hoy pasaría por afirmar una ausencia lleva una cita de control que sí debe aparecer (o exige `H:i`) para fallar por la razón esperada; si no, se declara de caracterización y se anota su rotura.
- **A9** → nota de T001, T003: un solo token de foco, ≥ 3:1, con el mismo nombre en `system.md`, `system.html` y `app.css` (sin dejar `--color-focus` y `--focus-ring` con valores distintos).
- **A12** → nota de T011, T020: `disponible = 0` cuenta como día sin horario, igual que `ReservaCitas`; sin `DB::raw`/`whereRaw`; caso en `test_la_ficha_lista_las_citas_futuras_fuera_del_horario`.
- **A14** → roadmap: al rehacer la 014 y la 020, decidir si la denegación 404 de `definir-horario` se audita, con su test.
- **A16** → nota de T023: "Citas fuera del horario" va antes de "Horarios de atención", con un encabezado de sección real.
- **A17** → nota de T001: la fila de Decisiones de `system.md` 1.1.0 justifica la escala de espaciado.
- **A18** → nota de T003, T004: la comparación con la línea base excluye los tests nuevos de la 027.
