---
id: 050
slug: hora-clinica
status: draft
created: 2026-11-17
extends: [002, 003, 004, 006, 007, 008, 011, 012, 026]
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

- [ ] CA1 · Dado que en la clínica son las 21:50 del 17 y hay citas el 17 y el 18, cuando la administración, una dentista o una asistente abre su panel de inicio, entonces la fecha del encabezado es el martes 17 de noviembre y la lista y las cuentas «de hoy» (citas programadas, agenda de hoy, próximo paciente) son las del 17; ninguna cita del 18 aparece como de hoy.
- [ ] CA2 · Dado un paciente con una cita a las 23:00 del 17 y otra a las 21:00 del 17, y que en la clínica son las 21:50 del 17, cuando abre su panel y «Mis citas», entonces la de las 23:00 aparece como su próxima cita y en las próximas, y la de las 21:00 aparece entre las pasadas y en el historial.
- [ ] CA3 · Dado un paciente con una cita a las 23:00 del 17 y que en la clínica son las 21:50 del 17, cuando el personal abre la ficha de ese paciente, entonces esa cita aparece como su próxima cita.
- [ ] CA4 · Dado que en la clínica son las 21:50 del 17, cuando el personal abre la agenda de citas, entonces el filtro propone el 17 como fecha, el atajo «Hoy» muestra las citas del 17, «Esta semana» la semana que contiene el 17, la vista de los próximos días empieza el 17 y la marca de cita de hoy o futura de cada fila corresponde al día y la hora de la clínica.
- [ ] CA5 · Dado que en la clínica son las 21:50 del 17, cuando alguien abre un formulario con fechas, entonces el calendario no deja elegir días anteriores al 17 para una cita ni anteriores al 18 para una próxima visita, la fecha sugerida para agendar es el 18, y el servidor acepta una cita del paciente para el 17 y una próxima visita para el 18; la fecha de nacimiento acepta el 16 y rechaza el 17; el aviso de próxima visita vencida o cercana del expediente cuenta los días desde el 17; y el año del pie de las páginas públicas es el de la clínica.
- [ ] CA6 · Dado que en la clínica son las 20:00 del 30 de noviembre, cuando la administración abre la ficha de una dentista, entonces «citas este mes» cuenta las de noviembre; y dado el 31 de diciembre a las 20:00 en la clínica, el panel de la dentista cuenta los pacientes atendidos en diciembre.
- [ ] CA7 · Dada una cita agendada a las 21:50 del 17 en la clínica y cambiada de estado a las 22:10, cuando el personal abre su detalle y el paciente abre el suyo, entonces «Agendada el» dice 17/11/2026 21:50 y el historial muestra el cambio a las 22:10 del 17; del mismo modo, la fecha de alta de un expediente, de un paciente y de un usuario se muestra en el día y la hora de la clínica, también para lo registrado antes de esta spec.
- [ ] CA8 · Dado que en la clínica son las 17:59, las 18:00 y las 23:59 del 17, cuando se repite cualquiera de los casos anteriores, entonces «hoy» es el 17 en los tres momentos; y a las 00:00 del 18 en la clínica, «hoy» pasa a ser el 18.
- [ ] CA9 · Dado que esta spec se libera, cuando se comprueba lo que ya usaba la hora de la clínica (si una cita ya empezó, el plazo de 24 horas del paciente, la reprogramación, los horarios de las dentistas, los reportes, el registro de auditoría y la hora de los correos), entonces todo eso se comporta igual que antes.
- [ ] CA10 (abuso) · Dado que en la clínica son las 21:50 del 17, cuando un paciente envía a mano una petición para agendar una cita el 16, o el personal registra a mano una próxima visita para el 17, entonces el servidor la rechaza con el mensaje de fecha no válida de siempre, aunque el calendario del formulario no se haya usado.

## Fuera de alcance
- Cambiar la hora con la que trabaja el sistema por dentro: sigue en hora universal (ver «Decisiones»).
- Varias clínicas o varias zonas horarias, y que cada usuario vea la hora de su propio lugar: todos ven la hora de la clínica.
- Indicar la zona horaria junto a las horas («hora de Ciudad de México»).
- Reescribir las fechas ya guardadas: se muestran convertidas, sin tocar los datos.
- El reloj del servidor y su sincronización, que son cosa del despliegue.
- El calendario de citas de la spec 049, que va en su propia rama: debe seguir la misma regla (ver «Notas para /plan»).
- Recordatorios de cita y el enlace a WhatsApp de la página de inicio, que tendrán sus propias specs.

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
expedientes, los formularios de cita, de expediente, de registro y de perfil, y el pie de las páginas
públicas. No necesita `/design-spec`: no hay nada que diseñar. Sistema:
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
| 2026-11-17 | brecha | La spec cambia nueve specs (más de cuatro): ¿una o dos? | Una sola spec: una sola regla con una sola causa | usuario |
| 2026-11-17 | supuesto | ¿En qué hora están las fechas que registra el sistema? | En hora universal; se muestran convertidas, también las anteriores a esta spec, sin migrar datos. `/plan` comprueba las que pone la propia base por defecto | — |
| 2026-11-17 | supuesto | ¿Se indica la zona junto a la hora? | No; todas las horas del panel son de la clínica | — |
| 2026-11-17 | supuesto | ¿Entran la fecha de nacimiento y el año del pie, con un efecto mínimo? | Sí: misma regla en todo el panel (CA5) | — |
| 2026-11-17 | supuesto | Calendario de la 049, aún sin fusionar | Sigue esta regla; la rama que se fusione segunda se adapta | — |

## Notas para /plan
- Mantener `app.timezone` en UTC. Patrón de referencia: `HorarioDentista.php:66` (`Carbon::now(config('clinica.zona_horaria'))`).
- Puntos conocidos (2026-11-17, sobre `f801977`): `DashboardController.php:15,59,121,198,228`; `ClienteCitasController.php:50,52,298,310`; `PacientesController.php:91,218,272`; `CitasController.php:120-121,217`; `PersonalController.php:211-212,456`; `ExpedientesController.php:120`; `PerfilController.php:38`; `RegisterController.php:36,126`.
- Vistas con `now()` o comparaciones: `dashboard/dentista`, `admin`, `asistente`, `citas/index` (incl. `isToday`/`isFuture` en 138-139 y atajos en 68-79), `citas/create`, `citas/cliente-agendar`, `expedientes/show` (152, 164-168) y `create` (120), `cliente/perfil`, `auth/register`, `auth/registro-contacto`, `pacientes/create`, `layouts/publico` (año), `dashboard/cliente` (`date('N')` del consejo del día).
- Fechas del sistema mostradas sin convertir: `citas/show.blade.php:146,218`, `citas/cliente-show.blade.php:74`, `expedientes/index` y `show`, `pacientes/index` y `show`, `usuarios/show`.
- `historial_citas.fecha` y varios `creado_en` tienen `DEFAULT current_timestamp()` en el volcado: comprobar en qué zona corre la base en local y en producción.
- Tests que fijan hoy la hora UTC (026/R6): `HistorialCitaTest.php:88` y `EstadosCitaVistasTest.php:120`.
- La 049 (`feature/049-calendario-citas`, worktree `dentissa-041`) puede tener su propio «hoy»: revisarlo al fusionar.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
