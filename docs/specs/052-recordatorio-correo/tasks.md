---
spec: 052-recordatorio-correo
plan: plan.md
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
- [ ] T001 Poner la rama al día con `origin/main` cuando la spec 054 esté fusionada (D9), resolver los choques, aplicar sus migraciones en `dentissa_test` y fijar la línea base de la suite — rama `feature/052-recordatorio-correo` — hecho cuando: la rama contiene la 054 (`usuarios.email` admite nulo en `dentissa_test`), no quedan conflictos y la suite completa pasa (resultado anotado)

## Tests (antes de implementar)
- [ ] T010 Escribir en `RecordatorioCitaTest` (con `Mail::fake()`, el tiempo fijado con `travelTo` en la hora de la clínica, las citas de `EscenarioEstadosCita` y `Artisan::call('dentissa:recordatorios')`) los casos de selección: `test_sale_cuando_faltan_24_horas` (a 24 h 1 min no sale; a 23 h 59 min sale un único `RecordatorioCita`), `test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos` (destinatario el correo de la cuenta del paciente de la cita y no el de otro paciente con cita a otra hora; TM1), `test_una_cita_a_menos_de_24_horas_sale_en_la_siguiente_revision` (creada a 3 h), `test_una_cita_que_ya_empezo_no_recibe_recordatorio`, `test_solo_agendadas_y_confirmadas_reciben_recordatorio` (en curso, completada, cancelada y no asistió no), `test_una_cuenta_desactivada_no_recibe_recordatorio`, `test_una_cuenta_sin_correo_se_salta_sin_registro` (`usuarios.email` nulo: sin correo y sin ningún log, con `CapturaLogs`), `test_apagado_no_envia_nada` (`recordatorios.activo` a false) y `test_apagado_por_defecto` (sin la variable, `config('recordatorios.activo')` es `false`) — tests/Feature/RecordatorioCitaTest.php — hecho cuando: fallan porque el comando y la configuración no existen (anotado) — cubre: CA1, CA2, CA5, CA6 — depende: T001
- [ ] T011 Añadir a `RecordatorioCitaTest` los casos de envío: `test_una_segunda_revision_no_reenvia`, `test_dos_revisiones_no_envian_dos_veces` (otra revisión reclama la cita entre la lectura y el reclamo: 0 correos de la segunda; TM6), `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` (con `ReprogramacionCitas`: la marca vuelve a 0 y sale el de la fecha nueva), `test_cancelar_despues_del_recordatorio_no_envia_otro_correo`, `test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta` (`Mail::shouldReceive('to')->andThrow(TransportException)`: la marca sigue en 0 y la revisión siguiente lo envía; TM8), `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` (`recordatorios.tope_diario` a 2 con 3 citas: salen 2; al día siguiente, la tercera si no ha empezado; TM4), `test_los_registros_no_llevan_datos_personales` (`recordatorio.revision`, `recordatorio.correo_fallido` y `recordatorio.tope_diario` con solo contadores o la clase del error, sin correo, nombre, dentista, fecha, servicio ni id de la cita; TM5), `test_la_marca_no_cambia_actualizado_en` (D2) y `test_la_revision_corre_cada_minuto` (`Schedule` con `dentissa:recordatorios` cada minuto y `withoutOverlapping`) — tests/Feature/RecordatorioCitaTest.php — hecho cuando: fallan porque el comando no existe y la reprogramación no reinicia la marca (anotado) — cubre: CA3, CA4, CA5, CA8, CA11 — depende: T010
- [ ] T012 [P] Escribir `RecordatorioCitaCorreoTest` sobre el HTML renderizado de `RecordatorioCita` (con `DOMXPath`) y su versión de texto: `test_el_correo_lleva_los_datos_de_la_cita` (nombre, «jueves 26 de noviembre de 2026», «10:30», dentista, servicio solo si la cita lo tiene, dirección y teléfono de `clinica.contacto`, «Ver mis citas»), `test_el_asunto_dice_hoy_o_manana` (D7), `test_la_version_de_texto_lleva_los_mismos_datos`, `test_el_logo_lleva_texto_alternativo`, `test_un_solo_enlace_a_mis_citas_sin_parametros` (un único `<a>`, `href` = `APP_URL` + `/cliente/citas`; TM2), `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` (TM2), `test_el_correo_no_lleva_notas_ni_expediente` (notas del paciente e internas con un texto marcador ausente del HTML y del texto; TM3), `test_el_nombre_del_paciente_sale_escapado` (TM7), `test_la_hoja_del_recordatorio_solo_usa_valores_de_tokens` y `test_las_plantillas_no_llevan_estilos_en_linea` — tests/Feature/RecordatorioCitaCorreoTest.php — hecho cuando: fallan porque `RecordatorioCita` y sus plantillas no existen, y el de `/cliente/citas` sin sesión pasa hoy y se comprueba quitando un momento el middleware `auth` de la ruta (anotado) — cubre: CA1, CA9, CA10 — depende: T001
- [ ] T013 [P] Añadir a `CitasPanelDetalleTest` `test_el_detalle_muestra_el_recordatorio_enviado` (con la marca a 1, «Recordatorio» y «Enviado» en el detalle del personal; con 0, nada) y `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` — tests/Feature/CitasPanelDetalleTest.php — hecho cuando: el de reprogramar falla porque la marca no se reinicia; el de la marca pasa hoy y se comprueba quitando un momento la fila de `show.blade.php` (anotado) — cubre: CA7 — depende: T001

