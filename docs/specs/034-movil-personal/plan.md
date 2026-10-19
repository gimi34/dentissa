---
spec: 034-movil-personal
status: approved
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
| P1 Spec aprobada antes de código | ✅ | La spec 034 se aprueba de nuevo junto con este plan (correcciones de /analyze r1 y r2). Verificación: manual, `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | `MovilPersonalTest` (nuevo) y los cambios en `MovilPacienteTest` se escriben antes que el código y fallan hoy. Las comprobaciones nuevas de `--medir` (cambios de modo, palabras largas, solapes) se comprueban rompiendo. Verificación: test: tareas de test de `/tasks` (cada implementación `depende:` de su test) |
| P3 Autorización en el servidor | ➖ | No cambian rutas, controladores ni consultas: las vistas muestran los mismos datos con el mismo alcance que hoy, que no cambia (la lista de pacientes no se limita por rol: riesgo abierto que corrige la 014; spec, Seguridad). Ocultar columnas en el móvil es presentación |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas: el filtro envía los mismos parámetros. El contador de filtros es un número calculado en la vista a partir de esos parámetros, sin consultas nuevas |
| P5 Nada sensible en el repo | ✅ | Capturas y medidas sobre la base desechable `dentissa_capturas` (cabecera de `capturar.mjs`), borrada al terminar. Verificación: test: gitleaks en la verificación final |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-13: `npm audit` 0 vulnerabilidades y `composer audit` sin avisos (2026-10-12). Verificación: test: `composer audit`, `npm audit` y `trivy fs` en la verificación final |
| P7 Auditoría de accesos | ➖ | No toca autenticación, permisos ni datos sensibles nuevos. Sigue la excepción P7 vigente (030/031) |
| P8 Correlación y logs sin datos sensibles | ➖ | No añade logs |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | Reglas nuevas solo con tokens: ocho tokens nuevos, peso `--weight-strong`, sin `letter-spacing` (A1, A2). Los `style=""` que impiden la tarjeta o el móvil pasan a clases con tokens, incluidos los del filtro y del panel de la dentista (D10b, A3, A33, A35). El filtro tocado queda con etiquetas asociadas, foco ≥ 3:1 (A4) y borde de sus campos ≥ 3:1 en los dos modos (A36, decisión del usuario); «Filtros» con borde ≥ 3:1 en sus dos estados (A58). Sus textos previos por debajo de 4,5:1 (etiquetas, «Filtrar» y el atajo activo) siguen como deuda DS1 (decisión del usuario, A59, spec Decisiones). Foco visible con tokens en lo que la spec añade o cambia (filtro, «Filtros», botones de las tarjetas y del panel de la dentista, menú; A60). Contraste de los pares nuevos calculado desde los tokens. Verificación: test: `MovilPersonalTest` (literales con control positivo, contraste, `for`/`id`, sin los `style=""` imprescindibles); test en navegador: `--medir` (reflujo, toque, solapes, etiquetas, foco); manual: NVDA y capturas contra `opcion-a.html` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`docs/design/system.md`, `system.html`) | Versión 1.5.0 con «Cambios a incorporar al sistema». Las capturas de las pantallas incluidas se rehacen a 320, 390 y 1440 | Bajo |
| Estilos del móvil (`public/css/movil.css`) | Bloque «Spec 034» dentro de la media query: tarjetas (D1), estadísticas en 2 columnas (D10b), filtro (D6), panel de la dentista (D5b), detalle (D7), textos (D8), botón solo con icono (D9) y logo (D10). Fuera de la media query, solo lo de «Reglas fuera del móvil», siempre antes de ella (A46) | Medio: afecta a todas las listas por debajo de 900 px |
| Estilos existentes (`public/css/app.css`) | Los ocho tokens nuevos en `:root`. Comentario de cierre `/* Fin spec 033 */` (A26). Bloque `/* Spec 034 */` con las clases de los `style=""` imprescindibles (rejillas de estadísticas, filtro, rejillas del detalle, `btn-icono` y `timeline-acciones`) con los mismos valores, la rejilla de escritorio del detalle (D7) y el borde y el foco de los campos del filtro (D6) | Medio: el escritorio debe quedar igual (CA11) |
| Script del filtro (`public/js/filtros.js`, **nuevo**) | Contrato en «Script `filtros.js`». Se carga en `<head>` con `@push('styles')` solo en la lista de citas | Bajo |
| Script del menú (`public/js/menu.js`) | R21: `ultimoFoco` se olvida cuando el foco se pierde sin que el elemento se oculte | Bajo |
| Layout (`resources/views/layouts/app.blade.php`) | Botón «Menú» sin texto, con `aria-label="Menú"`. `.sidebar-marca` sin texto, con `role="img"` y `aria-label="Melissa López N."` | Bajo |
| Lista de citas del personal (`resources/views/citas/index.blade.php`) | Rejilla de estadísticas a `stats-grid`. Fondo de la cita de hoy a `.fila-hoy` (D1, A57). Filtro reordenado y accesible, sin sus `style=""` (D6, A33). Tabla con `tabla-tarjetas`, `data-label`, clases de celda y `fila-hoy`. «Ver detalle» con `aria-label` y texto en el móvil (D5) | Medio |
| Detalle de cita del personal (`resources/views/citas/show.blade.php`) | Orden del HTML: datos, cambiar estado, información y acciones, control interno e historial (D7), conservando sus `id` y envoltorios. Las rejillas pasan a clases y la cabecera de la tarjeta lleva `texto-partible` (D8) | Medio: el escritorio debe quedar igual |
| Pacientes (`resources/views/pacientes/index.blade.php`) | Estadísticas a `stats-grid stats-grid-3`. Tabla en tarjetas; doctor, ciudad, tipo de sangre y registrado con `solo-escritorio`, y el `div` del correo también; `texto-partible` en el nombre de la cabecera (D8). «Ver perfil» y «Editar» con nombre y texto en el móvil (D5) | Bajo |
| Servicios (`resources/views/servicios/index.blade.php`) | Estadísticas a `stats-grid stats-grid-2` (conserva su `max-width`). Tabla en tarjetas; descripción y categoría con `solo-escritorio`. «Editar» y «Eliminar» con nombre y texto en el móvil (D5) | Bajo |
| Paneles (`resources/views/dashboard/{admin,asistente,dentista,cliente}.blade.php`) | Admin y asistente: estadísticas a `stats-grid stats-grid-2` y tabla de citas de hoy en tarjetas. Dentista: estadísticas a `stats-grid stats-grid-3`, enlace de la agenda con `btn-icono` y `aria-label="Ver detalle"`, acciones de la línea de tiempo en `timeline-acciones` (D5b, sin tarjetas, spec CA2) y `texto-partible` en el nombre de «Próximo paciente» (D8). Paciente: tabla de citas pasadas en tarjetas y `texto-partible` en la próxima cita (D8) | Bajo |
| Mis citas y detalle del paciente (`resources/views/citas/cliente-index.blade.php`, `cliente-show.blade.php`) | Tabla en tarjetas (033/R20). En el detalle, `texto-partible` en la rejilla de datos y en la cabecera de la tarjeta (D8). La pantalla de reprogramar no cambia: su resumen parte desde `movil.css` (D8) | Bajo |
| Tests (`tests/Feature/MovilPersonalTest.php` **nuevo**, `tests/Feature/MovilPacienteTest.php`) | Ver Estrategia. En `MovilPacienteTest` cambian el nombre del botón (texto → `aria-label`), la marca, el contrato de `menu.js` (R21), los textos largos, las reglas fuera del móvil, su orden y el final de `bloque033()` (033/R14 en parte, A47) | Bajo: tests de la 033 que cambian por la 034 (extends) |
| Capturas y medidas (`docs/design/capturar.mjs`) | `--medir` añade las pantallas incluidas, solapes, etiquetas y su posición, fondo de las tarjetas, filtro, panel de la dentista, detalle, logo, botón solo con icono, región «Menú», cambios de modo (033 T064 y R21), palabras de 30 letras y la comparación con la línea base (D13) | Medio: es la red de seguridad de la spec |

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
    rejilla `minmax(0, 1fr) auto`;
  - **fondo detrás de las tarjetas (A39):** el `tbody` lleva `--bg` y relleno `--space-5`, así cada
    tarjeta `--white` se distingue de lo que tiene detrás aunque la tabla esté dentro de una
    `.card`, como en la opción A;
  - **colocación en la tarjeta (A37):** `.celda-cab` va en la fila 1 y la columna 1, y
    `.celda-estado` en la fila 1 y la columna 2, sea cual sea su posición en la fila (la 4.ª en el
    paciente, la 6.ª en el personal). El resto de celdas va debajo, en su orden y a lo ancho
    (`grid-column: 1 / -1`);
  - cada celda con etiqueta anula el relleno y el borde de `app.css` (`padding: var(--space-1) 0`,
    `border-bottom: 0`) y es una rejilla `var(--tarjeta-etiqueta-w) minmax(0, 1fr)`: el `::before`
    en la columna 1 y el valor en la 2;
  - **valor con varios elementos (A37):** si el contenido de una celda con etiqueta no es solo texto
    ni un único elemento (nombre y teléfono, hora y fecha), va dentro de un único
    `<div class="celda-valor">`, que queda entero en la columna 2. En el escritorio el envoltorio no
    cambia nada;
  - `.celda-cab`, `.celda-estado` y `.celda-acciones` no son rejilla y también anulan el relleno y
    el borde de `app.css`: `padding: var(--space-1) 0` y `border-bottom: 0`; `.celda-acciones`
    lleva además `margin-top: var(--space-3)`, `padding-top: var(--space-3)` y su borde superior
    `--border-width` `--border` (A65);
  - su `::before`: `content: attr(data-label)`, `--text-small`, `--weight-strong`, mayúsculas,
    `--p600`, sin `letter-spacing` (A1);
  - `.celda-cab` y `.celda-estado` sin `::before`; las celdas de valor con
    `overflow-wrap: break-word`;
  - **acciones (A18):** `.celda-acciones` ocupa las dos columnas y va sin etiqueta, como en la opción A
    (D4; sin `::before`). Sus envoltorios (`div`, `form`)
    son `flex` con `flex-wrap` y `gap: var(--space-3)`, y sus `.btn` llevan `flex: 1 1 auto`;
  - `.fila-hoy`: tarjeta `--white` con la barra izquierda `--border-width-strong` `--p400`, como en
    la opción A. Su fondo `--p50` vale lo mismo que `--bg` y la tarjeta no se distinguiría de su
    contenedor (A57), así que el `style="background:var(--p50)"` de la fila (`citas/index:127`) pasa a
    la clase: en el escritorio, `.fila-hoy` lleva `--p50` (bloque 034 de `app.css`, igual que hoy) y,
    en el móvil, `--white` (D10b);
  - en el móvil, las celdas no cambian de fondo con `:hover` (`tr:hover td` de `app.css` las pondría
    en `--p50`, el color del contenedor; A57);
  - **foco (A60):** los enlaces y botones de las tarjetas (`.tabla-tarjetas a:focus-visible`,
    `.tabla-tarjetas .btn:focus-visible`) llevan `--focus-ring`, `--focus-ring-width` y
    `--focus-ring-offset`, en el bloque 034 de `app.css` (solo cambia el foco, no el aspecto en
    reposo).
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

El panel de la dentista conserva su línea de tiempo (spec CA2) y se adapta en el móvil (D5b).

**D5 · Botones de icono con nombre y texto en el móvil.** Hoy los enlaces y botones de icono de las
tablas incluidas solo tienen `title` («Ver detalle», «Ver perfil», «Editar», «Eliminar»):
- ganan `aria-label` con ese texto;
- dentro llevan el mismo texto en `<span class="solo-movil">`, que fuera del móvil no se muestra
  (`display: none`), así que el escritorio se ve igual.

El enlace de la agenda de la dentista gana `aria-label="Ver detalle"` y la clase `btn-icono` (D5b),
sin texto visible.

**D5b · Panel de la dentista (spec CA2, CA7 y CA8; A35, decisión del usuario).** Sigue sin tarjetas
(sin `tabla-tarjetas` ni `data-label`):
- **Estadísticas:** `dashboard/dentista:28` pasa a `stats-grid stats-grid-3`, sin `style=""`
  (`.stats-grid` ya da el margen inferior de 22 px). En el móvil, dos columnas, como el resto.
- **Enlace «Ver detalle»:** su `style="padding:4px 8px"` pasa a `.btn-icono` (`padding:
  var(--space-1) var(--space-3)`, bloque 034 de `app.css`). En el móvil, `.btn-icono` lleva
  `min-width: var(--touch-min)` y centra el icono; el alto ya lo da la regla de toque de la 033. Su
  foco, con los tokens de foco (`.btn-icono:focus-visible`, bloque 034 de `app.css`; A60).
- **Línea de tiempo:** el `div` del estado y el enlace (`style="display:flex;align-items:center;
  gap:8px"`) pasa a `.timeline-acciones` (`display: flex`, `align-items: center`, `gap:
  var(--space-3)`). En el móvil, `.timeline-item` lleva `flex-wrap: wrap` y `.timeline-acciones`
  ocupa su propia línea bajo el nombre (`flex-basis: 100%`, `justify-content: flex-end`), así el
  nombre usa todo el ancho que dejan la hora y el punto. `.timeline-title` y `.timeline-sub` parten
  según D8.

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
  - el borde de los campos (`.filtro-citas .field`) es `--border-control` (≥ 3:1) en los dos modos
    (decisión del usuario, A36; spec CA11 y Decisiones). Es la única diferencia del escritorio con
    la línea base (D13).
- **Clase del formulario:** el `style=""` del `<form>` (flex con `flex-wrap`, `gap: 10px`, `align-items:
  flex-end`) pasa a `.filtro-citas` con `--space-4`.
- **El resto de `style=""` del filtro (A33, decisión del usuario)** pasa a clases del bloque 034 de
  `app.css`, con los mismos valores en el escritorio:
  - la tarjeta (`margin-bottom:20px;padding:16px 20px`) → `.filtro-tarjeta` (`--space-7`;
    `--space-6` y `--space-7`);
  - el buscador (`flex:1;min-width:180px`) → `.filtro-buscar` (`flex: 1`,
    `min-width: var(--filtro-buscar-min)`);
  - desde y hasta (`min-width:130px`) → `.filtro-fecha` (`--filtro-fecha-min`); dentista
    (`min-width:150px`) → `.filtro-dentista` (`--filtro-dentista-min`); estado (`min-width:140px`)
    → `.filtro-estado` (`--filtro-estado-min`);
  - los atajos (`margin-left:auto;display:flex;gap:6px`) → `.filtro-atajos` (`--space-2`).

  En el móvil, esos `min-width` pasan a 0 y `.filtro-tarjeta` usa el relleno `--space-5`.
- **Escritorio** (se ve igual, CA11):
  - `.filtro-panel` con `display: contents` y «Filtros» oculto;
  - la fila de atajos al final, como hoy: `.filtro-atajos` lleva `order: 1` (los demás hijos tienen
    0) además de su `margin-left: auto`, en el bloque 034 de `app.css` (A55);
  - el orden de tabulación pasa a ser buscador, atajos y campos: aceptado por el usuario (A6).
- **Botón (A45):** `<button type="button" class="btn btn-outline filtro-btn" aria-expanded="false"
  aria-controls="filtro-panel">Filtros</button>`.
  - plegado: el `btn-outline` del sistema (`--p600` sobre `--white`), con el borde `--border-control`
    en lugar de `--p200` (≥ 3:1, como «Menú» de la 033; A58);
  - desplegado (`.filtro-btn[aria-expanded="true"]`): fondo `--p100`, texto `--p600` y borde
    `--border-control`;
  - foco: `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`.
- **Contador:** con filtros aplicados se muestra `<span class="filtro-contador">n</span>` y el botón
  pasa a `aria-label="Filtros, 1 aplicado"` con uno y `aria-label="Filtros, n aplicados"` con más
  (A49). Cuentan desde (si no es hoy), hasta, dentista y estado.
- **Móvil (A19, A55):** el formulario es una columna, como en los marcos 2 y 3 de la opción A
  (`flex-direction: column` y `align-items: stretch` en `.filtro-citas`):
  - el buscador, a todo el ancho del formulario (`flex: none`, `min-width: 0`);
  - debajo, la fila de atajos con «Filtros» (`order: 0`, `margin-left: 0`, `flex-wrap: wrap`);
  - después, el panel, también desplegado.

  El panel desplegado es una rejilla de dos columnas: desde y hasta lado a lado; dentista y estado a
  lo ancho; «Filtrar» y «Limpiar» lado a lado. Los campos ocupan el ancho de su celda, sin
  `min-width` (A33).
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
  `detalle-datos-rejilla` (`repeat(3, 1fr)` en el escritorio y `minmax(0, 1fr)` en el móvil, A34), y
  su `gap` en línea se queda.
- **CA11:** se comprueba contra la línea base a 1024 y 1440 px, del admin (con control interno), del
  asistente y de la dentista (sin él), y con un historial largo.

**D8 · Textos (CA7).**
- `.content` vuelve a `overflow-wrap: break-word`, y desaparece la regla `.content table` (033 T069).
- `overflow-wrap: anywhere` solo en los elementos que muestran datos dentro de una fila o rejilla
  flexible, donde `break-word` no reduce el ancho mínimo (A34):
  - la rejilla del detalle del paciente y la tarjeta de la próxima cita del panel (033/R1):
    `texto-partible`, sin tocar su `style=""`;
  - la cabecera de la tarjeta del detalle de cita del personal (servicio y fecha, en una fila
    flexible con `space-between`): `texto-partible` en su `div` de texto;
  - el título del banner de los paneles, con el nombre del usuario (`.welcome-title`, dentro de un
    banner con `overflow: hidden`);
  - la línea de tiempo de la dentista (`.timeline-title`, `.timeline-sub`), además de D5b;
  - el nombre del usuario del menú (`.user-name`, 033);
  - la cabecera de la tarjeta del detalle de cita del paciente (`cliente-show`, servicio en una fila
    flexible con el estado): `texto-partible` en su `div` de texto (A56);
  - el resumen de la cita en la pantalla de reprogramar de la 033 (`.resumen-cita dd`, en una
    rejilla `max-content 1fr`): `anywhere` en `movil.css`, sin tocar la vista (A56);
  - «Próximo paciente» del panel de la dentista (avatar y nombre en una fila flexible):
    `texto-partible` en el `div` del nombre (A56);
  - la cabecera de la tarjeta de pacientes (avatar y nombre en una fila flexible): `texto-partible`
    en el `div` del nombre (A56).

  La lista sale de recorrer las filas y rejillas flexibles con datos de las pantallas incluidas y de
  las 7 del paciente; `--medir` falla si falta alguna.
- La rejilla de datos del detalle del personal pasa a una columna `minmax(0, 1fr)` (D7), y las
  celdas de las tarjetas parten bien con `break-word` porque su columna es `minmax(0, 1fr)`.
- **Cuándo «cabe» una palabra (A38):** si su ancho sin partir es menor o igual que el ancho que el
  diseño da a su texto: el ancho de contenido de la tarjeta o `.card` que la contiene (o de
  `.content`, si no hay), menos `--tarjeta-etiqueta-w` si está en una celda con etiqueta.
  - `--medir` falla si una palabra que cabe tiene un salto dentro (más de un rectángulo en
    `Range.getClientRects()`), y si una que no cabe ensancha la página o queda cortada;
  - así, una columna más estrecha que su diseño (la línea de tiempo a 320 px sin D5b) falla.
- El cambio de `.content` no va sin los elementos de esta lista: la tarea que lo hace pasa la parte
  de palabras largas de `--medir` (la página no se desplaza y no se corta ningún texto). Las tablas
  que aún no sean tarjetas pueden desplazarse dentro de su `.table-wrap`, pero la página no.
- `--medir` lo prueba con una palabra de 30 letras en el paciente, el dentista, el servicio y el
  nombre del usuario (banner y menú), en todas las pantallas incluidas y en las 7 del paciente
  (A11).

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

**D10b · `style=""` imprescindibles (decisión del usuario, A3; ampliado en A33 y A35).** Pasan a
clases con tokens solo los que impiden la tarjeta o el móvil:
- **Rejillas de estadísticas:**
  - `citas/index:24` → `stats-grid`;
  - `pacientes/index:17` → `stats-grid stats-grid-3`;
  - `servicios/index:16` → `stats-grid stats-grid-2`, conservando `style="max-width:600px"`;
  - `dashboard/admin:16` y `asistente:25` → `stats-grid stats-grid-2`;
  - `dashboard/dentista:28` → `stats-grid stats-grid-3` (D5b).

  `.stats-grid` ya existe en `app.css`. Las clases `-2` y `-3` del bloque 034 solo fijan
  `grid-template-columns`. En el móvil, `.stats-grid` pasa a dos columnas.
- **Filtro:** el `<form>`, la tarjeta, el buscador, los campos y los atajos (D6, A33).
- **Panel de la dentista:** el enlace de la agenda y las acciones de la línea de tiempo (D5b, A35).
- **Rejillas del detalle de cita** (D7).
- **Fondo de la cita de hoy** de la lista de citas del personal (`citas/index:127`) → `.fila-hoy` (D1,
  A57).

El resto de `style=""` de esas vistas se queda como está (DS4). Sus pares de color no se tocan: los
que no cumplen 4,5:1 (`--green` 3,39:1 en servicios y `--text-light` en etiquetas del detalle)
siguen como deuda DS1 (A5, decisión A3). Tampoco se tocan los textos del filtro que vienen de sus
clases (`.field-label` con `--text-light`, 3,51:1, y `btn-primary` de «Filtrar» y del atajo activo,
3,93:1): siguen como deuda DS1 (decisión del usuario, A59; spec Decisiones).

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

**D13 · Comparación con la línea base (A21, A40).**
- **Línea base:** se genera justo antes de cada comparación, en la misma sesión y contra la misma
  base de prueba sin cambiar sus datos, con el código anterior a la 034:
  - `git worktree add <carpeta temporal fuera del repositorio> v0.7.0`: el tag de producción, que
    ya incluye la 033 (el `main` local puede estar desfasado y la rama sale de un commit de cierre
    de la 033 sin código nuevo; A61);
  - se sirve con `docker run --rm` (no `docker compose run`, cuyo volumen `.:/var/www` taparía la
    copia) sobre la imagen `app`, con la copia en `/var/www`, el volumen `vendor_data` en
    `/var/www/vendor` (la 034 no cambia dependencias), la red de Compose, el `.env` copiado a la
    carpeta temporal y `DB_DATABASE=dentissa_capturas`, en otro puerto;
  - `OUT=<carpeta fuera del repositorio>` para las capturas;
  - después se captura la rama y se comparan.

  Así coinciden lo que depende de la fecha y la hora: citas de hoy, filtro desde hoy, plazo de 24 h
  de «Reprogramar» y «Cancelar» y fecha del panel. Si una cita cruza el plazo de 24 h entre las dos
  capturas, se repiten las dos. El paso queda en la cabecera de `capturar.mjs`, y al terminar se
  borran la copia (`git worktree remove`) y su `.env`.
- **Incluye:**
  - las pantallas incluidas del personal y las del paciente, a 1024 y 1440;
  - el detalle de cita de los tres roles del personal, también con historial largo.
- **«Igual»:** como mucho el 0,1 % de los píxeles distintos y ninguno con una diferencia de
  intensidad mayor que 2/255 (ruido de renderizado). La comparación excluye los rectángulos de los
  campos del filtro (`.filtro-citas .field`, con su borde), que cambian por decisión del usuario
  (A36, spec CA11).

**Diferencias aceptadas con `design/opcion-a.html` (A50).** La verificación manual no las toma por
fallos:
- marcado: `td` con `data-label` en lugar de `dl`/`dt` (D1);
- atajos en el orden de hoy, «Esta semana · Hoy» (A15);
- «Ver perfil» en pacientes, en lugar de «Ver» (D5);
- etiqueta con `--weight-strong` y sin `letter-spacing`, en lugar del peso 600 con espaciado (A1);
- textos centrados en servicios: su `style=""` se queda (D10b);
- borde de los campos del filtro con `--border-control` (A36);
- panel de la dentista con la línea de tiempo adaptada (D5b), que la opción A no dibuja.

**Reglas fuera del móvil (únicas permitidas, ampliando la lista de la 033):**
- en `movil.css`, **antes de la media query**, porque las reglas del móvil tienen la misma
  especificidad y ganan por orden (033 A40; T014 lo comprueba para cada selector, A46):
  - `.filtro-btn, .solo-movil { display: none; }`;
  - `.filtro-panel { display: contents; }`;
- en `app.css`:
  - el bloque 034 con las clases de los `style=""` imprescindibles (mismos valores que hoy, con
    tokens), incluidas las del filtro, `btn-icono` y `timeline-acciones`;
  - la rejilla de escritorio de `.detalle-cita` (D7);
  - el borde (`--border-control`) y el foco de los campos del filtro (D6, A36);
  - el estado desplegado, el borde y el foco de «Filtros» (D6, A45, A58);
  - el `order` de `.filtro-atajos` (D6, A55);
  - el fondo `--p50` de `.fila-hoy` (D1, A57);
  - el foco de los enlaces y botones de las tarjetas y de `.btn-icono` (D1, D5b, A60).

### Cambios a incorporar al sistema (system.md 1.4.0 → 1.5.0)
- **Tokens:**
  - `--tarjeta-etiqueta-w: 88px` (columna de la etiqueta en la tarjeta del móvil);
  - `--logo-movil-h: 40px` (alto del logo en el menú del móvil);
  - `--space-detalle: 18px` (hueco del detalle de cita, el valor de hoy);
  - `--detalle-lateral-w: 360px` (columna lateral del detalle de cita, el valor de hoy) (A2);
  - `--filtro-buscar-min: 180px`, `--filtro-fecha-min: 130px`, `--filtro-dentista-min: 150px` y
    `--filtro-estado-min: 140px` (anchos mínimos de hoy de los campos del filtro en el escritorio;
    en el móvil, 0) (A33).
  - `--text-marca` queda sin uso: se anota en el sistema y no se retira, porque lo comprueba el test
    de la 033.
- **Componentes nuevos:**
  - «Tarjeta de lista (móvil)»: tabla con `tabla-tarjetas`, clases de celda, etiqueta `--p600`
    sobre `--white` (7,0:1) y borde `--border` decorativo (spec);
  - «Filtro plegable»: botón `btn-outline` con borde `--border-control` y estado desplegado
    (`--p600` sobre `--p100`),
    contador `--white` sobre `--p500`, foco, y borde de los campos `--border-control` en los dos
    modos (A36, A45);
  - «Rejilla de estadísticas»: `stats-grid` con `-2` y `-3`;
  - «Botón de icono» (`btn-icono`): 44 × 44 en el móvil (D5b).
- **Componentes que cambian:** «Botón Menú» (solo icono), «Marca del menú del móvil» (logo con
  máscara) y «Línea de tiempo» (en el móvil, estado y enlace bajo el nombre, D5b).
- **Pantallas:** filas de las pantallas incluidas a 320, 390 y 1440 px. Notas de DS6 y DS8
  («parcial (033, 034)») y de DS2 (los campos del filtro de citas pasan a `--border-control`, 034).
- **Decisiones:** fila de la opción A (2026-10-13, usuario).

## Estrategia de pruebas
- **PHPUnit, `tests/Feature/MovilPersonalTest.php` (nuevo, con el trait `ReglasCss`):**
  - **Marcado de las tarjetas:** cada tabla incluida tiene `tabla-tarjetas`; cada `<td>` del cuerpo
    tiene un `data-label` igual a su `<th>` (salvo Teléfono) y las mismas clases de columna; las
    columnas de D2 llevan `solo-escritorio`, y las esenciales no; el `div` del correo lleva
    `solo-escritorio`, sin contar como columna (A16).
    - **Contenido de cada celda con etiqueta (A37):** solo texto o un único elemento hijo
      (`celda-valor` cuando hay varios).
    - **Pantallas:** las del paciente con su cuenta; el resto con las cuentas de admin, dentista y
      asistente de la base de tests, con citas y pacientes creados en el test.
  - **Cita de hoy:** la fila lleva `fila-hoy` y el texto «Hoy».
  - **Botones de icono:** «Ver detalle», «Ver perfil», «Editar» y «Eliminar» tienen `aria-label` y
    su texto en `.solo-movil`; el enlace de la agenda de la dentista, su `aria-label` y `btn-icono`.
  - **`style=""` imprescindibles:** las rejillas de estadísticas (también la de la dentista), el
    `<form>`, la tarjeta, el buscador, los campos y los atajos del filtro, el enlace y las acciones
    de la línea de tiempo de la dentista y las rejillas del detalle ya no tienen esas declaraciones
    (XPath, con control positivo) (A33, A35).
  - **Filtro:**
    - orden del formulario y de los atajos;
    - `for`/`id` de cada campo;
    - botón con `aria-expanded="false"` y `aria-controls`;
    - contador y `aria-label`, con un filtro aplicado («1 aplicado»), con dos y sin ninguno (A49);
    - `filtros.js` en `<head>` con `?v=`.
  - **Detalle:** orden de los bloques por rol (admin con control interno; asistente y dentista sin
    él); se conservan los `id` y `.formulario-accesible`.
  - **`movil.css`:**
    - reglas de D1 (con la colocación de cabecera y estado y el fondo del `tbody`, A37 y A39),
      D5b, D6–D10 y de las estadísticas dentro de la media query, con sus tokens;
    - `min-width: 0` de los campos del filtro en el móvil (A33), el formulario en columna con el
      buscador a lo ancho y los atajos con `order: 0` (A55);
    - `.fila-hoy` con `--white` y celdas sin cambio de fondo con `:hover` (A57);
    - `celda-cab`, `celda-estado` y `celda-acciones` con su relleno y su borde (A65);
    - `anywhere` en `.resumen-cita dd` (A56);
    - `.content` con `break-word`, y `anywhere` solo en los elementos de D8 (A34).
  - **`app.css`:** bloque 034 solo con tokens (literales con control positivo), rejilla de
    escritorio de D7, `.detalle-datos-rejilla` con `repeat(3, 1fr)` en el escritorio y
    `minmax(0, 1fr)` en el móvil (A54), borde y foco de los campos del filtro, estado desplegado,
    borde y foco de «Filtros» (A45, A58), `order: 1` de `.filtro-atajos` (A55), `.fila-hoy` con
    `--p50` (A57) y foco de las tarjetas y de `.btn-icono` (A60).
  - **Contraste:** etiqueta, contador, borde de los campos del filtro en los dos modos (A36),
    «Filtros» desplegado, borde de «Filtros» en los dos estados (A58), foco y logo, calculados desde
    los tokens.
  - **Tokens:** los ocho nuevos coinciden con `system.md`.
  - **Contrato de `filtros.js`** (texto, como el de `menu.js`): el comportamiento lo mide `--medir`.
- **PHPUnit, `tests/Feature/MovilPacienteTest.php` (033, cambia por la 034):**
  - el nombre del botón pasa a `aria-label="Menú"` sin texto;
  - la marca, a `role="img"` y `aria-label`;
  - las declaraciones de `.sidebar-marca`, a las de D10;
  - `test_los_textos_largos_parten_de_linea`, según D8;
  - el contrato de `menu.js` exige el `focusout` de R21;
  - la lista de reglas fuera del móvil (única comprobación de ese conjunto, A28) se amplía con las
    del plan, y la comprobación de orden (reglas globales antes de la media query) cubre todos sus
    selectores, no solo `.sidebar-marca` (A46);
  - `bloque033()` se corta en `/* Fin spec 033 */` (A26). Corrige solo una parte de 033/R14: que las
    comprobaciones fallen ante selectores repetidos que se pisan sigue en el roadmap, y lo anota
    `/release` (A47).
- **Navegador, `capturar.mjs --medir`** (obligatorio por `AGENTS.md`), contra la base de prueba.
  Pantallas incluidas de los cuatro roles y las 7 del paciente, a 320, 390, 844 × 390 y 1440 px, y
  sin JavaScript a 390:
  - sin desplazamiento horizontal;
  - **solapes:** ningún control visible se cruza con otro ni sale de su tarjeta;
  - toque ≥ 44 × 44 a 320, 390 y 844 × 390 (A44);
  - cada celda de valor visible de una tarjeta muestra su etiqueta a la izquierda, en la línea de la
    primera línea del valor, y el estado queda arriba a la derecha, en la línea de la cabecera;
    también con un valor de dos líneas (A37);
  - el fondo calculado de cada tarjeta es distinto del de su contenedor (A39);
  - **palabras de 30 letras:** temporales, en el nombre de un paciente, un dentista, un servicio y
    del usuario (banner y menú). Sin desplazamiento de la página ni texto cortado, y ninguna palabra
    que cabe (según D8) partida (A11, A34, A38);
  - **filtro:** plegado al cargar, «Filtros» lo despliega y cambia `aria-expanded`; sin JavaScript,
    desplegado; desplegado a 320, sin solapes ni campos fuera de su tarjeta (A33); en el móvil, el
    buscador al ancho del formulario y el orden vertical buscador < atajos < panel, también
    desplegado (A55); foco visible en sus campos y en «Filtros» (A45);
  - **foco en las tarjetas:** visible en un botón de una tarjeta y en el enlace de la dentista (A60);
  - **cita de hoy:** su tarjeta tiene un fondo distinto del de su contenedor (A57);
  - **panel de la dentista:** enlace ≥ 44 × 44, y estado y enlace bajo el nombre (A35);
  - **detalle:** una columna en el orden de D7;
  - **logo:** visible, de `--logo-max-w` de ancho y con fondo `--p600`;
  - **botón «Menú»:** sin texto visible, 44 × 44, nombre «Menú»; al abrir, el foco queda dentro de
    `#menu-principal`;
  - **cambios de modo** (los casos (a)–(d) de 033 T064 y R21): con el foco en «Cerrar menú», al pasar
    a 1000 px va al enlace actual; con el foco en un enlace del menú, al pasar a 390 va a «Menú»;
    tras hacer clic en una zona sin foco, el cambio de modo no mueve el foco (A10);
  - **escritorio:** a 1024 y 1440, igual que la línea base según D13, también las pantallas del
    paciente (CA11, A40).
  - Cada comprobación nueva se comprueba rompiendo.
