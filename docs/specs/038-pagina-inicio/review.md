---
spec: 038-pagina-inicio
verdict: changes_requested
round: 4
date: 2026-11-01
base: 902f1ac
head: 04e4dd7
human_signoff: no requerido
---

# Review · 038 Página de inicio pública

## Resumen
Ronda 4 (`--rerun`, una sola ronda por la vía ligera que pidió el usuario) sobre el rediseño de las
rondas 5 y 6 desde la fusión con `main` (902f1ac..04e4dd7: 25 archivos de código, 195 KB). Un único
revisor independiente con los encargos de spec, constitución y calidad, seguridad y accesibilidad
(diff grande). Veredicto `changes_requested`: un bloqueante de contraste (el pie de foto de la escena
viva) y tres importantes (CA5 y dos tests que no prueban lo que dicen). Los menores baratos se
corrigen con ellos; el resto, al roadmap (decisión del usuario, 2026-11-01).
Conteo: bloqueante 1 · importante 3 · menor 9

## Verificación automática
| Comprobación | Resultado |
|---|---|
| Suite (`php artisan test`) | 613 passed (10 073 aserciones), sin fallos nuevos |
| Lint (`pint --test` sobre los PHP tocados desde 7cebde7) | 53 archivos, limpio |
| `node --test tests/js/publico.test.mjs` | 11/11, dos veces |
| `python .ai/bin/aidd.py validate` | 0 errores |
| Secretos (gitleaks 8.21.2, 7cebde7..HEAD) | 257 commits, sin fugas |
| SAST (semgrep 1.99.0 `p/php`, PHP añadidos o cambiados) | sin hallazgos |
| SCA (`composer audit`, `npm audit`) | sin vulnerabilidades altas ni críticas |
| `evaluate.mjs` con `design/criterios.json` | `/`: puertas 11/11, KPIs en verde (K6 y K7 sin el mapa); `/servicios`: puertas 11/11 |
| Navegador (`medir038.mjs`, comprobaciones «038» de `capturar.mjs`) | «todo en orden» dos veces a 320, 390, 900 y 1440 |
| Lighthouse 13.5.0 (móvil; el escritorio no termina por memoria) | SEO 92–100, accesibilidad 100 |

