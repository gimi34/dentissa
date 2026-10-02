---
result: pass
round: 4
mode: delta
constitution_version: 1.0.0
date: 2026-09-29
spec_sha: a62bb70ff6f0
plan_sha: c4dee4ea0d5e
tasks_sha: 7ea5691c88fd
---

# Análisis · 024 Dependencias sin vulnerabilidades críticas ni altas

## Resumen
Resultado **pass**. La ronda revisa la corrección D10: dos avisos bajos de Composer publicados
después del plan, que se registran como excepción EX1 (vence el 2026-10-31), y `composer audit`,
que pasa a ignorar las severidades baja y media en el CI, `/review` y `/release` (T020, T024, T040,
T091). Hay 4 hallazgos nuevos, CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 2, y el usuario los acepta
todos como notas.

El cambio es estructural solo en el plan y las tareas: una tarea nueva (T024), tres archivos más
en el alcance (entre ellos `.github/workflows/ai-dd.yml`, ruta protegida autorizada por T024) y
otro umbral de bloqueo para Composer. La spec y sus criterios no cambian, así que basta el análisis
delta. `aidd.py validate` da 0 errores.

Ronda anterior: [history/analysis.r3.md](history/analysis.r3.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 (CA1 y CA2 con T024 como tarea de implementación adicional) |
| Amenazas con control y test | 4 / 4 |
| Principios de la constitución evaluados | 8 / 8 (P6: el umbral nuevo no la viola, porque solo exige críticas y altas) |

## Seguimiento de rondas anteriores
Sin hallazgos abiertos: A23 y A24 se aceptaron en la ronda 3 como notas de T001 y T040. T001 ya
tiene la suya.

Comprobado sin hallazgo:
- El comando aparece exactamente en las 4 líneas que cita T024: `.github/workflows/ai-dd.yml`
  (líneas 101 y 120), `.ai/project.yaml` (línea 33) y `docs/security.md` (línea 70).
  `feature/020-auditoria-base` no toca esos archivos.
- `--ignore-severity` existe en Composer 2.10.2 y admite varios valores. Los avisos ignorados
  siguen apareciendo en la salida, con el motivo por el que se ignoran.
- La fila EX1 que pide T091 encaja en las columnas de "Excepciones aceptadas".
- `.github/` cumple `agent-security.md` §4 con T024 aprobada.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A25 | Inconsistencia | MEDIA | `tasks.md` segunda nota de T010; `plan.md` D10 | La nota de T010 registra la decisión como `composer audit --ignore-severity=low`, solo para "T020, T040 y el CI". D10 atribuye al usuario, con la misma fecha, ignorar también la severidad media y aplicarlo en `/review` y `/release`. | tasks.md y plan.md dejan constancia de la misma decisión: severidades baja y media, en CI, `/review` y `/release`. Comprobación: ninguna mención de la decisión dice solo `low` sin una aclaración posterior. → aceptado como nota de T010 |
| A26 | Cobertura de riesgos | MEDIA | `plan.md` D10; T091; `docs/security.md` Excepciones aceptadas y Riesgos conocidos | No se dice qué pasa cuando EX1 vence, quién actúa ni dónde se comprueba. El filtro es global y no depende de EX1, así que al vencer nada falla. EX1 tampoco queda enlazada con ningún riesgo: las bajas no forman parte de RS16. | La fila EX1 y la nota del roadmap indican qué se hace al vencer, quién lo hace y en qué paso se comprueba. EX1 dice que no pertenece a RS16 (severidad baja, fuera de P6). Comprobación: un grep de `EX1` en `security.md` y `roadmap.md` muestra el destino y la acción al vencer. → aceptado como nota de T091 |
| A27 | Inconsistencia | BAJA | `spec.md` Fuera de alcance, Supuestos (recuento) y Notas para /plan | La spec no menciona los avisos bajos: el recuento dice "9 altas y 7 medias", y la nota de verificación cita `composer audit` sin filtro. Es compatible con CA1, que solo exige críticas y altas. | La spec no cambia. D10 del plan registra que los bajos quedan fuera de la 024, con su motivo y su destino (EX1 y roadmap). → aceptado sin cambio (plan) |
| A28 | Duplicación y conflicto | BAJA | `plan.md` Rollout paso 5; `docs/roadmap.md` nota de la 020 en Pendientes | Ambos exigen que `composer audit` sin filtro dé "0 críticas y 0 altas". Tal cual, ese comando sale con código 1 por las bajas de EX1, y en `/release 020` puede tomarse como un fallo. | La nota de la 020 en el roadmap usa el comando filtrado de D10, o dice que se juzga por severidad y no por el código de salida. Comprobación: un grep de `composer audit` en `roadmap.md`. → aceptado como nota de T091 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-09-29:
- **A25** → nota de T010: aclarar que la decisión final (D10) ignora las severidades baja y media en el CI, `/review` y `/release`; la nota anterior queda como registro del momento.
- **A26** → nota de T091: la fila EX1 dice que no pertenece a RS16 (severidad baja, fuera de P6) y qué pasa al vencer. Al vencer, el usuario decide si actualiza `laravel/framework` y `league/flysystem` en una spec propia o si renueva EX1 con fecha y aprobación nuevas, y el primer `/plan` o `/release` posterior al 2026-10-31 lo comprueba. La nota del roadmap dice lo mismo.
- **A27** → plan: sin cambio. D10 ya registra que los bajos quedan fuera de la 024, y CA1 solo cubre críticas y altas.
- **A28** → nota de T091: la nota de la 020 en `docs/roadmap.md` pasa a usar `composer audit --ignore-severity=low --ignore-severity=medium` y `npm audit --audit-level=high` con código 0.
