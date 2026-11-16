---
spec: 049-calendario-citas
status: approved   # draft | approved | blocked
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
| P2 Test que falla antes | ✅ | Cada CA cambia comportamiento salvo el alcance por rol (CA10), que se fija con tests de caracterización comprobados rompiendo `aplicarAlcanceRol` | test: tareas de Tests (Trazabilidad) |
| P3 Autorización en servidor | ✅ | Sin rutas nuevas; `index` conserva `role:` del grupo y `aplicarAlcanceRol` en la consulta única (calendario, lista, cuentas y resumen salen de ella); el filtro `dentista_id` se suma al alcance, nunca lo amplía | test: `CalendarioCitasAlcanceTest` (dentista y asistente con `dentista_id` ajeno, periodo y vista manipulados → solo sus citas; cuentas por día incluidas) |
| P4 Entrada validada, bindings | ✅ | `vista` en `semana` o `mes`; `fecha` y `dia` con `date_format:Y-m-d`; `dentista_id` UUID; `estado` en la lista de estados; `buscar` ≤ 100 caracteres; lo no válido se descarta sin error (CA2). Consulta con Query Builder y bindings (el `whereRaw` con `?` del buscador es de hoy y no cambia). Salida con el eco escapado de Blade | test: `CalendarioCitasParametrosTest` (cada parámetro no válido → semana actual, 200, sin 500) |
| P5 Nada sensible en el repo | ✅ | Datos de prueba del escenario sintético | lint: gitleaks en T092 |
| P6 Sin vulnerabilidades altas | ✅ | Sin dependencias nuevas | test: `composer audit` y `npm audit` en `/review` y `/release` |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas; agendar sigue por el alta de hoy | — |
| P8 Correlación y logs | ➖ | Sin logs nuevos | — |
| P9 Solo el sistema de diseño | ✅ | `calendario.css` solo con tokens (sin tokens nuevos), sin `style=""` en lo nuevo; el componente «Calendario de citas» entra antes en `system.md`; contenido dentro de `.formulario-accesible` (DS1); WCAG AA medido | test: `CalendarioCitasCssTest` (`ReglasCss::literales()` vacío con control positivo; pares de contraste de las citas por estado y del anillo); manual: `design/medir.mjs` sobre la build (puertas y KPIs del acta) |
| P10 Rutas indexables | ➖ | «Citas» exige sesión | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `docs/design/system.md`, `system.html` | Componente «Calendario de citas» (barra del periodo, semana por horas, tira, mes, lista del periodo) con las correcciones del acta; versión siguiente a la vigente al fusionar | bajo |
| `app/Agenda/CalendarioCitas.php` (nuevo) | Periodo (semana lunes–sábado o mes), filas de 30 min del horario de la clínica ampliadas si hay citas fuera de él, carriles por coincidencia, huecos (futuros, en horario, libres), cuentas por día, cuadrícula del mes, títulos del periodo y del resumen | medio: cálculo de carriles y huecos (tests unitarios) |
| `app/Http/Controllers/CitasController.php` | `index`: validación de parámetros, periodo, consulta única sin paginar, resumen del periodo (antes del filtro de estado, como hoy), `CalendarioCitas`; `fecha_desde` como alias de `fecha` (enlaces y tests anteriores); `store` redirige a la semana y al día de la cita nueva | medio |
| `resources/views/citas/index.blade.php` | Rehecha: resumen del periodo, tarjeta del calendario (barra, filtro, semana o mes), lista del periodo; «Nueva cita» de la barra dentro de `.formulario-accesible` | medio |
| `resources/views/citas/calendario/barra.blade.php`, `semana.blade.php`, `mes.blade.php`, `lista.blade.php` (nuevos) | Parciales del candidato `c6` | bajo |
| `public/css/calendario.css` (nuevo) | Estilos del `c6` con las correcciones; cargado solo en «Citas» | bajo |
| `tests/Unit/CalendarioCitasTest.php` (nuevo) | Periodo, carriles, huecos, filas extra, mes | — |
| `tests/Feature/CalendarioCitasTest.php`, `CalendarioCitasAlcanceTest.php`, `CalendarioCitasParametrosTest.php`, `CalendarioCitasCssTest.php` (nuevos) | CA1–CA10, P3, P4, P9 | — |
| `tests/Feature/MovilPersonalTest.php` | La lista de «Citas» sale de las tablas de la 034 (D2, filtros de fechas y atajos): sus casos pasan a la lista nueva o se retiran, con nota (049 reemplaza 034 CA2, CA5 y CA11 para esa lista) | medio: test de otra spec |
| `docs/specs/049-calendario-citas/design/medir.mjs` | Ya existe; se usa sobre la build (cuentas de medición en la base local) | — |

