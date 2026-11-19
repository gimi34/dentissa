---
spec: 051-ciudad-lista-cerrada
verdict: approved
round: 2
date: 2026-11-18
base: c364592
head: f0c20fa
human_signoff: no requerido
---

# Review · 051 Ciudad elegida de una lista cerrada

## Resumen
Aprobada. Los hallazgos de la ronda 1 que el usuario decidió corregir (R1, R4, R5, R6 y R7) están
resueltos y probados, y la suite completa pasa. R2 y R3 quedan como deuda en el roadmap. Ronda
`--rerun` sin subagentes: el diff desde la ronda 1 son 5 KB en 3 archivos, releídos desde cero.
Conteo: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, base propia `dentissa_051_test` con la migración aplicada, como en la CI) | ✅ 1499 tests, 0 fallos (ronda 1: 1497; +2 tests de T032 y T034) |
| `vendor/bin/pint --test` sobre los archivos de T032–T034 | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/051-ciudad-lista-cerrada` | ✅ 0 errores |
| Navegador, flujo de R1 con clics reales (390 y 1440 px) | ✅ 12/12 con el arreglo; sin él, la prueba se queda atascada |
| Verificación manual de T033 | ✅ confirmada por el usuario el 2026-11-18 |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA4, CA6, CA7, CA9–CA11 | los de la ronda 1 (`history/review.r1.md`) | ✅ | sin cambios |
| CA5 | además `test_crear_mi_cuenta_vuelve_al_paso_1_si_no_es_valido` y la verificación de T033 | ✅ | R1 resuelto |
| CA8 | además `CorreccionCiudadesTest::test_la_migracion_ejecuta_la_correccion` | ✅ | R5 resuelto |
| CA12 | `test_la_correccion_registra_un_evento_con_totales_y_sin_valores`, ahora con un único evento del test | ✅ | R7 resuelto |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; T032–T034 los añadió la ronda 1 (`añadida por /review`).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1–P10 | sin cambios respecto a la ronda 1 | el diff solo añade un manejador al botón del registro y ajusta tests; P2: T032 falló antes del arreglo y el test de la migración falla al romperla |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks, semgrep, SCA y trivy | ✅ sin hallazgos en la ronda 1; el diff de esta ronda no añade PHP de aplicación, dependencias ni configuración |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Inyección | ✅ | el manejador nuevo no usa entrada del usuario ni inserta HTML |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí; el test de CA12 exige ahora un único evento
  escrito durante el test y la migración tiene su propio test.
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 3. Indexación y canónica de `/registro` | ✅ | el diff solo toca el script del formulario; `PaginasPublicasTest` en verde |
- Sección "Descubribilidad" del plan aplicada: sí (No aplica).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|

Seguimiento de la ronda 1:

| ID | Estado | Evidencia |
|---|---|---|
| R1 | resuelto | `register.blade.php:409-418`: si el paso 1 no es válido, «Crear mi cuenta» vuelve a él y lo marca; T032, T033 y verificación manual |
| R2 | aceptado → roadmap | ver «Aceptados sin tarea» |
| R3 | aceptado → roadmap | ver «Aceptados sin tarea» |
| R4 | resuelto | `CiudadListaCerradaTest`: sin `formulariosSinEsaCiudad` |
| R5 | resuelto | `CorreccionCiudadesTest::test_la_migracion_ejecuta_la_correccion` (falla al romper `up()`) |
| R6 | resuelto | `register.blade.php:322-324` |
| R7 | resuelto | `CorreccionCiudadesTest:101`, `auditoriaDelTest()->…->sole()` |

## Preparación para release
- Rollback factible: sí, sin cambios respecto a la ronda 1 (`deploy/volver.sh <tag anterior>`).
- Migraciones compatibles con la versión anterior: sí.
- Docs actualizadas (architecture, deployment): sí (T090, T091).

## Tareas añadidas
- T032 ← R1 · T033 ← R1, R6 · T034 ← R4, R5, R7 (ronda 1; hechas)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-18:
- **R2** → roadmap: asociar las etiquetas de los filtros del directorio de pacientes (Buscar, Ciudad, Doctor) con sus campos; deuda previa a la 051.
- **R3** → roadmap: extraer a un parcial el aviso y el `aria-describedby` del campo «Ciudad», hoy copiados en perfil y en alta/edición.
