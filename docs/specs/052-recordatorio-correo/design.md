---
spec: 052-recordatorio-correo
status: draft            # draft | approved (solo el usuario aprueba)
exploration: loop        # loop: ciclo por criterios · none: sin diseño nuevo (motivo en "Resultado")
threshold: 80            # puntuación mínima de la rúbrica (de .ai/project.yaml → design)
floor: 3                 # nota mínima de cada punto de la rúbrica
budget: 5                # candidatos como máximo
accepted: c2             # candidato aceptado
score: 82                # puntuación del aceptado
system_version: 1.20.0
created: 2026-11-21
updated: 2026-11-21
---

# Diseño · 052 Recordatorio de cita por correo

Hecho con `/design-spec 052` sobre `docs/design/system.md` (1.20.0). Criterios en la spec ("Criterios de
diseño") y en `design/criterios.json`; candidatos en `design/candidatos/` (los descartados se conservan).

Cada candidato es el correo de recordatorio montado sobre el **marco de los correos del acceso** (spec 041:
`resources/views/correos/marco-acceso.blade.php` con `correos/acceso.css`, copiada tal cual en
`<style id="acceso-041">`); lo nuevo va en `<style id="cambios-052">`. Datos de ejemplo del volcado ficticio
(paciente «Fernanda», dentista «Melissa López», servicio «Limpieza dental») y de `config/clinica.php`. Cada
candidato tiene dos variantes, con servicio (`cN.html`) y sin servicio (`cN-sin-servicio.html`), y las dos
se miden **servidas por HTTP** (`design/servir.mjs`) con `evaluate.mjs` en la imagen de Playwright 1.63.0 a
320 y 390 px; la variante sin servicio, con una copia de los criterios sin «Limpieza dental» en K2
(`.ai/inspect/052-criterios-sin-servicio.json`). K3 y K4 no tienen tipo en `evaluate.mjs`: se comprueban en
el DOM del candidato (y con tests en `/implement`).

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 11/11 (las dos variantes) | 2/2 · K3 ✅ · K4 ✅ | 74 | descartado: bajo el umbral y `plantilla` 2 bajo el mínimo; composición de cualquier recordatorio; la dirección al pie, fuera de la primera pantalla a 320 px; la fecha partida sin sentido y dos estilos de etiqueta | `.ai/inspect/052-c1/report.md` · `.ai/inspect/052-c1-sin/report.md` |
| `c2` | 11/11 (las dos variantes) | 2/2 · K3 ✅ · K4 ✅ | 82 | **aceptado** | `.ai/inspect/052-c2/report.md` · `.ai/inspect/052-c2-sin/report.md` |

## Resultado

**c2**, aceptado con 82/100 (umbral 80, ningún punto bajo 3). Página: `design/candidatos/c2.html` (sin
servicio: `design/candidatos/c2-sin-servicio.html`).

| Puerta o KPI | Objetivo | Medido | |
|---|---|---|---|
| G-desborde, G-h1, G-contraste, G-toque, G-tokens, G-solapamiento, G-aparece, G-imagenes, G-foco, G-reducido, G-sin-js, G-red | §2 de los criterios; `G-red` sin hosts externos | 11/11 sin fallos a 320 y 390 px, con y sin servicio | ✅ |
| K1 · Día y hora en la primera pantalla, con el tamaño mayor (CA1) | «26 de noviembre de 2026» y «10:30» sin desplazar a 320 y 390 px | a la vista en los dos anchos; fecha a 28 px y hora a 30 px, el resto ≤ 16,8 px (DOM) | ✅ |
| K2 · Datos de la cita (CA1) | dentista, dirección, teléfono, «Ver mis citas» y, con servicio, el servicio | 5/5 con servicio · 4/4 sin servicio | ✅ |
| K3 · Nada fuera de CA1 (CA10) | ni notas ni datos del expediente | ninguno en el DOM (solo nombre, fecha, hora, dentista, servicio, dirección, teléfono, horario y el enlace) | ✅ manual |
| K4 · Enlaces del correo (CA9) | un solo enlace de acción, «Ver mis citas», sin parámetros de acceso | un único `<a>`, a `https://dentissa.app/cliente/citas`, sin parámetros; el teléfono va como texto | ✅ manual |

| Rúbrica | Peso | Nota | Evidencia (evaluador independiente) |
|---|---|---|---|
| Jerarquía | 25 | 4 | `390-claro.png` y `320-claro.png` · bloque «Tu cita»: la fecha grande y la hora en granate dominan y el orden se sigue sin leer (saludo → fecha y hora → semana → Con/Servicio/Dónde → «Ver mis citas» → talón). No llega a 5: la fecha ocupa tres líneas y la hora se lee como una línea más del bloque |
| Primera pantalla | 25 | 5 | `film-320-00.png` y `film-390-00.png`: sin desplazar, el nombre, «Te esperamos mañana», el día y la hora, el dentista, el servicio, la dirección completa y el botón; igual sin servicio |
| Coherencia con el sistema y la marca | 20 | 4 | `390-claro.png`: cabecera granate con el logo, tarjeta blanca sobre rosa claro, botón rosa y talón del correo de restablecimiento; todas las etiquetas con la misma regla; la semana es la de la agenda de la página de inicio. A 320 las fichas de la semana quedan juntas |
| Lectura y ritmo | 15 | 4 | Medida e interlineado correctos, datos a 16,8 px y bloques bien separados; algún corte («de 2026» solo; «Sábados / 9:00–14:00» a 320) y el aviso de 13 px apretado frente a los datos |
| Diferencia frente a una plantilla | 15 | 3 | `390-claro.png` · la semana L–D de la consulta con el jueves de la cita marcado y el domingo cerrado en discontinuo, que sale de la agenda de la marca; el resto sigue el esquema de un recordatorio |

Mejoras que señaló el evaluador y no se aplican (el aceptado ya supera el umbral; se pueden pedir con
`/design-spec 052 --fix`): mantener juntos «26 de noviembre» y «de 2026»; llevar el horario del talón a las
mismas fichas de la semana; un tramo del horario por línea y el aviso con el interlineado del cuerpo.

## Imágenes de muestra

Ninguna. La única imagen es el logo propio (`public/images/logo-main.png`, `data-origen="propia"`), que en el
correo real se pide a `APP_URL` como en la 041.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Correo de recordatorio · con servicio | `candidatos/c2.html` a 320 | — | a 390 | CA1, CA9, CA10 |
| Correo de recordatorio · sin servicio | `candidatos/c2-sin-servicio.html` a 320 | — | a 390 | CA1, CA9, CA10 |
| Detalle de la cita del personal | no cambia: CA7 usa la marca «Recordatorio · Enviado» que ya existe (`citas/show.blade.php`) | — | — | CA7 |

## Componentes

- **Del sistema:** el marco de los correos del acceso (cabecera con `--acceso-panel-fondo` y el logo, tarjeta
  `--white` de hasta 480 px sobre `--p50`, `.btn-primario`, filete y talón `dl`), sin cambios.
- **Exclusivos de este correo** (en la hoja del correo, con los valores de los tokens copiados porque los
  clientes de correo no entienden `var()`, como `correos/acceso.css`):
  - **Tarjeta de la cita:** tabla de fondo `--p100` (`#FBEAF0`) y radio `--radius` (10 px) con la etiqueta
    «Tu cita», la fecha como `h1` a `--text-acceso-titulo` (28 px, peso 600, interlineado 1.15: el día de la
    semana en su línea y «26 de noviembre» sin partir) y la hora a `--text-acceso-lema` (30 px, peso 600,
    `--p600`).
  - **Semana de la consulta:** fila de siete fichas L–D (tabla de ancho fijo, separación 4 px), borde
    `--p200`, radio 10 px, 13 px peso 500; el día de la cita relleno de `--p600` con texto `--white`; el
    domingo (cerrado) con borde discontinuo, como la agenda de la página de inicio (038). Decorativa:
    `aria-hidden="true"`, porque la fecha ya está escrita.
  - **Etiquetas:** una sola regla para los datos y el talón: `--text-acceso-small` (12 px), peso 500,
    `--tracking-etiqueta` (0.08em), mayúsculas, `--p600`.
  - **Datos:** `dl` con «Con», «Servicio» (solo si la cita lo tiene) y «Dónde», valores a
    `--text-acceso-destacado` (16,8 px).
  - **Talón:** «Teléfono» y «Horario de la consulta» a `--text-meta` (13 px).

## Cambios a incorporar al sistema

Ninguno. Todo valor es de un token existente (copiado en la hoja del correo, como hace la 041).

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas `G-contraste`, `G-toque`, `G-foco`) sin
  fallos a 320 y 390 px.
