---
result: fail
round: 3
mode: delta
constitution_version: 1.2.1
date: 2026-10-08
spec_sha: 7b68114c7ac2
plan_sha: eed50bd20a03
tasks_sha: 759436b8f1ef
---

# Análisis · 028 Reprogramar citas

## Resumen
No pasa, por un hallazgo alto que el usuario decide corregir (A48). Los bloques que se tocan en
vistas existentes tienen que quedar sin `style=`, pero hoy maquetan en línea, y ninguna tarea crea
las clases que los sustituyen. Los 10 hallazgos abiertos de la ronda 2 quedan resueltos (5 de
ellos con efectos menores) y no hay críticos.

Ronda delta con subagente (diff de unos 68 KB). El agente principal verificó A48 y A49 en el
código.

Conteo de nuevos: CRÍTICA 0 · ALTA 1 · MEDIA 1 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 11 / 11 |
| Principios de la constitución evaluados | 9 / 9 (delta: P2 y P9 sobre lo cambiado) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A12 | MEDIA | resuelto | T012 separa al dentista en la ruta del paciente y pasa el caso del paciente a T013; T025 excluye los dos tests que necesitan las rutas de T027; T027 cierra todo `ReprogramarCitaRolesTest`. |
| A31 | CRÍTICA | resuelto (→ A51, A52) | Plan: P9, Pantallas, "Cambios a incorporar", fila de `app.css` y "Pares de contraste". Tareas: T001, T015, T024 y T025. `.formulario-accesible .badge-green` gana a `.badge-green`; `--text` sobre `--green-bg` da 18.50:1. |
| A32 | ALTA | resuelto | La tabla "Pares de contraste" fija frente y fondo; recalculados, todos cumplen su mínimo. |
| A35 | MEDIA | resuelto | Los `id` están en el plan y en T015, T026, T028 y T029; `seccion()` busca por `id`, así que los tests de la 026 siguen encontrando sus secciones. |
| A36 | MEDIA | resuelto con efecto nuevo (→ A54, A51) | T031 amplía `capturar.mjs` y T032 captura y enlaza; las dos tienen ≤ 3 archivos. |
| A37 | MEDIA | resuelto con efecto nuevo (→ A49, A53) | Test de tokens en T015 y T024, token `--timeline-time-width` y `:focus` del campo. |
| A38 | BAJA | resuelto con efecto nuevo (→ A50) | Los nombres de P9 y del RNF WCAG coinciden con T015. |
| A39 | BAJA | resuelto con efecto nuevo (→ A51) | El P2 del plan coincide con Trazabilidad; el de `tasks.md` aún lista 4 casos. |
| A40 | BAJA | resuelto con efecto nuevo (→ A55, A51) | Coherente en el plan y en T021, T028 y T029; `test_el_paciente_ve_por_que_no_puede_cancelar_una_cita_que_ya_empezo` sigue pasando sin cambios. |
| A44 | BAJA | resuelto | Plan (fila de `citas/show` y Pantallas), T015 y T026; ningún test existente aserta el texto anterior. |

