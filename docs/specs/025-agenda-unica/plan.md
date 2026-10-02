---
spec: 025-agenda-unica
status: approved   # draft | approved | blocked
created: 2026-09-29
---

# Plan · 025 Regla de agenda única sin doble reserva

## Enfoque técnico
La regla de agenda (horario del día, encaje en la franja y solape) sale de los dos controladores
a un único servicio, `App\Agenda\ReservaCitas`, que valida y guarda la cita dentro de una
transacción. Al empezar, la transacción bloquea la fila del dentista en `personal` con
`lockForUpdate()`. Así, dos reservas del mismo dentista se procesan una detrás de otra, y la
segunda ve la cita ya guardada y recibe el mensaje de no disponible.

El solape se calcula con límites estrictos (inicio nuevo < fin existente y fin nuevo < inicio
existente), así que las citas seguidas no chocan. Las citas candidatas se leen con Query Builder
y bindings, y la comparación se hace en PHP, sin SQL crudo.

El bloqueo se toma antes de leer el horario y las citas, así que la segunda reserva lee datos ya
confirmados.

Los controladores siguen haciendo lo propio de cada flujo:
- validar la entrada: fecha `Y-m-d` y hora `H:i` (RS12.a), y dentista de `tipo=dentista` con la
  cuenta activa (RS12.c), con mensajes en español;
- calcular la duración;
- decidir si se permiten fechas pasadas.

