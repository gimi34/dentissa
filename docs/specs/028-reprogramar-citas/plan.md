---
spec: 028-reprogramar-citas
status: draft
constitution_version: 1.2.1
created: 2026-10-07
---

# Plan · 028 Reprogramar citas

## Enfoque técnico
La reprogramación es una operación de la agenda que reutiliza las dos reglas que ya viven en un
solo sitio: la regla del hueco de `App\Agenda\ReservaCitas` (spec 025) y la regla de estados de
`App\Agenda\EstadosCita` (spec 026). Una clase nueva, `App\Agenda\ReprogramacionCitas`, abre una
transacción que bloquea primero al dentista (como la reserva, así una reprogramación y un agendado
del mismo dentista se procesan en fila) y después la cita. Dentro comprueba el estado, el plazo del
paciente y el hueco nuevo sin contar la propia cita, y guarda la fecha, el estado y una sola
entrada de historial. Quién puede reprogramar lo decide un Gate nuevo, `reprogramar-cita`, que
reutiliza el alcance de la 026. La cancelación definitiva no es un flujo nuevo: la pantalla de
reprogramar envía a las cancelaciones que ya existen, y el plazo de 24 horas del paciente entra en
la regla de estados, así que vale igual para cancelar desde el panel y desde la pantalla nueva.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 028 aprobada el 2026-10-07. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Todos los CA cambian comportamiento: hoy no existe la reprogramación (los tests de CA1, CA2, CA4–CA10 fallan con 404 o porque la cita no cambia) y el paciente cancela hasta el inicio (los tests de CA3 fallan). Los tests de la 026 que asertan lo anterior se actualizan en la misma tarea que cambia la regla (D6). Los casos que ya se cumplen dentro de un CA (el paciente que cancela con ≥ 24 h y la cancelación fuera de alcance) son tests de caracterización: Trazabilidad los marca con «caracterización» y dice qué se rompe un momento para comprobarlos (A11, A39). La cita ajena del paciente (404) va en el mismo test que su caso permitido (200), que falla, y el test de logs falla porque la clase aún no existe. Con `DatabaseTransactions` contra `dentissa_test`; los de concurrencia, con dos conexiones y limpieza en `tearDown`, como en la 025. | test: notas de cada tarea con el fallo previo · caracterización: marcada en Trazabilidad con lo que se rompe |
| P3 Autorización en servidor, por rol y recurso | ❌ aceptado: `GET /{rol}/citas/{id}`, `GET /cliente/citas` y `GET /cliente/citas/{id}` solo ganan los datos para mostrar u ocultar «Reprogramar» y el plazo, y conservan su control actual (rol con middleware y alcance sin Gate; `/cliente` sin `role:cliente`) → spec 014 (detalle del personal) y spec 015 (pantallas del paciente), como en la 026 — aprobado por el usuario el 2026-10-07 | Las rutas nuevas (GET y PATCH de reprogramar) tienen rol con middleware (`role:admin`, `role:asistente`, `role:cliente`) y el Gate `reprogramar-cita` sobre la cita. Tests por rol, permitido y denegado. | test: `ReprogramarCitaRolesTest` |
| P4 Entrada validada y SQL con bindings | ✅ | `fecha` (`date_format:Y-m-d`) y `hora` (`date_format:H:i`) con las reglas de `ReservaCitas::reglasDeEntrada()`, `bail`; `motivo` `nullable\|string\|max:500` (también en la cancelación del paciente). Todos los mensajes de validación en español, también `required` y los de `motivo` (`ReprogramacionCitas::mensajesDeEntrada()`; A8). Solo se leen las claves validadas: dentista, servicio, duración, paciente y estado enviados a mano se ignoran. Query Builder con bindings; la migración es DDL fijo. En Blade, solo salida escapada. | lint: semgrep `p/php` (también `routes/web.php`) · test: `ReprogramarCitaTest` (manipulación, CA9), `test_los_mensajes_de_validacion_estan_en_espanol` |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. | lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-07 en `main`: `composer audit --locked` limpio; `npm audit` con 2 críticas (`shell-quote`) y 1 alta (`source-map-js`), ya corregidas en la rama de la 031. La 028 se libera después de la 031 y fusiona `main` antes de `/release` (decisión del usuario, 2026-10-07). La excepción EX2 (imágenes) vence el 2026-10-31: si la liberación cae después, lo decide el usuario en `/release` (A17). | lint: `composer audit --locked`, `npm audit` y `trivy fs` en la verificación final, `/review` y `/release` |
| P7 Auditoría de datos sensibles | ❌ aceptado: la reprogramación y la cancelación quedan en el historial de la cita con autor, fechas y motivo, pero sin evento de auditoría formal (→ spec 022), y los 403 del Gate nuevo no se registran (→ spec 020), como en la 026 — aprobado por el usuario el 2026-10-07 | El almacén de auditoría (020) no está en `main`. | test: `ReprogramarCitaTest::test_la_reprogramacion_deja_una_sola_entrada_de_historial` |
| P8 Correlación y logs limpios | ❌ aceptado: si el `update` de la cita o el `insert` del historial fallan con una `QueryException` distinta de 1205/1213, el reporte por defecto registra el SQL con sus valores, motivo incluido; la redacción la hace la spec 023, como en la 026 — aprobado por el usuario el 2026-10-07 | Sin `Log::` nuevos; los rechazos y el bloqueo agotado vuelven como mensaje. La correlación por petición aún no existe (020). | manual: `/review` busca llamadas nuevas a `Log::` · test: `ReprogramarConcurrenteTest::test_ni_la_reprogramacion_ni_su_rechazo_escriben_en_los_logs` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | Las dos pantallas nuevas y el parcial van dentro del componente nuevo «Formulario accesible» (`.formulario-accesible`, D11), que da a sus campos, etiquetas, botones y foco valores que cumplen AA (etiqueta en `--text`, borde `--border-control`, foco `--focus-ring`, botón principal sobre `--p500`), y usan `.btn-danger`. Los dos componentes se incorporan antes al sistema ("Cambios a incorporar al sistema"). Las pantallas nuevas no cargan `citas.css`: el componente define el campo completo. En las vistas existentes, solo los bloques que se tocan (acciones e historial del detalle del personal; acciones y aviso del panel del paciente) quedan sin `style=`, dentro del componente; el resto de cada vista sigue como deuda DS4 (decisión del usuario, 2026-10-08; A2). Dentro del componente, el badge verde («Completada») usa `--text` sobre `--green-bg` (decisión del usuario, 2026-10-08; A31). Los bloques tocados tienen `id` fijos (tabla Pantallas; A35). Las reglas CSS nuevas solo usan tokens: la familia se hereda y el ancho de la hora del historial usa un token nuevo, `--timeline-time-width` (A37). Errores con `aria-describedby` y `aria-invalid`. | test: `ReprogramarCitaVistasTest::test_los_pares_de_contraste_cumplen_aa` (lista explícita de pares frente/fondo de "Pares de contraste", más los colores de las reglas nuevas medidos sobre el fondo de su propia regla; A32), `test_las_reglas_css_nuevas_solo_usan_tokens` (A37), `test_los_controles_tienen_foco_visible`, `test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_personal`, `…_en_la_pantalla_del_paciente`, `…_en_el_detalle_del_personal`, `…_en_el_detalle_del_paciente` y `…_en_la_lista_del_paciente` (sin `style=` ni colores literales, por `id`, con control positivo; A38) · manual: pantallas a 390 y 1440 px contra `docs/design/system.html`; a 390 px, lo nuevo no añade desbordamiento propio (la vista ya se desborda por DS8) (A16) |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/ReservaCitas.php` | Extraer de `reservarConBloqueo` dos métodos públicos para usar dentro de una transacción ya abierta: `bloquearDentista(string $id)` y `comprobarHueco(string $dentistaId, Carbon $inicio, Carbon $fin, ?string $excluirCitaId = null)` (horario del día, franja y solape; la cita excluida no cuenta). `reservar()` no cambia de comportamiento. | medio: la regla del agendado se toca; la cubren `ReglaAgendaTest` y `ReservaConcurrenteTest` sin cambios |
| `app/Agenda/ReprogramacionCitas.php` (nuevo) | Constructor `__construct(ConnectionInterface $db, ReservaCitas $reservas, EstadosCita $estados)`: las tres piezas comparten la conexión, y la espera de bloqueo es la de `ReservaCitas` (en los tests, `new ReprogramacionCitas($c, new ReservaCitas($c, 1), new EstadosCita($c))`; A9). `reglasDeEntrada()` y `mensajesDeEntrada()` estáticos: los de `fecha` y `hora` de la 025 más `required` y `motivo`, en español (A8). `reprogramar(string $citaId, string $fecha, string $hora, Usuario $actor, ?string $motivo): void`. Transacción: lee el dentista de la cita, lo bloquea, bloquea la cita, comprueba estado y plazo (`EstadosCita`), fecha pasada o igual (la nueva fecha y hora se interpretan en la zona de la clínica y se comparan con el instante actual; A4, D13), y el hueco (`comprobarHueco` excluyendo la cita); actualiza `fecha_hora`, `estado` (confirmada → agendada) y `actualizado_en`, e inserta una entrada de historial. Un bloqueo agotado al bloquear la cita → `CambioDeEstadoNoPermitido::ocupada()` (A21, D15); el resto de 1205/1213 y `DeadlockException` → `CitaNoAgendable::noDisponible()`. | medio: concurrencia (D3) |
| `app/Agenda/EstadosCita.php` | `ANTELACION_PACIENTE_HORAS = 24`; `dentroDelPlazoDelPaciente(object $cita): bool`; `comprobarReprogramable(object $cita, Usuario $actor)` (cerrada → `cerrada()`; en curso o ya empezada → `yaEmpezo` del personal; paciente fuera de plazo → `fueraDePlazo()`); en `comprobar()`, la cancelación del paciente de una cita ya empezada sigue dando `yaEmpezo()`, y la de una que aún no empieza pero está fuera de plazo da `fueraDePlazo()` (A40); `vuelta()` recibe el rol del actor (A18). | medio: cambia 026 CA7 (D6) |
| `app/Agenda/CambioDeEstadoNoPermitido.php` | `fueraDePlazo()` con el texto aprobado; `yaEmpezoPersonal()` («Esta cita ya empezó y no se puede reprogramar.»); `vuelta(string $rol)` con el texto según el rol (tabla Mensajes; decisión del usuario, 2026-10-08; A18). `yaEmpezo()` se mantiene para las citas del paciente que ya empezaron (decisión del usuario, 2026-10-08; A40). | bajo |
| `app/Agenda/CitaNoAgendable.php` | `enElPasado()` y `mismaFechaYHora()`. | bajo |
| `app/Autorizacion/ReprogramarCita.php` (nuevo) | `autorizar(Usuario $usuario, object $cita): Response`: alcance de `CambiarEstadoCita::alcance()` (personal fuera de alcance → 403, paciente con cita ajena → 404) y rol en `admin`, `asistente` o `cliente`; el dentista se deniega con mensaje. | bajo |
| `app/Providers/AppServiceProvider.php` | `Gate::define('reprogramar-cita', [ReprogramarCita::class, 'autorizar'])`. | bajo |
| `app/Http/Controllers/CitasController.php` | `reprogramarForm(string $id)` (GET) y `reprogramar(Request, string $id)` (PATCH): busca la cita (404), `Gate::authorize('reprogramar-cita')`, valida `fecha`, `hora` y `motivo`, llama a `ReprogramacionCitas` y vuelve al detalle con éxito. Los rechazos del hueco, de fecha pasada y de fecha igual vuelven al formulario con el error en `hora`, como el agendado de la 025 (A22, D14); los de estado (cerrada, ya empezó) vuelven con el mensaje `error`, y el GET muestra entonces el aviso en lugar del formulario (A6). `show()` añade `puede_reprogramar`. | bajo |
| `app/Http/Controllers/ClienteCitasController.php` | `reprogramarForm` y `reprogramar` para el paciente (mismo esquema, con mensajes sin estados internos); Una cita cerrada o en curso se traduce a «Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.», sin estados internos (A7). `cancelar()` acepta `motivo` opcional con los mensajes en español de la 026, conserva la entrada con `withInput()` (D10) y traduce `fueraDePlazo`; `conPuedeCancelar()` pasa a usar el plazo y añade `puede_reprogramar`, `aviso_plazo` (detalle: el mensaje de `yaEmpezo()` si la cita ya empezó, el de `fueraDePlazo()` si aún no empieza pero faltan menos de 24 h; A40) y `aviso_plazo_corto` (listado, solo si aún no empieza y faltan menos de 24 h; A13). | medio: cambia lo que ve el paciente |
| `routes/web.php` | `GET` y `PATCH /{admin,asistente}/citas/{id}/reprogramar` (`citas.reprogramar`, `citas.reprogramar.update`) en sus grupos; `GET` y `PATCH /cliente/citas/{id}/reprogramar` con `->middleware('role:cliente')`. Ninguna en el grupo del dentista. | bajo |
| `database/migrations/2026_10_07_000000_reprogramacion_en_historial_citas.php` (nuevo) | `historial_citas` gana `fecha_hora_anterior` y `fecha_hora_nueva` (`DATETIME NULL`); `down()` las quita. | bajo: solo añade columnas que admiten nulo |
| `resources/views/citas/partials/reprogramar.blade.php` (nuevo) | Dentro de `.formulario-accesible`: resumen de la cita (paciente o dentista según quien mira, servicio, fecha, hora, estado), formulario de fecha, hora y motivo con errores por campo, y la sección separada `id="no-reagendar"` con motivo y `.btn-danger` «Cancelar la cita definitivamente» (texto: no se puede deshacer; sin `confirm()`, D12). Cada formulario tiene sus propios `id` (`reprogramar-motivo`, `cancelar-motivo`) y el campo oculto `formulario` (`reprogramar` o `no-reagendar`): `old()` y los errores solo se pintan en la sección cuyo `formulario` coincide (D10). Si la cita ya no se puede mover, el motivo y un enlace al detalle en lugar de los dos formularios (A6). | bajo |
| `resources/views/citas/reprogramar.blade.php`, `citas/cliente-reprogramar.blade.php` (nuevos) | Pantallas del personal y del paciente con el parcial y sus rutas. | bajo |
| `resources/views/citas/show.blade.php` | En «Acciones rápidas», que pasa a `id="acciones"`: «Ver expediente del paciente», «Reprogramar» (`.btn btn-outline`, si `puede_reprogramar`) y «Agendar otra cita» (antes «Reagendar / Nueva cita», mismo destino; decisión del usuario, 2026-10-08; A44). En `id="historial"`: «Reprogramada del … al …» y el cambio de estado si lo hubo; la línea «por …» pasa a una clase en `--text` (A32). Los dos bloques (`#acciones` y `#historial`) quedan sin `style=`, dentro de `.formulario-accesible` (A2, A35). `#estado` no se toca. | bajo |
| `resources/views/citas/cliente-show.blade.php`, `cliente-index.blade.php` | «Reprogramar» y «Cancelar» (`.btn-danger`) solo dentro del plazo; fuera, el aviso de plazo en el detalle y el aviso corto en el listado (A13, A40). Los bloques tocados quedan sin `style=`, dentro de `.formulario-accesible`: en el detalle, el contenido de `topbar-actions` dentro de `id="acciones-cita"`; en la lista, la celda de acciones de cada fila dentro de `id="acciones-{id de la cita}"` (A2, A35). La confirmación existente de «Cancelar» no cambia (DS7). | bajo |
| `public/css/app.css` | `.btn-danger` con tokens (`--red`, `--red-bg`) y foco visible (`--focus-ring*`); componente `.formulario-accesible` (D11); las reglas del historial que hoy van en línea pasan a reglas acotadas a `.formulario-accesible`, sin tocar las globales que usa `dashboard/dentista` (A49): `.formulario-accesible .timeline-time` con `--timeline-time-width`, `.formulario-accesible .timeline-dot` en `--p300`, y `.historial-estado-anterior` (`--text`), `.historial-estado-nuevo` (`--p600`) y `.historial-autor` (`--text`, `--text-small`, `margin-top: var(--space-1)`); clases de maquetación de los bloques tocados, también acotadas y solo con tokens (A48, tabla "Maquetación de los bloques tocados"); token nuevo `--timeline-time-width: 100px` en `:root`; `.formulario-accesible .field:focus` con borde `--border-control` y sin sombra, para ganar a `citas.css` en `citas/show` (A37); `.formulario-accesible .badge-green` con `color: var(--text)` (A31). Ninguna regla nueva declara familia, px, hex, rgb ni pesos literales. Solo añadidos. | bajo |
| `docs/design/system.md`, `system.html` | Componentes `.btn-danger` y «Formulario accesible», y la pantalla de reprogramar; versión 1.3.0. | bajo |
| `tests/Feature/` | Nuevos: `ReprogramarCitaTest`, `ReprogramarCitaRolesTest`, `ReprogramarCitaPacienteTest`, `ReprogramarConcurrenteTest`, `ReprogramarCitaVistasTest`. Modificados (D6): `EstadosCitaRolesTest` (plazo del paciente), `EstadosCitaTransicionesTest` (texto de `vuelta` por rol), `HistorialCitaTest` (el caso del paciente pasa a una cita con ≥ 24 h) y `EstadosCitaVistasTest` (un test del paciente, A40). | — |

