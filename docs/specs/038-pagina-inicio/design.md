---
spec: 038-pagina-inicio
status: approved         # draft | approved (solo el usuario aprueba)
exploration: options
system_version: 1.6.0
created: 2026-10-20
updated: 2026-10-20
---

# Diseño · 038 Página de inicio pública (y lo que añaden la 039 y la 040)

Hecho con `/design-spec 038` sobre el sistema `docs/design/system.md`. Cubre a la vez la página de
inicio y `/servicios` (038), el carrusel, `/galeria` y su gestión (039), y las promociones y su
gestión (040), para que la página se piense entera. Las specs 039 y 040 tienen su propio
`design.md`, que remite a este. Composición elegida en `design/opcion-b.html`. Las opciones de la
ronda 1 están archivadas en `design/history/ronda-1/`.

Documentos de partida: `docs/design/nicho.md` (territorio A · «La consulta por dentro»),
`brief.md`, `creative-direction.md` y `anti-cliches.md`, aprobados el 2026-10-20.

## Dirección elegida

**Opción B · «La puerta de la consulta», ronda 2.** En la ronda 1 se eligió entre A · «El
directorio de la entrada» y B · «La puerta de la consulta». El usuario eligió la B y pidió cambios:
fotos de trabajos en lugar de la entrada, un carrusel con su galería, un avance de servicios con su
página, «Sobre nosotros» y promociones. La ronda 2 adapta la B a eso.

Estás frente a la puerta de la consulta. La página empieza en la **placa** de la consulta (logo,
título, «Agendar mi primera cita» y el **aviso de la puerta** con el horario y «Llamar»). Detrás de
la puerta está lo demás:
- quiénes somos, en texto corrido;
- las categorías del catálogo como **puertas**, que llevan a `/servicios`;
- las promociones como **avisos** con su vigencia;
- los **trabajos** en un carrusel;
- la dirección como otra placa, apoyada sobre el mapa.

- **Motivo:** la placa responde en la primera pantalla del móvil a lo que busca quien llega:
  - qué es la consulta;
  - cómo pedir cita;
  - cuándo abre;
  - cómo llamar.

  El resto es un avance breve que lleva a sus páginas, sin saturar la presentación (decisión del
  usuario).
- **Vecino más parecido:** el inicio de sesión y el registro (pantallas públicas del acceso, con
  una tarjeta centrada). La diferencia: la página de inicio es un recorrido de secciones con la
  placa como pieza firma y el aviso de la puerta dentro, no un formulario. Dentro de la app, el
  vecino de `/servicios` es la lista de servicios del admin (tabla con precio y acciones). La
  diferencia: `/servicios` es de lectura, va agrupada por categoría con un índice de puertas y no
  muestra precios.
- **Riesgo asumido:**
  - **Portada sin foto, solo tipográfica.** Se compensa con la placa con borde de 2 px en
    `--p600` y el logo grande, y los trabajos aparecen al bajar.
  - **Las 8 puertas de servicios ocupan 8 filas a 320 px.** Se acepta porque son enlaces útiles:
    cada una lleva a su categoría en `/servicios`.
  - **Las promociones de ejemplo llevan cifras.** Se compensa con las condiciones y la vigencia
    siempre visibles y sin estética de oferta (`anti-cliches.md`).

## Pantallas

Todas en `opcion-b.html`; las anchas se ven a escala y su altura es la del contenido.

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Página de inicio completa (038 + 039 + 040) | `#inicio-320` · `#inicio-m` | `#inicio-i` | `#inicio-d` | 038 CA1, CA3–CA5, CA9 · 039 CA1 · 040 CA1 |
| Página de inicio solo con la 038 | `#inicio-solo038-m` | — | — | 038 CA1 |
| Página de inicio sin servicios activos, promociones ni fotos | `#inicio-vacio-m` | — | — | 038 CA4 · 039 CA3 · 040 CA3 |
| Carrusel sin JavaScript (fila deslizable) | `#inicio-sinjs-m` | — | — | 039 CA4 |
| `/servicios` | `#servicios-m` | — | `#servicios-d` | 038 CA6 |
| `/servicios` sin servicios activos | `#servicios-vacio-m` | — | — | 038 CA6 |
| `/galeria` | `#galeria-m` | — | `#galeria-d` | 039 CA2 |
| `/galeria` vacía | `#galeria-vacia-m` | — | — | 039 CA2 |
| Gestión de la galería | `#g-galeria-m` | — | `#g-galeria-d` | 039 CA8–CA10 |
| Gestión de la galería · subida sin consentimiento | `#g-galeria-error-m` | — | — | 039 CA11 |
| Quitar una foto · confirmación | `#g-quitar-m` | — | — | 039 CA10 |
| Gestión de promociones | `#g-promos-m` | — | `#g-promos-d` | 040 CA4, CA5 |
| Gestión de promociones · errores | `#g-promos-error-m` | — | — | 040 CA6 |

