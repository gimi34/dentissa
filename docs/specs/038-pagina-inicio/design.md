---
spec: 038-pagina-inicio
status: approved         # aprobado por el usuario el 2026-10-31
exploration: loop        # ciclo por criterios (AI-DD 1.19), ronda 6
threshold: 80
floor: 3
budget: 7                # 5 de project.yaml + 2 (decisión del usuario, 2026-10-31)
accepted: c5
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
| `c4` | 12/12 | 13/13 | 80 | aceptado por el ciclo y **no aprobado por el usuario**: el menú del móvil con la palabra y el borde, «Sobre nosotros» y el apartado de los dientes no le convencen (2026-10-31) | `.ai/inspect/038-c4/report.md` |
| `c5` | 12/12 | 13/13 | 80 | **aceptado** (mejor que c4 en jerarquía, igual en lo demás) | `.ai/inspect/038-c5/report.md` |

K6 y K7 se miden sobre una copia del candidato sin el mapa de Google (`.ai/inspect/038-cN-peso/`):
`evaluate.mjs` recorre la página entera y el mapa (≈ 1,8 MB de JavaScript de Google) se carga, pero la
spec lo excluye de la primera carga y del presupuesto de P10 (Requisitos no funcionales). Ver
Decisiones.

## Resultado

**c5**, aceptado con 80/100 (umbral 80, ningún punto bajo 3). Página: `design/candidatos/c5.html`.

**Idea:** la consulta vista por la forma de su arcada. La foto propia de la marca va en un marco con la
forma de la arcada en la presentación; en la escena «consulta», la misma arcada se abre al bajar hasta
llenar la pantalla. El arco vuelve como fondo dibujado de la presentación y de la tarjeta de la agenda.
«Sobre nosotros» es una cita destacada (la primera frase de CA3) con los tres pasos en línea, y «Agenda
sin llamar» muestra la semana real de la consulta.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde, G-h1, G-red, G-contraste, G-toque, G-tokens | — | sin fallos a 320, 390, 900 y 1440 | ✅ |
| G-solapamiento, G-aparece, G-imagenes, G-foco, G-reducido, G-sin-js | — | sin fallos | ✅ |
| K1 · Acción principal en la primera pantalla (CA1) | «Agendar mi primera cita» | a la vista en 320, 390, 900 y 1440 | ✅ |
| K2 · Titular y teléfono en la primera pantalla (CA1) | a 900 y 1440 | 2/2 a la vista | ✅ |
| K3 · Orden de las secciones (CA1) | presentación → «Sobre nosotros» → servicios → ubicación | en orden | ✅ |
| K4 · Textos exactos (CA1, CA3, CA4) | 7 textos | 7/7 presentes | ✅ |
| K5 · Contacto exacto (CA5) | 8 textos | 8/8 presentes | ✅ |
| K6 · Peso de la primera visita (P10) | ≤ 977 kB | 330 kB (sin el mapa) | ✅ |
| K7 · JavaScript de la ruta (P10) | ≤ 100 kB | 3 kB (sin el mapa) | ✅ |
| K8 · Estabilidad visual | ≤ 0.1 | 0.012 | ✅ |
| K9 · Líneas de cada titular | ≤ 3 | ≤ 3 | ✅ |
| K10 · Escenas de scroll | ≥ 1 | 1 a cada ancho | ✅ |
| K11 · Elementos en movimiento | ≥ 8 | 29 | ✅ |
| K12 · Regiones con fondo propio | ≥ 2 | 2 | ✅ |
| K13 · Imágenes de muestra | 0 | 0 | ✅ |

| Rúbrica | Peso | Nota | Evidencia |
|---|---|---|---|
| Jerarquía | 20 | 4 | `film-1440-01/02`, `film-320-02` · la cita en Newsreader domina «Sobre nosotros» y el orden se lee solo; la escena repite la foto de la presentación |
| Primera pantalla | 20 | 5 | `film-1440-00`, `film-320-00` · titular, etiqueta, la acción, el estado, el horario, el teléfono e «Iniciar sesión · Crear cuenta» a la vista también a 320, con el menú solo como icono |
| Coherencia con el sistema y la marca | 15 | 4 | `film-1440-00, 02, 09, 12` · solo rosas, vino, tinta y blanco, el logo en la cabecera y en la foto; desentonan la pastilla del pie de foto y el mapa de Google |
| Lectura y ritmo | 10 | 3 | `film-320-02/03/04` bien medidos; huecos tras «Crear mi cuenta» y antes de «Visítanos», cortes de línea a 320 en el botón de la agenda y en el estado |
| Diferencia frente a una plantilla | 15 | 4 | `film-1440-05/06, 07, 09` · la foto con el logo que se abre desde la arcada, la semana real y el índice editorial; la cita con tres pasos se parece a una línea de tiempo corriente |
| Riqueza visual y movimiento | 10 | 3 | `film-1440-03→06` · un recorrido (la arcada que se abre), aislado y con una imagen ya vista; fondos planos en el resto |
| Tipografía | 10 | 4 | `film-1440-00, 02, 11` · Newsreader para la voz y Montserrat para los datos, con motivo y cuidado; demasiados pesos de Montserrat |

