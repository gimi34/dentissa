---
spec: 053-whatsapp-inicio
status: approved           # draft | approved (solo el usuario aprueba)
exploration: loop        # loop: ciclo por criterios · none: sin diseño nuevo (motivo en "Resultado")
threshold: 80            # puntuación mínima de la rúbrica (de .ai/project.yaml → design)
floor: 3                 # nota mínima de cada punto de la rúbrica
budget: 5                # candidatos como máximo
accepted: c3             # candidato aceptado
score: 81                # puntuación del aceptado
system_version: 1.20.0
created: 2026-11-20
updated: 2026-11-20
---

# Diseño · 053 Escríbenos por WhatsApp desde la página de inicio

Hecho con `/design-spec 053` sobre `docs/design/system.md` (1.20.0). Criterios en la spec ("Criterios de
diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los descartados se conservan).

Cada candidato es la página de inicio **renderizada en local** (`main` a2cd958, visitante sin sesión) con
el CSS y el JS reales de `public/` enlazados desde la raíz del repo; lo nuevo va en `<style id="cambios-053">`
y en el marcado de los dos botones. Los genera `design/construir.mjs` desde ese HTML y se miden **servidos
por HTTP** (`design/servir.mjs`) con `evaluate.mjs` en la imagen de Playwright 1.63.0, como la 045.
K6 y K9 se miden sobre una copia con el mapa de Google en blanco (`<iframe src="about:blank">`), porque el
mapa queda fuera del peso por el RNF de la 038: con él, la página pesa 2,5 MB y 1,8 MB de JavaScript ajeno
a esta spec (igual en la página actual). K3 no tiene tipo en `evaluate.mjs`: se comprueba en el DOM del
candidato a los cuatro anchos.

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 11/11 | 7/7 (K6 y K9 sin el mapa) · K3 ✅ | 77 | descartado: bajo el umbral; WhatsApp suelto en su propia fila bajo el horario y el teléfono, cerca del pliegue; en contacto, WhatsApp y «Cómo llegar» se apilan con anchos distintos (borde dentado) a 320, 390 y 900 px | `.ai/inspect/053-c1/report.md` · `.ai/inspect/053-c1-sin-mapa/report.md` |
| `c2` | 11/11 | 7/7 (K6 y K9 sin el mapa) · K3 ✅ | 81 | aceptado por criterios; sustituido por c3 a petición del usuario (logotipo de WhatsApp en lugar de la burbuja) | `.ai/inspect/053-c2/report.md` · `.ai/inspect/053-c2-sin-mapa/report.md` |
| `c3` | 11/11 | 7/7 (K6 y K9 sin el mapa) · K3 ✅ | 81 | **aceptado**: c2 con el logotipo de WhatsApp de una tinta (decisión del usuario) | `.ai/inspect/053-c3/report.md` · `.ai/inspect/053-c3-sin-mapa/report.md` |

## Resultado

**c3**, aceptado con 81/100 (umbral 80, ningún punto bajo 3). Es c2 con el logotipo de WhatsApp en lugar de la burbuja (decisión del usuario, 2026-11-20); medidas y notas iguales a las de c2. Página: `design/candidatos/c3.html`.

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde, G-h1, G-contraste, G-toque, G-tokens, G-solapamiento, G-aparece, G-imagenes, G-foco, G-reducido, G-sin-js | §2 de los criterios; `G-red` solo `www.google.com` | 11/11 sin fallos a 320, 390, 900 y 1440 px | ✅ |
| K1 · «Escríbenos por WhatsApp» en la primera pantalla (CA1) | entero en la primera pantalla | a la vista en 320, 390, 900 y 1440 px | ✅ |
| K2 · «Agendar mi primera cita» sigue en la primera pantalla (038 CA1) | entero en la primera pantalla; que destaque más lo juzga `jerarquia` | a la vista en los cuatro anchos; `jerarquia` 4: «la única píldora rellena en rosa y domina» | ✅ |
| K3 · WhatsApp junto al teléfono en contacto (CA2) | justo debajo del teléfono a los cuatro anchos | 18 px bajo el teléfono, 44 px de alto, a 320, 390, 900 y 1440 px (comprobado en el DOM) | ✅ manual |
| K4 · Contacto de la 038 (CA2) | los 8 textos de 038 K5 | 8/8 presentes | ✅ |
| K5 · Toque de los dos botones (P9) | ≥ 44 × 44 px | puerta `G-toque` sin fallos; 44 px de alto | ✅ |
| K6 · JavaScript de la ruta (CA4, P10) | ≤ 13 994 bytes (sin aumento) | 14 kB: solo `publico.js`, sin cambios | ✅ |
| K7 · Titular y teléfono en la primera pantalla (038 CA1) | a la vista a 900 y 1440 px | 2/2 a la vista | ✅ |
| K8 · Estabilidad visual | ≤ 0.1 | 0 | ✅ |
| K9 · Peso de la primera visita (P10) | ≤ 1 MB sin el mapa | 293 kB | ✅ |

