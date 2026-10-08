---
id: 028
slug: reprogramar-citas
status: approved
created: 2026-10-07
extends: [003, 004, 026]
---

# 028 · Reprogramar citas

## Problema
Hoy una cita no se puede mover a otra fecha u hora: hay que cancelarla y agendar una nueva. Se
pierde la relación entre las dos y el historial no dice que fue un cambio de hora. Además, la
cita nueva puede quedarse sin el hueco si otra persona lo toma entre medias. Esto afecta a la
administración y a los asistentes, que tienen que mover citas cuando cambia un horario (027 CA7)
o el paciente lo pide, y al paciente, que solo puede cancelar.

También cambia cuándo puede el paciente cancelar: hoy puede hacerlo hasta que la cita empieza, y
eso deja huecos vacíos a última hora.

## Historias de usuario
- Como administración, quiero mover cualquier cita a otra fecha y hora libres, para resolver cambios de horario y peticiones de los pacientes sin perder la cita.
- Como asistente, quiero mover las citas de los dentistas que tengo asignados, para atender los cambios que me piden.
- Como paciente, quiero cambiar la fecha y hora de mi cita desde mi panel con al menos un día de antelación, para no tener que cancelar y volver a pedirla.
- Como quien reprograma, quiero poder decidir que la cita no se reagenda y cancelarla definitivamente, para no dejar citas que ya no se van a atender.
- Como personal de la clínica, quiero ver en el historial de la cita quién la movió y desde qué fecha y hora, para entender qué pasó con ella.

## Criterios de aceptación
- [ ] CA1 · Dada una cita agendada o confirmada que aún no ha empezado, cuando la administración (cualquier cita) o un asistente (citas de los dentistas que tiene asignados) elige otra fecha y hora válidas y guarda, entonces la cita queda en la nueva fecha y hora con el mismo paciente, dentista, servicio y duración; aparece en la agenda en su nuevo hueco y el anterior queda libre.
- [ ] CA2 · Dado un paciente con una cita propia agendada o confirmada que empieza dentro de 24 horas o más, cuando elige otra fecha y hora válidas desde su panel, entonces la cita se mueve como en CA1. El plazo se cuenta sobre la fecha y hora actuales de la cita: la nueva fecha y hora solo tiene que cumplir CA4, aunque empiece dentro de menos de 24 horas. Si faltan menos de 24 horas para el inicio, su panel no ofrece reprogramar, y si lo intenta se rechaza con un mensaje que le pide contactar con la clínica; la cita no cambia.
- [ ] CA3 · Dado un paciente con una cita propia agendada o confirmada, cuando la cancela, entonces se acepta solo si faltan 24 horas o más para el inicio; si faltan menos, se rechaza con el mismo mensaje de CA2 y la cita no cambia. Reemplaza en ese punto a 026 CA7, que admitía cancelar hasta el inicio. Lo que puede cancelar el personal no cambia.
- [ ] CA4 · Dada la nueva fecha y hora, cuando se guarda, entonces se aplica la misma regla de agenda que al agendar (025 CA2–CA7: día con horario, encaje en la franja, solape con otras citas no canceladas ni no asistidas del mismo dentista); la propia cita no cuenta como conflicto con su hora anterior. Una fecha y hora ya pasadas en la hora de la clínica, o iguales a las actuales, se rechazan con un mensaje que lo dice, y la cita no cambia.
- [ ] CA5 · Dada una cita confirmada, cuando se reprograma, entonces vuelve a agendada; una agendada sigue agendada. Reemplaza en ese punto a 026 CA1, solo al reprogramar: desde el cambio de estado una cita sigue sin poder volver a agendada, y el mensaje de rechazo dice cómo moverla (a la administración y al asistente, que usen reprogramar; al dentista, que pida a su asistente o a la administración que la reprogramen). Una cita en curso, completada, cancelada o no asistida no se puede reprogramar: se rechaza con el mensaje de 026 CA2 (cita cerrada) o uno que dice que ya empezó, y no cambia. Las citas canceladas no se reabren (026 CA2).
- [ ] CA6 · Dada la pantalla de reprogramar, cuando quien reprograma decide no reagendar y elige cancelar la cita definitivamente, con un motivo opcional, entonces la cita pasa a cancelada con las mismas reglas que la cancelación de 026 (administración y asistentes según su alcance; el paciente, con la antelación de CA3); el dentista no tiene esta opción.
- [ ] CA7 · Dada una reprogramación aceptada, cuando se guarda, entonces queda exactamente una entrada en el historial de la cita con quién la hizo, la fecha y hora anteriores y las nuevas, el cambio de estado si lo hubo (confirmada → agendada), el motivo si se dio y la fecha del cambio; el historial de la cita lo muestra así. Los rechazos no dejan historial.
- [ ] CA8 (abuso) · Dado un dentista, un asistente con una cita de un dentista que no tiene asignado, o un paciente con una cita que no es suya, cuando intenta reprogramarla o usar la cancelación definitiva de CA6, entonces se rechaza por permisos (el paciente no la encuentra, como en 004 CA6) y la cita no cambia.
- [ ] CA9 (abuso) · Dado un formulario de reprogramar manipulado (otro dentista, servicio, duración, paciente o estado añadidos a mano, una fecha u hora mal formadas, o una petición del paciente fuera del plazo de 24 horas), cuando se envía, entonces solo pueden cambiar la fecha y la hora y solo si son válidas: lo demás no cambia, lo mal formado se rechaza con un error de validación del campo y nunca hay un error del servidor.
- [ ] CA10 (abuso) · Dadas dos peticiones simultáneas que quieren el mismo hueco del mismo dentista (dos reprogramaciones, o una reprogramación y un agendado nuevo, de cualquier rol), cuando se procesan, entonces solo una se queda el hueco y la otra recibe el mensaje de hueco no disponible de 025 CA4, nunca un error del servidor; la cita que no se movió conserva su fecha y hora.

