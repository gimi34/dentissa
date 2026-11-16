---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-12
spec_sha: eb26303d9a52
plan_sha: d1e8ff11056e
tasks_sha: fed29182d019
---

# Análisis · 046 Navegación interna

## Resumen
`fail`: ningún hallazgo crítico, pero cuatro altos que salen de comparar el plan con el código: el
plan da por hechas cosas que el código no cumple («Cancelar» de editar usuario, la línea base de
`capturar.mjs --medir`, las reglas de `movil.css` que fija un test de la 035 y el texto de la
entrada del menú). Análisis completo con un subagente independiente; los hallazgos A1–A4, A6 y A8
se verificaron después abriendo el código citado.
Conteo: CRÍTICA 0 · ALTA 4 · MEDIA 5 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 11 (CA6 sin la corrección de «Editar usuario», A1) |
| Amenazas con control y test | 3 / 3 |
| Principios de la constitución evaluados | 10 / 10 (P2 y P9 con reservas: A1, A2, A5, A9) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Cobertura / Inconsistencia | ALTA | plan, Trazabilidad CA6; T012, T028, Cobertura CA6; spec, Decisiones (supuesto de las pantallas de edición); `resources/views/usuarios/create.blade.php:112` | En «Editar usuario», «Cancelar» lleva al listado de usuarios, y el penúltimo elemento de «Usuarios › *usuario* › Editar» es la ficha: CA6 sí necesita un cambio de código ahí y su test no es de caracterización en esa pantalla. En los otros nueve formularios coincide | En «Editar usuario», «Cancelar» lleva a la misma dirección que el penúltimo elemento de las migas; `test_cancelar_lleva_al_mismo_sitio_que_las_migas` recorre los 10 formularios, falla antes en «Editar usuario» y pasa después → `/specify --edit` (supuesto), `/plan 046 --fix A1`, `/tasks 046 --fix A1` |
| A2 | Inconsistencia (specs extendidas) | ALTA | `docs/design/capturar.mjs:362-367` (pantallas comparadas con la línea base, ≤ 0,1 % de píxeles); 033 CA8, 034 CA11, 035 CA10; T030, T031 | `--medir` compara el escritorio con una línea base que incluye detalle de cita, fichas, altas, expediente y las pantallas del paciente. La 046 cambia ahí el color y el subrayado de las migas, quita «Volver al paciente» y las migas de «Agendar cita» y marca otra entrada del menú: `--medir` fallaría. La spec no declara que reemplaza esa parte de esos criterios y ninguna tarea ajusta la comparación | La spec declara qué parte de 033 CA8, 034 CA11 y 035 CA10 reemplaza; una tarea fija cómo compara `--medir` tras la 046, de modo que T030 y T031 tengan un criterio alcanzable → decisión del usuario, luego `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A3 | Cobertura / Inconsistencia | ALTA | T021, T014; `tests/Feature/MovilPacientesExpedientesTest.php:475-478` | Ese test exige en `movil.css` `.migas {flex-wrap: wrap}`, `.migas a {overflow-wrap: anywhere}` y `.migas-actual {overflow-wrap: anywhere; min-width: 0}`. El plan y T021 las retiran de `movil.css`, pero ninguna tarea actualiza ese test; el plan no dice qué pasa con `min-width: 0` | Una tarea de 3 archivos o menos lleva esas aserciones a la regla nueva, con «`MovilPacientesExpedientesTest` pasa»; el plan lo recoge en «Cambios por módulo» y dice si se conserva `min-width: 0` → `/plan --fix A3`, `/tasks --fix A3` |
| A4 | Ambigüedad / Inconsistencia | ALTA | spec, «Pantallas y caminos»; plan, Trazabilidad CA9; `layouts/app.blade.php:114`, `:176` | El menú de la dentista y el del paciente dicen «Mis Citas»; la tabla da «Citas › …» a todo el personal y «Mis citas» al paciente. El test de CA9, que compara el texto de la entrada marcada con el primer elemento de las migas, fallaría para la dentista y el paciente | La spec fija el texto del primer elemento por rol; el test de CA9 compara por destino o por el texto decidido → decisión del usuario, luego `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A5 | Constitución (P2) / orden | MEDIA | tasks, dependencias de T020, T022, T023, T026, T029, T031 | Ninguna tarea de implementación depende de T012 ni de T013, y T020 no depende de T011: con las `[P]` una implementación puede ir antes de su test | Cada tarea depende del test que hace pasar: T020 de T011 y T013; T022, T023, T026 y T029 de T012; T031 de T013 → `/tasks --fix A5` |
| A6 | Inconsistencia | MEDIA | plan D2 y fila de `citas/reprogramar`; `CitasController.php:385-395` | La vista de reprogramar del personal no recibe el prefijo del rol (`$rp` solo existe en el controlador); sí recibe el nombre del paciente y `rutaDetalle` | El plan dice de dónde sale la dirección de «Citas» en esa vista; el test comprueba que el primer enlace es el listado de citas del rol → `/plan --fix A6` |
| A7 | Ambigüedad | MEDIA | plan, fila del parcial y de `app.css`; design, separador | No queda dicho si el separador va dentro del `li` o entre los `li` (entre ellos sería HTML inválido) | El contrato fija el separador dentro del `li`; el test comprueba que todos los hijos del `ol` son `li` y que hay n−1 separadores con `aria-hidden` → `/plan --fix A7`, `/tasks --fix A7` |
| A8 | Ambigüedad | MEDIA | design, «Pantallas» («encima de avisos»); spec CA1; `layouts/app.blade.php:246-265` | El layout pinta los avisos de sesión antes del contenido de cada vista: tras un aviso, las migas quedan debajo, al revés de lo que dice el acta | La spec o el acta fijan el orden respecto de los avisos del layout y la verificación de CA1 incluye una pantalla con aviso → decisión del usuario |
| A9 | Cobertura (RNF) | MEDIA | plan, Estrategia; T031; `capturar.mjs` | `--medir` solo mide pantallas de la 033–035; la verificación manual cubre 2 de las 18 pantallas. Nadie mide los caminos de tres elementos de personal, usuarios y reprogramar | La verificación declara qué pantallas se miden a 320, 390 y 1440 px y justifica la muestra, con un camino de tres elementos y nombre largo de personal o usuarios y de reprogramar → `/tasks --fix A9` |
| A10 | Seguridad | BAJA | plan, TM3; `routes/web.php:196-200` | En el detalle de cita del paciente lo que deniega es el filtro del controlador (404), no el middleware de rol; TM3 no lo nombra | TM3 nombra ese control y su test incluye a un miembro del personal abriendo el detalle de cita del paciente: 404, sin migas ni nombres |
| A11 | Conflicto con la 045 | BAJA | plan, Riesgos | La 045 también toca `public/css/app.css` y `AGENTS.md`, que el plan no cita | Añadirlos a los riesgos; T001 sigue exigiendo la suite completa tras fusionar |
| A12 | Cobertura de riesgos (DS15) | BAJA | spec, «Fuera de alcance»; T091 | De DS15 entran los títulos; el favicon ya lo corrigió la ficha `2026-11-12-chore-favicon`; queda solo el isotipo | T091 deja DS15 así: títulos hechos (046), favicon hecho (chore), isotipo pendiente |
| A13 | Inconsistencia | BAJA | spec, «Fuera de alcance» | Se deja fuera la 033/R9, que el roadmap da por resuelta en la 034 | Quitar la mención |
| A14 | Inconsistencia | BAJA | T098 | Solo se anota el Historial de 033 y 035; también cambian pantallas de 008, 009, 010, 028 y 034 | Una línea en el Historial de cada spec extendida |
| A15 | Inconsistencia | BAJA | plan, Trazabilidad CA1; T025 | Son 15 archivos de vista, no 17; la dentista no da de alta pacientes y la asistente no los edita ni da de alta expedientes | Corregir los recuentos; la tabla de T010 solo lleva las combinaciones de rol y dirección que existen |
| A16 | Ambigüedad | BAJA | spec CA6; `citas/partials/reprogramar.blade.php:121` | No se enumeran los formularios de CA6; «Cancelar la cita definitivamente» no es el «Cancelar» de CA6 | El test enumera los formularios y excluye esa acción |

## Decisiones pendientes del usuario
- A2: cómo compara `capturar.mjs --medir` el escritorio después de la 046.
- A4: texto del primer elemento de las migas para la dentista y el paciente.
- A8: orden de las migas respecto de los avisos del layout.

## Aceptados
Ninguno todavía.