## Contratos y datos
Sin rutas nuevas ni cambios de esquema. **Parámetros de `GET /{rol}/citas`:**

| Parámetro | Validación | Si no es válido |
|---|---|---|
| `vista` | `semana` · `mes` | `semana` |
| `fecha` (alias `fecha_desde`) | `Y-m-d` | hoy (hora de la clínica) |
| `dia` | `Y-m-d`, dentro del periodo | sin día elegido |
| `dentista_id` | UUID; se suma al alcance del rol | sin filtro |
| `estado` | `agendada`, `confirmada`, `en_curso`, `completada`, `cancelada`, `no_asistio` | sin filtro |
| `buscar` | texto ≤ 100 | sin filtro |

**Periodo:** semana de lunes a sábado que contiene `fecha` (un domingo pertenece a la semana que termina
el sábado anterior); mes de `fecha`, en celdas de lunes a sábado desde el lunes de la primera semana hasta
el sábado de la última. Las filas van de la apertura al cierre (`horario_semanal`); si alguna cita del
periodo empieza antes o termina después, o cae en domingo, la cuadrícula se amplía para mostrarla (las
citas nunca se ocultan). «Ahora» es `now(config('clinica.zona_horaria'))`, la zona en que se guardan las
citas.

**Huecos:** tramos de 30 min sin ninguna cita, dentro del horario, desde el tramo en curso; enlace a
`route('<rol>.citas.create', ['fecha' => …, 'hora' => …, 'dentista' => …])`, con `dentista` solo si el
calendario muestra un dentista (la dentista o el filtro de uno). Nombre accesible «Agendar el <día> <n> a
las <hora>».

**Carriles:** el máximo de citas simultáneas del día; la columna crece en proporción (clases hasta 4
carriles; más de 4 a la vez no se da con el personal de la clínica: Riesgos).

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
cada cita destacan la hora y el paciente.

## Estrategia de pruebas
- **Unitarias** (`tests/Unit/CalendarioCitasTest.php`): periodo (lunes–sábado, domingo, mes y sus bordes),
  filas extra con citas fuera de horario, carriles (dos y tres a la vez), huecos (pasados, ocupados, fuera
  de horario y libres), cuentas por día.
- **Feature** con `EscenarioEstadosCita` y reloj fijo: marcado por CA sobre el HTML (`DOMXPath`), enlaces y
  parámetros, alcance por rol, parámetros no válidos, alta con parámetros manipulados.
- **CSS** con `ReglasCss` sobre `calendario.css` (literales y contraste).
- **Navegador:** `design/medir.mjs` sobre la build servida en local (puertas y KPIs del acta a 320, 390 y
  1440 px, con cuentas de medición) y una comprobación dirigida de pulsar una cita y un hueco; verificación
  manual del usuario de las cuatro pantallas contra `c6` con los refinados.
