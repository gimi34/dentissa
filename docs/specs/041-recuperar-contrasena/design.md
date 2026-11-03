---
spec: 041-recuperar-contrasena
status: approved         # draft | approved (solo el usuario aprueba)
exploration: loop        # loop: ciclo por criterios · none: sin diseño nuevo (motivo en "Resultado")
threshold: 80            # puntuación mínima de la rúbrica (de .ai/project.yaml → design)
floor: 3                 # nota mínima de cada punto de la rúbrica
budget: 7                # candidatos como máximo (5 + 2 por decisión del usuario)
accepted: none           # el usuario volvió al estilo anterior: ver "Resultado" y "Decisiones" (2026-11-03)
score: none              # c7 sacó 88; el estilo anterior no pasó por la rúbrica
system_version: 1.8.0
created: 2026-10-27
updated: 2026-11-03
---

# Diseño · 041 Recuperar contraseña

Hecho con `/design-spec 041` (AI-DD 1.19.0) sobre `docs/design/system.md`. Criterios en la spec
("Criterios de diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los
descartados se conservan). Cada candidato es una carpeta con ocho páginas (seis pantallas y dos
correos) y se mide pantalla por pantalla con `design/medir.mjs`, que parte `criterios.json` por
pantalla y llama a `evaluate.mjs` (decisión de 2026-10-27, abajo).

Antecedente: las opciones A, B y C (`design/opcion-*.html` y `critica-*.md`) se hicieron con el
método de opciones de AI-DD 1.16.0 antes del upgrade a 1.19.0; ninguna se eligió. El ciclo parte de
la B («El carnet de citas») por decisión del usuario.

## Candidatos

Puertas y KPIs sumados en las ocho páginas (12 puertas por página; los KPIs de cada pantalla más los
comunes). Rúbrica: un evaluador independiente por candidato, que solo vio las capturas y comparó con
el mejor anterior.

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 95/96 | 34/34 | — | descartado: `G-toque` en `exito` (casilla «Recordarme» de 18×18 a 320 y 390 px) | `.ai/inspect/041-c1/resumen.md` |
| `c2` | 96/96 | 34/34 | 78 | descartado: bajo el umbral; el login con el aviso no compartía el marco de la recuperación, la hora de «Revisa tu correo» competía con el título, el teléfono se partía a 320 px | `.ai/inspect/041-c2/resumen.md` |
| `c3` | 96/96 | 34/34 | 75 | descartado: mejor que c2 en jerarquía, primera pantalla y sistema; peor en `plantilla` (perdió la hora destacada) y talón desalineado | `.ai/inspect/041-c3/resumen.md` |
| `c4` | 96/96 | 42/42 | 78 | descartado: bajo el umbral; «Revisa tu correo» sin un bloque dominante, teléfono repetido en panel y talón a 1440 px, texto de apoyo pequeño | `.ai/inspect/041-c4/resumen.md` |
| `c5` | 96/96 | 42/42 | 75 | descartado: mejor jerarquía que c4, peor en sistema y lectura (talón vacío a 1440 px, pares del talón sin separación, botón activo en «Demasiados intentos») | `.ai/inspect/041-c5/resumen.md` |
| `c6` | 96/96 | 42/42 | 78 | descartado: bajo el umbral, mejor que c4 en jerarquía y sistema; botón activo en «Demasiados intentos», panel con el lema comercial, talón críptico en «Pedir» | `.ai/inspect/041-c6/resumen.md` |
| `c7` | 96/96 | 42/42 | **88** | descartado: lo eligió el ciclo y el usuario lo aprobó, pero al verlo construido prefirió el estilo anterior (2026-11-03) | `.ai/inspect/041-c7/resumen.md` |

c2 y c3 se fotografiaron sin Roboto (el entorno de medición usaba una letra de reemplazo de un solo
peso); desde c4 la letra va incrustada y el KPI K12 comprueba que carga. c6 y c7 salen del
presupuesto ampliado por el usuario.

## Resultado

