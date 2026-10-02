---
id: 005
slug: horarios-dentista
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 005 · Horario semanal de los dentistas

## Problema
Para agendar citas hay que saber qué días y en qué franja atiende cada dentista. Sin horario, no
se puede agendar con él.

## Historias de usuario
- Como admin, quiero definir el horario semanal de cada dentista para que la agenda solo acepte citas en esas franjas.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un admin en la ficha de un dentista, cuando guarda el horario (`POST /admin/personal/{id}/horarios`), entonces se borran los horarios anteriores y se inserta uno por día marcado, con `disponible=1`.
- [ ] CA2 · Dado un dentista, cuando tiene como máximo un horario por día (1 = lunes … 7 = domingo), entonces la restricción única (personal_id, dia_semana) lo garantiza.
- [ ] CA3 · Dado un dentista sin horario para un día, cuando se intenta agendar ese día, entonces la cita se rechaza (specs 003 y 004). → reemplazado por 025 CA2
- [ ] CA4 (abuso) · Como admin, envío un horario con hora de fin anterior a la de inicio o un día inexistente → se rechaza con error de validación. **HOY NO SE CUMPLE: `dias[*]` no se valida.**

## Fuera de alcance
- Excepciones por fecha (vacaciones, festivos) y varias franjas por día.

## Seguridad y privacidad
- Datos sensibles involucrados: No aplica (datos operativos).
- Quién puede hacer qué: solo admin define horarios.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos
- El volcado parece no traer filas en `horarios_dentista`; en una base nueva hay que definir horarios antes de agendar.

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:84`, `app/Http/Controllers/PersonalController.php` (`saveHorarios`), `resources/views/personal/show.blade.php`, `database/dentissa.sql` (`horarios_dentista`)
- Tests: sin tests

## Observaciones (solo si status=inferred)
- El guardado borra e inserta sin comprobar citas ya agendadas fuera del nuevo horario.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-10-02 | Comportamiento modificado por 025 en v0.2.0 | /release |
