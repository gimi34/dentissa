---
spec: 049-calendario-citas
verdict: changes_requested
round: 1
date: 2026-11-17
base: eb65b90
head: da2d873
human_signoff: no requerido
---

# Review · 049 Calendario de citas

## Resumen
`changes_requested`: sin bloqueantes, pero el usuario decide corregir antes de liberar los 12 importantes
del código, la accesibilidad y los tests (R1–R12) y cuatro menores baratos (R14, R21, R26, R27). R13
(`npm audit`: 6 altas de `picomatch`, dependencias de build ajenas a la 049) se resuelve con un `/chore` antes
del `/release`. El resto de menores va al roadmap. Revisores independientes: spec (A), constitución y calidad
(C+D), seguridad (S) y accesibilidad; B (plan y alcance) hecho sobre `scope.md`. SEO no aplica («Citas» exige
sesión).
Conteo: bloqueante 0 · importante 13 · menor 22

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 1303 en verde (línea base 1257; código sin cambios desde la pasada, comprobado con `git diff b3b0156..HEAD` sobre `app resources public tests routes config database`) |
| Tests de la 049 y `MovilPersonalTest`, dos veces | ✅ 69 y 69 en verde (sin intermitentes) |
| `vendor/bin/pint --test` (13 PHP de la spec) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/049-calendario-citas` | ✅ 0 errores |
| `design/medir.mjs` sobre la build renderizada (T026) | ✅ 12/12 puertas y todos los KPIs en las cuatro pantallas |
| `docs/design/capturar.mjs --medir` (T028) | ✅ en orden, con control positivo |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `CalendarioCitasTest::test_la_semana_muestra_cada_cita_en_su_dia_y_hora`, Unit (periodo y filas) | ✅ | R35 (menor): el HTML de «Cerrado desde las 14:00» solo se prueba en el unitario |
| CA2 | `test_la_navegacion_lleva_al_periodo_y_la_vista`, `CalendarioCitasParametrosTest` | ⚠ | R4: «Semana» desde un mes que empieza en domingo; R6: test del día fuera del periodo vacuo |
| CA3 | `test_el_mes_cuenta_las_citas_y_filtra_por_dia` | ✅ | |
| CA4 | `test_la_lista_agrupa_el_periodo_y_el_dia_elegido`, `test_los_filtros_se_aplican_al_calendario_y_a_la_lista`, `test_el_filtro_conserva_el_periodo_y_limpiar_tambien` | ⚠ | R2: día elegido sin citas |
| CA5 | `test_cada_cita_abre_su_detalle_con_un_enlace` · T026 (pulsar con sesión) | ✅ | |
| CA6 | `test_las_citas_que_coinciden_van_en_carriles`, Unit (carriles) | ⚠ | R1: más de 4 a la vez se tapan |
| CA7 | `test_los_huecos_llevan_a_nueva_cita`, `test_el_buscador_y_el_estado_no_ofrecen_huecos_sobre_citas_vigentes_ocultas`, Unit (huecos) | ⚠ | R3: ocupación fuera de la cuadrícula |
| CA8 | `test_la_tira_tiene_los_dias_con_sus_cuentas`, `test_el_dia_del_movil_…`, `CalendarioCitasCssTest`, medir.mjs | ⚠ | R12: «hoy» en la tira solo por color |
| CA9 | `test_un_periodo_sin_citas_lo_dice` | ✅ | |
| CA10 | `CalendarioCitasAlcanceTest`, `test_el_alta_ignora_parametros_no_validos`, `test_nueva_cita_responde_con_parametros_no_escalares` | ⚠ | R5, R7: los tests no prueban del todo sus controles |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 14 archivos de código, todos citados por una tarea).
- Partes del plan sin implementar: ninguna. Desvíos anotados en `tasks.md`: T015 (el filtro de dentista ajeno da 0 citas, según el plan), `citas.css` retirada de «Citas» (T024).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | Spec, acta, plan y tareas aprobados el 2026-11-17; `impl_base` eb65b90 |
| P2 Test que falla antes | ⚠ | Cada tarea con su test; R5 y R6: dos tests que pasarían con el control roto |
| P3 Autorización en servidor | ⚠ | `aplicarAlcanceRol` en las dos consultas (`CitasController.php:118`, `:160`); R7: la segunda sin test que falle al quitarlo |
| P4 Entrada validada, bindings | ✅ | `textoDe`, `fechaDe`, listas de permitidos, `whereRaw` con `?`; R27 (menor): año sin acotar |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas |
| P6 Sin vulnerabilidades altas | ⚠ | `composer audit` limpio; `npm audit` 6 altas ajenas a la 049 (R13) |
| P7 Auditoría | ➖ | Sin acciones nuevas |
| P8 Correlación y logs | ➖ | Sin logs nuevos |
| P9 Solo el sistema de diseño | ⚠ | Sin `style=""`, solo tokens (`ReglasCss::literales()`); R8: la lista de contrastes no es exhaustiva |
| P10 Rutas indexables | ➖ | «Citas» exige sesión |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (diff de la spec y documentos) | ✅ sin fugas |
| semgrep `p/php` (archivos del diff) | ✅ sin hallazgos |
| `composer audit --locked` (altas y críticas) | ✅ sin avisos (1 baja de `laravel/framework`, ignorada por la política) |
| `npm audit --audit-level=high` | ❌ 6 altas: `picomatch` (GHSA-fc5q-39xc-vjpf, ReDoS) vía `vite`, `fdir`, `tinyglobby`, `vite-plugin-full-reload`; sin cambios de lockfile en la 049 → R13 |
| `trivy fs` (HIGH, CRITICAL; sin `vendor`, `node_modules`, `.ai`) | ✅ sin hallazgos (dependencias de desarrollo aparte) |

| Tema OWASP (Top 10:2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ (⚠ R7) | `role:` en los tres grupos; alcance en las dos consultas; `show` comprueba `autorizadoParaCita` |
| A05 Inyección | ✅ | Query Builder con bindings; listas de permitidos; semgrep limpio |
| XSS / salida | ✅ | Solo `{{ }}`; `href` con `route()`; sin `<script>` nuevo (test) |
| Validación de entradas / DoS | ✅ | Escalares, formato y periodo de una semana o un mes como mucho; R27 (año sin acotar, menor) |
| Datos sensibles en URL | ➖ | R28: `buscar` viaja en los enlaces (ya pasaba en la 034) |
| Dependencias | ❌ | R13 (`npm audit`) |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (sin acciones nuevas; el alta ya se registra).
- Datos sensibles en logs del código tocado: ninguno (sin logs nuevos).
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `semana.blade.php:18,25,36,45`; `calendario.css` (`.cal-carriles-*`, `.cal-carril-*`); `CalendarioCitas.php` (`asignarCarriles`) | Más de 4 citas a la vez (las canceladas cuentan) se apilan en la columna 4 y se tapan (CA6) | Carriles hasta 8 en la vista y el CSS; test unitario y Feature con 5 a la vez |
| R2 | importante | `lista.blade.php:7-10,24-27` | Con un día elegido sin citas, la lista dice «No hay citas en esta semana / en este mes» | «No hay citas el <día>» y «Ver toda la semana / todo el mes»; test Feature |
| R3 | importante | `CalendarioCitas.php` (`posicion()`, constructor) | Una cita vigente oculta por `buscar` o `estado` antes de la primera fila (8:00) marca ocupado el tramo de las 9:00 (CA7) | Ocupación por minuto de reloj; test unitario con `citas: []` y ocupación a las 8:00 |
| R4 | importante | `CalendarioCitas.php` (`fechaOtraVista()`) | Desde un mes cuyo día 1 es domingo y que no contiene hoy, «Semana» abre la semana anterior, del mes previo (CA2) | Base en el primer día del mes que no sea domingo; test unitario con marzo de 2026 |
| R5 | importante | `tests/Feature/CalendarioCitasTest.php:382, 395` | El alta con `fecha=ayer&hora=25:99&dentista=nadie` y con no escalares solo comprueba 200; no prueba que se ignoren (CA10, TM3) | Comprobar `fecha` por defecto, `hora` 09:00 y ningún dentista ni paciente seleccionado; comprobar rompiendo |
| R6 | importante | `tests/Feature/CalendarioCitasParametrosTest.php:52-56` | El test del día fuera del periodo pasaría con el control quitado | Título exacto y sin «Ver toda la semana / todo el mes»; comprobar rompiendo |
| R7 | importante | `CalendarioCitasAlcanceTest.php`; control en `CitasController.php:160` | La consulta de ocupación (la única con su propio `aplicarAlcanceRol`) no se ejercita en el test de alcance (TM1) | Asistente con `buscar` y con `dentista_id` ajeno: el hueco de la cita ajena sigue; comprobar quitando el alcance |
| R8 | importante | `tests/Feature/CalendarioCitasCssTest.php:23-47` | La lista de pares de contraste es manual: reglas de color sin comprobar (`.mes-hoy`, `.cal-lista-hoy`, `.tira-dia--elegido .tira-total`…) | Toda regla con `color:` de la hoja debe estar en la lista o en una de excepciones justificadas; añadir los pares |
| R9 | importante | `index.blade.php:112,119` | `@includeIf` deja la página sin calendario ni lista, con 200, si falta un parcial | `@include` |
| R10 | importante | `calendario.css:22, 31-33` | El foco de «Semana · Mes» lo recorta `overflow: hidden` de `.cal-vista` (2.4.7) | Foco por dentro (`outline-offset` negativo) |
| R11 | importante | `calendario.css:31-33, 43-55` | El foco de las cabeceras de día lo recorta `.cal-semana` y lo tapan las columnas siguientes | Foco por dentro |
| R12 | importante | `semana.blade.php:60-64`; `calendario.css:281` | En la tira del móvil, «hoy» (no elegido) solo se distingue por el color del borde (1.4.1) y su nombre no lo dice | Texto oculto «, hoy» y borde de `--border-width-strong` |
| R13 | importante | `package-lock.json` (sin cambios en la 049) | `npm audit`: 6 altas (`picomatch` ReDoS vía `vite`); P6 | `/chore` de npm antes del `/release` (versiones con ≥ 7 días) |
| R14 | menor | `barra.blade.php:10,12` | «‹» y «›» se leen en voz alta; el nombre no dice qué recorre | Símbolos con `aria-hidden` y «semana / mes» oculto |
| R15 | menor | `semana.blade.php:44-50` | Unas 100 paradas de tabulador en los huecos de la semana | Decisión del diseño: «Saltar a la lista» lo evita; roadmap |
| R16 | menor | `lista.blade.php:13,15-16` | En el móvil la región se nombra con el `h2` oculto, no con el título visible | Nombrar con el título visible |
| R17 | menor | `lista.blade.php:35` | Cada día es un landmark `region` | `div` con el `h3` |
| R18 | menor | `mes.blade.php:3-7` | La cuadrícula del mes no tiene grupo con nombre | `role="group"` con nombre |
| R19 | menor | `calendario.css` (`.cal-dia--elegido`, `.cal-cita`) | Cita agendada `--p100` sobre el día elegido `--p100` | Borde o fondo distinto |
| R20 | menor | `calendario.css:54-55, 99-109` | Alto fijo y elipsis: con zoom de texto al 200 % se recorta (la lista y el nombre accesible lo tienen todo) | Documentar; `min-height` en la cabecera |
| R21 | menor | `semana.blade.php:7`; `lista.blade.php` | El destino de «Saltar a la lista» no tiene `tabindex="-1"` | `tabindex="-1"` en `#lista-titulo` |
| R22 | menor | `CalendarioCitasTest.php:361-369` | El test de `style=""` solo recorre la semana con citas | Mes, vacío y día elegido |
| R23 | menor | `CalendarioCitasTest.php:122-125`; `CalendarioCitasAlcanceTest.php:197` | `assertStringContainsString('1 cita')` acepta «11 citas» | Comparación exacta |
| R24 | menor | `CitasController.php:140, 177`; `index.blade.php:14-17` | Estados y etiquetas en tres sitios | Centralizar en `EstadosCita` |
| R25 | menor | `semana.blade.php:33`; `lista.blade.php:41` | Hora y etiqueta calculadas en dos parciales con `Carbon::parse` en la zona de la app | Exponerlas desde `CalendarioCitas` |
| R26 | menor | `CalendarioCitas.php` (`tituloResumen()`) | En una semana que cruza el año, el título del resumen no lleva año | Año cuando cambia; test unitario |
| R27 | menor | `CitasController.php` (`fechaDe`) | `fecha` acepta cualquier año (9999-12-31 lleva el fin de semana al año 10000) | Acotar el año; caso en `CalendarioCitasParametrosTest` |
| R28 | menor | `index.blade.php:20-28` | `buscar` (nombre o teléfono) viaja en todos los enlaces (ya en la 034) | Excepción documentada en `security.md` o no propagarlo |
| R29 | menor | `CitasController.php:102-103` | `vista=mes&fecha=basura` muestra el mes actual, no la semana (CA2 dice semana) | Aclarar en la spec |
| R30 | menor | `CalendarioCitas.php:103-111` | Un domingo, «Hoy» abre la semana que acaba de terminar | Confirmar con el usuario |
| R31 | menor | `CalendarioCitas.php:241-275`; `semana.blade.php:57-66` | Citas en domingo: el mes no las cuenta y la tira pasa a 7 días | Aceptar como dato fuera de regla |
| R32 | menor | `CalendarioCitas.php:241-247` | Un mes que empieza en domingo tiene una primera fila entera del mes anterior | Empezar en el lunes siguiente |
| R33 | menor | `CitasController.php:155-165` | La consulta de ocupación se repite para la dentista aunque coincida con la del periodo; condición poco legible | Simplificar la condición |
| R34 | menor | `tests/Unit/CalendarioCitasTest.php` | Sin casos de `titulo()` al cruzar el año ni de periodo anterior y siguiente del mes | Añadirlos |
| R35 | menor | `CalendarioCitasTest.php:57-85` | El HTML de «Cerrado desde las 14:00» no se prueba en Feature | Añadirlo |

