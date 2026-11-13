---
spec: 045-terminos-privacidad
status: approved           # draft | approved (solo el usuario aprueba)
exploration: loop        # loop: ciclo por criterios · none: sin diseño nuevo (motivo en "Resultado")
threshold: 80            # puntuación mínima de la rúbrica (de .ai/project.yaml → design)
floor: 3                 # nota mínima de cada punto de la rúbrica
budget: 5                # candidatos como máximo
accepted: c4             # candidato aceptado
score: 82                # puntuación del aceptado
system_version: 1.14.0
created: 2026-11-12
updated: 2026-11-12
---

# Diseño · 045 Términos de uso y aviso de privacidad

Hecho con `/design-spec 045` sobre `docs/design/system.md` (1.14.0). Criterios en la spec ("Criterios de
diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los descartados se conservan).

Cada candidato es una carpeta con dos páginas, `terminos.html` y `privacidad.html`, sobre el layout
público real (cabecera y pie de `layouts/publico`, con los dos enlaces nuevos del pie) y la composición de
`/servicios`; enlazan `public/css` y `public/js` reales, y lo nuevo va en `legal.css`. Se miden pantalla por
pantalla con `design/medir.mjs` (copia del de la 039). Desde c3 se miden **servidas por HTTP**, como la app
(`design/servir.mjs`), porque la máscara CSS del logo no carga desde `file://`; como `evaluate.mjs` no
comprueba `G-red` con una URL, cada candidato se mide además desde el archivo (12/12 puertas, `G-red`
incluida). El texto de las dos páginas es el mismo en todos los candidatos salvo los retoques anotados.

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 24/24 | 8/10 | — | descartado: K2 en términos (la entrada «…y tu cuenta de paciente» contenía «Tu cuenta» antes que la primera sección); K6 en el aviso (CLS 0,115 a 1440 px, por el cambio de fuente de Newsreader en los títulos) | `.ai/inspect/045-c1/` |
| `c2` | 24/24 | 10/10 | 70 | descartado: `plantilla` 2 (bajo el mínimo); cabecera sin logo en las capturas (medida desde `file://`), índice de 8 píldoras que tapa la primera pantalla a 320 px, índice sin orientación | `.ai/inspect/045-c2/` |
| `c3` | 22/22 (+ `G-red` desde el archivo) | 10/10 | 77 | descartado: bajo el umbral; índice del móvil en enlaces sueltos (fuera del sistema), «En pocas palabras» empujaba el índice fuera de la primera pantalla a 320 px, fecha pequeña, píldora partida y filete más largo que el texto | `.ai/inspect/045-c3/` |
| `c4` | 22/22 (+ `G-red` desde el archivo) | 10/10 | 82 | **aceptado** | `.ai/inspect/045-c4/` |

## Resultado