Después llaman al servicio, que reciben por inyección desde el contenedor, y convierten su rechazo
en un error del campo `hora`. Antes de tocar la lógica, un commit solo de formato pasa Pint sobre
los dos controladores.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 025 `approved`, aprobada de nuevo el 2026-09-30 tras ampliar CA9 y CA10. |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA2, CA3, CA6 y CA7 describen comportamiento que ya se cumple hoy, así que sus tests son de caracterización y no pueden fallar antes; cada uno se comprueba rompiendo la regla un momento — aprobado por el usuario el 2026-09-29 | CA1, CA4, CA5, CA8, CA9 y CA10 tienen tests que fallan hoy: CA1 y CA4, porque el mensaje es distinto entre flujos; CA5, porque las citas seguidas se rechazan en un sentido; CA8, porque no hay bloqueo antes de leer y el bloqueo agotado acaba en 500; CA9, porque una hora o una fecha con hora dan 500; CA10, porque se acepta un dentista de otro tipo o desactivado. Todos los tests usan `DatabaseTransactions` contra `dentissa_test`. La única escritura confirmada es la de la segunda conexión de `ReservaConcurrenteTest` (un usuario dentista, su fila en `personal` y su horario), también en `dentissa_test`; se borra al final del test y la base queda como en la línea base. Es la segunda excepción de este tipo (tras la 024), así que se propone enmendar P2 (D9). |
| P3 Autorización en servidor, por rol y recurso | ❌ aceptado: los dos endpoints modificados siguen sin Policy ni Gate. El alta del personal (`POST /{rol}/citas`) los recibe en la spec 014, con el Gate `agendar-para-paciente` (ADR 0003) y el alcance del paciente. El alta del paciente (`POST /cliente/agendar`) los recibe en la spec 015, junto con el rechazo por rol en `/cliente/*` — aprobado por el usuario el 2026-09-30 | No cambia ningún permiso: se conservan los middlewares de rol y `autorizadoParaCita`. `AltaCitasRolesTest` prueba, para cada rol, el caso permitido y el denegado. En el personal: admin permitido; dentista con su dentista permitido y con otro → 403; asistente con un dentista asignado permitido y con uno no asignado → 403; cliente → 403. En el paciente: permitido, y un `paciente_id` ajeno inyectado se ignora (la cita queda a su nombre). |
| P4 Entrada validada y SQL con bindings | ✅ | En ambos flujos, `fecha` con `date_format:Y-m-d`, `hora` con `date_format:H:i` y `dentista_id` con `Rule::exists` restringido a `tipo=dentista` y a usuario activo. Las consultas del servicio usan Query Builder con bindings; se retira el `whereRaw` de la regla (el del buscador de `index` queda fuera de alcance). |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación; sin credenciales. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. La rama sale de la 024, con `composer audit --locked` y `npm audit` en 0. |
| P7 Auditoría de datos sensibles | ❌ aceptado: (1) el alta guarda notas de la cita (dato de salud) sin evento de auditoría, que queda para la spec 022; (2) el 403 del alta del personal (`autorizadoParaCita`) no se registra, y el registro central de accesos denegados es de la spec 020 (OB1.c). La 025 no cambia qué notas se guardan, quién puede guardarlas ni cuándo se deniega — aprobado por el usuario el 2026-09-30 | La infraestructura de auditoría (spec 020) aún no está en esta rama. Las dos partes se anotan en el roadmap (D9). |
| P8 Correlación y logs limpios | ❌ aceptado: una `QueryException` del insert de `ReservaCitas` distinta de 1205/1213 sigue el reporte por defecto de Laravel, que registra el SQL con sus bindings, notas de la cita incluidas; la redacción de bindings para toda la aplicación es OB4.a y la corrige la spec 023 — aprobado por el usuario el 2026-09-30 | Sin logs nuevos. El rechazo por bloqueo agotado o interbloqueo se devuelve como mensaje, sin registrar SQL ni datos; un test comprueba que ninguna llamada a `Log` en la reserva ni en ese rechazo lleva SQL, bindings ni datos de la cita. El log con IDs de los conflictos de agenda (OB3.b) queda para el objetivo 3. El residuo se anota en el roadmap (D9). |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/ReservaCitas.php` (nuevo) | Constructor: conexión de base de datos (`ConnectionInterface`) y segundos de espera por bloqueo (5 por defecto). Se registra en el contenedor y los controladores lo reciben por inyección. `reservar(array $cita): string`: <ol><li>abre una transacción y fija `innodb_lock_wait_timeout`;</li><li>**lo primero**, bloquea la fila del dentista (`personal`, `lockForUpdate`);</li><li>después lee el horario del día (existe y `disponible=1`) y comprueba el encaje en la franja (bordes incluidos);</li><li>comprueba el solape estricto con las citas del mismo día y dentista que no están `cancelada` ni `no_asistio`;</li><li>inserta la cita en `agendada` y devuelve su id.</li></ol> Un bloqueo agotado (1205) o un interbloqueo (1213) se convierten en `CitaNoAgendable` con el mensaje de no disponible, sin registrar nada. Llegue como `QueryException` (transacción de nivel 1, producción) o como `Illuminate\Database\DeadlockException` (transacción anidada, como en los tests), se trata igual. | Medio: concentra la regla de los dos flujos. |
| `app/Agenda/CitaNoAgendable.php` (nuevo) | Excepción con el mensaje para el usuario. Tres casos: el dentista no atiende ese día; la cita queda fuera de la franja (con `H:i - H:i`); el horario ya no está disponible. | Bajo |
| `app/Http/Controllers/CitasController.php` y `ClienteCitasController.php` (formato previo) | Pint sobre los dos archivos completos, en un commit aparte y sin cambios de lógica; la suite sigue igual (D10). | Bajo: solo formato; se suma a los conflictos de formato con la 014. |
| `app/Http/Controllers/CitasController.php` | `store(Request, ReservaCitas)`: `fecha` → `required\|date_format:Y-m-d`; `hora` → `required\|date_format:H:i`; `dentista_id` → `Rule::exists` con `tipo=dentista` y usuario activo, con los mensajes en español de Contratos. Conserva `autorizadoParaCita`, la duración del formulario (10–300) y las fechas pasadas. Llama al servicio y convierte `CitaNoAgendable` en `back()->withInput()->withErrors(['hora' => …])`. Se elimina la copia de la regla. | Medio: la 014 también cambia `store` (D7). |
| `app/Http/Controllers/ClienteCitasController.php` | `agendarStore(Request, ReservaCitas)`: las mismas reglas y mensajes de `fecha` (más `after_or_equal:today`), `hora` y `dentista_id`. Conserva el servicio obligatorio y la duración del servicio. Usa el servicio y el mismo manejo de errores. Se elimina la copia de la regla. | Medio |
| `tests/Feature/ReglaAgendaTest.php` (nuevo) | CA1–CA7 por HTTP en ambos flujos. | Bajo |
| `tests/Feature/ReservaConcurrenteTest.php` (nuevo) | CA8: una segunda conexión con la fila del dentista bloqueada, el orden bloqueo → lectura, el interbloqueo simulado y la ausencia de logs. | Medio: depende del motor (InnoDB) |
| `tests/Feature/AltaCitasValidacionTest.php` (nuevo) | CA9 y CA10 en ambos flujos, con los mensajes en español y la entrada conservada. | Bajo |
| `tests/Feature/AltaCitasRolesTest.php` (nuevo) | Tests de rol de P3, permitido y denegado por rol. | Bajo |
| `tests/Feature/AltaCitasFlujosTest.php` (nuevo) | Diferencias que se mantienen por flujo y columnas guardadas (spec, Fuera de alcance; A16). | Bajo |
| `docs/architecture.md` | "Resumen", "Módulos", paso 4 del "Flujo principal" y "Deuda técnica" (T090). | Bajo |
| `docs/security.md` | RS12.a → "en curso (spec 025)". RS12.c sigue "pendiente", con la nota de que el agendado lo resuelve la 025 y el resto la 013 (D5). | Bajo |
| `docs/roadmap.md` | Pendientes: enmienda de P2 (D9); auditoría del alta con notas → 022 y del 403 del alta del personal → 020 (P7); Gate o Policy del alta del paciente → 015 (P3); residuo de SQL con bindings en el log del alta → 023 (P8); ajuste de la 014 sobre el `store` de la 025 (D7); límite de peticiones en el alta de citas (D13). | Bajo |

## Contratos y datos
- **Rutas:** sin cambios (`POST /{admin|dentista|asistente}/citas`, `POST /cliente/agendar`),
  con los mismos middlewares y redirecciones de éxito.
- **Errores de validación nuevos:** mensajes propios en el `validate()` de cada flujo, porque el
  idioma por defecto es `en` y no hay `lang/`.
  - `fecha` con formato distinto de `AAAA-MM-DD` → "La fecha debe tener el formato AAAA-MM-DD."
  - `hora` con formato distinto de `HH:MM` → "La hora debe tener el formato HH:MM."
  - `dentista_id` que no existe, no es un dentista o tiene la cuenta desactivada → "Elige un
    dentista válido."
  - Todos vuelven al formulario con `fecha`, `hora` y `dentista_id` conservados.
- **Mensajes de rechazo de la regla**, en el campo `hora` y iguales en los dos flujos:
  - "El dentista no tiene horario disponible ese día de la semana." (texto actual)
  - "El horario elegido está fuera del horario de atención del dentista (HH:MM - HH:MM)." (texto
    actual)
  - "Ese horario ya no está disponible. Por favor elige otro." El personal veía antes "El
    dentista ya tiene una cita en ese horario…"; es un cambio visible acordado en los Supuestos
    de la spec.
- **Datos:** sin migraciones ni cambios de esquema. La cita se inserta con las mismas columnas
  que hoy.
- **Pantallas:** sin cambios en las vistas. Los formularios ya envían la hora como `HH:MM`
  (`<input type="time">`). No cambian permisos, así que no aplica la tabla "Qué ve cada rol".
  La skill `design` no hace falta: no hay cambios de interfaz.

## Estrategia de pruebas
- PHPUnit Feature con `DatabaseTransactions` contra `dentissa_test`. Cada test crea sus datos:
  horario del dentista, pacientes, un segundo dentista y personal de otros tipos. El volcado solo
  trae 1 dentista y ningún horario.
- **Regla (CA1–CA7), `ReglaAgendaTest`**, cada caso por los dos flujos (personal como admin y
  paciente), con métodos o data providers nombrados por flujo para poder filtrar solo un flujo:
  - `test_el_personal_y_el_paciente_obtienen_el_mismo_resultado_para_la_misma_cita` (CA1): la misma
    fecha, hora, duración y dentista, en los casos día sin horario, fuera de franja, solape y
    válida, produce el mismo resultado y el mismo mensaje en los dos flujos.
  - `test_un_dia_sin_horario_o_no_disponible_se_rechaza` (CA2).
  - `test_una_cita_fuera_de_la_franja_se_rechaza_indicando_la_franja` y
    `test_los_bordes_exactos_de_la_franja_se_aceptan` (CA3).
  - `test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` (CA4): el mensaje es el texto
    unificado y no contiene el nombre del otro paciente ni su servicio.
  - `test_una_cita_que_termina_cuando_empieza_otra_se_acepta` y
    `test_una_cita_que_empieza_cuando_termina_otra_se_acepta` (CA5).
  - `test_las_citas_canceladas_o_no_asistio_no_bloquean_el_hueco` (CA6).
  - `test_la_cita_de_otro_dentista_no_bloquea_el_hueco` (CA7).
- **Concurrencia (CA8), `ReservaConcurrenteTest`:**
  - **Preparación con una segunda conexión.** Solo la usan los tests de bloqueo. Se define en el
    test copiando la configuración de la conexión por defecto, así sirve igual en local (`db`) y
    en el CI (`127.0.0.1`).
    - Crea y **confirma** un usuario dentista, su fila en `personal` y su horario. No depende del
      id del volcado.
    - Lo hace antes de la primera lectura de la app en el test, para que la vista de REPEATABLE
      READ de la app ya los vea.
  - **Nada de filas hijas desde la app.** La conexión de la app, dentro de `DatabaseTransactions`,
    no escribe ninguna fila que referencie a ese dentista. Si lo hiciera, la clave foránea le
    daría un bloqueo compartido y se bloquearía a sí misma. Los pacientes que crea la app llevan
    `dentista_id` nulo, porque `pacientes.dentista_id` también tiene clave foránea a `personal`.
  - **Limpieza sin esperas**, en este orden:
    1. la segunda conexión deshace su transacción, lo que suelta el `FOR UPDATE`;
    2. la app deshace la suya (`parent::tearDown()`);
    3. la segunda conexión, obtenida antes, borra lo que confirmó.

    Ejecutar el archivo dos veces seguidas no deja restos: los recuentos de usuarios, dentistas y
    horarios coinciden con la línea base.
  - **Tests sin segunda conexión:** los de orden, espera y logs usan un dentista creado dentro de
    la transacción de la app. Así no retienen bloqueos sobre filas de la segunda conexión.
  - **Espera acotada.** Las dos sesiones usan `innodb_lock_wait_timeout = 1`, y `ReservaCitas` se
    enlaza en el contenedor con 1 s.
  - `test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista` y
    `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500`, por los dos flujos: con la
    segunda conexión reteniendo `FOR UPDATE` sobre el dentista, la reserva responde el mensaje de
    no disponible y no guarda la cita. **Por qué falla hoy:** el insert espera al bloqueo (clave
    foránea a `personal`), se agota en 1 s y responde 500 sin manejar.
  - `test_el_bloqueo_del_dentista_se_toma_antes_de_leer_horario_y_citas`: registra las consultas
    de una reserva normal (`DB::listen`) y comprueba que el `select … for update` sobre
    `personal` va antes de la lectura de `horarios_dentista` y de `citas`. Falla hoy porque no hay
    `for update`.
  - `test_un_interbloqueo_se_convierte_en_no_disponible`: `ReservaCitas` con una conexión doble
    cuya transacción lanza, en tres casos, una `QueryException` 1205, una `QueryException` 1213 y
    una `DeadlockException`. En los tres se espera `CitaNoAgendable` con el mensaje de no
    disponible. El test de bloqueo por HTTP cubre, además, el camino real anidado.
  - `test_ni_la_reserva_ni_el_rechazo_por_bloqueo_escriben_datos_en_logs`: `Log::spy()` durante
    una reserva normal y durante un bloqueo agotado. Ninguna llamada lleva SQL, bindings ni datos
    de la cita (notas, paciente). Así el test sigue siendo válido cuando OB3.b añada un log con
    IDs.
- **Validación (CA9, CA10), `AltaCitasValidacionTest`, por flujo:**
  - `test_una_hora_mal_formada_se_rechaza_sin_error_500`, con "25:99" y "abc".
  - `test_una_fecha_mal_formada_se_rechaza_sin_error_500`, con "2026-10-05 10:00" y "abc".
  - `test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza`, con un asistente, un
    administrador, un dentista con la cuenta desactivada y un id que no existe. Error en
    `dentista_id` y 0 citas nuevas.
  - En todos: el texto exacto en español de Contratos y la entrada conservada en la sesión
    (`fecha`, `hora`, `dentista_id`).
- **Roles (P3), `AltaCitasRolesTest`.** Los pacientes están dentro del alcance del dentista que
  agenda, así que el 403 depende solo de `autorizadoParaCita` y el test sigue válido tras la 014.
  - `test_el_admin_puede_agendar`
  - `test_un_dentista_puede_agendar_para_si_mismo` y
    `test_un_dentista_no_puede_agendar_para_otro_dentista` (403)
  - `test_un_asistente_puede_agendar_para_un_dentista_asignado` y
    `test_un_asistente_no_puede_agendar_para_un_dentista_no_asignado` (403), con
    `asistente_dentista` creado en el test
  - `test_un_cliente_no_puede_usar_el_alta_del_personal` (403)
  - `test_un_cliente_puede_agendar_su_cita`
  - `test_un_cliente_no_puede_agendar_para_otro_paciente`: el `paciente_id` inyectado se ignora
    y la cita queda a su nombre.
- **Diferencias por flujo, `AltaCitasFlujosTest`:**
  - `test_la_cita_del_personal_guarda_duracion_notas_y_creador`: duración elegida, servicio
    nulo permitido, `notas_paciente`, `notas_internas` y `creado_por`.
  - `test_la_cita_del_paciente_toma_la_duracion_del_servicio`.
  - `test_el_paciente_debe_elegir_servicio`.
  - `test_solo_el_personal_puede_agendar_en_fecha_pasada`.
  - Caracterización, P2: se comprueban rompiendo el comportamiento que protegen.
- **Comprobación rompiendo (P2):** CA2, CA3, CA6 y CA7 se verifican rompiendo la regla un momento
  y anotándolo en la tarea.
  - Rompiendo: quitar la comprobación de `disponible`, mover un borde de la franja, dejar de
    excluir `cancelada` y quitar el filtro por dentista.
  - También se verifica así el sentido de CA5 que hoy ya se acepta.
- **Tiempo:** las pruebas de la regla, en total, corren en menos de 10 s en local (medido en la
  verificación final).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: dos reservas simultáneas se quedan con el mismo hueco (condición de carrera) | A06:2025 | `ReservaCitas::reservar` | Transacción con bloqueo de la fila del dentista **antes** de leer horario y citas | `ReservaConcurrenteTest::test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`, `test_el_bloqueo_del_dentista_se_toma_antes_de_leer_horario_y_citas` |
| TM2 | Denial of service: espera por bloqueo o interbloqueo que acaba en error 500 | A06:2025 | `ReservaCitas::reservar` | Espera de 5 s como máximo; los errores 1205/1213 se convierten en `CitaNoAgendable`, sin logs | `ReservaConcurrenteTest::test_el_bloqueo_agotado_responde_no_disponible_sin_error_500`, `test_un_interbloqueo_se_convierte_en_no_disponible`, `test_ni_la_reserva_ni_el_rechazo_por_bloqueo_escriben_datos_en_logs` |
| TM3 | Denial of service / divulgación: hora o fecha mal formada que provoca una excepción y un error 500 | A05:2025 | `CitasController@store`, `ClienteCitasController@agendarStore` | `date_format:Y-m-d` y `date_format:H:i` antes de interpretar la fecha | `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500`, `test_una_fecha_mal_formada_se_rechaza_sin_error_500` |
| TM4 | Tampering: se agenda con un asistente, un administrador o un dentista desactivado | A05:2025 | ambos controladores | `Rule::exists` sobre `personal` con `tipo=dentista` y usuario activo | `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza` |
| TM5 | Information disclosure: el mensaje de ocupado revela a otro paciente o su servicio | A01:2025 | `CitaNoAgendable` | Mensaje fijo, sin datos de la otra cita | `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` |

Casos de abuso de la spec → amenazas: CA8 → TM1, TM2; CA9 → TM3; CA10 → TM4; CA4 → TM5.

Amenaza fuera de alcance (D13):
- **TM6 · Denial of service.** Muchas reservas del mismo dentista esperan hasta 5 s cada una, en
  serie, y `POST /cliente/agendar` no tiene límite de peticiones.
- Categoría A06:2025; componentes `ReservaCitas` y rutas de alta.
- Control previsto: límite de peticiones en las rutas de escritura. Se anota en el roadmap para una
  spec propia.
- Sin test en esta spec.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: misma regla y resultado en los dos flujos | `ReservaCitas`, ambos controladores | Feature `ReglaAgendaTest::test_el_personal_y_el_paciente_obtienen_el_mismo_resultado_para_la_misma_cita` |
| CA2: día sin horario o no disponible | `ReservaCitas` | Feature `ReglaAgendaTest::test_un_dia_sin_horario_o_no_disponible_se_rechaza` |
| CA3: fuera de franja se rechaza; bordes se aceptan | `ReservaCitas` | Feature `ReglaAgendaTest::test_una_cita_fuera_de_la_franja_se_rechaza_indicando_la_franja`, `test_los_bordes_exactos_de_la_franja_se_aceptan` |
| CA4: solape rechazado sin revelar la otra cita | `ReservaCitas`, `CitaNoAgendable` | Feature `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` |
| CA5: citas seguidas se aceptan | `ReservaCitas` (solape estricto) | Feature `ReglaAgendaTest::test_una_cita_que_termina_cuando_empieza_otra_se_acepta`, `test_una_cita_que_empieza_cuando_termina_otra_se_acepta` |
| CA6: canceladas y no asistió no bloquean | `ReservaCitas` | Feature `ReglaAgendaTest::test_las_citas_canceladas_o_no_asistio_no_bloquean_el_hueco` |
| CA7: otro dentista no bloquea | `ReservaCitas` | Feature `ReglaAgendaTest::test_la_cita_de_otro_dentista_no_bloquea_el_hueco` |
| CA8 (abuso): solo una de dos reservas simultáneas | `ReservaCitas` (transacción y bloqueo previo a la lectura) | Feature `ReservaConcurrenteTest::test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`, `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500`, `test_el_bloqueo_del_dentista_se_toma_antes_de_leer_horario_y_citas`, `test_un_interbloqueo_se_convierte_en_no_disponible` |
| CA9 (abuso): hora o fecha mal formada sin error 500 | ambos controladores | Feature `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500`, `test_una_fecha_mal_formada_se_rechaza_sin_error_500` |
| CA10 (abuso): dentista inexistente, de otro tipo o desactivado | ambos controladores | Feature `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza` |
| TM1 | bloqueo de la fila del dentista antes de leer | `ReservaConcurrenteTest::test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`, `test_el_bloqueo_del_dentista_se_toma_antes_de_leer_horario_y_citas` |
| TM2 | espera acotada y conversión de 1205/1213 sin logs | `ReservaConcurrenteTest::test_el_bloqueo_agotado_responde_no_disponible_sin_error_500`, `test_un_interbloqueo_se_convierte_en_no_disponible`, `test_ni_la_reserva_ni_el_rechazo_por_bloqueo_escriben_datos_en_logs` |
| TM3 | `date_format:Y-m-d` y `date_format:H:i` | `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500`, `test_una_fecha_mal_formada_se_rechaza_sin_error_500` |
| TM4 | `exists` con `tipo=dentista` y usuario activo | `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza` |
| TM5 | mensaje fijo | `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` |

## Observabilidad
No aplica: la spec no añade logs, eventos de auditoría ni métricas (spec 025, Auditoría), y no
cambia permisos. El registro actual de cambios de estado (`trg_historial_cita`) no se toca.
Residuos declarados:
- P8 (excepción aceptada): el reporte por defecto de una `QueryException` inesperada deja SQL y
  bindings (OB4.a → spec 023).
- El log con IDs de los conflictos de agenda es OB3.b (objetivo 3).
- P7 (excepción aceptada): el 403 del alta del personal sin evento → spec 020.

Verificación: `ReservaConcurrenteTest::test_ni_la_reserva_ni_el_rechazo_por_bloqueo_escriben_datos_en_logs`.

## Rollout
- Sin despliegue configurado: hoy solo existe el entorno local (`docs/deployment.md`). Cuando
  haya staging, se libera como cualquier versión, sin pasos especiales.
- Feature flag: no (decisión del usuario, 2026-09-29, D8).
- Orden: un único paso. Sin migraciones.
- Compatibilidad: sin cambios de rutas ni de datos. Los formularios actuales siguen funcionando.
- Rollback: volver a desplegar la versión anterior. No hay datos que revertir, porque las citas se
  guardan igual.
- Qué vigilar al desplegar: errores 500 en `POST …/citas` y `POST /cliente/agendar`, y citas
  solapadas del mismo dentista.
- Consulta de comprobación, igual que la regla: pares de citas del mismo dentista, ninguna
  `cancelada` ni `no_asistio`, con solape estricto (inicio A < fin B y inicio B < fin A). Debe
  devolver 0 filas creadas después del despliegue. Las reactivaciones de citas canceladas por
  cambio de estado quedan para la 026.

## Decisiones (→ ADR si son arquitectónicas)
- D1 · Dónde vive la regla: un servicio en `app/Agenda/` (namespace `App\Agenda`), consumido por
  los dos controladores. Descartados: un trait compartido, que sigue mezclando la regla con HTTP y
  no se prueba por separado, y un Form Request, que no puede guardar dentro de la transacción. Es
  coherente con el módulo Agenda que plantea la 013. No es → ADR: es organización interna sin
  dependencias nuevas; se documenta en `architecture.md` (T090).
- D2 · Doble reserva:
  - Elegido: transacción con `lockForUpdate()` sobre la fila del dentista en `personal`, que
    serializa las reservas por dentista y no por clínica, sin esquema nuevo.
  - Descartado `GET_LOCK()` por nombre: no es transaccional y hay que liberarlo a mano.
  - Descartado el aislamiento `SERIALIZABLE`: bloqueos por rango y más interbloqueos.
  - Descartada una restricción única: no expresa solapes de intervalos en MariaDB.
- D3 · Espera por bloqueo: 5 s por sesión (`innodb_lock_wait_timeout`), configurable en el
  constructor del servicio (los tests usan 1 s). 1205 y 1213 → mensaje de no disponible (CA8),
  sin logs.
  - El servicio recibe la conexión por constructor, para poder simular 1205, 1213 y
    `DeadlockException` en un test.
  - Dentro de una transacción anidada (tests con `DatabaseTransactions`), Laravel convierte 1205 y
    1213 en `Illuminate\Database\DeadlockException`, que no es una `QueryException`. En nivel 1
    (producción) llega la `QueryException`. El servicio trata las dos formas igual.
  - Se registra en el contenedor y los controladores lo reciben por inyección de método, para que
    el enlace de 1 s de los tests aplique.
  - El bloqueo se toma antes de cualquier lectura de la transacción. Así, en REPEATABLE READ la
    lectura de horario y citas ve los datos confirmados por la reserva anterior.
- D4 · Solape estricto, calculado en PHP sobre las citas del mismo día y dentista leídas con Query
  Builder. Citas seguidas no chocan (spec CA5; decisión del usuario, 2026-09-29). Se retira el
  `whereRaw` actual (P4).
- D5 · Cobertura de riesgos:
  - RS12.a → "en curso (spec 025)"; `/release 025` la pasa a mitigada.
  - RS12.c sigue **"pendiente"**, con una nota: el agendado lo resuelve la 025 y el resto de
    usos la 013. Solo pasa a mitigada cuando las dos partes estén liberadas. `/release 025` no la
    marca.
  - RS12.b sigue "pendiente (→ 027)".
  - Así ningún estado dice "mitigada" con una parte fuera, y no hace falta dividir la corrección
    ni tocar la cobertura de las specs 013 y 025.
- D6 · Diferencias por flujo, fuera del servicio y en cada controlador:
  - la duración: el personal la elige y el paciente la toma del servicio;
  - el servicio: obligatorio solo para el paciente;
  - las fechas pasadas: solo el personal puede agendarlas.
- D7 · Orden con la 014: la 025 va primero. Al retomar `/plan 014 --fix`, su plan añade la
  comprobación de alcance del paciente sobre el `store` que deje la 025 (decisión del usuario,
  2026-09-29). Se anota en Pendientes del roadmap. Los tests de rol de la 025 usan pacientes
  dentro del alcance para seguir válidos tras la 014.
- D8 · Sin feature flag: no hay entornos, no hay migraciones, y un flag obligaría a mantener las dos
  copias de la regla (decisión del usuario, 2026-09-29).
- D9 · Excepciones de constitución:
  - P2: CA2, CA3, CA6 y CA7 (tests de caracterización comprobados rompiendo).
  - P3: sin Policy ni Gate en los dos endpoints modificados. El alta del personal los recibe en
    la 014 (Gate `agendar-para-paciente`, alcance del paciente) y el alta del paciente en la 015
    (Gate o Policy y rol de `/cliente/*`).
  - P7: auditoría del alta con notas → 022, y del 403 del alta del personal → 020 (OB1.c).
  - P8: residuo de SQL con bindings en el reporte de una `QueryException` inesperada del alta →
    023 (OB4.a).
  - Es la segunda vez que P2 necesita excepción, así que se anota en el roadmap proponer una
    enmienda MINOR de P2 que distinga cambios de comportamiento de tests de caracterización.
    También se anotan los destinos de las excepciones de P3, P7 y P8 (T041).
  - Decisiones del usuario del 2026-09-29 y del 2026-09-30.
- D10 · Formato: Pint ya falla hoy en los dos controladores por código que no se toca. Se aplica
  en un commit previo solo de formato, sin lógica, y así el paso Pint del CI queda en verde
  (decisión del usuario, 2026-09-30).
- D11 · Dueño de las tablas. `horarios_dentista` es del módulo Agenda, igual que `citas`.
  `personal` es de Identidad.
  - `ReservaCitas` bloquea la fila de `personal` directamente: es un acceso entre módulos que se
    acepta hasta que la 013 publique la interfaz de Identidad para bloquear a un dentista.
  - La prueba de arquitectura de la 013 debe permitir ese acceso o migrarlo a esa interfaz.
- D12 · Validación de entrada:
  - `fecha` con `date_format:Y-m-d` (spec CA9), porque la regla `date` acepta fechas con hora y
    luego falla al combinarlas con `hora`.
  - `dentista_id` restringido a `tipo=dentista` y usuario `activo=1` (spec CA10), igual que los
    formularios.
- D13 · TM6 (espera en serie y sin límite de peticiones) fuera de alcance: el volumen es académico.
  Se anota en el roadmap una spec de límite de peticiones para las rutas de escritura (decisión del
  usuario, 2026-09-30).

## Impacto en arquitectura
- `docs/architecture.md`, todo en T090:
  - "Resumen": ya hay una primera pieza de servicio, `app/Agenda/`.
  - "Módulos": las filas Agenda (personal y cliente) dicen que la regla vive en
    `app/Agenda/ReservaCitas.php`.
  - "Flujo principal", paso 4: la comprobación y el insert van en una transacción con bloqueo del
    dentista.
  - "Deuda técnica": se retira "Regla de agenda duplicada".
- Sin ADR nuevo.

## Riesgos y mitigaciones
- La 014 cambia el mismo `CitasController@store` → orden acordado (D7). El `store` queda más
  corto y fácil de ampliar.
- El bloqueo por dentista serializa las reservas de ese dentista → el volumen es académico y la
  sección bloqueada dura milisegundos. La espera máxima de 5 s solo se alcanza con un bloqueo
  anómalo.
- El test de concurrencia depende de InnoDB, de las claves foráneas a `personal` y de una
  segunda conexión. Mitigaciones:
  - la misma imagen `mariadb:10.11` en local y en el CI;
  - la conexión copia la configuración por defecto;
  - espera de 1 s en ambas sesiones;
  - los datos del dentista los confirma la segunda conexión antes de la primera lectura de la
    app;
  - pacientes con `dentista_id` nulo;
  - limpieza en orden (rollback de la segunda conexión → rollback de la app → borrado);
  - comprobado ejecutando el archivo dos veces seguidas sin restos.
- El volcado no trae horarios → cada test crea el suyo (en su transacción, salvo el de
  concurrencia).
- Cambia el mensaje de ocupado que ve el personal → acordado en la spec. Ningún test existente
  lo comprueba.
- Riesgos de seguridad por corrección:
  - RS12.a queda en curso con esta spec y pasa a mitigada en `/release`.
  - RS12.c sigue pendiente, con nota: el agendado lo resuelve la 025 y el resto la 013.
  - RS12.b sigue pendiente (→ 027).
  - El riesgo completo sigue abierto.