**Estilo anterior del inicio de sesión** (decisión del usuario, 2026-11-03; plan D20). Al ver `c7`
construido, el usuario prefirió el aspecto que tenía el login antes de esta spec: se conserva en el
login y lo adoptan las pantallas de la recuperación. La referencia es `login.blade.php` y `auth.css`
del commit `7c472bf`; no hay candidato nuevo ni pasó por la rúbrica, porque lo decide el usuario. Sí
pasa las puertas y los KPIs de `criterios.json`, medidos sobre la build (nota de T039 en `tasks.md`).

Lo único que cambia respecto a aquel login es lo que exige la constitución (P9, WCAG 2.1 AA):

| Elemento | Antes | Ahora | Motivo |
|---|---|---|---|
| Borde del campo | `--border` | `--border-control` (`--p400`) | Con `--border` no llega a 3:1 |
| Foco | Halo `--p300` | Contorno `--focus-ring` (`--p600`) | Con `--p300` no llega a 3:1 |
| Botón principal | `--p400` (hover `--p500`) | `--p500` (hover `--p600`) | Blanco sobre `--p400` es 3,93:1 |
| Ojo y enlace «¿Olvidaste…?» | `--p300` · 13 px sin zona de toque | `--p500` · 44 px | Contraste y `G-toque` |
| Alto de campos y casilla | 43 px · 16 px | 44 px con `--touch-min` | `G-toque` |
| Texto del panel | Blanco al 60 % | Blanco al 70 % | 4,5:1 también en pantallas bajas |
| Letra | Roboto de Google Fonts | Roboto autoalojada | `G-red`, la misma letra |
| Entrada del formulario | Sube y aparece | Solo sube | `G-sin-js`: el contenido siempre se ve |
| Títulos | Lema `h1`, título `h2` | Lema `p`, título `h1` | Un solo `h1` (SE2.a); se ve igual |

Sin cambios: los dos correos conservan su marco de `c7`, porque no había un estilo anterior.

Resultado original del ciclo, que se conserva como registro: `c7`, aceptado con 88/100 (umbral 80,
ningún punto bajo 3). Páginas: `design/candidatos/c7/*.html`.

| Puerta o KPI | Objetivo | Medido en c7 | |
|---|---|---|---|
| Puertas (`G-desborde`, `G-contraste`, `G-toque`, `G-foco`, `G-tokens`, `G-h1`, `G-solapamiento`, `G-aparece`, `G-imagenes`, `G-reducido`, `G-sin-js`, `G-red`, `G-errores`) | §2 de criterios.md | 12/12 en las ocho páginas a 320, 390 y 1440 px (correos a 320 y 390) | ✅ |
| K1 · Pedir el enlace y volver (CA1) | a la vista sin desplazarse | a la vista a 320, 390 y 1440 | ✅ |
| K2 · Revisa tu correo y mensaje genérico (CA2, CA8) | a la vista | a la vista | ✅ |
| K3 · Correo del enlace (CA2) | «Elegir contraseña nueva», «60 minutos», «Si no lo pediste…» | presentes | ✅ |
| K4 / K4b · Contraseña nueva (CA3, CA4) | campos y acción a la vista; regla y error presentes | cumple | ✅ |
| K5 / K5b · Enlace caducado (CA5) | «Pedir un enlace nuevo» a la vista; «Tu contraseña no cambió» | cumple | ✅ |
| K6 · Demasiados intentos (CA9) | «Espera un minuto» a la vista a 320 | cumple | ✅ |
| K7 · Éxito en el inicio de sesión (CA3) | aviso e «Iniciar sesión» a la vista | cumple | ✅ |
| K8 · Aviso de contraseña cambiada (CA7) | «¿No fuiste tú?» y el teléfono | presentes | ✅ |
| K9 · JavaScript | ≤ 100 kB | 0 B | ✅ |
| K10 · CLS | ≤ 0.1 | 0 | ✅ |
| K11 · Titulares | ≤ 2 líneas | cumple | ✅ |
| K12 · Roboto carga | cargada | cargada | ✅ |