**c4**, aceptado con **82/100** (umbral 80, ningún punto bajo 3). Páginas:
`design/candidatos/c4/terminos.html` y `design/candidatos/c4/privacidad.html`, con `c4/legal.css`.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| Puertas (`G-desborde`, `G-contraste`, `G-toque`, `G-foco`, `G-tokens`, `G-h1`, `G-solapamiento`, `G-aparece`, `G-imagenes`, `G-reducido`, `G-sin-js`, `G-red`) | las de `criterios.md` §2 | sin fallos a 320, 390 y 1440 px en las dos páginas | ✅ |
| K1 · Título y primer párrafo en la primera pantalla (modo leer) | a la vista a 320, 390 y 1440 | a la vista en las dos páginas | ✅ |
| K2 · Secciones en orden y fecha (CA1, CA2) | «Última actualización» y los títulos de sección, en orden | en orden en las dos páginas | ✅ |
| K3 · Lista de secciones al principio | un enlace por sección, antes de la primera | «En esta página» con 7 y 8 enlaces, antes de la sección 01 (comprobado en las capturas; `evaluate.mjs` no lo mide) | ✅ manual |
| K4 · Largo de línea | ≤ 80 caracteres a 1440 px | el texto y su filete llevan `max-width: var(--measure)` (62ch): ~62 caracteres (comprobado en `privacidad/1440-claro.png`) | ✅ manual |
| K5 · Enlaces del pie (CA6) | «Términos de uso» y «Aviso de privacidad» | presentes | ✅ |
| K6 · Estabilidad visual | ≤ 0.1 | 0,026 (términos) · 0,02 (aviso) | ✅ |
| K7 · JavaScript de la ruta | ≤ 100 kB | solo `publico.js`, sin JavaScript nuevo | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 25 | 4 | `terminos/1440-claro.png`, `privacidad/1440-claro.png` · el h1 en Newsreader domina la cabecera y cada sección tiene una sola entrada (número rosa, h2 y filete); la fecha pesa como la entrada |
| Primera pantalla | 25 | 5 | `terminos/film-320-00.png`, `privacidad/film-320-00.png` · a 320 px, etiqueta, título, entrada, fecha y el índice entero (7 de 7 y 7 de 8); a 1440, título, fecha, «En pocas palabras» y el comienzo del índice |
| Coherencia con el sistema y la marca | 20 | 4 | `terminos/320-claro.png` · las píldoras de `/servicios` en todos los anchos, la tarjeta de la agenda con su arco, la etiqueta, Newsreader, el logo y el pie del sitio |
| Lectura y ritmo | 15 | 4 | `privacidad/1440-claro.png` · el filete acaba donde acaba la medida del texto, ninguna píldora partida a 1440, interlineado holgado |
| Diferencia frente a una plantilla | 15 | 3 | `terminos/1440-claro.png` · «En pocas palabras» y las píldoras numeradas son recursos propios; el esqueleto (índice y columna) es el de una página legal |

Lo que el evaluador aún señaló en c4 (no impide aceptarlo; ver «Decisiones»): las frases clave solo se
distinguen por la negrita; el índice fijo no marca la sección en curso; a 1440 px queda libre la franja a la
derecha de la columna de lectura, y a 320 px la píldora «Con quién los compartimos» se parte en dos líneas y
«En pocas palabras» queda bajo el pliegue.

## Imágenes de muestra

Ninguna. El único dibujo es el arco decorativo de la tarjeta de la agenda del sistema (SVG en línea,
`aria-hidden`).

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Términos de uso · sin sesión | `candidatos/c4/terminos.html` a 320 y 390 | no aplica (una columna hasta 899 px) | a 1440 | CA1, CA6, CA7 |
| Aviso de privacidad · sin sesión | `candidatos/c4/privacidad.html` a 320 y 390 | no aplica | a 1440 | CA2, CA3, CA6, CA7 |
| Las dos · sin JavaScript | `*-sin-js.png` a 320 | — | a 1440 | CA8 |
| Las dos · con sesión | la cabecera y el pie del layout con «Ir a mi panel» (sin pantalla propia) | — | — | CA7 |
| Pie de las páginas públicas | los dos enlaces al final de `pub-foot__links` | — | — | CA6 |
| Casilla del registro (normal y con Google) | el texto de CA4 en la casilla actual y el mensaje de CA5 | — | — | CA4, CA5 |
| Ventana de los términos o del aviso en el registro (refinado) | `candidatos/c4/registro-dialogo.html` a 320 y 390 (la ventana sola, abierta en el HTML; la integración, sin medir, en `registro-integracion.html`) | — | a 1440 | CA4 |

## Componentes

- **Del sistema:** cabecera y pie públicos (`.pub-bar`, `.pub-menu`, `.pub-foot`); etiqueta
  (`.pub-etiqueta`); cabecera de página (`.lb-cabeza`: h1 en `--text-seccion`, entrada y fecha en
  `--text-lead` y `--p600`); índice en píldoras (`.lb-indice`, con su número en `small`, sticky en
  escritorio, una columna por debajo de 560 px y dos de 560 a 899 px); secciones (`.lb-lista section h2`
  con filete); tarjeta de la agenda (`.pub-book__card` con `.pub-book__arco`).
