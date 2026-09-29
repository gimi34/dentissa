---
spec: 024-dependencias-seguras
plan: plan.md
status: draft
---

# Tareas · 024 Dependencias sin vulnerabilidades críticas ni altas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).
- Versión anterior: `history/tasks.v1.md`, regenerada con `--redo` tras `/analyze` ronda 1 y `/plan --fix`.

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 024 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA4–CA7 sin test PHPUnit (construcción de assets, login manual y restricciones del diff) — aprobado por el usuario el 2026-09-29 | T011 (`DependenciasSegurasTest`) se escribe antes de actualizar y falla hoy; T020 y T021 dependen de ella (CA1, CA2). CA3 lo cubre la suite existente. CA4–CA7 se verifican en T021, T022 y T023, según la desviación aceptada en el plan. |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas ni permisos. |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas ni SQL. |
| P5 Nada sensible en el repo | ✅ | Solo lockfiles, `package.json`, un test y `docs/security.md`. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Objeto de la spec (T020, T021, T040). Ninguna tarea añade dependencias directas; la fijación de `rolldown` va en `overrides` (CA7). |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. |

## Preparación
- [ ] T001 Crear la rama `feature/024-dependencias-seguras` desde `chore/ai-dd-init` y traer con `cherry-pick` los commits de la spec, el plan, las tareas y el análisis (`analysis.md`) de la 024, resolviendo a mano los conflictos de `docs/specs/README.md` y `docs/roadmap.md` (solo se añaden la fila de la 024 y su enlace en Pendientes). Toca más de 3 archivos porque es un `cherry-pick` mecánico, sin cambios propios — docs/specs/024-dependencias-seguras/, docs/specs/README.md, docs/roadmap.md — hecho cuando: la rama existe, `aidd.py status` muestra la 024 con spec y plan `approved` y el análisis vigente, y `composer.lock`, `package.json` y `package-lock.json` son idénticos a los de `chore/ai-dd-init`
- [ ] T002 Registrar la línea base en la rama nueva: `php artisan test` (recuento en verde); `npm run build` sin errores; versiones instaladas de `guzzlehttp/guzzle` y `league/commonmark` (`vendor` vive en un volumen compartido entre ramas); versión mayor de cada paquete de `packages` (no `packages-dev`) de `composer.lock` y de cada paquete npm de los assets construidos; número de entradas con `hasInstallScript` en `package-lock.json` — docs/specs/024-dependencias-seguras/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos — cubre: CA3, CA4, CA6 — depende: T001

## Tests (antes de implementar)
- [ ] T010 Ejecutar las verificaciones de seguridad y confirmar que fallan por la razón esperada: `docker compose exec app composer audit` reporta altas y medias en `guzzlehttp/guzzle` y `league/commonmark`, y `npm audit --audit-level=high` reporta críticas y altas (test de TM4) — (sin archivos; nota en tasks.md) — hecho cuando: la nota recoge el recuento por severidad y el código de salida de ambas, y los paquetes afectados — cubre: CA1, CA2 — depende: T002
- [ ] T011 Escribir `DependenciasSegurasTest`: `test_las_dependencias_del_servidor_estan_en_versiones_corregidas` (versiones instaladas vía `Composer\InstalledVersions`) y `test_las_dependencias_del_frontend_estan_en_versiones_corregidas` (versiones de `package-lock.json`), contra la tabla de versiones mínimas corregidas del plan (test de TM4) — tests/Feature/DependenciasSegurasTest.php — hecho cuando: los dos fallan por la razón esperada (`guzzle` 7.13.2 < 7.15.2, `commonmark` 2.8.2 < 2.10.0, `vite` 8.0.1 < 8.0.16…) — cubre: CA1, CA2 — depende: T002