## Preparación para release
- Rollback factible: sí, `bash deploy/volver.sh <tag anterior>`; sin migraciones ni datos nuevos.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: no (D9, Rollout).
- Docs actualizadas (architecture, deployment): `architecture.md` y el mapa de `AGENTS.md` (T090); `deployment.md` sin cambios (T091).
- SCA: R13 abierto; se corrige con un `/chore` antes del `/release`.

## Tareas añadidas
- T030 ← R1, R3, R4, R26 (tests unitarios)
- T031 ← R2, R5, R6, R12, R14, R21, R27 (tests Feature)
- T032 ← R7 (test de alcance de la ocupación)
- T033 ← R8, R10, R11 (test de la hoja)
- T034 ← R1, R3, R4, R26 (`CalendarioCitas`)
- T035 ← R27 (`fechaDe`)
- T036 ← R2, R9, R12, R21 (vistas de la lista y la semana)
- T037 ← R1, R10, R11, R12, R14 (barra y `calendario.css`)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-17:
- **R13** → roadmap: `npm audit` con 6 altas de `picomatch` vía `vite` (dependencias de build ajenas a la 049); se corrige con un `/chore` de npm antes del `/release`
- **R15** → roadmap: muchas paradas de tabulador en los huecos; «Saltar a la lista» las evita
- **R16** → roadmap: nombre de la región de la lista en el móvil
- **R17** → roadmap: días de la lista como landmarks
- **R18** → roadmap: grupo con nombre en la cuadrícula del mes
- **R19** → roadmap: cita agendada sobre el día elegido, mismo fondo
- **R20** → roadmap: zoom de texto al 200 % en el calendario
- **R22** → roadmap: test de `style=""` en más vistas
- **R23** → roadmap: comparaciones exactas de «N citas»
- **R24** → roadmap: estados y etiquetas centralizados
- **R25** → roadmap: hora y etiqueta desde `CalendarioCitas`
- **R28** → roadmap: `buscar` en los enlaces (excepción en `security.md` o no propagarlo)
- **R29** → roadmap: `vista=mes` con fecha no válida
- **R30** → roadmap: «Hoy» un domingo
- **R31** → roadmap: citas en domingo en el mes y la tira
- **R32** → roadmap: primera fila del mes que empieza en domingo
- **R33** → roadmap: consulta de ocupación de la dentista
- **R34** → roadmap: tests de títulos y navegación del mes
- **R35** → roadmap: test Feature de «Cerrado desde las 14:00»