## Contratos y datos

### Rutas
| Método y ruta | Middleware | Gate | Respuestas |
|---|---|---|---|
| `GET /admin/citas/{id}/reprogramar` | `auth`, `role:admin` | `reprogramar-cita` | 200 formulario; 404 si no existe |
| `PATCH /admin/citas/{id}/reprogramar` | `auth`, `role:admin` | `reprogramar-cita` | 302 al detalle con éxito; 302 al formulario con errores |
| `GET`/`PATCH /asistente/citas/{id}/reprogramar` | `auth`, `role:asistente` | `reprogramar-cita` (alcance: sus dentistas) | igual; fuera de alcance 403 |
| `GET`/`PATCH /cliente/citas/{id}/reprogramar` | `auth`, `role:cliente` | `reprogramar-cita` (alcance: sus citas) | igual; cita ajena 404; personal 403 |
| `PATCH /cliente/citas/{id}/cancelar` (existente) | sin cambios | sin cambios | acepta `motivo` opcional; fuera de plazo → mensaje de plazo |
| `PATCH /{admin,asistente}/citas/{id}/estado` (existente) | sin cambios | sin cambios | lo usa la sección «No reagendar» del personal con `estado=cancelada` y `motivo` |

Si el formulario se abre para una cita que ya no se puede mover (cerrada, empezada, también una
agendada ya empezada, o fuera de plazo), responde 200 con el motivo y un enlace al detalle en lugar
de los formularios de reprogramar y de «No reagendar»; nunca un error del servidor (decisión del
usuario, 2026-10-08; A6). Tras una cancelación desde «No reagendar», se vuelve a esta pantalla, que
muestra el mensaje de éxito y el aviso de cita cerrada.

