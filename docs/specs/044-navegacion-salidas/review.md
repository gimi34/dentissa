---
spec: 044-navegacion-salidas
verdict: approved
round: 2
date: 2026-11-10
base: d321a10
head: 5199f57
human_signoff: gimi34 2026-11-10
---

# Review · 044 Salidas de las pantallas

## Resumen
`approved`. La ronda 2 (`--rerun`) comprueba las correcciones de la ronda 1 (T037–T045): el bloqueante R1 y
los importantes R2–R5 están resueltos, igual que los menores que el usuario decidió corregir, y el diff no
trae hallazgos nuevos de código. Quedan dos observaciones fuera del código de la 044, aceptadas por el
usuario: un test intermitente de la spec 043 (R23, al roadmap) y la prueba con NVDA del aviso (R24, a la
verificación en producción, T095). Diff de 29,5 KB revisado sin subagentes, releyendo los archivos desde cero.
Conteo (ronda 2): bloqueante 0 · importante 0 · menor 2

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 785 en verde (12 813 aserciones). En una pasada anterior falló una vez `ContrasenaDistintaTest::test_tras_el_rechazo_el_enlace_sigue_sirviendo` (spec 043): pasa 3/3 solo, 4/4 con su clase y en la suite siguiente (R23) |
| `node --test tests/js/publico.test.mjs` | ✅ 17/17 |
| `vendor/bin/pint --test` (PHP tocados en la ronda) | ✅ |
| `aidd.py validate` | ✅ 0 errores |
| Medición dirigida del menú en el navegador (ronda 1) | ✅ cuatro roles a 320, 390 y 1440; la ronda 2 no toca `layouts/app.blade.php` |
| Foco del logo-enlace en el navegador (T038) | ✅ `rgb(255, 255, 255)`, 2 px, en el inicio de sesión y en el registro |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `NavegacionSalidasTest::test_las_pantallas_de_acceso_empiezan_por_su_salida` | ✅ | |
| CA2 | `NavegacionSalidasTest::test_el_logo_del_acceso_lleva_al_inicio_con_nombre_accesible`, `NavegacionSalidasCssTest::test_el_foco_del_logo_enlace_es_el_inverso_sobre_el_panel_oscuro` | ✅ | R1 resuelto |
| CA3 | `NavegacionSalidasTest::test_contrasena_nueva_ofrece_volver_a_iniciar_sesion`, `::test_salir_de_contrasena_nueva_no_gasta_el_enlace` | ✅ | |
| CA4 | `NavegacionSalidasTest::test_salir_del_registro_con_google_no_crea_cuenta`, `::test_volver_a_entrar_con_google_reemplaza_los_datos_anteriores` | ✅ | |
| CA5 | `NavegacionSalidasTest` (panel, inicio con sesión, registro), `MovilPacienteTest` | ✅ | |
| CA6 | `NavegacionSalidasTest::test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez`, `tests/js/publico.test.mjs` | ✅ | R2: el texto se reescribe en la región de estado tras la carga; NVDA pendiente (R24, T095) |
| CA7 | `PaginasErrorTest::test_una_direccion_inexistente_*` | ✅ | R4: con sesión real |
| CA8 | `PaginasErrorTest::test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje`, `::test_el_acceso_denegado_sigue_en_la_auditoria` | ✅ | R5: solo explicaciones de permisos |
| CA9 | `PaginasErrorTest::test_un_formulario_caducado_ofrece_volver_a_iniciar_sesion` | ✅ | |
| CA10 | `PaginasErrorTest::test_demasiados_intentos_*`, `::test_los_limites_actuales_*`, `::test_la_500_*` | ✅ | |
| CA11 | `PaginasErrorTest::test_cualquier_verbo_*`, `::test_la_reserva_de_los_otros_verbos_no_lleva_sesion_ni_csrf`, `::test_los_enlaces_de_vuelta_*`, `::test_la_404_*` | ✅ | R3 resuelto |
| CA12 | `PaginasErrorTest::test_la_comprobacion_de_salud_no_se_indexa` | ✅ | |

## Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | bloqueante | resuelto | T037 y T038: `.logo-enlace:focus-visible` con `--focus-ring-inverso` en `acceso.css` y `auth.css`; test por hoja; medido en el navegador |
| R2 | importante | resuelto | T039 y T041: `<span role="status">` que `publico.js` vacía y reescribe a los 250 ms; test de JS. La prueba con NVDA queda en R24 |
| R3 | importante | resuelto | T042: los siete verbos y `test_la_reserva_de_los_otros_verbos_no_lleva_sesion_ni_csrf`; comprobado quitando `withoutMiddleware('web')` |
| R4 | importante | resuelto | T042: inicio de sesión real y guard olvidado; comprobado sacando la reserva GET de `web` |
| R5 | importante | resuelto | T042 y T043: la 403 toma la explicación de la `AuthorizationException` previa; `abort(403, DETALLE)` no se muestra |
| R6, R7, R8, R10, R14, R15, R16, R18, R19, R22 | menor | resueltos | T037, T039, T041, T042 y T044 |
| R9, R11, R12, R13, R17, R20, R21 | menor | aceptados en la ronda 1 | al roadmap en `/release` (`history/review.r1.md`) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea, 0 tareas sin diff).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | tareas de `/review` con su nota; `/analyze` r6 vigente |
| P2 | ✅ | los tests nuevos de la ronda fallaron antes o se comprobaron rompiendo (notas de T037, T039 y T042) |
| P3–P6, P8, P10 | ✅ | sin cambios respecto de la ronda 1; escáneres de la ronda en verde |
| P7 | ✅ (con excepción aceptada) | sin cambios |
| P9 | ✅ | R1 resuelto con un test que lo vigila (`NavegacionSalidasCssTest`) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`d321a10..HEAD`) | ✅ sin secretos |
| semgrep 1.99.0 `p/php` (PHP tocados en la ronda) | ✅ 0 hallazgos |
| composer audit / npm audit / trivy | sin cambios de dependencias en la ronda; ronda 1: solo EX4 (vigente hasta el 2026-11-11) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A10:2025 Manejo de errores | ✅ | R5: la 403 ya no puede mostrar el texto de otra excepción 403 |
| A08:2025 CSRF | ✅ | R3: un test fija que la reserva de los otros verbos no pasa por la comprobación CSRF |
| Resto | ✅ | sin cambios respecto de la ronda 1 |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí, sin cambios (403 auditado; logout en la excepción P7).
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2, 3 y 4 | ✅ | la ronda 2 solo cambia el aviso (dentro de `<main>`, con sesión cerrada) y la 403; las rutas indexables no cambian (ronda 1) |
- Sección "Descubribilidad" del plan aplicada: sí (ronda 1).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R23 | menor | `tests/Feature/ContrasenaDistintaTest.php:156` | Test de la spec 043 intermitente: falló una vez en la suite completa y pasó en todas las repeticiones. Fuera del código de la 044 | Ficha `/fix` aparte que busque la causa (límite de intentos por minuto, caducidad del token o tiempo de bcrypt) |
| R24 | menor | `resources/views/layouts/publico.blade.php`, `public/js/publico.js` | No se comprobó con un lector de pantalla que «Has cerrado sesión» se anuncia al llegar a la página | Comprobarlo con NVDA en la verificación en producción (T095) |

## Preparación para release
- Rollback factible: sí. Sin migraciones ni interruptor; `deploy/volver.sh <tag anterior>` sin copia.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas (architecture, deployment): sí (T090, T091); `design.md` y `system.md` al día con R1 y R2 (T038, T045).
- SCA: EX4 vence el 2026-11-11; el `/chore` de Symfony 7.4.23 va antes del `/release` (plan, Rollout).

## Tareas añadidas
- Ninguna en esta ronda. R24 va como subpunto de T095.

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-10:
- **R23** → roadmap: `ContrasenaDistintaTest::test_tras_el_rechazo_el_enlace_sigue_sirviendo` (spec 043) es intermitente en la suite completa; ficha `/fix` aparte
