---
result: pass
round: 2
mode: delta
constitution_version: 1.0.0
date: 2026-10-02
spec_sha: 5e0d7f6bd0a9
plan_sha: 183e39de4bb0
tasks_sha: 11a941767860
---

# Análisis · 026 Estados de la cita y quién puede cambiarlos

## Resumen
Resultado: **pass**. Delta sobre la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)), con los cambios de
`/specify --edit` (f63be31), `/plan --fix` (e90ab95) y `/tasks --fix` (ee660ea). Los 12 hallazgos abiertos quedan
resueltos: nueve sin más, y tres con un efecto nuevo menor (A21, A22, A23).

Hallazgos nuevos: 0 CRÍTICAS, 0 ALTAS, 4 MEDIAS y 3 BAJAS. Ninguno cambia el diseño, y los siete se aceptan como notas
de tarea.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 (la frase del paciente en CA2 recibe su test por A21) |
| Amenazas con control y test | 11 / 11 |
| Principios de la constitución evaluados | 8 / 8 (P3 revisado en el delta: excepción con formato válido) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A23) | P3 `❌ aceptado … — aprobado por el usuario el 2026-10-02` en plan y tareas; D11; viñeta en T041 |
| A2 | ALTA | resuelto | Supuesto "rol primero" en la spec; plan `CambiarEstadoCita`, `EstadosCita::cambiar` (cerrada → vuelta → transicion), mensaje `vuelta`, D6; T011, T012, T022, T023 |
| A3 | ALTA | resuelto | Test de zona solo del personal; caso del paciente en `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` (T012, cierra en T026) |
| A4 | MEDIA | resuelto | T024 no toca `show`; T025 cambia `show` y la vista juntos |
| A5 | MEDIA | resuelto | `gestionar-cita` antes de validar (plan, D6, TM2, T022, T024); `test_fuera_de_alcance_con_estado_invalido_responde_403` en T012 |
| A10 | MEDIA | resuelto | `test_solo_el_admin_puede_eliminar_una_cita` (plan, T012, TM10, CA6) |
| A11 | MEDIA | resuelto con efecto nuevo (→ A22) | Los helpers de `CitasController` delegan en `AlcanceClinico` (plan, T024); test de alcance del listado y del detalle y `AltaCitasRolesTest` en T024 |
| A12 | MEDIA | resuelto | Spec cita RS2 (RS2.a parcial, RS2.b → 015) y 004 CA7; filas RS2 del plan; nota en T091; nada se declara mitigado |
| A13 | BAJA | resuelto | "se mide en T040"; D3 y D10 → T041 |
| A14 | BAJA | resuelto con efecto nuevo (→ A21) | CA2 incluye el mensaje del paciente |
| A17 | BAJA | resuelto | `EstadosCita::__construct(private ConnectionInterface $db)` en plan y T023 |
| A19 | BAJA | resuelto | CA6 dice "del detalle de la cita" |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A21 | Cobertura | MEDIA | spec CA2 (frase del paciente); plan Trazabilidad CA2, D7; tasks matriz CA2 | Ningún test comprueba el mensaje del paciente con una cita cerrada o en curso. Hoy el texto es "Esta cita ya no se puede cancelar." y D7 lo amplía. | Aceptado → nota de T012, T026 |
| A22 | Cobertura / constitución | MEDIA | plan `CitasController`, P3; T024 | Con la delegación de A11 cambia el código de alcance de los desplegables de dentistas de `GET /{rol}/citas` y `GET /{rol}/citas/crear` (CitasController.php:152-154, 178-183), y ningún test los cubre. | Aceptado → nota de T012, T024 |
| A23 | Conflicto con 014 | MEDIA | plan D11, P3; T041 | La spec 014 no trata el detalle de cita (su código afectado es `CitasController::create/store`), así que el destino de la excepción P3 del detalle no está recogido en esa spec. | Aceptado → nota de T041 |
| A24 | Inconsistencia | MEDIA | tasks T012 "hecho cuando"; P2 de las tareas | Tres de los 18 tests de T012 (los casos permitidos de dentista, asistente y paciente) pasan hoy y no están clasificados. | Aceptado → nota de T012 |
| A25 | Inconsistencia | BAJA | plan Estrategia (`test_el_alcance_del_listado_y_del_detalle_no_cambia`) frente a T012 | El plan propone romper `dentistasDelAlcance`, que aún no existe cuando se escribe el test. | Aceptado → nota de T012 |
| A26 | Inconsistencia | BAJA | plan Enfoque, `ClienteCitasController`, D7; tasks P3, T026 | La cancelación del paciente solo usa `cambiar-estado-cita`; T026 traduce también `vuelta`, que el paciente no puede provocar. | Aceptado → nota de T026 |
| A27 | Inconsistencia | BAJA | tasks Cobertura (CA2, CA3, CA11) frente al `cubre` de T012, T022 y T023 | La matriz atribuye CA2 a T012 y T022, CA3 a T022 y CA11 a T023, pero su `cubre` no los incluye. | Aceptado → nota de T012, T022, T023 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-02 (ronda 1, se mantienen):
- **A6** → nota de T013: comprobar `test_el_motivo_se_muestra_escapado` rompiendo un momento la salida con `{!! !!}` y restaurar.
- **A7** → nota de T015: los botones "Cancelar cita" (admin, barra superior) y "Cancelar" (paciente) se comprueban por el formulario cuyo `action` es la ruta de destroy o de cancelar, no por sección.
- **A8** → nota de T015: la aserción del listado del paciente usa `?filtro=pasadas` (además del detalle); una cita listada como pasada que aún no empieza en la hora local sigue mostrando "Cancelar" (deuda D4).
- **A9** → nota de T014: la cita, su paciente, su dentista y sus usuarios se crean y confirman con `segunda_conexion` antes de cualquier lectura de la app, se anotan y se borran en `tearDown`; esos tests no usan `cita()` de la fixture.
- **A15** → nota de T025: el aviso de cita cerrada reutiliza el mensaje `cerrada`, y el autor nulo se muestra como "Sistema".
- **A16** → nota de T013, T025: la fecha del historial se muestra como hoy (UTC, sin convertir; deuda D4) y el test la comprueba en ese formato.
- **A18** → nota de T091: documentar en `docs/deployment.md` que, tras `bootstrap-test-db.sh`, hay que ejecutar `migrate` sobre `dentissa_test`.
- **A20** → nota de T012: `test_el_dentista_no_puede_cancelar_su_cita` y `test_el_asistente_no_puede_iniciar_ni_completar` comprueban el texto del mensaje del rol.

