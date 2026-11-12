---
id: 047
slug: reportes-admin
status: approved
created: 2026-11-10
extends: []
---

# 047 · Reportes de la clínica para la administración

## Problema
La administración no tiene dónde ver cómo va la clínica: cuántas citas hubo en un periodo, cuántas se
cancelaron o quedaron sin asistir, cómo se reparte el trabajo entre los dentistas y qué servicios se
piden más. La entrada «Reportes» del menú y del panel de la administración lleva hoy a una página
«en construcción». Para responder esas preguntas hay que contar las citas a mano en la agenda, día
por día, y no queda nada que se pueda imprimir o presentar.

## Historias de usuario
- Como administrador, quiero ver cuántas citas hubo en un periodo y en qué estado quedaron, para conocer la tasa de cancelación y de inasistencia de la clínica.
- Como administrador, quiero ver las citas de cada dentista en ese periodo, para repartir la carga de trabajo.
- Como administrador, quiero ver qué servicios se piden más, para decidir qué ofrecer y promocionar.
- Como administrador, quiero elegir el periodo del reporte, para comparar meses o revisar un rango concreto.
- Como administrador, quiero imprimir el reporte (o guardarlo como PDF desde el navegador), para presentarlo fuera del sistema.

## Criterios de aceptación
- [x] CA1 · Dado un administrador, cuando abre «Reportes», entonces en lugar de la página «en construcción» ve tres reportes del mes en curso («Citas por estado», «Citas por dentista» y «Servicios más solicitados»), con el periodo consultado escrito en la página (fecha de inicio y de fin). Una cita pertenece al periodo por la fecha para la que está agendada, no por la fecha en que se creó.
- [x] CA2 · Dado un administrador en «Reportes», cuando elige un periodo con «Desde» y «Hasta» o con los atajos «Este mes», «Mes pasado» y «Este año», entonces los tres reportes se recalculan para ese periodo (los dos días extremos incluidos) y la página muestra el periodo elegido; al recargar la página o abrir su dirección en otra pestaña se ve el mismo periodo.
- [x] CA3 · Dado un periodo con citas, cuando el administrador mira «Citas por estado», entonces ve el total de citas del periodo y, para cada uno de los seis estados (agendada, confirmada, en curso, completada, cancelada y no asistió), cuántas hay y qué porcentaje del total representan, con un decimal, también los estados con cero; la suma de los seis números es el total. La tasa de cancelación y la de inasistencia aparecen destacadas.
- [x] CA4 · Dado un periodo con citas, cuando el administrador mira «Citas por dentista», entonces ve una fila por cada dentista con al menos una cita en el periodo (aunque hoy esté inactivo), con su total, sus completadas, sus canceladas y sus inasistencias, ordenadas de más a menos citas y, en caso de empate, por nombre y apellido; una fila final con los totales coincide con las cifras de «Citas por estado».
- [x] CA5 · Dado un periodo con citas, cuando el administrador mira «Servicios más solicitados», entonces ve una fila por cada servicio con al menos una cita no cancelada en el periodo, con cuántas citas no canceladas tiene y qué porcentaje de ellas representa, ordenadas de más a menos y, en caso de empate, por nombre. Las citas sin servicio se agrupan en la fila «Sin servicio», que se ordena por su número de citas como una fila más, y un servicio que ya no está activo sigue apareciendo con su nombre.
- [x] CA6 · Dado un periodo sin ninguna cita, cuando el administrador lo consulta, entonces la pantalla muestra una sola vez el mensaje «No hay citas en este periodo» en lugar del resumen y de las tres tablas (sin tablas vacías ni porcentajes sin sentido), y el selector de periodo sigue disponible.
- [x] CA7 · Dado un administrador, cuando pide un periodo que no es válido (una fecha que no existe o mal escrita, «Hasta» anterior a «Desde», falta una de las dos fechas, o un periodo de más de 366 días contando los dos días extremos), entonces la página le explica en español, junto al selector, qué debe corregir, no muestra cifras de ese periodo y nunca responde con una página de error.
- [x] CA8 · Dado un administrador con un reporte en pantalla, cuando lo imprime, entonces el papel lleva el nombre de la clínica («Melissa López N. · Odontología integral», en texto), el título «Reportes», el periodo consultado, la fecha en que se generó y los tres reportes con sus cifras, sin el menú, el selector de periodo ni los botones; ninguna fila queda cortada entre dos páginas y las cifras se leen en blanco y negro.
- [x] CA9 · (abuso) Dado un dentista, un asistente, un paciente o alguien sin sesión, cuando abre la dirección de «Reportes», con o sin un periodo en ella, entonces no ve ninguna cifra: con sesión recibe la respuesta «acceso denegado» y el intento queda en el registro de auditoría con quién, qué página y cuándo, como cualquier otro acceso denegado; sin sesión llega al inicio de sesión. La entrada «Reportes» solo aparece en el menú y el panel de la administración.
- [x] CA10 · (abuso) Dado cualquier periodo, también uno en el que un dentista o un servicio tenga una sola cita, cuando se muestran o imprimen los reportes, entonces solo contienen totales y los nombres de dentistas y servicios: ningún nombre, contacto ni dato de salud de pacientes, ninguna nota de cita ni la fecha y hora de una cita concreta.

