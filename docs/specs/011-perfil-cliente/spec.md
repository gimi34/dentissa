---
id: 011
slug: perfil-cliente
status: implemented
created: 2026-09-28
extends: []
---

# 011 · Perfil y contraseña del paciente

## Problema
Los pacientes necesitan mantener al día sus datos personales y clínicos básicos, ver qué dentista
tienen asignado y gestionar su contraseña (también si entraron con Google).
Esta spec atiende parcialmente el riesgo del área del paciente sin control de rol y el de las
sesiones al cambiar la contraseña (`docs/security.md`, RS2 y RS9; ver «Cobertura de riesgos»).

## Historias de usuario
- Como paciente, quiero actualizar mis datos de contacto y salud.
- Como paciente que entró con Google, quiero establecer una contraseña para entrar también con correo.

## Criterios de aceptación
<!-- Spec inferida: [x] = cubierto por un test existente; [ ] = comportamiento observado en el código, sin test. -->
- [x] CA1 · Dado un paciente, cuando abre `/cliente/perfil`, entonces ve sus datos y su dentista asignado. (`PerfilClienteTest`)
- [x] CA2 · Dado un paciente, cuando actualiza su perfil, entonces se guardan los datos en `usuarios` y `pacientes`; si no existía su fila de paciente, se crea. (`PerfilClienteTest`)
- [x] CA3 · Dado un paciente con contraseña, cuando la cambia, entonces debe indicar la actual; la nueva exige `Password::min(8)`. (`ContrasenaDistintaTest`)
- [x] CA4 · Dado un paciente sin contraseña (cuenta de Google), cuando establece una, entonces no se le pide la actual. (`ContrasenaDistintaTest::test_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil`)
- [x] CA5 (abuso) · Como alguien con una sesión robada, cambio el email de la cuenta → se pide la contraseña actual. resuelto por 041 (v0.14.0), CA13 (`PerfilCambioCorreoTest`)
- [x] CA6 (abuso) · Como paciente, cambio mi contraseña → el resto de sesiones abiertas se cierran; la actual sigue abierta con otro identificador. Corregido en esta spec (decisión del usuario del 2026-11-20; `PerfilClienteTest`).
- [x] CA7 (abuso) · Como admin o personal, guardo `/cliente/perfil` → se rechaza (403) y no se crea ninguna ficha de paciente. Corregido en esta spec (decisión del usuario del 2026-11-20; el cambio de contraseña ya lo exigía desde la 043; `PerfilClienteTest`).

## Fuera de alcance
- Foto de perfil (`foto_url` existe sin uso).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales y de salud del propio paciente, credenciales.
- Quién puede hacer qué: cliente → solo su perfil.
- Casos de abuso: CA5, CA6, CA7.
- Fuera de esta spec: alguien con una sesión robada de una cuenta que entra solo con Google puede crear
  una contraseña sin dar la actual (CA4) y, con CA6, cerrar las sesiones del dueño. Se resuelve con la
  vinculación segura de las cuentas de Google (spec 018).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS2.a — exigir el rol de paciente en todas sus pantallas | fuera, parcialmente atendida | Guardar el perfil lo exige (CA7) y el cambio de contraseña ya lo exigía (043); abrir el perfil y el resto de las pantallas del paciente → spec 015 (decisión del usuario, 2026-11-20) |
| RS2.b — no crear ficha de paciente desde el perfil de quien no es paciente | dentro | CA7 |
| RS9.a — cerrar las sesiones y renovar la actual al cambiar la contraseña desde el perfil | dentro | CA6 |
| RS9.b — pedir la contraseña actual para cambiar el correo | dentro | CA5, ya cumplido por la 041; lo que queda (cuentas de Google con una sesión ajena) → spec 018 |

## Auditoría
Hoy no se registran cambios de perfil ni de contraseña. Eventos requeridos en `docs/observability.md`.
Esta spec no añade eventos (excepción a la constitución P7, decisión del usuario del 2026-11-20): los
cambios de contraseña → spec 021, los cambios del perfil → spec 022, y los cierres de sesión forzados
por CA6, con el registro pendiente de entradas y salidas (como los de la recuperación de la 041). Lo
que sí queda registrado es el rechazo de CA7, como acceso denegado.

