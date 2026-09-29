---
id: 007
slug: expedientes-clinicos
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 007 · Expedientes clínicos

## Problema
Cada consulta debe dejar un registro clínico (diagnóstico, tratamiento, receta, observaciones y
próxima visita) ligado a la cita y al paciente, consultable por el equipo que lo atiende.

## Historias de usuario
- Como dentista, quiero registrar el diagnóstico y el tratamiento de una consulta.
- Como dentista, quiero consultar los expedientes que he firmado.
- Como asistente, quiero ver un expediente en modo lectura para preparar la siguiente visita.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un dentista, cuando abre `GET /dentista/expedientes`, entonces solo ve los expedientes que él firmó, con búsqueda por paciente o diagnóstico.
- [ ] CA2 · Dada una cita `completada`, `en_curso` o `confirmada` sin expediente, cuando un admin o dentista crea el expediente, entonces se guarda con diagnóstico obligatorio; un asistente recibe 403.
- [ ] CA3 · Dado un expediente, cuando un admin o dentista lo edita, entonces se actualizan diagnóstico, tratamiento, receta, observaciones y próxima visita.
- [ ] CA4 · Dado un asistente, cuando abre un expediente, entonces lo ve en solo lectura.
- [ ] CA5 · Dado un expediente con `proxima_visita`, cuando se agenda la siguiente cita del paciente, entonces la fecha se precarga (spec 003).
- [ ] CA6 (abuso) · Como dentista, edito el expediente de otro dentista cambiando el id → 403. **HOY NO SE CUMPLE: `update` no comprueba propiedad.**
- [ ] CA7 (abuso) · Como dentista o asistente, abro un expediente de un paciente fuera de mi alcance → 403. **HOY NO SE CUMPLE: `show` no comprueba alcance.**
- [ ] CA8 (abuso) · Como dentista, creo un expediente a nombre de otro dentista o para una cita de otro paciente → se rechaza. **HOY NO SE CUMPLE: `store` acepta `dentista_id`, `paciente_id` y `cita_id` arbitrarios.**

## Fuera de alcance
- Archivos adjuntos (`archivos_url` siempre se guarda como `null`).
- Versionado de cambios del expediente.

## Seguridad y privacidad
- Datos sensibles involucrados: datos de salud (diagnóstico, tratamiento, receta, observaciones).
- Quién puede hacer qué: dentista autor → crear, leer, editar; admin → todo; asistente → leer.
- Casos de abuso: CA6, CA7, CA8.

## Auditoría
Hoy `update` sobrescribe el expediente sin guardar quién ni cuándo (la tabla solo tiene
`creado_en`); no se registran lecturas. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos
- Solo el dentista autor (o un admin) debe editar un expediente (regla confirmada por el usuario, 2026-09-28).

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:71-74,118-122,143`, `app/Http/Controllers/ExpedientesController.php`, `resources/views/expedientes/*.blade.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- El comentario "El dentista solo ve los expedientes que él mismo registró" (`ExpedientesController.php:29`) solo se cumple en `index`.
- `exists:personal,id` no exige `tipo=dentista`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
