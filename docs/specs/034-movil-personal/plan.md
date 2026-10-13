---
spec: 034-movil-personal
status: draft
constitution_version: 1.2.1
created: 2026-10-13
---

# Plan · 034 Móvil para el personal y listas en tarjetas

## Enfoque técnico
Por debajo de 900 px, las tablas de las pantallas incluidas pasan a ser tarjetas **solo con CSS**
(opción A, elegida por el usuario: [opcion-a.html](design/opcion-a.html)). Cada `<table>` lleva la
clase `tabla-tarjetas` y cada `<td>` lleva un `data-label` con su etiqueta. En `movil.css`, la fila
se convierte en tarjeta, la celda en una rejilla etiqueta/valor (la etiqueta sale de `::before`) y
las columnas que el móvil no muestra llevan `solo-escritorio`. El escritorio no cambia: las reglas
nuevas van dentro de la media query del móvil, como en la 033.

La prueba de concepto confirma que, en Chromium, el lector recibe cada celda con su etiqueta
(«Teléfono 5550102») y que la columna oculta desaparece también para él.

Solo pasan a clases los `style=""` que impiden la tarjeta o el móvil (D10b, decisión del usuario).
Añadir `data-label`, clases o `aria-label` no cuenta como tocar el estilo.

Además:
- **Filtro de citas:** se reordena para dejar a la vista el buscador y la fila «Esta semana · Hoy ·
  Filtros». Un script propio y pequeño (`filtros.js`), cargado en `<head>`, pliega y despliega el
  resto. Sin JavaScript, todo queda visible.
- **Detalle de cita del personal:** se reordena el HTML al orden de la spec. En el escritorio, una
  rejilla con la columna derecha extendida sobre todas las filas lo deja como hoy.
- **Menú del móvil:**
  - el botón muestra solo el icono, con `aria-label="Menú"`;
  - la marca de texto pasa a ser el logo actual pintado con `--p600` mediante una máscara CSS;
  - 033/R9 se cumple porque, al abrir, el foco entra en la región con nombre «Menú» y los lectores
    la anuncian;
  - `menu.js` incorpora 033/R21.

