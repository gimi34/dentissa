---
spec: 024-dependencias-seguras
plan: plan.md
status: draft
impl_base: c2d352d
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
| P2 Test que falla antes y pasa después | ❌ aceptado: CA3–CA7 sin test PHPUnit que falle antes, porque no hay comportamiento nuevo de la aplicación que probar — aprobado por el usuario el 2026-09-29 | T011 (`DependenciasSegurasTest`) se escribe antes de actualizar y falla hoy; T020 y T021 dependen de ella (CA1, CA2). CA3 (no regresión) se verifica con la suite completa en T020 y T040; CA4–CA7 en T021, T022 y T023, según la desviación aceptada en el plan. |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas ni permisos. |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas ni SQL. |
| P5 Nada sensible en el repo | ✅ | Solo lockfiles, `package.json`, un test, `docs/security.md`, `docs/roadmap.md` y el comando de auditoría en `.github/workflows/ai-dd.yml` y `.ai/project.yaml` (T024). |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Objeto de la spec (T020, T021, T040). Ninguna tarea añade dependencias directas; la fijación de `rolldown` va en `overrides` (CA7). T024 hace que Composer ignore solo las severidades baja y media, que P6 no exige (D10), y T091 registra los 2 avisos bajos como EX1. |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. |

## Preparación
- [x] T001 Crear la rama `feature/024-dependencias-seguras` desde `chore/ai-dd-init` y traer con `cherry-pick` los commits de la spec, el plan, las tareas y el análisis (`analysis.md`) de la 024, resolviendo a mano los conflictos de `docs/specs/README.md` y `docs/roadmap.md` (solo se añaden la fila de la 024 y su enlace en Pendientes). Toca más de 3 archivos porque es un `cherry-pick` mecánico, sin cambios propios — docs/specs/024-dependencias-seguras/, docs/specs/README.md, docs/roadmap.md — hecho cuando: la rama existe, `aidd.py status` muestra la 024 con spec y plan `approved` y el análisis vigente, y `composer.lock`, `package.json` y `package-lock.json` son idénticos a los de `chore/ai-dd-init`
  - nota: A23 — rama creada desde `chore/ai-dd-init` (95841da…4d668dc) con los 12 commits de la 024 (55f7cce…5c8ddf2) por `cherry-pick`. Único conflicto: la fila 020 de `docs/specs/README.md`, resuelto dejando la 020 como está en `chore/ai-dd-init` (`approved`) y añadiendo la fila 024. `git diff chore/ai-dd-init..feature/024-dependencias-seguras -- docs/roadmap.md` muestra exactamente dos viñetas: el enlace de la 024 y la nota de los pasos 4 y 5 de la 020. `docs/specs/024-dependencias-seguras` es idéntico al de `feature/020-auditoria-base`; los tres lockfiles y `package.json` son idénticos a `chore/ai-dd-init`; `aidd.py status` → 024 `approved`/`approved`, análisis a3 vigente, siguiente `/implement`.
- [x] T002 Registrar la línea base en la rama nueva: `php artisan test` (recuento en verde); `npm run build` sin errores; versiones instaladas de `guzzlehttp/guzzle` y `league/commonmark` (`vendor` vive en un volumen compartido entre ramas); versión mayor de cada paquete de `packages` (no `packages-dev`) de `composer.lock` y de cada paquete npm de los assets construidos, con la lista obtenida de una construcción con sourcemaps en un directorio temporal (los `node_modules/<paquete>` distintos de `sources`); número de entradas con `hasInstallScript` en `package-lock.json` — docs/specs/024-dependencias-seguras/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos — cubre: CA3, CA4, CA6 — depende: T001
  - nota: línea base (2026-09-29, rama en c2d352d): (1) `php artisan test` → 22 passed (108 assertions); (2) `npm run build` sin errores (2,8 s); (3) `vendor`: `guzzlehttp/guzzle` 7.13.2 y `league/commonmark` 2.8.2; (4) versiones mayores: los 82 paquetes de `packages` de `composer.lock` (entre ellos `laravel/framework` 12, `laravel/socialite` 5, `guzzlehttp/guzzle` 7, `guzzlehttp/psr7` 2, `guzzlehttp/promises` 2, `league/commonmark` 2, `nette/schema` 1, `nette/utils` 4, `symfony/*` 7 u 8, polyfills 1); paquetes npm de los assets (sourcemaps de `vite build --sourcemap` en un directorio temporal): solo `axios` 1.13.6, sin `proxy-from-env`; (5) `hasInstallScript`: 1 entrada (`node_modules/fsevents`). Los datos completos quedan en una copia local para comparar en T022.

