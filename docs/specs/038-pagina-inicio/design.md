---
spec: 038-pagina-inicio
status: draft            # draft | approved (solo el usuario aprueba)
exploration: options
system_version: 1.8.0
created: 2026-10-20
updated: 2026-10-21
---

# Diseño · 038 Página de inicio pública

Hecho con `/design-spec 038`, ronda 5, sobre el sistema `docs/design/system.md`. Parte de la
referencia del usuario (`design/referencia/`, hecha con UI/UX Pro Max), contrastada con CA1–CA10 y con
el sistema. Composición en `design/opcion-a.html` (ficha) y `design/opcion-a-pagina.html` (página de
prueba), con su crítica en `design/critica-a.md`. Las rondas anteriores se conservan en
`design/history/` (ronda 1; ronda 2, que siguen la 039 y la 040; ronda 3, la línea de abound; ronda 4,
las tres opciones desde cero).

## Dirección elegida

**Opción A · «La ventana del diente»**, una sola composición a petición del usuario (2026-10-21), sobre
su referencia.

En esta pantalla, **el contorno del diente del logo de la consulta** se convierte en **la ventana por
la que se ve la consulta: enmarca la foto de la marca en la portada, se abre al bajar hasta llenar la
pantalla y vuelve como marca de agua en la tarjeta de la agenda**; el nombre cierra la página a todo el
ancho.

- **Modo y registro:** persuadir · firme. `/servicios` es de leer: conserva su estructura (índice y
  listas por categoría) con la cabecera, el pie, la tipografía y los botones de esta opción.
- **Prueba del competidor:** con otra clínica del centro la frase deja de funcionar: el diente con la M
  es el logo de Melissa López N., la foto es la de su marca, las promesas salen de su texto aprobado
  (CA3) y el horario y la cita en línea son los suyos.
- **Reparto:** sin reparto nuevo (decisión del usuario: una sola opción sobre su referencia); escena
  `ventana` del catálogo de la referencia; la semilla 180218 de la ronda 4 queda en su historial.
- **Motivo:** el usuario encontró genéricas las rondas anteriores y trajo esta dirección; la ventana
  sale del sujeto (su logo y su foto), no de la categoría.
- **Vecino más parecido:** la ronda 3 (portada a sangre con foto y velo) → aquí la foto vive dentro de
  la forma del logo y se abre en una escena propia; ninguna pantalla de la app (Roboto, barra lateral)
  comparte esqueleto.
- **Riesgo asumido:** la foto solo existe a 900 px → la escena la amplía; se sustituye por el original
  si aparece. Dos familias tipográficas nuevas (≈ 140 kB) → dentro del 1 MB de `/`, se mide al
  implementar.

## Cosa viva

**«¿Está abierto ahora?»** en la portada. Con el horario de `config/clinica.php`
(`contacto.horario_semanal`, en `data-horario`) y la hora de Lázaro Cárdenas (`data-zona`), una
pastilla blanca dice «Abierto ahora · cerramos a las 20:00» o «Cerrado · abrimos mañana a las 9:00»
(«hoy» antes de abrir, «el lunes» desde el sábado por la tarde), con un punto `--green-texto` o
`--p400`; en la ubicación se marca la fila de hoy de la semana («· hoy»). **Sin JavaScript:** no hay
pastilla (vacía, oculta) y el horario completo se lee siempre al lado y en la ubicación (CA1). Se
escribe con `textContent`.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Página de inicio · reposo | `opcion-a.html#opcion-a-pagina-320` y `-390` | `opcion-a.html#opcion-a-pagina-900` | `opcion-a.html#opcion-a-pagina-1440` | CA1, CA3, CA4, CA5, CA7, CA8, CA9 |
| Página de inicio · menú abierto | `opcion-a-pagina.html#estado-menu` a 390 | — | — | CA9 |
| Página de inicio · sin servicios activos | `opcion-a-pagina.html#estado-sin-servicios` (sin la sección de servicios) | — | — | CA4 |
| Página de inicio · recorrido (escena, entradas) | tira `.ai/inspect/r5/film-320-*` | — | tira `.ai/inspect/r5/film-1440-*` | CA9 |
| `/servicios` | estructura de la ronda 2 (`history/ronda-2/opcion-b.html#servicios-m`) con la cabecera, el pie, la tipografía y los botones en píldora de esta opción | — | `#servicios-d` con los mismos cambios | CA6 |

