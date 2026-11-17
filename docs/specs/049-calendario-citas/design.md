---
spec: 049-calendario-citas
status: approved         # draft | approved (solo el usuario aprueba)
exploration: loop        # ciclo por criterios
threshold: 80
floor: 3
budget: 6                # 5 de project.yaml + 1 ampliado por el usuario (2026-11-16)
accepted: c6             # aceptado por el usuario bajo el umbral (78), con cinco refinados: ver Decisiones
score: 78
system_version: 1.17.0
created: 2026-11-15
updated: 2026-11-17
---

# Diseño · 049 Calendario de citas

Hecho con `/design-spec 049` sobre `docs/design/system.md` 1.17.0. Criterios en la spec («Criterios de
diseño», K1–K10) y en `design/criterios.json`; candidatos en `design/candidatos/` (los descartados se
conservan). Cada candidato son cuatro páginas sobre la carcasa real de «Citas» (barra superior y menú;
la barra lateral queda fuera de la medida): `semana.html`, `semana-dia.html`, `mes.html` y `vacia.html`,
con datos ficticios de la semana del 16 al 21 de noviembre de 2026 («ahora», el martes 17 a las 11:20).
Medidos con `design/medir.mjs` (que llama a `evaluate.mjs` por pantalla) a 320, 390 y 1440 px.

## Candidatos

| Candidato | Puertas | KPIs | Rúbrica | Resultado | Informe |
|---|---|---|---|---|---|
| `c1` | 9/12 (peor pantalla) | 5/7 | — | descartado: G-contraste (botón principal sobre `--p400`, DS1; «Completada»), G-tokens (tira y salto sin estilo fuera del móvil), G-solapamiento (textos fuera de su carril); K1 y K3 por los botones dentro de `<nav>` | `.ai/inspect/049-c1/` |
| `c2` | 11/12 | 7/7 | — | descartado: G-solapamiento (citas que coinciden en carriles estrechos; textos ocultos sin ancla en el mes); K1 en la semana vacía | `.ai/inspect/049-c2/` |
| `c3` | 12/12 | 7/7 | 64 | descartado: las cifras y los filtros tapaban la agenda en el móvil; cifras sin decir de qué periodo; estados cortados y filas de la lista altas | `.ai/inspect/049-c3/` |
| `c4` | 12/12 | 7/7 | 69 | descartado: buscador entre la tira y su lista; lista del escritorio estirada; en el mes, la cifra era el texto más débil | `.ai/inspect/049-c4/` |
| `c5` | 12/12 | 7/7 | 75 | descartado: filas de la lista altas con el 40 % vacío; nombres cortados a 320 px; título de la lista y día elegido confusos en el móvil | `.ai/inspect/049-c5/` |
| `c6` | 12/12 | 7/7 | 78 | **aceptado por el usuario bajo el umbral**, con cinco refinados (Decisiones) | `.ai/inspect/049-c6/` |

## Resultado

**c6**, aceptado por el usuario con 78/100 (umbral 80, ningún punto bajo 3) y cinco refinados que
aplica `/implement` y se verifican en pantalla (ver Decisiones). Páginas:
`design/candidatos/c6/{semana,semana-dia,mes,vacia}.html` con `calendario.css`.

| Puerta o KPI | Objetivo | Medido (c6) | |
|---|---|---|---|
| G-desborde, G-contraste, G-toque, G-foco, G-tokens, G-h1, G-solapamiento, G-aparece, G-imagenes, G-reducido, G-sin-js, G-red | las de `criterios.md` §2; fuentes de Google permitidas | 12/12 en las cuatro pantallas a 320, 390 y 1440 px | ✅ |
| K1 · «Nueva cita», «Anterior», «Hoy», «Siguiente», «Semana», «Mes» sin desplazarse (CA1, CA2) | a los tres anchos | a la vista en las cuatro pantallas | ✅ |
| K2 · Estados y dentistas en texto (CA1, CA6) | presentes | 6/6 | ✅ |
| K3 · Calendario antes que la lista (CA4) | orden | en orden (se medirá solo a 1440: Decisiones) | ✅ |
| K4 · «Ver toda la semana» con un día elegido (CA4) | presentes | 2/2 | ✅ |
| K5 · Mes con citas por día (CA3) | presentes | 2/2 | ✅ |
| K6 · Semana vacía (CA9) | presentes | 2/2 | ✅ |
| K7 · «Mar 17» sin desplazarse en el móvil (CA8) | a 320 y 390 | a la vista | ✅ |
| K8 · JavaScript | ≤ 50 kB | `menu.js` y `filtros.js` | ✅ |
| K9 · CLS | ≤ 0,1 | ✅ | ✅ |
| K10 · Títulos | ≤ 2 líneas | ✅ | ✅ |

