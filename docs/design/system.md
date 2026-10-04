---
status: approved         # re-extraído el 2026-10-04 y aprobado por el usuario el 2026-10-04
source: extracted        # documentado del código y de capturas de la app real
version: 1.2.0
extracted_with: 1.11.2   # re-extracción con /init --upgrade --redo-design (AI-DD 1.11.2)
html: docs/design/system.html
canvas: null
widths: [390, 1440]
created: 2026-10-02
updated: 2026-10-04
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
| `--green` / `--green-bg` | `#1D9E75` / `#E1F5EE` | `--color-success` | Badge "Completada", flash de éxito, cifra "Completadas" | 2.98:1 sobre --green-bg · 3.39:1 sobre --white (no cumple, DS1) |
| `--amber` / `--amber-bg` | `#854F0B` / `#FAEEDA` | `--color-warning` | Badge "En curso", avisos | 5.87:1 sobre --amber-bg |
| `--blue` / `--blue-bg` | `#185FA5` / `#E6F1FB` | `--color-info` | Badge "Confirmada" | 5.70:1 sobre --blue-bg |
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
| (sin token) | no aplica | — | `'DM Sans'` (usuarios) y `'DM Mono'` (receta del expediente) se nombran sin cargarse: los campos de usuarios caen a la sans-serif genérica (medido, DS9) |
| (sin token) | fuente del navegador | — | Los campos de servicios (crear y editar) se ven en Arial de 13.33px porque su `.field` local no declara familia (medido, DS12) |

Tokens de tamaño (spec 027): `--text-body` (13.5px) y `--text-small` (11.5px). **Son tamaños** y
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
- **Campo de hora:** `--field-time-width` (110px). Recorta el texto "AM"/"PM" con el formato de
  12 h del navegador (medido, DS13).
- **Toque mínimo: no definido.** Alturas medidas (DS6):
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
- **Peligro:** no existe un botón de peligro. Se hace en línea con `color: var(--red)` y
  `background: var(--red-bg)`, como "Desactivar" en la ficha del personal.

### Campo de formulario
- `.field`, `.field-label` y `.field-error` están en `public/css/citas.css`, que solo cargan 7
  vistas. Otras 11 los redefinen en bloques `<style>` dentro del contenido (DS5).
- Medido:
  - etiqueta de 11px/600 en mayúsculas, color `--text-light`;
  - campo de 13.5px y 34–38px de alto, borde 1px `--border`, radio 8px;
  - en servicios, 13.33px Arial y radio 5px, con el desplegable en gris (DS12).
- Foco: `outline: none`, borde `--p300` y halo al 7%.
- Error: borde `--red` y texto de ayuda en `--red` junto al campo.
- `.field-hint` y `.field-hint-error` (spec 027) están en `app.css`, con `aria-describedby` y
  `aria-invalid` solo en el horario.
- Acceso: campos de 14px, de 40–42px de alto y radio 10px, con icono a la izquierda, foco en
  `--p300` y error en `--red`.

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
| (sin token) | `all 0.15s` | Hover de botones, menú y accesos rápidos (`translateY(-1px)` en `.quick-action`) |
| (sin token) | animaciones `fadeUp` y `modalIn` | Entrada del acceso y del registro, y diálogos |

No respeta `prefers-reduced-motion` (DS10).

## Pantallas principales

Todas son capturas reales (galería completa en `system.html` → Pantallas).

| Pantalla | Móvil | Escritorio | Componentes |
|---|---|---|---|
| Inicio de sesión | `system.html#auth-login-390` | `system.html#auth-login-1440` | campos y botón del acceso, botón de Google |
| Panel del administrador | `system.html#admin-dashboard-390` | `system.html#admin-dashboard-1440` | barra lateral, tarjetas de resumen, tabla, badges, accesos rápidos |
| Citas (agenda del personal) | `system.html#citas-index-admin-390` | `system.html#citas-index-admin-1440` | filtros, tabla, badges |
| Ficha de la dentista con horario | `system.html#personal-show-dentista-390` | `system.html#personal-show-dentista-1440` | día del horario, lista de citas, tabla |
| Panel del paciente | `system.html#cliente-dashboard-390` | `system.html#cliente-dashboard-1440` | banner, próxima cita, historial, accesos |

A 390 px, 52 de las 60 vistas se desbordan en horizontal: la página mide de 472 a 1157 px de ancho
(DS8). Solo el acceso, el registro, los errores, los reportes y la edición del paciente por la
dentista no se desbordan.

## Inventario de vistas

