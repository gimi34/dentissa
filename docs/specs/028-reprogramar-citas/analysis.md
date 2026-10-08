---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-10-08
spec_sha: 3311b28fbecc
plan_sha: ef6c735eb359
tasks_sha: e8d78c76eec7
---

# Análisis · 028 Reprogramar citas

## Resumen
No pasa. La pantalla nueva de reprogramar usaría campos, etiquetas y un botón principal que no
cumplen WCAG 2.1 AA (deuda DS1 y DS2), y P9 lo exige en pantallas nuevas sin que haya excepción
registrada (A1). Además hay tres hallazgos altos:
- los bloques modificados de vistas existentes no tienen comprobación de P9 (A2);
- el cambio de confirmada a agendada contradice 026 CA1 sin declararlo (A3);
- no está definida la zona horaria con la que se decide que la nueva fecha ya pasó (A4).

El análisis lo hizo un subagente independiente en modo completo, y el agente principal verificó en
el código A1, A3 y A4.

Conteo: CRÍTICA 1 · ALTA 3 · MEDIA 14 · BAJA 12

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 11 / 11 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda: no hay hallazgos anteriores.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución P9 | CRÍTICA | `plan.md` → Constitution Check P9 y Pantallas; T024 | La pantalla nueva usa `.field` y `.field-label` de `public/css/citas.css`: etiqueta en `--text-light` (3.51:1), borde en `--border` (1.31:1), foco `outline: none` con `--p300` (2.23:1), y `.btn-primary` blanco sobre `--p400` (3.93:1). Todo es deuda DS1/DS2 y no cumple AA, que P9 exige en pantallas nuevas. No hay excepción. T024 tampoco dice que la vista cargue `citas.css`. | Todo par texto/fondo de la pantalla nueva da ≥ 4.5:1, y el borde de control y el foco ≥ 3:1, solo con tokens. Test de pares de contraste como `HorarioDentistaVistasTest::test_los_pares_de_contraste_cumplen_aa` sobre los pares de la pantalla, más un test de que fecha, hora, motivo y botones tienen `:focus-visible` con `--focus-ring` (decisión del usuario, 2026-10-08: cumplir AA ahora). `/plan --fix`, `/tasks --fix`. |
| A2 | Constitución P9 | ALTA | T015, T026, T028, T029 | Los bloques que se modifican en `citas/show` (botón y entrada de historial) y en `cliente-show`/`cliente-index` (Cancelar y aviso de plazo) no tienen comprobación automática. El test de T015 solo mira los archivos nuevos, y hoy esos bloques llevan `style=` en línea. | Los bloques tocados quedan sin `style=` y con clases del sistema; el resto de cada vista sigue como deuda DS4 (decisión del usuario, 2026-10-08: solo lo que se toca). Test que amplía `test_lo_nuevo_solo_usa_el_sistema` a las secciones modificadas, por `id`. `/plan --fix`, `/tasks --fix`. |
| A3 | Inconsistencia con spec extendida | ALTA | `spec.md` CA5 y su fila de Decisiones (`brecha`); `plan.md` → Impacto | 026 CA1 dice que solo se aceptan sus transiciones, y `vuelta()` prohíbe volver a «Agendada». La 028 introduce confirmada → agendada sin declararlo como contradicción. El plan solo anota que cambia 026 CA7. | Fila `contradicción` con fuente `usuario`; CA5 dice que reemplaza 026 CA1 en ese punto (solo al reprogramar); Impacto lo incluye para `/release`. Tests: `test_una_confirmada_vuelve_a_agendada` y `EstadosCitaTransicionesTest::test_volver_a_agendada_nunca_se_permite` sin cambios en `PATCH …/estado`. `/specify --edit`, `/plan --fix`. |
| A4 | Ambigüedad / zona horaria | ALTA | `plan.md` → fila de `ReprogramacionCitas` y D4; T022; T011 | No está definida la zona en la que se decide que la nueva fecha ya pasó. `ReservaCitas` construye `$inicio` en UTC (`config/app.php` usa `timezone` UTC) a partir de la hora local, mientras que `EstadosCita::inicio` usa la zona de la clínica. Comparar con `now()` rechazaría horas válidas de las próximas 6 h. | La nueva fecha y hora se interpreta en la zona de la clínica. Test en T011: con ahora = 2026-10-05 16:00 UTC (10:00 local), mover a las 12:00 locales del mismo día se acepta y a las 09:59 se rechaza con `enElPasado()`. `/plan --fix`, `/tasks --fix`. |
| A5 | Inconsistencia de diseño | MEDIA | `plan.md` → Pantallas y D5; T024, T027 | La pantalla tiene dos formularios con campo `motivo`, que comparten `old('motivo')`, el error y el `id`. `updateEstado` devuelve su error en `estado`, que T024 no pinta en `#no-reagendar`. | Cada formulario muestra sus valores y errores solo en su sección, sin `id` duplicados. Test: tras un rechazo de reprogramar con motivo, el motivo de `#no-reagendar` sale vacío y sin error; tras una cancelación rechazada, el error sale dentro de `#no-reagendar`. `/plan --fix`, `/tasks --fix`. |
| A6 | Cobertura | MEDIA | `plan.md` → Rutas (texto bajo la tabla); T011–T015 | Ningún test cubre el GET del formulario para una cita que ya no se puede mover (cerrada, empezada o fuera de plazo). | El GET da 200, muestra el motivo y un enlace al detalle, y no tiene formulario de reprogramar ni «No reagendar» (decisión del usuario, 2026-10-08: solo el aviso, también en una agendada ya empezada). Test por rol y por caso. `/plan --fix`, `/tasks --fix`. |
| A7 | Ambigüedad (026 D7) | MEDIA | `plan.md` → Mensajes; T027 | No está definido qué ve el paciente al reprogramar (o al abrir el GET) una cita cerrada o en curso. `cerrada()` está escrito para el personal y deja ver estados internos. | Texto del paciente sin estados internos, como en la cancelación de la 026. Test en `ReprogramarCitaPacienteTest`: con una cita completada, no asistida o en curso, el paciente recibe ese texto y nunca el de `cerrada()`. `/plan --fix`, `/tasks --fix`. |
| A8 | RNF «mensajes en español» / P4 | MEDIA | `plan.md` → Entrada; T025, T027 | `mensajesDeEntrada()` no define `required`, y faltan `motivo.string` y `motivo.max` en reprogramar y en la cancelación del paciente. Con `APP_LOCALE=en` salen en inglés. | Todos los rechazos de validación, en español. Test: fecha u hora vacías, y motivo como lista o de más de 500 caracteres, en las dos rutas, con el texto exacto y sin 500. `/plan --fix`, `/tasks --fix`. |
| A9 | Realizabilidad | MEDIA | `plan.md` → fila de `ReprogramacionCitas`; T014, T022 | No están definidos el constructor ni las dependencias de `ReprogramacionCitas`. T014 lo instancia con una conexión simulada antes de que exista, ni cómo se fija la espera de bloqueo en los tests. | Firma del constructor fijada en el plan (conexión compartida con `ReservaCitas`). `test_un_interbloqueo_se_convierte_en_no_disponible` se escribe contra ella. `/plan --fix`, `/tasks --fix`. |
| A10 | Inconsistencia plan ↔ tareas (D6) | MEDIA | `plan.md` D6 y fila de tests de Cambios por módulo | El plan omite `EstadosCitaVistasTest` (2 tests) y da un motivo equivocado para `HistorialCitaTest` (lo rompe el plazo, no las columnas). La parte del listado de `test_el_paciente_no_ve_cancelar…` usa `?filtro=pasadas`: mover la cita a 24 h o más obliga a cambiar también el filtro. No se encontraron más tests afectados. | D6 lista los cuatro archivos; T028 cambia también el filtro. `EstadosCitaVistasTest` en verde al cerrar T028. `/plan --fix`, `/tasks --fix`. |
| A11 | Constitución P2 | MEDIA | `plan.md` → P2 y Trazabilidad | El plan dice que todos los tests fallan antes del cambio, pero las tareas identifican tests de caracterización (cancelar fuera de alcance, el paciente cancela con ≥ 24 h, 404 de cita ajena, logs). P2 (1.2.0) exige distinguirlos. | La Trazabilidad marca cada test de caracterización y qué se rompe para comprobarlo. `/plan --fix`. |
| A12 | Criterio no verificable | MEDIA | T024, T025, T027, T028 | Varios «hecho cuando» piden que pase «la parte» de un test (`test_lo_nuevo_solo_usa_el_sistema`, `test_los_errores_van_asociados_a_su_campo`, `test_el_panel_oculta…`); un test pasa entero o no pasa. | Tests separados por pantalla o rol, de modo que cada tarea cierre con tests completos en verde. `/tasks --fix`. |
| A13 | Inconsistencia plan ↔ tareas | MEDIA | `plan.md` → Pantallas (panel del paciente); T028, T029 | El plan dice que fuera de plazo se ve el aviso; T029 solo oculta el enlace en el listado. | El listado muestra un aviso corto en lugar de los botones: «Faltan menos de 24 h: llama a la clínica para cambiarla.» (decisión del usuario, 2026-10-08). Test en `test_el_panel_oculta…` sobre el listado. `/plan --fix`, `/tasks --fix`. |
| A14 | Cobertura CA6 (paciente) | MEDIA | T013 | `test_el_paciente_cancela_con_motivo_desde_la_pantalla` solo prueba el PATCH, no la pantalla. | La pantalla del paciente tiene `#no-reagendar` con un formulario a `cliente.citas.cancelar` y campo `motivo`, y fuera de plazo no lo tiene. Test con GET. `/tasks --fix`. |
| A15 | Cobertura CA4 | MEDIA | T011 | Faltan los bordes de 025 CA3 (empezar justo al inicio de la franja o terminar justo al final, aceptados) y de 025 CA7 (la cita de otro dentista no cuenta). | Esos casos en `test_aplica_la_regla_de_agenda_al_nuevo_hueco`. `/tasks --fix`. |
| A16 | Ambigüedad (anchos) | MEDIA | `plan.md` P9, Estrategia y Trazabilidad CA6 | El plan dice 375/1280 y `.ai/project.yaml → design.widths` dice [390, 1440]. A 390 px el layout ya se desborda (DS8), y no está definido qué se comprueba ahí. | Plan con 390/1440 y criterio explícito a 390 px: lo nuevo no añade desbordamiento propio. Verificación manual en T025 y T029. `/plan --fix`, `/tasks --fix`. |
| A17 | Constitución P6 | MEDIA | T040; `plan.md` P6 | No se ejecuta `trivy fs` (P6, `en-ci`) ni se tiene en cuenta que EX2 vence el 2026-10-31. | T040 ejecuta trivy y anota el resultado frente a EX2; si la liberación cae después del 31-10, lo decide el usuario en `/release`. `/plan --fix`, `/tasks --fix`. |
| A18 | Alcance sin registrar | MEDIA | `plan.md` → fila de `CambioDeEstadoNoPermitido` y Mensajes (`vuelta`) | El texto nuevo de `vuelta()` no está en la spec, y se lo enseña al dentista, que no puede reprogramar. | Texto según el rol: admin y asistente, «Una cita no puede volver a «Agendada». Si hay que moverla, usa «Reprogramar».»; dentista, «Una cita no puede volver a «Agendada». Pide a tu asistente o a la administración que la reprogramen.» (decisión del usuario, 2026-10-08), registrado en Decisiones de la spec. Test en `EstadosCitaTransicionesTest` por rol. `/specify --edit`, `/plan --fix`, `/tasks --fix`. |
| A19 | Inconsistencia | BAJA | `plan.md` → Estrategia de pruebas | `EscenarioEstadosCita` no crea horarios; T010 lo resuelve con `conHorarios()`. | Corregir el texto del plan. `/plan --fix`. |
| A20 | Redundancia | BAJA | T015 `test_el_dentista_no_ve_la_opcion`; T024 | El dentista no llega a la pantalla (sin ruta y con 403), así que ocultarle la sección no hace nada. | El test comprueba que el detalle del dentista no enlaza a reprogramar. `/tasks --fix`. |
| A21 | Concurrencia | BAJA | `plan.md` D3 | No hay ciclo de bloqueos, pero si se agota la espera por la cita bloqueada por un cambio de estado, el mensaje dice «horario no disponible», que no es la causa. | Mensaje definido para ese caso, con test en `ReprogramarConcurrenteTest`. `/plan --fix`, `/tasks --fix`. |
| A22 | Terminología | BAJA | T025 | Los errores del hueco van a `fecha` en reprogramar y a `hora` en el agendado de la 025. | Un solo campo, el mismo que en la 025. `/plan --fix`, `/tasks --fix`. |
| A23 | Realizabilidad | BAJA | T001 | «Pantallas principales» de `system.md` lleva capturas reales y la pantalla no existe en T001. | Definir cuándo se añaden las capturas (T025 o T029). `/tasks --fix`. |
| A24 | Specs afectadas | BAJA | `plan.md` → Impacto | 004 (Fuera de alcance), 026 y 027 siguen enviando «reprogramar» o «reabrir» a otra spec. | Impacto lo anota para el Historial de esas specs en `/release`. `/plan --fix`. |
| A25 | Riesgos | BAJA | `plan.md`; T091 | RS2.a (pendiente → 015) no anota las rutas nuevas del paciente con `role:cliente`. | T091 actualiza la nota de RS2.a sin cambiar su estado. `/tasks --fix`. |
| A26 | Ambigüedad | BAJA | `spec.md` CA2, CA4 | No está dicho si el paciente puede mover su cita a un hueco que empiece en menos de 24 h. | Sí, como al agendar: el plazo se aplica a la cita actual y el hueco nuevo solo sigue la regla de agenda (decisión del usuario, 2026-10-08), registrado en Decisiones y con test en `ReprogramarCitaPacienteTest`. `/specify --edit`, `/tasks --fix`. |
| A27 | P4 | BAJA | T040 | semgrep no incluye `routes/web.php`. | Incluirlo. `/tasks --fix`. |
| A28 | Diseño | BAJA | `plan.md` → Pantallas | No dice si «No reagendar» pide confirmación con `confirm()` (deuda DS7). | Decidirlo en el plan, sin `confirm()` nuevo si es deuda. `/plan --fix`. |
| A29 | Código muerto | BAJA | T021, T028 | `CambioDeEstadoNoPermitido::yaEmpezo()` queda sin uso. | Retirarlo o anotarlo en la tarea. `/tasks --fix`. |
| A30 | Solapes | BAJA | 014, 020, 031 | La 014 toca `CitasController` y `AppServiceProvider` (ya declarado, T041); la 020 es el destino correcto de los 403; la 031 solo coincide en documentos. | Conflictos solo de texto, a resolver al fusionar `main` antes de `/release` (Rollout ya lo prevé). Sin cambio. |

## Decisiones pendientes del usuario
Ninguna. Todas las tomó el usuario el 2026-10-08:
- **A1:** cumplir AA ahora en la pantalla nueva.
- **A2:** migrar solo los bloques que se tocan.
- **A6:** en una cita que no se puede mover, también si es una agendada ya empezada, solo el aviso y el enlace al detalle.
- **A13:** aviso corto en el listado.
- **A18:** texto de `vuelta()` según el rol.
- **A26:** el hueco nuevo del paciente no exige 24 h.

## Aceptados
Ninguno todavía.

## Correcciones
1. `/specify 028 --edit`: A3, A18, A26.
2. `/plan 028 --fix`: A1, A2, A3, A4, A5, A6, A7, A8, A9, A10, A11, A13, A16, A17, A18, A19, A21, A22, A24, A28.
3. `/tasks 028 --fix`: A1, A2, A4, A5, A6, A7, A8, A9, A10, A12, A13, A14, A15, A16, A17, A18, A20, A21, A22, A23, A25, A26, A27, A29.
4. `/analyze 028`, que será delta.
