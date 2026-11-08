---
result: fail
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-08
spec_sha: 29289b146172
plan_sha: f718c850ad58
tasks_sha: 8ce460087bc8
---

# Análisis · 044 Salidas de las pantallas

## Resumen
`fail`. De los quince hallazgos de la ronda 1 quedan resueltos trece; A9 sigue abierto, rebajado a BAJA,
y A1 se resolvió pero con efectos nuevos. El efecto alto es A16: la ruta de reserva GET rompe un test de
rutas de la spec 010 que ninguna tarea cambia. Las demás correcciones abren ajustes medios y bajos
(A17–A22). Análisis delta hecho por un subagente independiente; A16 y A21 se comprobaron en los archivos.
Conteo (nuevos): CRÍTICA 0 · ALTA 1 · MEDIA 3 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 7 / 7 |
| Principios de la constitución evaluados | 10 / 10 (P2 con A19 y A22; P7 y P9 desalineados en `tasks.md`, A20) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A16, A17, A18) | plan D3, Contratos y T029 con dos rutas de reserva y `test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404`; las dos rutas conviven por verbo y `withoutMiddleware('web')` ya se usa en `routes/web.php` |
| A2 | ALTA | resuelto | CA10 preventiva, supuesto y Decisiones; T013 con ruta de prueba y `test_los_limites_actuales_siguen_con_su_propio_aviso` |
| A3 | ALTA | resuelto | T020 cambia `RecuperarContrasenaVistasTest`; plan → Cambios por módulo y Descubribilidad; T098 |
| A4 | ALTA | resuelto | CA5 «reemplaza 038 CA2 y 001 CA5»; Decisiones; T098 |
| A5 | ALTA | resuelto con efecto nuevo (→ A20) | plan P7 «❌ aceptado … — aprobado por el usuario el 2026-11-08»; spec → Auditoría y Decisiones; T091 al roadmap |
| A6 | ALTA | resuelto con efecto nuevo (→ A19, A20) | plan P9 con `NavegacionSalidasCssTest`; T014 |
| A7 | MEDIA | resuelto | plan P8, TM3, T013 y T033 coinciden |
| A8 | MEDIA | resuelto | RNF acotado; plan → Riesgos; T091 |
| A9 | MEDIA | abierto (rebajado a BAJA) | CA6 ya dice lo que se marca, pero T027 no tiene la verificación con un lector de pantalla |
| A10 | BAJA | resuelto | Contratos («control nuevo») y T011 |
| A11 | BAJA | resuelto | CA9, `design.md` y T031 dicen «Ir a la página de inicio» |
| A12 | BAJA | resuelto | T091 |
| A13 | BAJA | resuelto | plan → Riesgos, aceptado |
| A14 | BAJA | resuelto | T010 y T021 |
| A15 | BAJA | resuelto | plan P1 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A16 | Inconsistencia (contrato de otra spec) | ALTA | plan D3; T029 | Con la ruta de reserva GET, `AdminUsuariosRutasTest::test_un_identificador_sin_forma_de_uuid_no_resuelve_a_show` (spec 010, líneas 79-85) deja de recibir `NotFoundHttpException` de `Route::getRoutes()->match()`, porque responde la ruta de reserva; por HTTP sigue siendo 404, pero el test cae y ninguna tarea lo cambia | `/plan 044 --fix A16` y `/tasks 044 --fix A16`: un identificador sin forma de UUID no llega a `@show` y responde 404 (el test de la 010 lo comprueba así, en la tarea de la ruta de reserva); cambio del test declarado y anotado en el Historial de la 010 (T098) |
| A17 | Inconsistencia (contrato no declarado) | MEDIA | plan → Contratos; D3; CA11 | Con una reserva para cada verbo, un verbo equivocado sobre una ruta que existe pasa de 405 a 404 (p. ej. `GET /logout`), y `OPTIONS` sobre una ruta existente pasa de 200 a 404. Los tests actuales aceptan 404 o 405 | `/plan 044 --fix A17`: declarar el cambio con su decisión; caso de `PaginasErrorTest` con `GET /logout` que fija el código |
| A18 | Orden de tareas / Cobertura | MEDIA | T040; D3; T095, T097 | T040 se cierra antes del despliegue y exige un `curl` posterior a él; la única comprobación de que un POST sin token a una dirección inexistente da 404 (y no 419) no está en T095/T097 | `/tasks 044 --fix A18`: el `curl -X POST` va a T095/T097; T040 sin ese requisito |
| A19 | Constitución P2 | MEDIA | T014; T020, T021, T023, T027, T028; tasks → Constitution Check P2 | T014 va después de las tareas que implementan sus reglas y ninguna depende de ella, así que no puede fallar antes; `auth.css` no define `--touch-min` y T021 no dice qué tokens copia | `/tasks 044 --fix A19`: T014 antes de las tareas de interfaz, que dependen de ella y la incluyen en su «hecho cuando»; T021 nombra los tokens que copia, con `--touch-min` |
| A20 | Inconsistencia (tareas vs plan) | BAJA | tasks → Constitution Check P7, P9 | No reflejan la excepción P7 ni el test de P9 del plan | `/tasks 044 --fix A20` |
| A21 | Formato | BAJA | plan → Contratos | Las filas nuevas de la tabla no llevan la sangría de la viñeta y cortan la tabla | `/plan 044 --fix A21` |
| A22 | Constitución P2 (caracterización) | BAJA | T013; plan P2 | `test_los_limites_actuales_siguen_con_su_propio_aviso` es de caracterización sin control que se rompa anotado ni entrada en la lista de P2 | `/plan 044 --fix A22` y `/tasks 044 --fix A22`: romper el `->response()` de `registro` y anotarlo |

## Decisiones pendientes del usuario
Ninguna. A17 se resuelve en el plan como decisión técnica: un verbo equivocado responde 404, que no revela qué verbos acepta una dirección.

## Aceptados
Ninguno.
