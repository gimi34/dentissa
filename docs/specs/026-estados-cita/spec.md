---
id: 026
slug: estados-cita
status: approved
created: 2026-10-02
extends: [003, 004]
---

# 026 · Estados de la cita y quién puede cambiarlos

## Problema
Hoy el personal puede pasar una cita a cualquiera de los seis estados, en cualquier orden. Por
ejemplo, puede reabrir una cita cancelada sin comprobar que el hueco sigue libre, saltándose la
regla de agenda de la spec 025, o marcar como completada una cita que nunca empezó. El dentista
puede cancelar, aunque la spec 003 dice que no debería.

El historial de una cita no sirve para saber quién hizo cada cambio. Un cambio con motivo deja dos
filas: una sin autor y otra sin el estado anterior. Además, el cambio de estado acepta las notas
internas de la cita, así que un dentista o un asistente puede sobrescribirlas sin poder verlas.

Esto afecta al personal, que no puede fiarse del estado de la agenda, y a los pacientes, cuya cita
cancelada puede reaparecer encima de otra. Atiende la brecha OB1.e **parcialmente**: el historial
de estados gana el autor, y el evento de auditoría formal queda para la spec 022. Atiende la
brecha OB5 **parcialmente**: una sola entrada con autor por cambio, también en las cancelaciones;
las notas internas y la conservación del historial al borrar una cita quedan para la spec 022.
Atiende el riesgo RS2 **parcialmente**: solo un paciente puede usar la cancelación del paciente; el
resto de las pantallas del paciente y la ficha creada desde el perfil quedan para la spec 015.

## Historias de usuario
- Como personal de la clínica, quiero que una cita solo avance por estados que tengan sentido, para que la agenda refleje lo que pasó de verdad.
- Como dentista, quiero confirmar, iniciar y completar mis citas y marcar a quien no vino, sin poder cancelarlas.
- Como asistente, quiero confirmar, cancelar y marcar como no asistidas las citas de mis dentistas.
- Como paciente, quiero cancelar mi cita mientras no haya empezado.
- Como administración, quiero ver en el historial de cada cita quién cambió su estado, de cuál a cuál y por qué.

## Criterios de aceptación
- [ ] CA1 · Dada una cita, cuando se cambia su estado, entonces solo se aceptan estas transiciones: agendada → confirmada; confirmada → en curso; en curso → completada; agendada o confirmada → cancelada; agendada o confirmada → no asistió.
- [ ] CA2 · Dada una cita completada, cancelada o no asistida, cuando alguien intenta cambiar su estado, entonces se rechaza con un mensaje que dice que la cita ya está cerrada, y la cita no cambia. Para volver a atender al paciente se agenda una cita nueva. Al paciente se le dice que la cita ya no se puede cancelar.
- [ ] CA3 · Dada cualquier otra transición fuera de CA1 (por ejemplo, de agendada a completada o de en curso a cancelada), cuando se intenta, entonces se rechaza con un mensaje que indica desde qué estados se permite, y la cita no cambia.
- [ ] CA4 · Dado un dentista con una cita propia, cuando confirma, inicia, completa o marca que el paciente no asistió, entonces el cambio se acepta; cuando intenta cancelarla, se rechaza y la cita no cambia.
- [ ] CA5 · Dado un asistente con una cita de un dentista que tiene asignado, cuando la confirma, la cancela o marca que el paciente no asistió, entonces el cambio se acepta; cuando intenta iniciarla o completarla, se rechaza y la cita no cambia.
- [ ] CA6 · Dada la administración, cuando hace cualquier transición permitida por CA1, entonces se acepta; la acción "eliminar cita" del detalle de la cita es una cancelación y sigue las mismas reglas.
- [ ] CA7 · Dado un paciente con una cita propia agendada o confirmada que todavía no ha empezado, cuando la cancela, entonces pasa a cancelada; si ya empezó o está en otro estado, se rechaza con un mensaje y la cita no cambia. Dado un miembro del personal que usa la cancelación del paciente, cuando la envía, entonces se rechaza por permisos y la cita no cambia.
- [ ] CA8 · Dada una cita cuya hora de inicio aún no ha llegado, cuando alguien la marca en curso o no asistida, entonces se rechaza con un mensaje que lo explica.
- [ ] CA9 · Dado cualquier cambio de estado aceptado, por cualquier rol, cuando se guarda, entonces queda exactamente una entrada en el historial de la cita con quién lo hizo, el estado anterior, el nuevo, el motivo si se dio y la fecha; y el historial de la cita lo muestra así.
- [ ] CA10 (abuso) · Dados dos cambios de estado incompatibles enviados a la vez sobre la misma cita (por ejemplo, confirmar y cancelar), cuando se procesan, entonces solo se aplica uno; el otro se evalúa contra el estado ya cambiado y, si no está permitido, recibe el mensaje de CA2 o CA3, nunca un error del servidor.
- [ ] CA11 (abuso) · Dado un estado inexistente o una transición manipulada desde el formulario (por ejemplo, reabrir una cita cancelada), cuando se envía, entonces se rechaza con un error de validación o el mensaje de CA2, sin error del servidor y sin cambiar la cita. Dado un cambio de estado al que se añaden a mano notas internas, cuando se procesa, entonces las notas internas de la cita no cambian, lo envíe quien lo envíe.

