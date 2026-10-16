# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

## [0.8.0] - 2026-10-16

### Added
- Versión para el móvil de las pantallas del personal: los paneles de administración, dentista y
  asistente, la lista y el detalle de citas y las listas de pacientes y servicios se ven sin
  desplazamiento lateral desde 320 px de ancho, también con el móvil en horizontal (spec 034).
- En el móvil, las tablas de citas, pacientes y servicios (y las de los paneles) se leen como
  tarjetas, una por fila, con la etiqueta de cada dato y las acciones con su nombre visible; las
  columnas secundarias solo se ven en el escritorio (spec 034).
- Filtro de citas plegable en el móvil: el buscador, los atajos «Esta semana» y «Hoy», un botón
  «Buscar» y «Filtros», que despliega las fechas, el dentista y el estado e indica cuántos filtros
  hay aplicados. Sin JavaScript, el filtro queda desplegado (spec 034).

### Changed
- «Mis citas» y el panel del paciente muestran sus citas en tarjetas en el móvil, con «Ver»,
  «Reprogramar» y «Cancelar» siempre visibles (spec 034).
- El detalle de una cita del personal se lee en una columna en el móvil, en este orden: datos,
  cambiar estado, información, acciones, control interno e historial (spec 034).
- El botón del menú del móvil muestra solo su icono y el menú abierto muestra el logo de la clínica
  (spec 034).
- Los textos largos pasan a la línea siguiente sin partir letra a letra las palabras que caben
  (spec 034).
- En el escritorio no cambia nada visible, salvo que los campos del filtro de citas tienen un borde
  más marcado y lo que la spec toca muestra el anillo de foco de la marca al recorrerlo con el
  teclado (spec 034).

## [0.7.0] - 2026-10-12

### Added
- Versión para el móvil: por debajo de 900 px de ancho, la barra lateral se convierte en un menú
  desplegable con el botón «Menú», para todos los roles. Se abre y se cierra con el dedo, el teclado
  (Escape) o tocando fuera, marca la sección actual y funciona con lector de pantalla (spec 033).
- Las siete pantallas del paciente (panel, mis citas próximas y pasadas, detalle, reprogramar,
  agendar y perfil) se ven sin desplazamiento lateral desde 320 px de ancho, también con el móvil en
  horizontal; los nombres, correos y servicios largos pasan a la línea siguiente (spec 033).
- Botones, enlaces, campos y elementos del menú con un área de toque de al menos 44 × 44 px en el
  móvil (spec 033).

### Changed
- En el escritorio no cambia nada visible, salvo que las pestañas de «Mis citas» y las migas de pan
  muestran el anillo de foco de la marca al recorrerlas con el teclado (spec 033).
- Las pantallas del personal ganan el menú desplegable en el móvil; su contenido todavía no está
  adaptado y sus tablas se desplazan dentro de su tarjeta (pendiente de una spec propia).

## [0.6.0] - 2026-10-11

### Added
- La administración y los asistentes (con las citas de sus dentistas) pueden reprogramar una cita
  agendada o confirmada que aún no ha empezado: solo cambian la fecha y la hora, con la misma regla de
  agenda que al agendar y sin que la propia cita cuente como conflicto (spec 028).
- El paciente puede reprogramar sus citas desde su panel, su lista y el detalle si faltan 24 horas o
  más (spec 028).
- En la pantalla de reprogramar, la opción «No reagendar» cancela la cita definitivamente, con un
  motivo opcional y una casilla para confirmar que no se puede deshacer (spec 028).
- El historial de la cita muestra cada reprogramación con la fecha y hora anteriores y las nuevas,
  quién la hizo y el motivo (spec 028).

### Changed
- El paciente solo puede cancelar una cita con 24 horas o más de antelación; antes podía hasta el
  inicio. Con menos tiempo se le pide que llame a la clínica (spec 028).
- Una cita confirmada que se reprograma vuelve a agendada (spec 028).
- Si una cita no se puede mover porque ya empezó, está cerrada, su dentista ya no está activo o no
  tiene duración, la pantalla lo explica y no ofrece el formulario (spec 028).

### Security
- Herramientas de desarrollo actualizadas sin avisos críticos ni altos de `npm audit` (`shell-quote`
  1.11.0 y `source-map-js` 1.2.2). No llegan a producción, que no instala dependencias de npm. Se
  adelanta de la spec 031, T004.

