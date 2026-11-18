---
id: 049
slug: calendario-citas
status: implemented
created: 2026-11-15
extends: [034]
---

# 049 · Calendario de citas

## Problema
El apartado «Citas» del personal es hoy una tabla paginada de 25 en 25, con siete columnas y filtros
por fechas. Sirve para buscar una cita concreta, pero no deja ver de un vistazo cómo está la semana:
qué días van llenos, qué huecos quedan libres o cuántas citas tiene cada dentista. Para agendar en
un hueco, quien está en recepción tiene que deducirlo de la tabla y escribir a mano la fecha y la
hora en «Nueva cita».

## Historias de usuario
- Como administración, asistente o dentista, quiero ver la semana de citas en un calendario por horas, para saber de un vistazo qué días y horas están ocupados.
- Como personal, quiero cambiar a la vista del mes, para planear con más tiempo.
- Como personal, quiero seguir teniendo debajo una lista compacta de las citas, con el buscador y los filtros, para encontrar una cita concreta.
- Como recepción, quiero pulsar un hueco libre del calendario y llegar a «Nueva cita» con el día y la hora ya puestos, para agendar sin copiarlos a mano.

## Criterios de aceptación
- [x] CA1 · Dado el personal en «Citas», cuando entra sin elegir nada, entonces ve la semana actual de lunes a sábado como calendario por horas, desde la apertura hasta el cierre de la clínica (sábado hasta su cierre; lo que queda fuera del horario se distingue), con cada cita en su día y su hora, ocupando lo que dura, y mostrando su hora, el paciente, el dentista y su estado en texto; el color de la cita depende de su estado.
- [x] CA2 · Dado el calendario, cuando el personal usa «Anterior», «Siguiente» u «Hoy», o cambia entre «Semana» y «Mes», entonces ve el periodo y la vista pedidos, y la dirección de la página los recoge (al recargarla o compartirla se ve lo mismo); sin JavaScript funciona igual. Con un periodo o una vista no válidos en la dirección, se ve la semana actual, sin error.
- [x] CA3 · Dada la vista «Mes», cuando se abre, entonces se ve el mes en cuadrícula, con el número de citas de cada día; pulsar un día muestra en la lista solo las citas de ese día.
- [x] CA4 · Dado el calendario, cuando se mira la lista de debajo, entonces muestra las citas del periodo visible (la semana o el mes), agrupadas por día y en orden de hora, con hora, paciente, dentista y estado; al pulsar un día en el calendario, muestra solo las de ese día, con una forma visible de volver a todo el periodo. El buscador y los filtros de dentista y estado se aplican a la vez al calendario y a la lista.
- [x] CA5 · Dada una cita en el calendario o en la lista, cuando se pulsa en cualquier parte de ella (o se elige con el teclado y se pulsa Intro), entonces se abre su detalle, como en el panel de inicio (spec 048); cada cita es una sola parada de foco y su nombre accesible dice hora, paciente y dentista.
- [x] CA6 · Dadas dos o más citas que coinciden en el tiempo (de dentistas distintos), cuando se ven en la semana, entonces aparecen una junto a otra, todas legibles y pulsables. Las canceladas y las de «no asistió» se ven atenuadas, con su estado en texto, sin tapar a las vigentes.
- [x] CA7 · Dado un hueco libre del calendario semanal (un tramo de 30 minutos sin ninguna cita vigente: las canceladas y las de «no asistió» no lo ocupan; el buscador y el filtro de estado no cuentan, así que un hueco nunca se ofrece sobre una cita vigente que un filtro oculta; si el calendario muestra una sola dentista, los tramos libres de esa dentista), dentro del horario de la clínica y a partir del siguiente tramo (un tramo que empieza justo ahora ya no se ofrece), cuando el personal lo pulsa, entonces llega a «Nueva cita» con ese día y esa hora (en tramos de 30 minutos) ya puestos y, si el calendario muestra un solo dentista (la vista de la dentista o el filtro de uno), también ese dentista. Los huecos pasados o fuera del horario no se pueden pulsar. Agendar sigue las reglas de hoy (agenda única, spec 025; horarios, spec 027).
- [x] CA8 · Dado el personal en el móvil (por debajo de 900 px), cuando abre «Citas», entonces la semana se ve como una tira de los seis días de apertura (lunes a sábado) con el número de citas de cada uno y, debajo, la lista del día elegido (por defecto, hoy); la vista «Mes» es una cuadrícula compacta con el número de citas por día. No hay desplazamiento lateral a 320 px y cada día, cita o control mide al menos 44 px.
- [x] CA9 · Dado un periodo sin citas (o sin resultados para el buscador o los filtros), cuando se abre, entonces el calendario se ve vacío y la lista dice que no hay citas en ese periodo, con el acceso a «Nueva cita».
- [x] CA10 (abuso) · Como dentista o asistente, intento ver citas que no son mías cambiando en la dirección el dentista del filtro, el periodo o la vista → solo se muestran las citas que hoy puedo ver (la dentista, las suyas; la asistente, las de sus dentistas), en el calendario, en la lista y en los números por día. Como cualquier rol, escribo en la dirección un día, una hora o un dentista no válidos para «Nueva cita» → el formulario los ignora o los muestra para corregir, y la cita se valida al guardarla como hoy.