## Composición y jerarquía

- **Orden de lectura de `/`:**
  1. cabecera (logo, Servicios, Promociones, Trabajos, Ubicación, «Iniciar sesión»);
  2. placa;
  3. «Sobre nosotros»;
  4. servicios;
  5. promociones (040);
  6. trabajos (039);
  7. ubicación;
  8. pie.

  Es el mismo orden en todos los anchos. Sin la 039 ni la 040, sus secciones no existen y no
  dejan hueco.
- **Proporciones:**
  - placa: 3:2 (texto · aviso de la puerta);
  - «Sobre nosotros»: 1:2 (título · texto, con ancho de lectura `--measure`);
  - promociones: 1:3 (vigencia · texto), en lista;
  - carrusel: 7:5 (foto · panel);
  - `/servicios`: 1:3 (índice · listas);
  - `/galeria`: mosaico 5:7 que alterna con 7:5 por filas.
- **Rejilla de iguales:** solo en las puertas de servicios (4 columnas). El motivo es que son
  rótulos de igual rango, como las placas de un pasillo, y cada una lleva a su categoría.
- **Puntos de corte:**
  - el del sistema, 900 px: todo pasa a una columna, la placa apila el aviso debajo y las puertas
    y el índice van en 2 columnas;
  - uno propio de esta composición, **560 px**: puertas, índice y mosaico a 1 columna, las
    definiciones de servicio apiladas y los botones de la placa y del carrusel a todo el ancho.
- **Momento protagonista:** M-1, en el carrusel (039). La 038 sola no tiene ninguno; según las
  «Reglas de uso» del sistema, uno por página como máximo.
- **Pantallas de gestión:** dentro de la app (barra lateral, barra superior, Roboto, densidad de la
  app). Formulario arriba y lista debajo. Las entradas «Galería» y «Promociones» se añaden al menú
  del admin.

## Componentes

- **Del sistema:**
  - **Botones:**
    - principal sobre `--p500` (como `.formulario-accesible`, DS1);
    - secundario con borde `--border-control` y texto `--p600`;
    - `.btn-danger` en «Quitar» y «Retirar».
  - **Iconos y campos:**
    - `.btn-icono` para «Mover antes» y «Mover después», con `aria-label` que nombra la foto;
    - campos con el foco del sistema.
  - **Avisos y estados:**
    - avisos `c-alert` (error con `role="alert"`, advertencia del paciente);
    - menú del móvil de la app (spec 033) en las pantallas de gestión.
- **Nuevos, que pasan al sistema** (se reutilizan en varias pantallas):
  - **Placa:** superficie blanca, borde `--border-width-strong` en `--p600`, `--radius-md`,
    relleno `--space-8` (`--space-7` en el móvil). Aparece como placa de la consulta en la portada
    y como placa de la dirección sobre el mapa.
  - **Puerta (enlace de categoría):** alto ≥ `--touch-min`, borde `--border-control`, texto
    `--p600`, fondo `--p100` al pasar el puntero o con foco. En el avance de servicios va sin
    contador; en el índice de `/servicios`, con el número de servicios.
  - **Cabecera pública:** logo enmascarado en `--p600` (como el menú del móvil, 034) y navegación.
    Por debajo de 900 px, el menú es un `<details>` («Menú») que se abre y se cierra con el
    teclado y sin JavaScript. «Iniciar sesión» pasa a «Ir a mi panel» con sesión.
  - **Pie público:** logo, enlaces y © con filo superior.