| Rúbrica | Peso | Nota | Evidencia (evaluador independiente) |
|---|---|---|---|
| Jerarquía | 30 | 4 | `semana/1440-claro` · la cuadrícula domina; resumen → periodo y navegación → calendario → lista; hoy y el día elegido se distinguen. En el móvil, navegación, vista y buscador apilan ~230 px antes de la tira |
| Primera pantalla | 25 | 4 | `semana/film-1440-00` · se ve todo lo necesario; a 320 caben la tira y las primeras citas del día. El título de la lista en el móvil no nombra el día |
| Coherencia con el sistema y la marca | 20 | 4 | `semana/1440-claro`, `mes/390-claro` · tarjetas, botones, pastillas y Roboto del sistema; nada parece de otro producto |
| Lectura y ritmo | 15 | 4 | `semana/1440-claro` · lista en tres columnas de días (página de 2358 px frente a 3258 en c5; el mes, 3504 frente a 6500). Con un solo día, la lista deja dos tercios vacíos |
| Diferencia frente a una plantilla | 10 | 3 | `semana/1440-claro` · propio: sábado «Cerrado desde las 14:00» rayado, columna de hoy, resumen de hoy; el resto es el patrón de cualquier agenda (3 en las cuatro evaluaciones) |

Puntuación: 30·4/5 + 25·4/5 + 20·4/5 + 15·4/5 + 10·3/5 = 24 + 20 + 16 + 12 + 6 = **78**.

## Imágenes de muestra
Ninguna.

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Semana (sin día elegido) | `c6/semana.html` a 320 y 390: resumen, periodo, buscador, «Citas de la semana» con la tira y la lista de hoy | no medido (900–1023: la cuadrícula semanal, ver Riesgos del plan) | a 1440: cuadrícula por horas y lista en columnas de días | CA1, CA2, CA4–CA8 |
| Semana con un día elegido | `c6/semana-dia.html`: tira con el miércoles relleno y su lista | — | cabecera del día rellena en granate y «Citas del día» con «Ver toda la semana» | CA4, CA8 |
| Mes | `c6/mes.html`: cuadrícula de lunes a sábado con la cifra y «citas» debajo | — | cuadrícula con «N citas» como dato principal; días sin citas en blanco y de otro mes sin borde | CA3 |
| Semana vacía | `c6/vacia.html`: tira a cero y «No hay citas en esta semana» | — | cuadrícula con huecos y la misma lista vacía | CA7, CA9 |

## Componentes

- **Del sistema:** carcasa de la app (barra superior con «Nueva cita»), `.stats-grid` / `.stat-card` (resumen de hoy, con su título), `.card`, filtro de citas de la 034 (`.filtro-citas`, `filtros.js`), `.btn` (`-primary`, `-outline`, `-sm`, `-ghost`), `.badge-*` de estado, `.empty-state`, `.formulario-accesible` (botón principal sobre `--p500` y pastilla verde en `--text`: el contenido y «Nueva cita» de esta pantalla van dentro, por DS1).
- **Exclusivos de esta pantalla** (`calendario.css`, solo tokens):
  - **Barra del periodo:** título del periodo; «‹ Anterior», «Hoy», «Siguiente ›» y el interruptor «Semana · Mes» (`.cal-vista`, opción actual con `aria-current="page"`, fondo `--p600`), agrupados con `role="group"`, todos a la misma altura.
  - **Semana por horas** (≥ 900 px): columna de horas y una columna por día (lunes a sábado) con su cabecera (enlace al día, número de citas, marca «Hoy»; el día elegido, relleno `--p600`); filas de 30 min de `--touch-min + --space-3`; los días con citas que coinciden ocupan el doble de ancho y cada cita va en su carril. Cita (`.cal-cita`): enlace al detalle, tres líneas (hora y paciente · dentista · estado), fondo y borde izquierdo por estado (agendada `--p100`/`--p400`, confirmada `--blue-bg`/`--blue`, en curso `--amber-bg`/`--amber`, completada `--green-bg`/`--green-texto`); canceladas y «no asistió» sin relleno, borde discontinuo `--text-light` y, la cancelada, tachada. Hueco (`.cal-hueco`): tramo sin cita vigente a la vista (las canceladas y «no asistió» no lo ocupan: el hueco va en un carril junto a ellas; con una sola dentista a la vista, sus tramos libres), desde el siguiente tramo; enlace a «Nueva cita» con día y hora en `H:i` con cero inicial (`09:00`; el candidato escribía `9:00`, que el alta no acepta: /analyze A5), nombre «Agendar el <día> a las <hora>», sin nada visible hasta el puntero o el foco. Fuera de horario: rayado con «Cerrado desde las 14:00». Enlace «Saltar a la lista de citas» antes de la cuadrícula.
  - **Tira de días** (< 900 px): seis días (lunes a sábado; el domingo la clínica cierra) con su número de citas, dentro de la sección de la lista y bajo su título; día elegido relleno `--p600`, hoy con borde `--p400`.
  - **Mes:** cuadrícula de lunes a sábado; en cada día, el número del día y «N citas» como dato principal (`--p600`, negrita); sin citas, «0 citas» normal; días de otro mes sin borde ni enlace; hoy con borde `--p400`.
  - **Lista del periodo** (clases propias `.cal-lista-*`: `.lista-citas` ya es el componente de la 027, /analyze A20): un grupo por día (título con la fecha y «Hoy»), en columnas que llenan el ancho en el escritorio y una sola en el móvil; cada cita es un enlace (hora · paciente y dentista en dos líneas · pastilla de estado) que abre el detalle; es una variante de «Cita que abre su detalle» (048): la fila o el bloque entero es el enlace, con nombre «<hora>, <paciente>, <dentista>, <estado>»; en el móvil, solo el día elegido (por defecto, hoy si está en el periodo y, si no, el primero).

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Componente «Calendario de citas» (barra del periodo, semana por horas, tira de días, mes, lista del periodo) | corte 900 px | tira de días y lista del día | semana por horas y lista en columnas | Spec 049; sin tokens nuevos (versión siguiente a la vigente al fusionar) |

