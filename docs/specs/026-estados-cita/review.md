---
spec: 026-estados-cita
verdict: changes_requested
round: 1
date: 2026-10-02
base: 7aa05ac
head: 05ebda7
human_signoff: no requerido
---

# Review · 026 Estados de la cita y quién puede cambiarlos

## Resumen
**changes_requested.** No hay bloqueantes: todos los criterios tienen tests que pasan, los controles TM1–TM11 existen
y sus tests detectan el control roto, y las herramientas de seguridad salen limpias. Hay un importante (R1): si el
motivo no pasa la validación, la página vuelve sin ningún mensaje visible, y además en inglés, en contra del requisito
de mensajes en español. El usuario decide corregirlo junto con cuatro grupos de menores (R4, R5, R10, R20, R26). El
resto de menores va al roadmap.

Conteo: bloqueante 0 · importante 1 · menor 25.

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 123 passed (770 assertions); línea base de T001: 77 |
| `vendor/bin/pint --test` sobre los 16 archivos PHP tocados | ✅ sin cambios |
| `python .ai/bin/aidd.py validate docs/specs/026-estados-cita` | ✅ 0 errores |
| Tests de la 026 (RNF < 10 s) | ⚠ unos 12 s en local; aceptado por el usuario con nota en T040 y en el roadmap (medir en el CI) |
| Restos de `CambioEstadoConcurrenteTest` en `dentissa_test` | ✅ ninguno (8 usuarios, 2 citas, 1 historial, 1 personal, 6 pacientes) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `EstadosCitaTransicionesTest::test_solo_se_aceptan_las_transiciones_de_la_tabla` | ✅ | Recorre las 36 parejas y compara con la tabla. |
| CA2 | `EstadosCitaTransicionesTest::test_una_cita_cerrada_no_cambia_de_estado`, `EstadosCitaRolesTest::test_el_paciente_recibe_un_mensaje_sin_estados_internos_al_no_poder_cancelar`, `EstadosCitaVistasTest::test_una_cita_cerrada_no_ofrece_cambios_y_explica_como_seguir` | ✅ | El mensaje del paciente para canceladas y no asistidas no se comprueba con texto exacto (R4). |
| CA3 | `test_una_transicion_no_permitida_indica_desde_que_estados_se_permite`, `test_volver_a_agendada_nunca_se_permite` | ✅ | Con el mismo estado de origen y destino, el mensaje es el genérico (R2). |
| CA4 | `EstadosCitaRolesTest`: dentista confirma/inicia/completa/no asistió, no cancela, no toca otra cita; rol sin la transición sobre una cita cerrada | ✅ | |
| CA5 | `EstadosCitaRolesTest`: asistente confirma/cancela/no asistió, no inicia ni completa, no toca dentistas no asignados | ✅ | |
| CA6 | `test_eliminar_cita_es_una_cancelacion_con_las_mismas_reglas`, `test_eliminar_una_cita_inexistente_responde_404`, `test_solo_el_admin_puede_eliminar_una_cita`, `EstadosCitaVistasTest::test_el_boton_cancelar_del_admin_solo_aparece_en_citas_abiertas` | ✅ | |
| CA7 | `test_el_paciente_cancela_su_cita_antes_de_que_empiece`, `test_el_paciente_no_puede_cancelar_una_cita_que_ya_empezo`, `test_el_paciente_no_puede_cancelar_una_cita_en_otro_estado`, `test_el_paciente_no_puede_cancelar_la_cita_de_otro`, `test_el_personal_no_puede_usar_la_cancelacion_del_paciente`, `EstadosCitaVistasTest::test_el_paciente_no_ve_cancelar_en_una_cita_que_ya_empezo` | ✅ | Sin test en el borde exacto de la hora para el paciente (R3). Sin explicación en el detalle cuando ya empezó (R26). |
| CA8 | `test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza`, `test_a_la_hora_exacta_de_inicio_ya_se_puede_iniciar`, `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica`, `EstadosCitaVistasTest::test_antes_de_la_hora_se_explica_desde_cuando_se_puede_iniciar` | ✅ | |
| CA9 | `HistorialCitaTest::test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`, `test_un_rechazo_no_deja_entrada_en_el_historial`, `test_el_historial_muestra_autor_estados_y_motivo` | ✅ | Un motivo que no pasa la validación se rechaza sin mensaje visible (R1). La atomicidad no tiene test (R20). |
| CA10 | `CambioEstadoConcurrenteTest` (5 tests) | ✅ | |
| CA11 | `test_un_estado_inexistente_se_rechaza_sin_error_500`, `test_reabrir_una_cita_cancelada_se_rechaza`, `test_volver_a_agendada_nunca_se_permite`, `test_las_notas_internas_enviadas_con_el_estado_no_cambian` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `.env.example` aparece en `scope.md` sin tarea, pero lo cubre T020 (el detector no
  reconoce el punto inicial). `CitasController::avisoDeEstado` (helper privado) y `EstadosCita::inicio` (público) son
  detalles de implementación anotados en T025 y T023.
