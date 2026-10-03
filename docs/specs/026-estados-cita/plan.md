---
spec: 026-estados-cita
status: approved   # aprobado de nuevo por el usuario el 2026-10-02 tras /plan --fix A1,A2,A3,A5,A11,A12,A13,A17
created: 2026-10-02
---

# Plan · 026 Estados de la cita y quién puede cambiarlos

## Enfoque técnico
Un servicio nuevo, `App\Agenda\EstadosCita`, concentra las reglas de estado:
- la tabla de transiciones;
- los estados cerrados;
- la regla de tiempo ("ya empezó", en la zona horaria de la clínica);
- la escritura del cambio.

El cambio se hace en una transacción que bloquea la fila de la cita con `lockForUpdate()` **antes**
de leer su estado. Así, de dos cambios simultáneos, el segundo se evalúa contra el estado que dejó
el primero (CA10). En la misma transacción se escribe una sola entrada de historial con autor,
estado anterior, nuevo y motivo. Para eso se retira el trigger `trg_historial_cita`, que hoy
escribe otra sin autor.

Quién puede pedir cada transición lo decide el Gate `cambiar-estado-cita`, que también comprueba el
alcance; antes de validar la entrada, el Gate `gestionar-cita` comprueba solo el alcance, para que
el personal fuera de alcance reciba 403 con cualquier entrada. El rol va primero: un cambio que el
rol no puede pedir se rechaza por permisos aunque la cita esté cerrada. Para el alcance usa `App\Autorizacion\AlcanceClinico::dentistasDelAlcance()`, con la firma
que diseña la spec 014 (ADR 0003). Los tres puntos de entrada usan el mismo servicio y el mismo
Gate:
- el cambio de estado del personal;
- "eliminar" del admin;
- la cancelación del paciente.

