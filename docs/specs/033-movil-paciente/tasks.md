---
spec: 033-movil-paciente
plan: plan.md
status: draft
---

# Tareas · 033 Móvil para el paciente

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

## Constitution Check
Sobre el desglose (el análisis completo está en `plan.md`):

| Principio | Resultado | Evidencia en las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados; las tareas nacen en `draft` |
| P2 Test que falla antes y pasa después | ✅ | Los tests de T010–T013 y el modo `--medir` de T014 se escriben antes que la implementación (T020–T027) y fallan hoy. Las de caracterización van marcadas y se comprueban rompiendo. Cada implementación `depende:` de su test |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas, Gates ni controladores |
| P4 Entrada validada y SQL con bindings | ➖ | Ninguna tarea añade entradas ni consultas |
| P5 Nada sensible en el repo | ✅ | T014 usa la base anonimizada `dentissa_capturas` y textos largos inventados; T040 pasa gitleaks y borra la base de prueba |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias; T040 pasa `composer audit`, `npm audit` y `trivy fs` |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 añade al sistema los valores nuevos antes que el CSS (T020, T021); T011 y T013 comprueban solo tokens, contraste y ausencia de `style=""` en los bloques tocados; cada tarea de UI compara con `system.html` a 320, 390 y 1440 |

## Preparación
- [ ] T001 Incorporar al sistema de diseño los cambios del plan («Cambios a incorporar al sistema»): `system.md` 1.3.0 → 1.4.0, con una fila en sus Decisiones; tokens `--touch-min: 44px`, `--overlay: rgba(0, 0, 0, 0.45)` y `--menu-movil-w: 300px`; punto de corte «móvil < 900 px» (único literal admitido, en la condición de la media query, plan D6); componente «Menú del móvil» de la opción A (botón «Menú», panel blanco con `--p600`, «Cerrar menú», velo, foco, Escape e `inert`, y navegadores mínimos, plan D9 y Riesgos); `design.widths` [320, 390, 1440]; DS8 «en curso (033)» y DS6 «en parte (033)»; y en `system.html`, los tokens y el componente con la maqueta de `design/opcion-a.html` — docs/design/system.md, docs/design/system.html — hecho cuando: los dos archivos tienen los tres tokens con esos valores, el punto de corte y el componente, y `system.html` se abre sin errores · verificación manual: el componente de `system.html` se ve como `design/opcion-a.html` — cubre: CA2, CA3, CA4
- [ ] T002 [P] Cambiar `design.widths` a `[320, 390, 1440]` (con el motivo en el comentario: spec 033, WCAG 1.4.10) y registrar la línea base: `php artisan test` completo, `composer audit` y `npm audit` — .ai/project.yaml — hecho cuando: `aidd.py validate` no da error de diseño (widths igual en `project.yaml` y en `system.md`) y la nota recoge el número de tests y el resultado de las auditorías — depende: T001
- [ ] T003 [P] Sacar a un trait `ReglasCss` los ayudantes de CSS de `ReprogramarCitaVistasTest` (`reglasCss`, `declaraciones`, `literales` y el cálculo de contraste), con `reglasCss(string $archivo)`, que devuelve también las reglas de dentro de cada `@media` junto con su condición; el test de la 028 pasa a usarlo sin cambiar ninguna aserción — tests/Feature/Concerns/ReglasCss.php, tests/Feature/ReprogramarCitaVistasTest.php — hecho cuando: `ReprogramarCitaVistasTest` pasa entero dos veces y `pint --test` pasa en los dos archivos

