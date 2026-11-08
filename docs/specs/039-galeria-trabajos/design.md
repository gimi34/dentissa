---
spec: 039-galeria-trabajos
status: approved         # draft | approved (solo el usuario aprueba)
exploration: loop        # loop: ciclo por criterios · none: sin diseño nuevo (motivo en "Resultado")
threshold: 80            # puntuación mínima de la rúbrica (de .ai/project.yaml → design)
floor: 3                 # nota mínima de cada punto de la rúbrica
budget: 5                # candidatos como máximo
accepted: c3             # con K11 del inicio aceptado por el usuario como anterior a la 039 (Decisiones)
score: 80                # puntuación del aceptado
system_version: 1.11.0
created: 2026-11-07
updated: 2026-11-07
---

# Diseño · 039 Galería de trabajos

Hecho con `/design-spec 039` (ronda 2, AI-DD 1.19) sobre `docs/design/system.md`. Criterios en la spec
("Criterios de diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los
descartados se conservan). La ronda 1, diseñada dentro de la 038 (opción B, ronda 2), está en
`design/history/ronda-1/`.

Cada candidato es una carpeta con siete páginas: `inicio.html` (la página de inicio real, renderizada
en local, con la sección «Nuestros trabajos» añadida después del avance de servicios), `galeria.html`,
`galeria-vacia.html` (la estructura real de `/servicios` con su contenido) y cuatro pantallas de la
gestión sobre el layout real del administrador (`gestion.html`, `gestion-error.html`,
`gestion-consentimiento.html` y `gestion-quitar.html`). Los estilos nuevos están en `galeria.css` y
`gestion.css`, el carrusel en `galeria.js` y los dibujos en `dibujos/`. Las páginas enlazan
`public/css` y `public/js` reales. Se miden pantalla por pantalla con `design/medir.mjs`, que parte
`criterios.json` y llama a `evaluate.mjs`. En la gestión quita de la copia medida la barra lateral del
layout, y en el inicio mide K9 y K10 con el mapa en blanco (ver Decisiones).

## Candidatos

Las puertas y los KPIs se suman en las siete páginas: 12 o 13 puertas por página, más los KPIs de
cada pantalla y los comunes. La rúbrica la puntuó en cada candidato un evaluador independiente que
solo vio las capturas y comparó con el mejor anterior.

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 92/96 | 40/42 | — | descartado: `G-toque` en las cuatro pantallas de la gestión (radios y casilla de 16 px); K5 («Subir foto» también era el título del formulario, debajo de la primera pantalla a 320 y 390 px); K11 en el inicio | `.ai/inspect/039-c1/resumen.md` |
| `c2` | 96/96 | 41/42 | 75 | descartado: rúbrica por debajo de 80 (jerarquía 4, primera pantalla 4, sistema 4, lectura 4, plantilla 3, riqueza 3). Problemas: el mosaico de `/galeria` dejaba huecos; el selector de archivo nativo y sin error propio; «Quitar» con relleno en cada tarjeta | `.ai/inspect/039-c2/` |
| `c3` | 96/96 | 41/42 | 80 | aceptado (K11 del inicio, anterior a la 039, aceptado por el usuario); refinado con las tres observaciones del evaluador y medido de nuevo: 96/96 y 41/42 | `.ai/inspect/039-c3/` |

## Resultado

**`c3`**, con 80/100: llega al umbral de 80 y ningún punto queda por debajo de 3. Frente a `c2`, el
evaluador lo ve mejor en jerarquía y en sistema e igual en el resto. Pasa todas las puertas y 41 de 42
KPIs.

El que falla es **K11 en el inicio** (CLS 0.212). No lo causa esta spec: la página de inicio actual,
sin la sección nueva y sin el mapa, da el mismo 0.212. A los 300 ms, `publico.js` escribe la pastilla
«¿Está abierto ahora?» encima del titular sin reservarle sitio y empuja hacia abajo el `h1` y la
arcada; además, las fuentes cambian al cargar. La sección «Nuestros trabajos» no añade
desplazamientos: `/galeria` da 0.003. El usuario aceptó `c3` con K11 así, y el desplazamiento se corrige
aparte con un `/fix` de la 038 (Decisiones).

