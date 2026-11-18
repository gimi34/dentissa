---
id: 010
slug: usuarios-admin
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 010 · Administración de usuarios

## Problema
El admin necesita ver todas las cuentas, crear cuentas de cualquier rol, cambiar roles y
desactivar cuentas sin perder el historial clínico asociado.

## Historias de usuario
- Como admin, quiero buscar usuarios por texto y rol.
- Como admin, quiero crear una cuenta y cambiar su rol.
- Como admin, quiero desactivar una cuenta sin borrar sus datos.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [x] CA1 · Dado un admin, cuando abre el listado, entonces puede filtrar por texto y por rol; un filtro sin resultados muestra la pantalla vacía.
- [x] CA2 · Dado un admin, cuando crea un usuario con un correo ya existente, entonces se rechaza.
- [x] CA3 · Dado un alta sin contraseña, cuando se guarda, entonces se genera una aleatoria.
- [x] CA4 · Dado un usuario con rol asistente, cuando se edita parcialmente, entonces conserva su rol.
- [x] CA5 · Dado un admin, cuando desactiva un usuario, entonces es una baja lógica que conserva su expediente; la reactivación funciona.
- [x] CA6 · Dado un identificador sin forma de UUID, cuando se pide la ficha, entonces no resuelve a `show`; un usuario inexistente responde 404.
- [x] CA7 · Dado un usuario creado con contraseña, cuando inicia sesión, entonces entra (spec 001).
- [x] CA8 (abuso) · Como usuario desactivado con sesión abierta, sigo navegando → se cierra la sesión. Resuelto por la ficha 2026-11-17-fix-sesion-cuenta-desactivada (v0.20.1).
- [ ] CA9 (abuso) · Como admin, cambio el rol de alguien → queda registrado quién, a quién y de qué rol a cuál. **HOY NO SE CUMPLE: no hay auditoría de cambios de rol.**

## Fuera de alcance
- Borrado físico de cuentas (se retiró; ver test de rutas).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales y roles (permisos).
- Quién puede hacer qué: solo admin.
- Casos de abuso: CA8, CA9.

## Auditoría
Hoy no se registran altas, cambios de rol ni desactivaciones. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos

## Notas para /plan
- `Usuario::$fillable` incluye `rol`, `activo` y `password_hash`.

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:93-99`, `app/Http/Controllers/UsuarioController.php`, `app/Models/Usuario.php`, `resources/views/usuarios/*.blade.php`
- Tests: `tests/Feature/UsuariosAdminTest.php` (CA2–CA5), `tests/Feature/AdminVistasSmokeTest.php` (CA1, CA6, CA7), `tests/Feature/AdminUsuariosRutasTest.php` (CA6)

## Observaciones (solo si status=inferred)
- El resumen por rol del listado solo cuenta admin, dentista y cliente (falta asistente).
- `store` y `update` no muestran flash de éxito, a diferencia del resto.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-11-10 | Test de rutas cambiado por 044 en v0.15.0: con la ruta de reserva, un identificador sin forma de UUID no llega a `@show` y responde 404 (`AdminUsuariosRutasTest`, 044 /analyze A16) | /release |
| 2026-11-16 | Comportamiento modificado por 046 en v0.20.0: la ficha, el alta y la edición de usuario llevan las migas del parcial; «Cancelar» de «Editar usuario» vuelve a la ficha del usuario y no al listado | /release |
| 2026-11-17 | Corregido por la ficha 2026-11-17-fix-sesion-cuenta-desactivada en v0.20.1 (CA8): desactivar un usuario cierra su sesión abierta | /release |
| 2026-11-17 | Corregido por la ficha 2026-11-17-fix-admin-no-se-desactiva en v0.20.1: un admin no puede desactivarse ni quitarse el rol a sí mismo | /release |
| 2026-11-17 | Corregido por la ficha 2026-11-17-fix-detalles-servicios-usuarios en v0.20.1: tarjeta, filtro y grupo de asistentes; alta y edición avisan; un filtro sin resultados ofrece ver todos | /release |