**Mejoras del aceptado para `/implement`** (las que dejó el evaluador en c5; no cambian ningún criterio
y la página construida debe seguir pasando `evaluate.mjs`):
1. La escena a 320: en su estado final, la foto entera con el logo dentro del margen de 16 px; el pie de
   foto debajo de la foto, en `--p800` y sin pastilla.
2. Ritmo: el mismo espacio entre secciones en toda la página; «Crear mi cuenta» junto a los tres pasos;
   a 320, el botón de la agenda y la pastilla de estado en una línea.
3. Menos pesos de Montserrat (regular y semibold) y los números de los pasos centrados en su círculo.

La foto aparece dos veces (en la presentación y en la escena): se queda así por decisión del usuario
(ver Decisiones).

## Imágenes de muestra

Ninguna. La foto es la propia de la marca (`public/images/brand/access.webp`, `data-origen="propia"`,
D20); los arcos son dibujos en código.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Página de inicio · reposo | `candidatos/c5.html` a 320 y 390 | a 900 | a 1440 | CA1, CA3, CA4, CA5, CA7, CA8, CA9 |
| Página de inicio · menú abierto | `candidatos/c5.html` a 390, el icono pulsado | — | — | CA9 |
| Página de inicio · sin servicios activos | sin la sección «Nuestros servicios» (CA4) | — | — | CA4 |
| Página de inicio · recorrido | tira `.ai/inspect/038-c5/film-320-*` | — | tira `film-1440-*` | CA9 |
| `/servicios` (modo leer) | su estructura (índice y listas por categoría) con la cabecera, el pie, la tipografía, la etiqueta y los botones de c5 | — | ídem | CA6 |

## Componentes

- **Del sistema (1.9.0):** botón en píldora (principal `--p400` con tinta, tinta, fantasma; `--lg`),
  pastilla de estado, índice de categorías (ahora con su número `01…07` en `--p600`), cabecera pública,
  pie; foco del sistema y el inverso sobre la tinta.
- **Cambia:** **el menú del móvil** es solo el icono de hamburguesa (tres rayas, `--icon-md` + `--space-3`),
  sin la palabra ni el borde, en una caja de `--touch-min` × `--touch-min`, con `aria-label="Menú"`
  (decisión del usuario).
- **Nuevos de la superficie «Sitio público»:**
  - **Etiqueta** (`.etiqueta`): Montserrat `--weight-bold`, `--text-publico-small`, mayúsculas con
    `--tracking-etiqueta`, `--p600`; encabeza cada sección (en «Sobre nosotros» es el propio `h2`).
  - **Cita destacada** (`.nosotros__cita`): Newsreader `--text-seccion`, entre comillas `«»` en `--p400`
    (contenido generado), `max-width: 24ch`.
  - **Pasos en línea** (`.pasos`): tres pasos unidos por una línea `--p400`, con el número en un círculo
    `--p400` de `--touch-min`; en vertical por debajo de 900 px.
  - **Semana de la consulta** (`.agenda__semana`): siete casillas día–hora con borde `--p800`, el domingo
    con borde discontinuo y «Cerrado»; a 320, filas día–hora.