- **Ventana del registro** (refinado, CA4; en `auth.css`): `<dialog class="dialogo-legal">` con `aria-labelledby` a su título; cabecera fija con el título (`h2`), la fecha y «Cerrar» (el botón `.btn-back` del registro); el texto de la página (las mismas secciones, con `h3`) se desplaza dentro; el enlace al otro texto abre su ventana.
- **Nuevos de esta spec** (van al sistema, ver abajo): la rejilla de la página legal (`.lb-legal`), el
  texto corrido (`.lb-texto`), el número de sección (`.lb-num`), el título del índice
  (`.lb-indice--legal .pub-etiqueta`) y «En pocas palabras» (`.lb-resumen`, variante compacta de la
  tarjeta de la agenda).

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-display` | fijo | `"Newsreader", "Newsreader respaldo", "Newsreader respaldo Times", ui-serif, Georgia, serif` | — | En una página de lectura los títulos pesan más y el cambio de fuente movía el texto (c1: CLS 0,115). Como el respaldo de Montserrat (1.14.0) |
| `@font-face` «Newsreader respaldo» (Georgia del sistema) | fijo | 500: `size-adjust` 97,25 %, `ascent-override` 76,1 %, `descent-override` 27,8 % · 600–700 (Georgia Bold): 85,28 %, 86,8 %, 31,7 % · cursiva 500 (Georgia Italic): 91,1 %, 81,2 %, 29,6 %; `line-gap-override` 0 % | — | Medido en Chromium: ancho de Newsreader / ancho de Georgia por peso; override = ascenso (0,74) y descenso (0,27) de Newsreader / `size-adjust` |
| `@font-face` «Newsreader respaldo Times» (Times New Roman o Liberation Serif) | fijo | 500: 106,65 %, 69,4 %, 25,3 % · 600–700: 102,74 %, 72 %, 26,3 % · cursiva 500: 101,15 %, 73,2 %, 26,7 % | — | Georgia y Times no tienen las mismas medidas: la primera familia que tenga el sistema |
| Página legal (`.lb-legal`) | corte 900 px | una columna: cabecera, índice, resumen y texto (orden del documento) | zonas «cabeza cabeza resumen» / «indice texto texto» en `1fr 1.6fr 1.4fr`, separación `--space-seccion` × `--space-8` | La primera pantalla del móvil muestra título, fecha y el comienzo del índice; en escritorio el resumen ocupa el hueco junto al título |
| Texto corrido (`.lb-texto`) | fijo | párrafos y listas a `--measure`, separación `--space-4`, listas con viñeta `--p400` y `--space-3` entre puntos, enlaces `--text` subrayados en `--p400`; la columna entera a `--measure` para que el filete acabe con el texto | — | Lectura (modo leer, K4) |
| Número de sección (`.lb-num`) | fijo | `--font-publica` `--weight-bold` `--text-publico-small` `--p600`, `--tracking-etiqueta`, encima del h2; el mismo número en `small` dentro de la píldora del índice | — | Orientación en un documento largo (c2) |
| Título del índice | fijo | «En esta página» con `.pub-etiqueta`, a todo el ancho del índice | — | Nombra el índice para todos (y lo enlaza con `aria-labelledby`) |
| «En pocas palabras» (`.lb-resumen`) | fijo | `.pub-book__card` con `--space-8` de relleno, h2 en `--text-publico-h1`, puntos en párrafos `--p100` separados por un filete `--p600`, enlaces `--white` subrayados | — | Lo esencial de cada página con el contenido propio de la clínica (c2: `plantilla` 2) |
| Secciones con ancla | fijo | `scroll-margin-top: calc(var(--touch-min) * 2 + var(--space-8))` | — | Que la cabecera fija no tape el título al saltar desde el índice |

| Ventana del registro (`.dialogo-legal`, en `auth.css`) | corte 560 px | relleno lateral `--space-6` | ancho `calc(100% - var(--space-4) * 2)` hasta `calc(var(--measure) + var(--space-8) * 2)`, alto hasta `calc(100dvh - var(--space-8) * 2)`, `--radius-lg`, `--white`, `--shadow-soft`, velo `--overlay`; cabecera con filete `--border` y relleno `--space-6` `--space-8`; título `--text-marca` `--weight-bold`; fecha `--text-meta` `--p600`; texto `--text-publico-small` con interlineado 1.6, títulos `--text-marca`, números `--p600`, viñetas `--p400`, enlaces `--p600` subrayados; foco `--focus-ring` | CA4 (refinado): el texto sin salir del registro |
| Tokens que `auth.css` copia de `app.css` con el mismo valor | fijo | `--space-3`, `--space-6`, `--space-8`, `--radius-lg`, `--border-width`, `--overlay`, `--shadow-soft`, `--measure`, `--text-marca`, `--text-publico-small`, `--weight-bold` | — | Como la spec 044 (D8): el registro no carga `app.css` |

Ningún color nuevo: `criterios.json → tokens.colors` no cambia.

## Movimiento

Ninguno nuevo. Las píldoras del índice conservan la transición de fondo de `/servicios` (solo sin
movimiento reducido).

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`, `G-foco`).
  El teléfono de «En pocas palabras» va dentro de un párrafo, como los demás enlaces del texto.
