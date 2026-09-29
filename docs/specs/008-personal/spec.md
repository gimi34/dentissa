---
id: 008
slug: personal
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 008 · Gestión del personal y asignación asistente↔dentista

## Problema
La clínica necesita dar de alta a dentistas, asistentes y administradores con su información
profesional, y decidir qué dentistas apoya cada asistente, porque de eso depende qué agenda ve.

## Historias de usuario
- Como admin, quiero registrar a un dentista con su especialidad y cédula.
- Como admin, quiero asignar dentistas a cada asistente.
- Como asistente, quiero ver a mis doctores asignados y su agenda de hoy.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un admin, cuando abre `/admin/personal`, entonces ve el personal agrupado por tipo (dentista, asistente, administrador).
- [ ] CA2 · Dado un admin, cuando da de alta personal, entonces se crean su `usuarios` (rol derivado de `personal.tipo`) y su `personal` en una transacción; si no indica contraseña se genera una.
- [ ] CA3 · Dado un asistente dado de alta o editado con `dentistas[]`, cuando se guarda, entonces la tabla `asistente_dentista` queda sincronizada con esa lista.
- [ ] CA4 · Dado un admin, cuando desactiva o reactiva personal, entonces cambia `usuarios.activo` sin borrar datos.
- [ ] CA5 · Dado un asistente, cuando abre `/asistente/doctores`, entonces ve sus dentistas asignados con sus citas de hoy.
- [ ] CA6 (abuso) · Como personal desactivado con sesión abierta, sigo navegando → se cierra la sesión. **HOY NO SE CUMPLE: la desactivación no invalida sesiones.**

## Fuera de alcance
- Horarios (spec 005).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales del personal, cambios de rol.
- Quién puede hacer qué: solo admin gestiona personal; asistente solo consulta sus doctores.
- Casos de abuso: CA6.

## Auditoría
Hoy no se registran altas, cambios de rol ni desactivaciones. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos
- `personal.tipo = administrador` equivale a `usuarios.rol = admin`.

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:77-84,130`, `app/Http/Controllers/PersonalController.php` (incluye `sincronizarDoctoresAsignados`, `misDoctores`), `resources/views/personal/*.blade.php`, `database/migrations/2026_07_20_000000_create_asistente_dentista_table.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- La contraseña generada viaja en el flash de sesión, igual que en la spec 006.
- `asistente_dentista` solo existe en migraciones, no en el volcado; la base de tests no la tiene.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