## Tests (antes de implementar)
- [ ] T010 Escribir en `MovilPacienteTest` (escenario con un usuario de cada rol, `DatabaseTransactions`) los tests del layout: `test_cada_rol_tiene_el_boton_menu`: para los cuatro roles, en su panel, la barra superior tiene `.menu-btn` (`type="button"`) con el texto «Menú», `aria-expanded="false"` y `aria-controls` que apunta a `<aside id="menu-principal">`; el `<aside>` tiene `aria-label="Menú"` y un `.menu-cerrar` con `aria-label="Cerrar menú"`; existe `.menu-velo` con `hidden`; `test_la_pagina_carga_la_hoja_y_el_script_del_movil`: `movil.css` se enlaza después de `app.css` y `menu.js` va en `<head>`, sin `defer`; los dos llevan `?v=`; el `<meta name="viewport">` sigue presente (esta última parte es de caracterización); `test_el_menu_del_movil_tiene_las_mismas_secciones` (caracterización): por rol, la lista exacta de enlaces del `<aside>` es la de hoy (admin: Home, Citas, Pacientes, Personal, Servicios, Reportes, Usuarios; dentista: Home, Mis Citas, Pacientes, Expedientes; asistente: Home, Mis Doctores, Citas, Pacientes; paciente: Inicio, Mis Citas, Agendar Cita, Mi Perfil), con «Cerrar sesión» y el nombre del usuario, y es el único `<nav>` de la página; `test_el_bloque_del_menu_no_tiene_estilos_en_linea`: el `<aside>` no tiene `style=""` (P9, plan: bloque tocado) — tests/Feature/MovilPacienteTest.php — hecho cuando: los tests que no son de caracterización fallan por la razón esperada (no existen el botón, el velo, la hoja ni el script; el logo y la tarjeta de usuario tienen `style=`) y el de caracterización pasa y se comprueba rompiendo (quitar un enlace del menú del paciente) — cubre: CA2, CA3 — depende: T003
- [ ] T011 Escribir en `MovilPacienteTest` los tests de `public/css/movil.css`, con el trait `ReglasCss`: `test_movil_css_adapta_el_marco_y_las_rejillas`: existe `@media (max-width: 899px)` y, dentro, `.main` con `margin-left: 0` y `min-width: 0`; `.grid-auto`, `.grid-2` y `.grid-3` con `grid-template-columns: 1fr` y sus hijos con `min-width: 0`; `.topbar` con `flex-wrap: wrap` y `min-height`; y `.content` y `.topbar` con `padding` `var(--space-6)`; `test_el_menu_cerrado_no_ocupa_espacio`: dentro, `html.menu-js .sidebar` con `transform: translateX(-100%)` y `visibility: hidden`; `html.menu-abierto .sidebar` con `transform: none` y `visibility: visible`; `.menu-velo` visible con `background: var(--overlay)`; y `body` sin desplazamiento cuando el menú está abierto; `test_los_controles_tienen_toque_minimo`: dentro, `min-height: var(--touch-min)` para `.btn`, `.btn-sm`, `.nav-item`, `.btn-logout`, `.menu-btn`, `.menu-cerrar` y los campos (`.field`, `input`, `select`, `textarea`), `min-width: var(--touch-min)` en `.menu-cerrar` y zona de toque de la casilla en `.campo-casilla label`; `test_los_textos_largos_parten_de_linea`: dentro, `.content` con `overflow-wrap: break-word` y `.user-name` con `white-space: normal` y `overflow-wrap: anywhere`; `test_el_menu_se_recorre_con_poca_altura`: dentro, `.sidebar` con `height: 100dvh` y `.sidebar-nav` con `overflow-y: auto`; `test_fuera_del_movil_solo_se_ocultan_los_elementos_nuevos`: fuera de toda `@media` solo hay una regla, `.menu-btn, .menu-cerrar, .menu-velo { display: none; }`, y la transición del panel solo aparece bajo `prefers-reduced-motion: no-preference`; `test_movil_css_solo_usa_tokens`: ninguna declaración de `movil.css` tiene literales, con control positivo (salvo `0`, `1fr`, `none`, `-100%`, `85vw` y `100dvh`), y la única cifra con `px` es la de la condición de la media query; `test_los_pares_del_menu_cumplen_aa`: `.nav-item` del panel con `--text` sobre `--white`, `.nav-item.active` con `--p600` sobre `--p100` y `.nav-label` con `--p600` sobre `--white` dan ≥ 4.5:1; `test_los_controles_del_menu_tienen_foco_visible`: `.menu-btn:focus-visible` y `.menu-cerrar:focus-visible` con `outline` en `var(--focus-ring)`, con contraste ≥ 3:1 sobre `--white` — tests/Feature/MovilPacienteTest.php — hecho cuando: todos fallan porque `movil.css` no existe — cubre: CA1, CA2, CA4, CA5, CA6, CA7, CA8 — depende: T010
- [ ] T012 Escribir en `MovilPacienteTest` el test `test_menu_js_cumple_su_contrato`. El archivo existe y, como comprobación de texto: añade `menu-js` a `document.documentElement`; maneja `Escape`; usa `inert`, `aria-expanded` y `matchMedia('(max-width: 899px)')`; devuelve el foco a `.menu-btn`; y no contiene `innerHTML`, `outerHTML`, `insertAdjacentHTML`, `eval(` ni `new Function` — tests/Feature/MovilPacienteTest.php — hecho cuando: falla porque `menu.js` no existe — cubre: CA3 — depende: T011
- [ ] T013 Escribir en `MovilPacienteTest` los tests de las vistas del paciente: `test_los_bloques_tocados_no_tienen_estilos_en_linea`: como el paciente del escenario, las pestañas «Próximas / Pasadas» de mis citas usan `.pestanas-citas` sin `style=""`, y el detalle de la cita usa `.grid-2` sin `grid-template-columns` en línea; `.pestanas-citas` existe en `app.css` y solo usa tokens. La lista de bloques la amplían T026 y T027 con cada bloque que toquen (por su `id`); `test_los_campos_conservan_su_tipo` (caracterización, CA5): en agendar, `fecha` es `type="date"` y `hora` es `type="time"`; en el perfil, `telefono` es `type="tel"`, `email` es `type="email"` y `fecha_nacimiento` es `type="date"` — tests/Feature/MovilPacienteTest.php — hecho cuando: el primero falla por la razón esperada (las pestañas tienen `style=`, el detalle tiene la rejilla en línea y la clase no existe) y el de caracterización pasa y se comprueba rompiendo (cambiar `type="tel"` a `text`) — cubre: CA1, CA5 — depende: T012
- [ ] T014 [P] Ampliar `capturar.mjs` (plan D7): Anchos y vistas: el ancho 320 en las vistas que toca la spec (las 7 del paciente) y una vista nueva por rol con el menú abierto a 390. Datos de prueba (documentados en la cabecera, paso 3), todos inventados: un paciente con el nombre «Maximiliana Guadalupe de los Ángeles» y los apellidos «Fernández-Villaseñor de la Concepción»; un correo largo sin espacios; un servicio con el nombre «Limpieza dental profunda con aplicación de flúor y sellado de fisuras»; una cita futura agendada de ese paciente, que abra el formulario de reprogramar. Modo `--medir`, con la cuenta de cada rol: por cada vista del paciente, a 320, 390, 844 × 390 y 1440 px, `scrollWidth` ≤ ancho, y ninguna `.table-wrap` más ancha que su contenedor; a 390 px, cajas ≥ 44 × 44 de los controles visibles de `.main` y del menú abierto (sin los enlaces dentro de un `<p>`); en agendar, el perfil y reprogramar, enviados vacíos a 320 px, cada mensaje de error dentro del ancho y junto a su campo (CA5); el menú de cada rol a 390: panel oculto (`visibility: hidden`) y botón visible; al pulsar, `aria-expanded="true"`, foco dentro del panel, `<main>` con `inert` y todas las secciones, el nombre y «Cerrar sesión» visibles; Escape lo cierra, el foco vuelve al botón y el velo cierra también; a 844 × 390, el último elemento del menú abierto se alcanza desplazando el panel; a 1440, el botón y el velo ocultos y la barra lateral visible. Sale con código 1 y la lista de fallos. Monta la base de prueba según la cabecera, pero no la borra hasta T040 — docs/design/capturar.mjs — hecho cuando: `node capturar.mjs --seco --medir` lista las comprobaciones, y `--medir` contra la base de prueba falla hoy (página de 892 px en el panel a 390, sin botón «Menú»), anotado en la nota — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8 — depende: T002