## Requisitos no funcionales
- Ninguno declarado.

## Preguntas abiertas

## Supuestos

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | cierre | ¿Esta spec documenta bien el sistema actual? | Validada y aprobada | usuario |
| 2026-11-20 | brecha | ¿Los criterios que hoy no se cumplen se corrigen en esta spec o en una aparte? | En esta spec: quedan `[ ]` y pasan por `/plan` | usuario |
| 2026-11-20 | brecha | ¿Qué correcciones de los riesgos del área del paciente y de las sesiones cubre la spec? | Las de «Cobertura de riesgos» | usuario (`/analyze` A3) |
| 2026-11-20 | brecha | ¿Crear la primera contraseña de una cuenta de Google también cierra las otras sesiones (CA6)? | Sí; el caso de una sesión robada queda fuera → spec 018 | usuario (`/analyze` A4) |
| 2026-11-20 | contradicción | La constitución P7 pide auditar cambios de contraseña, cambios de pacientes y cierres de sesión; la spec no añade eventos | Excepción P7 → specs 021 y 022 y el registro de entradas y salidas, incluidos los cierres forzados por CA6 | usuario (`/plan` D7, `/analyze` A5) |

## Notas para /plan

## Evidencia (solo si status=inferred)
- Archivos: `routes/web.php:212-215`, `app/Http/Controllers/PerfilController.php`, `resources/views/cliente/perfil.blade.php`
- Tests: `PerfilClienteTest` (CA1, CA2, CA6, CA7), `PerfilCambioCorreoTest` (CA5), `ContrasenaDistintaTest` (cambio de contraseña desde el perfil, CA3 y CA4), `CiudadListaCerradaTest` (ciudad del perfil) y `HoraClinicaFormulariosTest` (fecha de nacimiento)

## Observaciones (solo si status=inferred)
- Comportamiento definido después por otras specs, que manda sobre esta: el correo solo cambia con la contraseña actual (041), la contraseña nueva distinta de la actual y `role:cliente` en el cambio de contraseña (043), fecha de nacimiento anterior al día de la clínica (050) y ciudad de una lista cerrada (051).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Spec inferida del código | /init |
| 2026-11-07 | Comportamiento modificado por 043 en v0.14.1: la contraseña nueva del perfil tiene que ser distinta de la actual (amplía CA3), y CA8 de la 043 exige `role:cliente` solo en `PUT /cliente/perfil/password`; CA7 (`PUT /cliente/perfil`) sigue pendiente (→ spec 015) | /release |
| 2026-11-18 | Comportamiento modificado por 050 en v0.22.0 | /release |
| 2026-11-19 | Comportamiento modificado por 051 en v0.23.0: la ciudad se elige de una lista cerrada | /release |
| 2026-11-20 | Validada con el sistema actual: evidencia y tests al día, observaciones revisadas y destino de cada «HOY NO SE CUMPLE» | /specify --edit |
| 2026-11-20 | Aprobada; los criterios que hoy no se cumplen se corrigen en esta spec | Decisión del usuario |
| 2026-11-20 | «Cobertura de riesgos» (RS2.a en parte, RS2.b, RS9.a, RS9.b), CA3 y CA4 marcados como cubiertos, evidencia al día, decisiones de D6 y de la excepción P7 | `/analyze` A3, A4, A5, A10 |
| 2026-11-20 | Implementada: CA6 (el cambio de contraseña cierra las otras sesiones) y CA7 (`role:cliente` al guardar el perfil); CA1 y CA2 con tests de caracterización | /implement |
| 2026-11-21 | Vuelve a `approved`: `/review` r1 pide corregir R1 y R2 (T023–T026) | /review |
| 2026-11-21 | Implementada de nuevo con las correcciones de `/review` R1 y R2 (T023–T026) | /implement |
