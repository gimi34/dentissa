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
- [ ] CA8 (abuso) · Como usuario desactivado con sesión abierta, sigo navegando → se cierra la sesión. **HOY NO SE CUMPLE: la desactivación no invalida sesiones.**
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
