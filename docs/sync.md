---
date: 2026-10-22
since: 95841da (commit de /init)
head: ae820ba
status: closed      # open → closed cuando el usuario cierra la conciliación
---
# Conciliación 2026-10-22

## Resumen

Primera conciliación desde el `/init` (`sync.last` estaba vacío): 521 commits revisados en
`main`, 4 fuera del flujo. Ninguno deja deriva sin conciliar: el único con comportamiento nuevo
(`b1bd2dd`, la landing del colaborador) ya lo recogieron dos fichas y la spec 038. Las 6 specs
`released` tocadas después siguen cumpliendo sus criterios. `architecture.md` no tiene huecos y no
hay fichas abiertas. Las 12 specs `inferred` sin validar quedan pospuestas por decisión del usuario; conciliación cerrada.

## Commits fuera del flujo

| commit | qué es | propuesta | decisión |
|---|---|---|---|
| `668e49c` (2026-10-21) chore(ai-dd): flujo actualizado a 1.16.0 | trivial: upgrade del flujo (`/init --upgrade`, PR #41); en código solo añade `.ai/inspect/` a `.gitignore` | nada | nada (usuario, 2026-10-22) |
| `b1bd2dd` (2026-10-17, abound64) Resolviendo conflictos de rutas | comportamiento nuevo + formato: `/` servía `landing.index` (con `cover-bg.jpg` y `logo-rosa.png`), `ExampleTest` cambiado a esperar la landing, formato de Pint en 13 archivos y, en Auth/Citas/ClienteCitas/Dashboard/Personal, los marcadores de conflicto que subió el merge `f096e17` | nada: ya conciliado por la ficha `2026-10-20-fix-marcadores-conflicto-main` (PR #35), la ficha `2026-10-20-fix-raiz-al-login` (PR #36: `/` vuelve al login y `ExampleTest` a esperarlo) y la spec 038 (T027 retira `landing/index.blade.php`, `cover-bg.jpg` y `logo-rosa.png`, que siguen en `main` sin ruta hasta que se libere) | nada (usuario, 2026-10-22) |
| `26da996` (2026-10-11) fix(deps): shell-quote 1.11.0 y source-map-js 1.2.2 | mantenimiento: el mismo contenido que la tarea T004 de la spec 031 (`released` en v0.10.0), adelantado para el `/release 028` (PR #21) | nada: la cubre la 031 y el CHANGELOG lo recoge | nada (usuario, 2026-10-22) |
| `4c7f48c` (2026-09-29) fix(ci): cargar el volcado de pruebas con utf8mb4_unicode_ci | mantenimiento del CI: alinea la base del servicio MariaDB con `bootstrap-test-db.sh` (errno 150 en `horarios_dentista`) | nada (el motivo está en el commit y en el comentario del workflow) · o ficha `/chore --retro` | nada (usuario, 2026-10-22) |

## Specs con deriva

| spec | commit | ¿respeta sus criterios? | propuesta | decisión |
|---|---|---|---|---|
| 024-dependencias-seguras | `26da996` (`package.json`) | sí: sube versiones sin avisos de `npm audit` | nada | nada (usuario, 2026-10-22) |
| 025-agenda-unica | `b1bd2dd` (controladores, middleware, modelos) | sí: formato de Pint; los marcadores los quitó el PR #35 y la suite está en verde | nada | nada (usuario, 2026-10-22) |
| 026-estados-cita | `b1bd2dd` (Citas, ClienteCitas, `routes/web.php`) | sí: ídem; la ruta `/` la devolvió el PR #36 | nada | nada (usuario, 2026-10-22) |
| 027-horarios-validos | `b1bd2dd` (`PersonalController`) | sí: solo marcadores, ya quitados | nada | nada (usuario, 2026-10-22) |
| 028-reprogramar-citas | `b1bd2dd` (Citas, ClienteCitas, Dashboard, `routes/web.php`) | sí: ídem | nada | nada (usuario, 2026-10-22) |
| 030-despliegue-servidor-propio | `668e49c` (`.gitignore`) | sí: una carpeta local del inspector de diseño | nada | nada (usuario, 2026-10-22) |

## Arquitectura

Sin huecos detectados.

## Specs inferidas

Las 12 creadas por `/init` siguen en `inferred`. Ninguna describe ya algo que el código no hace por
estos 4 commits (la landing nunca entró en una spec y `/` vuelve al login), pero ninguna está
validada por el usuario. Varias se han extendido o reemplazado en parte por specs liberadas, con
su Historial al día: 001 por 030 y 031; 003 y 004 por 025, 026 y 028; 005 por 025 y 027; 012 por
028. (La 038, aún sin liberar, extiende la 001: su Historial se añade en `/release 038`.)

| spec | actualizada | propuesta | decisión |
|---|---|---|---|
| 001-autenticacion | 2026-10-20 | `/specify --edit 001` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 002-registro-pacientes | 2026-10-04 | `/specify --edit 002` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 003-agenda-citas-personal | 2026-10-18 | `/specify --edit 003` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 004-citas-cliente | 2026-10-18 | `/specify --edit 004` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 005-horarios-dentista | 2026-10-18 | `/specify --edit 005` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 006-pacientes | 2026-09-28 | `/specify --edit 006` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 007-expedientes-clinicos | 2026-09-28 | `/specify --edit 007` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 008-personal | 2026-09-28 | `/specify --edit 008` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 009-servicios | 2026-09-28 | `/specify --edit 009` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 010-usuarios-admin | 2026-09-28 | `/specify --edit 010` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 011-perfil-cliente | 2026-09-28 | `/specify --edit 011` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |
| 012-paneles-por-rol | 2026-10-18 | `/specify --edit 012` | pospuesta (usuario, 2026-10-22): se valida con `/specify --edit` cuando una spec toque su zona |

## Fichas abiertas

Ninguna. (`2026-10-20-chore-vite-8.3.4` está en `done` y sale con la siguiente `/release`.)
