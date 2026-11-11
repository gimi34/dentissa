---
spec: 047-reportes-admin
status: approved         # draft | approved (solo el usuario aprueba); aprobado de nuevo por el usuario el 2026-11-11 tras /analyze r1
exploration: none        # sin ciclo de candidatos (decisión del usuario, 2026-11-10); motivo en "Resultado"
system_version: 1.13.0
created: 2026-11-10
updated: 2026-11-11
---

# Diseño · 047 Reportes de la clínica para la administración

Hecho con `/design-spec 047` en modo sin exploración, sobre `docs/design/system.md` 1.13.0. Los
criterios están en la spec («Criterios de diseño», K1–K7). No hay `design/criterios.json` ni
candidatos.

## Resultado

**Sin ronda de candidatos.** «Reportes» es una pantalla de trabajo de la administración que se arma
con piezas que el panel ya tiene: la rejilla de estadísticas, las tarjetas con tabla, el filtro con
formulario accesible y el estado vacío. El usuario pidió diseño directo (spec 047, Decisiones):
`/plan` coloca cada pieza con los componentes de abajo y el usuario la valida en pantalla durante
`/implement` a 320, 390 y 1440 px, y en la vista previa de impresión.

| Puerta o KPI | Objetivo | Cómo se comprueba |
|---|---|---|
| K1 · Periodo consultado y total de citas (CA1) | sin desplazarse a 320 (320×568), 390 y 1440 px | `capturar.mjs --medir`: la frase del periodo y la cifra «Citas en el periodo» quedan dentro de la primera pantalla a 320×568 y 390×844; verificación del usuario a 1440 |
| K2 · Selector de periodo y atajos (CA2) | teclado y dedo; toque ≥ 44 px | test sobre el HTML (etiquetas asociadas, atajos como enlaces) + `capturar.mjs --medir` |
| K3 · Tasas de cancelación e inasistencia (CA3) | en la primera pantalla, con texto y no solo color | test sobre el HTML + verificación del usuario |
| K4 · Las tres tablas (CA3, CA4, CA5) | completas a 320 px sin desplazamiento horizontal; cada cifra con su encabezado | `capturar.mjs --medir` (sin desborde; toda tabla visible es `tabla-tarjetas`, con su etiqueta en cada cifra) + test de encabezados |
| K5 · «No hay citas en este periodo» (CA6) | mensaje visible y selector disponible a los tres anchos | test sobre el HTML + verificación del usuario |
| K6 · Periodo no válido (CA7) | junto al selector, con texto, anunciado | test sobre el HTML (`role="alert"`, `aria-invalid`, `aria-describedby`) |
| K7 · Hoja impresa (CA8) | sin menú ni controles, con clínica, periodo y fecha, legible en blanco y negro | verificación manual del usuario en la vista previa de impresión + test de que la cabecera impresa está en el HTML |
| Puertas | contraste AA, foco visible, toque ≥ 44 px, sin desborde a 320 px | revisión contra `system.html` en `/review` y `capturar.mjs --medir` |

## Imágenes de muestra

Ninguna.

## Pantallas

Una sola pantalla, «Reportes» (`admin`), dentro del layout de la aplicación. De arriba abajo en
escritorio (por debajo de 900 px, la frase del periodo y la cifra «Citas en el periodo» se muestran
antes del filtro; el orden del HTML y del foco no cambia, porque ese bloque no tiene controles):

1. **Periodo:** el filtro con «Desde», «Hasta», «Ver reporte» y los atajos «Este mes», «Mes pasado» y
   «Este año», y debajo la frase del periodo consultado («Del 1 al 30 de noviembre de 2026»).
2. **Resumen:** tres cifras, «Citas en el periodo», «Cancelación» e «Inasistencia» (las dos tasas con
   su porcentaje y, debajo, «n de m citas»).
3. **Citas por estado:** tabla de seis filas (estado con su badge, citas, porcentaje).
4. **Citas por dentista:** tabla (dentista, total, completadas, canceladas, no asistió) con fila de
   totales.