Páginas: `design/candidatos/c3/`.

| Puerta o KPI | Objetivo | Medido en `c3` | |
|---|---|---|---|
| Puertas (`G-desborde`, `G-contraste`, `G-toque`, `G-foco`, `G-tokens`, `G-h1`, `G-solapamiento`, `G-aparece`, `G-imagenes`, `G-reducido`, `G-sin-js`, `G-red`, `G-errores`) | las de `criterios.md` §2 | 96/96 en las siete páginas | ✅ |
| K1 · Orden (CA1) | «Ver todos los servicios» → «Ver toda la galería» → «Cómo llegar» | en orden | ✅ |
| K2 · Textos del carrusel (CA1, CA4) | «Anterior», «Siguiente», «Foto 1 de 6», «Ver toda la galería» y las seis descripciones | 10/10, también sin JavaScript | ✅ |
| K3 · «Nuestros trabajos» en la primera pantalla de `/galeria` (CA2, CA6) | visible | a la vista a 320, 390, 900 y 1440 px | ✅ |
| K3b · «Volver al inicio» y las descripciones | presentes | 7/7 | ✅ |
| K4 · Galería vacía (CA2) | «Aún no hay fotos de nuestros trabajos.» y «Volver al inicio» | 2/2 | ✅ |
| K5 · «Subir foto» sin desplazarse (CA8) | visible | a la vista a 320, 390 y 1440 px | ✅ |
| K6 · «Consentimiento declarado por» en la tarjeta (CA9) | presente | 1/1 | ✅ |
| K6b · Casilla de consentimiento (CA9) | el texto de CA9 | 1/1 | ✅ |
| K7 y K7b · Mensajes de CA11 | los tres | 3/3 | ✅ |
| K8 · Confirmación al quitar (CA10) | «¿Quitar esta foto?», «Quitar», «Cancelar» | 3/3 | ✅ |
| K9 · Peso de la primera visita | ≤ 1 MB (977 kB) | inicio 262 kB sin el mapa · `/galeria` 183 kB · gestión 163 kB | ✅ |
| K10 · JavaScript de la ruta | ≤ 100 kB | inicio 11 kB sin el mapa · `/galeria` 11 kB · gestión 4 kB | ✅ |
| K11 · Estabilidad visual (CLS) | ≤ 0.1 | inicio **0.212** (la página actual da lo mismo) · `/galeria` 0.003 · gestión ≤ 0.004 | ❌ en el inicio |
| K12 · Líneas de cada titular | ≤ 3 públicas · ≤ 2 gestión | dentro | ✅ |
| K13 · Imágenes de muestra | 0 | 0 (dibujos propios, `data-origen="dibujada"`) | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 20 | 4 | `galeria-1440-claro.png`: la foto 01 domina, la 02 y la 03 se apilan a su lado y la fila 04–06 cierra el bloque. No llega a 5 porque, en el escritorio, el panel del carrusel deja unos 400 px vacíos encima del pie |
| Primera pantalla | 15 | 4 | `inicio-trabajos-390/1440`: etiqueta, título, subtítulo y «Ver toda la galería» se entienden en segundos, y «Foto 1 de 6», «Anterior» y «Siguiente» se ven a los dos anchos; `gestion-1440`: «Subir foto» y «Hay 6 de 12». A 1440 no asoma la foto siguiente |
| Coherencia con el sistema y la marca | 25 | 5 | El numeral «01» en Newsreader granate repite el índice de «Nuestros servicios»; la etiqueta, el título y los botones de píldora son los de la página. En la gestión, el selector de archivo con el botón de la app, el escudo del consentimiento en granate y «Quitar» con contorno |
| Lectura y ritmo | 15 | 4 | `galeria-320`: cada pie en dos o tres líneas, con el numeral colgado a la izquierda; `gestion-error-390`: cada error encima de su campo. Restan el hueco del panel a 1440 y las seis fichas antes del formulario a 390 |
| Diferencia frente a una plantilla | 15 | 3 | El numeral serif enlaza con la lista numerada de servicios, pero la estructura (carrusel con «Anterior» y «Siguiente», mosaico con una foto grande) es la habitual |
| Riqueza visual y movimiento | 10 | 3 | `carrusel-1440-no-preference-durante.png`: la foto se desliza con su pie; con movimiento reducido el cambio es inmediato, y sin JavaScript se ven todas desplazando a mano. Fondo de la banda plano |

