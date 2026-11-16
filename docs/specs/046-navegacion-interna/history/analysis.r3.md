---
result: fail
round: 3
mode: delta
constitution_version: 1.3.0
date: 2026-11-14
spec_sha: ef20ea163030
plan_sha: c047c6e66663
tasks_sha: 8b2a0bfafb78
---

# Análisis · 046 Navegación interna

## Resumen
`fail`: un hallazgo alto, efecto de la corrección de A17. En la ficha del expediente, quitar el botón
«Volver al paciente» deja más ancho al título, que con el nombre largo de las capturas se vuelve a
partir: cambian píxeles fuera de la franja excluida y puede cambiar el alto de la cabecera, así que
`--medir` no pasaría completo como pide T032. Los diez hallazgos de la ronda 2 están resueltos; hay
además tres bajos. Ronda delta sobre el diff desde `7d40ee1`, con un subagente independiente (el diff
supera los 30 kB); A27 se verificó abriendo `expedientes/show.blade.php` y `app.css`. No se ejecutó
el navegador: el alto real de la cabecera está por medir. Ronda anterior:
[history/analysis.r2.md](../history/analysis.r2.md).
Conteo (nuevos): CRÍTICA 0 · ALTA 1 · MEDIA 0 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (CA12 con reserva: A27) |
| Amenazas con control y test | 3 / 3 |
| Principios de la constitución evaluados | 10 / 10 (el delta toca P2 y P9: sin hallazgos) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A17 | ALTA | resuelto con efecto nuevo (→ A27, A29) | spec CA12 y Decisiones; plan D12, fila de `capturar.mjs`, Estrategia y Trazabilidad CA12; T032, T031. Realizable en el script (`capturar.mjs:729-741`, `:363-368`, `:1144-1161`) salvo la ficha del expediente |
| A18 | MEDIA | resuelto | plan, filas de `app.css` y de `MovilPacienteTest`; T021. Las declaraciones pasan `literales()` (`ReglasCss.php:64-81`) y quedan dentro de `bloque033()` |
| A19 | MEDIA | resuelto con efecto nuevo (→ A28) | plan, Estrategia; T010, T012, T022–T029. `#[DataProvider]` ya se usa (`AltaCitasValidacionTest.php:62-69`; PHPUnit 12.5) |
| A20 | MEDIA | resuelto | plan P2 y Trazabilidad CA6; T012. Hoy solo «Editar usuario» difiere de la tabla (`usuarios/create.blade.php:112`) |
| A21 | BAJA | resuelto (matiz → A30) | plan, Estrategia «Manual»; T031; `CitasController.php:418`; `layouts/app.blade.php:248-251` |
| A22 | BAJA | resuelto | spec CA12 |
| A23 | BAJA | resuelto | plan D8 y orden de D7–D12; acta con K1–K6; CA12 al final |
| A24 | BAJA | resuelto | plan, Trazabilidad CA9; T012 |
| A25 | BAJA | resuelto | T091; plan, Impacto en arquitectura |
| A26 | BAJA | resuelto | plan, Riesgos; T091 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A27 | Inconsistencia (plan y tareas frente al código; specs extendidas) | ALTA | plan, fila de `capturar.mjs` (zona b), D12 y Trazabilidad CA12; T032; spec CA12; `resources/views/expedientes/show.blade.php:24-42`; `public/css/app.css:687` | El botón es un elemento flexible de `.expediente-cabecera` junto al bloque de texto. Con el nombre largo del paciente de las capturas, el título ya va partido; al quitar el botón gana ancho y se parte por otro sitio: cambian píxeles fuera de la franja derecha y, probablemente a 1024 px, el alto de la cabecera, que desplaza lo de debajo. La comparación falla con una sola diferencia mayor de 2/255 o con «distinto tamaño», en `admin`, `dentista` y `asistente-ficha-expediente`. CA12 limita el desplazamiento a «Agendar cita» y «Reprogramar» del paciente | La zona excluida de la ficha del expediente cubre todo lo que se recompone al quitar el botón, y el plan dice qué se hace si el alto de la cabecera cambia a 1024 o 1440 px con los datos de captura (las tres fichas reciben entonces el trato de las dos pantallas del paciente y CA12 lo nombra). Se comprueba con «034 escritorio» contra el tag de producción: pasa en las tres fichas a los dos anchos y el control positivo falla fuera de la zona → `/specify --edit` (CA12), `/plan 046 --fix A27`, `/tasks 046 --fix A27` |
| A28 | Inconsistencia (dependencias) | BAJA | T030; T024 | T030 exige «`NavegacionInternaTest` pasa completo» y no depende de T024; el caso de «Agendar cita» falla hasta T024 | El «hecho cuando» de T030 se cumple con sus dependencias declaradas |
| A29 | Cobertura de riesgos | BAJA | plan, fila de `capturar.mjs` y Riesgos; T032, T091; `docs/roadmap.md:311-314`, `:380-381` | La 046 toca `capturar.mjs` y el roadmap ata a «la spec que toque el guion» dos pendientes: la diferencia intermitente del escritorio en las pantallas de la asistente y 035/R22. Ni el plan ni las tareas dicen si se atienden o se aplazan; el primero afecta al «pasa completo» de T032 | El plan declara para cada uno si se atiende o se aplaza y T091 lo anota; T032 dice qué se hace si aparece la diferencia intermitente |
| A30 | Ambigüedad | BAJA | plan, Estrategia «Manual»; T031; `layouts/app.blade.php:252-260` | El aviso de éxito se retira solo a los 4 s y el contenido sube; la muestra no dice que hay que mirar en ese intervalo ni que hace falta una reprogramación por ancho | La verificación manual dice cómo se observa y qué se espera después (las migas suben y siguen visibles) |

## Decisiones pendientes del usuario
- A27: corregir ahora (spec CA12, plan y T032, con una ronda delta corta) o aceptarlo como nota de
  T032 y medir en `/implement`.

## Aceptados
Ninguno todavía.