## Fuera de alcance
- Reprogramar una cita o reabrir una cancelada (spec 027 horarios-reprogramar; hoy se agenda de
  nuevo).
- Antelación mínima para que el paciente cancele (decisión del usuario, 2026-10-02: solo cuenta que
  la cita no haya empezado).
- Notificar al paciente o al dentista de los cambios de estado.
- Cambiar automáticamente el estado de citas pasadas (p. ej. a no asistió al terminar el día).
- Eventos de auditoría formales del cambio de estado y de los rechazos → spec 022 (objetivo 3).
- Que otros roles entren en las demás pantallas del paciente (resto de 004 CA7) → spec 015; esta
  spec solo lo impide en la cancelación (CA7).
- Editar las notas internas de la cita (003 CA8): siguen editándose solo desde su propio formulario
  de la administración. El cambio de estado deja de aceptarlas (CA11).
- Conservar el historial de una cita si la cita se borra (OB5.c) → spec 022.

## Seguridad y privacidad
- Datos sensibles involucrados: estado y motivo de la cita, asociados a un paciente (datos de
  salud según `docs/security.md`). El historial muestra el nombre de quien hizo el cambio y el
  motivo. Lo ven solo el personal con alcance sobre la cita y la administración.
- Quién puede hacer qué:
  - administración → todas las transiciones;
  - dentista → confirmar, iniciar, completar y no asistió en sus citas;
  - asistente → confirmar, cancelar y no asistió en las citas de sus dentistas;
  - paciente → cancelar las suyas.
  - El alcance por cita (personal) no cambia respecto a la 003, y el del paciente tampoco respecto
    a la 004.
- Casos de abuso:
  - Como personal, envío a la vez dos cambios incompatibles → solo uno se aplica, sin error del servidor (CA10).
  - Como personal, manipulo el formulario para reabrir una cita cancelada o mandar un estado inexistente → se rechaza sin cambios (CA11).
  - Como dentista o asistente, añado notas internas al cambio de estado para sobrescribirlas → las notas no cambian (CA11).
  - Como dentista, intento cancelar mi cita o cambiar la de otro dentista → se rechaza (CA4; el alcance ya lo rechaza hoy con 403).
  - Como paciente, intento cancelar la cita de otro paciente o una que ya empezó → se rechaza (CA7; 004 CA6).
  - Como personal, uso la cancelación pensada para el paciente → se rechaza por permisos (CA7; parte de 004 CA7).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| OB1.a — tabla de auditoría solo-anexado | fuera | infraestructura de auditoría → spec 020 |
| OB1.b — eventos de autenticación | fuera | no es de citas → spec 021 auditoria-accesos-cuentas |
| OB1.c — accesos denegados (403) | fuera | registro central → spec 020 |
| OB1.d — lecturas de ficha de paciente y expedientes | fuera | no es de citas → spec 022 auditoria-datos-clinicos |
| OB1.e — registrar cambios de pacientes, expedientes, notas y estado de citas (campos, sin valores) | fuera, parcialmente atendida | El historial de estados gana el autor (CA9), pero el evento de auditoría formal requiere la infraestructura de la spec 020 → spec 022 (decisión del usuario, 2026-10-02). |
| OB1.f — altas, cambios de rol y de cuentas | fuera | no es de citas → spec 021 |
| OB1.g — retención de 5 años con purga | fuera | → spec 020 |
| OB1.h — pantalla de consulta de auditoría | fuera | → spec 020 |
| OB5.a — una sola escritura por cambio, con actor, estado anterior, nuevo y motivo | dentro | CA9 |
| OB5.b — registrar cancelaciones y cambios de notas internas con actor | fuera, parcialmente atendida | Las cancelaciones de todos los roles quedan con autor (CA6, CA7, CA9); los cambios de notas internas no son cambios de estado → spec 022 (decisión del usuario, 2026-10-02). |
| RS2.a — exigir el rol de paciente en todas sus pantallas | fuera, parcialmente atendida | Solo la cancelación del paciente lo exige (CA7); el resto de las pantallas del paciente → spec 015 (decisión del usuario, 2026-10-02). |
| RS2.b — no crear ficha de paciente desde el perfil de quien no es paciente | fuera | no es de citas → spec 015 |
| OB5.c — impedir el borrado en cascada del historial | fuera | la aplicación no borra citas ("eliminar" es cancelar, CA6); la conservación del historial va con la auditoría de datos clínicos → spec 022 (decisión del usuario, 2026-10-02) |

