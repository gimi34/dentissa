---
id: 006
slug: pacientes
status: approved
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
- [ ] CA1 · Dado personal (admin, dentista, asistente), cuando abre el directorio, entonces ve los pacientes activos con búsqueda y filtros por ciudad y dentista, 20 por página. → reemplazado por 014 CA1, CA2 (cada rol, su alcance)
- [ ] CA2 · Dado un admin o asistente, cuando da de alta un paciente, entonces se crean su `usuarios` (rol cliente) y su `pacientes` en una transacción; si no indica contraseña se genera una y se muestra una sola vez.
- [ ] CA3 · Dada la ficha de un paciente, cuando se abre, entonces muestra sus datos, expedientes, últimas 10 citas, estadísticas y próxima cita.
- [ ] CA4 · Dado un admin o dentista, cuando edita un paciente, entonces actualiza datos demográficos, clínicos y el dentista asignado; un asistente recibe 403. → reemplazado por 014 CA3 y CA15 (fuera del alcance, 404; el doctor, solo el admin o el asignado)
- [ ] CA5 · Dado un admin, cuando desactiva o reactiva un paciente (`PATCH /admin/pacientes/{id}/toggle`), entonces cambia `usuarios.activo` sin borrar datos.
- [x] CA6 (abuso) · Como dentista, abro la ficha de un paciente que no es mío (ni asignado ni con cita conmigo) → 403. resuelto por 014 (v0.29.0) → reemplazado por 014 CA3 (responde 404, sin confirmar que existe)
- [x] CA7 (abuso) · Como asistente, listo pacientes → solo veo los de mis dentistas asignados. resuelto por 014 (v0.29.0)
- [x] CA8 (abuso) · Como paciente desactivado con sesión abierta, sigo navegando → se cierra la sesión. Resuelto por la ficha 2026-11-17-fix-sesion-cuenta-desactivada (v0.20.1).

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

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | cierre | ¿Esta spec documenta bien el sistema actual? | Validada y aprobada | usuario |
| 2026-11-20 | contradicción | CA6 y CA7 se iban a corregir en esta spec, pero la 014 (aprobada, con plan, `extends: [006]`) ya los cubre en sus CA1–CA3 | Se corrigen en la 014; esta spec no tiene trabajo propio pendiente | usuario |

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:62-68,113-116,138-141`, `app/Http/Controllers/PacientesController.php`, `resources/views/pacientes/*.blade.php`
- Tests (parciales): `CiudadListaCerradaTest` (alta y edición con la ciudad, filtro por ciudad del directorio y 403 del asistente al editar, CA4), `PacientesBotonAltaTest` (enlaces al alta por rol), `MovilPacientesExpedientesTest` (ficha y formularios), `HoraClinicaPanelesTest` y `HoraClinicaFechasSistemaTest` (próxima cita, edad y fechas de la ficha), `CuentaDesactivadaSesionTest` y `AdminNoSeDesactivaTest` (desactivar, CA5 y CA8)

## Observaciones (solo si status=inferred)
- La contraseña generada viaja en el flash de sesión y se interpola en un `onclick` del layout.
- El alta por personal exige `min:6` en la contraseña; el registro público exige 8.
- ~~Import `Carbon` sin usar en `PacientesController.php`~~: ya no está.
- Comportamiento definido después por otras specs, que manda sobre esta: botón de alta solo para admin y asistente (034), migas de pan (046), próxima cita, edad y fechas en la hora de la clínica (050) y ciudad de una lista cerrada, también en el filtro del directorio (051).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-11-17 | Corregido por la ficha 2026-11-17-fix-sesion-cuenta-desactivada en v0.20.1 (CA8): desactivar un paciente cierra su sesión abierta | /release |
| 2026-11-18 | Comportamiento modificado por 050 en v0.22.0 | /release |
| 2026-11-19 | Comportamiento modificado por 051 en v0.23.0: la ciudad se elige de una lista cerrada | /release |
| 2026-11-20 | Validada con el sistema actual: evidencia y tests al día, observaciones revisadas y destino de cada «HOY NO SE CUMPLE» | /specify --edit |
| 2026-11-20 | Aprobada; CA6 y CA7 se corrigen en la spec 014 | Decisión del usuario |
| 2026-11-22 | Comportamiento modificado por 014 en v0.29.0: CA1 y CA4 reemplazados (014 CA1–CA3, CA15); CA6 y CA7 resueltos (CA6 responde 404) | /release |
