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

Los controladores siguen haciendo lo propio de cada flujo: validar la entrada, con la hora en
formato `H:i` (RS12.a) y el dentista restringido a `tipo=dentista` (RS12.c); calcular la
duración; y decidir si se permiten fechas pasadas. Después llaman al servicio y convierten su
rechazo en un error del campo `hora`.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 025 `approved` el 2026-09-29. |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA2, CA3, CA6 y CA7 describen comportamiento que ya se cumple hoy, así que sus tests son de caracterización y no pueden fallar antes; cada uno se comprueba rompiendo la regla un momento — aprobado por el usuario el 2026-09-29 | CA1, CA4, CA5, CA8, CA9 y CA10 tienen tests que fallan hoy (mensaje distinto entre flujos, citas seguidas rechazadas en un sentido, reserva que no espera al bloqueo, error 500 con hora mal formada, dentista de otro tipo aceptado). Es la segunda excepción de este tipo (tras la 024): se propone enmendar P2 (D9). |
| P3 Autorización en servidor, por rol y recurso | ❌ aceptado: en `POST /cliente/agendar` no hay test del caso denegado porque hoy otros roles pueden entrar (004 CA7), y corregirlo es de la spec 015 — aprobado por el usuario el 2026-09-29 | No cambia ningún permiso. Los endpoints modificados conservan sus middlewares y `autorizadoParaCita`. `AltaCitasRolesTest` cubre el alta del personal (admin permitido, dentista con un dentista ajeno → 403, cliente → 403) y el caso permitido del paciente. |
| P4 Entrada validada y SQL con bindings | ✅ | `hora` con `date_format:H:i` y `dentista_id` con `Rule::exists('personal', 'id')->where('tipo', 'dentista')` en ambos flujos. Las consultas del servicio usan Query Builder con bindings, sin `whereRaw` (se retira el que había). |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación; sin credenciales. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. La rama sale de la 024, con `composer audit --locked` y `npm audit` en 0. |
| P7 Auditoría de datos sensibles | ➖ | El alta de citas no cambia datos clínicos ni permisos. Auditarla queda para la spec 022 (spec 025, Auditoría). |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. El rechazo por bloqueo agotado se devuelve como mensaje al usuario, sin registrar SQL ni datos. |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/ReservaCitas.php` (nuevo) | `reservar(array $cita): string`. Abre una transacción, bloquea la fila del dentista (`personal`, `lockForUpdate`) y fija `innodb_lock_wait_timeout` al valor del constructor (5 s por defecto). Comprueba el horario del día (existe y `disponible=1`), el encaje en la franja (bordes incluidos) y el solape estricto con las citas del mismo día y dentista que no están `cancelada` ni `no_asistio`. Inserta la cita en `agendada` y devuelve su id. Un bloqueo agotado (1205) o un interbloqueo (1213) se convierten en `CitaNoAgendable` con el mensaje de no disponible. | Medio: concentra la regla de los dos flujos. |
| `app/Agenda/CitaNoAgendable.php` (nuevo) | Excepción con el mensaje para el usuario. Tres casos: el dentista no atiende ese día; la cita queda fuera de la franja (con `H:i - H:i`); el horario ya no está disponible. | Bajo |
| `app/Http/Controllers/CitasController.php` | `store`: `hora` → `required\|date_format:H:i`; `dentista_id` → `Rule::exists` con `tipo=dentista`. Conserva `autorizadoParaCita`, la duración del formulario (10–300) y las fechas pasadas. Llama a `ReservaCitas::reservar` y convierte `CitaNoAgendable` en `back()->withInput()->withErrors(['hora' => …])`. Se elimina la copia de la regla. | Medio: la 014 también cambia `store` (D7). |
| `app/Http/Controllers/ClienteCitasController.php` | `agendarStore`: las mismas reglas de `hora` y `dentista_id`. Conserva el servicio obligatorio, la duración del servicio y `after_or_equal:today`. Usa el servicio y el mismo manejo de errores. Se elimina la copia de la regla. | Medio |
| `tests/Feature/ReglaAgendaTest.php` (nuevo) | CA1–CA7 por HTTP en ambos flujos. | Bajo |
| `tests/Feature/ReservaConcurrenteTest.php` (nuevo) | CA8 con una segunda conexión que tiene bloqueada la fila del dentista. | Medio: depende del motor (InnoDB) |
| `tests/Feature/AltaCitasValidacionTest.php` (nuevo) | CA9 y CA10 en ambos flujos. | Bajo |
| `tests/Feature/AltaCitasRolesTest.php` (nuevo) | Tests de rol de P3. | Bajo |
| `docs/architecture.md` | Módulo Agenda: la regla vive en `app/Agenda/`; se retira la deuda "regla duplicada" (T090). | Bajo |
| `docs/security.md` | RS12.a → "en curso (spec 025)"; RS12.c → "en curso (spec 025: agendado; resto → 013)" (T091). | Bajo |
| `docs/roadmap.md` | Pendientes: proponer enmendar P2 para los tests de caracterización (D9). | Bajo |

## Contratos y datos
- **Rutas:** sin cambios (`POST /{admin|dentista|asistente}/citas`, `POST /cliente/agendar`),
  con los mismos middlewares y redirecciones de éxito.
- **Errores de validación nuevos:** `hora` con formato distinto de `HH:MM` →
  "La hora debe tener el formato HH:MM." `dentista_id` que no es de un dentista → "Elige un
  dentista válido." Los dos vuelven al formulario con los datos conservados.
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
  paciente):
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
- **Concurrencia (CA8), `ReservaConcurrenteTest`:** una segunda conexión (`dentissa_test`,
  definida en el test) abre una transacción y bloquea la fila del dentista del volcado. La
  reserva, con `ReservaCitas` configurado a 1 s de espera, responde el mensaje de no disponible y
  no guarda la cita, en los dos flujos. Hoy la reserva no espera al bloqueo y guarda la cita, así
  que el test falla. Tests: `test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`
  y `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500`.
- **Validación (CA9, CA10), `AltaCitasValidacionTest`:**
  - `test_una_hora_mal_formada_se_rechaza_sin_error_500`, con "25:99" y "abc".
  - `test_un_dentista_de_otro_tipo_o_inexistente_se_rechaza`, con un asistente, un
    administrador y un id que no existe. Error en `dentista_id` y 0 citas nuevas.
- **Roles (P3), `AltaCitasRolesTest`:**
  - `test_el_admin_puede_agendar`
  - `test_un_dentista_no_puede_agendar_para_otro_dentista` (403)
  - `test_un_cliente_no_puede_usar_el_alta_del_personal` (403)
  - `test_un_cliente_puede_agendar_su_cita`
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
| TM1 | Tampering: dos reservas simultáneas se quedan con el mismo hueco (condición de carrera) | A06:2025 | `ReservaCitas::reservar` | Transacción con bloqueo de la fila del dentista antes de comprobar el solape | `ReservaConcurrenteTest::test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista` |
| TM2 | Denial of service: espera por bloqueo o interbloqueo que acaba en error 500 | A06:2025 | `ReservaCitas::reservar` | Espera de 5 s como máximo; los errores 1205/1213 se convierten en `CitaNoAgendable` | `ReservaConcurrenteTest::test_el_bloqueo_agotado_responde_no_disponible_sin_error_500` |
| TM3 | Denial of service / divulgación: hora mal formada que provoca una excepción y un error 500 | A05:2025 | `CitasController@store`, `ClienteCitasController@agendarStore` | `date_format:H:i` antes de interpretar la fecha | `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500` |
| TM4 | Tampering: se agenda con un asistente o un administrador como dentista | A05:2025 | ambos controladores | `Rule::exists('personal','id')->where('tipo','dentista')` | `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_o_inexistente_se_rechaza` |
| TM5 | Information disclosure: el mensaje de ocupado revela a otro paciente o su servicio | A01:2025 | `CitaNoAgendable` | Mensaje fijo, sin datos de la otra cita | `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` |

Casos de abuso de la spec → amenazas: CA8 → TM1, TM2; CA9 → TM3; CA10 → TM4; CA4 → TM5.

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
| CA8 (abuso): solo una de dos reservas simultáneas | `ReservaCitas` (transacción y bloqueo) | Feature `ReservaConcurrenteTest::test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista`, `test_el_bloqueo_agotado_responde_no_disponible_sin_error_500` |
| CA9 (abuso): hora mal formada sin error 500 | ambos controladores | Feature `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500` |
| CA10 (abuso): dentista que no es dentista | ambos controladores | Feature `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_o_inexistente_se_rechaza` |
| TM1 | bloqueo de la fila del dentista | `ReservaConcurrenteTest::test_una_reserva_no_se_guarda_mientras_otra_tiene_el_hueco_del_dentista` |
| TM2 | espera acotada y conversión de 1205/1213 | `ReservaConcurrenteTest::test_el_bloqueo_agotado_responde_no_disponible_sin_error_500` |
| TM3 | `date_format:H:i` | `AltaCitasValidacionTest::test_una_hora_mal_formada_se_rechaza_sin_error_500` |
| TM4 | `exists` con `tipo=dentista` | `AltaCitasValidacionTest::test_un_dentista_de_otro_tipo_o_inexistente_se_rechaza` |
| TM5 | mensaje fijo | `ReglaAgendaTest::test_una_cita_solapada_se_rechaza_sin_revelar_la_otra_cita` |

## Observabilidad
No aplica: la spec no añade logs, eventos de auditoría ni métricas (spec 025, Auditoría), y no
cambia permisos. El registro actual de cambios de estado (`trg_historial_cita`) no se toca.
Verificación: tras el cambio, una reserva normal no escribe nada nuevo en `storage/logs`.

## Rollout
- Sin despliegue configurado: hoy solo existe el entorno local (`docs/deployment.md`). Cuando
  haya staging, se libera como cualquier versión, sin pasos especiales.
- Feature flag: no (decisión del usuario, 2026-09-29, D8).
- Orden: un único paso. Sin migraciones.
- Compatibilidad: sin cambios de rutas ni de datos. Los formularios actuales siguen funcionando.
- Rollback: volver a desplegar la versión anterior. No hay datos que revertir, porque las citas se
  guardan igual.
- Qué vigilar al desplegar: errores 500 en `POST …/citas` y `POST /cliente/agendar`, y citas
  solapadas del mismo dentista. Consulta de comprobación: pares de citas del mismo dentista no
  canceladas cuyos intervalos se cruzan; debe devolver 0 filas nuevas.

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
  constructor del servicio (los tests usan 1 s). 1205 y 1213 → mensaje de no disponible (CA8).
- D4 · Solape estricto, calculado en PHP sobre las citas del mismo día y dentista leídas con Query
  Builder. Citas seguidas no chocan (spec CA5; decisión del usuario, 2026-09-29). Se retira el
  `whereRaw` actual (P4).
- D5 · Cobertura de riesgos:
  - RS12.a → "en curso (spec 025)".
  - RS12.c → "en curso (spec 025: agendado; resto → 013)".
  - RS12.b → "pendiente (spec 027)", sin cambio de estado aquí.
- D6 · Diferencias por flujo, fuera del servicio y en cada controlador:
  - la duración: el personal la elige y el paciente la toma del servicio;
  - el servicio: obligatorio solo para el paciente;
  - las fechas pasadas: solo el personal puede agendarlas.
- D7 · Orden con la 014: la 025 va primero. Al retomar `/plan 014 --fix`, su plan añade la
  comprobación de alcance del paciente sobre el `store` que deje la 025 (decisión del usuario,
  2026-09-29).
- D8 · Sin feature flag: no hay entornos, no hay migraciones, y un flag obligaría a mantener las dos
  copias de la regla (decisión del usuario, 2026-09-29).
- D9 · P2 con excepción para CA2, CA3, CA6 y CA7 (tests de caracterización comprobados rompiendo),
  y P3 con excepción para el caso denegado de `/cliente/agendar` (→ 015). Como es la segunda vez
  que P2 necesita excepción, se anota en el roadmap proponer una enmienda MINOR de P2 que distinga
  cambios de comportamiento de tests de caracterización (decisión del usuario, 2026-09-29).

## Impacto en arquitectura
- `docs/architecture.md`:
  - "Módulos": las filas Agenda (personal y cliente) dicen que la regla vive en
    `app/Agenda/ReservaCitas.php`.
  - "Deuda técnica": se retira "Regla de agenda duplicada".
  - Todo ello en T090.
- Sin ADR nuevo.

## Riesgos y mitigaciones
- La 014 cambia el mismo `CitasController@store` → orden acordado (D7). El `store` queda más
  corto y fácil de ampliar.
- El bloqueo por dentista serializa las reservas de ese dentista → el volumen es académico y la
  sección bloqueada dura milisegundos. La espera máxima de 5 s solo se alcanza con un bloqueo
  anómalo.
- El test de concurrencia depende de InnoDB y de una segunda conexión → la misma imagen
  `mariadb:10.11` en local y en el CI. La conexión se define en el propio test, sin tocar
  `config/database.php`.
- El volcado no trae horarios → cada test crea el suyo dentro de su transacción.
- Cambia el mensaje de ocupado que ve el personal → acordado en la spec. Ningún test existente
  lo comprueba.
- Riesgos de seguridad por corrección: RS12.a y RS12.c (agendado) quedan en curso con esta spec y
  pasan a mitigadas en `/release`. RS12.b y el resto de RS12.c siguen pendientes (→ 027 y 013),
  y el riesgo completo sigue abierto hasta entonces.
