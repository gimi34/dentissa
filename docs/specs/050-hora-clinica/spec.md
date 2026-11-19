---
id: 050
slug: hora-clinica
status: approved
created: 2026-11-17
extends: [002, 003, 004, 006, 007, 008, 010, 011, 012, 026, 038, 039, 049]
---

# 050 · La hora de la clínica en todo el panel

## Problema
Las citas se guardan con la hora de la clínica, pero casi todo el panel decide qué es «hoy» y «ahora»
con la hora universal, seis horas por delante de la clínica. Desde las 18:00 de la clínica y hasta la
medianoche, los paneles enseñan las citas de mañana como si fueran de hoy, las citas de las próximas
seis horas aparecen como pasadas y los formularios proponen o rechazan fechas con un día de desfase.
Además, la fecha en que el sistema registró algo («Agendada el…», el historial de la cita, el alta de
un expediente) sale seis horas adelantada a cualquier hora. Se vio en producción el 2026-11-17 a las
21:50 de la clínica. Es la deuda D4 que dejó la spec 026, que solo corrigió «la cita ya empezó».

## Historias de usuario
- Como personal de la clínica (administración, dentista o asistente), quiero que mi panel y mi agenda enseñen las citas del día de la clínica a cualquier hora, para no confundir las de mañana con las de hoy al final de la tarde.
- Como paciente, quiero que mi cita de esta noche siga apareciendo como próxima hasta que llegue su hora, para no creer que ya pasó.
- Como cualquier usuario del panel, quiero ver la hora en que se agendó o se cambió una cita en la hora de la clínica, para que coincida con la hora real.
- Como quien agenda una cita o registra una próxima visita, quiero que el formulario proponga y acepte fechas según el día de la clínica, para no tener que esquivar un día de desfase.

## Criterios de aceptación
En todos los ejemplos la clínica está en Ciudad de México (seis horas por detrás de la hora universal)
y «21:50 del 17» son las 21:50 del 17 de noviembre de 2026 en la clínica, que en hora universal ya son
las 03:50 del 18.

- [ ] CA1 · Dado que en la clínica son las 21:50 del 17 y hay citas el 17 y el 18, cuando la administración, una dentista o una asistente abre su panel de inicio, entonces la fecha del encabezado es el martes 17 de noviembre (en el formato de cada panel) y la lista y las cuentas «de hoy» (citas programadas, agenda de hoy, próximo paciente) son las del 17; ninguna cita del 18 aparece como de hoy.
- [ ] CA2 · Dado un paciente con una cita a las 23:00 del 17 y otra a las 21:00 del 17, y que en la clínica son las 21:50 del 17, cuando abre su panel y «Mis citas», entonces la de las 23:00 aparece como su próxima cita y en las próximas, y la de las 21:00 aparece entre las pasadas y en el historial.
- [ ] CA3 · Dado un paciente con una cita a las 23:00 del 17 y que en la clínica son las 21:50 del 17, cuando el personal abre la ficha de ese paciente, entonces esa cita aparece como su próxima cita; y la edad de la ficha cambia el día del cumpleaños en la clínica (la víspera, a las 21:50, aún muestra la edad anterior).
- [ ] CA4 · Dado que en la clínica son las 21:50 del 17, cuando el personal abre «Citas» (el calendario de la spec 049), entonces ve la semana del 16 al 21, el 17 marcado como hoy en la cabecera de la semana, en la tira del móvil y en la lista, y «Hoy» lleva a esa semana.
- [ ] CA5 · Dado que en la clínica son las 21:50 del 17, cuando alguien abre un formulario con fechas, entonces el calendario no deja elegir días anteriores al 17 para una cita ni anteriores al 18 para una próxima visita, la fecha sugerida para agendar es el 18, y el servidor acepta una cita del paciente para el 17 y una próxima visita para el 18; la fecha de nacimiento acepta el 16 y rechaza el 17, y su calendario sigue proponiendo como mucho la de hace un año, contada desde el día de la clínica; el aviso de próxima visita vencida o cercana del expediente cuenta los días desde el 17; y el año del pie de las páginas públicas es el de la clínica.
- [ ] CA6 · Dado que en la clínica son las 20:00 del 30 de noviembre, cuando la administración abre la ficha de una dentista, entonces «citas este mes» cuenta las de noviembre; y dado el 31 de diciembre a las 20:00 en la clínica, el panel de la dentista cuenta los pacientes atendidos en diciembre.
- [ ] CA7 · Dada una cita agendada a las 21:50 del 17 en la clínica y cambiada de estado a las 22:10, cuando el personal abre su detalle y el paciente abre el suyo, entonces «Agendada el» dice 17/11/2026 21:50 y el historial muestra el cambio a las 22:10 del 17; del mismo modo, la fecha de alta de un expediente, de un paciente y de un usuario, y la del consentimiento de una foto de la galería, se muestran en el día y la hora de la clínica, también para lo registrado antes de esta spec.
- [ ] CA8 · Dado que en la clínica son las 17:59, las 18:00 y las 23:59 del 17, cuando se repite cualquiera de los casos anteriores, entonces «hoy» es el 17 en los tres momentos; y a las 00:00 del 18 en la clínica, «hoy» pasa a ser el 18.
- [ ] CA9 · Dado que esta spec se libera, cuando se comprueba lo que ya usaba la hora de la clínica (si una cita ya empezó, el plazo de 24 horas del paciente, la reprogramación, los horarios de las dentistas, los reportes, el registro de auditoría y la hora de los correos), entonces todo eso se comporta igual que antes.
- [ ] CA10 (abuso) · Dado que en la clínica son las 21:50 del 17, cuando un paciente envía a mano una petición para agendar una cita el 16, o el personal registra a mano una próxima visita para el 17, entonces el servidor la rechaza con un mensaje en español que dice que la fecha no puede ser anterior a hoy (o, en la próxima visita, que debe ser posterior a hoy), aunque el calendario del formulario no se haya usado; la fecha de nacimiento del alta de paciente se rechaza igual con «La fecha de nacimiento debe ser anterior a hoy.».

