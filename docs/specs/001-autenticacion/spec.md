---
id: 001
slug: autenticacion
status: inferred
confidence: alta
created: 2026-09-28
extends: []
---

# 001 · Autenticación (correo, Google y cierre de sesión)

## Problema
Todo el personal (admin, dentista, asistente) y los pacientes (rol cliente) necesitan entrar al
sistema con una identidad propia y llegar al panel de su rol. Los pacientes pueden usar su cuenta
de Google para no recordar otra contraseña.

## Historias de usuario
- Como usuario registrado, quiero iniciar sesión con mi correo y contraseña para acceder a mi panel.
- Como paciente, quiero entrar con Google para no gestionar otra contraseña.
- Como usuario, quiero cerrar sesión para que nadie use mi cuenta en un equipo compartido.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [x] CA1 · Dado un usuario activo con contraseña, cuando envía `POST /login` con credenciales correctas, entonces se regenera la sesión y se le redirige al panel de su rol.
- [ ] CA2 · Dado un usuario con `activo=0`, cuando envía credenciales correctas, entonces el login se rechaza con un error en el formulario.
- [ ] CA3 · Dadas credenciales incorrectas, cuando se envía el login, entonces vuelve al formulario con error y sin sesión.
- [x] CA4 · Dado un visitante sin sesión, cuando abre `/`, entonces se le redirige a `/login`.
- [ ] CA5 · Dado un usuario autenticado, cuando abre `/`, entonces se le redirige al panel de su rol (`/admin`, `/dentista`, `/asistente` o `/cliente`).
- [ ] CA6 · Dado un usuario con `google_id` vinculado, cuando completa el callback de Google, entonces inicia sesión y va a su panel.
- [ ] CA7 · Dado un correo de Google sin cuenta, cuando completa el callback, entonces sus datos se guardan en sesión y se le envía a `/registro/contacto` (spec 002).
- [ ] CA8 · Dado un usuario autenticado, cuando envía `POST /logout`, entonces la sesión se invalida y el token CSRF se regenera.
- [ ] CA9 (abuso) · Como atacante, pruebo muchas contraseñas contra una cuenta → los intentos se limitan por IP y cuenta y se registran. **HOY NO SE CUMPLE: no se registran los intentos.** Atendido en parte por 030 (v0.5.0): los intentos fallidos se limitan por cuenta e IP (5 por minuto, `LimiteIntentosTest`); falta registrarlos (→ spec 020).
- [ ] CA10 (abuso) · Como atacante con una cuenta de Google cuyo email coincide con la de un admin, intento entrar → no se vincula sin email verificado ni confirmación. **HOY NO SE CUMPLE: se vincula por coincidencia de email.**
- [ ] CA11 (abuso) · Como usuario desactivado con sesión abierta, sigo navegando → la sesión se cierra. **HOY NO SE CUMPLE: `activo` solo se comprueba al hacer login.**

## Fuera de alcance
- Recuperación de contraseña (el enlace de la pantalla de login es `href="#"`).
- Verificación de correo y doble factor.

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales (hash de contraseña), email, `google_id`.
- Quién puede hacer qué: cualquiera puede intentar login; solo usuarios activos entran.
- Casos de abuso: CA9, CA10, CA11 (ver riesgos en `docs/security.md`).

## Auditoría
Hoy no se registra ningún evento (ni login exitoso, ni fallido, ni logout, ni acceso con Google).
Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Hash bcrypt (`Hash::make`), `BCRYPT_ROUNDS=12` declarado.

## Preguntas abiertas

## Supuestos
- El guard `web` usa `App\Models\Usuario`; `App\Models\User` es un resto del esqueleto sin uso.

## Notas para /plan
- La contraseña se lee de `usuarios.password_hash` mediante `getAuthPassword()`.

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:17-41`, `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/GoogleAuthController.php`, `config/auth.php`, `config/services.php`, `resources/views/auth/login.blade.php`
- Tests: `tests/Feature/AdminVistasSmokeTest.php::test_un_usuario_creado_con_contrasena_puede_iniciar_sesion` (CA1), `tests/Feature/ExampleTest.php::test_anonymous_visitor_is_redirected_to_login` (CA4)

## Observaciones (solo si status=inferred)
- `POST /logout` no exige el middleware `auth`.
- Las cuentas creadas con Google tienen `password_hash` nulo (migración `2026_07_18_195924`), pero el volcado lo declara `NOT NULL`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-10-04 | Comportamiento modificado por 030 en v0.5.0 | /release |