## Auditoría
- Cada cambio de estado aceptado deja una entrada de historial con quién, de qué estado a cuál,
  sobre qué cita, motivo y fecha (CA9).
- Los rechazos no dejan historial.
- El evento de auditoría formal (almacén solo-anexado, con resultado e identificador de petición)
  queda para la spec 022.

## Requisitos no funcionales
- Los mensajes de rechazo están en español y explican qué se puede hacer en su lugar.
- Las pruebas de estos criterios corren en menos de 10 s en local.

## Preguntas abiertas

## Supuestos
- El motivo es opcional en todos los cambios, como hoy (decisión razonable; se puede hacer
  obligatorio para cancelar en una spec posterior).
- La spec tiene 11 criterios, algo más de los ~10 recomendados; se mantiene junta porque es una
  sola capacidad que no se puede liberar por partes (decisión del usuario, 2026-10-02).
- "La cita ha empezado" significa que su fecha y hora de inicio ya pasó, en la hora local de la
  clínica.
- La administración puede cancelar una cita agendada o confirmada aunque su hora ya haya pasado.
  La regla de tiempo de CA8 solo afecta a "en curso" y "no asistió".
- Los estados existentes no cambian de nombre: agendada, confirmada, en curso, completada,
  cancelada y no asistió.
- Transiciones, permisos por rol, reglas de tiempo y alcance de la auditoría: decisiones del
  usuario, 2026-10-02.
- Si alguien pide un cambio que su rol no permite, se rechaza por permisos aunque la cita esté
  cerrada; el mensaje de CA2 aplica a los cambios que su rol sí puede pedir. Volver a "agendada"
  nunca está permitido: con la cita cerrada recibe el mensaje de CA2 y, si no, uno que dice que la
  cita no puede volver a ese estado (decisión del usuario, 2026-10-02, /analyze A2).

## Notas para /plan
- Corte acordado: 025 → 026 → 027 (decisión del usuario, 2026-09-29).
- Hoy `CitasController::updateEstado` acepta cualquier estado del `enum` y también
  `notas_internas`, que hay que dejar de aceptar (CA11, decisión del usuario, 2026-10-02).
  `CitasController::destroy` (admin) cancela sin comprobar el estado actual.
  `ClienteCitasController::cancelar` ya exige agendada o confirmada.
- El historial lo escribe el trigger `trg_historial_cita`, sin actor. Además, el controlador
  inserta una segunda fila sin `estado_anterior` cuando hay motivo, y por eso salen dos filas
  (003, Observaciones). Hay que dejar una sola fila con actor (CA9). La pregunta abierta de la 013
  sobre conservar o no el trigger queda condicionada a esto.
- La vista `citas/show.blade.php` ofrece hoy los seis estados como radio buttons. Debería ofrecer
  solo las transiciones permitidas para el rol y el estado actual (UI → revisar con la skill
  `design`).
- La concurrencia de CA10 se puede resolver como en la 025 (bloqueo de la fila de la cita en una
  transacción); `App\Agenda` es el módulo natural.
- Decisiones ya tomadas para `/plan` (decisión del usuario, 2026-10-02):
  - retirar `trg_historial_cita` con una migración (reversible) y escribir la fila desde la app;
  - zona horaria de la clínica configurable (por defecto America/Mexico_City) para "ya empezó";
  - Gate `cambiar-estado-cita` con `AlcanceClinico::dentistasDelAlcance()` (firma de la 014) y
    `role:cliente` en la ruta de cancelar del paciente;
  - excepciones P7 (→ 022 y 020) y P8 (redacción de datos en los logs de errores → 023) aceptadas; sin feature flag.
- Criterios de las specs extendidas que se modifican:
  - 003 CA7 ("a cualquiera de los 6 estados") → reemplazado por CA1–CA6 y CA9;
  - 003 CA9 ("eliminar" del admin) → reemplazado por CA6;
  - 003 Fuera de alcance "grafo de transiciones" → pasa a estar dentro;
  - 004 CA5 → reemplazado por CA7 (añade "antes de que empiece");
  - 004 CA7 → atendido en parte por CA7: la ruta de cancelar exige `role:cliente`; el resto del
    grupo `/cliente` → 015.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-02 | Spec creada | /specify, corte de la lógica de citas en 025–027 |
| 2026-10-02 | Aprobada | Decisión del usuario |
| 2026-10-02 | Cita OB5 (OB5.a dentro; OB5.b y OB5.c fuera) y CA11 rechaza las notas internas en el cambio de estado | /specify --edit, hallazgos de /plan |
| 2026-10-02 | Aprobada de nuevo | Decisión del usuario |
| 2026-10-02 | Cita RS2 (RS2.a parcial, RS2.b fuera), CA7 rechaza al personal en la cancelación del paciente, CA2 con el mensaje del paciente, CA6 "del detalle" y supuesto de "rol primero" | /analyze ronda 1: A12, A14, A19, A2 |
| 2026-10-02 | Aprobada de nuevo | Decisión del usuario |
