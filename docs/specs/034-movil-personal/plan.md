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

Además:
- **Filtro de citas:** se reordena para dejar a la vista el buscador y la fila «Hoy · Esta semana ·
  Filtros». Un script propio y pequeño (`filtros.js`) pliega y despliega el resto. Sin JavaScript,
  todo queda visible.
- **Detalle de cita del personal:** se reordena el HTML al orden de la spec. En el escritorio, una
  rejilla con la columna derecha extendida sobre todas las filas lo deja como hoy.
- **Menú del móvil:** el botón muestra solo el icono, con `aria-label="Menú"`. La marca de texto
  pasa a ser el logo actual pintado con `--p600` mediante una máscara CSS. `menu.js` incorpora
  033/R9 (el panel abierto es un diálogo con nombre) y 033/R21.

## Constitution Check
| Principio | Resultado | Evidencia y cómo se verifica |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | La spec 034 se aprueba de nuevo junto con este plan (edición de CA9 por R9 y R21). Verificación: manual, `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | `MovilPersonalTest` (nuevo) y los cambios en `MovilPacienteTest` se escriben antes que el código y fallan hoy. Las comprobaciones nuevas de `--medir` se comprueban rompiendo. Verificación: test: tareas de test de `/tasks` (cada implementación `depende:` de su test) |
| P3 Autorización en el servidor | ➖ | No cambian rutas, controladores ni consultas: las vistas muestran los mismos datos con el mismo alcance (el asistente, solo sus dentistas). Ocultar columnas en el móvil es presentación |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas: el filtro envía los mismos parámetros. El contador de filtros es un número calculado en la vista a partir de esos parámetros, sin consultas nuevas |
| P5 Nada sensible en el repo | ✅ | Capturas y medidas sobre la base desechable `dentissa_capturas` (cabecera de `capturar.mjs`), borrada al terminar. Verificación: test: gitleaks en la verificación final |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-13: `npm audit` 0 vulnerabilidades y `composer audit` sin avisos (2026-10-12). Verificación: test: `composer audit`, `npm audit` y `trivy fs` en la verificación final |
| P7 Auditoría de accesos | ➖ | No toca autenticación, permisos ni datos sensibles nuevos. Sigue la excepción P7 vigente (030/031) |
| P8 Correlación y logs sin datos sensibles | ➖ | No añade logs |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | Reglas nuevas solo con tokens (tres tokens nuevos en «Cambios a incorporar al sistema»). Los bloques tocados pierden su `style=""` (decisión del usuario: solo lo que se toca, D10). Contraste de los pares nuevos calculado desde los tokens. Verificación: test: `MovilPersonalTest` (literales con control positivo, contraste, sin `style=""` en los bloques tocados); test en navegador: `--medir` (reflujo, toque, solapes, etiquetas); manual: NVDA y capturas contra `opcion-a.html` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`docs/design/system.md`, `system.html`) | Versión 1.5.0 con «Cambios a incorporar al sistema». Las capturas de las pantallas incluidas se rehacen a 320, 390 y 1440 | Bajo |
| Estilos del móvil (`public/css/movil.css`) | Bloque «Spec 034» dentro de la media query: tarjetas (D1), filtro (D6), detalle (D7), textos (D8), botón solo con icono (D9) y logo (D10). Fuera de la media query, solo lo de «Reglas fuera del móvil» | Medio: afecta a todas las listas por debajo de 900 px |
| Estilos existentes (`public/css/app.css`) | Los tres tokens nuevos en `:root`. Bloque `/* Spec 034 */` con las clases de los bloques tocados, con los mismos valores que su `style=""` de hoy, y la rejilla del detalle en el escritorio (D7) | Medio: el escritorio debe quedar igual (CA11) |
| Script del filtro (`public/js/filtros.js`, **nuevo**) | Contrato en «Script `filtros.js`». Se carga con `@push('scripts')` solo en la lista de citas | Bajo |
| Script del menú (`public/js/menu.js`) | R9: al abrir, el `<aside>` pasa a `role="dialog"` y `aria-modal="true"`, y al cerrar se quitan. R21: `ultimoFoco` se olvida cuando el foco se pierde sin que el elemento se oculte | Bajo |
| Layout (`resources/views/layouts/app.blade.php`) | Botón «Menú» sin texto, con `aria-label="Menú"`. `.sidebar-marca` sin texto, con `role="img"` y `aria-label="Melissa López N."` | Bajo |
| Lista de citas del personal (`resources/views/citas/index.blade.php`) | Filtro reordenado (D6). Tabla con `tabla-tarjetas`, `data-label` y clases de celda. La fila de hoy, con la clase `fila-hoy` en lugar de su `style`. «Ver detalle» con `aria-label` y texto visible solo en el móvil (D5) | Medio |
| Detalle de cita del personal (`resources/views/citas/show.blade.php`) | Orden del HTML: datos, cambiar estado, información y acciones, control interno e historial (D7). Las rejillas pasan de `style=""` a clases | Medio: el escritorio debe quedar igual |
| Pacientes (`resources/views/pacientes/index.blade.php`) | Tabla en tarjetas. Correo, doctor, ciudad, tipo de sangre y registrado con `solo-escritorio` | Bajo |
| Servicios (`resources/views/servicios/index.blade.php`) | Tabla en tarjetas. Descripción y categoría con `solo-escritorio` | Bajo |
| Paneles (`resources/views/dashboard/{admin,asistente,cliente}.blade.php`) | Tabla de citas en tarjetas. `dashboard/cliente`: la tarjeta de la próxima cita lleva `texto-partible` (D8). `dashboard/dentista` no tiene tabla: solo se toca si `--medir` lo señala | Bajo |
| Mis citas y detalle del paciente (`resources/views/citas/cliente-index.blade.php`, `cliente-show.blade.php`) | Tabla en tarjetas (033/R20). En el detalle, la rejilla de datos con clase y `texto-partible` (D8) | Bajo |
| Tests (`tests/Feature/MovilPersonalTest.php` **nuevo**, `tests/Feature/MovilPacienteTest.php`) | Ver Estrategia. En `MovilPacienteTest` cambian el nombre del botón (texto → `aria-label`), la marca, el contrato de `menu.js`, los textos largos y las reglas fuera del móvil | Bajo: tests de la 033 que cambian por la 034 (extends) |
| Capturas y medidas (`docs/design/capturar.mjs`) | `--medir` añade las pantallas incluidas del personal, los solapes, las etiquetas de las tarjetas, el filtro, el detalle, el logo y el botón solo con icono | Bajo |

## Contratos y datos
Sin endpoints, migraciones ni datos nuevos. Las vistas reciben las mismas variables.

**D1 · Tabla en tarjetas.**
- **Marcado:** `<table class="tabla-tarjetas">`. Cada `<td>` lleva `data-label="<etiqueta>"`, y su
  `<th>` y él llevan las mismas clases de columna.
- **Clases de celda:**
  - `celda-cab`: el dato principal (hora y fecha, o el nombre del paciente o del servicio); en el
    móvil va arriba y sin etiqueta;
  - `celda-estado`: el estado, arriba a la derecha y sin etiqueta;
  - `celda-acciones`: los botones, a lo ancho al pie, con el borde superior;
  - `solo-escritorio`: la columna no se muestra en el móvil.
- **En el móvil (`movil.css`):**
  - `thead` sale con `display: none` (los lectores reciben la etiqueta de cada celda; prueba de
    concepto del 2026-10-13);
  - `tbody` y `tr` en bloque; cada `tr` es una tarjeta (`--white`, borde `--border`, `--radius`,
    relleno `--space-5` y `--space-6`, separación `--space-5`) con una rejilla de dos columnas
    `1fr auto` (cabecera y estado arriba);
  - cada celda es una rejilla `var(--tarjeta-etiqueta-w) minmax(0, 1fr)` y su `::before` con
    `content: attr(data-label)` (`--text-small`, 600, mayúsculas, `--p600`);
  - las celdas de valor usan `overflow-wrap: break-word`;
  - `celda-acciones` usa `flex-wrap` y sus botones `flex: 1 1 auto`.
- Las etiquetas de cada tabla son las del texto de su `<th>`, salvo «Contacto» de pacientes, que en
  la tarjeta se llama «Teléfono».

**D2 · Qué muestra cada tarjeta** (spec y supuestos).

| Pantalla | Cabecera | Estado | Con etiqueta | Acciones | Solo en el escritorio |
|---|---|---|---|---|---|
| Mis citas (paciente) | Fecha / Hora | sí | Servicio, Dentista | Ver, Reprogramar, Cancelar (las que correspondan) | — |
| Panel del paciente (citas pasadas) | Fecha | sí | Servicio, Dentista | — | — |
| Citas del personal | Hora / Fecha (con «Hoy») | sí | Paciente, Dentista, Servicio, Duración | Ver detalle | — |
| Citas de hoy del panel (admin, asistente) | Hora | sí | Paciente, Dentista (o Doctor), Servicio | — | — |
| Pacientes | Paciente (nombre) | — | Teléfono, Alergias | Ver, Editar | correo, Doctor, Ciudad, Tipo sangre, Registrado |
| Servicios | Nombre | — | Duración, Estado | Editar, Eliminar | Descripción, Categoría |

**D5 · «Ver detalle» en la lista de citas del personal.** Hoy es un icono de ojo con `title`, sin
nombre accesible. Pasa a tener `aria-label="Ver detalle"` y, dentro, el texto «Ver detalle» en
`<span class="solo-movil">`. Fuera del móvil ese texto no se muestra (`display: none`), así que el
escritorio se ve igual y además gana nombre accesible.

**D6 · Filtro de citas.**
- **Orden del formulario:** buscador, fila de atajos («Hoy», «Esta semana» y el botón «Filtros») y,
  por último, `<div id="filtro-panel" class="filtro-panel">` con desde, hasta, dentista (salvo el
  dentista), estado, «Filtrar» y «Limpiar».
- **Escritorio** (se ve igual, CA11): `.filtro-panel` con `display: contents`, «Filtros» oculto y
  la fila de atajos al final con `order` y `margin-left: auto`, como hoy. El orden de tabulación
  pasa a ser buscador, atajos y campos: se acepta (Riesgos).
- **Botón:** `<button type="button" class="btn filtro-btn" aria-expanded="false"
  aria-controls="filtro-panel">Filtros</button>`.
- **Contador:** con filtros aplicados se muestra `<span class="filtro-contador">n</span>` y el botón
  pasa a `aria-label="Filtros, n aplicados"`. Cuentan desde (si no es hoy), hasta, dentista y
  estado.
- **Móvil:** sin JavaScript, el botón no se muestra y el panel queda desplegado. Con `filtros.js`,
  el formulario lleva `filtro-js` y empieza plegado (`plegado`). «Filtros» alterna el plegado y
  `aria-expanded`.

**Script `filtros.js`.** Sin dependencias ni HTML desde cadenas. Busca `.filtro-citas`; si no
existe, sale. Añade `filtro-js` y `plegado`. El clic en `.filtro-btn` alterna `plegado` y
`aria-expanded`; al desplegar, el foco se queda en el botón. No usa `max-width` (el modo lo decide
`movil.css`) ni `innerHTML`.

**D7 · Detalle de cita del personal.**
- **HTML:** `<div class="detalle-cita">` con, en orden, `detalle-datos` (tarjeta de la cita),
  `detalle-lateral` (cambiar estado, e información y acciones), `detalle-control` (control interno,
  solo admin) y `detalle-historial`. Desaparece el envoltorio de la columna izquierda.
- **Escritorio (`app.css`, bloque 034):** `.detalle-cita` es una rejilla `1fr 360px` con filas
  `auto auto auto 1fr`, `column-gap: var(--space-7)`, `row-gap: var(--space-detalle)` (18 px, el
  hueco de hoy) y `align-items: start`.
  `detalle-lateral` ocupa la columna 2 y las filas `1 / -1`; los demás bloques, la columna 1 en
  orden. La fila flexible del final absorbe la diferencia de alto entre columnas, así que no quedan
  huecos entre los bloques de la izquierda.
- **Móvil:** una columna en el orden del HTML. La rejilla interna de 3 columnas (paciente,
  dentista, duración) pasa a 1 columna.
- **CA11:** se comprueba contra capturas de antes a 1024 y 1440 px, del admin (con control interno)
  y del asistente (sin él).

**D8 · Textos (CA7).**
- `.content` vuelve a `overflow-wrap: break-word`, y desaparece la regla `.content table` (033 T069).
- `overflow-wrap: anywhere` solo en `.texto-partible` (los dos casos de 033/R1: la rejilla del
  detalle del paciente y la tarjeta de la próxima cita del panel) y en `.user-name`.
- Las celdas de las tarjetas parten bien con `break-word` porque su columna es `minmax(0, 1fr)`.

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

**Reglas fuera del móvil (únicas permitidas, ampliando la lista de la 033):**
- `.filtro-btn, .solo-movil { display: none; }`;
- `.filtro-panel { display: contents; }`;
- el bloque 034 de `app.css` con las clases de los bloques tocados (mismos valores que hoy);
- la rejilla de escritorio de `.detalle-cita` (D7).

### Cambios a incorporar al sistema (system.md 1.4.0 → 1.5.0)
- **Tokens:**
  - `--tarjeta-etiqueta-w: 88px` (columna de la etiqueta en la tarjeta del móvil);
  - `--logo-movil-h: 40px` (alto del logo en el menú del móvil);
  - `--space-detalle: 18px` (hueco del detalle de cita, el valor de hoy).
  - `--text-marca` queda sin uso: se anota en el sistema y no se retira, porque lo comprueba el test
    de la 033.
- **Componentes nuevos:**
  - «Tarjeta de lista (móvil)»: tabla con `tabla-tarjetas`, clases de celda y pares de contraste
    (etiqueta `--p600`/`--white` 7,0:1, borde `--border` decorativo);
  - «Filtro plegable»: botón, contador `--white`/`--p500` y estados.
- **Componentes que cambian:** «Botón Menú» (solo icono) y «Marca del menú del móvil» (logo con
  máscara).
- **Pantallas:** filas de las pantallas incluidas a 320, 390 y 1440 px. Notas de DS6 y DS8
  («parcial (033, 034)»).
- **Decisiones:** fila de la opción A (2026-10-13, usuario).

## Estrategia de pruebas
- **PHPUnit, `tests/Feature/MovilPersonalTest.php` (nuevo, con el trait `ReglasCss`):**
  - **Marcado de las tarjetas:** cada tabla incluida tiene `tabla-tarjetas`; cada `<td>` del cuerpo
    tiene un `data-label` igual a su `<th>` (salvo Teléfono) y las mismas clases de columna; las
    columnas de D2 llevan `solo-escritorio`, y las esenciales no.
    - **Pantallas:** las del paciente con su cuenta; el resto con las cuentas de admin, dentista y
      asistente de la base de tests, con citas y pacientes creados en el test.
  - **Bloques tocados sin `style=""`:** filas de las tablas, rejillas del detalle y filtro (XPath),
    con control positivo.
  - **Cita de hoy:** la fila lleva `fila-hoy` y el texto «Hoy».
  - **«Ver detalle»:** el enlace tiene `aria-label` y el texto en `.solo-movil`.
  - **Filtro:** orden del formulario; botón con `aria-expanded="false"` y `aria-controls`; contador
    y `aria-label` con dos filtros aplicados y sin ninguno.
  - **Detalle:** orden de los bloques por rol (admin con control interno, asistente sin él).
  - **`movil.css`:**
    - reglas de D1, D6–D10 dentro de la media query, con sus tokens;
    - fuera de ella, solo las previstas;
    - `.content` con `break-word`, y `anywhere` solo en `.texto-partible` y `.user-name`.
  - **`app.css`:** bloque 034 solo con tokens (literales con control positivo) y con los mismos
    valores que los `style=""` que sustituye.
  - **Contraste:** etiqueta, contador y logo, calculados desde los tokens.
  - **Tokens:** los nuevos coinciden con `system.md`.
  - **Contrato de `filtros.js`** (texto, como el de `menu.js`): el comportamiento lo mide `--medir`.
- **PHPUnit, `tests/Feature/MovilPacienteTest.php` (033, cambia por la 034):**
  - el nombre del botón pasa a `aria-label="Menú"` sin texto;
  - la marca, a `role="img"` y `aria-label`;
  - las declaraciones de `.sidebar-marca`, a las de D10;
  - `test_los_textos_largos_parten_de_linea`, según D8;
  - el contrato de `menu.js` exige `role`/`aria-modal` al abrir y quitarlos al cerrar (R9), y el
    `focusout` que olvida `ultimoFoco` (R21);
  - la lista de reglas fuera del móvil se amplía.
- **Navegador, `capturar.mjs --medir`** (obligatorio por `AGENTS.md`), contra la base de prueba.
  Pantallas incluidas de los cuatro roles a 320, 390, 844 × 390 y 1440 px, y sin JavaScript a 390:
  - sin desplazamiento horizontal;
  - **solapes:** ningún control visible se cruza con otro ni sale de su tarjeta;
  - toque ≥ 44 × 44 a 390;
  - cada celda visible de una tarjeta muestra su etiqueta;
  - **filtro:** plegado al cargar, «Filtros» lo despliega y cambia `aria-expanded`; sin JavaScript,
    desplegado;
  - **detalle:** una columna en el orden de D7;
  - **logo:** visible, de `--logo-max-w` de ancho y con fondo `--p600`;
  - **botón «Menú»:** sin texto visible, 44 × 44, nombre «Menú»; al abrir, el `<aside>` es un
    diálogo;
  - a 1024 y 1440 px, las pantallas incluidas del personal son iguales a la línea base de antes
    (CA11).
  - Cada comprobación nueva se comprueba rompiendo.
- **Verificación manual:**
  - capturas a 320, 390 y 1440 px contra `design/opcion-a.html` y `system.html`;
  - NVDA en Windows (usuario): una tarjeta se lee con etiqueta y valor; «Filtros, botón, contraído»
    (y «2 aplicados»); al abrir el menú, «Menú, diálogo»; el logo como «Melissa López N., gráfico».

## Modelo de amenazas
No aplica: la spec solo cambia la presentación de datos que cada rol ya puede ver, con el mismo
alcance. No añade entradas, acciones, permisos ni datos. `filtros.js` es propio, del mismo origen,
no construye HTML ni lee datos de la página, y encaja con la CSP de la 031. Las columnas ocultas en
el móvil siguen en el HTML, como hoy: no es un control de acceso.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: tarjetas del paciente con acciones visibles | D1, D2 en `cliente-index` y `dashboard/cliente` | `MovilPersonalTest` (marcado de tarjetas del paciente, sin `style=""`) · `--medir` (etiquetas, solapes, sin desplazamiento) · verificación manual: capturas a 320/390 contra `opcion-a.html` |
| CA2: tarjetas de citas del personal y del panel; hoy no solo por color | D1, D2, D5 en `citas/index`, `dashboard/admin`, `dashboard/asistente` | `MovilPersonalTest` (tarjetas, «Hoy», `fila-hoy`, «Ver detalle») · `--medir` |
| CA3: pacientes solo con lo esencial | D1, D2 en `pacientes/index` | `MovilPersonalTest` (`solo-escritorio` en las columnas no esenciales) · `--medir` (ocultas a 390, visibles a 1440) |
| CA4: tarjeta de servicio | D1, D2 en `servicios/index` | `MovilPersonalTest` · `--medir` |
| CA5: filtro plegable con contador; sin JS, desplegado | D6, `filtros.js` | `MovilPersonalTest` (orden, botón, contador, CSS, contrato de `filtros.js`) · `--medir` (plegado, despliegue, `aria-expanded`, sin JS) · verificación manual: NVDA («Filtros, botón, contraído») |
| CA6: detalle del personal en una columna | D7 | `MovilPersonalTest` (orden por rol, rejillas con clase) · `--medir` (una columna, sin desplazamiento ni solapes) |
| CA7: textos sin partir letra a letra | D8, D1 (`minmax(0, 1fr)`) | `MovilPersonalTest` y `MovilPacienteTest` (`break-word`/`anywhere`) · `--medir` con los textos largos de la base de prueba · navegador: palabra de 30 letras en el detalle y el panel del paciente (como 033 T061) |
| CA8: sin desplazamiento, sin solapes, toque 44 | D1–D7 | `--medir` (anchos, solapes y toque a 320, 390 y 844 × 390) |
| CA9: botón solo con icono; R9 y R21 | D9, `menu.js` | `MovilPacienteTest` (nombre del botón, contrato de `menu.js`) · `--medir` (44 × 44, nombre, diálogo al abrir, casos de 033 T064) · verificación manual: NVDA («Menú, botón, contraído», «Menú, diálogo») |
| CA10: logo con contraste ≥ 3:1 y nombre | D10 | `MovilPacienteTest`/`MovilPersonalTest` (marca, máscara, contraste desde tokens) · `--medir` (logo visible) · verificación manual: NVDA y captura del menú abierto |
| CA11: escritorio igual | Reglas dentro de la media query; fuera, solo las previstas; D5–D7 sin cambio visual | `MovilPersonalTest` (reglas fuera del móvil) · `--medir` a 1024/1440 frente a la línea base · verificación manual: capturas a 1440 |
| RNF: WCAG 2.1 AA (1.4.10, 1.3.1, 1.4.11, 2.4.7, 4.1.2, 1.4.1) | D1, D5, D6, D9, D10 | Contraste y etiquetas en `MovilPersonalTest` · `--medir` · verificación manual: NVDA |
| RNF: toque ≥ 44 × 44 | D1, D6, D9 | `--medir` a 390 |
| RNF: medido a 320, 390, 844 × 390 y 1440, también sin JS | `capturar.mjs` | `--medir` |
| RNF: escritorio sin cambios contra capturas de antes | — | `--medir` (línea base a 1024 y 1440) |

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
  - la 020, al reconstruirse, añade «Auditoría» al menú con el patrón de la 033;
  - la versión se fija en `/release` (v0.8.0 si sale antes que la 031; si no, v0.9.0).
- **Métricas a vigilar tras el deploy:** errores 500 en `laravel.log` y 5xx en nginx durante
  15 minutos. Desde el móvil real: mis citas del paciente, citas, detalle, pacientes y servicios del
  personal, el filtro y el menú.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Decisión | Alternativas | Motivo |
|---|---|---|---|
| D1 | Tablas en tarjetas solo con CSS y `data-label` | Marcado aparte para el móvil (lista duplicada); etiquetas en CSS por posición (`nth-child`) | Un solo marcado y un solo origen de datos. La prueba de concepto del 2026-10-13 confirma etiquetas y celdas ocultas en el árbol de accesibilidad de Chromium |
| D3 | Ocultar `thead` con `display: none` en el móvil | Ocultarlo solo visualmente (`clip-path`, con literales en px) | Cada celda ya lleva su etiqueta; evita literales (P9) |
| D4 | Opción A completa, tarjeta con etiquetas y filtro en la página (decisión del usuario, 2026-10-13) | Opción B (cabecera y hoja inferior); mezclas | Menos piezas: sin velo ni manejo del foco en el filtro, y los botones bajan de línea a 320 px |
| D6 | `filtros.js` aparte, cargado solo en la lista de citas | Ampliar `menu.js`; `<details>` sin JS | Contratos separados. `<details>` empezaría plegado sin JS, en contra de CA5 |
| D7 | Detalle: HTML en el orden de la spec y rejilla de escritorio con la columna derecha extendida | Reordenar con `order` y `display: contents` | El orden de foco y de lectura coincide con el visual en el móvil (WCAG 2.4.3); el escritorio se mantiene igual |
| D8 | `anywhere` solo donde hace falta (033/R1) | Mantenerlo en `.content` | En el personal parte el texto letra a letra (visto en producción, v0.7.0) |
| D9 | Botón con `aria-label="Menú"` | Texto solo para lectores con una clase de ocultación (no existe y usaría literales en px) | El nombre accesible se mantiene sin reglas nuevas |
| D10 | Logo actual como máscara pintada con `--p600` | Filtro CSS (no da el color exacto y usaría números sueltos); recurso nuevo coloreado; panel rosa (DS1) | Color exacto del sistema sin redibujar el logo (decisión del usuario: derivarlo del actual). Comprobado en el navegador servido por http el 2026-10-13 (con `file://` la máscara no carga) |
| D10b | Limpiar solo los bloques que se tocan (decisión del usuario, 2026-10-13) | Las 9 vistas enteras | Como en la 033; el resto sigue en DS4 |
| D11 | R9 y R21 dentro (decisión del usuario, 2026-10-13). R9: el panel abierto es un diálogo con nombre en lugar de dejar el botón alcanzable | Botón alcanzable con el panel abierto | El panel tapa el botón: el foco iría a algo oculto |
| D12 | Sin ADR | — | Sin dependencias nuevas ni cambio de arquitectura: un script propio más, como `menu.js` |