| Rúbrica | Peso | Nota | Evidencia (evaluador independiente) |
|---|---|---|---|
| Jerarquía | 25 | 4 | `film-1440-00.png` · parte baja del hero: «Agendar mi primera cita» sigue siendo la única píldora rellena y domina; teléfono y WhatsApp forman un grupo de contacto bajo el horario. No llega a 5 porque el bloque apila cuatro franjas y el teléfono grande compite con la acción principal (038) |
| Primera pantalla | 25 | 4 | `film-1440-00.png` y `film-320-00.png`: qué es, dónde, para quién y qué hacer se leen a ambos anchos; WhatsApp a la vista. Titular genérico (038) y, a 320 px, el botón cerca del borde inferior |
| Coherencia con el sistema y la marca | 20 | 5 | `contacto-1440/900/320.png`: WhatsApp y «Cómo llegar» ocupan el ancho de la columna, en fila a 1440 px y apilados con el mismo ancho a 900 y 320 px; píldora secundaria del sistema, Montserrat e icono de línea como el resto |
| Lectura y ritmo | 15 | 4 | `film-1440-09.png`: huecos de unos 250 px entre «Agenda sin llamar» y «Visítanos», la dirección en cuatro líneas y el horario repetido (todo de la 038) |
| Diferencia frente a una plantilla | 15 | 3 | `1440-claro.png`: el arco y la semana son propios; titular, lista de servicios y bloque dirección + mapa son de plantilla (038). El botón no suma ni resta identidad |

Evidencia de las capturas de c3 (`.ai/inspect/053-c3/`); las de c2 dan las mismas notas. c2 frente a c1: mejor en jerarquía, primera pantalla y sistema; igual en lectura y plantilla. c3 frente a c2: igual en los cinco puntos («el glifo de WhatsApp identifica el canal más rápido que el bocadillo genérico de c2, pero no cambia la nota»).

## Imágenes de muestra

Ninguna. La única imagen es la foto propia de la marca (`images/brand/access.webp`, `data-origen="propia"`).

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Inicio · primera pantalla, visitante | `candidatos/c3.html` a 320 y 390 | 900 | 1440 | CA1, CA3, CA4 |
| Inicio · «Encuéntranos fácilmente» (contacto) | `candidatos/c3.html` a 320 y 390 | 900 | 1440 | CA2, CA3, CA4 |

Con sesión, la primera pantalla cambia solo «Agendar mi primera cita» por «Ir a mi panel» (044 CA5); el
grupo de contacto y su botón son los mismos (CA1).

## Componentes

- **Del sistema:** `c-btn c-btn--secondary` (píldora con contorno; reposo, hover con fondo blanco, foco con
  el anillo del sistema, pulsado con la escala de los botones) con un icono de 24 px (`--icon-md`) y el texto
  «Escríbenos por WhatsApp»; `c-btn c-btn--ink` («Cómo llegar», sin cambios).
