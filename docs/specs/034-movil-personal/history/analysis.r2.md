---
result: fail
round: 2
mode: full
constitution_version: 1.2.1
date: 2026-10-13
spec_sha: 72212d072500
plan_sha: e57780831bbd
tasks_sha: 967bd7f53231
---

# Análisis · 034 Móvil para el personal y listas en tarjetas

## Resumen
`fail`. Los 4 críticos de la ronda 1 quedan resueltos, pero hay 5 altos nuevos. Cuatro de ellos
producirían código que no cumple CA7 o CA8 (A33–A35, A37) o una tarea que no se puede terminar
(T035). El quinto es una decisión que el plan atribuye al usuario sin que conste (A36). El usuario
respondió hoy las decisiones de A33, A35 y A36.

Modo completo: el plan cambió más del 40 % desde la ronda 1. Lo hizo un subagente independiente, con
contexto limpio. Comprobé en el código A33, A34, A35, A36 y A37. A37 se sostiene en parte: D1 sí
coloca el estado arriba a la derecha.

Conteo: CRÍTICA 0 · ALTA 5 · MEDIA 9 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 (T035 no se puede terminar tal como está: A35) |
| Amenazas con control y test | 0 / 0 (sin modelo de amenazas: no hay entradas, datos ni permisos nuevos) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | plan D1: `::before` con `--weight-strong` y sin `letter-spacing`. T012 con `literales()` y control positivo |
| A2 | CRÍTICA | resuelto | token `--detalle-lateral-w` en el plan, T001 y T020. T012 comprueba `1fr var(--detalle-lateral-w)`. T014 corta `bloque033()` en `/* Fin spec 033 */` |
| A3 | CRÍTICA | resuelto con efecto nuevo (→ A33, A35) | decisión reflejada en el Enfoque, D10b y tasks.md. D10b deja fuera `style=""` que sí impiden el móvil |
| A4 | CRÍTICA | resuelto con efecto nuevo (→ A36) | `for`/`id` y foco con tokens en D6, T011, T012 y T034. El borde del escritorio sigue en 1,31:1 con una decisión sin fuente |
| A6 | ALTA | resuelto con efecto nuevo (→ A52) | decisión en D6, D6b y fila 4 de Riesgos. Falta la nota en `system.md` |
| A7 | ALTA | resuelto | spec CA1, CA2 y RNF 1.3.1; plan D1 y D2; T010 |
| A8 | ALTA | resuelto con efecto nuevo (→ A35) | spec CA2, plan D2 y D5, T035. T035 no puede cumplir su «hecho cuando» |
| A9 | ALTA | resuelto | D7: filas `auto auto auto 1fr` y `align-items: start`. T015 y T031 comparan los tres roles y un historial largo |
| A10 | ALTA | resuelto | Estrategia «cambios de modo»; T015; T023 comprobado rompiendo |
| A11 | ALTA | resuelto con efecto nuevo (→ A34, A38) | T015 prueba palabras de 30 letras en todas las pantallas. D8 no cubre todos los elementos y «cabe» no tiene referencia |
| A12 | ALTA | resuelto | T034 con su test en T012 |
| A13 | ALTA | resuelto con efecto nuevo (→ A39) | spec RNF 1.4.11, D1 y T001. Dentro de `.card.card-0` (blanca) no se cumple «la separan su fondo y su espacio» |
| A14 | MEDIA | resuelto | supuesto corregido en la spec; D7 nombra los envoltorios; T011; `ReprogramarCitaVistasTest` en T031 |
| A15 | MEDIA | resuelto | D6 mantiene «Esta semana · Hoy»; T011 |
| A16 | MEDIA | resuelto | D2 y T010: el correo con `solo-escritorio` |
| A17 | MEDIA | resuelto | D5 incluye pacientes y servicios; T029, T030 y T010 |
| A18 | MEDIA | resuelto con efecto nuevo (→ A37) | D1 define relleno, borde, acciones y `fila-hoy`. No define dónde caen los elementos de una celda con varios hijos |
| A19 | MEDIA | resuelto con efecto nuevo (→ A33) | panel en dos columnas; a 320 choca con los `min-width` en línea |
| A20 | MEDIA | resuelto con efecto nuevo (→ A35) | T032 queda como comprobación; `dashboard/dentista:28` no pasó a D10b |
| A21 | MEDIA | resuelto con efecto nuevo (→ A40) | D13 fija método y umbral. Falta rehacer la línea base el mismo día, la dependencia de la hora y el paciente a 1024 |
| A22 | MEDIA | resuelto | T021 y T022 dependen de T015; T033 de T023 |
| A23 | MEDIA | resuelto | D11, sin `role="dialog"` |
| A24 | MEDIA | resuelto | CA9 reescrito; T015; NVDA en T023 |
| A25 | MEDIA | resuelto con efecto nuevo (→ A41) | T024 con WebKit; puede no detectar el riesgo de Safari |
| A26 | MEDIA | resuelto con efecto nuevo (→ A47) | T014 y T020. Corrige media 033/R14 sin declararlo |
| A28 | BAJA | resuelto | la Estrategia marca la lista de reglas fuera del móvil como única comprobación (T014) |
| A29 | BAJA | resuelto con efecto nuevo (→ A42) | Rollout con la 020; solapes nuevos con la 020 y la 014 |
| A30 | BAJA | resuelto | P9 cita D10b; D2 y D5 en Decisiones |
| A31 | BAJA | resuelto | `filtros.js` en `<head>` |
| A32 | BAJA | resuelto | CA1 «y su hora en Mis citas»; D2 |

