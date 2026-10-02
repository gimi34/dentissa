---
spec: 025-agenda-unica
verdict: approved
round: 3
date: 2026-10-02
base: ba2ea8e
head: 8c3ee06
human_signoff: no requerido
---

# Review · 025 Regla de agenda única sin doble reserva

## Resumen
**approved.** R15, el único importante de la ronda 2, queda corregido y probado. La lectura con
bloqueo de las citas candidatas fuerza el índice del dentista, así que ya no bloquea citas de
otros dentistas. El test nuevo fallaba antes del cambio y `EXPLAIN` confirma el índice.

El diff de esta ronda trae un solo menor (R17), aceptado para el roadmap. Conteo de la ronda:
bloqueante 0 · importante 0 · menor 1.

Ronda anterior: [history/review.r2.md](history/review.r2.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 77 passed (404 assertions); línea base de T001: 24 |
| `vendor/bin/pint --test` sobre los 9 archivos PHP tocados | ✅ sin cambios |
| `npm run build` | ✅ código 0 |
| `python .ai/bin/aidd.py validate docs/specs/025-agenda-unica` | ✅ 0 errores |
| `php artisan test --filter='ReglaAgendaTest\|ReservaConcurrenteTest\|AltaCitasValidacionTest'` (RNF < 10 s) | ✅ 7,2 s, 6,6 s y 6,5 s con el entorno estable; en `/implement` se midieron picos de 10–12 s que afectaban igual a la versión anterior (nota de T026) |
| Restos de `ReservaConcurrenteTest` en `dentissa_test` | ✅ ninguno (8 usuarios, 1 dentista, 0 horarios, 2 citas, 6 pacientes) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReglaAgendaTest::test_el_personal_y_el_paciente_obtienen_el_mismo_resultado_para_la_misma_cita` | ✅ | Compara estado, errores y citas, y comprueba lo esperado por caso. |
| CA2 | `ReglaAgendaTest::test_un_dia_sin_horario_o_no_disponible_se_rechaza` | ✅ | |
| CA3 | `ReglaAgendaTest::test_una_cita_fuera_de_la_franja_se_rechaza_indicando_la_franja`, `test_los_bordes_exactos_de_la_franja_se_aceptan` | ✅ | |
| CA4 | `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita`, `test_un_solape_de_un_minuto_se_rechaza`, `test_un_servicio_de_duracion_cero_o_negativa_no_agenda_encima_de_otra_cita` | ✅ | |
| CA5 | `ReglaAgendaTest::test_una_cita_que_termina_cuando_empieza_otra_se_acepta`, `test_una_cita_que_empieza_cuando_termina_otra_se_acepta` | ✅ | |
| CA6 | `ReglaAgendaTest::test_las_citas_canceladas_o_no_asistio_no_bloquean_el_hueco`, `test_una_cita_confirmada_bloquea_el_hueco` | ✅ | |
| CA7 | `ReglaAgendaTest::test_la_cita_de_otro_dentista_no_bloquea_el_hueco`, `ReservaConcurrenteTest::test_la_reserva_de_otro_dentista_no_espera_a_las_citas_bloqueadas_de_otro` | ✅ | También bajo concurrencia (R15). |
| CA8 | `ReservaConcurrenteTest` (bloqueo por flujo, visibilidad de la cita confirmada, orden, espera, interbloqueo, error ajeno, logs) | ✅ | |
| CA9 | `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500`, `test_una_fecha_mal_formada_se_rechaza_sin_error_500`, `test_los_identificadores_enviados_como_array_se_rechazan_sin_error_500`, `test_el_personal_puede_agendar_sin_enviar_servicio` | ✅ | |
| CA10 | `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza` | ✅ | |

## Seguimiento de la ronda anterior
| ID | Estado | Evidencia |
|---|---|---|
| R15 | corregido | `ReservaCitas::comprobarSolape` usa `->forceIndex('idx_citas_dentista')` (`app/Agenda/ReservaCitas.php:157`); `EXPLAIN` → `type: ref`, `key: idx_citas_dentista`. `ReservaConcurrenteTest::test_la_reserva_de_otro_dentista_no_espera_a_las_citas_bloqueadas_de_otro` fallaba antes con el "no disponible" falso y vuelve a fallar sin `forceIndex`. |
| R8, R9, R11–R14, R16 | aceptados (sin cambio) | El código nuevo no los empeora. |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; `scope.md` no muestra archivos sin tarea (T025 y T026).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | T025 y T026 se añadieron por `/review` antes del código. |
| P2 | ✅ / ❌ aceptado | T025 fallaba antes (8c3ee06); excepción de caracterización sin cambios. |
| P3 | ❌ aceptado | Sin cambios de autorización. |
| P4 | ✅ | `forceIndex` recibe un nombre fijo, no entrada del usuario; Query Builder con bindings. |
| P5 | ✅ | gitleaks sin fugas. |
| P6 | ✅ | Sin dependencias nuevas. |
| P7 | ❌ aceptado | Sin cambios. |
| P8 | ❌ aceptado | Sin logs nuevos. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos (gitleaks, `f8c9aca..HEAD`) | ✅ 0 hallazgos |
| SAST (semgrep `p/php`, baseline f8c9aca) | ✅ 0 hallazgos nuevos |
| SCA (`composer audit --locked …` y `npm audit --audit-level=high`) | ✅ código 0 |
| Contenedores / FS | ➖ Sin cambios de dependencias. |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A06:2025 Concurrencia | ✅ | La reserva serializa por dentista y ya no bloquea citas de otros (R15). |
| A05:2025 Validación e inyección | ✅ | Sin cambios en esta ronda. |

## Observabilidad
- Eventos de auditoría: no aplica (excepciones P7 sin cambios).
- Datos sensibles en logs: ninguno nuevo.
- `request_id`: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R17 | menor | `app/Agenda/ReservaCitas.php:157` | `forceIndex('idx_citas_dentista')` depende del nombre del índice, que hoy solo crea `database/dentissa.sql:490`. Si las migraciones de la spec 013 lo crean con otro nombre, la reserva falla con un error SQL. | Aceptado sin tarea |

## Preparación para release
- Rollback factible: sí. Basta revertir el código; no hay migraciones.
- Migraciones: no hay.
- Feature flags: no aplica (D8).
- Docs: `architecture.md` (servicio, flujo con bloqueo) y `security.md` (RS12.a "en curso";
  RS12.c "pendiente" con nota) al día; `deployment.md` sin cambios necesarios.
- Vulnerabilidades críticas o altas sin excepción: ninguna.

## Tareas añadidas
Ninguna en esta ronda. Las de rondas anteriores están hechas:
- T015 ← R1 · T016 ← R2, R3 · T017 ← R4 · T018 ← R6, R7 · T019 ← R5
- T022 ← R1, R2, R4 · T023 ← R2, R3
- T024 ← R10 (añadida durante `/implement`)
- T025, T026 ← R15

## Aceptados sin tarea
- **R8** → roadmap: `SET SESSION innodb_lock_wait_timeout` en `ReservaCitas` deja 5 s fijados en la conexión para el resto de la petición; restaurar el valor o usar `SET STATEMENT … FOR` si aparecen procesos persistentes.
- **R9** → roadmap: `ReservaCitas::reservar()` convierte `DeadlockException` en no disponible; documentar o impedir que se llame dentro de otra transacción.
- **R11** → roadmap: extraer los helpers de fixtures de agenda de los 5 tests a un trait común.
- **R12** → roadmap: la búsqueda de solapes empieza en el inicio del día; revisar si la spec 027 permite franjas que crucen la medianoche.
- **R13** → roadmap: el test de la espera de 5 s depende del texto del SQL; comprobar el efecto (`@@SESSION.innodb_lock_wait_timeout`) si se pasa a binding.
- **R14** → roadmap: el test de orden debería exigir que el bloqueo sea la primera lectura de la transacción.
- **R16** → roadmap: actualizar en el plan de la 025 (TM2 y Trazabilidad) el nombre del test unido en T024.
- **R17** → roadmap: las migraciones de la spec 013 deben crear `idx_citas_dentista` con ese nombre, porque `ReservaCitas` lo fuerza; lo cubre `ReservaConcurrenteTest::test_la_reserva_de_otro_dentista_no_espera_a_las_citas_bloqueadas_de_otro`.
