---
spec: 033-movil-paciente
status: approved
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
- el contenido pasa a ocupar todo el ancho (`.main` sin margen y con `min-width: 0`);
- las rejillas pasan a una columna;
- los controles tienen al menos 44 px de toque;
- los textos largos parten de línea.

La prueba de concepto (D2) mostró que con esto 5 de las 7 pantallas del paciente ya caben a 320 px y
que las otras dos solo necesitan `min-width: 0`. Todas las reglas nuevas viven dentro de la media
query, y fuera solo hay reglas que ocultan elementos nuevos, así que el escritorio no cambia (CA8).
En los bloques de las vistas del paciente que haya que tocar, los `style=""` pasan a clases con
tokens (P9).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 033 aprobada el 2026-10-11 (`9c9ba12`) | `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA tiene un test PHPUnit de estructura que falla hoy: no existen el botón «Menú», `movil.css` ni sus reglas. Lo que solo se ve en un navegador (anchos medidos, toque, foco, Escape, horizontal) se comprueba además con el modo `--medir` de `capturar.mjs` (D7), que sale con código 1 si algo falla. El resultado se anota en la tarea, como comprobación declarada (P2, 1.2.1). | test: `MovilPacienteTest` (fallo previo anotado) · comprobación: `node capturar.mjs --medir` |
| P3 Autorización en servidor | ➖ | No cambian rutas, Gates ni datos; el menú del móvil es el mismo `<aside>` de cada rol | — |
| P4 Entrada validada y SQL con bindings | ➖ | No hay entradas nuevas ni consultas | — |
| P5 Nada sensible en el repo | ✅ | Las capturas y las medidas usan la base anonimizada `dentissa_capturas` (procedimiento de `capturar.mjs`); el texto largo de prueba es inventado | lint: gitleaks en T040 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas: `menu.js` es código propio y Playwright ya se usa en su contenedor (`capturar.mjs`). Línea base del 2026-10-11: `composer audit` y `npm audit` sin avisos | lint: composer audit, npm audit y trivy fs en T040 |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos (spec, Auditoría) | — |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos | — |
| P9 La interfaz usa solo el sistema de diseño | ✅ | Valores nuevos primero en `system.md` (T001): `--touch-min`, `--overlay`, `--menu-movil-w` y el punto de corte documentado. Las reglas de `movil.css` solo usan tokens; en los bloques tocados de las vistas, `style=""` → clases; los pares del menú cumplen AA (fondo blanco con `--p600`, 7:1) | test: `test_movil_css_solo_usa_tokens`, `test_los_pares_del_menu_cumplen_aa`, `test_los_bloques_tocados_no_tienen_estilos_en_linea` · manual: pantallas a 320, 390 y 1440 contra `system.html` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`docs/design/system.md`, `system.html`) | Versión 1.4.0. Tokens nuevos `--touch-min: 44px`, `--overlay: rgba(0, 0, 0, 0.45)` y `--menu-movil-w: 300px`. Punto de corte «móvil < 900 px», documentado (las media queries no admiten variables). Componente «Menú del móvil» (botón, panel, velo, cerrar) con la opción A. DS8 «en curso (033)» y DS6 «en parte (033)». Pantallas del paciente y menú a 320 px en la tabla de pantallas | Bajo |
| Configuración (`.ai/project.yaml`) | `design.widths: [320, 390, 1440]` (spec, Decisiones: 320 px) | Bajo |
| Tokens (`public/css/app.css`) | Solo los tres tokens nuevos en `:root`; ninguna regla existente cambia | Bajo |
| Estilos del móvil (`public/css/movil.css`, **nuevo**) | Contenido en el punto «Hoja `movil.css`» de Contratos | Medio: afecta a todas las pantallas por debajo de 900 px (el personal gana el menú y controles más grandes; su contenido sigue desbordándose, fuera de alcance) |
| Script del menú (`public/js/menu.js`, **nuevo**) | Contrato en «Script `menu.js`» | Medio: sin JS, el menú no debe quedar inaccesible (D5) |
| Layout (`resources/views/layouts/app.blade.php`) | Enlaza `movil.css` tras `app.css` y `menu.js` en `<head>`. En la barra superior, `<button type="button" class="menu-btn" aria-expanded="false" aria-controls="menu-principal">` con icono y el texto «Menú». En el `<aside>`: `id="menu-principal"`, `aria-label="Menú"` y un botón «Cerrar menú» (`aria-label`) en la cabecera. Añade un velo `<div class="menu-velo" hidden>`. El bloque tocado pierde sus `style=""` (logo y `min-width:0` de la tarjeta de usuario → clases) y el `</div>` sobrante tras `.sidebar-logo` | Medio: el layout es común a los cuatro roles |
| Vistas del paciente (`dashboard/cliente`, `citas/cliente-index`, `citas/cliente-show`, `citas/cliente-agendar`, `cliente/perfil`) | Solo los bloques que el modo `--medir` señale por desbordar o por controles < 44 px a 390 px. Ya se conocen: las pestañas «Próximas / Pasadas» de `cliente-index` (`style=` → `.pestanas-citas`) y la rejilla en línea `repeat(2,1fr)` de `cliente-show` (→ `.grid-2`). Cada bloque tocado, sin `style=""` (P9) | Medio: son 107 `style=""`, pero solo se tocan los bloques afectados (spec, Decisiones) |
| `citas/partials/reprogramar.blade.php`, `citas/cliente-reprogramar.blade.php` | Sin cambios previstos: ya cumplen P9 (028); `movil.css` les da toque y ancho | Bajo |
| Tests (`tests/Feature/MovilPacienteTest.php` **nuevo**, `tests/Feature/Concerns/ReglasCss.php` **nuevo**, `tests/Feature/ReprogramarCitaVistasTest.php`) | `MovilPacienteTest` (ver Estrategia). `ReglasCss`, un trait con `reglasCss($archivo)`, `declaraciones`, `literales` y el cálculo de contraste, sacados de `ReprogramarCitaVistasTest`, que pasa a usarlo sin cambiar sus aserciones | Bajo: refactorización de tests, que deben seguir en verde |
| Capturas y medidas (`docs/design/capturar.mjs`) | Ancho 320 en las vistas tocadas; datos de prueba con textos largos (CA6); modo `--medir` (D7) | Bajo |

## Contratos y datos
Sin endpoints, migraciones ni datos nuevos.

**Hoja `movil.css`** (todo dentro de `@media (max-width: 899px)` salvo lo indicado):
- **Fuera de la media query**, solo reglas que ocultan los elementos nuevos en el escritorio:
  `.menu-btn, .menu-cerrar, .menu-velo { display: none; }`.
- **Marco de la página:**
  - `.main { margin-left: 0; min-width: 0; }`;
  - `.content` y `.topbar` con `padding` `--space-6`;
  - la `.topbar` admite salto de línea (`flex-wrap`, `min-height` en lugar de `height`), para que
    sus acciones bajen de línea en lugar de desbordar;
  - `.grid-auto, .grid-2, .grid-3 { grid-template-columns: 1fr; }` y sus hijos con `min-width: 0`;
  - `.content { overflow-wrap: break-word; }` (CA6).
- **Menú:**
  - con `html.menu-js` (D5): `.sidebar` fijo a la izquierda, de ancho
    `min(var(--menu-movil-w), 85vw)` y alto `100dvh`, con fondo `--white` y borde `--border`;
    `.nav-item` en `--text` y, activo, `--p100` con `--p600`; `.nav-label` en `--p600`;
  - cerrado: `transform: translateX(-100%)` y `visibility: hidden`, para que no se pueda enfocar ni
    lo anuncie el lector;
  - con `html.menu-abierto`: visible, `.menu-velo` visible con `--overlay` y `body` sin
    desplazamiento;
  - la transición, solo con `prefers-reduced-motion: no-preference` (DS10).
- **Toque (CA4):**
  - `.btn, .btn-sm, .nav-item, .btn-logout, .menu-btn, .menu-cerrar`, los campos de formulario y
    los enlaces de acción con `min-height: var(--touch-min)` (y `min-width` en los botones de solo
    icono);
  - la casilla (`.campo-casilla`), con una etiqueta que da una zona de toque de 44 px.
- **Foco:** `.menu-btn` y `.menu-cerrar` con `:focus-visible { outline: var(--focus-ring-width) solid var(--focus-ring); }`.
- **Textos largos en el menú:** `.user-name` sin `nowrap` (parte de línea con `overflow-wrap: anywhere`).

**Script `menu.js`** (cargado en `<head>`, sin `defer`):
- Al cargar, añade `menu-js` a `<html>` (D5).
- Con el DOM listo, enlaza `.menu-btn`, `.menu-cerrar` y `.menu-velo`.
- **Abrir:**
  - pone `html.menu-abierto` y `aria-expanded="true"` en el botón;
  - pone `inert` en `<main>`;
  - lleva el foco al enlace de la sección actual, o al primero.
- **Cerrar** (botón «Cerrar menú», velo, Escape o paso a un ancho de escritorio por `matchMedia`):
  - deshace lo anterior;
  - devuelve el foco a `.menu-btn`, salvo si se cerró por cambio de ancho.
- Ninguna dependencia, ningún `eval`, ningún HTML desde cadenas. Encaja con la CSP de la 031
  (`script-src 'self'`).

**Pantallas y estados** (componentes del sistema; ningún estado nuevo de carga o error):
| Pantalla | Componentes | A 320–899 px |
|---|---|---|
| Menú (todos los roles) | `.sidebar`, `.nav-item`, `.user-card`, `.btn-logout`, nuevos `.menu-btn`, `.menu-cerrar`, `.menu-velo` | Opción A: botón «Menú» en la barra superior, panel blanco desde la izquierda |
| Panel del paciente | `.welcome-banner`, `.grid-auto`, `.card`, `.formulario-accesible .proxima-acciones`, `.table-wrap` | Una columna; la tabla del historial se desplaza dentro de su recuadro |
| Mis citas (próximas y pasadas) | pestañas (`.pestanas-citas`, nueva), `.card-0`, `.table-wrap`, `.acciones-fila` | La tabla se desplaza dentro de su recuadro; la página no |
| Detalle de mi cita | `.card`, `.grid-2`, `.acciones-fila` | Una columna |
| Reprogramar mi cita | `.formulario-accesible` (028) | Sin cambios de marcado |
| Agendar cita | `.grid-2`, campos | Una columna; campos de 44 px |
| Mi perfil | `.grid-2` (×4), campos | Una columna; campos de 44 px |

**Cambios a incorporar al sistema** (los aplica la primera tarea, `system.md` 1.3.0 → 1.4.0):
- Tokens: `--touch-min: 44px` (DS6), `--overlay: rgba(0, 0, 0, 0.45)` (velo del menú) y
  `--menu-movil-w: 300px` (ancho del panel).
- Punto de corte: móvil por debajo de 900 px. Es el único literal admitido, en la condición de la
  media query.
- Componente «Menú del móvil» (opción A,
  [opcion-a.html](design/opcion-a.html)): botón «Menú» con icono y texto, panel blanco con
  las secciones, nombre y «Cerrar sesión», «Cerrar menú», velo; foco, Escape e `inert`.
- `design.widths` [320, 390, 1440]; DS8 «en curso (033)» y DS6 «en parte (033)».

## Estrategia de pruebas
- **PHPUnit, `MovilPacienteTest`** (Feature, `DatabaseTransactions`, escenario de un usuario por
  rol). Todos fallan hoy salvo los de caracterización, que se marcan y se comprueban rompiendo.
  - **Layout:**
    - para los cuatro roles, la barra superior tiene `.menu-btn` con nombre «Menú»,
      `aria-expanded="false"` y `aria-controls` que apunta al `<aside id="menu-principal">`;
    - el `<aside>` tiene `aria-label` y `.menu-cerrar` con `aria-label="Cerrar menú"`;
    - existe `.menu-velo`;
    - `movil.css` está enlazado después de `app.css` y `menu.js` está en `<head>`;
    - (caracterización) el `<meta name="viewport">` sigue presente.
  - **Mismas secciones:** cada rol tiene los mismos enlaces del menú que hoy, con su lista exacta, y
    el `<aside>` es el único menú de la página.
  - **`movil.css`:**
    - existe el bloque `@media (max-width: 899px)`;
    - dentro: `.main` con `margin-left: 0` y `min-width: 0`; las tres rejillas a `1fr` y sus hijos
      con `min-width: 0`; el `.sidebar` cerrado con `visibility: hidden` y `translateX(-100%)`; con
      `.menu-abierto`, visible; `min-height: var(--touch-min)` en los controles de la lista;
      `overflow-wrap` en `.content` y en `.user-name`; transición solo bajo
      `prefers-reduced-motion: no-preference`;
    - fuera de la media query solo hay `display: none` de `.menu-btn`, `.menu-cerrar` y `.menu-velo`
      (CA8);
    - solo tokens en todas las declaraciones, con control positivo;
    - contraste ≥ 4.5:1 de los pares del menú y ≥ 3:1 del foco, con el trait `ReglasCss`.
  - **`menu.js`:** existe; añade `menu-js`; no usa `innerHTML` ni `eval`; maneja `Escape`, `inert`
    y `aria-expanded`. Es una comprobación de texto que asegura el contrato; el comportamiento real
    se comprueba con `--medir`.
  - **Vistas del paciente:** los bloques tocados no tienen `style=""` (por id de sección), las
    pestañas usan `.pestanas-citas` y el detalle usa `.grid-2`.
- **Comprobación en el navegador (`node capturar.mjs --medir`, D7)**, con la base de prueba y la
  cuenta de cada rol. Por cada vista del paciente, mide a 320, 390, 844 × 390 y 1440 px:
  `scrollWidth` ≤ ancho (CA1, CA6 y CA7, con textos largos), y que ninguna tabla ensancha la página.

  A 390 px mide además:
  - todos los controles visibles de `.main` y del menú abierto con caja ≥ 44 × 44 (CA4; los
    enlaces dentro de un párrafo quedan fuera);
  - el menú de cada rol (CA2, CA3): botón visible y panel oculto (no enfocable). Al pulsarlo,
    `aria-expanded="true"`, el foco dentro, `<main>` inerte y todas las secciones, el nombre y
    «Cerrar sesión» visibles. Escape lo cierra y el foco vuelve al botón;
  - a 844 × 390, el menú abierto se puede recorrer entero (CA7).

  A 1440 px, el botón y el velo están ocultos y la barra lateral está como hoy (CA8). Sale con
  código 1 y la lista de fallos.
- **Capturas** a 320, 390 y 1440 de las vistas tocadas y del menú abierto, contra `system.html`
  (verificación manual) y, a 1440, contra las capturas actuales (CA8).
- **Lector de pantalla** (verificación manual, CA3): con NVDA en Windows o TalkBack en el móvil del
  usuario, se anuncian «Menú, botón, contraído/expandido» y «Cerrar menú», y al cerrar se vuelve
  al botón.
- **Suite completa** y `pint --test` sobre los PHP tocados (solo tests).

## Modelo de amenazas
No aplica: la spec no toca datos sensibles, autenticación, permisos ni entradas externas, y el menú
es el mismo `<aside>` que ya ve cada rol. El único código nuevo que se ejecuta en el navegador,
`menu.js`, es propio, se sirve desde el mismo origen, no construye HTML desde cadenas y no lee
datos de la página. Encaja con la CSP de la 031.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: las 7 pantallas del paciente sin desplazamiento horizontal de 320 a 1440 | `movil.css` (marco, rejillas, `min-width: 0`), vistas del paciente | `MovilPacienteTest::test_movil_css_adapta_el_marco_y_las_rejillas`, `test_los_bloques_tocados_no_tienen_estilos_en_linea` · comprobación: `--medir` (anchos a 320, 390 y 1440) · verificación manual: capturas a 320 y 390 contra `system.html` |
| CA2: menú sin espacio fijo, todo a ≤ 2 toques, sección actual, mismas secciones, todos los roles | layout, `movil.css`, `menu.js` | `test_cada_rol_tiene_el_boton_menu`, `test_el_menu_del_movil_tiene_las_mismas_secciones`, `test_el_menu_cerrado_no_ocupa_espacio` · comprobación: `--medir` (menú de cada rol) |
| CA3: teclado y lector de pantalla | layout (`aria-*`), `menu.js`, `movil.css` (foco) | `test_cada_rol_tiene_el_boton_menu`, `test_menu_js_cumple_su_contrato`, `test_los_controles_del_menu_tienen_foco_visible` · comprobación: `--medir` (foco, Escape, `inert`) · verificación manual: NVDA o TalkBack |
| CA4: toque ≥ 44 × 44 a 390 | `movil.css` (toque), tokens | `test_los_controles_tienen_toque_minimo` · comprobación: `--medir` (cajas a 390) |
| CA5: formularios de agendar, reprogramar y perfil a 320 | `movil.css`, `cliente-agendar`, `cliente/perfil` | `test_movil_css_adapta_el_marco_y_las_rejillas`, `test_los_campos_conservan_su_tipo` (caracterización: `date`, `time`, `tel` y `email`) · comprobación: `--medir` (formularios a 320, error de validación visible junto al campo) · verificación manual: teclado del móvil del usuario |
| CA6: textos largos | `movil.css` (`overflow-wrap`), datos de prueba de `capturar.mjs` | `test_los_textos_largos_parten_de_linea` · comprobación: `--medir` con los textos largos a 320 |
| CA7: horizontal 844 × 390 | `movil.css` (alto `100dvh`, `overflow-y` del panel) | `test_el_menu_se_recorre_con_poca_altura` · comprobación: `--medir` a 844 × 390 |
| CA8: escritorio a 1440 sin cambios | todo dentro de la media query | `test_fuera_del_movil_solo_se_ocultan_los_elementos_nuevos` · comprobación: `--medir` a 1440 · verificación manual: capturas a 1440 contra las actuales |
| RNF: WCAG 2.1 AA (1.4.10, 1.3.4, 2.4.7, 4.1.2) | `movil.css`, layout, `menu.js` | `test_los_pares_del_menu_cumplen_aa`, `test_los_controles_del_menu_tienen_foco_visible` · comprobación: `--medir` |
| RNF: toque ≥ 44 × 44 | tokens, `movil.css` | `test_los_controles_tienen_toque_minimo` · comprobación: `--medir` |
| RNF: medido a 320, 390, 1440 y 844 × 390 | `capturar.mjs`, `.ai/project.yaml` | comprobación: `--medir` |
| P9: solo tokens | `movil.css`, `app.css` | `test_movil_css_solo_usa_tokens` |

## Observabilidad
No aplica: no toca datos sensibles, autenticación ni permisos, y no añade logs, eventos ni métricas.
Tras el despliegue se comprueba a mano, desde un móvil real, que la dirección pública se ve sin
desplazamiento lateral.

## Rollout
- **Feature flag:** no. Solo cambia la presentación por debajo de 900 px, el escritorio no cambia
  (CA8) y el proyecto no tiene mecanismo de flags. Se revierte con la vuelta atrás de la 030.
- **Orden de despliegue:** `deploy/desplegar.sh <tag>`. No hay migraciones; los archivos nuevos van
  en la imagen `web` (estáticos) y en `app` (vista).
- **Compatibilidad:** sin cambios de datos. Durante el despliegue, una página antigua en caché sigue
  funcionando, porque `app.css` lleva `?v=filemtime`, y `movil.css` y `menu.js` irán con el mismo
  sufijo.
- **Rollback:** `bash deploy/volver.sh <tag anterior>`, sin copia de la base.
- **Orden respecto a otras specs:** no comparte archivos con la 031 ni con la 032. La versión se fija
  en `/release` (v0.8.0 si la 031 sale antes como v0.7.0).
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
  - hay de 1 a 12 controles < 44 px por pantalla.

  De ahí, `min-width: 0` en `.main` y en los hijos de las rejillas.
- **D3 · Reutilizar el `<aside>` en lugar de un segundo menú.** Un único menú garantiza las mismas
  secciones (CA2) y no duplica el marcado de los cuatro roles.
- **D4 · Hoja aparte `movil.css`.** Un menú de solo CSS (casilla oculta o `:target`) no maneja
  `aria-expanded`, Escape ni el foco (CA3). `<details>` no cierra con Escape ni devuelve el foco.
  Por eso se usa un script propio. La hoja va aparte, en lugar de dentro de `app.css`, para que el
  test de «solo tokens» y el de CA8 la lean entera y no tengan que separar sus reglas de las
  anteriores (DS4).
- **D5 · Sin JS, el menú se ve.** El panel solo se oculta con `html.menu-js`, que pone el script al
  cargar en `<head>`, antes de pintar (sin parpadeo). Sin script, la barra lateral se ve encima del
  contenido, como un bloque.
- **D6 · Punto de corte 900 px.** A 844 × 390 (CA7) y en tabletas en vertical hay menú desplegable;
  desde 900 px, barra lateral, que deja 658 px de contenido. Las media queries no admiten variables,
  así que el valor se documenta en `system.md` y es el único literal admitido, solo en la condición.
- **D7 · Medir con `capturar.mjs --medir`.** Se reutiliza el contenedor de Playwright y la base de
  prueba anonimizada que ya documenta `capturar.mjs`, sin dependencias nuevas ni Playwright en el
  CI. El modo `--medir` comprueba en un navegador real lo que PHPUnit no puede (anchos, cajas,
  foco, Escape, `inert`) y sale con código 1 si algo falla; lo ejecutan `/implement` (T040) y
  `/review`. Los datos de prueba ganan:
  - un paciente con nombre y apellidos largos («Maximiliana Guadalupe de los Ángeles
    Fernández-Villaseñor») y un correo largo sin espacios;
  - un servicio de nombre largo;

  todo inventado (CA6, P5).
- **D8 · Toque solo por debajo de 900 px.** CA4 se mide a 390 y CA8 pide que a 1440 no cambie nada
  más, así que `--touch-min` se aplica dentro de la media query. En el escritorio, DS6 sigue como
  deuda.
- **D9 · Menú blanco en el móvil.** La barra lateral rosa da 4,3:1 con su texto blanco, por debajo
  de AA (P9 en lo que se toca). En el móvil, el panel es blanco con `--p600` (7:1); en el
  escritorio no cambia (CA8) y su contraste queda como deuda (DS1).
- **D10 · Deuda de las vistas** (spec, Decisiones; 028 R21). Solo se quitan los `style=""` de los
  bloques que se tocan para cumplir CA1, CA4 y CA5. El resto de cada vista sigue con su deuda DS4.
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
  `test_fuera_del_movil_solo_se_ocultan_los_elementos_nuevos`, `--medir` a 1440 y las capturas.
- **Navegadores del móvil:** `inert`, `100dvh` y `:focus-visible` están en Safari 15.4+ y Chrome
  102+. Sin `inert`, el foco podría salir del panel. Mitigación: el velo tapa la página y Escape y
  «Cerrar menú» están siempre disponibles; se anota en `system.md`.
- **Coste de la base de prueba:** montar `dentissa_capturas` lleva tiempo (procedimiento de
  `capturar.mjs`). Mitigación: se monta una vez en T040 y se borra al terminar, como en la 028 (T032).
- **107 `style=""` en las vistas del paciente:** tocarlos todos agrandaría la spec. Mitigación: D10;
  los que no estorban se quedan como DS4.
- **CSP de la 031:** si la 031 se libera antes, `script-src 'self'` admite `menu.js`. El script no
  usa `eval` ni manejadores en línea, así que también encajaría con una CSP estricta sin `'unsafe-inline'`.