Las vistas solo ofrecen las transiciones que el servicio daría por buenas para ese usuario, esa cita
y ese momento.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 026 `approved`, aprobada de nuevo el 2026-10-02 tras citar OB5 y ampliar CA11, y otra vez tras citar RS2 y ajustar CA2, CA6 y CA7 (`/analyze` ronda 1). |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene al menos un test que falla hoy (ver Estrategia de pruebas): hoy se acepta cualquier par de estados, no hay regla de rol, de tiempo ni de bloqueo, quedan dos filas de historial y `notas_internas` se guarda. Los casos que ya se cumplen hoy dentro de un CA se comprueban rompiendo el código un momento: el estado inexistente (CA11), el paciente con una cita en otro estado (CA7), el personal fuera de alcance (CA4, CA5) y el paciente en la ruta del personal. Todos usan `DatabaseTransactions` contra `dentissa_test`. La única escritura confirmada es la de la segunda conexión de `CambioEstadoConcurrenteTest`, que se borra al final, como en la 025. |
| P3 Autorización en servidor, por rol y recurso | ❌ aceptado: tres endpoints GET se modifican solo para pasar datos a la vista y conservan su autorización actual. `GET /{rol}/citas/{id}` (pasa `$disponibles`, `$empezada` e `$inicio`) verifica el rol con middleware y el alcance con `autorizadoParaCita`, sin Gate → spec 014. `GET /cliente/citas` y `GET /cliente/citas/{id}` (pasan `puede_cancelar`) siguen en `/cliente` sin `role:cliente`, con el alcance en la consulta por `paciente_id` → spec 015 — aprobado por el usuario el 2026-10-02 | Los tres endpoints que cambian estado sí cumplen. Verifican el rol con middleware: `role:admin\|dentista\|asistente` ya en sus grupos, y `role:cliente` nuevo en `PATCH /cliente/citas/{id}/cancelar`. El alcance se verifica con el Gate `gestionar-cita` antes de validar, y la transición con `cambiar-estado-cita`. `EstadosCitaRolesTest` cubre, por rol y acción, el caso permitido y el denegado (403; 404 para la cita de otro paciente), también en "eliminar". `autorizadoParaCita` y `aplicarAlcanceRol` delegan en `AlcanceClinico` (A11), así que el alcance de citas tiene una sola regla. |
| P4 Entrada validada y SQL con bindings | ✅ | `estado` → `required\|string\|in:` de los 6 estados; `motivo` → `nullable\|string\|max:500`; `notas_internas` ya no se lee. Consultas con Query Builder y bindings. La migración usa `DB::unprepared` con DDL fijo, sin entrada del usuario. En Blade, el motivo y los nombres se muestran solo con la salida escapada de Blade (dobles llaves). |
| P5 Nada sensible en el repo | ✅ | Código, tests y documentación; `.env.example` solo gana `CLINICA_ZONA_HORARIA` con su valor por defecto público. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-02: `composer audit --locked` sin críticas ni altas (un aviso medio de `phpseclib`, ignorado por la política del CI; dos bajos cubiertos por EX1) y `npm audit` en 0. |
| P7 Auditoría de datos sensibles | ❌ aceptado: el cambio de estado y su motivo quedan en el historial de la cita con autor, pero sin evento de auditoría formal en el almacén solo-anexado (→ spec 022, como ya fija la spec); los 403 del Gate `cambiar-estado-cita` no se registran (registro central de accesos denegados → spec 020) — aprobado por el usuario el 2026-10-02 | La infraestructura de auditoría (spec 020) aún no está en `main`. Los cambios de estado no figuran en la lista de P7 (que nombra notas de citas); se tratan igual por llevar un motivo libre. |
| P8 Correlación y logs limpios | ❌ aceptado: si el `insert` del historial o el `update` de la cita fallan con una `QueryException` distinta de 1205/1213, el reporte por defecto de Laravel registra el SQL con sus bindings, motivo incluido; la redacción de bindings para toda la aplicación la hace la spec 023 — aprobado por el usuario el 2026-10-02 | Sin logs nuevos. Los rechazos y el bloqueo agotado se devuelven como mensaje, sin registrar nada; `HistorialCitaTest::test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` lo comprueba. |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Principio añadido en la constitución 1.1.0 (2026-10-02), después de liberar esta spec en v0.3.0. Sus vistas (`citas/show`, `cliente-show`, `cliente-index`) quedan como código previo; lo que no cumple está en la deuda DS1–DS7 de `docs/design/system.md`. |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/EstadosCita.php` (nuevo) | `__construct(private ConnectionInterface $db)`, inyectado por el contenedor como `ReservaCitas`, para que los tests puedan pasar una conexión simulada. Constantes `TRANSICIONES` (estado nuevo → estados de origen), `CERRADOS` (`completada`, `cancelada`, `no_asistio`) y `REQUIEREN_INICIO` (`en_curso`, `no_asistio`). `cambiar(string $citaId, string $nuevo, Usuario $actor, ?string $motivo): void` hace en una transacción: `lockForUpdate()` de la cita; si ya no existe, 404; si está cerrada, `cerrada`; si el destino es `agendada`, `vuelta`; si el origen no está en `TRANSICIONES[$nuevo]`, `transicion`; si es `en_curso`/`no_asistio` y aún no empieza, `antesDeTiempo`; si el actor es paciente, cancela y ya empezó, `yaEmpezo`; después `update` de `estado` y `actualizado_en`, e `insert` en `historial_citas`. Los errores 1205/1213 y `DeadlockException` → `ocupada`; cualquier otro error se relanza. `disponibles(Usuario, object $cita): array` devuelve, en el orden de la tabla, los estados nuevos que pasan el Gate y las reglas sin bloquear (para las vistas). `haEmpezado(object $cita): bool` compara `fecha_hora`, interpretada en `config('clinica.zona_horaria')`, con `now()` en esa zona (borde incluido: a la hora exacta ya empezó) | Medio: es la regla central; tests de cada regla y de concurrencia |
| `app/Agenda/CambioDeEstadoNoPermitido.php` (nuevo) | `RuntimeException` con `public readonly string $tipo` (`cerrada`, `vuelta`, `transicion`, `antesDeTiempo`, `yaEmpezo`, `ocupada`) y fábricas con los mensajes de Contratos | Bajo |
| `app/Autorizacion/AlcanceClinico.php` (nuevo) | Solo `personalId(Usuario): ?string` y `dentistasDelAlcance(Usuario): ?array` (`null` = admin sin restricción; dentista → `[propio]`; asistente → asignados; otro rol → `[]`), con la firma del plan de la 014. La 014 añadirá el resto de métodos (D3). `CitasController` delega en ella su alcance (A11) | Bajo: lógica idéntica a la de los helpers actuales de `CitasController` |
| `app/Autorizacion/CambiarEstadoCita.php` (nuevo) | `alcance(Usuario $usuario, object $cita): Response`, que solo comprueba el alcance, y `autorizar(Usuario $usuario, object $cita, string $nuevo): Response`, que comprueba el alcance y la matriz. Personal fuera de alcance → `deny('No tienes acceso a esta cita.')` (403, como hoy). Paciente con una cita ajena → `denyAsNotFound()` (404, como hoy). Matriz por rol: admin → los 5 estados destino; dentista → `confirmada`, `en_curso`, `completada`, `no_asistio`; asistente → `confirmada`, `cancelada`, `no_asistio`; cliente → `cancelada`. Fuera de la matriz → `deny(<mensaje del rol>)` (403), también si la cita está cerrada ("rol primero", supuesto de la spec). Si el destino no es un estado de `TRANSICIONES` (solo `agendada`), `autorizar` comprueba solo el alcance y deja el rechazo al servicio (`cerrada` o `vuelta`) | Medio: tests por rol |
| `app/Providers/AppServiceProvider.php` | `Gate::define('gestionar-cita', [CambiarEstadoCita::class, 'alcance'])` y `Gate::define('cambiar-estado-cita', [CambiarEstadoCita::class, 'autorizar'])` en `boot()` | Bajo: la 020 y la 014 añaden más registros al mismo método |
| `app/Http/Controllers/CitasController.php` | `show`: quita `$estados` y pasa `$disponibles = EstadosCita::disponibles(...)`, `$empezada` y `$inicio` para los avisos. `updateEstado(Request, EstadosCita)`: carga la cita (404), `Gate::authorize('gestionar-cita', $cita)` (403 fuera de alcance, antes de validar), valida `estado` y `motivo` (sin `notas_internas`), `Gate::authorize('cambiar-estado-cita', [$cita, $estado])` y llama a `cambiar`; `CambioDeEstadoNoPermitido` → `back()->withErrors(['estado' => $e->getMessage()])`. `destroy(string $id, EstadosCita)`: 404 si no existe, `gestionar-cita` y `cambiar-estado-cita` con `cancelada`, el mismo servicio con motivo `null` y, si se rechaza, el mismo `back()->withErrors`. `personalIdPropio` y `dentistaIdsAsistente` pasan a delegar en `AlcanceClinico::personalId()` y `dentistasDelAlcance()`, así que `autorizadoParaCita`, `aplicarAlcanceRol` y los desplegables de `index` y `create` usan la misma regla que el Gate, sin cambiar su comportamiento (A11; la 014 no los toca) | Medio |
| `app/Http/Controllers/ClienteCitasController.php` | `cancelar(string $id, EstadosCita)`: carga la cita, `Gate::authorize('cambiar-estado-cita', [$cita, 'cancelada'])` (ajena → 404) y llama a `cambiar` con el paciente como actor. Los tipos `cerrada` y `transicion` → `back()->with('error', 'Esta cita ya no se puede cancelar. Si necesitas ayuda, llama a la clínica.')`; los demás tipos, con su mensaje. `index` y `show` pasan `puede_cancelar` por cita (`in_array(estado, [agendada, confirmada]) && ! haEmpezado`) | Bajo |
| `routes/web.php` | `->middleware('role:cliente')` en la ruta `cliente.citas.cancelar` | Bajo: el personal recibe 403 en lugar de 404 |
| `config/clinica.php` (nuevo), `.env.example` | `'zona_horaria' => env('CLINICA_ZONA_HORARIA', 'America/Mexico_City')`; la variable, documentada en `.env.example` | Bajo |
| `database/migrations/2026_10_02_000000_retirar_trigger_historial_cita.php` (nuevo) | `up`: `DROP TRIGGER IF EXISTS trg_historial_cita`. `down`: recrea el trigger con el mismo cuerpo que `database/dentissa.sql` (que no se toca: ruta protegida) | Medio: cambia quién escribe el historial; ver Rollout |
| `resources/views/citas/show.blade.php` | En "Cambiar estado" (`id="estado"`): una opción por cada estado de `$disponibles`, sin el estado actual, dentro de `<fieldset>` con `<legend>`; `@error('estado')` visible. Si no hay opciones, el aviso de "Qué ve cada rol" en lugar del formulario. Botón "Cancelar cita" del admin solo si `cancelada` está en `$disponibles`. `id="historial"` en el historial, que muestra autor, `anterior → nuevo`, motivo y fecha | Bajo: revisar con `design` |
| `resources/views/citas/cliente-show.blade.php`, `resources/views/citas/cliente-index.blade.php` | El botón "Cancelar" depende de `puede_cancelar` en lugar de solo el estado | Bajo |
| `tests/Feature/Concerns/EscenarioEstadosCita.php` (nuevo) | Fixture: admin; dentistas D1 y D2; asistente A1 asignado a D1; pacientes P1 y P2 (con usuario cliente); helpers `cita(dentista, paciente, estado, inicio)`, `cambiar(usuario, cita, estado, motivo, extra)`, `entradasDeHistorial(cita)` y `seccion(html, id)`, que extrae por XPath el nodo con ese `id` | Bajo |
| `tests/Feature/EstadosCitaTransicionesTest.php` (nuevo) | CA1, CA2, CA3, CA8, CA11 | — |
| `tests/Feature/EstadosCitaRolesTest.php` (nuevo) | CA4–CA7 y P3 | — |
| `tests/Feature/HistorialCitaTest.php` (nuevo) | CA9, TM7, TM9, TM11 | — |
| `tests/Feature/CambioEstadoConcurrenteTest.php` (nuevo) | CA10, TM4, TM5 | — |
| `tests/Feature/EstadosCitaVistasTest.php` (nuevo) | "Qué ve cada rol" | — |

## Contratos y datos

### Endpoints
| Método y ruta | Rol | Entrada | Éxito | Rechazos |
|---|---|---|---|---|
| `PATCH /{admin\|dentista\|asistente}/citas/{id}/estado` | personal | `estado` (uno de los 6), `motivo` opcional (≤ 500); `notas_internas` se ignora | 302 `back()` con `success` "Estado actualizado correctamente." | 404 cita inexistente · 403 fuera de alcance o rol sin esa transición · 302 `back()` con `errors.estado` (validación o regla de estado) |
| `DELETE /admin/citas/{id}` | admin | — | 302 a `admin.citas` con "Cita cancelada." | 404 · 302 `back()` con `errors.estado` |
| `PATCH /cliente/citas/{id}/cancelar` | cliente (`role:cliente` nuevo) | — | 302 `back()` con "Tu cita fue cancelada." | 403 otro rol · 404 cita de otro paciente · 302 `back()` con `error` |

### Mensajes (`CambioDeEstadoNoPermitido` y Gate)
| Caso | Mensaje |
|---|---|
| `cerrada` (CA2) | "Esta cita ya está {completada \| cancelada \| marcada como no asistida} y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva." |
| `vuelta` (CA3, CA11; destino `agendada` con la cita abierta) | "Una cita no puede volver a «Agendada». Si hay que moverla, cancélala y agenda una nueva." |
| `transicion` (CA3) | "Solo se puede pasar a «{Nuevo}» desde «{Origen}»." o, con varios orígenes, "…desde «Agendada» o «Confirmada»." |
| `antesDeTiempo` (CA8) | "La cita aún no empieza. Podrás marcarla como «{Nuevo}» a partir de las {H:i} del {d/m/Y}." |
| `yaEmpezo` (CA7) | "Tu cita ya empezó y no se puede cancelar. Si necesitas ayuda, llama a la clínica." |
| `ocupada` (bloqueo agotado o interbloqueo) | "Otra persona está cambiando esta cita en este momento. Recarga la página y vuelve a intentarlo." |
| Gate, dentista que cancela (CA4) | "Como dentista no puedes cancelar citas. Pide a tu asistente o a la administración que la cancelen." |
| Gate, asistente que inicia o completa (CA5) | "Como asistente no puedes iniciar ni completar citas. Eso lo hace el dentista o la administración." |
| Gate, paciente con otro estado | "Solo puedes cancelar tus citas." |
| Validación de `estado` (CA11) | "Elige un estado válido." |

Etiquetas de estado: Agendada, Confirmada, En curso, Completada, Cancelada, No asistió.

### Transiciones
| Estado nuevo | Desde | Regla de tiempo |
|---|---|---|
| confirmada | agendada | — |
| en_curso | confirmada | ya empezó |
| completada | en_curso | — |
| cancelada | agendada, confirmada | solo el paciente: aún no empieza |
| no_asistio | agendada, confirmada | ya empezó |

### Datos
- Sin cambios de esquema. Cada cambio aceptado inserta en `historial_citas` una fila con:
  - `id`: UUID;
  - `cita_id`;
  - `cambiado_por`: id del usuario actor;
  - `estado_anterior`: leído bajo el bloqueo;
  - `estado_nuevo`;
  - `motivo`: `null` si llega vacío;
  - `fecha`: `now()`.
- Migración que retira `trg_historial_cita`; su `down` lo recrea. Las filas antiguas sin autor se
  conservan y se muestran como "Sistema".

### Qué ve cada rol
Revisar con la skill `design` antes de `/implement` (avisos, `fieldset`/`legend` y foco en el error).

| Pantalla y elemento | Admin | Dentista (cita propia) | Asistente (cita de su dentista) | Paciente (cita propia) | Test |
|---|---|---|---|---|---|
| Detalle: opciones de "Cambiar estado" | las transiciones de la tabla desde el estado actual que cumplen la regla de tiempo | igual, sin `cancelada` | igual, sin `en_curso` ni `completada` | — | `EstadosCitaVistasTest::test_el_formulario_solo_ofrece_las_transiciones_del_rol_y_del_estado` |
| Detalle: cita cerrada | aviso "Esta cita está {estado} y ya no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva." en lugar del formulario | igual | igual | — | `test_una_cita_cerrada_no_ofrece_cambios_y_explica_como_seguir` |
| Detalle: cita abierta sin opciones ahora (p. ej. dentista con una confirmada que aún no empieza) | — (siempre puede cancelar) | aviso "Podrás marcarla como en curso o no asistida a partir de las {H:i} del {d/m/Y}." | si está `en_curso`: "Solo el dentista o la administración pueden completar esta cita." | — | `test_antes_de_la_hora_se_explica_desde_cuando_se_puede_iniciar`, `test_el_asistente_ve_quien_puede_completar_una_cita_en_curso` |
| Detalle: botón "Cancelar cita" (barra superior) | solo con la cita agendada o confirmada | no | no | — | `test_el_boton_cancelar_del_admin_solo_aparece_en_citas_abiertas` |
| Detalle: historial | autor, anterior → nuevo, motivo, fecha | igual | igual | — | `HistorialCitaTest::test_el_historial_muestra_autor_estados_y_motivo` |
| Mis citas y detalle del paciente: botón "Cancelar" | — | — | — | con la cita agendada o confirmada que aún no empieza | `test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo` |

El panel del paciente (`dashboard/cliente`, spec 012) no cambia: sigue mostrando "Cancelar" solo con
la próxima cita agendada, y el servidor aplica las mismas reglas (D8).

## Estrategia de pruebas
Todos los tests son Feature con PHPUnit, `DatabaseTransactions` y la fixture
`EscenarioEstadosCita`. La hora se fija con `Carbon::setTestNow` en UTC y las citas se crean en hora
local de la clínica. Las aserciones sobre HTML se hacen sobre la sección por `id` (`estado`,
`historial`), no sobre toda la página.

- **Transiciones** (`EstadosCitaTransicionesTest`). Falla hoy porque todo se acepta.
  - `test_solo_se_aceptan_las_transiciones_de_la_tabla` (CA1): recorre las 36 parejas origen → destino como admin, con la cita ya empezada, y exige que se acepten exactamente las 7 de la tabla y que en las demás la cita no cambie.
  - `test_una_cita_cerrada_no_cambia_de_estado` (CA2): desde los 3 cerrados, con el mensaje `cerrada`.
  - `test_una_transicion_no_permitida_indica_desde_que_estados_se_permite` (CA3): agendada → completada y en curso → cancelada, con el mensaje exacto.
  - `test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza` y `test_a_la_hora_exacta_de_inicio_ya_se_puede_iniciar` (CA8).
  - `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica` (CA8; solo flujos del personal, "iniciar" como dentista): una cita de las 10:00 locales con `now` = 15:30 UTC (09:30 en Ciudad de México) aún no empieza; con 16:00 UTC ya empezó. Falla si se compara en UTC.
  - `test_un_estado_inexistente_se_rechaza_sin_error_500` (CA11; ya se cumple hoy y se comprueba rompiendo la regla `in:`).
  - `test_reabrir_una_cita_cancelada_se_rechaza` (CA11): como admin, cancelada → agendada y cancelada → confirmada reciben el mensaje `cerrada`.
  - `test_volver_a_agendada_nunca_se_permite` (CA3, CA11): como admin, confirmada → agendada recibe el mensaje `vuelta`, sin cambios.
  - `test_las_notas_internas_enviadas_con_el_estado_no_cambian` (CA11, TM6): para admin, dentista y asistente.
- **Roles** (`EstadosCitaRolesTest`):
  - `test_el_dentista_confirma_inicia_completa_y_marca_no_asistio_en_sus_citas` (CA4);
  - `test_el_dentista_no_puede_cancelar_su_cita` (CA4, 403 sin cambios; falla hoy);
  - `test_el_dentista_no_puede_cambiar_la_cita_de_otro_dentista` (403, ya se cumple; rompiendo el alcance);
  - `test_fuera_de_alcance_con_estado_invalido_responde_403` (A5: dentista y asistente fuera de alcance con `estado` vacío o inexistente → 403 sin cambios; ya se cumple hoy y se comprueba moviendo un momento la validación antes del alcance);
  - `test_un_rol_sin_la_transicion_recibe_403_aunque_la_cita_este_cerrada` ("rol primero": dentista con una cita completada pide `cancelada` → 403 con el mensaje del rol);
  - `test_el_alcance_del_listado_y_del_detalle_no_cambia` (A11, caracterización: el dentista solo ve sus citas en el listado y recibe 403 en el detalle de otra; el asistente igual con dentistas no asignados; se comprueba rompiendo un momento `dentistasDelAlcance`);
  - `test_el_asistente_confirma_cancela_y_marca_no_asistio_en_citas_de_sus_dentistas` (CA5);
  - `test_el_asistente_no_puede_iniciar_ni_completar` (CA5, 403; falla hoy);
  - `test_el_asistente_no_puede_cambiar_citas_de_dentistas_no_asignados` (403);
  - `test_eliminar_cita_es_una_cancelacion_con_las_mismas_reglas` (CA6: agendada → cancelada; completada → mensaje `cerrada`; falla hoy);
  - `test_eliminar_una_cita_inexistente_responde_404`;
  - `test_solo_el_admin_puede_eliminar_una_cita` (P3: dentista, asistente y paciente → 403 en `DELETE /admin/citas/{id}`, sin cambios; ya se cumple con `role:admin`, se comprueba rompiendo un momento el middleware);
  - `test_el_paciente_cancela_su_cita_antes_de_que_empiece` (CA7);
  - `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` (CA7; falla hoy): una cita de las 10:00 locales con `now` = 16:30 UTC se rechaza con `yaEmpezo`; con 15:30 UTC se cancela (comprueba también la zona horaria en el flujo del paciente);
  - `test_el_paciente_no_puede_cancelar_una_cita_en_otro_estado` (CA7, ya se cumple);
  - `test_el_paciente_no_puede_cancelar_la_cita_de_otro` (404);
  - `test_el_personal_no_puede_usar_la_cancelacion_del_paciente` (403; falla hoy, responde 404);
  - `test_el_paciente_no_puede_usar_el_cambio_de_estado_del_personal` (403, ya se cumple).
- **Historial** (`HistorialCitaTest`):
  - `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor` (CA9): por cada rol y por "eliminar", con y sin motivo. Falla hoy: sin autor, y dos filas con motivo.
  - `test_un_rechazo_no_deja_entrada_en_el_historial`;
  - `test_el_historial_muestra_autor_estados_y_motivo` (CA9);
  - `test_el_motivo_se_muestra_escapado` (TM11);
  - `test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` (P8, TM9): escucha `MessageLogged`, con un control que sí registra para probar que el escuchador funciona.
- **Concurrencia** (`CambioEstadoConcurrenteTest`). Usa una segunda conexión `segunda_conexion` que confirma sus propios datos y los borra en `tearDown` con `finally`, como en la 025.
  - `test_un_cambio_se_evalua_contra_el_estado_ya_guardado_por_otro` (CA10): la segunda conexión cancela y confirma; después, "iniciar" desde el formulario anterior recibe el mensaje `cerrada`. Falla hoy.
  - `test_el_bloqueo_de_la_cita_se_toma_antes_de_leer_su_estado` (CA10, TM4): `DB::listen` exige que la primera consulta sobre `citas` dentro de la transacción sea el `select … for update` y que no haya otra lectura del estado.
  - `test_mientras_otro_tiene_la_cita_bloqueada_no_hay_error_500` (TM5): la segunda conexión mantiene `FOR UPDATE`; el test fija `innodb_lock_wait_timeout = 1` en la conexión de la app y espera el mensaje `ocupada`, sin cambios.
  - `test_un_interbloqueo_se_convierte_en_mensaje` (TM5): conexión simulada con 1205, 1213 y `DeadlockException`.
  - `test_otro_error_de_base_de_datos_no_se_oculta`: el 1452 se relanza.
- **Vistas** (`EstadosCitaVistasTest`): una prueba por fila de "Qué ve cada rol".
- **Requisito de tiempo:** la suite de la 026 debe correr en menos de 10 s; se mide en T040 con `--filter` sobre los 5 archivos.
- **Base de tests:** la migración se aplica a `dentissa_test` con
  `docker compose exec -e DB_DATABASE=dentissa_test app php artisan migrate --force`. El CI ya
  ejecuta `migrate` tras cargar el volcado.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un dentista cancela o un asistente inicia o completa manipulando la petición | A01:2025 | `updateEstado` | Gate `cambiar-estado-cita` con matriz por rol | `EstadosCitaRolesTest::test_el_dentista_no_puede_cancelar_su_cita`, `test_el_asistente_no_puede_iniciar_ni_completar` |
| TM2 | Elevation / divulgación: cambiar el estado de una cita fuera del alcance (otro dentista, otro paciente) | A01:2025 | los 3 endpoints | Alcance en el Gate (`AlcanceClinico`): 403 al personal, 404 al paciente | `test_el_dentista_no_puede_cambiar_la_cita_de_otro_dentista`, `test_el_asistente_no_puede_cambiar_citas_de_dentistas_no_asignados`, `test_el_paciente_no_puede_cancelar_la_cita_de_otro`, `test_fuera_de_alcance_con_estado_invalido_responde_403` |
| TM3 | Tampering: reabrir una cita cerrada o saltarse estados (p. ej. reocupar un hueco ya liberado) | A04:2025 | `EstadosCita::cambiar` | Tabla de transiciones y estados cerrados, evaluados en el servidor | `EstadosCitaTransicionesTest::test_solo_se_aceptan_las_transiciones_de_la_tabla`, `test_reabrir_una_cita_cancelada_se_rechaza`, `test_volver_a_agendada_nunca_se_permite` |
| TM4 | Tampering: dos cambios incompatibles simultáneos se aplican los dos | A04:2025 | `EstadosCita::cambiar` | `lockForUpdate()` de la cita antes de leer su estado, en la misma transacción que la escritura | `CambioEstadoConcurrenteTest::test_un_cambio_se_evalua_contra_el_estado_ya_guardado_por_otro`, `test_el_bloqueo_de_la_cita_se_toma_antes_de_leer_su_estado` |
| TM5 | Denial of service: bloqueo agotado o interbloqueo que acaba en 500 | A10:2025 | `EstadosCita::cambiar` | 1205/1213 y `DeadlockException` → mensaje `ocupada`, sin cambios ni logs | `test_mientras_otro_tiene_la_cita_bloqueada_no_hay_error_500`, `test_un_interbloqueo_se_convierte_en_mensaje` |
| TM6 | Tampering: sobrescribir las notas internas a través del cambio de estado | A01:2025 | `updateEstado` | `notas_internas` no se valida ni se escribe | `EstadosCitaTransicionesTest::test_las_notas_internas_enviadas_con_el_estado_no_cambian` |
| TM7 | Repudiation: un cambio sin autor, o con dos versiones contradictorias en el historial | A09:2025 | `EstadosCita::cambiar`, migración | Una sola fila con autor en la misma transacción; trigger retirado | `HistorialCitaTest::test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`, `test_un_rechazo_no_deja_entrada_en_el_historial` |
| TM8 | Tampering: marcar en curso o no asistida antes de la hora, o cancelar como paciente una cita ya empezada | A04:2025 | `EstadosCita::haEmpezado` | Hora del servidor en la zona de la clínica; el cliente no envía la hora | `test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza`, `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica`, `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` |
| TM9 | Information disclosure: el motivo (texto libre, posible dato de salud) en los logs | A09:2025 | `EstadosCita`, controladores | Sin logs nuevos; residuo de P8 aceptado (→ 023) | `HistorialCitaTest::test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` |
| TM10 | Elevation: usar la ruta de otro rol (el personal en la cancelación del paciente, el paciente en la del personal) | A01:2025 | `routes/web.php` | `role:cliente` en la cancelación del paciente; `role:` existentes en el personal | `test_el_personal_no_puede_usar_la_cancelacion_del_paciente`, `test_el_paciente_no_puede_usar_el_cambio_de_estado_del_personal`, `test_solo_el_admin_puede_eliminar_una_cita` |
| TM11 | Tampering (XSS): un motivo con HTML que se ejecuta al ver el historial | A05:2025 | `citas/show.blade.php` | Salida escapada de Blade (dobles llaves) | `HistorialCitaTest::test_el_motivo_se_muestra_escapado` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: solo las 7 transiciones de la tabla | `EstadosCita`, `CitasController@updateEstado` | Feature `EstadosCitaTransicionesTest::test_solo_se_aceptan_las_transiciones_de_la_tabla` |
| CA2: estados cerrados rechazados con mensaje | `EstadosCita`, `CambioDeEstadoNoPermitido` | Feature `test_una_cita_cerrada_no_cambia_de_estado`, `EstadosCitaVistasTest::test_una_cita_cerrada_no_ofrece_cambios_y_explica_como_seguir` |
| CA3: otra transición, con los estados de origen permitidos | `EstadosCita`, `CambioDeEstadoNoPermitido` | Feature `test_una_transicion_no_permitida_indica_desde_que_estados_se_permite`, `test_volver_a_agendada_nunca_se_permite` |
| CA4: dentista sin cancelar | Gate `cambiar-estado-cita`, `CambiarEstadoCita` | Feature `EstadosCitaRolesTest::test_el_dentista_confirma_inicia_completa_y_marca_no_asistio_en_sus_citas`, `test_el_dentista_no_puede_cancelar_su_cita`, `test_el_dentista_no_puede_cambiar_la_cita_de_otro_dentista`, `test_un_rol_sin_la_transicion_recibe_403_aunque_la_cita_este_cerrada`, `test_fuera_de_alcance_con_estado_invalido_responde_403`, `test_el_alcance_del_listado_y_del_detalle_no_cambia` |
| CA5: asistente sin iniciar ni completar | Gate, `CambiarEstadoCita`, `AlcanceClinico` | Feature `test_el_asistente_confirma_cancela_y_marca_no_asistio_en_citas_de_sus_dentistas`, `test_el_asistente_no_puede_iniciar_ni_completar`, `test_el_asistente_no_puede_cambiar_citas_de_dentistas_no_asignados` |
| CA6: admin y "eliminar" como cancelación | `CitasController@destroy`, `EstadosCita` | Feature `test_eliminar_cita_es_una_cancelacion_con_las_mismas_reglas`, `test_eliminar_una_cita_inexistente_responde_404`, `test_solo_el_admin_puede_eliminar_una_cita`, `EstadosCitaTransicionesTest::test_solo_se_aceptan_las_transiciones_de_la_tabla` (como admin), `EstadosCitaVistasTest::test_el_boton_cancelar_del_admin_solo_aparece_en_citas_abiertas` |
| CA7: el paciente cancela antes de que empiece | `ClienteCitasController@cancelar`, `EstadosCita::haEmpezado`, `routes/web.php`, vistas del paciente | Feature `test_el_paciente_cancela_su_cita_antes_de_que_empiece`, `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo`, `test_el_paciente_no_puede_cancelar_una_cita_en_otro_estado`, `test_el_paciente_no_puede_cancelar_la_cita_de_otro`, `test_el_personal_no_puede_usar_la_cancelacion_del_paciente`, `EstadosCitaVistasTest::test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo` |
| CA8: en curso y no asistió solo tras la hora de inicio | `EstadosCita::haEmpezado`, `config/clinica.php` | Feature `test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza`, `test_a_la_hora_exacta_de_inicio_ya_se_puede_iniciar`, `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica`, `EstadosCitaVistasTest::test_antes_de_la_hora_se_explica_desde_cuando_se_puede_iniciar` |
| CA9: una entrada con autor por cambio, visible en el historial | `EstadosCita::cambiar`, migración, `citas/show.blade.php` | Feature `HistorialCitaTest::test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`, `test_un_rechazo_no_deja_entrada_en_el_historial`, `test_el_historial_muestra_autor_estados_y_motivo` |
| CA10 (abuso): cambios simultáneos, solo uno y sin 500 | `EstadosCita::cambiar` (bloqueo) | Feature `CambioEstadoConcurrenteTest::test_un_cambio_se_evalua_contra_el_estado_ya_guardado_por_otro`, `test_el_bloqueo_de_la_cita_se_toma_antes_de_leer_su_estado`, `test_mientras_otro_tiene_la_cita_bloqueada_no_hay_error_500`, `test_un_interbloqueo_se_convierte_en_mensaje` |
| CA11 (abuso): estado inexistente, reapertura y notas internas | `CitasController@updateEstado` (validación), `EstadosCita` | Feature `test_un_estado_inexistente_se_rechaza_sin_error_500`, `test_reabrir_una_cita_cancelada_se_rechaza`, `test_volver_a_agendada_nunca_se_permite`, `test_las_notas_internas_enviadas_con_el_estado_no_cambian` |
| TM1 | Gate | `test_el_dentista_no_puede_cancelar_su_cita`, `test_el_asistente_no_puede_iniciar_ni_completar` |
| TM2 | Gates `gestionar-cita` y `cambiar-estado-cita`, `AlcanceClinico` | `test_el_dentista_no_puede_cambiar_la_cita_de_otro_dentista`, `test_el_asistente_no_puede_cambiar_citas_de_dentistas_no_asignados`, `test_el_paciente_no_puede_cancelar_la_cita_de_otro`, `test_fuera_de_alcance_con_estado_invalido_responde_403` |
| TM3 | `EstadosCita` | `test_solo_se_aceptan_las_transiciones_de_la_tabla`, `test_reabrir_una_cita_cancelada_se_rechaza`, `test_volver_a_agendada_nunca_se_permite` |
| TM4 | `EstadosCita` | `test_un_cambio_se_evalua_contra_el_estado_ya_guardado_por_otro`, `test_el_bloqueo_de_la_cita_se_toma_antes_de_leer_su_estado` |
| TM5 | `EstadosCita` | `test_mientras_otro_tiene_la_cita_bloqueada_no_hay_error_500`, `test_un_interbloqueo_se_convierte_en_mensaje`, `test_otro_error_de_base_de_datos_no_se_oculta` |
| TM6 | `updateEstado` | `test_las_notas_internas_enviadas_con_el_estado_no_cambian` |
| TM7 | `EstadosCita`, migración | `test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`, `test_un_rechazo_no_deja_entrada_en_el_historial` |
| TM8 | `EstadosCita::haEmpezado` | `test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza`, `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica`, `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo` |
| TM9 | sin logs nuevos | `test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` |
| TM10 | `routes/web.php` | `test_el_personal_no_puede_usar_la_cancelacion_del_paciente`, `test_el_paciente_no_puede_usar_el_cambio_de_estado_del_personal`, `test_solo_el_admin_puede_eliminar_una_cita` |
| TM11 | `citas/show.blade.php` | `test_el_motivo_se_muestra_escapado` |

## Observabilidad
- **Logs nuevos:** ninguno. Los rechazos (regla de estado, Gate, bloqueo) se devuelven al usuario
  sin escribir en el log. Residuo de P8 aceptado: una `QueryException` inesperada sigue el reporte
  por defecto (→ spec 023).
- **Historial de negocio (sección Auditoría de la spec):**
  - Cada cambio aceptado deja una entrada con autor, anterior, nuevo, motivo y fecha, sobre la cita. Se escribe en `EstadosCita::cambiar`, en la transacción del cambio, y lo comprueba `HistorialCitaTest::test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`.
  - Los rechazos no dejan entrada: `test_un_rechazo_no_deja_entrada_en_el_historial`.
  - El evento de auditoría formal queda para la spec 022 (excepción P7 aceptada).
- **Métricas y alertas:** sin cambios (no hay plataforma de métricas; objetivo 5).
- **Verificación tras el deploy** (consultas de solo lectura):
  - `SHOW TRIGGERS WHERE \`Trigger\` = 'trg_historial_cita'` → 0 filas;
  - `historial_citas` posteriores al despliegue con `cambiado_por IS NULL` → 0;
  - pares de filas de la misma cita con la misma `fecha` y el mismo `estado_nuevo` posteriores al despliegue → 0.

