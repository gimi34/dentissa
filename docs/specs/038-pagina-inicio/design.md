---
spec: 038-pagina-inicio
status: draft            # draft | approved (solo el usuario aprueba)
exploration: loop        # ciclo por criterios (AI-DD 1.19), ronda 6
threshold: 80
floor: 3
budget: 5
accepted: c4
score: 80
system_version: 1.9.0
created: 2026-10-31
updated: 2026-10-31
---

# Diseño · 038 Página de inicio pública

Hecho con `/design-spec 038`, ronda 6 (2026-10-31), con el motor por criterios de AI-DD 1.19 y desde
cero (decisión del usuario), sobre `docs/design/system.md` 1.9.0. Criterios en la spec ("Criterios de
diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los descartados se
conservan). Las rondas 1–5 están en `design/history/` (la 5, «La ventana del diente», ya implementada
en T046–T058, en `design/history/ronda-5/`).

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 12/12 | 12/13 | — | descartado: K2, el teléfono queda bajo la primera pantalla a 1440; el odontograma se lee como un anillo de puntos | `.ai/inspect/038-c1/report.md` |
| `c2` | 12/12 | 13/13 | 67 (tipografía 2 < 3) | descartado: tildes corridas de Cormorant Garamond, presentación recortada a 320 con JavaScript, huecos y dos titulares en «Sobre nosotros» | `.ai/inspect/038-c2/report.md` |
| `c3` | 12/12 | 13/13 | 73 | descartado: foto borrosa (recorte ampliado), escena entre la presentación y «Sobre nosotros» con bandas vacías, tríada de columnas | `.ai/inspect/038-c3/report.md` |
| `c4` | 12/12 | 13/13 | 80 | **aceptado** | `.ai/inspect/038-c4/report.md` |

K6 y K7 se miden sobre una copia del candidato sin el mapa de Google (`.ai/inspect/038-cN-peso/`):
`evaluate.mjs` recorre la página entera y el mapa (≈ 1,8 MB de JavaScript de Google) se carga, pero la
spec lo excluye de la primera carga y del presupuesto de P10 (Requisitos no funcionales). Ver
Decisiones.

## Resultado

**c4**, aceptado con 80/100 (umbral 80, ningún punto bajo 3). Página: `design/candidatos/c4.html`.

**Idea («De 20 a 32»):** «toda la familia» contada con el odontograma: los 20 dientes de leche se
completan hasta los 32 permanentes al bajar, en una escena que continúa «Sobre nosotros». La foto
propia de la marca va en un marco con la forma de la arcada; el arco vuelve como fondo dibujado de la
presentación y de la tarjeta de la agenda. Con otra clínica, el odontograma, la frase de CA3 anotada y
la semana real dejan de tener sentido.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde, G-h1, G-red, G-contraste, G-toque, G-tokens | — | sin fallos a 320, 390, 900 y 1440 | ✅ |
| G-solapamiento, G-aparece, G-imagenes, G-foco, G-reducido, G-sin-js | — | sin fallos | ✅ |
| K1 · Acción principal en la primera pantalla (CA1) | «Agendar mi primera cita» | a la vista en 320, 390, 900 y 1440 | ✅ |
| K2 · Titular y teléfono en la primera pantalla (CA1) | a 900 y 1440 | 2/2 a la vista | ✅ |
| K3 · Orden de las secciones (CA1) | presentación → «Sobre nosotros» → servicios → ubicación | en orden | ✅ |
| K4 · Textos exactos (CA1, CA3, CA4) | 7 textos | 7/7 presentes | ✅ |
| K5 · Contacto exacto (CA5) | 8 textos | 8/8 presentes | ✅ |
| K6 · Peso de la primera visita (P10) | ≤ 977 kB | 307 kB (sin el mapa) | ✅ |
| K7 · JavaScript de la ruta (P10) | ≤ 100 kB | 3 kB (sin el mapa) | ✅ |
| K8 · Estabilidad visual | ≤ 0.1 | 0.012 | ✅ |
| K9 · Líneas de cada titular | ≤ 3 | ≤ 3 | ✅ |
| K10 · Escenas de scroll | ≥ 1 | 1 a cada ancho | ✅ |
| K11 · Elementos en movimiento | ≥ 8 | 31 | ✅ |
| K12 · Regiones con fondo propio | ≥ 2 | 2 | ✅ |
| K13 · Imágenes de muestra | 0 | 0 | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 20 | 4 | `film-1440-00…11` · el orden de CA1 se cumple y cada pantalla tiene un titular que manda; la escena comparte el rosa de «Sobre nosotros» y la semana compite con la acción en la tarjeta negra |
| Primera pantalla | 20 | 5 | `film-1440-00`, `film-320-00` · logo, «Odontología integral · Lázaro Cárdenas», la acción en una línea, el estado, el horario, el teléfono y «Iniciar sesión · Crear cuenta» antes del pliegue, también a 320 |
| Coherencia con el sistema y la marca | 15 | 4 | `film-1440-00, 03, 08` · la foto con el logo, los rosas, los dientes en blanco y `--p800`; desentonan el hueco de la lista de notas y el mapa de Google |
| Lectura y ritmo | 10 | 3 | `film-1440-05, 08`, `film-320-03/04, 09` · buena medida en «Sobre nosotros» y servicios; huecos tras la escena y antes de «Visítanos», las horas de la semana partidas a 320 |
| Diferencia frente a una plantilla | 15 | 4 | `film-1440-00, 04, 08` · la foto propia en la arcada, el odontograma 20 → 32 y la semana real; el lema del titular sigue siendo genérico |
| Riqueza visual y movimiento | 10 | 3 | `film-1440-03→04` · la escena del odontograma es el único movimiento ligado al contenido; el resto, arcos finos sobre fondos planos |
| Tipografía | 10 | 4 | `film-1440-00, 06, 10` · Newsreader con la cursiva «prioridad.», tildes correctas, cifras de estilo antiguo; faltan los numerales de las notas |