Aceptados por el usuario el 2026-10-02 (ronda 2):
- **A21** → nota de T012, T026: test en `EstadosCitaRolesTest` (cubre CA2 y CA7): el paciente que cancela una cita completada y otra en curso recibe exactamente "Esta cita ya no se puede cancelar. Si necesitas ayuda, llama a la clínica.", sin nombres de estados internos. Falla hoy por el texto y pasa con T026.
- **A22** → nota de T012, T024: test de caracterización en T012: dentista y asistente ven en los desplegables de dentistas de `GET /{rol}/citas` y `GET /{rol}/citas/crear` solo los suyos, como hoy. Se comprueba rompiendo un momento `personalIdPropio` o `dentistaIdsAsistente`. T024 lo exige en verde y anota que la delegación es una refactorización sin cambio de autorización en esos endpoints.
- **A23** → nota de T041: la viñeta de la 014 dice que esa spec debe incorporar con `/specify --edit 014` el Gate del detalle de cita (`GET /{rol}/citas/{id}`), además de ajustar su plan.
- **A24** → nota de T012: los tres casos permitidos (dentista, asistente y paciente) pasan hoy. Se declaran regresión dentro de CA4, CA5 y CA7, que ya tienen tests que fallan, y se comprueban rechazando un momento todo cambio en `updateEstado` o `cancelar`.
- **A25** → nota de T012: el test del alcance del listado y del detalle se comprueba rompiendo un momento `autorizadoParaCita` o `aplicarAlcanceRol`, que existen al escribirlo.
- **A26** → nota de T026: el flujo del paciente solo aplica `cambiar-estado-cita` (no tiene entrada que validar). Traduce `cerrada`, `transicion` y `vuelta` al mismo mensaje; `vuelta` no se puede producir desde ese flujo.
- **A27** → nota de T012, T022, T023: al cerrar cada tarea, la nota indica los CA que le atribuye la matriz de Cobertura (T012: CA2; T022: CA2, CA3; T023: CA11).