### Cobertura de riesgos (según la spec)
| Corrección | Alcance | Cómo |
|---|---|---|
| OB1.a | fuera | → spec 020 |
| OB1.b | fuera | → spec 021 |
| OB1.c | fuera | → spec 020 (los 403 del Gate tampoco se registran; excepción P7) |
| OB1.d | fuera | → spec 022 |
| OB1.e | fuera, parcialmente atendida | el historial de estados gana el autor (CA9); el evento formal → spec 022 |
| OB1.f | fuera | → spec 021 |
| OB1.g | fuera | → spec 020 |
| OB1.h | fuera | → spec 020 |
| OB5.a | dentro | `EstadosCita::cambiar` y la migración que retira el trigger; CA9 |
| OB5.b | fuera, parcialmente atendida | las cancelaciones de los tres flujos pasan por `EstadosCita` y quedan con autor; las notas internas → spec 022 |
| OB5.c | fuera | → spec 022; la app no borra citas |
| RS2.a | fuera, parcialmente atendida | `role:cliente` solo en `PATCH /cliente/citas/{id}/cancelar` (CA7); el resto del grupo `/cliente` → spec 015 |
| RS2.b | fuera | → spec 015 |

## Rollout
- Sin despliegue configurado: hoy solo existe el entorno local (`docs/deployment.md`). Cuando haya
  staging, se libera con estos pasos.
