---
result: fail
round: 6
mode: delta
constitution_version: 1.3.0
date: 2026-10-31
spec_sha: 8ce69ee1e5e4
plan_sha: 3dd5098fb13f
tasks_sha: 1172a78825d5
---

# Análisis · 038 Página de inicio pública

## Resumen
Delta de la ronda 5 de diseño («La ventana del diente»: plan D19–D21, T046–T059 y T089), revisado por
un subagente con contexto limpio. El diseño nuevo es coherente con la spec y los criterios no cambian.
El resultado es `fail` por tres hallazgos altos que no vienen del diseño: la rama va 203 commits por
detrás de `main`, donde la 020 (v0.12.0) cambió archivos que tocan las tareas pendientes, y ninguna
tarea fusiona `main`. El usuario decide corregirlos con una tarea de fusión (vía ligera, 2026-10-31).
Conteo: CRÍTICA 0 · ALTA 3 · MEDIA 3 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | sin cambios desde la ronda 5 |
| Principios de la constitución evaluados | 10 / 10 (P7 y P8 no aplican; P10 con su excepción aceptada) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A33 | BAJA | resuelto | La ronda 5 retira el botón de pausa (D21, T054); su nota en T076 y T083 sigue valiendo para lo hecho |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A34 | Conflicto · Constitución (P5) | ALTA | `config/clinica.php` (main l. 30–36; rama l. 35–47); spec CA5; T049 | La 020 creó en `main` la clave `contacto` con `telefono` `753 162 5220` (el teléfono real de la landing) y sin `direccion` ni `horario_semanal`; la rama la tiene con el ficticio `753 000 0000` que exige CA5. La 503 de la 020 y los correos de la 041 leen esa clave | Un único `contacto` con `direccion`, `telefono` `753 000 0000`, `telefono_enlace` `tel:+527530000000`, `horario` y `horario_semanal` (decisión del usuario, 2026-10-31: el ficticio). Test: `PaginaInicioTest` (contacto de CA5) y los tests de la 503 de la 020 en verde |
| A35 | Conflicto · Inconsistencia | ALTA | plan D21 y fila `app.css`; T051, T053; main `public/css/app.css` l. 83 y 87, `public/css/errores.css` l. 17–41 | D21 y T053 retiran `--sombra-tarjeta-alta` como «1.8.0 sin uso», pero en `main` la usa la página de error de la 020, igual que `--leading-publico`. Retirarla dejaría esa página sin sombra | Mantener `--sombra-tarjeta-alta` y `--leading-publico` con una sola declaración en `app.css`. Test: un caso de `PaginasPublicasCssTest` exige las dos y que todas las variables de `errores.css` estén declaradas en `app.css` |
| A36 | Conflicto · Cobertura | ALTA | T046–T059; `system.md` y `system.html` (1.7.0 y 1.8.0 en las dos líneas, con contenido distinto); `routes/web.php`; `capturar.mjs` | Ninguna tarea fusiona `main` antes de la ronda 5. Chocan `system.md`/`system.html`, `app.css`, `routes/web.php` (`/auditoria` y `/_disenio/020` de la 020 junto a `/` y `/servicios`) y `capturar.mjs` (`excluidos` de la 020 junto a las comprobaciones de la 038) | Tarea nueva antes de T046, de la que dependa T046: fusionar `origin/main` y reconciliar (A34, A35). Hecho cuando: el historial de `system.md` y `system.html` lleva lo de las dos specs, las cuatro rutas existen, `capturar.mjs` conserva las dos partes, y la suite, `pint --test`, `node --test tests/js/*.test.mjs`, `validate` y `capturar.mjs --seco --medir` pasan |
| A37 | Conflicto | MEDIA | T050, T087; plan, fila `public/fonts/` | `main` trae `public/fonts/roboto/` (020); T050 y T087 solo cuentan Cormorant Garamond y Montserrat | Ver «Aceptados» |
| A38 | Seguridad | MEDIA | T047, T050; plan D19 | La verificación de las fuentes nuevas queda solo en una nota de tarea, y T050 solo exige el `OFL.txt` | Ver «Aceptados» |
| A39 | Constitución (P9/CA9) · Cobertura | MEDIA | T052, T054 | De `publico.js` solo `estadoAhora()` tiene test unitario; la fila de hoy y el progreso `--p` de la escena no | Ver «Aceptados» |
| A40 | Inconsistencia | BAJA | plan, Rollout (`desplegar.sh v0.11.0`) | La versión de la 038 sigue a v0.12.0 | Ver «Aceptados» |

## Decisiones pendientes del usuario
Ninguna. A34: el teléfono de toda la app es el ficticio `753 000 0000` (usuario, 2026-10-31).

## Aceptados
Aceptados por el usuario el 2026-10-31 (vía ligera):
- **A37** → nota de T050, T087: `public/fonts/` lleva Roboto (020), Cormorant Garamond y Montserrat; `test_las_fuentes_van_con_su_licencia` comprueba un `OFL.txt` en cada carpeta y el peso de `/` cuenta solo las fuentes que carga la página pública
- **A38** → nota de T047, T050: T047 comprueba con `npm view` la versión, la fecha y la integridad de cada paquete y anota en `system.md` («Fuentes») el SHA-256 de cada woff2; T050 añade un caso que compara los archivos con esa tabla
- **A39** → nota de T052, T054: la fila de hoy y el progreso de la escena salen a funciones puras expuestas como `estadoAhora` y se prueban en `tests/js/publico.test.mjs`
- **A40** → nota de T097: la versión de la 038 se fija en `/release 038` (la siguiente menor tras v0.12.0), no v0.11.0