Aceptados en la ronda 2 (A33, A34, A41, A42, A43, A45, A46, A47): sus notas están bajo las tareas
indicadas.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A48 | Ambigüedad / realizabilidad (P9) | ALTA | `plan.md` → fila de `public/css/app.css`; T024, T026, T028, T029 | Los bloques tocados maquetan hoy en línea: `#acciones` con flex en columna y separación; `#historial` con relleno y el vacío a 22px; `#acciones-cita` con formulario en línea y el aviso con tamaño y color; `#acciones-{id}` con flex alineado a la derecha. `app.css` no tiene clases de maquetación para ellos, y T026, T028 y T029 no tocan `app.css` (T028 llegaría a 4 archivos). | El plan nombra las clases de maquetación de los cuatro bloques, con tokens (`--space-*`, `--text-body`), dentro de `.formulario-accesible`, y T024 las crea; el vacío del historial usa `--space-7` (decisión del usuario, 2026-10-08). `test_las_reglas_css_nuevas_solo_usan_tokens` las incluye, y los tests de «solo usa el sistema» de los bloques siguen exigiendo cero `style=`. `/plan --fix`, `/tasks --fix`. |
| A49 | Inconsistencia / P9 | MEDIA | `plan.md` → fila de `app.css`; T024; T015 | `.timeline-time` ya existe en `app.css` (48px) y la usa `dashboard/dentista`. Cambiarla, o añadir otra global, ensancharía el historial del panel del dentista, y el test de tokens encontraría los px de la regla antigua. | Las clases del historial van con ámbito `.formulario-accesible` y nombres fijados en el plan (estado anterior, estado nuevo, autor); el test de tokens filtra por ese ámbito; `dashboard/dentista` no cambia. `/plan --fix`, `/tasks --fix`. |
| A50 | Inconsistencia (nombres) | BAJA | `plan.md` → Trazabilidad CA5, TM1 y RNF WCAG; Estrategia | Faltan en el plan `test_el_paciente_ve_el_motivo_si_su_cita_no_se_puede_mover` (T013) y `test_el_estado_completada_se_ve_con_contraste_aa` (T015). Estrategia atribuye a `ReprogramarCitaRolesTest` el caso del paciente, y TM1 no cita `test_el_personal_no_puede_usar_la_ruta_del_paciente`. | Trazabilidad y Estrategia con los nombres de las tareas. `/plan --fix`. |
| A51 | Inconsistencia (redacción) | BAJA | `tasks.md` P2 y T001; `plan.md` → "Cambios a incorporar", fila `tests/Feature/` y «≈ 19:1» | Textos que quedaron atrás: P2 de las tareas con 4 casos de caracterización; T001 dice que las capturas las añade T031 (ahora T032); la fila de tests dice que cambian dos tests de `EstadosCitaVistasTest` (con A40, uno); «≈ 19:1» es 18.50:1; la viñeta «`system.md` sube a 1.3.0» quedó bajo «Pares de contraste». | Cada texto coincide con la decisión vigente. `/plan --fix`, `/tasks --fix`. |
| A52 | Ambigüedad (tests) | BAJA | T015 `test_el_estado_completada_se_ve_con_contraste_aa`; T013 `test_la_lista_muestra_el_aviso_corto_fuera_de_plazo`; T015 `…_en_el_detalle_del_personal` | El test de A31 no dice el rol (con el paciente, T025 no podría cerrarlo). «Ni botones ni aviso» choca con «Ver», que sigue en `#acciones-{id}`. «`#estado` sin cambios» no dice qué se aserta. | Aceptado como nota (ver abajo). |
| A53 | Ambigüedad (P9) | BAJA | `plan.md` → "Cambios a incorporar" frente a la fila de `app.css`; T024 | Los `input` y `textarea` no heredan la fuente, y las pantallas nuevas no cargan `citas.css`: sin declarar nada, los campos salen con la fuente del sistema. | Aceptado como nota (ver abajo). |
| A54 | Realizabilidad / P5 | BAJA | T031, T032; `capturar.mjs` | El guion recorre siempre `COMPONENTES`, y dos de ellos salen del detalle de una cita que cambia en T026: se regenerarían sus PNG y fallaría el «hecho cuando» de T032. La cabecera no debe incluir el valor de `CLAVE`. | Aceptado como nota (ver abajo). |
| A55 | Inconsistencia spec ↔ plan | BAJA | spec CA3 frente a A40 (Mensajes, D6, T021) | La cancelación de una cita ya empezada sigue rechazándose, pero con el mensaje de `yaEmpezo()`, no con el de CA2. La decisión está en el plan, no en la spec. | Decisión en el plan (D17): el «faltan menos» de CA3 se aplica a citas que aún no empiezan, y las ya empezadas conservan el mensaje de 026 CA7 (decisión del usuario, 2026-10-08, A40). Test existente sin cambios: `EstadosCitaRolesTest::test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo`. `/plan --fix`. |

## Decisiones pendientes del usuario
Ninguna. Decisiones del usuario del 2026-10-08:
- **A48:** las clases las crea T024.
- **A48:** el vacío del historial usa `--space-7`.
- Corregir A48–A51 y A55, y aceptar como notas A52–A54.

## Aceptados
Aceptados por el usuario el 2026-10-08 (los de la ronda 2 siguen vigentes):
- **A52** → nota de T013, T015: `test_el_estado_completada_se_ve_con_contraste_aa` usa solo al personal, y cierra en T025. En la lista, con la cita ya empezada, no hay «Reprogramar», «Cancelar» ni aviso; «Ver» sigue. «`#estado` sin cambios» aserta que `#estado` existe y conserva su formulario hacia `…/estado`.
- **A53** → nota de T015, T024: `.formulario-accesible .field` declara `font-family: inherit`, y el test de tokens acepta `inherit`.
- **A54** → nota de T031: con el argumento de selección, el guion no recorre `COMPONENTES`, y la ejecución en seco lo muestra. La cabecera solo nombra la variable `CLAVE`, sin su valor. gitleaks en T040.

## Correcciones
1. `/plan 028 --fix A48,A49,A50,A51,A55`
2. `/tasks 028 --fix A48,A49,A51`, que también añade las notas de A52–A54.
3. `/analyze 028`, que será delta.