## Seguimiento de la ronda 3
Los hallazgos de la ronda 3 (R1–R31) se refieren a la composición de la ronda 3, sustituida por las
rondas 5 y 6; los aceptados siguen en el roadmap (`history/review.r3.md`). Ninguno empeora.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R32 | bloqueante | `public/css/publico.css:399` (con 386, 389 y 671) | En la escena viva, el pie de foto en `--p800` va encima de la foto (y, por debajo de 560 px, encima de la banda `--p400` que deja `object-fit: contain`) sin fondo propio: unos 2,6:1, por debajo de AA (P9, RNF). `evaluate.mjs` no lo ve porque mide contra el fondo de la sección | Devolver la pastilla `--white` de `c5` al pie de foto de la escena viva |
| R33 | importante | `resources/views/publico/inicio.blade.php:141-152` | CA5 pide el horario «Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00» tal cual en la sección de contacto; la ubicación lo muestra como tabla día por día | Añadir la línea exacta en la ubicación (decisión del usuario, 2026-11-01) |
| R34 | importante | `tests/Feature/PaginaInicioTest.php:391-392` | `test_contacto_exacto_con_mapa_y_como_llegar` comprueba las dos partes del horario por separado: seguiría en verde sin la cadena de CA5 | Comprobar la cadena exacta dentro de `#ubicacion` |
| R35 | importante | `tests/Feature/PaginasPublicasCssTest.php:271-292` | El test de contraste no tiene los pares de la ronda 6: el pie de foto (`--p800` sobre `--p100` y sobre su pastilla `--white`) y «Cerrado» de la semana (`--p200` sobre `--text`) | Añadir esos pares |
| R36 | menor | `public/css/publico.css:286-292, 380-383, 647-661` | Proporciones distintas de `c5`: la arcada sin su 5:6, sin el máximo del corte de 900 px, y la escena quieta en `70svh`/`60svh` en lugar de 16:9 (4:5 en el móvil) | Roadmap |
| R37 | menor | `public/css/publico.css:187, 382, 390-394, 670, 696` | Literales `88vw`, `70svh`, `60svh`, `100svh`, `calc(1.8 * 100svh)` y `rotate(-45deg)` fuera de las excepciones declaradas; `literalesPublicos()` no detecta `vw`, `svh` ni `deg` | Roadmap |
| R38 | menor | `resources/views/publico/inicio.blade.php:73` | `data-hold="0.8"` no lo lee nadie (el CSS fija `1.8 * 100svh`) y el comentario del test de `progresoEscena` habla de 1.4 pantallas | Corregir ahora |
| R39 | menor | `public/js/publico.js:6, 150`; `tests/js/publico.test.mjs`; `PaginasPublicasCssTest.php:250, 351, 359`; `PaginaInicioTest.php:57, 421` | Comentarios y mensajes de rondas anteriores (la ventana, Cormorant, el trazo del diente, `@json`) | Corregir ahora |
| R40 | menor | `inicio.blade.php:41`, `publico.css:275`, `publico.js:99-101` | `aria-live` de la pastilla no anuncia nada (el texto se escribe con el contenedor oculto) y, sin JavaScript, el primer `<li>` queda vacío | Roadmap (cambiarlo cambia lo que afirma un test) |
| R41 | menor | `publico.css:334-342` (`.pub-pasos`, `.pub-index`, `.pub-semana`) | Con `list-style: none`, Safari con VoiceOver deja de anunciar las listas como listas | `role="list"` en las tres; corregir ahora |
| R42 | menor | `publico.css:424-428, 694-696` | El índice desplaza el nombre y gira la flecha con el puntero, pero no con el foco del teclado | `:focus-visible` en esas reglas; corregir ahora |
| R43 | menor | `inicio.blade.php:12-14, 148-151`, `layouts/publico.blade.php:4` | El texto del horario se calcula igual en la vista y en el layout; «Domingos · Cerrado» y el mapa de filas están escritos a mano | Roadmap |
| R44 | menor | `layouts/publico.blade.php:28-29` | El enlace del logo se llama «Inicio» y anula el nombre del `role="img"`; ninguna imagen anuncia ya la marca | Roadmap |

## Preparación para release
- **Rollback:** sin migraciones; volver es desplegar el tag anterior (`deploy/volver.sh`).
- **Migraciones y flags:** ninguna; sin feature flag (la página no cambia datos).
- **Documentación:** `architecture.md` (T087, T090) y `deployment.md` (T091) al día; `AGENTS.md` con aprobación del usuario (T087).
- **SCA:** sin críticas ni altas.
- **Observabilidad:** la 038 no emite eventos de auditoría ni logs nuevos (páginas públicas sin datos sensibles).

## Tareas añadidas
| Tarea | Hallazgo |
|---|---|
| T003 | R34, R35 |
| T006 | R32, R33 |
| T007 | R38, R39 (código), R41, R42 |
| T008 | R39 (tests) |

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-01:
- **R36** → roadmap: proporciones de la arcada (5:6 y máximo a 900 px) y de la escena quieta (16:9, 4:5 en el móvil) como en `c5`, con tokens
- **R37** → roadmap: literales `vw`, `svh` y `deg` de `publico.css` como tokens o excepción declarada, y `literalesPublicos()` que los detecte
- **R40** → roadmap: la pastilla sin `aria-live` inútil y sin el `<li>` vacío sin JavaScript
- **R43** → roadmap: el horario en texto calculado una vez y el domingo sacado de `horario_semanal`
- **R44** → roadmap: el nombre accesible del enlace del logo con la marca
