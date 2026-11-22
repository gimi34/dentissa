---
spec: 052-recordatorio-correo
verdict: approved
round: 2
date: 2026-11-22
base: 4427723
head: 6cf9ae8
human_signoff: no requerido
---

# Review · 052 Recordatorio de cita por correo

## Resumen
Ronda 2 (`--rerun`, sin subagentes: diff pequeño desde `38fa91f`, 53 líneas en 3 archivos, releído desde cero).
`approved`: R1 y R2 quedan corregidos con T027 y T028, la suite completa sigue en verde y no hay hallazgos
nuevos. R1: `withoutOverlapping(10)` (`routes/console.php:52`), probado por
`test_el_bloqueo_de_la_revision_caduca_pronto`. R2: al primer `TransportExceptionInterface` la revisión se corta
y cuenta el resto como aplazadas (`RecordatoriosCitas.php:79-84`); lo prueba
`test_con_el_correo_caido_se_intenta_una_vez_por_revision`, que falla al quitar el `break`. Si una revisión durara
más de 10 min y se solapara con la siguiente, el reclamo atómico sigue evitando duplicados (TM6). Los menores de la
ronda 1 siguen aceptados al roadmap ([history/review.r1.md](history/review.r1.md)).
Conteo (abiertos): bloqueante 0 · importante 0 · menor 0 (12 menores aceptados en la ronda 1)


## Verificación automática
| Comando | Resultado |
|---|---|
| `vendor/bin/phpunit` (suite completa, `dentissa_test`, contenedor `dentissa-app` con el código del worktree) | ✅ r2: 1624 tests, 20 541 aserciones, 0 fallos (r1: 1622; +2 de T027) |
| `vendor/bin/pint --test` sobre los 10 PHP tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/052-recordatorio-correo` | ✅ 0 errores |
| `evaluate.mjs` con `design/criterios.json` sobre el correo renderizado (T022) | ✅ 11/11 puertas y 2/2 KPIs con y sin servicio (`.ai/inspect/052-build/con`, `sin`) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RecordatorioCitaTest::test_sale_cuando_faltan_24_horas` · `test_llega_al_correo_de_la_cuenta_del_paciente_con_sus_datos` · `RecordatorioCitaCorreoTest::test_el_correo_lleva_los_datos_de_la_cita` | ✅ | El de los datos ajenos es débil (R5) |
| CA2 | `test_una_cita_a_menos_de_24_horas_sale_en_la_siguiente_revision` · `test_una_cita_que_ya_empezo_no_recibe_recordatorio` | ✅ | En la práctica depende de R1 (bloqueo de 24 h) |
| CA3 | `test_una_segunda_revision_no_reenvia` · `test_dos_revisiones_no_envian_dos_veces` | ✅ | R3: duplicado posible si falla la caché tras el envío |
| CA4 | `test_reprogramar_reinicia_el_recordatorio_y_sale_otro` | ✅ | No comprueba la fecha del segundo correo (R6) |
| CA5 | `test_solo_agendadas_y_confirmadas_reciben_recordatorio` · `test_cancelar_despues_del_recordatorio_no_envia_otro_correo` | ✅ | |
| CA6 | `test_una_cuenta_desactivada_no_recibe_recordatorio` | ✅ | R4: el reclamo no vuelve a mirar `activo` |
| CA7 | `CitasPanelDetalleTest::test_el_detalle_muestra_el_recordatorio_enviado` · `test_el_detalle_no_muestra_el_recordatorio_tras_reprogramar` | ✅ | |
| CA8 | `test_un_fallo_del_correo_no_marca_la_cita_y_se_reintenta` · `test_un_error_al_reclamar_no_deja_datos_en_el_registro` | ✅ | R2: sin freno con el SMTP caído |
| CA9 | `RecordatorioCitaCorreoTest::test_un_solo_enlace_a_mis_citas_sin_parametros` · `test_mis_citas_sin_sesion_lleva_al_inicio_de_sesion` | ✅ | |
| CA10 | `test_el_correo_no_lleva_notas_ni_expediente` | ✅ | Con la revisión real |
| CA11 | `test_el_tope_diario_aplaza_el_exceso_al_dia_siguiente` · `test_los_registros_no_llevan_datos_personales` | ✅ | R10: no distingue día de la clínica y día UTC |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 16 archivos de código, todos citados por una tarea; T026 añadida durante `/implement` con decisión del usuario).
- Partes del plan sin implementar: ninguna. T001 sin diff de código (rebase).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, diseño, plan y tareas aprobados y `/analyze` r1 `pass` antes del primer commit de código |
| P2 | ✅ | Notas de T010–T013 con el fallo previo; caracterizaciones y controles comprobados rompiéndolos (T012, T013, T021, T023, T026) |
| P3 | ➖ | Sin endpoints nuevos |
| P4 | ✅ | Query Builder con bindings, sin `DB::raw`/`whereRaw`; plantillas con `{{ }}`; semgrep sin hallazgos |
| P5 | ✅ | gitleaks sin fugas; solo el nombre de la variable |
| P6 | ✅ | Sin dependencias nuevas; SCA limpio |
| P7 | ➖ | La spec declara «Auditoría: No aplica» |
| P8 | ✅ | Logs solo con contadores o la clase del error (test); el comando no tiene `request_id`, como los demás comandos (R11) |
| P9 | ✅ | `test_la_hoja_del_recordatorio_solo_usa_valores_de_tokens` (con controles negativos) y `test_las_plantillas_no_llevan_estilos_en_linea`; `evaluate.mjs` anotado en T022 |
| P10 | ➖ | Sin rutas públicas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (22 archivos desde `impl_base`; r2: los 3 archivos tocados) | ✅ 0 hallazgos |
| semgrep `p/php` 1.99.0 (10 PHP; r2: los 3 tocados, 0 errores) | ✅ 0 hallazgos (aviso de análisis parcial por las propiedades `readonly` promovidas de `RecordatorioCita`; revisado a mano por S) |
| `composer audit --locked` (regla de la CI) + `auditar-npm.mjs` | ✅ crít: 0 · alta: 0 |
| trivy 0.57.1 `fs` | ✅ 0 altas ni críticas en la ronda de la 053 con los mismos lockfiles; sin lockfiles cambiados (`deps.stat` vacío) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Destinatario y datos (TM1, TM3) | ✅ | `up.email` de la cuenta del paciente de la cita; sin notas ni expediente |
| A05:2025 Inyección (TM7) | ✅ | `{{ }}` en plantillas y asunto; asunto solo con fechas |
| A06:2025 Volumen y concurrencia (TM4, TM6) | ✅ | Tope diario, reclamo atómico y, desde la r2, un solo intento por revisión con el correo caído (R2) |
| A07:2025 Enlace (TM2) | ✅ | Un solo enlace, sin token, a una ruta con `auth` |
| A09:2025 Registros y alertas (TM5) | ✅ / ❌ alertas | Logs sin datos (test); `recordatorio.correo_fallido` no genera aviso (R9) |
| A10:2025 Condiciones excepcionales (TM8) | ✅ | `try/catch` por cita con liberación; bloqueo de 10 min desde la r2 (R1) |
| Proceso | ✅ | Rutas protegidas solo en T090 (`AGENTS.md`, mapa); sin instrucciones a agentes |

