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

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 024 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ✅ | T010 comprueba que las verificaciones de seguridad (CA1, CA2) fallan antes de actualizar, y T020/T021 dependen de ella. CA6 y CA7 son restricciones sobre el propio cambio, sin estado previo que pueda fallar: T022 las verifica sobre el diff justo después. La línea base de la suite y de la construcción (T002) protege contra regresiones (CA3, CA4). |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas ni permisos. |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas ni SQL. |
| P5 Nada sensible en el repo | ✅ | Solo lockfiles, `package.json` y `docs/security.md`. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Objeto de la spec (T020, T021, T040). Ninguna tarea añade dependencias directas (CA7). |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. |

## Preparación
- [ ] T001 Crear la rama `feature/024-dependencias-seguras` desde `chore/ai-dd-init` y traer con `cherry-pick` los commits de la spec, el plan, las tareas y el análisis de la 024, resolviendo a mano los conflictos de `docs/specs/README.md` y `docs/roadmap.md` (solo se añaden la fila de la 024 y su enlace en Pendientes) — docs/specs/024-dependencias-seguras/, docs/specs/README.md, docs/roadmap.md — hecho cuando: la rama existe, `aidd.py status` muestra la 024 con spec y plan `approved`, y `composer.lock`, `package.json` y `package-lock.json` son idénticos a los de `chore/ai-dd-init`
- [ ] T002 Registrar la línea base en la rama nueva: `php artisan test` (recuento en verde), `npm run build` sin errores, versiones mayores de `laravel/framework` y de las dependencias directas (`composer show --direct`, `npm ls --depth=0`) y número de entradas con `hasInstallScript` en `package-lock.json` — docs/specs/024-dependencias-seguras/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos — cubre: CA3, CA4, CA6 — depende: T001

## Tests (antes de implementar)
- [ ] T010 Ejecutar las verificaciones de seguridad y confirmar que fallan por la razón esperada: `docker compose exec app composer audit` reporta altas en `guzzlehttp/guzzle` y `league/commonmark`, y `npm audit --audit-level=high` reporta críticas y altas (control TM4) — (sin archivos; nota en tasks.md) — hecho cuando: la nota recoge el recuento por severidad de ambas y los paquetes afectados — cubre: CA1, CA2 — depende: T002

## Implementación
- [ ] T020 Actualizar las dependencias del servidor: `docker compose exec app composer update league/commonmark guzzlehttp/guzzle --with-dependencies`, sin tocar `composer.json` (controles TM3, TM4) — composer.lock — hecho cuando: `composer audit` no reporta críticas ni altas; cambian exactamente los 8 paquetes del plan a sus versiones (`guzzle` 7.15.5, `commonmark` 2.10.3…); `composer.json` sin cambios; `php artisan test` iguala la línea base de T002 — cubre: CA1, CA3, CA6 — depende: T010
- [ ] T021 Actualizar las dependencias del frontend: `npm audit fix --ignore-scripts` y después `npm install --save-dev vite@8.3.0 --ignore-scripts` (controles TM1, TM2, TM4) — package-lock.json, package.json — hecho cuando: `npm audit --audit-level=high` no reporta críticas ni altas; `vite` queda en 8.3.0; en `package.json` solo cambia el rango de `vite` (`^8.3.0`); `npm run build` termina sin errores — cubre: CA2, CA4, CA6, CA7 — depende: T010
- [ ] T022 Verificar las restricciones del cambio sobre el diff: versiones mayores antes y después (ni `laravel/framework` ni ninguna dependencia directa cambia de versión mayor); `composer.json` y `package.json` sin dependencias directas nuevas; fechas de publicación de las versiones nuevas de directas (`npm view <paquete> time`: `axios@1.20.0`, `concurrently@9.2.4`, `vite@8.3.0`) con al menos 7 días; ninguna entrada nueva con `hasInstallScript` respecto a T002 (tests de TM1 y TM2) — docs/specs/024-dependencias-seguras/tasks.md — hecho cuando: la nota recoge la comparación, las fechas y el recuento de `hasInstallScript`, y todo cumple — cubre: CA6, CA7 — depende: T020, T021
- [ ] T023 Verificación manual del login con Google en local, hecha por el usuario tras T020 (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: el usuario confirma que entra con Google en `http://localhost:8000` igual que antes, y la nota lo registra con fecha — cubre: CA5 — depende: T020

## Integración y documentación
- [ ] T040 Verificación final completa: `php artisan test`, `npm run build`, `composer audit` y `npm audit --audit-level=high` — (sin archivos) — hecho cuando: los cuatro en verde (suite igual a la línea base de T002; ambas auditorías sin críticas ni altas) — cubre: CA1, CA2, CA3, CA4 — depende: T022, T023
- [ ] T090 Comprobar que `docs/architecture.md` no necesita cambios (no cita versiones de dependencias) y anotarlo
- [ ] T091 Actualizar `docs/security.md → RS16`: RS16.a, RS16.b y RS16.c → "en curso (spec 024)", por corrección, y el recuento de la línea base con los datos de T010; `docs/deployment.md` y `docs/observability.md` sin cambios
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T040 | T020 |
| CA2 | T010, T040 | T021 |
| CA3 | T002, T040 | T020 |
| CA4 | T002, T040 | T021 |
| CA5 | T023 | T020 |
| CA6 | T002, T022 | T020, T021 |
| CA7 | T022 | T021 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021 | T022 |
| TM2 | T021 | T022 |
| TM3 | T020 | T023, T040 |
| TM4 | T020, T021 | T010, T040 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| composer.lock | T020 |
| package-lock.json | T021 |
| package.json | T021 |
| docs/security.md | T091 |
