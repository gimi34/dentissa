---
spec: 052-recordatorio-correo
plan: plan.md
impl_base: 4427723
status: approved
---

# Tareas · 052 Recordatorio de cita por correo

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza.

Tests con `php vendor/bin/phpunit --filter <Clase>` en un contenedor `dentissa-app` con el código del
worktree (suite completa al cerrar cada fase) y Pint sobre los archivos tocados. Los tests de
`RecordatorioCitaTest` comparten archivo: T010 y T011 van en serie.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ninguna tarea de código antes de aprobar este `tasks.md`; T001 trae `main` con la 054 (D9) antes de
  tocar código.
- ✅ P2: cada tarea de implementación depende de su test (T010–T013), que falla antes y se anota; lo que ya se
  cumple hoy (la marca del detalle con valor 1, `/cliente/citas` sin sesión) se comprueba rompiendo lo que
  protege. Los controles TM1–TM8 se comprueban rompiéndolos en su tarea de implementación.
- ➖ P3: ninguna tarea cambia rutas, middleware ni Gates.
- ✅ P4: la selección y el reclamo con Query Builder (T023); semgrep en T092.
- ✅ P5: solo el nombre de la variable en `.env.example` y `phpunit.xml` (T020); gitleaks en T092.
- ✅ P6: ninguna tarea añade dependencias; SCA en T092.
- ➖ P7: sin eventos de auditoría.
- ✅ P8: los registros sin datos personales los prueba T011 antes de T023.
- ✅ P9: la hoja del correo con valores de tokens la prueba T012 antes de T022; `evaluate.mjs` en T022.
- ➖ P10: sin rutas públicas.