## Fuera de alcance
- Cambiar la hora con la que trabaja el sistema por dentro: sigue en hora universal (ver «Decisiones»).
- Varias clínicas o varias zonas horarias, y que cada usuario vea la hora de su propio lugar: todos ven la hora de la clínica.
- Indicar la zona horaria junto a las horas («hora de Ciudad de México»).
- Reescribir las fechas ya guardadas: se muestran convertidas, sin tocar los datos.
- El reloj del servidor y su sincronización, que son cosa del despliegue.
- Cambiar el calendario de citas de la spec 049 (liberada en v0.21.0): ya calcula con la hora de la clínica; la 050 solo lo fija con un test (CA4).
- Recordatorios de cita y el enlace a WhatsApp de la página de inicio, que tendrán sus propias specs.
- La autorización por recurso (Gates) y la auditoría de lecturas y cambios de los endpoints que la 050 toca: conservan las de hoy (excepciones P3 y P7 del plan, aprobadas por el usuario el 2026-11-18); son deuda de las specs 014, 015 (el grupo `/cliente`) y 022 (`docs/security.md`, `docs/observability.md`).

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno nuevo. Las pantallas muestran los mismos datos de citas, pacientes y expedientes que ya enseñan; solo cambia qué día o qué hora se toma como referencia.
- Quién puede hacer qué: sin cambios. Cada rol ve las mismas pantallas y los mismos registros que hoy.
- Casos de abuso:
  - Como paciente, envío a mano una fecha pasada (según el día de la clínica) para agendar una cita, o como personal una próxima visita que no es futura → se rechaza en el servidor (CA10).

## Auditoría
No aplica: la spec no cambia quién accede a qué ni crea acciones nuevas sobre datos sensibles. El
registro de auditoría ya guarda y filtra sus fechas con la hora de la clínica (spec 020) y no cambia
(CA9).

## Diseño
Sin cambios visuales: las mismas pantallas, con los mismos componentes, enseñan el día y la hora de la
clínica. Pantallas afectadas: los paneles de inicio de los cuatro roles, la agenda de citas, «Mis
citas» y el detalle de la cita (personal y paciente), la ficha del paciente y de la dentista, los
expedientes, la gestión de la galería, los formularios de cita, de expediente, de registro y de perfil, y el pie
de las páginas públicas. No necesita `/design-spec`: no hay nada que diseñar. Sistema:
[docs/design/system.md](../../design/system.md), sin cambios.

## Criterios de diseño
No aplica: ningún criterio cambia la forma de una pantalla, solo el día o la hora que muestra. La
única puerta es que las pantallas afectadas se vean igual que antes de la spec.

