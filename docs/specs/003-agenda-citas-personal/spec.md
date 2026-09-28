---
id: 003
slug: agenda-citas-personal
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 003 · Agenda de citas del personal

## Problema
El personal de la clínica necesita ver la agenda, agendar citas dentro del horario de cada
dentista sin solaparlas y seguir el estado de cada cita hasta que se atiende o se cancela.

## Historias de usuario
- Como admin, quiero ver y gestionar todas las citas para organizar la clínica.
- Como dentista, quiero ver solo mis citas y cambiar su estado según avanza la consulta.
- Como asistente, quiero agendar citas para los dentistas que tengo asignados.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un admin, cuando abre `GET /admin/citas`, entonces ve todas las citas de los próximos 7 días por defecto, filtrables por fechas, dentista, estado y texto, 25 por página.
- [ ] CA2 · Dado un dentista, cuando abre su agenda, entonces solo ve sus citas; dado un asistente, solo las de sus dentistas asignados.
- [ ] CA3 · Dada una cita nueva, cuando la fecha y hora caen fuera del horario semanal del dentista o no caben en él, entonces se rechaza con error.
- [ ] CA4 · Dada una cita nueva que se solapa con otra no cancelada ni `no_asistio` del mismo dentista, cuando se guarda, entonces se rechaza con error.
- [ ] CA5 · Dada una cita válida (duración 10–300 min, servicio opcional), cuando se guarda, entonces se crea en estado `agendada` con `creado_por` = usuario actual.
- [ ] CA6 · Dado el detalle de una cita fuera de su alcance, cuando un dentista o asistente la abre, entonces recibe 403.
- [ ] CA7 · Dado el detalle de una cita, cuando se cambia su estado (`PATCH …/estado`) a cualquiera de los 6 estados, entonces se guarda y el trigger añade una fila a `historial_citas`.
- [ ] CA8 · Dado un admin, cuando edita las notas internas (`PATCH /admin/citas/{id}/notas`), entonces se guardan; solo el admin las ve.
- [ ] CA9 · Dado un admin, cuando "elimina" una cita (`DELETE /admin/citas/{id}`), entonces su estado pasa a `cancelada` sin borrarla.
- [ ] CA10 (abuso) · Como dentista o asistente, abro el formulario de alta → solo veo los pacientes de mi alcance. **HOY NO SE CUMPLE: se listan todos los pacientes activos con teléfono y se lee `proxima_visita` de cualquier paciente.**
- [ ] CA11 (abuso) · Como dos usuarios que agendan el mismo hueco a la vez → solo una cita se guarda. **HOY NO SE CUMPLE: comprobación e inserción sin transacción ni bloqueo.**

## Fuera de alcance
- Recordatorios de cita (`recordatorio_enviado` existe pero nunca se escribe).
- Grafo de transiciones de estado (hoy se permite cualquier cambio).

## Seguridad y privacidad
- Datos sensibles involucrados: notas del paciente e internas (salud), teléfono de pacientes.
- Quién puede hacer qué: admin → todo; dentista → sus citas (sin notas internas ni cancelar);
  asistente → citas de sus dentistas.
- Casos de abuso: CA6, CA10, CA11.

## Auditoría
Solo el trigger `trg_historial_cita` registra cambios de estado, sin actor. Cancelaciones y notas
no dejan rastro con actor. Ver brechas en `docs/observability.md`.

## Requisitos no funcionales
- Estados: agendada, confirmada, en_curso, completada, cancelada, no_asistio.

## Preguntas abiertas

## Supuestos
- `cancelada` y `no_asistio` liberan el hueco para nuevas citas.

## Notas para /plan
- La misma regla de agenda está duplicada en `ClienteCitasController` (spec 004); la spec 013
  propone extraerla a un solo sitio.

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:53-59,107-111,132-136`, `app/Http/Controllers/CitasController.php`, `resources/views/citas/index.blade.php`, `create.blade.php`, `show.blade.php`, `database/dentissa.sql` (trigger)
- Tests: sin tests

## Observaciones (solo si status=inferred)
- Un cambio de estado con motivo produce dos filas en `historial_citas` (trigger sin actor + controlador sin `estado_anterior`).
- `citas/show.blade.php:96` dice "Solo visible para el personal", pero la condición es `rol === 'admin'`.
- `hora` solo se valida como `required`; un valor mal formado provoca error 500.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
