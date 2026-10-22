---
spec: 038-pagina-inicio
status: approved         # draft | approved (solo el usuario aprueba)
exploration: options
system_version: 1.7.2
created: 2026-10-20
updated: 2026-10-21
---

# Diseño · 038 Página de inicio pública

Hecho con `/design-spec 038` (ronda 3, motor creativo de AI-DD 1.16.0) sobre el sistema
`docs/design/system.md`. Composiciones en `design/opcion-c.html` y `design/opcion-d.html`, con sus
páginas de prueba (`opcion-c-pagina.html`, `opcion-d-pagina.html`) y su crítica (`critica-c.md`,
`critica-d.md`). Las rondas anteriores se conservan: la ronda 1 en `design/history/ronda-1/`, y la
ronda 2 (opción B «La puerta de la consulta», con el refinado D15) en
`design/history/ronda-2/design.md` y `design/opcion-b.html`. El diseño de la 039 (galería) y la 040
(promociones) sigue en la ronda 2 hasta su propio `/design-spec`, que lo adaptará a esta línea.

## Dirección elegida

**Opción C · «La línea de abound»**, elegida entre C · «La línea de abound» (firme) y D · «La
ventana de la consulta» (audaz); **combinada con la ubicación de D** (mapa grande a la izquierda,
3:2). Es la línea de la landing que el colaborador fusionó en `main` (merge `f096e17`), llevada al
sistema de Dentissa: el usuario encontró «muy básica» la opción B y pidió seguir esa línea
(decisión del usuario, 2026-10-21).

En esta pantalla, **el letrero de horario de la puerta de Av. Insurgentes** se convierte en **una
franja viva que dice si la consulta está abierta ahora, pasa por la página en tipo grande y vuelve en
la ubicación y el pie**; la portada es la de abound: la consulta en grande y el mensaje encima.

- **Modo y registro:** persuadir · firme. `/servicios` es de leer: conserva la estructura de la ronda 2
  con la cabecera, el pie, los botones en píldora y el título centrado de esta opción.
- **Prueba del competidor:** con otra clínica del centro de Lázaro Cárdenas la frase deja de
  funcionar en lo que la sostiene: el horario, la dirección, el nombre y la agenda en línea son de
  Dentissa; la portada con foto y velo, en cambio, es de la categoría (la toma de abound a propósito,
  decisión del usuario).
- **Reparto:** semilla 371542 → escena `hoja-sobre-imagen` y jugada `marquesina` (C); `ventana` e
  `indice-con-vista` se descartaron al combinar.
- **Motivo:** la línea que el usuario quiere (abound) con lo que el motor exige: una cosa viva con
  datos reales, una escena, entradas distintas por contenido, plenitud y el pie como cierre.
- **Vecino más parecido:** la opción B de la ronda 2 (placa sobre fondo claro) → C cambia la
  estructura: portada a sangre con foto, hoja que sube sobre ella, tarjetas, franja del horario,
  ubicación 3:2 y pie con el nombre. Las pantallas de la app (Roboto, barra lateral) no comparten
  ningún esqueleto.
- **Riesgo asumido:** la foto de la portada es de banco (ver "Imágenes": libre y acreditada) → velo
  de la marca, sin presentarla como el equipo de la consulta, y sustitución por una foto real cuando
  la haya;
  tarjetas con icono y franja tipográfica rozan clichés → son las 7 categorías reales con su número
  de tratamientos y el horario real, una sola franja.

## Cosa viva

**«¿Abierto ahora?»** en la portada, junto a «Agendar mi primera cita». Con el horario de
`config/clinica.php` (lunes a viernes 9:00–20:00, sábados 9:00–14:00) y la hora de Lázaro Cárdenas
(`America/Mexico_City`), una pastilla blanca dice «Abierto ahora · cierra a las 20:00» o «Cerrado
ahora · abre mañana a las 9:00» (o «el lunes»); debajo, el horario completo y el teléfono. Es
distinta en cada visita y prueba lo que dice la página: si está cerrado, la cuenta se crea y la cita
se agenda igual. **Sin JavaScript:** no hay pastilla; se lee el horario completo y el teléfono, como
pide CA1. En el teléfono cabe en una línea a 320 px.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Página de inicio · reposo | `opcion-c.html#opcion-c-pagina-320` y `-390` | `opcion-c.html#opcion-c-pagina-900` | `opcion-c.html#opcion-c-pagina-1440` | CA1, CA3, CA4, CA5, CA8, CA9 |
| Página de inicio · recorrido (hoja, franja, entradas) | tira `.ai/inspect/opcion-c/film-320-*` | — | tira `.ai/inspect/opcion-c/film-1440-*` | CA9 |
| Página de inicio · sin servicios activos | sin la sección de servicios (CA4), como en la ronda 2 | — | — | CA4 |
| `/servicios` | estructura de la ronda 2 (`opcion-b.html#servicios-m`) con la cabecera, el pie, las píldoras y el título centrado de C | — | `opcion-b.html#servicios-d` con los mismos cambios | CA6 |

