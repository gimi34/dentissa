---
result: fail
round: 2
mode: full
constitution_version: 1.3.0
date: 2026-11-12
spec_sha: 205f05a2fa0f
plan_sha: c6621a511e64
tasks_sha: 1de7d531e7f2
---

# Análisis · 046 Navegación interna

## Resumen
`fail`: un hallazgo alto. La corrección de A2 (una «línea base nueva» de `capturar.mjs`) no encaja
con el procedimiento real del script, que toma la línea base en cada sesión del tag en producción:
T032 compararía la rama consigo misma. Ningún hallazgo de la ronda 1 sigue abierto; seis
correcciones dejaron efectos nuevos, todos menores salvo ese. Ronda completa (la spec ganó CA12),
con un subagente independiente; A17 y A18 se verificaron abriendo `capturar.mjs` y
`MovilPacienteTest`. Ronda anterior: [history/analysis.r1.md](history/analysis.r1.md).
Conteo (nuevos): CRÍTICA 0 · ALTA 1 · MEDIA 3 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (CA12 y CA6 con reservas: A17, A20) |
| Amenazas con control y test | 3 / 3 |
| Principios de la constitución evaluados | 10 / 10 (P2 y P9 con reservas: A17, A18, A20) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A19, A20) | spec CA6; plan, fila de `usuarios/create` y Trazabilidad CA6; T012, T028. La ruta `admin.usuarios.show` existe y la vista tiene el identificador |
| A2 | ALTA | resuelto con efecto nuevo (→ A17, A22) | CA12, Decisiones, plan D12, T032 |
| A3 | ALTA | resuelto con efecto nuevo (→ A18) | plan D6 y fila de `movil.css`; T014. Las reglas de `movil.css` son compatibles con la estructura nueva |
| A4 | ALTA | resuelto con efecto nuevo (→ A24) | spec, tabla; plan D10; menú: «Citas» y «Mis Citas» según el rol |
| A5 | MEDIA | resuelto | dependencias de T020–T031 |
| A6 | MEDIA | resuelto | plan, fila de `citas/reprogramar`, D2 |
| A7 | MEDIA | resuelto | contrato del parcial; T011 |
| A8 | MEDIA | resuelto con efecto nuevo (→ A21) | CA1, acta, plan D11 |
| A9 | MEDIA | resuelto | plan, Estrategia: muestra de cinco pantallas |
| A10 | BAJA | resuelto | TM3; T013 |
| A11 | BAJA | resuelto | plan, Riesgos |
| A12 | BAJA | resuelto | spec, Fuera de alcance; T091 |
| A13 | BAJA | resuelto | spec, Fuera de alcance |
| A14 | BAJA | resuelto | T098 |
| A15 | BAJA | resuelto | 15 vistas, 18 pantallas; T010 |
| A16 | BAJA | resuelto | CA6; T012 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A17 | Inconsistencia (specs extendidas) | ALTA | plan D12 y Estrategia («Navegador»); T032, T031; `docs/design/capturar.mjs` (cabecera, pasos 7b y 8; `EN_PAGINA.excluidos`, líneas 729-741; comparación, 1144-1161) | La línea base se toma en cada sesión, justo antes de `--medir`, del tag en producción, y se borra al terminar. Tomarla de la propia interfaz de la 046 compara la rama consigo misma: «pasa completo» no comprueba nada, y en `/review`, siguiendo la cabecera, `--medir` vuelve a fallar en las pantallas con migas. La salida de la comparación es un porcentaje por pantalla, sin decir dónde: no permite comprobar «solo difieren las migas, el botón y la entrada marcada». El script ya excluye zonas por cambios deliberados de otras specs (filtro de citas, auditoría, galería) | Una comprobación repetible en `/implement` y en `/review`, con el procedimiento de la cabecera, que falle si una pantalla comparada difiere de la línea base de producción en más de 0,1 % de píxeles fuera de las migas, del botón quitado y de la entrada marcada; el plan dice qué se hace con las pantallas cuyo contenido se desplaza (migas que aparecen o desaparecen) → decisión del usuario (revisar D12), luego `/plan 046 --fix A17`, `/tasks 046 --fix A17` |
| A18 | Cobertura / Inconsistencia | MEDIA | plan, filas de `app.css` y de `MovilPacienteTest`; T021; `tests/Feature/MovilPacienteTest.php:527-548` | Ese test exige en el bloque de la spec 033 de `app.css` exactamente `.migas-actual` = `color: var(--text)` y las declaraciones exactas de `.migas` y `.migas a`. El plan añade declaraciones a `.migas-actual`, pero solo prevé actualizar `.migas` y `.migas a`; tampoco dice dónde van las reglas nuevas (`ol`, `li`, separador, puntero) ni qué test vigila que solo usen tokens | El plan y T021 listan todas las aserciones de `MovilPacienteTest` que cambian, incluida `.migas-actual`, y dónde van las reglas nuevas; hecho cuando `MovilPacienteTest` pasa con las declaraciones del diseño nuevo y las reglas nuevas pasan una comprobación de solo tokens → `/plan --fix A18`, `/tasks --fix A18` |
| A19 | Inconsistencia (dependencias) | MEDIA | T028; T022, T023, T025–T027, T029; plan, Estrategia | Los tests se describen como un método que recorre una tabla: fallan mientras quede una pantalla sin hacer, así que el «hecho cuando» de cada tarea («sus filas pasan», «`test_cancelar…` pasa») no puede cumplirse con sus dependencias declaradas | Cada «hecho cuando» se cumple con las dependencias declaradas: los tests tabulares informan por pantalla y rol y cada tarea se comprueba con el filtro de su caso → `/plan --fix A19`, `/tasks --fix A19` |
| A20 | Constitución (P2) | MEDIA | plan, Trazabilidad CA6; T012; T031 | Si el test de CA6 compara «Cancelar» con el penúltimo elemento de las migas, antes de implementar falla en los diez formularios por falta de migas: no es caracterización en los otros nueve | El test de CA6 compara con el destino fijo de la tabla: antes de implementar falla solo en «Editar usuario» y pasa en los otros nueve, que se comprueban rompiendo un destino → `/plan --fix A20`, `/tasks --fix A20` |
| A21 | Ambigüedad | BAJA | plan, Estrategia («Manual»); T031; `CitasController.php:410-415` | Un rechazo por hueco no disponible se muestra junto al campo, sin aviso arriba; la muestra podría hacerse sin el aviso del layout | La muestra incluye una pantalla con el aviso del layout visible y dice cómo se provoca |
| A22 | Inconsistencia (specs extendidas) | BAJA | spec CA12; `capturar.mjs:150-151` | «Reprogramar» del paciente, una de las pantallas de 033 CA8, pasa de no tener migas a tenerlas; CA12 no lo nombra | CA12 nombra también la aparición de migas en «Reprogramar» del paciente |
| A23 | Inconsistencia | BAJA | plan D8; acta (cabecera, tabla de Resultado); spec (orden de CA12); plan (orden de D10–D12) | Recuentos y orden sin actualizar: son 12 criterios y K1–K6 | Recuentos alineados; K6 con su forma de comprobación en el acta |
| A24 | Ambigüedad | BAJA | plan, Trazabilidad CA9; T012 | El menú usa direcciones relativas y las migas, absolutas: «mismo destino» sin normalizar fallaría siempre | El test de CA9 compara por ruta, y lo dice |
| A25 | Cobertura de riesgos | BAJA | T091; `docs/roadmap.md` (033/R11) | La 046 extiende a 18 pantallas la prueba de la entrada marcada del menú, parte de 033/R11; ninguna tarea lo anota | T091 anota en 033/R11 lo que cubre la 046 |
| A26 | Conflicto con otra spec | BAJA | plan, Riesgos; `docs/specs/014-autorizacion-alcance-clinico/plan.md` | La 014 (aprobada, sin implementar) toca `pacientes/create`, `expedientes/show` y `expedientes/create`, y el roadmap le pide conservar las migas | Riesgos cita la 014; T091 actualiza la nota del roadmap |

## Decisiones pendientes del usuario
- A17: cómo se compara el escritorio con la línea base de producción después de la 046.

## Aceptados
Ninguno todavía.
