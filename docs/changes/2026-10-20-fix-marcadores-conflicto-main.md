---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: f096e17                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: marcadores de conflicto en main

## Problema
El merge `f096e17` («Merge branch 'main' of https://github.com/gimi34/dentissa», 2026-10-19),
subido a `main` sin PR junto con la landing, trae marcadores de conflicto sin resolver
(`<<<<<<< Updated upstream` / `=======` / `>>>>>>> Stashed changes`) en cinco controladores:
`AuthController` (4), `CitasController` (20), `ClienteCitasController` (14),
`DashboardController` (4) y `PersonalController` (4). PHP no puede analizar esos archivos: con
`main` así, el login, las citas, el panel y los horarios dan error 500 y la app no se puede
liberar (la 031 sale desde `main`). Producción sigue en v0.9.0 y no está afectada.

## Reproducción
`tests/Feature/CodigoSinConflictosTest.php` → `test_ningun_archivo_de_codigo_tiene_marcadores_de_conflicto`
recorre `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes` y `tests` (`.php`,
`.js`, `.css`). En rojo sobre `f096e17`:

```
⨯ ningun archivo de codigo tiene marcadores de conflicto
Hay conflictos de git sin resolver: app/Http/Controllers/AuthController.php: 6 marcadores;
app/Http/Controllers/CitasController.php: 30 marcadores; app/Http/Controllers/ClienteCitasController.php:
21 marcadores; app/Http/Controllers/DashboardController.php: 6 marcadores;
app/Http/Controllers/PersonalController.php: 6 marcadores
```

## Causa raíz
El commit `b1bd2dd` («Resolviendo conflictos de rutas», 2026-10-17, en el `main` local de un
colaborador) se hizo tras un `git stash pop` que chocó con el `main` remoto: git escribió en los
archivos las dos versiones entre marcadores (`Updated upstream` = `main` remoto, `Stashed changes` =
el código guardado en el stash, anterior a las specs 025–028 y 030) y se commitearon tal cual. Los
merges `92f9108` y `f096e17` los arrastraron hasta `origin/main`, que se subió directamente, sin PR,
así que la CI no lo paró. En los cinco archivos el merge **solo añadió** líneas (281 añadidas, 0
borradas respecto de `3eb64a6`): el lado `Updated upstream` está intacto.

## Cambio
Los cinco controladores vuelven a su versión de `3eb64a6` (el `main` anterior al merge, igual al
liberado en v0.9.0 en esos archivos): se quitan las 281 líneas añadidas (marcadores y código del
stash). El resto del merge (landing, imágenes, ruta `/`, formato de Pint) no se toca. Test nuevo
`CodigoSinConflictosTest`. Commit `fix(fix-marcadores-conflicto-main): …` con `Ficha:` en el cuerpo.

## Escalado
- Al empezar: `lane-check` → 6 archivos, 5 de código; «seguridad?» por el nombre de
  `AuthController.php`. Confirmado mirando el cambio: solo se quitan líneas que añadió el merge (un
  `Auth::attempt` sin el límite de intentos de la 030); el archivo queda como en v0.9.0. El usuario
  aceptó seguir en el carril (Decisiones).

## Verificación
- Línea base (`f096e17`): 344 tests, **227 fallan** y 117 pasan.
- Tras el arreglo: 344 tests, **342 pasan y 2 fallan**: `ProxyHttpsTest` →
  `test_detras_del_proxy_las_redirecciones_salen_en_https` y
  `test_un_x_forwarded_port_ajeno_no_cambia_el_puerto`. Ya fallaban en la línea base y no son de
  esta ficha: los causa la landing, que cambió `/` de redirigir al login a mostrar
  `landing.index` (Decisiones).
- `php -l` sin errores en los 5 controladores y el test; `pint --test` sobre lo tocado: PASS.
- Suite ejecutada en un contenedor de la imagen `dentissa-app` montando el worktree de la rama.
- semgrep `p/php` sobre lo tocado: 0 hallazgos. gitleaks (modo carpeta sobre lo tocado): sin fugas.
- `lane-check` al cerrar: el mismo «seguridad?» de `AuthController`, sin motivos nuevos.
- **Review ligera** (subagente `standard`, solo diff + ficha): sin bloqueantes.
  - Con el código revertido el test falla (`1 failed`) y con el arreglo pasa (`1 passed`).
  - `git diff 3eb64a6 a7bfdd4` está vacío en los 5 controladores, sin otros marcadores en el código y
    sin código del stash fuera de ellos (el resto del merge es Pint y la landing).
  - Menores aceptados (Decisiones): el test no mira `yml`/`json`/`md` ni `lang/`; `File::allFiles`
    falla si una carpeta de la lista desaparece; los 2 fallos de `ProxyHttpsTest` dejan la CI de
    `main` en rojo hasta que la landing entre por su spec o `/sync` (el merge sí adaptó
    `ExampleTest`, no `ProxyHttpsTest`).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | implícita | Ninguna spec define «el código PHP se puede analizar»; es un defecto evidente | Carril `/fix`, sin `relates` | el síntoma |
| 2026-10-20 | usuario | ¿Reparar `main` antes de traer la landing? | Sí: «repara main» | usuario |
| 2026-10-20 | supuesto | `lane-check` marca «seguridad?» por `AuthController`: ¿escalar? | Seguir en `/fix`: solo se quita lo que añadió el merge y el archivo vuelve a la versión revisada | usuario |
| 2026-10-20 | usuario | Los 2 fallos de `ProxyHttpsTest` vienen de la landing (`/` ya no redirige al login) | Fuera de esta ficha: se resuelven cuando la landing entre por su spec o `/sync`; `main` queda con esos 2 fallos | usuario |
| 2026-10-20 | supuesto | Review: ampliar el test a `yml`/`json`/`md`/`lang` y proteger carpetas inexistentes | No: el defecto fue en PHP; `docs/` cita marcadores a propósito y todas las carpetas existen. Arreglo mínimo | review ligera |