## Composición y jerarquía

- **Orden de lectura (CA1):** cabecera; portada (logo, titular, texto, «Agendar mi primera cita», «¿Abierto
  ahora?», horario y teléfono); «Sobre nosotros» (la hoja que sube); «Nuestros servicios» (tarjetas y
  «Ver todos los servicios»); franja del horario (decorativa); «Encuéntranos fácilmente» (mapa y
  datos); pie. Igual en todos los anchos.
- **Proporciones:** portada de 100svh, con el texto en el 60 % izquierdo sobre el tramo fuerte del velo
  (el titular, a todo el ancho de la columna de contenido); «Sobre nosotros» centrado en `--measure`;
  tarjetas en rejilla de iguales (4 · 2 columnas) porque las categorías tienen el mismo rango;
  ubicación 3:2 con el mapa a la izquierda; pie en tres columnas y el nombre a todo el ancho.
- **Puntos de corte:** 900 px (menú con la hamburguesa, velo uniforme, ubicación apilada, tarjetas a
  2 columnas, pie apilado) y 560 px (botones a todo el ancho).
- **Momento protagonista:** M-1 (la hoja que sube sobre la portada). La franja (M-2) es el segundo y
  único otro; ningún bucle más.

## Componentes

- **Del sistema:** cabecera pública rosa con la hamburguesa (1.7.1); pie público rosa en columnas
  (1.7.1); foco del sistema y foco inverso sobre el rosa; `c-link`.
- **Cambian en esta spec (pasan al sistema):**
  - **Botón en píldora** (`.c-btn`): `--radius-pill`, `--border-width-strong`, alto `--touch-min`;
    principal `--p500` con `--sombra-tarjeta-alta`, claro (blanco con `--p600`) en la cabecera y la
    portada, secundario con borde `--border-control`. Cede al pulsar (`scale(0.97)`).
  - **Tarjeta de categoría** (`.tarjeta`): blanco, borde `--border`, `--radius`, `--sombra-tarjeta`;
    icono en círculo `--p100` con trazo `--p600` (`--icono-tarjeta`); nombre `--text-publico-h3` y
    «N tratamientos» en `--p600`. Con el puntero o el foco: borde `--border-control`, sombra alta e icono
    invertido (`--p600` con trazo blanco); no se levanta.
- **Exclusivos de esta pantalla:**
  - **Portada a sangre:** foto (`object-position` a la derecha), velo `--velo-portada` →
    `--velo-portada-suave` de izquierda a derecha (uniforme por debajo de 900 px), titular
    `--text-portada` con «prioridad.» en cursiva `--p200`, logo blanco.
  - **Pastilla «¿Abierto ahora?»** (`.estado-ahora`): blanca, texto `--p600`, punto del color del
    texto, sin salto de línea.
  - **Franja del horario** (`.marquesina`): campo `--p600` a todo el ancho con el horario en
    `--text-marquesina` y `--p100`.
  - **Pausa de la franja** (`.marquesina-pausa`, refinado A23): `button` de `--touch-min` × `--touch-min`
    en el extremo derecho de la franja, fuera de la zona `aria-hidden`, con el icono de pausa o de
    reproducir (SVG `--icon-md` en `--p100`, `aria-hidden`) y borde `--border-width-strong` en `--p100`;
    nombre fijo «Pausar la franja del horario» y estado en `aria-pressed`; foco `--focus-ring-inverso`.
    Lleva el atributo `hidden` en el HTML y lo quita el script común, que es quien lo maneja.
- **Adornos generados desde datos:** los iconos de categoría (SVG dibujados en código, uno por
  categoría conocida, la muela para las demás) y la franja (sale del horario de `config/clinica.php`);
  `aria-hidden` los dos.

## Cambios a incorporar al sistema