## Observabilidad
- Eventos de auditoría: no aplica (spec).
- Datos sensibles en logs del código tocado: ninguno (test `test_los_registros_no_llevan_datos_personales`; fuera del bucle por cita, los bindings son estados y límites de la ventana).
- `request_id`: los comandos no tienen petición; sin `request_id` (R11).
- El plan dice que `dentissa-vigilar` recoge los `error`: es falso, el vigilante lee el log de nginx y el canal `seguridad` (R9).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `routes/console.php:51` | `withoutOverlapping()` sin argumento caduca a los 1440 min y el mutex vive en la caché `database`: si el `scheduler` muere a mitad de una revisión (`desplegar.sh` y `aplicar-entorno.sh app` recrean el contenedor), no sale ningún recordatorio en 24 h y nada avisa (CA1, CA2, RNF de entrega) | Caducidad corta (10 min) y test sobre `expiresAt` |
| R2 | importante | `app/Agenda/RecordatoriosCitas.php:51-76` | Los fallos de envío no tienen freno: con el SMTP caído o la contraseña revocada, cada minuto se intenta una conexión por cada candidata de las próximas 24 h (el tope solo cuenta los envíos buenos), con autenticaciones fallidas contra la cuenta que comparten la 041 y la 032 y un `correo_fallido` por cita y minuto | Cortar la revisión al primer fallo del transporte y contar las demás como aplazadas; test con dos candidatas y el transporte caído |
| R3 | menor | `app/Agenda/RecordatoriosCitas.php:70` | `RateLimiter::hit` va en el mismo `try` que el envío: si la caché falla después de un envío bueno, se libera la cita y puede salir otro correo | Contar fuera del bloque que libera |
| R4 | menor | `app/Agenda/RecordatoriosCitas.php:125, :136` | Reclamo y liberación reescriben el `actualizado_en` leído al principio (puede pisar una edición en la ventana) y el reclamo no vuelve a comprobar cuenta activa y correo | Condición sobre `actualizado_en`; valorar recomprobar la cuenta |
| R5 | menor | `tests/Feature/RecordatorioCitaTest.php:75` | En el test de TM1 la segunda cita está fuera de la ventana: «no los de otro» se cumple siempre | Dos citas dentro de la ventana |
| R6 | menor | `tests/Feature/RecordatorioCitaTest.php:239` | El test de CA4 cuenta dos correos pero no comprueba que el segundo sea de la fecha nueva | Comprobar `fechaHora` del último enviado |
| R7 | menor | `tests/Feature/Auditoria/InmutabilidadAuditoriaTest.php:186` | Quedan dos docblocks (el antiguo dice «solo la purga») y el nombre del test ya no describe lo que comprueba | Un docblock y renombrar |
| R8 | menor | `docker-compose.prod.yml:37`, `docs/security.md:94` | Siguen diciendo que el `scheduler` solo programa `auditoria:purgar` | Actualizar los dos textos |
| R9 | menor | `plan.md` (Observabilidad, Rollout) | `recordatorio.correo_fallido` no genera ningún aviso: `dentissa-vigilar` no lee el canal por defecto | Brecha OB para alertar de los fallos de envío |
| R10 | menor | `tests/Feature/RecordatorioCitaTest.php` (tope) | El test del tope no distingue el día de la clínica del día UTC | Un caso a partir de las 18:00 de la clínica |
| R11 | menor | `app/Console/Commands/EnviarRecordatorios.php` | Los logs del comando no llevan `request_id` (deuda previa: `auditoria:purgar` igual) | Un identificador por ejecución en el `Context` |
| R12 | menor | `config/mail.php:48` | `timeout => null` en SMTP: cada intento puede bloquear ~60 s | Timeout corto del mailer |
| R13 | menor | `resources/views/correos/recordatorio-cita.blade.php:16, :19, :36`, `recordatorio-cita-texto.blade.php` | Accesibilidad y fidelidad: `class=""` vacío en las fichas; el talón sin `.rango` (nowrap) de `c2`; Outlook ignora `border-spacing` y márgenes de tabla; firma «Dentissa» y día en minúscula en el texto; nombres `semana-dia` y `$dia_` | Retoques opcionales |
| R14 | menor | `tests/Feature/RecordatorioCitaTest.php`, `app/Agenda/RecordatoriosCitas.php:44, :101`, `app/Mail/RecordatorioCita.php:45` | Calidad: el mock de `Mail` repetido (helper), el acoplamiento de `DB::listen` al SQL (protegido por los controles), una línea larga, `candidatas()` sin tipo de retorno, `file_get_contents` sin comprobar | Retoques opcionales |

