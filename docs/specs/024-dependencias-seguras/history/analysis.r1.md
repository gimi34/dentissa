---
result: fail
round: 1
mode: full
constitution_version: 1.0.0
date: 2026-09-29
spec_sha: 8d3a03d90d4c
plan_sha: f3dfdd2fb5b4
tasks_sha: 7628a7f18bf0
---

# Análisis · 024 Dependencias sin vulnerabilidades críticas ni altas

## Resumen
Resultado **fail**: no hay CRÍTICOS, pero hay 2 ALTOS que el usuario decide corregir.
- **A01:** P2 exige un test PHPUnit por criterio y la spec no tiene ninguno.
- **A02:** la regla de antigüedad no mira las versiones transitivas, que son casi todo lo que cambia.

Hay además 7 MEDIOS sobre ambigüedad de criterios, integración entre ramas y controles de
cadena de suministro sin comprobación, y 7 BAJOS. Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 7 · BAJA 7.

Datos del plan comprobados y ciertos:
- Solo `welcome.blade.php` usa Vite, y `resources/js/bootstrap.js` importa `axios`.
- `chore/ai-dd-init` tiene 22 tests.
- Las versiones de partida coinciden con el plan.
- Ni la 014 ni la 020 tocan lockfiles ni `package.json`, y la 024 no toca rutas protegidas.
- `--ignore-scripts` no rompe la construcción: hoy solo `fsevents` tiene `hasInstallScript`.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 (ninguno con test PHPUnit: A01) |
| Amenazas con control y test | 4 / 4 (el control de integridad de TM1 no tiene comprobación: A09) |
| Principios de la constitución evaluados | 8 / 8 (P2 como desviación, no como cumplimiento: A01) |

