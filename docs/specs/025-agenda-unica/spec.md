---
id: 025
slug: agenda-unica
status: implemented
created: 2026-09-29
extends: [003, 004, 005]
---

# 025 · Regla de agenda única sin doble reserva

## Problema
La regla que decide si un dentista puede atender una cita (tiene horario ese día, la cita cabe en
su franja y no se solapa con otra) está copiada en el agendado del personal y en el del paciente.
No tiene pruebas y cualquier cambio exige acordarse de tocar los dos sitios. Además, dos personas
que reservan a la vez el mismo hueco pueden quedarse las dos con él. Las citas pegadas se tratan
distinto según el orden: se acepta una cita que empieza justo cuando termina otra, pero se rechaza
una que termina justo cuando empieza otra. Y una hora mal escrita o un dentista que no es
dentista producen un error del servidor o se aceptan sin más.

Afecta a pacientes, que pueden encontrarse con una cita duplicada o un error, y al personal, que
no puede agendar citas seguidas sin hueco. Atiende RS12 **parcialmente**: RS12.a entra completa,
RS12.c solo en el agendado de citas y RS12.b va a la spec 027.

## Historias de usuario
- Como integrante del equipo, quiero que la regla de agenda viva en un solo sitio con pruebas propias, para cambiarla sin romper el otro flujo.
- Como paciente o personal, quiero que dos personas no puedan quedarse con el mismo hueco de un dentista.
- Como personal de la clínica, quiero agendar citas seguidas sin dejar huecos entre ellas.
- Como paciente o personal, quiero un mensaje claro cuando la hora o el dentista no son válidos, en lugar de un error.

## Criterios de aceptación
- [x] CA1 · Dada una misma fecha, hora, duración y dentista, cuando se valida la cita en el agendado del personal y en el del paciente, entonces ambos flujos aplican la misma regla (horario del día, encaje en la franja y solape) y dan el mismo resultado.
- [x] CA2 · Dado un dentista sin horario ese día de la semana, o con ese día marcado como no disponible, cuando se pide una cita ese día, entonces se rechaza con un mensaje que dice que el dentista no atiende ese día.
- [x] CA3 · Dada una cita que empieza antes del inicio de la franja del dentista o termina después de su fin, cuando se pide, entonces se rechaza con un mensaje que indica la franja de atención. Una cita que empieza justo al inicio o termina justo al fin de la franja se acepta.
- [x] CA4 · Dada una cita que se solapa al menos un minuto con otra del mismo dentista que no está cancelada ni marcada como no asistió, cuando se pide, entonces se rechaza con un mensaje de hueco no disponible que no revela el paciente, el servicio ni el motivo de la otra cita.
- [x] CA5 · Dada una cita que empieza exactamente cuando termina otra del mismo dentista, o que termina exactamente cuando empieza otra, cuando se pide, entonces se acepta: citas seguidas no son conflicto.
- [x] CA6 · Dadas citas canceladas o marcadas como no asistió en ese hueco, cuando se pide una cita nueva del mismo dentista, entonces no cuentan como conflicto y la cita se acepta.
- [x] CA7 · Dada una cita de otro dentista a la misma hora, cuando se pide una cita para este dentista, entonces no cuenta como conflicto.
- [x] CA8 (abuso) · Dadas dos peticiones simultáneas para huecos que se solapan del mismo dentista, del personal, del paciente o una de cada, cuando se procesan, entonces solo una cita queda guardada y la otra recibe el mensaje de hueco no disponible, nunca un error del servidor.
- [x] CA9 (abuso) · Dada una hora mal formada (fuera de rango como "25:99", o texto) o una fecha mal formada (con hora incluida como "2026-10-05 10:00", o texto), cuando se envía en cualquiera de los dos flujos, entonces se rechaza con un error de validación del campo correspondiente y nunca con un error del servidor.
- [x] CA10 (abuso) · Dado un identificador de dentista que no existe, que corresponde a otro tipo de personal (asistente o administración) o a un dentista cuya cuenta está desactivada, cuando se agenda en cualquiera de los dos flujos, entonces se rechaza con un error de validación y no se guarda ninguna cita.

## Fuera de alcance
- Qué cambios de estado se permiten y quién puede hacerlos → spec 026 estados-cita.
- Validar el formulario de horarios del dentista (RS12.b), avisar de citas que quedan fuera al
  cambiar un horario y reprogramar citas → spec 027 horarios-reprogramar.
- Las diferencias propias de cada flujo se mantienen:
  - el personal elige la duración (10–300 min) y el paciente la toma del servicio;
  - el servicio es obligatorio solo para el paciente;
  - solo el personal puede agendar en fechas pasadas.