- **Exclusivos de esta pantalla:**
  - **Grupo de contacto de la primera pantalla** (`.pub-hero__contacto`): el `li` del teléfono pasa a ser una
    fila propia (`flex-basis: 100%`) con el teléfono y el botón en línea, separados por `--space-6` (filas,
    `--space-2`); el horario queda encima como dato aparte. Dentro de `.pub-hero__facts`, el botón recupera
    el tamaño de texto de los botones (`--text-publico-small`) y no se subraya al pasar por encima.
  - **Acciones de contacto** (`.pub-visit__acciones`): WhatsApp y «Cómo llegar» en un grupo flexible con
    `--space-4` de separación y `--space-4` arriba; cada botón crece para repartirse el ancho (`flex: 1 1 auto`),
    así que en fila llenan la columna y al apilarse tienen el mismo ancho. Sustituye al `div` que solo envolvía
    «Cómo llegar».
  - Por debajo de 560 px, los dos botones van a todo el ancho con el relleno lateral `--space-6`, como las
    acciones del hero.
  - **Icono:** el logotipo de WhatsApp de una tinta (`fill="currentColor"`, el color del texto del botón), sin
    alterar, a 24 px (`--icon-md`), en SVG en línea. Origen: Simple Icons 13.21.0 (`icons/whatsapp.svg`, licencia
    CC0 de los datos; el símbolo es marca de WhatsApp/Meta). Las normas de la marca admiten el símbolo en un solo
    color; el verde de la marca no se usa porque no es un token del sistema (`G-tokens`). Se sirve desde el propio
    sitio: no se carga nada de WhatsApp ni de Meta (RNF de privacidad).
- **Enlace:** `https://wa.me/527530000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20agendar%20una%20cita.`
  con `target="_blank"`, `rel="noopener noreferrer"` y `aria-label="Escríbenos por WhatsApp (se abre WhatsApp en
  una pestaña nueva)"`, que empieza por el texto visible (WCAG 2.5.3).

## Cambios a incorporar al sistema

Ninguno. Solo se usan tokens, componentes y puntos de corte existentes (560 px ya es un corte del sistema
en la página pública).

## Movimiento

Ninguno nuevo. Los botones heredan la escala al pulsar y el desplazamiento del icono al pasar por encima de
`.c-btn` (038); el grupo de contacto entra con el `data-reveal` de su columna, sin cambios.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`, `G-foco`) sin
  fallos; los dos botones miden 44 px de alto.
- **Encabezados y estructura:** sin cambios; un solo `h1`. Los botones son enlaces con nombre accesible que
  avisa de que abren WhatsApp en una pestaña nueva.
- **Teclado:** en la primera pantalla, el orden es teléfono → WhatsApp; en contacto, teléfono → WhatsApp →
  «Cómo llegar».
- **Decorativos ocultos:** el icono lleva `aria-hidden="true"` y `focusable="false"`.
- **Sin JavaScript y movimiento reducido:** todo visible (`G-sin-js`, `G-reducido`); el enlace funciona sin
  JavaScript (CA4).

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | supuesto | K6 «sin aumento» y K9 con la página entera fallan por el mapa de Google (1,8 MB de JavaScript, ya en la página actual) | K6 ≤ 13 994 bytes y K9 ≤ 1 MB, medidos con el mapa en blanco, como el RNF de la 038 y la 045 | `docs/specs/038-pagina-inicio/spec.md` (RNF) · `docs/specs/045-terminos-privacidad/design/medir.mjs` |
| 2026-11-20 | supuesto | K2 («más destacado que WhatsApp») y K3 («junto al teléfono en contacto») no tienen tipo en `evaluate.mjs` | K2: en la primera pantalla por KPI y su peso por el punto `jerarquia`; K3: comprobado en el DOM y con test en `/implement` | `design-spec/references/criterios.md` §3 |
| 2026-11-20 | diseño | ¿Logotipo de WhatsApp o icono propio? | Burbuja de conversación de trazo del sistema (c2); la sustituye el logotipo por decisión del usuario (fila de abajo) | /design-spec 053 |
| 2026-11-20 | diseño | Problemas de la rúbrica que son de la 038 (huecos entre secciones, dirección en cuatro líneas, horario repetido, peso del teléfono, titular) | Fuera de esta spec: la 053 no cambia el resto de la página de inicio (038 CA5) | /design-spec 053 · evaluador c1 y c2 |
| 2026-11-20 | diseño | Burbuja de conversación (c2) o logotipo de WhatsApp | Logotipo de WhatsApp de una tinta en `currentColor` (c3); medido otra vez (11/11 puertas, 7/7 KPIs, rúbrica 81) | usuario |
