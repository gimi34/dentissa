# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

### Changed — flujo AI-DD actualizado a 1.11.0
- Validador `.ai/bin/aidd.py` y plantillas de spec, plan, tareas y review de la versión 1.11.0
  (tabla "Decisiones", columna "Cómo se verifica", línea "Conteo:").
- Herramientas de seguridad con estado (`en-ci`; DAST `no-aplica` sin staging) y semgrep con
  `--metrics=off` en `project.yaml` y en el CI.
- Sistema de diseño extraído del código en `docs/design/system.md` y `system.html`, aprobado,
  con la deuda de diseño DS1–DS11.
- Constitución 1.1.0: principio P9, "La interfaz usa solo el sistema de diseño", que exige WCAG
  2.1 AA en las pantallas nuevas o modificadas.
- Roadmap: objetivo 6 de accesibilidad WCAG 2.1 AA; el resto de la deuda de diseño queda en
  "Pendientes y deuda".

## [0.3.0] - 2026-10-02

### Added
- Cada cambio de estado de una cita queda en su historial con quién lo hizo, el estado anterior,
  el nuevo y el motivo, y el detalle de la cita lo muestra así (spec 026).
- El detalle de la cita explica por qué no se puede cambiar su estado cuando no hay ningún cambio
  posible: cita cerrada, cita que aún no empieza o cambio reservado a otro rol (spec 026).
- El paciente ve por qué ya no puede cancelar una cita que ya empezó (spec 026).

### Changed
- Una cita solo avanza por estados con sentido: agendada → confirmada → en curso → completada, o
  bien agendada o confirmada → cancelada o no asistió. Las citas completadas, canceladas o no
  asistidas ya no se pueden reabrir; para volver a atender al paciente se agenda una cita nueva
  (spec 026).
- Qué puede hacer cada rol: el dentista confirma, inicia, completa y marca no asistió en sus
  citas, pero no las cancela; el asistente confirma, cancela y marca no asistió en las citas de
  sus dentistas, pero no las inicia ni completa; la administración puede hacer cualquier cambio
  permitido (spec 026).
- "En curso" y "no asistió" solo se pueden marcar cuando la cita ya empezó, según la hora de la
  clínica; el paciente solo puede cancelar mientras su cita no haya empezado (spec 026).
- El formulario de cambio de estado solo ofrece los cambios posibles para quien lo usa y en ese
  momento, y "eliminar cita" del administrador es una cancelación con las mismas reglas (spec 026).

### Fixed
- Un cambio de estado con motivo ya no deja dos entradas en el historial, una de ellas sin autor
  (spec 026).
- Dos cambios de estado enviados a la vez sobre la misma cita ya no se aplican los dos; el segundo
  recibe un mensaje claro en lugar de un error del servidor (spec 026).
- Un motivo demasiado largo muestra un mensaje en español junto al campo y conserva lo escrito
  (spec 026).

### Security
- El cambio de estado ya no acepta notas internas: dentistas y asistentes no pueden
  sobrescribirlas (spec 026).
- Solo un paciente puede usar la cancelación de citas del paciente (spec 026).

## [0.2.0] - 2026-10-02

### Added
- Pruebas automáticas de la regla de agenda: horario del dentista, franja, citas seguidas,
  solapes, reservas simultáneas, validación de la entrada y permisos por rol (spec 025).

### Changed
- El personal y los pacientes agendan con la misma regla y reciben los mismos mensajes. Cuando
  un horario ya está ocupado, el personal ve ahora "Ese horario ya no está disponible. Por favor
  elige otro." (spec 025).
- Se pueden agendar citas seguidas sin hueco entre ellas, por ejemplo 10:00–10:30 y
  10:30–11:00 (spec 025).
- Un dentista con la cuenta desactivada ya no se puede elegir para una cita (spec 025).

### Fixed
- Dos personas que reservaban a la vez el mismo hueco de un dentista podían quedarse las dos con
  él; ahora solo una lo consigue (spec 025).
- Una hora o fecha mal escritas, un dentista no válido o un servicio sin duración ya no producen
  un error del servidor ni una cita encima de otra: se muestra un mensaje claro y el formulario
  conserva los datos (spec 025).

## [0.1.0] - 2026-09-29

### Added
- Prueba automática que impide volver a versiones vulnerables de las dependencias corregidas
  (spec 024).
- Flujo de desarrollo AI-DD inicializado con `/init`: `AGENTS.md`, constitución, arquitectura,
  despliegue, seguridad, observabilidad, roadmap, specs inferidas 001–012 y validador `.ai/bin/aidd.py`.
- Workflow de CI `.github/workflows/ai-dd.yml` (validador, tests, Pint y escáneres de seguridad).

### Changed
- AI-DLC en pausa: `.claude/CLAUDE.md` movido literalmente a `AIDLC.md`; `.claude/rules/aidlc.md`
  y los hooks/statusLine de `.claude/settings.json` movidos a `aidlc/paused/`
  (decisión del usuario, 2026-09-28).
- La intención AI-DLC `260920-monolito-modular` se migró como spec draft 013.
- Las comprobaciones de seguridad del CI auditan el lockfile de Composer y solo bloquean por
  vulnerabilidades críticas o altas, como ya hacía la de npm (spec 024).

### Fixed
- El análisis estático del CI dejaba de ejecutarse por un conjunto de reglas inexistente
  (spec 024).

### Security
- Dependencias del servidor y del frontend actualizadas a versiones sin vulnerabilidades
  críticas ni altas conocidas, entre ellas el cliente HTTP que usa el inicio de sesión con Google
  (spec 024).
- Dos avisos de severidad baja publicados el mismo día quedan registrados como excepción temporal,
  con vencimiento el 2026-10-31 (spec 024).