## Implementación
- [ ] T020 Añadir a `:root` de `app.css` los tokens `--touch-min`, `--overlay` y `--menu-movil-w` con los valores de T001, sin cambiar ninguna otra regla — public/css/app.css — hecho cuando: `git diff public/css/app.css` solo añade esas tres líneas y `test_las_reglas_css_nuevas_solo_usan_tokens` sigue en verde — cubre: CA4 — depende: T001, T011
- [ ] T021 Crear `public/css/movil.css` según «Hoja `movil.css`» del plan (todo dentro de `@media (max-width: 899px)` salvo el `display: none` de los elementos nuevos) — public/css/movil.css — hecho cuando: pasan dos veces todos los tests de T011 — cubre: CA1, CA2, CA4, CA5, CA6, CA7, CA8 — depende: T011, T020
- [ ] T022 [P] Crear `public/js/menu.js` según «Script `menu.js`» del plan: al cargar, `menu-js` en `<html>`; con el DOM listo, abrir y cerrar con `.menu-btn`, `.menu-cerrar`, `.menu-velo` y Escape; `aria-expanded`; `inert` en `<main>`; foco a la sección actual (o la primera) al abrir y de vuelta a `.menu-btn` al cerrar; y cerrar sin mover el foco cuando `matchMedia('(max-width: 899px)')` deja de cumplirse — public/js/menu.js — hecho cuando: pasa dos veces `test_menu_js_cumple_su_contrato` — cubre: CA3 — depende: T012
- [ ] T023 Cambiar el layout según «Cambios por módulo» del plan: enlazar `movil.css` tras `app.css` y `menu.js` en `<head>`, los dos con `?v={{ filemtime(…) }}`; poner `.menu-btn` (icono SVG con `aria-hidden="true"` y el texto «Menú») antes del título de la barra superior; en el `<aside>`, `id="menu-principal"`, `aria-label="Menú"` y `.menu-cerrar` en `.sidebar-logo`; añadir `<div class="menu-velo" hidden>`; pasar a clases de `app.css` con tokens el `style=` del logo (`.sidebar-logo img`) y el de la tarjeta de usuario (`.user-card-texto`); quitar el `</div>` sobrante tras `.sidebar-logo` — resources/views/layouts/app.blade.php, public/css/app.css — hecho cuando: pasan dos veces los tests de T010 y los de T011 y T012 siguen en verde · verificación manual: el menú de cada rol cerrado y abierto a 390 px, contra `system.html` (componente «Menú del móvil») y `design/opcion-a.html`; el escritorio a 1440 sin cambios visibles; con NVDA en Windows o TalkBack en el móvil del usuario, se anuncian «Menú, botón, contraído/expandido» y «Cerrar menú», y al cerrar el foco vuelve al botón (CA3) — cubre: CA2, CA3, CA6, CA8 — depende: T010, T021, T022
- [ ] T024 [P] Pasar las pestañas «Próximas / Pasadas» de mis citas a `.pestanas-citas` (nueva en `app.css`, solo tokens, mismo aspecto que hoy a 1440) y quitar los `style=""` de ese bloque, así como los de otros bloques de esta vista que `--medir` señale a 320 o 390 (desbordamiento o controles < 44 px) — resources/views/citas/cliente-index.blade.php, public/css/app.css — hecho cuando: pasa la parte de las pestañas de `test_los_bloques_tocados_no_tienen_estilos_en_linea` y `--medir` no da fallos en mis citas (próximas y pasadas) · verificación manual: mis citas a 320, 390 y 1440 contra `system.html` (a 1440, igual que su captura actual) — cubre: CA1, CA4 — depende: T013, T014, T021, T023
- [ ] T025 Pasar la rejilla en línea del detalle de la cita a `.grid-2` y quitar los `style=""` de ese bloque y de los que `--medir` señale en esta vista — resources/views/citas/cliente-show.blade.php — hecho cuando: pasa la parte del detalle de `test_los_bloques_tocados_no_tienen_estilos_en_linea` y `--medir` no da fallos en el detalle · verificación manual: el detalle a 320, 390 y 1440 contra `system.html` — cubre: CA1, CA4 — depende: T013, T014, T021, T023
- [ ] T026 Corregir en el panel del paciente los bloques que `--medir` señale (desbordamiento a 320 o controles < 44 px a 390), pasando sus `style=""` a clases con tokens en `app.css`, y añadir el `id` de cada bloque tocado a `test_los_bloques_tocados_no_tienen_estilos_en_linea` — resources/views/dashboard/cliente.blade.php, tests/Feature/MovilPacienteTest.php — hecho cuando: `--medir` no da fallos en el panel y el test ampliado pasa · verificación manual: el panel a 320, 390 y 1440 contra `system.html` (a 1440, igual que su captura actual) — cubre: CA1, CA4, CA6 — depende: T024, T025
  - nota: las clases nuevas van en `app.css`; si T024 la tiene abierta, esta tarea espera (comparten el archivo). Por eso no es `[P]`.