### Entrada
`fecha` (`bail|required|date_format:Y-m-d`), `hora` (`bail|required|date_format:H:i`), `motivo`
(`nullable|string|max:500`), con los mensajes de `ReprogramacionCitas::mensajesDeEntrada()`: los de
`ReservaCitas` más «Elige una fecha.», «Elige una hora.», «El motivo debe ser un texto.» y «El motivo
puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.» (A8). Nada más se lee, salvo
el campo oculto `formulario`, que solo usa la vista para elegir la sección (D10). Los errores de
validación van a su campo; los del hueco, de fecha pasada y de fecha igual, a `hora` (D14).

### Datos
`historial_citas` (expand, sin borrar nada):

| Columna | Tipo | Uso |
|---|---|---|
| `fecha_hora_anterior` | `DATETIME NULL` | inicio antes de reprogramar; nulo en los cambios de estado |
| `fecha_hora_nueva` | `DATETIME NULL` | inicio después de reprogramar |

Una reprogramación inserta `estado_anterior` = estado previo, `estado_nuevo` = `agendada`, las dos
fechas, `motivo`, `cambiado_por` y `fecha`. El volcado `database/dentissa.sql` (ruta protegida) no se
toca: la migración se aplica después de cargarlo, como la de la 026.

### Mensajes
| Situación | Mensaje |
|---|---|
| Paciente fuera de plazo (cancelar o reprogramar) | «Faltan menos de 24 horas para tu cita: ya no puedes cambiarla ni cancelarla desde aquí. Llama a la clínica.» (decisión del usuario, 2026-10-07) |
| Cita cerrada | el de `CambioDeEstadoNoPermitido::cerrada()` (026 CA2) |
| Cita ya empezada (personal) | «Esta cita ya empezó y no se puede reprogramar.» |
| Cita del paciente ya empezada (cancelar y detalle) | el de `yaEmpezo()` (026): «Tu cita ya empezó y no se puede cancelar. Si necesitas ayuda, llama a la clínica.» (decisión del usuario, 2026-10-08; A40) |
| Fecha y hora pasadas | «Elige una fecha y hora que aún no hayan pasado.» |
| Igual a la actual | «La cita ya está en esa fecha y hora. Elige otra.» |
| Hueco | los de `CitaNoAgendable` (025 CA2–CA4) |
| Volver a «Agendada» por el cambio de estado (admin y asistente) | «Una cita no puede volver a «Agendada». Si hay que moverla, usa «Reprogramar».» (decisión del usuario, 2026-10-08) |
| Volver a «Agendada» por el cambio de estado (dentista) | «Una cita no puede volver a «Agendada». Pide a tu asistente o a la administración que la reprogramen.» (decisión del usuario, 2026-10-08; A18) |
| Paciente con una cita cerrada o en curso (reprogramar) | «Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.», sin estados internos, como 026 D7 (A7) |
| Listado del paciente fuera de plazo | «Faltan menos de 24 h: llama a la clínica para cambiarla.» (decisión del usuario, 2026-10-08; A13) |
| Bloqueo agotado al bloquear la cita | el de `CambioDeEstadoNoPermitido::ocupada()` (026) (A21) |
| Validación | «Elige una fecha.», «Elige una hora.», los de formato de la 025 y los de `motivo` de la 026 (A8) |

