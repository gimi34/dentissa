---
spec: 024-dependencias-seguras
verdict: approved
round: 2
date: 2026-09-29
base: 9c3f95b
head: dd17bbd
human_signoff: no requerido
---

# Review · 024 Dependencias sin vulnerabilidades críticas ni altas

## Resumen
**approved.** Las tareas T012, T025 y T026 corrigen los siete hallazgos que el usuario decidió
arreglar en la ronda 1 (R1–R7):
- el control SCA del CI audita el lockfile con `--locked`;
- semgrep usa un ruleset que existe;
- el test del servidor lee `composer.lock` con mínimas por rama mayor;
- la fijación de `rolldown` tiene su recordatorio en el roadmap.

El diff de esta ronda no introduce hallazgos nuevos. Conteo: bloqueante 0 · importante 0 · menor 0
(R8, de la ronda 1, sigue aceptado sin tarea).

Ronda anterior: [history/review.r1.md](history/review.r1.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 24 passed (110 assertions); línea base de T002: 22 passed |
| `docker compose exec app vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` | ✅ sin cambios |
| `npm run build` | ✅ código 0 |
| `python .ai/bin/aidd.py validate docs/specs/024-dependencias-seguras` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `tests/Feature/DependenciasSegurasTest.php::test_las_dependencias_del_servidor_estan_en_versiones_corregidas`; `composer audit --locked --ignore-severity=low --ignore-severity=medium` | ✅ | Lee `composer.lock`. Falla con el lock de c2d352d (Guzzle 7.13.2, CommonMark 2.8.2) y si una rama mayor no tiene mínima (nota de T012). |
| CA2 | `…::test_las_dependencias_del_frontend_estan_en_versiones_corregidas`; `npm audit --audit-level=high` | ✅ | Sin cambios desde la ronda 1. |
| CA3 | `php artisan test` | ✅ | 24 = línea base de 22 + los 2 de `DependenciasSegurasTest`. |
| CA4 | `npm run build` | ✅ | |
| CA5 | Verificación manual (T023) | ✅ | Sin cambios de dependencias desde la confirmación. |
| CA6 | T022 | ✅ | Los lockfiles no cambian en esta ronda (`deps.stat` vacío). |
| CA7 (abuso) | T022 | ✅ | Ídem. |

## Seguimiento de la ronda anterior
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `.github/workflows/ai-dd.yml:101` y `:120`, `.ai/project.yaml` (`sca`) y `docs/security.md` (fila SCA) usan `composer audit --locked …`. En un directorio sin `vendor`: lock actual → 0; lock de c2d352d → 1 (nota de T025). |
| R2 | corregido | `p/laravel` no aparece en el CI, `.ai/project.yaml` ni `docs/security.md`. `semgrep scan --config p/php --baseline-commit c2d352d --error` → 0. |
| R3 | corregido | El test ya no usa `InstalledVersions`. Si el paquete falta, falla con "no aparece en composer.lock". |
| R4 | corregido | `MINIMAS_SERVIDOR` por rama mayor; una rama sin mínima falla. |
| R5 | corregido | El test del servidor lee `composer.lock`; el docblock lo dice. |
| R6 | corregido | El docblock explica que `follow-redirects` se incluye a propósito aunque su aviso era moderado. |
| R7 | corregido | `docs/roadmap.md → Pendientes`: retirar `overrides.rolldown` cuando `rolldown` 1.2.11 o posterior cumpla 7 días. |
| R8 | aceptado sin tarea | Sin cambios (ver "Aceptados sin tarea"). |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin tarea. `scope.md` no muestra archivos sin tarea. T025 amplía
  D10 con `--locked` y la retirada de `p/laravel`, por decisión del usuario en `/review` ronda 1.
- Partes del plan sin implementar: ninguna. T001, T002 y T022 están en el rango de la ronda 1, y
  T026 solo cambia documentación.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Tareas añadidas por `/review` con la aprobación del usuario antes del código (359f121). |
| P2 | ✅ / ❌ aceptado | El test de T012 falla con el lock vulnerable y pasa con el actual. CA3–CA7 siguen con la excepción aceptada. |
| P3, P4, P7, P8 | ➖ | Sin rutas, entradas, datos sensibles ni logs. |
| P5 | ✅ | gitleaks sin fugas en `c2d352d..HEAD`. |
| P6 | ✅ | 0 críticas y 0 altas; los controles SCA y SAST del CI ahora pueden ejecutarse y fallar por hallazgos reales. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos (gitleaks v8.21.2, `c2d352d..HEAD`) | ✅ 0 hallazgos |
| SAST (semgrep 1.99.0, `--config p/php --baseline-commit c2d352d`) | ✅ 0 hallazgos nuevos |
| SCA (`composer audit --locked --ignore-severity=low --ignore-severity=medium && npm audit --audit-level=high`) | ✅ crít: 0 · alta: 0 · media: 0 (2 bajas ignoradas, EX1) |
| Contenedores / FS (trivy) | ✅ Sin cambios de dependencias desde la ronda 1. Resultado anterior descartado con justificación (documentación de `mockery` fuera de git). |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A03:2025 Cadena de suministro | ✅ | Sin cambios en los lockfiles; auditorías con código 0. |
| A08:2025 Integridad del software (CI) | ✅ | Los controles SCA y SAST del PR ya no fallan de forma incondicional (R1, R2). Acciones fijadas por etiqueta: RD6.a, ya registrado. |
| Proceso: rutas protegidas | ✅ | `.github/` cambia solo por T025, aprobada por el usuario en `/review` antes del cambio. |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (sin sección de Auditoría).
- Datos sensibles en logs del código tocado: ninguno; no hay logs nuevos.
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
Ninguno nuevo en esta ronda.

## Preparación para release
- Rollback factible: sí. Basta revertir los lockfiles, `package.json` y las líneas del CI. No hay
  migraciones.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas: `docs/security.md` (RS16 en curso, por corrección; EX1; herramientas),
  `docs/roadmap.md` (EX1, nota de la 020 y override de `rolldown`). `architecture.md` y
  `deployment.md` sin cambios necesarios.
- Vulnerabilidades críticas o altas sin excepción: ninguna.
- Antes de abrir el PR: integrar `chore/ai-dd-init` en `main` con merge commit (Rollout, paso 2)
  y repetir las comprobaciones de A24 (nota de T040).

## Tareas añadidas
Ninguna en esta ronda. Las de la ronda 1 (T012 ← R3–R6, T025 ← R1–R2, T026 ← R7) están hechas.

## Aceptados sin tarea
- **R8** → roadmap: la evidencia de T022 no registra que `integrity` y `resolved` coincidan con el registro (comprobado en /review: coinciden las 48); en próximas specs de dependencias, compararlos con `npm view <p>@<v> dist.integrity dist.tarball`.