5. **Servicios más solicitados:** tabla (posición, servicio, citas, porcentaje).
6. Botón «Imprimir» junto al título de la página.

| Pantalla y estado | Ancho menor (320/390) | Ancho mayor (1440) | Criterios |
|---|---|---|---|
| Con datos | una columna: frase del periodo y «Citas en el periodo» primero, después el filtro, las dos tasas lado a lado y las tres tablas una debajo de otra, las tres como tarjetas (`tabla-tarjetas`: una tarjeta por estado, por dentista y por servicio, cada cifra con su etiqueta; los totales, en una última tarjeta «Total») | periodo en una fila; resumen en tres columnas; «por estado» y «servicios» lado a lado y «por dentista» a lo ancho | CA1–CA5 |
| Periodo sin citas | periodo y, en lugar del resumen y las tablas, un solo estado vacío «No hay citas en este periodo» | igual | CA6 |
| Periodo no válido | el aviso y el error bajo su campo; sin resumen ni tablas | igual | CA7 |
| Impresa | — | cabecera solo con texto: «Melissa López N. · Odontología integral», «Reportes», el periodo y «Generado el …» (sin imagen: el logo del menú no se ve sobre blanco); resumen y las tres tablas en una columna, en negro sobre blanco; sin barra lateral, filtro, atajos ni «Imprimir» | CA8 |

## Componentes

- **Del sistema:**
  - Layout de la aplicación con la entrada «Reportes» de la barra lateral (ya existe) y el menú del
    móvil (spec 033).
  - Filtro: `.formulario-accesible` (028) con campos de fecha y el patrón de error del filtro de
    auditoría (020): aviso con `role="alert"`, error bajo su campo en `--red`, `aria-invalid` y
    `aria-describedby`, foco del campo de fecha con `:focus-within`. Botones `btn-primary` («Ver
    reporte») y `btn-outline` con borde `--border-control` (atajos e «Imprimir»), con `--touch-min` a
    todos los anchos. El atajo del periodo en pantalla lleva `aria-current="true"`.
  - Resumen: `.stats-grid` con `.stats-grid-3` (034) y `.stat-card` (cifra y etiqueta).
  - Tablas: `.card-0` con tabla; `th` en `--p600` como la tabla de auditoría (020), porque los `th`
    globales no cumplen AA (DS1); cifras con números tabulares y alineadas a la derecha.
  - Tablas que pasan a tarjetas por debajo de 900 px: `tabla-tarjetas` (034) en las tres.
  - Estado: `partials/badge-estado` en «Citas por estado» (el estado se lee en el texto).
  - Vacío: `.empty-state` con `role="status"`.
- **Nuevos (ver Cambios):** frase del periodo consultado, fila de totales de tabla, cabecera impresa
  y reglas de impresión.

## Cambios a incorporar al sistema

Ningún token nuevo: todo sale de los tokens de 1.13.0. Componentes y reglas que la primera tarea
añade a `system.md` y `system.html` (versión 1.14.0):

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Frase del periodo (`.reporte-periodo`) | fijo | texto `--text` en `--text-meta`, `--weight-strong`, bajo el filtro | — | CA1, CA2: el periodo consultado siempre escrito, también impreso |
| Fila de totales de tabla (`tfoot`) | fijo | `--weight-strong`, borde superior 1px `--border`, etiqueta «Total» como encabezado de fila | — | CA4 |
| Tasa en `.stat-card` | fijo | porcentaje como cifra, etiqueta («Cancelación», «Inasistencia») y línea «n de m citas» en `--text-meta`; sin color de alarma: el dato se lee en el texto | — | CA3, K3 |
| Cabecera impresa (`.solo-impresion`) | fijo | oculta en pantalla; al imprimir, solo texto: nombre de la clínica, «Reportes», periodo y «Generado el …» | — | CA8 |
| Reglas de impresión del layout | fijo | al imprimir se ocultan la barra lateral, la barra superior, el filtro y los botones; fondo blanco, texto negro, tarjetas sin sombra y con borde; las filas no se parten entre páginas | — | CA8; primera hoja de impresión del sistema |
| Resumen antes del filtro en el móvil | punto de corte 900 px | la frase del periodo y «Citas en el periodo» se muestran antes del filtro | el filtro primero | K1: el periodo y el total, en la primera pantalla a 320×568 |
| Fila de totales en `tabla-tarjetas` | punto de corte 900 px | el `tfoot` se muestra como una última tarjeta «Total», con la etiqueta de cada cifra | fila de la tabla | CA4: `tabla-tarjetas` no contemplaba `tfoot` |

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** pares ya medidos del sistema (`--text` sobre `--white`, `--p600` en los
  `th`, botones con borde `--border-control`); controles con `--touch-min`; foco visible
  del sistema. El badge «completada» no llega a AA (DS1): en «Citas por estado» el nombre del estado
  va además como texto de la celda, no solo en el badge.