Observaciones del evaluador, sin cambiar la nota. Están aplicadas a `c3` como `refinado`, por decisión
del usuario al aprobar el acta, y `c3` se volvió a medir: 96/96 puertas y 41/42 KPIs.
1. En el escritorio, el panel del carrusel dejaba un hueco encima del pie y la foto siguiente no asomaba.
   → El panel se centra junto a la foto y `--carrusel-foto-w` pasa a 90 % desde 900 px.
2. A 390 px, el diálogo «¿Quitar esta foto?» tocaba los bordes. → Ahora lleva un margen lateral de
   `--space-4` y el radio `--radius` de las tarjetas.
3. La casilla de consentimiento se veía antes de responder la pregunta del paciente. → Ahora solo
   aparece al marcar «Sí» (CSS `:has()`; sin soporte, se ve siempre).

## Imágenes de muestra

Ninguna. Las seis láminas de `design/candidatos/c3/dibujos/` son dibujos propios (`data-origen="dibujada"`) que
ocupan el lugar de las fotos en el diseño. La aplicación nunca los publica: muestra las fotos que sube
el administrador y, sin fotos, oculta el carrusel (CA3).

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Inicio con el carrusel | `c3/inicio.html` a 320 y 390 | 900 | 1440 (lámina de foto y panel) | CA1, CA4, CA5, CA15 |
| Inicio sin fotos | la página de inicio actual, sin cambios | — | — | CA3 |
| `/galeria` | `c3/galeria.html` a 320 (una columna) | 900 (dos columnas) | 1440 (tres columnas, la 01 a doble ancho y doble alto) | CA2, CA5, CA6 |
| `/galeria` vacía | `c3/galeria-vacia.html` a 390 | — | 1440 | CA2 |
| Gestión: lista y subir | `c3/gestion.html` a 320 y 390 | — | 1440 (lista 3 · formulario 2) | CA8, CA9, CA10 |
| Gestión: errores | `c3/gestion-error.html` y `c3/gestion-consentimiento.html` | — | 1440 | CA9, CA11 |
| Gestión: quitar | `c3/gestion-quitar.html` | — | 1440 | CA10 |

## Componentes

- **Del sistema:**
  - En las páginas públicas: `.pub-etiqueta`, `.pub-services__head` (la cabecera de la sección, como en el avance de servicios), `.c-btn--secondary` con flecha, `.lb-cabeza` y `.lb-volver` (`/galeria` usa la estructura de `/servicios`), y la entrada M-3 (`data-reveal`) en la cabecera.
  - En la gestión: el layout del administrador (`.topbar` y `.card`), `.formulario-accesible` (`.campo`, `.field-label`, `.field`, `.btn-primary` sobre `--p500`, `.campo-casilla`), `.btn-outline`, `.modal-overlay` y `.modal-box`.