- Partes del plan sin implementar: ninguna. T001 y T041 solo tocan documentación.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, plan y tareas `approved`; `/analyze` ronda 2 en `pass`. |
| P2 Test que falla antes y pasa después | ✅ | 31 tests fallaban antes de implementar (commit 6d95f00). Los que ya pasaban se comprobaron rompiendo el código (notas de T011–T013). |
| P3 Autorización por rol y recurso | ✅ con excepción aceptada | Gates `gestionar-cita` y `cambiar-estado-cita` en los tres endpoints que cambian estado; `role:cliente` en la cancelación; tests permitido/denegado por rol. Los tres GET de la excepción (→ 014, 015) no cambian su autorización. `autorizadoParaCita` conserva su propia comparación, no estricta (R15, → 014). |
| P4 Validación y SQL con bindings | ✅ | `estado` con `in:` de valores constantes; `motivo` `nullable\|string\|max:500` (sus mensajes, R1); Query Builder con bindings; DDL fijo en la migración; sin `{!! !!}`. |
| P5 Nada sensible en el repo | ✅ | gitleaks limpio; `.env.example` sin secretos. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | composer audit, npm audit y trivy en 0 altas o críticas; sin dependencias nuevas. |
| P7 Auditoría | ✅ con excepción aceptada | Historial con autor en los cinco flujos; sin evento formal (→ 022) ni registro de 403 (→ 020). |
| P8 Logs limpios | ✅ con excepción aceptada | Sin logs nuevos; `test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs` con control. Residuo de QueryException → 023. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`7aa05ac..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (PHP de app/, database/, config/, routes/ tocados) | ✅ 0 hallazgos |
| composer audit `--locked` / npm audit `--audit-level=high` | ✅ crít: 0 · alta: 0 (medias y bajas ignoradas por política; bajas cubiertas por EX1) |
| trivy fs HIGH,CRITICAL | ✅ 0 hallazgos |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | Gates con comparación estricta, 404 al paciente con cita ajena, `role:cliente`; TM1, TM2 y TM10 fallan con el control roto. |
| A05:2025 Inyección y XSS | ✅ | `in:` con constantes, bindings, salida escapada; TM11 falla con `{!! !!}`. |
| A01:2025 CSRF | ✅ | `@csrf @method` en los formularios; sin excepciones. |
| A06/A10:2025 Diseño y condiciones de carrera | ✅ | `lockForUpdate()` antes de leer el estado, en la misma transacción que el UPDATE y el INSERT; TM4 y TM5 probados. |
| A10:2025 Errores | ✅ | 1205/1213 → mensaje; el resto se propaga (P8 aceptado). |
| A09:2025 Logs y auditoría | ✅ | Sin logs nuevos; historial con autor del servidor. |
| A02:2025 Configuración | ✅ (menor) | `CLINICA_ZONA_HORARIA` no se valida (R19). |
| A03:2025 Cadena de suministro | ✅ | Sin cambios en lockfiles. |
| Proceso: rutas protegidas e instrucciones a agentes | ✅ | Ninguna ruta protegida tocada; sin texto dirigido a agentes. |

## Observabilidad
- Historial de la sección Auditoría: una entrada con autor, anterior, nuevo y motivo por cambio aceptado, en los cinco
  flujos (`HistorialCitaTest::test_cada_cambio_aceptado_deja_una_sola_entrada_con_autor`), y ninguna en los rechazos.
  El evento de auditoría formal queda para la 022 (excepción P7).
- Datos sensibles en logs del código tocado: ninguno (`test_ni_el_cambio_ni_el_rechazo_escriben_datos_en_logs`).
- `request_id` en logs nuevos: no aplica, no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `app/Http/Controllers/CitasController.php:334`, `resources/views/citas/show.blade.php:164,194` | Si `motivo` no pasa la validación (`max:500` o un array), el error queda en `errors.motivo` sin texto en español y la vista solo muestra `@error('estado')`: la página vuelve sin mensaje y la cita no cambia. Se da sin manipular nada: `maxlength="500"` cuenta un salto de línea como 1 carácter y el navegador lo envía como CRLF (2), así que el servidor puede recibir más de 500. | Mensajes en español para `motivo.max` y `motivo.string`, y `@error('motivo')` junto al textarea; test con un motivo de 501 caracteres. |
| R2 | menor | `app/Agenda/EstadosCita.php:149` | Pedir el mismo estado que ya tiene la cita (doble envío, dos pestañas) da el mensaje genérico de transición, que parece decir que el cambio falló aunque se aplicó. | Mensaje propio: "La cita ya está «X»; recarga la página para ver su estado actual". |
| R3 | menor | `tests/Feature/EstadosCitaRolesTest.php:219-229` | Para el paciente no se prueba el borde exacto de la hora de inicio ni una cita agendada ya empezada. | Casos a las 16:00:00 UTC y con origen `agendada`. |
| R4 | menor | `tests/Feature/EstadosCitaRolesTest.php:231-241` | `test_el_paciente_no_puede_cancelar_una_cita_en_otro_estado` solo exige un error no vacío; el mensaje del paciente para `cancelada` y `no_asistio` no se comprueba con texto exacto. | `assertSame` con el mensaje del paciente. |
| R5 | menor | `tests/Feature/HistorialCitaTest.php:63` | `test_un_rechazo_no_deja_entrada_en_el_historial` no comprueba el status ni que el estado siga igual: pasaría si el endpoint fallara antes de la regla. | `assertStatus` y `assertSame` del estado en cada petición. |
| R6 | menor | `tests/Feature/HistorialCitaTest.php:88`, `tests/Feature/EstadosCitaVistasTest.php:120` | Dos aserciones fijan como esperado el defecto conocido D4 (fechas en UTC); fallarán por la razón equivocada cuando se corrija. | Referenciar la spec que corrija D4 o aislar esas aserciones. |
| R7 | menor | `tests/Feature/CambioEstadoConcurrenteTest.php:124` | El bloqueo real en el test llega como `DeadlockException` (transacción anidada); el 1205 de nivel 1 de producción solo lo cubre el test con Mockery. | Indicarlo en el docblock del test. |
| R8 | menor | `tests/Feature/CambioEstadoConcurrenteTest.php:75` | Si `dentissa_test` conserva el trigger (bootstrap sin `migrate`), el UPDATE confirmado de la segunda conexión deja una fila de historial y el borrado de la cita falla por FK. | Borrar el historial de la cita en `tearDown` o fallar con un mensaje claro si el trigger existe. |
| R9 | menor | `tests/Feature/Concerns/EscenarioEstadosCita.php:80` | La rama `cliente → admin` del helper `cambiar` no se usa y confunde. | Quitarla. |
| R10 | menor | `app/Http/Controllers/CitasController.php:346` | Un rechazo de la regla de estado vuelve sin `withInput()`: el motivo escrito se pierde. | `back()->withInput()->withErrors(...)`. |
| R11 | menor | `app/Autorizacion/CambiarEstadoCita.php:21` | `PERMITIDOS['admin']` repite a mano las claves de `EstadosCita::TRANSICIONES`. | Derivarla de `TRANSICIONES`. |
| R12 | menor | `app/Http/Controllers/ClienteCitasController.php:117` | `conPuedeCancelar` repite la regla en lugar de usar el servicio. | Usar `TRANSICIONES['cancelada']` o `disponibles()`. |
| R13 | menor | `app/Http/Controllers/CitasController.php:302` | `avisoDeEstado` reescribe el formato de `antesDeTiempo`. | Fábrica común en `CambioDeEstadoNoPermitido`. |
| R14 | menor | `resources/views/citas/show.blade.php:129-132,178` | La vista pide las etiquetas de estado a una clase de excepción. | Llevar `ETIQUETAS` a `EstadosCita` o a un helper de presentación. |
| R15 | menor | `app/Http/Controllers/CitasController.php:75` | `autorizadoParaCita` (show y store) sigue siendo una segunda implementación del alcance, con `in_array` no estricto. | Migrarlo al Gate `gestionar-cita` en la 014. |
| R16 | menor | `app/Agenda/EstadosCita.php:81` | `disponibles()` evalúa el Gate para 5 estados, con consultas de alcance repetidas por cada detalle. | Calcular el alcance una vez o memorizarlo por petición. |
| R17 | menor | `app/Http/Controllers/CitasController.php:375` | En `destroy`, `gestionar-cita` es redundante con `cambiar-estado-cita`. | Quitarlo o comentar la simetría. |
| R18 | menor | `app/Agenda/EstadosCita.php:116` | El servicio lanza `NotFoundHttpException` (HTTP) dentro del dominio. | Excepción de dominio traducida en el controlador. |
| R19 | menor | `app/Agenda/EstadosCita.php:105`, `config/clinica.php:17` | Un `CLINICA_ZONA_HORARIA` inválido da 500 en el detalle y en cada cambio de estado. | Validarlo al arrancar o volver al valor por defecto. |
| R20 | menor | `tests/Feature/HistorialCitaTest.php` (control en `app/Agenda/EstadosCita.php:56,122-134`) | Ningún test prueba que el UPDATE y el INSERT del historial sean atómicos (TM7); hoy lo son. | Test que haga fallar el INSERT tras el UPDATE y compruebe que la cita no cambió. |
| R21 | menor | `docs/specs/026-estados-cita/plan.md` (Modelo de amenazas, TM3, TM4, TM8) | Categorías `A04:2025` (Cryptographic Failures en la edición 2025); deberían ser `A06:2025` (Insecure Design). | Corregir la categoría. |
| R22 | menor | `resources/views/citas/show.blade.php:164-166` | El error de `estado` no se asocia al grupo de radios (`aria-describedby`/`aria-invalid`) y nada lleva el foco a él tras recargar. | `id` en el error y `aria-describedby` en el `fieldset`. |
| R23 | menor | `resources/views/citas/show.blade.php:164-166` | El error va arriba de la tarjeta, con `role` y margen en línea, a diferencia del patrón de `create.blade.php`. | Clase en `citas.css` o el patrón común. |
| R24 | menor | `resources/views/citas/show.blade.php:177-178` | "(ahora: X)" en la `legend` usa `--text-light` (≈ 3,5:1, por debajo de 4,5:1). | `--text-muted` o mayor tamaño. |
| R25 | menor | `resources/views/citas/show.blade.php:176-182` | Estilos en línea del `fieldset` y las opciones; `transition` sobrante. | Clases en `citas.css`. |
| R26 | menor | `resources/views/citas/cliente-show.blade.php:5` | Si la cita agendada o confirmada ya empezó, el botón desaparece sin explicar por qué. | Texto breve coherente con el mensaje `yaEmpezo`. |

## Preparación para release
- Rollback factible: sí. El `down` de la migración recrea el trigger (comprobado en T021) y la versión anterior se
  vuelve a desplegar; las filas de historial nuevas siguen siendo válidas.
- Migraciones compatibles con la versión anterior: la retirada del trigger no es *expand*/*contract*; el plan y
  `deployment.md` exigen desplegar código y migración en el mismo paso.
- Feature flags: ninguno (decisión del usuario, 2026-10-02).
- Docs actualizadas: `architecture.md` (T090) y `deployment.md`, `security.md`, `observability.md` (T091).
- Cobertura de riesgos: OB5.a "en curso (spec 026)"; OB5.b y RS2.a siguen "pendiente" con nota; ningún riesgo figura
  como mitigado con correcciones pendientes.

## Tareas añadidas
- T016 ← R1, R10
- T017 ← R4, R5, R20
- T018 ← R26
- T028 ← R1, R10
- T029 ← R26

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-02 (van al roadmap en `/release`):
- **R2** → roadmap: mensaje propio cuando se pide el mismo estado que ya tiene la cita.
- **R3** → roadmap: tests del paciente en el borde exacto de la hora y con una cita agendada ya empezada.
- **R6** → roadmap: aserciones que fijan las fechas en UTC (D4); actualizarlas con la spec que corrija D4.
- **R7** → roadmap: documentar en `CambioEstadoConcurrenteTest` que el bloqueo real llega como `DeadlockException`.
- **R8** → roadmap: limpieza robusta en `CambioEstadoConcurrenteTest` si `dentissa_test` conserva el trigger.
- **R9** → roadmap: quitar la rama `cliente → admin` del helper `cambiar` de la fixture.
- **R11** → roadmap: derivar los destinos del admin en `CambiarEstadoCita` de `EstadosCita::TRANSICIONES`.
- **R12** → roadmap: `conPuedeCancelar` debe usar la regla del servicio.
- **R13** → roadmap: una sola fuente para el texto de "aún no empieza".
- **R14** → roadmap: etiquetas de estado fuera de la clase de excepción.
- **R15** → roadmap: `autorizadoParaCita` (show y store) al Gate `gestionar-cita`, con comparación estricta, en la 014.
- **R16** → roadmap: evitar consultas de alcance repetidas en `EstadosCita::disponibles()`.
- **R17** → roadmap: `gestionar-cita` redundante en `destroy`.
- **R18** → roadmap: excepción de dominio en lugar de `NotFoundHttpException` en `EstadosCita`.
- **R19** → roadmap: validar `CLINICA_ZONA_HORARIA` al arrancar.
- **R21** → roadmap: corregir a `A06:2025` las categorías OWASP de TM3, TM4 y TM8 en el plan de la 026.
- **R22** → roadmap: asociar el error de `estado` al grupo de radios (`aria-describedby`) y llevarle el foco.
- **R23** → roadmap: unificar el patrón del error de `estado` con el resto de formularios de citas.
- **R24** → roadmap: contraste de "(ahora: X)" en la `legend` del formulario de estado.
- **R25** → roadmap: llevar los estilos en línea del formulario de estado a `citas.css`.
