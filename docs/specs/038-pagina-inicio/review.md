---
spec: 038-pagina-inicio
verdict: approved
round: 5
date: 2026-11-01
base: 04e4dd7
head: c6e9bba
human_signoff: no requerido
---

# Review · 038 Página de inicio pública

## Resumen
Ronda 5 (`--rerun`) sobre las correcciones de la ronda 4 (04e4dd7..c6e9bba: 6 archivos de código,
19 KB de diff). Diff pequeño: revisado sin subagentes, releyendo los archivos desde cero. Los cuatro
hallazgos que había que corregir (R32–R35) y los menores baratos (R38, R39, R41, R42) están
corregidos y probados; los demás menores siguen en el roadmap por decisión del usuario. Veredicto
`approved`.
Conteo: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comprobación | Resultado |
|---|---|
| Suite (`php artisan test`) | 613 passed (10 088 aserciones) |
| Lint (`pint --test` sobre los PHP tocados) | limpio |
| `node --test tests/js/publico.test.mjs` | 11/11 |
| `python .ai/bin/aidd.py validate` | 31 specs, 6 fichas, 0 errores |
| Secretos (gitleaks 8.21.2, 04e4dd7..HEAD) | 6 commits, sin fugas |
| SAST / SCA | sin PHP de la app ni dependencias cambiadas en el rango (solo vistas, CSS, JS y tests) |
| `evaluate.mjs` con `design/criterios.json` | `/`: puertas 11/11; K6 y K7 sin el mapa en verde (111 kB y 8,4 kB) |
| Navegador (`medir038.mjs`) | «todo en orden» a 320, 390, 900 y 1440, con y sin JavaScript |
| Verificación manual | pie de foto y ubicación a 390 y 1440 px, OK del usuario (2026-11-01) |

## Seguimiento de la ronda 4
| Hallazgo | Estado | Evidencia |
|---|---|---|
| R32 (bloqueante) | corregido | `publico.css:400-411`: pastilla `--white` en el pie de la escena viva; medido en el navegador (fondo blanco, `--p800`, abierta y a medio abrir); `PaginasPublicasCssTest` lo exige |
| R33 (importante) | corregido | `inicio.blade.php:141-142`: la línea exacta de CA5 sobre la tabla de la semana |
| R34 (importante) | corregido | `test_contacto_exacto_con_mapa_y_como_llegar` busca la cadena exacta dentro de `#ubicacion`; falló antes de T006 |
| R35 (importante) | corregido | tres pares nuevos en `test_contraste_de_los_pares_de_las_rondas_5_y_6`; comprobado rompiendo `--p800` |
| R38 | corregido | sin `data-hold` en la escena |
| R39 | corregido | comentarios de `publico.js` y de los tres tests al día (T007, T008) |
| R41 | corregido | `role="list"` en `.pub-pasos`, `.pub-index` y `.pub-semana` |
| R42 | corregido | `publico.css:707-711` (líneas 708 y 711): el nombre y la flecha del índice con `:focus-visible`, dentro de `no-preference` |
| R36, R37, R40, R43, R44 | aceptados → roadmap | sin cambios; ninguno empeora |

Nota: T007 acotó `test_el_foco_es_el_del_sistema` a las reglas del elemento enfocado (antes exigía
el anillo también a `a:focus-visible .pub-index__name`, que mueve el nombre y no es un foco).
Sigue fallando sin el anillo en `.pub-index a:focus-visible` y R42 tiene su aserción aparte
(comprobado rompiendo ambas cosas, nota de T007).

## Hallazgos
Ninguno nuevo.

## Preparación para release
- **Rollback:** sin migraciones; volver es desplegar el tag anterior (`deploy/volver.sh`).
- **Migraciones y flags:** ninguna; sin feature flag (la página no cambia datos).
- **Documentación:** `architecture.md` (T087, T090) y `deployment.md` (T091) al día; `AGENTS.md` con aprobación del usuario (T087).
- **SCA:** sin críticas ni altas.
- **Observabilidad:** la 038 no emite eventos de auditoría ni logs nuevos (páginas públicas sin datos sensibles).

## Tareas añadidas
Ninguna en esta ronda (las de la ronda 4, T003 y T006–T008, están hechas).

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-01 (ronda 4):
- **R36** → roadmap: proporciones de la arcada (5:6 y máximo a 900 px) y de la escena quieta (16:9, 4:5 en el móvil) como en `c5`, con tokens
- **R37** → roadmap: literales `vw`, `svh` y `deg` de `publico.css` como tokens o excepción declarada, y `literalesPublicos()` que los detecte
- **R40** → roadmap: la pastilla sin `aria-live` inútil y sin el `<li>` vacío sin JavaScript
- **R43** → roadmap: el horario en texto calculado una vez y el domingo sacado de `horario_semanal`
- **R44** → roadmap: el nombre accesible del enlace del logo con la marca