- **Exclusivos de esta spec:**
  - **Carrusel de trabajos** (`.pub-carrusel`): ventana con `overflow-x` y `scroll-snap`, tabulable, con `role="region"` y nombre. Los controles van debajo de un filete de tinta: «Anterior» y «Siguiente» son enlaces a la foto vecina y, con JavaScript, la mueven. Una región de estado anuncia la foto.
  - **Foto de trabajo** (`.pub-trabajo`): foto `--foto-ratio` con radio `--radius-lg`, borde `--border` y `--shadow-soft`. Pie con el numeral en Newsreader (`aria-hidden`), «Foto n de N» como etiqueta y la descripción. En el escritorio, dentro del carrusel, es una lámina con la foto (7) y el panel (5).
  - **Mosaico de `/galeria`** (`.pub-mosaico`): tres, dos o una columna. Con tres, la 01 ocupa dos columnas y dos filas para que las filas cierren.
  - **Galería vacía** (`.pub-galeria__vacia`).
  - **Tarjeta de foto en la gestión** (`.foto-tarjeta`): miniatura `--thumb-w`, posición, descripción, declaración de consentimiento con escudo en `--p600`, y «Arriba», «Abajo» y «Quitar» con contorno, de 44 px.
  - **Pregunta del paciente** (`.campo-paciente`): radios de 44 px con un círculo de `--icon-md`.
  - **Consentimiento** (`.consentimiento`): casilla de 44 px con palomita, el patrón de la casilla del acceso.
  - **Selector de archivo** (`.campo-archivo`), con el botón de la app en `::file-selector-button`.
  - **Aviso de error** (`.aviso-error`) y `.campo-error`.
  - **Menú del administrador:** entrada «Galería» después de «Servicios».

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--foto-ratio` | fijo | `4 / 3` | — | Las fotos se recortan a esta proporción al publicarlas: no hay saltos (CLS) y el mosaico alinea |
| `--carrusel-foto-w` | corte 560 y 900 px | `86%` · `72%` desde 560 px | `90%` desde 900 px | Una foto principal (en el escritorio, la lámina con su panel) y el borde de la siguiente, que dice que la fila sigue |
| `--thumb-w` | fijo | `120px` | — | Miniatura de la tarjeta de la gestión |
| Componentes «Carrusel de trabajos», «Foto de trabajo», «Mosaico de `/galeria`», «Galería vacía» (superficie pública, `publico.css`) | — | — | — | Ver Componentes |
| Componentes «Tarjeta de foto», «Pregunta del paciente», «Consentimiento», «Selector de archivo», «Aviso de error» (dentro de `.formulario-accesible`, `app.css`) | — | — | — | Ver Componentes. Los radios y la casilla de 44 px generalizan el patrón de `.remember-label` (spec 041) |
| Utilidad `.pub-solo-lector` | — | — | — | Región de estado del carrusel, oculta a la vista y leída por el lector de pantalla |
| Entrada «Galería» en el menú del administrador | — | — | — | Toca `layouts/app.blade.php`: la verificación final ejecuta `capturar.mjs --medir` (AGENTS.md) |

No hay colores nuevos: `criterios.json → tokens.colors` no cambia.

## Movimiento

### M-1 — Cambio de foto del carrusel
- **Propósito y frecuencia:** decir que la fila se ha movido y cuál es la foto actual. Ocurre cada vez que se pulsa «Anterior» o «Siguiente».
- **Disparador:** clic, toque o Intro en «Anterior» o «Siguiente». Se repite. Sin avance automático (WCAG 2.2.2).
- **Elementos y propiedades:** `scroll-left` de la ventana (`scroll-behavior: smooth`). La foto actual pasa de `scale(1.04)` a `none`.
- **Duración y curva:** `--duration-reveal` con `--ease-expo` (el desplazamiento usa la curva del navegador).
- **Reducido:** salto directo, sin escala. **Sin JavaScript:** la fila se desplaza a mano y los enlaces van a cada foto. **Táctil:** se desliza con el dedo, con `scroll-snap`.
- **Coste:** 2,6 kB de JavaScript (`galeria.js`) de los 100 kB de P10.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque` y `G-foco`, 96/96).
- **Encabezados y estructura:**
  - Inicio: `h2` «Nuestros trabajos».
  - `/galeria`: `h1` «Nuestros trabajos».
  - Gestión: `h1` «Galería de trabajos» y los `h2` «Fotos publicadas» y «Nueva foto».
  - Las fotos van en `ol` y cada una en `figure` con `figcaption`. La descripción es el `alt`.