- Feature flag: no (decisión del usuario, 2026-10-02).
- Orden: desplegar el código y ejecutar `php artisan migrate --force` en el mismo paso. Sin
  migración, el código nuevo y el trigger escribirían dos filas; con la migración y el código
  anterior, los cambios quedarían sin historial. Ventana máxima: la del propio despliegue.
- Compatibilidad: rutas y formularios iguales. El personal pierde opciones que hoy ve, y el
  personal que entre en la ruta de cancelar del paciente recibe 403 en lugar de 404.
- Rollback:
  1. `php artisan migrate:rollback --step=1`, que recrea el trigger.
  2. Volver a desplegar la versión anterior.

  Las filas de historial escritas por la versión nueva siguen siendo válidas y no se borran.
- Qué vigilar: errores 500 en `PATCH …/estado`, `DELETE /admin/citas/{id}` y
  `PATCH /cliente/citas/{id}/cancelar`, y las consultas de Observabilidad.

## Decisiones (→ ADR si son arquitectónicas)
- **D1. Dónde vive la regla.**
  - Opciones: (a) servicio `App\Agenda\EstadosCita`, junto a `ReservaCitas`; (b) un enum con métodos de transición; (c) dejarla en cada controlador.
  - Se elige (a): sigue el patrón de la 025, encaja en el módulo Agenda de la 013 y sirve a los tres flujos.
  - Sin ADR: no cambia la arquitectura.