Versión **1.8.0** de `system.md` (y `system.html`), en la primera tarea del rediseño.

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--text-portada` | fluido | 40px (320) | 96px (1440) | Titular de la portada, ≥ 4× el cuerpo (motor creativo §6) |
| `--leading-portada` | fijo | 0.98 | — | Interlineado del titular de portada y de la franja |
| `--tracking-portada` | fijo | -0.03em | — | Interletrado del titular de portada y de la franja |
| `--text-marquesina` | fluido | 34px | 80px | Franja del horario |
| `--velo-portada` | fijo | `rgba(114, 36, 62, 0.82)` (`--p800` al 82 %) | — | Blanco ≥ 6.41:1 sobre la foto en el peor caso |
| `--velo-portada-suave` | fijo | `rgba(114, 36, 62, 0.35)` | — | Extremo derecho del degradado, donde se ve la foto |
| `--radius-pill` | fijo | 999px | — | Botones, índice de `/servicios` y pastilla (línea de abound) |
| `--sombra-tarjeta` | fijo | `0 1px 2px rgba(114, 36, 62, 0.08)` | — | Tarjetas en reposo |
| `--sombra-tarjeta-alta` | fijo | `0 14px 32px rgba(114, 36, 62, 0.16)` | — | Botón principal y tarjeta con el puntero |
| `--icono-tarjeta` | fijo | 56px | — | Círculo del icono de la tarjeta |
| `--ease-out` | fijo | `cubic-bezier(0.23, 1, 0.32, 1)` | — | Respuestas por debajo de 600 ms (movimiento 1.16.0) |
| `--ease-expo` | fijo | `cubic-bezier(0.16, 1, 0.3, 1)` | — | Entradas de sección |
| `--duration-reveal` | fijo | 800ms | — | Entradas de sección |
| `--stagger` | fijo | 80ms | — | Escalonado entre partes (cuatro pasos como máximo) |
| `--duration-marquesina` | fijo | 48s | — | Una vuelta de la franja |
| Componentes «Botón en píldora» y «Tarjeta de categoría» | — | — | — | Se reutilizarán en la 039 y la 040 |
| Reglas de uso (páginas públicas) | — | — | — | Una escena y una franja como máximo por página; entradas que cambian con el contenido; nada se levanta al pasar el puntero; foto solo bajo un velo de la marca |
| `docs/design/anti-cliches.md` | — | — | — | Las seis filas nuevas de AI-DD 1.16.0 (página quieta, idea de categoría, secciones delgadas, primera pantalla seguida de plantilla, pantalla de carga, scroll secuestrado) y los clichés que esta opción roza con su alternativa |
| `docs/design/creative-direction.md` | — | — | — | Secciones «Modo y registro» (registro máximo de las páginas públicas: audaz; la 038, firme) e «Imágenes disponibles» (la foto libre de la portada, acreditada, y el logo) |

## Imágenes

| Imagen | Origen | Licencia y crédito | Sustituir antes de publicar |
|---|---|---|---|
| Portada: una consulta dental (dentista y paciente, luz clara, encuadre con el sujeto a la derecha) | Foto de un banco abierto con licencia de uso comercial (p. ej. Unsplash o Pexels), descargada al repo; sustituye a la de abound (`cover-bg.jpg`, licencia desconocida), que solo se usa en las maquetas | La del banco, con autor y fuente acreditados en el pie de `design.md` y en `docs/design/system.md` → «Imágenes»; la elige `/implement` y la aprueba el usuario | Sí, por una foto real de la consulta cuando exista; nunca se presenta como el equipo de Dentissa |
| Logo «Melissa López N. · Odontología integral» | `public/images/logo-icon.png` (del proyecto), pintado por máscara | Propio | No |
| Iconos de categoría | Dibujados en código (SVG de 24 × 24, trazo 1.6) | Propios | No |

## Movimiento

Capa del modo persuadir (`design-spec/references/movimiento.md` §2): una escena, una jugada firma,
entradas por sección, respuesta bajo la mano. Vivo en `design/opcion-c-pagina.html`; un solo script
común (~1.5 kB) para las entradas y la cosa viva.

### M-1 — La hoja que sube sobre la consulta
- **Tipo y filtro:** escena (`hoja-sobre-imagen`); una vez por visita; explica el paso de la portada al contenido y es el momento protagonista.
- **Disparador:** progreso del scroll nativo; ocurre cada vez que se baja (no captura la rueda).
- **Elementos y propiedades:** la portada es `position: sticky; top: 0` de 100svh; «Sobre nosotros» tiene fondo `--bg`, `z-index` mayor, esquinas `--radius-lg` arriba y una sombra hacia arriba, y sube encima. Nada se anima: es el desplazamiento.
- **Duración y curva:** las del scroll; **inicial → final:** portada a la vista → la hoja la cubre.
- **Técnica:** CSS, sin script.
- **Reducido:** igual (no hay animación) · **Sin JavaScript:** igual · **Táctil:** igual · **Teléfono:** la misma.
- **Coste:** 0 kB; no comparte propiedades.
- **Fotogramas:** tira `.ai/inspect/opcion-c/film-1440-01` y `-02`.

### M-2 — La franja del horario
- **Tipo y filtro:** firma (`marquesina`); se ve una o dos veces por visita; decorativa (el horario está en la portada, la ubicación y el pie).
- **Disparador:** carga, solo con JavaScript (clase `js`); bucle continuo hasta que se pausa.
- **Elementos y propiedades:** pista con el texto duplicado, `transform: translateX(-50%)`.
- **Duración y curva:** `--duration-marquesina` (48 s), `linear`; se para con el puntero encima y con
  el botón «Pausar la franja del horario» (`aria-pressed="true"`, clase `is-pausada`), que la reanuda
  al volver a pulsarlo (WCAG 2.2.2, refinado A23).
- **Técnica:** CSS (keyframes) bajo `.js`; el script común solo maneja el botón.
- **Reducido:** quieta y sin botón · **Sin JavaScript:** quieta y sin botón (no hay forma de pararla
  sin script, así que no se mueve) · **Táctil:** se mueve; el botón la pausa.
- **Coste:** 0 kB; no comparte propiedades. `aria-hidden="true"`.
- **Fotogramas:** tira `.ai/inspect/opcion-c/film-1440-05`.

### M-3 — Entradas por sección
- **Tipo y filtro:** entrada; una vez por visita y sección.
- **Disparador:** la parte superior llega al 88 % de la ventana; una sola vez.
- **Elementos y propiedades:** títulos por línea (`translateY(0.6em)`), párrafos y filas de datos subiendo 24 px, tarjetas una a una, el mapa desde una máscara (`clip-path`); `opacity` en todas.
- **Duración y curva:** `--duration-reveal` con `--ease-expo`; escalonado `--stagger` por orden, cuatro pasos como máximo.
- **Técnica:** CSS con la clase `is-in` que pone el script común (IntersectionObserver).
- **Reducido:** sin entradas, todo visible · **Sin JavaScript:** todo visible (la clase `js` la pone el script) · **Táctil:** igual.
- **Coste:** parte del script común; comparte `opacity` y `transform` solo consigo misma.

### M-4 — Respuesta bajo la mano
- **Tipo y filtro:** respuesta; varias veces por visita: rápida.
- **Elementos y propiedades:** botones `scale(0.97)` al pulsar (`--duration-fast`, `--ease-out`); tarjetas: borde, sombra y color del icono con el puntero o el foco (sin levantarse).
- **Reducido:** solo el color · **Sin JavaScript:** igual (CSS) · **Táctil:** el foco; sin hover.

### M-5 — «¿Abierto ahora?»
- **Tipo:** cosa viva; sin animación (el texto aparece ya escrito). `aria-live="polite"`.

## Inspección

- **Rondas:** 2 de 3 en C y en D, y una pasada más de C tras combinar · **Inspector:** C 0 FAIL ·
  2 WARN (titular en tres líneas a 320 y 390 px, defendido: la escala mínima de portada no cabe en
  dos en 288 px) · **Crítica:** `design/critica-c.md` (y `critica-d.md` de la descartada).
- **Tres problemas corregidos:** el titular en tres líneas en el escritorio (ahora dos); la pastilla
  de «Abierto ahora» partida en el teléfono; el texto inventado de la ubicación («a unas calles de…»),
  sustituido por «Estamos en el centro de Lázaro Cárdenas.».

## Accesibilidad

- **Contraste** (calculado):
  - titular y texto de la portada, blanco sobre el velo en el peor caso → 6.41:1;
  - «prioridad.» en `--p200` sobre el velo → 4.06:1 (texto grande);
  - titular sobre el degradado (≈ 61 % en el 75 % del ancho) → ≥ 3.6:1 (texto grande);
  - pastilla `--p600` sobre blanco → 7.00:1; botón principal, blanco sobre `--p500` → 5.42:1;
  - franja `--p100` sobre `--p600` → 6.05:1; pie, blanco sobre `--p600` → 7.00:1;
  - tarjeta: texto sobre blanco 21.00:1, `--p600` sobre blanco 7.00:1, icono `--p600` sobre `--p100` 6.05:1;
  - borde del botón secundario y de la tarjeta con foco, `--border-control` sobre blanco → 3.93:1.
- **Encabezados y estructura:** un `h1` (el titular); `h2` para «Sobre nosotros», «Nuestros servicios»,
  «Encuéntranos fácilmente» y las columnas del pie; `h3` en cada tarjeta. `nav` «Secciones» (también
  la del menú del móvil) y «Pie».
- **Teclado:** el orden del HTML es el visual; foco del sistema en todo lo interactivo (inverso sobre
  el rosa y la portada); las tarjetas son enlaces enteros; la franja se pausa y se reanuda con su
  botón (Intro o Espacio), que anuncia su estado con `aria-pressed` (WCAG 2.2.2).
- **Botón de la franja:** borde e icono `--p100` sobre `--p600` → 6.05:1 (≥ 3:1, componente); foco
  blanco sobre `--p600` → 7.00:1.
- **Decorativos ocultos:** la foto (fondo), el velo, el logo grande de la portada y el del pie, los
  iconos y la franja, con `aria-hidden`.
- **Sin animación:** todo el contenido en el HTML, visible sin JavaScript y con movimiento reducido;
  la franja queda quieta con movimiento reducido y sin JavaScript, y con el botón de pausa (CA9).

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Foto de stock en la portada | **Riesgo asumido por el usuario:** una foto libre de un banco abierto, acreditada, bajo el velo de la marca y sin presentarla como el equipo; se sustituye por una foto real cuando la haya (refinado del 2026-10-21) |
| Tríada de tarjetas icono + título + texto | Siete tarjetas, una por categoría real del catálogo, con su número de tratamientos (no ventajas inventadas); iconos dibujados para cada categoría |
| Todo entra con el mismo fundido al hacer scroll | Entradas que cambian con el contenido: títulos por línea, tarjetas una a una, mapa desde una máscara; la portada no espera al scroll |
| Hover que levanta todas las tarjetas | La tarjeta cambia borde, sombra e icono; no se mueve |
| Página quieta | La hoja que sube, la franja, las entradas y la cosa viva |
| Primera pantalla distinta seguida de plantilla | Cada sección tiene su forma (hoja, rejilla, franja, 3:2, pie con el nombre) y el horario vuelve hasta el pie |
| Marquesina decorativa sin idea | Una sola, con el horario real; se detiene con el puntero, con su botón de pausa y con movimiento reducido |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | diseño | Rondas 1 y 2 | Opción B «La puerta de la consulta», con el refinado D15 (cabecera y pie rosas, hamburguesa) — archivada en `design/history/ronda-2/design.md` | usuario |
| 2026-10-21 | diseño | ¿Rediseñar la 038 tras su aprobación? | Sí: la opción B es «muy básica»; seguir la línea de la landing de abound | usuario |
| 2026-10-21 | diseño | Portada | A pantalla completa con la foto de abound (`cover-bg.jpg`), comprimida y bajo un velo de la marca | usuario |
| 2026-10-21 | diseño | Servicios | Tarjetas con icono, nombre y «N tratamientos» (CA4 sin cambios: no muestran servicios sueltos) | usuario |
| 2026-10-21 | diseño | Movimiento con JavaScript | Sí, con respaldo sin JavaScript y con movimiento reducido (~1.5 kB, P10 lo permite) | usuario |
| 2026-10-21 | diseño | Placa del aviso de la puerta | Se quita; el horario y el teléfono van en la portada (CA1 sin cambios), la ubicación y el pie | usuario |
| 2026-10-21 | diseño | Motor creativo de AI-DD 1.16.0 | Ronda 3 con dos composiciones (C firme, D audaz), semilla 371542, inspector y crítica | `/design-spec` 1.16.0 |
| 2026-10-21 | diseño | Elección | C con la ubicación de D (mapa a la izquierda, 3:2) y el nombre grande en el pie | usuario |
| 2026-10-21 | supuesto | Licencia de la foto de abound | Desconocida: se confirma con el colaborador o se sustituye antes de `/release` | `inspeccion` · `creatividad.md` §6 |
| 2026-10-21 | refinado | ¿Cómo se resuelve la licencia de la foto de la portada? | Foto libre de un banco abierto con licencia de uso comercial, descargada al repo y acreditada; la de abound queda solo en las maquetas | usuario (`/plan 038 --fix`) |
| 2026-10-21 | diseño | ¿Se aprueba este documento (ronda 3)? | Aprobado | usuario |
| 2026-10-21 | refinado | La franja solo se detiene con el puntero: con teclado o táctil no se puede parar (WCAG 2.2.2, `/analyze` A23) | Botón de pausa accesible en la franja, que maneja el script común; sin JavaScript y con movimiento reducido la franja queda quieta | usuario (`/analyze 038` r4) |