- **Teclado:** «Ver toda la galería», luego la ventana del carrusel (tabulable, con flechas para desplazar) y después «Anterior» y «Siguiente».
- **Anuncios:** la región de estado del carrusel (`role="status"`) está montada siempre y vacía, y recibe «Foto n de N y la descripción» al cambiar de foto, sin el numeral decorativo.
- **Formulario:**
  - Cada error va junto a su campo, con `aria-invalid` y `aria-describedby`, y hay un aviso `role="alert"` arriba.
  - La pregunta del paciente es un `fieldset` con `legend`.
  - El diálogo de quitar lleva `role="dialog"`, `aria-modal`, `aria-labelledby` y `aria-describedby`.
- **Decorativos ocultos:** el numeral (`aria-hidden`), las flechas (`aria-hidden`) y las miniaturas de la gestión (`alt=""`, porque la descripción va al lado).

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-07 | diseño | La ronda 1 se diseñó con la página de inicio de la opción B, que la 038 sustituyó | Nuevo ciclo con los criterios K1–K13 de la spec | usuario |
| 2026-11-07 | implícita | La barra lateral del layout del administrador no cumple contraste (DS1, DS4) y el layout carga Roboto de Google Fonts; nada de eso es de la 039 | En las pantallas de la gestión, `medir.mjs` quita la barra lateral de la copia medida (`quitar`) y `G-red` admite `fonts.googleapis.com` y `fonts.gstatic.com`. Lo que diseña la 039 se mide entero | `docs/design/system.md` (DS1, DS4) · `resources/views/layouts/app.blade.php` |
| 2026-11-07 | implícita | El mapa de Google pesa 2,5 MB y trae 1,8 MB de JavaScript | K9 y K10 del inicio se miden con el mapa en blanco (`sin_iframes`), como en la 038 | spec 038, RNF y `design-spec` de la 038 |
| 2026-11-07 | contradicción | K11 (CLS ≤ 0,1) falla en el inicio con 0,212, igual en la página actual sin la 039: lo causan la pastilla de `publico.js` y el cambio de fuentes | Se acepta `c3` con K11 anotado como anterior a la 039; el desplazamiento se corrige aparte con un `/fix` de la 038 (reservar el sitio de la pastilla y estabilizar las fuentes) | usuario |
| 2026-11-07 | supuesto | Textos que la spec no fija | «Hecho en la consulta», «Algunos tratamientos que terminamos aquí.», «Fotos de tratamientos hechos en la consulta.», «Volver al inicio», «Galería de trabajos», «Nueva foto», «Publicar foto», «Elige la foto que quieres publicar.», «No se publicó la foto. Corrige lo marcado y vuelve a subirla.», «Sin pacientes en la foto», «Consentimiento declarado por {nombre} el {fecha}», «Arriba», «Abajo», «¿Quitar esta foto?» con su explicación | razonable; se confirman al aprobar |
| 2026-11-07 | supuesto | Selector de archivo: ¿control propio o nativo? | Nativo con el botón de la app (`::file-selector-button`). Su texto sigue el idioma del navegador; en las capturas sale en inglés porque el Chromium de Docker está en inglés | `evaluate.mjs` mide el foco en el propio control; un control oculto con el foco en la etiqueta no lo mostraría |
| 2026-11-07 | refinado | Tres observaciones del evaluador: el hueco del panel del carrusel en el escritorio, el diálogo de quitar sin margen a 390 px y la casilla de consentimiento visible antes de responder | Aplicadas a `c3` y medidas de nuevo (96/96 puertas, 41/42 KPIs) | usuario |
| 2026-11-07 | diseño | ¿Se aprueba el acta? | Aprobada, con `c3` refinado | usuario |
| 2026-11-08 | refinado | En `/implement` (T030), `ReprogramarCitaVistasTest` (spec 028) exige contraste de 3:1 a todo borde dentro de `.formulario-accesible`; el de la tarjeta de foto (`--border`, 1,31:1) y el del botón «Elegir archivo» (`--p200`, 1,58:1) no llegan | Los dos pasan a `--border-control` (3,93:1 sobre blanco), en `c3/gestion.css` y en `app.css`. `c3` vuelto a medir: `gestion` 12/12 puertas y 7/7 KPIs | usuario |
