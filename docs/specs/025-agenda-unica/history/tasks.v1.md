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

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 025 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA2, CA3, CA6 y CA7 describen comportamiento que ya se cumple hoy, así que sus tests son de caracterización y no pueden fallar antes; cada uno se comprueba rompiendo la regla un momento — aprobado por el usuario el 2026-09-29 | T010–T012 se escriben antes de implementar. CA1, CA4, CA5 (en un sentido), CA8, CA9 y CA10 fallan hoy. T020 y T021 dependen de esos tests. La comprobación rompiendo de CA2, CA3, CA6, CA7 y el otro sentido de CA5 va en T021. |
| P3 Autorización en servidor | ❌ aceptado: en `POST /cliente/agendar` no hay test del caso denegado porque hoy otros roles pueden entrar (004 CA7), y corregirlo es de la spec 015 — aprobado por el usuario el 2026-09-29 | T013 cubre el alta del personal (permitido y 403) y el caso permitido del paciente. Ninguna tarea cambia middlewares ni `autorizadoParaCita`. |
| P4 Entrada validada y SQL con bindings | ✅ | T020 y T021 validan `hora` con `date_format:H:i` y `dentista_id` con `tipo=dentista`, y retiran el `whereRaw`; T040 corre semgrep sobre los archivos tocados. |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias. |
| P7 Auditoría de datos sensibles | ➖ | Sin eventos nuevos (spec 025, Auditoría → 022). |
| P8 Correlación y logs limpios | ➖ | Ninguna tarea añade llamadas a `Log::`. |

## Preparación
- [ ] T001 Registrar la línea base en `feature/025-agenda-unica`: `php artisan test` (recuento en verde); en `dentissa_test`, número de dentistas, de horarios y de citas; y que hoy una hora mal formada en `POST /admin/citas` da error 500 — docs/specs/025-agenda-unica/tasks.md — hecho cuando: la nota bajo esta tarea recoge los tres datos

## Tests (antes de implementar)
- [ ] T010 Escribir `ReglaAgendaTest` con los tests de CA1–CA7 del plan, cada caso por los dos flujos: personal como admin en `POST /admin/citas` y paciente en `POST /cliente/agendar`. Cada test crea su horario, sus pacientes y, para CA7, un segundo dentista — tests/Feature/ReglaAgendaTest.php — hecho cuando: fallan por la razón esperada `test_el_personal_y_el_paciente_obtienen_el_mismo_resultado_para_la_misma_cita` (mensaje de solape distinto entre flujos), `test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` (el personal recibe otro texto) y `test_una_cita_que_termina_cuando_empieza_otra_se_acepta` (hoy se rechaza); los demás pasan hoy (caracterización, P2); `vendor/bin/pint --test` sin cambios — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7 — depende: T001
- [ ] T011 Escribir `ReservaConcurrenteTest`: una segunda conexión a `dentissa_test`, definida en el test, abre una transacción y bloquea la fila del dentista del volcado; la reserva, con `ReservaCitas` enlazado en el contenedor con 1 s de espera, se intenta por los dos flujos (tests de TM1 y TM2) — tests/Feature/ReservaConcurrenteTest.php — hecho cuando: `test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista` y `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500` fallan por la razón esperada (hoy la cita se guarda sin esperar al bloqueo); la segunda conexión se cierra en `tearDown`; `pint --test` sin cambios — cubre: CA8 — depende: T001
- [ ] T012 Escribir `AltaCitasValidacionTest` con horas mal formadas ("25:99", "abc") y dentistas inválidos (un asistente y un administrador creados en el test, y un id inexistente), por los dos flujos (tests de TM3 y TM4) — tests/Feature/AltaCitasValidacionTest.php — hecho cuando: `test_una_hora_mal_formada_se_rechaza_sin_error_500` falla porque hoy responde 500; `test_un_dentista_de_otro_tipo_o_inexistente_se_rechaza` falla porque hoy no hay error en `dentista_id` para el asistente ni el administrador; `pint --test` sin cambios — cubre: CA9, CA10 — depende: T001
- [ ] T013 Escribir `AltaCitasRolesTest` con los cuatro tests de rol de P3 del plan — tests/Feature/AltaCitasRolesTest.php — hecho cuando: los cuatro pasan hoy y cada uno se comprueba rompiendo un momento lo que protege (el middleware de rol del grupo del personal y `autorizadoParaCita`), con nota; `pint --test` sin cambios — depende: T001

