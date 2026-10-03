---
status: approved         # aprobado por el usuario el 2026-10-02
source: extracted        # documentado del código el 2026-10-02 (upgrade a AI-DD 1.11.0)
version: 1.1.0
html: docs/design/system.html
canvas: null
widths: [390, 1440]
created: 2026-10-02
updated: 2026-10-03
---

# Sistema de diseño de Dentissa

Fuente de verdad visual del proyecto. Toda pantalla nueva o modificada usa estos tokens y
componentes. Si hace falta un valor que no está aquí, primero se añade aquí (sección "Cambios a
incorporar" del plan que lo necesite). Vista: [system.html](system.html).

## Dirección

Es el sistema extraído del código el 2026-10-02: describe lo que la app hace hoy, sin mejoras.

Es una app de gestión clínica para el personal y los pacientes. Usa una paleta rosa de marca sobre
un fondo rosa muy claro, tarjetas blancas con borde suave y una barra lateral rosa intensa con el
menú de cada rol. Es densa (cuerpo de 13–13,5 px) y pensada para escritorio.

Objetivo de accesibilidad: **WCAG 2.1 AA** (decisión del usuario, 2026-10-02). Lo que hoy no lo
cumple está en "Deuda de diseño".

## Color

Tokens reales de `:root` en `public/css/app.css:3-49`. La columna "Rol" da el nombre semántico de
la plantilla de ai-dd (`--color-*`), que es el que usa `system.html`.

| Token | Valor | Rol | Uso | Contraste sobre su fondo |
|---|---|---|---|---|
| `--bg` / `--p50` | `#FDF5F8` | `--color-bg` | Fondo de la app | — |
| `--white` | `#FFFFFF` | `--color-surface` | Tarjetas, tablas, campos | — |
| `--text` | `#000000` | `--color-text` | Texto principal | 19.59:1 sobre --bg · 21.00:1 sobre --white |
| `--text-muted` | `#000000` | `--color-text-muted` | Texto secundario (145 usos en línea); hoy es igual a `--text` (DS3) | 19.59:1 sobre --bg |
| `--text-light` | `#9B8290` | `--color-text-light` | Etiquetas de campo, ayudas, `th`, `.nav-label` | 3.51:1 sobre --white · 3.27:1 sobre --bg (no cumple, DS1) |
| `--p400` | `#D4537E` | `--color-primary` | Botón principal, acentos | — |
| `--white` sobre `--p400` | `#FFFFFF` | `--color-on-primary` | Texto del botón principal | 3.93:1 sobre --p400 (no cumple, DS1) · 5.42:1 en hover sobre --p500 |
| `--p500` | `#B83C65` | `--color-link` | Enlaces y texto de acento | 5.42:1 sobre --white |
| `--p600` | `#993556` | `--color-accent-text` | Texto de nav activo, `.btn-outline`, `.badge-pink` | 7.00:1 sobre --white · 6.05:1 sobre --p100 |
| `--p100` / `--p200` / `--p300` / `--p800` | `#FBEAF0` / `#F4C0D1` / `#ED93B1` / `#72243E` | — | Fondos de hover y activo, bordes de botón, foco, degradados | — |
| `--border` | `#F0DCE4` | `--color-border` | Bordes de tarjeta y de campo | 1.31:1 sobre --white (no cumple como borde de control, DS2) |
| `--border-control` = `--p400` | `#D4537E` | `--color-border-control` | Borde de control que cumple 3:1. Desde la spec 027, solo en los campos de hora del horario | 3.93:1 sobre --white · 3.67:1 sobre --p50 |
| `--focus-ring` = `--p600` | `#993556` | `--color-focus` | **Único token de foco**: anillo `:focus-visible` de `--focus-ring-width` con desplazamiento `--focus-ring-offset` (spec 027) | 7.00:1 sobre --white · 6.54:1 sobre --p50 |
| `--p300` | `#ED93B1` | — (foco antiguo) | Borde de campo con foco en el código previo (más halo `rgba(212,83,126,.07)`); no se usa en pantallas nuevas | 2.23:1 sobre --white (no cumple, DS2) |
| `--red` / `--red-bg` | `#A32D2D` / `#FCEBEB` | `--color-danger` | Errores, badge rojo, flash de error | 6.13:1 sobre --red-bg · 7.07:1 sobre --white |
| `--green` / `--green-bg` | `#1D9E75` / `#E1F5EE` | `--color-success` | Éxito, badge verde, flash de éxito | 2.98:1 sobre --green-bg · 3.39:1 sobre --white (no cumple, DS1) |
| `--amber` / `--amber-bg` | `#854F0B` / `#FAEEDA` | `--color-warning` | Avisos, badge ámbar (en curso) | 5.87:1 sobre --amber-bg |
| `--blue` / `--blue-bg` | `#185FA5` / `#E6F1FB` | `--color-info` | Badge azul (confirmada) | 5.70:1 sobre --blue-bg |
| (literal) `#c75078` | `#c75078` | `--color-sidebar` | Fondo de la barra lateral; no es token (DS4) | blanco 4.33:1 · blanco 90% 3.82:1 (menú, no cumple) · blanco 65% 2.72:1 (`.nav-label`, no cumple) |

Contraste calculado con la fórmula de luminancia relativa de WCAG 2.x. Umbrales: ≥ 4.5:1 en texto
normal, ≥ 3:1 en texto grande y en bordes de control. Modo oscuro: no aplica (no existe).

`public/css/auth.css:8-26` (login y registro) redefine un subconjunto de estos tokens, con
`--text-light: #000000` (DS5).

## Tipografía

| Token | Familia | Pesos | Uso |
|---|---|---|---|
| (sin token) | Roboto, sans-serif | 300–700 (layout) · 300–600 (auth) | Toda la app |
| `--weight-regular` / `--weight-strong` | — | 400 / 500 | Pesos de las reglas nuevas (spec 027) |
| (sin token) | no aplica | — | `'DM Sans'` y `'DM Mono'` se nombran en 3 vistas pero no se cargan (DS9) |

Tokens de tamaño (spec 027): `--text-body` (13.5px, cuerpo) y `--text-small` (11.5px, ayudas y
notas). En `app.css`, el prefijo `--text-` lo usan también los colores (`--text`, `--text-muted`,
`--text-light`): **`--text-body` y `--text-small` son tamaños** y solo van en `font-size`; los
otros tres son colores. Los demás tamaños siguen sin token (DS4). Tamaños reales (solo
escritorio, 17 distintos en el CSS):

| Nivel | Escritorio | Dónde |
|---|---|---|
| Título de página (`h1` de la barra superior) | 19px / 500 | `app.css:190` |
| Cifra de tarjeta (`.stat-value`) | 28px / 600 | `app.css:251` |
| Título de sección (`span.section-title`) | 15px / 500 | `app.css:267` |
| Cuerpo (tabla, menú, campo, botón) | 13–13.5px | `app.css`, `citas.css` |
| Pequeño (etiqueta, ayuda, badge, `th`) | 10–12.5px | `app.css`, `citas.css` |

Fuentes: **Roboto cargada desde Google Fonts** (`layouts/app.blade.php:7` y las tres vistas de
auth). Es un servicio externo que `docs/security.md` no aprueba (DS9).

## Espaciado, radios, bordes y sombras

- Escala de espaciado (spec 027): `--space-1` … `--space-8` = 4, 6, 8, 10, 14, 16, 20 y 28px.
  Recoge los valores literales más usados (`gap` 8px (52), 10px (34), 6px (17); `margin-bottom`
  16px (29), 20px (24), 14px (20)) más 4px (margen de la ayuda de campo) y 28px (margen de la
  página). 12px y 18px quedan fuera para no tener pasos de 2px: al migrar una pantalla, 12px pasa a
  10 o 14px y 18px a 16 o 20px. Las pantallas anteriores siguen con literales (DS4).
- Margen de la página: `.content` 28px; barra superior de 60px de alto con padding lateral 28px.
  Tarjeta: padding 22px.
- Radios: `--radius-sm` 8px (botones y campos) · `--radius` 12px (tarjetas) · `--radius-lg` 16px.
  Además hay literales: 10px en auth, 20px en píldoras, y 5px y 9px en servicios (DS4).
- Bordes: `--border-width` (1px) `--border` (1.5px en los campos redefinidos en las vistas, DS5).
  Foco: `--focus-ring-width` (2px) y `--focus-ring-offset` (2px). Sombras: no hay.
- Campo de hora: `--field-time-width` (110px).
- Toque mínimo: **no definido**. Botón ≈ 32px, `.btn-sm` ≈ 25px y campo ≈ 36px de alto (DS6).

## Componentes

### Botón
- Clases: `.btn` (padding 8px 16px, 13px/500, radio 8px) con las variantes `.btn-primary`
  (`--p400`, texto blanco), `.btn-outline` (blanco, texto `--p600`, borde `--p200`), `.btn-ghost`
  (transparente) y `.btn-sm` (5px 12px, 12px).
- En auth: `.btn-submit`, `.btn-next`, `.btn-back` y `.btn-google`, de unos 43px.
- Estados: solo `:hover`. No hay foco visible propio, ni deshabilitado, ni carga (DS2, DS7).
- No existe un botón de peligro: se hace en línea con `background: var(--red)`.

### Campo de formulario
- `.field`, `.field-label` y `.field-error` están en
  `public/css/citas.css`, que solo cargan 7 vistas. Las demás copian las reglas en bloques
  `<style>` con variantes distintas (DS5).
- Etiqueta de 11px en `--text-light`, campo de 13.5px con padding 9px 13px y borde 1px `--border`.
- Foco: `outline: none`, borde `--p300` y halo al 7%.
- Error: borde `--red` y texto de ayuda en `--red` junto al campo (`@error`).
- Desde la spec 027, `.field-hint` (`--text-small`, margen `--space-1`) y `.field-hint-error`
  (`--red`) están en `app.css`, que carga el layout en todas las vistas. El mensaje lleva un `id`, y
  el campo `aria-describedby` hacia él y `aria-invalid="true"`.

### Día del horario (spec 027)
Fila de un día en el horario semanal del dentista, sin estilos en línea:
- `.horario-dia`: flex, `gap` `--space-5`, padding `--space-4` `--space-5`, radio `--radius-sm`,
  borde `--border-width` `--border`, fondo transparente. Con `.horario-dia--activo` (día marcado):
  fondo `--p50` y borde `--p200`.
- `.horario-dia__dia` (etiqueta con la casilla; casilla de `--space-6` con `accent-color` `--p400`),
  `.horario-dia__nombre` (`--text-body`, `--text-muted`; `--weight-strong` y `--text` si está
  activo), `.horario-dia__horas` (flex, `gap` `--space-3`) y `.horario-dia__nota` (`--text-small`,
  `--text-muted`, para "a", la duración y "No disponible").
- Campo de hora `.horario-dia .field`: ancho `--field-time-width`, padding `--space-2` `--space-4`,
  `--text-body` y borde `--border-control` (3.93:1).
- "No disponible" se oculta con el atributo `hidden`; el JavaScript solo cambia clases, `hidden` y
  `disabled`.

### Lista de citas (spec 027)
`.lista-citas`: filas con padding `--space-5` `--space-6`, separadas por `--border-width`
`--border`, texto `--text` (no `--text-light`), y una acción por fila con `.btn btn-outline btn-sm`.
Enlaces con el anillo `--focus-ring` en `:focus-visible`.

### Lista, tabla y tarjeta
- `.card` (blanca, borde `--border`, radio 12px, padding 22px) y `.card-0` (sin padding, para
  tablas).
- `.stat-card`, con icono, cifra y badge.
- Tablas: `th` de 10.5px en mayúsculas `--text-light`, `tr:hover` en `--p50`, separador
  `#FDF0F4`.
- `.timeline`, para el historial de la cita.

### Badge de estado
`.badge` es una píldora de 11px/500 con las variantes `pink`, `blue`, `amber`, `green`, `red` y
`gray`. `partials/badge-estado` asigna:
- agendada → pink;
- confirmada → blue;
- en curso → amber;
- completada → green;
- cancelada → red;
- no asistió → gray.

El estado se lee en texto, no solo por el color.

### Aviso, diálogo y mensajes
- **Mensajes flash del layout:** éxito (verde, se oculta a los 4 s) y error (rojo, persistente),
  con estilos en línea y sin `aria-live` (DS7). El único `role="alert"` está en `citas/show`.
- **Diálogos:** dos modales hechos a mano, el de eliminar servicio y el de activar o desactivar
  usuario. No tienen `role="dialog"`, ni manejo del foco, ni cierre con Escape.
- **Confirmaciones:** `confirm()` nativo en 5 sitios (DS7).

### Estados de pantalla
- Vacío: `.empty-state` (icono de 40px y texto de 13.5px) en 15 vistas.
- Cargando: no existe.
- Error del servidor: no hay páginas `errors/` propias (DS7).

## Movimiento

| Token | Valor | Uso |
|---|---|---|
| (sin token) | `all 0.15s` | Hover de botones, menú y accesos rápidos (`translateY` en `.quick-action`) |
| (sin token) | animaciones `fadeUp` y `modalIn` | Entrada del login y del registro, y modales |

No respeta `prefers-reduced-motion` (DS10).

## Pantallas principales

Sin capturas (decisión del usuario, 2026-10-02): `system.html` las muestra como maquetas con los
valores reales.

| Pantalla | Móvil | Escritorio | Componentes |
|---|---|---|---|
| Inicio de sesión | `system.html#login-m` | `system.html#login-d` | campos y botón de auth, botón de Google |
| Agenda del personal (listado de citas) | `system.html#citas-m` | `system.html#citas-d` | barra lateral, tarjetas de resumen, tabla, badges |
| Detalle de cita con cambio de estado | — | `system.html#detalle-d` | tarjeta, opciones de estado, campo de motivo, historial |

La app no se adapta a móvil (sin `@media` en `app.css`): la vista a 390 px muestra cómo se ve hoy
(DS8).

## Reglas de uso

- Los colores semánticos solo se usan con su significado (rojo = error o cancelada, verde = éxito
  o completada); el estado de una cita se muestra siempre con su badge, que incluye el texto.
- Pantallas nuevas o modificadas: solo tokens de este documento, sin valores literales de color,
  tamaño ni espaciado. Si falta un valor, se añade aquí primero. El código anterior no bloquea
  (regla de "Código previo"; principio propuesto en la constitución).

## Deuda de diseño

Es lo que el código hace hoy y no cumple este sistema o WCAG 2.1 AA. No bloquea el código previo:
se corrige cuando una spec toca la pantalla o como objetivo del roadmap.

| ID | Problema | Dónde (evidencia) | Corrección propuesta | Estado |
|---|---|---|---|---|
| DS1 | Texto con contraste < 4.5:1: botón principal 3.93:1, `--text-light` 3.51:1 (etiquetas, ayudas, `th`), verde de éxito 2.98:1, menú del sidebar 3.82:1, `.nav-label` 2.72:1, hover de "Cerrar sesión" 1.75:1 | `public/css/app.css:15,19-20,63,107,117,171,210`, `citas.css:12`, `.field-hint` en `app.css:427` | Oscurecer `--p400` del botón (o usar `--p500`), `--text-light` y `--green`; texto del sidebar en blanco puro; quitar `#FF0000` | pendiente |
| DS2 | Foco e indicadores invisibles: borde de campo 1.31:1, foco `--p300` 2.23:1 con halo al 7%, `outline: none` en campos y sin `:focus-visible` en botones | `public/css/citas.css:20,29`, `app.css:194-219` | Token `--color-focus` ≥ 3:1 y `:focus-visible` en botones, campos y enlaces | en curso: `--focus-ring` y `--border-control` existen; se aplican en el horario de la ficha del personal (spec 027) |
| DS3 | `--text-muted` vale lo mismo que `--text` (#000): no hay jerarquía de texto secundario | `public/css/app.css:13-14` | Definir un secundario que cumpla 4.5:1 | pendiente |
| DS4 | Valores fijos: 871 `style=""` en las vistas, 17 tamaños de letra, ninguna escala de espaciado ni de tipografía; sidebar `#c75078` y radios literales fuera de los tokens | `resources/views/**`, `public/css/app.css:63,171` | Tokens de espaciado y tipografía; migrar los estilos en línea al tocar cada pantalla | en curso: escala de espaciado, `--text-body`, `--text-small` y pesos (spec 027); filas y campos de la sección de horario de la ficha sin estilos en línea (sus envoltorios previos los conservan) |
| DS5 | Componentes duplicados o contradictorios: 14 bloques `<style>` redefinen `.field` (borde 1.5px, radio 5px, 'DM Sans'), `citas.css` solo en 7 vistas, y `auth.css` redefine tokens (`--text-light: #000`) | `expedientes/create:139`, `pacientes/create:200`, `usuarios/index:208`…, `public/css/auth.css:8-26` | Una sola hoja de componentes cargada por el layout | en curso: `.field-hint` y `.field-hint-error` pasan a `app.css` (spec 027) |
| DS6 | Objetivo de toque < 44px: botón ≈ 32px, `.btn-sm` ≈ 25px, campo ≈ 36px | `public/css/app.css:194-219,353-356`, `citas.css:16-30` | `min-height` con un token `--touch-min` | pendiente |
| DS7 | Estados y semántica que faltan: sin deshabilitado ni carga, sin páginas de error propias, mensajes flash sin `aria-live`, modales sin `role="dialog"` ni manejo del foco, 5 `confirm()` nativos, títulos de sección como `span` (un solo `h1`) | `layouts/app.blade.php:207-253`, `servicios/index:121-193`, `usuarios/index:215-299` | Componentes de aviso y diálogo accesibles y estados de carga | pendiente |
| DS8 | La app no se adapta a móvil: sin `@media`, sidebar fijo de 242px y rejillas fijas (solo auth se adapta) | `public/css/app.css` (0 `@media`), `:60-70,234,270-272` | Diseño adaptable a partir de 390px, empezando por las pantallas del paciente | pendiente |
| DS9 | Tipografía: Roboto se carga desde Google Fonts sin aprobación en `security.md`; 'DM Sans' y 'DM Mono' se nombran sin cargarse | `layouts/app.blade.php:7`, `usuarios/create:127`, `expedientes/create:110` | Autoalojar Roboto o aprobar el servicio; quitar las familias no cargadas | pendiente |
| DS10 | El movimiento no respeta `prefers-reduced-motion` | `public/css/auth.css:164,346,504-509`, `usuarios/index:295`, `app.css:374` | Bloque `prefers-reduced-motion: reduce` | pendiente |
| DS11 | Restos sin uso: Tailwind 4, Vite y `welcome.blade.php` (ninguna ruta los sirve); clases sin uso (`.logo-icon`, `.appt-card`, `.alert-item`…) | `package.json`, `vite.config.js`, `resources/views/welcome.blade.php`, `public/css/app.css:80-94,379,409-417` | Retirarlos o decidir migrar a ellos | pendiente |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-02 | diseño | ¿Documentar el diseño existente (upgrade a 1.11.0)? | Sí; sistema extraído, sin rediseño | usuario |
| 2026-10-02 | diseño | Objetivo de accesibilidad (antes "propuesto WCAG 2.1 AA") | WCAG 2.1 AA | usuario |
| 2026-10-02 | diseño | ¿Escritorio o también móvil? | Escritorio; que no se adapte a móvil es deuda (DS8); anchos [390, 1440] | usuario |
| 2026-10-02 | diseño | ¿Capturas de pantalla con la base del volcado? | Sin capturas; `system.html` usa maquetas con los valores reales | usuario |
| 2026-10-02 | diseño | ¿Se aprueba el sistema extraído? | Aprobado tal como está; la deuda DS1–DS11 sigue pendiente | usuario |
| 2026-10-02 | contradicción | `--text-light` vale `#9B8290` en `app.css` y `#000000` en `auth.css` | Se documenta como está; lo resuelve DS5 | /init --upgrade (subagente G) |
| 2026-10-03 | diseño | ¿Qué incorpora el sistema para el horario del dentista (spec 027)? | Versión 1.1.0: escala de espaciado, grosores, `--text-body`/`--text-small`, pesos, `--field-time-width`, un solo token de foco (`--focus-ring` = `--p600`), `--border-control` (`--p400`) solo en los campos de hora, componentes "Día del horario" y "Lista de citas", y `.field-hint` común | usuario (plan 027, D7, D12 y D13) |
