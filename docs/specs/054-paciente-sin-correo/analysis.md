---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-21
spec_sha: 2dce5a681b49
plan_sha: c1c716a06e5f
tasks_sha: 57970372fe08
---

# Análisis · 054 Alta de pacientes sin correo

## Resumen
**pass.** Los 18 hallazgos de la ronda 1 ([r1](history/analysis.r1.md)) están resueltos; cuatro de
ellos con un efecto nuevo menor, que aparece abajo. No queda nada crítico ni alto. De los siete
hallazgos nuevos, A24 y A25 se corrigieron en el acto; A19–A23 (un medio y cuatro bajos, sin cambio
de diseño) se aceptan como notas de las tareas que los resuelven.

La revisión delta la hizo un subagente independiente (perfil `standard`), con el diff de
`aidd.py changes` (unos 72 KB) y los hallazgos abiertos, y la contrastó con el código. A19 se
comprobó contra `GoogleAuthController.php`.

Conteo (abiertos y nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 1 (aceptada) · BAJA 6 (2 corregidas, 4 aceptadas)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 10 / 10 (TM1 con control en T028; TM10 nueva) |
| Principios de la constitución evaluados | 10 / 10 (P3 y P7 con excepción aceptada) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A19, A21) | Guarda en el callback de Google antes de guardar `google_registro`, así que `contactoStore` nunca recibe un correo nulo: plan D14, TM1, Rollout; T015, T028, T029 |
| A2 | ALTA | resuelto | `withInput($request->except('password'))` (el `withInput()` manual no pasa por `dontFlash`); TM10, D6; T012, T021 |
| A3 | ALTA | resuelto con efecto nuevo (→ A25) | T027 adapta las aserciones de `aria-describedby` de `MovilPacientesExpedientesTest` |
| A4 | ALTA | resuelto | Fila en «Decisiones» de la spec (usuario); P3 ampliada a `create`; D7, D11, D12 |
| A5 | MEDIA | resuelto | `PosiblesDuplicados` sin filtro de rol; T010, T020 |
| A6 | MEDIA | resuelto | `exclude_without:email` es una regla válida y excluye `password` con correo nulo, vacío o solo con espacios; T011, T021 |
| A7 | MEDIA | resuelto | T025 rompe el `abort(403)` de la edición |
| A8 | MEDIA | resuelto | Nota en la spec 052 (decisión del usuario); T003 comprueba si la 052 ya está en `main` |
| A9 | MEDIA | resuelto | `.marca-sin-correo` en el bloque 054, con `--text-small` y `--text-muted`; T017, T022, T023 |
| A10 | MEDIA | resuelto con efecto nuevo (→ A23) | D13; T012 (el primer envío no lleva `confirmar_duplicado`); verificación manual en T022 |
| A11 | MEDIA | resuelto | Altas como asistente con dentista asignado; D11 encarga a la 014 el paso del 403 a 404 |
| A12 | BAJA | resuelto | Redacción de «Rendimiento» corregida |
| A13 | BAJA | resuelto | 405 en el plan y en T015 |
| A14 | BAJA | resuelto con efecto nuevo (→ A22) | `h2` con `tabindex="-1"` y `autofocus`; T017, T022 |
| A15 | BAJA | resuelto | Dos filas de «Decisiones» con fuente `usuario` |
| A16 | BAJA | resuelto | La fila de CA10 en la tabla de cobertura cita T015 y T016 |
| A17 | BAJA | resuelto | Icono SVG con su tamaño en atributos; P9, T022 |
| A18 | BAJA | resuelto | T091 explicita la correspondencia con OB1.f |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A19 | Seguridad | MEDIA | plan «Rollout» → Rollback; `GoogleAuthController.php:45-49` y `:69` | En la versión anterior, la vinculación por correo guarda `google_id` antes de comprobar `activo`. Durante un rollback, un inicio con Google sin correo dejaría el `google_id` del atacante en una cuenta sin correo desactivada. Al reactivarla con la 054, la búsqueda por `google_id` la encontraría | Aceptado → notas de T097 y T091: tras volver a la 054, ninguna cuenta reactivada sin correo conserva un `google_id` (comprobación: `SELECT` de las cuentas sin correo con `google_id` no nulo devuelve 0) |
| A20 | Ambigüedad | BAJA | plan «Rollout» → Rollback; T097 | Desactivar con `WHERE email IS NULL` y reactivar todas reactivaría también las que ya estaban desactivadas a propósito | Aceptado → nota de T097: solo se desactivan y reactivan las cuentas sin correo que estaban activas |
| A21 | Ambigüedad | BAJA | plan D14; T028 | No se fija si la guarda va antes de la búsqueda por `google_id`, y un correo solo con espacios pasaría una guarda que solo mire nulo o vacío | Aceptado → nota de T028: un correo de Google nulo, vacío o solo con espacios nunca llega a ninguna búsqueda ni a `google_registro` (test en T015 con los tres casos) |
| A22 | Ambigüedad | BAJA | plan `pacientes/create`; T022 | La verificación manual no pide comprobar que el foco cae en el título del aviso ni que el lector no lo lea dos veces (`role="alert"` más el foco) | Aceptado → nota de T022: el foco inicial cae en el título en Chrome y Firefox y el lector de pantalla lee el aviso una sola vez; si lo lee dos veces, se quita `role="alert"` y se deja el foco |
| A23 | Ambigüedad | BAJA | plan D13; T022 | D13 deja dos mecanismos sin elegir, y Safari puede ignorar como botón por defecto un botón con `display:none` | Aceptado → nota de T022: se usa `.envio-por-defecto` desde el principio; la comprobación manual es en Chrome y Firefox, y Safari se declara no cubierto si no hay equipo para probarlo |
| A24 | Inconsistencia | BAJA | plan P1; spec CA3 | P1 decía que la spec estaba aprobada mientras estaba en `draft`; CA3 no mencionaba la excepción de la contraseña | **Corregido** el 2026-11-21: CA3 aclara «salvo la contraseña temporal», con su fila en el Historial, y P1 recoge la nueva aprobación |
| A25 | Inconsistencia | BAJA | tasks T027 | T027 dependía de T022, así que la suite quedaba en rojo entre las dos (P2) | **Corregido** el 2026-11-21: T027 pasa a la fase de tests, depende de T017 y T022 depende de ella |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-11-21:
- **A19** → nota de T097, T091: tras volver a la 054, ninguna cuenta sin correo reactivada conserva `google_id` (comprobado con un `SELECT` que devuelve 0); en `deployment.md`, el paso del rollback.
- **A20** → nota de T097: desactivar y reactivar solo las cuentas sin correo que estaban activas.
- **A21** → nota de T028: la guarda va justo tras obtener el usuario de Google, antes de cualquier búsqueda, y rechaza correos nulos, vacíos o solo con espacios (test en T015 con los tres casos).
- **A22** → nota de T022: foco inicial en el título del aviso y una sola lectura del lector de pantalla; si lo lee dos veces, quitar `role="alert"`.
- **A23** → nota de T022: `.envio-por-defecto` desde el principio; Chrome y Firefox comprobados, Safari declarado si no se prueba.
