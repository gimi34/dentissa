---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-10-13
spec_sha: f650f4362bcf
plan_sha: d60deb62ddd6
tasks_sha: 6139b62bf952
---

# Análisis · 034 Móvil para el personal y listas en tarjetas

## Resumen
`fail`. Hay 4 críticos de P9. Las reglas nuevas y los bloques tocados usan valores que no tienen
token: el peso 600, la columna de 360 px y los valores en línea de los bloques tocados (A1–A3).
Además, el filtro tocado queda sin etiquetas asociadas y con un foco por debajo de 3:1 (A4). Los
altos piden decisiones del usuario (A6–A8, A13) o correcciones localizadas de plan y tareas.

Lo hizo un subagente independiente, con contexto limpio. Comprobé en el código A1–A4, A8, A15 y
A23.

Conteo: CRÍTICA 4 · ALTA 9 · MEDIA 13 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 (CA2 sin el panel de la dentista: A8) |
| Amenazas con control y test | 0 / 0 (sin modelo de amenazas: no aplica) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución P9 | CRÍTICA | plan D1 (`::before` con peso 600), T012; `ReglasCss.php:73` | El peso 600 no tiene token (`--weight-strong` = 500); `literales()` lo marca. La 033 (D13) ya renunció a 600 | Toda regla nueva usa un peso del sistema o un token añadido en T001 (test: `literales()` de T012 con control positivo) → `/plan --fix` |
| A2 | Constitución P9 | CRÍTICA | plan D7 (`1fr 360px`), T012, T031 | El valor `360px` no tiene token. Además, el test de la 033 (`bloque033()` hasta el final de `app.css`) analizaría el bloque 034 | Toda medida del bloque 034 es un token del sistema (test: T012) → `/plan --fix`, T001 |
| A3 | Constitución P9 | CRÍTICA | plan D10b y «Cambios por módulo»; T012, T024–T031; `citas/show:54` y `cliente-show:49` (12 px), `pacientes/index:154` (12,5 px), `servicios/index:77-79`, `citas/index:50-80` (`min-width` 130–180 px) | «Mismos valores que el `style=""`» y «solo tokens» a la vez son imposibles sin tokens para esos valores; /implement tendría que decidir | Decisión del usuario (alcance de «tocado») y, según ella, inventario de valores con su token en T001 o reducción de lo tocado (test: T010–T012) |
| A4 | Constitución P9 | CRÍTICA | D6, T011, T027; `citas/index:51-89` (`<label>` sin `for`), `citas.css:26,29` (foco `--p300`, 2,23:1) | El filtro que se reordena queda con etiquetas sin asociar y un foco por debajo de 3:1 | Cada campo del filtro tiene su etiqueta asociada, y foco y borde ≥ 3:1 con tokens (test: T011, T012 y el contraste en `--medir`) → `/plan --fix`, `/tasks --fix` |
| A5 | Constitución P9 | ALTA | D10b, T012; `servicios/index:77` (`--green` 3,39:1), `citas/show:56,67,72` (`--text-light` 3,51:1) | Los bloques tocados conservan pares por debajo de 4,5:1 sin test | Todo par de texto de un bloque tocado ≥ 4,5:1 (test: contraste de esos pares en T012) → `/plan --fix` |
| A6 | Constitución P9 (2.4.3) | ALTA | plan D6, Riesgos fila 3 | En el escritorio, el orden del foco del filtro deja de coincidir con el visual; se acepta sin decisión del usuario | Decisión del usuario |
| A7 | Inconsistencia | ALTA | spec CA1, CA2 y RNF 1.3.1; plan D1 (`celda-cab` y `celda-estado` sin etiqueta); T015 | El plan deja sin etiqueta la hora o el nombre y el estado, que la spec pide con etiqueta | Decisión del usuario (`/specify --edit` o etiquetarlas) |
| A8 | Cobertura | ALTA | spec CA2; plan D2 («admin, asistente»); `dashboard/dentista.blade.php:73-104` | La agenda de hoy de la dentista (línea de tiempo, no tabla) queda sin cambio ni test para CA2, con un enlace de ojo sin nombre y con `style=""` | Decisión del usuario (incluirla en D2 o excluirla en la spec) |
| A9 | Inconsistencia CA11 | ALTA | plan D7, Riesgos fila 1 | Con `row-gap` y filas `auto auto auto 1fr`, una fila vacía (asistente y dentista sin control interno) añade un hueco: el escritorio cambia según el rol y el largo del historial | El detalle a 1024 y 1440 es igual a la línea base para los tres roles y con historial largo (test: `--medir` con ese caso) → `/plan --fix` |
| A10 | Constitución P2 / 033/R21 | ALTA | spec CA9; T014, T015, T023; `capturar.mjs:250-259` | `--medir` no cruza el punto de corte: R21 y los casos (a)–(d) de 033 T064 no tienen un test que falle antes | Los cambios de modo forman parte de `--medir`, comprobados rompiendo (T015) → `/tasks --fix` |
| A11 | Riesgos CA7 (033/R1, R20) | ALTA | plan D8; T015, T025 | Quitar `anywhere` de `.content` puede reabrir R1 en otras pantallas; solo T025 prueba palabras de 30 letras | `--medir` incluye una palabra de 30 letras en todas las pantallas incluidas y en las 7 del paciente (T015) → `/tasks --fix` |
| A12 | Inconsistencia (tareas) | ALTA | T027 (3 archivos sin `app.css`), T011 | Las clases del filtro tocado no tienen tarea ni test | Tarea propia para el filtro en `app.css` con su test en T011 → `/tasks --fix` |
| A13 | Inconsistencia | ALTA | spec RNF (1.4.11 «los bordes de las tarjetas»); plan («borde `--border` decorativo», 1,31:1) | El plan rebaja el borde a decorativo sin decisión, contra la spec | Decisión del usuario |
| A14 | Inconsistencia | MEDIA | spec, supuesto del orden del detalle («el orden actual de lectura»); plan D7; `citas/show:37-259`; `ReprogramarCitaVistasTest:65-93,254-291` | El orden actual del HTML no es ese; D7 cambia el orden de foco del escritorio sin declararlo y debe conservar `.formulario-accesible`, `#acciones` y `#historial` | Corregir el supuesto (`/specify --edit`), añadir el riesgo y nombrar los envoltorios (`/plan --fix`) |
| A15 | Inconsistencia CA11 | MEDIA | D6, T011; `citas/index:96-100` | Hoy el orden es «Esta semana» y después «Hoy»; el plan lo invierte | Conservar el orden actual (test: T011) |
| A16 | Inconsistencia | MEDIA | plan D2 (correo), T010; `pacientes/index:114-123` | El correo es un `div` dentro de la celda del paciente, no una columna | `solo-escritorio` en ese `div`, fuera de la comprobación por columna |
| A17 | Inconsistencia | MEDIA | plan D5; T029, T030; `opcion-a.html` (marco 4) | El diseño muestra «Ver» y «Editar» con texto; el plan deja los iconos de pacientes y servicios | Extender D5 a pacientes y servicios (test: T010) |
| A18 | Ambigüedad | MEDIA | plan D1; `cliente-index:51-66`, `citas/index:164`, `pacientes/index:158`; `app.css:299` | Los botones van dentro de envoltorios y formularios; falta cómo anular el relleno y el borde de `td` y el aspecto de `fila-hoy` en el móvil | Concretar selectores y el estilo de `fila-hoy` (barra `--border-width-strong` `--p400`) |
| A19 | Ambigüedad | MEDIA | plan D6; T012 | No está definida la maquetación del panel desplegado en el móvil | Especificarla según `opcion-a.html` (marco 3) |
| A20 | Ambigüedad (tareas) | MEDIA | T032 | Tarea abierta con archivos y decisiones sin fijar; candidatos ya visibles: rejillas de estadísticas en línea (`citas/index:24`, `pacientes/index:17`, `dashboard/admin:16`, `asistente:25`, `servicios/index:16`) y el enlace del panel de la dentista | Convertirlos en tareas concretas con su test |
| A21 | Ambigüedad | MEDIA | T015, T024–T033 | No se define cómo se compara con la línea base; el contenido depende de la fecha; la línea base no incluye al paciente | Método y umbral de comparación, misma fecha y pantallas del paciente en la línea base |
| A22 | Inconsistencia (dependencias) | MEDIA | T021, T022, T033, T040 | La línea base podría tomarse después de cambiar el CSS; las capturas del menú, antes de T023 | T021 y T022 dependen de T015; T033, de T023 |
| A23 | Constitución P9 (4.1.2) | MEDIA | plan D11; `layouts/app.blade.php:17` | ARIA in HTML no admite `role="dialog"` en `<aside>` | El diálogo va en un elemento que lo admite (test: contrato de `menu.js` y `--medir`) |
| A24 | Ambigüedad | MEDIA | spec CA9; T023 | Con `<main>` inerte, «expandido» nunca se anuncia; el criterio no es verificable | Reescribir CA9 según el diálogo (`/specify --edit`) |
| A25 | Cobertura de riesgos | MEDIA | Riesgos fila 2; T097 | La semántica de las tarjetas en Safari y VoiceOver solo se comprueba en producción | Árbol de accesibilidad con WebKit (Playwright) en T024 |
| A26 | Riesgos (033/R14) | MEDIA | T020; `MovilPacienteTest.php:466-496,590-597` | Los tests de la 033 analizarán el bloque 034 sin declararlo | Acotar `bloque033()` en T014 |
| A27 | Riesgos (DS8) | BAJA | spec «Fuera de alcance» | Ninguna tarea anota en el roadmap el destino del resto de DS8 | Anotarlo en T090 o en el cierre |
| A28 | Duplicación | BAJA | T012 y T014 | Dos tests comprueban las reglas fuera del móvil | Uno solo |
| A29 | Conflicto | BAJA | plan Rollout | La 020 también toca `layouts/app.blade.php` y `architecture.md` | Anotar el orden de fusión |
| A30 | Inconsistencia | BAJA | plan (P9 cita «D10» por D10b; faltan D2 y D5 en Decisiones; D5 dice «sin nombre accesible» y hay `title`) | Referencias e IDs incoherentes | Corregir la redacción |
| A31 | Ambigüedad | BAJA | plan «Script `filtros.js`» | Cargado al final del `<body>`, el panel puede pintarse desplegado antes de plegarse | Decidirlo y documentarlo |
| A32 | Inconsistencia | BAJA | spec CA1 («fecha y hora»); `dashboard/cliente:114-116` (solo fecha) | El panel del paciente no muestra la hora | Aclarar CA1 para el panel |

## Decisiones pendientes del usuario
- **A3:** alcance de «bloque tocado»: inventario de valores con tokens nuevos, o convertir solo los `style=""` que impiden la tarjeta.
- **A6:** orden de foco del filtro en el escritorio.
- **A7:** hora/nombre y estado de la cabecera de la tarjeta, sin etiqueta.
- **A8:** panel de la dentista dentro o fuera de CA2.
- **A13:** borde de las tarjetas, decorativo o con 3:1.

## Aceptados
Ninguno todavía.
