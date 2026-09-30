---
spec: 025-agenda-unica
plan: plan.md
status: draft
---

# Tareas · 025 Regla de agenda única sin doble reserva

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).
- Versión anterior: `history/tasks.v1.md`, regenerada con `--redo` tras `/analyze` ronda 1 y `/plan --fix`.

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 025 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA2, CA3, CA6 y CA7 describen comportamiento que ya se cumple hoy, así que sus tests son de caracterización y no pueden fallar antes; cada uno se comprueba rompiendo la regla un momento — aprobado por el usuario el 2026-09-29 | T010–T014 se escriben antes de implementar. CA1, CA4, CA5 (un sentido), CA8, CA9 y CA10 fallan hoy; T020 y T021 dependen de esos tests. La comprobación rompiendo de CA2, CA3, CA6, CA7 y el otro sentido de CA5 va en T021. La de los tests de rol y de flujo, en T013 y T014. |
| P3 Autorización en servidor | ❌ aceptado: los dos endpoints modificados siguen sin Policy ni Gate hasta la spec 014, que los introduce (ADR 0003); el alcance del paciente en el alta del personal es de la 014 y el rechazo por rol en `/cliente/*` es de la 015 — aprobado por el usuario el 2026-09-30 | T013 prueba, por rol, el caso permitido y el denegado: admin, dentista, asistente, cliente en el alta del personal, y cliente con otro paciente. Ninguna tarea cambia middlewares ni `autorizadoParaCita`. |
| P4 Entrada validada y SQL con bindings | ✅ | T020 y T021 validan `fecha`, `hora` y `dentista_id` (tipo y usuario activo) y retiran el `whereRaw` de la regla; T040 corre semgrep sobre los archivos de `app/` tocados. |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. |
| P7 Auditoría de datos sensibles | ❌ aceptado: el alta guarda notas de la cita (dato de salud) sin evento de auditoría; auditarla queda para la spec 022, porque la 025 no cambia qué notas se guardan ni quién puede guardarlas — aprobado por el usuario el 2026-09-30 | T041 lo anota en el roadmap. |
| P8 Correlación y logs limpios | ➖ | Ninguna tarea añade llamadas a `Log::`. T011 comprueba que ni la reserva ni el rechazo por bloqueo escriben logs. |

## Preparación
- [ ] T001 Registrar la línea base en `feature/025-agenda-unica`: `php artisan test` (recuento en verde); en `dentissa_test`, número de dentistas, horarios y citas; que hoy una hora mal formada ("25:99") y una fecha con hora ("2026-10-05 10:00") en `POST /admin/citas` dan error 500; y que `vendor/bin/pint --test` falla en los dos controladores de citas — docs/specs/025-agenda-unica/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cuatro datos
- [ ] T002 Aplicar solo formato con `vendor/bin/pint` a los dos controladores de citas, sin cambios de lógica, en un commit propio y anterior a cualquier otro cambio de código (D10) — app/Http/Controllers/CitasController.php, app/Http/Controllers/ClienteCitasController.php — hecho cuando: `pint --test` no reporta nada en ambos; `php artisan test` da el mismo recuento que T001; el diff solo contiene cambios de las reglas de Pint, que se listan en la nota — depende: T001

