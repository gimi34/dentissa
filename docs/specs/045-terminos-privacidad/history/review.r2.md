---
spec: 045-terminos-privacidad
verdict: approved
round: 2
date: 2026-11-13
base: cbc2224
head: 24af13e
human_signoff: gimi34 2026-11-13
---

# Review · 045 Términos de uso y aviso de privacidad

## Resumen
`approved`: las siete correcciones que decidió el usuario en la ronda 1
([history/review.r1.md](../history/review.r1.md)) están hechas y probadas (R1, R2, R3, R5, R8, R13, R14), sin
hallazgos nuevos. Ronda `--rerun` sobre `cbc2224..24af13e` (14 KB de código), revisada sin subagentes releyendo
los archivos, con la verificación automática completa. Los siete menores aceptados en la ronda 1 van al roadmap.
Conteo (abiertos): bloqueante 0 · importante 0 · menor 0 (7 menores aceptados sin tarea)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, en `dbc9775`, mismo código que `24af13e`) | ✅ 931 en verde (14 790 aserciones); línea base 914 de `main`, sin fallos previos |
| Pint sobre `PaginasPublicasController.php` y `TerminosPrivacidadTest.php` | ✅ PASS |
| `python .ai/bin/aidd.py validate docs/specs/045-terminos-privacidad` | ✅ 0 errores |
| Build | ➖ sin cambios de assets |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `test_terminos_tiene_sus_secciones_y_la_fecha`, `test_los_datos_de_la_clinica_salen_de_la_configuracion` (ahora cambia la configuración), `test_el_horario_en_texto_admite_un_tramo_sin_guion`, `test_el_texto_es_el_aprobado` | ✅ | R2, R3 |
| CA2 | `test_el_aviso_tiene_sus_secciones_y_la_fecha` («datos sensibles» en su viñeta), `test_el_texto_es_el_aprobado` | ✅ | R5 |
| CA3 | `test_el_aviso_nombra_cada_dato_del_registro` (cada dato en su viñeta) + verificación manual (T023) | ✅ | R5 |
| CA4 | `test_la_casilla_del_registro` (cada enlace abre su ventana), `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` (dos ventanas distintas; enlaces internos con `aria-haspopup`), `test_la_ventana_del_registro_solo_usa_tokens`, `test_el_script_de_las_ventanas_abre_y_cierra` + verificación manual (usuario) | ✅ | R1, R13 |
| CA5 | `test_el_registro_sin_la_casilla_se_rechaza`, `test_el_registro_con_google_sin_la_casilla_se_rechaza` | ✅ | |
| CA6 | `test_el_pie_publico_enlaza_las_dos_paginas` + K5 | ✅ | |
| CA7 | `test_con_y_sin_sesion_se_ven_igual` | ✅ | |
| CA8 | `test_el_texto_es_el_aprobado`, `PaginasPublicasTest::test_todos_los_enlaces_internos_resuelven` + `G-sin-js` | ✅ | |

## Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | importante | corregido (T034) | cada enlace de la casilla abre la ventana con su título y las dos ventanas tienen títulos distintos; comprobado intercambiando los `data-dialogo` (falla) |
| R2 | importante | corregido (T035) | el test cambia dirección, teléfono y horario y los encuentra en las dos páginas y en las dos ventanas; comprobado escribiendo la dirección a mano (falla) |
| R3 | menor | corregido (T036, T037) | un tramo sin guion largo da «los domingos cerrado»; antes `ErrorException` |
| R4 | menor | aceptado → roadmap | |
| R5 | menor | corregido (T038) | cada dato en su viñeta; comprobado quitando «teléfono» de la de la cuenta (falla) |
| R6 | menor | aceptado → roadmap | |
| R7 | menor | aceptado → roadmap | |
| R8 | menor | corregido (T041) | `docs/security.md` → RS8.d anota el script de las ventanas |
| R9 | menor | aceptado → roadmap | |
| R10 | menor | aceptado → roadmap | |
| R11 | menor | aceptado → roadmap | |
| R12 | menor | aceptado → roadmap | |
| R13 | menor | corregido (T039, T040) | `aria-haspopup="dialog"` en el enlace al aviso dentro de la ventana de términos |
| R14 | menor | corregido (T041) | TM2 en «A05:2025 Injection» |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md` de `cbc2224..HEAD`: 3 archivos de código, todos de tareas de la review).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1 salvo P2, reforzado: R1, R2 y R5 eran tests que pasarían con lo protegido roto;
ahora fallan si se rompe (comprobado en cada tarea).

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (archivos cambiados en `cbc2224..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (los 3 archivos de código) | ✅ 0 hallazgos |
| SCA | ➖ sin cambios en lockfiles desde la ronda 1 (allí: 0 críticas, 0 altas, 0 medias) |
| trivy | ➖ sin cambios desde la ronda 1 (allí: 0 HIGH/CRITICAL) |

Temas OWASP: sin cambios respecto a la ronda 1; `horarioEnTexto()` deja de poder romper el registro con un horario
mal escrito (A10).

## Observabilidad
- Eventos de auditoría: no aplica. Datos sensibles en logs: ninguno. Logs nuevos: ninguno.

## SEO
Sin cambios en las rutas ni en los metadatos desde la ronda 1 (allí, todos los temas ✅; Lighthouse SEO 100 en
`/terminos` y `/aviso-de-privacidad`).

## Hallazgos
Ninguno nuevo.

## Preparación para release
- Rollback factible: sí, al tag anterior con `docs/deployment.md`; sin migraciones ni datos.
- Docs actualizadas: `architecture.md`, `deployment.md`, `seo.md`, `security.md` (RS17.b, RS8.d) y `AGENTS.md`.
- Feature flags: ninguno.

## Tareas añadidas
- T034 ← R1 · T035 ← R2 · T036, T037 ← R3 · T038 ← R5 · T039, T040 ← R13 · T041 ← R8, R14 (ronda 1)

## Aceptados sin tarea
- **R4** → roadmap: la fecha de actualización de los términos y del aviso, en una sola fuente y una por texto
- **R6** → roadmap: el comportamiento de las ventanas con un runner JS si su script pasa a `public/js/`
- **R7** → roadmap: `horarioEnTexto()` fuera del controlador (clase de dominio) o pasado desde `RegisterController`
- **R9** → roadmap: `aria-haspopup` de los enlaces de la casilla solo cuando hay `showModal`
- **R10** → roadmap: las secciones de los textos legales sin `aria-labelledby` (menos regiones para el lector)
- **R11** → roadmap: el texto desplazable de la ventana enfocable con el teclado en navegadores antiguos
- **R12** → roadmap: la ventana no se cierra al soltar sobre el velo una selección empezada dentro
