---
result: fail
round: 3
mode: full
constitution_version: 1.2.1
date: 2026-10-13
spec_sha: 8e72b62ccbe5
plan_sha: faff6efd0cf3
tasks_sha: 94cd4733a008
---

# Análisis · 034 Móvil para el personal y listas en tarjetas

## Resumen
`fail`. Los 5 altos de la ronda 2 quedan resueltos y las decisiones del usuario (A33, A35, A36)
están bien reflejadas. Hay 5 altos nuevos: tres dejan tareas que no pueden cumplir su «hecho cuando»
(T026 por A57, T027 por A55, T036 por A56) y dos son huecos de P9 (A58, A59). El usuario respondió
hoy A59 y A60.

Modo completo: el plan cambió más del 40 % desde la ronda 2. Lo hizo un subagente independiente,
con contexto limpio. Comprobé en el código A55, A56, A57, A58, A59 y A61.

Conteo: CRÍTICA 0 · ALTA 5 · MEDIA 2 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 (T026, T027 y T036 no se pueden terminar tal como están) |
| Amenazas con control y test | 0 / 0 (sin entradas, datos ni dependencias nuevas) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A33 | ALTA | resuelto con efecto nuevo (→ A55) | D6, D6c y D10b pasan a clases el form, la tarjeta, el buscador, los campos y los atajos con los mismos valores; T011 y T015. Falta la maqueta móvil de la parte de arriba del form |
| A34 | ALTA | resuelto con efecto nuevo (→ A56) | D8 con lista cerrada; T036 con `--medir`, comprobado rompiendo. A la lista le faltan 4 elementos |
| A35 | ALTA | resuelto | CA2 y D5b; `.btn-icono` y `.timeline-acciones` reproducen los valores; a 320 el nombre pasa de unos 29 a unos 160 px; T035 |
| A36 | ALTA | resuelto | `--border-control` 3,93:1; `.filtro-citas .field` gana a `.field`; D13 excluye esos rectángulos; decisión registrada en la spec |
| A37 | ALTA | resuelto (menor relacionado → A65) | D1 coloca cabecera y estado y añade `celda-valor`; acciones sin etiqueta; T010, T012 y T015 |
| A38 | MEDIA | resuelto | D8 «Cuándo cabe»; T015 y T036 |
| A39 | MEDIA | resuelto con efecto nuevo (→ A57) | `tbody` con `--bg`; T012 y T015 |
| A40 | MEDIA | resuelto con efecto nuevo (→ A61) | D13 con `git worktree` en la misma sesión y paciente a 1024; T015 y T033 |
| A41 | MEDIA | resuelto | Estrategia «WebKit» con control positivo y alcance declarado; VoiceOver o TalkBack en T097 |
| A42 | MEDIA | resuelto con efecto nuevo (→ A63) | el Rollout resuelve el foco de la 020 y el orden con la 014 |
| A43 | MEDIA | resuelto con efecto nuevo (→ A62) | corregido en la spec; el plan P3 conserva la premisa |
| A44 | MEDIA | resuelto | Estrategia y T015 miden el toque a 320, 390 y 844 × 390 |
| A45 | MEDIA | resuelto con efecto nuevo (→ A58) | `btn btn-outline filtro-btn`, estado desplegado y foco con tokens |
| A46 | MEDIA | resuelto | T014: orden de todas las reglas globales, comprobado rompiendo |
| A47 | BAJA | resuelto | spec «Fuera de alcance», plan y nota en T014 |
| A48 | BAJA | resuelto | CA2 «solo la hora en las citas de hoy del panel» |
| A49 | BAJA | resuelto | D6 y T011 |
| A50 | BAJA | resuelto | plan «Diferencias aceptadas con `opcion-a.html`» |
| A51 | BAJA | resuelto | tasks.md, Constitution Check P1 |
| A52 | BAJA | resuelto | T001 incluye la nota de D6b |
| A53 | BAJA | resuelto | fuente `033/plan.md` D6 |
| A54 | BAJA | resuelto | T012 con `repeat(3, 1fr)` y `minmax(0, 1fr)` |

