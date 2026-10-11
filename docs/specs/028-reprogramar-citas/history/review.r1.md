---
spec: 028-reprogramar-citas
verdict: changes_requested
round: 1
date: 2026-10-10
base: 961c9e4
head: 23198da
human_signoff: no requerido
---

# Review · 028 Reprogramar citas

## Resumen
`changes_requested`. Ningún hallazgo bloquea, pero el usuario decidió corregir antes de liberar seis
de los ocho importantes. Son R1–R5 y R7: mensajes contradictorios tras cancelar desde «No reagendar»,
panel de inicio del paciente sin el plazo de 24 h, dos aserciones vacías, el encabezado del historial y
reprogramar a un dentista dado de baja. Para R6 eligió la casilla obligatoria. R8 lo aceptó con
condición. De los menores, corrige ahora R9, R10, R12 y R18; los demás van al roadmap.
Conteo: bloqueante 0 · importante 8 · menor 10

Insumo: además de los revisores A, C+D, S y accesibilidad (subagentes con contexto limpio), los
hallazgos de dos `/code-review` lanzadas por el usuario el 2026-10-10, deduplicados y verificados.

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 242 passed, 0 fallos (línea base de T002: 183 passed, sin fallos) |
| tests nuevos (`--filter Reprogramar`), segunda ejecución | ✅ 58 passed, sin intermitentes |
| `docker compose exec app vendor/bin/pint --test <21 archivos PHP tocados>` | ✅ PASS |
| `npm run build` | ✅ salida 0 |
| `python .ai/bin/aidd.py validate docs/specs/028-reprogramar-citas` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReprogramarCitaTest::test_la_administracion_reprograma_cualquier_cita`, `test_el_asistente_reprograma_citas_de_sus_dentistas`, `test_solo_cambian_fecha_y_hora_y_el_hueco_anterior_queda_libre`; `ReprogramarCitaVistasTest::test_el_detalle_ofrece_reprogramar_solo_si_se_puede` | ✅ | La cita se mueve y el hueco anterior queda libre; que aparezca en la agenda no se comprueba en una vista (R16) |
| CA2 | `ReprogramarCitaPacienteTest::test_el_paciente_reprograma_con_24_horas_o_mas`, `test_el_hueco_nuevo_puede_empezar_en_menos_de_24_horas`, `test_con_menos_de_24_horas_se_rechaza_con_el_mensaje_de_plazo`, `test_el_detalle_oculta_reprogramar_fuera_de_plazo_y_explica_por_que`, `test_la_lista_muestra_el_aviso_corto_fuera_de_plazo` | ⚠ | Borde y zona horaria bien cubiertos; el panel de inicio del paciente no aplica el plazo (R2) |
| CA3 | `ReprogramarCitaPacienteTest::test_el_paciente_cancela_con_24_horas_o_mas`, `test_el_paciente_no_cancela_con_menos_de_24_horas`; tests de la 026 actualizados (D6) | ⚠ | El servidor lo cumple; el panel de inicio ofrece «Cancelar» con menos de 24 h (R2) |
| CA4 | `ReprogramarCitaTest::test_aplica_la_regla_de_agenda_al_nuevo_hueco`, `test_la_propia_cita_no_cuenta_como_conflicto`, `test_una_fecha_pasada_o_igual_se_rechaza`, `test_la_nueva_hora_se_interpreta_en_la_zona_de_la_clinica` | ✅ | Casos latentes: segundos en «misma fecha y hora» (R9) y duración < 1 (R10); dentista inactivo fuera de CA4 (R7) |
| CA5 | `ReprogramarCitaTest::test_una_confirmada_vuelve_a_agendada`, `test_una_cita_cerrada_o_empezada_no_se_reprograma`; `ReprogramarCitaRolesTest::test_el_formulario_de_una_cita_que_no_se_puede_mover_muestra_el_motivo`; `ReprogramarCitaPacienteTest::test_el_paciente_no_ve_estados_internos`; `EstadosCitaTransicionesTest` | ⚠ | En `test_el_paciente_no_ve_estados_internos`, el GET de los casos 2–4 ve el aviso del PATCH anterior (R4) |
| CA6 | `ReprogramarCitaVistasTest::test_la_seccion_no_reagendar_cancela_con_motivo`, `test_cada_formulario_muestra_sus_valores_y_errores_en_su_seccion`, `test_el_detalle_del_dentista_no_enlaza_a_reprogramar`; `ReprogramarCitaPacienteTest::test_el_paciente_cancela_con_motivo_desde_la_pantalla` | ⚠ | Cancela bien, pero al volver a la pantalla los mensajes se contradicen y no hay test (R1); la cancelación irreversible va en un clic (R6) |
| CA7 | `ReprogramarCitaTest::test_la_reprogramacion_deja_una_sola_entrada_de_historial`, `test_un_rechazo_no_deja_historial`; `ReprogramarCitaVistasTest::test_el_historial_muestra_la_reprogramacion` | ✅ | |
| CA8 | `ReprogramarCitaRolesTest` (dentista, asistente fuera de alcance, paciente ajeno, rutas cruzadas, `test_fuera_de_alcance_tampoco_cancela_definitivamente`) | ✅ | GET y PATCH, con la cita sin cambios |
| CA9 | `ReprogramarCitaTest::test_los_campos_anadidos_a_mano_no_cambian_la_cita`, `test_una_fecha_u_hora_mal_formada_da_error_de_validacion`, `test_los_mensajes_de_validacion_estan_en_espanol`; `ReprogramarCitaPacienteTest::test_fuera_de_plazo_se_rechaza_aunque_se_envie_a_mano`, `test_las_listas_en_el_formulario_no_dan_error_del_servidor` | ✅ | Una aserción `assertSee('')` es vacía (R3); lo demás prueba el criterio |
| CA10 | `ReprogramarConcurrenteTest` (bloqueo real con segunda conexión, mismo hueco con agendado, interbloqueo, cita bloqueada) | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 28 archivos de código, todos con tarea; coinciden con «Cambios por módulo»).
- Partes del plan sin implementar: ninguna. T001, T031, T032 y T041 no tienen diff de código porque solo tocan documentación (`docs.stat`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, plan y tareas aprobados antes de `impl_base` (`961c9e4`) |
| P2 Test que falla antes y pasa después | ✅ | 5 archivos de test nuevos y 4 de la 026 ajustados (D6), sin `skip`; dos aserciones no prueban lo que dicen (R3, R4) |
| P3 Autorización por rol y recurso | ✅ (❌ aceptado en los GET existentes) | Rutas nuevas con `role:` y `Gate::authorize('reprogramar-cita')`; probado en `ReprogramarCitaRolesTest`. Excepción aceptada → specs 014 y 015 (`docs/roadmap.md`) |
| P4 Entrada validada y SQL con bindings | ✅ | `ReprogramacionCitas::reglasDeEntrada()` con `bail` y `date_format`; Query Builder con bindings; Blade solo con `{{ }}` |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas; datos de prueba ficticios |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios en lockfiles; avisos de `npm audit` preexistentes (R8) |
| P7 Auditoría de datos sensibles | ❌ aceptado | Solo historial de la cita; evento formal → 022, 403 → 020 |
| P8 Correlación y logs limpios | ❌ aceptado | Sin `Log::` nuevos; SQL en `QueryException` no prevista → 023 |
| P9 La interfaz usa solo el sistema de diseño | ⚠ | Todo el CSS nuevo usa tokens y los bloques tocados no tienen `style=`. En el bloque del historial, que la 028 tocó, el título sigue siendo un `<span>` y no un encabezado (R5) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 `detect --no-banner` | ✅ 0 hallazgos (321 commits) |
| semgrep 1.99.0 `--config p/php --metrics=off --error` (archivos de `app/` y `database/migrations/` tocados y `routes/web.php`) | ✅ 0 hallazgos |
| `composer audit --locked` · `npm audit --audit-level=high` | composer: ✅ sin avisos · npm: crít: 2 · alta: 1 · media: — (dependencias de desarrollo, preexistentes, corregidas en la 031; R8) |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos (sin secretos) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (TM1, TM2, CA8) | ✅ | `role:` por grupo y en cada ruta del paciente; Gate `reprogramar-cita` antes de validar; `citaDelPaciente` → 404; `ReprogramarCitaRolesTest` |
| A01:2025 Divulgación en el rechazo (TM8) | ✅ | `CitaNoAgendable::noDisponible()` genérico; `test_el_hueco_ocupado_no_revela_la_otra_cita` |
| A01:2025 Limitación de tasa | ❌ (menor) | Los PATCH de reprogramar y cancelar no tienen `throttle` (R13) |
| A02:2025 Configuración incorrecta | ➖ | Sin cambios de configuración |
| A03:2025 Cadena de suministro | ✅ | Sin cambios en lockfiles |
| A04:2025 Fallos criptográficos | ➖ | Sin criptografía ni datos sensibles en URLs |
| A05:2025 Inyección (SQL, XSS, validación; TM5, TM11) | ✅ | Bindings; solo `{{ }}`; `old()` solo si es texto; `test_el_motivo_de_la_reprogramacion_se_muestra_escapado`, `test_una_fecha_u_hora_mal_formada_da_error_de_validacion` |
| A06:2025 Diseño inseguro (TM3, TM4, TM6, TM10) | ✅ | Solo fecha, hora y motivo; plazo y estado comprobados con la cita bloqueada; `ReprogramarConcurrenteTest` |
| A07:2025 Autenticación | ➖ | Sin cambios |
| A08:2025 Integridad / CSRF | ✅ | `@csrf` y `@method('PATCH')` en los formularios nuevos |
| A09:2025 Registro y auditoría (TM9) | ✅ (P7 aceptado) | Una entrada de historial con autor, fechas y motivo; `test_ni_la_reprogramacion_ni_su_rechazo_escriben_en_los_logs` |
| A10:2025 Manejo de excepciones (TM7) | ✅ | 1205/1213 y `DeadlockException` → `noDisponible`; `test_un_interbloqueo_se_convierte_en_no_disponible` |
| Proceso: rutas protegidas e instrucciones a agentes | ✅ | Contra `origin/main` no se tocan rutas protegidas; sin instrucciones sospechosas. Los cambios sin commitear en `aidlc/` no son de esta spec |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no hay evento formal (P7 aceptado → 022); la reprogramación deja una entrada de historial probada por `test_la_reprogramacion_deja_una_sola_entrada_de_historial`.
- Datos sensibles en logs del código tocado: ninguno (`git diff 961c9e4 -- app/` sin `Log::` nuevos; `test_ni_la_reprogramacion_ni_su_rechazo_escriben_en_los_logs`). Residuo P8 aceptado → 023.
- `request_id` presente en los logs nuevos: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | app/Http/Controllers/ClienteCitasController.php:139; app/Http/Controllers/CitasController.php:356; resources/views/citas/partials/reprogramar.blade.php:40-43 | Tras cancelar con éxito desde «No reagendar», `back()` vuelve a la pantalla de reprogramar. Allí se muestran juntos el éxito y el motivo de que ya no se puede mover: al paciente, «Esta cita ya no se puede reprogramar…»; al personal, el texto de `cerrada()`. Ningún test sigue la redirección | Con `formulario=no-reagendar`, redirigir al detalle de la cita del rol; test que sigue la redirección |
| R2 | importante | resources/views/dashboard/cliente.blade.php:53; app/Http/Controllers/DashboardController.php:179 | El panel de inicio del paciente ofrece «Cancelar» en la próxima cita `agendada` sin mirar el plazo de 24 h: con menos, el clic siempre falla con el mensaje de plazo. Tampoco ofrece «Reprogramar» | Calcular `puede_cancelar` y `puede_reprogramar` con `EstadosCita::dentroDelPlazoDelPaciente` y mostrar el aviso corto fuera de plazo, con test |
| R3 | importante | tests/Feature/ReprogramarCitaTest.php:282 | `assertSee($this->errorDe(...))` se evalúa después del GET, cuando el flash `errors` ya caducó: `errorDe()` devuelve `''` y `assertSee('')` siempre pasa | Guardar el mensaje antes del GET y exigir que no esté vacío |
| R4 | importante | tests/Feature/ReprogramarCitaPacienteTest.php:198-201 | En el bucle, el GET de los casos 2–4 llega con el flash `error` del PATCH anterior, que pinta el layout. `assertSee(NO_REPROGRAMABLE)` pasaría aunque la pantalla mostrara el formulario | Sesión limpia en cada GET y comprobar que no aparece `name="fecha"` |
| R5 | importante | resources/views/citas/show.blade.php:116 | El bloque `#historial`, reescrito por la 028, sigue titulado con `<span class="section-title">`. Parece un encabezado y no lo es (WCAG 1.3.1). P9 pide corregir la deuda (DS7) al tocar la pantalla | `<h2 class="section-title">Historial de cambios</h2>`, con test |
| R6 | importante | resources/views/citas/partials/reprogramar.blade.php:89, 108 | «Cancelar la cita definitivamente» es irreversible y se envía en un clic, sin confirmación ni revisión (WCAG 3.3.4). La misma cancelación desde la lista y el detalle del paciente sí pide confirmación | Casilla obligatoria «Entiendo que la cancelación no se puede deshacer», con su etiqueta y validada en el servidor, sin JS (decisión del usuario, 2026-10-10: modifica D12, que seguía «sin `confirm()`») |
| R7 | importante | app/Agenda/ReprogramacionCitas.php:99 | Reprogramar no comprueba que el dentista siga activo, y agendar sí (025 CA10). Una cita de un dentista dado de baja se puede mover a un hueco futuro nuevo. CA4 solo cita 025 CA2–CA7 | Rechazar con `CitaNoAgendable::noDisponible()` si el usuario del dentista no está activo (decisión del usuario, 2026-10-10) |
| R8 | importante | package-lock.json (sin cambios en la 028) | `npm audit`: 2 críticas (`shell-quote`) y 1 alta (`source-map-js`) en dependencias de desarrollo, sin excepción en `docs/security.md`. Ya están corregidas en la 031 | Aceptado con condición (decisión del usuario, 2026-10-10, como el 2026-10-07): `/release 028` fusiona `main` con la 031 y repite `npm audit` en verde antes de avanzar |
| R9 | menor | app/Agenda/ReprogramacionCitas.php:106-108 | «Igual a la actual» compara el minuto elegido con `fecha_hora` y sus segundos. Una cita con segundos (datos importados) se «mueve» al mismo minuto, con historial, y una confirmada pasa a agendada. Hoy no hay ninguna en `dentissa` ni en `dentissa_test` | Comparar con `inicio($cita)->startOfMinute()` |
| R10 | menor | app/Agenda/ReprogramacionCitas.php:114-115 | Sin la guarda de `ReservaCitas::reservar` para `duracion_min < 1`: con duración 0, el solape estricto nunca salta | Lanzar `CitaNoAgendable::duracionInvalida()` |
| R11 | menor | app/Agenda/ReprogramacionCitas.php:119 | El `update` no reinicia `recordatorio_enviado`; el detalle del personal muestra «Enviado» para la fecha nueva. Hoy ningún código envía recordatorios | Reiniciarlo al reprogramar cuando existan los recordatorios |
| R12 | menor | app/Http/Controllers/ClienteCitasController.php:233; resources/views/citas/cliente-show.blade.php; cliente-index.blade.php | Las vistas no leen `puede_reprogramar`: el enlace «Reprogramar» depende de `puede_cancelar` | Usar `puede_reprogramar` en el enlace |
| R13 | menor | routes/web.php:58-59, 140-141, 158-159 | Los PATCH de reprogramar y cancelar no tienen `throttle`. Con un script se puede hacer crecer el historial y competir por el bloqueo del dentista. Preexistente en el agendado (TM6 de la 025) | Limitador por usuario en una spec posterior (ya hay viñeta «Límite de peticiones en las rutas de escritura») |
| R14 | menor | resources/views/citas/partials/reprogramar.blade.php:66, 77, 98-104 | El error de `estado` del personal marca como inválido el campo `cancelar-motivo`. Puede haber varias `role="alert"` al cargar y el foco no va al primer error | Error de `estado` como mensaje del formulario; `autofocus` en el primer campo con error |
| R15 | menor | public/css/app.css:496; resources/views/citas/cliente-index.blade.php:58-64; resources/views/layouts/app.blade.php:207-224 | `.aviso-plazo` sin margen inferior en «No reagendar». En cada fila, «Ver», «Reprogramar» y «Cancelar» tienen el mismo nombre accesible. Los avisos flash no tienen `role` (DS7, previo) | Margen con `--space-5`; texto oculto con la fecha; `role="status"`/`role="alert"` al corregir DS7 |
| R16 | menor | tests/Feature/ReprogramarCitaTest.php:71-89, 101, 315; tests/Feature/ReprogramarCitaVistasTest.php:437-453; tests/Feature/ReprogramarConcurrenteTest.php:152-164 | Huecos en los tests: CA1 sin vista de agenda; sin caso `disponible = 0`; la aserción sobre «Paula» no puede fallar; `literales()` no ve `rem`, `em` ni `ms`; el test de logs no pasa por HTTP | Completarlos al volver a tocar estos tests |
| R17 | menor | app/Http/Controllers/CitasController.php:382-415; app/Http/Controllers/ClienteCitasController.php:121-126, 159-215; app/Agenda/ReprogramacionCitas.php:24, 93, 96 | Duplicación: `reprogramar()` y `motivoNoReprogramable()` en los dos controladores; `citaDelPaciente()` frente a `show()`; reglas de `motivo` en tres sitios; cuarta copia de `ERRORES_DE_BLOQUEO`. Además, `NotFoundHttpException` en el servicio de dominio y una consulta extra de `dentista_id` dentro de la sección bloqueada | Extraer al refactorizar el módulo Agenda (spec 013) |
| R18 | menor | app/Http/Controllers/CitasController.php:359; app/Http/Controllers/ClienteCitasController.php:239 | El encabezado `// ── UPDATE NOTAS` quedó sobre la sección de reprogramar. `fueraDePlazo()` recibe `$estados` sin usarlo y depende de que antes se ejecute `conPuedeCancelar` | Mover el comentario; `fueraDePlazo` calcula con `dentroDelPlazoDelPaciente` |

