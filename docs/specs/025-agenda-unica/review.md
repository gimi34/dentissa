---
spec: 025-agenda-unica
verdict: changes_requested
round: 1
date: 2026-10-01
base: f8c9aca
head: d76594e
human_signoff: no requerido
---

# Review · 025 Regla de agenda única sin doble reserva

## Resumen
**changes_requested.** No hay bloqueantes: los diez criterios tienen tests que pasan y la
verificación automática está limpia. El usuario decide corregir los siete hallazgos importantes
antes de liberar:
- **R1:** un servicio de 0 minutos o con duración negativa permite agendar encima de otra cita.
- **R2 y R3:** dos casos dan 500 (verificado): identificadores enviados como array y la falta de
  `servicio_id` en el alta del personal.
- **R4:** la lectura de citas no bloquea, y ningún test prueba que la segunda reserva vea la cita
  ya confirmada.
- **R5–R7:** calidad de los tests: CA1 no mira el estado HTTP, la limpieza del test de concurrencia
  es frágil y no se prueba que un error SQL ajeno se relance.

Cuatro menores baratos se incorporan a esas tareas y los otros siete se aceptan para el roadmap.

Conteo: bloqueante 0 · importante 7 · menor 11 (4 incorporados a tareas y 7 aceptados sin tarea).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 67 passed (350 assertions); línea base de T001: 24 |
| `vendor/bin/pint --test` sobre los 9 archivos PHP tocados | ✅ sin cambios |
| `npm run build` | ✅ código 0 |
| `python .ai/bin/aidd.py validate docs/specs/025-agenda-unica` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReglaAgendaTest::test_el_personal_y_el_paciente_obtienen_el_mismo_resultado_para_la_misma_cita` | ⚠ | Pasa, pero no mira el estado HTTP ni las citas guardadas (R5 → T019). |
| CA2 | `ReglaAgendaTest::test_un_dia_sin_horario_o_no_disponible_se_rechaza` | ✅ | |
| CA3 | `ReglaAgendaTest::test_una_cita_fuera_de_la_franja…`, `test_los_bordes_exactos…` | ✅ | |
| CA4 | `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` | ⚠ | Se puede saltar con un servicio de duración ≤ 0 (R1 → T015, T022); falta el borde de 1 minuto (→ T019). |
| CA5 | `ReglaAgendaTest::test_una_cita_que_termina…`, `test_una_cita_que_empieza…` | ✅ | |
| CA6 | `ReglaAgendaTest::test_las_citas_canceladas_o_no_asistio_no_bloquean_el_hueco` | ✅ | `confirmada` sin probar (→ T019). |
| CA7 | `ReglaAgendaTest::test_la_cita_de_otro_dentista_no_bloquea_el_hueco` | ✅ | |
| CA8 | `ReservaConcurrenteTest` (bloqueo, orden, espera, interbloqueo, logs) | ⚠ | El código serializa bien en producción, pero ningún test prueba que se vea la cita confirmada por otra conexión, y la lectura no bloquea (R4 → T017, T022). |
| CA9 | `AltaCitasValidacionTest::test_una_hora_mal_formada…`, `test_una_fecha_mal_formada…` | ⚠ | Los ids enviados como array y la falta de `servicio_id` dan 500 (R2, R3 → T016, T022, T023). |
| CA10 | `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza` | ⚠ | `dentista_id[]` pasa la validación y da 500 (R2). |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` no muestra archivos sin tarea. T001 y T041 solo
  tocan documentación. El commit fd749f9 es el formato previo con Pint que pide T002 (D10).
- Partes del plan sin implementar: ninguna.
- La corrección de R1 añade un mensaje que el plan no listaba en Contratos: "La duración del
  servicio no es válida." Lo decidió el usuario el 2026-10-01 y queda registrado en T022.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, plan y tareas aprobados antes del código (impl_base f8c9aca). |