## Constitution Check
| Principio | Resultado | Evidencia y cómo se verifica |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | La spec 034 se aprueba de nuevo junto con este plan (correcciones de /analyze r1). Verificación: manual, `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | `MovilPersonalTest` (nuevo) y los cambios en `MovilPacienteTest` se escriben antes que el código y fallan hoy. Las comprobaciones nuevas de `--medir` (cambios de modo, palabras largas, solapes) se comprueban rompiendo. Verificación: test: tareas de test de `/tasks` (cada implementación `depende:` de su test) |
| P3 Autorización en el servidor | ➖ | No cambian rutas, controladores ni consultas: las vistas muestran los mismos datos con el mismo alcance (el asistente, solo sus dentistas). Ocultar columnas en el móvil es presentación |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas: el filtro envía los mismos parámetros. El contador de filtros es un número calculado en la vista a partir de esos parámetros, sin consultas nuevas |
| P5 Nada sensible en el repo | ✅ | Capturas y medidas sobre la base desechable `dentissa_capturas` (cabecera de `capturar.mjs`), borrada al terminar. Verificación: test: gitleaks en la verificación final |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-13: `npm audit` 0 vulnerabilidades y `composer audit` sin avisos (2026-10-12). Verificación: test: `composer audit`, `npm audit` y `trivy fs` en la verificación final |
| P7 Auditoría de accesos | ➖ | No toca autenticación, permisos ni datos sensibles nuevos. Sigue la excepción P7 vigente (030/031) |
| P8 Correlación y logs sin datos sensibles | ➖ | No añade logs |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | Reglas nuevas solo con tokens: cuatro tokens nuevos, peso `--weight-strong`, sin `letter-spacing` (A1, A2). Los `style=""` imprescindibles pasan a clases con tokens (D10b, A3). El filtro tocado queda con etiquetas asociadas y foco ≥ 3:1 (A4). Contraste de los pares nuevos calculado desde los tokens. Verificación: test: `MovilPersonalTest` (literales con control positivo, contraste, `for`/`id`, sin los `style=""` imprescindibles); test en navegador: `--medir` (reflujo, toque, solapes, etiquetas, foco); manual: NVDA y capturas contra `opcion-a.html` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`docs/design/system.md`, `system.html`) | Versión 1.5.0 con «Cambios a incorporar al sistema». Las capturas de las pantallas incluidas se rehacen a 320, 390 y 1440 | Bajo |
| Estilos del móvil (`public/css/movil.css`) | Bloque «Spec 034» dentro de la media query: tarjetas (D1), estadísticas en 2 columnas (D10b), filtro (D6), detalle (D7), textos (D8), botón solo con icono (D9) y logo (D10). Fuera de la media query, solo lo de «Reglas fuera del móvil» | Medio: afecta a todas las listas por debajo de 900 px |
| Estilos existentes (`public/css/app.css`) | Los cuatro tokens nuevos en `:root`. Comentario de cierre `/* Fin spec 033 */` (A26). Bloque `/* Spec 034 */` con las clases de los `style=""` imprescindibles (rejillas de estadísticas, filtro, rejilla del detalle) con los mismos valores, la rejilla de escritorio del detalle (D7) y el foco del filtro (D6) | Medio: el escritorio debe quedar igual (CA11) |
| Script del filtro (`public/js/filtros.js`, **nuevo**) | Contrato en «Script `filtros.js`». Se carga en `<head>` con `@push('styles')` solo en la lista de citas | Bajo |
| Script del menú (`public/js/menu.js`) | R21: `ultimoFoco` se olvida cuando el foco se pierde sin que el elemento se oculte | Bajo |
| Layout (`resources/views/layouts/app.blade.php`) | Botón «Menú» sin texto, con `aria-label="Menú"`. `.sidebar-marca` sin texto, con `role="img"` y `aria-label="Melissa López N."` | Bajo |
| Lista de citas del personal (`resources/views/citas/index.blade.php`) | Rejilla de estadísticas a `stats-grid`. Filtro reordenado y accesible (D6). Tabla con `tabla-tarjetas`, `data-label`, clases de celda y `fila-hoy`. «Ver detalle» con `aria-label` y texto en el móvil (D5) | Medio |
| Detalle de cita del personal (`resources/views/citas/show.blade.php`) | Orden del HTML: datos, cambiar estado, información y acciones, control interno e historial (D7), conservando sus `id` y envoltorios. Las rejillas pasan a clases | Medio: el escritorio debe quedar igual |
| Pacientes (`resources/views/pacientes/index.blade.php`) | Estadísticas a `stats-grid stats-grid-3`. Tabla en tarjetas; doctor, ciudad, tipo de sangre y registrado con `solo-escritorio`, y el `div` del correo también. «Ver perfil» y «Editar» con nombre y texto en el móvil (D5) | Bajo |
| Servicios (`resources/views/servicios/index.blade.php`) | Estadísticas a `stats-grid stats-grid-2` (conserva su `max-width`). Tabla en tarjetas; descripción y categoría con `solo-escritorio`. «Editar» y «Eliminar» con nombre y texto en el móvil (D5) | Bajo |
| Paneles (`resources/views/dashboard/{admin,asistente,dentista,cliente}.blade.php`) | Admin y asistente: estadísticas a `stats-grid stats-grid-2` y tabla de citas de hoy en tarjetas. Dentista: su enlace de la agenda con `aria-label="Ver detalle"` (sin tarjetas, spec CA2). Paciente: tabla de citas pasadas en tarjetas y `texto-partible` en la próxima cita (D8) | Bajo |
| Mis citas y detalle del paciente (`resources/views/citas/cliente-index.blade.php`, `cliente-show.blade.php`) | Tabla en tarjetas (033/R20). En el detalle, `texto-partible` en la rejilla de datos (D8) | Bajo |
| Tests (`tests/Feature/MovilPersonalTest.php` **nuevo**, `tests/Feature/MovilPacienteTest.php`) | Ver Estrategia. En `MovilPacienteTest` cambian el nombre del botón (texto → `aria-label`), la marca, el contrato de `menu.js` (R21), los textos largos, las reglas fuera del móvil y el final de `bloque033()` | Bajo: tests de la 033 que cambian por la 034 (extends) |
| Capturas y medidas (`docs/design/capturar.mjs`) | `--medir` añade las pantallas incluidas, solapes, etiquetas, filtro, detalle, logo, botón solo con icono, región «Menú», cambios de modo (033 T064 y R21), palabras de 30 letras y la comparación con la línea base (D13) | Medio: es la red de seguridad de la spec |

## Contratos y datos
Sin endpoints, migraciones ni datos nuevos. Las vistas reciben las mismas variables.

**D1 · Tabla en tarjetas.**
- **Marcado:** `<table class="tabla-tarjetas">`. Cada `<td>` lleva `data-label="<etiqueta>"`, y su
  `<th>` y él llevan las mismas clases de columna.
- **Clases de celda:**
  - `celda-cab`: el dato principal (fecha y hora, o el nombre del paciente o del servicio); va arriba
    y sin etiqueta visible (spec CA1, CA2);
  - `celda-estado`: el estado, arriba a la derecha y sin etiqueta visible;
  - `celda-acciones`: los botones, a lo ancho al pie, con el borde superior;
  - `solo-escritorio`: la columna no se muestra en el móvil.
- **En el móvil (`movil.css`):**
  - `thead` sale con `display: none` (prueba de concepto del 2026-10-13);
  - `tbody` y `tr` en bloque. Cada `tr` es una tarjeta: `--white`, borde `--border` decorativo
    (spec), `--radius`, relleno `--space-5` y `--space-6`, separación `--space-5`, con una
    rejilla `minmax(0, 1fr) auto` (cabecera y estado arriba);
  - cada `td` anula el relleno y el borde de `app.css` (`padding: var(--space-1) 0`,
    `border-bottom: 0`) y es una rejilla `var(--tarjeta-etiqueta-w) minmax(0, 1fr)`;
  - su `::before`: `content: attr(data-label)`, `--text-small`, `--weight-strong`, mayúsculas,
    `--p600`, sin `letter-spacing` (A1);
  - `.celda-cab` y `.celda-estado` sin `::before`; las celdas de valor con
    `overflow-wrap: break-word`;
  - **acciones (A18):** `.celda-acciones` ocupa las dos columnas. Sus envoltorios (`div`, `form`)
    son `flex` con `flex-wrap` y `gap: var(--space-3)`, y sus `.btn` llevan `flex: 1 1 auto`;
  - `.fila-hoy`: barra izquierda `--border-width-strong` `--p400` (el fondo `--p50` sigue en el
    `style=""` de la fila, que no se toca).
- Las etiquetas de cada tabla son las del texto de su `<th>`, salvo «Contacto» de pacientes, que en
  la tarjeta se llama «Teléfono».

**D2 · Qué muestra cada tarjeta** (spec y supuestos).

| Pantalla | Cabecera | Estado | Con etiqueta | Acciones | Solo en el escritorio |
|---|---|---|---|---|---|
| Mis citas (paciente) | Fecha / Hora | sí | Servicio, Dentista | Ver, Reprogramar, Cancelar (las que correspondan) | — |
| Panel del paciente (citas pasadas) | Fecha (sin hora, como hoy) | sí | Servicio, Dentista | — | — |
| Citas del personal | Hora / Fecha (con «Hoy») | sí | Paciente, Dentista, Servicio, Duración | Ver detalle | — |
| Citas de hoy del panel (admin, asistente) | Hora | sí | Paciente, Dentista (o Doctor), Servicio | — | — |
| Pacientes | Paciente (nombre, sin el `div` del correo) | — | Teléfono, Alergias | Ver perfil, Editar | correo (`div` dentro de la celda), Doctor, Ciudad, Tipo sangre, Registrado |
| Servicios | Nombre | — | Duración, Estado | Editar, Eliminar | Descripción, Categoría |

El panel de la dentista conserva su línea de tiempo (spec CA2): solo cumple CA8 y su enlace gana
nombre (D5).

**D5 · Botones de icono con nombre y texto en el móvil.** Hoy los enlaces y botones de icono de las
tablas incluidas solo tienen `title` («Ver detalle», «Ver perfil», «Editar», «Eliminar»):
- ganan `aria-label` con ese texto;
- dentro llevan el mismo texto en `<span class="solo-movil">`, que fuera del móvil no se muestra
  (`display: none`), así que el escritorio se ve igual.

El enlace de la agenda de la dentista solo gana `aria-label="Ver detalle"`.

**D6 · Filtro de citas.**
- **Orden del formulario** (`.filtro-citas`):
  - el buscador;
  - la fila de atajos: «Esta semana» y «Hoy», en el orden de hoy (A15), y el botón «Filtros»;
  - `<div id="filtro-panel" class="filtro-panel">` con desde, hasta, dentista (salvo para la
    dentista), estado, «Filtrar» y «Limpiar».
- **Accesibilidad del bloque tocado (A4):**
  - cada `<label>` lleva `for` y cada campo su `id`;
  - el foco de los campos (`.filtro-citas .field:focus-visible`) usa `--focus-ring`,
    `--focus-ring-width` y `--focus-ring-offset` en los dos modos;
  - en el móvil, el borde de los campos es `--border-control` (≥ 3:1). En el escritorio, el borde en
    reposo no cambia (deuda DS2, decisión del usuario: escritorio igual, CA11).
- **Clase del formulario:** el `style=""` del `<form>` (flex con `flex-wrap`, `gap: 10px`, `align-items:
  flex-end`) pasa a `.filtro-citas` con `--space-4`. Los `min-width` en línea de cada campo se
  quedan (no impiden el móvil).
- **Escritorio** (se ve igual, CA11):
  - `.filtro-panel` con `display: contents` y «Filtros» oculto;
  - la fila de atajos al final, con `order` y `margin-left: auto`, como hoy;
  - el orden de tabulación pasa a ser buscador, atajos y campos: aceptado por el usuario (A6).
- **Botón:** `<button type="button" class="btn filtro-btn" aria-expanded="false"
  aria-controls="filtro-panel">Filtros</button>`.
- **Contador:** con filtros aplicados se muestra `<span class="filtro-contador">n</span>` y el botón
  pasa a `aria-label="Filtros, n aplicados"`. Cuentan desde (si no es hoy), hasta, dentista y
  estado.
- **Móvil (A19):** el panel desplegado es una rejilla de dos columnas: desde y hasta lado a lado;
  dentista y estado a lo ancho; «Filtrar» y «Limpiar» lado a lado. Los campos ocupan el ancho de su
  celda.
- **Plegado:**
  - sin JavaScript, «Filtros» no se muestra y el panel queda desplegado;
  - con `filtros.js`, `<html>` lleva `filtros-js` desde `<head>`, sin salto de maquetación (A31), y
    el panel está oculto mientras el formulario no tenga `abierto`;
  - «Filtros» alterna `abierto` y `aria-expanded`.

**Script `filtros.js`.** Sin dependencias ni HTML construido desde cadenas.
- Se carga en `<head>` sin `defer`, con `@push('styles')` y `?v=`.
- Marca `<html>` con `filtros-js` al cargar.
- En `DOMContentLoaded` busca `.filtro-citas` y sale si no existe.
- El clic en `.filtro-btn` alterna `abierto` en el formulario y `aria-expanded`; el foco se queda en
  el botón.
- No usa `max-width` (el modo lo decide `movil.css`) ni `innerHTML`.

**D7 · Detalle de cita del personal.**
- **HTML.** Dentro de `<div class="detalle-cita">`, en este orden:
  - `detalle-datos`: la tarjeta de la cita;
  - `detalle-lateral`: lo que hoy es la columna derecha: `#estado`, la tarjeta de información y
    `.formulario-accesible > #acciones`;
  - `detalle-control`: `#control-interno`, solo para el admin;
  - `detalle-historial`: `.formulario-accesible > #historial`.

  Desaparecen el envoltorio de la columna izquierda y el `style=""` de la rejilla. Se conservan
  todos los `id` y `.formulario-accesible` (`ReprogramarCitaVistasTest`, A14).