- **Exclusivos de las páginas públicas** (no se reutilizan en la app):
  - **Aviso de promoción (040):** fila de lista con filo inferior.
    - A la izquierda va la vigencia corta en `--p600` (decorativa, `aria-hidden`).
    - A la derecha van el título (h3), la descripción, «Condiciones: …» y la vigencia completa en
      texto («Válida del … al …»).
    - Lista con filo superior de 2 px. Sin tarjetas, cintillos ni contadores.
  - **Carrusel de trabajos (039):** región con `aria-roledescription="carrusel"`.
    - A la izquierda, la foto 4:3; a la derecha, el panel con «Foto n de N» (`aria-live="polite"`),
      la descripción, «Anterior» / «Siguiente» (≥ 44 px) y «Ver toda la galería».
    - Sin JavaScript es una fila con desplazamiento horizontal propio (`scroll-snap`, el 85 % por
      foto, `tabindex="0"`), que no desborda la página.
  - **Mosaico de `/galeria` (039):** filas de dos fotos que alternan 5:7 y 7:5, con la descripción
    debajo.
  - **Mapa con placa (038):** mapa a todo el ancho de la sección (`--radius-lg`) con la placa de la
    dirección apoyada abajo a la izquierda. En el móvil la placa queda debajo, solapando el borde.
    Lleva la nota «El mapa lo sirve Google».
- **Exclusivos de la gestión:**
  - **Pregunta del paciente (039):** `fieldset` con `legend` «¿Aparece la boca o los dientes de un
    paciente?» y dos radios (Sí · No: corona, prótesis, modelo, laboratorio…).
    - Debajo, la casilla «Tengo el consentimiento escrito del paciente para publicar esta foto y no
      se le puede reconocer», siempre visible (sin JavaScript) con la ayuda «Solo si respondiste
      «Sí». Quedará tu nombre y la fecha.».
    - El error se ve junto a la casilla, en rojo y con texto.
  - **Tarjeta de foto en la gestión (039):** puesto, miniatura, descripción, «Sin paciente» o «Con
    paciente · consentimiento declarado por … el …», mover y quitar.
  - **Estado de promoción (040):** etiqueta con texto y borde: «Se ve ahora» (`--green-texto`),
    «Programada: desde el …» (`--amber` sobre `--amber-bg`), «No se ve: ya hay 3 vigentes que
    vencen antes» y «Vencida el …» (`--p600`). El estado se lee en el texto, no solo en el color.
- **Adornos generados desde datos:** la vigencia corta de cada promoción sale de sus fechas
  («1–30 nov»). Va oculta al lector de pantalla porque la vigencia completa está en el texto.

## Cambios a incorporar al sistema

