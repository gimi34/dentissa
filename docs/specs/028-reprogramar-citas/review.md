---
spec: 028-reprogramar-citas
verdict: changes_requested
round: 2
date: 2026-10-11
base: 23198da
head: 0498553
human_signoff: no requerido
---

# Review · 028 Reprogramar citas

## Resumen
`changes_requested`. Las correcciones de la ronda 1 funcionan: R1–R7, R9, R10 y R12 quedan corregidos
y R18 solo en parte (ver R29). Pero esas correcciones abren tres importantes que el usuario decide
corregir. R19: T070 rechaza en el servidor al dentista inactivo y la duración inválida, pero la
pantalla sigue ofreciendo «Reprogramar» y el usuario queda en un bucle sin salida. R20: un rechazo
del personal desde «No reagendar» se pierde. R21: el bloque del panel que tocó T064 conserva deuda de
P9. De los menores, se corrigen R22, R23, R24 y R26 y el resto va al roadmap.
Conteo: bloqueante 0 · importante 3 · menor 10

Rango: `23198da..0498553` (correcciones T060–T072). Revisión con un subagente independiente
(encargos A, C+D y S juntos, diff de 44 KB) más los hallazgos de cuatro `/code-review` lanzadas
por el usuario, deduplicados y verificados en el código. Las comparaciones de fecha en UTC frente a
hora local (listas y panel del paciente, hora del historial), que señalan las `/code-review`, ya
están en `docs/roadmap.md` y no se cuentan como hallazgo nuevo.

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 251 passed, 0 fallos (línea base de T002: 183, sin fallos) |
| tests de reprogramar (`--filter Reprogramar`), segunda ejecución | ✅ 67 passed, sin intermitentes |
| `docker compose exec app vendor/bin/pint --test <22 archivos PHP tocados desde 961c9e4>` | ✅ PASS |
| `npm run build` | ✅ salida 0 |
| `python .ai/bin/aidd.py validate docs/specs/028-reprogramar-citas` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReprogramarCitaTest` (admin y asistente), `ReprogramarCitaVistasTest::test_el_detalle_ofrece_reprogramar_solo_si_se_puede` | ✅ | |
| CA2 | `ReprogramarCitaPacienteTest::test_el_panel_de_inicio_respeta_el_plazo` y los de la ronda 1 | ✅ | Corregido R2 |
| CA3 | `ReprogramarCitaPacienteTest::test_el_paciente_cancela_con_24_horas_o_mas`, `test_el_paciente_no_cancela_con_menos_de_24_horas`, `test_el_panel_de_inicio_respeta_el_plazo` | ✅ | |
| CA4 | `ReprogramarCitaTest::test_no_se_reprograma_la_cita_de_un_dentista_inactivo`, `test_la_misma_hora_con_segundos_se_rechaza_como_igual`, `test_una_cita_sin_duracion_no_se_reprograma` y los de la ronda 1 | ⚠ | El servidor rechaza bien, pero la pantalla sigue ofreciendo reprogramar (R19) |
| CA5 | `ReprogramarCitaPacienteTest::test_el_paciente_no_ve_estados_internos` (sesión limpia por caso) | ✅ | Corregido R4 |
| CA6 | `ReprogramarCitaVistasTest::test_no_reagendar_pide_confirmar_la_cancelacion`, `test_tras_cancelar_desde_no_reagendar_se_vuelve_al_detalle`; `ReprogramarCitaPacienteTest::test_el_paciente_confirma_la_cancelacion_desde_la_pantalla` | ⚠ | Corregidos R1 y R6; un rechazo del personal desde «No reagendar» se pierde (R20) |
| CA7 | `ReprogramarCitaVistasTest::test_el_historial_tiene_su_encabezado` y los de la ronda 1 | ✅ | Corregido R5 |
| CA8 | `ReprogramarCitaRolesTest` | ✅ | |
| CA9 | `ReprogramarCitaTest::test_una_fecha_u_hora_mal_formada_da_error_de_validacion` (mensaje leído antes del GET) | ✅ | Corregido R3 |
| CA10 | `ReprogramarConcurrenteTest` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: `DashboardController.php` y `dashboard/cliente.blade.php` (R2, tarea T064), y `app.css` en T064 (decisión del usuario: `.acciones-fila` con salto de línea). Todos tienen tarea de /review (`scope.md`: 14 archivos, 0 sin tarea).
- Partes del plan sin implementar: ninguna. D10 y D12 cambiaron por decisión del usuario en la ronda 1 (R1, R6), sin anotarlo en el plan (R27).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Tareas de /review añadidas antes de implementarlas |
| P2 Test que falla antes y pasa después | ✅ | T060, T063, T067 y T069 fallaron antes de su implementación; T065 y T066 comprobados rompiendo |
| P3 Autorización por rol y recurso | ✅ (❌ aceptado en los GET existentes) | Sin rutas nuevas; el panel del paciente calcula acciones sin cambiar su control (excepción aceptada → 015) |
| P4 Entrada validada y SQL con bindings | ✅ | `formulario` y `confirmar` validados en los dos controladores; Query Builder |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios en lockfiles; `composer audit` limpio; `npm audit` según R8 (ronda 1) |
| P7 Auditoría | ❌ aceptado | Sin cambios respecto a la ronda 1 |
| P8 Correlación y logs limpios | ❌ aceptado | Sin `Log::` nuevos |
| P9 La interfaz usa solo el sistema de diseño | ⚠ | Lo nuevo usa tokens y no tiene `style=`, pero el bloque «Tu próxima cita» que tocó T064 conserva el título en `<span>` y un contenedor con `style=` (R21) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 `detect --no-banner` | ✅ 0 hallazgos |
| semgrep 1.99.0 `--config p/php --metrics=off --error` (archivos de `app/` tocados desde `23198da` y `routes/web.php`) | ✅ 0 hallazgos |
| `composer audit --locked` · `npm audit` | composer: ✅ sin avisos · npm: sin cambios desde la ronda 1 (R8, aceptado con condición) |
| trivy fs | sin cambios de dependencias ni de imágenes desde la ronda 1 (✅ 0 HIGH/CRITICAL el 2026-10-10) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | Sin rutas nuevas; el panel no amplía el alcance del paciente |
| A04/A06:2025 Diseño inseguro (confirmación de acción irreversible) | ✅ | La casilla es una confirmación de interfaz (R6, decisión del usuario); solo se exige desde «No reagendar» |
| A05:2025 Inyección y validación | ✅ | `formulario` con `in:`; `confirmar` con `accepted`; salida escapada |
| A06:2025 Carreras | ❌ (menor) | `usuarios.activo` se lee sin bloqueo frente a una baja simultánea (R30) |
| A10:2025 Manejo de excepciones | ⚠ | Un rechazo de estado desde «No reagendar» no se muestra al personal (R20) |
| Proceso | ✅ | Sin rutas protegidas ni dependencias nuevas; los cambios sin commitear de `aidlc/` no son de esta spec y no se incluyen |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sin cambios (P7 aceptado → 022).
- Datos sensibles en logs del código tocado: ninguno (`git diff 23198da -- app/` sin `Log::`).
- `request_id` presente en los logs nuevos: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R19 | importante | app/Agenda/EstadosCita.php (`comprobarReprogramable`); app/Agenda/ReprogramacionCitas.php:106-115; app/Http/Controllers/CitasController.php:299; app/Http/Controllers/ClienteCitasController.php:243; app/Http/Controllers/DashboardController.php:214 | T070 rechaza al guardar al dentista inactivo y la duración menor de 1, pero `comprobarReprogramable` no los cubre. El detalle, la lista, el panel y la pantalla siguen ofreciendo «Reprogramar» y el formulario, y cada envío vuelve con «Ese horario ya no está disponible…» o «La duración del servicio no es válida.»: un bucle sin salida | Comprobarlo en `comprobarReprogramable` con tipos propios. El personal ve «El dentista de esta cita ya no está activo y no se puede reprogramar. Cancélala o agenda una cita nueva con otro dentista.» (decisión del usuario, 2026-10-11) y el paciente, el mensaje genérico; `puede_reprogramar` deja de ser una copia de `puede_cancelar` |
| R20 | importante | app/Http/Controllers/CitasController.php:356-359; resources/views/citas/partials/reprogramar.blade.php:40-48, 98 | Si la cancelación del personal desde «No reagendar» falla con `CambioDeEstadoNoPermitido` (p. ej. la cita pasó a «en curso»), el error va a `errors['estado']`. La pantalla ya no muestra «No reagendar» y el layout no pinta `$errors`, así que el motivo se pierde. Al paciente no le pasa, porque recibe el error como aviso | Con `formulario=no-reagendar`, devolver el rechazo con `with('error', …)`, como el paciente |
| R21 | importante | resources/views/dashboard/cliente.blade.php:26, 50 | El bloque «Tu próxima cita», tocado por T064, conserva el título en `<span class="section-title">` (WCAG 1.3.1) y un contenedor con `style="margin-top:14px;…"`. P9 pide corregir esa deuda al tocar la pantalla (igual que R5) | `<h2 class="section-title">` y una clase acotada a `.formulario-accesible` con tokens en lugar de `style=` |
| R22 | menor | resources/views/citas/partials/reprogramar.blade.php:90 | El formulario «No reagendar» no lleva `novalidate` («Nueva fecha y hora» sí). Con `required` en la casilla, el navegador muestra su globo y el error en español, asociado a la casilla, nunca se ve | `novalidate` en ese formulario |
| R23 | menor | app/Http/Controllers/CitasController.php:341, 363 | La casilla y la redirección al detalle se aplican con `formulario=no-reagendar` aunque `estado` no sea `cancelada` (solo con una petición manipulada) | Exigir la casilla y redirigir solo si además `estado=cancelada` |
| R24 | menor | app/Http/Controllers/ClienteCitasController.php:234 | `mensajeParaElPaciente` traduce los tipos `transicion` y `vuelta`, que la reprogramación nunca lanza: son ramas muertas | Quitarlas |
| R25 | menor | tests/Feature/ReprogramarCitaPacienteTest.php:355 | Tercera copia del helper `dom()` (también en `ReprogramarCitaVistasTest` y `HorarioDentistaVistasTest`) | Llevarlo al trait `EscenarioEstadosCita` |
| R26 | menor | tests/Feature/ReprogramarCitaVistasTest.php:314-319; public/css/app.css:500 | El foco visible de la casilla (`.formulario-accesible .campo-casilla input:focus-visible`) no tiene test | Añadir el selector a `test_los_controles_tienen_foco_visible` |
| R27 | menor | docs/specs/028-reprogramar-citas/plan.md:330, 336 | D10 («ningún controlador lee `formulario`») y D12 («sin confirmación») ya no describen el código. Las decisiones de R1 y R6 están en `review.md`, `tasks.md` y `system.md`, pero no en el plan | Anotarlo en el plan en la próxima revisión del plan |
| R28 | menor | resources/views/citas/partials/reprogramar.blade.php:104, 117 | Con el motivo demasiado largo y la casilla sin marcar salen dos `role="alert"` a la vez en «No reagendar», y el foco no va al primer error (amplía R14) | Resolverlo junto con R14 |
| R29 | menor | app/Http/Controllers/DashboardController.php:211-218; app/Http/Controllers/ClienteCitasController.php:17, 251 | Tercera copia de la regla de acciones del paciente y una constante pública entre controladores; `fueraDePlazo()` sigue dependiendo de `conPuedeCancelar`, lo que queda de R18 (amplía R17) | Un método en `EstadosCita` que dé las acciones del paciente (refactorización de la 013) |
| R30 | menor | app/Agenda/ReprogramacionCitas.php:106; app/Agenda/ReservaCitas.php (`bloquearDentista`) | `usuarios.activo` se lee sin bloqueo y la baja de un dentista no bloquea su fila: una baja simultánea puede colarse. Las guardas de dentista activo y duración estarían mejor en `bloquearDentista` y `comprobarHueco`, que comparten agendar y reprogramar (cerraría también la carrera del agendado de la 025) | Spec propia o la 013 |
| R31 | menor | app/Http/Controllers/ClienteCitasController.php:191; app/Http/Controllers/CitasController.php:299 | Consultas de más: el PATCH del paciente lee la cita con tres JOIN solo para autorizar y el servicio la vuelve a leer; el detalle del personal recalcula el alcance en el Gate | Junto con R17 |

## Preparación para release
- Rollback factible: sí (sin migraciones nuevas desde la ronda 1).
- Migraciones compatibles con la versión anterior: sí.
- Docs actualizadas: `architecture.md` al día tras la ronda 1; `deployment.md` y `security.md` sin cambios necesarios.
- SCA: según R8 (ronda 1).

## Tareas añadidas
- T073 ← R19 (test)
- T074 ← R19
- T075 ← R19, R24
- T076 ← R21, R22, R26 (test)
- T077 ← R19, R21
- T078 ← R22
- T079 ← R20, R23 (test)
- T080 ← R20, R23

## Aceptados sin tarea
- **R25** → roadmap: el helper `dom()` de los tests está en tres archivos; llevarlo al trait `EscenarioEstadosCita`.
- **R27** → roadmap: anotar en D10 y D12 del plan de la 028 los cambios de R1 y R6 (casilla obligatoria y `formulario` leído por los controladores).
- **R28** → roadmap: en «No reagendar» pueden salir dos `role="alert"` a la vez; resolverlo con el foco en el primer error (R14).
- **R29** → roadmap: la regla de acciones del paciente en tres sitios (`index`, `show` y panel), la constante pública entre controladores y `fueraDePlazo()` acoplada a `conPuedeCancelar` → un método en `EstadosCita` (spec 013).
- **R30** → roadmap: el dentista activo se lee sin bloqueo; llevar las guardas de dentista activo y duración a `ReservaCitas::bloquearDentista` y `comprobarHueco` para agendar y reprogramar (spec propia o la 013).
- **R31** → roadmap: consultas de más en el PATCH del paciente y en el detalle del personal (junto con R17).
