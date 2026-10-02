---
spec: 024-dependencias-seguras
verdict: changes_requested
round: 1
date: 2026-09-29
base: c2d352d
head: 9c3f95b
human_signoff: no requerido
---

# Review · 024 Dependencias sin vulnerabilidades críticas ni altas

## Resumen
**changes_requested.** No hay bloqueantes: los siete criterios se cumplen y las auditorías locales
no reportan vulnerabilidades críticas ni altas. El usuario decide corregir antes de liberar dos
hallazgos importantes del CI y cinco menores:
- R1: `composer audit` sin `--locked` falla siempre en un runner sin `vendor`.
- R2: `semgrep` con `p/laravel`, un ruleset que no existe, rompe el paso SAST antes que el de
  dependencias.
- R3–R6: cuatro hallazgos menores en el test de versiones.
- R7: un recordatorio del override de `rolldown`.

Conteo: bloqueante 0 · importante 2 · menor 6 (R8 aceptado sin tarea).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 24 passed (110 assertions); línea base de T002: 22 passed |
| `docker compose exec app vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` | ✅ sin cambios |
| `npm run build` | ✅ código 0 (vite 8.3.0) |
| `python .ai/bin/aidd.py validate docs/specs/024-dependencias-seguras` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `tests/Feature/DependenciasSegurasTest.php::test_las_dependencias_del_servidor_estan_en_versiones_corregidas`; `composer audit --ignore-severity=low --ignore-severity=medium` | ✅ | Las mínimas coinciden con los avisos altos del lock de c2d352d. El test lee `vendor` y no el lock (R5), y usa una sola mínima por paquete (R4) → T012. |
| CA2 | `…::test_las_dependencias_del_frontend_estan_en_versiones_corregidas`; `npm audit --audit-level=high` | ✅ | Cubre los 8 paquetes con avisos críticos o altos, también las copias anidadas; falla si un paquete falta o cambia a una rama mayor sin mínima. |
| CA3 | `php artisan test` | ✅ | 24 = línea base de 22 + los 2 tests de T011. |
| CA4 | `npm run build` | ✅ | |
| CA5 | Verificación manual (T023) | ✅ | Confirmada por el usuario el 2026-09-29, con Guzzle 7.15.5. |
| CA6 | Comparación de versiones mayores (T022) | ✅ | 82 → 82 paquetes de Composer sin saltos mayores. Assets: solo `axios` 1.13.6 → 1.20.0. `proxy-from-env` (1 → 2) no llega a los assets. |
| CA7 (abuso) | Comprobación de directas, fechas, origen e integridad (T022) | ✅ | Sin dependencias directas nuevas. Las 48 entradas npm y los 8 paquetes de Composer tienen ≥ 7 días. El revisor comprobó `resolved`, `integrity` y `dist.reference` contra los registros. |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los 3 archivos de código (`.github/workflows/ai-dd.yml`,
  `package.json` y el test) tienen tarea, y `T002` solo cambia `tasks.md`.
- Partes del plan sin implementar: ninguna. Todos los módulos de "Cambios por módulo" aparecen en
  el diff.
- Las correcciones de R1 y R2 (T025) amplían lo que cambia en el CI respecto a D10: añaden
  `--locked` y quitan `p/laravel`. Las añade `/review` con la aprobación del usuario.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, plan y tareas aprobados antes del primer cambio de código (a79188f, 944fb55). |
| P2 | ✅ / ❌ aceptado | CA1 y CA2: test escrito antes de actualizar (a79188f) y que fallaba. CA3–CA7: excepción aceptada por el usuario el 2026-09-29. |
| P3 | ➖ | Sin rutas ni permisos. |
| P4 | ➖ | Sin entradas ni SQL. |
| P5 | ✅ | gitleaks sin fugas. El diff solo contiene lockfiles, un test, comandos y documentación. |
| P6 | ✅ | 0 críticas y 0 altas. Las 2 bajas de Composer están en EX1, que tiene motivo, aprobador y vencimiento. El filtro de severidades no afecta a críticas ni altas. |
| P7 | ➖ | Sin accesos a datos sensibles. |
| P8 | ➖ | Sin logs nuevos. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| Secretos (gitleaks v8.21.2, `c2d352d..HEAD` y `docs/`) | ✅ 0 hallazgos |
| SAST (semgrep 1.99.0) | ❌ con `--config p/laravel`: HTTP 404, código 7 (R2). ✅ con `--config p/php` sobre los archivos tocados: 0 hallazgos |
| SCA (`composer audit --ignore-severity=low --ignore-severity=medium && npm audit --audit-level=high`) | ✅ crít: 0 · alta: 0 · media: 0 (Composer muestra 2 bajas ignoradas, EX1). Sin `vendor`, `composer audit` sale con 1 (R1) |
| Contenedores / FS (trivy 0.57.1, HIGH/CRITICAL) | ✅ Descartado: el único resultado es `vendor/mockery/mockery/docs/requirements.txt` (setuptools, urllib3, wheel). Es documentación de una dependencia de desarrollo, fuera de git (`.gitignore:21`), y no se ejecuta ni se despliega. |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A03:2025 Cadena de suministro: vulnerabilidades conocidas | ✅ | Auditorías con código 0; `DependenciasSegurasTest`. |
| A03:2025 Cadena de suministro: dependencias nuevas y antigüedad | ✅ | Sin directas nuevas. Transitivas nuevas registradas en T022 con fecha, origen e `integrity`. `rolldown` fijado en 1.2.9 por la regla de 7 días. |
| A03:2025 Cadena de suministro: scripts de instalación | ✅ | Con `--ignore-scripts`. `hasInstallScript` solo en `fsevents`, igual que antes. |
| A08:2025 Integridad del software (CI) | ❌ → T025 | Los controles SCA y SAST del PR no funcionan: R1 y R2. Las acciones del CI siguen fijadas por etiqueta y no por SHA, ya registrado como RD6.a. |
| Proceso: rutas protegidas | ✅ | T024 se aprobó (a8c7989) antes de cambiar `.github/` (944fb55); no se tocan otras rutas protegidas. |
| Proceso: instrucciones dirigidas a agentes | ✅ | Ninguna en el diff. |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica. La spec no tiene sección de
  Auditoría y no toca datos sensibles.
