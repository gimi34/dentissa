---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: c6359c2                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [001, 006, 008, 010] # specs cuyo comportamiento toca ([] si ninguna)
covers: [CA11, CA8, CA6]     # 001 CA11, 006 CA8, 008 CA6 y 010 CA8 (el validador no distingue la spec de cada CA)
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: una cuenta desactivada conserva su sesión abierta

## Problema
Si el admin desactiva a un usuario, a un miembro del personal o a un paciente que tiene la sesión
abierta, esa persona sigue navegando con su rol hasta que cierra la sesión o caduca: puede seguir
viendo pacientes, expedientes y citas. Lo exigen los casos de abuso 001 CA11, 006 CA8, 008 CA6 y
010 CA8 («sigo navegando → se cierra la sesión»), marcados «HOY NO SE CUMPLE» desde el /init
(2026-09-28). Es el riesgo RS3 de `docs/security.md` (alta, A07), corrección RS3.a.

## Reproducción
`tests/Feature/CuentaDesactivadaSesionTest.php`. La cuenta entra y su panel abre. El admin la
desactiva desde su propio navegador por la ruta de usuarios, de personal o de pacientes. La
siguiente petición de la cuenta debería volver a `/login`. Antes del arreglo, 4 en rojo y 2 en verde:

```
FAILED  CuentaDesactivadaSesionTest > desactivar a un paciente…
  Expected response status code [201, 301, 302, 303, 307, 308] but received 200.
  at tests/Feature/CuentaDesactivadaSesionTest.php:66
Tests:    4 failed, 2 passed (57 assertions)
```

Fallan los cuatro casos de desactivación: usuario (asistente), personal (dentista), paciente y otro
admin. Pasan los dos que no deben cambiar: las demás cuentas siguen con su sesión, y una cuenta
reactivada vuelve a entrar y sigue.

## Causa raíz
`activo` solo se comprueba al entrar: `Auth::attempt([... 'activo' => 1])` en `AuthController` y
`! $usuario->activo` en `GoogleAuthController`. En las peticiones siguientes, el guard de sesión
carga la cuenta por su id con el proveedor Eloquent, que no filtra por `activo`. Ningún middleware
del grupo `web` lo mira: `RoleMiddleware` comprueba el rol y `SesionVigente` (spec 041) compara
solo `version_sesion`, que sube al restablecer la contraseña y no al desactivar. Los tres
`toggleActivo` (usuarios, personal y pacientes) cambian `usuarios.activo` y nada más. Con la
sesión ya abierta, la cuenta desactivada sigue autenticada con su rol.

## Cambio
- `app/Http/Middleware/SesionVigente.php`: la sesión se cierra (logout, invalidar, regenerar el
  token y redirigir a `/login`) también cuando la cuenta cargada tiene `activo` en falso. Usa el
  mismo camino que el cierre por restablecimiento y el usuario ya cargado, sin consulta adicional.
  Cubre las tres pantallas de desactivar a la vez, porque todas cambian `usuarios.activo`, y
  también la casilla «Activo» de la edición de usuarios. Corresponde a la corrección RS3.a. RS3.b
  (borrar las sesiones al desactivar) deja de hacer falta para cerrar la sesión: la sesión de una
  cuenta inactiva se cierra en su siguiente petición.
- `tests/Feature/CuentaDesactivadaSesionTest.php`: 7 tests nuevos. Uno es el de la casilla
  «Activo» de la edición, añadido por la review ligera.
- `docs/security.md`: RS3.a pasa a «en curso» (será «mitigada» al liberarse) y RS3.b lleva una nota
  de defensa en profundidad.

## Escalado
`lane-check` al empezar (`SesionVigente.php` y el test): dos archivos, uno de código; no hay que
escalar. El motivo 4 (seguridad: autenticación) lo aceptó el usuario de forma expresa
(Decisiones).

## Verificación
- **Test nuevo:** 7 en verde (81 aserciones). Sin el arreglo fallan los 5 de desactivación con
  `received 200`; lo confirmaron la review ligera (4 de 6) y una pasada propia (el caso de la
  edición).
- **Tests del módulo:** el filtro `CuentaDesactivadaSesionTest|SesionVigenteTest|UsuariosAdminTest|Personal|Pacientes|Login|Auth`
  da 134 en verde.
- **Suite completa:** 1263 en verde (17476 aserciones, 659 s), sin fallos previos. Después solo se
  añadió el test de la edición.
- **Pint `--test`** sobre `SesionVigente.php` y el test: PASS.
- **Escáneres:** semgrep `p/php` sobre lo tocado, 0 hallazgos; gitleaks en modo carpeta sobre lo
  tocado, sin fugas. No se tocaron dependencias, así que no aplica la SCA.
- **Review ligera** (un subagente, solo el diff, la ficha y los criterios): aprobada, sin
  bloqueantes.
  - La causa raíz explica el síntoma y el arreglo la ataca en el middleware de cada petición del
    grupo `web`.
  - No hay más sitios con el defecto: no hay rutas con sesión fuera de `web`, «Recordarme» no emite
    cookie, Google y la recuperación ya filtran por `activo`, y no hay bucle en `/login`.
  - Hubo cuatro menores:
    1. Faltaba el test de la casilla «Activo»: añadido.
    2. Ambigüedad de RS3.b: nota en `security.md`.
    3. Cambios ajenos de `aidlc/` en la copia de trabajo: fuera del commit.
    4. Un admin puede desactivarse a sí mismo: es RS19.a, preexistente y fuera de alcance.
- `lane-check` al cerrar sobre los archivos del fix: no hay que escalar (solo el motivo 4, aceptado).
- `python .ai/bin/aidd.py validate`: sin errores.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | supuesto | El cambio toca la autenticación (lanes §3, motivo 4: seguridad); ¿escalar a spec? | Seguir en el carril `/fix`: el comportamiento ya lo definen 001 CA11, 006 CA8, 008 CA6 y 010 CA8 | usuario |
| 2026-11-17 | implícita | ¿A dónde va la cuenta inactiva al cerrarse su sesión? | A `/login`, sin aviso propio, igual que el cierre por restablecimiento de la 041. Al intentar entrar recibe el error genérico del login | agente |
| 2026-11-17 | supuesto | Review ligera, menor 4: un admin puede desactivarse a sí mismo | Fuera de alcance: es RS19.a, ya registrada en `docs/security.md` | agente |
