---
id: 027
slug: horarios-validos
status: approved
created: 2026-10-02
extends: [005]
---

# 027 · Horario válido del dentista y aviso de citas afectadas

## Problema
La administración define el horario semanal de cada dentista en su ficha, y la agenda solo acepta
citas dentro de ese horario (spec 025). Hoy el horario se guarda sin comprobar nada:
- una franja puede terminar antes de empezar;
- un día marcado sin horas se rellena en silencio con 09:00–18:00;
- un día o una hora mal escritos pueden acabar en un error del servidor;
- también se puede guardar un horario para un asistente o un administrador.

Además, al cambiar el horario, las citas ya agendadas que quedan fuera no se ven en ningún sitio:
la administración no sabe que tiene que moverlas.

Esto afecta a la administración, que configura una agenda en la que no puede confiar, y a los
pacientes, cuya cita puede quedar fuera del horario del dentista sin que nadie lo advierta.

Atiende el riesgo RS12 **parcialmente**: RS12.b (validar el horario) queda dentro. RS12.a ya está
mitigada, y RS12.c se atiende solo en el horario; el resto va a la spec 013.

## Historias de usuario
- Como administración, quiero que el horario de un dentista solo se guarde si es coherente, para que la agenda acepte citas en las franjas que de verdad atiende.
- Como administración, quiero ver las citas que quedan fuera del horario al cambiarlo, para reprogramarlas o cancelarlas a tiempo.

## Criterios de aceptación
- [ ] CA1 · Dado un dentista, cuando la administración guarda su horario con días marcados y, en cada uno, una hora de inicio y una de fin posterior el mismo día, entonces queda guardado un horario por día marcado y la ficha lo muestra así; los días no marcados quedan sin horario.
- [ ] CA2 (abuso) · Dado un día marcado cuya hora de fin es igual o anterior a la de inicio (incluida una franja que cruzaría la medianoche, como 22:00–02:00), cuando se guarda, entonces se rechaza con un mensaje junto a ese día que dice que la hora de fin debe ser posterior a la de inicio, y el horario anterior del dentista no cambia.
- [ ] CA3 (abuso) · Dado un horario manipulado (un día que no existe, una hora mal escrita como "25:99" o un texto, o una lista en lugar de un valor), cuando se envía, entonces se rechaza con un error de validación, nunca con un error del servidor, y el horario anterior no cambia.
- [ ] CA4 · Dado un día marcado al que le falta la hora de inicio o la de fin, cuando se guarda, entonces se rechaza con un mensaje junto a ese día y el horario anterior no cambia; nunca se rellena una hora por defecto sin que la administración la vea.
- [ ] CA5 · Dado un dentista, cuando la administración guarda su horario sin ningún día marcado, entonces el horario queda vacío y el mensaje avisa de que no se podrán agendar citas con ese dentista hasta que tenga horario.
- [ ] CA6 (abuso) · Dada una persona del personal que no es dentista (asistente o administración), o un identificador que no existe, cuando se intenta guardar un horario para ella, entonces se rechaza y no se guarda nada; la ficha de un asistente o un administrador no ofrece el formulario de horario.
- [ ] CA7 · Dado un dentista con citas futuras agendadas o confirmadas, cuando la administración guarda un horario nuevo que deja alguna fuera (en un día sin horario, o que empieza antes o termina después de la franja de su día), entonces el horario se guarda, las citas no cambian y la administración ve la lista de las que quedaron fuera, con paciente, fecha, hora y estado y un enlace a cada una.
- [ ] CA8 · Dado un cambio de horario que no deja ninguna cita futura fuera, cuando se guarda, entonces solo se muestra el mensaje de que se guardó, sin lista; las citas pasadas, canceladas, no asistidas, completadas o en curso nunca aparecen en la lista.
- [ ] CA9 (abuso) · Dado un dentista, un asistente o un paciente, cuando intenta guardar el horario de un dentista, entonces se rechaza por permisos y el horario no cambia.

## Fuera de alcance
- Reprogramar una cita a otra fecha u hora, o reabrir una cancelada → spec 028 reprogramar-citas
  (decisión del usuario, 2026-10-02).
- Cambiar o cancelar automáticamente las citas que quedan fuera del horario: se avisan, no se
  tocan.
- Avisar al paciente o al dentista del cambio de horario.
- Franjas que cruzan la medianoche, varias franjas por día y excepciones por fecha (vacaciones,
  festivos).
- Que el dentista edite su propio horario.
- Que otras partes de la app comprueben que la persona sea un dentista (resto de RS12.c → spec
  013).

## Seguridad y privacidad
- **Datos sensibles:**
  - el horario es un dato operativo, no sensible (spec 005);
  - la lista de citas afectadas muestra nombres de pacientes y fechas de citas (dato personal y
    de salud según `docs/security.md`) y solo la ve la administración, que ya tiene acceso a
    todas las citas.
- **Quién puede hacer qué:**
  - administración → define el horario de cualquier dentista;
  - dentista, asistente y paciente → no pueden definirlo.