## Preparación para release
- Rollback factible: sí. La migración solo añade columnas que admiten nulo, su `down()` las quita, y `deploy/volver.sh <tag anterior>` no necesita revertirla ni restaurar la copia (`docs/deployment.md`, Migraciones).
- Migraciones compatibles con la versión anterior: sí (solo *expand*; el código anterior ignora las columnas).
- Feature flags: el plan no pide ninguno.
- Docs actualizadas (architecture, deployment): sí (T090, T091; también `security.md`).
- SCA: avisos de `npm audit` sin excepción, aceptados con la condición de R8.
- Cobertura de riesgos: la spec no declara correcciones de riesgos dentro; ningún riesgo cambia de estado.

## Tareas añadidas
- T060 ← R1, R6 (test)
- T061 ← R6
- T062 ← R1, R6
- T063 ← R2 (test)
- T064 ← R2
- T065 ← R3
- T066 ← R4
- T067 ← R5 (test)
- T068 ← R5
- T069 ← R7, R9, R10 (test)
- T070 ← R7, R9, R10
- T071 ← R12
- T072 ← R18

## Aceptados sin tarea
- **R11** → roadmap: al reprogramar no se reinicia `recordatorio_enviado`; reiniciarlo cuando existan los recordatorios.
- **R13** → roadmap: los PATCH de reprogramar y cancelar no tienen límite de peticiones; entra en la viñeta del límite en las rutas de escritura.
- **R14** → roadmap: en la pantalla de reprogramar, el error de `estado` marca el campo motivo y no hay foco en el primer error.
- **R15** → roadmap: margen de `.aviso-plazo`, nombres accesibles repetidos en la lista del paciente y `role` de los avisos flash (DS7).
- **R16** → roadmap: completar los tests de la 028 (agenda en CA1, `disponible = 0`, aserción de «Paula», `literales()` con `rem`/`em`/`ms`, test de logs por HTTP).
- **R17** → roadmap: duplicación de la reprogramación entre controladores, de las reglas de `motivo` y de `ERRORES_DE_BLOQUEO`; excepción HTTP en el servicio de dominio → refactorización de la spec 013.