## Composición y jerarquía

- **Orden de lectura (CA1):** cabecera (logo, Servicios, Ubicación, Iniciar sesión, Crear cuenta;
  «Menú» por debajo de 900 px); presentación (titular, texto, «Agendar mi primera cita», «Ver
  servicios», pastilla, horario y teléfono; a la derecha la ventana del diente con «Antes de empezar,
  te explicamos cada paso.»); escena de la foto; «Sobre nosotros» (campo rosa); «Antes de cada
  tratamiento» (tres promesas); «Nuestros servicios» (índice); tarjeta «Agenda sin llamar» (decisión
  del usuario); «Encuéntranos fácilmente» (dirección, semana, teléfono, «Cómo llegar», mapa); pie.
  Igual en todos los anchos.
- **Proporciones:** presentación 1.25fr : 1fr; «Sobre nosotros», «Antes de cada tratamiento» y la
  ubicación 5fr : 7fr; el índice a todo el ancho (`--page-ancho` 1440 px); la tarjeta de la agenda
  1.4fr : auto; el nombre del pie a todo el ancho.
- **Puntos de corte:** los del sistema: 900 px (navegación, columnas, título fijo) y 560 px (botones a
  todo el ancho, foto de la escena contenida sobre el rosa).
- **Momento protagonista:** M-1, la ventana que se abre. El titular y el trazo (M-2, M-3) son la firma
  de la primera pantalla.

## Componentes

- **Del sistema:** foco `--focus-ring` (`--p600`) e inverso sobre la tinta; `<details>` del menú;
  `--touch-min` en todo lo que se toca.
- **Superficie «Sitio público» (pasan al sistema):**
  - **Botón en píldora** (`.btn`): `--radius-pill`, alto `--touch-min` (56 px en `--lg`), Montserrat 600.
    Principal: `--p400` con tinta (con el puntero, `--p200`); tinta: `--text` con blanco (con el
    puntero, `--p800`); fantasma: borde `--border-control`. Cede al pulsar (`scale(.97)`) y la flecha
    avanza 3 px.
  - **Pastilla de estado** (`.status`): blanca, `--shadow-soft`, esquinas de 22 px, punto de 10 px.
  - **Promesa** (`.promise__item`): blanca, 24 px de radio, `--shadow-soft`, icono de 44 px en `--p400`,
    título Cormorant 600 y texto `--p600`.
  - **Índice de categorías** (`.index`): filete superior de tinta, filas de 88 px con el nombre en
    `--text-indice`, la cuenta en `--p600` y la flecha en un círculo de 48 px; con el puntero o el
    foco, la fila se llena de `--p100` desde abajo, el nombre se desplaza 12 px y la flecha gira.
  - **Tarjeta de la agenda** (`.book__card`): tinta con texto `--p100`, 36 px de radio, el contorno
    del diente como marca de agua.
  - **Cabecera pública** (`.bar`): fija arriba, `--p50`, filete `--border` al bajar; «Menú» en píldora
    con borde `--border-control` por debajo de 900 px.
- **Exclusivos de esta pantalla:** **la ventana del diente** (`.tooth`: la foto recortada dentro del
  contorno del logo, con su trazo `--p400` y la etiqueta blanca) y **su escena** (`.open`: velo
  `--p50` con un hueco en forma de diente que crece).