**Mejoras del aceptado para `/implement`** (los tres problemas que el evaluador dejó en c4; no cambian
ningún criterio y la página construida debe seguir pasando `evaluate.mjs`):
1. Las notas de «Sobre nosotros»: el número 1·2·3 de cada nota se ve (el atributo `data-n` va en el
   `li`, no en el `b`; en c4 el hueco queda vacío) en Newsreader `--p800`, y a 320 la lista va a todo el
   ancho.
2. Ritmo: la escena termina cuando acaba el paso de 20 a 32 (sin tramo rosa vacío), el espacio antes
   de «Visítanos» es el de las demás secciones, y la escena se separa de «Sobre nosotros» con
   `--p100` y los dientes en `--p400`/`--p800`.
3. Móvil: la foto de la presentación al ancho de la columna; la acción de la tarjeta negra en una
   línea; la semana a 320 en filas día–hora en lugar de siete casillas.

## Imágenes de muestra

Ninguna. La foto de la presentación es la propia de la marca (`public/images/brand/access.webp`,
`data-origen="propia"`, D20); el odontograma y los arcos son dibujos en código.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Página de inicio · reposo | `candidatos/c4.html` a 320 y 390 | a 900 | a 1440 | CA1, CA3, CA4, CA5, CA7, CA8, CA9 |
| Página de inicio · menú abierto | `candidatos/c4.html` a 390, «Menú» abierto | — | — | CA9 |
| Página de inicio · sin servicios activos | sin la sección «Nuestros servicios» (CA4) | — | — | CA4 |
| Página de inicio · recorrido | tira `.ai/inspect/038-c4/film-320-*` | — | tira `film-1440-*` | CA9 |
| `/servicios` (modo leer) | su estructura (índice y listas por categoría) con la cabecera, el pie, la tipografía, la etiqueta y los botones de c4 | — | ídem | CA6 |

## Componentes

- **Del sistema (1.9.0):** botón en píldora (principal `--p400` con tinta, tinta, fantasma; `--lg`),
  pastilla de estado (`--shadow-soft`, punto `--green-texto`/`--p400`), índice de categorías (ahora con
  su número `01…07` en `--p600`), cabecera pública con «Menú» en píldora, pie; foco del sistema y el
  inverso sobre la tinta.
- **Nuevos de la superficie «Sitio público»:**
  - **Etiqueta** (`.etiqueta`): Montserrat `--weight-bold`, `--text-publico-small`, mayúsculas con
    `--tracking-etiqueta`, `--p600` (tinta sobre `--p400`); encabeza cada sección.
  - **Notas de una promesa** (`.nota` y `.notas`): partes de un párrafo subrayadas con su número en
    superíndice (contenido generado, el texto no cambia) y debajo la lista de notas con el número
    grande en Newsreader, el título y el texto en `--text-publico`.
  - **Semana de la consulta** (`.agenda__semana`): siete casillas día–hora con borde `--p800`, el
    domingo con borde discontinuo y «Cerrado»; a 320, filas día–hora.