## Implementación
- [ ] T020 Crear `App\Agenda\CitaNoAgendable` y `App\Agenda\ReservaCitas` según D1–D4 del plan. `reservar(array $cita): string` abre una transacción, fija `innodb_lock_wait_timeout` (5 s por defecto, configurable en el constructor), bloquea la fila del dentista con `lockForUpdate()` y comprueba horario del día, encaje y solape estricto (en PHP, sobre las citas del mismo día y dentista no `cancelada` ni `no_asistio`). Inserta la cita en `agendada` y convierte 1205/1213 en `CitaNoAgendable`. `CitasController@store`: `hora` → `required|date_format:H:i`, `dentista_id` → `Rule::exists('personal','id')->where('tipo','dentista')`; llama al servicio y devuelve `CitaNoAgendable` como error del campo `hora` con `withInput()`; se elimina su copia de la regla (controles TM1, TM2, TM3, TM4, TM5) — app/Agenda/ReservaCitas.php, app/Agenda/CitaNoAgendable.php, app/Http/Controllers/CitasController.php — hecho cuando: los casos del flujo del personal de T010, T011 y T012 pasan; T013 sigue pasando; `CitasController` no contiene `whereRaw` ni consulta `horarios_dentista`; `pint --test` sin cambios en los tres archivos — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10 — depende: T010, T011, T012, T013
- [ ] T021 `ClienteCitasController@agendarStore`: las mismas reglas de `hora` y `dentista_id`; conserva el servicio obligatorio, la duración del servicio y `after_or_equal:today`; llama a `ReservaCitas::reservar` con el mismo manejo de `CitaNoAgendable`; se elimina su copia de la regla (controles TM1–TM5 en el flujo del paciente) — app/Http/Controllers/ClienteCitasController.php — hecho cuando: T010, T011, T012 y T013 pasan completos; comprobado rompiendo un momento `ReservaCitas`, con nota por caso: quitar la comprobación de `disponible` (CA2), mover el borde final de la franja (CA3), dejar de excluir `cancelada` (CA6), quitar el filtro por dentista (CA7) y hacer inclusivo el solape (CA5, sentido "empieza cuando termina otra"); cada test afectado falla y se restaura; `ClienteCitasController` no contiene `whereRaw` ni consulta `horarios_dentista`; `pint --test` sin cambios — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10 — depende: T020

## Integración y documentación
- [ ] T040 Verificación final: `php artisan test` completo; `vendor/bin/pint --test` sobre los 8 archivos PHP tocados; `php artisan test --filter='ReglaAgendaTest|ReservaConcurrenteTest|AltaCitasValidacionTest'` dura menos de 10 s; `semgrep scan --config p/php --error` sobre los 3 archivos de `app/` tocados; `python .ai/bin/aidd.py validate docs/specs/025-agenda-unica` — (sin archivos) — hecho cuando: la suite es la línea base de T001 más los tests de T010–T013, todos en verde; Pint sin cambios; la regla corre en menos de 10 s; semgrep con 0 hallazgos; validador sin errores — cubre: CA1, CA8, CA9, CA10 — depende: T021
- [ ] T041 Añadir en `docs/roadmap.md → Pendientes` la propuesta de enmienda MINOR de P2 (distinguir cambios de comportamiento de tests de caracterización), citando las excepciones de la 024 y la 025 (D9) — docs/roadmap.md — hecho cuando: la viñeta existe y cita D9 y las specs 024 y 025
- [ ] T090 Actualizar `docs/architecture.md`: en "Módulos", las filas Agenda (personal y cliente) dicen que la regla vive en `app/Agenda/ReservaCitas.php`; en "Deuda técnica" se retira la regla de agenda duplicada
- [ ] T091 Actualizar `docs/security.md → RS12`, por corrección: RS12.a → "en curso (spec 025)"; RS12.c → "en curso (spec 025: agendado; resto → 013)"; RS12.b sin cambios (→ 027). `docs/deployment.md` y `docs/observability.md` sin cambios
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
| app/Http/Controllers/CitasController.php | T020 |
| app/Http/Controllers/ClienteCitasController.php | T021 |
| tests/Feature/ReglaAgendaTest.php | T010 |
| tests/Feature/ReservaConcurrenteTest.php | T011 |
| tests/Feature/AltaCitasValidacionTest.php | T012 |
| tests/Feature/AltaCitasRolesTest.php | T013 |
| docs/architecture.md | T090 |
| docs/security.md | T091 |
| docs/roadmap.md | T041 |