## Fuera de alcance
- Mover o cambiar la duración de una cita arrastrándola en el calendario: reprogramar sigue en el detalle (spec 028).
- Un calendario para el paciente: «Mis citas» sigue como lista (decisión del usuario).
- La vista de un solo día por horas en el escritorio; columnas separadas por dentista.
- Huecos calculados con el horario de cada dentista: el hueco se ofrece por el horario de la clínica y la regla del dentista se comprueba al guardar (spec 027).
- Cambiar qué cuenta el resumen de cifras de arriba («Total», «Agendadas»…): sigue contando el rango que se muestra, que ahora es el periodo del calendario o el día elegido, con un título que lo dice. Tampoco cambian el detalle de la cita ni el formulario de «Nueva cita» más allá de recibir el día, la hora y el dentista.
- Exportar o imprimir el calendario.

## Seguridad y privacidad
- Datos sensibles involucrados: los mismos de la lista de hoy (nombre del paciente, dentista, estado y hora de la cita), según `docs/security.md`.
- Quién puede hacer qué: no cambia. La administración ve todas las citas; la dentista, las suyas; la asistente, las de sus dentistas asignados. El paciente no tiene calendario.
- Casos de abuso:
  - Como dentista o asistente, cambio el filtro de dentista, el periodo o la vista en la dirección para ver citas ajenas → solo veo las mías (CA10).
  - Como cualquier rol, paso un día, una hora o un dentista manipulados a «Nueva cita» → no se agenda nada fuera de las reglas de hoy (CA10).

## Auditoría
No aplica: la 049 no añade accesos, permisos ni acciones; consultar la agenda ya existe y agendar sigue
pasando por el alta de hoy, que ya se registra.

## Diseño
Quien está en recepción tiene que entender la semana de un golpe de vista y agendar en un hueco con
un toque, sin perder la lista para buscar. Sistema: [docs/design/system.md](../../design/system.md)
(componentes de la lista de citas, el filtro de la 034 y la cita que abre su detalle de la 048).

Pantallas que toca: «Citas» del personal (administración, asistente y dentista), en escritorio y en
móvil: el calendario semanal por horas, el mensual, la tira de días del móvil y la lista compacta;
«Nueva cita» solo recibe el día, la hora y el dentista. Qué debe diseñarse: la cuadrícula semanal
(franjas, citas que coinciden, huecos pulsables, fuera de horario, citas atenuadas), la cuadrícula
del mes, la tira de días, la lista compacta agrupada por día, la navegación del periodo y el
interruptor «Semana · Mes». Recursos de marca: los del sistema; colores por estado (los de las
pastillas de estado). `/design-spec 049` genera candidatos y los mide. Acta: [design.md](design.md) (c6, aceptado por el usuario con 78/100 y cinco refinados).

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos (6, ampliado por el usuario) · anchos 320, 390 y 1440 px. Pantallas medidas (datos ficticios, semana del 16 al 21 de noviembre de 2026): semana, semana con un día elegido, mes y semana vacía. Archivo: [design/criterios.json](design/criterios.json).

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | «Nueva cita», «Anterior», «Hoy», «Siguiente», «Semana» y «Mes» | visibles sin desplazarse a los tres anchos | CA1, CA2 |
| K2 | Estados y dentistas en texto | visibles «Agendada», «Confirmada», «Cancelada», «No asistió» y los nombres de las dentistas | CA1, CA6 |
| K3 | El calendario antes que la lista | orden: «Semana», «Mes», «Citas de la semana», a 1440 px (en el móvil el título nombra el día) | CA4 |
| K4 | Volver a todo el periodo con un día elegido | visibles «Ver toda la semana» y el día elegido | CA4 |
| K5 | Mes con el número de citas por día | visibles el mes y «3 citas» | CA3 |
| K6 | Semana vacía | visibles «No hay citas en esta semana» y «Nueva cita» | CA9 |
| K7 | Tira de días del móvil | el día de hoy («Mar 17») sin desplazarse a 320 y 390 px | CA8 |
| K8 | JavaScript de la página | ≤ 50 kB | P9 |
| K9 | Estabilidad visual | CLS ≤ 0,1 | modo operar |
| K10 | Títulos | ≤ 2 líneas | modo operar |