- **Encabezados y estructura:** un `h1` «Reportes» y un `h2` por reporte; cada tabla con `caption`
  (o `aria-labelledby` a su `h2`), `th scope="col"` en las columnas y `th scope="row"` en la primera
  celda de cada fila, también en las tarjetas del móvil.
- **Teclado:** orden filtro («Desde», «Hasta», «Ver reporte»), atajos, «Imprimir» y contenido. Los
  atajos son enlaces, no dependen de JavaScript; «Imprimir» sí lo necesita y sin él no se muestra
  (la página se imprime igual desde el navegador).
- **Estados:** el vacío en `role="status"`; el periodo no válido en `role="alert"` con el error
  asociado a su campo.
- **Decorativos ocultos:** iconos con `aria-hidden="true"`.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-10 | diseño | Ciclo de candidatos | Sin exploración (`exploration: none`): componentes del sistema y validación en pantalla del usuario | usuario |
| 2026-11-10 | diseño | ¿Gráficas? | No: tablas y cifras destacadas (spec 047, Fuera de alcance) | docs/specs/047-reportes-admin/spec.md |
| 2026-11-10 | diseño | «Citas por dentista» a 320 px, con cinco columnas | Tarjetas por dentista con `tabla-tarjetas` (034), cada cifra con su etiqueta | razonable (K4; `docs/design/system.md` → «Tarjeta de lista en el móvil») |
| 2026-11-10 | diseño | ¿Las tasas llevan color de alarma? | No: no hay umbral definido de qué tasa es mala; cifra y texto | razonable (K3) |
| 2026-11-10 | diseño | Encabezados de tabla | `--p600`, como la tabla de auditoría, para no añadir deuda DS1 | `docs/design/system.md` → «Tabla de auditoría» |
| 2026-11-11 | diseño | /analyze A8: `capturar.mjs --medir` exige que toda tabla visible sea `tabla-tarjetas` | Las tres tablas pasan a tarjetas por debajo de 900 px; el `tfoot`, como última tarjeta | /analyze A8 |
| 2026-11-11 | diseño | /analyze A9: nombre y logo de la hoja impresa | Solo texto, «Melissa López N. · Odontología integral» | usuario |
| 2026-11-11 | refinado | Tras la verificación manual de `/implement`: el usuario prefiere «Descargar» a «Imprimir» | El botón que este documento llama «Imprimir» se rotula «Descargar», con icono de descarga; hace lo mismo (abre el diálogo de impresión del navegador, donde se guarda como PDF o se imprime). Mismo componente, lugar y orden de foco; sin archivo generado por el servidor ni dependencias nuevas | usuario |
| 2026-11-11 | diseño | /analyze A10: K1 a 320 px | Se mide a 320×568; por debajo de 900 px la frase del periodo y el total van antes del filtro, sin cambiar el orden del HTML ni del foco | usuario |
| 2026-11-11 | diseño | /analyze A20: ¿a qué anchos se exige el objetivo de toque? | A todos: los controles de «Reportes» llevan `--touch-min` también en escritorio | /analyze A20 |
