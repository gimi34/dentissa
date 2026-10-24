---
id: 004
slug: citas-cliente
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 004 · Citas del paciente (consultar, agendar y cancelar)

## Problema
Los pacientes quieren consultar sus citas, agendar nuevas sin llamar a la clínica y cancelarlas
con antelación.

## Historias de usuario
- Como paciente, quiero ver mis próximas citas y las pasadas.
- Como paciente, quiero agendar una cita eligiendo servicio, dentista, fecha y hora.
- Como paciente, quiero cancelar una cita que ya no puedo atender.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un paciente, cuando abre `GET /cliente/citas`, entonces ve solo sus citas próximas (o las pasadas con `?filtro=pasadas`).
- [ ] CA2 · Dado un paciente, cuando abre el detalle de una cita que no es suya, entonces no la ve (404).
- [x] CA3 · Dado un paciente, cuando agenda en `POST /cliente/agendar`, entonces el servicio es obligatorio y fija la duración, y no se aceptan fechas pasadas.
- [ ] CA4 · Dada una cita pedida fuera del horario del dentista o solapada, cuando se envía, entonces se rechaza con la misma regla que usa el personal (spec 003). → reemplazado por 025 CA1
- [ ] CA5 · Dada una cita propia en estado `agendada` o `confirmada`, cuando la cancela, entonces pasa a `cancelada`; en otro estado se rechaza. → reemplazado por 026 CA7
- [x] CA6 (abuso) · Como paciente, intento cancelar o ver la cita de otro paciente cambiando el id → se rechaza. (Se cumple hoy: todas las consultas filtran por el paciente del usuario autenticado.)
- [ ] CA7 (abuso) · Como admin, dentista o asistente, entro a `/cliente/*` → se rechaza con 403. **HOY NO SE CUMPLE: el grupo solo exige `auth`, no `role:cliente`.** Atendido en parte por 026 (v0.3.0): la cancelación (`PATCH /cliente/citas/{id}/cancelar`) ya exige `role:cliente`, y desde 028 (v0.6.0) también las dos rutas de reprogramar; el resto del grupo → spec 015.

## Fuera de alcance
- ~~Reprogramar una cita (hay que cancelar y agendar de nuevo).~~ Lo añade la 028 (v0.6.0), con 24 h o más de antelación.
- Recordatorios.

## Seguridad y privacidad
- Datos sensibles involucrados: notas del paciente, historial de citas propio.
- Quién puede hacer qué: cliente → solo sus citas.
- Casos de abuso: CA6, CA7.

## Auditoría
Desde 026 (v0.3.0) la cancelación del paciente deja una fila en `historial_citas` con su autor, y
desde 028 también la reprogramación. Ver `docs/observability.md`.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos

## Notas para /plan
- La regla de agenda ya vive en un solo sitio, `app/Agenda/ReservaCitas.php` (spec 025).

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:152-166`, `app/Http/Controllers/ClienteCitasController.php`, `resources/views/citas/cliente-index.blade.php`, `cliente-show.blade.php`, `cliente-agendar.blade.php`
- Tests: `AltaCitasFlujosTest` (CA3), `EstadosCitaRolesTest` (CA6)

## Observaciones (solo si status=inferred)
- `cliente-agendar.blade.php` limita la hora entre 08:00 y 20:00 en HTML, mientras la regla real depende de `horarios_dentista`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-10-02 | Comportamiento modificado por 025 en v0.2.0 | /release |
| 2026-10-02 | Comportamiento modificado por 026 en v0.3.0 | /release |
| 2026-10-11 | Comportamiento modificado por 028 en v0.6.0 | /release |
| 2026-10-18 | Criterios con test marcados; alcance, Auditoría y Notas al día con 025, 026 y 028 | /init (re-sincronizar) |