Todas las vistas de la app (rutas, plantillas, diálogos y pasos sin URL propia), capturadas el
2026-10-04 con Playwright 1.63.0 sobre una base de datos **solo de prueba**: el volcado ficticio
anonimizado, una cuenta por rol `<rol>1@dentissa.test` y citas, un expediente y un horario de
prueba. El guion es [capturar.mjs](capturar.mjs). Resultado: **60 de 65** vistas y estados
capturados; los 5 restantes no son accesibles sin Google o son avisos transitorios.

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
| Pacientes | /admin/pacientes · pacientes/index | admin | capturas/pacientes-index-admin-390.png · capturas/pacientes-index-admin-1440.png | capturada |
| Nuevo paciente | /admin/pacientes/crear · pacientes/create | admin | capturas/pacientes-create-admin-390.png · capturas/pacientes-create-admin-1440.png | capturada |
| Ficha del paciente | /admin/pacientes/{id} · pacientes/show | admin | capturas/pacientes-show-admin-390.png · capturas/pacientes-show-admin-1440.png | capturada |
| Editar paciente | /admin/pacientes/{id}/edit · pacientes/edit → create | admin | capturas/pacientes-edit-admin-390.png · capturas/pacientes-edit-admin-1440.png | capturada |
| Nuevo expediente | /admin/expedientes/crear?paciente_id={id} · expedientes/create | admin | capturas/expedientes-create-admin-390.png · capturas/expedientes-create-admin-1440.png | capturada |
| Expediente | /admin/expedientes/{id} · expedientes/show | admin | capturas/expedientes-show-admin-390.png · capturas/expedientes-show-admin-1440.png | capturada |
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
| Ficha del paciente (dentista) | /dentista/pacientes/{id} · pacientes/show | dentista | capturas/pacientes-show-dentista-390.png · capturas/pacientes-show-dentista-1440.png | capturada |
| Editar paciente (dentista) | /dentista/pacientes/{id}/edit · pacientes/edit | dentista | capturas/pacientes-edit-dentista-390.png · capturas/pacientes-edit-dentista-1440.png | capturada |
| Expedientes | /dentista/expedientes · expedientes/index | dentista | capturas/expedientes-index-dentista-390.png · capturas/expedientes-index-dentista-1440.png | capturada |
| Nuevo expediente (dentista) | /dentista/expedientes/crear?paciente_id={id} · expedientes/create | dentista | capturas/expedientes-create-dentista-390.png · capturas/expedientes-create-dentista-1440.png | capturada |
| Expediente (dentista) | /dentista/expedientes/{id} · expedientes/show | dentista | capturas/expedientes-show-dentista-390.png · capturas/expedientes-show-dentista-1440.png | capturada |
| Panel de la asistente | /asistente · dashboard/asistente | asistente | capturas/asistente-dashboard-390.png · capturas/asistente-dashboard-1440.png | capturada |
| Mis doctores | /asistente/doctores · personal/mis-doctores | asistente | capturas/asistente-doctores-390.png · capturas/asistente-doctores-1440.png | capturada |
| Citas (asistente) | /asistente/citas · citas/index | asistente | capturas/citas-index-asistente-390.png · capturas/citas-index-asistente-1440.png | capturada |
| Nueva cita (asistente) | /asistente/citas/crear · citas/create | asistente | capturas/citas-create-asistente-390.png · capturas/citas-create-asistente-1440.png | capturada |
| Detalle de cita (asistente) | /asistente/citas/{id} · citas/show | asistente | capturas/citas-show-asistente-390.png · capturas/citas-show-asistente-1440.png | capturada |
| Pacientes (asistente) | /asistente/pacientes · pacientes/index | asistente | capturas/pacientes-index-asistente-390.png · capturas/pacientes-index-asistente-1440.png | capturada |
| Nuevo paciente (asistente) | /asistente/pacientes/crear · pacientes/create | asistente | capturas/pacientes-create-asistente-390.png · capturas/pacientes-create-asistente-1440.png | capturada |
| Ficha del paciente (asistente) | /asistente/pacientes/{id} · pacientes/show | asistente | capturas/pacientes-show-asistente-390.png · capturas/pacientes-show-asistente-1440.png | capturada |
| Expediente (asistente, solo lectura) | /asistente/expedientes/{id} · expedientes/show | asistente | capturas/expedientes-show-asistente-390.png · capturas/expedientes-show-asistente-1440.png | capturada |
| Panel del paciente | /cliente · dashboard/cliente | cliente | capturas/cliente-dashboard-390.png · capturas/cliente-dashboard-1440.png | capturada |
| Mis citas (próximas) | /cliente/citas · citas/cliente-index | cliente | capturas/citas-index-cliente-390.png · capturas/citas-index-cliente-1440.png | capturada |
| Mis citas (pasadas) | /cliente/citas?filtro=pasadas · citas/cliente-index | cliente | capturas/citas-index-cliente-pasadas-390.png · capturas/citas-index-cliente-pasadas-1440.png | capturada |
| Detalle de mi cita | /cliente/citas/{id} · citas/cliente-show | cliente | capturas/citas-show-cliente-390.png · capturas/citas-show-cliente-1440.png | capturada |
| Agendar cita | /cliente/agendar · citas/cliente-agendar | cliente | capturas/cliente-agendar-390.png · capturas/cliente-agendar-1440.png | capturada |
| Mi perfil | /cliente/perfil · cliente/perfil | cliente | capturas/cliente-perfil-390.png · capturas/cliente-perfil-1440.png | capturada |
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

