---
spec: 050-hora-clinica
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-17
---

# Plan · 050 La hora de la clínica en todo el panel

## Enfoque técnico
El sistema sigue en UTC (`config/app.php`) y solo cambia la referencia de «hoy» y «ahora» y cómo se muestran las
fechas del sistema. Una clase nueva y sin estado, `App\Agenda\RelojClinica`, es la única fuente de la hora de la
clínica (`config('clinica.zona_horaria')`). Generaliza el patrón que ya usa `HorarioDentista.php:66`. Los
controladores comparan `fecha_hora`, guardada en hora local sin zona, con texto en hora local de la clínica, siempre
con bindings. Las reglas de validación reciben la fecha de hoy de la clínica en lugar de `today`, y las vistas
dejan `now()` y `Carbon::today()`. Las fechas que guarda el sistema en UTC (`creado_en`, `historial_citas.fecha`,
`consentimiento_en`) se convierten a la hora de la clínica al mostrarlas, sin tocar los datos. El código que ya usa
la hora de la clínica (estados, reprogramación, horarios, reportes, auditoría y correos) no se toca.

**Orden con la 049 (decisión del usuario, 2026-11-17):** la 049 se fusiona antes. Las tareas de la 050 se escriben
y se implementan sobre `main` con la 049 ya fusionada. La 049 rehace la agenda y su `CitasController::index` ya
calcula con la hora de la clínica (`CitasController.php:99` y `:254` en la rama de la 049). Por eso, en la agenda la
050 añade el test de CA4 sobre la agenda de la 049 y corrige solo lo que siga en UTC, como la fecha sugerida de
`create` (`:283` en la 049).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 050 aprobada el 2026-11-17 (`8495fb6`); nada de código hasta aprobar plan y tareas | manual: estado de spec, plan y tareas antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene un test con el reloj fijado a las 21:50 de la clínica (03:50 UTC del día siguiente), donde hoy falla; los de R6 se actualizan para que esperen lo correcto | test: `RelojClinicaTest`, `HoraClinicaPanelesTest`, `HoraClinicaFormulariosTest`, `HoraClinicaFechasSistemaTest` (rojos antes del cambio) |
| P3 Autorización en el servidor | ❌ aceptado: la 050 modifica endpoints sin Gate por recurso solo para cambiar la fecha de referencia o la regla de fecha, y conservan su autorización actual: `PacientesController::show/store/update`, `ExpedientesController::store/update`, `CitasController::create`, `PersonalController::show` y `misDoctores` (rol con middleware, alcance con los filtros de hoy) y `ClienteCitasController::index/agendarForm/agendarStore` (grupo `/cliente` sin `role:` en el grupo). Deuda RS1.a–e → spec 014 y RS2.a → su spec (`docs/security.md`) — aprobado por el usuario el 2026-11-18 (/analyze A1) | No cambia quién ve qué; las consultas conservan su filtro de alcance | test: los tests de alcance existentes de esas pantallas siguen en verde (T092) |
| P4 Entrada validada y SQL con bindings | ✅ | Las reglas de fecha se siguen validando en el servidor, ahora con el día de la clínica; las comparaciones pasan texto por bindings (`where`/`whereDate`), sin SQL crudo nuevo | test: `test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica` (CA10) · lint: Pint |
| P5 Nada sensible en el repositorio | ➖ | Sin secretos ni datos nuevos | — |
| P6 Sin vulnerabilidades críticas o altas | ✅ | La 050 no añade dependencias. Las 6 altas previas de `npm audit` (`picomatch` y sus dependientes) las cubre la excepción EX5 (`docs/security.md`, vence el 2026-12-31; el `/chore` de npm de la v0.20.1 la registró). `composer audit`: solo 1 baja | lint: `composer audit` y `npm audit --audit-level=high` en la CI y en T092 |
| P7 Auditoría de accesos y cambios | ❌ aceptado: endpoints que la 050 modifica no registran la lectura de la ficha del paciente (`PacientesController::show`) ni los cambios de pacientes y expedientes (`PacientesController::store/update`, `ExpedientesController::store/update`): OB1.d y OB1.e → spec 022 (`docs/observability.md`). La 050 no añade acciones; el registro de auditoría ya usa la hora de la clínica (spec 020) y no se toca — aprobado por el usuario el 2026-11-18 (/analyze A2) | — | test: suite `tests/Feature/Auditoria/` en verde (CA9) |
| P8 Correlación y logs limpios | ➖ | No se añaden logs | — |
| P9 Solo el sistema de diseño | ✅ | Ningún cambio de marcado, clase ni estilo: solo cambia el valor de fechas y horas | test: los tests de vistas existentes siguen en verde · manual: las pantallas afectadas se ven igual que antes |
| P10 Rutas indexables descubribles | ➖ | Las rutas indexables (`/`, `/servicios`, `/registro`) solo cambian el año del pie y la fecha máxima de nacimiento del registro; no cambian títulos, descripciones, canónica ni sitemap | test: `PaginasPublicasTest` y `PaginaInicioTest` en verde |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/RelojClinica.php` (nuevo) | `zona()`, `ahora()`, `hoy()` (inicio del día), `fechaHoy()` (`Y-m-d`), `momentoLocal()` (`Y-m-d H:i:s`), `deCita(string)` (interpreta `fecha_hora` en la zona de la clínica) y `delSistema($valor)` (interpreta un valor UTC, string o Carbon, y lo pasa a la zona de la clínica; `null` → `null`) | bajo |
| `app/Http/Controllers/DashboardController.php` | `whereDate(..., fechaHoy())` en los paneles de administración, dentista y asistente (15, 59, 121 y sus usos); mes de la clínica en los pacientes atendidos del mes (80-81); próxima y pasadas del paciente con `momentoLocal()` (198, 228) | medio: cuatro paneles |
| `app/Http/Controllers/ClienteCitasController.php` | Próximas y pasadas con `momentoLocal()` (50, 52); fecha sugerida `hoy()->addDay()`, y la `fecha` de la dirección solo se propone si es texto `AAAA-MM-DD` válido (si no, se ignora; /analyze A3, TM2) (298); `after_or_equal:`.`fechaHoy()` con mensaje «La fecha de la cita no puede ser anterior a hoy.» (310) | medio |
| `app/Http/Controllers/PacientesController.php` | Próxima cita de la ficha con `momentoLocal()` (218); `before:`.`fechaHoy()` en el alta y la edición (91, 272) con «La fecha de nacimiento debe ser anterior a hoy.»; la edad de la ficha (`pacientes/show`, `->age`) con el día de la clínica (/analyze A10) | bajo |
| `app/Http/Controllers/CitasController.php` | Sobre la versión de la 049: fecha sugerida de `create` con `hoy()->addDay()`; si queda algún `Carbon::today()` o `now()` de la agenda, `RelojClinica` | bajo |
| `app/Http/Controllers/PersonalController.php` | «Citas este mes» con el mes de la clínica (211-212); «Mis doctores», citas de hoy con `fechaHoy()` (456) | bajo |
| `app/Http/Controllers/ExpedientesController.php` | `after:`.`fechaHoy()` en `proxima_visita` del alta (`store`, 120) y de la ficha (`update`, 201, que hoy no la valida; /analyze A4) con «La próxima visita debe ser posterior a hoy.» | bajo |
| `app/Http/Controllers/PerfilController.php`, `RegisterController.php` | `before:`.`fechaHoy()` en `fecha_nacimiento` (38; 36 y 126); sus mensajes en español no cambian | bajo |
| Vistas con «hoy» y «ahora» | `now()`, `date()` e `isToday`/`isFuture` → `RelojClinica`: `dashboard/{admin,dentista,asistente,cliente}` (fecha del encabezado, consejo del día), `citas/index` (lo que quede tras la 049), `citas/create`, `citas/cliente-agendar`, `expedientes/{create,show}` (`min`; vencida o cercana comparando fechas con `hoy()`, con la distancia en días sin signo: hoy `diffInDays` de Carbon 3 da negativo y toda futura sale «cercana», /analyze A13), `cliente/perfil`, `auth/{register,registro-contacto}` y `pacientes/create` (`max` de nacimiento: sigue siendo hace un año, `hoy()->subYear()`, decisión del usuario /analyze A7), `layouts/publico` (año) | medio: muchas vistas, cambio mecánico |
| Vistas con fechas del sistema | `Carbon::parse($x->creado_en)`, `$h->fecha` y `consentimiento_en` → `RelojClinica::delSistema(...)`: `citas/show` (146, 218), `citas/cliente-show` (74), `expedientes/{index,show}`, `pacientes/{index,show}`, `usuarios/show`, `galeria/index`. **No** se convierten `fecha_hora_anterior`, `fecha_hora_nueva` ni `fecha_cita`, que ya están en hora local | medio: convertir de más adelantaría 6 h lo que hoy está bien |
| `tests/Feature/HistorialCitaTest.php`, `EstadosCitaVistasTest.php` | 026/R6: la fecha del historial se espera en hora de la clínica (`05/10 11:00`); el comentario de la deuda D4 se corrige | bajo |

## Contratos y datos
- Sin migraciones ni cambios de esquema. Las fechas guardadas siguen igual: las de las citas en hora local y las del
  sistema en UTC.
- Comprobado el 2026-11-17: la base local corre en UTC (`@@system_time_zone = UTC`, `NOW() = UTC_TIMESTAMP()`), así
  que los `DEFAULT current_timestamp()` del volcado (`historial_citas.fecha`, `creado_en`) también dan UTC. La app
  escribe esos campos con `now()` en UTC (`EstadosCita.php:179`, `ReprogramacionCitas.php:136`, `ReservaCitas.php:165`).
  En producción la base usa la misma imagen `mariadb:10.11`; se comprueba en el Rollout.
- Contratos visibles que cambian (decisión del usuario, 2026-11-17; spec CA10): tres mensajes de validación pasan de
  los de Laravel en inglés a español:
  - `fecha.after_or_equal` (cita del paciente): «La fecha de la cita no puede ser anterior a hoy.»
  - `proxima_visita.after` (expediente): «La próxima visita debe ser posterior a hoy.»
  - `fecha_nacimiento.before` (alta y edición de paciente): «La fecha de nacimiento debe ser anterior a hoy.»
- Pantallas: mismas pantallas y mismos componentes. Cambia el valor de la fecha del encabezado de los paneles,
  las listas de hoy, próximas y pasadas, los atributos `min`/`max` de los campos de fecha, la fecha sugerida, las
  marcas de cita de hoy o futura y las fechas del sistema mostradas.

## Estrategia de pruebas
PHPUnit, `DatabaseTransactions` contra `dentissa_test`, trait `Concerns/EscenarioEstadosCita` (citas en hora local
de la clínica). El reloj se fija con `Carbon::setTestNow(Carbon::parse('2026-11-18 03:50:00', 'UTC'))`, las 21:50
del 17 en la clínica, salvo donde se indique. Cada test se escribe primero y falla con el código actual.

- **Unit**, `tests/Unit/RelojClinicaTest.php`: `fechaHoy()` a las 17:59, 18:00 y 23:59 del 17 de la clínica da el
  17, y a las 00:00 del 18 da el 18 (CA8); `momentoLocal()` a las 03:50 UTC del 18 da `2026-11-17 21:50:00`;
  `delSistema('2026-11-18 03:50:00')` da las 21:50 del 17 y `delSistema(null)` da `null`; `deCita('2026-11-17 23:00:00')`
  es posterior a `ahora()`. No necesita base de datos.
- **Feature**, `tests/Feature/HoraClinicaPanelesTest.php`, con citas el 17 a las 21:00 y a las 23:00 y el 18 a las 10:00:
  - `test_los_paneles_del_personal_muestran_el_dia_de_la_clinica` (CA1): encabezado «martes, 17 de noviembre» y la lista de
    hoy con las citas del 17 y sin la del 18, en los paneles de administración, dentista y asistente.
  - `test_el_paciente_ve_como_proxima_su_cita_de_esta_noche` (CA2): la de las 23:00 aparece en «Tu próxima cita» y en
    las próximas de «Mis citas»; la de las 21:00, en las pasadas y en el historial del panel.
  - `test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche` (CA3).
  - `test_la_agenda_usa_el_dia_de_la_clinica` (CA4, caracterización sobre el calendario de la 049, que ya usa la hora de
    la clínica): la semana del 16 al 21, el 17 marcado como hoy en la cabecera, la tira y la lista, y «Hoy» a esa semana;
    se comprueba cambiando un momento la zona de `CitasController::index` a UTC (fallan las marcas de hoy).
  - La edad de la ficha del paciente la víspera de su cumpleaños a las 21:50 sigue siendo la anterior (CA3, A10).
  - `test_las_cuentas_del_mes_usan_el_mes_de_la_clinica` (CA6): «citas este mes» a las 20:00 del 30 de noviembre
    (02:00 UTC del 1 de diciembre) y pacientes atendidos el 31 de diciembre a las 20:00.
  - `test_mis_doctores_cuenta_las_citas_de_hoy_de_la_clinica` (CA1, asistente).
  - `test_los_bordes_del_dia_de_la_clinica` (CA8): el panel del personal a las 17:59, 18:00 y 23:59 del 17 y a las
    00:00 del 18.
- **Feature**, `tests/Feature/HoraClinicaFormulariosTest.php`:
  - `test_los_formularios_proponen_fechas_del_dia_de_la_clinica` (CA5): `min` con el 17 en las citas del personal y
    del paciente y con el 18 en la próxima visita (también a las 10:00 de la clínica, donde la hora UTC da el mismo día;
    /analyze A11); fecha sugerida, el 18, también la que sale de una próxima visita del 18 en «Nueva cita» con
    `paciente_id` (A12); `max` de nacimiento, 2025-11-17 (hace un año); año del pie de `/`, el de la clínica (también el
    31 de diciembre a las 20:00); el consejo del día del panel del paciente y la fecha de la tarjeta de agenda de la
    dentista, los del 17 (A12).
  - `test_agendar_ignora_una_fecha_manipulada` (TM2): `GET /cliente/agendar?fecha[]=x` y `?fecha=ayer` responden 200 con
    la fecha sugerida de siempre (/analyze A3).
  - `test_el_servidor_acepta_las_fechas_validas_segun_el_dia_de_la_clinica` (CA5): cita del paciente para el 17 a las
    23:30 (el test da a la dentista un horario de prueba que cubre esa hora); próxima visita el 18; nacimiento el 16.
  - `test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica` (CA10, TM1): cita del paciente el 16,
    próxima visita el 17 por el alta (`POST`) y por la ficha (`PUT`, /analyze A4) y nacimiento el 17 en el alta y la
    edición de paciente, el perfil y los dos registros (/analyze A5) se rechazan, con los tres mensajes en español donde
    son nuevos; el nacimiento del 16 se acepta en todos.
  - `test_el_aviso_de_proxima_visita_cuenta_los_dias_de_la_clinica` (CA5): una próxima visita del 17 no sale como
    vencida a las 21:50 del 17, una del 24 sale como cercana y una del 25 no (/analyze A13).
- **Feature**, `tests/Feature/HoraClinicaFechasSistemaTest.php`:
  - `test_agendada_el_y_el_historial_se_muestran_en_hora_de_la_clinica` (CA7): cita creada a las 03:50 UTC del 18 y
    cambiada de estado a las 04:10 UTC. Su detalle, el del personal y el del paciente, dice «17/11/2026 21:50», y el
    historial dice «17/11 22:10».
  - `test_las_fechas_de_alta_se_muestran_en_hora_de_la_clinica` (CA7): expediente, paciente, usuario y consentimiento
    de la galería creados a las 03:50 UTC del 18 se muestran con el 17.
  - `test_las_fechas_de_reprogramacion_no_se_convierten` (CA7, riesgo de convertir de más): «Reprogramada del … al …»
    muestra las horas locales tal cual.
- **Regresión (CA9):** siguen en verde, sin tocarlas, `EstadosCita*Test`, `ReprogramarCita*Test`,
  `HorarioDentista*Test`, `CitasFueraDelHorarioTest`, `Reportes/*`, `Auditoria/*`, `CorreosRestablecimientoTest`,
  `RecuperarContrasena*Test`, `AvisoProduccionTest` y `ContrasenaDistintaTest` (/analyze A19).
- 026/R6: `HistorialCitaTest.php:113` espera `05/10 11:00` (17:00 UTC); `EstadosCitaVistasTest.php:120`, el
  comentario sin «deuda D4» (la aserción ya vale con la hora de la clínica).
- Suite completa y `pint --test` sobre lo tocado, con `docker run` sobre la red de Compose y el worktree montado,
  porque `dentissa_app` monta el clon de otra sesión.

## Modelo de amenazas
Se cambian reglas que validan entradas externas (fechas de formularios); no hay datos sensibles ni permisos nuevos.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: una petición hecha a mano con una fecha pasada según la clínica (cita del paciente, próxima visita, nacimiento) que hoy entra o se rechaza con un día de desfase | A06:2025 Insecure Design | `ClienteCitasController::agendarStore`, `ExpedientesController::store/update`, `PacientesController::store/update`, `PerfilController`, `RegisterController` (los dos registros) | Validación en el servidor con `RelojClinica::fechaHoy()`, independiente del `min`/`max` del formulario | `test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica` |
| TM2 | Tampering / DoS: una `fecha` manipulada o no escalar en la dirección de «Agendar cita» del paciente rompe la página (500) o se propone sin validar (/analyze A3) | A05:2025 Injection (entrada sin validar) | `ClienteCitasController::agendarForm` | La `fecha` solo se propone si es texto `AAAA-MM-DD` válido; si no, la sugerida de siempre | `test_agendar_ignora_una_fecha_manipulada` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: paneles del personal con el día de la clínica | `DashboardController` (admin, dentista, asistente), `PersonalController::misDoctores`, `dashboard/{admin,dentista,asistente}` | Feature: `test_los_paneles_del_personal_muestran_el_dia_de_la_clinica`, `test_mis_doctores_cuenta_las_citas_de_hoy_de_la_clinica` |
| CA2: próxima y pasadas del paciente | `DashboardController` (cliente), `ClienteCitasController::index`, `dashboard/cliente` | Feature: `test_el_paciente_ve_como_proxima_su_cita_de_esta_noche` |
| CA3: ficha del paciente (próxima cita y edad) | `PacientesController::show`, `pacientes/show` | Feature: `test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche` (con la edad) |
| CA4: calendario de la 049 | Ninguno: la 049 ya usa la hora de la clínica (caracterización) | Feature: `test_la_agenda_usa_el_dia_de_la_clinica` |
| CA5: formularios, validación, aviso de próxima visita y año del pie | Vistas de formularios, reglas `after_or_equal`/`after`/`before`, `expedientes/show`, `layouts/publico` | Feature: `test_los_formularios_proponen_fechas_del_dia_de_la_clinica`, `test_el_servidor_acepta_las_fechas_validas_segun_el_dia_de_la_clinica`, `test_el_aviso_de_proxima_visita_cuenta_los_dias_de_la_clinica` |
| CA6: mes de la clínica | `PersonalController::show`, `DashboardController` (dentista) | Feature: `test_las_cuentas_del_mes_usan_el_mes_de_la_clinica` |
| CA7: fechas del sistema en hora de la clínica | `RelojClinica::delSistema`, vistas de fechas del sistema; `HistorialCitaTest` (R6) | Feature: `test_agendada_el_y_el_historial_se_muestran_en_hora_de_la_clinica`, `test_las_fechas_de_alta_se_muestran_en_hora_de_la_clinica`, `test_las_fechas_de_reprogramacion_no_se_convierten` · verificación manual: detalle de una cita agendada de noche en local |
| CA8: bordes 17:59, 18:00, 23:59 y 00:00 | `RelojClinica` | Unit: `RelojClinicaTest`; Feature: `test_los_bordes_del_dia_de_la_clinica` |
| CA9: lo que ya usaba la hora de la clínica no cambia | Ningún cambio en `EstadosCita`, `ReprogramacionCitas`, `HorarioDentista`, `ReporteCitas`, `app/Auditoria`, `app/Mail` | Suites de regresión listadas en «Estrategia de pruebas» |
| CA10 (abuso): fechas pasadas enviadas a mano | Reglas con `fechaHoy()` y mensajes en español | Feature: `test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica` |
| TM1 | Igual que CA10 | Igual que CA10 |
| TM2 | `ClienteCitasController::agendarForm` | Feature: `test_agendar_ignora_una_fecha_manipulada` |
| RNF: comprobado en la franja de 18:00 a 23:59 y en los bordes | Reloj fijado en todos los tests nuevos | Todos los tests nuevos (21:50 del 17) y `test_los_bordes_del_dia_de_la_clinica` |
| RNF: una sola zona configurada | `RelojClinica::zona()` lee `clinica.zona_horaria`; ningún «hoy», «ahora» ni fecha mostrada sin zona en los archivos tocados | Unit: `test_la_zona_sale_de_la_configuracion` (cambia `clinica.zona_horaria` a `America/Bogota` y `fechaHoy()` la sigue) · T092 y `/review`: grep de `now()`, `Carbon::today()`, `today`, `date(`, `isPast`, `isFuture`, `isToday`, `->age` y `parse($x->creado_en)`/`parse($h->fecha)` sin `delSistema` en `app/Http` y `resources/views`; excepciones: las escrituras de `creado_en`/`actualizado_en` y el vencimiento del enlace de recuperación (`RecuperarContrasenaController`), que siguen en UTC (/analyze A14) |

## Observabilidad
No aplica: la spec no toca datos sensibles, autenticación ni permisos, y no añade logs, eventos ni métricas.
Verificación tras el deploy: ver «Rollout».

## Descubribilidad
No aplica: no se crean ni cambian rutas indexables; en `/` y `/servicios` solo cambia el año del pie, y en
`/registro` la fecha máxima de nacimiento. `PaginasPublicasTest` y `PaginaInicioTest` siguen en verde.

## Rollout
- Feature flag: no aplica. Es una corrección de bajo riesgo, sin datos ni flujos nuevos, y una bandera mantendría a
  la vez el comportamiento erróneo.
- Orden de despliegue: el `/chore` de `npm audit` (v0.20.1, EX5) y la 049 (v0.21.0) ya están en producción; la 050
  sale sola con `deploy/desplegar.sh` según `docs/deployment.md`. No hay migraciones.
- Compatibilidad: sin cambios de datos; la versión anterior y la nueva leen y escriben lo mismo.
- Rollback: `bash deploy/volver.sh <tag anterior>`, sin la copia de la base, porque no hay migración.
- Tras el deploy:
  - Comprobar, solo leyendo, que la base de producción está en UTC:
    `docker compose -f docker-compose.prod.yml exec db sh -c 'mariadb … -e "SELECT @@system_time_zone, NOW(), UTC_TIMESTAMP()"'`,
    con el usuario de la aplicación. Si no está en UTC, se para y se revisa D3 antes de dar la versión por buena.
  - Después de las 18:00 de la clínica, abrir el panel y comprobar que la fecha y las citas de hoy son las del día.
  - Vigilar los 5xx de `deploy/verificar.sh` y de los avisos (spec 032).

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Dónde vive la hora de la clínica.** Opciones: (a) clase estática `App\Agenda\RelojClinica`; (b) cambiar
  `app.timezone`; (c) repetir `Carbon::now(config(...))` en cada sitio. Se elige (a): un solo sitio, fácil de probar y
  de usar en Blade, y sigue el patrón de `HorarioDentista`. (b) quedó descartada en la 026 (plan D4) porque cambia
  todas las fechas del sistema y la purga de la auditoría mide en UTC (spec 020). (c) es la causa de la deuda. No es
  arquitectónica: no lleva ADR.
- **D2 · Comparar con texto en hora local.** `fecha_hora` no tiene zona. Se compara con
  `RelojClinica::momentoLocal()` o `fechaHoy()` como texto, y no con un Carbon, para no depender de cómo el driver
  formatee una fecha con zona.
- **D3 · Fechas del sistema en UTC.** Se convierten al mostrarlas (`delSistema`) y no se migran. Las anteriores a la
  spec también están en UTC, porque la app escribe con `now()` en UTC y la base corre en UTC (comprobado en local el
  2026-11-17). Producción se comprueba en el Rollout.
- **D4 · No refactorizar lo que funciona.** `EstadosCita`, `ReprogramacionCitas`, `HorarioDentista`, `ReporteCitas`,
  la auditoría y los correos siguen con su código. Pasarlos a `RelojClinica` es un `/refactor` aparte, si se quiere.
- **D5 · Mensajes en español** para las tres reglas sin mensaje propio (decisión del usuario, 2026-11-17; spec CA10).
- **D6 · La galería entra en CA7.** `consentimiento_en` es una fecha guardada por el sistema en UTC (cast
  `datetime`) y se muestra sin convertir. Se aplica la misma regla (señalado al usuario al pedir la aprobación
  del plan).
- **D7 · Orden con la 049** (decisión del usuario, 2026-11-17): la 049 se fusiona antes; las tareas de la 050 se
  escriben después de esa fusión.
- **D8 · `npm audit`** (decisión del usuario, 2026-11-17): las 6 altas previas de `picomatch` van en un `/chore` aparte
  en `main`, fuera de esta spec y antes del próximo `/release`.

## Impacto en arquitectura
- `AGENTS.md`, fila de `app/Agenda/`: añadir `RelojClinica` (la hora de la clínica).
- `docs/architecture.md`: la sección de la agenda o de las convenciones de fechas («el sistema guarda en UTC; las citas,
  en hora local; toda referencia a hoy o ahora y toda fecha mostrada pasa por `RelojClinica`»).
- `docs/roadmap.md`: cerrar la deuda D4 de la 026 y 026/R6 al liberar.
- `config/clinica.php`: el comentario de `zona_horaria` dice que decide toda referencia a hoy, ahora y las fechas
  mostradas, no solo «si una cita ya empezó» (/analyze A20).
- `docs/deployment.md`: la fila de `CLINICA_ZONA_HORARIA` describe el mismo alcance (/analyze A20).

## Riesgos y mitigaciones
- **Convertir de más.** Pasar por `delSistema` una fecha que ya está en hora local (`fecha_hora`, `fecha_hora_anterior`,
  `fecha_hora_nueva`, `fecha_cita`, `proxima_visita`) la adelantaría o atrasaría 6 h. Mitigación: la tabla de
  «Cambios por módulo» separa las dos familias, y `test_las_fechas_de_reprogramacion_no_se_convierten` lo fija.
- **Conflicto con la 049** en `citas/index` y `CitasController`. Mitigación: D7, tareas escritas después de fusionarla.
- **Un `now()` olvidado.** Mitigación: el grep de la fila RNF en `/review`, y los tests de CA1 a CA7 en la franja
  donde falla.
- **La base de producción en otra zona.** Mitigación: comprobación de solo lectura en el Rollout antes de dar la
  versión por buena.
- **Vistas con mucho cambio mecánico.** Mitigación: los tests de vistas existentes siguen en verde, y ninguna clase
  ni marcado cambia (P9).