- **Encabezados y estructura:** un h1; h2 «En pocas palabras» y un h2 por sección, cada sección con
  `aria-labelledby`; el número de sección con `aria-hidden` (no se lee dos veces). El índice es un `nav`
  con `aria-labelledby` a «En esta página»; el resumen, un `aside` con su título.
- **Teclado:** el orden del documento es el del móvil (título, índice, resumen, texto); en escritorio el
  resumen se ve arriba a la derecha pero se recorre después del índice.
- **Decorativos ocultos:** el arco de la tarjeta (`aria-hidden`, `focusable="false"`).
- **Ventanas del registro (CA4, refinado):** cada enlace de la casilla lleva `aria-haspopup="dialog"`; la ventana
  (`<dialog>` modal) se nombra con su título (`aria-labelledby`), al abrirse el foco va a «Cerrar» (primer
  elemento enfocable, comportamiento nativo de `showModal`), se cierra con «Cerrar», Escape o pulsando fuera,
  y el foco vuelve al enlace que la abrió; el fondo queda inerte mientras está abierta (nativo).

## Contenido para revisar

El texto de las dos páginas está en `candidatos/c4/terminos.html` y `candidatos/c4/privacidad.html`. Es
una base para un proyecto académico con datos ficticios, no asesoría legal. Se escribió con lo que la app
hace hoy (campos del registro y de Google, contacto de emergencia de la ficha, regla de 24 horas para
cancelar o cambiar citas, «Mi perfil», correos de la cuenta, registro de seguridad de 5 años con los
accesos denegados y algunos cambios, copias cifradas con las 14 más recientes, cookies de sesión, el mapa de
Google del inicio). Refinado tras `/analyze` r1 (A3, A8): ver «Decisiones». Conviene que revises sobre todo estos puntos, que
no salen de ningún documento del proyecto:

- **Menores de edad** (términos, «Tu cuenta»): «la crea y la usa su madre, su padre o su tutor».
- **Plazos de respuesta** (aviso, «Tus derechos»): 20 días hábiles para responder y 15 para aplicarlo.
- **Medios para los derechos:** en la clínica, en su horario, o por teléfono (no hay correo de la clínica
  publicado).
- **Proveedores** (aviso, «Con quién los compartimos»): el servidor, Cloudflare y Google (inicio de sesión
  y correos).
- **Registros técnicos del servidor:** se mencionan (IP, página y hora) sin plazo de conservación, porque
  aún no lo tienen (OB8.b de `docs/observability.md`).
