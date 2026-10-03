---
result: pass
round: 3
mode: delta
constitution_version: 1.2.0
date: 2026-10-03
spec_sha: 2de730ee8ff9
plan_sha: 6c6fafcda76d
tasks_sha: b894b032814d
---

# Análisis · 027 Horario válido del dentista y aviso de citas afectadas

## Resumen
`pass`. Los 8 hallazgos de la ronda 2 quedan resueltos. Los 7 nuevos (4 medios y 3 bajos) no
cambian el diseño y el usuario los acepta como notas de tarea.

A29 se propuso como ALTA y se rebaja a MEDIA. Comprobado en el contenedor: leer lo enviado campo a
campo (`data_get`/`old("dias.N.campo")`) devuelve `null` cuando `dias` o `dias[N]` es un texto, sin
error. El 500 solo aparece si la vista indexa la lista entera.

Delta hecho por un subagente independiente (diff de unos 48 KB). Sin cambio estructural.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 4 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 9 / 9 |
| Amenazas con control y test | 8 / 8 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Rondas anteriores: [history/analysis.r1.md](history/analysis.r1.md),
[history/analysis.r2.md](history/analysis.r2.md).

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A21 | ALTA | resuelto con efecto nuevo (→ A32) | T003 depende de T012; orden T002 → T012 → T003 → T023, sin ciclos |
| A22 | CRÍTICA | resuelto (resto en → A29) | Plan (fila `show.blade.php`, D11), T010 y T023: solo valores de texto de `old()`, 200 en cada variante con listas; comprobado en el contenedor |
| A23 | MEDIA | resuelto con efecto nuevo (→ A30) | Campo oculto `formulario=horario`, D11; el test del bloqueo sigue la redirección; las reglas de `dias` no se ven afectadas (comprobado) |
| A24 | ALTA | resuelto con efecto nuevo (→ A31, A34) | Componente "Día del horario" (fila `app.css`, D13, T001, T003, T023) y test sin `style=`; valores coherentes con `system.html:110-115` |
| A25 | MEDIA | resuelto | `--text-small`/`--text-body` en plan y tareas; sin `--font-size-hint` |
| A26 | MEDIA | resuelto | Test de T012: cada campo de hora en una fila `horario-dia` con `field` |
| A27 | MEDIA | resuelto | TM1 → `HoraFinPosterior`, `required_with` |
| A28 | BAJA | resuelto | "Qué ve cada rol" cita `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A29 | Cobertura (CA3) | MEDIA | plan → fila `show.blade.php`, estrategia de CA3; T010, T023 | Las variantes que deben dar 200 tras la redirección son listas donde se espera un valor, pero no lo contrario: un texto donde se espera una lista (`dias=texto`, `dias[1]=texto`). Si la vista indexa `old('dias')[$n]`, da 500. | La ficha responde 200 tras el rechazo también con esas variantes, que se añaden al test. → nota de T010, T023 |
| A30 | Inconsistencia | MEDIA | T010 (test del bloqueo), T012 (test de errores, A6) | La vista solo recupera lo enviado si llega `formulario=horario`, y las descripciones de los tests no dicen que el POST lo envíe. | Los POST de esos tests envían lo mismo que el formulario real, incluido `formulario=horario`, más un caso sin él que muestra el horario guardado. → nota de T010, T012 |
| A31 | Ambigüedad | MEDIA | plan → componente "Día del horario", D13; T003, T023 | No tienen clase la etiqueta de la casilla (`gap:8px`, `min-width:110px`), el separador "a" ni el margen de la duración, y `hidden` en `toggleDia` puede leerse como clase o como atributo. | Cada parte de la fila tiene su clase con tokens. "No disponible" se oculta con el atributo HTML `hidden`, y la verificación manual de T023 lo comprueba al alternar un día. → nota de T003, T023 |
| A32 | Inconsistencia | MEDIA | T003 → "hecho cuando" | Al ejecutar T003 ya existen los tests de la 027 que fallan, así que "la suite da la línea base" no se cumple al pie de la letra, y "la parte CSS" de un test no se comprueba por separado. | Criterio de T003: sin fallos nuevos fuera de los tests de la 027, y `test_los_pares_de_contraste_cumplen_aa` más un test propio de las reglas CSS en verde. → nota de T003, T012 |
| A33 | Inconsistencia | BAJA | plan → Rollout | Rollout dice que el formulario envía los mismos campos y que las vistas no cambian de aspecto. | Recoger el campo `formulario` y el cambio de aspecto de la sección de horario al cerrar T023. → nota de T023 |
| A34 | Constitución (P9) | BAJA | plan → `.horario-dia__nombre` (peso 500) | Peso literal, aunque `system.html:111` define `--weight-strong: 500`. | `--weight-regular` y `--weight-strong` pasan a `system.md` y `app.css`, y las reglas nuevas los usan. Lo comprueba el test de tokens. → nota de T001, T003, T012 |
| A35 | Ambigüedad | BAJA | plan → "Tipografía"; T001, T003 | En `app.css` el prefijo `--text-` es de colores, y `--text-body`/`--text-small` son tamaños. | `system.md` dice qué `--text-*` son tamaños y cuáles colores, y el test de contraste solo toma tokens de color. → nota de T001, T012 |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-03:
- **A29** → nota de T010, T023: la ficha lee lo enviado campo a campo (`old("dias.N.campo")`), nunca indexando la lista; el test de CA3 añade `dias=texto` y `dias[1]=texto`, sigue la redirección y exige 200.
- **A30** → nota de T010, T012: los POST de los tests envían `formulario=horario` como el formulario real; un caso sin ese campo muestra el horario guardado.
- **A31** → nota de T003, T023: la etiqueta de la casilla, el separador "a" y la duración tienen clase del componente con tokens; "No disponible" se oculta con el atributo HTML `hidden`; la verificación manual alterna un día y comprueba que aparece y desaparece.
- **A32** → nota de T003, T012: las comprobaciones de las reglas CSS van en un test propio; T003 se cumple con ese test y `test_los_pares_de_contraste_cumplen_aa` en verde, sin fallos nuevos fuera de los tests de la 027.
- **A33** → nota de T023: al cerrar T023, Rollout recoge el campo oculto `formulario` y el cambio de aspecto de la sección de horario.
- **A34** → nota de T001, T003, T012: `--weight-regular` y `--weight-strong` en el sistema y en `app.css`; `.horario-dia__nombre` usa `var(--weight-strong)`; el test de tokens exige `var(--weight-*)` en `font-weight`.
- **A35** → nota de T001, T012: `system.md` distingue los tokens `--text-*` de tamaño de los de color; el test de contraste solo toma tokens de color.

Siguen vigentes los aceptados de la ronda 1 (usuario, 2026-10-02):
- **A4** → nota de T010, T011: cada test que hoy pasaría por afirmar una ausencia lleva una cita de control que sí debe aparecer (o exige `H:i`) para fallar por la razón esperada; si no, se declara de caracterización y se anota su rotura.
- **A9** → nota de T001, T003: un solo token de foco, ≥ 3:1, con el mismo nombre en `system.md`, `system.html` y `app.css` (sin dejar `--color-focus` y `--focus-ring` con valores distintos).
- **A12** → nota de T011, T020: `disponible = 0` cuenta como día sin horario, igual que `ReservaCitas`; sin `DB::raw`/`whereRaw`; caso en `test_la_ficha_lista_las_citas_futuras_fuera_del_horario`.
- **A14** → roadmap: al rehacer la 014 y la 020, decidir si la denegación 404 de `definir-horario` se audita, con su test.
- **A16** → nota de T023: "Citas fuera del horario" va antes de "Horarios de atención", con un encabezado de sección real.
- **A17** → nota de T001: la fila de Decisiones de `system.md` 1.1.0 justifica la escala de espaciado.
- **A18** → nota de T003, T004: la comparación con la línea base excluye los tests nuevos de la 027.