## Seguimiento de rondas anteriores
Primera ronda: no hay hallazgos anteriores.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A01 | Constitución (P2) | ALTA | `plan.md` Constitution Check P2 y D5; `tasks.md` Constitution Check P2 | P2 exige que cada criterio tenga al menos un test PHPUnit que falle sin el cambio y pase con él. Ningún criterio de la 024 lo tiene, y aun así se marca ✅. | Decidido: tests PHPUnit que comprueben que los paquetes vulnerables de servidor y frontend están en versiones corregidas (fallan antes de T020/T021 y pasan después) para CA1 y CA2, y P2 registrado como desviación aceptada por el usuario para CA4–CA7. → `/plan 024 --fix`, `/tasks 024 --fix` |
| A02 | Seguridad | ALTA | `spec.md` CA7; `plan.md` TM1 y Estrategia (CA7); T022 | La antigüedad mínima de 7 días solo se comprueba en las directas, y todo lo que cambia en el servidor es transitivo (`guzzle`, `commonmark`…), igual que gran parte de npm. Una versión maliciosa entraría justo por una transitiva. | Decidido: la antigüedad aplica a toda versión que cambie en cualquiera de los dos lockfiles, directa o transitiva. Si alguna no la cumple, se usa la versión corregida anterior o se espera. Comprobación: tabla de T022 con la fecha de publicación de cada paquete cambiado. → `/specify --edit 024` (CA7), `/plan 024 --fix`, `/tasks 024 --fix` |
| A03 | Ambigüedad | MEDIA | T020, T021, T022; `plan.md` D2 y Cambios por módulo | Las versiones salen de una simulación del 2026-09-29, pero `composer update` y `npm audit fix` resuelven la última disponible al ejecutarse. Si hay un parche nuevo, T020 falla o T022 comprueba versiones que no son las instaladas. | Decidido: las comprobaciones usan las versiones realmente resueltas en el diff. Una versión distinta de la simulada se acepta si es menor o de parche y cumple la antigüedad; si no, se reabre el plan. → `/plan 024 --fix`, `/tasks 024 --fix` |
| A04 | Inconsistencia / Ambigüedad | MEDIA | `spec.md` CA6 frente a `plan.md` Estrategia (CA6), T002 y T022 | CA6 habla de dependencias que se ejecutan en producción, lo que incluye transitivas como `guzzle` y `commonmark`, y `axios` (va empaquetado). La comprobación solo mira las directas, y `proxy-from-env` 1 → 2 no se clasifica. | Decidido: CA6 mira todas las dependencias no dev del servidor (también las transitivas) y los paquetes npm que acaban en los assets construidos. Comprobación: T022 lista cada paquete cambiado con su versión mayor antes y después. → `/plan 024 --fix`, `/tasks 024 --fix` |
| A05 | Ambigüedad | MEDIA | CA1, T020, T040; `.github/workflows/ai-dd.yml` (paso de dependencias) | `composer audit` sale con error ante cualquier aviso, también los medios, y el job del PR que cambia lockfiles ejecuta `composer audit && npm audit`. El plan no dice cuántos avisos quedan. | Decidido: el job de dependencias del PR debe quedar en verde (código de salida 0 de ambas auditorías; se esperan 0 avisos en el servidor). Comprobación: nota de T020/T040 con el recuento por severidad y el código de salida. → `/plan 024 --fix`, `/tasks 024 --fix` |
| A06 | Duplicación y conflicto | MEDIA | `plan.md` Rollout y D7; T001 | Los commits de la spec, el plan y las tareas de la 024 están también en `feature/020-auditoria-base`. Al integrar la 020 después, sus copias antiguas pueden devolver los documentos de la 024 a su estado anterior. | Decidido: 024 primero y 020 después. Antes de integrar la 020, su rama se reconstruye sobre `main` sin sus copias de los commits de la 024. Comprobación: tras integrar la 020, el diff de `docs/specs/024-dependencias-seguras` respecto a `main` anterior está vacío. → `/plan 024 --fix` |
| A07 | Ambigüedad | MEDIA | `plan.md` Rollout y D7 | La secuencia "`chore/ai-dd-init` → main, luego 024, luego 020" no tiene tarea ni criterio. Faltan la base del PR de la 024 y cómo se liberan ambas. | Decidido: se integra `chore/ai-dd-init` en `main`; la 024 se implementa, revisa y libera sola; después la 020. Comprobación: antes de `/release 020`, el merge de la 024 es ancestro del commit que se libera y las auditorías dan 0 críticas y 0 altas sobre ese árbol. → `/plan 024 --fix` |
| A08 | Seguridad | MEDIA | `plan.md` D2 y Rollout; T021 | `npm audit fix` se ejecuta en el host Windows, y npm puede perder en el lockfile las entradas opcionales de otras plataformas, con lo que el `npm ci && npm run build` en Linux fallaría. CA4 solo se comprueba en el host. | El lockfile conserva las entradas linux-x64 (gnu/musl) de `@rolldown/binding-*`, `lightningcss-*` y `@tailwindcss/oxide-*` en sus versiones nuevas, o la construcción se verifica en Linux (contenedor). Comprobación: nota de T021/T022. → `/tasks 024 --fix` |
| A09 | Seguridad | MEDIA | `plan.md` TM1 (control "registro oficial, con la integridad del lockfile") | Ninguna tarea comprueba ese control. | Cada entrada nueva o cambiada de `package-lock.json` tiene `resolved` bajo `https://registry.npmjs.org/` y un `integrity`; los paquetes cambiados de `composer.lock` vienen de Packagist o GitHub. Comprobación: nota de T022. → `/tasks 024 --fix` |
| A10 | Ambigüedad | BAJA | `plan.md` línea base de CA3; T002 | `vendor` vive en el volumen `vendor_data`, compartido entre ramas: la línea base solo vale si `vendor` corresponde al lock de la rama. | T002 registra las versiones instaladas de `guzzle` y `commonmark` antes de T020, y T020 las nuevas. → `/tasks 024 --fix` |
| A11 | Inconsistencia | BAJA | `plan.md` Cambios por módulo, D3 y Riesgos | `lightningcss` y sus paquetes por plataforma ya están en el lockfile, así que no son "transitivas nuevas"; y `@emnapi/*`, `@napi-rs/wasm-runtime` y `@tybys/wasm-util` están en el lockfile como opcionales, no "ajenos". | La lista de transitivas nuevas debe coincidir con el diff real del lockfile. → `/plan 024 --fix` |
| A12 | Cobertura de riesgos | BAJA | `spec.md` Problema y Cobertura de riesgos (aviso del validador) | RD7.a figura como "fuera", con motivo y destino, pero el Problema solo nombra RS16. | El Problema dice que RD7 no se atiende (solo condiciona dónde se ejecuta Composer). Comprobación: `aidd.py validate` sin el aviso. → `/specify --edit 024` |
| A13 | Inconsistencia | BAJA | T010 | Dice "(control TM4)", pero la tabla de cobertura la trata como test de TM4. | Unificar como test de TM4. → `/tasks 024 --fix` |
| A14 | Cobertura | BAJA | T040, T092 | Ninguna tarea incluye `aidd.py validate` sin errores, que exige la Definición de terminado. | T040 o T092 incluyen `aidd.py validate` sin errores. → `/tasks 024 --fix` |
| A15 | Mecánica de tareas | BAJA | T001 | Toca más de 3 archivos (es un `cherry-pick` mecánico) y cita el commit del análisis, que aún no existía. | Mantenerla, pero decir de forma explícita que se incluye `analysis.md`. → `/tasks 024 --fix` |
| A16 | Ambigüedad | BAJA | `plan.md` D2 | No se registra la versión corregida mínima de `vite` que se consideró (el rango afectado termina en 8.0.15) ni por qué se descarta frente a 8.3.0. | D2 registra la versión mínima considerada y el motivo del descarte. → `/plan 024 --fix` |

## Decisiones pendientes del usuario
Resueltas en esta ronda:
- **A01:** tests PHPUnit de versiones corregidas para CA1 y CA2, y P2 como desviación aceptada para CA4–CA7 (decisión del usuario, 2026-09-29).
- **A02:** la antigüedad mínima de 7 días aplica a toda versión que cambie en ambos lockfiles, directa o transitiva (decisión del usuario, 2026-09-29).
- **A06/A07:** `chore/ai-dd-init` → `main`; la 024 se implementa, revisa y libera primero; la 020 se reconstruye sobre `main` sin sus copias de la 024 y se libera después (decisión del usuario, 2026-09-29).
- **A03, A04, A05, A08 y A09, y los bajos:** se corrigen en el mismo `--fix`, con los criterios de la columna Corrección (decisión del usuario, 2026-09-29).

## Aceptados
Ninguno: todos los hallazgos se corrigen antes de `/implement`.