- Limitar el selector de hora del paciente a la franja real del dentista: hoy el formulario ofrece
  de 08:00 a 20:00.
- Que el formulario del personal liste solo los pacientes de su alcance (003 CA10) → spec 014.
- Que otros roles no entren en las pantallas del paciente (004 CA7) → spec 015
  portal-paciente-aislado.
- Esquema por migraciones, modelos, cifrado y prueba de arquitectura → spec 013.
- Registrar en auditoría el alta y los rechazos de citas → spec 022 auditoria-datos-clinicos
  (objetivo 3).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales de citas (paciente, dentista, fecha y hora). La
  regla no muestra datos nuevos; el mensaje de hueco no disponible no debe revelar datos de otra
  cita (CA4).
- Quién puede hacer qué: sin cambios respecto a las specs 003 y 004. Personal según su rol y
  alcance, y el paciente solo para sí mismo.
- Casos de abuso:
  - Como dos usuarios concurrentes, intento reservar el mismo hueco → solo uno lo consigue y el otro recibe un mensaje, no un error (CA8).
  - Como usuario, envío una hora o una fecha mal formadas → se rechazan con error de validación, sin error del servidor (CA9).
  - Como usuario, envío como dentista a un asistente, a un administrador o a un dentista desactivado → se rechaza y no se guarda la cita (CA10).
  - Como paciente, intento averiguar quién tiene cita en un hueco → el mensaje de no disponible no lo revela (CA4).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS12.a — validar `hora` con formato | dentro | CA9 |
| RS12.b — validar `dias[*]` (día, horas y orden) en el horario del dentista | fuera | se edita en la ficha del dentista, no en el agendado → spec 027 horarios-reprogramar |
| RS12.c — dentista con restricción de tipo, en el agendado de citas (personal y paciente) | dentro | CA10 |
| RS12.c — dentista con restricción de tipo, en pacientes, expedientes y asignación de asistentes | fuera | no forman parte de la regla de agenda → spec 013 (CA7) |

## Auditoría
Esta spec no añade eventos de auditoría ni cambia permisos. Registrar el alta y los rechazos de
citas con actor queda para la spec 022 (objetivo 3). El registro actual de cambios de estado no
cambia.

## Requisitos no funcionales
- Las pruebas de la regla de agenda corren en menos de 10 s en local.
- Los mensajes de rechazo están en español y el formulario conserva los datos introducidos.

## Preguntas abiertas

## Supuestos
- Las citas canceladas y las marcadas como no asistió liberan el hueco (003, Supuestos).
- Cada dentista tiene como máximo un horario por día de la semana (005 CA2).
- El mensaje de hueco no disponible es el mismo en ambos flujos, "Ese horario ya no está
  disponible. Por favor elige otro.", el que hoy ve el paciente. El del personal cambia a este
  para no depender del flujo.
- RS12.c queda repartida: el agendado va en esta spec y el resto sigue en la 013 (decisión del
  usuario, 2026-09-29).

## Notas para /plan
- Corte acordado: 025 agenda-unica → 026 estados-cita → 027 horarios-reprogramar, en ese orden
  (decisión del usuario, 2026-09-29).
- Asume 013 CA4, CA5 y CA6 (CA6 decidido: citas seguidas no son conflicto, decisión del usuario,
  2026-09-29). Tras aprobar esta spec, `/specify --edit 013` retira esos criterios, RS12.a y la
  parte de RS12.c del agendado.
- Criterios de specs extendidas que esta spec reemplaza:
  - 003 CA3 y CA4 → CA1–CA7;
  - 003 CA11 ("HOY NO SE CUMPLE") → CA8;
  - 004 CA4 → CA1;
  - 005 CA3 → CA2.
- Hoy la regla está copiada en `CitasController::store` y `ClienteCitasController::agendarStore`.
  El solape usa un `whereBetween` inclusivo, que produce la asimetría de las citas pegadas.
- La doble reserva no se cierra solo con unificar la regla: hace falta transacción con bloqueo o
  restricción en el almacén (nota de la 013).
- `hora` solo es `required` y `dentista_id` es `exists:personal,id` sin tipo (RS12).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-29 | Spec creada | /specify, corte de la lógica de citas en 025–027 |
| 2026-09-29 | Aprobada | Decisión del usuario |
| 2026-09-30 | CA9 incluye la fecha mal formada y CA10 el dentista desactivado; vuelve a `draft` | `/analyze` ronda 1, A9 y A10 (decisión del usuario, 2026-09-30) |
| 2026-09-30 | Aprobada de nuevo | Decisión del usuario |
