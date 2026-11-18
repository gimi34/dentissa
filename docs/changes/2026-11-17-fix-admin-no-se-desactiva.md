---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: c6359c2                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [008, 010]          # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # ninguna de las dos specs lo recoge como CA: lo define RS19.a de docs/security.md
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: un admin puede desactivarse o quitarse el rol a sí mismo

## Problema
Un admin puede desactivar su propia cuenta o quitarse el rol de administrador desde cuatro sitios:
- «Desactivar» en el listado de usuarios.
- «Desactivar» en la ficha del personal.
- La casilla «Activo» o el rol al editar su usuario.
- El tipo al editar su ficha del personal.

Con el fix de la sesión de las cuentas desactivadas, desactivarse lo saca de la aplicación al
momento. Si es el último admin activo, nadie puede volver a administrar la clínica sin tocar la
base de datos. Es el riesgo RS19 de `docs/security.md` (media, A01), corrección RS19.a, detectado
el 2026-10-18.

## Reproducción
`tests/Feature/AdminNoSeDesactivaTest.php`: un caso por cada una de las cuatro vías (el rol y la
casilla «Activo» de la edición de usuarios van por separado). Antes del arreglo fallan los cinco
porque la acción se acepta: sin error en la sesión, o con la redirección del éxito en vez de volver
al formulario:

```
⨯ no puede desactivarse desde el listado de usuarios
  Failed asserting that null matches expected 'No puedes desactivar tu propia cuenta.'.
⨯ no puede desmarcar activo al editar su usuario
⨯ no puede cambiarse el rol al editar su usuario
⨯ no puede desactivarse desde su ficha del personal
⨯ no puede cambiarse el tipo al editar su ficha del personal
Tests:    5 failed, 2 passed (24 assertions)
```

Los dos de control pasan: editar los propios datos sin tocar el rol ni «Activo», y desactivar o
degradar a otro admin.

## Causa raíz
`UsuarioController::update` y `toggleActivo`, y `PersonalController::update` y `toggleActivo`,
aplican el rol, el tipo o `activo` que llegan del formulario o del botón sin mirar si la cuenta
afectada es la del propio admin. El grupo `role:admin` solo comprueba quién hace la acción, no a
quién se la hace. Estos controladores vienen del código anterior al flujo, y la spec 010 (inferida)
nunca recogió esta regla. Se detectó como RS19 en la resincronización del 2026-10-18.

## Cambio
- `app/Http/Controllers/Controller.php`: los dos mensajes y dos ayudantes. `esLaPropiaCuenta()`
  compara con `auth()->id()`. `impedirQuitarsePropioAcceso()` lanza una `ValidationException` con
  los errores que apliquen, solo si la cuenta es la propia.
- `UsuarioController`:
  - `update` rechaza el rol distinto de `admin` (error en `rol`) y la casilla «Activo» sin marcar
    (error en `activo`) sobre la propia cuenta. El formulario ya muestra los errores en su
    `alerta-errores`.
  - `toggleActivo` devuelve `error` («No puedes desactivar tu propia cuenta.») si se trata de la
    propia cuenta activa.
- `PersonalController`:
  - `update` rechaza un tipo distinto de `administrador` en la propia ficha (error en `tipo`, que el
    formulario muestra bajo las tarjetas).
  - `toggleActivo` hace lo mismo que el de usuarios.
- `PacientesController::toggleActivo` hace lo mismo. Es la quinta vía, que encontró la review
  ligera: un admin puede tener ficha de paciente (`PerfilController::update` la crea a cualquier
  cuenta, RS2.b; o un cliente ascendido la conserva, RS19.c) y el listado de pacientes no filtra
  por rol.
- `tests/Feature/AdminNoSeDesactivaTest.php`: 8 tests nuevos.
- `docs/security.md`: RS19.a pasa a «en curso». RS19.b lleva una nota: con RS19.a ya no se puede
  dejar la aplicación sin admins desde la propia aplicación.

## Escalado
`lane-check` al empezar dio 3 archivos de código. Al cerrar, con Pacientes, da 6 archivos, 4 de
código (el límite es 5). No hay que escalar. Toca permisos
(motivo 4), y el usuario lo aceptó al pedir el fix.

## Verificación
- **Test nuevo:** 8 en verde (50 aserciones).
  - Sin el arreglo, los 5 primeros casos «no puede…» fallaron en la pasada de la review ligera
    (`5 failed, 2 passed`).
  - El de Pacientes falla sin su arreglo en una pasada propia (`Failed asserting that null matches
    expected 'No puedes desactivar tu propia cuenta.'`).
  - Los de control pasan con y sin arreglo.
- **Suite completa:** 1264 en verde (17450 aserciones) antes de añadir Pacientes. Con Pacientes,
  1265 en verde (17457 aserciones, 669 s), sin fallos.
- **Pint `--test`** sobre los 4 controladores y el test: PASS.
- **Escáneres:** semgrep `p/php` sobre lo tocado, 0 hallazgos; gitleaks en modo carpeta, sin
  fugas. No hay dependencias nuevas, así que no aplica la SCA.
- **Review ligera** (un subagente, solo el diff, la ficha y RS19.a): aprobada, sin bloqueantes.
  - La causa explica el síntoma y el arreglo compara la cuenta afectada (id leído de la base)
    con `auth()->id()` antes de escribir.
  - No hay otras vías: los altas exigen correo único, y el perfil, el registro y Google no tocan
    `rol` ni `activo`.
  - Hallazgos y qué se hizo:
    - M1 (vía de Pacientes): corregido.
    - N1 (P3, autorización con Gate): se documenta en Decisiones.
    - T1 (el control de la edición no comprobaba el guardado de usuarios por separado):
      corregido.
    - T2 (la frase de «Reproducción»): corregida.
    - Tras el error de rol, el formulario vuelve con «Dentista» seleccionado: aceptado, el mensaje
      lo explica.
    - El listado sigue ofreciendo «Desactivar» en la propia fila: aceptado, el servidor lo
      rechaza con el mensaje.
- `python .ai/bin/aidd.py validate`: sin errores.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | supuesto | El cambio toca permisos (lanes §3, motivo 4: seguridad) | Seguir en el carril `/fix`: el usuario lo pidió como fix tras proponerlo así | usuario |
| 2026-11-17 | implícita | ¿Qué comportamiento es el correcto? Ninguna spec lo recoge | El de RS19.a: un admin no puede quitarse el rol ni desactivarse a sí mismo | docs/security.md RS19.a |
| 2026-11-17 | supuesto | P3 y AGENTS.md piden la autorización sobre el recurso con Gates de `app/Autorizacion/` (403); ¿Gate o validación? | Validación con mensaje (302 de vuelta al formulario), en un ayudante del `Controller` base. No es un permiso del rol (el admin sí puede desactivar o degradar cuentas), sino una regla de negocio sobre la propia cuenta que la persona puede corregir en el formulario. Un 403 no le diría qué cambiar (review ligera, N1) | agente |
| 2026-11-17 | supuesto | La review ligera encontró una quinta vía (Pacientes); ¿en esta ficha o aparte? | En esta ficha: es el mismo defecto con el mismo arreglo, y sigue dentro del límite del carril (4 archivos de código) | agente |