## Impacto en arquitectura
`docs/architecture.md` (T090), sección Frontend: `public/js/filtros.js` (filtro plegable de la lista
de citas), y en `movil.css` las tablas en tarjetas (`tabla-tarjetas`) y el logo con máscara. Sin
ADR.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| La rejilla del detalle cambia el escritorio (huecos o anchos) | Fila flexible al final y `align-items: start`. `--medir` compara a 1024 y 1440 con la línea base, del admin y del asistente |
| Safari puede perder la semántica de tabla al cambiar el `display` | Las etiquetas viajan en el texto de cada celda (`::before`), así que el lector sigue teniendo etiqueta y valor. Verificación manual con NVDA; en el móvil real, comprobación en T097 |
| En el escritorio, el orden de tabulación del filtro cambia (los atajos antes que los campos) | Los atajos son independientes de los campos y el orden visual no cambia; se anota en `system.md` |
| `mask` sin prefijo no existe en navegadores antiguos | `-webkit-mask` y `mask` juntos. Si no se admite, el bloque queda como un rectángulo `--p600`: se acepta, igual que el soporte mínimo de `inert` de la 033 |
| Las columnas ocultas siguen en el HTML del móvil | Es presentación: los mismos datos que el rol ya ve en el escritorio (spec, Seguridad) |
| Tests de la 033 que cambian | Cambian en tareas de test propias, que fallan antes del cambio (P2), y quedan anotados en «Notas» |