- **Adornos generados desde datos:** la marca «· hoy» de la semana sale del horario; el filete de
  cada sección (`.kicker-line`) es decorativo (`aria-hidden`).

## Cambios a incorporar al sistema

Versión **1.9.0** de `system.md` (y `system.html`): superficie «Sitio público». Mismos colores y logo;
el panel no cambia. Las fuentes van en woff2 servidas por la app (`public/fonts/`), con su `OFL.txt`.

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-display` | fijo | `"Cormorant Garamond", ui-serif, Georgia, serif` | — | Títulos del sitio público (500 y 600 de `@fontsource/cormorant-garamond` 5.3.0, OFL-1.1, Fontsource, publicada el 2026-07-19) |
| `--font-publica` | fijo | `"Montserrat", ui-sans-serif, system-ui, sans-serif` | — | Texto del sitio público (400–700 de `@fontsource/montserrat` 5.3.0, OFL-1.1, 2026-07-19); sustituye a Lexend en las páginas públicas |
| `--text-display` | fluido | 52px (320) | 120px (1440) | Titular de cartel, una palabra por línea |
| `--text-statement` | fluido | 28px | 44px | Frase de entrada de «Sobre nosotros» |
| `--text-seccion` | fluido | 40px | 80px | Títulos de sección |
| `--text-indice` | fluido | 28px | 56px | Nombres del índice de categorías |
| `--text-firma` | fluido | 76px | 360px | El nombre a todo el ancho en el pie |
| `--text-lead` | fluido | 18px | 21px | Texto de presentación |
| `--shadow-soft` | fijo | `0 1px 2px rgba(114, 36, 62, 0.05), 0 12px 32px -12px rgba(114, 36, 62, 0.24)` | — | Promesas, pastilla, etiqueta de la ventana y menú (sombra teñida del rosa) |
| `--ease-in-out` | fijo | `cubic-bezier(0.77, 0, 0.175, 1)` | — | El trazo del diente |
| `--ease-expo`, `--duration-reveal`, `--stagger`, `--radius-pill` | fijo | los de 1.8.0 | — | Se conservan: entradas y píldoras |
| `--page-ancho` | fijo | 1440px | — | Ancho de cartel del sitio público (la app sigue con `--page-max`) |
| `--gutter` | fluido | 16px | 56px | Margen lateral del sitio público |
| `--space-seccion-publica` | fluido | 80px | 168px | Aire entre secciones |
| Componentes «Sitio público» | — | — | — | Botón en píldora (principal, tinta, fantasma), pastilla de estado, promesa, índice de categorías, tarjeta de la agenda, cabecera pública con «Menú» |
| Reglas de uso (páginas públicas) | — | — | — | Una escena por página; la foto de la marca solo dentro de la forma del logo o a pantalla completa en su escena; todo movimiento solo sin `prefers-reduced-motion: reduce` y lo que oculta, solo con JavaScript; nada se presenta como el equipo |
| Se retiran de las páginas públicas | — | — | — | Lexend y los componentes 1.8.0 de la ronda 3 que esta dirección no usa (portada a sangre con velo, franja con su pausa, tarjeta de categoría) |
| `docs/design/creative-direction.md` | — | — | — | Concepto firma: la ventana del diente; tipografía Cormorant Garamond y Montserrat; registro firme en `/` |

## Imágenes

| Imagen | Origen | Licencia y crédito | Sustituir antes de publicar |
|---|---|---|---|
| Foto de la marca: revisión con espejo teñida de rosa, con el logo encima (`design/referencia/access.webp`, 900 × 900) y su recorte sin logo (`design/referencia/access-recorte.webp`, 520 × 600) | Propia de la clínica (decisión del usuario, 2026-10-21), extraída de la referencia; `/implement` la guarda en `public/images/brand/` | Propia; sin crédito externo | No; si existe un original mayor, se usa ese |
| Logo «Melissa López N. · Odontología integral» | `public/images/logo-icon.png`, por máscara | Propio | No |

## Movimiento

Capa del modo persuadir: una escena, la firma de la primera pantalla, entradas por sección y
respuesta bajo la mano. Vivo en `design/opcion-a-pagina.html`; un solo script común (cosa viva,
entradas, escena, cabecera y menú).

### M-1 — La ventana se abre
- **Tipo y filtro:** escena (`ventana`); una vez por visita; es el momento protagonista: la consulta,
  vista por la forma de su logo, se abre entera.
- **Disparador:** progreso del desplazamiento nativo (hold 1.4); no captura la rueda.
- **Elementos y propiedades:** máscara SVG con el contorno del diente; `transform: scale()` del
  hueco según `--p` (de 1 a ≈ 19), sobre la foto a pantalla completa.
- **Duración y curva:** las del desplazamiento; **inicial → final:** un diente pequeño en el centro → la
  foto entera.
- **Técnica:** CSS con la variable `--p` que pone el script común.
- **Reducido:** la foto entera, sin velo · **Sin JavaScript:** igual · **Táctil:** igual ·
  **Teléfono:** la misma escena; por debajo de 560 px la foto contenida sobre `--p400`.
- **Coste:** parte del script común; comparte `transform` solo consigo misma.
- **Fotogramas:** `.ai/inspect/r5/film-1440-02` y `-03`.

### M-2 — El trazo del diente
- **Tipo y filtro:** firma; una vez al cargar; dibuja el logo alrededor de la foto.
- **Elementos y propiedades:** `stroke-dashoffset` del trazo (2.2 s, `--ease-in-out`, 0.35 s de
  espera); la foto se asienta de `scale(1.06)` y opacidad 0 a reposo (1.3 s, `--ease-expo`).
- **Técnica:** CSS bajo `.js`.
- **Reducido:** ya dibujado · **Sin JavaScript:** ya dibujado · **Táctil:** igual.

### M-3 — El titular por líneas
- **Tipo y filtro:** entrada de la primera pantalla; una vez.
- **Elementos y propiedades:** cada línea sube desde su máscara (`translateY(105%)` → 0), 1 s con
  `--ease-expo`, escalonadas 90 ms.
- **Reducido y sin JavaScript:** quieto y legible desde el primer fotograma.

### M-4 — Entradas por sección
- **Tipo y filtro:** entrada; una vez por sección.
- **Disparador:** la parte superior llega al 88 % de la ventana.
- **Elementos y propiedades:** títulos desde una máscara (`clip-path`), párrafos, promesas y filas del
  índice que suben 24 px; `opacity`; `--duration-reveal` con `--ease-expo`, escalonado `--stagger`,
  cuatro pasos como máximo.
- **Reducido:** todo visible · **Sin JavaScript:** todo visible (la clase `js` la pone el script).

### M-5 — Respuesta bajo la mano
- **Tipo y filtro:** respuesta; varias veces por visita: rápida.
- **Elementos y propiedades:** botones que ceden (`scale(.97)`, 140 ms, `--ease-out`) y flecha que
  avanza 3 px; filas del índice que se llenan de `--p100` (420 ms), nombre que se desplaza 12 px y
  flecha que gira; menú que aparece desde su esquina (220 ms).
- **Reducido:** solo el color · **Táctil:** el foco; sin hover.

### M-6 — «¿Está abierto ahora?»
- **Tipo:** cosa viva; sin animación (el texto aparece ya escrito); `aria-live="polite"`.

## Inspección

- **Rondas:** 2 de 3 · **Inspector:** 0 FAIL · 4 WARN (`titular-largo` en los cuatro anchos,
  defendido: una palabra por línea, como cartel) · **Crítica:** `design/critica-a.md`.
- **Tres problemas corregidos:** el horario desaparecía al escribirse la pastilla (en la referencia);
  el nombre del pie no llegaba a todo el ancho; la pastilla partía su píldora a 320 px.

## Accesibilidad

- **Contraste** (calculado en `opcion-a.html#color`): texto sobre `--p50` 19.59:1; tinta sobre
  `--p400` («Sobre nosotros» y botón principal) 5.34:1; tinta sobre `--p200` (botón con el puntero)
  13.31:1; blanco sobre tinta 21.00:1; `--p600` sobre `--p50` 6.54:1 y sobre blanco 7.00:1; tinta sobre
  `--p100` (índice) 18.13:1; `--p100` sobre tinta (agenda) 18.13:1; `--p600` sobre `--p100` (pie)
  6.05:1; `--border-control` sobre `--p50` 3.67:1 (no texto); `--green-texto` sobre blanco 5.30:1 (punto).
