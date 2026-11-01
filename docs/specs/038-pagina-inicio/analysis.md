---
result: pass
round: 8
mode: delta
constitution_version: 1.3.0
date: 2026-10-31
spec_sha: 5fb72c7e70e5
plan_sha: a80c24416f5a
tasks_sha: da044406f137
---

# Análisis · 038 Página de inicio pública

## Resumen
Delta de la ronda 6 de diseño («Criterios de diseño» de la spec, `design.md` aprobado con `c5`, plan
D23, tareas T034–T039 y los ajustes de T057–T059, T087 y T089), revisado por un subagente con contexto
limpio. Sin cambio estructural: D23 es visual y reutiliza rutas, controlador, consulta y script. Nueve
hallazgos medios y bajos, todos aceptados como notas de tarea (vía ligera, decisión del usuario).
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 6 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | sin cambios desde la ronda 5 |
| Principios de la constitución evaluados | P5, P9 y P10 revisados en el delta; el resto sin cambios |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A34–A36 | ALTA | resueltos (ronda 7) | T033 hecha |
| A37–A40 | MEDIA/BAJA | aceptados, siguen vigentes | A37 y A38 en T050, T087 y T035; A39 en T052 y T054 (`progresoEscena` no cambia); A40 en T097 (ver A48) |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A41 | Cobertura | MEDIA | T036, T039 | CA6 «pasa las mismas puertas en su tarea», pero T039 no pasa `evaluate.mjs` y T036 no tiene casos de `/servicios` | Ver «Aceptados» |
| A42 | Inconsistencia | MEDIA | T035, T036, T037 | Borrar `cormorant-garamond/` en T035 deja la suite en rojo hasta T037 (`publico.css` aún cita esos woff2) | Ver «Aceptados» |
| A43 | Inconsistencia | MEDIA | plan D23, T035–T037 | El plan reduce Montserrat a 400, 500 y 600, pero ninguna tarea retira el woff2 700 ni su fila de la tabla de huellas | Ver «Aceptados» |
| A44 | Ambigüedad | MEDIA | spec CA3, K4, T036, T038 | `c5` parte el primer párrafo de CA3 (la cita y el resto); el test del texto exacto tiene que unir las partes | Ver «Aceptados» |
| A45 | Cobertura | MEDIA | T036 | No dice qué casos de la ronda 5 de `PaginaInicioTest` se retiran (promesas en tarjetas, ventana del diente, agenda con el diente, orden con «Antes de cada tratamiento» como sección) | Ver «Aceptados» |
| A46 | Cobertura | BAJA | design.md, T037 | El ancho del logo de la cabecera (`calc(var(--logo-max-w) * 1.25)` desde 560 px) no está en T037 ni tiene test | Ver «Aceptados» |
| A47 | Inconsistencia | BAJA | plan, Trazabilidad «Diseño M-1 a M-4» y Pantallas «Menú del móvil» | Aún citan M-5, M-6, la escena `ventana`, `r5/film` y «Menú» visible en píldora | Ver «Aceptados» |
| A48 | Cobertura | MEDIA | T097 | Falta la nota de A40 y su verificación remite a `opcion-a.html` y a la escena de la ventana | Ver «Aceptados» |
| A49 | Duplicación | BAJA | T089, `capturar.mjs` | «038 menú» está escrita para «Menú» con texto | Ver «Aceptados» |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario (vía ligera) el 2026-10-31; los de la ronda 6 (A37–A40) siguen vigentes:
- **A37** → nota de T050, T087: `public/fonts/` lleva Roboto (020), Newsreader y Montserrat; `test_las_fuentes_van_con_su_licencia` comprueba un `OFL.txt` en cada carpeta y el peso de `/` cuenta solo las fuentes que carga la página pública
- **A38** → nota de T047, T050, T035: la versión, la fecha y la integridad de cada paquete con `npm view` y el SHA-256 de cada woff2 en `system.md` («Fuentes»), que un caso compara con los archivos
- **A39** → nota de T052, T054: la fila de hoy y el progreso de la escena salen a funciones puras expuestas como `estadoAhora` y se prueban en `tests/js/publico.test.mjs`
- **A40** → nota de T097: la versión de la 038 se fija en `/release 038` (la siguiente menor tras v0.12.0), no v0.11.0
- **A41** → nota de T036, T039: T036 añade los casos de `/servicios` de la ronda 6 (la etiqueta, el título en `--font-display`, «Volver al inicio» en `.c-btn`) y T039 pasa además `evaluate.mjs` sobre `/servicios` con las puertas (sin los KPIs de `/`)
- **A42** → nota de T035, T037: T035 copia Newsreader y el borrado de `cormorant-garamond/` va en T037, junto a su `@font-face`; la suite queda como estaba al cerrar T035
- **A43** → nota de T035, T036, T037: se retira `montserrat-latin-700-normal.woff2` y su fila de la tabla de `system.md`; el test de huellas exige que los woff2 de cada carpeta sean exactamente los de la tabla y el `@font-face` declara solo 400, 500 y 600
- **A44** → nota de T036: el texto de CA3 se compara normalizado (la cita más los dos párrafos, en orden, dentro de «Sobre nosotros») con el literal de la spec; comprobado rompiendo una palabra
- **A45** → nota de T036: el orden de CA1 con las marcas de `c5` (presentación, «Sobre nosotros», escena `consulta`, servicios, agenda, ubicación, pie); los pasos con título y texto en `.pub-pasos`; los casos retirados (promesas en tarjetas, ventana del diente, agenda con el diente) con su equivalente anotado
- **A46** → nota de T037: el ancho del logo de la cabecera (`calc(var(--logo-max-w) * 1.25)`, y `--logo-max-w` por debajo de 560 px) con un caso en `PaginasPublicasCssTest`
- **A47** → nota de T038: en la Trazabilidad vigente, M-1 es la escena `consulta`, M-2 el dibujo de la presentación, M-3 las entradas y M-4 la respuesta; el menú del móvil es solo el icono; la verificación manual es contra `c5.html` (el plan se corrige en T090)
- **A48** → nota de T097: versión fijada en `/release 038` (la siguiente menor tras v0.12.0) y verificación contra `design/candidatos/c5.html` (1440 y 390) y la escena `consulta`
- **A49** → nota de T089: «038 menú» exige el nombre accesible «Menú», sin texto visible, y una caja de 44 × 44
