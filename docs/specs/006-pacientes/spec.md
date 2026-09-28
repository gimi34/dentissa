---
id: 006
slug: pacientes
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 006 · Gestión de pacientes por el personal

## Problema
El personal necesita un directorio de pacientes con su ficha clínica (alergias, tipo de sangre,
enfermedades, medicación), su historial de citas y expedientes, y poder dar de alta pacientes que
no se registraron solos.

## Historias de usuario
- Como asistente o admin, quiero dar de alta a un paciente que llega a la clínica.
- Como dentista, quiero consultar la ficha clínica de mis pacientes antes de atenderlos.
- Como admin, quiero desactivar a un paciente sin perder su historial.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado personal (admin, dentista, asistente), cuando abre el directorio, entonces ve los pacientes activos con búsqueda y filtros por ciudad y dentista, 20 por página.
- [ ] CA2 · Dado un admin o asistente, cuando da de alta un paciente, entonces se crean su `usuarios` (rol cliente) y su `pacientes` en una transacción; si no indica contraseña se genera una y se muestra una sola vez.
- [ ] CA3 · Dada la ficha de un paciente, cuando se abre, entonces muestra sus datos, expedientes, últimas 10 citas, estadísticas y próxima cita.
- [ ] CA4 · Dado un admin o dentista, cuando edita un paciente, entonces actualiza datos demográficos, clínicos y el dentista asignado; un asistente recibe 403.
- [ ] CA5 · Dado un admin, cuando desactiva o reactiva un paciente (`PATCH /admin/pacientes/{id}/toggle`), entonces cambia `usuarios.activo` sin borrar datos.
- [ ] CA6 (abuso) · Como dentista, abro la ficha de un paciente que no es mío (ni asignado ni con cita conmigo) → 403. **HOY NO SE CUMPLE: index/show/edit/update no filtran por alcance.**
- [ ] CA7 (abuso) · Como asistente, listo pacientes → solo veo los de mis dentistas asignados. **HOY NO SE CUMPLE: ve a todos los pacientes activos.**
- [ ] CA8 (abuso) · Como paciente desactivado con sesión abierta, sigo navegando → se cierra la sesión. **HOY NO SE CUMPLE: la desactivación no invalida sesiones.**

## Fuera de alcance
- Borrado físico de pacientes.
- Adjuntos y estudios de imagen.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales y de salud (alergias, tipo de sangre, enfermedades crónicas, medicamentos, notas médicas).
- Quién puede hacer qué: admin → todo; dentista → leer y editar (hoy sin límite de alcance);
  asistente → listar, crear y leer (hoy sin límite de alcance).
- Casos de abuso: CA6, CA7, CA8.

## Auditoría
Hoy no se registran lecturas ni cambios de la ficha clínica. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos
- "Paciente del dentista" = asignado en `pacientes.dentista_id` o con alguna cita con él (regla confirmada por el usuario, 2026-09-28; aún no implementada).

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:62-68,113-116,138-141`, `app/Http/Controllers/PacientesController.php`, `resources/views/pacientes/*.blade.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- La contraseña generada viaja en el flash de sesión y se interpola en un `onclick` del layout.
- El alta por personal exige `min:6` en la contraseña; el registro público exige 8.
- Import `Carbon` sin usar en `PacientesController.php`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