## Tests (antes de implementar)
- [x] T010 Ejecutar las verificaciones de seguridad y confirmar que fallan por la razón esperada: `docker compose exec app composer audit` reporta altas y medias en `guzzlehttp/guzzle` y `league/commonmark`, y `npm audit --audit-level=high` reporta críticas y altas (test de TM4) — (sin archivos; nota en tasks.md) — hecho cuando: la nota recoge el recuento por severidad y el código de salida de ambas, y los paquetes afectados — cubre: CA1, CA2 — depende: T002
  - nota: 2026-09-29, línea base de seguridad. `composer audit` → código 1: 9 altas y 7 medias (`guzzlehttp/guzzle` 1 alta y 5 medias; `league/commonmark` 8 altas y 2 medias), más 2 bajas publicadas hoy tras el plan (`laravel/framework` 12.62.0, CVE-2026-102279, corregida en 12.69.0; `league/flysystem` 3.35.1, CVE-2026-102601, corregida en 3.35.3); ningún paquete abandonado. `npm audit --audit-level=high` → código 1: 2 críticas (`concurrently`, `shell-quote`), 6 altas (`axios`, `form-data`, `nanoid`, `picomatch`, `postcss`, `vite`) y 1 moderada (`follow-redirects`).
  - nota: desvío detectado en /implement: las 2 bajas hacen que `composer audit` y el job de CI fallen aunque se cumpla CA1. El usuario decide (2026-09-29) registrarlas como excepción con vencimiento en `docs/security.md` y que T020, T040 y el CI exijan `composer audit --ignore-severity=low`, sin ampliar la actualización. Implementación detenida antes de T011 hasta `/plan 024 --fix`, `/tasks 024 --fix` y `/analyze 024`.
  - nota: A25 — aclaración de la nota anterior: en `/plan 024 --fix` el usuario amplió la decisión (2026-09-29) a ignorar las severidades baja y media (`composer audit --ignore-severity=low --ignore-severity=medium`), aplicada en el CI, `/review` y `/release` vía `.ai/project.yaml` (D10, T024). La nota anterior queda como registro del momento.
- [x] T011 Escribir `DependenciasSegurasTest`: `test_las_dependencias_del_servidor_estan_en_versiones_corregidas` (versiones instaladas vía `Composer\InstalledVersions`) y `test_las_dependencias_del_frontend_estan_en_versiones_corregidas` (versiones de `package-lock.json`), contra la tabla de versiones mínimas corregidas del plan (test de TM4); el test del frontend evalúa toda entrada de `packages` cuya ruta termine en `node_modules/<paquete>`, también las anidadas, la compara con la mínima de su rama mayor y falla si la rama no tiene mínima definida — tests/Feature/DependenciasSegurasTest.php — hecho cuando: los dos fallan por la razón esperada (`guzzle` 7.13.2 < 7.15.2, `commonmark` 2.8.2 < 2.10.0, `vite` 8.0.1 < 8.0.16, y a la vez `picomatch` 4.0.3 y la anidada 2.3.1…) y `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` no reporta cambios dentro del contenedor — cubre: CA1, CA2 — depende: T002
  - nota: hecha tras retomar `/implement` (2026-09-29): no depende de la decisión sobre los avisos bajos de T010, porque las mínimas del plan no cambian. Los dos tests fallan por la razón esperada: servidor `guzzlehttp/guzzle 7.13.2 < 7.15.2` y `league/commonmark 2.8.2 < 2.10.0`; frontend, 10 instancias, entre ellas `node_modules/picomatch 4.0.3 < 4.0.4` y la anidada `node_modules/vite-plugin-full-reload/node_modules/picomatch 2.3.1 < 2.3.2`, y `vite 8.0.1 < 8.0.16`. Comprobado rompiendo la tabla: sin la mínima de la rama 2 de `picomatch`, el fallo cita "la rama 2 no tiene mínima definida"; restaurada. `vendor/bin/pint --test` sin cambios dentro del contenedor.