## Preparación
- [x] T001 Poner la rama al día con `origin/main` cuando la spec 054 esté fusionada (D9), resolver los choques, aplicar sus migraciones en `dentissa_test` y fijar la línea base de la suite — rama `feature/052-recordatorio-correo` — hecho cuando: la rama contiene la 054 (`usuarios.email` admite nulo en `dentissa_test`), no quedan conflictos y la suite completa pasa (resultado anotado)
  - nota: desbloqueada 2026-11-21 — la 054 está en `main` (origin/main bce170b, PR #106, v0.26.0)
  - nota: rebase sobre `origin/main` bce170b sin choques; en `dentissa_test` se aplicaron las migraciones pendientes `2026_11_18_000000_corregir_ciudades_de_pacientes` (051) y `2026_11_20_000000_correo_opcional_en_usuarios` (054: `usuarios.email` admite nulo). Línea base: 1591 tests, 0 fallos (7 min 22 s), con `vendor/bin/phpunit` en un contenedor `dentissa-app` con el código del worktree.

## Tests (antes de implementar)
- [x] T010 Escribir en `RecordatorioCitaTest` (con `Mail::fake()`, el tiempo fijado con `travelTo` en la hora de la clínica, las citas de `EscenarioEstadosCita` y `Artisan::call('dentissa:recordatorios')`) los casos de selección: `test_sale_cuando_faltan_24_horas` (a 24 h 1 min no sale; a 23 h 59 min sale un único `RecordatorioCita`), `test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos` (destinatario el correo de la cuenta del paciente de la cita y no el de otro paciente con cita a otra hora; TM1), `test_una_cita_a_menos_de_24_horas_sale_en_la_siguiente_revision` (creada a 3 h), `test_una_cita_que_ya_empezo_no_recibe_recordatorio`, `test_solo_agendadas_y_confirmadas_reciben_recordatorio` (en curso, completada, cancelada y no asistió no), `test_una_cuenta_desactivada_no_recibe_recordatorio`, `test_una_cuenta_sin_correo_se_salta_sin_registro` (`usuarios.email` nulo: sin correo y sin ningún log, con `CapturaLogs`), `test_apagado_no_envia_nada` (`recordatorios.activo` a false) y `test_apagado_por_defecto` (sin la variable, `config('recordatorios.activo')` es `false`) — tests/Feature/RecordatorioCitaTest.php — hecho cuando: fallan porque el comando y la configuración no existen (anotado) — cubre: CA1, CA2, CA5, CA6 — depende: T001
  - nota: fallan por la razón esperada: `dentissa:recordatorios` no existe (8) y `config/recordatorios.php` no existe (`test_apagado_por_defecto`). Reloj fijado en el miércoles 25 de noviembre de 2026, 10:30 de la clínica; el destinatario y los datos se comprueban con `hasTo` y el HTML renderizado del correo (TM1).
- [x] T011 Añadir a `RecordatorioCitaTest` los casos de envío: `test_una_segunda_revision_no_reenvia`, `test_dos_revisiones_no_envian_dos_veces` (otra revisión reclama la cita entre la lectura y el reclamo: 0 correos de la segunda; TM6), `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` (con `ReprogramacionCitas`: la marca vuelve a 0 y sale el de la fecha nueva), `test_cancelar_despues_del_recordatorio_no_envia_otro_correo`, `test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta` (`Mail::shouldReceive('to')->andThrow(TransportException)`: la marca sigue en 0 y la revisión siguiente lo envía; TM8), `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` (`recordatorios.tope_diario` a 2 con 3 citas: salen 2; al día siguiente, la tercera si no ha empezado; TM4), `test_los_registros_no_llevan_datos_personales` (`recordatorio.revision`, `recordatorio.correo_fallido` y `recordatorio.tope_diario` con solo contadores o la clase del error, sin correo, nombre, dentista, fecha, servicio ni id de la cita; TM5), `test_la_marca_no_cambia_actualizado_en` (D2) y `test_la_revision_corre_cada_minuto` (`Schedule` con `dentissa:recordatorios` cada minuto y `withoutOverlapping`) — tests/Feature/RecordatorioCitaTest.php — hecho cuando: fallan porque el comando no existe y la reprogramación no reinicia la marca (anotado) — cubre: CA3, CA4, CA5, CA8, CA11 — depende: T010
  - nota: fallan por la razón esperada: `dentissa:recordatorios` no existe (9) y no hay tarea programada (`test_la_revision_corre_cada_minuto`: 0 eventos).
  - nota: A2 — `test_un_error_al_reclamar_no_deja_datos_en_el_registro`: un `DB::listen` lanza un error con el id y la fecha de la cita tras el `update` del reclamo; la cita queda sin avisar, la otra sale y el log no lleva ni el id ni la fecha.
  - nota: A3 — la carrera de CA3 se simula con un `DB::listen` que marca la cita justo después de la lectura de candidatas: la revisión no envía nada.
  - nota: A4 — en el test del tope, dos revisiones el mismo día dejan una sola línea `recordatorio.tope_diario`.
- [x] T012 [P] Escribir `RecordatorioCitaCorreoTest` sobre el HTML renderizado de `RecordatorioCita` (con `DOMXPath`) y su versión de texto: `test_el_correo_lleva_los_datos_de_la_cita` (nombre, «jueves 26 de noviembre de 2026», «10:30», dentista, servicio solo si la cita lo tiene, dirección y teléfono de `clinica.contacto`, «Ver mis citas»), `test_el_asunto_dice_hoy_o_manana` (D7), `test_la_version_de_texto_lleva_los_mismos_datos`, `test_el_logo_lleva_texto_alternativo`, `test_un_solo_enlace_a_mis_citas_sin_parametros` (un único `<a>`, `href` = `APP_URL` + `/cliente/citas`; TM2), `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` (TM2), `test_el_correo_no_lleva_notas_ni_expediente` (notas del paciente e internas con un texto marcador ausente del HTML y del texto; TM3), `test_el_nombre_del_paciente_sale_escapado` (TM7), `test_la_hoja_del_recordatorio_solo_usa_valores_de_tokens` y `test_las_plantillas_no_llevan_estilos_en_linea` — tests/Feature/RecordatorioCitaCorreoTest.php — hecho cuando: fallan porque `RecordatorioCita` y sus plantillas no existen, y el de `/cliente/citas` sin sesión pasa hoy y se comprueba quitando un momento el middleware `auth` de la ruta (anotado) — cubre: CA1, CA9, CA10 — depende: T001
  - nota: 9 fallan por la razón esperada: `RecordatorioCita` no existe (6), `dentissa:recordatorios` no existe (CA10, que se prueba con la revisión real y pasa en T023), la hoja y las plantillas no existen (2). `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` pasa hoy: comprobado quitando un momento el middleware `auth` del grupo `cliente` en `routes/web.php` (falla) y restaurado.
  - nota: A1 — `test_el_correo_lleva_los_datos_de_la_cita` exige «Diego Primero» y ningún «Dr.» ni «Dra.». El Mailable recibe `nombre`, `fechaHora`, `dentista` y `servicio` (solo los datos de CA1); la hoja se comprueba con los traits `ReglasCss` y `TokensAcceso` de la 041.
- [x] T013 [P] Añadir a `CitasPanelDetalleTest` `test_el_detalle_muestra_el_recordatorio_enviado` (con la marca a 1, «Recordatorio» y «Enviado» en el detalle del personal; con 0, nada) y `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` — tests/Feature/CitasPanelDetalleTest.php — hecho cuando: el de reprogramar falla porque la marca no se reinicia; el de la marca pasa hoy y se comprueba quitando un momento la fila de `show.blade.php` (anotado) — cubre: CA7 — depende: T001
  - nota: `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` falla por la razón esperada (la reprogramación no reinicia la marca: «Recordatorio Enviado» sigue). `test_el_detalle_muestra_el_recordatorio_enviado` pasa hoy (admin, asistente y dentista): comprobado cambiando un momento `@if($cita->recordatorio_enviado)` por `@if(false)` en `show.blade.php` (falla) y restaurado.

## Implementación
- [x] T020 Crear `config/recordatorios.php` (`activo` desde `RECORDATORIOS_CORREO`, `false` por defecto; `tope_diario` 250; `antelacion_horas` 24), añadir `RECORDATORIOS_CORREO=false` con su comentario a `.env.example` y `true` a `phpunit.xml` — config/recordatorios.php, .env.example, phpunit.xml — hecho cuando: `test_apagado_por_defecto` pasa — depende: T010
  - nota: `test_apagado_por_defecto` en verde; Pint PASS en `config/recordatorios.php`.
- [x] T021 Crear el Mailable y sus plantillas según `design.md` (`c2`) y el plan (D7, D8): `RecordatorioCita` (asunto «Tu cita es {hoy|mañana}, …», fecha con `translatedFormat` en `es`, HTML con `acceso.css` y `recordatorio.css` pasadas a estilos en línea con `CssToInlineStyles`, vista de texto), la plantilla HTML sobre `correos.marco-acceso` (saludo, tarjeta con el `h1` de la fecha, la hora y la semana desde `clinica.contacto.horario_semanal`, «Con», «Servicio» solo si hay, «Dónde», «Ver mis citas» a `route('cliente.citas')`, aviso y talón con teléfono y horario) y la de texto — app/Mail/RecordatorioCita.php, resources/views/correos/recordatorio-cita.blade.php, resources/views/correos/recordatorio-cita-texto.blade.php — hecho cuando: los tests de T012 pasan salvo el de la hoja; TM2, TM3 y TM7 comprobados rompiéndolos (un segundo enlace, `notas_internas` en la plantilla, `{!! !!}` en el nombre; anotado) — cubre: CA1, CA9, CA10 — depende: T012
  - nota: 8 de 10 tests de T012 en verde; quedan el de CA10 (pasa con la revisión de T023) y el de la hoja (T022). Por eso, en esta tarea el correo se pasa a línea solo con `acceso.css`; T022 suma `recordatorio.css` al Mailable. Pint PASS.
  - nota: A1 — el dentista se escribe con el nombre que recibe el Mailable, sin título; el test exige «Diego Primero» y ningún «Dr.» ni «Dra.».
  - nota: TM2 comprobado rompiéndolo (el teléfono como enlace `tel:`: falla «Un único enlace») y TM7 (`{!! $nombre !!}`: falla el escapado); restaurados. TM3 (las notas) se comprueba en T023, que es donde la revisión elige qué datos pasan al correo.
- [x] T022 Crear la hoja del correo con las reglas de `c2` (tarjeta, semana, etiquetas, datos, talón y aviso) y los valores copiados de tokens — resources/views/correos/recordatorio.css — hecho cuando: T012 pasa entero y `evaluate.mjs` con `design/criterios.json` pasa sobre el HTML de `RecordatorioCita` renderizado con una cita de prueba con servicio y, con la copia de criterios sin «Limpieza dental», sin servicio (D11; informes en `.ai/inspect/`, anotado) · verificación manual: el correo renderizado a 320 y 390 px junto a `docs/specs/052-recordatorio-correo/design/candidatos/c2.html` y `c2-sin-servicio.html`, y su versión de texto — cubre: CA1 — depende: T021
  - nota: T012 en verde salvo el de CA10 (pasa con la revisión de T023); Pint PASS. Archivo extra justificado: `app/Mail/RecordatorioCita.php` suma `recordatorio.css` a `acceso.css` antes de `CssToInlineStyles` (en T021 solo llevaba `acceso.css`, para que sus tests pasaran sin la hoja).
  - nota: dos valores de `c2` que no eran tokens se llevan al token más cercano: interlineado de los datos 1.4 → 1.5 (`--leading-publico`) y margen entre datos 12 px → 14 px (`--space-5`).
  - nota: A5 — HTML de `RecordatorioCita` renderizado con `renderForAssertions()` (con y sin servicio), guardado en `.ai/inspect/052-build/` con el logo en `/public/images/logo-main.png`, servido con `servir.mjs` y medido con `evaluate.mjs`: 11/11 puertas y 2/2 KPIs con servicio (`.ai/inspect/052-build/con`) y sin servicio (`.ai/inspect/052-build/sin`, con la copia de criterios sin «Limpieza dental»).
  - nota: verificación manual OK 2026-11-21 (usuario): el correo real junto a `c2.html` y `c2-sin-servicio.html` a 320 y 390 px; el dentista sin «Dra.» (A1).
- [x] T023 Crear la revisión: `RecordatoriosCitas::enviarPendientes()` (interruptor; selección de D3 con Query Builder y bindings, sin notas ni expediente; tope por día de la clínica con `RateLimiter`, D5; reclamo con `UPDATE` condicional que repite `actualizado_en`, D2; envío al correo de la cuenta del paciente; `try/catch` por cita que libera el reclamo; los tres registros de Observabilidad sin datos personales), el comando `dentissa:recordatorios` y su programación cada minuto con `withoutOverlapping()` en `routes/console.php` (y el comentario que decía que `auditoria:purgar` era la única tarea) — app/Agenda/RecordatoriosCitas.php, app/Console/Commands/EnviarRecordatorios.php, routes/console.php — hecho cuando: T010 y T011 pasan salvo `test_reprogramar_reinicia_el_recordatorio_y_sale_otro`; TM1, TM4, TM5, TM6 y TM8 comprobados rompiéndolos (destinatario de otra fila, sin tope, el correo en el log, sin condición en el reclamo, sin liberar tras el fallo; anotado) — cubre: CA1, CA2, CA3, CA5, CA6, CA8, CA11 — depende: T011, T020, T022
  - nota: `RecordatorioCitaTest` y `RecordatorioCitaCorreoTest` en verde salvo `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` (T024); Pint PASS en los 3 archivos. Se corrigieron dos errores de preparación de los tests de T011, sin quitar aserciones: la cita aplazada del test de registros a 20 h (a 3 h ya había empezado al día siguiente) y olvidar el mock de `Mail` antes de volver a `Mail::fake()`.
  - nota: A2 — todo el tratamiento de una cita (reclamo y envío) va dentro del `try/catch`: libera el reclamo y deja solo `recordatorio.correo_fallido` con la clase del error. A4 — `recordatorio.tope_diario` una vez por día de la clínica con `Cache::add` sobre una clave del día.
  - nota: TM1, TM3, TM4, TM5, TM6 y TM8 comprobados rompiéndolos con `scratchpad/d052/romper1.py` (el correo del dentista como destinatario; las notas internas como servicio; sin tope; el correo en el log; el reclamo sin condiciones; sin liberar tras el fallo): cada test falla y el archivo quedó igual (mismo SHA-1). TM6 no fallaba al principio, porque MariaDB no cuenta como afectada una fila que no cambia; el test se reforzó con un caso real (tras el reclamo de otra revisión, el personal edita las notas internas y cambia `actualizado_en`) y ahora falla al quitar las condiciones del reclamo.
- [x] T024 Reiniciar la marca al reprogramar: `recordatorio_enviado => 0` en el `update` de la reprogramación (l. 122–126) — app/Agenda/ReprogramacionCitas.php — hecho cuando: `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` y `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` pasan y los tests de la 028 (`ReprogramarCita*`) siguen en verde — cubre: CA4, CA7 — depende: T013, T023
  - nota: los dos tests de reprogramar en verde; los de la 028 (`--filter Reprogramar`: 106 tests, incluidos los de esta spec) en verde; Pint PASS.
- [x] T025 [P] Añadir a «Tareas programadas» de `verificar.sh` que `schedule:list` tenga `dentissa:recordatorios`, como `auditoria:purgar` — deploy/verificar.sh — hecho cuando: `bash -n deploy/verificar.sh` sin errores y la comprobación nueva junto a la de `auditoria:purgar` — depende: T023
  - nota: `bash -n` sin errores. El bloque de `auditoria:purgar` se deja tal cual (lo fija `ConfiguracionProduccionTest::test_verificar_comprueba_el_scheduler`, spec 020) y se añade otro igual para `dentissa:recordatorios`; `ConfiguracionProduccionTest` en verde. Se ejecutará en producción en T097.

- [x] T026 Ajustar el test de la spec 020 que exige una sola tarea programada: exactamente dos, `auditoria:purgar` y `dentissa:recordatorios`, y sigue sin `dentissa:vigilar` — tests/Feature/Auditoria/InmutabilidadAuditoriaTest.php — hecho cuando: el test pasa con las dos tareas y falla si sobra o falta una — depende: T023
  - nota: añadida durante /implement (decisión del usuario, 2026-11-21): la suite completa daba un único fallo, `test_la_programacion_solo_tiene_la_purga`, porque la 052 añade la segunda tarea del plan (D1); el test pasa a exigir las dos tareas y conserva la comprobación de `dentissa:vigilar`.
  - nota: comprobado comentando un momento la programación de `dentissa:recordatorios` en `routes/console.php`: el test falla («Dos tareas programadas…»); restaurado. Pint PASS.

- [ ] T027 Añadir a `RecordatorioCitaTest` `test_el_bloqueo_de_la_revision_caduca_pronto` (el evento de `dentissa:recordatorios` en el `Schedule` tiene `withoutOverlapping` con `expiresAt` = 10 minutos) y `test_con_el_correo_caido_se_intenta_una_vez_por_revision` (dos candidatas y `Mail::shouldReceive('to')` que lanza `TransportException` una sola vez: un único intento, un único `recordatorio.correo_fallido`, las dos citas sin marcar, `recordatorio.revision` con `fallidos` 1 y `aplazados` 1; en la revisión siguiente, con el correo de vuelta, salen las dos) — tests/Feature/RecordatorioCitaTest.php — hecho cuando: fallan hoy (caducidad de 1440 min; dos intentos) (anotado) — cubre: CA1, CA2, CA8
  - nota: añadida por /review: R1, R2
- [ ] T028 Programar la revisión con `withoutOverlapping(10)` y, en `RecordatoriosCitas`, cortar la revisión en el primer fallo del transporte de correo (`Symfony\Component\Mailer\Exception\TransportExceptionInterface`): libera la cita, deja su `recordatorio.correo_fallido` y cuenta las demás candidatas como aplazadas; los demás errores de una cita siguen como hasta ahora (se registra y la revisión sigue) — routes/console.php, app/Agenda/RecordatoriosCitas.php — hecho cuando: T027 pasa, `RecordatorioCitaTest` y `RecordatorioCitaCorreoTest` en verde (incluido `test_un_error_al_reclamar_no_deja_datos_en_el_registro`, que sigue con las demás citas ante un error que no es del transporte), Pint limpio y el corte comprobado rompiéndolo (sin el `break`, T027 falla; anotado) — cubre: CA1, CA2, CA8 — depende: T027
  - nota: añadida por /review: R1, R2

## Integración y documentación
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura: `RecordatoriosCitas`, el comando y la tarea programada, y `RecordatorioCita` en «Correo»; y en `AGENTS.md` → «Mapa del repositorio», `app/Agenda/`, `app/Mail/` y `app/Console/Commands/`
  - nota: `docs/architecture.md`: fila «Agenda (recordatorios)» tras la hora de la clínica. A7 — `AGENTS.md` (ruta protegida) solo en esta tarea y solo en el «Mapa del repositorio»: `app/Agenda/`, `app/Console/Commands/` y `app/Mail/`.
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `deployment.md`, la variable `RECORDATORIOS_CORREO` (encender y apagar con `aplicar-entorno.sh app`) y `dentissa:recordatorios` en el `scheduler`; en `observability.md`, los eventos `recordatorio.revision`, `recordatorio.correo_fallido` y `recordatorio.tope_diario`; en `docs/security.md`, las tareas programadas (l. 88) y el uso de la cuenta de Gmail con los recordatorios
  - nota: `deployment.md`: fila de `RECORDATORIOS_CORREO` y el `scheduler` con dos tareas. `observability.md`: los tres eventos `recordatorio.*`. `security.md`: tareas programadas y la salida SMTP a Gmail con los recordatorios y su tope. Sin riesgos `RS` que cambien de estado.
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint sobre lo tocado, gitleaks sobre el diff de la spec, semgrep `p/php` sobre los PHP tocados, `composer audit` (regla de la CI) y `auditar-npm.mjs` sin altas ni críticas, `python .ai/bin/aidd.py validate` sin errores, criterios `[x]` y `docs/specs/README.md`; resultados anotados en la tarea
  - nota: suite completa 1622 tests en verde (línea base 1591: 31 nuevos, ninguno roto; 9 min). Pint PASS en los 10 PHP tocados; gitleaks 8.21.2 sin fugas en los 22 archivos cambiados desde `impl_base`; semgrep `p/php` 1.99.0 sin hallazgos (un aviso de análisis parcial: no entiende las propiedades `readonly` promovidas del constructor de `RecordatorioCita`; el resto del archivo sí se analiza); `composer audit` con la regla de la CI y `auditar-npm.mjs` sin avisos; `aidd.py validate` sin errores. CA1–CA11 `[x]`, spec `implemented` y su fila del README.

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097, con el interruptor encendido después del despliegue
  - verificación manual del RNF de entrega y de CA1 en producción: con `RECORDATORIOS_CORREO=true`, una cita de prueba de una cuenta de paciente de prueba (con un correo del usuario) a 24 h 2 min recibe el correo en los minutos siguientes con los datos de la cita; el detalle de esa cita en el panel del personal muestra «Recordatorio · Enviado»; el correo se lee bien en el móvil del usuario
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones) con `RECORDATORIOS_CORREO=false`, `verificar.sh` (con `dentissa:recordatorios` en «Tareas programadas»), encender el interruptor con `aplicar-entorno.sh app` para la comprobación de T095 y vigilar `recordatorio.correo_fallido` y `recordatorio.tope_diario`. Rollback: apagar el interruptor; si hace falta, `bash deploy/volver.sh <tag anterior>`, sin copia
- [ ] T098 Marcar spec como `released`; en las specs 003, 004 y 028 anotar en su Historial que la 052 envía el recordatorio (y cierra 028/R11); en el roadmap, 028/R11 resuelto

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T027 | T021, T022, T023, T028 |
| CA2 | T010, T027 | T023, T028 |
| CA3 | T011 | T023 |
| CA4 | T011 | T024 |
| CA5 | T010, T011 | T023 |
| CA6 | T010 | T023 |
| CA7 | T013 | T024 |
| CA8 | T011, T027 | T023, T028 |
| CA9 | T012 | T021 |
| CA10 | T012 | T021 |
| CA11 | T011 | T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T023 | T010 |
| TM2 | T021 | T012 |
| TM3 | T021 | T012 |
| TM4 | T023 | T011 |
| TM5 | T023 | T011 |
| TM6 | T023 | T011 |
| TM7 | T021 | T012 |
| TM8 | T023 | T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| config/recordatorios.php | T020 |
| .env.example, phpunit.xml | T020 |
| app/Agenda/RecordatoriosCitas.php | T023 |
| app/Console/Commands/EnviarRecordatorios.php | T023 |
| routes/console.php | T023 |
| app/Mail/RecordatorioCita.php | T021 |
| resources/views/correos/recordatorio-cita.blade.php, recordatorio-cita-texto.blade.php | T021 |
| resources/views/correos/recordatorio.css | T022 |
| app/Agenda/ReprogramacionCitas.php | T024 |
| routes/console.php (R1) | T028 |
| app/Agenda/RecordatoriosCitas.php (R2) | T028 |
| deploy/verificar.sh | T025 |
| tests/Feature/Auditoria/InmutabilidadAuditoriaTest.php (añadida durante /implement) | T026 |
| tests/Feature/RecordatorioCitaTest.php | T010, T011 |
| tests/Feature/RecordatorioCitaCorreoTest.php | T012 |
| tests/Feature/CitasPanelDetalleTest.php | T013 |
| docs/architecture.md, AGENTS.md | T090 |
| docs/deployment.md, docs/observability.md, docs/security.md | T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Entrega (24 h–23 h 50 min; menos de 10 min) | T010, T011 (`test_la_revision_corre_cada_minuto`) · T095 |
| Accesibilidad (contraste AA, 320 px, `alt`, versión de texto) | T012 · T022 (puertas de `evaluate.mjs`) |
| Registros técnicos sin datos personales (P8) | T011 |
| Secretos fuera del repositorio (P5) | T092 (gitleaks) |
| Volumen ≤ 250 al día | T011 |
| Cuentas sin correo de la 054 se saltan sin fallo ni registro | T010 |
| Interruptor apagado por defecto | T010 |