| Rúbrica | Peso | Nota | Evidencia (evaluador) |
|---|---|---|---|
| Jerarquía | 25 | 4 | `c7/espera/390` y `1440`: aviso ámbar → campo → botón desactivado «Disponible en un minuto», sin un botón activo que contradiga el aviso; en las demás domina título + botón o la hora. No llega a 5: a 1440 el panel vino con el logo grande resta peso a la tarjeta |
| Primera pantalla | 25 | 5 | `pedir/320` y `1440`: pregunta, frase llana, campo, «Enviar enlace» y volver arriba del pliegue; a 1440 el panel dice para qué sirve el paso; `espera/320` y `exito/390` se entienden a la primera |
| Coherencia con el sistema y la marca | 25 | 5 | `exito/1440` frente a `nueva/1440`: mismo panel con el logo, tarjeta, campos, botón y enlaces; avisos con los colores semánticos; correos con la franja y el troquel de la app |
| Lectura y ritmo | 15 | 4 | `pedir/390`: medida corta e interlineado holgado. Fallos: en el teléfono los valores del talón apilados empiezan en minúscula; «minutos» sola en la frase del panel a 1440; hueco rosa bajo la tarjeta a 390 en `caducado` |
| Diferencia frente a una plantilla | 10 | 3 | `enviado/1440`: el troquel y «Vale hasta las 15:35» son propios y el panel habla de este caso; la composición panel + tarjeta es la de cualquier acceso |

**Pendiente para `/implement`** (problemas que señaló el evaluador en c7, sin bajar del umbral; se
resuelven en la tarea de cada pantalla y se vuelve a medir con `medir.mjs`):
- valores del talón en el teléfono como frases completas, con mayúscula («Vale 60 minutos…»,
  «Cerramos tus sesiones…», «Hasta las 15:35»);
- frase del panel sin palabra suelta (`text-wrap: balance`);
- el aviso de enlace caducado separa «Tu contraseña no cambió» del motivo, para que no lo dé el bloque
  de error;
- en el correo, la dirección de respaldo completa, partida en varias líneas (en el candidato es un
  marcador «…»).

## Imágenes de muestra

Ninguna. El logo (`public/images/logo-main.png`) es propio (`data-origen="propia"`).

## Pantallas

Desde el 2026-11-03 las pantallas se miden sobre la build (`/_disenio/041/`); las rutas de `c7` quedan
como registro. Los correos siguen siendo los de `c7`.

| Pantalla y estado | Ancho menor | Ancho mayor | Criterios |
|---|---|---|---|
| Pedir el enlace | `candidatos/c7/pedir.html` a 320 y 390 | a 1440 | CA1 |
| Revisa tu correo | `candidatos/c7/enviado.html` a 320 y 390 | a 1440 | CA2, CA8 |
| Contraseña nueva, con error | `candidatos/c7/nueva.html` | a 1440 | CA3, CA4 |
| Enlace que ya no sirve | `candidatos/c7/caducado.html` | a 1440 | CA5, CA10 |
| Demasiados intentos | `candidatos/c7/espera.html` a 320 | a 1440 | CA9 |
| Inicio de sesión con el aviso de éxito | `candidatos/c7/exito.html` | a 1440 | CA3 |
| Correo con el enlace | `candidatos/c7/correo-enlace.html` a 320 y 390 | — | CA2 |
| Aviso de contraseña cambiada | `candidatos/c7/correo-aviso.html` a 320 y 390 | — | CA7 |

Sin ancho intermedio: no hay filas de elementos.

## Componentes

- **Del sistema:** campo (etiqueta, ayuda, error con `aria-invalid` y `aria-describedby`), botón
  primario y secundario, enlace, avisos de error, éxito y espera (`role="alert"` o `role="status"`),
  marca (logo).