## Implementación
- [ ] T020 Actualizar las dependencias del servidor: `docker compose exec app composer update league/commonmark guzzlehttp/guzzle --with-dependencies`, sin tocar `composer.json` (controles TM1, TM3, TM4) — composer.lock — hecho cuando: `composer audit --ignore-severity=low --ignore-severity=medium` termina con código 0 y `composer audit` sin filtro solo reporta los 2 avisos bajos de EX1 (`laravel/framework`, `league/flysystem`; D10); `test_las_dependencias_del_servidor_estan_en_versiones_corregidas` pasa; `composer.json` sin cambios; `php artisan test` iguala la línea base de T002 más los tests de T011; la nota recoge las versiones resueltas (si difieren de la simulación, solo se aceptan menores o de parche, D8) y las nuevas de `guzzle` y `commonmark` — cubre: CA1, CA3, CA6, CA7 — depende: T010, T011
- [ ] T021 Actualizar las dependencias del frontend: `npm audit fix --ignore-scripts`, `npm install --save-dev vite@8.3.0 --ignore-scripts` y `"overrides": { "rolldown": "1.2.9" }` en `package.json` seguido de `npm install --ignore-scripts` (controles TM1, TM2, TM4) — package-lock.json, package.json — hecho cuando: `npm audit --audit-level=high` termina con código 0; `test_las_dependencias_del_frontend_estan_en_versiones_corregidas` pasa; `vite` queda en 8.3.0 y `rolldown` en 1.2.9; en `package.json` solo cambian el rango de `vite` (`^8.3.0`) y `overrides`; el lockfile conserva las entradas linux-x64 (gnu/musl) de `@rolldown/binding-*` y `lightningcss-*`; `npm run build` termina sin errores — cubre: CA2, CA4, CA6, CA7 — depende: T010, T011
- [ ] T022 Verificar las restricciones del cambio sobre el diff de ambos lockfiles, con las versiones realmente resueltas (tests de TM1 y TM2): (1) ninguna versión mayor cambia en `packages` de `composer.lock` ni en los paquetes de los assets construidos, con la lista obtenida como en T002 (sourcemaps), y `proxy-from-env` no aparece en esa lista; (2) `composer.json` y `package.json` sin dependencias directas nuevas; (3) cada versión que cambia, directa o transitiva, lleva al menos 7 días publicada (`npm view <paquete> time`, campo `time` de Packagist); (4) cada entrada nueva o cambiada de `package-lock.json` tiene `resolved` bajo `https://registry.npmjs.org/` e `integrity`, y cada paquete cambiado de `composer.lock` viene de Packagist o GitHub; (5) ninguna entrada nueva con `hasInstallScript` respecto a T002 — docs/specs/024-dependencias-seguras/tasks.md — hecho cuando: la nota recoge la tabla de paquetes cambiados (versión antes y después, fecha y origen) y los cinco puntos se cumplen; si alguno no se cumple, se detiene la implementación y se reabre el plan (D8) — cubre: CA6, CA7 — depende: T020, T021
- [ ] T023 Verificación manual del login con Google en local, hecha por el usuario tras T020 (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: el usuario confirma que entra con Google en `http://localhost:8000` igual que antes, y la nota lo registra con fecha — cubre: CA5 — depende: T020
- [ ] T024 Cambiar el comando de auditoría de dependencias a `composer audit --ignore-severity=low --ignore-severity=medium && npm audit --audit-level=high` en los dos pasos de dependencias de `.github/workflows/ai-dd.yml` (el job del PR y el informativo del repo completo), en `security.tools.sca` de `.ai/project.yaml` y en la fila SCA de la tabla Herramientas de `docs/security.md` (D10; `.github/` es ruta protegida y esta tarea aprobada la autoriza) — .github/workflows/ai-dd.yml, .ai/project.yaml, docs/security.md — hecho cuando: `git diff` de los tres archivos solo cambia esas 4 líneas; el comando, ejecutado en local (Composer dentro del contenedor, npm en el host), termina con código 0 — cubre: CA1, CA2 — depende: T020, T021

## Integración y documentación
- [ ] T040 Verificación final completa: `php artisan test`, `npm run build`, `composer audit --ignore-severity=low --ignore-severity=medium`, `npm audit --audit-level=high`, `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` y `python .ai/bin/aidd.py validate docs/specs/024-dependencias-seguras` — (sin archivos) — hecho cuando: los seis en verde (suite igual a la línea base de T002 más T011; ambas auditorías con código 0, así que el job de dependencias del PR queda en verde; Pint sin cambios; validador sin errores) — cubre: CA1, CA2, CA3, CA4 — depende: T022, T023, T024
- [ ] T090 Comprobar que `docs/architecture.md` no necesita cambios (no cita versiones de dependencias) y anotarlo
- [ ] T091 Actualizar `docs/security.md → RS16`: RS16.a, RS16.b y RS16.c → "en curso (spec 024)", por corrección; el recuento de la línea base con los datos de T010; y una nota de que `rolldown` va fijado en 1.2.9 con `overrides` hasta que una versión más reciente cumpla los 7 días. En `docs/security.md → Excepciones aceptadas`, fila EX1: `laravel/framework` 12.62.0 (CVE-2026-102279) y `league/flysystem` 3.35.1 (CVE-2026-102601), severidad baja, motivo de D10, aprobado por el usuario el 2026-09-29, vence el 2026-10-31. En `docs/roadmap.md → Pendientes`: actualizar `laravel/framework` a ≥ 12.69.0 y `league/flysystem` a ≥ 3.35.3 antes del 2026-10-31 (EX1). `docs/deployment.md` y `docs/observability.md` sin cambios
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011, T040 | T020, T024 |
| CA2 | T010, T011, T040 | T021, T024 |
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
| docs/security.md | T024, T091 |
| .github/workflows/ai-dd.yml | T024 |
| .ai/project.yaml | T024 |
| docs/roadmap.md | T091 |