- **Escritorio (`app.css`, bloque 034):** `.detalle-cita` es una rejilla
  `1fr var(--detalle-lateral-w)` con filas `auto auto auto 1fr`, `column-gap: var(--space-7)`, **sin
  `row-gap`** y `align-items: start`.
  - `detalle-lateral` ocupa la columna 2 y las filas `1 / -1`, con su `gap` de hoy
    (`--space-detalle`);
  - `detalle-control` y `detalle-historial` llevan `margin-top: var(--space-detalle)` en la
    columna 1;
  - sin control interno, su fila queda a alto 0 y sin hueco, y la fila flexible del final absorbe
    la diferencia de alto entre columnas (A9).
- **Móvil:** una columna en el orden del HTML, con `--space-detalle` entre bloques. La rejilla
  interna de 3 columnas pasa a 1 columna: su `grid-template-columns` en línea pasa a la clase
  `detalle-datos-rejilla`, y su `gap` en línea se queda.
- **CA11:** se comprueba contra la línea base a 1024 y 1440 px, del admin (con control interno), del
  asistente y de la dentista (sin él), y con un historial largo.

**D8 · Textos (CA7).**
- `.content` vuelve a `overflow-wrap: break-word`, y desaparece la regla `.content table` (033 T069).
- `overflow-wrap: anywhere` solo en `.texto-partible` y en `.user-name`. `.texto-partible` se
  añade, sin tocar su `style=""`, a los dos casos de 033/R1: la rejilla del detalle del paciente y
  la tarjeta de la próxima cita del panel.