- **D2. Historial con autor** (decisión del usuario, 2026-10-02).
  - Se retira `trg_historial_cita` con una migración reversible, y la app escribe la fila en la transacción del cambio.
  - Opción descartada: un trigger que lea autor y motivo de variables de sesión. Reparte la lógica y las variables se pueden filtrar entre peticiones.
  - Contra: un `UPDATE` hecho a mano en la BD ya no deja historial.
  - Resuelve la pregunta abierta de la spec 013 sobre el trigger: la escritura pasa a la aplicación. Se anota en el roadmap para la `/specify --edit 013`.
- **D3. Autorización** (decisión del usuario, 2026-10-02).
  - Gates `gestionar-cita` y `cambiar-estado-cita` en la clase `App\Autorizacion\CambiarEstadoCita` (métodos `alcance` y `autorizar`), con el alcance de `AlcanceClinico::dentistasDelAlcance()` y la firma del plan de la 014, según ADR 0003.
  - Más `role:cliente` en la ruta de cancelar del paciente, que atiende RS2.a solo en esa ruta (el resto del grupo → 015; analyze A12).
  - Conflicto con la 014 (plan `approved`, sin tareas): esa spec da `AlcanceClinico` como nuevo y también registra Gates en `AppServiceProvider` y toca `CitasController` (`create`/`store`). Tras liberar la 026, `/plan 014 --fix` debe partir de la clase existente, conservar sus dos métodos y contar con que `CitasController` ya delega en ella su alcance (A11). Se anota en el roadmap (T041).
  - Conflicto con la 020: su plan también edita `AppServiceProvider::boot()`. Se resuelve al reconstruirla sobre `main`.
