---
result: fail
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-18
spec_sha: abbe59d4810d
plan_sha: fc490ec9c132
tasks_sha: e83372f9b258
---

# Análisis · 051 Ciudad elegida de una lista cerrada

## Resumen
Los 16 hallazgos de la ronda 1 quedan atendidos, pero tres correcciones tuvieron efectos nuevos. Sin
críticos ni altos; A17 y A18 producirían código incorrecto si se implementan tal como están escritos,
así que se corrigen antes de implementar.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 2

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 6 / 6 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | `plan.md` y `tasks.md` P3: `❌ aceptado` con endpoints, destino y aprobación del usuario |
| A2 | CRÍTICA | resuelto | `plan.md` y `tasks.md` P7: `❌ aceptado` con OB1.e → 022 y OB1.f → 021 |
| A3 | ALTA | resuelto con efecto nuevo (→ A19) | D12, T015 y T022 |
| A4 | ALTA | resuelto | T027: p1 pasa a «Morelia» en `setUp` |
| A5 | MEDIA | resuelto con efecto nuevo (→ A17) | D14 y T013 |
| A6 | MEDIA | resuelto | La regla falla si el valor no es cadena; T010 y T012 con `ciudad[]` |
| A7 | MEDIA | resuelto con efecto nuevo (→ A18, A20) | D13, T012 y T023 |
| A8 | MEDIA | resuelto | D8 deja lo que debe aplicar la 014 |
| A9 | BAJA | resuelto | P8 |
| A10 | BAJA | resuelto | `index` interseca con la lista, sin `DISTINCT`; T014 |
| A11 | BAJA | resuelto | La regla compara con `trim($guardada)`; T010 |
| A12 | BAJA | resuelto | Contratos y T014 |
| A13 | BAJA | resuelto | Rollout, T091 y T097 |
| A14 | BAJA | resuelto | Contratos y T011 |
| A15 | BAJA | resuelto | Estrategia de pruebas y Trazabilidad |
| A16 | BAJA | resuelto | T024 y T025 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A17 | Inconsistencia | MEDIA | `plan.md` D14 y parcial; T013; `cliente/perfil.blade.php:117` | El perfil tiene un segundo formulario, el de la contraseña. Si falla, `hasOldInput()` sin clave es verdadero y `old('ciudad')` es `null`: el formulario de datos mostraría «Sin indicar» y, al guardarlo, borraría la ciudad | `/plan 051 --fix A17` y `/tasks 051 --fix A17`: un error en la contraseña deja elegida la ciudad guardada (test en T013) |
| A18 | Ambigüedad | MEDIA | `plan.md` D13; T012 y T023; `register.blade.php:322-329` y `:411-417` | El script fija el paso en dos sitios; ampliar solo la condición dejaría visibles los dos pasos | `/plan 051 --fix A18` y `/tasks 051 --fix A18`: con un error del paso 2 se ve solo ese paso (test en T012 y comprobación en el navegador en T023) |
| A19 | Inconsistencia | BAJA | `plan.md` D12 y filas de CSS; T015 | Con foco, `.field:focus` y `.page-register .field:focus` ganan por especificidad y el borde vuelve a `--p300` | `/plan 051 --fix A19` y `/tasks 051 --fix A19`: el borde enfocado sigue en `--border-control` (test en T015) |
| A20 | Ambigüedad | BAJA | `plan.md` D13; T012 | Un error solo de `terminos`, que está en el paso 2, deja a la persona en el paso 1 | `/plan 051 --fix A20` y `/tasks 051 --fix A20`: todo error ajeno al paso 1 abre el paso 2 (test en T012) |

## Decisiones pendientes del usuario
Ninguna: A20 aplica la decisión de A7 (un error del paso 2 abre ese paso).

## Aceptados
Ninguno: se corrigen con `/plan 051 --fix A17,A18,A19,A20` y `/tasks 051 --fix A17,A18,A19,A20`.