- Las celdas de las tarjetas parten bien con `break-word` porque su columna es `minmax(0, 1fr)`.
- `--medir` lo prueba con una palabra de 30 letras en todas las pantallas incluidas y en las 7 del
  paciente (A11).

**D9 · Botón «Menú» solo con icono.** Sin el texto «Menú» y con `aria-label="Menú"`.
`width` y `height` a `--touch-min`, relleno 0. El SVG sigue con `aria-hidden`.

**D10 · Logo en el menú.**
- `.sidebar-marca` (vacío, `role="img"`, `aria-label="Melissa López N."`) es, en el móvil, un bloque
  de `--logo-max-w` × `--logo-movil-h`.
- Lo pinta `background: var(--p600)` a través de `mask` y `-webkit-mask` con
  `url("../images/logo-icon.png") left center / contain no-repeat`.
- Contraste `--p600` sobre `--white`: 7,0:1 (≥ 3:1, WCAG 1.4.11). La CSP de la 031 lo permite
  (`img-src 'self'`).
- En el escritorio sigue el `<img>` actual sobre la barra rosa.

**D10b · `style=""` imprescindibles (decisión del usuario, A3).** Pasan a clases con tokens solo los
que impiden la tarjeta o el móvil:
- **Rejillas de estadísticas:**
  - `citas/index:24` → `stats-grid`;
  - `pacientes/index:17` → `stats-grid stats-grid-3`;
  - `servicios/index:16` → `stats-grid stats-grid-2`, conservando `style="max-width:600px"`;
  - `dashboard/admin:16` y `asistente:25` → `stats-grid stats-grid-2`.

  `.stats-grid` ya existe en `app.css`. Las clases `-2` y `-3` del bloque 034 solo fijan
  `grid-template-columns`. En el móvil, `.stats-grid` pasa a dos columnas.
- **Formulario del filtro** (D6).
- **Rejillas del detalle de cita** (D7).

El resto de `style=""` de esas vistas se queda como está (DS4). Sus pares de color no se tocan: los
que no cumplen 4,5:1 (`--green` 3,39:1 en servicios y `--text-light` en etiquetas del detalle)
siguen como deuda DS1 (A5, decisión A3).