## Fuera de alcance
- Ingresos o cualquier cifra de dinero: el sistema no registra pagos ni el precio cobrado, solo el precio de catálogo (decisión del usuario). Un reporte de ingresos pide antes una spec de cobros.
- El detalle cita por cita detrás de cada cifra y cualquier lista con pacientes (decisión del usuario): para eso está la agenda.
- Descargar los reportes como archivo (hoja de cálculo, PDF generado por el sistema): solo pantalla e impresión del navegador (decisión del usuario).
- Reportes para dentistas, asistentes o pacientes (decisión del usuario): solo la administración.
- Gráficas: las cifras van en tablas y números destacados; una barra de proporción junto a un número es decoración admitida, no un requisito.
- Otros reportes (pacientes nuevos, ocupación del horario, reprogramaciones, tiempo de espera) y filtros adicionales (por dentista, por servicio, por categoría).
- Comparar dos periodos en la misma pantalla y programar el envío de reportes por correo.
- Cambiar las cifras del panel de la administración (citas de hoy, pacientes activos): sigue como está.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno de pacientes. Los reportes solo muestran totales de citas y los nombres de dentistas y servicios, que la administración ya gestiona. Un total pequeño (una sola cita de un dentista en un día) no identifica a ningún paciente porque no se muestran pacientes, fechas ni horas concretas.
- Quién puede hacer qué: administrador → consultar e imprimir los reportes de toda la clínica. Dentista, asistente y paciente → denegado. Sin sesión → inicio de sesión. Nadie cambia datos desde esta pantalla: es solo de lectura.
- Casos de abuso:
  - Como dentista, asistente o paciente, escribo la dirección de «Reportes» para ver las cifras de la clínica o de mis compañeros → acceso denegado y registrado (CA9).
  - Como usuario malintencionado, pongo en la dirección fechas mal formadas, un rango invertido o un texto que no es una fecha para provocar un error o colar una instrucción en la consulta → la página lo rechaza con un mensaje y no muestra cifras ni detalles internos (CA7).
  - Como usuario malintencionado o por descuido, pido un periodo de muchos años para sobrecargar el sistema → el periodo está limitado a 366 días (CA7).
  - Como alguien que recibe un reporte impreso, busco datos de pacientes en él → no los hay (CA10).

## Auditoría
- Acceso denegado a «Reportes» de un usuario con sesión → queda registrado quién, qué página pidió, cuándo y con resultado denegado, como exige `docs/observability.md` para todo acceso denegado (CA9).
- La consulta de los reportes por la administración no genera evento: no es una lectura de datos sensibles (no hay ficha de paciente ni expediente; `docs/observability.md` → "Registro de auditoría") y no cambia nada.