## Tests (antes de implementar)
- [ ] T010 Escribir `ReglaAgendaTest` con los tests de CA1–CA7 del plan, cada caso separado por flujo (métodos o data providers con nombre `personal` y `paciente`, filtrables con `--filter`): personal como admin en `POST /admin/citas` y paciente en `POST /cliente/agendar`. Cada test crea su horario, sus pacientes y, para CA7, un segundo dentista — tests/Feature/ReglaAgendaTest.php — hecho cuando: fallan por la razón esperada CA1 (mensaje de solape distinto entre flujos), CA4 en el flujo del personal (otro texto) y "termina cuando empieza otra" de CA5 en ambos flujos (hoy se rechaza); los demás pasan hoy (caracterización, P2); `pint --test` sin cambios — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7 — depende: T002
- [ ] T011 Escribir `ReservaConcurrenteTest` según el plan, con los tests de TM1 y TM2. Una segunda conexión copia la configuración de la conexión por defecto; crea y confirma un usuario dentista, su fila en `personal` y su horario; los borra en `tearDown`; y retiene `FOR UPDATE` sobre el dentista. La conexión de la app no escribe filas que referencien a ese dentista. Ambas sesiones con `innodb_lock_wait_timeout = 1`, y `ReservaCitas` enlazado en el contenedor con 1 s. Los tests: bloqueo por flujo, bloqueo agotado sin 500, orden bloqueo → lectura con `DB::listen`, interbloqueo 1213 con una conexión simulada, y sin logs con `Log::spy()` — tests/Feature/ReservaConcurrenteTest.php — hecho cuando: fallan por la razón esperada los de bloqueo (hoy el insert espera por la clave foránea, se agota en 1 s y responde 500), el de orden (hoy no hay `for update`), el de interbloqueo (no existe `ReservaCitas`) y el de logs (hoy el 500 se registra); el test termina en pocos segundos, sin colgarse; `pint --test` sin cambios — cubre: CA8 — depende: T002
- [ ] T012 Escribir `AltaCitasValidacionTest`, por flujo, con horas mal formadas ("25:99", "abc"), fechas mal formadas ("2026-10-05 10:00", "abc") y dentistas inválidos (asistente, administrador, dentista con usuario desactivado e id inexistente). Comprueba el texto exacto en español de Contratos y que la sesión conserva `fecha`, `hora` y `dentista_id` (tests de TM3 y TM4) — tests/Feature/AltaCitasValidacionTest.php — hecho cuando: fallan por la razón esperada (hoy la hora y la fecha mal formadas dan 500; el asistente, el administrador y el desactivado no dan error en `dentista_id`; los mensajes no existen); `pint --test` sin cambios — cubre: CA9, CA10 — depende: T002
- [ ] T013 Escribir `AltaCitasRolesTest` con los tests de rol de P3 del plan: admin permitido; dentista para sí mismo permitido y para otro → 403; asistente con dentista asignado permitido y con uno no asignado → 403 (`asistente_dentista` creado en el test); cliente en el alta del personal → 403; cliente permitido; y cliente con un `paciente_id` ajeno inyectado, que se ignora. Los pacientes están dentro del alcance del dentista que agenda — tests/Feature/AltaCitasRolesTest.php — hecho cuando: pasan hoy y cada uno se comprueba rompiendo un momento lo que protege (middleware de rol del grupo, `autorizadoParaCita` y el `paciente_id` tomado del usuario), con nota; `pint --test` sin cambios — depende: T002
- [ ] T014 Escribir `AltaCitasFlujosTest` con las diferencias que se mantienen por flujo: la cita del personal guarda la duración elegida, el servicio nulo, `notas_paciente`, `notas_internas` y `creado_por`; la del paciente toma la duración del servicio; el paciente debe elegir servicio; y solo el personal puede agendar en fecha pasada (A16) — tests/Feature/AltaCitasFlujosTest.php — hecho cuando: pasan hoy y cada uno se comprueba rompiendo un momento lo que protege, con nota; `pint --test` sin cambios — depende: T002

## Implementación
- [ ] T020 Crear `App\Agenda\CitaNoAgendable` y `App\Agenda\ReservaCitas` según el plan (D1–D4, D11): constructor con la conexión y los segundos de espera (5 por defecto), registrado en el contenedor; `reservar(array $cita): string` fija la espera, bloquea **primero** la fila del dentista con `lockForUpdate()`, después lee el horario y comprueba encaje y solape estricto (en PHP), e inserta en `agendada`; 1205 y 1213 se convierten en `CitaNoAgendable`, sin logs. `CitasController@store` recibe `ReservaCitas` por inyección y valida con los mensajes en español de Contratos: `fecha` → `date_format:Y-m-d`; `hora` → `date_format:H:i`; `dentista_id` → `Rule::exists` con `tipo=dentista` y usuario activo. Convierte `CitaNoAgendable` en error del campo `hora` con `withInput()` y elimina su copia de la regla (controles TM1–TM5 en el flujo del personal) — app/Agenda/ReservaCitas.php, app/Agenda/CitaNoAgendable.php, app/Http/Controllers/CitasController.php — hecho cuando: pasan los casos del personal de T010 y T012 (`--filter` por el nombre del flujo) y todos los de T011 excepto los del flujo del paciente; T013 y T014 siguen pasando; el método `store` no contiene `whereRaw` ni consulta `horarios_dentista` (el `whereRaw` del buscador de `index` queda fuera); `pint --test` sin cambios en los tres archivos — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10 — depende: T010, T011, T012, T013, T014
- [ ] T021 `ClienteCitasController@agendarStore` recibe `ReservaCitas` por inyección y aplica las mismas reglas y mensajes de `fecha` (más `after_or_equal:today`), `hora` y `dentista_id`. Conserva el servicio obligatorio y la duración del servicio, usa el mismo manejo de `CitaNoAgendable` y elimina su copia de la regla (controles TM1–TM5 en el flujo del paciente) — app/Http/Controllers/ClienteCitasController.php — hecho cuando: T010–T014 pasan completos; `agendarStore` no contiene `whereRaw` ni consulta `horarios_dentista`; `pint --test` sin cambios; comprobado rompiendo un momento `ReservaCitas`, con una nota por caso: quitar la comprobación de `disponible` (CA2), mover el borde final de la franja (CA3), dejar de excluir `cancelada` (CA6), quitar el filtro por dentista (CA7) y hacer inclusivo el solape (CA5, sentido "empieza cuando termina otra"). Cada test afectado falla y después se restaura. — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10 — depende: T020