**D11 · Menú: 033/R9 y R21.**
- **R9:** el `<aside id="menu-principal" aria-label="Menú">` ya es una región con nombre. Al abrir, el
  foco entra en ella y los lectores la anuncian («Menú, complementario» en NVDA). El botón queda bajo
  el panel y no recibe el foco. Sin código nuevo:
  - `--medir` comprueba que, al abrir, el foco queda dentro de esa región;
  - NVDA lo confirma a mano.

  `role="dialog"` no está permitido en `<aside>` (A23).
- **R21:** un `focusout` olvida `ultimoFoco` cuando el foco se pierde sin `relatedTarget` y el
  elemento sigue visible (clic en una zona sin foco). Si el elemento se oculta por el cambio de modo,
  se conserva.

**D13 · Comparación con la línea base (A21).**
- **Línea base:** `capturar.mjs` la guarda fuera del repositorio el mismo día de la comparación,
  porque las fechas de la base de prueba son relativas. Incluye:
  - las pantallas incluidas del personal a 1024 y 1440;
  - las del paciente a 1440;
  - el detalle de cita de los tres roles del personal, también con historial largo.
- **«Igual»:** como mucho el 0,1 % de los píxeles distintos y ninguno con una diferencia de
  intensidad mayor que 2/255 (ruido de renderizado).

**Reglas fuera del móvil (únicas permitidas, ampliando la lista de la 033):**
- en `movil.css`:
  - `.filtro-btn, .solo-movil { display: none; }`;
  - `.filtro-panel { display: contents; }`;
- en `app.css`:
  - el bloque 034 con las clases de los `style=""` imprescindibles (mismos valores que hoy, con
    tokens);
  - la rejilla de escritorio de `.detalle-cita` (D7);
  - el foco del filtro (D6).

### Cambios a incorporar al sistema (system.md 1.4.0 → 1.5.0)
- **Tokens:**
  - `--tarjeta-etiqueta-w: 88px` (columna de la etiqueta en la tarjeta del móvil);
  - `--logo-movil-h: 40px` (alto del logo en el menú del móvil);
  - `--space-detalle: 18px` (hueco del detalle de cita, el valor de hoy);
  - `--detalle-lateral-w: 360px` (columna lateral del detalle de cita, el valor de hoy) (A2).
  - `--text-marca` queda sin uso: se anota en el sistema y no se retira, porque lo comprueba el test
    de la 033.
- **Componentes nuevos:**
  - «Tarjeta de lista (móvil)»: tabla con `tabla-tarjetas`, clases de celda, etiqueta `--p600`
    sobre `--white` (7,0:1) y borde `--border` decorativo (spec);
  - «Filtro plegable»: botón, contador `--white` sobre `--p500`, estados y foco;
  - «Rejilla de estadísticas»: `stats-grid` con `-2` y `-3`.
- **Componentes que cambian:** «Botón Menú» (solo icono) y «Marca del menú del móvil» (logo con
  máscara).
- **Pantallas:** filas de las pantallas incluidas a 320, 390 y 1440 px. Notas de DS6 y DS8
  («parcial (033, 034)») y de DS2 (borde del filtro en el escritorio, sin cambio).
- **Decisiones:** fila de la opción A (2026-10-13, usuario).

## Estrategia de pruebas
- **PHPUnit, `tests/Feature/MovilPersonalTest.php` (nuevo, con el trait `ReglasCss`):**
  - **Marcado de las tarjetas:** cada tabla incluida tiene `tabla-tarjetas`; cada `<td>` del cuerpo
    tiene un `data-label` igual a su `<th>` (salvo Teléfono) y las mismas clases de columna; las
    columnas de D2 llevan `solo-escritorio`, y las esenciales no; el `div` del correo lleva
    `solo-escritorio`, sin contar como columna (A16).
    - **Pantallas:** las del paciente con su cuenta; el resto con las cuentas de admin, dentista y
      asistente de la base de tests, con citas y pacientes creados en el test.
  - **Cita de hoy:** la fila lleva `fila-hoy` y el texto «Hoy».
  - **Botones de icono:** «Ver detalle», «Ver perfil», «Editar» y «Eliminar» tienen `aria-label` y
    su texto en `.solo-movil`; el enlace de la agenda de la dentista, su `aria-label`.
  - **`style=""` imprescindibles:** las rejillas de estadísticas, el `<form>` del filtro y las
    rejillas del detalle ya no tienen esas declaraciones (XPath, con control positivo).
  - **Filtro:**
    - orden del formulario y de los atajos;
    - `for`/`id` de cada campo;
    - botón con `aria-expanded="false"` y `aria-controls`;
    - contador y `aria-label`, con dos filtros aplicados y sin ninguno;
    - `filtros.js` en `<head>` con `?v=`.
  - **Detalle:** orden de los bloques por rol (admin con control interno; asistente y dentista sin
    él); se conservan los `id` y `.formulario-accesible`.
  - **`movil.css`:**
    - reglas de D1, D6–D10 y de las estadísticas dentro de la media query, con sus tokens;
    - `.content` con `break-word`, y `anywhere` solo en `.texto-partible` y `.user-name`.
  - **`app.css`:** bloque 034 solo con tokens (literales con control positivo), rejilla de
    escritorio de D7 y foco del filtro.
  - **Contraste:** etiqueta, contador, borde de los campos del filtro en el móvil, foco y logo,
    calculados desde los tokens.
  - **Tokens:** los cuatro nuevos coinciden con `system.md`.
  - **Contrato de `filtros.js`** (texto, como el de `menu.js`): el comportamiento lo mide `--medir`.
