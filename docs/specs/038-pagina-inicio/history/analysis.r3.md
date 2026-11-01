---
result: pass
round: 3
mode: delta
constitution_version: 1.3.0
date: 2026-10-20
spec_sha: 3672869d6dd0
plan_sha: 38cab2107430
tasks_sha: e59c904ae85d
---

# Análisis · 038 Página de inicio pública

## Resumen
`pass`. Delta sobre `eed6dc0` (ronda 2): el refinado D15 de la cabecera, el menú y el pie
(`/design-spec 038 --fix` en `e352c32`, plan en `29be970`, tareas T005, T018 y T032 en `66e443d`).
Unos 54 KB de diff, casi todo notas de tareas hechas; el cambio de diseño es pequeño y sin cambio
estructural (ni módulo, ni contrato, ni criterio nuevos), así que se revisó sin subagente,
releyendo del disco `design.md` («Cabecera pública», «Pie público», contraste, Decisiones), el
plan (Cambios por módulo, Pantallas, Navegación, Accesibilidad medible, Estrategia de pruebas,
Trazabilidad, D15) y las tareas T005, T018, T025, T026 y T032 con las tablas de Cobertura.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 1 · BAJA 1 (los dos aceptados como notas de tarea)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA1 y CA9 también con T018 y T032) |
| Amenazas con control y test | 6 / 6 (el refinado no añade entradas ni datos) |
| Principios de la constitución evaluados | 10 / 10 (P2: T018 antes que T032; P9: T005 incorpora 1.7.1 antes de T032; P10: el SVG del icono va en el HTML, 0 kB de JS propio; sin dependencias nuevas) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1–A11, A18, A20 | — | resueltos en rondas anteriores | `history/analysis.r2.md` |
| A12–A17, A19 | BAJA | aceptados | notas de tarea y roadmap (ver Aceptados) |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A21 | Constitución (P9, WCAG 2.4.7) | MEDIA | `design.md` «Cabecera pública»; plan «Accesibilidad medible» y «Estrategia de pruebas» (D15); T018, T032 | El foco «dentro de la cabecera» usa `--focus-ring-inverso` (blanco), pero el panel del menú abierto es blanco y está dentro de la cabecera: el foco de sus enlaces no se vería | Los enlaces del panel abierto (`.pub-menu li a`) usan `--focus-ring`; `--focus-ring-inverso` solo en lo que se pinta sobre `--p500` y `--p600` (barra, `summary`, botón, pie). Test: en T018, la regla de foco de `.pub-menu li a` declara `--focus-ring` y no el inverso |
| A22 | Inconsistencia | BAJA | T025, T032; `config/clinica.php` | La columna «Horarios» del pie (T032) usa el horario en pares día → horas, un cambio de `config/clinica.php` hecho durante T025, que aún no tiene commit y ahora va después de T032 | El cambio de `config/clinica.php` entra en el commit de T032 como archivo extra justificado, con su nota; `PaginaInicioTest` sigue en verde |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-20 como notas de tarea (sin otra vuelta de plan):
- **A12** → nota de T010, T012: los casos de vacío desactivan todos los servicios en la transacción; el orden se comprueba con categorías propias
- **A13** → nota de T012, T021: agrupación sin distinguir mayúsculas ni acentos; «Otros servicios» se funde con las vacías
- **A14** → nota de T012, T026: el índice de `/servicios` muestra el número de servicios por categoría
- **A15** → nota de T010, T025: «Aviso de la puerta» como `h2`; «Ver la ubicación» en la placa y «Cómo llegar» solo en la dirección
- **A16** → roadmap: el plan de la 039 modifica los casos «ningún script» y «ningún recurso externo» de `PaginasPublicasTest` en lugar de duplicarlos
- **A17** → nota de T040: corrección del sitemap y barra de la raíz en `docs/seo.md`; enmienda de P10 → roadmap
- **A19** → nota de T010, T013: canónica de producción fijada en el test, búsqueda de referencias solo en `resources/` y `public/`, motivo del fallo de T010
- **A21** → nota de T018, T032: el foco de los enlaces del panel abierto del menú es `--focus-ring`; el inverso, solo sobre el rosa
- **A22** → nota de T032: `config/clinica.php` (horario en pares día → horas) entra en el commit de T032 como archivo extra justificado