## Integración y documentación
- [ ] T040 Verificación final — (sin archivos) — hecho cuando: `php artisan test` completo da la línea base de T001 más los tests de T010–T014, todos en verde; `vendor/bin/pint --test` sobre los 9 archivos PHP tocados, sin cambios; `php artisan test --filter='ReglaAgendaTest|ReservaConcurrenteTest|AltaCitasValidacionTest'` dura menos de 10 s; `semgrep scan --config p/php --error` sobre los 4 archivos de `app/` tocados, con 0 hallazgos; `python .ai/bin/aidd.py validate docs/specs/025-agenda-unica`, sin errores. — cubre: CA1, CA8, CA9, CA10 — depende: T021
- [ ] T041 Añadir en `docs/roadmap.md → Pendientes` cuatro viñetas (D7, D9, D13) — docs/roadmap.md — hecho cuando: las cuatro viñetas existen y citan su decisión y la spec 025: propuesta de enmienda MINOR de P2, citando las excepciones de la 024 y la 025; auditoría del alta de citas con notas → spec 022 (excepción P7); ajuste de `/plan 014 --fix` sobre el `store` que deja la 025; límite de peticiones en las rutas de escritura (TM6).
- [ ] T090 Actualizar `docs/architecture.md`: "Resumen" (primera pieza de servicio en `app/Agenda/`), "Módulos" (las filas Agenda dicen que la regla vive en `app/Agenda/ReservaCitas.php`), paso 4 del "Flujo principal" (comprobación e insert en una transacción con bloqueo del dentista) y "Deuda técnica" (se retira la regla de agenda duplicada)
- [ ] T091 Actualizar `docs/security.md → RS12`, por corrección: RS12.a → "en curso (spec 025)"; RS12.c sigue "pendiente", con la nota "agendado: spec 025; pacientes, expedientes y asignación de asistentes: spec 013" (D5); RS12.b sin cambios (→ 027). `docs/deployment.md` y `docs/observability.md` sin cambios
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T040 | T020, T021 |
| CA2 | T010 | T020, T021 |
| CA3 | T010 | T020, T021 |
| CA4 | T010 | T020, T021 |
| CA5 | T010 | T020, T021 |
| CA6 | T010 | T020, T021 |
| CA7 | T010 | T020, T021 |
| CA8 | T011, T040 | T020, T021 |
| CA9 | T012, T040 | T020, T021 |
| CA10 | T012, T040 | T020, T021 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T021 | T011 |
| TM2 | T020, T021 | T011 |
| TM3 | T020, T021 | T012 |
| TM4 | T020, T021 | T012 |
| TM5 | T020, T021 | T010 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Agenda/ReservaCitas.php | T020 |
| app/Agenda/CitaNoAgendable.php | T020 |
| app/Http/Controllers/CitasController.php y ClienteCitasController.php (formato previo) | T002 |
| app/Http/Controllers/CitasController.php | T020 |
| app/Http/Controllers/ClienteCitasController.php | T021 |
| tests/Feature/ReglaAgendaTest.php | T010 |
| tests/Feature/ReservaConcurrenteTest.php | T011 |
| tests/Feature/AltaCitasValidacionTest.php | T012 |
| tests/Feature/AltaCitasRolesTest.php | T013 |
| tests/Feature/AltaCitasFlujosTest.php | T014 |
| docs/architecture.md | T090 |
| docs/security.md | T091 |
| docs/roadmap.md | T041 |
