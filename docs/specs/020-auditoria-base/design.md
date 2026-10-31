---
spec: 020-auditoria-base
status: approved         # draft | approved (solo el usuario aprueba)
exploration: loop
threshold: 80
floor: 3
budget: 5
accepted: c4
score: 80
system_version: 1.6.0
created: 2026-10-28
updated: 2026-10-28
---

# Diseño · 020 Registro de auditoría base y correlación por petición

Hecho con `/design-spec 020` sobre `docs/design/system.md`. Criterios en la spec ("Criterios de
diseño") y en `design/criterios.json`; candidatos en `design/candidatos/c<N>/` (una carpeta por
candidato, una página por pantalla; los descartados se conservan). Se miden con `design/medir.mjs`,
que parte `criterios.json` por pantalla y llama a `evaluate.mjs` (Playwright en Docker, imagen
`mcr.microsoft.com/playwright:v1.63.0-noble` con el volumen `dentissa_pw_node`).

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | G-foco falla en las seis pantallas con filtro | K4 y K5 fallan por cómo estaban escritos (ver Decisiones) | — | descartado: el botón del calendario de los campos de fecha no muestra el anillo de foco | `.ai/inspect/020-c1/` |
| `c2` | todas (con las excepciones de la barra lateral) | 13/13 | 71 (jerarquía 3 · primera pantalla 4 · sistema 4 · lectura 3) | descartado: rúbrica bajo el umbral (identificadores de tres líneas en la tabla, filtros de texto libre y paginación) | `.ai/inspect/020-c2/` |
| `c3` | todas (ídem) | 13/13 | 76 (4 · 4 · 4 · 3; mejor que c2 en los cuatro puntos) | descartado: rúbrica bajo el umbral (tabla sin margen derecho, tarjeta dentro de tarjeta en el teléfono, vacío con otra cabecera) | `.ai/inspect/020-c3/` |
| `c4` | todas (ídem) | 13/13 | 80 (4 · 4 · 4 · 4; frente a c3: igual en jerarquía y sistema, mejor en primera pantalla y lectura) | aceptado | `.ai/inspect/020-c4/` |

## Resultado