- **D4. Zona horaria de la clínica** (decisión del usuario, 2026-10-02).
  - `config('clinica.zona_horaria')` (por defecto America/Mexico_City, sin horario de verano desde 2022), solo para `haEmpezado`.
  - Opción descartada: cambiar `app.timezone`. Contradice el plan de la 020 (`ocurrido_en` en UTC) y cambia todas las fechas de sistema.
  - Deuda en el roadmap: el resto de comparaciones con `now()` siguen en UTC (paneles, próximas citas del paciente, `after_or_equal:today`, fechas del historial mostradas sin convertir).
- **D5. Bloqueo.**
  - `lockForUpdate()` de la fila de la cita, con la espera por defecto de MariaDB (50 s). No se usa `SET SESSION`, para no repetir 025/R8.
  - El caso normal de CA10 se cumple porque el segundo cambio espera y se evalúa contra el estado nuevo.
  - Que la espera se agote o haya un interbloqueo no está en la spec. Se responde con el mensaje `ocupada`, sin 500, coherente con "nunca un error del servidor".
  - Igual que en 025/R9, `cambiar()` no debe llamarse dentro de otra transacción. Se documenta en el docblock.
- **D6. Autorización frente a regla de estado.**
  - El rol y el alcance los deciden los Gates: 403, o 404 para la cita de otro paciente. El alcance se comprueba antes de validar la entrada (A5).
  - "Rol primero" (decisión del usuario, 2026-10-02, analyze A2): un cambio que el rol no puede pedir da 403 aunque la cita esté cerrada; el mensaje de CA2 aplica a los cambios que el rol sí puede pedir. Volver a `agendada` pasa los Gates (no es una transición de la matriz) y el servicio lo rechaza con `cerrada` o `vuelta`.
  - El estado y el tiempo los decide el servicio: mensaje en el formulario.
  - Así se separan los tests de P3 de los de la regla, y el personal ve un mensaje útil en los casos que la interfaz no puede evitar (formulario desactualizado, CA10).
