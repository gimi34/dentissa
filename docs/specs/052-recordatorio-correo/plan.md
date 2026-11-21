---
spec: 052-recordatorio-correo
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-21
---

# Plan · 052 Recordatorio de cita por correo

## Enfoque técnico
Una **revisión periódica** —el comando `dentissa:recordatorios`, programado cada minuto en el `scheduler`
que ya corre `schedule:work` en producción (spec 020)— busca las citas agendadas o confirmadas que empiezan en
las próximas 24 h (hora de la clínica, `RelojClinica`), aún sin recordatorio y de pacientes con cuenta activa y
correo, y envía a cada una el correo de `design.md` (`c2`). Para no duplicar (CA3), cada cita se **reclama** con
un `UPDATE` condicional de `citas.recordatorio_enviado` de 0 a 1 antes de enviar; si el envío falla, se
devuelve a 0 y la siguiente revisión lo reintenta (CA8). Reprogramar pone la marca a 0 (CA4, 028/R11), y la
marca que ya enseña el detalle de la cita del personal pasa a ser real (CA7). El tope de 250 al día (CA11) se
cuenta con el `RateLimiter` en el almacén `database`, como el tope de enlaces de la 041. Todo sale **apagado**
con el interruptor `RECORDATORIOS_CORREO` (decisión del usuario). Sin migraciones ni dependencias nuevas.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y `design.md` (`c2`) aprobados; este plan y `tasks.md` se aprueban antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con su test (Trazabilidad), escrito antes del cambio y visto en rojo. CA7 ya se muestra hoy si la marca vale 1: su parte de «sí se ve» es de caracterización (comprobada rompiendo la vista); la de «deja de verse al reprogramar» cambia comportamiento | test: Trazabilidad; `/implement` anota el fallo previo o la rotura |
| P3 Autorización en el servidor | ➖ | Sin endpoints ni acciones nuevas: el comando no recibe peticiones; el detalle de la cita conserva su middleware y su Gate; el enlace «Ver mis citas» lleva a una ruta con `auth` que ya existe | — |
| P4 Entrada validada y SQL con bindings | ✅ | Sin entrada externa nueva. Selección y reclamo con Query Builder y bindings, sin `DB::raw` ni `whereRaw`; en las plantillas solo salida escapada con dobles llaves | lint: `semgrep p/php` sobre lo tocado · test: `test_el_nombre_del_paciente_sale_escapado` |
| P5 Nada sensible en el repositorio | ✅ | Solo el nombre de la variable `RECORDATORIOS_CORREO` en `.env.example` y `phpunit.xml`; las credenciales de Gmail siguen en `app.env` | lint: `gitleaks detect --no-banner` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Línea base del 2026-11-21 (/review 053): `composer audit` con la regla de la CI, `auditar-npm.mjs` y trivy sin altas ni críticas. Sin dependencias nuevas: `CssToInlineStyles` ya está (041) | manual: SCA en la verificación final y en `/review` |
| P7 Auditoría | ➖ | La spec declara «Auditoría: No aplica»: la aplicación envía al paciente datos de su propia cita; no es un evento de P7 | — |
| P8 Correlación y logs sin datos sensibles | ✅ | Tres eventos técnicos con solo contadores o la clase del error (Observabilidad); ninguno lleva correo, nombre, dentista, fecha ni identificador de cita | test: `test_los_registros_no_llevan_datos_personales` (CA8, CA11) |
| P9 Sistema de diseño | ✅ | El correo sigue `c2`: hoja nueva `correos/recordatorio.css` con valores copiados de tokens, como `correos/acceso.css` (los clientes de correo no entienden `var()`); sin `style=""` en las plantillas. WCAG AA por las puertas de `criterios.json` | test: `test_la_hoja_del_recordatorio_solo_usa_valores_de_tokens` · test: sin `style=` en las plantillas · manual: `evaluate.mjs` con `design/criterios.json` sobre el correo renderizado (D11) |
| P10 Rutas indexables descubribles | ➖ | Sin rutas públicas nuevas ni cambiadas | — |

