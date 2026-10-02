---
result: fail
round: 1
mode: full
constitution_version: 1.0.0
date: 2026-10-02
spec_sha: ccf7cf5a6965
plan_sha: 8ba67096a9c6
tasks_sha: 62a66f85a751
---

# Análisis · 026 Estados de la cita y quién puede cambiarlos

## Resumen
Resultado: **fail**, por un hallazgo crítico (A1): el plan modifica tres pantallas GET cuya autorización no cumple P3 y no
registra la excepción. Además hay dos altos que el usuario decide corregir (A2, A3).

Conteo: 1 CRÍTICA, 2 ALTAS, 9 MEDIAS, 8 BAJAS. Ocho medios y bajos se aceptan como notas de tarea; el resto se corrige
con `/specify --edit 026`, `/plan 026 --fix` y `/tasks 026 --fix`.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 11 / 11 (la prueba de TM11 no es concluyente: A6) |
| Principios de la constitución evaluados | 8 / 8 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución | CRÍTICA | plan.md Cambios por módulo (`CitasController::show`, `ClienteCitasController::index/show`) y Constitution Check P3; tasks.md T024, T026 y Constitution Check P3 | `GET /{rol}/citas/{id}`, `GET /cliente/citas` y `GET /cliente/citas/{id}` se modifican (pasan `$disponibles` o `puede_cancelar`). El detalle del personal sigue con `autorizadoParaCita` inline, sin Gate. Las dos pantallas del paciente están en `/cliente`, que solo exige `auth`. P3 figura como ✅ y no hay excepción registrada. | Decisión del usuario (2026-10-02): excepción P3 para esos tres GET (→ 014 el detalle del personal, → 015 las del paciente), registrada con el formato `❌ aceptado` en plan y tareas y anotada en el roadmap (T041). → `/plan --fix`, `/tasks --fix` |
| A2 | Inconsistencia | ALTA | plan.md `CambiarEstadoCita` y D6; spec CA2, CA3, CA11 | El Gate deniega con 403 cualquier destino fuera de la matriz, `agendada` incluido, antes de que actúe la regla de estado. Reabrir una cita (→ agendada) o pedir confirmada → agendada daría 403, no el mensaje de CA2/CA3/CA11. Un rol sin esa transición sobre una cita cerrada también recibe 403. | Decisión del usuario (2026-10-02), "rol primero": el Gate decide alcance y matriz por rol (403 con el mensaje del rol, aunque la cita esté cerrada); `agendada` pasa el Gate y el servicio la rechaza: `cerrada` si la cita está cerrada, y si no "Una cita no puede volver a «Agendada»". El plan deja escrito que CA2 aplica a las transiciones que el rol puede pedir. Debe cumplirse y probarse: admin con cancelada → agendada recibe el mensaje de CA2; admin con confirmada → agendada recibe el mensaje de vuelta atrás; dentista con una completada → cancelada recibe 403. → `/plan --fix`, `/tasks --fix` (T011) |
| A3 | Inconsistencia | ALTA | tasks.md T024 ("pasan todo `EstadosCitaTransicionesTest`"); plan `test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica` (CA7, CA8) | Si el test de zona cubre la cancelación del paciente (CA7), solo puede pasar después de T026, que depende de T024; T024 no se podría cerrar. | Cada "hecho cuando" debe poder cumplirse con sus dependencias: el test de zona de `EstadosCitaTransicionesTest` usa solo flujos del personal (CA8), y el caso del paciente se prueba en `EstadosCitaRolesTest` y cierra con T026. → `/plan --fix`, `/tasks --fix` |
| A4 | Inconsistencia | MEDIA | tasks.md T024 (`show` sin `$estados`) y T025 | Entre T024 y T025, `citas/show.blade.php` sigue usando `$estados` y el detalle da 500; cualquier test de T024 que cargue el detalle fallaría por otra causa. | El detalle no se rompe entre tareas: el cambio de `show` en el controlador va con el de la vista (T025), y los tests exigidos en T024 no cargan el detalle. → `/tasks --fix` |
| A5 | Inconsistencia | MEDIA | plan.md Contratos y `updateEstado` (cargar → validar → Gate); spec "el alcance no cambia"; TM2 | Hoy el orden es 404 → 403 por alcance → validación. Con el plan, el personal fuera de alcance con un `estado` inválido recibiría errores de validación en lugar de 403. | El personal fuera de alcance recibe 403 con cualquier entrada (test: fuera de alcance con estado inválido → 403, sin cambios). → `/plan --fix`, `/tasks --fix` (T012) |
| A6 | Seguridad | MEDIA | plan TM11; tasks T013 | La vista ya escapa el motivo; `test_el_motivo_se_muestra_escapado` fallará hoy solo por la falta de `id="historial"`, no por XSS. | Aceptado → nota de T013 |
| A7 | Ambigüedad | MEDIA | plan Estrategia ("aserciones sobre la sección por `id`"); T015, T025, T027 | El botón "Cancelar cita" del admin está en la barra superior, y los del paciente no tienen `id`. Dos tests de T015 no pueden cumplir la regla. | Aceptado → nota de T015 |
| A8 | Ambigüedad | MEDIA | plan "Qué ve cada rol"; T015; `ClienteCitasController::index` | El listado por defecto ("próximas", comparado en UTC) nunca muestra una cita ya empezada; el test del listado pasaría hoy sin `?filtro=pasadas`. | Aceptado → nota de T015 |
| A9 | Ambigüedad | MEDIA | plan Concurrencia; T010, T014 | La cita que cambia o bloquea la segunda conexión (con su paciente, dentista y usuarios) debe estar confirmada por esa conexión antes de cualquier lectura de la app; los datos de la fixture no son visibles para ella, y REPEATABLE READ podría ocultar la cita. | Aceptado → nota de T014 |
| A10 | Constitución | MEDIA | plan P3; T012 | `DELETE /admin/citas/{id}` es un endpoint modificado y solo tiene tests del admin; falta el denegado por rol. | Dentista, asistente y cliente reciben 403 en "eliminar" y la cita no cambia (test en T012, comprobado rompiendo el middleware). → `/tasks --fix` |
| A11 | Duplicación y conflicto | MEDIA | plan `CitasController` ("la 014 los migra"); plan 014 | El plan de la 014 no toca `autorizadoParaCita` ni `aplicarAlcanceRol`; la regla de alcance de citas quedaría duplicada sin una spec que la unifique. | Una sola regla de alcance de citas: `autorizadoParaCita` y `aplicarAlcanceRol` delegan en `AlcanceClinico::dentistasDelAlcance()` (los tests de alcance de la 025 y de T012 siguen en verde). → `/plan --fix`, `/tasks --fix` (T024) |
| A12 | Cobertura | MEDIA | spec Seguridad y Cobertura de riesgos; plan `routes/web.php`; docs/security.md RS2.a; T091 | `role:cliente` en la cancelación atiende en parte RS2.a y 004 CA7, pero la spec no cita RS2 ni declara el cambio de 004 CA7, y T091 no actualiza RS2. | Decisión del usuario (2026-10-02): mantener `role:cliente` y citar RS2 en la spec (RS2.a fuera, parcialmente atendida: solo la cancelación; el resto del grupo y RS2.b → 015), declarar el cambio parcial de 004 CA7 y añadir la nota en RS2 (T091). → `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A13 | Inconsistencia | BAJA | plan Estrategia ("se mide en T092"), D3 y D10 ("(T090)"); tasks T040, T041 | Las referencias no coinciden: la medida está en T040 y la nota del roadmap en T041. | Alinear las referencias. → `/plan --fix` |
| A14 | Inconsistencia | BAJA | spec CA2; plan D7 | CA2 pide el mensaje de cita cerrada para cualquiera, pero el paciente recibe "Esta cita ya no se puede cancelar…". | Declarar en la spec el mensaje del paciente. → `/specify --edit` |
| A15 | Ambigüedad | BAJA | plan "Qué ve cada rol" frente a Contratos `cerrada`; `citas/show.blade.php` ("Sistema (trigger)"); D3 "clase invocable" | Hay dos textos para la cita cerrada, la etiqueta del autor nulo no coincide, y "clase invocable" no encaja con `autorizar()`. | Aceptado → nota de T025 (y el término "clase invocable" se corrige en el `/plan --fix`) |
| A16 | Ambigüedad | BAJA | spec CA9; plan D4 y Datos | La fecha del historial se guarda y se muestra en UTC, sin convertir, y CA9 no fija la zona. | Aceptado → nota de T013, T025 |
| A17 | Ambigüedad | BAJA | plan TM5; `EstadosCita` | El test con conexión simulada necesita inyectarla, pero el plan no fija el constructor. | `EstadosCita::__construct(ConnectionInterface $db)`, como `ReservaCitas`. → `/plan --fix` |
| A18 | Cobertura | BAJA | plan "Base de tests"; `database/testing/bootstrap-test-db.sh` | El script recarga el volcado (con el trigger) y no ejecuta `migrate`; tras usarlo, `HistorialCitaTest` vería dos filas. | Aceptado → nota de T091 |
| A19 | Inconsistencia | BAJA | spec CA6 ("del listado") | "Eliminar cita" está en el detalle, no en el listado. | Corregir la redacción de CA6. → `/specify --edit` |
| A20 | Cobertura | BAJA | spec RNF de mensajes; T012 | Ningún test comprueba el texto de los 403 de CA4 y CA5. | Aceptado → nota de T012 |

## Decisiones pendientes del usuario
Resueltas el 2026-10-02:
- **A1:** excepción P3 para los tres GET (→ 014, → 015).
- **A2:** "rol primero".
- **A12:** mantener `role:cliente` y editar la spec.

## Aceptados
Aceptados por el usuario el 2026-10-02:
- **A6** → nota de T013: comprobar `test_el_motivo_se_muestra_escapado` rompiendo un momento la salida con `{!! !!}` y restaurar.
- **A7** → nota de T015: los botones "Cancelar cita" (admin, barra superior) y "Cancelar" (paciente) se comprueban por el formulario cuyo `action` es la ruta de destroy o de cancelar, no por sección.
- **A8** → nota de T015: la aserción del listado del paciente usa `?filtro=pasadas` (además del detalle); una cita listada como pasada que aún no empieza en la hora local sigue mostrando "Cancelar" (deuda D4).
- **A9** → nota de T014: la cita, su paciente, su dentista y sus usuarios se crean y confirman con `segunda_conexion` antes de cualquier lectura de la app, se anotan y se borran en `tearDown`; esos tests no usan `cita()` de la fixture.
- **A15** → nota de T025: el aviso de cita cerrada reutiliza el mensaje `cerrada`, y el autor nulo se muestra como "Sistema".
- **A16** → nota de T013, T025: la fecha del historial se muestra como hoy (UTC, sin convertir; deuda D4) y el test la comprueba en ese formato.
- **A18** → nota de T091: documentar en `docs/deployment.md` que, tras `bootstrap-test-db.sh`, hay que ejecutar `migrate` sobre `dentissa_test`.
- **A20** → nota de T012: `test_el_dentista_no_puede_cancelar_su_cita` y `test_el_asistente_no_puede_iniciar_ni_completar` comprueban el texto del mensaje del rol.
