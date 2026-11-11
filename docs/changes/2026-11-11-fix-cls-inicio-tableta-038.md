---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: d56afdf                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [038]               # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir (K8 es un KPI de diseño, no un CA)
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: la página de inicio salta al cargar entre 960 y 1024 px

## Problema
La spec 038 pide una estabilidad visual (CLS) de **≤ 0,1** en la página de inicio (K8). Tras la ficha
`2026-11-11-fix-cls-inicio-038` (la pastilla), el inicio cumple a 1440 px (K11 de `medir.mjs`, 0,018) y en
casi todos los anchos, pero entre unos 960 y 1024 px sigue dando **~0,19** (0,24 antes de esa ficha). Hacia los
400 ms, la arcada baja 33 px y los arcos del fondo 20 px, a la vez que la navegación cambia de ancho (el
cambio de fuente). Precargar las fuentes no lo arregló en una prueba.

## Reproducción
1. Con las seis fotos de prueba publicadas, `.ai/inspect/039-render.php inicio` y un `PerformanceObserver` de
   `layout-shift` (carga de 2 s, Playwright 1.63.0): 960 px **0,201**, 1024 px **0,188** (el resto de anchos,
   ≤ 0,045; 1440 px, 0,019). A 1024 px el salto llega a los ~400 ms: la arcada baja 33 px, los arcos 20 px y
   la navegación cambia de ancho.
2. Siguiendo la altura de las piezas del hero en cada fotograma, a 1024 px: al cargarse las fuentes,
   `.pub-hero__actions` pasa de 56 a 122 px (los dos botones, «Agendar mi primera cita» y «Ver servicios»,
   ya no caben en una fila) y la columna de 642 a 708 px. A 1200 px caben con las dos fuentes.
3. Una copia de la página con un respaldo de Montserrat ajustado (Arial con `size-adjust`) da ≤ 0,025 en
   los diez anchos medidos; precargar las fuentes, en cambio, no cambia nada.

Tests que prueban el arreglo: `PaginasPublicasCssTest::test_montserrat_tiene_un_respaldo_con_sus_medidas`
(antes: «Una cara de respaldo por peso de Montserrat. Failed asserting that actual size 0 matches expected
size 3») y `test_los_tokens_038_coinciden_con_el_sistema` con el token nuevo (antes: «system.md documenta
--font-publica con "Montserrat", "Montserrat respaldo", …»). El CLS no se mide en PHPUnit: ver
«Verificación».

## Causa raíz
Las fuentes van con `font-display: swap`: hasta que llega Montserrat, el texto se pinta con la siguiente
familia de `--font-publica` (`ui-sans-serif, system-ui, sans-serif`), que es bastante más estrecha. Montserrat
es entre un 7,5 % y un 12,5 % más ancha que Arial, según el peso. Entre unos 960 y 1024 px, la columna del hero
deja sitio justo para los dos botones con la fuente del sistema, pero no con Montserrat: al llegar, el segundo
botón pasa a otra fila, la columna crece 66 px y la arcada, centrada, baja la mitad. En el resto de anchos
pasa lo mismo a menor escala (la navegación y el texto cambian de ancho). El ancho exacto donde salta depende
de la fuente del sistema de cada equipo (en el navegador de medida es WenQuanYi Zen Hei; en Windows, Segoe UI).

## Cambio
- `public/css/publico.css`: tres `@font-face` de «Montserrat respaldo» (400, 500 y 600–700), con Arial del
  sistema o Liberation Sans (su equivalente métrico en Linux) por `local()`, sin descarga, y `size-adjust`,
  `ascent-override`, `descent-override` y `line-gap-override` calculados para que ocupen lo mismo que
  Montserrat en cada peso.
- `public/css/app.css`: `--font-publica` pasa a
  `"Montserrat", "Montserrat respaldo", ui-sans-serif, system-ui, sans-serif`.
- `docs/design/system.md` 1.14.0 (sin tokens nuevos: cambia el valor de `--font-publica`), con su decisión, y
  `docs/design/system.html`, con el mismo valor.
- `tests/Feature/PaginasPublicasCssTest.php`: el test nuevo de las caras de respaldo, el token nuevo en
  `TOKENS` y `EN_EL_SISTEMA`, y `test_font_face_declara_las_dos_familias_autoalojadas` sigue contando las seis
  caras que se descargan (las de respaldo no tienen archivo).

