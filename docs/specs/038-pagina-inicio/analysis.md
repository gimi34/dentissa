---
result: pass
round: 7
mode: delta
constitution_version: 1.3.0
date: 2026-10-31
spec_sha: 8ce69ee1e5e4
plan_sha: 1b88929b37a6
tasks_sha: 61bf611585c2
---

# Análisis · 038 Página de inicio pública

## Resumen
Delta pequeño sobre las correcciones de la ronda 6 (`/plan 038 --fix A35, A36` y `/tasks 038 --fix
A34–A36`: D22, T033 nueva y ajustes de D21, T046, T051, T053 y T058), hecho sin subagente releyendo las
secciones afectadas. Los tres altos quedan resueltos y no aparecen hallazgos nuevos.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | sin cambios desde la ronda 5 |
| Principios de la constitución evaluados | 10 / 10 (sin cambios desde la ronda 6) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A34 | ALTA | resuelto | T033: `contacto` único con la forma de la 038 y el teléfono ficticio `753 000 0000`, comprobado con `PaginaInicioTest` y los tests de la 503 de la 020; plan D22 |
| A35 | ALTA | resuelto | plan D21 y fila `app.css`, T053: `--sombra-tarjeta-alta` y `--leading-publico` se quedan; T051 exige que estén declaradas una vez y que existan todas las variables de `errores.css` |
| A36 | ALTA | resuelto | T033 antes de T046 (T046 `depende: T033`; T047–T059 dependen de T046): fusión con criterio de hecho comprobable (suite, `pint`, `node --test`, `validate` y `capturar.mjs --seco --medir`); T033 cumple ≤ 3 archivos (la fusión cuenta como uno generado) y está en las filas CA5 y CA9 de Cobertura |
| A37–A40 | MEDIA/BAJA | aceptados | Notas de T047, T050, T052, T054, T087 y T097 (ver «Aceptados») |

## Hallazgos nuevos
Ninguno.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-31 en la ronda 6 (vía ligera), siguen vigentes:
- **A37** → nota de T050, T087: `public/fonts/` lleva Roboto (020), Cormorant Garamond y Montserrat; `test_las_fuentes_van_con_su_licencia` comprueba un `OFL.txt` en cada carpeta y el peso de `/` cuenta solo las fuentes que carga la página pública
- **A38** → nota de T047, T050: T047 comprueba con `npm view` la versión, la fecha y la integridad de cada paquete y anota en `system.md` («Fuentes») el SHA-256 de cada woff2; T050 añade un caso que compara los archivos con esa tabla
- **A39** → nota de T052, T054: la fila de hoy y el progreso de la escena salen a funciones puras expuestas como `estadoAhora` y se prueban en `tests/js/publico.test.mjs`
- **A40** → nota de T097: la versión de la 038 se fija en `/release 038` (la siguiente menor tras v0.12.0), no v0.11.0