### Pantallas
| Pantalla | Componentes | Estados |
|---|---|---|
| Reprogramar (personal y paciente) | todo dentro de `.formulario-accesible` (D11); `.card` con el resumen y badge de estado (el verde, con `--text`; A31); `.field`/`.field-label` para fecha, hora y motivo; `.field-hint-error` con `aria-describedby` y `aria-invalid`; `.btn btn-primary` «Guardar nueva fecha»; sección aparte `#no-reagendar` con `.btn-danger` «Cancelar la cita definitivamente», sin `confirm()` (D12); cada formulario con sus `id` y su campo `formulario` (D10) | éxito (vuelve al detalle con el mensaje flash), error por campo en la sección de su formulario, no reprogramable (aviso y enlace al detalle en lugar de los formularios, A6) |
| Detalle de la cita del personal | `#acciones`: «Ver expediente del paciente», `.btn btn-outline` «Reprogramar» y «Agendar otra cita» (A44); `#historial` con la entrada nueva; `#estado` sin cambios (A35) | con o sin «Reprogramar» según `puede_reprogramar` |
| Panel del paciente (detalle y lista) | `#acciones-cita` en el detalle y `#acciones-{id}` por fila en la lista (A35): «Reprogramar» y «Cancelar» (`.btn-danger`) dentro del plazo; fuera, el aviso de plazo en el detalle y el aviso corto en la lista (A13); con la cita ya empezada, el texto de `yaEmpezo()` en el detalle y nada en la lista (A40) | dentro de plazo, fuera de plazo o ya empezada |

### Qué ve cada rol
| Rol | Reprogramar | Cancelar definitivamente desde la pantalla |
|---|---|---|
| admin | todas las agendadas o confirmadas no empezadas | sí |
| asistente | las de sus dentistas | sí |
| cliente | las suyas, con ≥ 24 h | sí, con ≥ 24 h |
| dentista | no (sin ruta en su grupo; 403 en las de otros grupos) | no |

### Cambios a incorporar al sistema
- **`.btn-danger`** en `public/css/app.css`: texto `var(--red)` sobre `var(--red-bg)` (6.13:1, el
  par del badge rojo), borde `var(--border-width)` `var(--red)` (≥ 3:1 sobre blanco) y foco
  `outline: var(--focus-ring-width) solid var(--focus-ring)` con `var(--focus-ring-offset)`. Motivo:
  el sistema no tiene botón de peligro y hoy se hace en línea (Componentes → Botón).
- **«Formulario accesible»** (`.formulario-accesible`) en `public/css/app.css`: dentro de él,
  `.field-label` en `var(--text)` (21:1 sobre `--white`, 19.59:1 sobre `--p50`), `.field` completo
  (familia, tamaño `--text-body`, espaciado y radio con tokens, borde `var(--border-width)`
  `var(--border-control)`, 3.93:1), `.btn-primary` sobre `var(--p500)` (blanco, 5.42:1) y
  `:focus-visible` en campos, botones y enlaces con `--focus-ring`, `--focus-ring-width` y
  `--focus-ring-offset` (7.00:1). Motivo: los `.field`, `.field-label` y `.btn-primary` actuales no
  cumplen AA (DS1, DS2) y P9 lo exige en pantallas nuevas; el componente lo cumple solo donde se usa
  y el resto de la app no cambia (decisión del usuario, 2026-10-08; A1, D11). DS1 y DS2 anotan que
  avanzan en esta pantalla.
- **`--timeline-time-width`** (100px), el ancho actual de la hora en el historial, para que la regla
  `.timeline-time` no lleve un valor literal (A37).
- **Badge verde dentro del componente:** `--text` sobre `--green-bg` (18.50:1) en lugar de `--green`
  (2.98:1, DS1); el resto de la app no cambia (decisión del usuario, 2026-10-08; A31).
- **Pantalla «Reprogramar cita»** en `system.md` → Pantallas principales; sus capturas a 390 y 1440 px
  se añaden cuando la pantalla existe (tarea de capturas, después de las vistas del paciente).
- `system.md` sube a 1.3.0 con su fila en "Decisiones". Lo hace la primera tarea, antes del código.

### Maquetación de los bloques tocados (A48)
Sustituyen a los `style=` de los bloques que se tocan; todas con ámbito `.formulario-accesible` y
solo tokens (decisión del usuario, 2026-10-08: las crea la tarea del CSS; las tareas de vistas solo
las usan):

| Clase | Bloque | Valores (antes en línea → token) |
|---|---|---|
| `.acciones-columna` | `#acciones` (detalle del personal) | flex en columna, `gap: var(--space-3)` (8px); sus `.btn` con `justify-content: center` |
| `.acciones-fila` | `#acciones-{id}` (lista del paciente) | flex, `gap: var(--space-1)` (4px), `justify-content: flex-end` |
| `.form-en-linea` | formularios de «Cancelar» en `#acciones-cita` y `#acciones-{id}` | `display: inline` |
| `.aviso-plazo` | aviso de plazo (detalle) y aviso corto (lista) | `font-size: var(--text-body)` (antes 13px), `color: var(--text)` |
| `.historial-lista` | contenedor de `#historial` | `padding: var(--space-2) var(--space-7)` (6px 20px) |
| `.historial-vacio` | vacío de `#historial` | `padding: var(--space-7)` (antes 22px; decisión del usuario, 2026-10-08) |

`test_las_reglas_css_nuevas_solo_usan_tokens` revisa los selectores que empiezan por
`.formulario-accesible` y `.btn-danger` (A49), y los tests de «solo usa el sistema» de los bloques
siguen exigiendo cero `style=`.

