---
spec: 049-calendario-citas
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-16
---

# Plan · 049 Calendario de citas

## Enfoque técnico
Todo se pinta en el servidor, sin JavaScript nuevo: el calendario, la navegación, los días y los huecos
son enlaces con el periodo en la dirección (`?vista=semana|mes&fecha=AAAA-MM-DD&dia=AAAA-MM-DD`, más los
filtros de hoy `dentista_id`, `estado` y `buscar`). `CitasController::index` valida esos parámetros (los no
válidos se ignoran y se usa la semana actual), calcula el periodo y hace **una** consulta con el alcance
del rol de hoy (`aplicarAlcanceRol`) y los filtros, sin paginar. Una clase de dominio nueva,
`App\Agenda\CalendarioCitas`, sin base de datos, convierte esas citas, el horario de la clínica
(`config('clinica.contacto.horario_semanal')`) y la hora actual de la clínica en la estructura que pinta
la vista: días con sus citas en tramos de 30 minutos y carriles, huecos pulsables, cuentas por día y la
cuadrícula del mes. La vista `citas/index` se rehace como el candidato `c6` del acta, con las cuatro
correcciones aceptadas, en parciales `citas/calendario/*` y una hoja propia `public/css/calendario.css`
cargada con `@push('styles')` (como `reportes.css`, spec 047). Agendar en un hueco usa la ruta de alta de
hoy, que ya acepta `fecha`, `hora` y `dentista`.