Resultado: 6 ✅, 4 ➖ y ninguna violación.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `config/recordatorios.php` (nuevo) | `activo` (`RECORDATORIOS_CORREO`, `false` por defecto), `tope_diario` (250) y `antelacion_horas` (24) | Bajo |
| `.env.example`, `phpunit.xml` | `RECORDATORIOS_CORREO=false` en el ejemplo (con su comentario) y `true` en los tests | Bajo |
| `app/Agenda/RecordatoriosCitas.php` (nuevo) | `enviarPendientes(): array` (contadores): si está apagado, no hace nada; selecciona las candidatas (D3), respeta el tope (D5), reclama cada cita (D2), envía `RecordatorioCita` al correo de la cuenta del paciente y, si falla, libera el reclamo; deja los registros de Observabilidad | Medio: corazón de la spec |
| `app/Console/Commands/EnviarRecordatorios.php` (nuevo) | `dentissa:recordatorios`: llama a `RecordatoriosCitas::enviarPendientes()` | Bajo |
| `routes/console.php` | `Schedule::command('dentissa:recordatorios')->everyMinute()->withoutOverlapping()`; el comentario deja de decir que `auditoria:purgar` es la única tarea | Bajo |
| `app/Mail/RecordatorioCita.php` (nuevo) | Mailable con asunto (D7), vista HTML con `acceso.css` + `recordatorio.css` pasadas a estilos en línea con `CssToInlineStyles` y vista de texto, como `EnlaceRestablecimiento` | Bajo |
| `resources/views/correos/recordatorio-cita.blade.php` (nuevo), `recordatorio-cita-texto.blade.php` (nuevo), `recordatorio.css` (nuevo) | El correo de `c2` sobre `correos.marco-acceso`: saludo, tarjeta de la cita (`h1` con la fecha, hora, semana con el día marcado desde `clinica.contacto.horario_semanal`), «Con», «Servicio» (solo si hay), «Dónde», «Ver mis citas», aviso y talón (teléfono y horario); la versión de texto con los mismos datos | Bajo |
| `app/Agenda/ReprogramacionCitas.php` | En el `update` de la reprogramación (l. 122–126), `recordatorio_enviado => 0` (CA4, 028/R11) | Medio: tests de la 028 deben seguir en verde |
| `resources/views/citas/show.blade.php` | Sin cambios: ya muestra «Recordatorio · Enviado» si la marca vale 1 (CA7) | — |
| `deploy/verificar.sh` | «Tareas programadas»: además de `auditoria:purgar`, comprueba que `schedule:list` tiene `dentissa:recordatorios` | Bajo |
| `tests/Feature/RecordatorioCitaTest.php` (nuevo) | Selección, envío, reclamo, reintento, tope, interruptor, cuentas desactivadas y sin correo, registros (CA1–CA6, CA8, CA11) | — |
| `tests/Feature/RecordatorioCitaCorreoTest.php` (nuevo) | Contenido del correo, enlaces, texto, hoja (CA1, CA9, CA10, P9) | — |
| `tests/Feature/CitasPanelDetalleTest.php` | La marca «Recordatorio · Enviado» del detalle (CA7) | Bajo |
| `docs/architecture.md`, `docs/deployment.md`, `docs/security.md`, `docs/observability.md`, `AGENTS.md` | T090–T091: el recordatorio, la tarea programada, la variable, los eventos y la cuota de Gmail | — |

## Contratos y datos
- **Sin rutas, respuestas ni migraciones nuevas.** Se reutiliza `citas.recordatorio_enviado` (`tinyint(1)`,
  `database/dentissa.sql:64`): 1 = «salió el recordatorio de la fecha actual de la cita».
