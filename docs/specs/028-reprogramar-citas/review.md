---
spec: 028-reprogramar-citas
verdict: approved
round: 3
date: 2026-10-11
base: 0498553
head: efec3f8
human_signoff: no requerido
---

# Review · 028 Reprogramar citas

## Resumen
`approved`. Las correcciones de la ronda 2 funcionan:
- **Importantes:** R19, R20 y R21 corregidos.
- **Menores:** R22, R23, R24 y R26 corregidos.

Sale un solo hallazgo nuevo, R32 (menor, va al roadmap): la lista del paciente hace una consulta por cita.
Conteo: bloqueante 0 · importante 0 · menor 1

Rango: `0498553..efec3f8` (correcciones T073–T080 y cierre). El diff es de 25 KB, así que la
revisión de `--rerun` la hace el agente principal releyendo los archivos desde cero, sin
subagentes. Las capturas de T077 y T078 las confirmó el usuario. El diseño para móvil queda para una
spec propia (DS8, decisión del usuario, 2026-10-11): a 390 px el documento ya se desbordaba por la
barra lateral fija antes de la 028, así que no es un hallazgo de esta spec.

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 256 passed, 0 fallos (línea base de T002: 183, sin fallos) |
| tests de reprogramar (`--filter Reprogramar`), dos ejecuciones | ✅ 72 passed las dos veces, sin intermitentes |
| `docker compose exec app vendor/bin/pint --test <22 archivos PHP tocados desde 961c9e4>` | ✅ PASS |
| `npm run build` | ✅ salida 0 |
| `python .ai/bin/aidd.py validate docs/specs/028-reprogramar-citas` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReprogramarCitaTest` (admin y asistente), `ReprogramarCitaVistasTest::test_el_detalle_ofrece_reprogramar_solo_si_se_puede` | ✅ | |
| CA2 | `ReprogramarCitaPacienteTest::test_el_panel_de_inicio_respeta_el_plazo`, `test_con_el_dentista_inactivo_el_paciente_solo_puede_cancelar` | ✅ | |
| CA3 | `ReprogramarCitaPacienteTest::test_el_paciente_cancela_con_24_horas_o_mas`, `test_el_paciente_no_cancela_con_menos_de_24_horas`, `test_el_panel_de_inicio_respeta_el_plazo` | ✅ | |
| CA4 | `ReprogramarCitaTest::test_no_se_reprograma_la_cita_de_un_dentista_inactivo`, `test_una_cita_sin_duracion_no_se_reprograma`; `ReprogramarCitaVistasTest::test_sin_dentista_activo_o_sin_duracion_no_se_ofrece_reprogramar` | ✅ | Corregido R19: el detalle, el panel y la pantalla ya no ofrecen mover la cita, y el personal ve el motivo |
| CA5 | `ReprogramarCitaPacienteTest::test_el_paciente_no_ve_estados_internos`, `test_con_el_dentista_inactivo_el_paciente_solo_puede_cancelar` | ✅ | El paciente recibe el mensaje genérico para `dentistaInactivo` y `sinDuracion` |
| CA6 | `ReprogramarCitaVistasTest::test_un_rechazo_desde_no_reagendar_se_ve_en_la_pantalla`, `test_la_casilla_solo_se_pide_al_cancelar`, `test_no_reagendar_valida_en_el_servidor` y los de la ronda 2 | ✅ | Corregidos R20, R22 y R23 |
| CA7 | `ReprogramarCitaVistasTest::test_el_historial_tiene_su_encabezado` y los de la ronda 1 | ✅ | |
| CA8 | `ReprogramarCitaRolesTest` | ✅ | |
| CA9 | `ReprogramarCitaTest::test_una_fecha_u_hora_mal_formada_da_error_de_validacion` | ✅ | |
| CA10 | `ReprogramarConcurrenteTest` | ✅ | `comprobarReprogramable` se sigue llamando con el dentista y la cita ya bloqueados (`ReprogramacionCitas.php:99-104`) |

## Conformidad con el plan
- **Cambios fuera del plan:** ninguno. Los 12 archivos de código del rango tienen tarea de /review (`scope.md`: 0 sin tarea, 0 tareas sin diff).
- **Partes del plan sin implementar:** ninguna. D10 y D12 siguen sin anotar en el plan; es R27, aceptado y llevado al roadmap en la ronda 2.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | T073–T080 añadidas por /review antes de implementarlas |
| P2 Test que falla antes y pasa después | ✅ | T073, T076 y T079 fallaron antes de su implementación (notas de `tasks.md`); el de R21 se comprobó rompiéndolo |
| P3 Autorización por rol y recurso | ✅ (❌ aceptado en los GET existentes) | Sin rutas nuevas ni cambios en los Gates |
| P4 Entrada validada y SQL con bindings | ✅ | `confirmar` con `exclude_unless:formulario,no-reagendar\|exclude_unless:estado,cancelada\|accepted`; Query Builder con bindings |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas en el rango |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios en lockfiles; `composer audit` limpio; `npm audit` según R8 (ronda 1) |
| P7 Auditoría | ❌ aceptado | Sin cambios respecto a la ronda 1 |
| P8 Correlación y logs limpios | ❌ aceptado | Sin `Log::` nuevos |
| P9 La interfaz usa solo el sistema de diseño | ✅ | «Tu próxima cita» es `<h2 class="section-title">`; `.proxima-acciones` solo con tokens y sin `style=` (`test_las_reglas_css_nuevas_solo_usan_tokens`, `test_lo_nuevo_solo_usa_el_sistema_en_el_panel_del_paciente`); el foco de la casilla tiene test (R26) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 `git --log-opts=0498553..HEAD` | ✅ 10 commits, sin fugas |
| semgrep 1.99.0 `--config p/php --metrics=off --error` (6 archivos de `app/` tocados en el rango) | ✅ 0 hallazgos |
| `composer audit --locked --ignore-severity=low --ignore-severity=medium` · `npm audit` | composer: ✅ sin avisos · npm: sin cambios desde la ronda 1 (R8, aceptado con condición) |
| trivy fs | sin cambios de dependencias ni de imágenes desde la ronda 1 (✅ 0 HIGH/CRITICAL el 2026-10-10) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | Sin rutas nuevas; `sePuedeMover` solo decide si se muestra un enlace |
| A05:2025 Inyección y validación | ✅ | Sin entradas nuevas; mensajes escapados con `{{ }}` |
| A06:2025 Carreras | ❌ (menor, aceptado) | Sin cambios: `usuarios.activo` se lee sin bloqueo (R30, ronda 2) |
| A10:2025 Manejo de excepciones | ✅ | El rechazo desde «No reagendar» vuelve como aviso visible (R20) y el del dentista inactivo, como aviso con su motivo, no como error de la hora |
| Proceso | ✅ | Sin rutas protegidas ni dependencias nuevas; los cambios sin commitear de `aidlc/` no son de esta spec y no se incluyen |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sin cambios (P7 aceptado → 022).
- Datos sensibles en logs del código tocado: ninguno (sin `Log::` en el rango).
- `request_id` presente en los logs nuevos: no aplica (sin logs nuevos).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R32 | menor | app/Http/Controllers/ClienteCitasController.php:57-58, 248; app/Agenda/EstadosCita.php:132-135 | `conPuedeCancelar` llama ahora a `comprobarReprogramable` por cada cita de «Mis citas» que esté dentro de plazo. Eso es una consulta por fila para leer `usuarios.activo` (hasta 10 por página) | Leer `usuarios.activo` en la consulta de la lista con un JOIN, junto con R31 |

## Hallazgos de la ronda anterior
| ID | Estado | Evidencia |
|---|---|---|
| R19 | ✅ corregido | `EstadosCita::comprobarReprogramable` con `dentistaInactivo` y `sinDuracion`; `puede_reprogramar` usa esa comprobación en el detalle, la lista y el panel |
| R20 | ✅ corregido | `CitasController::updateEstado`: `$desdeNoReagendar` devuelve el rechazo con `with('error', …)` |
| R21 | ✅ corregido | `dashboard/cliente.blade.php:26` y `.proxima-acciones` en `app.css` |
| R22 | ✅ corregido | `novalidate` en `citas/partials/reprogramar.blade.php:90` |
| R23 | ✅ corregido | `confirmar` y la redirección al detalle solo con `estado=cancelada` |
| R24 | ✅ corregido | `mensajeParaElPaciente` ya no trae `transicion` ni `vuelta` |
| R26 | ✅ corregido | Selector de la casilla en `test_los_controles_tienen_foco_visible` |
| R25, R27–R31 | aceptados (ronda 2) | Sin cambios; el código nuevo no los empeora (R29: `sePuedeMover` sigue el mismo patrón que `motivoNoReprogramable`) |

## Preparación para release
- **Rollback:** factible; no hay migraciones nuevas desde la ronda 1.
- **Migraciones:** compatibles con la versión anterior.
- **Docs:** `architecture.md` al día. La fila «Agenda (reprogramación)» recoge que las guardas de dentista activo y duración están en `comprobarReprogramable`. `deployment.md` y `security.md` no necesitan cambios.
- **SCA:** según R8 (ronda 1). Hay que fusionar `main` antes de `/release`.

## Tareas añadidas
- ninguna

## Aceptados sin tarea
- **R25** → roadmap: el helper `dom()` de los tests está en tres archivos; llevarlo al trait `EscenarioEstadosCita`.
- **R27** → roadmap: anotar en D10 y D12 del plan de la 028 los cambios de R1 y R6 (casilla obligatoria y `formulario` leído por los controladores).
- **R28** → roadmap: en «No reagendar» pueden salir dos `role="alert"` a la vez; resolverlo con el foco en el primer error (R14).
- **R29** → roadmap: la regla de acciones del paciente en tres sitios (`index`, `show` y panel), la constante pública entre controladores y `fueraDePlazo()` acoplada a `conPuedeCancelar` → un método en `EstadosCita` (spec 013).
- **R30** → roadmap: el dentista activo se lee sin bloqueo; llevar las guardas de dentista activo y duración a `ReservaCitas::bloquearDentista` y `comprobarHueco` para agendar y reprogramar (spec propia o la 013).
- **R31** → roadmap: consultas de más en el PATCH del paciente y en el detalle del personal (junto con R17).
- **R32** → roadmap: «Mis citas» hace una consulta por cita para saber si el dentista sigue activo; leerlo en la consulta de la lista (junto con R31).
