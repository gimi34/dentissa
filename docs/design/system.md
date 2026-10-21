---
status: approved         # re-extraído el 2026-10-04 y aprobado por el usuario el 2026-10-04
source: extracted        # documentado del código y de capturas de la app real
version: 1.7.0
extracted_with: 1.11.2   # re-extracción con /init --upgrade --redo-design (AI-DD 1.11.2)
html: docs/design/system.html
canvas: null
widths: [320, 390, 1440]   # 320 por WCAG 1.4.10 (spec 033)
created: 2026-10-02
updated: 2026-10-20
---

# Sistema de diseño de Dentissa

Fuente de verdad visual del proyecto. Toda pantalla nueva o modificada usa estos tokens y
componentes. Si hace falta un valor que no está aquí, primero se añade aquí (sección "Cambios a
incorporar" del plan que lo necesite). Vista: [system.html](system.html).

Versión anterior (1.1.0, extraída el 2026-10-02 sin capturas):
[history/system.v1.md](history/system.v1.md).

## Dirección

Sistema re-extraído el 2026-10-04 con el procedimiento de AI-DD 1.11.2. Las 60 vistas de la app se
capturaron a 390 y 1440 px con datos de prueba, y cada estilo se midió con `getComputedStyle`. El
documento describe lo que la app hace hoy, sin mejoras. Si lo medido difiere de lo que declara el
código, manda lo medido y la diferencia es deuda.

Es una app de gestión clínica para el personal y los pacientes:
- **Marca:** el logo de la consulta, "Melissa López N. · Odontología integral" (`public/images/logo-icon.png`
  en la barra lateral y `logo-main.png` en el acceso). La pestaña del navegador dice "Dentissa".
- **Aspecto:** paleta rosa sobre un fondo rosa muy claro, tarjetas blancas con borde suave y una
  barra lateral rosa intensa con el menú de cada rol.
- **Densidad y pantalla:** es densa (cuerpo de 13–13,5 px) y está pensada para escritorio. Solo el
  acceso y el registro se adaptan a móvil.

Objetivo de accesibilidad: **WCAG 2.1 AA** (decisión del usuario, 2026-10-02). Lo que hoy no lo
cumple está en "Deuda de diseño".

## Color

Tokens reales de `:root` en `public/css/app.css:3-49`. Los valores de las vistas coinciden con lo
medido. La columna "Rol" da el nombre semántico de la plantilla de AI-DD (`--color-*`), que es el
que usa `system.html`.

| Token | Valor | Rol | Uso (medido) | Contraste sobre su fondo |
|---|---|---|---|---|
| `--bg` / `--p50` | `#FDF5F8` | `--color-bg` | Fondo de todas las vistas (`body`, 114 de 120 capturas) | — |
| `--white` | `#FFFFFF` | `--color-surface` | Tarjetas, tablas, campos y barra superior | — |
| `--text` | `#000000` | `--color-text` | Texto principal y celdas de tabla | 19.59:1 sobre --bg · 21.00:1 sobre --white |
| `--text-muted` | `#000000` | `--color-text-muted` | Texto secundario (150 usos en vistas); igual que `--text` (DS3) | 19.59:1 sobre --bg |
| `--text-light` | `#9B8290` | `--color-text-light` | Etiquetas de campo (11px/600, 70 capturas) y `th` (10.5px/600) | 3.51:1 sobre --white · 3.27:1 sobre --bg (no cumple, DS1) |
| `--p400` | `#D4537E` | `--color-primary` | Botón principal (medido en 90 capturas) | — |
| `--white` sobre `--p400` | `#FFFFFF` | `--color-on-primary` | Texto del botón principal | 3.93:1 sobre --p400 (no cumple, DS1) · 5.42:1 en hover sobre --p500 |
| `--p500` | `#B83C65` | `--color-link` | Enlaces y texto de acento | 5.42:1 sobre --white |
| `--p600` | `#993556` | `--color-accent-text` | Menú activo, `.btn-outline`, badge rosa, botón "Cerrar sesión" | 7.00:1 sobre --white · 6.05:1 sobre --p100 |
| `--p100` / `--p200` / `--p300` / `--p800` | `#FBEAF0` / `#F4C0D1` / `#ED93B1` / `#72243E` | — | Fondos de hover y activo, bordes de botón, foco antiguo, degradados | — |
| `--border` | `#F0DCE4` | `--color-border` | Borde de tarjeta (1px) y de campo (1px medido) | 1.31:1 sobre --white (no cumple como borde de control, DS2) |
| `--border-control` = `--p400` | `#D4537E` | `--color-border-control` | Borde de control que cumple 3:1; solo en los campos de hora del horario | 3.93:1 sobre --white |
| `--focus-ring` = `--p600` | `#993556` | `--color-focus` | Anillo `:focus-visible` (2px, desplazamiento 2px) del horario y de la lista de citas | 7.00:1 sobre --white |
| `--p300` | `#ED93B1` | — (foco antiguo) | Borde de campo con foco en el código previo y en el acceso | 2.23:1 sobre --white (no cumple, DS2) |
| `--red` / `--red-bg` | `#A32D2D` / `#FCEBEB` | `--color-danger` | Errores, badge "Cancelada", flash de error, botón "Desactivar" | 6.13:1 sobre --red-bg · 7.07:1 sobre --white |
| `--green-texto` (spec 038) | `#137A5A` | `--color-success-text` | Éxito en texto en las páginas públicas y su gestión (estado «Se ve ahora», avisos de éxito) | 5.30:1 sobre --white · 4.95:1 sobre --bg |
| `--green` / `--green-bg` | `#1D9E75` / `#E1F5EE` | `--color-success` | Badge "Completada", flash de éxito, cifra "Completadas" | 2.98:1 sobre --green-bg · 3.39:1 sobre --white (no cumple, DS1) |
| `--amber` / `--amber-bg` | `#854F0B` / `#FAEEDA` | `--color-warning` | Badge "En curso", avisos | 5.87:1 sobre --amber-bg |
| `--blue` / `--blue-bg` | `#185FA5` / `#E6F1FB` | `--color-info` | Badge "Confirmada" | 5.70:1 sobre --blue-bg |
| `--overlay` | `rgba(0, 0, 0, 0.45)` | `--color-overlay` | Velo bajo el menú abierto en el móvil (spec 033) | — (no lleva texto) |
| (literal) `#C75078` | `#C75078` | `--color-sidebar` | Barra lateral, medida en las 104 capturas con menú; no es token (DS4) | blanco 4.33:1 · menú en blanco al 90% 3.82:1 (no cumple, DS1) · `.nav-label` en blanco al 65% 2.72:1 (no cumple, DS1) |

Contraste calculado con la fórmula de luminancia relativa de WCAG 2.x. Umbrales: ≥ 4.5:1 en texto
normal, ≥ 3:1 en texto grande y en bordes de control. Modo oscuro: no aplica (no existe en la app;
solo `welcome.blade.php`, sin ruta, lo declara).

`public/css/auth.css:8-26` (acceso y registro) redefine un subconjunto de estos tokens, con
`--text-light: #000000` (DS5).

## Tipografía

| Token | Familia | Pesos | Uso |
|---|---|---|---|
| (sin token) | Roboto, sans-serif | 300–700 cargados (layout) · 300–600 (acceso); se usan 400, 500 y 600 | Toda la app (medido en 114 de 120 capturas) |
| `--weight-regular` / `--weight-strong` | — | 400 / 500 | Pesos de las reglas de la spec 027 |
| `--weight-bold` | — | 600 | Iniciales del perfil del paciente (spec 035; el valor de hoy) |
| (sin token) | no aplica | — | `'DM Sans'` (usuarios) y `'DM Mono'` (receta del expediente) se nombran sin cargarse: los campos de usuarios caen a la sans-serif genérica (medido, DS9) |
| (sin token) | fuente del navegador | — | Los campos de servicios (crear y editar) se ven en Arial de 13.33px porque su `.field` local no declara familia (medido, DS12) |

Páginas públicas (spec 038; [diseño](../specs/038-pagina-inicio/design.md)): familia `--font-publica`
(`"Lexend", system-ui, sans-serif`), con Lexend 400 y 600 autoalojada en `public/fonts/lexend/`
(woff2 latino de `@fontsource/lexend` 5.3.0, licencia OFL-1.1) y declarada con `@font-face` en
`public/css/publico.css`. Tamaños: `--text-publico-h1` (fluido de 30px a 320 px a 48px a 1440 px),
`--text-publico-h2` (fluido de 22px a 30px), `--text-publico-h3` (19px), `--text-publico` (17px) y
`--text-publico-small` (15px). La app de gestión sigue en Roboto (DS9 no cambia). En las páginas
públicas el texto secundario es `--p600` (6.54:1 sobre `--bg`); `--text-muted` de la app no cambia (DS3).

Tokens de tamaño (spec 027): `--text-body` (13.5px) y `--text-small` (11.5px); (spec 033)
`--text-meta` (13px, migas de pan) y `--text-marca` (16px; sin uso desde la spec 034, que cambia la marca de
texto por el logo; no se retira porque lo comprueba el test de la 033). **Son tamaños** y
solo van en `font-size`. Los colores `--text`, `--text-muted` y `--text-light` comparten el
prefijo, pero son colores. Los demás tamaños no tienen token (DS4): hay 17 en `public/css` y 20 si
se cuentan las vistas.

| Nivel | Medido (escritorio) | Dónde |
|---|---|---|
| Título de página (barra superior) | 19px / 500 | `app.css:190`, las 104 vistas con menú |
| Cifra de tarjeta (`.stat-value`) | 28px / 600 | `app.css:251` |
| Título del acceso | 28–30px / 600 | `auth.css` |
| Título de sección | 15px / 500 | `app.css:267` |
| Cuerpo: menú, campo | 13.5px | `.nav-item` y campos (medido) |
| Cuerpo: tabla, botón | 13px | `td` y `.btn` (medido) |
| Pequeño: etiqueta, badge, `th`, `.nav-label` | 11px · 11px · 10.5px · 10px | medido |

Fuentes: **Roboto cargada desde Google Fonts** (`layouts/app.blade.php:7` y las tres vistas de
acceso). Es un servicio externo que `docs/security.md` no aprueba (DS9).

## Espaciado, radios, bordes y sombras

- **Escala de espaciado (spec 027):** `--space-1` … `--space-8` = 4, 6, 8, 10, 14, 16, 20 y 28px.
  - Solo la usan las reglas de la spec 027 (`app.css:427-466`); `--space-7` y `--space-8` no tienen
    ningún uso.
  - Las pantallas anteriores siguen con literales (DS4). Los más repetidos son `gap` 8px (54),
    10px (43) y 6px (20), y `margin-bottom` 16px (32), 20px (26) y 14px (23).
- **Medidas de página:**
  - margen de la página `.content`: 28px (medido en las 104 vistas con menú);
  - barra superior: 60px de alto, blanca;
  - barra lateral: 242px.
- **Padding de tarjeta:** el más común es 22px (48 capturas). También aparecen 16px 20px,
  28px 22px, 14px 18px y 0 en las tarjetas de tabla.
- **Radios:**
  - `--radius-sm` 8px (botones y campos) · `--radius` 12px (tarjetas y diálogos) · `--radius-lg`
    16px (banner de bienvenida);
  - literales: 10px en el acceso, 20px en las píldoras y 5px en los campos de servicios (DS4).
- **Bordes:**
  - `--border-width` (1px) en `--border`, medido en tarjetas y campos;
  - foco: `--focus-ring-width` (2px) y `--focus-ring-offset` (2px).
- **Sombras:** solo en el acceso (botones y campos) y en el diálogo de servicios. La app interna no
  usa sombras en tarjetas.
- **Hora del historial (spec 028):** `--timeline-time-width` (100px), el ancho que hoy lleva en línea
  la hora del historial de la cita; lo usa `.formulario-accesible .timeline-time`.
- **Campo de hora:** `--field-time-width` (110px). Recorta el texto "AM"/"PM" con el formato de
  12 h del navegador (medido, DS13).
- **Spec 033 (móvil):**
  - `--touch-min` (44px): zona de toque mínima, aplicada por debajo de 900 px;
  - `--menu-movil-w` (300px): ancho del panel del menú en el móvil, como mucho el 85 % de la pantalla;
  - `--topbar-h` (60px): alto de la barra superior, el mismo que hoy;
  - `--border-width-strong` (2px): indicador de la pestaña activa y de la sección actual del menú;
  - `--logo-max-w` (160px): ancho máximo del logo de la barra lateral;
  - `--icon-md` (18px): icono de los botones del menú del móvil.
- **Spec 034 (móvil del personal):**
  - `--tarjeta-etiqueta-w` (88px): columna de la etiqueta en la tarjeta de lista del móvil;
  - `--logo-movil-h` (40px): alto del logo en el menú del móvil (su ancho es `--logo-max-w`);
  - `--space-detalle` (18px): hueco entre bloques del detalle de cita del personal (el valor de hoy);
  - `--detalle-lateral-w` (360px): columna lateral del detalle de cita en el escritorio (el valor de hoy);
  - `--filtro-buscar-min` (180px), `--filtro-fecha-min` (130px), `--filtro-dentista-min` (150px) y
    `--filtro-estado-min` (140px): anchos mínimos de hoy de los campos del filtro de citas en el
    escritorio; en el móvil pasan a 0.
- **Spec 035 (pacientes y expedientes del personal):**
  - `--ficha-lateral-w` (320px) y `--expediente-lateral-w` (300px): columna lateral de la ficha del
    paciente y de la del expediente en el escritorio (los valores de hoy);
  - `--perfil-avatar-w` (64px) y `--perfil-avatar-movil` (48px): iniciales del perfil del paciente en
    el escritorio (hoy) y en el móvil;
  - `--perfil-iniciales-text` (22px) y `--perfil-iniciales-movil` (17px): letra de las iniciales;
  - `--perfil-nombre-text` (20px) y `--perfil-nombre-movil` (16px): letra del nombre del perfil;
  - `--expediente-dato-max` (140px): ancho máximo de hoy de los valores de «Información» de la ficha del
    expediente en el escritorio (`.info-valor`); en el móvil, sin máximo;
  - `--campo-fecha-max` (220px): ancho máximo de hoy de la fecha sugerida del alta de expediente en el
    escritorio (`.campo-fecha`); en el móvil, sin máximo (/review 035 R14);
  - `--weight-bold` (600, en la tabla de tipografía).
- **Spec 038 (páginas públicas):**
  - `--space-seccion`: aire entre secciones, fluido de 40px (320 px) a 96px (1440 px);
  - `--measure` (62ch): ancho de lectura;
  - `--page-max` (1180px): ancho máximo del contenido público;
  - corte propio de las páginas públicas a **560 px** (`@media (max-width: 559px)`): puertas, índice y
    definiciones en una columna y botones a todo el ancho. Acompaña al de 900 px y, como él, es el único
    literal admitido, solo en la condición.
- **Punto de corte (spec 033):** móvil por debajo de 900 px (`@media (max-width: 899px)`). Las media
  queries no admiten variables: es el único literal admitido, solo en la condición. `menu.js` no lo
  repite: deduce el modo de si se muestra el botón «Menú».
- **`z-index` del menú del móvil:** 100 (velo) y 110 (panel), por encima de la barra superior (50).
  No son color, tipografía, espaciado, radio, sombra ni duración, así que quedan fuera de P9.
- **Toque mínimo en el escritorio: sin aplicar** (D8 de la 033). Alturas medidas (DS6):
  - botón ≈ 31px;
  - `.btn-sm` 22–27px;
  - campo 34–38px;
  - elemento del menú 35px;
  - botón de acceso 44px (este sí cumple).

## Componentes

Recortes reales de las capturas en `capturas/componentes/` (vista en `system.html` → Componentes).

### Botón
- **Clases:** `.btn` (8px 16px, 13px/500, radio 8px; **31px de alto medido**), con las variantes:
  - `.btn-primary` (`--p400`, texto blanco);
  - `.btn-outline` (blanco, texto `--p600`, borde `--p200`);
  - `.btn-ghost` (transparente);
  - `.btn-sm` (5px 12px, 12px; 22–27px de alto).
- **Acceso:** `.btn-submit` (15px, radio 10px, 44px), `.btn-next`, `.btn-back` y `.btn-google`
  (blanco con el logo de Google).
- **Estados:** solo `:hover`. No hay foco propio (queda el del navegador), ni deshabilitado, ni
  carga (DS2, DS7).
- **Peligro (spec 028):** `.btn-danger`, texto `var(--red)` sobre `var(--red-bg)` (6.13:1), borde
  `var(--border-width)` `var(--red)` (7.07:1 sobre `--white`) y foco `outline: var(--focus-ring-width)
  solid var(--focus-ring)` con `var(--focus-ring-offset)`. Para acciones irreversibles («Cancelar la
  cita definitivamente») y el «Cancelar» del panel del paciente. Las pantallas anteriores siguen
  haciéndolo en línea, como "Desactivar" en la ficha del personal (DS4).

- **Botón de icono (spec 034):** `.btn-icono`, para enlaces de solo icono como «Ver detalle» de la
  agenda de la dentista. Relleno `--space-1` `--space-3` (el de hoy); nombre accesible con
  `aria-label`; foco con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`. En el móvil
  mide al menos `--touch-min` × `--touch-min`.

### Campo de formulario
- `.field`, `.field-label` y `.field-error` están en `public/css/citas.css`, que solo cargan 7
  vistas. Otras 11 los redefinen en bloques `<style>` dentro del contenido (DS5).
- Medido:
  - etiqueta de 11px/600 en mayúsculas, color `--text-light`;
  - campo de 13.5px y 34–38px de alto, borde 1px `--border`, radio 8px;
  - en servicios, 13.33px Arial y radio 5px, con el desplegable en gris (DS12).
- Foco: `outline: none`, borde `--p300` y halo al 7%. Desde la spec 035, los formularios del paciente
  y del expediente (`.form-paciente`, `.form-expediente`) usan el foco del sistema: anillo
  `--focus-ring` de `--focus-ring-width` con `--focus-ring-offset`, borde `--border-control` y sin
  halo; los demás formularios siguen como deuda DS2.
- Error: borde `--red` y texto de ayuda en `--red` junto al campo.
- `.field-hint` y `.field-hint-error` (spec 027) están en `app.css`, con `aria-describedby` y
  `aria-invalid` solo en el horario.
- Acceso: campos de 14px, de 40–42px de alto y radio 10px, con icono a la izquierda, foco en
  `--p300` y error en `--red`.

### Formulario accesible (spec 028)
`.formulario-accesible` es un contenedor que da a lo que lleva dentro valores que cumplen WCAG 2.1 AA
sin cambiar el resto de la app (los `.field`, `.field-label` y `.btn-primary` globales no cumplen:
DS1, DS2). Lo usan la pantalla «Reprogramar cita» y los bloques que la spec 028 toca en vistas
existentes.
- **Etiqueta** (`.field-label`): `var(--text)` (21:1 sobre `--white`, 19.59:1 sobre `--p50`).
- **Campo** (`.field`): definido completo, sin depender de `citas.css`; familia heredada
  (`font-family: inherit`), tamaño `--text-body`, espaciado y radio con tokens, borde
  `var(--border-width)` `var(--border-control)` (3.93:1 sobre `--white`, 3.67:1 sobre `--p50`);
  con foco, el mismo borde y sin sombra.
- **Botón principal** (`.btn-primary`): blanco sobre `var(--p500)` (5.42:1).
- **Casilla** (`.campo-casilla`): casilla y etiqueta en fila con `gap: var(--space-3)`; la casilla
  mide `var(--space-6)` y usa `accent-color: var(--border-control)` (3.93:1 sobre `--white`); la
  etiqueta es su objetivo de toque ampliado. La usa la confirmación de «No reagendar» (WCAG 3.3.4).
- **Foco:** `:focus-visible` en campos, casillas, botones y enlaces con `--focus-ring` (7.00:1),
  `--focus-ring-width` y `--focus-ring-offset`.
- **Badge verde** («Completada»): `var(--text)` sobre `var(--green-bg)` (18.50:1) en lugar de
  `--green` (2.98:1).
- **Historial:** `.timeline-time` con `--timeline-time-width`, punto en `--p300` (decorativo: el
  estado se lee en el texto), `.historial-estado-anterior` en `--text`, `.historial-estado-nuevo` en
  `--p600` y `.historial-autor` en `--text` con `--text-small`. Las reglas globales `.timeline-*`
  (panel del dentista) no cambian.
- **Maquetación:** `.acciones-columna` (columna, `--space-3`), `.acciones-fila` (fila a la derecha,
  `--space-1`), `.form-en-linea` (formulario en línea), `.aviso-plazo` (`--text-body` en `--text`),
  `.historial-lista` (`--space-2` `--space-7`) e `.historial-vacio` (`--space-7`).

Pares de contraste que comprueba el test de la spec 028:

| Uso | Frente | Fondo | Contraste |
|---|---|---|---|
| Etiqueta, aviso, autor y estado anterior del historial | `--text` | `--white` / `--p50` | 21.00:1 / 19.59:1 |
| Botón principal | `--white` | `--p500` | 5.42:1 |
| `.btn-danger` | `--red` | `--red-bg` | 6.13:1 |
| Errores del campo | `--red` | `--white` / `--p50` | 7.07:1 / 6.60:1 |
| Estado nuevo del historial, `.btn-outline` | `--p600` | `--white` | 7.00:1 |
| Badges | `--p600`/`--p100`, `--blue`/`--blue-bg`, `--amber`/`--amber-bg`, `--text`/`--green-bg`, `--red`/`--red-bg`, `--text-muted`/`--p50` | — | 6.05, 5.70, 5.87, 18.50, 6.13 y 19.59:1 |
| Borde de campo y de `.btn-danger` (≥ 3:1) | `--border-control` / `--red` | `--white`, `--p50` | 3.93 y 3.67:1 / 7.07 y 6.60:1 |
| Anillo de foco (≥ 3:1) | `--focus-ring` | `--white` / `--p50` | 7.00:1 / 6.54:1 |

### Día del horario (spec 027)
- `.horario-dia`: fila de un día, con casilla, nombre, dos campos de hora y la duración.
  - Si está activo, fondo `--p50` y borde `--p200`.
  - Si no, el texto "No disponible".
- Campos de hora de `--field-time-width` (110px) con borde `--border-control`. Medido: el formato
  de 12 h no cabe y se corta en "09:00 A" (DS13).

### Lista de citas (spec 027)
`.lista-citas` en la ficha de la dentista ("Citas fuera del horario"). Cada fila tiene:
- el paciente, la fecha y la hora en 16px;
- el badge de estado;
- el botón "Ver cita" (`.btn btn-outline btn-sm`).

### Menú del móvil (spec 033)
Opción A de la spec 033 ([maqueta](../specs/033-movil-paciente/design/opcion-a.html)). Por debajo de
900 px, la barra lateral se convierte en un panel que se despliega sobre la página:
- **Botón «Menú»** (`.menu-btn`), en la barra superior antes del título, **solo con el icono** de
  `--icon-md` (spec 034): `aria-label="Menú"`, `--touch-min` × `--touch-min` y relleno 0.
  `aria-expanded` y `aria-controls="menu-principal"`. Desde la spec 035, **solo el icono, sin
  recuadro ni fondo**: icono `--p600` sobre la barra blanca (7,00:1), `border: 0`, fondo transparente,
  el área de `--touch-min` y el anillo de foco del sistema (`--radius-sm` le da forma).
- **Panel** (`<aside id="menu-principal" aria-label="Menú">`): blanco, de `--menu-movil-w` (como mucho
  85 % del ancho), alto `100dvh` y desplazable. Arriba, la marca (`.sidebar-marca`): desde la spec 034,
  el logo actual pintado con `--p600` (7,00:1 sobre `--white`) a través de `mask` y `-webkit-mask`, de
  `--logo-max-w` × `--logo-movil-h`, con `role="img"` y `aria-label="Melissa López N."`; si el
  navegador no admite la máscara, queda un rectángulo `--p600`. Al lado, el botón «Cerrar menú»
  (`.menu-cerrar`, solo icono, con `aria-label`, mismo aspecto que «Menú»: sin recuadro desde la
  spec 035). Debajo, las mismas
  secciones del escritorio (`.nav-item` en `--text`; la actual con `aria-current="page"`, fondo
  `--p100`, texto `--p600` y una barra de `--border-width-strong` a la izquierda, para que no dependa
  solo del color), el nombre y «Cerrar sesión» (borde `--border-control`). El logo en imagen se
  oculta, porque con `mix-blend-mode: screen` desaparece sobre blanco.
- **Velo** (`.menu-velo`, `--overlay`): entre el panel y la página; tocarlo cierra el menú.
- **Teclado y lector de pantalla:** al abrir, el foco va a la sección actual y `<main>` queda `inert`;
  Escape, «Cerrar menú» o el velo lo cierran y el foco vuelve a «Menú». Sin `role="dialog"`: el estado
  lo anuncia `aria-expanded`, y al abrir los lectores anuncian la región «Menú» (033/R9, spec 034). Al
  cruzar los 900 px, el foco solo cambia de sitio si estaba en el menú o en sus botones (033/R21).
- **Sin JavaScript:** el menú queda en el flujo, encima del contenido, sin taparlo, y no hay botón.
- **Movimiento:** transición de `--duration-fast` solo con `prefers-reduced-motion: no-preference`.
- **Navegadores:** `inert` desde Safari 15.5 y Chrome 102; `100dvh` desde Safari 15.4 y Chrome 108.

En el escritorio (900 px o más) el menú es la barra lateral de siempre.

### Pestañas (spec 033)
`.pestanas-citas` (mis citas del paciente): fila flexible con `gap` `--space-2`, `margin-bottom`
`--space-7` y borde inferior `--border-width` `--border`. Cada enlace lleva `padding` `--space-4`
`--space-1`, `--text-body`, `--weight-strong`, sin subrayado, `--text-muted` y un borde inferior
transparente de `--border-width-strong`; el primero, `margin-right` `--space-7`. La activa (`.activa`,
`aria-current="page"`): texto `--p500` (5,06:1 sobre `--bg`) y borde `--p400` (3,67:1). En el móvil,
cada enlace mide al menos `--touch-min` × `--touch-min`.

### Migas de pan (spec 033)
`.migas` (detalle de la cita y agendar): fila flexible centrada con `gap` `--space-3`, `margin-bottom`
`--space-7`, `--text-meta` y `--text-muted`; enlaces en `--text-muted` sin subrayado y la página actual
en `.migas-actual` (`--text`). En el móvil, cada enlace mide al menos `--touch-min` × `--touch-min`.

### Lista, tabla y tarjeta
- **Tarjetas:**
  - `.card`: blanca, borde 1px `--border`, radio 12px; padding medido de 22px en la mayoría.
  - `.card-0`: sin padding, para tablas.
  - `.stat-card`: icono en recuadro de color, cifra de 28px, etiqueta y badge.
  - `.quick-action`: accesos rápidos del panel.
- **Tablas:**
  - `th`: 10.5px/600 en mayúsculas, color `--text-light`;
  - `td`: 13px en `--text`;
  - `tr:hover` en `--p50` y fila de hoy resaltada;
  - separador `#FDF0F4`.
- **Historial de la cita:** `.timeline` con punto de color y hora.
- **Línea de tiempo del panel de la dentista (spec 034):** en el móvil, `.timeline-item` baja de
  línea y el estado y el enlace («Ver detalle», `.btn-icono`) van debajo del nombre
  (`.timeline-acciones`), que usa todo el ancho que dejan la hora y el punto. No es tarjeta.

### Tarjeta de lista en el móvil (spec 034)
Opción A de la spec 034 ([maqueta](../specs/034-movil-personal/design/opcion-a.html)). Por debajo de
900 px, las tablas con `tabla-tarjetas` se ven como una tarjeta por fila, solo con CSS:
- **Marcado:** cada `<td>` lleva `data-label` con el texto de su `<th>`; clases de celda
  `celda-cab` (dato principal, arriba sin etiqueta), `celda-estado` (arriba a la derecha, sin
  etiqueta), `celda-acciones` (al pie, sin etiqueta) y `solo-escritorio` (no se muestra en el móvil).
  Un valor con varios elementos va en un único `<div class="celda-valor">`.
- **Tarjeta:** `--white`, borde `--border` decorativo (la separan su fondo y su espacio), `--radius`,
  relleno `--space-5` y `--space-6`, separación `--space-5`, sobre el `tbody` en `--bg`.
- **Celda con etiqueta:** rejilla `var(--tarjeta-etiqueta-w) minmax(0, 1fr)`; la etiqueta sale de
  `::before`, en `--text-small`, `--weight-strong`, mayúsculas y `--p600` (7,00:1 sobre `--white`).
- **Acciones:** a lo ancho, con borde superior `--border` y botones que se reparten el espacio.
- **Cita de hoy** (`.fila-hoy`): tarjeta `--white` con barra izquierda de `--border-width-strong` en
  `--p400` y el texto «Hoy»; en el escritorio, la fila conserva su fondo `--p50`.
- **Foco:** los enlaces y botones de las tarjetas, con los tokens de foco.
- En el escritorio, la tabla de siempre.

### Filtro plegable (spec 034)
Filtro de la lista de citas del personal (`.filtro-citas`):
- **Móvil:** una columna con el buscador a lo ancho, la fila «Esta semana · Hoy · Filtros» (los tres
  botones se reparten el ancho, `--space-3`) y el panel (`#filtro-panel`) con desde y hasta lado a
  lado, dentista y estado a lo ancho, y «Filtrar» y «Limpiar». Con JavaScript (`filtros.js`) el panel
  empieza plegado; sin JavaScript, desplegado.
- **Botón «Filtros»:** `btn-outline` con borde `--border-control` (3,93:1) en los dos estados;
  desplegado (`aria-expanded="true"`), fondo `--p100` con texto `--p600` (6,05:1). Con filtros
  aplicados, contador `--white` sobre `--p500` (5,42:1) y nombre «Filtros, 1 aplicado» o «Filtros, n
  aplicados».
- **Campos:** borde `--border-control` en los dos modos (decisión del usuario, spec 034 A36), etiqueta
  asociada con `for`/`id` y foco con los tokens de foco. Todos los controles del filtro llevan ese
  foco.
- **Escritorio:** se ve como hoy (el panel con `display: contents`, «Filtros» oculto y los atajos al
  final). El orden de tabulación pasa a ser buscador, atajos y campos (decisión del usuario, plan 034
  D6b).
- Las etiquetas de los campos (`--text-light`, 3,51:1) y «Filtrar» y el atajo activo (`btn-primary`,
  3,93:1) siguen como deuda DS1 (decisión del usuario, spec 034).

### Rejilla de estadísticas (spec 034)
`.stats-grid` (ya existía) con las variantes `.stats-grid-2` y `.stats-grid-3`, que solo fijan
`grid-template-columns`. En el móvil, dos columnas.

### Ficha en una columna (spec 035)
Ficha del paciente (`.ficha-paciente`: `var(--ficha-lateral-w) 1fr`) y del expediente
(`.ficha-expediente`: `1fr var(--expediente-lateral-w)`), con hueco `--space-7`. Por debajo de 900 px,
una columna en el orden del HTML (`minmax(0, 1fr)`): primero la columna del perfil o del contenido y
después la otra ([diseño](../specs/035-movil-pacientes-expedientes/design.md), opción A).

### Perfil compacto del paciente (spec 035, exclusivo de la ficha del paciente)
Opción C de la spec 035 ([maqueta](../specs/035-movil-pacientes-expedientes/design/opcion-c.html)). En
el escritorio, la tarjeta de identidad de siempre, centrada (`.perfil-identidad`, iniciales de
`--perfil-avatar-w` con `--perfil-iniciales-text` y `--weight-bold`, nombre de `--perfil-nombre-text`).
Por debajo de 900 px:
- `.perfil-cab`: rejilla `var(--perfil-avatar-movil) minmax(0, 1fr)` con las iniciales (48 px,
  `--perfil-iniciales-movil`, `aria-hidden`) y un único bloque con el nombre (`--perfil-nombre-movil`),
  la edad y el tipo de sangre;
- los datos de contacto en filas etiqueta / valor (`.perfil-dato`: `var(--tarjeta-etiqueta-w)
  minmax(0, 1fr)`), con la etiqueta visible («Correo», «Teléfono», «Ciudad», «Doctor») en
  `--text-small`, `--weight-strong`, mayúsculas y `--p600`, y sin los iconos del escritorio.

### Acciones de la barra en el móvil (spec 035)
`.acciones-ficha` en la barra superior de la ficha del paciente: en el escritorio, a la derecha del
título con hueco `--space-4`; por debajo de 900 px, una fila bajo el título (`.topbar-actions:has(...)`)
con los botones a partes iguales y el texto centrado. Sin `:has()`, queda a la derecha, sin desbordar.

### Acciones de formulario (spec 035)
`.acciones-formulario` («Cancelar», «Guardar…»): a la derecha en el escritorio, con hueco
`--space-4`; por debajo de 900 px, botones del mismo ancho con el texto y el icono centrados.

### Páginas públicas (spec 038)
Componentes de `/` y `/servicios` (y de `/galeria` con la 039), en `public/css/publico.css`, solo con
tokens ([diseño](../specs/038-pagina-inicio/design.md), opción B «La puerta de la consulta»):
- **Placa** (`.lb-placa`, también `.lb-placa-dir`): superficie `--white`, borde `--border-width-strong`
  en `--p600`, `--radius` y relleno `--space-8` (`--space-7` por debajo de 900 px). Es la placa de la
  consulta en la portada (logo, título, «Agendar mi primera cita» y el aviso de la puerta con horario y
  «Llamar») y la placa de la dirección sobre el mapa.
- **Puerta** (`.lb-puertas a`, `.lb-indice a`): enlace de categoría con alto ≥ `--touch-min`, borde
  `--border-control`, texto `--p600` y fondo `--p100` con el puntero o el foco. En el índice de
  `/servicios` lleva el número de servicios.
- **Cabecera pública** (`.pub-bar`): logo enmascarado en `--p600` (como el menú del móvil, 034),
  navegación (`.pub-nav-d`) y «Iniciar sesión» o «Ir a mi panel». Por debajo de 900 px, un `<details>`
  «Menú» (`.pub-menu`) que se abre y se cierra con el teclado y sin JavaScript.
- **Pie público** (`.lb-pie`): logo, enlaces y © con filo superior `--border`.

### Badge de estado
`.badge` es una píldora de 11px/500, 19px de alto y radio 20px. `partials/badge-estado` asigna:
- agendada → pink (`--p600` sobre `--p100`, 6.05:1);
- confirmada → blue (5.70:1);
- en curso → amber (5.87:1);
- completada → green (2.98:1, no cumple, DS1);
- cancelada → red (6.13:1);
- no asistió → gray (`--text-muted` sobre `--p50`).

El estado se lee en el texto, no solo por el color.

### Aviso, diálogo y mensajes
- **Mensajes flash del layout:**
  - éxito, en verde, se oculta a los 4 s;
  - error, en rojo, persistente;
  - contraseña generada, con botón "Copiar".

  Usan estilos en línea y no tienen `aria-live` (DS7). Son estados transitorios, así que no tienen
  captura.
- **Diálogos:** el de eliminar servicio y el de activar o desactivar usuario, blancos, con radio
  12px y ancho de 420px (medido). Ninguno tiene `role="dialog"`, ni manejo del foco, ni cierre con
  Escape (DS7).
- **Confirmaciones:** `confirm()` nativo en 5 sitios (DS7).
- **Avisos de formulario:** `.alert-error` en el acceso; `.alerta-errores` en servicios y usuarios.

### Estados de pantalla
- **Vacío:** `.empty-state` (icono de 40px y texto) en 15 vistas.
- **Cargando:** no existe.
- **Error del servidor:** no hay páginas propias. 403 y 404 muestran las de Laravel, en inglés y sin
  la marca (capturas `error-403` y `error-404`, DS7).

### Iconos
Iconos SVG de línea en el menú, los botones y las tarjetas. En el panel del paciente se mezclan con
emojis (🕐, 👨‍⚕️, 💡) (DS14).

## Movimiento

| Token | Valor | Uso |
|---|---|---|
| `--duration-fast` | `0.15s` | La duración que ya usan las transiciones de `app.css`; con token, la transición del menú del móvil (spec 033) |
| `--duration-base` (spec 038) | `0.25s` | Transiciones de las páginas públicas |
| `--ease-puerta` (spec 038) | `cubic-bezier(0.2, 0, 0, 1)` | Curva sin rebote del territorio de las páginas públicas |
| (sin token) | `all 0.15s` | Hover de botones, menú y accesos rápidos (`translateY(-1px)` en `.quick-action`) |
| (sin token) | animaciones `fadeUp` y `modalIn` | Entrada del acceso y del registro, y diálogos |

No respeta `prefers-reduced-motion` (DS10), salvo el menú del móvil de la spec 033, que solo se anima
con `prefers-reduced-motion: no-preference`.

## Pantallas principales

Todas son capturas reales (galería completa en `system.html` → Pantallas).

| Pantalla | Móvil | Escritorio | Componentes |
|---|---|---|---|
| Inicio de sesión | `system.html#auth-login-390` | `system.html#auth-login-1440` | campos y botón del acceso, botón de Google |
| Panel del administrador | `system.html#admin-dashboard-390` · `capturas/admin-dashboard-320.png` | `system.html#admin-dashboard-1440` | barra lateral, tarjetas de resumen, tabla (tarjetas en el móvil), badges, accesos rápidos |
| Panel de la dentista (spec 034) | `system.html#dentista-dashboard-390` · `capturas/dentista-dashboard-320.png` | `system.html#dentista-dashboard-1440` | banner, línea de tiempo con acciones, tarjetas de resumen |
| Panel de la asistente (spec 034) | `system.html#asistente-dashboard-390` · `capturas/asistente-dashboard-320.png` | `system.html#asistente-dashboard-1440` | banner, tarjetas de resumen, tabla (tarjetas en el móvil) |
| Citas (agenda del personal) | `system.html#citas-index-admin-390` · `capturas/citas-index-admin-320.png` | `system.html#citas-index-admin-1440` | buscador, atajos, filtro plegable «Filtros», tabla (tarjetas en el móvil), badges |
| Detalle de cita (personal, spec 034) | `system.html#citas-show-admin-390` · `capturas/citas-show-admin-320.png` | `system.html#citas-show-admin-1440` | migas, datos, cambiar estado, información, acciones, control interno, historial (una columna en el móvil) |
| Pacientes (spec 034) | `system.html#pacientes-index-admin-390` · `capturas/pacientes-index-admin-320.png` | `system.html#pacientes-index-admin-1440` | tarjetas de resumen, tabla (tarjetas en el móvil), «⚠ Sí» de alergias |
| Servicios (spec 034) | `system.html#servicios-index-390` · `capturas/servicios-index-320.png` | `system.html#servicios-index-1440` | tarjetas de resumen, tabla (tarjetas en el móvil), badges |
| Ficha de la dentista con horario | `system.html#personal-show-dentista-390` | `system.html#personal-show-dentista-1440` | día del horario, lista de citas, tabla |
| Panel del paciente | `system.html#cliente-dashboard-390` | `system.html#cliente-dashboard-1440` | banner, próxima cita, historial, accesos |
| Reprogramar cita (personal) | `system.html#citas-reprogramar-admin-390` | `system.html#citas-reprogramar-admin-1440` | formulario accesible, resumen de la cita, badge, botón principal, `.btn-danger` |
| Reprogramar cita (paciente) | `system.html#citas-reprogramar-cliente-390` | `system.html#citas-reprogramar-cliente-1440` | formulario accesible, resumen de la cita, badge, botón principal, `.btn-danger` |
| Menú del móvil (spec 033) | `capturas/menu-abierto-cliente-390.png` · `capturas/menu-abierto-admin-390.png` · `capturas/menu-abierto-dentista-390.png` · `capturas/menu-abierto-asistente-390.png` | — (en el escritorio es la barra lateral) | botón «Menú», panel blanco, sección actual con `aria-current`, «Cerrar menú», velo |
| Ficha del paciente (spec 035) | `system.html#pacientes-show-admin-390` · `capturas/pacientes-show-admin-320.png` | `system.html#pacientes-show-admin-1440` | migas, acciones de la barra en una fila, perfil compacto, ficha en una columna, historial de citas (tarjetas en el móvil) |
| Nuevo y editar paciente (spec 035) | `system.html#pacientes-create-admin-390` · `capturas/pacientes-create-admin-320.png` | `system.html#pacientes-create-admin-1440` | migas, formulario con etiquetas y errores accesibles, foco del sistema, acciones de formulario |
| Expedientes (spec 035) | `system.html#expedientes-index-dentista-390` · `capturas/expedientes-index-dentista-320.png` | `system.html#expedientes-index-dentista-1440` | buscador con etiqueta, tabla (tarjetas en el móvil), paginación |
| Expediente (spec 035) | `system.html#expedientes-show-admin-390` · `capturas/expedientes-show-admin-320.png` | `system.html#expedientes-show-admin-1440` | migas, cabecera, avisos de alergias y medicamentos, formulario con etiquetas, próxima visita, información (ficha en una columna) |
| Página de inicio (spec 038) | capturas en T031 | capturas en T031 | cabecera pública, placa, «Sobre nosotros», puertas de categorías, mapa con placa, pie |
| Servicios (`/servicios`, spec 038) | capturas en T031 | capturas en T031 | cabecera pública, índice de puertas, listas por categoría, pie |
| Nuevo expediente (spec 035) | `system.html#expedientes-create-admin-390` · `capturas/expedientes-create-admin-320.png` | `system.html#expedientes-create-admin-1440` | migas, formulario con etiquetas y errores accesibles, acciones de formulario |

**Reprogramar cita (spec 028)**, del personal y del paciente: dentro de `.formulario-accesible`, una
tarjeta con el resumen de la cita (paciente o dentista, servicio, fecha, hora y badge de estado), el
formulario de fecha, hora y motivo con «Guardar nueva fecha», y aparte la sección «No reagendar» con
su motivo, una casilla obligatoria «Entiendo que la cancelación no se puede deshacer» (validada en el
servidor, sin JS ni confirmación nativa) y `.btn-danger` «Cancelar la cita definitivamente», con el
texto «No se puede deshacer». Si la cita ya no se puede mover, muestra el motivo y un enlace al detalle. Sus
capturas a 390 y 1440 px son del 2026-10-10 (spec 028, T032), con `capturar.mjs` y solo esas dos vistas.

A 390 px, 54 de las 62 vistas se desbordan en horizontal: la página mide de 472 a 1157 px de ancho
(DS8). Solo el acceso, el registro, los errores, los reportes y la edición del paciente por la
dentista no se desbordan.

**Móvil (spec 033):** el menú común y las 7 vistas del paciente ya no se desbordan de 320 a 1440 px
(`capturar.mjs --medir`). Sus capturas a 320, 390 y 1440 px y las del menú abierto de cada rol a 390 son
del 2026-10-11 (spec 033, T028), con la base de prueba y textos largos inventados; a 1440 son
iguales a las de antes. Las vistas del personal siguen desbordándose en el móvil (resto de DS8).

**Móvil del personal (spec 034):** los paneles de admin, dentista y asistente, la lista y el detalle
de citas y las listas de pacientes y servicios de los tres roles ya no se desbordan de 320 a 1440 px
(`capturar.mjs --medir`): las tablas se leen como tarjetas, el filtro de citas se pliega tras
«Filtros» y el detalle va en una columna. Sus capturas a 320, 390 y 1440 px, las de las 7 vistas del
paciente y las del menú abierto de los cuatro roles a 390 son del 2026-10-15 (spec 034, T033), con la
base de prueba y textos largos inventados. A 1024 y 1440, esas pantallas son iguales a una línea
base de v0.7.0 generada en la misma sesión (D13), comparadas en reposo: el foco con tokens de lo que
la 034 toca es una diferencia admitida en el escritorio (A72). El resto de las vistas del personal
(formularios, fichas, expedientes) sigue desbordándose en el móvil (resto de DS8).

**Móvil de pacientes y expedientes (spec 035):** la ficha y el formulario del paciente (alta y
edición) y la lista, la ficha y el alta de expedientes ya no se desbordan de 320 a 1440 px
(`capturar.mjs --medir`): las fichas van en una columna en el orden del HTML, el historial de citas y
la lista de expedientes se leen como tarjetas, las acciones de la barra bajan a una fila bajo el
título, el perfil del paciente es compacto y los textos largos parten sin salirse. Los iconos «Menú» y
«Cerrar menú» quedan sin recuadro. Sus capturas a 320, 390 y 1440 px y las del menú abierto de los
cuatro roles a 390 son del 2026-10-18 (spec 035, T028), con la base de prueba y textos largos
inventados. A 1024 y 1440, esas pantallas son iguales a una línea base de v0.8.0 generada en la misma
sesión (D13). Quedan la agenda y los servicios (spec 036) y la administración (spec 037).

## Inventario de vistas

Todas las vistas de la app (rutas, plantillas, diálogos y pasos sin URL propia), capturadas el
2026-10-04 con Playwright 1.63.0 sobre una base de datos **solo de prueba**: el volcado ficticio
anonimizado, una cuenta por rol `<rol>1@dentissa.test` y citas, un expediente y un horario de
prueba. El guion es [capturar.mjs](capturar.mjs). Resultado: **62 de 67** vistas y estados
capturados; los 5 restantes no son accesibles sin Google o son avisos transitorios. Las dos
pantallas de reprogramar (spec 028) se capturaron el 2026-10-10 eligiéndolas en el guion, sin
rehacer el resto de la galería.

| Vista | Ruta / plantilla | Rol | Capturas | Estado |
|---|---|---|---|---|
| Inicio de sesión | /login · auth/login | invitado | capturas/auth-login-390.png · capturas/auth-login-1440.png | capturada |
| Inicio de sesión con error | /login (tras credenciales incorrectas) · auth/login | invitado | capturas/auth-login-error-390.png · capturas/auth-login-error-1440.png | capturada |
| Registro, paso 1 (cuenta) | /registro · auth/register | invitado | capturas/auth-registro-paso1-390.png · capturas/auth-registro-paso1-1440.png | capturada |
| Registro, paso 2 (contacto) | /registro (botón "Continuar", `nextStep(2)`) · auth/register | invitado | capturas/auth-registro-paso2-390.png · capturas/auth-registro-paso2-1440.png | capturada |
| Registro con errores | /registro (tras un envío vacío) · auth/register | invitado | capturas/auth-registro-error-390.png · capturas/auth-registro-error-1440.png | capturada |
| Panel del administrador | /admin · dashboard/admin | admin | capturas/admin-dashboard-390.png · capturas/admin-dashboard-1440.png | capturada |
| Servicios | /admin/servicios · servicios/index | admin | capturas/servicios-index-390.png · capturas/servicios-index-1440.png | capturada |
| Servicios: diálogo de eliminar | /admin/servicios (papelera, `abrirModalEliminar`) · servicios/index | admin | capturas/servicios-modal-eliminar-390.png · capturas/servicios-modal-eliminar-1440.png | capturada |
| Nuevo servicio | /admin/servicios/create · servicios/create | admin | capturas/servicios-create-390.png · capturas/servicios-create-1440.png | capturada |
| Nuevo servicio con errores | /admin/servicios/create (tras un envío vacío) · servicios/create | admin | capturas/servicios-create-error-390.png · capturas/servicios-create-error-1440.png | capturada |
| Editar servicio | /admin/servicios/{id}/edit · servicios/edit | admin | capturas/servicios-edit-390.png · capturas/servicios-edit-1440.png | capturada |
| Citas | /admin/citas · citas/index | admin | capturas/citas-index-admin-390.png · capturas/citas-index-admin-1440.png | capturada |
| Nueva cita | /admin/citas/crear · citas/create | admin | capturas/citas-create-admin-390.png · capturas/citas-create-admin-1440.png | capturada |
| Detalle de cita | /admin/citas/{id} · citas/show | admin | capturas/citas-show-admin-390.png · capturas/citas-show-admin-1440.png | capturada |
| Reprogramar cita (personal) | /admin/citas/{id}/reprogramar · citas/reprogramar | admin | capturas/citas-reprogramar-admin-390.png · capturas/citas-reprogramar-admin-1440.png | capturada |
| Pacientes | /admin/pacientes · pacientes/index | admin | capturas/pacientes-index-admin-390.png · capturas/pacientes-index-admin-1440.png | capturada |
| Nuevo paciente | /admin/pacientes/crear · pacientes/create | admin | capturas/pacientes-create-admin-320.png · capturas/pacientes-create-admin-390.png · capturas/pacientes-create-admin-1440.png | capturada |
| Ficha del paciente | /admin/pacientes/{id} · pacientes/show | admin | capturas/pacientes-show-admin-320.png · capturas/pacientes-show-admin-390.png · capturas/pacientes-show-admin-1440.png | capturada |
| Editar paciente | /admin/pacientes/{id}/edit · pacientes/edit → create | admin | capturas/pacientes-edit-admin-320.png · capturas/pacientes-edit-admin-390.png · capturas/pacientes-edit-admin-1440.png | capturada |
| Nuevo expediente | /admin/expedientes/crear?paciente_id={id} · expedientes/create | admin | capturas/expedientes-create-admin-320.png · capturas/expedientes-create-admin-390.png · capturas/expedientes-create-admin-1440.png | capturada |
| Expediente | /admin/expedientes/{id} · expedientes/show | admin | capturas/expedientes-show-admin-320.png · capturas/expedientes-show-admin-390.png · capturas/expedientes-show-admin-1440.png | capturada |
| Personal | /admin/personal · personal/index | admin | capturas/personal-index-390.png · capturas/personal-index-1440.png | capturada |
| Nuevo miembro del personal | /admin/personal/crear · personal/create | admin | capturas/personal-create-390.png · capturas/personal-create-1440.png | capturada |
| Nuevo personal: asistente | /admin/personal/crear (tipo "Asistente", `selectTipo`) · personal/create | admin | capturas/personal-create-asistente-390.png · capturas/personal-create-asistente-1440.png | capturada |
| Ficha de dentista (horario) | /admin/personal/{id} · personal/show + partials/citas-fuera | admin | capturas/personal-show-dentista-390.png · capturas/personal-show-dentista-1440.png | capturada |
| Ficha de asistente | /admin/personal/{id} · personal/show | admin | capturas/personal-show-asistente-390.png · capturas/personal-show-asistente-1440.png | capturada |
| Ficha de administrativo | /admin/personal/{id} · personal/show | admin | capturas/personal-show-administrador-390.png · capturas/personal-show-administrador-1440.png | capturada |
| Editar personal | /admin/personal/{id}/edit · personal/edit → create | admin | capturas/personal-edit-390.png · capturas/personal-edit-1440.png | capturada |
| Reportes (en construcción) | /admin/reportes · placeholder | admin | capturas/admin-reportes-390.png · capturas/admin-reportes-1440.png | capturada |
| Usuarios | /admin/usuarios · usuarios/index | admin | capturas/usuarios-index-390.png · capturas/usuarios-index-1440.png | capturada |
| Usuarios: diálogo de activar o desactivar | /admin/usuarios (`abrirModalEstado`) · usuarios/index | admin | capturas/usuarios-modal-estado-390.png · capturas/usuarios-modal-estado-1440.png | capturada |
| Nuevo usuario | /admin/usuarios/crear · usuarios/create | admin | capturas/usuarios-create-390.png · capturas/usuarios-create-1440.png | capturada |
| Detalle de usuario | /admin/usuarios/{uuid} · usuarios/show | admin | capturas/usuarios-show-390.png · capturas/usuarios-show-1440.png | capturada |
| Editar usuario | /admin/usuarios/{uuid}/editar · usuarios/edit → create | admin | capturas/usuarios-edit-390.png · capturas/usuarios-edit-1440.png | capturada |
| Panel de la dentista | /dentista · dashboard/dentista | dentista | capturas/dentista-dashboard-390.png · capturas/dentista-dashboard-1440.png | capturada |
| Citas (dentista) | /dentista/citas · citas/index | dentista | capturas/citas-index-dentista-390.png · capturas/citas-index-dentista-1440.png | capturada |
| Nueva cita (dentista) | /dentista/citas/crear · citas/create | dentista | capturas/citas-create-dentista-390.png · capturas/citas-create-dentista-1440.png | capturada |
| Detalle de cita (dentista) | /dentista/citas/{id} · citas/show | dentista | capturas/citas-show-dentista-390.png · capturas/citas-show-dentista-1440.png | capturada |
| Pacientes (dentista) | /dentista/pacientes · pacientes/index | dentista | capturas/pacientes-index-dentista-390.png · capturas/pacientes-index-dentista-1440.png | capturada |
| Ficha del paciente (dentista) | /dentista/pacientes/{id} · pacientes/show | dentista | capturas/pacientes-show-dentista-320.png · capturas/pacientes-show-dentista-390.png · capturas/pacientes-show-dentista-1440.png | capturada |
| Editar paciente (dentista) | /dentista/pacientes/{id}/edit · pacientes/edit | dentista | capturas/pacientes-edit-dentista-320.png · capturas/pacientes-edit-dentista-390.png · capturas/pacientes-edit-dentista-1440.png | capturada |
| Expedientes | /dentista/expedientes · expedientes/index | dentista | capturas/expedientes-index-dentista-320.png · capturas/expedientes-index-dentista-390.png · capturas/expedientes-index-dentista-1440.png | capturada |
| Nuevo expediente (dentista) | /dentista/expedientes/crear?paciente_id={id} · expedientes/create | dentista | capturas/expedientes-create-dentista-320.png · capturas/expedientes-create-dentista-390.png · capturas/expedientes-create-dentista-1440.png | capturada |
| Expediente (dentista) | /dentista/expedientes/{id} · expedientes/show | dentista | capturas/expedientes-show-dentista-320.png · capturas/expedientes-show-dentista-390.png · capturas/expedientes-show-dentista-1440.png | capturada |
| Panel de la asistente | /asistente · dashboard/asistente | asistente | capturas/asistente-dashboard-390.png · capturas/asistente-dashboard-1440.png | capturada |
| Mis doctores | /asistente/doctores · personal/mis-doctores | asistente | capturas/asistente-doctores-390.png · capturas/asistente-doctores-1440.png | capturada |
| Citas (asistente) | /asistente/citas · citas/index | asistente | capturas/citas-index-asistente-390.png · capturas/citas-index-asistente-1440.png | capturada |
| Nueva cita (asistente) | /asistente/citas/crear · citas/create | asistente | capturas/citas-create-asistente-390.png · capturas/citas-create-asistente-1440.png | capturada |
| Detalle de cita (asistente) | /asistente/citas/{id} · citas/show | asistente | capturas/citas-show-asistente-390.png · capturas/citas-show-asistente-1440.png | capturada |
| Pacientes (asistente) | /asistente/pacientes · pacientes/index | asistente | capturas/pacientes-index-asistente-390.png · capturas/pacientes-index-asistente-1440.png | capturada |
| Nuevo paciente (asistente) | /asistente/pacientes/crear · pacientes/create | asistente | capturas/pacientes-create-asistente-320.png · capturas/pacientes-create-asistente-390.png · capturas/pacientes-create-asistente-1440.png | capturada |
| Ficha del paciente (asistente) | /asistente/pacientes/{id} · pacientes/show | asistente | capturas/pacientes-show-asistente-320.png · capturas/pacientes-show-asistente-390.png · capturas/pacientes-show-asistente-1440.png | capturada |
| Expediente (asistente, solo lectura) | /asistente/expedientes/{id} · expedientes/show | asistente | capturas/expedientes-show-asistente-320.png · capturas/expedientes-show-asistente-390.png · capturas/expedientes-show-asistente-1440.png | capturada |
| Panel del paciente | /cliente · dashboard/cliente | cliente | capturas/cliente-dashboard-320.png · capturas/cliente-dashboard-390.png · capturas/cliente-dashboard-1440.png | capturada |
| Mis citas (próximas) | /cliente/citas · citas/cliente-index | cliente | capturas/citas-index-cliente-320.png · capturas/citas-index-cliente-390.png · capturas/citas-index-cliente-1440.png | capturada |
| Mis citas (pasadas) | /cliente/citas?filtro=pasadas · citas/cliente-index | cliente | capturas/citas-index-cliente-pasadas-320.png · capturas/citas-index-cliente-pasadas-390.png · capturas/citas-index-cliente-pasadas-1440.png | capturada |
| Detalle de mi cita | /cliente/citas/{id} · citas/cliente-show | cliente | capturas/citas-show-cliente-320.png · capturas/citas-show-cliente-390.png · capturas/citas-show-cliente-1440.png | capturada |
| Reprogramar mi cita | /cliente/citas/{id}/reprogramar · citas/cliente-reprogramar | cliente | capturas/citas-reprogramar-cliente-320.png · capturas/citas-reprogramar-cliente-390.png · capturas/citas-reprogramar-cliente-1440.png | capturada |
| Agendar cita | /cliente/agendar · citas/cliente-agendar | cliente | capturas/cliente-agendar-320.png · capturas/cliente-agendar-390.png · capturas/cliente-agendar-1440.png | capturada |
| Mi perfil | /cliente/perfil · cliente/perfil | cliente | capturas/cliente-perfil-320.png · capturas/cliente-perfil-390.png · capturas/cliente-perfil-1440.png | capturada |
| Menú del móvil abierto (administración) | /admin (tras pulsar «Menú») · layouts/app | admin | capturas/menu-abierto-admin-390.png | capturada |
| Menú del móvil abierto (dentista) | /dentista (tras pulsar «Menú») · layouts/app | dentista | capturas/menu-abierto-dentista-390.png | capturada |
| Menú del móvil abierto (asistente) | /asistente (tras pulsar «Menú») · layouts/app | asistente | capturas/menu-abierto-asistente-390.png | capturada |
| Menú del móvil abierto (paciente) | /cliente (tras pulsar «Menú») · layouts/app | cliente | capturas/menu-abierto-cliente-390.png | capturada |
| Error 403 (por defecto de Laravel) | /admin con rol dentista · vista por defecto del framework | dentista | capturas/error-403-390.png · capturas/error-403-1440.png | capturada |
| Error 404 (por defecto de Laravel) | /no-existe · vista por defecto del framework | dentista | capturas/error-404-390.png · capturas/error-404-1440.png | capturada |
| Registro con Google: contacto | /registro/contacto · auth/registro-contacto | invitado | — | no accesible (exige volver del acceso con Google con `session('google_registro')`; no se simula sin la cuenta de Google) |
| Registro con Google: contacto con errores | /registro/contacto (tras un envío con errores) · auth/registro-contacto | invitado | — | no accesible (misma sesión de Google) |
| Aviso flash de éxito | layouts/app.blade.php:208 | todos | — | sin captura (estado transitorio tras una acción; se oculta a los 4 s por JS) |
| Aviso flash de error | layouts/app.blade.php:222 | todos | — | sin captura (estado transitorio tras una acción fallida) |
| Aviso de contraseña generada | layouts/app.blade.php:226 | admin | — | sin captura (solo tras dar de alta a alguien sin contraseña; no se crearon cuentas para no mostrar una contraseña) |

Estados: `capturada` · `sin captura (motivo)` · `no accesible (motivo)`.

## Reglas de uso

- Los colores semánticos solo se usan con su significado (rojo = error o cancelada, verde = éxito
  o completada). El estado de una cita se muestra siempre con su badge, que incluye el texto.
- Pantallas nuevas o modificadas: solo tokens de este documento, sin valores literales de color,
  tamaño ni espaciado. Si falta un valor, se añade aquí primero. El código anterior no bloquea
  (regla de "Código previo"; constitución, P9).
- Páginas públicas (spec 038): un momento firma como máximo por página (el carrusel de la 039); las
  promociones (040) sin cintillo, contadores ni colores de oferta; ninguna foto bajo texto; sin scripts
  propios salvo el del carrusel.

## Deuda de diseño

Es lo que el código hace hoy y no cumple este sistema o WCAG 2.1 AA. No bloquea el código previo:
se corrige cuando una spec toca la pantalla o como objetivo del roadmap. Se concilió con la versión
1.1.0 el 2026-10-04: los IDs que siguen ocurriendo se conservan y lo nuevo empieza en DS12.

| ID | Problema | Dónde (evidencia) | Corrección propuesta | Estado |
|---|---|---|---|---|
| DS1 | Texto con contraste < 4.5:1 (medido): botón principal 3.93:1, etiquetas y `th` en `--text-light` 3.51:1, verde de éxito 2.98:1, menú de la barra lateral 3.82:1, `.nav-label` 2.72:1, hover de "Cerrar sesión" (`--p600` sobre `#FF0000`) 1.75:1 | `public/css/app.css:15,19-20,63,107,117,171,210`, `citas.css:12`; capturas `admin-dashboard-1440`, `citas-index-admin-1440` | Oscurecer `--p400` del botón (o usar `--p500`), `--text-light` y `--green`; texto del menú en blanco puro; quitar `#FF0000` | en curso: dentro de `.formulario-accesible` (pantalla de reprogramar y bloques que toca la spec 028) el botón principal va sobre `--p500`, las etiquetas en `--text` y el badge verde con texto `--text`; el filtro de citas que toca la 034 conserva sus etiquetas, «Filtrar» y el atajo activo (decisión del usuario) |
| DS2 | Foco e indicadores invisibles: borde de campo 1.31:1, foco `--p300` 2.23:1 con halo al 7%, `outline: none` en campos y sin `:focus-visible` en botones | `public/css/citas.css:20,29`, `app.css:194-219`, `auth.css:204-221` | Token `--color-focus` ≥ 3:1 y `:focus-visible` en botones, campos y enlaces | en curso: `--focus-ring` y `--border-control` existen; se aplican en el horario de la ficha del personal (spec 027), dentro de `.formulario-accesible` (spec 028) y en el filtro de citas, las tarjetas y el botón de icono (spec 034: los campos del filtro pasan a `--border-control` en los dos modos). Los buscadores de pacientes y servicios y las notas de la dentista siguen sin foco visible (decisión del usuario, spec 034) |
| DS3 | `--text-muted` vale lo mismo que `--text` (#000): no hay jerarquía de texto secundario (medido: todo el texto secundario sale en negro) | `public/css/app.css:13-14` | Definir un secundario que cumpla 4.5:1 | pendiente |
| DS4 | Valores fijos: 861 `style=""` en las vistas, 20 tamaños de letra, escala de espaciado solo en las reglas de la 027; barra lateral `#C75078` y radios literales fuera de los tokens | `resources/views/**`, `public/css/app.css:63,171` | Tokens de espaciado y tipografía; migrar los estilos en línea al tocar cada pantalla | en curso: escala de espaciado, `--text-body`, `--text-small` y pesos (spec 027); filas y campos de la sección de horario de la ficha sin estilos en línea |
| DS5 | Componentes duplicados o contradictorios: 11 bloques `<style>` dentro del contenido redefinen `.field` (borde 1.5px, radio 5px, 'DM Sans'), `citas.css` solo en 7 vistas, y `auth.css` redefine tokens (`--text-light: #000`) | `expedientes/create:139`, `pacientes/create:200`, `usuarios/index:208`…, `public/css/auth.css:8-26` | Una sola hoja de componentes cargada por el layout | en curso: `.field-hint` y `.field-hint-error` pasan a `app.css` (spec 027) |
| DS6 | Objetivo de toque < 44px (medido): botón 31px, `.btn-sm` 22–27px, campo 34–38px, elemento del menú 35px | `public/css/app.css:194-219,353-356`, `citas.css:16-30`; medidas de las 104 vistas con menú | `min-height` con un token `--touch-min` | parcial (033, 034): `--touch-min` existe y se aplica por debajo de 900 px en el menú y las pantallas del paciente (spec 033) y en las pantallas más usadas del personal (spec 034) |
| DS7 | Estados y semántica que faltan: sin deshabilitado ni carga, sin páginas de error propias (403 y 404 de Laravel en inglés), mensajes flash sin `aria-live`, diálogos sin `role="dialog"` ni manejo del foco, 5 `confirm()` nativos, títulos de sección como `span` | `layouts/app.blade.php:207-253`, `servicios/index:121-193`, `usuarios/index:215-299`; capturas `error-403`, `error-404` | Componentes de aviso y diálogo accesibles, estados de carga y páginas de error con la marca | pendiente |
| DS8 | La app no se adapta a móvil (medido): a 390 px, 54 de 62 vistas se desbordan en horizontal (472–1157px de ancho; las dos de reprogramar, 498px por la barra lateral), con barra lateral fija de 242px y rejillas fijas; solo el acceso y el registro se adaptan | `public/css/app.css` (0 `@media`), `:60-70,234,270-272`; capturas `*-390` | Diseño adaptable a partir de 390px, empezando por las pantallas del paciente | parcial (033, 034, 035): menú común y pantallas del paciente desde 320 px (spec 033); paneles, citas con su filtro, detalle de cita, pacientes y servicios del personal, con las tablas como tarjetas (spec 034); ficha y formulario del paciente y lista, ficha y alta de expedientes (spec 035); agenda y servicios (spec 036) y administración (spec 037), pendientes |
| DS9 | Tipografía: Roboto se carga desde Google Fonts sin aprobación en `security.md`; 'DM Sans' y 'DM Mono' se nombran sin cargarse (medido: los campos de usuarios caen a la sans-serif genérica); el peso 300 se carga y no se usa | `layouts/app.blade.php:7`, `usuarios/create:127`, `usuarios/index:210`, `servicios/index:37`, `expedientes/create:110` | Autoalojar Roboto o aprobar el servicio; quitar las familias no cargadas y los pesos sin uso | pendiente |
| DS10 | El movimiento no respeta `prefers-reduced-motion` | `public/css/auth.css:164,346,504-509`, `usuarios/index:295`, `app.css:374` | Bloque `prefers-reduced-motion: reduce` | parcial (033, v0.7.0): solo el menú del móvil (spec 033) |
| DS11 | Restos sin uso: Tailwind 4, Vite y `welcome.blade.php` (ninguna ruta los sirve); clases sin uso (`.logo-icon`, `.appt-card`, `.alert-item`…, y en `auth.css` `.brand-logo`, `.features`…); tokens `--space-7` y `--space-8` sin uso | `package.json`, `vite.config.js`, `resources/views/welcome.blade.php`, `public/css/app.css:37-38,80-94,379,409-417`, `auth.css` | Retirarlos o decidir migrar a ellos | pendiente |
| DS12 | Declarado ≠ cargado en servicios: crear y editar servicio no cargan `citas.css` y su `.field` local no declara familia ni color; los campos se ven en Arial de 13.33px, radio 5px y el desplegable en gris del navegador (medido) | `servicios/create:54-56`, `servicios/edit:60,121-122`; capturas `servicios-create-1440`, `servicios-edit-1440` | Cargar los estilos comunes de campo en el layout (ver DS5) | pendiente |
| DS13 | El campo de hora del horario (`--field-time-width` 110px) recorta "AM/PM" con el formato de 12 h del navegador ("09:00 A") | `public/css/app.css:48`, `personal/show:257-266`; captura `personal-show-dentista-1440` | Ancho mínimo que quepa en 12 h, o formato de 24 h | pendiente |
| DS14 | Iconos mezclados: emojis (🕐, 👨‍⚕️, 💡) junto a los iconos SVG de línea en el panel del paciente | `dashboard/cliente.blade.php`; captura `cliente-dashboard-1440` | Iconos SVG de la misma familia | pendiente |
| DS15 | Marca y títulos: favicon vacío (0 bytes), isotipo `logo-icon1.png` sin uso, y títulos de página por defecto ("Dashboard" en la barra superior de crear y editar servicio, que no definen `title`) | `public/favicon.ico`, `public/images/logo-icon1.png`, `servicios/create`, `servicios/edit`; captura `servicios-create-1440` | Favicon con el isotipo y un título en cada vista | pendiente |

Estados: `pendiente` · `la resuelve NNN` · `resuelta por NNN` · `no se confirma (re-extracción
AAAA-MM-DD: evidencia)`. Un ID nunca se reutiliza.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-02 | diseño | ¿Documentar el diseño existente (upgrade a 1.11.0)? | Sí; sistema extraído, sin rediseño | usuario |
| 2026-10-02 | diseño | Objetivo de accesibilidad (antes "propuesto WCAG 2.1 AA") | WCAG 2.1 AA | usuario |
| 2026-10-02 | diseño | ¿Escritorio o también móvil? | Escritorio; que no se adapte a móvil es deuda (DS8); anchos [390, 1440] | usuario |
| 2026-10-02 | contradicción | `--text-light` vale `#9B8290` en `app.css` y `#000000` en `auth.css` | Se documenta como está; lo resuelve DS5 | /init --upgrade (subagente G) |
| 2026-10-03 | diseño | ¿Qué incorpora el sistema para el horario del dentista (spec 027)? | Versión 1.1.0: escala de espaciado, grosores, `--text-body`/`--text-small`, pesos, `--field-time-width`, un solo token de foco (`--focus-ring` = `--p600`), `--border-control` (`--p400`) solo en los campos de hora, componentes "Día del horario" y "Lista de citas", y `.field-hint` común | usuario (plan 027, D7, D12 y D13) |
| 2026-10-04 | diseño | Re-extracción con el procedimiento de AI-DD 1.11.2 (`/init --upgrade --redo-design`) | Versión 1.2.0: capturas reales de todas las vistas a 390 y 1440 px, medidas con `getComputedStyle`, marca y textos de la app, inventario de vistas y deuda conciliada (DS1–DS11 siguen ocurriendo; DS12–DS15 nuevas). La versión 1.1.0 queda en `history/system.v1.md` | usuario |
| 2026-10-04 | diseño | ¿Con qué datos se capturan las vistas? | Base aparte `dentissa_capturas`: volcado ficticio con nombres, correos, teléfonos, direcciones y notas anonimizados, una cuenta de prueba por rol y citas, expediente y horario de prueba; se borra al terminar | usuario |
| 2026-10-04 | diseño | ¿Con qué herramienta se capturan? | Playwright 1.63.0 en un contenedor Docker temporal (`mcr.microsoft.com/playwright:v1.63.0-noble`); en el repo solo queda el guion `capturar.mjs` | usuario |
| 2026-10-04 | diseño | ¿Se aprueba el sistema re-extraído (1.2.0)? | Aprobado tal como está; la deuda DS1–DS15 sigue pendiente (DS12–DS15 fuera del objetivo 6, al tocar cada pantalla) | usuario |
| 2026-10-04 | implícita | Una vista que responde 5xx con la depuración activa muestra detalles internos | El guion no guarda capturas de respuestas 5xx | `docs/constitution.md` P5 |
| 2026-10-08 | diseño | ¿Qué incorpora el sistema para reprogramar citas (spec 028)? | Versión 1.3.0: botón de peligro `.btn-danger`; componente acotado «Formulario accesible» (`.formulario-accesible`) que cumple AA donde se usa sin cambiar el resto de la app, con el badge verde en `--text`, reglas del historial acotadas y clases de maquetación; token `--timeline-time-width`; pantalla «Reprogramar cita» (capturas en T032); sin `confirm()` nuevo | usuario (plan 028, D11, D12; /analyze A1, A31, A37, A48, A49) |
| 2026-10-10 | diseño | ¿Cómo se confirma la cancelación definitiva de «No reagendar»? | Con una casilla obligatoria «Entiendo que la cancelación no se puede deshacer» (`.campo-casilla`), validada en el servidor y sin JS; sustituye a «sin confirmación» (WCAG 3.3.4) | usuario (/review 028 R6; modifica D12) |
| 2026-10-13 | diseño | ¿Qué incorpora el sistema para el móvil del personal (spec 034)? | Versión 1.5.0: opción A (tarjeta con etiquetas y filtro en la página); tokens `--tarjeta-etiqueta-w`, `--logo-movil-h`, `--space-detalle`, `--detalle-lateral-w` y los cuatro anchos mínimos del filtro; componentes «Tarjeta de lista en el móvil», «Filtro plegable», «Rejilla de estadísticas» y «Botón de icono»; cambian «Botón Menú» (solo icono), «Marca del menú del móvil» (logo con máscara) y la línea de tiempo de la dentista; `--text-marca` sin uso | usuario (spec 034 y plan 034, D1, D4, D5b, D6, D6b, D10; /analyze A33, A35, A36, A59, A60, A68, A69) |
| 2026-10-11 | diseño | ¿Qué incorpora el sistema para el móvil del paciente (spec 033)? | Versión 1.4.0: tokens `--touch-min`, `--overlay`, `--menu-movil-w`, `--duration-fast`, `--topbar-h`, `--border-width-strong`, `--text-meta`, `--logo-max-w`, `--icon-md` y `--text-marca`; punto de corte de 900 px; componentes «Menú del móvil» (opción A), «Pestañas» y «Migas de pan»; anchos [320, 390, 1440]; DS6, DS8 y DS10 en curso | usuario (spec 033 y plan 033, D1, D6, D11, D13; /analyze A1, A4, A35) |
| 2026-10-20 | diseño | ¿Qué incorpora el sistema para las páginas públicas (spec 038)? | Versión 1.7.0: familia `--font-publica` (Lexend autoalojada), `--text-publico-h1`/`-h2` fluidos, `--text-publico-h3`, `--text-publico`, `--text-publico-small`, `--space-seccion` (fluido), `--measure`, `--page-max`, `--duration-base`, `--ease-puerta` y `--green-texto`; secundario público `--p600`; corte de 560 px; componentes «Placa», «Puerta», «Cabecera pública» y «Pie público»; reglas de uso de las páginas públicas. Los tokens de la 039 (`--foto-ratio`, `--thumb-w`, `--duration-firma`) entran con ella | usuario (spec 038, design.md y plan D14) |
| 2026-10-16 | diseño | ¿Qué incorpora el sistema para pacientes y expedientes del personal (spec 035)? | Versión 1.6.0: tokens `--ficha-lateral-w`, `--expediente-lateral-w`, `--perfil-avatar-w`, `--perfil-avatar-movil`, `--perfil-iniciales-text`, `--perfil-iniciales-movil`, `--perfil-nombre-text`, `--perfil-nombre-movil` y `--weight-bold`; componentes «Ficha en una columna», «Perfil compacto del paciente», «Acciones de la barra en el móvil» y «Acciones de formulario»; foco del sistema en los formularios del paciente y del expediente; botones del menú del móvil solo con el icono, sin recuadro | usuario (spec 035, design.md y plan) |
