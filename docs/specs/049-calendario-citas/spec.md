---
id: 049
slug: calendario-citas
status: approved
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
- [ ] CA1 · Dado el personal en «Citas», cuando entra sin elegir nada, entonces ve la semana actual de lunes a sábado como calendario por horas, desde la apertura hasta el cierre de la clínica (sábado hasta su cierre; lo que queda fuera del horario se distingue), con cada cita en su día y su hora, ocupando lo que dura, y mostrando su hora, el paciente, el dentista y su estado en texto; el color de la cita depende de su estado.
- [ ] CA2 · Dado el calendario, cuando el personal usa «Anterior», «Siguiente» u «Hoy», o cambia entre «Semana» y «Mes», entonces ve el periodo y la vista pedidos, y la dirección de la página los recoge (al recargarla o compartirla se ve lo mismo); sin JavaScript funciona igual. Con un periodo o una vista no válidos en la dirección, se ve la semana actual, sin error.
- [ ] CA3 · Dada la vista «Mes», cuando se abre, entonces se ve el mes en cuadrícula, con el número de citas de cada día; pulsar un día muestra en la lista solo las citas de ese día.
- [ ] CA4 · Dado el calendario, cuando se mira la lista de debajo, entonces muestra las citas del periodo visible (la semana o el mes), agrupadas por día y en orden de hora, con hora, paciente, dentista y estado; al pulsar un día en el calendario, muestra solo las de ese día, con una forma visible de volver a todo el periodo. El buscador y los filtros de dentista y estado se aplican a la vez al calendario y a la lista.
- [ ] CA5 · Dada una cita en el calendario o en la lista, cuando se pulsa en cualquier parte de ella (o se elige con el teclado y se pulsa Intro), entonces se abre su detalle, como en el panel de inicio (spec 048); cada cita es una sola parada de foco y su nombre accesible dice hora, paciente y dentista.
- [ ] CA6 · Dadas dos o más citas que coinciden en el tiempo (de dentistas distintos), cuando se ven en la semana, entonces aparecen una junto a otra, todas legibles y pulsables. Las canceladas y las de «no asistió» se ven atenuadas, con su estado en texto, sin tapar a las vigentes.
- [ ] CA7 · Dado un hueco libre del calendario semanal, dentro del horario de la clínica y a partir de ahora, cuando el personal lo pulsa, entonces llega a «Nueva cita» con ese día y esa hora (en tramos de 30 minutos) ya puestos y, si el calendario muestra un solo dentista (la vista de la dentista o el filtro de uno), también ese dentista. Los huecos pasados o fuera del horario no se pueden pulsar. Agendar sigue las reglas de hoy (agenda única, spec 025; horarios, spec 027).
- [ ] CA8 · Dado el personal en el móvil (por debajo de 900 px), cuando abre «Citas», entonces la semana se ve como una tira de los siete días con el número de citas de cada uno y, debajo, la lista del día elegido (por defecto, hoy); la vista «Mes» es una cuadrícula compacta con el número de citas por día. No hay desplazamiento lateral a 320 px y cada día, cita o control mide al menos 44 px.
- [ ] CA9 · Dado un periodo sin citas (o sin resultados para el buscador o los filtros), cuando se abre, entonces el calendario se ve vacío y la lista dice que no hay citas en ese periodo, con el acceso a «Nueva cita».
- [ ] CA10 (abuso) · Como dentista o asistente, intento ver citas que no son mías cambiando en la dirección el dentista del filtro, el periodo o la vista → solo se muestran las citas que hoy puedo ver (la dentista, las suyas; la asistente, las de sus dentistas), en el calendario, en la lista y en los números por día. Como cualquier rol, escribo en la dirección un día, una hora o un dentista no válidos para «Nueva cita» → el formulario los ignora o los muestra para corregir, y la cita se valida al guardarla como hoy.

## Fuera de alcance
- Mover o cambiar la duración de una cita arrastrándola en el calendario: reprogramar sigue en el detalle (spec 028).
- Un calendario para el paciente: «Mis citas» sigue como lista (decisión del usuario).
- La vista de un solo día por horas en el escritorio; columnas separadas por dentista.
- Huecos calculados con el horario de cada dentista: el hueco se ofrece por el horario de la clínica y la regla del dentista se comprueba al guardar (spec 027).
- Cambiar el resumen de cifras de arriba («Total», «Agendadas»…), el detalle de la cita o el formulario de «Nueva cita» más allá de recibir el día, la hora y el dentista.
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
pastillas de estado). `/design-spec 049` genera candidatos y los mide.

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos (`.ai/project.yaml`). `/design-spec 049` completa los KPIs y puertas.

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución P9): el estado de cada cita en texto, no solo por color; contraste AA; foco visible; navegación del calendario y de la lista con teclado; objetivos de toque ≥ 44 px en el móvil; a 320, 390 y 1440 px.
- Funciona sin JavaScript: el calendario, la navegación, el cambio de vista, los días y los huecos son enlaces del servidor.
- La página de «Citas» con un mes completo (hasta ~300 citas visibles) responde en menos de 1 s en el servidor de producción.

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
| 2026-11-15 | supuesto | Móvil | Tira de los siete días con su número de citas y la lista del día elegido; mes en cuadrícula compacta | razonable (propuesta del 2026-11-14 que el usuario aceptó al elegir calendario y lista) |
| 2026-11-15 | supuesto | Tramos de los huecos | 30 minutos, la duración por defecto de una cita | razonable |
| 2026-11-15 | implícita | Descubribilidad | No aplica: «Citas» exige sesión y no se indexa | `docs/seo.md` |

## Notas para /plan
- Hoy: `CitasController::index` (filtros `fecha_desde`, `fecha_hasta`, `dentista_id`, `estado`, `buscar`; `paginate(25)`; resumen del día) y `resources/views/citas/index.blade.php` (tabla `tabla-tarjetas`, filtro de la 034 con `public/js/filtros.js`).
- `CitasController::create` ya acepta `?fecha=`, `?hora=` y `?dentista=` (los muestra como valores sugeridos sin validarlos); `store` valida todo.
- Reemplaza de la 034: CA2 (para la lista de «Citas»; las citas del panel siguen), CA5 y CA11 (para la lista de «Citas»). `MovilPersonalTest` fija hoy la tabla `citas-personal` (D2, filtro, atajos): habrá que actualizarlo.
- Reutiliza el componente «Cita que abre su detalle» (spec 048, `system.md` 1.17.0).
- Choques posibles: la 046 (otra sesión, sin liberar) añade migas en `citas/show` y toca `layouts/app`; no toca `citas/index`.