### Pares de contraste (A32)
El test de contraste comprueba esta lista, frente sobre fondo, y además cada `color` de las reglas
nuevas sobre el `background` de su propia regla (o sobre `--white` y `--p50` si no declara fondo):

| Uso | Frente | Fondo | Mínimo |
|---|---|---|---|
| Etiqueta, aviso, autor y estado anterior del historial | `--text` | `--white`, `--p50` | 4.5:1 |
| Botón principal del componente | `--white` | `--p500` | 4.5:1 |
| `.btn-danger` | `--red` | `--red-bg` | 4.5:1 |
| Errores del campo | `--red` | `--white`, `--p50` | 4.5:1 |
| Estado nuevo del historial, `.btn-outline` | `--p600` | `--white` | 4.5:1 |
| Badges que puede mostrar la pantalla | `--p600`/`--p100`, `--blue`/`--blue-bg`, `--amber`/`--amber-bg`, `--text`/`--green-bg`, `--red`/`--red-bg`, `--text-muted`/`--p50` | — | 4.5:1 |
| Borde de campo y de `.btn-danger` | `--border-control`, `--red` | `--white`, `--p50` | 3:1 |
| Anillo de foco | `--focus-ring` | `--white`, `--p50` | 3:1 |

El punto del historial (`--p300`) es decorativo: el estado se lee en el texto.

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, `dentissa_test`)** con el escenario de
  `tests/Feature/Concerns/EscenarioEstadosCita.php` (dentistas, asistente asignado y pacientes; los
  horarios no los crea y se añaden con un método propio que solo llaman los tests nuevos, A19) y `Carbon::setTestNow` en UTC para fijar "ahora" respecto a la zona de la clínica:
  - `ReprogramarCitaTest`: CA1, CA4, CA5, CA7 y CA9 (admin y asistente; cada regla del hueco; la
    propia cita no cuenta; pasada e igual; confirmada → agendada; cerradas y empezadas; historial;
    campos manipulados y mal formados sin 500; la hora nueva en la zona de la clínica: con ahora =
    10:00 locales, las 12:00 locales del mismo día se aceptan y las 09:59 se rechazan, A4; mensajes de
    validación en español, A8; bordes de franja y la cita de otro dentista, 025 CA3 y CA7).
  - `ReprogramarCitaRolesTest`: CA8 y P3 (admin, asistente dentro y fuera de alcance, dentista en
    las rutas de otros grupos, paciente con cita propia y ajena, personal en la ruta del paciente),
    GET y PATCH; el GET de una cita que no se puede mover, por rol del personal y por caso (A6; el del
    paciente, en `ReprogramarCitaPacienteTest`, A50).
  - `ReprogramarCitaPacienteTest`: CA2, CA3 y CA6 del paciente (borde exacto de 24 h aceptado y
    23 h 59 min rechazado, el hueco nuevo dentro de menos de 24 h aceptado, cancelar con motivo desde
    la pantalla (GET con `#no-reagendar` y PATCH), mensaje de plazo, cita cerrada o en curso sin
    estados internos (A7), panel con y sin botones, aviso corto en la lista).
  - `ReprogramarConcurrenteTest`: CA10 y TM6–TM7 con una segunda conexión que retiene el bloqueo del
    dentista (como `ReservaConcurrenteTest`): la reprogramación espera y responde "no disponible";
    reprogramación frente a agendado; la cita bloqueada por un cambio de estado → mensaje `ocupada()`
    (A21); interbloqueo → mensaje; sin datos en logs.
  - `ReprogramarCitaVistasTest`: CA6 del personal (la sección «No reagendar» envía al cambio de
    estado con `cancelada` y el motivo; el dentista no la ve), el historial muestra la
    reprogramación con su texto escapado, cada formulario con sus valores y errores en su sección
    (A5), y P9: pares de contraste de "Pares de contraste" y de las reglas nuevas, reglas CSS nuevas
    solo con tokens, foco visible y sin `style=` ni colores literales en lo nuevo y en los bloques
    tocados por `id`, con un test por pantalla o bloque y control positivo (A1, A2, A32, A35, A37, A38);
    la pantalla de una cita completada muestra el badge verde con `--text` (A31).
- **Regresión:** `ReglaAgendaTest`, `ReservaConcurrenteTest`, `AltaCitas*`, `EstadosCita*` y
  `HistorialCitaTest` sin cambios, salvo los de D6, que se actualizan en la misma tarea que cambia la
  regla. `EstadosCitaTransicionesTest::test_volver_a_agendada_nunca_se_permite` sigue rechazando la
  vuelta en `PATCH …/estado` (A3).
- **Manual:** las pantallas nuevas y los bloques tocados a 390 y 1440 px (`.ai/project.yaml →
  design.widths`) contra `docs/design/system.html`, foco con teclado y lectura de errores; a 390 px se
  comprueba que lo nuevo no añade desbordamiento propio (DS8) (verificación manual de las tareas de
  las vistas; A16).

## Modelo de amenazas
Flujo: navegador (personal o paciente) → rutas nuevas → Gate → `ReprogramacionCitas` → MariaDB.
Entradas externas: `id` de la ruta, `fecha`, `hora`, `motivo` y cualquier campo añadido a mano.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un dentista o un asistente fuera de alcance mueve o cancela una cita | A01:2025 | rutas, Gate `reprogramar-cita` | sin ruta en el grupo del dentista; `role:` en cada grupo; Gate con alcance y rol | `ReprogramarCitaRolesTest::test_el_dentista_no_puede_reprogramar`, `test_el_asistente_no_reprograma_citas_de_dentistas_no_asignados` |
| TM2 | Information disclosure / Elevation: un paciente cambia el id para mover la cita de otro (IDOR) | A01:2025 | `ClienteCitasController`, Gate | alcance por el paciente del usuario → 404 | `test_el_paciente_no_encuentra_la_cita_de_otro` |
| TM3 | Tampering: se añaden a mano dentista, servicio, duración, paciente o estado | A06:2025 | controladores | solo se leen `fecha`, `hora` y `motivo` validados; el servicio no recibe otros campos | `ReprogramarCitaTest::test_los_campos_anadidos_a_mano_no_cambian_la_cita` |
| TM4 | Tampering: el paciente salta el plazo de 24 h enviando la petición directamente | A06:2025 | `EstadosCita::comprobarReprogramable` y `comprobar` | el plazo se comprueba en el servidor, dentro de la transacción, con la hora de la cita bloqueada | `ReprogramarCitaPacienteTest::test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano` |
| TM5 | Tampering / mal manejo de excepciones: fecha u hora mal formadas o listas provocan un 500 | A05:2025, A10:2025 | validación | `bail` + `date_format`; `string` en `motivo` | `test_una_fecha_u_hora_mal_formada_da_error_de_validacion` |
| TM6 | Tampering (carrera): dos peticiones se quedan el mismo hueco | A06:2025 | `ReprogramacionCitas` | bloqueo del dentista y luego de la cita, en una transacción, como la reserva | `ReprogramarConcurrenteTest::test_la_reprogramacion_espera_al_bloqueo_del_dentista_y_responde_no_disponible` |
| TM7 | Denial of service / excepción: bloqueo agotado o interbloqueo terminan en 500 | A10:2025 | `ReprogramacionCitas` | 1205/1213 y `DeadlockException` → mensaje de hueco no disponible | `test_un_interbloqueo_se_convierte_en_no_disponible` |
| TM8 | Information disclosure: el rechazo revela la otra cita | A01:2025 | `CitaNoAgendable` | mismo mensaje genérico de 025 CA4 | `ReprogramarCitaTest::test_el_hueco_ocupado_no_revela_la_otra_cita` |
| TM9 | Repudiation: no se sabe quién movió la cita | A09:2025 | historial | una entrada con `cambiado_por`, fechas y motivo | `test_la_reprogramacion_deja_una_sola_entrada_de_historial` |
| TM10 | Tampering: reabrir una cita cerrada o mover una empezada | A06:2025 | `EstadosCita::comprobarReprogramable` | estado y hora comprobados con la cita bloqueada | `test_una_cita_cerrada_o_empezada_no_se_reprograma` |
| TM11 | Information disclosure: el motivo se pinta sin escapar (XSS almacenado) | A05:2025 | `citas/show` | salida escapada de Blade | `ReprogramarCitaVistasTest::test_el_motivo_de_la_reprogramacion_se_muestra_escapado` |