La primera tarea de la 038 los aplica a `system.md` (versión **1.7.0**) y `system.html`. Los de la
039 y la 040 entran en su primera tarea si la 038 no los necesita.

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-publica` | fijo | `"Lexend", system-ui, sans-serif` | — | Familia de las páginas públicas: lectura fluida y tono cercano (`brief.md`). La app sigue en Roboto (DS9 no cambia) |
| `@font-face` Lexend 400 y 600 | fijo | woff2 latino de `@fontsource/lexend` 5.3.0, servido por la app | — | Sin servicios externos de fuentes (CSP de la 031). Cómo se traen los archivos (dependencia o copia con su licencia OFL) lo decide `/plan` |
| `--text-publico-h1` | fluido | 30px (320) | 48px (1440) | Título de la placa y de `/servicios` y `/galeria` |
| `--text-publico-h2` | fluido | 22px | 30px | Títulos de sección |
| `--text-publico-h3` | fijo | 19px | — | Rótulos de placa, aviso y categoría |
| `--text-publico` | fijo | 17px | — | Lectura en el móvil, con sol (el cuerpo de la app es de 13.5px) |
| `--text-publico-small` | fijo | 15px | — | Condiciones, notas y pies de foto |
| `--space-seccion` | fluido | 40px | 96px | Aire entre secciones públicas |
| `--measure` | fijo | 62ch | — | Ancho de lectura |
| `--page-max` | fijo | 1180px | — | Ancho máximo del contenido público |
| `--foto-ratio` | fijo | 4 / 3 | — | Proporción fija de las fotos de trabajos (039) |
| `--thumb-w` | fijo | 96px | — | Base de la miniatura en la gestión (039) |
| `--duration-base` | fijo | 0.25s | — | Transiciones de interfaz |
| `--duration-firma` | fijo | 0.6s | — | Momento firma (M-1, 039) |
| `--ease-puerta` | fijo | `cubic-bezier(0.2, 0, 0, 1)` | — | Curva sin rebote del territorio |
| `--green-texto` | fijo | `#137A5A` | — | Éxito en texto: 5.30:1 sobre `--white` y 4.95:1 sobre `--bg` (el `--green` de hoy da 2.98:1, DS1) |
| Secundario público = `--p600` | fijo | — | — | `--text-muted` de la app sigue igual que `--text` (DS3); en las páginas públicas el secundario es `--p600` (6.54:1 sobre `--bg`) |
| Corte propio de las páginas públicas | corte 560px | — | — | Una columna en puertas, índice, mosaico y definiciones; acompaña al corte de 900px del sistema |
| Componentes «Placa», «Puerta», «Cabecera pública» y «Pie público» | — | — | — | Se reutilizan en `/`, `/servicios` y `/galeria` |
| Reglas de uso | — | — | — | Páginas públicas: un momento firma como máximo (el carrusel); promociones sin cintillo, contadores ni colores de oferta; fotos nunca bajo texto |
| Pantallas e inventario | — | — | — | `/`, `/servicios`, `/galeria`, gestión de la galería y de promociones, con sus capturas al cerrar cada spec |

## Movimiento

### M-1 — La hoja de la puerta (spec 039)
- **Disparador:** pulsar «Anterior» o «Siguiente» en el carrusel. Ocurre cada vez que el visitante
  cambia de foto y nunca solo.
- **Elementos y propiedades:** la foto que sale y la que entra (`transform`, `opacity`).
- **Duración y curva:** `--duration-firma` / `--ease-puerta`.
- **Inicial → final:**
  - la que sale va de `translateX(0)`, opaca, a `translateX(-30%)`, transparente;
  - la que entra va de `translateX(30%)`, transparente, a `translateX(0)`, opaca;
  - las dos a la vez (al pulsar «Anterior», en sentido contrario).
- **Técnica:** CSS (keyframes) con una clase que pone el JavaScript del carrusel.
- **Reducido:** cambio instantáneo. **Sin JavaScript:** no hay carrusel; la fila deslizable
  muestra todas las fotos. **Táctil:** igual con los botones; deslizar el dedo usa el
  desplazamiento nativo de la fila.
- **Coste:** ~1 kB de JavaScript para el carrusel entero (botones, «Foto n de N», clase de la
  animación), de los 100 kB por ruta de P10. No comparte propiedades con otras animaciones.
- **Fotogramas:** `opcion-b.html#movimiento`.

### Microinteracciones
- Botones y enlaces cambian de color en `--duration-fast` y se hunden 1 px al pulsar
  (`translateY`). Con movimiento reducido, solo cambia el color.
- Puertas e índice: fondo `--p100` con el puntero o con foco. En táctil, sin hover, basta el foco.
- Menú del móvil: `<details>`, sin animación.
- Sin entradas al hacer scroll ni transiciones de página.

## Accesibilidad

- **Contraste** (calculado en `opcion-b.html#color`):

  | Par | Contraste |
  |---|---|
  | texto `--text` sobre `--bg` | 19.59:1 |
  | texto sobre `--white` | 21.00:1 |
  | secundario `--p600` sobre `--bg` | 6.54:1 |
  | secundario `--p600` sobre `--white` | 7.00:1 |
  | rótulo `--p600` sobre `--p100` | 6.05:1 |
  | botón principal: blanco sobre `--p500` | 5.42:1 |
  | blanco sobre `--p600` (hover) | 7.00:1 |
  | error `--red` sobre `--red-bg` | 6.13:1 |
  | `--green-texto` sobre `--white` | 5.30:1 |
  | `--green-texto` sobre `--bg` | 4.95:1 |
  | texto sobre `--amber-bg` | 18.31:1 |
  | texto sobre `--p100` (zona más difícil) | 18.13:1 |
  | borde de control `--border-control` sobre `--white` | 3.93:1 |
  | borde de placa y foco `--p600` sobre `--bg` | 6.54:1 |

  La barra lateral de la app (blanco sobre `#C75078`, 4.33:1) sigue siendo deuda DS1: las
  pantallas de gestión no la cambian.