## Fuera de alcance
- Cambiar el dentista, el servicio o la duración al reprogramar: para eso se cancela y se agenda otra cita (decisión del usuario, 2026-10-07).
- Reabrir una cita cancelada, completada o no asistida (decisión del usuario, 2026-10-07: se respeta 026 CA2).
- Que el dentista reprograme o cancele sus citas.
- Avisar al paciente o al dentista del cambio (correo, mensajes).
- Reprogramar varias citas a la vez, por ejemplo todas las que quedaron fuera de un horario nuevo (027 CA7): se mueven una a una desde el detalle de cada cita.
- Antelación mínima para que el paciente agende una cita nueva: sigue como hoy (004 CA3).
- Eventos de auditoría formales de la reprogramación → spec 022 (objetivo 3).

## Seguridad y privacidad
- **Datos sensibles:** fecha, hora y estado de la cita y el motivo del cambio, asociados a un paciente (datos de salud según `docs/security.md`). El historial lo ven solo el personal con alcance sobre la cita y la administración, como en la 026.
- **Quién puede hacer qué:**
  - administración → reprogramar o cancelar definitivamente cualquier cita agendada o confirmada que no ha empezado;
  - asistente → lo mismo con las citas de sus dentistas asignados;
  - paciente → lo mismo con sus citas, solo con 24 horas o más de antelación;
  - dentista → ni reprograma ni cancela.
- **Casos de abuso:**
  - Como dentista, asistente fuera de alcance o paciente con la cita de otro, intento moverla o cancelarla → se rechaza por permisos (CA8).
  - Como usuario, manipulo el formulario para cambiar el dentista, el servicio, la duración o el estado, o mando fechas mal formadas → solo cambian fecha y hora válidas, sin error del servidor (CA9).
  - Como paciente, manipulo la petición para mover o cancelar mi cita a menos de 24 horas → se rechaza (CA2, CA3, CA9).
  - Como dos personas que quieren el mismo hueco a la vez → solo una lo obtiene, sin error del servidor (CA10).

## Auditoría
- Cada reprogramación aceptada deja una entrada de historial con quién, sobre qué cita, la fecha y hora anteriores y las nuevas, el cambio de estado, el motivo y la fecha (CA7). La cancelación definitiva deja la entrada de 026 CA9.
- Los rechazos no dejan historial.
- El evento de auditoría formal (almacén solo-anexado, con resultado e identificador de petición) queda para la spec 022, como en la 026.

## Diseño
Quien reprograma tiene que ver de un vistazo la cita que mueve (paciente, dentista, servicio,
fecha y hora actuales) y elegir entre moverla o cancelarla, sin dudas sobre qué va a pasar.
- La opción de cancelar definitivamente está en la misma pantalla, separada de la de guardar la
  nueva fecha y claramente marcada como irreversible.
- El paciente ve en su panel si todavía puede reprogramar o cancelar; si no, el motivo (menos de
  24 horas) y que debe contactar con la clínica.
- Los errores de fecha u hora van junto a su campo.

Pantallas que toca:
- el detalle de la cita del personal (acción de reprogramar) y una pantalla de reprogramar;
- el detalle o la lista de citas del panel del paciente (reprogramar y cancelar con su plazo);
- el historial de la cita (entrada de reprogramación).

Sistema: [docs/design/system.md](../../design/system.md). Lo nuevo o modificado sigue el principio
P9: solo tokens del sistema y WCAG 2.1 AA, con errores asociados a su campo y anunciados, y foco
visible.