- **D7. Mensajes del paciente.** Los tipos `cerrada` y `transicion` se traducen a "Esta cita ya no se puede cancelar…", el texto actual ampliado. No se le muestran estados internos.
- **D8. Panel del paciente (spec 012) sin cambios.** Fuera de `extends`. Su botón solo aparece con la próxima cita agendada, y el servidor aplica las reglas igualmente.
- **D9. Sin feature flag** (decisión del usuario, 2026-10-02).
- **D10. Excepciones P7 y P8** (decisión del usuario, 2026-10-02): ver Constitution Check. Se anotan en el roadmap (T041).
- **D11. Excepción P3 en tres GET** (decisión del usuario, 2026-10-02, analyze A1): el detalle de cita del personal recibe su Gate en la 014, y "Mis citas" y el detalle del paciente, `role:cliente` en la 015. Se anota en el roadmap (T041).

## Impacto en arquitectura
Al implementar (T090/T091):
- **`docs/architecture.md`:**
  - módulo Agenda con `app/Agenda/EstadosCita.php`;
  - nueva carpeta `app/Autorizacion`;
  - el párrafo del trigger (escritura del historial desde la app);
  - "Esquema" (trigger retirado por migración);
  - quitar la deuda "dos filas por cambio con motivo".
- **`docs/security.md`:** "Dónde se aplica" gana los Gates `gestionar-cita` y `cambiar-estado-cita` y `role:cliente` en la cancelación; RS2.a sigue "pendiente", con la nota "cancelación del paciente con `role:cliente`: spec 026; resto del grupo → spec 015".
- **`docs/observability.md`:**
  - OB5.a → "en curso (spec 026)"; pasa a mitigada en `/release`;
  - OB5.b, con una nota sobre las cancelaciones con autor y las notas pendientes → 022;
  - la "Brecha por confirmar" del trigger en producción queda resuelta: el trigger ya no se usa.