- **Exclusivos de `/`:** **la arcada** (`.arcada`: la foto propia en un marco con la forma de la
  arcada, `--shadow-soft`, con su pie de foto), **el odontograma** (escena `arco`: 32 piezas dibujadas
  en código, las 20 de leche en blanco y las 12 permanentes en `--p800`, con la leyenda) y **los arcos
  de fondo** (trazo `--p200` en la presentación, `--p200` al 18 % en la tarjeta de la agenda).

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-display` | fijo | `"Newsreader", ui-serif, Georgia, serif` | — | Cormorant Garamond corre las tildes a la derecha por diseño y en titulares en español se leen rotas (c2, tipografía 2/5); Newsreader (500, 600 y cursiva 500 de `@fontsource/newsreader` 5.3.0, OFL-1.1, publicada el 2026-07-19) las centra, tiene cifras claras y está hecha para titulares en pantalla |
| Cormorant Garamond | — | — | — | Se retira de `public/fonts/` (sustituida por Newsreader) |
| Ancho del logo de la cabecera pública | fijo · corte 560 px | `--logo-max-w` (160px) por debajo de 560 | `calc(var(--logo-max-w) * 1.25)` | Que el subtítulo del logo se lea en el escritorio sin desbordar a 320 |
| Componentes «Etiqueta», «Notas de una promesa» y «Semana de la consulta» | — | — | — | Ver Componentes |
| Índice de categorías con número | — | — | — | Variante del índice 1.9.0 con `01…07` en `--p600` |
| Se retiran de la superficie | — | — | — | La ventana del diente y su escena, la tarjeta de la agenda con el diente y la promesa en tarjetas (ronda 5); `--text-firma` deja de usarse en `/` (el pie ya no lleva el nombre a todo el ancho) |

## Movimiento

### M-1 — De 20 a 32 (escena `arco`)
- **Propósito y frecuencia:** explica «toda la familia» con el odontograma; una vez por visita.
- **Disparador:** progreso del desplazamiento nativo (`--p`, `data-hold="0.35"`), sin capturar la rueda.
- **Elementos y propiedades:** las 12 piezas permanentes: `opacity` y `transform: scale()` según `--p` y
  su orden `--k` · **inicial → final:** 20 piezas → 32.
- **Reducido:** las 32 piezas, quietas · **Sin JavaScript:** igual, con la leyenda · **Táctil:** igual.
- **Coste:** parte del script común (≈ 3 kB en total).

### M-2 — La presentación se dibuja
- **Propósito y frecuencia:** firma de la primera pantalla; una vez al cargar.
- **Elementos y propiedades:** los arcos del fondo (`stroke-dashoffset`, `--ease-in-out`, escalonados
  `--stagger`), el titular por líneas (`translateY(105%)` → 0, `--ease-expo`) y la foto que se asienta
  (`scale(1.06)` y opacidad → reposo). Todo termina en menos de 5 s; el titular y la acción se leen
  desde el primer fotograma.
- **Reducido y sin JavaScript:** dibujado y quieto.

### M-3 — Entradas por sección
- Una vez por elemento, al llegar al 88 % de la ventana: títulos desde su máscara, párrafos, notas, filas
  del índice y la tarjeta que suben 24 px; `--duration-reveal`, `--ease-expo`, escalonado `--stagger`
  (cuatro pasos). **Reducido y sin JavaScript:** todo visible.

### M-4 — Respuesta bajo la mano
- Botones que ceden (`scale(0.97)`, `--duration-fast`) con la flecha que avanza; la flecha del índice
  gira con el puntero. **Reducido:** solo el color · **Táctil:** el foco, sin hover.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`, `G-foco`)
  a 320, 390, 900 y 1440.
- **Encabezados y estructura:** un `h1` (el titular); `h2` por sección; `h3` «De 20 a 32» dentro de
  «Sobre nosotros» · **Teclado:** orden del HTML = orden visual; «Menú» con `<details>`, Escape lo
  cierra · **Decorativos ocultos:** arcos, odontograma (con su leyenda en texto), flechas, números de
  las notas (generados) · la foto de la presentación lleva su texto alternativo.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-31 | diseño | Regenerar el diseño con el motor por criterios de AI-DD 1.19 | Ronda 6 desde cero, con los criterios K1–K13, las puertas y la rúbrica de la spec; la ronda 5 al historial | usuario |
| 2026-10-31 | implícita | `evaluate.mjs` cuenta el mapa de Google en K6 y K7 al recorrer la página | K6 y K7 se miden sobre una copia sin el mapa, como dicen los Requisitos no funcionales de la spec («no cuenta en la primera carga ni en el presupuesto de JavaScript de P10») | `spec.md` → Requisitos no funcionales |
| 2026-10-31 | brecha | Cormorant Garamond (1.9.0) coloca las tildes corridas y el evaluador las leyó rotas | `--font-display` pasa a Newsreader; afecta también a lo implementado en la ronda 5 | `/design-spec` (c2) |
| 2026-10-31 | diseño | Aceptar c4 con 80/100 justo en el umbral | Aceptado por la regla del ciclo; las tres mejoras que dejó el evaluador van a `/implement` sin cambiar criterios | `references/evaluacion.md` §4 |