## Constitution Check
| Principio | Resultado | Cómo se cumple | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada | ✅ | Spec 049 y acta `approved` (2026-11-16); el código empieza con `tasks.md` aprobado | manual: `aidd.py status` |
| P2 Test que falla antes | ✅ | Cada CA cambia comportamiento salvo el alcance por rol y el 403 por grupo (CA10), que se fijan con tests de caracterización comprobados rompiendo `aplicarAlcanceRol` y `role:`; la redirección nueva de `store` tiene su test que falla antes (A2) | test: tareas de Tests (Trazabilidad) |
| P3 Autorización en servidor | ✅ | Sin rutas nuevas; `index` conserva `role:` del grupo y `aplicarAlcanceRol` en la consulta única (calendario, lista, cuentas y resumen salen de ella); el filtro `dentista_id` se suma al alcance, nunca lo amplía. Excepción al Gate (A14): el listado no autoriza un recurso sino que filtra una colección; `aplicarAlcanceRol` usa `AlcanceClinico` (la clase de `app/Autorizacion/` de los Gates de la 026) y la 049 no lo cambia | test: `CalendarioCitasAlcanceTest` (dentista y asistente con `dentista_id` ajeno, periodo y vista manipulados → solo sus citas, cuentas y resumen incluidos; 403 del paciente en los tres grupos y del personal en los grupos ajenos) |
| P4 Entrada validada, bindings | ✅ | `vista` en `semana` o `mes`; `fecha` y `dia` con `date_format:Y-m-d`; `dentista_id` UUID; `estado` en la lista de estados; `buscar` ≤ 100 caracteres; lo no válido (también un valor no escalar, `?fecha[]=…`) se descarta sin error (CA2); `create` ignora los valores no escalares o mal formados de `fecha`, `hora` y `dentista` (A13). Consulta con Query Builder y bindings (el `whereRaw` con `?` del buscador es de hoy y no cambia). Salida con el eco escapado de Blade | test: `CalendarioCitasParametrosTest` (cada parámetro no válido o no escalar → semana actual, 200, sin 500) y `test_el_alta_ignora_parametros_no_validos` (también no escalares) |
| P5 Nada sensible en el repo | ✅ | Datos de prueba del escenario sintético | lint: gitleaks en T092 |
| P6 Sin vulnerabilidades altas | ✅ | Sin dependencias nuevas | test: `composer audit` y `npm audit` en `/review` y `/release` |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas; agendar sigue por el alta de hoy | — |
| P8 Correlación y logs | ➖ | Sin logs nuevos | — |
| P9 Solo el sistema de diseño | ✅ | `calendario.css` solo con tokens (sin tokens nuevos) y clases propias (`.cal-*`, sin `.lista-citas` de la 027, A20); ningún `style=""` en `citas/index` ni en sus parciales, tampoco en el resumen de cifras, que hoy los lleva (A7); el componente «Calendario de citas» entra antes en `system.md`; contenido dentro de `.formulario-accesible` (DS1); WCAG AA medido | test: `CalendarioCitasCssTest` (`ReglasCss::literales()` vacío con control positivo; **todos** los pares texto/fondo ≥ 4,5:1 y borde o foco/fondo ≥ 3:1 de `calendario.css`, listados en la tarea: citas por estado, atenuadas, opción actual y día elegido sobre `--p600`, «N citas», hora de la lista, «Cerrado», días de otro mes (A6)); `CalendarioCitasTest` (ningún `style=""` en la página, A7); `design/medir.mjs` sobre la build renderizada (puertas y KPIs del acta) |
| P10 Rutas indexables | ➖ | «Citas» exige sesión | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| (rama) | Fusionar `origin/main` (v0.20.0, spec 046, sistema 1.18.0) antes de empezar; hecho en `/analyze` r1 (A15) | bajo |
| `docs/design/system.md`, `system.html` | Componente «Calendario de citas» (barra del periodo, semana por horas, tira, mes, lista del periodo) con los cinco refinados del acta, en 1.19.0; «Filtro plegable (spec 034)» dice cómo queda el filtro de «Citas» (sin «Desde/Hasta» ni atajos, A20) | bajo |
| `app/Agenda/CalendarioCitas.php` (nuevo) | Periodo (semana lunes–sábado o mes), filas de 30 min del horario de la clínica ampliadas si hay citas fuera de él, carriles por coincidencia, huecos (desde el siguiente tramo, en horario, sin cita vigente a la vista o de la dentista mostrada; hora `H:i` con cero), cuentas por día, cuadrícula del mes, títulos del periodo y del resumen | medio: cálculo de carriles y huecos (tests unitarios) |
| `app/Http/Controllers/CitasController.php` | `index`: validación de parámetros, periodo, consulta única sin paginar, resumen del periodo (antes del filtro de estado, como hoy), `CalendarioCitas`; `fecha_desde` como alias de `fecha` (enlaces y tests anteriores); `store` redirige a la semana y al día de la cita nueva; `create` ignora `fecha`, `hora` y `dentista` no escalares o mal formados (A13) | medio |
| `resources/views/citas/index.blade.php` | Rehecha: resumen del periodo sin `style=""`, tarjeta del calendario (barra, filtro, semana o mes), lista del periodo; «Nueva cita» de la barra dentro de `.formulario-accesible`; `@push('styles')` de `calendario.css`; `filtros.js` sigue en `<head>` sin `defer` (034 D6, A22) | medio |
| `resources/views/citas/calendario/barra.blade.php`, `semana.blade.php`, `mes.blade.php`, `lista.blade.php` (nuevos) | Parciales del candidato `c6` | bajo |
| `public/css/calendario.css` (nuevo) | Estilos del `c6` con las correcciones; cargado solo en «Citas» | bajo |
| `tests/Unit/CalendarioCitasTest.php` (nuevo) | Periodo, carriles, huecos, filas extra, mes | — |
| `tests/Feature/CalendarioCitasTest.php`, `CalendarioCitasAlcanceTest.php`, `CalendarioCitasParametrosTest.php`, `CalendarioCitasCssTest.php` (nuevos) | CA1–CA10, P3, P4, P9 | — |
| `tests/Feature/MovilPersonalTest.php` | La lista de «Citas» sale de las tablas de la 034 (D2, filtros de fechas y atajos): sus casos pasan a la lista nueva o se retiran, con nota (049 reemplaza 034 CA2, CA5 y CA11 para esa lista) | medio: test de otra spec |
| `.ai/inspect/049-render-build.php` (no versionado) y `docs/specs/049-calendario-citas/design/medir.mjs` | La build se mide **renderizada por la app** con los datos y la hora de los candidatos (las mismas dentistas, pacientes y citas ficticias, «ahora» el martes 17/11/2026 a las 11:20) en las cuatro páginas del acta; `medir.mjs` las mide como archivos (A4) | — |
| `docs/design/capturar.mjs` | Las comprobaciones de la 034 sobre la lista de «Citas» del personal (atajos en una línea, foco de «Hoy», capturas `tabla` y `badges-estado`) se retiran o pasan a la pantalla nueva; `citas-index-{admin,dentista,asistente}` salen de la comparación del escritorio con la línea base (A3) | medio |