## Diseño
Quien abre «Reportes» tiene que entender en cinco segundos cómo fue el periodo: cuántas citas hubo y
cuántas se perdieron por cancelación o inasistencia. Es una pantalla de trabajo, sobria, con el mismo
aspecto que el resto del panel de la administración. Sistema: [docs/design/system.md](../../design/system.md).

Pantallas que toca: una sola, «Reportes» de la administración, que reemplaza la página «en
construcción». Estados: con datos, periodo sin citas, periodo no válido y la versión impresa.
Recursos de marca: el nombre de la clínica, en texto y sin imagen, para la cabecera impresa. Qué debe diseñarse:
el selector de periodo con sus atajos, los números destacados del periodo (total, tasa de
cancelación y tasa de inasistencia), las tres tablas, cómo se acomodan en el móvil sin desbordarse y
la hoja impresa.

Acta de diseño: [design.md](design.md). Sin ciclo de candidatos de `/design-spec` (decisión del usuario): `/plan`
coloca las piezas con los componentes que ya existen y el usuario las valida en pantalla durante `/implement`.

## Criterios de diseño
Modo: operar · sin ciclo de candidatos (decisión del usuario) · la verificación es la del usuario en pantalla y los tests de `/implement`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | El periodo consultado y el total de citas | visibles sin desplazarse a 320 (pantalla de 320×568), 390 y 1440 px; por debajo de 900 px van antes del selector de periodo | CA1 |
| K2 | Selector de periodo y atajos | utilizables con teclado y con el dedo; objetivo de toque ≥ 44 px | CA2 |
| K3 | Tasas de cancelación e inasistencia | destacadas en la primera pantalla, con su texto (no solo color) | CA3 |
| K4 | Tablas «por dentista» y «por servicio» | se leen completas a 320 px sin desplazamiento horizontal de la página; cada cifra conserva su encabezado | CA4, CA5 |
| K5 | Estado «No hay citas en este periodo» | mensaje visible y selector disponible a 320, 390 y 1440 px | CA6 |
| K6 | Mensaje de periodo no válido | junto al selector, con texto y no solo color, anunciado a lectores de pantalla | CA7 |
| K7 | Hoja impresa | sin menú ni controles, con clínica, periodo y fecha, legible en blanco y negro | CA8 |