| P2 | ✅ / ❌ aceptado | Los tests que debían fallar antes fallaban (d17d974). Los de caracterización (CA2, CA3, CA6, CA7, roles y flujos) se comprobaron rompiendo, según la excepción aceptada. |
| P3 | ❌ aceptado | Sin Policy ni Gate (→ 014/015); se conservan los middlewares y `autorizadoParaCita`, y los tests de rol cubren permitido y denegado. |
| P4 | ⚠ | `bail` y `date_format` en fecha y hora, `exists` restringido, Query Builder con bindings y sin `whereRaw` en la regla. Pero los ids enviados como array pasan `exists` (R2), y `servicio_id` ausente da 500 (R3). El `SET SESSION … = (int)` concatena un entero propio: es aceptable. |
| P5 | ✅ | gitleaks sin fugas. |
| P6 | ✅ | Sin dependencias nuevas; las auditorías dan 0. |
| P7 | ❌ aceptado | Auditoría del alta con notas → 022 y del 403 → 020. |
| P8 | ❌ aceptado | Residuo OB4.a → 023. No hay logs nuevos, y el test comprueba que los logs no llevan SQL ni datos. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos (gitleaks v8.21.2, `f8c9aca..HEAD`) | ✅ 0 hallazgos |
| SAST (semgrep 1.99.0, `--config p/php --baseline-commit f8c9aca`) | ✅ 0 hallazgos nuevos |
| SCA (`composer audit --locked --ignore-severity=low --ignore-severity=medium && npm audit --audit-level=high`) | ✅ crít: 0 · alta: 0 (sin cambios de dependencias) |
| Contenedores / FS (trivy) | ➖ Sin cambios de dependencias ni de imágenes en el rango. |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `role:` en las rutas y `autorizadoParaCita` antes de reservar; `AltaCitasRolesTest`. `/cliente` solo con `auth` (excepción P3 → 015). |
| A05:2025 Validación e inyección | ❌ → T016, T022, T023 | Fecha, hora y dentista validados, con bindings. Los ids como array pasan `exists` y dan 500 (R2), y `servicio_id` ausente da 500 (R3). |
| A01:2025 Divulgación en mensajes | ✅ | Mensajes fijos en `CitaNoAgendable`; el test de CA4 comprueba que no aparecen el nombre ni el servicio de la otra cita. |
| A06:2025 Concurrencia (TM1) | ✅ código / ❌ test → T017, T022 | En producción, el bloqueo va antes de la primera lectura. Pero la lectura de citas no bloquea y depende de no haber una transacción exterior, y no hay test de visibilidad de la cita confirmada (R4). R1 permite saltar el solape con una duración ≤ 0. |
| A06:2025 DoS por espera (TM2) | ✅ | Espera de 5 s; 1205/1213 → no disponible en sus dos formas. TM6 queda fuera de alcance (D13). |
| A09:2025 Logs | ✅ | Sin logs nuevos; residuo P8 aceptado. |
| CSRF | ✅ | Grupo `web`; las vistas con `@csrf` no se tocaron. |
| Mass assignment de `paciente_id` (cliente) | ✅ | Sale de la sesión; `AltaCitasRolesTest` lo comprueba. |
| Proceso: rutas protegidas e instrucciones a agentes | ✅ | Sin cambios en `.github`, `docker`, `database/dentissa.sql` ni `aidlc`. Ninguna instrucción sospechosa. |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica. La spec no añade eventos
  (excepción P7 → 022/020).
- Datos sensibles en logs del código tocado: ninguno nuevo. El residuo OB4.a está aceptado (P8 →
  023), y `ReservaConcurrenteTest` comprueba que no se registra SQL ni datos de la cita durante un
  bloqueo agotado real.