**`c4`**, aceptado con 80/100 (umbral 80, ningún punto bajo 3) en el cuarto de cinco candidatos.
Páginas: `design/candidatos/c4/` (`consulta.html`, `vacia.html`, `filtro.html`, `menu.html` y
`error.html`). Medición: `.ai/inspect/020-c4/resumen.md`.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde, G-h1, G-red, G-toque, G-tokens, G-solapamiento, G-imagenes, G-foco, G-sin-js | las de `criterios.md` §2 | sin fallos en las seis pantallas a 320, 390 y 1440 | ✅ |
| G-contraste | 4.5:1 · 3:1 en texto grande | sin fallos fuera de la barra lateral del escritorio; la barra (menú 3.81:1, rótulos 2.71:1) es deuda DS1 (Decisiones) | ✅ con excepción |
| G-aparece, G-reducido | todo visible | sin fallos fuera del menú cerrado del teléfono, oculto a propósito (Decisiones); el menú abierto pasa | ✅ con excepción |
| K1 · Filtros y primer evento a 390 px (CA6) | «Filtros» y el primer evento en la primera pantalla | a la vista | ✅ |
| K1b · Filtros y primer evento a 1440 px (CA6) | «Actor», «Filtrar» y el primer evento | a la vista | ✅ |
| K2 · Cada evento completo (CA1, CA6) | hora, acción, rol e identificador del actor, recurso, resultado, origen y petición | presentes; sin desplazamiento horizontal a 320 px | ✅ |
| K3 · Estado vacío (CA6) | «No hay eventos con estos filtros» | presente | ✅ |
| K4 · Filtro no válido (CA6) | el error bajo su campo, antes de «Filtrar» | en orden | ✅ |
| K5 y K5b · Paginación (CA6) | tras la lista; «Siguiente» y la página | en orden; presentes | ✅ |
| K6 · Error genérico (CA11) | «No se realizó» y el identificador de petición a 320 px | a la vista (la vista no depende de la sesión) | ✅ |
| K7 y K7b · Menú (CA6) | «Auditoría» como sección actual (`aria-current="page"`) en el teléfono y en el escritorio | presente | ✅ |
| K8 · JavaScript por página (P10) | ≤ 100 kB | 5 kB (`menu.js` y `filtros.js`) · 0 en el error | ✅ |
| K9 · Estabilidad visual | CLS ≤ 0.1 | 0.032 como mucho | ✅ |
| K10 · Titulares | ≤ 2 líneas | ≤ 2 líneas | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 25 | 4 | `consulta/film-1440-00.png`: título, filtros y tabla «Eventos» en un orden claro, y las insignias destacan el resultado; en cada fila compiten la hora y la acción, las dos en negrita, y los identificadores largos pesan casi como el dato clave |
| Primera pantalla | 25 | 4 | `consulta/film-1440-00.png`: frase de alcance, seis filtros, recuento con la página y siete eventos; `film-320-00.png`: frase, «Filtros», recuento y un evento completo. «del más reciente al más antiguo» aparece dos veces |
| Coherencia con el sistema y la marca | 30 | 4 | `consulta/1440-claro.png` y `menu/320-claro.png`: «Auditoría» marcada en «Análisis» como el resto del menú, con tarjetas, campos, botones, insignias y cabeceras del sistema; `error/1440-claro.png`: el panel de marca del acceso. A 320 px los campos de fecha se cortan |
| Lectura y ritmo | 20 | 4 | `consulta/film-1440-00.png`: más interlineado que en c3; `film-320-01.png`: sin tarjeta dentro de tarjeta, los identificadores parten en dos líneas como mucho. «Solicitó admin.auditoria.index» parte en dos líneas y las filas alternan dos y tres |
| Diferencia frente a una plantilla | 0 | — | no cuenta: herramienta interna (supuesto de la spec) |

**Observaciones del evaluador que quedan abiertas** (no impiden aceptar; `/design-spec 020 --fix`
las corrige si en la revisión manual de `/implement` se piden):
1. A 320 px, «Desde» y «Hasta» van en dos columnas y la fecha se corta («mm/dd/yyy»): apilarlas a
   todo el ancho por debajo de 360 px.
2. En la tabla, la columna «Acción» parte el detalle en dos líneas mientras «Recurso» y
   «Resultado» dejan blanco (se probó dejarlo en una línea: la tabla se pasaba 26 px a 1440 px).
3. «del más reciente al más antiguo» se dice en la frase de entrada y en el recuento: basta una vez.

## Imágenes de muestra

Ninguna. Las imágenes son el logo del proyecto (`public/images/logo-icon.png` en la barra lateral y
`logo-main.png` en el error), con `data-origen="propia"`.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Consulta · con eventos y varias páginas | `candidatos/c4/consulta.html` a 320 y 390 (tarjetas sobre el fondo, filtro plegado) | 900–1399 px: tabla; los identificadores pueden partirse en dos líneas | a 1440 (tabla, filtro desplegado, cada identificador en una línea) | CA1, CA6 |
| Consulta · vacía | `candidatos/c4/vacia.html` | igual | igual | CA6 |
| Consulta · filtro no válido | `candidatos/c4/filtro.html` (panel abierto) | igual | igual | CA6 |
| Menú del teléfono abierto | `candidatos/c4/menu.html` a 320 y 390 | no aplica | la barra lateral de siempre con «Auditoría» marcada (`consulta.html`) | CA6 |
| Error genérico (503), con y sin sesión | `candidatos/c4/error.html` | 769 px o más: panel de marca a la izquierda | igual | CA11 |

## Composición y jerarquía

