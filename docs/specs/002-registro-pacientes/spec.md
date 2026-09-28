---
id: 002
slug: registro-pacientes
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 002 · Autorregistro de pacientes

## Problema
Los pacientes nuevos necesitan crear su cuenta sin depender del personal de la clínica y quedar
asignados a un dentista para poder agendar.

## Historias de usuario
- Como paciente nuevo, quiero registrarme con mis datos para agendar citas en línea.
- Como paciente que entró con Google, quiero completar mis datos de contacto para terminar mi alta.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [ ] CA1 · Dado un visitante, cuando completa `POST /registro` con datos válidos, entonces se crean en una transacción un `usuarios` con rol `cliente` y su fila en `pacientes`, y se inicia sesión.
- [ ] CA2 · Dado el alta, cuando se crea el paciente, entonces se le asigna el dentista activo con menos pacientes (desempate por antigüedad).
- [ ] CA3 · Dado un email ya registrado, cuando se envía el registro, entonces se rechaza con un error de validación.
- [ ] CA4 · Dado un visitante que viene del callback de Google sin cuenta, cuando completa `POST /registro/contacto` (teléfono, datos demográficos y términos), entonces se crea el usuario sin contraseña y su paciente.
- [ ] CA5 · Dado el alta, cuando se crea la cuenta, entonces el rol es siempre `cliente` (fijado en el servidor, no viene del formulario).
- [ ] CA6 · Dada una contraseña de menos de 8 caracteres, cuando se envía el registro, entonces se rechaza.
- [ ] CA7 (abuso) · Como bot, envío cientos de registros → se limitan por IP. **HOY NO SE CUMPLE: no hay throttle ni captcha.**
- [ ] CA8 (abuso) · Como atacante, uso el registro para averiguar qué emails tienen cuenta → la respuesta no lo revela. **HOY NO SE CUMPLE: mensaje explícito de correo ya registrado.**

## Fuera de alcance
- Verificación del correo electrónico.
- Alta de pacientes hecha por el personal (spec 006).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales (nombre, email, teléfono, fecha de nacimiento, dirección).
- Quién puede hacer qué: cualquier visitante puede registrarse solo como cliente.
- Casos de abuso: CA7, CA8.

## Auditoría
Hoy no se registra el alta de cuentas. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Contraseña con `Password::min(8)`.

## Preguntas abiertas

## Supuestos
- La asignación automática ignora dentistas inactivos.

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:20-24`, `app/Http/Controllers/RegisterController.php` (incluye `asignarDentistaAutomatico`), `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`
- Tests: sin tests

## Observaciones (solo si status=inferred)
- `register.blade.php` marca `terminos` como `required` en HTML, pero `register` no lo valida en el servidor; `contactoStore` sí.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