- **Casos de abuso:**
  - Como administración, envío una franja invertida o que cruza la medianoche → se rechaza y el horario anterior no cambia (CA2).
  - Como usuario malintencionado, envío un horario manipulado (día inexistente, hora mal escrita, listas) → error de validación, nunca error del servidor (CA3).
  - Como administración, intento poner horario a un asistente o a alguien que no existe → se rechaza (CA6).
  - Como dentista, asistente o paciente, intento guardar el horario de un dentista → se rechaza (CA9).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS12.a — validar `hora` del agendado con formato de hora | fuera | ya mitigada en v0.2.0 (spec 025) |
| RS12.b — validar cada día del horario (día, horas y orden) | dentro | CA2, CA3, CA4 |
| RS12.c — comprobar que se trata de un dentista donde se espera uno | fuera, parcialmente atendida | En el horario sí (CA6); en pacientes, expedientes y la asignación de asistentes → spec 013 (decisión del usuario, 2026-09-29). |

## Auditoría
No aplica a los eventos de auditoría obligatorios: el horario es un dato operativo, y cambiarlo
no es un cambio de pacientes, expedientes, notas ni cuentas (`docs/observability.md`,
constitución P7). La lista de citas afectadas se muestra a la administración, pero no se
registra.

## Diseño
La administración tiene que entender de un vistazo qué guardó y qué tiene que atender.
- Los errores de un día van junto a ese día.
- La lista de citas que quedaron fuera es la acción pendiente más visible tras guardar, y cada
  cita lleva a su detalle.

Pantallas que toca:
- la sección de horario de la ficha del dentista (y su ausencia en la de un asistente o un
  administrador);
- el aviso de citas afectadas tras guardar.

Sistema: [docs/design/system.md](../../design/system.md). Lo nuevo o modificado sigue el principio
P9: solo tokens del sistema y WCAG 2.1 AA. Esto incluye los errores asociados a su campo y
anunciados, y el foco visible.

## Requisitos no funcionales
- Los mensajes de rechazo están en español y dicen qué corregir.
- Las partes nuevas o modificadas de la pantalla cumplen WCAG 2.1 AA (constitución P9).

## Preguntas abiertas

## Decisiones
Cada brecha, contradicción o supuesto resuelto, con su origen (`shared/contract.md` → "Decisiones").

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-02 | brecha | Horarios y reprogramar citas son dos capacidades que se liberan por separado | Dividir: 027 horarios válidos, 028 reprogramar-citas | usuario |
| 2026-10-02 | brecha | ¿Qué pasa con las citas futuras que quedan fuera del horario nuevo? | Se guarda el horario y la administración ve la lista; las citas no cambian | usuario |
| 2026-10-02 | brecha | ¿Puede una franja cruzar la medianoche? | No: la hora de fin es posterior a la de inicio el mismo día. Así se cierra la deuda 025/R12 sin cambiar la regla de agenda | usuario |
| 2026-10-02 | brecha | ¿Quién define el horario y a quién? | Solo la administración, y solo a dentistas | usuario |
| 2026-10-02 | implícita | ¿El horario es un dato sensible? | No, es un dato operativo; solo admin lo define | docs/specs/005-horarios-dentista/spec.md (Seguridad) |
| 2026-10-02 | implícita | Accesibilidad de la pantalla modificada | WCAG 2.1 AA y solo tokens del sistema | docs/constitution.md P9 |
| 2026-10-02 | implícita | Un horario por día de la semana, de lunes a domingo | Se mantiene | docs/specs/005-horarios-dentista/spec.md CA2 |
| 2026-10-02 | supuesto | ¿Qué cuenta como cita "futura"? | La que aún no empieza en la hora de la clínica, como en la spec 026 | docs/specs/026-estados-cita/spec.md |
| 2026-10-02 | supuesto | ¿Se puede guardar un horario sin días? | Sí, con aviso de que no se podrán agendar citas | /specify |

## Notas para /plan
- Código actual: `PersonalController::saveHorarios` (`POST /admin/personal/{id}/horarios`) borra
  e inserta sin validar `dias[*]`, rellena 09:00–18:00 por defecto y no comprueba
  `personal.tipo`. Formulario en `resources/views/personal/show.blade.php` (líneas 193–253).
- La regla de agenda (`App\Agenda\ReservaCitas`, spec 025) bloquea la fila del dentista en
  `personal` antes de leer el horario. Conviene que el guardado del horario tome el mismo bloqueo,
  para que una reserva simultánea vea el horario anterior completo o el nuevo, nunca uno a medias
  ni sin días.
- La "cita futura" debería usar `EstadosCita::haEmpezado` / `clinica.zona_horaria` de la 026.
- Criterios de las specs extendidas que se modifican:
  - 005 CA1 → reemplazado por CA1, CA4 y CA5 (ya no se rellenan horas por defecto);
  - 005 CA4 (HOY NO SE CUMPLE) → lo resuelven CA2 y CA3.
- La deuda 025/R12 (franjas que cruzan la medianoche) se cierra con CA2.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-02 | Spec creada | /specify, corte de la lógica de citas: 027 horarios y 028 reprogramar |
| 2026-10-02 | Aprobada | Decisión del usuario |