- Datos sensibles en logs del código tocado: ninguno; no hay logs nuevos.
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `.github/workflows/ai-dd.yml:101`, `:120`; `.ai/project.yaml:33`; `docs/security.md:70` | El job del CI no ejecuta `composer install`, y sin `vendor` `composer audit` sale con 1 ("No installed packages found… pass --locked"). El control SCA del PR falla haya o no vulnerabilidades. Comprobado: con `--locked`, el lock nuevo da 0 y el de c2d352d da 1. | `composer audit --locked …` en los 4 sitios → T025 |
| R2 | importante (ya existía) | `.github/workflows/ai-dd.yml:93`, `:119`; `.ai/project.yaml:32`; `docs/security.md:69` | `--config p/laravel` devuelve HTTP 404 (código 7): el paso SAST falla en cada PR, antes que el paso de dependencias del mismo job. | Solo `--config p/php` → T025 |
| R3 | menor | `tests/Feature/DependenciasSegurasTest.php:47-50` | `InstalledVersions::getPrettyVersion()` lanza `OutOfBoundsException` si el paquete no está instalado, así que el mensaje "no está instalado" nunca aparece. No es un falso verde. | → T012 |
| R4 | menor | `tests/Feature/DependenciasSegurasTest.php:21-24` | Las mínimas del servidor no van por rama mayor: Guzzle 8.0.0, vulnerable, pasaría. Hoy no puede ocurrir por CA6. | Mínimas por rama → T012 |
| R5 | menor | `tests/Feature/DependenciasSegurasTest.php:10`, `:47` | El test del servidor lee `vendor`, que en local se comparte entre ramas, y no `composer.lock`. El docblock dice que protege el lockfile. | Leer `composer.lock` → T012 |
| R6 | menor | `tests/Feature/DependenciasSegurasTest.php:9`, `:34` | El docblock dice "avisos críticos o altos", pero `follow-redirects` tenía un aviso moderado. Está en la tabla del plan a propósito. | Docblock exacto → T012 |
| R7 | menor | `package.json:17`; `docs/security.md` RS16 | Retirar el override de `rolldown` solo figura en el texto de RS16, que se cerrará. | Viñeta en el roadmap → T026 |
| R8 | menor | `tasks.md` T022, punto 4 | La evidencia escrita solo comprueba que existen `resolved` e `integrity`, no que coincidan con el registro. El revisor lo comprobó y coinciden las 48. | Aceptado sin tarea |

Descartado tras verificar: `--ignore-severity` no oculta los avisos de la salida. `composer audit`
sigue listándolos como "Ignore reason: low severity is ignored", así que no se pierde visibilidad.

## Preparación para release
- Rollback factible: sí. Basta revertir `composer.lock`, `package-lock.json`, `package.json` y las
  líneas del CI. No hay migraciones y `composer.json` no cambia (redeploy del tag anterior, según
  el Rollout).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no aplica (Rollout).
- Docs actualizadas: `docs/architecture.md` sin cambios necesarios (T090); `docs/deployment.md`
  sin cambios (T091). `docs/security.md` pone RS16.a, RS16.b y RS16.c en "en curso (spec 024)",
  por corrección y sin ninguna mitigada, y añade EX1.
- Vulnerabilidades críticas o altas sin excepción: ninguna.
- Integración: el PR no se abre hasta integrar `chore/ai-dd-init` en `main` (Rollout paso 2;
  nota A24 de T040).

## Tareas añadidas
- T012 ← R3, R4, R5, R6
- T025 ← R1, R2
- T026 ← R7

## Aceptados sin tarea
- **R8** → roadmap: la evidencia de T022 no registra que `integrity` y `resolved` coincidan con el registro (comprobado en /review: coinciden las 48); en próximas specs de dependencias, compararlos con `npm view <p>@<v> dist.integrity dist.tarball`.
