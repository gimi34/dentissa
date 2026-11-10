---
spec: 044-navegacion-salidas
status: approved            # draft | approved (solo el usuario aprueba)
exploration: none        # sin ciclo de candidatos (decisión del usuario, 2026-11-08); motivo en "Resultado"
system_version: 1.11.0
created: 2026-11-08
updated: 2026-11-08
---

# Diseño · 044 Salidas de las pantallas

Hecho con `/design-spec 044` en modo sin exploración, sobre `docs/design/system.md` 1.11.0. Los
criterios están en la spec («Criterios de diseño», K1–K5). No hay `design/criterios.json` ni
candidatos.

## Resultado

**Sin diseño nuevo.** La spec coloca enlaces y un aviso dentro de pantallas ya diseñadas: el acceso
de la 041, el registro, la barra lateral de la 033/034 y las páginas públicas de la 038. Las páginas
de error reutilizan la carcasa del «error genérico» de la spec 020 (`errors/auditoria-no-disponible`). El usuario pidió diseño directo, sin ronda de
candidatos (spec 044, Decisiones): `/plan` coloca cada pieza con los componentes de abajo, y el
usuario lo valida en pantalla durante `/implement` a 320, 390 y 1440 px.

| Puerta o KPI | Objetivo | Cómo se comprueba |
|---|---|---|
| K1 · «Volver al inicio» en el acceso (CA1) | sin desplazarse a 320, 390 y 1440 px; toque ≥ 44 px | es lo primero del formulario (test sobre el HTML) y lo ve el usuario a los tres anchos |
| K2 · Logo enlace (CA2) | nombre accesible «Ir a la página de inicio» | test sobre el HTML |
| K3 · «Página de inicio» en el panel (CA5) | cuatro roles, escritorio y menú del móvil | test por rol + `capturar.mjs --medir` |
| K4 · Aviso «Has cerrado sesión» (CA6) | en la primera pantalla, `role="status"` | test sobre el HTML + verificación del usuario |
| K5 · Páginas de error (CA7–CA10) | en español, con la marca y una salida a los tres anchos | tests por código + verificación del usuario |
| Puertas | contraste AA, foco visible, toque ≥ 44 px, sin desborde a 320 px | revisión contra `system.html` en `/review` y `capturar.mjs --medir` |

## Imágenes de muestra

Ninguna.

## Pantallas

| Pantalla y estado | Ancho menor (320/390) | Ancho mayor (1440) | Criterios |
|---|---|---|---|
| Inicio de sesión | «← Volver al inicio» encima del título del formulario (el panel de marca no se ve) | el mismo enlace arriba del formulario; el logo del panel de marca es enlace | CA1, CA2 |
| Recuperación (pedir enlace, enviado, caducado, contraseña nueva) | «← Volver a iniciar sesión» encima del título, sin repetirlo abajo | igual; el logo del panel de marca lleva a la página de inicio | CA1, CA2, CA3 |
| Registro y completar el registro con Google | «← Volver al inicio» encima del formulario | igual; el logo del panel izquierdo es enlace | CA1, CA2, CA4 |
| Barra lateral del panel (cuatro roles) | dentro del menú del móvil, al final de las secciones | al final de las secciones, bajo la etiqueta «Sitio» | CA5 |
| Página de inicio con sesión | cabecera con «Ir a mi panel» (ya existe); los botones de cuenta del cuerpo llevan al panel | igual | CA5 |
| Página de inicio tras cerrar sesión | aviso «Has cerrado sesión» bajo la cabecera, encima de la portada | igual | CA6 |
| Páginas de error (404, 403, 419, 429, 500) | carcasa del error genérico (spec 020): franja con el logo, etiqueta «Error NNN · …», `h1`, una frase, la acción principal y los enlaces secundarios debajo | igual, con el panel de marca | CA7–CA10 |

### Qué ve quien tiene sesión en la página de inicio (CA5)
| Elemento | Sin sesión (hoy) | Con sesión |
|---|---|---|
| Cabecera y menú del móvil | «Iniciar sesión» · «Crear cuenta» | «Ir a mi panel» (ya existe, spec 038) |
| Enlaces de la portada (`.pub-acceso`) | «Iniciar sesión» · «Crear cuenta» | un solo «Ir a mi panel» |
| Botones «Agendar mi primera cita» y «Crear mi cuenta» | llevan al registro | «Ir a mi panel», al panel de su rol |
| Aviso «Has cerrado sesión» | solo justo después de cerrar sesión | — |

### Salidas de cada página de error
| Página | `h1` | Acción principal | Enlaces secundarios |
|---|---|---|---|
| 404 | No encontramos esta página | «Ir a la página de inicio» | sin sesión: «Iniciar sesión» · «Crear cuenta»; con sesión: «Ir a mi panel» |
| 403 | No tienes acceso a esta página | «Ir a mi panel» | «Ir a la página de inicio»; la explicación concreta del permiso, si la hay, como frase |
| 419 | La página caducó | «Volver a iniciar sesión» | «Ir a la página de inicio» |
| 429 | Demasiados intentos | «Ir a la página de inicio» | frase «Espera unos minutos y vuelve a probar» |
| 500 | Algo salió mal | «Ir a la página de inicio» | frase «Vuelve a intentarlo más tarde» |