- **PHPUnit, `tests/Feature/MovilPacienteTest.php` (033, cambia por la 034):**
  - el nombre del botón pasa a `aria-label="Menú"` sin texto;
  - la marca, a `role="img"` y `aria-label`;
  - las declaraciones de `.sidebar-marca`, a las de D10;
  - `test_los_textos_largos_parten_de_linea`, según D8;
  - el contrato de `menu.js` exige el `focusout` de R21;
  - la lista de reglas fuera del móvil (única comprobación de ese conjunto, A28) se amplía con las
    del plan;
  - `bloque033()` se corta en `/* Fin spec 033 */` (A26).
- **Navegador, `capturar.mjs --medir`** (obligatorio por `AGENTS.md`), contra la base de prueba.
  Pantallas incluidas de los cuatro roles y las 7 del paciente, a 320, 390, 844 × 390 y 1440 px, y
  sin JavaScript a 390:
  - sin desplazamiento horizontal;
  - **solapes:** ningún control visible se cruza con otro ni sale de su tarjeta;
  - toque ≥ 44 × 44 a 390;
  - cada celda de valor visible de una tarjeta muestra su etiqueta;
  - **palabras de 30 letras:** temporales, en el nombre de un paciente, un dentista y un servicio, sin
    desplazamiento ni partición letra a letra de las palabras que caben (A11);
  - **filtro:** plegado al cargar, «Filtros» lo despliega y cambia `aria-expanded`; sin JavaScript,
    desplegado; foco visible en sus campos;
  - **detalle:** una columna en el orden de D7;
  - **logo:** visible, de `--logo-max-w` de ancho y con fondo `--p600`;
  - **botón «Menú»:** sin texto visible, 44 × 44, nombre «Menú»; al abrir, el foco queda dentro de
    `#menu-principal`;
  - **cambios de modo** (los casos (a)–(d) de 033 T064 y R21): con el foco en «Cerrar menú», al pasar
    a 1000 px va al enlace actual; con el foco en un enlace del menú, al pasar a 390 va a «Menú»;
    tras hacer clic en una zona sin foco, el cambio de modo no mueve el foco (A10);
  - **escritorio:** a 1024 y 1440, igual que la línea base según D13 (CA11).
  - Cada comprobación nueva se comprueba rompiendo.
- **WebKit (A25):** el árbol de accesibilidad de una tarjeta (Playwright WebKit) conserva etiqueta y
  valor de cada celda.
- **Verificación manual:**
  - capturas a 320, 390 y 1440 px contra `design/opcion-a.html` y `system.html`;
  - NVDA en Windows (usuario): una tarjeta se lee con etiqueta y valor; «Filtros, botón, contraído»
    (y «2 aplicados»); al abrir el menú, la región «Menú»; el logo como «Melissa López N., gráfico».

