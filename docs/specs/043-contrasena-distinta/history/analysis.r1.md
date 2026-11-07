---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-04
spec_sha: 64bfb1673c0a
plan_sha: 0ba51a74c141
tasks_sha: 10a8293ade7a
---

# Análisis · 043 La contraseña nueva tiene que ser distinta de la actual

## Resumen
`fail`: A1 es crítico. El plan modifica `PUT /cliente/perfil/password`, que solo exige `auth`, y marca
P3 como cumplido sin una excepción aceptada. El usuario decide corregirlo con `role:cliente` en esa
ruta. A2 es alto: dos de las roturas que piden T020 y T021 no pueden hacer fallar su test. Los demás
hallazgos son de precisión y se corrigen en la misma vuelta. Análisis hecho por un subagente de
contexto limpio; verifiqué cada hallazgo contra el archivo que cita.
Conteo: CRÍTICA 1 · ALTA 1 · MEDIA 3 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 |
| Amenazas con control y test | 4 / 4 (TM3 con un test que no puede fallar, A2; TM4 sin rotura, A3) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P3) | CRÍTICA | `plan.md` Constitution Check P3; `tasks.md` P3; `routes/web.php:191` | P3 exige que todo endpoint modificado verifique el rol con middleware, con un test de permitido y otro de denegado. `PUT /cliente/perfil/password` se modifica y solo exige `auth` (RS2.a, 011 CA7); P3 está como ✅ sin excepción. La spec dice «el paciente cambia solo la suya», pero el personal llega por URL. | Decisión del usuario (2026-11-04): exigir `role:cliente` en esa ruta. `/specify --edit 043` (un criterio con el personal rechazado y «Cobertura de riesgos» con RS2.a en parte), `/plan 043 --fix A1` y `/tasks 043 --fix A1`. Test: un cliente cambia su contraseña y admin, dentista y asistente reciben 403 sin que cambie. |
| A2 | Cobertura (P2) | ALTA | `tasks.md` T020 (TM3, CA4), T021 (CA4); `plan.md` Estrategia de pruebas | Dos roturas no cambian el comportamiento. `Usuario::find($cuenta)` es la misma cuenta que validó `cuentaDelEnlace()`, y una `cuenta` ajena ya se rechaza antes como enlace caducado. `Hash::check($v, null)` devuelve `false`, así que quitar la condición de `password_hash` no rompe nada. | Cada control lleva una rotura que hace fallar su test. TM3: con el token de A y `cuenta` = B y la contraseña de B como nueva, sale el enlace inválido y nunca «distinta»; la rotura es comparar antes de validar el enlace. CA4: declarar que lo garantiza `Hash::check` con `null` y elegir una rotura que falle. `/plan --fix A2` y `/tasks --fix A2`. |
| A3 | Seguridad (TM4, P8) | MEDIA | `plan.md` P8, TM4; `tasks.md` T020, T021 | `dontFlash` solo actúa cuando `validate()` lanza la excepción. Las respuestas nuevas son `redirect()->withErrors()` y `back()->withErrors()`, y lo que protege la contraseña es que no llevan `withInput()`. Ninguna tarea rompe TM4. | El control de TM4 es que las respuestas nuevas no conservan la entrada. Test: `…_la_contrasena_rechazada_no_queda_en_la_sesion` falla en los dos flujos al añadir `->withInput()`. `/plan --fix A3` y `/tasks --fix A3`. |
| A4 | Cobertura (P2) | MEDIA | `tasks.md` T021, T010 (CA3) | T021 no rompe los tests de CA3 y CA5 del perfil, que pasan desde el principio. La rotura «pasar a minúsculas» solo detecta algo si los datos de CA3 lo permiten. | Roturas de CA3 (normalizar la nueva) y CA5 (comparar antes de `validate()`) en el perfil, y datos fijos: actual en minúsculas y nueva que solo cambia una mayúscula. `/tasks --fix A4`. |
| A5 | Constitución (P9, WCAG) | MEDIA | `plan.md` D6; `tasks.md` T022; `cliente/perfil.blade.php:122-140` | Las etiquetas de «Cambiar contraseña» no tienen `for`, y «Contraseña actual» no tiene nombre accesible. El error quedaría asociado a campos sin nombre (WCAG 1.3.1, 4.1.2). | Cada campo de «Cambiar contraseña» tiene una etiqueta asociada. Test: el de accesibilidad de T011 comprueba `label[for]` hacia el `id` del campo, además de `aria-describedby`. `/plan --fix A5` y `/tasks --fix A5`. |
| A6 | Inconsistencia | BAJA | `plan.md` P4 | En el perfil, las reglas son `required`, `confirmed` y `Password::min(8)`, sin `max:255`, y la comparación va fuera de `validate()`. | Corregir la fila P4. `/plan --fix A6`. |
| A7 | Ambigüedad | BAJA | `spec.md` CA3 y su supuesto; `plan.md` D4 | bcrypt solo usa los primeros 72 bytes: una nueva que solo difiere después del byte 72 se rechaza como «igual», y eso contradice «difiere en cualquier carácter». | Alinear CA3 con «igual es la que deja entrar» y declarar el límite. `/specify --edit` y `/plan --fix A7`. Sin test propio. |
| A8 | Inconsistencia | BAJA | `plan.md` D4 | `TrimStrings` recorta `password_actual`: una cuenta cuya contraseña empieza o acaba con un espacio no puede acreditarla en el perfil. El fallo es anterior a la 043. | → roadmap (posible `/fix`). En T011, la actual de prueba no lleva espacios en los extremos. |
| A9 | Ambigüedad (P2) | BAJA | `tasks.md` T010, `…_los_rechazos_cuentan_para_el_limite_del_enlace` | La expectativa de hoy («pasa») depende de qué envíos hagan de rechazo: hoy, enviar la actual tiene éxito y consume el token. | Fijar que los rechazos son contraseñas cortas (el mismo limitador), que el test es de caracterización y que su rotura es quitar el `throttle`. `/tasks --fix A9`. |
| A10 | Cobertura | BAJA | `plan.md` Cambios por módulo (riesgo de la vista); T011, T022 | El riesgo de que el formulario de datos compita por el foco no se verifica. | Test: con un error de contraseña, la página tiene un solo `autofocus`. `/tasks --fix A10`. |
| A11 | Cobertura de riesgos | BAJA | `plan.md` P3, P7; spec, Fuera de alcance | La deuda aplazada no cita su ID (RS2.a, OB1.f, RS9.a). | RS2.a entra en «Cobertura de riesgos» con A1. OB1.f y RS9.a no se citan: el validador exigiría declarar todas sus correcciones y la spec ya los deja fuera con su destino (spec 021, objetivo 2). → spec. |

## Decisiones pendientes del usuario
- Ninguna. A1 decidido el 2026-11-04: exigir `role:cliente` en `PUT /cliente/perfil/password`.

## Aceptados
- **A8** → roadmap: `TrimStrings` recorta `password_actual` y `contrasena_correo` (anterior a la 043).
- **A11** → spec: RS2.a en «Cobertura de riesgos»; OB1.f y RS9.a siguen fuera con su destino, sin ID.