- **WebKit (A25, A41):** Playwright WebKit abre «Mis citas» a 390 y comprueba que cada celda de una
  tarjeta muestra su etiqueta (el `content` del `::before`, con `getComputedStyle`) y su valor,
  visibles y sin solaparse. Tiene un control positivo: falla si se quita un `data-label`.
  - Detecta lo que cambia el motor de Safari al dibujar la tarjeta.
  - No lee el árbol de accesibilidad nativo: `ariaSnapshot()` y `getByRole` calculan roles y nombres
    con el código de Playwright, igual en todos los navegadores.
  - Si VoiceOver pierde la semántica de la tabla al cambiar su `display`, queda para T097: VoiceOver
    o TalkBack en el móvil real del usuario.
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
| CA2: tarjetas de citas del personal y del panel (admin, asistente); hoy no solo por color; dentista con su línea de tiempo | D1, D2, D5, D5b, D10b en `citas/index`, `dashboard/admin`, `dashboard/asistente`, `dashboard/dentista` | `MovilPersonalTest` (tarjetas, «Hoy», `fila-hoy`, botones con nombre, estadísticas, `style=""` de la dentista) · `--medir` (también el panel de la dentista: enlace y acciones bajo el nombre) |
| CA3: pacientes solo con lo esencial | D1, D2, D5, D10b en `pacientes/index` | `MovilPersonalTest` (`solo-escritorio`, correo) · `--medir` (ocultas a 390, visibles a 1440) |
| CA4: tarjeta de servicio | D1, D2, D5, D10b en `servicios/index` | `MovilPersonalTest` · `--medir` |
| CA5: filtro plegable con contador; sin JS, desplegado | D6, `filtros.js` | `MovilPersonalTest` (orden, `for`/`id`, botón, contador, CSS, contrato de `filtros.js`) · `--medir` (plegado, despliegue, `aria-expanded`, sin JS, foco) · verificación manual: NVDA («Filtros, botón, contraído») |
| CA6: detalle del personal en una columna | D7 | `MovilPersonalTest` (orden por rol, `id` conservados, rejillas con clase) · `--medir` (una columna, sin desplazamiento ni solapes) |
| CA7: textos sin partir letra a letra | D8 (con su referencia de «cabe»), D1 (`minmax(0, 1fr)`), D5b, D7 | `MovilPacienteTest` (`break-word`/`anywhere`) · `MovilPersonalTest` (elementos de D8, `.detalle-datos-rejilla`) · `--medir` con palabras de 30 letras en todas las pantallas incluidas y las 7 del paciente, incluido el nombre del usuario |
| CA8: sin desplazamiento, sin solapes, toque 44 | D1–D7, D5b, D10b | `--medir` (anchos, solapes y toque a 320, 390 y 844 × 390; filtro desplegado a 320) |
| CA9: botón solo con icono; R9 y R21 | D9, D11, `menu.js` | `MovilPacienteTest` (nombre del botón, contrato de `menu.js`) · `--medir` (44 × 44, nombre, foco en la región al abrir, cambios de modo y R21) · verificación manual: NVDA («Menú, botón, contraído», región «Menú») |
| CA10: logo con contraste ≥ 3:1 y nombre | D10 | `MovilPacienteTest`/`MovilPersonalTest` (marca, máscara, contraste desde tokens) · `--medir` (logo visible) · verificación manual: NVDA y captura del menú abierto |
| CA11: escritorio igual, salvo el borde de los campos del filtro | Reglas dentro de la media query; fuera, solo las previstas y antes de ella; D5–D7, D5b, D10b sin cambio visual; borde del filtro (D6, A36) | `MovilPacienteTest` (reglas fuera del móvil y su orden) · `MovilPersonalTest` (contraste del borde en los dos modos) · `--medir` a 1024/1440 frente a la línea base (D13, sin los campos del filtro) · verificación manual: capturas a 1440 |
| RNF: WCAG 2.1 AA (1.4.10, 1.3.1, 1.4.11, 2.4.7 en lo que la spec añade o cambia, 4.1.2, 1.4.1) | D1, D5, D5b, D6, D9, D10, D11 | Contraste, etiquetas, `for`/`id` y reglas de foco en `MovilPersonalTest` · `--medir` (foco visible en el filtro, «Filtros», las tarjetas, el enlace de la dentista y el menú) · WebKit · verificación manual: NVDA |
| RNF: toque ≥ 44 × 44 | D1, D5b, D6, D9 | `--medir` a 320, 390 y 844 × 390 |
| RNF: medido a 320, 390, 844 × 390 y 1440, también sin JS | `capturar.mjs` | `--medir` |
| RNF: escritorio sin cambios (salvo el borde del filtro) contra capturas de antes | D13 | `--medir` (línea base del mismo día a 1024 y 1440, personal y paciente) |

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
  - la 020 añade además en `citas.css` un foco global de `.field` con literales. Los campos del
    filtro tienen una sola regla de foco, la de la 034 (`.filtro-citas .field:focus-visible`, más
    específica y con tokens); la de la 020 queda para los demás campos. Tras fusionar la otra rama,
    T011 y T012 siguen en verde (A42);
  - la 014 (aprobada) cambia el filtro y el estado vacío de `pacientes/index`, que la 034 pasa a
    tarjetas. La 034 va antes, y la 014 se aplica sobre el marcado de tarjetas: toda columna o celda
    nueva lleva `data-label` y su clase, y `MovilPersonalTest` falla si no. Se avisa en
    `docs/roadmap.md` junto a la 014 (A42);
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
| D5b | Panel de la dentista: estadísticas y enlace a clases, y línea de tiempo con estado y enlace bajo el nombre en el móvil (decisión del usuario, A35) | Solo un ancho mínimo para el enlace; excepción a CA7 para el panel | Cumple CA7 y CA8 a 320 px sin convertirlo en tarjetas (decisión A8) |
| D6 | `filtros.js` aparte, cargado en `<head>` solo en la lista de citas | Ampliar `menu.js`; `<details>` sin JS; cargarlo al final del `<body>` | Contratos separados. `<details>` empezaría plegado sin JS (CA5). En `<head>` no hay salto de maquetación (A31) |
| D6b | Orden de tabulación del filtro en el escritorio: buscador, atajos y campos (decisión del usuario, A6) | Panel encima de los atajos | El escritorio se ve igual y los atajos son independientes de los campos |
| D6c | `style=""` del filtro a clases con cuatro tokens de ancho mínimo (decisión del usuario, A33) | Panel en una columna en el móvil; un solo ancho mínimo para todos los campos | Mantiene las dos columnas de la opción A y el escritorio exactamente igual |
| D6d | Borde de los campos del filtro con `--border-control` también en el escritorio (decisión del usuario, A36) | Escritorio igual, con contradicción registrada (deuda DS2) | Cumple P9 en el bloque tocado; es la única diferencia admitida en CA11 |
| D7 | Detalle: HTML en el orden de la spec y rejilla de escritorio con la columna derecha extendida, márgenes en lugar de `row-gap` | Reordenar con `order` y `display: contents` | El orden de foco y de lectura coincide con el visual en el móvil (WCAG 2.4.3); el escritorio se mantiene igual con cualquier rol (A9) |
| D8 | `anywhere` solo en los elementos de datos de filas y rejillas flexibles, con lista cerrada (033/R1, A34) | Mantenerlo en `.content`; volver a `anywhere` en `.content` y `break-word` solo en tablas (033 T069) | En el personal parte el texto letra a letra (visto en producción, v0.7.0). La lista sale de recorrer las pantallas incluidas y las 7 del paciente (A34, A56) y `--medir` falla si falta algún caso |
| D9 | Botón con `aria-label="Menú"` | Texto solo para lectores con una clase de ocultación (no existe y usaría literales en px) | El nombre accesible se mantiene sin reglas nuevas |
| D10 | Logo actual como máscara pintada con `--p600` | Filtro CSS (no da el color exacto y usaría números sueltos); recurso nuevo coloreado; panel rosa (DS1) | Color exacto del sistema sin redibujar el logo (decisión del usuario: derivarlo del actual). Comprobado en el navegador servido por http el 2026-10-13 (con `file://` la máscara no carga) |
| D10b | Solo pasan a clases los `style=""` que impiden la tarjeta o el móvil (decisión del usuario, A3) | Inventario completo con tokens nuevos | P9 prohíbe literales nuevos, no obliga a limpiar el código anterior; menos tokens y menos riesgo para el escritorio. La deuda de color (DS1) y de estilos en línea (DS4) sigue |
| D11 | R9 por la región con nombre «Menú» (sin código) y R21 en `menu.js` (decisión del usuario: R9 y R21 dentro) | `role="dialog"` en el `<aside>` (no permitido, A23); en un envoltorio con `display: contents` (fallos de Safari); botón alcanzable con el panel abierto (tapado) | El foco entra en la región con nombre al abrir y los lectores la anuncian |
| D12 | Sin ADR | — | Sin dependencias nuevas ni cambio de arquitectura: un script propio más, como `menu.js` |
| D13 | Comparación con una línea base generada justo antes, con el código de v0.7.0 (`git worktree` servido con `docker run`, A61), ≤ 0,1 % de píxeles distintos y diferencia ≤ 2/255, sin los campos del filtro | Comparación visual a mano; una línea base única creada en T015 | Repetible: la fecha, la hora y los datos coinciden en las dos capturas (A21, A40) |

