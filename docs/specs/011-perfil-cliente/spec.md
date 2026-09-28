---
id: 011
slug: perfil-cliente
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 011 · Perfil y contraseña del paciente

## Problema
Los pacientes necesitan mantener al día sus datos personales y clínicos básicos, ver qué dentista
tienen asignado y gestionar su contraseña (también si entraron con Google).

## Historias de usuario
- Como paciente, quiero actualizar mis datos de contacto y salud.
- Como paciente que entró con Google, quiero establecer una contraseña para entrar también con correo.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un paciente, cuando abre `/cliente/perfil`, entonces ve sus datos y su dentista asignado.
- [ ] CA2 · Dado un paciente, cuando actualiza su perfil, entonces se guardan los datos en `usuarios` y `pacientes`; si no existía su fila de paciente, se crea.
- [ ] CA3 · Dado un paciente con contraseña, cuando la cambia, entonces debe indicar la actual; la nueva exige `Password::min(8)`.
- [ ] CA4 · Dado un paciente sin contraseña (cuenta de Google), cuando establece una, entonces no se le pide la actual.
- [ ] CA5 (abuso) · Como alguien con una sesión robada, cambio el email de la cuenta → se pide la contraseña actual. **HOY NO SE CUMPLE: el email se cambia sin reautenticación.**
- [ ] CA6 (abuso) · Como paciente, cambio mi contraseña → el resto de sesiones abiertas se cierran. **HOY NO SE CUMPLE: no se invalidan otras sesiones ni se regenera la actual.**
- [ ] CA7 (abuso) · Como admin o personal, guardo `/cliente/perfil` → se rechaza. **HOY NO SE CUMPLE: el grupo no exige `role:cliente` y se crea una ficha de paciente para ese usuario.**

## Fuera de alcance
- Foto de perfil (`foto_url` existe sin uso).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales y de salud del propio paciente, credenciales.
- Quién puede hacer qué: cliente → solo su perfil.
- Casos de abuso: CA5, CA6, CA7.

## Auditoría
Hoy no se registran cambios de perfil ni de contraseña. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:157-159`, `app/Http/Controllers/PerfilController.php`, `resources/views/cliente/perfil.blade.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