## Componentes

- **Del sistema:**
  - Acceso (spec 041): panel de marca, formulario, `.form-header` con un solo `h1`, `.btn-submit` como
    acción principal (enlace con el aspecto del botón primario del acceso), `.forgot-link` y
    `.enlace-volver` para los enlaces secundarios, `.talon`.
  - Barra lateral: `.nav-label` y `.nav-item` con icono de 1,8 de trazo, como las demás entradas; en el
    móvil, el mismo elemento dentro del menú (spec 033).
  - Páginas públicas (spec 038): `.c-btn` (las variantes que ya usa cada botón) y la cabecera con
    «Ir a mi panel».
  - Error genérico (spec 020, `errores.css`): franja del teléfono, panel de marca, `.carnet` con
    `.etiqueta`, `h1`, `.sub`, `.codigo` (identificador de la petición) y `.btn-primario`, `.talon`.
- **Nuevos (ver Cambios):** enlace «Volver al inicio» del acceso y del registro; logo-enlace del
  panel de marca; aviso «Has cerrado sesión» del sitio público; plantilla común de las páginas de error
  y su enlace secundario.

## Cambios a incorporar al sistema

Ningún token nuevo: todo sale de los tokens de 1.11.0. Componentes y reglas que la primera tarea
añade a `system.md` y `system.html` (versión 1.12.0):

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Enlace «Volver al inicio» (`.enlace-inicio`, acceso y registro) | fijo | icono flecha `--icon-md` + texto en `--text-meta`, `--weight-strong`, `--p500` (hover `--p600` subrayado), alto `--touch-min`, margen inferior `--space-4`; primer elemento del formulario | — | CA1: visible sin desplazarse en todas las pantallas, también donde el panel de marca no se ve |
| Logo-enlace del panel de marca | fijo | el logo dentro de un enlace con `aria-label="Ir a la página de inicio"` (la imagen con `alt=""`); foco con el anillo de foco del sistema | — | CA2 |
| Entrada «Página de inicio» de la barra lateral | fijo | `.nav-label` «Sitio» + `.nav-item` con icono de casa, al final de `.sidebar-nav` | — | CA5 |
| Aviso del sitio público (`.pub-aviso`) | fijo | franja bajo la cabecera, fondo `--p50`, borde inferior `--border`, texto `--text` en `--text-publico-small`, icono de palomita `--p600`, relleno `--space-3`/`--space-4`; `role="status"`; no se cierra solo | — | CA6 |
| Página de error (plantilla común sobre el error genérico de la 020) | fijo | `.etiqueta` «Error NNN · …», `h1`, `.sub`, acción principal `.btn-primario`; `.codigo` con el identificador de la petición solo en la 500; el logo de la franja y del panel como enlace a la página de inicio | — | CA7–CA10; cierra la parte de páginas de error de DS7 |
| Enlace secundario del error (`.enlace-secundario`, `errores.css`) | fijo | `--text-meta`, `--weight-strong`, `--p500` (hover `--p600` subrayado), alto `--touch-min`, en fila con `--space-4` de separación y en columna por debajo de 400 px | — | CA7, CA8: «Iniciar sesión», «Crear cuenta», «Ir a mi panel» |

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** pares ya medidos del sistema (`--p500` sobre `--white` y sobre `--p50`
  en el acceso; `--text` sobre `--p50` en el aviso); los enlaces nuevos con `--touch-min`; foco
  visible del sistema. Se revisan en `/review` contra `system.html` y con `capturar.mjs --medir`.
- **Encabezados y estructura:** cada página de error con un solo `h1`; el aviso de sesión cerrada no
  es un encabezado y se anuncia con `role="status"` sin mover el foco.
- **Teclado:** «Volver al inicio» es el primer enlace del formulario (después del logo en el
  escritorio); en la barra lateral, «Página de inicio» va después de las secciones del rol y antes de
  «Cerrar sesión».
- **Decorativos ocultos:** iconos con `aria-hidden="true"`; la imagen del logo-enlace con `alt=""`.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-08 | diseño | Ciclo de candidatos | Sin exploración (`exploration: none`): componentes del sistema y validación en pantalla del usuario | usuario (spec 044) |
| 2026-11-08 | diseño | Dónde va «Volver al inicio» en el móvil, donde el panel de marca no se ve | Primer elemento del formulario, a todos los anchos; el logo-enlace es un extra del escritorio | razonable (CA1) |
| 2026-11-08 | diseño | Aspecto de las páginas de error | La carcasa del error genérico de la 020: ya es la página de error de la marca, lleva `noindex`, solo usa recursos propios y no necesita sesión ni base de datos | `docs/specs/020-…/design.md` (c4/error.html), RNF de la spec |
| 2026-11-09 | refinado | Salida de arriba en la recuperación de contraseña (verificación de T020) | «← Volver a iniciar sesión», con el mismo componente `.enlace-inicio`; se quita el «Volver a iniciar sesión» de abajo | usuario |
| 2026-11-08 | diseño | Botones de cuenta de la página de inicio con sesión | Pasan a «Ir a mi panel», para que ningún texto prometa registrarse a quien ya tiene cuenta | razonable (CA5; contracts-by-type → «Qué ve cada rol») |