- [ ] T027 Corregir en agendar y en el perfil los bloques que `--medir` señale (desbordamiento a 320, controles < 44 px a 390 o errores fuera del ancho o lejos de su campo), pasando sus `style=""` a clases con tokens en `app.css`, y añadir sus `id` al test de T013 — resources/views/citas/cliente-agendar.blade.php, resources/views/cliente/perfil.blade.php, tests/Feature/MovilPacienteTest.php — hecho cuando: `--medir` no da fallos en agendar ni en el perfil, el test ampliado pasa y `test_los_campos_conservan_su_tipo` sigue en verde · verificación manual: agendar y perfil a 320, 390 y 1440 contra `system.html`; en el móvil del usuario, los campos de fecha, hora, teléfono y correo abren su selector o teclado (CA5) — cubre: CA1, CA4, CA5 — depende: T026
- [ ] T028 Rehacer con `capturar.mjs` las capturas de las 7 vistas del paciente a 320, 390 y 1440 y las del menú abierto de cada rol, y poner las filas nuevas en la tabla de pantallas de `system.md` — docs/design/capturas/ (generado por node capturar.mjs), docs/design/system.md — hecho cuando: las capturas existen con fecha de hoy y la tabla las enlaza · verificación manual: las capturas a 1440 son iguales a las anteriores, salvo los datos de prueba (CA8) — cubre: CA1, CA8 — depende: T027