Casos de abuso de la spec: CA8 → TM1, TM2; CA9 → TM3, TM4, TM5; CA10 → TM6, TM7.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: admin y asistente mueven la cita, mismo paciente, dentista, servicio y duración | `ReprogramacionCitas`, `CitasController`, rutas, vistas | Feature `ReprogramarCitaTest::test_la_administracion_reprograma_cualquier_cita`, `test_el_asistente_reprograma_citas_de_sus_dentistas`, `test_solo_cambian_fecha_y_hora_y_el_hueco_anterior_queda_libre` |
| CA2: paciente con ≥ 24 h reprograma; con menos, rechazo y sin botón | `EstadosCita`, `ClienteCitasController`, vistas del paciente | Feature `ReprogramarCitaPacienteTest::test_el_paciente_reprograma_con_24_horas_o_mas`, `test_el_hueco_nuevo_puede_empezar_en_menos_de_24_horas`, `test_con_menos_de_24_horas_se_rechaza_con_el_mensaje_de_plazo`, `test_el_detalle_oculta_reprogramar_fuera_de_plazo_y_explica_por_que`, `test_la_lista_muestra_el_aviso_corto_fuera_de_plazo` (A13) |
| CA3: paciente cancela solo con ≥ 24 h (reemplaza 026 CA7) | `EstadosCita::comprobar`, `ClienteCitasController::cancelar` | Feature `ReprogramarCitaPacienteTest::test_el_paciente_cancela_con_24_horas_o_mas` (caracterización: se rompe haciendo que `cancelar` rechace siempre), `test_el_paciente_no_cancela_con_menos_de_24_horas`; `EstadosCitaRolesTest`, `HistorialCitaTest` y `EstadosCitaVistasTest` actualizados (D6) |
| CA4: regla de hueco de la 025 sin contar la propia cita; pasada o igual rechazadas | `ReservaCitas::comprobarHueco`, `ReprogramacionCitas` | Feature `ReprogramarCitaTest::test_aplica_la_regla_de_agenda_al_nuevo_hueco` (sin horario, fuera de franja, solape, seguidas, canceladas no cuentan), `test_la_propia_cita_no_cuenta_como_conflicto`, `test_una_fecha_pasada_o_igual_se_rechaza`, `test_la_nueva_hora_se_interpreta_en_la_zona_de_la_clinica` (A4) |
| CA5: confirmada → agendada; cerradas y empezadas no se mueven; no se reabren | `EstadosCita::comprobarReprogramable`, `ReprogramacionCitas` | Feature `test_una_confirmada_vuelve_a_agendada`, `test_una_cita_cerrada_o_empezada_no_se_reprograma`, `ReprogramarCitaRolesTest::test_el_formulario_de_una_cita_que_no_se_puede_mover_muestra_el_motivo` (A6), `ReprogramarCitaPacienteTest::test_el_paciente_ve_el_motivo_si_su_cita_no_se_puede_mover` (A50), `test_el_paciente_no_ve_estados_internos` (A7); `EstadosCitaTransicionesTest::test_volver_a_agendada_nunca_se_permite` sin cambios en el cambio de estado (A3) y `test_el_mensaje_de_volver_a_agendada_depende_del_rol` (A18) |
| CA6: cancelar definitivamente desde la pantalla, con las reglas de la 026 | parcial `reprogramar`, `CitasController@updateEstado`, `ClienteCitasController@cancelar` | Feature `ReprogramarCitaVistasTest::test_la_seccion_no_reagendar_cancela_con_motivo`, `test_el_detalle_del_dentista_no_enlaza_a_reprogramar`, `test_cada_formulario_muestra_sus_valores_y_errores_en_su_seccion` (A5); `ReprogramarCitaPacienteTest::test_el_paciente_cancela_con_motivo_desde_la_pantalla` (GET y PATCH) · verificación manual: la sección «No reagendar» separada y marcada como irreversible, a 390 y 1440 px, contra `system.html` |
| CA7: una sola entrada de historial con autor, fechas, estado y motivo; los rechazos no dejan | migración, `ReprogramacionCitas`, `citas/show` | Feature `ReprogramarCitaTest::test_la_reprogramacion_deja_una_sola_entrada_de_historial`, `test_un_rechazo_no_deja_historial`; `ReprogramarCitaVistasTest::test_el_historial_muestra_la_reprogramacion` |
| CA8 (abuso): dentista, asistente fuera de alcance y paciente ajeno rechazados | rutas, Gate `reprogramar-cita` | Feature `ReprogramarCitaRolesTest` (GET y PATCH por rol, permitido y denegado); `test_fuera_de_alcance_tampoco_cancela_definitivamente` (caracterización: se rompe `CambiarEstadoCita::alcance`) |
| CA9 (abuso): formulario manipulado, mal formado o fuera de plazo | validación, `ReprogramacionCitas`, `EstadosCita` | Feature `ReprogramarCitaTest::test_los_campos_anadidos_a_mano_no_cambian_la_cita`, `test_una_fecha_u_hora_mal_formada_da_error_de_validacion`, `test_los_mensajes_de_validacion_estan_en_espanol` (A8); `ReprogramarCitaPacienteTest::test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano`, `test_los_mensajes_de_validacion_de_la_cancelacion_estan_en_espanol` (A8) |
| CA10 (abuso): dos peticiones por el mismo hueco, sin 500 | `ReprogramacionCitas` (bloqueo) | Feature `ReprogramarConcurrenteTest::test_la_reprogramacion_espera_al_bloqueo_del_dentista_y_responde_no_disponible`, `test_reprogramar_y_agendar_el_mismo_hueco_solo_guarda_uno`, `test_un_interbloqueo_se_convierte_en_no_disponible` |
| TM1: dentista o asistente fuera de alcance | rutas, Gate | Feature `ReprogramarCitaRolesTest::test_el_dentista_no_puede_reprogramar`, `test_el_asistente_no_reprograma_citas_de_dentistas_no_asignados`, `test_el_personal_no_puede_usar_la_ruta_del_paciente` (A50) |
| TM2: IDOR del paciente | `ClienteCitasController`, Gate | Feature `ReprogramarCitaRolesTest::test_el_paciente_no_encuentra_la_cita_de_otro` (con su cita propia → 200 en el mismo test) |
| TM3: campos añadidos a mano | controladores | Feature `ReprogramarCitaTest::test_los_campos_anadidos_a_mano_no_cambian_la_cita` |
| TM4: el paciente salta el plazo | `EstadosCita` | Feature `ReprogramarCitaPacienteTest::test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano` |
| TM5: entrada mal formada → 500 | validación | Feature `ReprogramarCitaTest::test_una_fecha_u_hora_mal_formada_da_error_de_validacion` |
| TM6: carrera por el mismo hueco | `ReprogramacionCitas` | Feature `ReprogramarConcurrenteTest::test_la_reprogramacion_espera_al_bloqueo_del_dentista_y_responde_no_disponible` |
| TM7: bloqueo agotado o interbloqueo → 500 | `ReprogramacionCitas` | Feature `ReprogramarConcurrenteTest::test_un_interbloqueo_se_convierte_en_no_disponible`, `test_la_cita_bloqueada_por_un_cambio_de_estado_responde_ocupada` (A21) |
| TM8: el rechazo revela la otra cita | `CitaNoAgendable` | Feature `ReprogramarCitaTest::test_el_hueco_ocupado_no_revela_la_otra_cita` |
| TM9: no se sabe quién movió la cita | historial | Feature `ReprogramarCitaTest::test_la_reprogramacion_deja_una_sola_entrada_de_historial` |
| TM10: reabrir una cerrada o mover una empezada | `EstadosCita` | Feature `ReprogramarCitaTest::test_una_cita_cerrada_o_empezada_no_se_reprograma` |
| TM11: motivo sin escapar | `citas/show` | Feature `ReprogramarCitaVistasTest::test_el_motivo_de_la_reprogramacion_se_muestra_escapado` |
| RNF: mensajes en español que dicen qué hacer | `CambioDeEstadoNoPermitido`, `CitaNoAgendable`, `ReprogramacionCitas::mensajesDeEntrada()` | Feature: los tests de CA2, CA4 y CA5 asertan el texto exacto; `test_los_mensajes_de_validacion_estan_en_espanol` y el de la cancelación del paciente (A8) |
| RNF: WCAG 2.1 AA en lo nuevo | parcial, vistas, `.btn-danger`, `.formulario-accesible` | Feature `ReprogramarCitaVistasTest::test_los_pares_de_contraste_cumplen_aa`, `test_las_reglas_css_nuevas_solo_usan_tokens`, `test_los_controles_tienen_foco_visible`, `test_el_estado_completada_se_ve_con_contraste_aa` (personal; A50), `test_lo_nuevo_solo_usa_el_sistema_en_la_pantalla_del_personal`, `…_en_la_pantalla_del_paciente`, `…_en_el_detalle_del_personal`, `…_en_el_detalle_del_paciente`, `…_en_la_lista_del_paciente`, `test_los_errores_del_personal_van_asociados_a_su_campo`, `test_los_errores_del_paciente_van_asociados_a_su_campo` · verificación manual: foco visible con teclado y lectura de errores a 390 y 1440 px contra `system.html` (A1, A2, A16) |
| RNF: ninguna entrada produce un 500 | validación, mapeo de bloqueos | Feature: los tests de CA9 y CA10 asertan respuesta distinta de 500 |

