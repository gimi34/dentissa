---
result: pass
round: 5
mode: delta
constitution_version: 1.3.0
date: 2026-10-21
spec_sha: eed58ab5829d
plan_sha: f7553000022b
tasks_sha: 6d3e0761abe1
---

# Análisis · 038 Página de inicio pública

## Resumen
Ronda 5, delta pequeño sin subagente (las correcciones de A23 y A30 en `design.md`, plan y
tareas, c3fa1a8), releído desde los archivos. A23 y A30 quedan resueltos. Hay un hallazgo nuevo,
BAJO, aceptado como nota. Pasa.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 6 / 6 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A23 | ALTA | resuelto con efecto nuevo (→ A33) | `design.md`: componente «Pausa de la franja», M-2 solo bajo `.js` y decisión `refinado`; plan: inicio, `publico.js`, `publico.css`, Pantallas, Clases, Accesibilidad medible, Estrategia de pruebas, Trazabilidad (CA9, M-2) y D16; tareas: T074 (botón en el HTML), T076 (CSS), T079 (`--medir` con el teclado), T081 (script), T083, T084 y T086 |
| A30 | BAJA | resuelto | plan, fila del layout sin «Sin scripts.» |
| A24–A29, A31, A32 | — | aceptados (ronda 4) | se mantienen abajo como notas de tarea |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A33 | Inconsistencia | BAJA | T076, T083, T084 | El botón de pausa sale en el HTML con `hidden`; una regla de `publico.css` con `display` (p. ej. `inline-flex`) anularía el atributo y el botón se vería sin JavaScript | Ver «Aceptados» (nota de T076, T083) |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-21 (A24–A32 en la ronda 4, A33 en esta) como notas de tarea:
- **A24** → nota de T076, T083, T086: el texto pequeño de la portada queda sobre el velo con opacidad ≥ 0.82. El test comprueba que el degradado mantiene `--velo-portada` hasta el final de la columna de texto (60 %) y que el velo es uniforme por debajo de 900 px. T086 mide el contraste del texto pequeño sobre la captura con la foto real (≥ 4.5:1)
- **A25** → nota de T074, T077, T080:
  - `horario_semanal` es `[1 => ['9:00', '20:00'], …, 5 => ['9:00', '20:00'], 6 => ['9:00', '14:00']]`, con el domingo (7) ausente. `estadoAhora(horario, zona, fecha)` recibe un `Date` y devuelve el texto completo, o `null` si los datos no valen.
  - Salidas: «Abierto ahora · cierra a las H:MM»; «Cerrado ahora · abre hoy a las H:MM» (antes de abrir); «… abre mañana a las H:MM» (el día siguiente); «… abre el <día> a las H:MM» (más adelante).
  - Casos: el lunes a las 0:00 «abre hoy a las 9:00»; el domingo «abre mañana a las 9:00»; el sábado a las 15:00 «abre el lunes a las 9:00».
  - `test_el_horario_en_texto_coincide_con_el_semanal` rehace las filas «Lunes a viernes» y «Sábados» desde `horario_semanal` y las compara con `horario`
- **A26** → nota de T074, T084:
  - Cada tarjeta enlaza a `/servicios#categoria-N`, el mismo índice de `porCategoria()` que usa `/servicios` (un solo orden, una consulta por página). El test comprueba que esa ancla existe en el HTML de `/servicios`.
  - «Otros servicios» tiene su tarjeta, como hoy su puerta.
  - Icono propio para las claves normalizadas `cirugia`, `diagnostico`, `endodoncia`, `ortodoncia`, `preventivo`, `prostodoncia` y `restauracion`; la muela para las demás
- **A27** → nota de T075, T077, T081:
  - El caso de TM1 prohíbe `innerHTML`, `outerHTML`, `insertAdjacentHTML`, `document.write`, `eval` y `new Function` en `publico.js`.
  - En `tests/js/`, `estadoAhora()` con JSON roto o con una zona inválida devuelve `null`, sin lanzar.
  - Sin `IntersectionObserver`, el script no pone `js` y no oculta nada; `js` se pone solo después de preparar el observador
- **A28** → nota de T072, T075:
  - T072 quita los metadatos EXIF y prefiere una foto sin rostros reconocibles (de espaldas, con mascarilla o fuera de foco), sin logotipos de terceros.
  - El test exige en `system.md` → «Imágenes» autor, URL, licencia, fecha y el SHA-256 de cada WebP, no vacíos, y que cada hash coincida con su archivo
- **A29** → nota de T078: se hace después de T081 (con `node --test tests/js/` en verde), para que la CI no quede en rojo
- **A31** → nota de T074, T082: T074 `cubre` también CA3 (texto de «Sobre nosotros» en la hoja); T082 deja intactos los metadatos del layout (CA10) y el caso de metadatos de `PaginasPublicasTest` sigue en verde
- **A32** → nota de T084, T073: las tiras de `.ai/inspect/` son locales; la verificación manual de T084 se hace en esta máquina y la nota lo dice. T073 solo toca la fila de la foto de «Imágenes» en `design.md`
- **A33** → nota de T076, T083: `.marquesina-pausa[hidden]` mantiene `display: none` aunque el botón tenga su propio `display`; el test de T076 lo comprueba (control positivo: sin esa regla, falla)