- **Selección** (Query Builder, con bindings): `citas` ⨝ `pacientes` ⨝ `usuarios` (paciente) ⨝ `personal` ⨝
  `usuarios` (dentista) ⟕ `servicios`, con `citas.estado` en (`agendada`, `confirmada`),
  `citas.recordatorio_enviado = 0`, `RelojClinica::momentoLocal() < citas.fecha_hora ≤ momentoLocal + 24 h`,
  `usuarios.activo = 1` y `usuarios.email` ni nulo ni vacío; orden por `fecha_hora` y como mucho lo que quede
  del tope del día.
- **Reclamo:** `UPDATE citas SET recordatorio_enviado = 1, actualizado_en = <el leído> WHERE id = ? AND
  recordatorio_enviado = 0 AND fecha_hora = <la leída> AND estado IN (…)`; solo si afecta a una fila se envía.
  Liberación tras un fallo: el `UPDATE` inverso con las mismas condiciones de `fecha_hora`.
- **Correo** (`design.md`, `c2`): destinatario el correo de la cuenta del paciente; asunto «Tu cita es
  {hoy|mañana}, {jueves 26 de noviembre}, a las {10:30}» (D7); un solo enlace, `route('cliente.citas')`
  absoluto desde `APP_URL` y sin parámetros (`https://dentissa.app/cliente/citas`).
- **Configuración:** `recordatorios.activo`, `recordatorios.tope_diario`, `recordatorios.antelacion_horas`;
  variable `RECORDATORIOS_CORREO` en `app.env` de producción (T091).
- **Pantallas y estados** (`design.md`): el correo con servicio y sin servicio a 320 y 390 px, y su versión de
  texto; el detalle de la cita del personal sin cambios visuales. Accesibilidad medible: un solo `h1` (la
  fecha), tablas de maquetación con `role="presentation"`, la semana con `aria-hidden`, logo con `alt`,
  contraste por las puertas de `criterios.json`.

## Estrategia de pruebas
- **PHPUnit Feature** (`DatabaseTransactions`, `dentissa_test`) con `Mail::fake()` y el tiempo fijado
  (`$this->travelTo()` sobre la hora de la clínica), las citas del trait `EscenarioEstadosCita` y
  `Artisan::call('dentissa:recordatorios')`: cada CA con su caso; el reintento con
  `Mail::shouldReceive('to')->andThrow(TransportException)` como `CorreosRestablecimientoTest`; los registros con
  `Tests\Support\CapturaLogs`; el tope con `recordatorios.tope_diario` reducido en el test.
- **Concurrencia de CA3:** dos revisiones sobre la misma cita, simulando que otra ya la reclamó entre la lectura
  y el reclamo (el `UPDATE` condicional afecta a 0 filas y no se envía).
- **Correo:** el HTML renderizado del Mailable leído con `DOMXPath` (datos, enlace único, sin notas, sin
  `style=` en las plantillas, nombre escapado) y la versión de texto; la hoja contra los tokens de
  `system.md`, como `test_la_hoja_solo_usa_valores_de_tokens` de la 041.
- **Planificador:** el `Schedule` tiene `dentissa:recordatorios` cada minuto y sin solaparse.
- **Diseño en el navegador:** `evaluate.mjs` con `design/criterios.json` sobre el HTML del Mailable renderizado
  y guardado (con y sin servicio), contra `candidatos/c2.html` (D11). Verificación manual de la tarea del
  correo: la CI no ejecuta navegador.
- **Regresión:** los tests de la 028 (`ReprogramarCita*`), de la 041 (`CorreosRestablecimientoTest`) y del
  detalle de cita.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Divulgación: el recordatorio llega a otra persona o lleva datos de otra cita | A01:2025 Broken Access Control | `RecordatoriosCitas` | Destinatario y datos salen de la misma fila de la cita (su paciente y su cuenta); ningún dato de la petición | `test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos` |
