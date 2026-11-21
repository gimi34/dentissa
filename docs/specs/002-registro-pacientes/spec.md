---
id: 002
slug: registro-pacientes
status: implemented
created: 2026-09-28
extends: []
---

# 002 · Autorregistro de pacientes

## Problema
Los pacientes nuevos necesitan crear su cuenta sin depender del personal de la clínica y quedar
asignados a un dentista para poder agendar.
Esta spec atiende parcialmente el riesgo de enumeración de cuentas en el registro (`docs/security.md`, RS13; ver
«Cobertura de riesgos»).

## Historias de usuario
- Como paciente nuevo, quiero registrarme con mis datos para agendar citas en línea.
- Como paciente que entró con Google, quiero completar mis datos de contacto para terminar mi alta.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [x] CA1 · Dado un visitante, cuando completa `POST /registro` con datos válidos, entonces se crean en una transacción un `usuarios` con rol `cliente` y su fila en `pacientes`, y se inicia sesión. (`RegistroPacientesTest`)
- [x] CA2 · Dado el alta, cuando se crea el paciente, entonces se le asigna el dentista activo con menos pacientes (a igualdad, el que lleva más tiempo en el personal de la clínica); un dentista inactivo nunca se asigna, y si no hay ninguno activo el alta se completa y el paciente queda sin dentista asignado. (`RegistroPacientesTest`)
- [x] CA3 · Dado un email ya registrado, cuando se envía el registro, entonces se rechaza con un error de validación. (`RegistroPacientesTest`)
- [x] CA4 · Dado un visitante que viene del callback de Google sin cuenta, cuando completa `POST /registro/contacto` (teléfono, datos demográficos y términos), entonces se crea el usuario sin contraseña y su paciente. (`RegistroPacientesTest`)
- [x] CA5 · Dado el alta, cuando se crea la cuenta, entonces el rol es siempre `cliente` (fijado en el servidor, no viene del formulario). (`RegistroPacientesTest`)
- [x] CA6 · Dada una contraseña de menos de 8 caracteres, cuando se envía el registro, entonces se rechaza. (`RegistroPacientesTest`)
- [x] CA7 (abuso) · Como bot, envío cientos de registros → se limitan por IP. resuelto por 030 (v0.5.0)
- [x] CA8 (abuso) · Como atacante, uso el registro para averiguar qué emails tienen cuenta → el rechazo de un correo ya registrado no dice que ese correo tenga cuenta: muestra un mensaje que no afirma nada («No pudimos crear la cuenta con estos datos. Si ya tienes una, inicia sesión o recupera tu contraseña.»), con enlaces a iniciar sesión y a recuperar la contraseña, y conserva los demás datos escritos salvo la contraseña (la casilla de términos y aviso de privacidad vuelve sin marcar, porque el consentimiento se da en cada envío); el volumen de intentos sigue limitado (CA7). Corregido en esta spec (decisión del usuario del 2026-11-20 y del 2026-11-21; `RegistroPacientesTest`).

## Fuera de alcance
- Verificación del correo electrónico.
- Ocultar del todo si un correo tiene cuenta: con un correo nuevo el alta entra al panel y con uno registrado no, así
  que la diferencia sigue siendo visible; ocultarla exige confirmar el correo antes de crear la cuenta (spec futura,
  decisión del usuario del 2026-11-21).
- Avisar por correo al dueño de una cuenta cuando alguien intenta registrarse con su dirección (decisión del usuario
  del 2026-11-21).
- Alta de pacientes hecha por el personal (spec 006).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales (nombre, email, teléfono, fecha de nacimiento, dirección).
- Quién puede hacer qué: cualquier visitante puede registrarse solo como cliente.
- Casos de abuso: CA7, CA8.

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS13.a — limitar las altas por dirección | fuera | ya mitigada por la 030 (v0.5.0); CA7 la documenta |
| RS13.b — mensaje genérico en el alta, que no diga si el correo ya tiene cuenta | dentro | CA8 |
| RS13.c — no distinguir el alta de una cuenta nueva del rechazo de una existente (hoy una entra al panel y la otra no) | fuera | exige confirmar el correo antes de crear la cuenta → spec futura de confirmación del correo, objetivo 2 (decisión del usuario, 2026-11-21) |

## Auditoría
Hoy no se registra el alta de cuentas. Eventos requeridos en `docs/observability.md`.

## Requisitos no funcionales
- Contraseña con `Password::min(8)`.

## Preguntas abiertas