## Contratos y datos
Sin rutas nuevas ni cambios de esquema. **Parámetros de `GET /{rol}/citas`:**

| Parámetro | Validación | Si no es válido |
|---|---|---|
| `vista` | `semana` · `mes` | `semana` |
| `fecha` (alias `fecha_desde`) | `Y-m-d` | hoy (hora de la clínica) |
| `dia` | `Y-m-d`, dentro del periodo | sin día elegido |
| cualquiera | escalar (un array, `?x[]=…`, no es válido) | como «no válido» |
| `dentista_id` | UUID; se suma al alcance del rol | sin filtro |
| `estado` | `agendada`, `confirmada`, `en_curso`, `completada`, `cancelada`, `no_asistio` | sin filtro |
| `buscar` | texto ≤ 100 | sin filtro |

**Periodo:** semana de lunes a sábado que contiene `fecha` (un domingo pertenece a la semana que termina
el sábado anterior); mes de `fecha`, en celdas de lunes a sábado desde el lunes de la primera semana hasta
el sábado de la última. Las filas van de la apertura al cierre (`horario_semanal`); si alguna cita del
periodo empieza antes o termina después, o cae en domingo, la cuadrícula se amplía para mostrarla (las
citas nunca se ocultan). «Ahora» es `now(config('clinica.zona_horaria'))`, la zona en que se guardan las
citas.

**Huecos** (decisión del usuario, 2026-11-16): tramos de 30 min sin ninguna cita vigente a la vista (las
canceladas y «no asistió» no ocupan: el hueco va en un carril junto a ellas); con una sola dentista a la
vista (la vista de la dentista o el filtro de una), los tramos libres de esa dentista; dentro del horario y
desde el **siguiente** tramo (a las 11:20 el primero es el de las 11:30, A24). Hora siempre `H:i` con cero
(`09:00`, A5). Enlace a
`route('<rol>.citas.create', ['fecha' => …, 'hora' => …, 'dentista' => …])`, con `dentista` solo si el
calendario muestra un dentista (la dentista o el filtro de uno). Nombre accesible «Agendar el <día> <n> a
las <hora>».

**Posición de las citas no alineadas** (A10): una cita ocupa desde el tramo en que empieza hasta el tramo
en que termina (10:15 de 45 min ocupa los tramos de 10:00 y 10:30); para los huecos, ese tramo está ocupado.

**Carriles:** el máximo de citas simultáneas del día (las atenuadas también ocupan carril); la columna crece en proporción (clases hasta 4
carriles; más de 4 a la vez no se da con el personal de la clínica: Riesgos).

**Filtros y navegación** (A11): «Anterior», «Hoy», «Siguiente», «Semana · Mes», los días y los huecos
conservan `buscar`, `dentista_id` y `estado`; el formulario lleva `vista`, `fecha` y `dia` ocultos, así que
«Filtrar» conserva el periodo; «Limpiar» quita los filtros y conserva el periodo.

**Día del móvil** (A12): el elegido; si no hay, hoy si está en el periodo y, si no, el primer día del periodo.
El HTML lo marca (`data-dia-movil`) y el título de la lista en el móvil lo nombra.

**Vista:** variables `calendario` (estructura de `CalendarioCitas`), `citas` (colección del periodo,
ordenada; la usa `EstadosCitaRolesTest` con `viewData`), `dentistas` (como hoy), `resumen` y
`tituloResumen`.

