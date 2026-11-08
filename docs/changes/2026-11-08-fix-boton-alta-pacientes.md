---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: cb9a0a0                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: botón de alta de pacientes con la ruta de otro rol

## Problema
En la lista de pacientes (`resources/views/pacientes/index.blade.php`):

- El botón «Nuevo paciente» de la barra superior lleva al **dentista** a
  `/asistente/pacientes/crear` (`asistente.pacientes.create`), que le responde 403 porque es del
  grupo `role:asistente`. El dentista no tiene ruta de alta de pacientes en `routes/web.php`.
- El botón «Registrar primer paciente» del estado vacío apunta siempre a `admin.pacientes.create`
  (`/admin/pacientes/crear`): 403 para el **dentista** y para el **asistente**, aunque el
  asistente sí tiene su propia ruta de alta.

## Reproducción
`tests/Feature/PacientesBotonAltaTest.php`. Salida en rojo antes del arreglo:

```
FAILED  PacientesBotonAltaTest > admin y asistente enlazan…
  asistente (vacía): el alta es la de su rol.
  -'/asistente/pacientes/crear'
  +'/admin/pacientes/crear'
FAILED  PacientesBotonAltaTest > el dentista no ve enlaces…
  dentista (lista): sin enlaces de alta.
  +    0 => '/asistente/pacientes/crear',
Tests:    2 failed (21 assertions)
```

## Causa raíz
Los dos enlaces vienen del commit inicial de los módulos (25ff32e, 2026-07-11) y no distinguen los
tres roles que comparten la vista. El de la barra superior usa una condicional binaria
(`admin` → ruta del admin; cualquier otro → ruta del asistente), que da por hecho que todo
no-admin es asistente; el grupo `dentista` de `routes/web.php` nunca tuvo `pacientes.create`. El del
estado vacío lleva fija la ruta del admin. Los dos enlaces llevan, por tanto, a un grupo con
`role:` ajeno, y `RoleMiddleware` responde 403.

## Cambio
- `resources/views/pacientes/index.blade.php`: un `@php` al principio calcula `$rutaAlta` con
  `match` sobre el rol (`admin` y `asistente` con su ruta de alta; el resto, `null`) y los dos
  botones («Nuevo paciente» y «Registrar primer paciente») se muestran solo si existe, enlazando a
  esa ruta.
- `tests/Feature/PacientesBotonAltaTest.php`: admin y asistente enlazan solo a su alta (lista con
  datos y estado vacío) y esa página responde 200; el dentista no ve ningún enlace de alta.
- Commit `fix(fix-boton-alta-pacientes): …` con `Ficha:` en el cuerpo.

## Escalado
- Al empezar (`--files` vista y test): `Escalar: no. El cambio cabe en el carril.` (1 archivo de código).
- Al cerrar, sobre el diff desde `base`: el script marca «seguridad: rutas protegidas», pero solo
  por archivos `aidlc/` sin commitear que no son de esta ficha (AI-DLC en pausa; no se tocan ni se
  commitean). Con los archivos del arreglo (`--files`): `Escalar: no.` El arreglo no toca rutas,
  middleware ni Gates: solo deja de mostrar enlaces que el servidor ya rechazaba.

## Verificación
- Tests ejecutados con `docker compose run --rm --no-deps -T app …`: los contenedores en marcha
  montan otro clon (`dentissa-041`) y no se tocaron.
- `PacientesBotonAltaTest`: `2 passed (27 assertions)`, dos ejecuciones seguidas.
- Suite completa: `721 passed (12189 assertions)`, sin fallos previos en la línea base.
- Pint (`--test`) sobre el test nuevo: PASS. La vista Blade no la cubre Pint.
- Review ligera (subagente, solo diff y ficha): sin bloqueantes.
  - Reversión: con la vista revertida y el test intacto, `2 failed`; con el arreglo, `2 passed`.
    Repetida con el test endurecido por H1: `2 failed (23 assertions)` sin el arreglo.
  - La causa raíz explica el síntoma y el arreglo la ataca. No hay más sitios con el mismo defecto:
    los otros enlaces por rol de `pacientes/show`, `citas/show` y `expedientes/show` están
    protegidos por rol o por Gate. El diff no toca rutas, middleware, datos, contratos,
    dependencias ni el sistema de diseño.
  - H1 (menor, corregido): en el estado vacío, el test exige los dos enlaces de alta y el texto del
    estado vacío para admin y asistente.
  - H2 (menor, aceptado): la vista calcula el rol dos veces (`$rutaAlta` arriba y `$base` en cada
    fila).
  - H3 (ficha sin terminar): la review leyó una versión anterior; ya está completa.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-08 | implícita | Ninguna spec dice qué botón de alta ve cada rol en la lista de pacientes | Cada rol solo ve enlaces a rutas de su grupo: admin y asistente ven el alta con su prefijo; el dentista, que no tiene ruta de alta, no ve el botón. Es un defecto evidente (enlace que siempre da 403) | síntoma + `routes/web.php` |
| 2026-11-08 | supuesto | Review H2: unificar el cálculo del rol de la vista (`$base` por fila y `$rutaAlta`) | Se acepta sin cambio: unificarlo es un refactor fuera del arreglo mínimo; si se quiere, va por `/refactor` | review ligera |