## Integración y documentación
- [ ] T040 Hacer la verificación final — (sin archivos) — hecho cuando:
  - `php artisan test` completo da la línea base de T002 más los tests de `MovilPacienteTest`, todos
    en verde;
  - `pint --test` pasa en los PHP tocados (`MovilPacienteTest`, `ReglasCss` y
    `ReprogramarCitaVistasTest`);
  - `node capturar.mjs --medir` termina con código 0 en las 7 vistas, en los 4 anchos y con los 4
    roles; esto incluye reprogramar, que no cambia de marcado;
  - gitleaks sin fugas, y `composer audit`, `npm audit` y `trivy fs --severity HIGH,CRITICAL` sin
    avisos nuevos respecto a T002;
  - la base `dentissa_capturas`, su servidor y el volumen de Playwright quedan borrados, y la imagen
    de Playwright se conserva.

  Cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8.
- [ ] T090 Actualizar `docs/architecture.md`: en la sección de la interfaz, `public/css/movil.css`
  (adaptación por debajo de 900 px) y `public/js/menu.js` (menú del móvil, primer script propio en
  un archivo) junto a `app.css` y al layout único
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas
  - nota: el plan no prevé cambios (sin variables, migraciones, logs ni métricas); se anota «no
    aplica» al cerrar.
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - No hay staging (030): las comprobaciones en un móvil real se hacen en producción, en T097.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
  - verificación manual: desde el móvil del usuario, en la dirección pública, el paciente recorre
    sus 7 pantallas sin desplazamiento lateral y abre y cierra el menú; un rol del personal abre el
    menú y llega a todas sus secciones.
  - Sin errores 500 en `laravel.log` ni respuestas 5xx en el log de nginx durante 15 minutos.
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T013, T014 | T021, T024, T025, T026, T027, T028 |
| CA2 | T010, T011, T014 | T021, T022, T023 |
| CA3 | T010, T012, T014 | T022, T023 |
| CA4 | T011, T014 | T020, T021, T024, T025, T026, T027 |
| CA5 | T011, T013, T014 | T021, T027 |
| CA6 | T011, T014 | T021, T023, T026 |
| CA7 | T011, T014 | T021 |
| CA8 | T011, T014 | T021, T023, T028 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| — (el plan no tiene amenazas: no toca datos, permisos ni entradas) | — | — |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Sistema de diseño (`system.md`, `system.html`) | T001, T028 |
| Configuración (`.ai/project.yaml`) | T002 |
| Tokens (`public/css/app.css`) | T020, T023, T024, T026, T027 |
| Estilos del móvil (`public/css/movil.css`) | T021 |
| Script del menú (`public/js/menu.js`) | T022 |
| Layout (`layouts/app.blade.php`) | T023 |
| Vistas del paciente | T024, T025, T026, T027 |
| `citas/partials/reprogramar`, `citas/cliente-reprogramar` (sin cambios previstos) | T040 (`--medir`) |
| Tests (`MovilPacienteTest`, `ReglasCss`, `ReprogramarCitaVistasTest`) | T003, T010, T011, T012, T013, T026, T027 |
| Capturas y medidas (`capturar.mjs`) | T014, T028 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA (1.4.10, 1.3.4, 2.4.7, 4.1.2) | T011, T014, T040 · verificación manual del lector de pantalla en T023 |
| Toque ≥ 44 × 44 px | T011, T014, T040 |
| Medido a 320, 390, 1440 y 844 × 390 | T014, T040 |