Puertas: las de `design-spec/references/criterios.md` §2 (contraste AA, toque ≥ 44 px por debajo de 1024, foco visible, solo colores del sistema, sin desbordes, todo sin JavaScript); fuentes de Google permitidas por la barra de la app, y la barra lateral fuera de la medida (no la diseña esta spec). Sin KPI propio: los huecos que agendan (CA7) y abrir la cita (CA5) son enlaces sin texto visible propio; los comprueban la medición en navegador y los tests de `/implement`. Rúbrica: `jerarquia` 30, `primera_pantalla` 25, `sistema` 20, `lectura` 15, `plantilla` 10 (en un calendario denso manda leerlo de un golpe; la diferencia frente a una plantilla pesa menos).

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución P9): el estado de cada cita en texto, no solo por color; contraste AA; foco visible; navegación del calendario y de la lista con teclado; objetivos de toque ≥ 44 px en el móvil; a 320, 390 y 1440 px.
- Funciona sin JavaScript: el calendario, la navegación, el cambio de vista, los días y los huecos son enlaces del servidor.
- La página de «Citas» con un mes completo (hasta ~300 citas visibles) responde en menos de 1 s, medido en local con datos equivalentes; al desplegar, el usuario comprueba en producción que el mes abre sin esperas.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-14 | brecha | ¿Solo calendario, calendario y lista, o solo lista? | Calendario y lista | usuario (2026-11-14) |
| 2026-11-14 | brecha | Vista al entrar | Semana con horas; se puede cambiar a mes | usuario (2026-11-14) |
| 2026-11-14 | brecha | ¿Quién tiene calendario? | Todo el personal; el paciente sigue con su lista | usuario (2026-11-14) |
| 2026-11-14 | brecha | ¿Cómo se distinguen las citas? | Color por estado; el dentista en el texto de la cita; el filtro «Dentista» deja ver uno solo | usuario (2026-11-14) |
| 2026-11-15 | brecha | ¿Qué citas muestra la lista? | Las del periodo visible y, al pulsar un día, las de ese día; buscador y filtros para las dos | usuario (2026-11-15) |
| 2026-11-15 | brecha | ¿Cómo es la lista compacta? | Hora, paciente, dentista y estado; la fila abre el detalle (spec 048); las acciones quedan en el detalle | usuario (2026-11-15) |
| 2026-11-15 | brecha | Canceladas y «no asistió» | Atenuadas, con su estado en texto | usuario (2026-11-15) |
| 2026-11-15 | brecha | ¿Se agenda pulsando un hueco? | Sí: «Nueva cita» con día, hora y, si hay uno solo, dentista | usuario (2026-11-15) |
| 2026-11-15 | implícita | Horas del calendario | Las del horario de la clínica: lunes a viernes 9:00–20:00, sábado 9:00–14:00, domingo cerrado | `config/clinica.php` (`horario_semanal`) |
| 2026-11-15 | implícita | Qué citas ve cada rol | Las de hoy en «Citas»: todas, las suyas o las de sus dentistas | `docs/security.md` (modelo de autorización), spec 014 |
| 2026-11-15 | contradicción | La 034 fija para la lista de citas tarjetas con todas sus columnas y acciones (CA2), filtros de fechas con los atajos «Hoy» y «Esta semana» (CA5) y que en el escritorio la tabla se vea igual que antes (CA11) | La 049 los reemplaza para la lista de «Citas»: lista compacta de cuatro datos que abre el detalle, y el periodo lo elige el calendario («Hoy», «Anterior», «Siguiente», «Semana · Mes») en lugar de las fechas «Desde» y «Hasta». El resto de la 034 (otras listas, el panel, el detalle) no cambia | usuario (respuestas del 2026-11-14 y 2026-11-15) |
| 2026-11-15 | supuesto | Móvil | Tira de los días con su número de citas y la lista del día elegido; mes en cuadrícula compacta | razonable (propuesta del 2026-11-14 que el usuario aceptó al elegir calendario y lista) |
| 2026-11-15 | supuesto | Tramos de los huecos | 30 minutos, la duración por defecto de una cita | razonable |
| 2026-11-15 | diseño | Criterios de diseño (K1–K10, rúbrica con `jerarquia` 30 y `plantilla` 10) | Aprobados | usuario (2026-11-15) |
| 2026-11-16 | diseño | Diseño del calendario | c6 aceptado bajo el umbral (78) con cinco refinados; acta en [design.md](design.md) | usuario (2026-11-16) |
| 2026-11-16 | supuesto | La tira del móvil (CA8) | Seis días (lunes a sábado), no siete: el domingo la clínica cierra y siete no caben a 320 px con objetivos de 44 px | `config/clinica.php`, /design-spec 049 |
| 2026-11-16 | contradicción | El resumen de cifras cuenta hoy el rango que muestra la lista, no el día de hoy (hallado en /plan 049, refinado 5 del acta) | Cuenta el periodo del calendario o el día elegido, con un título que lo dice; el «Fuera de alcance» lo recoge | usuario (2026-11-16) |
| 2026-11-16 | brecha | ¿Qué es un hueco libre? (/analyze A9) | Un tramo sin cita vigente a la vista: las canceladas y «no asistió» no lo ocupan; con una sola dentista a la vista, sus tramos libres; desde el siguiente tramo | usuario (2026-11-16) |
| 2026-11-17 | brecha | ¿Cuentan el buscador y el filtro de estado para los huecos? (/analyze A28) | No: los huecos se calculan con todas las citas vigentes del alcance; solo cuenta el filtro de dentista (o la vista de la dentista) | usuario (2026-11-17) |
| 2026-11-16 | brecha | ¿Dónde se mide el RNF de rendimiento? (/analyze A16) | En local con 300 citas (< 1 s) y, al desplegar, el usuario comprueba en producción que el mes abre sin esperas | usuario (2026-11-16) |
| 2026-11-15 | implícita | Descubribilidad | No aplica: «Citas» exige sesión y no se indexa | `docs/seo.md` |

