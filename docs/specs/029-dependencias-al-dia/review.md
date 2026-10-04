---
spec: 029-dependencias-al-dia
verdict: approved
round: 2
date: 2026-10-03
base: 436d20a
head: 728c107
human_signoff: no requerido
---

# Review · 029 Dependencias sin avisos de seguridad conocidos

## Resumen
**approved.** R1 queda corregido. El docblock de `DependenciasSegurasTest` distingue ya las mínimas
de la 024 (críticas o altas, salvo `follow-redirects`) de las de la 029 (bajas y una media), y su
línea más larga mide 84 caracteres.

El diff de esta ronda (`436d20a..728c107`, unos 1.2 KB, solo el comentario) se revisó
directamente, releyendo el archivo, como prevé `--rerun` para un diff pequeño. No aparecen hallazgos
nuevos.

Conteo de la ronda: bloqueante 0 · importante 0 · menor 0.

Ronda anterior: [history/review.r1.md](history/review.r1.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 152 passed (1207 assertions); línea base de T001: 152 |
| `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` | ✅ PASS |
| `python .ai/bin/aidd.py validate docs/specs/029-dependencias-al-dia` | ✅ 0 errores |

## Criterios de aceptación
Sin cambios respecto a la ronda 1: CA1–CA7 ✅. En esta ronda solo cambia un comentario del test de CA1.

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea; T024 cita el archivo cambiado).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1–P9 | ✅ / ➖ | Sin cambios respecto a la ronda 1; esta ronda solo toca un comentario |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`436d20a..HEAD`) | ✅ 0 hallazgos |
| composer audit `--locked` sin filtro | ✅ "No security vulnerability advisories found" |
| npm audit | ✅ 0 |
| semgrep, trivy | ➖ Sin cambios en `app/`, lockfiles ni Docker desde la ronda 1 (trivy: solo el falso positivo de `vendor/`, 027/R20) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| Todos | ✅ / ➖ | Sin cambios respecto a la ronda 1 |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica.
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
Seguimiento de la ronda 1:

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | menor | corregido | T024: `tests/Feature/DependenciasSegurasTest.php:8-15` con el texto nuevo; ninguna línea pasa de 84 caracteres; `pint --test` pasa y el test sigue 2/2 |

Hallazgos nuevos: ninguno.

## Preparación para release
- Rollback factible: sí. Volver al tag anterior restaura `composer.lock`, pero reabre RS17 y los avisos de EX1.
- Migraciones: no hay.
- Feature flags: no aplica.
- Docs actualizadas: `security.md` (T091); `architecture.md` sin cambios (T090).
- SCA: 0 avisos de cualquier severidad.
- Cobertura de riesgos: RS17.a "en curso" hasta la release, que la pasa a mitigada y retira EX1 (T098). Nada figura como mitigado antes de tiempo.

## Tareas añadidas
(Ronda 1: T024 ← R1, hecha.)

## Aceptados sin tarea
Ninguno.