- **Encabezados y estructura:**
  - Hay un solo `h1` por página: el título de la placa en `/` y «Nuestros servicios» o «Nuestros
    trabajos» en sus páginas. El aviso de la puerta es un `h2`.
  - Las secciones son `h2`, y las categorías y las promociones son `h3`.
  - Las categorías de `/servicios` son `h2`, cada una con su lista de definiciones.
  - La navegación lleva `aria-label`.
- **Teclado:** el orden del HTML es el orden visual.
  - El menú del móvil es un `<details>` (Intro o Espacio).
  - El carrusel se recorre con Tab hasta «Anterior» y «Siguiente».
  - La fila sin JavaScript es enfocable y se desplaza con las flechas.
  - El foco del sistema (2px `--p600`) se ve en todo elemento interactivo.
- **Decorativos ocultos:** vigencia corta de las promociones y logos con `role="img"` y nombre («Melissa
  López N. · Odontología integral»).
- **Textos alternativos:** la descripción de cada foto es su texto alternativo y además se ve escrita
  (039 CA1, CA2).
- **Sin animación:** todo el contenido está en el HTML del servidor, visible sin JavaScript y con
  movimiento reducido. El carrusel nunca avanza solo (WCAG 2.2.2).

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Foto de stock de sonrisa en la portada | Portada tipográfica con la placa; solo fotos reales de trabajos, subidas por el admin |
| Azul o blanco clínico, verde menta | Rosa de la marca como señalética sobre `--bg` y blanco |
| Iconos de muela o brackets | Sin iconos en servicios: cada categoría se nombra en su puerta |
| Cuadrícula de tarjetas icono + título + texto para los servicios | Puertas de texto que llevan a `/servicios`, y listas de definiciones agrupadas allí |
| Promociones en cintillo, con contadores o colores de oferta | Lista de avisos sobria, con condiciones y vigencia en texto |
| Carrusel que avanza solo | Solo con «Anterior» y «Siguiente», y «Foto n de N» |
| Carrusel de reseñas y logos inventados | Nada inventado: sin reseñas mientras no existan |
| Todo entra con fundido al hacer scroll | Un solo momento (M-1) y solo cuando el visitante cambia de foto |
| Mismo esqueleto en todas las secciones | Cada sección tiene su forma: placa, texto 1:2, puertas, avisos, carrusel 7:5 y mapa con placa |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | diseño | Territorio de identidad | A · «La consulta por dentro» (`nicho.md`) | usuario |
| 2026-10-20 | diseño | Ronda 1: A «El directorio de la entrada» o B «La puerta de la consulta» | B, con cambios de contenido que llevaron a dividir la spec en 038, 039 y 040 | usuario |
| 2026-10-20 | diseño | Ronda 2: una sola opción B refinada para las tres specs | Aprobada para escribir este documento | usuario |
| 2026-10-20 | diseño | Tipografía de las páginas públicas | Lexend 400/600, servida por la app; la app sigue en Roboto | usuario (opción B) |
| 2026-10-20 | diseño | Momento firma | M-1 en el carrusel, al cambiar de foto; la portada sin animación | `creative-direction.md` |
| 2026-10-20 | supuesto | `public/images/cover-bg.jpg` (foto de banco, uniforme azul) | No se usa; se retira en la 038 si nada más la usa | `brief.md` · `anti-cliches.md` |
| 2026-10-20 | supuesto | Índice de `/servicios` en el escritorio | Fijo al desplazarse (`position: sticky`); en el visor va estático para que el marco se lea bien | razonable |
| 2026-10-20 | supuesto | Casilla de consentimiento sin JavaScript | Siempre visible, con la ayuda «Solo si respondiste «Sí»»; el servidor la exige solo si la respuesta es «Sí» | 039 CA9, CA11 (P10, sin JS) |
| 2026-10-20 | diseño | ¿Se aprueba este documento? | Aprobado | usuario |
