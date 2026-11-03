---
result: fail
round: 4
mode: delta
constitution_version: 1.3.0
date: 2026-10-21
spec_sha: eed58ab5829d
plan_sha: 23809b6ab9ae
tasks_sha: cf1f17b641c8
---

# Análisis · 038 Página de inicio pública

## Resumen
Ronda 4, delta del rediseño de la ronda 3: `design.md` (651e316 y refinado de la foto), plan con
D16–D18 (f559633) y tareas T071–T088 (79db2bc), con un subagente independiente (diff de 72 kB
desde 651e316). El rediseño es consistente en archivos, orden y trazabilidad. Falla por un umbral
de accesibilidad: la franja del horario (M-2) se mueve sin fin y solo se detiene con el puntero
(WCAG 2.2.2, P9). El usuario decide corregirlo con un botón de pausa. Los otros nueve hallazgos se
aceptan como notas de tarea.
Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 4 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 6 / 6 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
No quedaban hallazgos abiertos. Los aceptados de la ronda 3 (A12–A22) se cumplieron como notas de
sus tareas, ya hechas (`history/analysis.r3.md`).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A23 | Constitución (P9, WCAG 2.2.2) | ALTA | `design.md` M-2 · plan «Estrategia de pruebas» · T076, T083 | La franja es un bucle de 48 s que solo se detiene con el puntero encima (`.marquesina:hover`): con teclado o en el móvil no hay forma de pararla. WCAG 2.2.2 (nivel A) exige pausar, detener u ocultar el movimiento automático de más de 5 s mostrado junto a otro contenido, aunque sea decorativo. Ningún test lo comprueba | Debe existir un control accesible con teclado y táctil que detenga y reanude la franja (decisión del usuario: botón de pausa), con su nombre y estado anunciados. `design.md` (refinado), plan y tareas lo recogen. Test: un caso de `PaginasPublicasTest` (el control existe, es un `button` con nombre y estado) y uno de `PaginasPublicasCssTest` (el estado pausado detiene la animación), más el del script si el control lo maneja el script |
| A24 | Constitución (P9) | ALTA | plan «Accesibilidad medible» · T076, T083, T086 | El contraste de la portada se calcula en el peor caso del velo al 82 % (6.41:1). En el degradado baja a ≈ 3.6:1, que solo vale para texto grande. Que el texto pequeño (texto, horario, teléfono y pastilla) quede sobre el tramo fuerte del velo depende de las paradas del degradado y del ancho de la columna, y solo se vería a ojo | Todo texto de la portada por debajo de 24 px (18.66 px en negrita) queda sobre el velo con opacidad ≥ 0.82. Test en `PaginasPublicasCssTest`: el degradado mantiene `--velo-portada` al menos hasta el final de la columna de texto (60 %), y por debajo de 900 px el velo es uniforme. Medición de contraste sobre la captura con la foto real en T086 |
| A25 | Ambigüedad | MEDIA | plan `config/clinica.php` · T074, T077, T080 | No se define el formato de `contacto.horario_semanal` (cadenas o enteros, domingo), el tipo de `fecha`, el texto completo de las salidas, la regla «mañana» frente al nombre del día, ni cómo se prueba que el texto y la estructura coinciden | Ver «Aceptados» (notas de T074, T077, T080) |
| A26 | Ambigüedad | MEDIA | plan «Navegación» · T074, T084 | No se define el ancla de cada tarjeta, si «Otros servicios» tiene tarjeta ni qué categorías tienen icono propio | Ver «Aceptados» (notas de T074, T084) |
| A27 | Seguridad (TM1) | MEDIA | plan TM1 · T075, T077, T081 | Los casos de TM1 solo miran `innerHTML`; no hay casos de entrada mal formada (`data-horario` roto, zona inválida) ni del invariante «si el script falla, el contenido sigue visible» | Ver «Aceptados» (notas de T075, T077, T081) |
| A28 | Constitución (P5) | MEDIA | D17 · T072, T075 | El test de la foto solo pide que `system.md` «lleve» el crédito, sin valores no vacíos ni relación con los archivos; no se pide quitar los metadatos EXIF; una licencia de banco no autoriza a presentar a las personas fotografiadas | Ver «Aceptados» (notas de T072, T075) |
| A29 | Inconsistencia (P2) | BAJA | T078 | T078 depende solo de T077, que falla a propósito hasta T081: la CI quedaría en rojo entre commits | Ver «Aceptados» (nota de T078) |
| A30 | Inconsistencia | BAJA | plan «Cambios por módulo», fila del layout | La celda dice «Sin scripts.» y después «carga `/js/publico.js`» | `/plan 038 --fix A23,A30`: quitar «Sin scripts.» |
| A31 | Cobertura | BAJA | `tasks.md` Cobertura · T074, T082 | T074 figura como test de CA3 pero su `cubre` no lo incluye; T082 figura en CA10 | Ver «Aceptados» (notas de T074, T082) |
| A32 | Inconsistencia | BAJA | T084 · T073 | Las tiras `.ai/inspect/opcion-c/film-*` están en `.gitignore`: `/review` en otra máquina no podría contrastarlas. T073 toca `design.md` aprobado | Ver «Aceptados» (notas de T084, T073) |

## Decisiones pendientes del usuario
Ninguna: el usuario decidió el 2026-10-21 que A23 se corrige con un botón de pausa y que A24–A29,
A31 y A32 se aceptan como notas de tarea.

Correcciones antes de `/implement`:
1. `/design-spec 038 --fix` — A23: botón de pausa de la franja en M-2 y en «Accesibilidad» (decisión `refinado`).
2. `/plan 038 --fix A23,A30` — control, sus tests y la fila del layout.
3. `/tasks 038 --fix A23` — las tareas que lo prueban y lo construyen.
4. `/analyze 038` (delta).

## Aceptados
Aceptados por el usuario el 2026-10-21 como notas de tarea (sin otra vuelta de plan):
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
