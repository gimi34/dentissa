---
id: 012
slug: paneles-por-rol
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 012 · Paneles de inicio por rol

## Problema
Cada rol necesita, al entrar, un resumen de lo más urgente de su día.

## Historias de usuario
- Como admin, quiero ver las citas de hoy y el total de pacientes activos.
- Como dentista, quiero ver mis citas de hoy, cuántos pacientes atendí este mes y mi próxima cita.
- Como asistente, quiero ver mis doctores asignados y su agenda de hoy.
- Como paciente, quiero ver mi próxima cita y las últimas que tuve.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un admin, cuando abre `/admin`, entonces ve el número y la lista de citas de hoy y el total de pacientes activos.
- [ ] CA2 · Dado un dentista, cuando abre `/dentista`, entonces ve sus citas de hoy, los pacientes atendidos en el mes y su próxima cita.
- [ ] CA3 · Dado un asistente, cuando abre `/asistente`, entonces ve sus dentistas asignados y la agenda de hoy de todos ellos.
- [ ] CA4 · Dado un paciente, cuando abre `/cliente`, entonces ve su próxima cita y sus 5 últimas citas pasadas.
- [ ] CA5 (abuso) · Como usuario de un rol, abro el panel de otro rol → 403. (Se cumple para admin, dentista y asistente por `role:`; **HOY NO SE CUMPLE para `/cliente`, que solo exige `auth`.**)

## Fuera de alcance
- Reportes (`/admin/reportes` es un placeholder "en construcción").

## Seguridad y privacidad
- Datos sensibles involucrados: nombres de pacientes y citas del día.
- Quién puede hacer qué: cada rol solo su panel.
- Casos de abuso: CA5.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:46,105,128,148`, `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard/*.blade.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- La vista SQL `v_citas_hoy` existe pero el panel no la usa (consulta propia).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