## Notas para /plan
- Hoy: `CitasController::index` (filtros `fecha_desde`, `fecha_hasta`, `dentista_id`, `estado`, `buscar`; `paginate(25)`; resumen del día) y `resources/views/citas/index.blade.php` (tabla `tabla-tarjetas`, filtro de la 034 con `public/js/filtros.js`).
- `CitasController::create` ya acepta `?fecha=`, `?hora=` y `?dentista=` (los muestra como valores sugeridos sin validarlos); `store` valida todo.
- Reemplaza de la 034: CA2 (para la lista de «Citas»; las citas del panel siguen), CA5 y CA11 (para la lista de «Citas»). `MovilPersonalTest` fija hoy la tabla `citas-personal` (D2, filtro, atajos): habrá que actualizarlo.
- Reutiliza el componente «Cita que abre su detalle» (spec 048, `system.md` 1.17.0).
- Choques posibles: la 046 (otra sesión, sin liberar) añade migas en `citas/show` y toca `layouts/app`; no toca `citas/index`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-16 | CA8: tira de seis días (lunes a sábado) en lugar de siete; K3 medido a 1440 px; presupuesto 6; enlace al acta; vuelve a `draft` | /design-spec 049 (puerta G-toque y correcciones aceptadas por el usuario) |
| 2026-11-16 | Spec, acta y criterios aprobados de nuevo | usuario |
| 2026-11-16 | CA7 (qué es un hueco libre; desde el siguiente tramo), «Fuera de alcance» (el resumen cuenta el periodo, refinado 5), RNF de rendimiento (local y comprobación en producción), «cinco refinados»; vuelve a `draft` | `/analyze` r1: A8, A9, A16, A24, A26 |
| 2026-11-17 | CA7: el buscador y el filtro de estado no cuentan para los huecos; un tramo que empieza justo ahora ya no se ofrece | `/analyze` r2: A28, A30 |
| 2026-11-17 | Spec, acta, plan y tareas aprobados de nuevo tras `/analyze` r3 pass | usuario |
| 2026-11-17 | Implementada: CA1–CA10 con sus tests en verde (suite 1303); `implemented` | /implement 049 (T092) |
| 2026-11-17 | `/review` r1 `changes_requested` (R1–R12 y cuatro menores se corrigen con T030–T037); vuelve a `approved` | /review 049 |
| 2026-11-17 | Correcciones de /review r1 (T030–T037); suite 1318; `implemented` | /implement 049 |
| 2026-11-17 | `/review` r2 `changes_requested` (R36, R37, R38, R41 y R42 se corrigen con T038–T040); vuelve a `approved` | /review 049 --rerun |
| 2026-11-17 | Correcciones de /review r2 (T038–T040); suite 1322; `implemented` | /implement 049 |
