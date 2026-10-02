---
spec: 026-estados-cita
verdict: approved
round: 2
date: 2026-10-02
base: 05ebda7
head: e4f8f59
human_signoff: no requerido
---

# Review · 026 Estados de la cita y quién puede cambiarlos

## Resumen
**approved.** Los seis hallazgos que se decidió corregir en la ronda 1 (R1, R4, R5, R10, R20, R26) quedan corregidos y
probados. Los tests nuevos de R1, R10 y R26 fallaban antes del cambio, y los de R4, R5 y R20 se comprobaron rompiendo
el código que protegen. El diff de esta ronda (`05ebda7..e4f8f59`, unos 10 KB) se revisó directamente, releyendo los
archivos, como prevé `--rerun` para un diff pequeño. Solo trae un menor nuevo (R27), aceptado para el roadmap.

Conteo de la ronda: bloqueante 0 · importante 0 · menor 1.

Ronda anterior: [history/review.r1.md](history/review.r1.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 127 passed (788 assertions); línea base de T001: 77 |
| `vendor/bin/pint --test` sobre los 16 archivos PHP tocados desde `impl_base` | ✅ sin cambios |
| `python .ai/bin/aidd.py validate docs/specs/026-estados-cita` | ✅ 0 errores |
| Restos en `dentissa_test` | ✅ ninguno (8 usuarios, 2 citas, 1 historial) |

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `CitasController.php:339-340` (mensajes de `motivo.string` y `motivo.max` en español); `citas/show.blade.php:197-199` (`@error('motivo')` bajo el campo, con el patrón de `create.blade.php`). `EstadosCitaTransicionesTest::test_un_motivo_demasiado_largo_muestra_un_mensaje_en_espanol` comprueba el texto exacto en la sesión y en la sección `estado` del detalle; antes daba el mensaje en inglés. |
| R4 | corregido | `EstadosCitaRolesTest.php:239`: texto exacto del mensaje del paciente para `en_curso`, `completada`, `cancelada` y `no_asistio`. Comprobado acortando un momento el texto traducido. |
| R5 | corregido | `HistorialCitaTest.php:71-76`: 302 en el rechazo por regla, 403 en el rechazo por rol y estado sin cambios. Comprobado con `abort(500)` al principio de `updateEstado`. |
| R10 | corregido | `CitasController.php:349`: `back()->withInput()->withErrors(...)`. `test_un_rechazo_por_la_regla_conserva_el_motivo_escrito` fallaba antes. `destroy` no tiene entrada que conservar. |
| R20 | corregido | `HistorialCitaTest::test_si_falla_el_historial_la_cita_no_cambia`: un autor inexistente hace fallar el INSERT por la clave foránea, y la cita sigue `agendada` (leída con bloqueo). Comprobado quitando un momento la transacción de `EstadosCita::cambiar`. |
| R26 | corregido | `ClienteCitasController.php:88-91` y `citas/cliente-show.blade.php:14-15`: con la cita agendada o confirmada ya empezada, el detalle muestra el mensaje `yaEmpezo` en lugar del botón. `EstadosCitaVistasTest::test_el_paciente_ve_por_que_no_puede_cancelar_una_cita_que_ya_empezo` fallaba antes. |

## Criterios de aceptación
Sin cambios respecto a la ronda 1: los 11 criterios ✅. CA2 gana la comprobación exacta del mensaje del paciente (R4),
CA7 la explicación en el detalle (R26), y CA9 el mensaje visible del motivo (R1) y el test de atomicidad (R20).

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los 8 archivos del diff están citados en T016–T018, T028 y T029.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Tareas añadidas por `/review` con las reglas de `/tasks`; no requieren `/analyze`. |
| P2 | ✅ | T016 y T018 fallaban antes de T028 y T029; T017 comprobado rompiendo el código (notas de las tareas). |
| P3 | ✅ con excepción aceptada | Sin cambios de autorización en esta ronda. |
| P4 | ✅ | Validación de `motivo` con mensajes en español; salida escapada (`{{ }}`) del aviso nuevo. |
| P5 | ✅ | gitleaks limpio. |
| P6 | ✅ | Sin dependencias nuevas; composer audit y npm audit en 0 altas o críticas. |
| P7 | ✅ con excepción aceptada | Sin cambios. |
| P8 | ✅ con excepción aceptada | Sin logs nuevos. |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`7aa05ac..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (PHP de app/, database/, config/, routes/ tocados) | ✅ 0 hallazgos |
| composer audit `--locked` / npm audit `--audit-level=high` | ✅ crít: 0 · alta: 0 |
| trivy fs HIGH,CRITICAL | ✅ sin cambios en lockfiles ni en el Dockerfile desde la ronda 1 (0 hallazgos entonces) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Inyección y XSS | ✅ | El aviso del paciente y el error del motivo se muestran escapados. |
| A01:2025 Control de acceso | ✅ | Sin cambios en esta ronda. |
| A06:2025 Diseño (atomicidad) | ✅ | TM7 ahora probado (R20). |

## Observabilidad
- Historial: sin cambios; la atomicidad del UPDATE y el INSERT queda probada (R20).
- Datos sensibles en logs del código tocado: ninguno; sin logs nuevos.
- `request_id` en logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R27 | menor | `resources/views/citas/show.blade.php:194`, `app/Http/Controllers/CitasController.php:334` | `maxlength="500"` cuenta cada salto de línea como 1 carácter, pero el navegador lo envía como CRLF (2). Un motivo con saltos que el formulario admite puede rechazarse en el servidor; desde R1 lo hace con un mensaje claro en español. | Normalizar CRLF a LF antes de validar, o contar igual en los dos lados. |

## Preparación para release
- Rollback factible: sí, sin cambios respecto a la ronda 1 (el `down` de la migración recrea el trigger).
- Migraciones: ninguna nueva en esta ronda.
- Docs actualizadas: sin cambios necesarios; los textos nuevos no cambian contratos ni pasos de despliegue.
- Cobertura de riesgos: sin cambios; OB5.a "en curso (spec 026)", RS2.a y OB5.b "pendiente" con nota.

## Tareas añadidas
Ninguna en esta ronda. De la ronda 1: T016 ← R1, R10 · T017 ← R4, R5, R20 · T018 ← R26 · T028 ← R1, R10 · T029 ← R26.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-02 (van al roadmap en `/release`):
- **R2** → roadmap: mensaje propio cuando se pide el mismo estado que ya tiene la cita.
- **R3** → roadmap: tests del paciente en el borde exacto de la hora y con una cita agendada ya empezada.
- **R6** → roadmap: aserciones que fijan las fechas en UTC (D4); actualizarlas con la spec que corrija D4.
- **R7** → roadmap: documentar en `CambioEstadoConcurrenteTest` que el bloqueo real llega como `DeadlockException`.
- **R8** → roadmap: limpieza robusta en `CambioEstadoConcurrenteTest` si `dentissa_test` conserva el trigger.
- **R9** → roadmap: quitar la rama `cliente → admin` del helper `cambiar` de la fixture.
- **R11** → roadmap: derivar los destinos del admin en `CambiarEstadoCita` de `EstadosCita::TRANSICIONES`.
- **R12** → roadmap: `conPuedeCancelar` (y `aviso_cancelar`) deben usar la regla del servicio.
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
- **R27** → roadmap: el límite de 500 caracteres del motivo cuenta distinto los saltos de línea en el navegador y en el servidor.