## Deuda de diseño

Es lo que el código hace hoy y no cumple este sistema o WCAG 2.1 AA. No bloquea el código previo:
se corrige cuando una spec toca la pantalla o como objetivo del roadmap. Se concilió con la versión
1.1.0 el 2026-10-04: los IDs que siguen ocurriendo se conservan y lo nuevo empieza en DS12.

| ID | Problema | Dónde (evidencia) | Corrección propuesta | Estado |
|---|---|---|---|---|
| DS1 | Texto con contraste < 4.5:1 (medido): botón principal 3.93:1, etiquetas y `th` en `--text-light` 3.51:1, verde de éxito 2.98:1, menú de la barra lateral 3.82:1, `.nav-label` 2.72:1, hover de "Cerrar sesión" (`--p600` sobre `#FF0000`) 1.75:1 | `public/css/app.css:15,19-20,63,107,117,171,210`, `citas.css:12`; capturas `admin-dashboard-1440`, `citas-index-admin-1440` | Oscurecer `--p400` del botón (o usar `--p500`), `--text-light` y `--green`; texto del menú en blanco puro; quitar `#FF0000` | pendiente |
| DS2 | Foco e indicadores invisibles: borde de campo 1.31:1, foco `--p300` 2.23:1 con halo al 7%, `outline: none` en campos y sin `:focus-visible` en botones | `public/css/citas.css:20,29`, `app.css:194-219`, `auth.css:204-221` | Token `--color-focus` ≥ 3:1 y `:focus-visible` en botones, campos y enlaces | en curso: `--focus-ring` y `--border-control` existen; se aplican en el horario de la ficha del personal (spec 027) |
| DS3 | `--text-muted` vale lo mismo que `--text` (#000): no hay jerarquía de texto secundario (medido: todo el texto secundario sale en negro) | `public/css/app.css:13-14` | Definir un secundario que cumpla 4.5:1 | pendiente |
| DS4 | Valores fijos: 861 `style=""` en las vistas, 20 tamaños de letra, escala de espaciado solo en las reglas de la 027; barra lateral `#C75078` y radios literales fuera de los tokens | `resources/views/**`, `public/css/app.css:63,171` | Tokens de espaciado y tipografía; migrar los estilos en línea al tocar cada pantalla | en curso: escala de espaciado, `--text-body`, `--text-small` y pesos (spec 027); filas y campos de la sección de horario de la ficha sin estilos en línea |
| DS5 | Componentes duplicados o contradictorios: 11 bloques `<style>` dentro del contenido redefinen `.field` (borde 1.5px, radio 5px, 'DM Sans'), `citas.css` solo en 7 vistas, y `auth.css` redefine tokens (`--text-light: #000`) | `expedientes/create:139`, `pacientes/create:200`, `usuarios/index:208`…, `public/css/auth.css:8-26` | Una sola hoja de componentes cargada por el layout | en curso: `.field-hint` y `.field-hint-error` pasan a `app.css` (spec 027) |
| DS6 | Objetivo de toque < 44px (medido): botón 31px, `.btn-sm` 22–27px, campo 34–38px, elemento del menú 35px | `public/css/app.css:194-219,353-356`, `citas.css:16-30`; medidas de las 104 vistas con menú | `min-height` con un token `--touch-min` | pendiente |
| DS7 | Estados y semántica que faltan: sin deshabilitado ni carga, sin páginas de error propias (403 y 404 de Laravel en inglés), mensajes flash sin `aria-live`, diálogos sin `role="dialog"` ni manejo del foco, 5 `confirm()` nativos, títulos de sección como `span` | `layouts/app.blade.php:207-253`, `servicios/index:121-193`, `usuarios/index:215-299`; capturas `error-403`, `error-404` | Componentes de aviso y diálogo accesibles, estados de carga y páginas de error con la marca | pendiente |
| DS8 | La app no se adapta a móvil (medido): a 390 px, 52 de 60 vistas se desbordan en horizontal (472–1157px de ancho), con barra lateral fija de 242px y rejillas fijas; solo el acceso y el registro se adaptan | `public/css/app.css` (0 `@media`), `:60-70,234,270-272`; capturas `*-390` | Diseño adaptable a partir de 390px, empezando por las pantallas del paciente | pendiente |
| DS9 | Tipografía: Roboto se carga desde Google Fonts sin aprobación en `security.md`; 'DM Sans' y 'DM Mono' se nombran sin cargarse (medido: los campos de usuarios caen a la sans-serif genérica); el peso 300 se carga y no se usa | `layouts/app.blade.php:7`, `usuarios/create:127`, `usuarios/index:210`, `servicios/index:37`, `expedientes/create:110` | Autoalojar Roboto o aprobar el servicio; quitar las familias no cargadas y los pesos sin uso | pendiente |
| DS10 | El movimiento no respeta `prefers-reduced-motion` | `public/css/auth.css:164,346,504-509`, `usuarios/index:295`, `app.css:374` | Bloque `prefers-reduced-motion: reduce` | pendiente |
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
