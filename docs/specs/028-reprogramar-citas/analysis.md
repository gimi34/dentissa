---
result: pass
round: 4
mode: delta
constitution_version: 1.2.1
date: 2026-10-08
spec_sha: 7b68114c7ac2
plan_sha: 78b55496ec1a
tasks_sha: 9477bef16e59
---

# Análisis · 028 Reprogramar citas

## Resumen
Pasa. Los cinco hallazgos que la ronda 3 dejó para corregir (A48, A49, A50, A51 y A55) quedan
resueltos en el plan (commit `0c24ec5`, aprobado en `7abdd98`) y en las tareas (`ec24075`), sin
efectos nuevos. Siguen vigentes, como notas obligatorias de sus tareas, los hallazgos aceptados en
las rondas 2 y 3.

Ronda delta sin subagente. El diff ocupa unos 39 KB por las líneas largas de las tablas, pero cambia
poco y nada estructural: lo revisó el agente principal releyendo desde cero las secciones afectadas
del plan (fila de `app.css`, «Maquetación de los bloques tocados», Estrategia, Trazabilidad, D17) y
las tareas T001, T013, T015, T024, T026, T028, T029 y T031, con el validador en 0 errores.

Conteo de nuevos: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 11 / 11 |
| Principios de la constitución evaluados | 9 / 9 (delta: P2 y P9 sobre lo cambiado) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A48 | ALTA | resuelto | Plan: tabla «Maquetación de los bloques tocados» con 6 clases acotadas a `.formulario-accesible` y solo tokens (el vacío del historial con `--space-7`), y la fila de `app.css`. Tareas: las crea T024 (3 archivos); T026 (`.acciones-columna`, `.historial-lista`, `.historial-vacio`), T028 (`.form-en-linea`, `.aviso-plazo`) y T029 (`.acciones-fila`, `.form-en-linea`, `.aviso-plazo`) solo las usan y no tocan `app.css`. T028 sigue en 3 archivos. Los tests de «solo usa el sistema» siguen exigiendo cero `style=`. |
| A49 | MEDIA | resuelto | Plan: las reglas del historial pasan a reglas acotadas a `.formulario-accesible`, sin tocar las globales de `dashboard/dentista`. T024: el «hecho cuando» exige que las reglas globales `.timeline-*` no cambien. T015: el test de tokens filtra por los selectores que empiezan por `.formulario-accesible` o `.btn-danger`. |
| A50 | BAJA | resuelto | Trazabilidad CA5 (`test_el_paciente_ve_el_motivo_si_su_cita_no_se_puede_mover`), TM1 (`test_el_personal_no_puede_usar_la_ruta_del_paciente`), RNF WCAG (`test_el_estado_completada_se_ve_con_contraste_aa`, personal) y Estrategia (el GET del paciente, en `ReprogramarCitaPacienteTest`) coinciden con T012, T013 y T015. |
| A51 | BAJA | resuelto | Plan: 18.50:1; la fila de `tests/Feature/` dice que cambia un test de `EstadosCitaVistasTest`; la viñeta de 1.3.0 está en «Cambios a incorporar»; las capturas van en su tarea propia. Tareas: el P2 cita los dos tests de caracterización y T001 remite a T032. |
| A55 | BAJA | resuelto | Plan D17: el «faltan menos» de CA3 se aplica a citas que aún no empiezan, y las ya empezadas conservan el mensaje de 026 CA7 (decisión del usuario, 2026-10-08), con el test existente sin cambios. |

## Hallazgos nuevos
Ninguno.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Siguen vigentes los aceptados por el usuario el 2026-10-08. El detalle está en
`history/analysis.r2.md` y `history/analysis.r3.md`, y cada uno ya está como nota bajo su tarea:
- **A33** → nota de T015, T025: el error de una cancelación rechazada se ve en `#no-reagendar` con la cita abierta; con la cita cerrada, la pantalla muestra el aviso.
- **A34** → nota de T011, T013, T024: con listas en `old()`, el GET del formulario responde 200 con el error, nunca 500.
- **A41** → nota de T013, T021, T027: texto genérico del paciente para una cita agendada ya empezada o en curso.
- **A42** → nota de T022: `comprobarHueco` recibe la hora local sin zona.
- **A43** → nota de T015, T024: el mensaje `error` se ve una sola vez; con `formulario` ausente, los errores van a la sección de reprogramar.
- **A45** → nota de T040: `trivy fs` se compara con la línea base; EX2 se revisa en `/release`.
- **A46** → nota de T013: razón esperada del fallo del test de validación de la cancelación.
- **A47** → nota de T015: los errores se anuncian con `role="alert"`.
- **A52** → nota de T013, T015: el test del badge solo usa al personal; «Ver» sigue en la lista; qué se comprueba de `#estado`.
- **A53** → nota de T015, T024: `font-family: inherit` en los campos.
- **A54** → nota de T031: la selección de vistas no recorre `COMPONENTES`; nunca se escribe el valor de `CLAVE`.

## Siguiente paso
Aprobar las tareas y ejecutar `/implement 028`.