## Modelo de amenazas
No aplica: la spec solo cambia la presentación de datos que cada rol ya puede ver, con el mismo
alcance. No añade entradas, acciones, permisos ni datos. `filtros.js` es propio, del mismo origen,
no construye HTML ni lee datos de la página, y encaja con la CSP de la 031. Las columnas ocultas en
el móvil siguen en el HTML, como hoy: no es un control de acceso.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: tarjetas del paciente con acciones visibles | D1, D2 en `cliente-index` y `dashboard/cliente` | `MovilPersonalTest` (marcado de tarjetas del paciente) · `--medir` (etiquetas, solapes, sin desplazamiento) · WebKit (árbol de accesibilidad) · verificación manual: capturas a 320/390 contra `opcion-a.html` y NVDA |
| CA2: tarjetas de citas del personal y del panel (admin, asistente); hoy no solo por color; dentista con su línea de tiempo | D1, D2, D5, D10b en `citas/index`, `dashboard/admin`, `dashboard/asistente`, `dashboard/dentista` | `MovilPersonalTest` (tarjetas, «Hoy», `fila-hoy`, botones con nombre, estadísticas) · `--medir` |
| CA3: pacientes solo con lo esencial | D1, D2, D5, D10b en `pacientes/index` | `MovilPersonalTest` (`solo-escritorio`, correo) · `--medir` (ocultas a 390, visibles a 1440) |
| CA4: tarjeta de servicio | D1, D2, D5, D10b en `servicios/index` | `MovilPersonalTest` · `--medir` |
| CA5: filtro plegable con contador; sin JS, desplegado | D6, `filtros.js` | `MovilPersonalTest` (orden, `for`/`id`, botón, contador, CSS, contrato de `filtros.js`) · `--medir` (plegado, despliegue, `aria-expanded`, sin JS, foco) · verificación manual: NVDA («Filtros, botón, contraído») |
| CA6: detalle del personal en una columna | D7 | `MovilPersonalTest` (orden por rol, `id` conservados, rejillas con clase) · `--medir` (una columna, sin desplazamiento ni solapes) |
| CA7: textos sin partir letra a letra | D8, D1 (`minmax(0, 1fr)`) | `MovilPacienteTest` (`break-word`/`anywhere`) · `--medir` con palabras de 30 letras en todas las pantallas incluidas y las 7 del paciente |
| CA8: sin desplazamiento, sin solapes, toque 44 | D1–D7, D10b | `--medir` (anchos, solapes y toque a 320, 390 y 844 × 390) |
| CA9: botón solo con icono; R9 y R21 | D9, D11, `menu.js` | `MovilPacienteTest` (nombre del botón, contrato de `menu.js`) · `--medir` (44 × 44, nombre, foco en la región al abrir, cambios de modo y R21) · verificación manual: NVDA («Menú, botón, contraído», región «Menú») |
| CA10: logo con contraste ≥ 3:1 y nombre | D10 | `MovilPacienteTest`/`MovilPersonalTest` (marca, máscara, contraste desde tokens) · `--medir` (logo visible) · verificación manual: NVDA y captura del menú abierto |
| CA11: escritorio igual | Reglas dentro de la media query; fuera, solo las previstas; D5–D7, D10b sin cambio visual | `MovilPacienteTest` (reglas fuera del móvil) · `--medir` a 1024/1440 frente a la línea base (D13) · verificación manual: capturas a 1440 |
| RNF: WCAG 2.1 AA (1.4.10, 1.3.1, 1.4.11, 2.4.7, 4.1.2, 1.4.1) | D1, D5, D6, D9, D10, D11 | Contraste, etiquetas y `for`/`id` en `MovilPersonalTest` · `--medir` · WebKit · verificación manual: NVDA |
| RNF: toque ≥ 44 × 44 | D1, D6, D9 | `--medir` a 390 |
| RNF: medido a 320, 390, 844 × 390 y 1440, también sin JS | `capturar.mjs` | `--medir` |
| RNF: escritorio sin cambios contra capturas de antes | D13 | `--medir` (línea base a 1024 y 1440) |

## Observabilidad
No aplica: no toca datos sensibles, autenticación ni permisos, y no añade logs, eventos ni
métricas. Tras el despliegue se comprueba a mano, desde el móvil del usuario, la dirección pública.

## Rollout
- **Feature flag:** no. Solo cambia la presentación por debajo de 900 px y el escritorio no cambia
  (CA11); el proyecto no tiene mecanismo de flags.
- **Orden de despliegue:** `deploy/desplegar.sh <tag>`. No hay migraciones. Los estáticos nuevos
  (`filtros.js`, `movil.css`, `app.css`) van en la imagen `web` con `?v=filemtime`.
- **Compatibilidad:** sin cambios de datos; una página en caché no mezcla versiones por el `?v=`.
- **Rollback:** `bash deploy/volver.sh <tag anterior>` (v0.7.0 si es la siguiente versión), sin
  copia de la base.
- **Orden respecto a otras specs:**
  - no comparte código con la 031 (servidor, nginx, Google) ni con la 032 (scripts de despliegue);
  - la 020 (rama `feature/020-auditoria-base`) también toca `layouts/app.blade.php` y
    `docs/architecture.md`. La que se fusione después resuelve los conflictos: «Auditoría» en el menú
    con el patrón de la 033 y la sección Frontend de `architecture.md` (A29);
  - la versión se fija en `/release` (v0.8.0 si sale antes que la 031; si no, v0.9.0).
- **Métricas a vigilar tras el deploy:** errores 500 en `laravel.log` y 5xx en nginx durante
  15 minutos. Desde el móvil real: mis citas del paciente, citas, detalle, pacientes y servicios del
  personal, el filtro y el menú.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Decisión | Alternativas | Motivo |
