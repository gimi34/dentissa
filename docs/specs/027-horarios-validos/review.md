---
spec: 027-horarios-validos
verdict: approved
round: 2
date: 2026-10-03
base: 5d17d1c
head: 92e4655
human_signoff: no requerido
---

# Review · 027 Horario válido del dentista y aviso de citas afectadas

## Resumen
**approved.** Los nueve hallazgos que se decidió corregir en la ronda 1 (R1–R7, R10, R15) quedan corregidos y
probados.
- Los tests de las partes nuevas que cambian comportamiento (borde con foco, R2; `toggleDia`, R10) fallaban antes de
  la corrección.
- Los de caracterización (R1, R3, R5, R6 y R7) se comprobaron rompiendo el código que protegen.
- Las verificaciones manuales de T024 y T025 las confirmó el usuario.

El diff de esta ronda (`5d17d1c..92e4655`, unos 12.6 KB) se revisó directamente, releyendo los archivos, como prevé
`--rerun` para un diff pequeño. No aparecen hallazgos nuevos.

Conteo de la ronda: bloqueante 0 · importante 0 · menor 0.

Ronda anterior: [history/review.r1.md](history/review.r1.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 152 passed (1207 assertions); línea base de T002: 127 |
| Tests de la 027, dos veces | ✅ 25 passed en las dos ejecuciones |
| `vendor/bin/pint --test` sobre los 3 archivos PHP de esta ronda | ✅ PASS |
| `python .ai/bin/aidd.py validate docs/specs/027-horarios-validos` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `HorarioDentistaTest::test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` | ✅ | Ahora comprueba los valores y la casilla dentro de la fila de cada día (R5) |
| CA2 | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza`, `HorarioDentistaVistasTest::test_los_errores_de_un_dia_se_asocian_a_sus_campos` | ✅ | |
| CA3 | `test_un_horario_manipulado_se_rechaza_sin_error_500` | ✅ | |
| CA4 | `test_un_dia_marcado_sin_horas_se_rechaza`, `test_los_errores_de_un_dia_se_asocian_a_sus_campos` | ✅ | Las horas salen vacías tras el rechazo (R6) |
| CA5 | `test_un_horario_sin_dias_se_guarda_con_aviso` | ✅ | |
| CA6 | `test_no_se_guarda_horario_para_quien_no_es_dentista`, `test_el_gate_definir_horario_distingue_rol_y_tipo` | ✅ | |
| CA7 | `CitasFueraDelHorarioTest::test_la_ficha_lista_las_citas_futuras_fuera_del_horario`, `test_una_cita_de_hoy_que_aun_no_empieza_en_la_clinica_cuenta_como_futura` | ✅ | Límite "futura" en la hora de la clínica (R1) |
| CA8 | `test_sin_citas_fuera_no_hay_lista`, `test_las_citas_pasadas_o_cerradas_no_aparecen` | ✅ | |
| CA9 | `test_solo_la_administracion_guarda_horarios`, `test_el_gate_definir_horario_distingue_rol_y_tipo` | ✅ | La rama de rol del Gate, probada directamente (R7) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` del rango no tiene archivos sin tarea; T043 solo toca `docs/security.md`.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1–P8 | ✅ / ➖ | Sin cambios respecto a la ronda 1. P3 gana el test directo del Gate (R7) |
| P9 | ✅ | `.horario-dia .field:focus` mantiene `--border-control` frente al `.field:focus` previo (especificidad 0,3,0 frente a 0,2,0), y el borde mide `--border-width` (R2). El test de contraste saca los colores de las propias reglas nuevas (R3) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`5d17d1c..HEAD`) | ✅ 0 hallazgos |
| semgrep | ➖ Sin cambios en `app/` en esta ronda (ronda 1: 0 hallazgos) |
| composer audit / npm audit | ➖ Sin cambios en los lockfiles desde la ronda 1. La media de phpseclib queda registrada como RS17 (R4) |
| trivy | ➖ Sin cambios; el falso positivo de `vendor/` está justificado en la ronda 1 (R20) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | Test directo del Gate: admin sobre un dentista, permitido; admin sobre un asistente, 404; otros roles, 403 |
| Resto | ✅ | Sin cambios en los controles de la ronda 1 |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (spec, Auditoría; P7 ➖).
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
Seguimiento de la ronda 1:

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | importante | corregido | T013: `test_una_cita_de_hoy_que_aun_no_empieza_en_la_clinica_cuenta_como_futura`; con `Carbon::now()` en UTC falla |
| R2 | importante | corregido | T015 (c) falló antes de T024; `app.css`: `.horario-dia .field:focus` y `border-width: var(--border-width)`; verificación manual OK |
| R3 | importante | corregido | T015 (d): pares sacados de las reglas (`color`, bordes de `.field`, `outline`), al menos 10 comprobaciones; con `--text-light` en `.horario-dia__nota` falla |
| R4 | importante | corregido | T043: RS17 en `docs/security.md:269-277`, con destino y fecha |
| R5 | menor | corregido | T014: valores y casilla por fila; sin el recorte H:i falla |
| R6 | menor | corregido | T015 (a): horas vacías tras el rechazo; con los valores por defecto falla |
| R7 | menor | corregido | T015 (b): `test_el_gate_definir_horario_distingue_rol_y_tipo`; sin la rama de rol falla |
| R10 | menor | corregido | T015 (e) falló antes de T025; `toggleDia` alterna `hidden` en los `.field-hint-error` de la fila; verificación manual OK |
| R15 | menor | corregido | T042: `system.md` con el texto de "Campo de formulario", DS1 y DS4 corregidos y las referencias de línea al día |

Hallazgos nuevos: ninguno.

## Preparación para release
- Rollback factible: sí. Basta con volver a desplegar la versión anterior: sin migraciones, y los horarios guardados siguen siendo válidos para la versión anterior.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: ninguno (D9).
- Docs actualizadas: `architecture.md` (T090), `security.md` (T091; RS17, T043), `system.md` (T001, T042); `deployment.md` sin cambios.
- SCA: 0 críticas o altas sin excepción.
- Cobertura de riesgos: RS12.b implementada y probada, todavía "en curso" hasta la release; RS12.c sigue pendiente con la nota de la 027; RS17 está pendiente, con destino.

## Tareas añadidas
(Ronda 1: T013, T014, T015, T024, T025, T042 y T043, todas hechas.)

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-03 (ronda 1):
- **R8** → roadmap: comprobar `tipo = dentista` y la existencia de la fila dentro de la transacción del horario.
- **R9** → roadmap: una fila heredada con `disponible = 0` sale marcada en la ficha y se normaliza a 1 al guardar.
- **R11** → roadmap: "Horarios de atención" como `h2`, con nombre accesible para el formulario (DS7).
- **R12** → roadmap: llevar el foco al resumen de errores del horario tras el rechazo.
- **R13** → roadmap: texto oculto en los "Ver cita" de la lista de citas fuera (2.4.9, AAA).
- **R14** → roadmap: objetivos de toque de la fila, el campo de hora y "Ver cita" (DS6).
- **R16** → roadmap: `DB::raw`/`whereRaw` previos de `PersonalController` (P4, código previo).
- **R17** → roadmap: validación del horario en un Form Request y estado de la vista en un view-model.
- **R18** → roadmap: tokens de tamaño propios para la casilla y la etiqueta del día.
- **R19** → roadmap: ayudas de test de la 027 al trait común (junto con 025/R11).
- **R20** → roadmap: excluir `vendor/` de trivy en local (falsos positivos de la documentación de mockery).