- **Encabezados y estructura:** un `h1` (el titular); `h2` por sección; `h3` en las promesas; `nav`
  «Secciones» (cabecera y menú) y «Pie».
- **Teclado:** orden del HTML = orden visual; foco del sistema; el menú se cierra con Escape y devuelve
  el foco a «Menú»; las filas del índice son enlaces enteros.
- **Decorativos ocultos:** filetes, trazo, marca de agua, iconos de las promesas, flechas y el nombre
  del pie; la foto de la ventana y la de la escena llevan su texto alternativo (son de la clínica).
- **Sin animación:** todo el contenido en el HTML, visible sin JavaScript y con movimiento reducido.

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Foto de stock en la portada | La foto propia de la marca, dentro del contorno de su logo |
| Iconos de muela genéricos | El único diente es el del logo de la consulta; las promesas llevan trazos lineales propios |
| Azul o blanco clínico y verde menta | El rosa de la marca, la tinta y `--p50`; el verde solo como punto de «abierto» |
| Tríada de tarjetas icono + título + texto | Las tres promesas salen del texto aprobado (CA3) y van junto a su título fijo, no como ventajas |
| Grid de cards idénticas sin jerarquía | Índice editorial a todo el ancho, con el nombre en tipo de cartel |
| Primera pantalla distinta seguida de plantilla | El diente vuelve en la escena y en la tarjeta de la agenda; el nombre cierra el pie |
| Página quieta | La escena, el trazo, las entradas, el índice que responde y la cosa viva |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-21 | diseño | ¿Rediseñar desde cero? | Sí; rondas 4 (tres opciones) y 5 (una sola opción sobre la referencia del usuario) | usuario |
| 2026-10-21 | diseño | Base de la ronda 5 | La referencia «La ventana del diente» (`design/referencia/`, UI/UX Pro Max), contrastada con CA1–CA10 y el sistema | usuario |
| 2026-10-21 | contradicción | La referencia usa una paleta que no es la del sistema («system.md 2.0.0») | Colores del sistema, como pide el usuario («mismos colores y logo») | usuario · `system.md` |
| 2026-10-21 | diseño | Foto de la portada | Propia de la clínica; se usa la versión incrustada en la referencia (900 px) | usuario |
| 2026-10-21 | diseño | Textos de las tres promesas de «Antes de cada tratamiento» | Aprobados tal cual | usuario |
| 2026-10-21 | diseño | Tarjeta «Agenda sin llamar» (no está en el orden de CA1) | Se mantiene, entre servicios y ubicación | usuario |
| 2026-10-21 | diseño | Tipografía y superficie «Sitio público» | Cormorant Garamond (títulos) y Montserrat (texto) en woff2 servidas por la app; `--text-display`, `--text-statement`, `--shadow-soft`, píldoras y tokens de movimiento; el panel no cambia | usuario |
| 2026-10-21 | implícita | Mapa | El iframe de Google con carga diferida, `title` y `referrerpolicy`, y su nota | spec CA5, CA7 · plan TM4 |
| 2026-10-21 | implícita | Horario en la presentación | Siempre visible junto a la pastilla (en la referencia lo sustituía) | spec CA1 |
