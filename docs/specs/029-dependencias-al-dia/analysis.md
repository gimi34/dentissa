---
result: pass
round: 2
mode: delta
constitution_version: 1.2.1
date: 2026-10-03
spec_sha: 565e273c0508
plan_sha: b81df02db34e
tasks_sha: 690f9f273499
---

# Análisis · 029 Dependencias sin avisos de seguridad conocidos

## Resumen
`pass`. Delta sin subagente: el diff contra la ronda 1 ocupa unos 26 KB, no hay hallazgos abiertos
y no hay cambio estructural. Se releyeron desde cero CA7, la fila de `docs/security.md` y la
Estrategia, la Trazabilidad y D6 del plan, y T040, T091 y T098.

CA7 ya se comprueba al implementar (T040) con lo que deja T091. Spec, plan y tareas coinciden en
que retirar EX1 y mitigar RS17.a son pasos de la release.

Quedan dos bajos de seguimiento, aceptados como notas.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 2

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 |
| Amenazas con control y test | 4 / 4 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Ronda anterior: [history/analysis.r1.md](history/analysis.r1.md). Sus once hallazgos se aceptaron como
notas; A1 se resolvió además con `/specify --edit`, `/plan --fix A1` y `/tasks --fix A1`.

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | MEDIA | resuelto con efecto nuevo (→ A13) | spec CA7 reescrito (Decisiones, fuente `usuario (/analyze A1)`); plan, Estrategia CA7, Trazabilidad y D6; T040 comprueba CA7 y T091 lo implementa; T098 dice "pasos de la release (plan D6)" |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A12 | Ambigüedad | BAJA | spec CA7 (última frase: "Al liberar, EX1 sale… y RS17.a pasa a mitigada") | La última frase de CA7 describe el paso de la release dentro del propio criterio. Puede leerse como parte de lo que se marca en T092. | CA7 se marca en T092 por lo que comprueba T040 (estado tras la actualización). La última frase se comprueba en T098 al liberar. → nota de T092, T098 |
| A13 | Inconsistencia | BAJA | T040, nota "A1 — T040 no cubre CA7…" | Tras el `--fix`, T040 sí cubre CA7, pero su nota de la ronda 1 dice lo contrario. | T040 gana una nota que corrige la anterior y registra la comprobación de CA7 en `docs/security.md` (EX1 anotada, RS17.a "en curso (spec 029)"). → nota de T040 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-03 (ronda 1, siguen vigentes; ver [history/analysis.r1.md](history/analysis.r1.md)):
- **A1** → nota de T040, T098: T040 no cubre CA7; T098 lo cumple y lo comprueba (EX1 fuera de "Excepciones aceptadas", RS17.a mitigada con su versión, `composer audit --locked` sin filtro sin avisos sin registrar). *(Corregido por A13 tras el `--fix A1`.)*
- **A2** → nota de T098: en la release, la entrada de EX1 del roadmap deja de figurar como pendiente y la de dependencias cita la 029 como atendida.
- **A3** → nota de T091: la nota en la fila de EX1 no adelanta su retirada, que sigue en la release; difiere del plan solo en esa línea.
- **A4** → nota de T021: es la tarea de las comprobaciones de TM1 (el plan dice T022 por error).
- **A5** → nota de T021: cada paquete cambiado es dependencia, directa o transitiva, de alguna de las tres bibliotecas según el lock anterior (`composer why`/`depends`).
- **A6** → nota de T020, T040: si aparece un aviso sin versión corregida con ≥ 7 días, la tarea se bloquea → `/specify --edit 029` (CA1).
- **A7** → nota de T021: `source.reference` y `dist` de cada versión cambiada coinciden con Packagist; limitación del `time` aceptada y anotada.
- **A8** → nota de T021: ninguna versión cambiada es `composer-plugin` ni `composer-installer`, y `config.allow-plugins` no cambia.
- **A9** → nota de T021: tras T020, `git status` solo muestra `composer.lock`, o los archivos generados se revisan y se justifican.
- **A10** → nota de T040: CA2 es una comprobación declarada (`npm audit` con 0), según P2 1.2.1.
- **A11** → nota de T002, T021: ≥ 7×24 h entre el `time` de Packagist (UTC) y la ejecución de T020, con ambas marcas anotadas.

Aceptados por el usuario el 2026-10-03 (ronda 2):
- **A12** → nota de T092, T098: CA7 se marca en T092 por lo que comprueba T040; su última frase (EX1 retirada y RS17.a mitigada) se comprueba en T098.
- **A13** → nota de T040: corrige la nota de A1 de la ronda 1: T040 sí comprueba CA7 (EX1 anotada y RS17.a "en curso (spec 029)" en `docs/security.md`).