## Implementación
- [ ] T020 Crear `config/recordatorios.php` (`activo` desde `RECORDATORIOS_CORREO`, `false` por defecto; `tope_diario` 250; `antelacion_horas` 24), añadir `RECORDATORIOS_CORREO=false` con su comentario a `.env.example` y `true` a `phpunit.xml` — config/recordatorios.php, .env.example, phpunit.xml — hecho cuando: `test_apagado_por_defecto` pasa — depende: T010
- [ ] T021 Crear el Mailable y sus plantillas según `design.md` (`c2`) y el plan (D7, D8): `RecordatorioCita` (asunto «Tu cita es {hoy|mañana}, …», fecha con `translatedFormat` en `es`, HTML con `acceso.css` y `recordatorio.css` pasadas a estilos en línea con `CssToInlineStyles`, vista de texto), la plantilla HTML sobre `correos.marco-acceso` (saludo, tarjeta con el `h1` de la fecha, la hora y la semana desde `clinica.contacto.horario_semanal`, «Con», «Servicio» solo si hay, «Dónde», «Ver mis citas» a `route('cliente.citas')`, aviso y talón con teléfono y horario) y la de texto — app/Mail/RecordatorioCita.php, resources/views/correos/recordatorio-cita.blade.php, resources/views/correos/recordatorio-cita-texto.blade.php — hecho cuando: los tests de T012 pasan salvo el de la hoja; TM2, TM3 y TM7 comprobados rompiéndolos (un segundo enlace, `notas_internas` en la plantilla, `{!! !!}` en el nombre; anotado) — cubre: CA1, CA9, CA10 — depende: T012
- [ ] T022 Crear la hoja del correo con las reglas de `c2` (tarjeta, semana, etiquetas, datos, talón y aviso) y los valores copiados de tokens — resources/views/correos/recordatorio.css — hecho cuando: T012 pasa entero y `evaluate.mjs` con `design/criterios.json` pasa sobre el HTML de `RecordatorioCita` renderizado con una cita de prueba con servicio y, con la copia de criterios sin «Limpieza dental», sin servicio (D11; informes en `.ai/inspect/`, anotado) · verificación manual: el correo renderizado a 320 y 390 px junto a `docs/specs/052-recordatorio-correo/design/candidatos/c2.html` y `c2-sin-servicio.html`, y su versión de texto — cubre: CA1 — depende: T021
- [ ] T023 Crear la revisión: `RecordatoriosCitas::enviarPendientes()` (interruptor; selección de D3 con Query Builder y bindings, sin notas ni expediente; tope por día de la clínica con `RateLimiter`, D5; reclamo con `UPDATE` condicional que repite `actualizado_en`, D2; envío al correo de la cuenta del paciente; `try/catch` por cita que libera el reclamo; los tres registros de Observabilidad sin datos personales), el comando `dentissa:recordatorios` y su programación cada minuto con `withoutOverlapping()` en `routes/console.php` (y el comentario que decía que `auditoria:purgar` era la única tarea) — app/Agenda/RecordatoriosCitas.php, app/Console/Commands/EnviarRecordatorios.php, routes/console.php — hecho cuando: T010 y T011 pasan salvo `test_reprogramar_reinicia_el_recordatorio_y_sale_otro`; TM1, TM4, TM5, TM6 y TM8 comprobados rompiéndolos (destinatario de otra fila, sin tope, el correo en el log, sin condición en el reclamo, sin liberar tras el fallo; anotado) — cubre: CA1, CA2, CA3, CA5, CA6, CA8, CA11 — depende: T011, T020, T022
- [ ] T024 Reiniciar la marca al reprogramar: `recordatorio_enviado => 0` en el `update` de la reprogramación (l. 122–126) — app/Agenda/ReprogramacionCitas.php — hecho cuando: `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` y `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` pasan y los tests de la 028 (`ReprogramarCita*`) siguen en verde — cubre: CA4, CA7 — depende: T013, T023
- [ ] T025 [P] Añadir a «Tareas programadas» de `verificar.sh` que `schedule:list` tenga `dentissa:recordatorios`, como `auditoria:purgar` — deploy/verificar.sh — hecho cuando: `bash -n deploy/verificar.sh` sin errores y la comprobación nueva junto a la de `auditoria:purgar` — depende: T023

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: `RecordatoriosCitas`, el comando y la tarea programada, y `RecordatorioCita` en «Correo»; y en `AGENTS.md` → «Mapa del repositorio», `app/Agenda/`, `app/Mail/` y `app/Console/Commands/`
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `deployment.md`, la variable `RECORDATORIOS_CORREO` (encender y apagar con `aplicar-entorno.sh app`) y `dentissa:recordatorios` en el `scheduler`; en `observability.md`, los eventos `recordatorio.revision`, `recordatorio.correo_fallido` y `recordatorio.tope_diario`; en `docs/security.md`, las tareas programadas (l. 88) y el uso de la cuenta de Gmail con los recordatorios
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint sobre lo tocado, gitleaks sobre el diff de la spec, semgrep `p/php` sobre los PHP tocados, `composer audit` (regla de la CI) y `auditar-npm.mjs` sin altas ni críticas, `python .ai/bin/aidd.py validate` sin errores, criterios `[x]` y `docs/specs/README.md`; resultados anotados en la tarea

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097, con el interruptor encendido después del despliegue
  - verificación manual del RNF de entrega y de CA1 en producción: con `RECORDATORIOS_CORREO=true`, una cita de prueba de una cuenta de paciente de prueba (con un correo del usuario) a 24 h 2 min recibe el correo en los minutos siguientes con los datos de la cita; el detalle de esa cita en el panel del personal muestra «Recordatorio · Enviado»; el correo se lee bien en el móvil del usuario
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones) con `RECORDATORIOS_CORREO=false`, `verificar.sh` (con `dentissa:recordatorios` en «Tareas programadas»), encender el interruptor con `aplicar-entorno.sh app` para la comprobación de T095 y vigilar `recordatorio.correo_fallido` y `recordatorio.tope_diario`. Rollback: apagar el interruptor; si hace falta, `bash deploy/volver.sh <tag anterior>`, sin copia
- [ ] T098 Marcar spec como `released`; en las specs 003, 004 y 028 anotar en su Historial que la 052 envía el recordatorio (y cierra 028/R11); en el roadmap, 028/R11 resuelto

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012 | T021, T022, T023 |
| CA2 | T010 | T023 |
| CA3 | T011 | T023 |
| CA4 | T011 | T024 |
| CA5 | T010, T011 | T023 |
| CA6 | T010 | T023 |
| CA7 | T013 | T024 |
| CA8 | T011 | T023 |
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
| deploy/verificar.sh | T025 |
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