- **Cambios de los términos:** «Si sigues usando tu cuenta después, aceptas la versión nueva».

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-12 | supuesto | K3 (lista de secciones al principio) y K4 (largo de línea) no tienen un tipo en `evaluate.mjs` | Se comprueban a mano en el acta y con tests en `/implement` (criterios.md §3: no se inventan tipos) | `design-spec/references/criterios.md` §3 |
| 2026-11-12 | supuesto | El medidor buscaba «Tu cuenta» y la encontraba en la entrada y en el resumen antes que en la sección | La entrada y el resumen dicen «la cuenta» | medida de c1 y c3 |
| 2026-11-12 | diseño | Cambio de fuente de Newsreader en los títulos (c1: CLS 0,115) | Respaldo de Newsreader con sus medidas (Georgia y Times), a «Cambios a incorporar al sistema» (1.16.0), como el de Montserrat de 1.14.0 | medida de c1; review del fix `2026-11-11-fix-cls-inicio-tableta-038` (hallazgo 5) |
| 2026-11-12 | supuesto | La máscara CSS del logo no carga desde `file://`, así que no se veía en las capturas | Medir servido por HTTP desde c3, y además desde el archivo para `G-red` | medida de c2 |
| 2026-11-12 | diseño | «Cuánto tiempo los conservamos» se partía en su píldora | El título de la sección pasa a «Cuánto los conservamos» (CA2 sigue cubierto: dice cuánto tiempo) | evaluador de c3 |
| 2026-11-12 | supuesto | Lo que el evaluador aún señaló en c4: frases clave solo en negrita, índice sin la sección en curso, franja libre a 1440 px, una píldora partida a 320 px | Se acepta: c4 supera el umbral. Marcar la sección en curso pediría JavaScript nuevo (la spec no lo quiere) | evaluador de c4 |
| 2026-11-12 | diseño | ¿Se aprueban el diseño (c4) y el texto de las dos páginas, con los puntos de «Contenido para revisar»? | Aprobados tal cual | usuario |
| 2026-11-12 | refinado | `/analyze` r1 A3 y A8: el aviso describía una cookie de «Recordarme» que no existe (el inicio de sesión no la usa) y un registro de seguridad con «entradas y cambios» que hoy no se auditan, y no nombraba el contacto de emergencia ni el navegador de la sesión | El aviso describe lo que la app hace hoy: sin «Recordarme»; «Proteger el servicio y detectar accesos indebidos»; el registro de seguridad con «los accesos denegados y algunos cambios de la cuenta, como el restablecimiento de la contraseña»; datos técnicos con el navegador «en tu sesión»; y «En tu ficha de paciente: el nombre y el teléfono de un contacto de emergencia». `c4` vuelto a medir: puertas y KPIs en verde en las dos páginas | usuario |
| 2026-11-12 | refinado | `/analyze` r2 A15: la sesión no guarda la IP ni el navegador (driver `file` en local y producción), pero el log de acceso de nginx sí | Datos técnicos «en los registros del servidor y de seguridad del sitio», sin «en tu sesión»; `c4` vuelto a medir | `deploy/README.md` (`SESSION_DRIVER=file`), `docker/nginx/dentissa.prod.conf` (`log_format` con `$http_user_agent`) |
| 2026-11-12 | refinado | Verificación manual de `/implement` (T025): el usuario prefiere que en el registro los enlaces abran el texto sin salir de la pestaña | Ventana (`dialog`) con el texto completo de la página, de la misma fuente; se cierra con «Cerrar», Escape o pulsando fuera; sin JavaScript, el enlace lleva a la página en la misma pestaña (spec CA4 reescrito). Medida sola y abierta en `registro-dialogo.html`: 11/11 puertas por HTTP (12/12 desde el archivo) y K8; el resto del registro no se mide: su contraste, toques, colores del logo de Google y logo sin `data-origen` son código previo (deuda DS) | usuario |
| 2026-11-13 | refinado | Al fusionar `main` antes de `/release`, la v0.17.0 (spec 047) ya había liberado `system.md` 1.15.0 | Los cambios de la 045 al sistema pasan a la versión 1.16.0 (también en `system.html`, en los comentarios del CSS y de los tests); el contenido no cambia. `plan.md` y `tasks.md` conservan «1.15.0» como registro de lo planeado | `/release 045` |