| TM2 | Elevación: el enlace del correo da acceso a la cuenta o actúa sobre la cita (CA9) | A07:2025 Authentication Failures | plantillas | Un solo enlace, a `route('cliente.citas')` sin parámetros; sin sesión, el middleware `auth` lleva al inicio de sesión | `test_un_solo_enlace_a_mis_citas_sin_parametros` · `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` |
| TM3 | Divulgación de datos de salud en un buzón ajeno (CA10) | A01:2025 Broken Access Control | selección y plantillas | La selección solo lee nombre, fecha, dentista, servicio y contacto; nunca `notas_paciente`, `notas_internas` ni expediente | `test_el_correo_no_lleva_notas_ni_expediente` |
| TM4 | Denegación de servicio: citas en masa agotan la cuota de Gmail que comparten la 041 y la 032 (CA11) | A06:2025 Insecure Design | `RecordatoriosCitas` | Tope de 250 al día (`RateLimiter`, clave por día de la clínica) | `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` |
| TM5 | Divulgación en los registros técnicos (P8, CA8, CA11) | A09:2025 Security Logging and Alerting Failures | `RecordatoriosCitas` | Solo contadores y la clase del error | `test_los_registros_no_llevan_datos_personales` |
| TM6 | Repudio / correo duplicado por dos revisiones a la vez (CA3) | A06:2025 Insecure Design | reclamo | `UPDATE` condicional atómico + `withoutOverlapping()` | `test_dos_revisiones_no_envian_dos_veces` |
| TM7 | Manipulación: el nombre del paciente o del dentista con HTML rompe o inyecta en el correo | A05:2025 Injection | plantillas | salida escapada con dobles llaves en las plantillas; el asunto no lleva nombres | `test_el_nombre_del_paciente_sale_escapado` |
| TM8 | Fallo del servicio de correo que deja la cita como avisada (CA8) | A10:2025 Mishandling of Exceptional Conditions | `RecordatoriosCitas` | `try/catch` por cita: libera el reclamo, registra la clase del error y sigue con las demás | `test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta` |