## Implementación
- [ ] T020 Actualizar las dependencias del servidor: `docker compose exec app composer update league/commonmark guzzlehttp/guzzle --with-dependencies`, sin tocar `composer.json` (controles TM1, TM3, TM4) — composer.lock — hecho cuando: `composer audit` termina con código 0 (0 avisos); `test_las_dependencias_del_servidor_estan_en_versiones_corregidas` pasa; `composer.json` sin cambios; `php artisan test` iguala la línea base de T002 más los tests de T011; la nota recoge las versiones resueltas (si difieren de la simulación, solo se aceptan menores o de parche, D8) y las nuevas de `guzzle` y `commonmark` — cubre: CA1, CA3, CA6, CA7 — depende: T010, T011
- [ ] T021 Actualizar las dependencias del frontend: `npm audit fix --ignore-scripts`, `npm install --save-dev vite@8.3.0 --ignore-scripts` y `"overrides": { "rolldown": "1.2.9" }` en `package.json` seguido de `npm install --ignore-scripts` (controles TM1, TM2, TM4) — package-lock.json, package.json — hecho cuando: `npm audit --audit-level=high` termina con código 0; `test_las_dependencias_del_frontend_estan_en_versiones_corregidas` pasa; `vite` queda en 8.3.0 y `rolldown` en 1.2.9; en `package.json` solo cambian el rango de `vite` (`^8.3.0`) y `overrides`; el lockfile conserva las entradas linux-x64 (gnu/musl) de `@rolldown/binding-*` y `lightningcss-*`; `npm run build` termina sin errores — cubre: CA2, CA4, CA6, CA7 — depende: T010, T011
- [ ] T022 Verificar las restricciones del cambio sobre el diff de ambos lockfiles, con las versiones realmente resueltas (tests de TM1 y TM2): (1) ninguna versión mayor cambia en `packages` de `composer.lock` ni en los paquetes de los assets construidos, y `proxy-from-env` no aparece en `public/build`; (2) `composer.json` y `package.json` sin dependencias directas nuevas; (3) cada versión que cambia, directa o transitiva, lleva al menos 7 días publicada (`npm view <paquete> time`, campo `time` de Packagist); (4) cada entrada nueva o cambiada de `package-lock.json` tiene `resolved` bajo `https://registry.npmjs.org/` e `integrity`, y cada paquete cambiado de `composer.lock` viene de Packagist o GitHub; (5) ninguna entrada nueva con `hasInstallScript` respecto a T002 — docs/specs/024-dependencias-seguras/tasks.md — hecho cuando: la nota recoge la tabla de paquetes cambiados (versión antes y después, fecha y origen) y los cinco puntos se cumplen; si alguno no se cumple, se detiene la implementación y se reabre el plan (D8) — cubre: CA6, CA7 — depende: T020, T021
- [ ] T023 Verificación manual del login con Google en local, hecha por el usuario tras T020 (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: el usuario confirma que entra con Google en `http://localhost:8000` igual que antes, y la nota lo registra con fecha — cubre: CA5 — depende: T020

## Integración y documentación
- [ ] T040 Verificación final completa: `php artisan test`, `npm run build`, `composer audit`, `npm audit --audit-level=high` y `python .ai/bin/aidd.py validate docs/specs/024-dependencias-seguras` — (sin archivos) — hecho cuando: los cinco en verde (suite igual a la línea base de T002 más T011; ambas auditorías con código 0, así que el job de dependencias del PR queda en verde; validador sin errores) — cubre: CA1, CA2, CA3, CA4 — depende: T022, T023
- [ ] T090 Comprobar que `docs/architecture.md` no necesita cambios (no cita versiones de dependencias) y anotarlo
- [ ] T091 Actualizar `docs/security.md → RS16`: RS16.a, RS16.b y RS16.c → "en curso (spec 024)", por corrección; el recuento de la línea base con los datos de T010; y una nota de que `rolldown` va fijado en 1.2.9 con `overrides` hasta que una versión más reciente cumpla los 7 días. `docs/deployment.md` y `docs/observability.md` sin cambios
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011, T040 | T020 |
| CA2 | T010, T011, T040 | T021 |
| CA3 | T002, T040 | T020 |
| CA4 | T002, T040 | T021 |
| CA5 | T023 | T020 |
| CA6 | T002, T022 | T020, T021 |
| CA7 | T022 | T020, T021 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T021 | T022 |
| TM2 | T021 | T022 |
| TM3 | T020 | T023, T040 |
| TM4 | T020, T021 | T010, T011, T040 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| composer.lock | T020 |
| package-lock.json | T021 |
| package.json | T021 |
| tests/Feature/DependenciasSegurasTest.php | T011 |
| docs/security.md | T091 |
