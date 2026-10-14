---
result: pass
round: 4
mode: delta
constitution_version: 1.2.1
date: 2026-10-13
spec_sha: 150377a121b4
plan_sha: 80d9af91392f
tasks_sha: 40ed921147a7
---

# Análisis · 034 Móvil para el personal y listas en tarjetas

## Resumen
`pass`. Los 11 hallazgos de la ronda 3 quedan resueltos. Ninguno de los 7 nuevos es crítico ni
alto: 4 medios y 3 bajos, que no cambian el diseño. El usuario decidió hoy A68, A69 y A72 y aceptó
los 7 como notas de tarea para `/implement`.

Modo delta (el plan cambió un 21 % y las tareas un 14 % desde la ronda 3), con subagente porque el
diff ocupa unos 77 KB. Sin cambio estructural: solo se añade la tarea de documentación T041.
Comprobé en el código A66, A67 y A69.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 4 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 0 / 0 (sin entradas, datos ni dependencias nuevas) |
| Principios de la constitución evaluados | 9 / 9 (P9 en las líneas cambiadas; el resto, sin cambios desde la ronda 3) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A55 | ALTA | resuelto con efecto nuevo (→ A69, A70) | escritorio: `order: 1` en `.filtro-atajos` con `display: contents` reproduce el orden de hoy (D6, T034, T012). Móvil: columna con buscador, atajos y panel (D6, T012, T015, T027) |
| A56 | ALTA | resuelto | D8 con los 4 elementos, cada uno en su tarea (T025, T029, T035, T036); T010, T012 y T014; T036 → T029 sin ciclos |
| A57 | ALTA | resuelto con efecto nuevo (→ A67) | `.fila-hoy` con `--p50` en el escritorio (sin cambio visible, también con `:hover`) y `--white` en el móvil; T010, T012 y T015 |
| A58 | ALTA | resuelto | borde `--border-control` en «Filtros» en los dos estados (D6, T001, T012, T034) |
| A59 | ALTA | resuelto | fila `contradicción` con fuente `usuario` en la spec; D10b y P9 del plan y de tasks.md |
| A60 | MEDIA | resuelto con efecto nuevo (→ A68, A72) | RNF 2.4.7 acotado; foco con tokens en tarjetas y `btn-icono`; T012 y T015 |
| A61 | MEDIA | resuelto con efecto nuevo (→ A66) | D13 y T015 con el tag v0.7.0; el código fuera de `docs/` es igual al de la rama |
| A62 | BAJA | resuelto | P3 del plan coincide con la spec |
| A63 | BAJA | resuelto | T041 en «Integración y documentación» |
| A64 | BAJA | resuelto | T014 incluye `test_los_pares_del_menu_cumplen_aa` |
| A65 | BAJA | resuelto | D1 y T012 fijan relleno y borde con tokens |