**Pantallas y estados:**
| Pantalla | Estado | Qué se ve |
|---|---|---|
| Semana | con citas, sin día | resumen del periodo, barra, filtro, cuadrícula (≥ 900) o tira (< 900), lista de la semana (en el móvil, el día de hoy si está en la semana, con su título y «Ver toda la semana»; refinado 1) |
| Semana | con día elegido | cabecera del día rellena; lista solo de ese día a todo el ancho (refinado 2), con «Ver toda la semana» |
| Mes | | cuadrícula con «N citas»; lista del mes en orden de filas (refinado 2); en el móvil, el día de hoy o el elegido |
| Cualquiera | sin citas o sin resultados | «No hay citas en esta semana / en este mes» y «Agendar una cita» |
| Cualquiera | parámetros no válidos | semana actual |

**Qué ve cada rol:** no cambia (administración todas; dentista las suyas; asistente las de sus dentistas).

**Refinados del acta en el contrato:** (1) en el móvil el título de la lista nombra el día que muestra y
ofrece volver al periodo; (2) un solo día ocupa todo el ancho y los días del periodo se ordenan por filas;
(3) en el móvil, navegación y cambio de vista en una sola banda si caben con objetivos de 44 px; (4) en
cada cita destacan la hora y el paciente; (5) el resumen cuenta el periodo o el día elegido y su título lo
dice. Las citas del calendario y de la lista son una variante de «Cita que abre su detalle» (048): el
bloque o la fila entera es el enlace, con nombre «<hora>, <paciente>, <dentista>, <estado>» (A21).

## Estrategia de pruebas
- **Unitarias** (`tests/Unit/CalendarioCitasTest.php`): periodo (lunes–sábado, domingo, mes y sus bordes),
  filas extra con citas fuera de horario, carriles (dos y tres a la vez), huecos (pasados, ocupados, fuera
  de horario y libres), cuentas por día.
- **Feature** con `EscenarioEstadosCita` y reloj fijo: marcado por CA sobre el HTML (`DOMXPath`), enlaces y
  parámetros, alcance por rol, parámetros no válidos, alta con parámetros manipulados.
- **CSS** con `ReglasCss` sobre `calendario.css` (literales y contraste).
- **Navegador:** `design/medir.mjs` sobre las cuatro páginas renderizadas por la app con los datos y la hora
  de los candidatos (puertas y KPIs del acta a 320, 390 y 1440 px); una comprobación dirigida en la app con
  sesión de pulsar una cita y un hueco; `capturar.mjs --medir` adaptado (A3); verificación manual del
  usuario de las cuatro pantallas contra `c6` con los refinados a 320, 390, 844×390 (034 CA8, A19), 900, 1024
  y 1440 px.
- **Rendimiento (RNF):** un mes con 300 citas en la base local responde en < 1 s (medida en T026); en
  producción, el usuario comprueba en T097 que el mes abre sin esperas (decisión del usuario, A16).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Punto de entrada | Control | Test |
