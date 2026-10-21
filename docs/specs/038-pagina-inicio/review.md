---
spec: 038-pagina-inicio
verdict: changes_requested
round: 2
date: 2026-10-21
base: ed46634
head: e78622a
human_signoff: no requerido
---

# Review · 038 Página de inicio pública

## Resumen
`changes_requested`: los 16 hallazgos que la ronda 1 mandó corregir están corregidos, pero la
corrección de R24 (ocultar el `<dd>` vacío) descoloca la rejilla de `/servicios` cuando un servicio
no tiene descripción (R30, importante, comprobado en el navegador). Además, el comentario de
cabecera de `publico.css` no menciona los factores de `calc()` (R31, menor). El usuario corrige los
dos (T069, T070). Rerun sobre `ed46634..e78622a` (32 KB de código) con un solo revisor de contexto
limpio (encargos A, C+D y S, `opus`). Ronda anterior: [history/review.r1.md](history/review.r1.md).
Conteo: bloqueante 0 · importante 1 · menor 1

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 404 pasan (0 fallos) |
| `pint --test` sobre los 8 PHP tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/038-pagina-inicio` | ✅ 0 errores |
| `capturar.mjs --medir` (comprobación «038 páginas públicas»), dos veces | ✅ «todo en orden» ×2 |
| Lighthouse 13.5.0, dos pasadas | ✅ SEO 100 en `/` y `/servicios`, móvil y escritorio |

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | Tokens 1.7.2 en `app.css` y `system.md`, usados en `publico.css:27, 51, 52, 88`; detector con controles en `PaginasPublicasCssTest`; comentario a medias (→ R31) |
| R2 | corregido | `publico.css:293-294`, `position: relative` bajo 900 px; `elementFromPoint` devuelve la placa (nota de T062) |
| R3 | corregido | Aserciones dentro de `.lb-aviso` y de `#ubicacion` (`PaginaInicioTest`) |
| R4 | corregido | Precio y duración sobre el texto de `<main>` (`ServiciosPublicosTest`) |
| R5 | corregido | Descripción de `/registro` comparada en cuanto exista |
| R6 | corregido | Servicios propios del test en los dos casos |
| R7 | corregido | Aserción `opacity: 0` retirada |
| R8 | corregido | `test_los_acentos_no_separan_categorias` |
| R13 | corregido | `hojas()` recorre `app.css` y `publico.css` en los dos tests |
| R14 | corregido | `deploy/verificar.sh:109-121`, con su test |
| R16 | corregido | `/;x`, `/.`, `/%3F`, `/index.php` en `CabecerasSeguridadTest` |
| R18 | corregido | `<nav class="pub-menu" aria-label="Secciones">` con `<details class="pub-menu-detalles">`; selectores, foco, corte y `capturar.mjs` siguen valiendo |
| R21 | corregido | Logo de la placa con `aria-hidden="true"`; test de dos `role="img"` |
| R23 | corregido | `aria-label` que empieza por el texto visible (2.5.3) |
| R24 | corregido con efecto nuevo (→ R30) | `.lb-lista dd:empty { display: none; }` |
| R27 | corregido | `lang="es"` y `viewport` en el test de metadatos |

Los aceptados sin tarea de la ronda 1 (R9–R12, R15, R17, R19, R20, R22, R25, R26, R28, R29) no
empeoran.

## Seguridad
`deploy/verificar.sh`: solo expande `"${compose[@]}"` y `"${cabeceras}"`, con URL y patrones fijos
y entrecomillados. P9: ningún literal visual nuevo (`auto`, `relative`, `none` son palabras clave).
Herramientas: gitleaks y semgrep `p/php` sin hallazgos en el rango; `composer audit` y `npm audit`
sin avisos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R30 | importante | `public/css/publico.css:255, 261` · `resources/views/publico/servicios.blade.php:31-34` | A 560 px o más, `.lb-lista dl` es una rejilla de dos columnas con colocación automática; con el `<dd>` vacío oculto, el `<dt>` siguiente cae en la columna de las descripciones y la categoría queda invertida. Comprobado a 1440 px vaciando una descripción en la página: los `<dt>` 2 y 3 pasan de x = 454 a x = 790. El test de R24 solo mira el CSS y `--medir` no crea servicios sin descripción | Fijar los `<dt>` a la primera columna, con su test de CSS y la comprobación en el navegador |
| R31 | menor | `public/css/publico.css:1-4` | El comentario de cabecera no menciona los factores de `calc()` sobre `--touch-min` y `--space-8` (R25, aceptado) | Añadirlos al comentario |

## Tareas añadidas
- T069 ← R30
- T070 ← R30, R31

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-21 (ronda 1):
- **R9** → roadmap: trait de tests de las páginas públicas (`xpath()`, `crearServicio()`, alta de usuario).
- **R10** → roadmap: sacar `panelDelRol()` del controlador (modelo o `app/Autorizacion/`).
- **R11** → roadmap: `--green-texto` se usa con la 039 y la 040.
- **R12** → roadmap: aclarar en la spec que el horario de CA5 se ve en el aviso de la puerta y en el pie.
- **R15** → roadmap: `sandbox` en el `iframe` del mapa, probando que sigue funcionando.
- **R17** → roadmap: nombre del enlace del logo con la marca (WCAG 2.5.3).
- **R19** → roadmap: enlace «Saltar al contenido» en el layout público.
- **R20** → roadmap: `aria-current="page"` en la navegación pública.
- **R22** → roadmap: título del aviso más descriptivo que «Aviso de la puerta» (cambia texto de la spec).
- **R25** → roadmap: tokens de tamaño propios para logo, mapa, placa y menú en lugar de múltiplos de `--touch-min`.
- **R26** → roadmap: simplificar las reglas de foco de `publico.css`.
- **R28** → roadmap: título de `/servicios` más descriptivo (cambia texto de la spec).
- **R29** → roadmap: `id` y `aria-labelledby` en los `h2` del aviso y del pie.