- **Consulta:** título «Auditoría» en la barra superior; una frase que dice qué es el registro (solo
  lectura, del más reciente al más antiguo, la consulta también queda registrada); el filtro; la
  lista con su cabecera («Eventos», recuento y página) y la paginación al pie. Es el orden del HTML
  en todos los anchos.
- **Cada evento:** la hora y la acción dominan (peso 500) y el resultado es una insignia con texto;
  el actor (rol e identificador), el recurso (tipo e identificador), el origen y el identificador
  de petición van debajo o en su columna, en `--text-small`.
- **Error genérico:** etiqueta «Error 503 · servicio no disponible», título «No se realizó la
  acción», una frase sin detalles internos, el identificador de la petición sobre `--p50` con la
  indicación de darlo a soporte, «Volver al inicio» y el teléfono de la consulta en el talón.

## Componentes

- **Del sistema:**
  - layout de la app con la barra lateral (escritorio) y el menú del teléfono de la 033 (`.active` y
    `aria-current="page"` en «Auditoría», sección «Análisis»);
  - filtro plegable de la 034 (`.filtro-citas`, `filtros.js` sin cambios: plegado con JS en el
    teléfono, desplegado sin JS) dentro de `.formulario-accesible` (028) para las etiquetas, campos,
    «Filtrar» en `--p500` y foco;
  - tabla con tarjetas de lista en el móvil (`tabla-tarjetas`, 034) dentro de `.card.card-0`;
  - insignias de resultado: «Éxito» (`badge-green` con texto `--text`, como en 028), «Denegado» y
    «Denegado · 404» (`badge-red`);
  - estado vacío (`.empty-state`) y botones `btn-primary`, `btn-outline`.