|---|---|---|---|---|
| TM1 | Divulgación: dentista o asistente cambia `dentista_id`, `fecha`, `vista` o `dia` para ver citas ajenas (A01) | `GET /{rol}/citas` | `aplicarAlcanceRol` en la consulta única; `dentista_id` solo restringe | `CalendarioCitasAlcanceTest` (caracterización, comprobada rompiendo el alcance) |
| TM2 | Manipulación / DoS: parámetros mal formados o no escalares, o periodos enormes, provocan errores o consultas pesadas (A03, A04) | `GET /{rol}/citas` | Validación de cada parámetro (escalar y con formato); el periodo es como mucho un mes | `CalendarioCitasParametrosTest` |
| TM3 | Manipulación: enlace de hueco con fecha, hora o dentista alterados (o no escalares) para agendar fuera de las reglas o romper el formulario (A04) | `GET /{rol}/citas/crear?…` y `POST /{rol}/citas` | `create` descarta lo no escalar o mal formado (A13); los valores solo se proponen; `store` valida horario, agenda única (025, 027) y alcance (014) | `CalendarioCitasTest::test_el_alta_ignora_parametros_no_validos` (caracterización) más los tests de 025/027 |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: semana por horas con citas, color y estado en texto | `CalendarioCitas`, `CitasController::index`, `semana.blade.php`, `calendario.css` | Unit: periodo y filas; Feature: `test_la_semana_muestra_cada_cita_en_su_dia_y_hora` · verificación manual: semana a 1440 contra `c6` |
| CA2: navegación, vista en la dirección, sin JS, parámetros no válidos | `index`, `barra.blade.php` | `test_la_navegacion_lleva_al_periodo_y_la_vista`, `CalendarioCitasParametrosTest` |
| CA3: mes con cuentas y día que filtra | `CalendarioCitas`, `mes.blade.php` | Unit: mes; Feature: `test_el_mes_cuenta_las_citas_y_filtra_por_dia` |
| CA4: lista del periodo, día elegido, filtros a la vez | `index`, `lista.blade.php` | `test_la_lista_agrupa_el_periodo_y_el_dia_elegido`, `test_los_filtros_se_aplican_al_calendario_y_a_la_lista` · verificación manual: refinados 1 y 2 |
| CA5: abrir la cita; una parada; nombre accesible | `semana.blade.php`, `lista.blade.php` | `test_cada_cita_abre_su_detalle_con_un_enlace` · `medir.mjs` (pulsar e Intro) |
| CA6: coincidencias en carriles; atenuadas | `CalendarioCitas`, `semana.blade.php`, `calendario.css` | Unit: carriles; Feature: `test_las_citas_que_coinciden_van_en_carriles` · verificación manual |
| CA7: huecos → «Nueva cita» con día, hora y dentista | `CalendarioCitas`, `semana.blade.php` | Unit: huecos; Feature: `test_los_huecos_llevan_a_nueva_cita`, `test_el_alta_ignora_parametros_no_validos` |
| CA8: móvil (tira de seis días, lista del día, mes compacto, 44 px, sin desbordes) | `lista.blade.php`, `calendario.css` | `test_la_tira_tiene_los_dias_con_sus_cuentas` · `medir.mjs` a 320 y 390 · verificación manual: refinado 3 |
| CA9: periodo vacío | `lista.blade.php` | `test_un_periodo_sin_citas_lo_dice` |
| CA10 (abuso): alcance y parámetros del alta | `index` (alcance de hoy), `create` | `CalendarioCitasAlcanceTest` (caracterización, con 403 por grupo), `test_el_alta_ignora_parametros_no_validos` |
| Redirección de `store` a la semana y el día de la cita nueva (cambio del plan) | `store` | `test_tras_agendar_se_vuelve_a_la_semana_y_al_dia_de_la_cita` |
| TM1 | `aplicarAlcanceRol` | `CalendarioCitasAlcanceTest` |
| TM2 | validación en `index` | `CalendarioCitasParametrosTest` |
| TM3 | alta y `store` de hoy | `test_el_alta_ignora_parametros_no_validos` |
| RNF: WCAG 2.1 AA, 44 px en el móvil | `calendario.css` | `CalendarioCitasCssTest` · `medir.mjs` |
| RNF: sin JavaScript | vistas | Feature: enlaces en el HTML, ningún `<script>` nuevo · `medir.mjs` (`G-sin-js`) |
| RNF: mes de ~300 citas en < 1 s | consulta única | manual: medida en local en T026; comprobación del usuario en producción en T097 |

## Observabilidad
No aplica: sin logs, eventos de auditoría ni métricas nuevas; agendar y abrir el detalle ya existen.

## Descubribilidad
No aplica: «Citas» exige sesión (sin ella redirige al inicio de sesión) y no es indexable.

## Rollout
- Sin feature flag: cambia la pantalla de «Citas» del personal, pero no datos, permisos ni el alta; la vuelta
  atrás es la versión anterior (decisión propuesta, como en la 044 y la 048).