- **Rendimiento (RNF):** un mes con 300 citas en la base local responde en < 1 s (medida manual en T092).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Punto de entrada | Control | Test |
|---|---|---|---|---|
| TM1 | Divulgación: dentista o asistente cambia `dentista_id`, `fecha`, `vista` o `dia` para ver citas ajenas (A01) | `GET /{rol}/citas` | `aplicarAlcanceRol` en la consulta única; `dentista_id` solo restringe | `CalendarioCitasAlcanceTest` (caracterización, comprobada rompiendo el alcance) |
| TM2 | Manipulación / DoS: parámetros mal formados o periodos enormes provocan errores o consultas pesadas (A03, A04) | `GET /{rol}/citas` | Validación de cada parámetro; el periodo es como mucho un mes | `CalendarioCitasParametrosTest` |
| TM3 | Manipulación: enlace de hueco con fecha, hora o dentista alterados para agendar fuera de las reglas (A04) | `GET /{rol}/citas/crear?…` y `POST /{rol}/citas` | El alta de hoy: los valores solo se proponen; `store` valida horario, agenda única (025, 027) y alcance (014) | `CalendarioCitasTest::test_el_alta_ignora_parametros_no_validos` (caracterización) más los tests de 025/027 |

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
| CA10 (abuso): alcance y parámetros del alta | `index` (alcance de hoy), alta de hoy | `CalendarioCitasAlcanceTest` (caracterización), `test_el_alta_ignora_parametros_no_validos` |
| TM1 | `aplicarAlcanceRol` | `CalendarioCitasAlcanceTest` |
| TM2 | validación en `index` | `CalendarioCitasParametrosTest` |
| TM3 | alta y `store` de hoy | `test_el_alta_ignora_parametros_no_validos` |
| RNF: WCAG 2.1 AA, 44 px en el móvil | `calendario.css` | `CalendarioCitasCssTest` · `medir.mjs` |
| RNF: sin JavaScript | vistas | Feature: enlaces en el HTML, ningún `<script>` nuevo · `medir.mjs` (`G-sin-js`) |
| RNF: mes de ~300 citas en < 1 s | consulta única | manual: medida en T092 |

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
- Métricas: `5xx` en `/{admin,dentista,asistente}/citas` la primera hora; tiempo de respuesta del mes.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Incógnita | Opciones | Decisión y motivo |
|---|---|---|---|
| D1 | Dónde se calcula el calendario | en la vista; en el controlador; clase de dominio | `App\Agenda\CalendarioCitas`, sin base de datos: carriles y huecos se prueban en unitarios; el controlador solo consulta y valida (convención de `app/Agenda/`) |
| D2 | Una consulta o varias | por día; una del periodo | Una del periodo (≤ un mes), sin paginar: calendario, lista, cuentas y resumen salen de la misma; RNF < 1 s |
| D3 | Parámetros del periodo | rango libre `desde/hasta`; `vista` + `fecha` + `dia` | `vista`, `fecha`, `dia`: el periodo siempre es una semana o un mes (TM2) y la dirección se puede compartir (CA2); `fecha_desde` queda como alias |
| D4 | CSS | en `app.css`; hoja propia | `public/css/calendario.css` con `@push('styles')`, como `reportes.css`; no se toca `app.css`, `movil.css` ni `layouts/app` (sin `capturar.mjs --medir`, AGENTS.md) |
| D5 | Resumen de cifras | de hoy; del periodo | Del periodo que se ve (o del día elegido), como hoy calcula la app, con título que lo dice (decisión del usuario, 2026-11-16); el acta dice «Hoy, martes 17»: refinado en `design.md` |
| D6 | Citas fuera del horario o en domingo | ocultarlas; ampliar la cuadrícula | Ampliarla en esa semana: ninguna cita desaparece del calendario |
| D7 | Tests de la 034 sobre la lista | conservarlos; adaptarlos | Adaptarlos: la 049 reemplaza 034 CA2, CA5 y CA11 para la lista de «Citas»; los casos de las demás listas no cambian |
| D8 | «Ahora» | UTC de la app; zona de la clínica | Zona de la clínica (`clinica.zona_horaria`), como la spec 026: las citas se guardan en esa hora |
| D9 | Feature flag | sí; no | No (propuesta): ver Rollout |

## Impacto en arquitectura
`docs/architecture.md`: añadir `App\Agenda\CalendarioCitas` en el módulo de agenda y la hoja
`calendario.css` (T090). `AGENTS.md`: mencionar `calendario.css` en el mapa de `public/css/` (ruta
protegida: se pide aprobación en la tarea).

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| Entre 900 y 1023 px la cuadrícula semanal queda estrecha (barra lateral visible) | Medir a 1024 px en la verificación manual; si no se lee, el corte de la tira sube con un refinado |
| Más de 4 citas a la vez en un día | Con el personal de la clínica no se da; si pasara, los carriles se reparten igual y el texto se recorta (aceptado) |
| `MovilPersonalTest` (034) pierde casos de la lista de «Citas» | Cada caso retirado o adaptado lleva nota en la tarea; los de las demás listas siguen |
| Choque con la 046 (otra sesión) | La 046 toca `citas/show` y `layouts/app`; la 049, `citas/index`, `CitasController::index/store` y archivos nuevos; `system.md` en bloques distintos (versión siguiente al fusionar) |
| El contenedor `dentissa_app` monta hoy el clon de la otra sesión | Tests de la 049 con `docker run --rm` sobre `dentissa-041` (imagen `dentissa-app`, `vendor_data` de solo lectura, red de la app) |