A5 y A27 siguen aceptados (ronda 1).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A33 | Inconsistencia / CA8 | ALTA | plan D6 («Los `min-width` en línea… se quedan»), D10b; T027, T011; `citas/index:50-80` | A 320, cada columna del panel en dos columnas mide unos 118 px. «Desde» y «Hasta» llevan `min-width:130px` en línea, que ninguna hoja anula: se montan o salen de la tarjeta. El buscador conserva `flex:1;min-width:180px`. Según el criterio de D10b, esos `style=""` sí impiden el móvil | Decisión del usuario: pasar a clases. El filtro desplegado no se solapa ni sale de su tarjeta a 320, 390 y 844×390, en dos columnas (marco 3). Test: `--medir` con el panel abierto (T015, T027) y T011 comprueba que esos `style=""` ya no están. `/plan --fix`, `/tasks --fix` |
| A34 | Riesgos (CA7, 033/R1) | ALTA | plan D8; T021, T025; `citas/show:41-54`, `dashboard/cliente`, `asistente`, `dentista`; `movil.css:21-23,66` | D8 deja `anywhere` solo en `.texto-partible` y `.user-name`. Hay más filas y rejillas flexibles con datos (cabecera y rejilla del detalle del personal, título del banner, línea de tiempo) donde `break-word` no reduce el ancho mínimo: la página se ensancha o el texto se corta. T021, que quita `anywhere`, no exige pasar `--medir` | Con una palabra de 30 letras en paciente, dentista, servicio y nombre del banner, ninguna pantalla incluida ni las 7 del paciente se desplaza de lado ni corta texto a 320. Test: `--medir` de T015 como criterio de T021; D8 lista cada elemento con su comprobación en T012/T014. `/plan --fix`, `/tasks --fix` |
| A35 | Cobertura (CA2, CA7, CA8; decisión A8) | ALTA | spec CA2; plan D2, D5, D10b; T035; `dashboard/dentista:28,100`; `movil.css:124-127` | (1) El enlace de la agenda mide unos 31 px de ancho (`style="padding:4px 8px"`) y T035 pide 44×44 «sin tocar su `style`»: imposible. (2) La rejilla lleva `repeat(3,1fr)` en línea, que gana a la regla móvil de `.stats-grid`. (3) A 320 la línea de tiempo deja unos 29 px para el nombre | Decisión del usuario: rejilla y enlace a clases; línea de tiempo adaptada en el móvil (estado y enlace bajo el nombre, sigue sin ser tarjeta). El panel de la dentista cumple CA7 y CA8 a 320, 390 y 844×390 (enlace ≥ 44×44, sin desplazamiento ni solapes). Test: `--medir` en T035 y T010 sin esos `style=""`. `/specify --edit` (CA2 matiza A8), `/plan --fix`, `/tasks --fix` |
| A36 | Constitución P9 | ALTA | plan D6 («decisión del usuario: escritorio igual»), Riesgos fila 5; spec Decisiones; T001, T012 | El filtro es un bloque modificado y en el escritorio sus campos conservan `--border` (1,31:1). El plan lo atribuye al usuario, pero no consta en las Decisiones ni en las respuestas de la ronda 1 | Decisión del usuario: borde ≥ 3:1 también en el escritorio. Registrarla en las Decisiones de la spec. Test: T012 comprueba el contraste del borde en los dos modos y D13 admite ese cambio frente a la línea base. `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A37 | Ambigüedad | ALTA | plan D1; T012; `citas/index:128-145`, `dashboard/admin:69-77`, `asistente:70-78`, `pacientes/index:114-123` | Con el `td` en rejilla «etiqueta · valor», el segundo hijo de una celda (teléfono bajo el nombre, fecha bajo la hora, avatar y nombre) cae en la columna de 88 px de la etiqueta. Tampoco dice si las acciones llevan etiqueta: el diseño aprobado (opción A, D4) no la muestra | Cada valor queda entero a la derecha de su etiqueta, aunque tenga varios elementos; las acciones van sin etiqueta, como en la opción A. Test: T012 con esas reglas y `--medir` con un valor de dos líneas. `/plan --fix`, `/tasks --fix` |
| A38 | Ambigüedad | MEDIA | spec CA7; plan D8 y Estrategia; T015 | No se define respecto a qué ancho «cabe» una palabra; con la columna real, el defecto de origen pasaría el test | Ancho de referencia fijado (la tarjeta o `.content`). Test: T015, comprobado rompiendo con una columna estrecha. `/plan --fix`, `/tasks --fix` |
| A39 | Inconsistencia (A13) | MEDIA | spec RNF 1.4.11; plan D1; `app.css:237-243` | Las tablas van dentro de `.card.card-0`, blanca: tarjeta blanca sobre blanco con un borde decorativo de 1,31:1. En la opción A van sobre `--bg` | En el móvil, el fondo de la tarjeta se distingue del de su contenedor. Test: T012 con la regla del contenedor y `--medir` comparando los fondos calculados. `/plan --fix`, `/tasks --fix` |
| A40 | Ambigüedad (CA11) | MEDIA | plan D13; T015, T024–T033 | La línea base se crea una vez (T015), pero D13 la pide del mismo día; lo visible depende de la hora (citas de hoy, filtro por defecto, plazo de 24 h, fecha del panel). El paciente solo tiene línea base a 1440 | Cada comparación usa una línea base del mismo día, con el código anterior a la 034 y los mismos datos, y el paciente también a 1024. Test: paso documentado en T015 y comprobado en T033. `/plan --fix`, `/tasks --fix` |
| A41 | Riesgos | MEDIA | plan Riesgos fila 3, Estrategia «WebKit»; T024 | No se dice con qué API se lee el árbol de accesibilidad; con `ariaSnapshot()`/`getByRole` Playwright calcula roles igual en todos los navegadores y no muestra el riesgo de Safari | Declarar qué detecta la comprobación y qué queda para T097 (VoiceOver real). Test: T024 con un control positivo que falle al quitar `data-label`. `/plan --fix`, `/tasks --fix` |
| A42 | Duplicación y conflicto | MEDIA | plan Rollout; rama `feature/020-auditoria-base` (`citas.css`, foco de `.field`); plan 014 (`pacientes/index`) | La 020 añade un foco global de `.field` con literales que se solapa con D6. La 014 cambia el filtro y el estado vacío de `pacientes/index`, que la 034 pasa a tarjetas | El Rollout fija el orden y cómo se resuelve cada solape (una sola regla de foco para el filtro; la 014 sobre el marcado de tarjetas). Test: T011 y T012 en verde tras fusionar la otra rama. `/plan --fix` |
| A43 | Seguridad | MEDIA | spec «Seguridad y privacidad»; plan P3; `PacientesController::index`; `docs/security.md` RS1 | La spec dice que el asistente solo ve los pacientes de sus dentistas; hoy no se filtra por alcance (RS1, pendiente de la 014). La 034 no lo empeora, pero parte de una premisa falsa | La spec cita RS1 y la 014 y dice que la 034 no cambia el alcance. Sin test nuevo. `/specify --edit` |
| A44 | Inconsistencia | MEDIA | plan Estrategia («toque ≥ 44×44 a 390») frente a Trazabilidad CA8; T015 | El ancho al que se mide el toque cambia entre secciones | `--medir` comprueba el toque a 320, 390 y 844×390. Test: T015, comprobado rompiendo. `/plan --fix`, `/tasks --fix` |
| A45 | Ambigüedad / P9 | MEDIA | plan D6 (`btn filtro-btn`), componente «Filtro plegable»; T001, T012; `app.css:206-219` | `.btn` no fija fondo ni borde: «Filtros» saldría con el fondo del navegador, sin estado desplegado visible ni foco definido | «Filtros» usa una variante del sistema, con estado desplegado y foco con tokens. Test: sus declaraciones y contraste en T012 y foco visible con `--medir`. `/plan --fix`, `/tasks --fix` |
| A46 | Inconsistencia | MEDIA | plan «Reglas fuera del móvil»; T014; `MovilPacienteTest.php:212-213` | Las reglas globales nuevas (`.solo-movil`, `.filtro-panel`) solo funcionan antes de la `@media` (033 A40); el test de orden solo mira `.sidebar-marca` | Toda regla global de `movil.css` va antes de la media query. Test: T014 amplía la comprobación de orden a los selectores nuevos. `/plan --fix`, `/tasks --fix` |
| A47 | Riesgos (033/R14) | BAJA | spec «Fuera de alcance»; plan «Cambios por módulo»; T014, T020 | La 034 corrige la mitad de 033/R14 (cierre del bloque) aunque la spec lo deja fuera | Declarar R14 parcial: qué entra, con su test en T014, y qué sigue en el roadmap. `/specify --edit`, `/plan --fix` |
| A48 | Inconsistencia | BAJA | spec CA2 («hora y fecha»); plan D2 | El panel de admin y asistente solo muestra la hora en las citas de hoy | Aclarar CA2 para el panel. `/specify --edit` |
| A49 | Ambigüedad | BAJA | plan D6 «Contador»; T011 | Sin singular: «Filtros, 1 aplicados» | El nombre es correcto con 1 y con n. Test: T011 con un solo filtro. `/plan --fix`, `/tasks --fix` |
| A50 | Inconsistencia | BAJA | plan D1, D5, D6; `servicios/index:62-80`; verificación manual de T022, T024, T026–T030 | No se listan las diferencias deliberadas con `opcion-a.html` (orden de atajos, «Ver perfil», peso de la etiqueta, textos centrados en servicios) | Listar en el plan las diferencias aceptadas con el diseño. `/plan --fix` |
| A51 | Inconsistencia | BAJA | tasks.md, Constitution Check P1 | Dice «Spec y plan aprobados el 2026-10-13», pero están en `draft` | Actualizarlo al volver a aprobarlos. `/tasks --fix` |
| A52 | Inconsistencia | BAJA | plan Riesgos fila 4; T001 | La nota del orden de tabulación (decisión A6) en `system.md` no está en T001 | Añadirla a T001. `/tasks --fix` |
| A53 | Inconsistencia | BAJA | spec Decisiones (implícita «Punto de corte») | Los 900 px están en `033/plan.md` D6, no en la spec 033 | Citar la fuente correcta. `/specify --edit` |
| A54 | Cobertura | BAJA | plan D7; T012, T031 | Ningún test comprueba que `.detalle-datos-rejilla` conserve `repeat(3, 1fr)` en el escritorio y pase a una columna en el móvil | T012 comprueba las dos reglas. `/tasks --fix` |

## Decisiones pendientes del usuario
Respondidas por el usuario el 2026-10-13:
- **A33:** los `style=""` del filtro que impiden el móvil pasan a clases; el panel conserva las dos
  columnas de la opción A.
- **A35:** la rejilla de estadísticas y el enlace del panel de la dentista pasan a clases; la línea de
  tiempo se adapta en el móvil (estado y enlace bajo el nombre), sin pasar a tarjetas.
- **A36:** el borde de los campos del filtro pasa a 3:1 o más también en el escritorio.

A37 (acciones sin etiqueta) no necesita decisión: lo fija la opción A aprobada (D4).

## Aceptados
Ninguno en esta ronda. Siguen aceptados A5 y A27 (ronda 1).
