---
result: fail
round: 2
mode: delta
constitution_version: 1.0.0
date: 2026-09-29
spec_sha: a62bb70ff6f0
plan_sha: 165d0c1c8d32
tasks_sha: eca56b182d93
---

# Análisis · 024 Dependencias sin vulnerabilidades críticas ni altas

## Resumen
Resultado **fail** por un hallazgo CRÍTICO, A17: CA3 queda fuera de la excepción aceptada de P2.
Es un criterio de no regresión y no puede tener un test que falle antes. Además, la fila P2 del
plan tiene 2 celdas en una tabla de 3. Las correcciones de la ronda 1 resuelven A02, A03, A05 y
A08–A16. A01, A04, A06 y A07 se resolvieron pero su corrección abrió hallazgos nuevos: A01 → A17,
A04 → A19, y A06 y A07 → A20. Abiertos: CRÍTICA 1 · ALTA 0 · MEDIA 2 · BAJA 3. El usuario ya
decidió todos.

El cambio es estructural pero acotado: CA7 se amplía a las transitivas, se añade el test T011, y
aparecen `overrides`, D8 y D9. No hace falta otro `--redo`; la ronda 3 puede ser delta.

Ronda anterior: [history/analysis.r1.md](../history/analysis.r1.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 (CA1 y CA2 con test PHPUnit en T011; CA3–CA7 sin él, pendiente de registrar la excepción de P2: A17) |
| Amenazas con control y test | 4 / 4 |
| Principios de la constitución evaluados | 8 / 8 (P2 con excepción incompleta: A17) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A01 | ALTA | resuelto con efecto nuevo (→ A17) | T011 (`DependenciasSegurasTest`) cubre CA1 y CA2, y P2 figura como `❌ aceptado`; pero la excepción solo cubre CA4–CA7 y da CA3 por cubierto con "la suite completa". |
| A02 | ALTA | resuelto | CA7 de la spec ("cada versión que cambia, sea de una dependencia directa o de una de las que estas arrastran"), Estrategia CA7, TM1 y D9 del plan, y T022 (3). |
| A03 | MEDIA | resuelto | Cambios por módulo ("las definitivas son las que resuelva la actualización") y D8; T020 y T022 usan las versiones resueltas. |
| A04 | MEDIA | resuelto con efecto nuevo (→ A19) | CA6 cubre `packages` no dev y los paquetes de `public/build` (plan, T002, T022); pero buscar `proxy-from-env` en el bundle minificado no demuestra nada. |
| A05 | MEDIA | resuelto | Ambas auditorías deben terminar con código 0 (plan, T010, T020, T040); `composer.lock` no tiene paquetes `abandoned`. |
| A06 | MEDIA | resuelto con efecto nuevo (→ A20) | Paso 4 del Rollout, cuya comprobación solo mira `docs/specs/024-…`. |
| A07 | MEDIA | resuelto con efecto nuevo (→ A20) | Pasos 2, 3 y 5 del Rollout; los pasos 4 y 5 no están escritos en ningún documento de la 020. |
| A08 | MEDIA | resuelto | T021 conserva las entradas linux-x64 de `@rolldown/binding-*` y `lightningcss-*`; `@tailwindcss/oxide-*` no cambia. |
| A09 | MEDIA | resuelto | T022 (4): `resolved` bajo el registro oficial con `integrity`; en Composer, origen Packagist o GitHub. |
| A10 | BAJA | resuelto | T002 y T020 registran las versiones de `vendor`. |
| A11 | BAJA | resuelto | Las listas de paquetes nuevos y de `extraneous` coinciden con el lockfile. |
| A12 | BAJA | resuelto | La spec ya no cita RD7; el validador no da el aviso. |
| A13 | BAJA | resuelto | T010 "(test de TM4)". |
| A14 | BAJA | resuelto | T040 incluye `aidd.py validate`. |
| A15 | BAJA | resuelto | T001 incluye `analysis.md` y justifica que toque más de 3 archivos. |
| A16 | BAJA | resuelto | D2 registra `vite` 8.0.16 como la versión corregida mínima y el motivo del descarte. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A17 | Constitución (P2) | CRÍTICA | `plan.md` Constitution Check P2 y D5; `tasks.md` Constitution Check P2 | P2 exige un test PHPUnit que falle sin el cambio y pase con él. CA3 no puede tenerlo: la suite pasa antes y después, y ningún test puede fallar antes en un criterio de "sin regresiones". Aun así se da por cubierto, y la excepción aprobada solo abarca CA4–CA7. Además, la fila P2 del plan tiene 2 celdas en una tabla de 3. | Decidido: la excepción de P2 cubre CA3–CA7, con el mismo motivo y la misma fecha en el plan (fila P2 y D5) y en las tareas (fila P2), y la fila del plan tiene 3 celdas con el formato `❌ aceptado: <motivo> — aprobado por el usuario el <fecha>`. Comprobación: las filas P2 de plan y tareas listan el mismo conjunto de criterios y `aidd.py validate` no da errores. → `/plan 024 --fix`, `/tasks 024 --fix` |
| A18 | Inconsistencia | BAJA | `plan.md` Constitution Check P5; Modelo de amenazas, columna Test de TM4 | P5 dice "solo cambian lockfiles y el rango de `vite`", pero `package.json` también recibe `overrides` y hay un test nuevo. La columna Test de TM4 no cita `DependenciasSegurasTest`, aunque Trazabilidad y las tareas sí. | P5 y TM4 del plan coinciden con Cambios por módulo y con Trazabilidad. → `/plan 024 --fix` |
| A19 | Cobertura / Ambigüedad | MEDIA | `plan.md` Estrategia y Trazabilidad de CA6; T002; T022 (1) | Buscar `proxy-from-env` en `public/build` no demuestra nada: el bundle minificado no contiene nombres de paquete (0 apariciones de `node_modules` ni de `proxy-from-env`, y sí falsos positivos como el literal `form-data`). Tampoco hay método para obtener la lista de "paquetes npm de los assets construidos", que T002 y T022 necesitan. | La lista de paquetes npm de los assets sale del grafo real de la construcción, igual antes (T002) y después (T022). Comprobación: construcción con sourcemaps en un directorio temporal, listando los `node_modules/<paquete>` distintos de `sources`; `proxy-from-env` no aparece y ningún paquete de la lista cambia de versión mayor. → `/plan 024 --fix`, `/tasks 024 --fix` |
| A20 | Duplicación y conflicto / Cobertura | MEDIA | `plan.md` Rollout pasos 3–5 y D7 | (i) Los pasos 4 y 5 se ejecutan en el ciclo de la 020, pero solo están escritos en el plan de la 024. (ii) La comprobación del paso 4 solo mira `docs/specs/024-…`, y los commits de la 024 también tocan la fila 024 de `docs/specs/README.md` y su enlace en `docs/roadmap.md`. (iii) Si `chore/ai-dd-init` se integra con squash o rebase, el PR de la 024 vuelve a arrastrar sus commits. | Decidido: los pasos 4 y 5 se anotan en Pendientes del roadmap. La comprobación del paso 4 incluye la fila 024 del README y su enlace en el roadmap. `chore/ai-dd-init` se integra con merge commit, sin squash, y antes de abrir el PR de la 024, `git log --oneline main..feature/024-dependencias-seguras` solo muestra commits de la 024. → `/plan 024 --fix` (Rollout) y nota en el roadmap |
| A21 | Cobertura | BAJA | T011, T040 | La 024 añade un PHP (`tests/Feature/DependenciasSegurasTest.php`), y la Definición de terminado y el job `calidad` exigen `pint --test` sobre los PHP cambiados; ninguna tarea lo incluye. | `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php` sin cambios dentro del contenedor, en el criterio de T011 o de T040. → `/tasks 024 --fix` |
| A22 | Ambigüedad | BAJA | T011; `plan.md` tabla de versiones mínimas (`picomatch`) | El lockfile tiene dos instancias: `node_modules/picomatch` 4.0.3 y `node_modules/vite-plugin-full-reload/node_modules/picomatch` 2.3.1. Si el test solo mira el primer nivel, la mínima de la rama 2 nunca se comprueba. | El test evalúa toda entrada de `packages` cuya ruta termine en `node_modules/<paquete>`, la compara con la mínima de su rama mayor y falla si la rama no tiene mínima. Comprobación: el fallo de T011 cita a la vez `picomatch` 4.0.3 y la anidada 2.3.1. → `/tasks 024 --fix` |

## Decisiones pendientes del usuario
Resueltas en esta ronda:
- **A17:** la excepción aceptada de P2 se amplía a CA3–CA7 (decisión del usuario, 2026-09-29).
- **A20:** los pasos 4 y 5 de la integración se anotan en Pendientes del roadmap, y `chore/ai-dd-init` se integra en `main` con merge commit, sin squash (decisión del usuario, 2026-09-29).
- **A18, A19, A21 y A22:** se corrigen en el mismo `--fix` (decisión del usuario, 2026-09-29).

## Aceptados
Ninguno: todos los hallazgos se corrigen antes de `/implement`.