- Orden: un solo despliegue, sin migraciones.
- Compatibilidad: los enlaces antiguos con `fecha_desde` siguen abriendo la semana de esa fecha.
- Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia.
- Métricas: `5xx` en `/{admin,dentista,asistente}/citas` la primera hora; el usuario abre el mes en
  producción y comprueba que carga sin esperas (RNF).

## Decisiones (→ ADR si son arquitectónicas)
| ID | Incógnita | Opciones | Decisión y motivo |
|---|---|---|---|
| D1 | Dónde se calcula el calendario | en la vista; en el controlador; clase de dominio | `App\Agenda\CalendarioCitas`, sin base de datos: carriles y huecos se prueban en unitarios; el controlador solo consulta y valida (convención de `app/Agenda/`) |
| D2 | Una consulta o varias | por día; una del periodo | Una del periodo (≤ un mes), sin paginar: calendario, lista, cuentas y resumen salen de la misma; RNF < 1 s |
| D3 | Parámetros del periodo | rango libre `desde/hasta`; `vista` + `fecha` + `dia` | `vista`, `fecha`, `dia`: el periodo siempre es una semana o un mes (TM2) y la dirección se puede compartir (CA2); `fecha_desde` queda como alias |
| D4 | CSS | en `app.css`; hoja propia | `public/css/calendario.css` con `@push('styles')`, como `reportes.css`; no se toca `app.css`, `movil.css` ni `layouts/app`. Aun así `capturar.mjs` comprueba la lista de «Citas» y se adapta (A3) |
| D5 | Resumen de cifras | de hoy; del periodo | Del periodo que se ve (o del día elegido), como hoy calcula la app, con título que lo dice (decisión del usuario, 2026-11-16); el acta dice «Hoy, martes 17»: refinado en `design.md` |
| D6 | Citas fuera del horario o en domingo | ocultarlas; ampliar la cuadrícula | Ampliarla en esa semana: ninguna cita desaparece del calendario |
| D7 | Tests de la 034 sobre la lista | conservarlos; adaptarlos | Adaptarlos: la 049 reemplaza 034 CA2, CA5 y CA11 para la lista de «Citas»; los casos de las demás listas no cambian |
| D8 | «Ahora» | UTC de la app; zona de la clínica | Zona de la clínica (`clinica.zona_horaria`), como la spec 026: las citas se guardan en esa hora |
| D9 | Feature flag | sí; no | No (aprobado con el plan, 2026-11-16): ver Rollout |
| D10 | Medir la build | en la app con sesión; renderizada con datos fijos | Renderizada por la app con los datos y la hora de los candidatos (`.ai/inspect/049-render-build.php`), para que los KPIs del acta sigan valiendo; la interacción (pulsar cita y hueco) se comprueba aparte en la app con sesión (A4) |

## Impacto en arquitectura
`docs/architecture.md`: añadir `App\Agenda\CalendarioCitas` en el módulo de agenda y la hoja
`calendario.css` (T090). `AGENTS.md`: mencionar `calendario.css` en el mapa de `public/css/` (ruta
protegida: se pide aprobación en la tarea).

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| Entre 900 y 1023 px la cuadrícula semanal queda estrecha (barra lateral visible) | Verificación manual a 900 y 1024 px; si no se lee, el corte de la tira sube con un refinado (A25) |
| Más de 4 citas a la vez en un día | Con el personal de la clínica no se da; si pasara, los carriles se reparten igual y el texto se recorta (aceptado) |
| `MovilPersonalTest` (034) pierde casos de la lista de «Citas» | Cada caso retirado o adaptado lleva nota en la tarea; los de las demás listas siguen |
| La 046 ya está en `main` (v0.20.0) | Fusionada en la rama en `/analyze` r1 (sin conflictos); el componente va en `system.md` 1.19.0; `capturar.mjs` se adapta sobre la versión de la 046 (`SIN_COMPARAR_046`) |
| El contenedor `dentissa_app` monta hoy el clon de la otra sesión | Tests de la 049 con `docker run --rm` sobre `dentissa-041` (imagen `dentissa-app`, `vendor_data` de solo lectura, red de la app) |