- **Encabezados y estructura:** un solo `h1`, la fecha de la cita; las tablas de maquetación con
  `role="presentation"`; datos y talón como listas de definición.
- **Decorativos ocultos:** la semana lleva `aria-hidden="true"`; el filete también.
- **Texto:** el asunto repite el día y la hora («Tu cita es mañana, jueves 26 de noviembre, a las 10:30»).

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-21 | supuesto | K3 («nada fuera de CA1») y K4 («un solo enlace de acción») no tienen tipo en `evaluate.mjs` | Se comprueban en el DOM de cada candidato y con tests en `/implement` | `design-spec/references/criterios.md` §3 |
| 2026-11-21 | supuesto | ¿Cómo medir la variante sin servicio con K2? | Con una copia de `criterios.json` sin «Limpieza dental» | /design-spec 052 |
| 2026-11-21 | contradicción | El evaluador de c1 pedía el carnet con troquel y talón de un candidato de la 041; el usuario lo descartó en la 041 (D20, 2026-11-03) | No se usa; el recurso propio es la semana de la agenda de la página de inicio (038) | usuario (041 D20) · `docs/design/system.md` Decisiones |
| 2026-11-21 | supuesto | ¿Cómo va el teléfono? | Como texto, no como enlace `tel:`, para que el único enlace sea «Ver mis citas» (CA9, K4) | /design-spec 052 |