Casos de abuso de la spec: CA9 → TM2; CA10 → TM3; CA11 → TM4; registros técnicos → TM5.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: un correo con nombre, día y hora, dentista, servicio si hay, dirección, teléfono y «Ver mis citas», entre 24 h y 23 h 50 min antes | `RecordatoriosCitas`, `RecordatorioCita`, plantillas, comando, `routes/console.php` | `RecordatorioCitaTest::test_sale_cuando_faltan_24_horas` (a 24 h 1 min no, a 23 h 59 min sí) · `test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos` · `RecordatorioCitaCorreoTest::test_el_correo_lleva_los_datos_de_la_cita` (con y sin servicio) · `test_la_version_de_texto_lleva_los_mismos_datos` · `test_la_revision_corre_cada_minuto` · verificación manual: `evaluate.mjs` sobre el correo renderizado contra `candidatos/c2.html` a 320 y 390 px |
| CA2: cita creada o reprogramada a menos de 24 h → en menos de 10 min; si empieza antes, no | `RecordatoriosCitas` | `test_una_cita_a_menos_de_24_horas_sale_en_la_siguiente_revision` · `test_una_cita_que_ya_empezo_no_recibe_recordatorio` |
| CA3: no hay segundo recordatorio | reclamo, `withoutOverlapping` | `test_una_segunda_revision_no_reenvia` · `test_dos_revisiones_no_envian_dos_veces` |
| CA4: al reprogramar deja de constar como enviado y llega uno nuevo | `ReprogramacionCitas` | `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` |
| CA5: canceladas, en curso, completadas, «no asistió» o ya empezadas no reciben; cancelar después no envía nada | selección | `test_solo_agendadas_y_confirmadas_reciben_recordatorio` · `test_cancelar_despues_del_recordatorio_no_envia_otro_correo` |
| CA6: cuenta desactivada no recibe | selección | `test_una_cuenta_desactivada_no_recibe_recordatorio` |
| CA7: «Recordatorio · Enviado» en el detalle si salió, y no si no o tras reprogramar | `ReprogramacionCitas` (la vista no cambia) | `CitasPanelDetalleTest::test_el_detalle_muestra_el_recordatorio_enviado` (caracterización, comprobada rompiendo la vista) · `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` |
| CA8: fallo → no marcada, reintento, registro sin datos | `RecordatoriosCitas` | `test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta` · `test_los_registros_no_llevan_datos_personales` |
| CA9 (abuso): el enlace solo lleva al inicio de sesión; nada que dé acceso o actúe sobre la cita | plantillas | `RecordatorioCitaCorreoTest::test_un_solo_enlace_a_mis_citas_sin_parametros` · `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` |
| CA10 (abuso): sin notas ni expediente | selección, plantillas | `RecordatorioCitaCorreoTest::test_el_correo_no_lleva_notas_ni_expediente` |
| CA11 (abuso): 250 al día; el exceso sale al día siguiente; registro sin datos | `RecordatoriosCitas`, `config/recordatorios.php` | `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` · `test_los_registros_no_llevan_datos_personales` |
| Cuentas sin correo (spec 054, decisión del usuario): se saltan sin fallo ni registro | selección | `test_una_cuenta_sin_correo_se_salta_sin_registro` (con `usuarios.email` nulo de la 054, D9) |
| Interruptor apagado: no envía nada | `config/recordatorios.php`, `RecordatoriosCitas` | `test_apagado_no_envia_nada` · `test_apagado_por_defecto` |
| TM1 | `RecordatoriosCitas` | `test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos` |
| TM2 | plantillas | `test_un_solo_enlace_a_mis_citas_sin_parametros` · `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` |
| TM3 | selección, plantillas | `test_el_correo_no_lleva_notas_ni_expediente` |
| TM4 | `RecordatoriosCitas` | `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` |
| TM5 | `RecordatoriosCitas` | `test_los_registros_no_llevan_datos_personales` |
| TM6 | reclamo | `test_dos_revisiones_no_envian_dos_veces` |
| TM7 | plantillas | `test_el_nombre_del_paciente_sale_escapado` |
| TM8 | `RecordatoriosCitas` | `test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta` |
| RNF entrega (24 h–23 h 50 min; menos de 10 min) | comando cada minuto | `test_la_revision_corre_cada_minuto` · verificación manual en T095: con el interruptor encendido, una cita de prueba a 24 h 2 min recibe el correo en los minutos siguientes |
| RNF accesibilidad (contraste AA, 320 px, `alt`, versión de texto) | plantillas, hoja | `test_la_version_de_texto_lleva_los_mismos_datos` · `test_el_logo_lleva_texto_alternativo` · verificación manual: puertas de `evaluate.mjs` sobre el correo renderizado |
| RNF registros (P8) | `RecordatoriosCitas` | `test_los_registros_no_llevan_datos_personales` |
| RNF secretos (P5) | — | gitleaks en la verificación final |
| RNF volumen (≤ 250/día dentro de ~500 de Gmail) | `config/recordatorios.php` | `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` |

## Observabilidad
Logs técnicos nuevos (canal por defecto; el comando no tiene `request_id` porque no hay petición, como
`auditoria:purgar` y `dentissa:vigilar`):
- `recordatorio.revision` (`info`), solo si la revisión hizo algo: `{enviados, fallidos, aplazados}`.
- `recordatorio.correo_fallido` (`error`) por cada envío fallido: `{error: class_basename($e)}`.
- `recordatorio.tope_diario` (`warning`), una vez al día al alcanzar el tope: `{tope: 250}`.
Ninguno lleva correo, nombre, dentista, fecha, servicio ni identificador de la cita (CA8, CA11, P8).
Sin eventos de auditoría (spec: «Auditoría: No aplica»). Métricas: sin cambios; los `error` los recoge el aviso
automático `dentissa-vigilar` como los demás. Verificación post-deploy: `schedule:list` con
`dentissa:recordatorios` (`verificar.sh`) y, al encenderlo, `recordatorio.revision` en el log tras la cita de
prueba de T095.