|---|---|---|---|
| D1 | Tablas en tarjetas solo con CSS y `data-label` | Marcado aparte para el móvil (lista duplicada); etiquetas en CSS por posición (`nth-child`) | Un solo marcado y un solo origen de datos. La prueba de concepto del 2026-10-13 confirma etiquetas y celdas ocultas en el árbol de accesibilidad de Chromium |
| D2 | Contenido de cada tarjeta según la tabla D2 | Mostrar todas las columnas | Spec CA1–CA4 y sus supuestos; cabecera y estado sin etiqueta (decisión del usuario, A7) |
| D3 | Ocultar `thead` con `display: none` en el móvil | Ocultarlo solo visualmente (`clip-path`, con literales en px) | Cada celda ya lleva su etiqueta; evita literales (P9) |
| D4 | Opción A completa, tarjeta con etiquetas y filtro en la página (decisión del usuario, 2026-10-13) | Opción B (cabecera y hoja inferior); mezclas | Menos piezas: sin velo ni manejo del foco en el filtro, y los botones bajan de línea a 320 px |
| D5 | Botones de icono con `aria-label` y texto visible solo en el móvil | Cambiar también el escritorio | El escritorio se ve igual y los botones ganan nombre accesible; el diseño los muestra con texto (A17) |
| D6 | `filtros.js` aparte, cargado en `<head>` solo en la lista de citas | Ampliar `menu.js`; `<details>` sin JS; cargarlo al final del `<body>` | Contratos separados. `<details>` empezaría plegado sin JS (CA5). En `<head>` no hay salto de maquetación (A31) |
| D6b | Orden de tabulación del filtro en el escritorio: buscador, atajos y campos (decisión del usuario, A6) | Panel encima de los atajos | El escritorio se ve igual y los atajos son independientes de los campos |
| D7 | Detalle: HTML en el orden de la spec y rejilla de escritorio con la columna derecha extendida, márgenes en lugar de `row-gap` | Reordenar con `order` y `display: contents` | El orden de foco y de lectura coincide con el visual en el móvil (WCAG 2.4.3); el escritorio se mantiene igual con cualquier rol (A9) |
| D8 | `anywhere` solo donde hace falta (033/R1) | Mantenerlo en `.content` | En el personal parte el texto letra a letra (visto en producción, v0.7.0) |
| D9 | Botón con `aria-label="Menú"` | Texto solo para lectores con una clase de ocultación (no existe y usaría literales en px) | El nombre accesible se mantiene sin reglas nuevas |
| D10 | Logo actual como máscara pintada con `--p600` | Filtro CSS (no da el color exacto y usaría números sueltos); recurso nuevo coloreado; panel rosa (DS1) | Color exacto del sistema sin redibujar el logo (decisión del usuario: derivarlo del actual). Comprobado en el navegador servido por http el 2026-10-13 (con `file://` la máscara no carga) |
| D10b | Solo pasan a clases los `style=""` que impiden la tarjeta o el móvil (decisión del usuario, A3) | Inventario completo con tokens nuevos | P9 prohíbe literales nuevos, no obliga a limpiar el código anterior; menos tokens y menos riesgo para el escritorio. La deuda de color (DS1) y de estilos en línea (DS4) sigue |
| D11 | R9 por la región con nombre «Menú» (sin código) y R21 en `menu.js` (decisión del usuario: R9 y R21 dentro) | `role="dialog"` en el `<aside>` (no permitido, A23); en un envoltorio con `display: contents` (fallos de Safari); botón alcanzable con el panel abierto (tapado) | El foco entra en la región con nombre al abrir y los lectores la anuncian |
| D12 | Sin ADR | — | Sin dependencias nuevas ni cambio de arquitectura: un script propio más, como `menu.js` |
| D13 | Comparación con la línea base: mismo día, ≤ 0,1 % de píxeles distintos y diferencia ≤ 2/255 | Comparación visual a mano | Repetible y con fechas relativas iguales en la base de prueba (A21) |

## Impacto en arquitectura
`docs/architecture.md` (T090), sección Frontend: `public/js/filtros.js` (filtro plegable de la lista
de citas), y en `movil.css` las tablas en tarjetas (`tabla-tarjetas`) y el logo con máscara. Sin
ADR.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| La rejilla del detalle cambia el escritorio (huecos o anchos) | Márgenes en lugar de `row-gap`, fila flexible al final y `align-items: start`. `--medir` compara a 1024 y 1440 con la línea base: admin, asistente y dentista, y con historial largo |
| El reorden del detalle cambia el orden de tabulación y de lectura del escritorio (datos, cambiar estado, información y acciones, control interno, historial) | Es el orden de la spec y respeta los `id` y envoltorios de la 026 y la 028; `ReprogramarCitaVistasTest` sigue en verde (A14) |
| Safari puede perder la semántica de tabla al cambiar el `display` | Las etiquetas viajan en el texto de cada celda (`::before`). WebKit (Playwright) en la tarea del paciente; NVDA a mano; en el móvil real, comprobación en T097 |
| En el escritorio, el orden de tabulación del filtro cambia (los atajos antes que los campos) | Aceptado por el usuario (D6b); se anota en `system.md` |
| El borde de los campos del filtro en el escritorio sigue por debajo de 3:1 | Deuda DS2 sin cambio (escritorio igual, CA11). En el móvil, `--border-control`; el foco cumple en los dos |
| `mask` sin prefijo no existe en navegadores antiguos | `-webkit-mask` y `mask` juntos. Si no se admite, el bloque queda como un rectángulo `--p600`: se acepta, igual que el soporte mínimo de `inert` de la 033 |
| Las columnas ocultas siguen en el HTML del móvil | Es presentación: los mismos datos que el rol ya ve en el escritorio (spec, Seguridad) |
| Tests de la 033 que cambian | Cambian en una tarea de test propia, que falla antes del cambio (P2), y quedan anotados en «Notas» |