Puertas: las de `design-spec/references/criterios.md` §2 que apliquen (contraste AA, foco visible, toque ≥ 44 px, sin desbordes a 320 px).

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución P9): contraste AA, foco visible, objetivo de toque ≥ 44 px y sin desbordes a 320, 390 y 1440 px; las tablas tienen encabezados de fila y de columna que un lector de pantalla anuncia; ningún dato depende solo del color.
- Rendimiento: con 10 000 citas en un periodo de 366 días, la página responde en menos de 2 segundos en el entorno local.
- Exactitud: las cifras se calculan en el momento de la consulta con las citas tal como están entonces; dos consultas del mismo periodo sin cambios entre medias dan las mismas cifras.
- Descubribilidad: «Reportes» exige sesión y no se indexa (`docs/seo.md`).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-10 | brecha | ¿Qué reportes incluye la primera versión? | Citas por estado, citas por dentista y servicios más solicitados; ingresos estimados queda fuera | usuario |
| 2026-11-10 | brecha | ¿Solo totales o también el detalle con pacientes? | Solo totales, sin datos de pacientes | usuario |
| 2026-11-10 | brecha | ¿Cómo salen los reportes del sistema? | Pantalla e impresión desde el navegador; sin archivos descargables | usuario |
| 2026-11-10 | brecha | ¿Quién ve los reportes? | Solo la administración | usuario |
| 2026-11-10 | implícita | ¿Qué estados de cita existen? | Los seis de la agenda: agendada, confirmada, en curso, completada, cancelada y no asistió | docs/specs/026-estados-cita/spec.md |
| 2026-11-10 | implícita | ¿El acceso denegado se registra? | Sí, como todo acceso denegado de un usuario con sesión | docs/observability.md, docs/specs/020-auditoria-base/spec.md |
| 2026-11-10 | implícita | ¿Consultar los reportes es una lectura de datos sensibles que auditar? | No: solo se auditan las lecturas de ficha de paciente y de expediente, y aquí no hay datos de pacientes | docs/observability.md, docs/constitution.md P7 |
| 2026-11-10 | implícita | ¿A qué anchos debe funcionar la pantalla? | 320, 390 y 1440 px | .ai/project.yaml → design.widths |
| 2026-11-10 | implícita | ¿Número de la spec? | 047: la 045 (términos y privacidad) y la 046 (migas y «Volver») están reservadas | docs/specs/044-navegacion-salidas/spec.md |
| 2026-11-10 | supuesto | ¿Qué periodo se ve al entrar y cómo se elige otro? | El mes en curso; «Desde» y «Hasta» más los atajos «Este mes», «Mes pasado» y «Este año» | /specify |
| 2026-11-10 | supuesto | ¿Qué fecha decide a qué periodo pertenece una cita? | La fecha para la que está agendada; por eso el mes en curso incluye citas futuras aún agendadas | /specify |
| 2026-11-10 | supuesto | ¿Cuánto puede durar un periodo? | Como mucho 366 días (un año completo, también bisiesto) | /specify |
| 2026-11-10 | supuesto | ¿Qué citas cuenta «Servicios más solicitados»? | Las no canceladas (una cita cancelada no es demanda atendida ni por atender); las inasistencias sí cuentan | /specify |
| 2026-11-10 | supuesto | ¿Qué dentistas aparecen en «Citas por dentista»? | Los que tienen al menos una cita en el periodo, aunque hoy estén inactivos; los que no tienen citas no aparecen | /specify |
| 2026-11-10 | supuesto | ¿Con cuántos decimales van los porcentajes? | Uno | /specify |
| 2026-11-10 | supuesto | ¿Qué volumen sirve de referencia para el rendimiento? | 10 000 citas en 366 días, menos de 2 segundos en local | /specify |
| 2026-11-10 | diseño | ¿Ciclo de candidatos para «Reportes»? | Sin ciclo: componentes existentes del panel y validación en pantalla; acta en `design.md` | usuario |
| 2026-11-10 | cierre | ¿Se aprueba la spec con sus supuestos? | Aprobada sin cambios | usuario |
| 2026-11-11 | contradicción | CA6 pedía un mensaje de vacío por reporte y el `design.md` aprobado uno solo | Un solo mensaje para toda la pantalla; se corrige CA6 | usuario (/analyze A5) |
| 2026-11-11 | brecha | ¿Qué nombre y qué logo lleva la hoja impresa? | Solo texto, «Melissa López N. · Odontología integral», sin imagen | usuario (/analyze A9) |
| 2026-11-11 | brecha | ¿Con qué altura se mide «sin desplazarse» en el móvil y qué pasa si el selector empuja el total? | Se mide a 320×568 px; por debajo de 900 px la frase del periodo y el total van antes del selector; en escritorio el orden no cambia | usuario (/analyze A10) |
| 2026-11-11 | supuesto | ¿Cómo se cuentan los 366 días y cómo se desempata? | Contando los dos días extremos; «Sin servicio» se ordena como una fila más; el desempate de dentistas es por nombre y apellido | /analyze A16 |

## Notas para /plan
- La entrada actual es `/admin/reportes`, hoy una vista `placeholder` con el título «Reportes», enlazada desde el menú (`layouts/app.blade.php`) y el panel de la administración; `docs/design/capturar.mjs` la captura como `admin-reportes` y la usa como ejemplo de estado vacío.
- Al liberar, quitar «Reportes» de "Funcionalidades no documentadas como spec" en `docs/specs/README.md` y actualizar la mención de `docs/architecture.md` y la fila de `docs/design/system.md`.
- La spec 037 prevista (móvil de la administración) incluía «reportes»: esta pantalla nace ya adaptada al móvil, así que la 037 puede dejarla fuera.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-11 | CA6 (un solo mensaje de vacío), CA8 y Diseño (cabecera impresa en texto), K1 (altura de referencia y orden en el móvil), CA4, CA5 y CA7 (desempates y cuenta de los 366 días) | `/analyze` r1: A5, A9, A10 y A16 |