A5 y A27 siguen aceptados (ronda 1).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A55 | Inconsistencia / Ambigüedad (CA5, CA7, CA8, CA11) | ALTA | plan D6 («la fila de atajos al final, con `order`…», «Móvil»); T034, T012, T027; `citas/index:48-101`; `opcion-a.html:63-65` | (1) `order` solo aparece en D6: ninguna tarea ni test lo crea, y sin él los atajos quedan tras el buscador en el escritorio. (2) Si va en `app.css` sin anularse en el móvil, los atajos y «Filtros» quedan bajo el panel desplegado. (3) En el móvil el form sigue en fila con `flex-wrap`: a 390 el buscador comparte línea con los atajos (unos 66 px) y su etiqueta parte palabras que caben | En el escritorio, el filtro igual que la línea base. En el móvil, a 320, 390 y 844 × 390: buscador a todo el ancho, fila de atajos con «Filtros» debajo y el panel después, también desplegado. Test: T012 con las reglas de orden y ancho de los dos modos; `--medir` con el orden vertical buscador < atajos < panel y el buscador al ancho del form; D13 en T027. `/plan --fix`, `/tasks --fix` |
| A56 | Riesgos (033/R1, 033 CA6, CA7) | ALTA | plan D8 (lista «cerrada»); T025, T029, T035, T036; `cliente-show:36`, `citas/partials/reprogramar:26`, `dashboard/dentista:129-136`, `pacientes/index:120-129` | Al volver `.content` a `break-word` quedan 4 filas o rejillas con datos sin `anywhere`: la cabecera del detalle del paciente (servicio en fila flexible con el estado), `.resumen-cita` de reprogramar (`max-content 1fr`), «Próximo paciente» de la dentista (avatar y nombre) y la cabecera de la tarjeta de pacientes (avatar y nombre). T036 no puede pasar su `--medir` sin tocar vistas que no son suyas | Con la palabra de 30 letras, ninguna pantalla incluida ni de las 7 del paciente se ensancha ni saca texto de su tarjeta a 320 y 390. D8 incluye esos 4 elementos, cada uno con su tarea. Test: T014 y T011 con `texto-partible` (o su regla) en esos elementos; `--medir` de T036. `/plan --fix`, `/tasks --fix` |
| A57 | Inconsistencia (A39, RNF 1.4.11) | ALTA | plan D1 («el fondo `--p50` sigue en el `style=""` de la fila»); T026, T015; `citas/index:127`; `app.css:4,17` | `--p50` y `--bg` valen lo mismo (#FDF5F8): la tarjeta de una cita de hoy no se distingue de su contenedor. La comprobación de A39 en `--medir` falla en T026 y contradice la opción A (tarjeta blanca con barra) | En el móvil, la tarjeta de hoy se distingue de su contenedor; en el escritorio, la fila de hoy conserva su fondo. Ese `style=""` impide la tarjeta (criterio de D10b). Test: T010 sin ese `style=""`, T012 con su clase en los dos modos, `--medir` con una cita de hoy. `/plan --fix`, `/tasks --fix` |
| A58 | Constitución P9 | ALTA | plan D6 «Botón» (plegado `btn-outline`), «Componentes nuevos»; T001, T012; `app.css:228` | «Filtros» es un componente nuevo y plegado lleva el borde `--p200` (unos 1,6:1). La 033 usó `--border-control` en «Menú» para cumplir el 3:1 de P9. T012 solo comprueba el estado desplegado | Borde de «Filtros» ≥ 3:1 en los dos estados. Test: T012 calcula el contraste del borde plegado y del desplegado desde los tokens. `/plan --fix`, `/tasks --fix` |
| A59 | Constitución P9 | ALTA | plan P9, D10b; T012; `citas.css:6-14`; `citas/index:92,100` | El filtro reestilado conserva textos por debajo de 4,5:1 que no están en `style=""` (A5 no los cubre): `.field-label` con `--text-light` (3,51:1) y «Filtrar» y el atajo activo en `btn-primary` (3,93:1). El check de P9 del plan los da por buenos | Decisión del usuario: deuda DS1, como A5. Spec y plan lo declaran (Decisiones y D10b) y P9 lo cita. Sin test nuevo. `/specify --edit`, `/plan --fix` |
| A60 | Ambigüedad (RNF 2.4.7) | MEDIA | spec RNF; plan Trazabilidad RNF; `servicios/index:36`, `pacientes/index:221-222`, `dashboard/dentista:195` | La spec pide foco visible en las pantallas incluidas; tres campos de esas pantallas tienen `outline:none` y el plan no los trata | Decisión del usuario: el RNF se acota a lo que la 034 añade o cambia (filtro, «Filtros», tarjetas y sus botones, menú); esos campos quedan como deuda DS2. `/specify --edit`, `/plan --fix` |
| A61 | Ambigüedad (D13, CA11) | MEDIA | plan D13; T015 | El `main` local está en `db6f48a`, anterior a la 033; la rama sale de `ceae648`. «El commit de `main` del que sale la rama» daría una línea base sin la 033. El `worktree` no tiene `vendor/` ni `.env`, y montarlo choca con el volumen `.:/var/www` | D13 y la cabecera de `capturar.mjs` fijan el commit base por su tag (v0.7.0) y cómo se sirve con sus dependencias. Test: T015 con una línea base cuyo menú es el de la 033. `/plan --fix`, `/tasks --fix` |
| A62 | Inconsistencia | BAJA | plan Constitution Check P3 | Sigue la premisa «el asistente, solo sus dentistas» | P3 dice lo mismo que la spec. `/plan --fix` |
| A63 | Cobertura | BAJA | plan Rollout (aviso en `docs/roadmap.md` junto a la 014) | Ninguna tarea hace ese aviso | Una tarea anota el aviso. `/tasks --fix` |
| A64 | Inconsistencia | BAJA | T014; `MovilPacienteTest.php:309-312` | `test_los_pares_del_menu_cumplen_aa` exige `color: var(--p600)` en `.sidebar-marca` y T014 no lo nombra | T014 incluye ese test entre los que cambian. `/tasks --fix` |
| A65 | Ambigüedad | BAJA | plan D1; T012; `app.css:299` | No se fija el relleno ni el borde de `.celda-cab`, `.celda-estado` y `.celda-acciones`: conservan `padding: 11px 16px` y el borde inferior | D1 fija su relleno y su borde con tokens. Test: T012. `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Respondidas por el usuario el 2026-10-13:
- **A59:** los textos previos del filtro por debajo de 4,5:1 (etiquetas, «Filtrar» y el atajo
  activo) quedan como deuda DS1, como A5. El escritorio sigue con una sola diferencia (el borde).
- **A60:** el RNF de foco visible se acota a lo que la 034 añade o cambia (filtro de citas,
  «Filtros», tarjetas y sus botones, menú). Los buscadores de pacientes y servicios y las notas de
  la dentista quedan como deuda DS2.

A55, A56, A57 y A58 no necesitan decisión: los fijan la opción A, el criterio de D10b y el
precedente de la 033.

## Aceptados
Ninguno en esta ronda. Siguen aceptados A5 y A27 (ronda 1).