## Impacto en arquitectura
`docs/architecture.md` (T090), sección Frontend: `public/js/filtros.js` (filtro plegable de la lista
de citas), y en `movil.css` las tablas en tarjetas (`tabla-tarjetas`) y el logo con máscara. Sin
ADR.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| La rejilla del detalle cambia el escritorio (huecos o anchos) | Márgenes en lugar de `row-gap`, fila flexible al final y `align-items: start`. `--medir` compara a 1024 y 1440 con la línea base: admin, asistente y dentista, y con historial largo |
| El reorden del detalle cambia el orden de tabulación y de lectura del escritorio (datos, cambiar estado, información y acciones, control interno, historial) | Es el orden de la spec y respeta los `id` y envoltorios de la 026 y la 028; `ReprogramarCitaVistasTest` sigue en verde (A14) |
| Safari puede perder la semántica de tabla al cambiar el `display` | Las etiquetas viajan en el texto de cada celda (`::before`). WebKit (Playwright) comprueba cómo se dibuja la tarjeta, no el árbol de accesibilidad nativo (A41); NVDA a mano; VoiceOver o TalkBack en el móvil real, en T097 |
| En el escritorio, el orden de tabulación del filtro cambia (los atajos antes que los campos) | Aceptado por el usuario (D6b); se anota en `system.md` |
| El borde de los campos del filtro cambia en el escritorio | Decisión del usuario (D6d, A36): es la única diferencia admitida. D13 excluye esos rectángulos y T012 comprueba ≥ 3:1 en los dos modos |
| Las tablas que aún no son tarjetas se ensanchan con palabras largas al quitar `anywhere` de `.content` | D8: la tarea que lo quita pasa la parte de palabras largas de `--medir`; las tablas se desplazan dentro de su `.table-wrap`, nunca la página (A34) |
| `mask` sin prefijo no existe en navegadores antiguos | `-webkit-mask` y `mask` juntos. Si no se admite, el bloque queda como un rectángulo `--p600`: se acepta, igual que el soporte mínimo de `inert` de la 033 |
| Las columnas ocultas siguen en el HTML del móvil | Es presentación: los mismos datos que el rol ya ve en el escritorio (spec, Seguridad) |
| Tests de la 033 que cambian | Cambian en una tarea de test propia, que falla antes del cambio (P2), y quedan anotados en «Notas» |