- **Exclusivos de esta pantalla:**
  - `.filtro-auditoria`: rejilla de seis campos en el escritorio (dos filas entre 900 y 1279 px) y
    los botones debajo; en el teléfono, dos columnas con actor e identificador de recurso a lo ancho;
  - en el teléfono, la lista y el filtro sin el marco de tarjeta (cada evento es una tarjeta sobre
    el fondo, sin tarjeta dentro de tarjeta); el panel del filtro, al abrirse, sí lleva el marco;
  - `.tabla-auditoria`: `th` en `--p600` (los globales en `--text-light`, DS1), celdas alineadas
    arriba, sin fondo al pasar el ratón; origen y petición en una columna en el escritorio y en dos
    filas de la tarjeta en el teléfono; identificadores en `--text-small` con cifras tabulares, sin
    partir desde 1400 px;
  - `.paginacion`: texto «Mostrando 1–25 de 1.284 · página 1 de 52» y «Anterior» / «Siguiente»
    (`btn-outline` con borde `--border-control`), solo los que llevan a otra página; en el teléfono,
    a lo ancho con 44 px de alto;
  - error de campo (`.campo-error` en `--red`, campo con borde `--border-width-strong` en `--red`,
    `aria-invalid` y `aria-describedby`) y aviso arriba del filtro (`role="alert"`);
  - carcasa del error genérico: la del acceso de la 041 (franja `--p800` con el logo en el teléfono,
    panel `--p600`→`--p800` en el escritorio, tarjeta blanca con talón).

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--leading-publico` | fijo | 1.5 | — | Interlineado del texto corrido (introducción, error); el mismo nombre que propone la 041 |
| `--text-acceso-titulo` | fijo | 28px | — | Título del error genérico; hoy es un literal del acceso (DS4); nombre de la 041 |
| `--text-acceso` | fijo | 14px | — | Texto del error genérico; nombre de la 041 |
| `--acceso-form-max` | fijo | 480px | — | Ancho de la tarjeta del error; nombre de la 041 |
| `--sombra-tarjeta-alta` | fijo | `0 14px 32px rgba(114, 36, 62, 0.16)` | — | Sombra de la tarjeta del error; nombre de la 041 |
| Paginación (`.paginacion`) | corte 900 px | a lo ancho, botones de 44 px | a la derecha del texto | No había componente: la lista de citas la pinta con `style=""` |
| Tabla de auditoría (`.tabla-auditoria`) | corte 900 px y 1400 px | tarjetas con origen y petición en filas propias | tabla; identificadores sin partir desde 1400 px | Siete datos por evento con tres identificadores largos |
| Foco del campo de fecha | fijo | anillo en el campo con `:focus` y `:focus-within` | — | El botón del calendario recibe el foco y `:focus-visible` del campo no se aplica (G-foco en c1) |

La 020 se libera antes que la 041 (D1 de la 041): los tokens con nombre de la 041 entran al sistema
con la 020 y la 041 los reutiliza.

## Movimiento

Ninguno nuevo. El menú del teléfono conserva su transición de la 033.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (`G-contraste`, `G-toque`, `G-foco`); la
  barra lateral del escritorio queda como deuda DS1 (ver Decisiones).
- **Encabezados y estructura:** un `h1` («Auditoría»); «Eventos» es `h2` y da nombre a la sección de
  la lista; la tabla lleva `th scope="col"`; la paginación es un `nav` con nombre propio.
- **Teclado:** orden del HTML: menú, filtro (campos, «Filtrar», «Limpiar»), lista y paginación; en
  el teléfono, «Filtros» con `aria-expanded` y, con filtros aplicados, nombre «Filtros, n
  aplicados».
- **Estados:** el vacío con `role="status"`; el filtro no válido, con el aviso `role="alert"` y el
  error unido a su campo con `aria-describedby` y `aria-invalid`; el resultado de cada evento se
  lee en el texto de la insignia, no solo por su color.
- **Decorativos ocultos:** iconos con `aria-hidden="true"`.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-28 | contradicción | La barra lateral del escritorio (#C75078) no cumple el contraste (menú 3.81:1, rótulos 2.71:1, DS1) y G-contraste mide la página entera | La barra se queda como está, como en la 034; G-contraste se aplica a todo lo demás y `medir.mjs` comprueba que los únicos fallos son de la barra | usuario |
| 2026-10-28 | supuesto | G-aparece y G-reducido cuentan como contenido que nunca aparece el menú cerrado del teléfono, oculto a propósito (`visibility: hidden`, spec 033) | Falso positivo del medidor: `medir.mjs` lo admite por debajo de 900 px solo si la página pasa sin la barra; el menú abierto se mide como pantalla propia (`menu.html`), sin excepción | /design-spec |
| 2026-10-28 | supuesto | K4 y K5 de `criterios.json` fallaban en c1 por cómo estaban escritos: en el escritorio, «Acción» está a la derecha de «Actor», en la misma fila; «Siguiente» está centrado unos píxeles por encima de «Mostrando…» | K4 mide «Actor», su error y «Filtrar» en ese orden; K5 mide la última fila y «Mostrando…», y K5b pide «Siguiente» y la página. Los criterios de la spec no cambian | /design-spec |
| 2026-10-28 | diseño | ¿Cuántos eventos por página? (el plan de 2026-09-28 decía 50) | 25: con 50, la página del teléfono medía unos 13 500 px y la paginación quedaba muy lejos (evaluador de c2). Lo recoge `/plan 020 --redo` | /design-spec |
| 2026-10-28 | diseño | ¿Acción y tipo de recurso, texto libre o selector? | Selector con los valores registrados («Todas» y, por ejemplo, `acceso_denegado`, `auditoria_consultada`, `auditoria_purgada`): con texto libre había que saber los códigos (evaluador de c2). El actor sigue siendo un identificador (supuesto de la spec). Lo recoge `/plan 020 --redo` | /design-spec |
| 2026-10-28 | diseño | ¿Se aprueba el acta con `c4`? | Aprobada | usuario |