## Requisitos no funcionales
- Los mensajes de rechazo están en español y dicen qué corregir o qué hacer (por ejemplo, contactar con la clínica).
- Las partes nuevas o modificadas de las pantallas cumplen WCAG 2.1 AA (constitución P9).
- Ninguna entrada, válida o manipulada, produce un error del servidor.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-02 | brecha | Horarios y reprogramar citas se liberan por separado | Dividir: 027 horarios válidos, 028 reprogramar citas | usuario (spec 027, Decisiones) |
| 2026-10-07 | brecha | Quién puede reprogramar | Administración (todas), asistente (sus dentistas) y paciente (las suyas, con antelación); el dentista no | usuario |
| 2026-10-07 | contradicción | La 026 (liberada) deja al paciente cancelar hasta que la cita empieza; el usuario quiere que cancelar y reprogramar exijan antelación | El paciente cancela y reprograma solo con 24 horas o más de antelación; reemplaza 026 CA7 en ese punto (CA3) | usuario |
| 2026-10-07 | brecha | Qué se puede cambiar al reprogramar | Solo fecha y hora; dentista, servicio y duración se mantienen | usuario |
| 2026-10-07 | brecha | Qué hacer si no se quiere reagendar | La pantalla de reprogramar ofrece cancelar definitivamente, con las reglas de cancelación de la 026 | usuario |
| 2026-10-07 | contradicción | La 027 dejó «reabrir una cancelada» para la 028, pero 026 CA2 dice que una cita cancelada está cerrada | Se respeta 026 CA2: no se reabren citas | usuario |
| 2026-10-07 | brecha | Estado de una cita confirmada tras reprogramarla | Vuelve a agendada | usuario |
| 2026-10-07 | implícita | Regla del nuevo hueco | La misma regla de agenda única que al agendar | `docs/specs/025-agenda-unica/spec.md` CA1–CA8 |
| 2026-10-07 | implícita | Alcance del asistente y del paciente sobre las citas | El de la 003 (asistente: sus dentistas) y la 004 (paciente: solo las suyas) | `docs/specs/003-agenda-citas-personal/spec.md`, `docs/specs/004-citas-cliente/spec.md` |
| 2026-10-07 | supuesto | Qué pasa con una fecha y hora iguales a las actuales o ya pasadas | Se rechazan con un mensaje | agente |
| 2026-10-07 | supuesto | Mensaje al paciente fuera de plazo | Le pide contactar con la clínica, sin datos de contacto nuevos | agente |
| 2026-10-07 | supuesto | Motivo de la reprogramación | Opcional, como en los cambios de estado de la 026 | agente |
| 2026-10-08 | contradicción | 026 CA1 solo admite sus transiciones y prohíbe volver a agendada; la 028 pasa una confirmada a agendada al reprogramarla | Se mantiene confirmada → agendada solo al reprogramar; reemplaza 026 CA1 en ese punto, y el cambio de estado sigue sin permitir volver a agendada (CA5) | usuario (2026-10-07); `/analyze A3` |
| 2026-10-08 | brecha | Qué mensaje ve cada rol al intentar volver a agendada desde el cambio de estado | Administración y asistente: que usen reprogramar; dentista: que pida a su asistente o a la administración que la reprogramen (CA5) | usuario; `/analyze A18` |
| 2026-10-08 | brecha | ¿Puede el paciente mover su cita a un hueco que empiece dentro de menos de 24 horas? | Sí, como al agendar: el plazo se aplica a la cita actual y el hueco nuevo solo sigue la regla de agenda (CA2) | usuario; `/analyze A26` |
| 2026-10-07 | supuesto | Cómo se cuentan las 24 horas | Desde el momento de la petición hasta la hora de inicio de la cita, en la zona horaria de la clínica | agente (zona horaria de la clínica, spec 026) |

## Notas para /plan
- La regla de agenda ya vive en un solo sitio (spec 025) y las transiciones de estado en la 026: la reprogramación debería reutilizarlas, excluyendo la propia cita del cálculo de solape.
- La lista de citas fuera de horario de la 027 enlaza al detalle de cada cita: desde ahí se reprograma.
- Hay tests de la 026 que asertan que el paciente cancela hasta el inicio: cambian con CA3.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-07 | Creación en borrador | `/specify 028` |
| 2026-10-07 | Aprobada | Aprobación del usuario |
| 2026-10-08 | CA2: el plazo se cuenta sobre la hora actual de la cita y el hueco nuevo no exige 24 h; CA4: «ya pasada» en la hora de la clínica; CA5: reemplaza 026 CA1 solo al reprogramar, con el mensaje de volver a agendada según el rol. Vuelve a `draft` | `/analyze A3, A4, A18, A26` |
| 2026-10-08 | Aprobada de nuevo | Aprobación del usuario |