- **Exclusivos de estas pantallas (D20):**
  - **Panel de marca:** desde 769 px, panel al 48 % con el degradado y los círculos del login anterior,
    el logo, el lema «Tu sonrisa, nuestra prioridad» y su frase, igual en todas las pantallas. En el
    teléfono no se muestra: solo el formulario sobre `--p50`.
  - **Formulario:** ancho máximo `--acceso-form-ancho`, sobre `--white` en el escritorio. Campos con
    icono a la izquierda, fondo `--p50` y radio `--radius-acceso`.
  - **Datos de abajo (`talon`):** lista `dl` de etiqueta y valor, en `--text-meta`, bajo una línea
    `--border`. Apila etiqueta y valor por debajo de 400 px. Lleva el teléfono de la consulta.
  - **Bloque «Vale hasta»:** `--p50` con la etiqueta, la hora en `--p800` al tamaño del titular y la
    instrucción.
  - **Botón en espera:** desactivado, `--p100` con texto `--p800` y borde `--border`, con el texto
    «Disponible en un minuto».
  - **Ver la contraseña** (solo en el inicio de sesión, refinado el 2026-11-03): botón de 44 × 44 px
    (`--touch-min`) dentro del campo, a la derecha, con el icono del ojo en `--p500` y `aria-pressed`.
    Es mejora progresiva: sin JavaScript no aparece y el campo sigue igual.
  - **Logo de Google:** sus cuatro colores son los de la marca de Google, fuera del sistema; solo en
    el botón «Continuar con Google».

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--font-acceso` | fijo | `Roboto, system-ui, sans-serif` | — | El acceso tiene hoy la familia como literal en `auth.css` (DS4) |
| `--text-acceso-titulo` · `--text-acceso` · `--text-acceso-small` | fijo | 28 px · 14 px · 12 px | — | Igual: literales de `auth.css` |
| `--acceso-form-max` | fijo | 480 px | — | Ancho del carnet |
| `--duration-respuesta` · `--ease-clinico` | fijo | 150 ms · `cubic-bezier(.2, .7, .2, 1)` | — | Respuesta de los botones (M-1) |
| Botón primario del acceso en `--p500` (hover `--p600`) | fijo | — | — | Blanco sobre `--p400` es 3.93:1 (DS1); sobre `--p500`, 5.42:1 |
| Borde de los campos del acceso en `--p400` (`--border-control`) | fijo | — | — | El borde actual no llega a 3:1 |
| Etiquetas, ayuda y error de campo a `--text-acceso-small` (12 px) | fijo | — | — | El tamaño del login anterior (D20) |
| Panel de marca del acceso: `--acceso-panel-fondo` (degradado del login anterior), `--acceso-panel-sub`, `--acceso-panel-sub-ancho`, `--acceso-circulo`, `--acceso-panel-pad-y` y `-x`, `--logo-acceso-w`, `--text-acceso-lema`, `--leading-acceso-panel` | corte 769 px | oculto | panel al 48 % | Valores del login anterior, llevados a tokens (D20) |
| Formulario del acceso: `--acceso-form-ancho`, `--acceso-cabecera-pie`, `--acceso-campo-sangria`, `--radius-acceso`, `--tracking-campo`, `--sombra-boton-acceso`, `--duration-entrada` | fijo | — | — | Igual (D20) |
| Botón en espera (desactivado) | fijo | — | — | «Demasiados intentos» no debe invitar a pulsar (evaluador, c5 y c6) |
| Casilla con zona de toque de 44 px (cuadro de 18 px de un solo trazo; marcada, palomita sin relleno) | fijo | — | — | `G-toque` (c1); el usuario pide lados iguales y que no se rellene (2026-11-03) |
| Roboto autoalojada (variable, OFL 1.1), sin Google Fonts | fijo | — | — | Recursos externos (`G-red`) y privacidad; como Lexend en la 038 |
| Ver la contraseña en el inicio de sesión: botón de 44 px con el ojo, mejora progresiva | fijo | — | — | El login actual lo tiene y el usuario lo conserva (refinado, 2026-11-03) |

## Movimiento

### M-2 — Entrada del formulario (D20)
- **Propósito y frecuencia:** la del login anterior; una vez por página.
- **Elementos y propiedades:** `transform: translateY` desde 16 px · **Duración:** `--duration-entrada`.
- **Reducido:** sin animación · **Sin JavaScript:** CSS puro. Sin `opacity`: el contenido siempre se ve.

### M-1 — Respuesta de los botones
- **Propósito y frecuencia:** confirmar que la pulsación se recibió; varias veces por visita.
- **Disparador:** puntero o toque sobre un botón; se repite.
- **Elementos y propiedades:** `transform: scale(.98)` y color de fondo · **Duración y curva:**
  `--duration-respuesta` (150 ms), `--ease-clinico`.
- **Reducido:** sin transición (0 ms) · **Sin JavaScript:** CSS puro · **Táctil:** `:active`.
- **Coste:** 0 B de JavaScript de 100 kB.

c2 tenía además «el talón crece» en «Revisa tu correo»; se quitó en c3 porque competía con el título.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (`G-contraste`, `G-toque`, `G-foco`) en
  las ocho páginas.
- **Encabezados y estructura:** un `h1` por página (el título del formulario; en el inicio de sesión,
  «Bienvenido de vuelta», y el lema del panel es un párrafo). `main` para el formulario y `aside` para
  el panel.
- **Avisos:** «Revisa tu correo» con `role="status"`; enlace caducado y demasiados intentos con
  `role="alert"`; el error del campo enlazado con `aria-describedby`. Las regiones de estado siguen
  `references/spec-mode.md` §6 en la implementación.
- **Botón en espera:** `disabled` (no recibe foco); el aviso explica cuánto esperar.
- **Ver la contraseña:** botón con `aria-label="Mostrar contraseña"`, `aria-pressed` y `aria-controls`;
  el icono va con `aria-hidden`.
- **Enlace en el texto del talón** («pide otro enlace»): enlace dentro de un párrafo, exento del
  tamaño de toque (WCAG 2.5.8).
- **Decorativos ocultos:** círculos del panel e íconos de campos y avisos con `aria-hidden`.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-27 | diseño | ¿Se elige entre A, B y C (método 1.16.0) o se repite con criterios? | Rehacer el diseño con el método por criterios de 1.19.0 | usuario |
| 2026-10-27 | diseño | Criterios, pesos y punto de partida | Modo operar, K1–K11, umbral 80, mínimo 3, presupuesto 5, `sistema` 25 y `plantilla` 10; primer candidato desde la opción B | usuario |
| 2026-10-27 | brecha | `evaluate.mjs` mide una página y la 041 tiene ocho | Un `criterios.json` con el campo `pantalla` en cada KPI y `design/medir.mjs`, que lo parte por pantalla; `/implement` y `/review` lo usan sobre la build | /design-spec |
| 2026-10-27 | brecha | El entorno de medición no tiene Roboto y los pesos 500/600 no se veían | Roboto incrustada desde c4 y KPI K12 (la letra carga); c2 y c3 se puntuaron sin ella | /design-spec |
| 2026-10-27 | supuesto | ¿Cambia el marco del inicio de sesión? | Sí, en lo visual: el login con el aviso de éxito adopta la franja y la tarjeta (sus campos, textos y comportamiento no cambian); /plan lo trata como cambio de la vista de la spec 001 | /design-spec (evaluador, c2) |
| 2026-10-27 | diseño | Presupuesto agotado (5) sin candidato sobre el umbral; mejor c4 con 78 | Dos candidatos más (presupuesto 7) desde c4, corrigiendo sus tres problemas | usuario |
| 2026-10-27 | diseño | Resultado del ciclo | `c7` aceptado con 88/100 | /design-spec |
| 2026-10-27 | diseño | ¿Se aprueba el acta? | Aprobada | usuario |
| 2026-11-03 | refinado | `c7` quitaba el botón del ojo del inicio de sesión para no usar JavaScript. ¿Se conserva? | Se conserva, solo en el inicio de sesión y como mejora progresiva: un archivo propio con `defer`; sin él, la contraseña se escribe igual. Las pantallas de la recuperación siguen sin JavaScript. `c7/exito.html` remedido: puertas 12/12 y KPIs 5/5 (`.ai/inspect/041-c7-ojo/`). Ningún criterio cambia: K9 pide ≤ 100 kB y `G-sin-js`, que todo el contenido esté sin JavaScript | usuario |
| 2026-11-03 | refinado | Al ver `c7` construido: «no me gustó el nuevo estilo, me gusta más el anterior». ¿Qué se conserva? | Se vuelve al estilo del login anterior a la 041, en el login y en la recuperación; sin rúbrica, por debajo del umbral por decisión del usuario, y con las puertas y KPIs de `criterios.json`. Se mantienen los cambios que exige P9 (ver «Resultado»). Los correos siguen como `c7` | usuario |