- **Exclusivos de `/`:** **la arcada** (`.arcada`: la foto propia en un marco con la forma de la arcada,
  `--shadow-soft`), **la escena «consulta»** (la foto que se abre desde la arcada hasta llenar la
  pantalla, con su pie de foto) y **los arcos de fondo** (trazo `--p200` en la presentación y al 18 % en
  la tarjeta de la agenda).

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-display` | fijo | `"Newsreader", ui-serif, Georgia, serif` | — | Cormorant Garamond corre las tildes a la derecha por diseño y en titulares en español se leen rotas (c2, tipografía 2/5); Newsreader (500, 600 y cursiva 500 de `@fontsource/newsreader` 5.3.0, OFL-1.1, publicada el 2026-07-19) las centra, tiene cifras claras y está hecha para titulares en pantalla |
| Cormorant Garamond | — | — | — | Se retira de `public/fonts/` (sustituida por Newsreader) |
| Ancho del logo de la cabecera pública | fijo · corte 560 px | `--logo-max-w` (160px) por debajo de 560 | `calc(var(--logo-max-w) * 1.25)` | Que el subtítulo del logo se lea en el escritorio sin desbordar a 320 |
| Menú del móvil | — | — | — | Solo el icono de hamburguesa, sin palabra ni borde (decisión del usuario) |
| Componentes «Etiqueta», «Cita destacada», «Pasos en línea» y «Semana de la consulta» | — | — | — | Ver Componentes |
| Índice de categorías con número | — | — | — | Variante del índice 1.9.0 con `01…07` en `--p600` |
| Se retiran de la superficie | — | — | — | La ventana del diente y su escena, la tarjeta de la agenda con el diente y la promesa en tarjetas (ronda 5); `--text-firma` deja de usarse en `/` (el pie ya no lleva el nombre a todo el ancho) |

## Movimiento

### M-1 — La consulta se abre (escena `consulta`)
- **Propósito y frecuencia:** el momento protagonista: la consulta, vista por la forma de su arcada, se
  abre entera; una vez por visita.
- **Disparador:** progreso del desplazamiento nativo (`--p`, `data-hold="0.8"`), sin capturar la rueda.
- **Elementos y propiedades:** `clip-path: inset(… round …)` de la foto según `--p`, de la arcada en el
  centro a la pantalla entera · **inicial → final:** arcada → foto completa.
- **Reducido:** la foto entera, quieta, 16:9 (4:5 en el móvil) · **Sin JavaScript:** igual · **Táctil:** igual.
- **Coste:** parte del script común (≈ 3 kB en total).

### M-2 — La presentación se dibuja
- **Propósito y frecuencia:** firma de la primera pantalla; una vez al cargar.
- **Elementos y propiedades:** los arcos del fondo (`stroke-dashoffset`, `--ease-in-out`, escalonados
  `--stagger`), el titular por líneas (`translateY(105%)` → 0, `--ease-expo`) y la foto que se asienta
  (`scale(1.06)` y opacidad → reposo). Todo termina en menos de 5 s; el titular y la acción se leen
  desde el primer fotograma.
- **Reducido y sin JavaScript:** dibujado y quieto.

### M-3 — Entradas por sección
- Una vez por elemento, al llegar al 88 % de la ventana: la cita desde su máscara, párrafos, pasos,
  filas del índice y la tarjeta que suben 24 px; `--duration-reveal`, `--ease-expo`, escalonado
  `--stagger` (cuatro pasos). **Reducido y sin JavaScript:** todo visible.

### M-4 — Respuesta bajo la mano
- Botones que ceden (`scale(0.97)`, `--duration-fast`) con la flecha que avanza; la flecha del índice
  gira con el puntero. **Reducido:** solo el color · **Táctil:** el foco, sin hover.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`, `G-foco`)
  a 320, 390, 900 y 1440.
- **Encabezados y estructura:** un `h1` (el titular); `h2` por sección («Sobre nosotros», como
  etiqueta); `h3` en los pasos · **Teclado:** orden del HTML = orden visual; el menú con `<details>`,
  nombre «Menú», Escape lo cierra · **Decorativos ocultos:** arcos, flechas, números de los pasos,
  comillas (generadas) · las dos fotos llevan su texto alternativo.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-31 | diseño | Regenerar el diseño con el motor por criterios de AI-DD 1.19 | Ronda 6 desde cero, con los criterios K1–K13, las puertas y la rúbrica de la spec; la ronda 5 al historial | usuario |
| 2026-10-31 | implícita | `evaluate.mjs` cuenta el mapa de Google en K6 y K7 al recorrer la página | K6 y K7 se miden sobre una copia sin el mapa, como dicen los Requisitos no funcionales de la spec («no cuenta en la primera carga ni en el presupuesto de JavaScript de P10») | `spec.md` → Requisitos no funcionales |
| 2026-10-31 | brecha | Cormorant Garamond (1.9.0) coloca las tildes corridas y el evaluador las leyó rotas | `--font-display` pasa a Newsreader; afecta también a lo implementado en la ronda 5 | `/design-spec` (c2) |
| 2026-10-31 | diseño | c4 (80/100) no convence: menú del móvil, «Sobre nosotros» y los dientes | El menú del móvil, solo el icono de hamburguesa; «Sobre nosotros» como cita destacada con los pasos en línea; la escena pasa a ser la foto que se abre desde la arcada; presupuesto de 5 a 7 candidatos | usuario |
| 2026-10-31 | diseño | Aceptar c5 con 80/100 justo en el umbral | Aceptado por la regla del ciclo; las mejoras que dejó el evaluador van a `/implement` sin cambiar criterios | `references/evaluacion.md` §4 |
| 2026-10-31 | diseño | La foto aparece en la presentación y en la escena (lo señaló el evaluador) | Se queda en los dos sitios; acta aprobada | usuario |
