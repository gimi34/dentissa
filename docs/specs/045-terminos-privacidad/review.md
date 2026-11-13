---
spec: 045-terminos-privacidad
verdict: approved
round: 3
date: 2026-11-13
base: a2e2dfa
head: 1ed6982
human_signoff: gimi34 2026-11-13
---

# Review · 045 Términos de uso y aviso de privacidad

## Resumen
`approved`: ronda corta sobre la fusión de `main` antes de `/release` (la v0.17.0, spec 047, su cierre y el
`chore` del favicon). La fusión no cambia el código de la 045: en sus archivos solo entran el `@include` del
favicon (`register`, `registro-contacto`, `layouts/publico`) y la ruta de reportes; los conflictos fueron de
documentación y se resolvieron conservando las dos partes, con la 045 en `system.md` 1.16.0. La ronda 2
([history/review.r2.md](history/review.r2.md)) sigue vigente para el código de la 045.
Conteo: bloqueante 0 · importante 0 · menor 0 (los 7 menores aceptados de la ronda 1 siguen → roadmap)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, en `1ed6982`) | ✅ 1001 en verde (15 342 aserciones): los 931 de la 045 más los de la 047 |
| `composer install` tras la fusión | ✅ sin cambios de paquetes (`composer.lock` igual: `phpseclib` 3.0.58 en las dos ramas) |
| `python .ai/bin/aidd.py validate docs/specs/045-terminos-privacidad` | ✅ 0 errores |
| `design/medir.mjs` sobre la app (`terminos`, `privacidad`) | ✅ 11/11 puertas y 5/5 KPIs en cada una, con el favicon |
| Ventana del registro en Chromium | ✅ abre, cierra con «Cerrar», Escape y fuera, foco de vuelta, sin JavaScript navega |

## Conformidad con el plan
- La fusión no añade cambios de la 045. Conflictos: `system.md` y `system.html` (secciones de la 047 y de la 045;
  la 045 renumerada a 1.16.0 en ellos, en `design.md` y en los comentarios del CSS y de los tests),
  `security.md` (puntos de entrada de las dos) y `docs/specs/README.md`.

## Seguridad
Sin cambios de dependencias por la fusión; la SCA de la puerta de `/release` se pasa sobre el commit a liberar.

## Hallazgos
Ninguno.

## Aceptados sin tarea
- **R4** → roadmap: la fecha de actualización de los términos y del aviso, en una sola fuente y una por texto
- **R6** → roadmap: el comportamiento de las ventanas con un runner JS si su script pasa a `public/js/`
- **R7** → roadmap: `horarioEnTexto()` fuera del controlador (clase de dominio) o pasado desde `RegisterController`
- **R9** → roadmap: `aria-haspopup` de los enlaces de la casilla solo cuando hay `showModal`
- **R10** → roadmap: las secciones de los textos legales sin `aria-labelledby` (menos regiones para el lector)
- **R11** → roadmap: el texto desplazable de la ventana enfocable con el teclado en navegadores antiguos
- **R12** → roadmap: la ventana no se cierra al soltar sobre el velo una selección empezada dentro
