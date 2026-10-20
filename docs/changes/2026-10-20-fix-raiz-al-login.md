---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: 9033a68                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [001]               # specs cuyo comportamiento toca ([] si ninguna)
covers: [CA4]                # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: `/` vuelve a llevar al inicio de sesión

## Problema
Desde el merge `f096e17` (2026-10-19, subido a `main` sin PR), `/` sin sesión sirve la página de
inicio del colaborador (`landing.index`) en lugar de redirigir a `/login`, como pide 001 CA4. Esa
página carga Tailwind desde un CDN que la política de contenido de la 031 bloquea, así que en
producción se vería sin estilos. Además deja en rojo 2 pruebas de `ProxyHttpsTest` (030 CA2) y
haría fallar «/ redirige a https» de `deploy/verificar.sh`. Mientras no lo arregle la spec 038,
`main` no se puede liberar (la 031 sale desde `main`).

## Reproducción
`tests/Feature/ExampleTest.php` → `test_anonymous_visitor_is_redirected_to_login` (el test de 001 CA4,
devuelto a su versión de `3eb64a6`), más las dos pruebas de `ProxyHttpsTest` que ya fallaban. En
rojo sobre `9033a68`:

```
⨯ anonymous visitor is redirected to login
⨯ detras del proxy las redirecciones salen en https
⨯ un x forwarded port ajeno no cambia el puerto
Expected response status code [201, 301, 302, 303, 307, 308] but received 200.
Tests: 3 failed, 5 passed
```

## Causa raíz
El merge `f096e17` cambió el final del closure de `/` en `routes/web.php`: pasó de
`return redirect('/login');` a `return view('landing.index');`. A la vez reescribió `ExampleTest`
para esperar la vista nueva. Fue un cambio de comportamiento (001 CA4) hecho fuera del flujo, sin
spec y sin PR, así que la CI no lo paró. La página que sustituye a esa redirección es la spec 038,
aún sin implementar.

## Cambio
- `routes/web.php` y `tests/Feature/ExampleTest.php` vuelven a su versión de `3eb64a6`:
  - `/` sin sesión redirige a `/login`;
  - con sesión, cada rol sigue yendo a su panel (sin cambios);
  - el test vuelve a esperar la redirección.
- La vista `resources/views/landing/index.blade.php` y sus imágenes (`cover-bg.jpg`,
  `logo-rosa.png`) quedan sin uso hasta que la 038 las retire (T027). No se borran aquí: arreglo
  mínimo.
- Commit `fix(fix-raiz-al-login): …` con `Ficha:` en el cuerpo.

## Escalado
- Al empezar: `lane-check` → 2 archivos, 1 de código. «Escalar: no. El cambio cabe en el carril.»

## Verificación
- Línea base (`9033a68`): 344 tests, 342 pasan y 2 fallan (`ProxyHttpsTest`, ficha
  `2026-10-20-fix-marcadores-conflicto-main.md`).
- Tras el arreglo: **344 pasan** (6604 aserciones). `pint --test` sobre lo tocado: PASS.
- Suite ejecutada en un contenedor de la imagen `dentissa-app` montando el worktree de la rama.
- semgrep `p/php` sobre lo tocado: 0 hallazgos. gitleaks (modo carpeta): sin fugas.
- `lane-check` al cerrar: sin motivos de escalado (1 archivo de código).
- **Review ligera** (subagente `standard`, solo diff + ficha): aprobado, sin bloqueantes.
  - Con `routes/web.php` revertido: `Tests: 3 failed, 5 passed`; con el arreglo: `Tests: 8 passed`.
  - `git diff 3eb64a6 084ddca` está vacío en los dos archivos.
  - El resto del merge `f096e17` es formato o la landing sin uso; nada más referencia `landing.index`.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | usuario | ¿Cómo se prepara `main` para liberar la 031 con la landing del colaborador en `/`? | Un `/fix` que devuelve `/` al login hasta que la 038 la sustituya | usuario (`/analyze 038` A1) |
