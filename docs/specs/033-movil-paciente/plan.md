---
spec: 033-movil-paciente
status: draft
constitution_version: 1.2.1
created: 2026-10-11
---

# Plan · 033 Móvil para el paciente

## Enfoque técnico
Por debajo de 900 px de ancho, una hoja de estilos nueva, `public/css/movil.css`, convierte la
barra lateral existente en un menú desplegable (opción A, elegida por el usuario). Lo abre un botón
«Menú» en la barra superior y lo controla un script pequeño propio, `public/js/menu.js`, sin
dependencias. Como se reutiliza el mismo `<aside>`, cada rol ve en el móvil exactamente las mismas
secciones que en el escritorio. El resto del arreglo es CSS:
- el contenido pasa a ocupar todo el ancho, sin margen y con `min-width: 0` en `.main` y en los
  hijos de las rejillas (D2, A6);
- las rejillas pasan a una columna;
- los controles tienen al menos 44 px de toque;
- los textos largos parten de línea.

Fuera de la media query, `movil.css` solo oculta los elementos nuevos y pone el `min-width: 0`, que
a 1440 no cambia nada visible porque hoy nada se desborda. Así el escritorio no cambia (CA8). Los
pocos bloques de las vistas que hay que tocar pierden sus `style=""` por clases nuevas en `app.css`
que reproducen los mismos valores con tokens; los valores que no tenían token se añaden antes al
sistema (A1, decisión del usuario).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 033 aprobada el 2026-10-11 (`9c9ba12`) | `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene un test PHPUnit de estructura que falla hoy: no existen el botón «Menú», `movil.css` ni sus reglas. Además, lo que solo se ve en un navegador real (anchos, cajas de toque, foco al abrir, Escape, `inert`, el velo y el alto en horizontal) es **comportamiento** y lo verifica el modo `--medir` de `capturar.mjs` (D7). `--medir` falla hoy (T014) y se comprueba rompiendo en T023, la primera tarea en que puede pasar: sin el manejo de Escape sale con código 1, y una regla de toque quitada también lo hace fallar. Las caracterizaciones (viewport, secciones del menú, tipos de campo) se comprueban rompiendo (A15, A24) | test: `MovilPacienteTest` (fallo previo anotado) · test en navegador: `node capturar.mjs --medir` (fallo previo y rotura anotados) |
| P3 Autorización en servidor | ➖ | No cambian rutas, Gates ni datos; el menú del móvil es el mismo `<aside>` de cada rol | — |
| P4 Entrada validada y SQL con bindings | ➖ | No hay entradas nuevas ni consultas | — |
| P5 Nada sensible en el repo | ✅ | Las capturas y las medidas usan la base anonimizada `dentissa_capturas` (procedimiento de `capturar.mjs`); los textos largos de prueba son inventados | lint: gitleaks en T040 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas: `menu.js` es código propio y Playwright ya se usa en su contenedor (`capturar.mjs`). Línea base del 2026-10-11: `composer audit` y `npm audit` sin avisos | lint: composer audit, npm audit y trivy fs en T040 |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos (spec, Auditoría) | — |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos | — |
| P9 La interfaz usa solo el sistema de diseño | ✅ | Valores nuevos primero en `system.md` (T001): los ocho tokens de «Cambios a incorporar al sistema» y el punto de corte documentado. Toda regla nueva de `movil.css` y de `app.css` usa solo tokens, también las duraciones; `z-index` queda fuera de P9 (no es color, tipografía, espaciado, radio, sombra ni duración) y se documenta. Los bloques tocados pierden sus `style=""`. Los pares del menú cumplen AA, la sección actual no se indica solo con color y todos los controles del menú tienen foco visible (A2, A4, A5, A16) | test: `test_movil_css_solo_usa_tokens`, `test_las_reglas_nuevas_de_app_css_solo_usan_tokens`, `test_los_pares_del_menu_cumplen_aa`, `test_los_controles_del_menu_tienen_foco_visible`, `test_los_bloques_tocados_no_tienen_estilos_en_linea` · manual: pantallas a 320, 390 y 1440 contra `system.html` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`docs/design/system.md`, `system.html`) | Versión 1.4.0 con los tokens y el componente de «Cambios a incorporar al sistema»; `widths: [320, 390, 1440]`; estados de deuda: DS6 y DS8 «en curso (033, …)», DS10 «en curso (033, solo el menú del móvil)» (A26) | Bajo |
| Configuración (`.ai/project.yaml`) | `design.widths: [320, 390, 1440]` (spec, Decisiones: 320 px) | Bajo |
| Estilos existentes (`public/css/app.css`) | Los ocho tokens nuevos en `:root`. Al final, un bloque marcado `/* Spec 033 */` con las clases nuevas de los bloques tocados (ver «Bloques tocados»), solo con tokens y con los mismos valores que hoy. Ninguna regla existente cambia | Bajo |
| Estilos del móvil (`public/css/movil.css`, **nuevo**) | Contenido en «Hoja `movil.css`» | Medio: afecta a todas las pantallas por debajo de 900 px (el personal gana el menú y controles más grandes; su contenido sigue desbordándose, fuera de alcance) |
| Script del menú (`public/js/menu.js`, **nuevo**) | Contrato en «Script `menu.js`» | Medio: sin JS, el menú no debe quedar inaccesible (D5) |
| Layout (`resources/views/layouts/app.blade.php`) | Enlaza `movil.css` tras `app.css` y `menu.js` en `<head>`. Añade a la barra superior un `<button type="button" class="menu-btn" aria-expanded="false" aria-controls="menu-principal">`. El `<aside>` gana `id="menu-principal"`, `aria-label="Menú"`, el texto de marca `.sidebar-marca` («Melissa López N.», oculto en el escritorio, A9) y el botón `.menu-cerrar` (`aria-label="Cerrar menú"`) en la cabecera. El enlace de la sección actual gana `aria-current="page"` (A5). El velo `<div class="menu-velo" hidden>` va entre `</aside>` y `<main>`, fuera de `<main>` (A19). El bloque tocado pierde sus `style=""` (logo → `.sidebar-logo img`; tarjeta → `.user-card-texto`) y el `</div>` sobrante tras `.sidebar-logo` | Medio: el layout es común a los cuatro roles |
| Vistas del paciente (`citas/cliente-index`, `citas/cliente-show`, `citas/cliente-agendar`) | Bloques ya conocidos: las pestañas «Próximas / Pasadas» de `cliente-index` (→ `.pestanas-citas`, con `.activa` y `aria-current="page"`) y las migas de pan de `cliente-show` y `cliente-agendar` (→ `.migas`), cuyos enlaces no llegan a 44 px | Bajo |
| Vistas del paciente (`dashboard/cliente`, `citas/cliente-show` resto, `cliente/perfil`) | Solo si `--medir` las señala en T014 (desbordamiento a 320 o controles < 44 px a 390 que `movil.css` no corrige). La prueba de concepto (D2) indica que con `movil.css` no hace falta, porque sus controles son `.btn` o campos y la rejilla del detalle cabe | Bajo |
| `citas/partials/reprogramar.blade.php`, `citas/cliente-reprogramar.blade.php` | Sin cambios previstos: ya cumplen P9 (028); `movil.css` les da toque y ancho | Bajo |
| Tests (`tests/Feature/MovilPacienteTest.php` **nuevo**, `tests/Feature/Concerns/ReglasCss.php` **nuevo**, `tests/Feature/ReprogramarCitaVistasTest.php`) | `MovilPacienteTest` (ver Estrategia). `ReglasCss` es un trait con `reglasCss(string $archivo)`, que devuelve `[selector, cuerpo, condiciones]` (las condiciones `@media` que envuelven la regla, de fuera a dentro; `[]` si no hay), y con `declaraciones`, `literales` y el cálculo de contraste, sacados de `ReprogramarCitaVistasTest`. Este pasa a usarlo sin cambiar sus aserciones (A17) | Bajo: refactorización de tests, que deben seguir en verde |
| Capturas y medidas (`docs/design/capturar.mjs`) | Ancho 320 en las vistas tocadas; datos de prueba con textos largos en la cuenta del paciente (A18); modo `--medir` (D7) | Bajo |

## Contratos y datos
Sin endpoints, migraciones ni datos nuevos.

**Hoja `movil.css`.**
- **Fuera de toda media query**, solo dos reglas:
  - `.menu-btn, .menu-cerrar, .menu-velo, .sidebar-marca { display: none; }`, que oculta los
    elementos nuevos en el escritorio;
  - `.main, .grid-auto > *, .grid-2 > *, .grid-3 > * { min-width: 0; }`. Evita que un hijo flexible
    o de la rejilla ensanche la página de 900 a 1100 px (A6); a 1440 no cambia nada visible, porque
    hoy nada se desborda (lo comprueban `--medir` y las capturas).
- **Dentro de `@media (max-width: 899px)`:**
  - **Marco de la página:**
    - `.main { margin-left: 0; }`;
    - `.content` y `.topbar` con `padding` `--space-6`;
    - `.topbar` con `height: auto`, `min-height: var(--topbar-h)`, `flex-wrap: wrap` y
      `gap: var(--space-3)`;
    - `.grid-auto, .grid-2, .grid-3 { grid-template-columns: 1fr; }`;
    - `.content { overflow-wrap: break-word; }` (CA6).
  - **Panel del menú, con o sin JS:**
    - `.sidebar` con fondo `--white` y borde derecho `--border-width` `--border`, y
      `overflow-y: auto` en el propio `.sidebar` (A10);
    - `.nav-item` en `--text`; activo, `--p100` con `--p600` y la marca
      `box-shadow: inset var(--border-width-strong) 0 0 var(--p600)`, que no es solo color (A5);
    - `.nav-label` en `--p600`;
    - `.sidebar-logo img { display: none; }` y `.sidebar-marca` visible en `--p600`, porque el logo
      con `mix-blend-mode: screen` desaparece sobre blanco (A9);
    - `.user-name` con `white-space: normal` y `overflow-wrap: anywhere`;
    - `.btn-logout` con borde `--border-width` `--p200` y, en hover, `--p100` con `--p600` (A16).
  - **Sin JS** (`html:not(.menu-js)`, D5, A3): `body { flex-direction: column; }` y
    `.sidebar { position: static; width: auto; min-height: 0; }`. El menú queda en el flujo, encima
    del contenido, sin taparlo, y `.menu-btn` no se muestra.
  - **Con JS** (`html.menu-js`):
    - `.menu-btn` visible;
    - `.sidebar` fijo a la izquierda, de ancho `min(var(--menu-movil-w), 85vw)` y alto `100dvh`,
      con `z-index: 110`;
    - cerrado: `transform: translateX(-100%)` y `visibility: hidden`;
    - con `html.menu-abierto`: `transform: none`, `visibility: visible`, `.menu-velo` visible
      (`position: fixed; inset: 0; background: var(--overlay); z-index: 100`) y `body` con
      `overflow: hidden`;
    - el velo queda por encima de la barra superior (`z-index: 50`) y por debajo del panel (A19).
  - **Movimiento:** `@media (prefers-reduced-motion: no-preference) { html.menu-js .sidebar {
    transition: transform var(--duration-fast), visibility var(--duration-fast); } }` (DS10, A4).
  - **Toque (CA4):**
    - `min-height: var(--touch-min)` en `.btn`, `.btn-sm`, `.nav-item`, `.btn-logout`, `.menu-btn`,
      `.menu-cerrar`, `.field`, `select`, `textarea`,
      `input:not([type=checkbox]):not([type=radio]):not([type=hidden])`, `.pestanas-citas a` y
      `.migas a`;
    - `min-width: var(--touch-min)` en `.menu-cerrar`;
    - la casilla conserva su tamaño, y la zona de 44 px la da su etiqueta:
      `.campo-casilla { min-height: var(--touch-min); }` (A20).
  - **Foco:** `.menu-btn`, `.menu-cerrar`, `.nav-item` y `.btn-logout` con
    `:focus-visible { outline: var(--focus-ring-width) solid var(--focus-ring);
    outline-offset: var(--focus-ring-offset); }` (A16).
- **`z-index`:** los valores 100 y 110 quedan fuera de P9 (no son color, tipografía, espaciado,
  radio, sombra ni duración); se documentan en `system.md` junto al componente.

**Script `menu.js`** (cargado en `<head>`, sin `defer`):
- Al cargar, añade `menu-js` a `<html>` (D5).
- Con el DOM listo, enlaza `.menu-btn`, `.menu-cerrar` y `.menu-velo`.
- **Abrir:**
  - pone `html.menu-abierto` y `aria-expanded="true"` en el botón;
  - quita `hidden` al velo y pone `inert` en `<main>`;
  - lleva el foco al enlace con `aria-current="page"`, o al primero.
- **Cerrar** (botón «Cerrar menú», velo, Escape, o al dejar de ser móvil):
  - deshace lo anterior;
  - devuelve el foco a `.menu-btn`, salvo si se cerró por cambio de ancho.
- Para saber si está en modo móvil no repite el punto de corte: mira si `.menu-btn` se muestra
  (`getComputedStyle(...).display !== 'none'`) en el evento `resize` (A22).
- Ninguna dependencia, ningún `eval`, ningún HTML desde cadenas. Encaja con la CSP de la 031
  (`script-src 'self' 'unsafe-inline'`) y con una futura sin `'unsafe-inline'` (A23).

**Semántica del panel.** El `<aside>` conserva su papel en los dos modos y contiene el `<nav>`. No se
le da `role="dialog"`: en el móvil, el modo modal lo dan el `inert` de `<main>`, el velo y Escape, y
el estado se anuncia con `aria-expanded` del botón (A5).

**Bloques tocados y sus valores** (mismos valores que hoy, con tokens; A1):
| Bloque | Hoy (`style=""`) | Clase y tokens |
|---|---|---|
| Logo del menú (layout) | `width:100%; max-width:160px; object-fit:contain; display:block; margin:0 auto; mix-blend-mode:screen` | `.sidebar-logo img`: `max-width: var(--logo-max-w)` y el resto sin cambios (no son valores del sistema) |
| Texto de la tarjeta de usuario (layout) | `min-width:0` | `.user-card-texto { min-width: 0; }` |
| Pestañas de mis citas (`cliente-index`) | contenedor: `display:flex; gap:6px; margin-bottom:20px; border-bottom:1px solid var(--border)`; enlace: `padding:10px 4px; margin-right:20px` (solo el primero); `font-size:13.5px; font-weight:500`; sin subrayado; color `--p500` (activa) o `--text-muted`; `border-bottom:2px solid` `--p400` (activa) o `transparent` | `.pestanas-citas`: `gap: var(--space-2)`, `margin-bottom: var(--space-7)`, `border-bottom: var(--border-width) solid var(--border)`; `.pestanas-citas a`: `padding: var(--space-4) var(--space-1)`, `font-size: var(--text-body)`, `font-weight: var(--weight-strong)`, `color: var(--text-muted)`, `border-bottom: var(--border-width-strong) solid transparent`; `.pestanas-citas a:first-child { margin-right: var(--space-7); }`; `.pestanas-citas a.activa { color: var(--p500); border-bottom-color: var(--p400); }`, con `aria-current="page"` en la activa |
| Migas de pan (`cliente-show`, `cliente-agendar`) | contenedor: `display:flex; align-items:center; gap:8px; margin-bottom:20px; font-size:13px; color:var(--text-muted)`; enlace: `color:var(--text-muted); text-decoration:none`; actual: `color:var(--text)` | `.migas`: `gap: var(--space-3)`, `margin-bottom: var(--space-7)`, `font-size: var(--text-meta)`, `color: var(--text-muted)`; `.migas a { color: var(--text-muted); text-decoration: none; }`; `.migas-actual { color: var(--text); }` |

Si `--medir` señala otro bloque (T014), se añade a esta tabla con `/plan --fix` antes de tocarlo.

**Pantallas y estados** (componentes del sistema; ningún estado nuevo de carga o error):
| Pantalla | Componentes | A 320–899 px |
|---|---|---|
| Menú (todos los roles) | `.sidebar`, `.nav-item` (con `aria-current`), `.user-card`, `.btn-logout`, nuevos `.menu-btn`, `.menu-cerrar`, `.menu-velo`, `.sidebar-marca` | Opción A: botón «Menú» en la barra superior, panel blanco desde la izquierda; sin JS, el menú en el flujo encima del contenido |
| Panel del paciente | `.welcome-banner`, `.grid-auto`, `.card`, `.formulario-accesible .proxima-acciones`, `.table-wrap` | Una columna; la tabla del historial se desplaza dentro de su recuadro |
| Mis citas (próximas y pasadas) | `.pestanas-citas` (nueva), `.card-0`, `.table-wrap`, `.acciones-fila` | La tabla se desplaza dentro de su recuadro, la página no |
| Detalle de mi cita | `.migas` (nueva), `.card`, rejilla en línea de dos columnas (sin cambios), `.acciones-fila` | Cabe a 320 (D2) |
| Reprogramar mi cita | `.formulario-accesible` (028) | Sin cambios de marcado |
| Agendar cita | `.migas` (nueva), `.grid-2`, campos | Una columna; campos de 44 px |
| Mi perfil | `.grid-2` (×4), campos | Una columna; campos de 44 px |

**Cambios a incorporar al sistema** (los aplica la primera tarea; `system.md` 1.3.0 → 1.4.0):
- Tokens nuevos (A1, A4):
  - `--touch-min: 44px` (DS6);
  - `--overlay: rgba(0, 0, 0, 0.45)` (velo del menú);
  - `--menu-movil-w: 300px` (ancho del panel);
  - `--duration-fast: 0.15s` (la duración que ya usan las transiciones de `app.css`);
  - `--topbar-h: 60px` (alto de la barra superior);
  - `--border-width-strong: 2px` (indicador de pestaña y de sección actual);
  - `--text-meta: 13px` (migas de pan);
  - `--logo-max-w: 160px`.
- Punto de corte: móvil por debajo de 900 px. Es el único literal admitido, en la condición de la
  media query; `menu.js` no lo repite (A22).
- Componente «Menú del móvil» (opción A, [opcion-a.html](design/opcion-a.html)):
  - botón «Menú» con icono y texto;
  - panel blanco con la marca de texto, las secciones con la actual marcada (`aria-current` y una
    barra de 2 px), el nombre y «Cerrar sesión»;
  - «Cerrar menú» y velo;
  - foco, Escape e `inert`;
  - sin JS, el menú en el flujo;
  - `z-index` 100 (velo) y 110 (panel), fuera de P9;
  - navegadores: `inert` desde Safari 15.5 y Chrome 102; `100dvh` desde Safari 15.4 y Chrome 108.
- Componentes «Pestañas» (`.pestanas-citas`) y «Migas de pan» (`.migas`), con los valores de
  «Bloques tocados».
- `widths: [320, 390, 1440]`; DS6 «en curso (033, móvil del paciente)», DS8 «en curso (033, menú y
  pantallas del paciente)» y DS10 «en curso (033, solo el menú del móvil)».

## Estrategia de pruebas
- **PHPUnit, `MovilPacienteTest`** (Feature, `DatabaseTransactions`, escenario de un usuario por
  rol). Todos fallan hoy salvo los de caracterización, que se marcan y se comprueban rompiendo.
  - **Layout:**
    - para los cuatro roles, la barra superior tiene `.menu-btn` con nombre «Menú»,
      `aria-expanded="false"` y `aria-controls` que apunta al `<aside id="menu-principal">`;
    - el `<aside>` tiene `aria-label`, `.sidebar-marca` y `.menu-cerrar` con
      `aria-label="Cerrar menú"`;
    - `.menu-velo` con `hidden` está entre `</aside>` y `<main>`;
    - `movil.css` está enlazado después de `app.css`, y `menu.js` en `<head>`;
    - el enlace de la sección actual, y solo ese, lleva `aria-current="page"`, en una página de cada
      rol;
    - el `<aside>` no tiene `style=""`.
  - **Caracterización:**
    - `<meta name="viewport">` presente, en un test propio (A24);
    - por rol, la lista exacta de enlaces del menú (A7: la 020 la ampliará).
  - **`movil.css`:** las reglas de «Hoja `movil.css`», leídas con `reglasCss()` del trait y sus
    condiciones:
    - fuera de toda media query, solo las dos reglas previstas;
    - la transición, solo bajo `prefers-reduced-motion: no-preference`;
    - sin JS, `.sidebar` estático;
    - `overflow-y: auto` en `.sidebar`;
    - la lista de toque, sin casillas ni radios;
    - solo tokens en todas las declaraciones, duraciones incluidas, con control positivo (`px`, `s`,
      `ms`, colores, `font-weight`), salvo `0`, `1fr`, `none`, `auto`, `-100%`, `85vw`, `100dvh`,
      `transparent` y los `z-index` 100 y 110;
    - contraste ≥ 4.5:1 de los pares del menú (`.nav-item`, activo, `.nav-label`, `.sidebar-marca`,
      `.btn-logout` y su hover) y ≥ 3:1 del foco, con el trait.
  - **`app.css`:** las reglas del bloque `/* Spec 033 */` solo usan tokens, con control positivo, y
    los ocho tokens están en `:root` con los valores de `system.md` (A2, A21).
  - **`menu.js`:** existe; añade `menu-js`; no usa `innerHTML`, `outerHTML`, `insertAdjacentHTML`,
    `eval(` ni `new Function`; no contiene `max-width` (no repite el punto de corte). Es una
    comprobación de texto que asegura el contrato; el comportamiento lo comprueba `--medir`.
  - **Vistas del paciente:**
    - los bloques tocados, por su `id`, no tienen `style=""`;
    - las pestañas usan `.pestanas-citas`, con `.activa` y `aria-current` en la actual;
    - las migas usan `.migas`;
    - (caracterización) los campos `fecha` y `hora` de agendar y de reprogramar son `date` y
      `time`, y `telefono`, `email` y `fecha_nacimiento` del perfil son `tel`, `email` y `date`
      (A12).
- **Test en el navegador (`node capturar.mjs --medir`, D7)**, con la base de prueba y la cuenta de
  cada rol. Sale con código 1 y la lista de fallos.
  - **Anchos:** por cada vista del paciente, a 320, 390, 844 × 390, 900, 1024 y 1440 px,
    `scrollWidth` ≤ ancho y ninguna `.table-wrap` más ancha que su contenedor (CA1, CA6 y CA7, A6).
  - **Toque, a 390:** cajas ≥ 44 × 44 de los controles visibles de `.main` y del menú abierto (sin
    los enlaces dentro de un `<p>`; la casilla, por su etiqueta) (CA4).
  - **Formularios, a 320:** en agendar, el perfil y reprogramar, cada etiqueta queda a ≤ 16 px de su
    campo y solapada en horizontal con él, y cada mensaje de error, enviado vacío, cabe en el ancho y
    queda junto a su campo (CA5, A12).
  - **Menú de cada rol, a 320 y 390** (CA2, CA3, A11):
    - botón visible y panel oculto, que no se puede enfocar;
    - al pulsar el botón, `aria-expanded="true"`, foco en el enlace actual, `<main>` inerte y
      visibles todas las secciones, la actual marcada con `aria-current`, el nombre y «Cerrar
      sesión»;
    - Escape lo cierra y el foco vuelve al botón;
    - tocar el velo también lo cierra (A19).
  - **En horizontal, a 844 × 390:** con el menú abierto se alcanzan, desplazando el panel, el último
    enlace y «Cerrar sesión» (CA7, A10).
  - **Sin JS, a 390:** una pasada con JavaScript desactivado no se desborda, el menú no tapa
    contenido y no hay botón «Menú» (A3).
  - **A 1440:** el botón, el velo y la marca de texto ocultos, y la barra lateral visible (CA8).
- **Línea base de 1440** (A8): tras T014 y antes de cualquier cambio de estilos, capturas a 1440 de
  las 7 vistas y del menú de cada rol con los datos de prueba nuevos, en una carpeta temporal fuera
  del repositorio. T028 compara con ellas.
- **Capturas** a 320, 390 y 1440 de las vistas tocadas y del menú abierto, contra `system.html`
  (verificación manual) y, a 1440, contra la línea base (CA8).
- **Lector de pantalla** (verificación manual, CA3): con NVDA en Windows o TalkBack en el móvil del
  usuario, se anuncian «Menú, botón, contraído/expandido», «Cerrar menú» y la sección actual, y al
  cerrar se vuelve al botón.
- **Suite completa** y `pint --test` sobre los PHP tocados (solo tests).

## Modelo de amenazas
No aplica: la spec no toca datos sensibles, autenticación, permisos ni entradas externas, y el menú
es el mismo `<aside>` que ya ve cada rol. El único código nuevo que se ejecuta en el navegador,
`menu.js`, es propio, se sirve desde el mismo origen, no construye HTML desde cadenas y no lee
datos de la página. Encaja con la CSP de la 031.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: las 7 pantallas del paciente sin desplazamiento horizontal de 320 a 1440 | `movil.css` (marco, rejillas, `min-width: 0`), bloques tocados | `MovilPacienteTest::test_movil_css_adapta_el_marco_y_las_rejillas`, `test_los_bloques_tocados_no_tienen_estilos_en_linea` · test en navegador: `--medir` (anchos de 320 a 1440) · verificación manual: capturas a 320 y 390 contra `system.html` |
| CA2: menú sin espacio fijo, todo a ≤ 2 toques, sección actual, mismas secciones, todos los roles | layout, `movil.css`, `menu.js` | `test_cada_rol_tiene_el_boton_menu`, `test_la_seccion_actual_lleva_aria_current`, `test_el_menu_del_movil_tiene_las_mismas_secciones`, `test_el_menu_cerrado_no_ocupa_espacio`, `test_sin_js_el_menu_queda_en_el_flujo` · test en navegador: `--medir` (menú de cada rol a 320 y 390, sin JS) |
| CA3: teclado y lector de pantalla | layout (`aria-*`), `menu.js`, `movil.css` (foco) | `test_cada_rol_tiene_el_boton_menu`, `test_menu_js_cumple_su_contrato`, `test_los_controles_del_menu_tienen_foco_visible` · test en navegador: `--medir` (foco, Escape, velo, `inert`; rotura comprobada sin Escape) · verificación manual: NVDA o TalkBack |
| CA4: toque ≥ 44 × 44 a 390 | `movil.css` (toque), tokens, pestañas y migas | `test_los_controles_tienen_toque_minimo` · test en navegador: `--medir` (cajas a 390) |
| CA5: formularios de agendar, reprogramar y perfil a 320 | `movil.css`, migas de agendar | `test_movil_css_adapta_el_marco_y_las_rejillas`, `test_los_campos_conservan_su_tipo` (caracterización) · test en navegador: `--medir` (etiqueta junto al campo, errores) · verificación manual: teclado del móvil del usuario |
| CA6: textos largos | `movil.css` (`overflow-wrap`), datos de prueba de `capturar.mjs` | `test_los_textos_largos_parten_de_linea` · test en navegador: `--medir` con los textos largos a 320 |
| CA7: horizontal 844 × 390 | `movil.css` (`100dvh`, `overflow-y` del panel) | `test_el_menu_se_recorre_con_poca_altura` · test en navegador: `--medir` a 844 × 390 |
| CA8: escritorio a 1440 sin cambios | reglas dentro de la media query; fuera, solo ocultar y `min-width: 0`; bloques tocados con los mismos valores | `test_fuera_del_movil_solo_hay_las_reglas_previstas`, `test_las_reglas_nuevas_de_app_css_solo_usan_tokens` · test en navegador: `--medir` a 1440 · verificación manual: capturas a 1440 contra la línea base |
| RNF: WCAG 2.1 AA (1.4.10, 1.3.4, 2.4.7, 4.1.2, 1.4.1) | `movil.css`, layout, `menu.js` | `test_los_pares_del_menu_cumplen_aa`, `test_los_controles_del_menu_tienen_foco_visible`, `test_la_seccion_actual_lleva_aria_current` · test en navegador: `--medir` |
| RNF: toque ≥ 44 × 44 | tokens, `movil.css` | `test_los_controles_tienen_toque_minimo` · test en navegador: `--medir` |
| RNF: medido a 320, 390, 1440 y 844 × 390 | `capturar.mjs`, `.ai/project.yaml` | test en navegador: `--medir`; `test_los_tokens_y_anchos_coinciden_con_el_sistema` |
| P9: solo tokens | `movil.css`, `app.css` | `test_movil_css_solo_usa_tokens`, `test_las_reglas_nuevas_de_app_css_solo_usan_tokens` |

## Observabilidad
No aplica: no toca datos sensibles, autenticación ni permisos, y no añade logs, eventos ni métricas.
Tras el despliegue se comprueba a mano, desde un móvil real, que la dirección pública se ve sin
desplazamiento lateral.

## Rollout
- **Feature flag:** no. Solo cambia la presentación por debajo de 900 px, el escritorio no cambia
  (CA8) y el proyecto no tiene mecanismo de flags. Se revierte con la vuelta atrás de la 030.
- **Orden de despliegue:** `deploy/desplegar.sh <tag>`. No hay migraciones; los archivos nuevos van
  en la imagen `web` (estáticos) y en `app` (vista).
- **Compatibilidad:** sin cambios de datos. `movil.css` y `menu.js` llevan `?v=filemtime`, como
  `app.css`, para que una página en caché no mezcle versiones.
- **Rollback:** `bash deploy/volver.sh <tag anterior>`, sin copia de la base.
- **Orden respecto a otras specs:**
  - con la 031 y la 032 no comparte código; solo documentación de índices (`architecture.md`,
    `roadmap.md`, `specs/README.md`);
  - **la 033 va antes que la 020** (decisión del usuario, 2026-10-11, A7). La 020, cuando se
    reconstruya sobre `main`, añade «Auditoría» al menú de admin y actualiza la lista de
    `test_el_menu_del_movil_tiene_las_mismas_secciones`. Se anota en el roadmap;
  - la versión se fija en `/release` (v0.8.0 si la 031 sale antes como v0.7.0).
- **Métricas a vigilar tras el deploy:** errores 500 en `laravel.log` y 5xx en el log de nginx
  durante 15 minutos; desde un móvil real, la dirección pública con el paciente y con un rol del
  personal (menú).

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Navegación: opción A, menú desplegable** (decisión del usuario, 2026-10-11, tras ver
  [opcion-a.html](design/opcion-a.html) y [opcion-b.html](design/opcion-b.html)). Es el mismo
  patrón para los cuatro roles y cumple CA2 tal como está; la B ocupaba 64 px fijos y chocaba con
  CA2. Es un componente del sistema, no un rediseño (se mantiene la marca), así que no hacen falta
  `brief.md`, `creative-direction.md` ni `anti-cliches.md`.
- **D2 · Prueba de concepto (comprobado el 2026-10-11)**, en local con Playwright 1.63.0 y la cuenta
  de prueba del paciente, inyectando CSS sin tocar el repositorio:
  - con la barra lateral oculta, `.main` sin margen y las rejillas a una columna, mis citas
    (próximas), detalle, reprogramar, agendar y perfil quedan en 320 px;
  - el panel queda en 419 px (los hijos de `.grid-auto` no se encogen) y mis citas (pasadas) en
    481 px (la tabla ensancha su tarjeta, porque `.main` es hijo flexible de `body` sin
    `min-width: 0`);
  - hay de 1 a 12 controles < 44 px por pantalla, casi todos `.btn` o campos.

  De ahí, `min-width: 0` en `.main` y en los hijos de las rejillas, y la lista de toque.
- **D3 · Reutilizar el `<aside>` en lugar de un segundo menú.** Un único menú garantiza las mismas
  secciones (CA2) y no duplica el marcado de los cuatro roles.
- **D4 · Hoja aparte `movil.css`.** Un menú de solo CSS (casilla oculta o `:target`) no maneja
  `aria-expanded`, Escape ni el foco (CA3). `<details>` no cierra con Escape ni devuelve el foco.
  Por eso se usa un script propio. La hoja va aparte, en lugar de dentro de `app.css`, para que el
  test de «solo tokens» y el de CA8 la lean entera y no tengan que separar sus reglas de las
  anteriores (DS4).
- **D5 · Sin JS, el menú se ve en el flujo** (A3). El panel solo se oculta con `html.menu-js`, que
  pone el script al cargar en `<head>`, antes de pintar (sin parpadeo). Sin script, por debajo de
  900 px la barra lateral pasa a estática, encima del contenido, con los colores del panel blanco,
  y el botón «Menú» no se muestra.
- **D6 · Punto de corte 900 px.** A 844 × 390 (CA7) y en tabletas en vertical hay menú desplegable;
  desde 900 px, barra lateral, que deja 658 px de contenido. Las media queries no admiten variables,
  así que el valor se documenta en `system.md` y es el único literal admitido, solo en la
  condición. `menu.js` no lo repite: deduce el modo de si se muestra `.menu-btn` (A22).
- **D7 · `capturar.mjs --medir` como test en el navegador.** Comprueba en un navegador real el
  comportamiento que PHPUnit no puede ver (anchos, cajas, foco, Escape, `inert`, velo, sin JS):
  - se reutilizan el contenedor de Playwright y la base de prueba anonimizada que ya documenta
    `capturar.mjs`, sin dependencias nuevas ni Playwright en el CI;
  - sale con código 1 si algo falla y lo ejecutan `/implement` (fallo previo en T014, roturas
    comprobadas en T023 y T040) y `/review` (A15);
  - la cuenta del paciente con la que se mide (`cliente1`) pasa a llamarse «Maximiliana Guadalupe de
    los Ángeles Fernández-Villaseñor de la Concepción», con un correo largo sin espacios;
  - el servicio de nombre largo («Limpieza dental profunda con aplicación de flúor y sellado de
    fisuras») va en su próxima cita, en una pasada y en la que abre reprogramar, así que aparece en
    el menú, el panel, mis citas, el detalle y reprogramar;
  - todo es inventado (CA6, P5, A18).
- **D8 · Toque solo por debajo de 900 px.** CA4 se mide a 390 y CA8 pide que a 1440 no cambie nada
  más, así que `--touch-min` se aplica dentro de la media query. En el escritorio, DS6 sigue como
  deuda.
- **D9 · Menú blanco en el móvil.** El texto del menú rosa es blanco al 90 % (3,82:1) y
  `.nav-label` al 65 % (2,72:1), por debajo de AA (P9 en lo que se toca; `system.md`, DS1). En el
  móvil, el panel es blanco: `--p600` sobre `--white` da 7,00:1 y sobre `--p100`, 6,05:1; `--text`
  sobre `--white`, 21:1. En el escritorio no cambia (CA8) y su contraste queda como deuda (DS1)
  (A23).
- **D10 · Deuda de las vistas** (spec, Decisiones; 028 R21). Solo se tocan los bloques de «Bloques
  tocados» y los que añada `--medir` con `/plan --fix`, y se les quitan todos sus `style=""`. El
  resto de cada vista sigue con su deuda DS4.
- **D11 · Mismos valores con tokens nuevos** (decisión del usuario, 2026-10-11, A1). Cada valor que
  pierda su `style=""` pasa a un token que ya exista con ese valor exacto, o a uno nuevo que se añade
  antes al sistema. Nunca se aproxima al más cercano, porque CA8 no admite cambios a 1440.
- **D12 · El detalle de la cita no se toca.** Su rejilla de dos columnas en línea cabe a 320 (D2).
  Pasarla a `.grid-2` cambiaría el `gap` de 12 a 16 px a 1440 (A1). Si `--medir` la señala con los
  textos largos, se añade a «Bloques tocados» con `/plan --fix`.
- Ninguna decisión es → ADR: no hay dependencias nuevas ni cambios de arquitectura. El layout común
  ya existe.

## Impacto en arquitectura
- `docs/architecture.md`, en la sección de la interfaz: `public/css/movil.css` (adaptación por
  debajo de 900 px) y `public/js/menu.js` (menú del móvil, primer script propio en un archivo) junto
  a `app.css` y al layout único.
- `docs/design/system.md` (T001) y su HTML.

## Riesgos y mitigaciones
- **Cambio en todas las pantallas por debajo de 900 px:** `movil.css` afecta también al personal.
  Mitigación: solo reglas de marco, menú, toque y textos largos; `--medir` también abre el menú de
  los tres roles del personal; sus pantallas siguen desbordándose, como hasta ahora (fuera de
  alcance).
- **Escritorio:** una regla fuera de la media query cambiaría la vista a 1440. Mitigación:
  `test_fuera_del_movil_solo_hay_las_reglas_previstas`, `--medir` a 1440 y las capturas contra la
  línea base.
- **Navegadores del móvil:** `inert` (Safari 15.5+, Chrome 102+), `100dvh` (Safari 15.4+, Chrome
  108+) y `:focus-visible`. Sin `inert`, el foco podría salir del panel. Mitigación: el velo tapa la
  página y Escape y «Cerrar menú» están siempre disponibles; se anota en `system.md`.
- **Coste de la base de prueba:** montar `dentissa_capturas` lleva tiempo (procedimiento de
  `capturar.mjs`). Mitigación: se monta una vez en T014 y se borra en T040, como en la 028 (T032).
- **107 `style=""` en las vistas del paciente:** tocarlos todos agrandaría la spec. Mitigación: D10
  y D12; los que no estorban se quedan como DS4.
- **Conflicto con la 020:** comparte el layout. Mitigación: orden decidido (033 primero) y nota en
  el roadmap (A7).
- **CSP de la 031:** `script-src 'self' 'unsafe-inline'` admite `menu.js`; el script no usa `eval`
  ni manejadores en línea, así que también encajaría con una CSP sin `'unsafe-inline'`.