### Changed — flujo AI-DD actualizado a 1.11.5
- Solo cambia la versión del flujo: las novedades de 1.11.3–1.11.5 (opciones de diseño con idea
  propia, rehacer opciones y afinar un eje) aplican a proyectos sin sistema de diseño aprobado; el
  de Dentissa es extraído y está aprobado (1.2.0), así que no se tocan. Validador y plantillas sin
  cambios.

## [0.5.0] - 2026-10-04

### Added
- Entorno de producción en un servidor propio, publicado en internet solo a través de un túnel de
  Cloudflare con conexión segura: ningún puerto abierto en el router ni en el servidor, y la base de
  datos y su administrador inaccesibles desde fuera (spec 030).
- Procedimiento documentado y scripts para preparar el servidor, desplegar una versión, hacer y
  restaurar copias de la base y volver a la versión anterior, todos ensayados en el servidor (spec 030).
- Comando para cambiar las contraseñas de las cuentas de prueba antes de abrir producción (spec 030).

### Security
- Límite de 5 intentos por minuto en el inicio de sesión (por cuenta y dirección), en el alta de
  cuentas y en la vuelta del acceso con Google, con un mensaje que no revela si la cuenta existe
  (spec 030).
- Las direcciones y redirecciones de la aplicación usan siempre conexión segura detrás del túnel, y
  quien entra por `http://` se redirige a `https://` (spec 030).
- En los equipos de desarrollo, la base de datos y su administrador ya solo aceptan conexiones del
  propio equipo (spec 030).
- Producción muestra errores genéricos sin detalles internos y comprueba su configuración en cada
  despliegue (spec 030).

### Changed — flujo AI-DD actualizado a 1.11.2
- Validador `.ai/bin/aidd.py` 1.11.2: cada plan guarda `constitution_version` y se valida contra
  esa versión; avisos de specs cerradas como "heredados"; comando `templates`.
- Plantilla del plan con `constitution_version`; huellas de las plantillas en `project.yaml`
  (`template_hashes`) y clave `masvs_level` (no aplica).
- Planes abiertos con su versión de la constitución: 014 y 020 (1.0.0) y 030 (1.2.1).
- Sistema de diseño re-extraído (1.2.0): capturas reales de las 60 vistas a 390 y 1440 px con datos
  de prueba, medidas de estilo, inventario de vistas y deuda conciliada (DS12–DS15 nuevas); la versión
  anterior queda en `docs/design/history/`.

## [0.4.1] - 2026-10-03

### Security
- Se actualizan las bibliotecas del servidor que tenían avisos de seguridad conocidos (el
  framework, la gestión de archivos y la biblioteca criptográfica que usa el acceso con Google),
  sin cambios en lo que hace la aplicación. La verificación de dependencias ya no reporta ningún
  aviso de ninguna severidad (spec 029).

## [0.4.0] - 2026-10-03

### Added
- Al guardar el horario de un dentista, su ficha muestra las citas futuras, agendadas o
  confirmadas, que quedaron fuera del nuevo horario, con paciente, fecha, hora, estado y un enlace
  a cada una; el mensaje dice cuántas son. Las citas no se modifican: se reprograman o cancelan
  desde su detalle (spec 027).

### Changed
- El horario de un dentista solo se guarda si es coherente: cada día marcado necesita hora de
  inicio y de fin, y la de fin debe ser posterior a la de inicio el mismo día (no se admiten
  franjas que crucen la medianoche). Ya no se rellenan 09:00–18:00 sin que se vea. Los errores
  aparecen junto a su día y en un resumen, y el formulario conserva lo escrito (spec 027).
- Solo los dentistas tienen horario: la ficha de un asistente o de un administrador ya no ofrece
  el formulario (spec 027).
- Guardar un horario sin días avisa de que no se podrán agendar citas con ese dentista hasta que
  tenga horario (spec 027).
- La sección de horario de la ficha sigue el sistema de diseño: campos de hora con borde visible,
  anillo de foco al navegar con el teclado y textos con mejor contraste (spec 027).

### Security
- Un horario manipulado (días que no existen, horas mal escritas o listas) se rechaza con un error
  de validación, nunca con un error del servidor, y solo la administración puede definir horarios
  (spec 027).

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