- `request_id` presente en los logs nuevos: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `app/Agenda/ReservaCitas.php:98` (origen: `ServiciosController.php:21`, solo `required\|integer`) | Con un servicio de duración ≤ 0, `fin <= inicio` y el solape nunca se cumple, así que se acepta una cita encima de otra. Rompe CA4 y CA8. | Rechazar una duración < 1 → T015, T022 |
| R2 | importante | `app/Agenda/ReservaCitas.php:44`; `CitasController.php` (`paciente_id`, `servicio_id`); `ClienteCitasController.php` (`servicio_id`) | `exists` acepta arrays. Verificado: `dentista_id[]` en el alta del personal da 500 (TypeError). | `string` antes de `exists` → T016, T022, T023 |
| R3 | importante | `app/Http/Controllers/CitasController.php:249` | Sin la clave `servicio_id`, `$data['servicio_id'] ?: null` da "Undefined array key". Verificado: 500. Viene de antes, pero la línea se reescribió. | `?? null` → T016, T023 |
| R4 | importante | `app/Agenda/ReservaCitas.php:141-146`; `tests/Feature/ReservaConcurrenteTest.php:89-112` | La lectura de citas no bloquea: dentro de una transacción exterior (como en los tests) vería una vista desfasada. Ningún test prueba que la segunda reserva vea la cita confirmada por otra conexión. Además, no se comprueba que exista la fila bloqueada del dentista. | Lectura con bloqueo y "no disponible" si el dentista ya no existe → T017, T022 |
| R5 | importante | `tests/Feature/ReglaAgendaTest.php:246-251` | El test de CA1 da la cita por aceptada si no hay error en `hora`. Pasaría aunque los dos flujos devolvieran 500 o fallaran en otro campo. | Estado, errores y citas en el resultado, y bordes nuevos → T019 |
| R6 | importante | `tests/Feature/ReservaConcurrenteTest.php:61-79, 184-195` | Si un insert o `parent::tearDown()` falla, quedan filas confirmadas en `dentissa_test`, y el `tearDown` puede fallar por un índice ausente. | Limpieza en `finally` con ids guardados uno a uno → T018 |
| R7 | importante | `tests/Feature/ReservaConcurrenteTest.php:150-155, 258-277` | Solo se prueba el camino 1205/1213 → no disponible. Una implementación que convirtiera cualquier error SQL en "no disponible" pasaría. | Caso 1452 que se relanza → T018 |
| R8 | menor | `app/Agenda/ReservaCitas.php:91` | `SET SESSION innodb_lock_wait_timeout` deja la espera de 5 s fijada en la conexión para el resto de la petición. | Aceptado sin tarea |
| R9 | menor | `app/Agenda/ReservaCitas.php:76-78` | Convertir `DeadlockException` supone que nadie llama a `reservar()` dentro de otra transacción; si alguien lo hiciera, su transacción ya estaría revertida. | Aceptado sin tarea |
| R10 | menor | `tests/Feature/ReservaConcurrenteTest.php:88-112` | Los dos tests de bloqueo real repiten casi el mismo escenario y suman unos 4 s de los 9,3 s del límite de 10 s. | Aceptado sin tarea |
| R11 | menor | Los 5 tests nuevos | Helpers de fixtures duplicados (usuario, dentista, horario, paciente, servicio, mensaje de hora). | Aceptado sin tarea |
| R12 | menor | `app/Agenda/ReservaCitas.php:146` | La búsqueda de candidatos empieza en `startOfDay()` y no ve citas del día anterior que crucen la medianoche. Hoy no es posible, porque la franja es del mismo día, pero depende de que la 027 valide `hora_fin > hora_inicio`. | Aceptado sin tarea |
| R13 | menor | `tests/Feature/ReservaConcurrenteTest.php:142` | La regex del test de la espera exige el valor literal en el SQL; fallaría si se pasara a binding. | Aceptado sin tarea |
| R14 | menor | `tests/Feature/ReservaConcurrenteTest.php:116-132` | El test de orden comprueba que el bloqueo va antes de las lecturas de horario y citas, no que sea la primera lectura de la transacción. | Aceptado sin tarea |

Incorporados a tareas, sin ID propio: que la fila bloqueada del dentista exista (→ T022), el borde
de 1 minuto y la cita `confirmada` (→ T019), el control del listener de logs y `::class` en vez de
cadenas (→ T018).

## Preparación para release
- Rollback factible: sí. Basta revertir el código; no hay migraciones ni cambios de datos.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (D8).
- Docs actualizadas: `docs/architecture.md` (Resumen, Módulos, Flujo principal y Deuda técnica) y
  `docs/security.md` (RS12.a "en curso"; RS12.c "pendiente" con nota, por corrección).
  `deployment.md` sin cambios necesarios.
- Vulnerabilidades críticas o altas sin excepción: ninguna.

## Tareas añadidas
- T015 ← R1
- T016 ← R2, R3
- T017 ← R4
- T018 ← R6, R7
- T019 ← R5
- T022 ← R1, R2, R4
- T023 ← R2, R3

## Aceptados sin tarea
- **R8** → roadmap: `SET SESSION innodb_lock_wait_timeout` en `ReservaCitas` deja 5 s fijados en la conexión para el resto de la petición; restaurar el valor o usar `SET STATEMENT … FOR` si aparecen procesos persistentes.
- **R9** → roadmap: `ReservaCitas::reservar()` convierte `DeadlockException` en no disponible; documentar o impedir que se llame dentro de otra transacción.
- **R10** → roadmap: unir los dos tests de bloqueo real de `ReservaConcurrenteTest` (≈4 s de 9,3 s) si el límite de 10 s se acerca.
- **R11** → roadmap: extraer los helpers de fixtures de agenda de los 5 tests a un trait común.
- **R12** → roadmap: la búsqueda de solapes empieza en el inicio del día; revisar si la spec 027 permite franjas que crucen la medianoche.
- **R13** → roadmap: el test de la espera de 5 s depende del texto del SQL; comprobar el efecto (`@@SESSION.innodb_lock_wait_timeout`) si se pasa a binding.
- **R14** → roadmap: el test de orden debería exigir que el bloqueo sea la primera lectura de la transacción.