## Movimiento
Ninguno.

## Accesibilidad

- **Contraste, toque y foco:** medidos por `evaluate.mjs` (puertas en verde en las cuatro pantallas). El estado de cada cita va en texto; el color lo refuerza.
- **Encabezados y estructura:** `h1` «Citas» (barra superior); `h2` del resumen (en el candidato, «Hoy, martes 17 de noviembre»; en la build, el periodo o el día elegido: refinado 5), el periodo (calendario) y la lista; `h3` por día en la lista. Cada columna de día es un grupo con el nombre de la fecha.
- **Teclado:** resumen → navegación del periodo → vista → buscador y filtros → «Saltar a la lista» → citas y huecos de cada día → lista. Una parada por cita (todo el bloque o toda la fila es el enlace).
- **Sin JavaScript:** todo es HTML del servidor; el panel de filtros se ve desplegado.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-15 | diseño | Criterios (K1–K10; rúbrica con `jerarquia` 30 y `plantilla` 10) | Aprobados | usuario |
| 2026-11-15 | diseño | El botón principal global no llega a AA sobre `--p400` (DS1) | El contenido de «Citas» y su «Nueva cita» van dentro de `.formulario-accesible` (botón sobre `--p500`) | `docs/design/system.md` (DS1, componente de la 028) |
| 2026-11-15 | supuesto | La tira del móvil: ¿siete días? | Seis (lunes a sábado): el domingo la clínica cierra y siete no caben a 320 px con objetivos de 44 px | `config/clinica.php`; puerta G-toque (cambia el texto de CA8: ver Historial de la spec) |
| 2026-11-15 | diseño | Botón del estado vacío | «Agendar una cita» (el «Nueva cita» de la barra superior sigue siendo el acceso de CA9) | medida de K1 |
| 2026-11-16 | diseño | Presupuesto agotado con c5 (75) | Un candidato más (c6) | usuario |
| 2026-11-16 | diseño | c6 con 78/100, bajo el umbral, sin presupuesto | Aceptado con las correcciones que aplica `/implement` (refinados 1–4; el 5 salió en `/plan`) y se verifican en pantalla | usuario |
| 2026-11-16 | refinado | 1 · Título de la lista en el móvil | Nombra el día que muestra («Citas de hoy, martes 17» o del día elegido) y ofrece «Ver toda la semana» o «Ver todo el mes»; K3 (orden con «Citas de la semana») se mide solo a 1440 | usuario (corrección del evaluador sobre c6) |
| 2026-11-16 | refinado | 2 · Huecos en la lista del escritorio | Con un solo día, la lista ocupa todo el ancho; en el periodo, los días se leen en orden de filas (izquierda a derecha, arriba abajo) sin huecos irregulares | usuario (ídem) |
| 2026-11-16 | refinado | 3 · Alto antes de la tira en el móvil | Navegación del periodo y cambio de vista en menos alto (una sola banda si caben con objetivos de 44 px) | usuario (ídem) |
| 2026-11-16 | refinado | 5 · Título del resumen de cifras (hallado en /plan 049: la app las calcula sobre el rango mostrado, no sobre hoy) | Las cifras son del periodo que se ve (o del día elegido) y su título lo dice («Resumen de la semana del 16 al 21 de noviembre»), en lugar de «Hoy, martes 17» | usuario (2026-11-16) |
| 2026-11-16 | refinado | 4 · Bloques del calendario | La hora y el paciente destacan sobre el dentista y el estado (peso y tamaño), sin perder las tres líneas | usuario (ídem) |