- **`docs/deployment.md`:** la migración de la 026 se ejecuta en el mismo paso que el código, y la variable `CLINICA_ZONA_HORARIA`.
- **`docs/roadmap.md` (Pendientes y deuda):**
  - comparaciones con `now()` en UTC (D4);
  - `/plan 014 --fix` con `AlcanceClinico` existente (D3);
  - la pregunta del trigger de la 013 resuelta (D2);
  - residuos de P7 y P8 (D10);
  - excepción P3 de los tres GET → 014 y 015 (D11).

## Riesgos y mitigaciones
- **Doble escritura del historial** si el código llega sin la migración. Mitigación: Rollout en un solo paso; la consulta de duplicados de Observabilidad.
- **Bases sin el trigger** (la "Brecha por confirmar"). Mitigación: `DROP TRIGGER IF EXISTS` es idempotente.
- **Zona horaria mal configurada.** Un valor inválido en `CLINICA_ZONA_HORARIA` provoca un error al evaluar "ya empezó". Mitigación: valor por defecto válido y documentado en `deployment.md`. Las fechas del historial siguen en UTC (deuda, D4).
- **Solape con la 014 y la 020** en `AppServiceProvider`, `CitasController` y `AlcanceClinico`. Mitigación: D3 y nota en el roadmap; la 026 solo añade métodos que la 014 ya diseña.
- **Cambio visible para el personal:** pierde opciones que hoy usa, por ejemplo cancelar como dentista o reabrir citas. Mitigación: avisos que explican qué hacer; CHANGELOG en `/release`.
- **Tests de concurrencia** que dejan datos confirmados si fallan a medias. Mitigación: `tearDown` con `finally` y borrado en orden de FK, como en la 025.
- **Residuos aceptados:**
  - P7: sin evento formal, y 403 sin registro (→ 022, 020);
  - P8: SQL con el motivo en el reporte por defecto (→ 023).