## Requisitos no funcionales
- Cada criterio se comprueba con la hora fijada dentro de la franja de 18:00 a 23:59 de la clínica, donde hoy falla, además de en los bordes de CA8 (constitución P2).
- La zona de la clínica es una sola y se configura en un único lugar; todas las pantallas la toman de ahí.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | implícita | ¿Pasar el sistema entero a la hora de la clínica? | No: el sistema sigue en hora universal y solo «hoy», «ahora» y lo que se muestra usan la hora de la clínica | `docs/specs/026-estados-cita/plan.md` D4 (opción descartada) y la 020 (`ocurrido_en` en UTC) |
| 2026-11-17 | implícita | ¿Qué hora es la de referencia? | La zona configurada de la clínica, la misma que ya decide si una cita empezó | `docs/roadmap.md` (deuda D4 de la 026); `config/clinica.php` |
| 2026-11-17 | implícita | Las aserciones de la 026 que dan por buena la hora universal (026/R6) | Se corrigen en esta spec | `docs/roadmap.md` (026/R6) |
| 2026-11-17 | implícita | Correos, auditoría, reportes, «ya empezó», plazo del paciente y reprogramación | Ya usan la hora de la clínica; no cambian (CA9) | `app/Agenda/EstadosCita.php`, `app/Auditoria/AlmacenAuditoriaBaseDatos.php`, `app/Reportes/ReporteCitas.php`, `app/Mail/` |
| 2026-11-17 | brecha | La spec cambia nueve specs (más de cuatro; trece desde /analyze A8): ¿una o dos? | Una sola spec: una sola regla con una sola causa | usuario |
| 2026-11-17 | supuesto | ¿En qué hora están las fechas que registra el sistema? | En hora universal; se muestran convertidas, también las anteriores a esta spec, sin migrar datos. `/plan` comprueba las que pone la propia base por defecto | — |
| 2026-11-17 | supuesto | ¿Se indica la zona junto a la hora? | No; todas las horas del panel son de la clínica | — |
| 2026-11-17 | supuesto | ¿Entran la fecha de nacimiento y el año del pie, con un efecto mínimo? | Sí: misma regla en todo el panel (CA5) | — |
| 2026-11-17 | supuesto | Calendario de la 049, aún sin fusionar (superado: la 049 se liberó en v0.21.0, ver la fila del 2026-11-18) | Sigue esta regla; la rama que se fusione segunda se adapta | — |
| 2026-11-18 | brecha | La 049 se liberó (v0.21.0) sin filtro de fechas ni atajos, que CA4 nombraba (/analyze A6) | CA4 se comprueba sobre el calendario de la 049: semana, hoy en la cabecera, la tira y la lista, y «Hoy» | usuario (2026-11-18) |
| 2026-11-18 | brecha | ¿Entra el consentimiento de la galería en CA7? (/analyze A9) | Sí: es una fecha que guarda el sistema en UTC | usuario (2026-11-18) |
| 2026-11-18 | brecha | El calendario de nacimiento propone hoy como mucho «hace un año» (/analyze A7) | Se mantiene, contado desde el día de la clínica | usuario (2026-11-18) |
| 2026-11-18 | brecha | La edad de la ficha se calcula con la hora universal (/analyze A10) | Entra: se calcula con el día de la clínica (CA3) | usuario (2026-11-18) |
| 2026-11-17 | contradicción | Tres reglas de fecha (cita del paciente, próxima visita, nacimiento en el alta de paciente) muestran hoy el mensaje de Laravel en inglés con «today»; con el día de la clínica mostrarían la fecha (CA10 pedía «el mensaje de siempre») | Mensajes en español sin la fecha, como los del perfil y el registro (CA10) | usuario (en /plan 050) |

## Notas para /plan
- Mantener `app.timezone` en UTC. Patrón de referencia: `HorarioDentista.php:66` (`Carbon::now(config('clinica.zona_horaria'))`).
- Puntos conocidos (2026-11-17, sobre `f801977`): `DashboardController.php:15,59,121,198,228`; `ClienteCitasController.php:50,52,298,310`; `PacientesController.php:91,218,272`; `CitasController.php:120-121,217`; `PersonalController.php:211-212,456`; `ExpedientesController.php:120`; `PerfilController.php:38`; `RegisterController.php:36,126`.
- Vistas con `now()` o comparaciones: `dashboard/dentista`, `admin`, `asistente`, `citas/index` (antes de la 049; tras ella, el calendario ya usa la hora de la clínica), `citas/create`, `citas/cliente-agendar`, `expedientes/show` (152, 164-168) y `create` (120), `cliente/perfil`, `auth/register`, `auth/registro-contacto`, `pacientes/create`, `layouts/publico` (año), `dashboard/cliente` (`date('N')` del consejo del día).
- Fechas del sistema mostradas sin convertir: `citas/show.blade.php:146,218`, `citas/cliente-show.blade.php:74`, `expedientes/index` y `show`, `pacientes/index` y `show`, `usuarios/show`.
- `historial_citas.fecha` y varios `creado_en` tienen `DEFAULT current_timestamp()` en el volcado: comprobar en qué zona corre la base en local y en producción.
- Tests que fijan hoy la hora UTC (026/R6): `HistorialCitaTest.php:113` y `EstadosCitaVistasTest.php:120`.
- La 049 (v0.21.0) ya calcula con la hora de la clínica; la 050 se escribe sobre ella (superado lo de revisarla al fusionar).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-17 | CA10: mensaje en español en lugar de «el mensaje de siempre» | Decisión del usuario en /plan 050: el mensaje de hoy está en inglés y cambiaría de todos modos |
| 2026-11-18 | CA1 (formato de cada panel), CA3 (edad), CA4 (calendario de la 049), CA5 (calendario de nacimiento), CA7 (galería); `extends` con 010, 038, 039 y 049; Diseño, Fuera de alcance y Decisiones; vuelve a `draft` | `/analyze` r1: A6, A7, A8, A9, A10, A16, A20 |
| 2026-11-18 | Decisiones y Notas al día con la 049 liberada; la 015 en «Fuera de alcance» | `/analyze` r2: A22, A23 |