## Preparación para release
- Rollback factible: sí; apagar `RECORDATORIOS_CORREO` con `aplicar-entorno.sh app`, o `deploy/volver.sh <tag anterior>` sin copia (sin migraciones).
- Migraciones compatibles: no hay.
- Feature flag: `RECORDATORIOS_CORREO`, `false` por defecto (test `test_apagado_por_defecto`).
- Docs actualizadas: `architecture.md`, `AGENTS.md` (T090); `deployment.md`, `observability.md`, `security.md` (T091; la fila l. 94 de `security.md`, R8).
- SCA sin críticas ni altas: sí.
- Cobertura de riesgos: la spec cierra 028/R11 (T024, con test); el estado del roadmap lo actualiza `/release`.

## Tareas añadidas
- T027 ← R1, R2 (tests), hecha
- T028 ← R1, R2 (corrección), hecha

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-22:
- **R3** → roadmap: contar el envío fuera del bloque que libera la cita (duplicado si falla la caché).
- **R4** → roadmap: el reclamo no pisa `actualizado_en` ni pasa por alto una cuenta desactivada en la ventana.
- **R5** → roadmap: test de TM1 con dos citas dentro de la ventana.
- **R6** → roadmap: el test de CA4 comprueba la fecha del segundo correo.
- **R7** → roadmap: docblock y nombre del test de la programación (020).
- **R8** → roadmap: `docker-compose.prod.yml` y `docs/security.md:94` con la tarea de recordatorios.
- **R9** → roadmap: alerta para `recordatorio.correo_fallido` (brecha de observabilidad).
- **R10** → roadmap: test del tope en el cambio de día UTC.
- **R11** → roadmap: `request_id` en los comandos programados.
- **R12** → roadmap: timeout corto del mailer SMTP.
- **R13** → roadmap: retoques del correo (`class` vacío, `.rango`, Outlook, versión de texto, nombres).
- **R14** → roadmap: retoques de calidad de los tests y de `RecordatoriosCitas`.