A5 y A27 siguen aceptados (ronda 1).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A66 | Inconsistencia / Riesgos (CA11, D13) | MEDIA | plan D13; T015; `docker-compose.yml`; `capturar.mjs` paso 5 | El procedimiento de D13 no basta para levantar la línea base: no da los nombres reales de la imagen, el volumen y la red de Compose; no da el comando (la imagen arranca `php-fpm`, sin HTTP; el paso 5 usa `php artisan serve`); faltan `SESSION_SECURE_COOKIE=false` y `SESSION_DOMAIN=null`, y un `--name` propio para la `BASE` de Playwright | Nota de T015 (aceptado) |
| A67 | Inconsistencia (dependencias) | MEDIA | T026; T034 | T026 quita el fondo en línea de la fila de hoy, pero el `--p50` del escritorio llega con `.fila-hoy` en T034, y T026 no depende de T034 | Nota de T026 (aceptado) |
| A68 | Constitución P9 / RNF 2.4.7 | MEDIA | plan P9, D6; T012, T015; `citas/index:92-100` | «Esta semana», «Hoy», «Filtrar» y «Limpiar» se mueven o cambian de clase y se quedan con el foco del navegador; la decisión A60 incluye «el filtro de citas» | Decisión del usuario: foco con tokens en todos los controles del filtro. Notas de T012, T034 y T015 (aceptado) |
| A69 | Inconsistencia (opción A) | MEDIA | plan D6 «Móvil»; T027; `opcion-a.html:64-65` | En la opción A los tres botones de la fila de atajos se reparten el ancho (`flex: 1`, separación `--space-3`); las reglas del móvil no lo fijan | Decisión del usuario: como la opción A. Notas de T012 y T027 (aceptado) |
| A70 | Cobertura (CA11) | BAJA | T027 «hecho cuando» | T027 no exige la comparación D13 a 1024 y 1440 | Nota de T027 (aceptado) |
| A71 | Inconsistencia | BAJA | T010; Cobertura (CA7) | La tabla pone T010 como test de CA7, pero su `cubre:` no lo incluye | Nota de T010 (aceptado) |
| A72 | Ambigüedad (CA11, RNF 2.4.7) | BAJA | spec CA11 y Decisiones; RNF 2.4.7; plan D1, D5b | Las reglas de foco nuevas también se ven en el escritorio, y CA11 dice que el único cambio es el borde. El RNF no nombra el enlace de la dentista ni los enlaces de las tarjetas, que el plan cubre | Decisión del usuario: se admite el foco con tokens en lo tocado; CA11 compara en reposo. Notas de T033 y T040 (aceptado) |

## Decisiones pendientes del usuario
Respondidas por el usuario el 2026-10-13:
- **A68:** el foco visible con tokens abarca todos los controles del filtro de citas, también
  «Esta semana», «Hoy», «Filtrar» y «Limpiar».
- **A69:** en el móvil, «Esta semana», «Hoy» y «Filtros» se reparten el ancho de la fila, como en
  los marcos 2 y 3 de la opción A.
- **A72:** en el escritorio se admite el foco con tokens en lo que la 034 toca; CA11 se comprueba en
  reposo.

## Aceptados
Aceptados por el usuario el 2026-10-13:
- **A66** → nota de T015: la cabecera de `capturar.mjs` levanta la línea base sin pasos implícitos: imagen, volumen y red con sus nombres reales de Compose (comprobados con `docker image ls`, `docker volume ls` y `docker network ls`), el comando `php artisan serve --no-reload --host=0.0.0.0 --port=<otro>`, `SESSION_SECURE_COOKIE=false` y `SESSION_DOMAIN=null`, y un `--name` propio que sirve de `BASE` a Playwright; la línea base muestra el menú de la 033 con sesión iniciada en los cuatro roles.
- **A67** → nota de T026: T026 depende también de T034, para que la fila de hoy conserve su fondo `--p50` en el escritorio; a 1024 y 1440 la lista de citas es igual a la línea base (D13).
- **A68** → nota de T012, T034, T015: todos los controles enfocables del filtro de citas («Esta semana», «Hoy», «Filtros», «Filtrar», «Limpiar» y los campos) muestran el foco con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`; T012 comprueba la regla y `--medir` el foco visible en «Hoy» y «Filtrar».
- **A69** → nota de T012, T027: en el móvil, los tres botones de la fila de atajos se reparten el ancho (cada uno `flex: 1`, separación `--space-3`); T012 comprueba la regla y `--medir`, que a 320 y 390 los tres van en una línea, miden al menos 44 × 44 y no salen de la tarjeta.
- **A70** → nota de T027: el «hecho cuando» incluye la lista de citas igual a la línea base a 1024 y 1440 según D13, sin los campos del filtro.
- **A71** → nota de T010: T010 cubre también CA7 (`texto-partible` en pacientes y en «Próximo paciente»).
- **A72** → nota de T033, T040: CA11 se comprueba en reposo (D13); el foco con tokens de lo que la 034 toca es una diferencia admitida en el escritorio (decisión del usuario, 2026-10-13), y el foco visible cubre también el enlace de la dentista y los enlaces de las tarjetas. `/release` lo menciona en el CHANGELOG.