## Supuestos
- Ninguno (el de los dentistas inactivos pasó a CA2).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | cierre | ¿Esta spec documenta bien el sistema actual? | Validada y aprobada | usuario |
| 2026-11-20 | brecha | ¿Los criterios que hoy no se cumplen se corrigen en esta spec o en una aparte? | En esta spec: quedan `[ ]` y pasan por `/plan` | usuario |
| 2026-11-21 | contradicción | CA8 pedía que la respuesta no revelara si el correo tiene cuenta, pero el alta entra al panel al instante y la confirmación del correo está fuera de alcance: la diferencia sigue siendo visible | Mensaje neutro en el rechazo, con el límite de la 030; ocultarlo del todo → spec futura de confirmación del correo | usuario (`/plan`) |
| 2026-11-21 | brecha | ¿Se avisa por correo al dueño cuando alguien intenta registrarse con su dirección? | No | usuario (`/plan`) |
| 2026-11-21 | contradicción | La constitución P7 pide auditar las altas de cuentas; la spec no añade el evento | Excepción P7 → spec 021 (altas y cambios de cuentas), como en la 051 | usuario (`/plan` D6, `/analyze` A4) |
| 2026-11-21 | brecha | ¿Qué corrección de RS13 queda fuera? | RS13.b (el mensaje) dentro; RS13.c (no distinguir el alta nueva del rechazo) fuera → spec futura de confirmación del correo | usuario (`/analyze` A3) |
| 2026-11-21 | brecha | ¿Los dentistas inactivos y el alta sin ningún dentista activo son requisitos? | Sí, en CA2 (antes, un supuesto); sin dentistas activos el alta se completa sin dentista | usuario (`/analyze` A7) |
| 2026-11-21 | brecha | ¿La casilla de términos vuelve marcada tras un rechazo? | No: vuelve sin marcar | usuario (`/analyze` A5) |

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:20-24`, `app/Http/Controllers/RegisterController.php` (incluye `asignarDentistaAutomatico`), `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`
- Tests: `RegistroPacientesTest` (CA1–CA6, CA8), `CiudadListaCerradaTest` (los dos registros crean el usuario y su ficha de paciente con la ciudad elegida), `LimiteIntentosTest` (límite de altas, CA7), `TerminosPrivacidadTest` (sin la casilla no se crea la cuenta, en los dos registros), `HoraClinicaFormulariosTest` (fecha de nacimiento) y `NavegacionSalidasTest` (salir del registro con Google no crea cuenta)

## Observaciones (solo si status=inferred)
- ~~`register.blade.php` marca `terminos` como `required` en HTML, pero `register` no lo valida en el servidor~~: resuelto por 045 (v0.18.0), que lo valida en los dos registros.
- Comportamiento definido después por otras specs, que manda sobre esta: límite de altas por dirección (030), salidas del registro (044), casilla de términos y aviso de privacidad (045), fecha de nacimiento anterior al día de la clínica (050) y ciudad elegida de una lista cerrada (051).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-10-04 | Comportamiento modificado por 030 en v0.5.0 | /release |
| 2026-11-10 | Comportamiento modificado por 044 en v0.15.0: «Volver al inicio» y logo-enlace en el registro; con sesión, el registro lleva al panel del rol | /release |
| 2026-11-13 | Comportamiento modificado por 045 en v0.18.0: la casilla dice «aviso de privacidad», sus enlaces abren los textos en una ventana y `POST /registro` valida `terminos` en el servidor | /release |
| 2026-11-18 | Comportamiento modificado por 050 en v0.22.0 | /release |
| 2026-11-19 | Comportamiento modificado por 051 en v0.23.0: la ciudad se elige de una lista cerrada | /release |
| 2026-11-20 | Validada con el sistema actual: evidencia y tests al día, observaciones revisadas y destino de cada «HOY NO SE CUMPLE» | /specify --edit |
| 2026-11-20 | Aprobada; los criterios que hoy no se cumplen se corrigen en esta spec | Decisión del usuario |
| 2026-11-21 | CA8 reescrito (mensaje neutro, sin ocultar del todo), «Cobertura de riesgos» (RS13) y dos exclusiones en «Fuera de alcance» | Decisión del usuario en `/plan` |
| 2026-11-21 | CA2 (inactivos y sin dentistas), CA8 (casilla), RS13 partido en RS13.b y RS13.c, excepción P7 en Decisiones | `/analyze` A3, A4, A5, A7 |
| 2026-11-21 | Implementada: CA8 (mensaje neutro con enlaces); CA1–CA6 con tests de caracterización | /implement |
| 2026-11-21 | Vuelve a `approved`: `/review` r1 pide corregir R1–R9 (T023–T028) | /review |
| 2026-11-21 | Implementada de nuevo con las correcciones de `/review` r1 (T023–T028) | /implement |