Un sistema sin Arial ni Liberation Sans (Android, por ejemplo) usa la siguiente familia, como antes.

## Escalado
`lane-check` sobre el diff desde `d56afdf`: «Escalar: sí, salvo aceptación explícita del usuario — diseño:
tokens o componentes del sistema de diseño (`docs/design/system.md`)». El usuario eligió **seguir en el
carril `/fix`** (ver «Decisiones»). 2 archivos de código (`app.css` y `publico.css`).

## Verificación
- CLS en `/` renderizada por la app (seis fotos de prueba, `PerformanceObserver`, carga de 2 s), con el
  arreglo: 320 px 0,025 · 390 0 · 560 0 · 640 0,001 · 700 0,001 · 800 0,023 · 899 0,017 · 900 0 · 960 0,021 ·
  1024 0 · 1100 0 · 1200 0 · 1440 0. Antes: 960 px 0,201 y 1024 px 0,188.
- `medir.mjs` de la 039 (`--solo inicio`): puertas 12/12 · KPIs 5/5 · **K11 0** (antes, 0,018); variante sin
  mapa, 12/12 y 2/2. `--solo galeria`: 12/12 y 7/7, sin regresión en la otra página pública que lo usa.
- Tests: `test_montserrat_tiene_un_respaldo_con_sus_medidas` y `test_los_tokens_038_coinciden_con_el_sistema`
  fallan sin el arreglo y pasan con él; `PaginasPublicasCssTest`, `PaginaInicioTest`, `PaginasPublicasTest` y
  `AuditoriaVistasTest` (lee `system.html`), 66 en verde. Pint sobre el test: PASS. Suite completa: 914 pasan
  (14 031 aserciones), los 913 de `main` más el nuevo.
- Review ligera (subagente `standard`): sin bloqueantes. Con el código revertido los dos tests fallan; restaurado
  (hashes idénticos), pasan. La causa explica el síntoma y el arreglo ataca la causa; los porcentajes de los
  override cuadran con la fórmula (métrica de Montserrat / `size-adjust`). Menores:
  - (1) «Verificación» pendiente: resuelto aquí.
  - (2), (3) y (4): aceptados, ver «Decisiones».

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-11 | implícita | K8 de la 038 (CLS ≤ 0,1) no se cumple entre 960 y 1024 px | Es un fix: lo define la spec | spec 038, K8; ficha 2026-11-11-fix-cls-inicio-038 |
| 2026-11-11 | supuesto | El arreglo cambia el valor del token `--font-publica` y añade caras de fuente (motivo 6 de escalado) | Seguir en el carril `/fix` con aceptación explícita; `system.md` sube a 1.14.0 con su decisión | usuario |
| 2026-11-11 | supuesto | ¿Respaldo ajustado, precarga de las fuentes o `font-display: optional`? | Respaldo ajustado: la precarga no cambió nada en la medida, y `optional` dejaría la primera visita sin Montserrat | — |
| 2026-11-11 | supuesto | Un sistema sin Arial ni Liberation Sans (Android trae Roboto) no usa el respaldo | Se queda como antes (la siguiente familia de la pila); no se añade Roboto porque sus medidas son otras | — |
| 2026-11-11 | supuesto | Review (2): las muestras vivas de `system.html` declaran Montserrat pero no su respaldo | Se acepta: es documentación, carga sus propias caras y no mide el CLS | — |
| 2026-11-11 | supuesto | Review (3): Safari ignora los `*-override` (solo aplica `size-adjust`) y Android no tiene Arial | Se acepta: el ancho, que es lo que mueve la página, se corrige donde hay Arial; donde no, queda como antes | — |
| 2026-11-11 | supuesto | Review (4): los comentarios de `publico.css` y del test citan la ficha por su nombre | Se acepta: las fichas no se renombran al cerrarse | — |
| 2026-11-11 | supuesto | Review (5): Newsreader (`--font-display`), el Roboto del acceso y el de `errores.css` también usan `swap` sin respaldo ajustado | Fuera de esta ficha. El inicio ya cumple K8 en todos los anchos medidos con Newsreader tal cual; si una medida del acceso o de los titulares lo pide, ficha aparte | — |
