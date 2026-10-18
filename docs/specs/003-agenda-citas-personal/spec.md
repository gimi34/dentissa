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
- [x] CA2 · Dado un dentista, cuando abre su agenda, entonces solo ve sus citas; dado un asistente, solo las de sus dentistas asignados.
- [ ] CA3 · Dada una cita nueva, cuando la fecha y hora caen fuera del horario semanal del dentista o no caben en él, entonces se rechaza con error. → reemplazado por 025 CA2, CA3
- [ ] CA4 · Dada una cita nueva que se solapa con otra no cancelada ni `no_asistio` del mismo dentista, cuando se guarda, entonces se rechaza con error. → reemplazado por 025 CA4, CA5, CA6, CA7
- [x] CA5 · Dada una cita válida (duración 10–300 min, servicio opcional), cuando se guarda, entonces se crea en estado `agendada` con `creado_por` = usuario actual.
- [x] CA6 · Dado el detalle de una cita fuera de su alcance, cuando un dentista o asistente la abre, entonces recibe 403.
- [ ] CA7 · Dado el detalle de una cita, cuando se cambia su estado (`PATCH …/estado`) a cualquiera de los 6 estados, entonces se guarda y el trigger añade una fila a `historial_citas`. → reemplazado por 026 CA1, CA2, CA3, CA4, CA5, CA6, CA9
- [ ] CA8 · Dado un admin, cuando edita las notas internas (`PATCH /admin/citas/{id}/notas`), entonces se guardan; solo el admin las ve.
- [ ] CA9 · Dado un admin, cuando "elimina" una cita (`DELETE /admin/citas/{id}`), entonces su estado pasa a `cancelada` sin borrarla. → reemplazado por 026 CA6
- [ ] CA10 (abuso) · Como dentista o asistente, abro el formulario de alta → solo veo los pacientes de mi alcance. **HOY NO SE CUMPLE: se listan todos los pacientes activos con teléfono y se lee `proxima_visita` de cualquier paciente.**
- [x] CA11 (abuso) · Como dos usuarios que agendan el mismo hueco a la vez → solo una cita se guarda. Resuelto por 025 (v0.2.0) → reemplazado por 025 CA8

## Fuera de alcance
- Recordatorios de cita (`recordatorio_enviado` existe pero nunca se escribe).
- Grafo de transiciones de estado (hoy se permite cualquier cambio). → incorporado por 026 (v0.3.0)

## Seguridad y privacidad
- Datos sensibles involucrados: notas del paciente e internas (salud), teléfono de pacientes.
- Quién puede hacer qué: admin → todo; dentista → sus citas (sin notas internas, cancelar ni
  reprogramar); asistente → citas de sus dentistas, que también puede reprogramar (spec 028).
- Casos de abuso: CA6, CA10, CA11.

## Auditoría
Cada cambio de estado, también la cancelación, y cada reprogramación dejan una sola fila en
`historial_citas` con su autor (specs 026 y 028; el trigger `trg_historial_cita` está retirado). Los
cambios de notas internas no dejan rastro con actor. Ver brechas en `docs/observability.md`.

## Requisitos no funcionales
- Estados: agendada, confirmada, en_curso, completada, cancelada, no_asistio.

## Preguntas abiertas

## Supuestos
- `cancelada` y `no_asistio` liberan el hueco para nuevas citas.

## Notas para /plan
- La regla de agenda ya vive en un solo sitio, `app/Agenda/ReservaCitas.php` (spec 025).

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:52-61,109-113,134-141`, `app/Http/Controllers/CitasController.php`, `resources/views/citas/index.blade.php`, `create.blade.php`, `show.blade.php`, `database/dentissa.sql` (trigger)
- Tests: `AltaCitasFlujosTest` (CA5), `EstadosCitaRolesTest` (CA2, CA6) y los de su CA11

## Observaciones (solo si status=inferred)
- ~~Un cambio de estado con motivo produce dos filas en `historial_citas`.~~ Resuelto por 026 (una sola entrada con autor).
- `citas/show.blade.php:192` dice "Solo visible para el personal", pero la condición (línea 183) es `$rp === 'admin'`.
- ~~`hora` solo se valida como `required`; un valor mal formado provoca error 500.~~ Resuelto por 025 (CA9).
- CA1 dice «próximos 7 días»: el código filtra de hoy a hoy + 7, ambos incluidos, y el botón «Hoy» se marca activo cuando no hay `fecha_desde`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-10-02 | Comportamiento modificado por 025 en v0.2.0 | /release |
| 2026-10-02 | Comportamiento modificado por 026 en v0.3.0 | /release |
| 2026-10-11 | Comportamiento modificado por 028 en v0.6.0 | /release |
| 2026-10-18 | Criterios con test marcados, Auditoría, Seguridad y Observaciones al día con 025, 026 y 028 | /init (re-sincronizar) |