## Observabilidad
- Logs nuevos: ninguno. Los rechazos vuelven como mensaje y no se registran.
- Eventos de auditoría: el historial de la cita registra cada reprogramación (CA7) y cada
  cancelación (026 CA9); el evento formal queda para la 022 (P7 ❌ aceptado).
- Métricas y alertas: ninguna nueva.
- Cómo se verifica tras el deploy: en producción, reprogramar una cita ficticia como
  administración y comprobar en su detalle la entrada «Reprogramada del … al …»; después
  devolverla a su hora y comprobar la segunda entrada.

## Rollout
- Feature flag: no. El proyecto no tiene mecanismo de flags; el cambio es pequeño y se revierte
  con la vuelta atrás de la 030.
- Orden de despliegue: `deploy/desplegar.sh <tag>` hace la copia, ejecuta `migrate --force`
  (añade las dos columnas) y levanta el código nuevo en el mismo paso.
- Compatibilidad: la migración solo añade columnas con nulo; el código anterior las ignora, así
  que una vuelta atrás no necesita revertirla.
- Rollback: `bash deploy/volver.sh <tag anterior>` sin copia (no hace falta deshacer la
  migración). Las entradas de historial de reprogramaciones siguen en la tabla; la versión
  anterior las muestra como un cambio de estado sin fechas.
- Orden respecto a otras specs: se libera después de la 031; antes de `/release` se fusiona
  `main` en la rama (decisión del usuario, 2026-10-07).
- Métricas a vigilar tras el deploy: errores 500 en `laravel.log` y en el log de nginx durante
  15 minutos, y la verificación de Observabilidad.

## Decisiones (→ ADR si son arquitectónicas)
- **D1. Dónde vive la reprogramación.** Opciones: (a) un método más en `ReservaCitas`; (b) dentro
  de `EstadosCita`; (c) una clase nueva que use las dos. Decisión: (c) `ReprogramacionCitas`.
  `ReservaCitas` sigue siendo la única dueña de la regla del hueco (expone `comprobarHueco`) y
  `EstadosCita` la de estados y plazos. Así ninguna de las dos mezcla responsabilidades. Sin ADR:
  sigue el patrón de la 025 y la 026.
- **D2. Excluir la propia cita del solape.** `comprobarHueco(..., ?string $excluirCitaId)` añade
  `where('id', '!=', $excluirCitaId)` a la consulta de solape. Descartado: liberar el hueco
  cancelando antes, porque dejaría una ventana en la que otra reserva se lo queda.
- **D3. Orden de bloqueo.** Primero el dentista (`personal` `FOR UPDATE`), después la cita, como
  la reserva (dentista → citas). El cambio de estado solo bloquea la cita, así que no hay ciclo.
  Un bloqueo agotado o un interbloqueo → "no disponible", con la misma espera de 5 s. No se llama
  dentro de otra transacción (025/R9).
- **D4. Plazo del paciente.** Constante `EstadosCita::ANTELACION_PACIENTE_HORAS = 24`. Se acepta
  si `inicio − ahora ≥ 24 h`, con el inicio en la zona horaria de la clínica (`config/clinica.php`).
  Una constante y no un ajuste de entorno: es una regla de negocio aprobada en la spec.