## Descubribilidad
No aplica: la spec no crea ni cambia rutas públicas (el enlace del correo lleva a una ruta privada existente).

## Rollout
- **Feature flag:** `RECORDATORIOS_CORREO`, `false` por defecto y en producción al desplegar (decisión del
  usuario, 2026-11-21). Se enciende editando `app.env` y `bash deploy/aplicar-entorno.sh app` (recrea `app` y
  `scheduler`); se apaga igual, sin volver de versión.
- **Orden de despliegue:** un solo paso (`deploy/desplegar.sh vX.Y.Z`), sin migraciones; el `scheduler` se
  reconstruye con la imagen y ve la tarea nueva. Después, encender con la cita de prueba de T095.
- **Compatibilidad:** la versión anterior ignora la tarea y la marca; la marca puesta por la nueva no la
  cambia nadie en la anterior (salvo el reinicio al reprogramar, que la anterior no hace: 028/R11 vuelve hasta
  volver a subir).
- **Rollback:** primero apagar el interruptor; si hace falta, `bash deploy/volver.sh <tag anterior>`, sin copia
  de la base (no hay cambios de esquema).
- **Métricas a vigilar:** `recordatorio.correo_fallido` y `recordatorio.tope_diario` en el log; avisos de
  `dentissa-vigilar`; en Gmail, la cuota diaria (unos 500: 250 recordatorios + 200 enlaces de la 041 + avisos).

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Revisión periódica.** Opciones: (a) temporizador de systemd nuevo como `dentissa-vigilar`; (b) tarea del
  planificador de Laravel en el `scheduler` que ya corre `schedule:work`. **(b)**: sin unidades nuevas en el
  servidor, se despliega con la imagen y `verificar.sh` ya comprueba `schedule:list`. Cada minuto, para cumplir
  el margen de 10 min (CA1, CA2) con holgura.
- **D2 · Sin duplicados.** Opciones: (a) bloqueo de fila con `lockForUpdate`; (b) `UPDATE` condicional que
  reclama la cita antes de enviar. **(b)**: atómico sin transacción larga mientras se habla con el SMTP. Se
  repite `actualizado_en` con el valor leído para que la marca no cambie la fecha de modificación de la cita
  (la columna tiene `ON UPDATE`), sin `DB::raw`. Si se reprograma entre el reclamo y el envío, puede salir un
  correo de la fecha antigua; el reinicio de CA4 manda después el de la nueva (riesgo aceptado, Riesgos).
- **D3 · Qué cuenta como «faltan 24 h».** `fecha_hora` está en hora local de la clínica: se compara con
  `RelojClinica::momentoLocal()` y `RelojClinica::ahora()->addHours(24)`. Una cita entra en cuanto queda a 24 h
  o menos y aún no empieza: cubre CA1 (sale en el minuto siguiente a las 24 h) y CA2 (creada o reprogramada
  más tarde, sale en la revisión siguiente).
- **D4 · Marca.** Opciones: (a) `recordatorio_enviado` tal cual, con reinicio al reprogramar; (b) una columna nueva
  con la fecha para la que se envió. **(a)**: la única escritura de `fecha_hora` tras crear la cita es
  `ReprogramacionCitas` (comprobado: `EstadosCita` solo cambia el estado y `CitasController` las notas), y así no
  hay migración. Si otra spec cambiara la fecha por otro camino, tendría que reiniciar la marca (lo vigila el
  test de CA4).
- **D5 · Tope diario.** Opciones: (a) contar en la base; (b) `RateLimiter` en el almacén `database`, clave por día
  de la clínica (`recordatorios:dia:AAAA-MM-DD`), como `restablecimiento:dia` de la 041. **(b)**: sin migración y
  con el patrón del proyecto. Si la caché se vaciara a mitad del día, ese día podrían salir más de 250 (riesgo
  bajo, Riesgos).
