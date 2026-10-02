---
spec: 025-agenda-unica
verdict: changes_requested
round: 2
date: 2026-10-01
base: d76594e
head: ba2ea8e
human_signoff: no requerido
---

# Review · 025 Regla de agenda única sin doble reserva

## Resumen
**changes_requested.** Los siete importantes de la ronda 1 (R1–R7) quedan corregidos y probados.
R10, que se había aceptado como menor, también queda resuelto, con T024.

La corrección de R4, que lee las citas candidatas con bloqueo, abre un hallazgo importante nuevo,
R15. Comprobado con `EXPLAIN`: MariaDB usa `idx_citas_fecha`, así que el `FOR UPDATE` bloquea
citas de otros dentistas en ese tramo del día. Dos reservas simultáneas de dentistas distintos
pueden esperarse o interbloquearse, y una recibiría un "no disponible" falso (CA7 bajo
concurrencia). El usuario decide corregirlo.

Conteo de esta ronda: bloqueante 0 · importante 1 · menor 1.

Ronda anterior: [history/review.r1.md](../history/review.r1.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 76 passed (402 assertions) |
| `vendor/bin/pint --test` sobre los 9 archivos PHP tocados | ✅ sin cambios |
| `npm run build` | ✅ código 0 |
| `python .ai/bin/aidd.py validate docs/specs/025-agenda-unica` | ✅ 0 errores |
| `php artisan test --filter='ReglaAgendaTest\|ReservaConcurrenteTest\|AltaCitasValidacionTest'` (RNF < 10 s) | ✅ 8,8 s y 8,9 s |
| Restos de `ReservaConcurrenteTest` en `dentissa_test` | ✅ ninguno (8 usuarios, 1 dentista, 0 horarios, 2 citas, 6 pacientes) |

## Seguimiento de la ronda anterior
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `ReservaCitas::reservar` rechaza `duracion_min < 1` con "La duración del servicio no es válida." (`ReservaCitas.php:76-78`). `ReglaAgendaTest::test_un_servicio_de_duracion_cero_o_negativa_no_agenda_encima_de_otra_cita` fallaba antes. |
| R2 | corregido | `string` antes de `exists` en `dentista_id` (`ReservaCitas.php:43`), `paciente_id` y `servicio_id` (los dos controladores), con mensajes en español. `AltaCitasValidacionTest::test_los_identificadores_enviados_como_array_se_rechazan_sin_error_500`. |
| R3 | corregido | `$data['servicio_id'] ?? null` en `CitasController@store`. `AltaCitasValidacionTest::test_el_personal_puede_agendar_sin_enviar_servicio`. |
| R4 | corregido con efecto nuevo (→ R15) | Lectura de citas con `lockForUpdate()` y "no disponible" si no existe la fila del dentista (`ReservaCitas.php:101-106, 160`). Tests `test_la_reserva_ve_la_cita_que_otra_conexion_confirmo_despues_de_su_primera_lectura` y `test_un_dentista_que_ya_no_existe_responde_no_disponible`. |
| R5 | corregido | CA1 compara estado, errores y citas, y comprueba el resultado esperado por caso. Se añaden el borde de 1 minuto y la cita `confirmada`. Se comprobó rompiendo el código. |
| R6 | corregido | `tearDown` con `finally` anidados; `confirmar()` apunta cada fila al insertarla; dos ejecuciones sin restos. |
| R7 | corregido | `test_un_error_sql_que_no_es_de_bloqueo_se_relanza_tal_cual` (1452), control del listener de logs y `::class`. Se comprobó rompiendo el código. |
| R10 | corregido (T024) | Los dos tests de bloqueo real se unen en `test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`. Los tests de la regla pasan de ~11 s a 8,8 s. |
| R8, R9, R11–R14 | aceptados (sin cambio) | El código nuevo no los empeora. |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` no muestra archivos sin tarea. T024 se añadió
  durante `/implement` con la decisión del usuario.
- El plan sigue citando `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500` (TM2 y
  Trazabilidad), que se unió al test de TM1 en T024 → R16.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Tareas añadidas por `/review` antes del código. |
| P2 | ✅ / ❌ aceptado | Los tests de R1–R4 fallaban antes (8806966). Los de R5–R7 se comprobaron rompiendo, según la excepción aceptada. |
| P3 | ❌ aceptado | Sin cambios de autorización. |
| P4 | ✅ | `string` antes de `exists` en los tres ids; ya no hay 500 por entradas mal formadas conocidas. |
| P5 | ✅ | gitleaks sin fugas. |
| P6 | ✅ | Sin dependencias nuevas. |
| P7 | ❌ aceptado | Sin cambios. |
| P8 | ❌ aceptado | Sin logs nuevos; el residuo OB4.a no empeora. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos (gitleaks, `f8c9aca..HEAD`) | ✅ 0 hallazgos |
| SAST (semgrep `p/php`, baseline f8c9aca) | ✅ 0 hallazgos nuevos |
| SCA (`composer audit --locked …` y `npm audit --audit-level=high`) | ✅ código 0 |
| Contenedores / FS | ➖ Sin cambios de dependencias. |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Validación e inyección | ✅ | R2 y R3 corregidos y probados. |
| A06:2025 Concurrencia | ✅ / ❌ → T025, T026 | La visibilidad de la cita confirmada está corregida y probada (R4). Pero la lectura con bloqueo usa el índice por fecha y bloquea citas de otros dentistas (R15). |
| A01:2025 Divulgación en mensajes | ✅ | El mensaje nuevo de duración no lleva datos de otras citas. |

## Observabilidad
- Eventos de auditoría: no aplica; las excepciones P7 no cambian.
- Datos sensibles en logs: ninguno nuevo. `ReservaConcurrenteTest` comprueba que no se registra SQL
  ni datos de la cita, y ahora verifica también que el listener de logs funciona.
- `request_id`: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R15 | importante | `app/Agenda/ReservaCitas.php:154-161` | La lectura con bloqueo de las citas candidatas usa `idx_citas_fecha` (comprobado con `EXPLAIN`): el `FOR UPDATE` bloquea las citas de todos los dentistas en el tramo del día. Una reserva de otro dentista a la vez espera o se interbloquea, y si pierde recibe un "no disponible" falso (CA7 bajo concurrencia). | Forzar `idx_citas_dentista` en esa lectura → T025, T026 |
| R16 | menor | `docs/specs/025-agenda-unica/plan.md` (TM2 y Trazabilidad) | El plan cita `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500`, que T024 unió a `test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`; sus aserciones están en el test unido. | Aceptado sin tarea |

## Preparación para release
- Rollback factible: sí. Basta revertir el código; no hay migraciones.
- Migraciones: no hay.
- Feature flags: no aplica (D8).
- Docs: `architecture.md` y `security.md` siguen al día; los cambios de esta ronda no las afectan.
- Vulnerabilidades críticas o altas sin excepción: ninguna.

## Tareas añadidas
- T025 ← R15 (test)
- T026 ← R15 (corrección)

De la ronda 1 (hechas): T015 ← R1 · T016 ← R2, R3 · T017 ← R4 · T018 ← R6, R7 · T019 ← R5 · T022 ← R1, R2, R4 · T023 ← R2, R3. Añadida durante `/implement`: T024 ← R10.

## Aceptados sin tarea
- **R8** → roadmap: `SET SESSION innodb_lock_wait_timeout` en `ReservaCitas` deja 5 s fijados en la conexión para el resto de la petición; restaurar el valor o usar `SET STATEMENT … FOR` si aparecen procesos persistentes.
- **R9** → roadmap: `ReservaCitas::reservar()` convierte `DeadlockException` en no disponible; documentar o impedir que se llame dentro de otra transacción.
- **R11** → roadmap: extraer los helpers de fixtures de agenda de los 5 tests a un trait común.
- **R12** → roadmap: la búsqueda de solapes empieza en el inicio del día; revisar si la spec 027 permite franjas que crucen la medianoche.
- **R13** → roadmap: el test de la espera de 5 s depende del texto del SQL; comprobar el efecto (`@@SESSION.innodb_lock_wait_timeout`) si se pasa a binding.
- **R14** → roadmap: el test de orden debería exigir que el bloqueo sea la primera lectura de la transacción.
- **R16** → roadmap: actualizar en el plan de la 025 (TM2 y Trazabilidad) el nombre del test unido en T024.
