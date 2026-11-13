---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: fdcfa2e                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: favicon del sitio

## Problema
`public/favicon.ico` es el de la plantilla de Laravel y está vacío (0 bytes), y ninguna vista enlaza
un ícono en su `<head>`: la pestaña del navegador sale sin ícono en todas las rutas. Es la brecha
SE5.a de `docs/seo.md` y parte de la deuda DS15 de `docs/design/system.md`. El usuario aporta el
ícono en `public/images/favicon.png` (isotipo de la muela con la M dentro de un círculo, rosa de la
marca sobre transparente, 516×484).

## Cambio
- `public/favicon.ico`: deja de estar vacío; lleva el ícono en 16, 32 y 48 px.
- `public/favicon-32x32.png` (32×32, fondo transparente) y `public/apple-touch-icon.png` (180×180,
  fondo blanco: iOS pinta de negro lo transparente), nuevos.
- `public/images/favicon.png`: la imagen de origen, aportada por el usuario. Los tres archivos salen
  de ella con Pillow en la máquina de desarrollo (no es una dependencia del proyecto): se recorta al
  dibujo, se centra en un cuadrado y se reduce con Lanczos.
- `resources/views/partials/iconos.blade.php`, nuevo: los tres `<link>` (`icon` con el `.ico`, `icon`
  PNG de 32×32 y `apple-touch-icon`), con `?v=filemtime` como el resto de los recursos.
- `@include('partials.iconos')` después del `<title>` en las siete plantillas con `<head>` propio:
  `layouts/app`, `layouts/acceso`, `layouts/publico`, `errors/plantilla`,
  `errors/auditoria-no-disponible`, `auth/register` y `auth/registro-contacto`. Quedan fuera
  `welcome.blade.php` (sin ruta) y `correos/marco-acceso.blade.php` (un correo no lleva ícono).
- Fuera de esta ficha: SE5.a pedía además un `favicon.svg`, que no se puede sacar de un PNG sin
  redibujarlo, y SE5.b (`theme-color`) sigue pendiente. El estado de SE5.a en `docs/seo.md` y el de
  DS15 en `docs/design/system.md` no se tocan aquí (son de `/seo verify` y `/design-spec`).

## Escalado
- Al empezar (`lane-check --files`, 12 archivos previstos): **tamaño**, 8 archivos de código contra un
  máximo de 5 (el parcial y las siete plantillas). El usuario aceptó seguir en el carril: es la misma
  línea en cada `<head>` (supuesto en «Decisiones»).
- Al cerrar (diff real desde `fdcfa2e`): el mismo motivo de tamaño y, además, avisos que no son de
  esta ficha: los cambios sin commit de `aidlc/` que ya estaban en el árbol de trabajo antes de
  empezar (AI-DLC en pausa; cuentan como «rutas protegidas» y como 2 archivos de código). No se
  tocan ni entran en el commit. De esta ficha: 8 archivos de código, 4 imágenes y la ficha.

## Verificación
- Suite completa sobre este clon, con el cambio aplicado (`docker compose run --rm --no-deps app php
  artisan test`): 984 pasaron, 0 fallaron (14 599 aserciones). Sin línea base aparte: no hay fallos
  que comparar.
- En un servidor de un solo uso sobre este clon (`php artisan serve`, puerto 8010): `/`, `/servicios`,
  `/galeria`, `/login`, `/registro` y una 404 llevan los tres `<link>`; `/favicon.ico` (8 397 bytes,
  `image/vnd.microsoft.icon`), `/favicon-32x32.png` y `/apple-touch-icon.png` responden 200.
- Pint: no aplica, no se tocó ningún archivo PHP (solo Blade e imágenes). Sin dependencias tocadas:
  SCA no aplica.
- No ejecutado: `node docs/design/capturar.mjs --medir` (regla de `AGENTS.md` para las specs que tocan
  `layouts/app.blade.php`). El cambio es un `@include` en el `<head>`, no toca el menú, y los
  contenedores locales de `localhost:8000` montan el worktree `dentissa-041` (otra sesión, spec 045).
- `lane-check` al cerrar: ver «Escalado». `aidd.py validate`: sin errores.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-12 | decisión | ¿Carril o flujo completo? | `/chore`: no cambia comportamiento, datos ni seguridad | usuario |
| 2026-11-12 | supuesto | `lane-check` pide escalar por tamaño (8 archivos de código, máximo 5) | Seguir en `/chore`: una línea `@include` idéntica en cada plantilla | usuario |
| 2026-11-12 | decisión | ¿Qué imagen se usa? | `public/images/favicon.png`, aportada por el usuario (no `logo-icon.png`, que es rosa muy claro y no se ve en una pestaña clara) | usuario |