- **D6 · Interruptor.** `RECORDATORIOS_CORREO` en `config/recordatorios.php`, como `RECUPERAR_CONTRASENA` de la
  041; apagado por defecto (decisión del usuario, 2026-11-21).
- **D7 · «Hoy» o «mañana».** El diseño muestra «mañana»; con CA2 la cita puede ser el mismo día. Asunto y saludo
  dicen «hoy» si la cita es el día de la clínica en curso y «mañana» si es el siguiente. La fecha se escribe con
  `translatedFormat` en `es` (la app está en `en` por defecto).
- **D8 · Plantilla y hoja.** El correo extiende `correos.marco-acceso` y suma `correos/recordatorio.css` a
  `acceso.css` antes de `CssToInlineStyles`; la semana se pinta desde `clinica.contacto.horario_semanal` (días sin
  tramo, con borde discontinuo) y el talón usa el mismo horario en texto que la página de inicio. Valores de
  `design.md`; si hace falta otro, `/design-spec 052 --fix`.
- **D9 · Orden con la 054.** La 052 se implementa después de fusionar la 054 en `main` (decisión del usuario,
  2026-11-21): el test de la cuenta sin correo usa `usuarios.email` nulo. Si la 054 no está en `main` al empezar
  `/implement`, la tarea de preparación queda bloqueada.
- **D10 · Una sola spec.** 11 criterios y 3 specs extendidas, por encima del tamaño en que se propone dividir: se
  mantiene junta (decisión del usuario, 2026-11-21).
- **D11 · Medir el diseño sobre el correo real.** El HTML del Mailable (`RecordatorioCita::render()`) con una cita
  de prueba se guarda y se mide con `evaluate.mjs` y `design/criterios.json` (y la copia sin servicio), como se
  midieron los candidatos.
- Ninguna decisión es arquitectónica: sin ADR (D1 sigue la tarea programada de la 020; el envío, el ADR 0008).

## Impacto en arquitectura
- `docs/architecture.md`: el módulo `app/Agenda/RecordatoriosCitas.php`, el comando y la tarea programada; el
  Mailable en «Correo».
- `AGENTS.md` (mapa del repositorio): `RecordatoriosCitas` en `app/Agenda/`, `RecordatorioCita` en `app/Mail/` y
  el comando en `app/Console/Commands/`.
- `docs/deployment.md`: la variable `RECORDATORIOS_CORREO`, encenderla y apagarla, y `dentissa:recordatorios`
  en el `scheduler`.
- `docs/security.md`: tareas programadas (l. 88) y el uso de la cuenta de Gmail (TM13 de la 032/ADR 0008) con
  los recordatorios.
- `docs/observability.md`: los tres eventos técnicos.
- Specs extendidas (al liberar): 003 y 004 (el recordatorio deja de estar fuera de alcance), 028 (R11 cerrado).

## Riesgos y mitigaciones
- **Reprogramar entre el reclamo y el envío** (D2): puede salir un correo con la fecha antigua; el reinicio
  manda el de la nueva. Ventana de milisegundos; aceptado.
- **Vaciar la caché a mitad del día** (D5): el tope se reinicia ese día. Bajo; la cuota de Gmail (~500) deja
  margen.
- **Cuota compartida de Gmail:** 250 + 200 (041) + avisos (032) caben en ~500; si un día se acercara, el aviso
  `dentissa-vigilar` recogería los `error` de envío.
- **Tests de la 028:** añadir `recordatorio_enviado` al `update` de la reprogramación no debe cambiar lo que
  asertan; se ejecutan en la tarea de `ReprogramacionCitas`.
- **La 054 aún no está en `main`** (D9): la implementación espera a su fusión.
- **Hora del servidor:** el `scheduler` usa la zona de la app; toda comparación va por `RelojClinica` (050).