- **D5. Cancelación definitiva.** Sin ruta nueva: el personal usa `PATCH …/estado` con
  `cancelada` y el paciente `PATCH /cliente/citas/{id}/cancelar`, que gana `motivo` opcional. Así
  las reglas de la 026 (roles, cerradas, historial) se aplican sin duplicarlas.
- **D6. Tests de la 026 que cambian** (contrato visible, decisión del usuario, 2026-10-07):
  `EstadosCitaRolesTest::test_el_paciente_cancela_su_cita_antes_de_que_empiece` pasa a una cita con
  ≥ 24 h (`test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` no cambia: una cita ya empezada
  sigue dando el mensaje de `yaEmpezo()`, A40); `EstadosCitaTransicionesTest` aserta el texto nuevo de `vuelta()` por rol;
  `HistorialCitaTest::test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor` mueve el caso del
  paciente a una cita con ≥ 24 h; `EstadosCitaVistasTest::test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo`
  mueve la cita pendiente a ≥ 24 h (y cambia `?filtro=pasadas` en su parte del listado, porque la
  cita deja de ser «pasada»); `test_el_paciente_ve_por_que_no_puede_cancelar_una_cita_que_ya_empezo`
  no cambia (A10, A40). No hay más tests afectados. Se cambian en la misma tarea que
  cambia la regla.
- **D7. Historial con columnas nuevas** en lugar de escribir las fechas en `motivo`: se pueden
  mostrar y filtrar sin interpretar texto. Las dos admiten nulo para no tocar las filas existentes.
- **D8. Dentista sin rutas.** Las rutas de reprogramar no se declaran en el grupo `/dentista`; en
  las de otros grupos, `role:` responde 403 antes del Gate, y el Gate también lo deniega.
- **D10. Dos formularios en la misma pantalla** (A5). Reprogramar y «No reagendar» envían `motivo`
  a rutas distintas (la de cancelar es la de la 026, D5). Cada uno lleva un campo oculto
  `formulario` con un valor fijo; la vista compara `old('formulario')` con ese literal para pintar
  el valor anterior y los errores (`motivo`, `estado`, mensaje `error`) solo en su sección, y los
  `id` son distintos. Ningún controlador lee `formulario`. Descartado: renombrar `motivo` en la
  cancelación, porque cambiaría el contrato de `PATCH …/estado` de la 026.
- **D11. Formulario accesible** (decisión del usuario, 2026-10-08; A1). Un componente acotado,
  `.formulario-accesible`, en lugar de cambiar `.field`, `.field-label` y `.btn-primary` en toda la
  app: cumple AA donde se usa y no cambia el aspecto de las demás vistas. Descartado: el arreglo
  global (DS1, DS2), que toca unas 60 vistas y queda para el objetivo de diseño del roadmap.
- **D12. Sin confirmación** en «No reagendar» (decisión del usuario, 2026-10-08; A28): la sección
  va separada, con título, el texto «No se puede deshacer» y el botón rojo; no se añade un
  `confirm()` nuevo (DS7).
- **D13. Zona horaria de la nueva fecha** (A4). La fecha y hora elegidas son hora de la clínica
  (`config('clinica.zona_horaria')`), como `EstadosCita::inicio`; «ya pasó» compara ese instante con
  el actual. Se guarda en `fecha_hora` igual que el agendado de la 025 (hora local sin zona).
- **D14. Campo del error del hueco** (A22): `hora`, como el agendado de la 025, también para fecha
  pasada o igual.
- **D15. Bloqueo agotado sobre la cita** (A21). Si la espera se agota al bloquear la cita (la tiene
  un cambio de estado en curso), el mensaje es `ocupada()` de la 026 y no «no disponible», que
  culparía al horario. Al bloquear el dentista o en el solape, sigue `noDisponible()`.
- **D16. Confirmada → agendada** (A3). Reemplaza 026 CA1 solo al reprogramar (spec 028 CA5,
  contradicción resuelta por el usuario); el cambio de estado sigue rechazando la vuelta a
  «Agendada», con el mensaje según el rol (A18).
- **D17. Plazo y citas ya empezadas** (A55). El «faltan menos» de spec CA3 se aplica a citas que
  aún no empiezan; una cita ya empezada se sigue rechazando con el mensaje de `yaEmpezo()` de 026 CA7
  (decisión del usuario, 2026-10-08; A40). Test sin cambios:
  `EstadosCitaRolesTest::test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo`.
- **D9. Excepciones de la constitución** P3, P7 y P8: las mismas que en la 026, con destino 014 y
  015, 022 y 020, y 023 (decisión del usuario, 2026-10-07).

## Impacto en arquitectura
- `docs/architecture.md` → módulo Agenda: `ReprogramacionCitas` y `comprobarHueco`; Gate
  `reprogramar-cita` en la lista de Gates; columnas nuevas de `historial_citas`.
- `docs/security.md` → superficie de ataque: rutas de reprogramar por rol.
- `docs/specs/026-estados-cita/spec.md` (Historial, en `/release`): CA7 reemplazado en el plazo
  por 028 CA3, y CA1 reemplazado al reprogramar por 028 CA5 (confirmada → agendada; A3).
- Historial de las specs que enviaban «reprogramar» o «reabrir» a otra spec (en `/release`; A24):
  004 (Fuera de alcance), 026 y 027 (reabrir una cancelada queda descartado por la 028, Decisiones).
- `docs/design/system.md`: componentes `.btn-danger` y «Formulario accesible» (1.3.0); DS1 y DS2
  anotan que avanzan en la pantalla de reprogramar.

## Riesgos y mitigaciones
- **Romper el agendado al extraer `comprobarHueco`.** Mitigación: refactor sin cambio de
  comportamiento en su propia tarea, con `ReglaAgendaTest`, `ReservaConcurrenteTest` y `AltaCitas*`
  en verde antes de seguir.
- **Cambio visible para el paciente** (deja de poder cancelar en las últimas 24 h). Mitigación:
  aviso en el panel con el motivo y qué hacer; CHANGELOG en `/release`.
- **Solape con la 014** en `CitasController@show`, `AlcanceClinico` y `AppServiceProvider`.
  Mitigación: la 028 solo añade un dato a `show` y un Gate nuevo; la 014 (`/plan 014 --fix`
  pendiente) lo tendrá en cuenta.
- **Zona horaria.** El plazo y "ya pasó" dependen de `CLINICA_ZONA_HORARIA`. Mitigación: los tests
  fijan "ahora" en UTC y comprueban el borde en la zona de la clínica, como en la 026.
- **Tests de concurrencia** que dejan datos si fallan a medias. Mitigación: `tearDown` con
  `finally` y borrado en orden de FK, como en la 025.
- **Dependencia del orden con la 031** (avisos de npm en `main`). Mitigación: fusionar `main`
  tras liberar la 031 y antes de `/release` (Rollout).
- **Residuos aceptados:** P3 (GET sin Gate → 014 y 015), P7 (sin evento formal → 022; 403 sin
  registro → 020), P8 (SQL con el motivo en el reporte por defecto → 023).
