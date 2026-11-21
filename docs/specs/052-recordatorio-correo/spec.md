---
id: 052
slug: recordatorio-correo
status: approved
created: 2026-11-20
extends: [003, 004, 028]
---

# 052 · Recordatorio de cita por correo

## Problema
Hoy nadie avisa al paciente de que tiene una cita: si la agendó hace días o se la agendó el personal,
depende de su memoria. Cuando la olvida, el hueco del dentista se pierde y la cita acaba como «no
asistió». El sistema ya enseña al personal una marca de «Recordatorio enviado» en el detalle de la
cita, pero nunca se envía ninguno, así que la marca no aparece nunca. Esta spec envía un único
recordatorio por correo al paciente un día antes de su cita y cierra el pendiente 028/R11 (al
reprogramar, la marca de «enviado» seguía valiendo para la fecha nueva).

## Historias de usuario
- Como paciente con una cita agendada o confirmada, quiero recibir un correo un día antes con el día,
  la hora, el dentista y la dirección, para no olvidarla ni tener que buscar los datos.
- Como paciente al que le cambiaron la cita de fecha, quiero que el recordatorio sea de la fecha
  nueva, para no presentarme el día equivocado.
- Como personal de la clínica (administración, dentista o asistente), quiero ver en el detalle de la
  cita si el recordatorio ya salió, para saber si el paciente fue avisado antes de llamarle.

## Criterios de aceptación
Todas las horas son las de la clínica (spec 050). «Revisión» es la comprobación periódica que decide
qué recordatorios tocan; no es algo que haga una persona.

- [ ] CA1 · Dada una cita agendada o confirmada de un paciente con cuenta activa que se agendó con más
  de 24 h de antelación, cuando faltan 24 h para su hora, entonces llega al correo de la cuenta del
  paciente, entre 24 h y 23 h 50 min antes de la cita, un único correo en español de Dentissa con: el
  nombre del paciente, el día y la hora de la cita (p. ej. «jueves 26 de noviembre de 2026, 10:30»),
  el nombre del dentista, el servicio (si la cita tiene uno), la dirección y el teléfono de la clínica
  que muestra la página de inicio (038 CA5) y un enlace «Ver mis citas».
- [ ] CA2 · Dada una cita agendada o confirmada que se crea o se reprograma cuando faltan menos de
  24 h para su hora, cuando se guarda, entonces el recordatorio de CA1 llega en menos de 10 minutos;
  si la cita empieza antes de que salga, no se envía.
- [ ] CA3 · Dada una cita cuyo recordatorio ya salió, cuando la revisión vuelve a pasar o pasan dos
  revisiones a la vez, entonces no llega un segundo recordatorio para esa misma fecha y hora.
- [ ] CA4 · Dada una cita con el recordatorio ya enviado, cuando el personal o el paciente la
  reprograman (spec 028), entonces deja de constar como enviado y llega un recordatorio nuevo para la
  fecha nueva según CA1 o CA2. Cierra 028/R11.
- [ ] CA5 · Dada una cita cancelada, en curso, completada o marcada como «no asistió», o una que ya
  empezó, cuando llegaría el momento de su recordatorio, entonces no se envía; una cita cancelada
  después de recibir el recordatorio no genera ningún otro correo.
- [ ] CA6 · Dado un paciente cuya cuenta está desactivada, cuando llega el momento del recordatorio de
  una de sus citas, entonces no se envía.
- [ ] CA7 · Dado un miembro del personal que puede ver una cita, cuando abre su detalle, entonces ve
  «Recordatorio · Enviado» si el recordatorio de la fecha actual de la cita ya salió, y no lo ve si
  aún no ha salido o si la cita se reprogramó después del envío.
- [ ] CA8 · Dado un fallo del servicio de correo al enviar un recordatorio, cuando la revisión no
  logra enviarlo, entonces la cita no queda como enviada, se reintenta en la siguiente revisión
  mientras la cita no haya empezado y el fallo queda en el registro técnico sin el correo, el nombre
  ni los datos de la cita.
- [ ] CA9 · (abuso) Como alguien que recibe un recordatorio reenviado o abre el buzón de otra
  persona, uso el enlace «Ver mis citas» → sin sesión lleva al inicio de sesión; el correo no lleva
  ningún enlace que dé acceso a la cuenta ni que cambie, confirme o cancele la cita.
- [ ] CA10 · (abuso) Como alguien que lee un recordatorio ajeno, busco datos de salud → el correo no
  incluye las notas del paciente, las notas internas, el expediente ni ningún otro dato fuera de los
  de CA1.
- [ ] CA11 · (abuso) Como alguien que crea o reprograma citas en masa para agotar el envío de correo
  de la clínica → como mucho 250 recordatorios al día en total, para que los correos de recuperación
  de contraseña (spec 041) y los avisos de producción (spec 032) sigan saliendo; los que excedan el
  tope no se envían ese día, salen en la siguiente revisión del día siguiente si la cita no ha
  empezado, y el exceso queda en el registro técnico sin datos personales.

## Fuera de alcance
- Recordatorios automáticos por WhatsApp o SMS (decisión del usuario, 2026-11-20). WhatsApp solo
  aparece como botón «Escríbenos» en la página de inicio (spec 053).
- Confirmar, cancelar o reprogramar la cita desde el correo: el correo es solo informativo.
- Un segundo recordatorio (p. ej. 2 h antes) o elegir con cuánta antelación llega.
- Correos de cita nueva, cancelada o reprogramada.
- Que el paciente pueda darse de baja de los recordatorios o elegir no recibirlos.
- Recordatorios al dentista o al personal, y reenviar un recordatorio a mano desde el panel.
- Mostrar al paciente en su panel si el recordatorio salió.
- Correos en otros idiomas.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales (nombre y correo del paciente, día y hora de la
  cita, dentista y servicio) que salen de la aplicación hacia el buzón del propio paciente. El
  servicio puede insinuar un tratamiento, pero no se envía ningún dato del expediente ni notas
  (`docs/security.md` → Clasificación de datos). No toca credenciales.
- Quién puede hacer qué: nadie dispara el recordatorio a mano; lo envía la aplicación solo al correo
  de la cuenta del paciente de la cita. El personal que ya puede ver la cita ve si salió (CA7);
  ningún rol gana permisos nuevos.
- Casos de abuso:
  - Como alguien con un recordatorio ajeno, uso su enlace para entrar o tocar la cita → solo lleva al
    inicio de sesión y no actúa sobre la cita (CA9).
  - Como alguien que lee un recordatorio ajeno, busco datos de salud → no están (CA10).
  - Como alguien que crea citas en masa, intento agotar el envío de correo de la clínica → tope
    diario de recordatorios (CA11).
  - Como alguien con acceso a los registros técnicos, busco correos o datos de citas → no aparecen
    (CA8, CA11 y requisitos no funcionales, P8).

## Auditoría
No aplica: enviar un recordatorio no es ninguno de los eventos de P7 (`docs/constitution.md`) ni de
`.ai/project.yaml → observability.audit.events`. Ninguna persona lee ni cambia datos sensibles: la
aplicación envía al paciente datos de su propia cita. El envío y sus fallos quedan en el registro
técnico, sin datos personales (CA8, CA11).

## Diseño
Quien lo recibe tiene que ver de un vistazo, en el móvil, qué día y a qué hora es su cita y dónde,
con un tono amable de recordatorio, como el recordatorio de WhatsApp y la tarjeta de cita que
describe `docs/design/nicho.md` («día y hora en grande»). Sistema:
[docs/design/system.md](../../design/system.md).

- Qué debe lograr: en cinco segundos, quien abre el correo sabe qué día y a qué hora es su cita, con
  qué dentista y dónde; la única acción es «Ver mis citas».
- Recursos de marca existentes: los de los correos de la spec 041 (cabecera, logo y pie) y el sistema
  de diseño.
- Qué debe diseñarse: el correo de recordatorio, legible a 320 y 390 px, con y sin servicio. El
  detalle de la cita del personal no cambia (CA7 usa la marca que ya existe).

## Criterios de diseño
Modo: leer · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Día y hora de la cita en la primera pantalla del correo | día y hora visibles sin desplazar a 320 y 390 px, con el tamaño mayor del correo | CA1 |
| K2 | Datos de la cita | nombre del dentista, dirección y teléfono de la clínica y «Ver mis citas» presentes; con servicio, también el servicio | CA1 |
| K3 | Nada fuera de CA1 | ni notas ni datos del expediente en el correo de ejemplo | CA10 |
| K4 | Enlaces del correo | un solo enlace de acción, «Ver mis citas», sin parámetros de acceso | CA9 |

Puertas: las de `design-spec/references/criterios.md` §2. Rúbrica: pesos por defecto.

## Requisitos no funcionales
- Entrega: en producción, el recordatorio de CA1 sale entre 24 h y 23 h 50 min antes de la cita y el
  de CA2 en menos de 10 minutos.
- Accesibilidad (P9, WCAG 2.1 AA): el correo cumple contraste AA, se lee a 320 px sin desplazamiento
  horizontal, el logo lleva texto alternativo y tiene una versión de solo texto con los mismos datos.
- Registros técnicos (P8): ningún log contiene el correo, el nombre del paciente, el dentista ni la
  fecha de la cita.
- Secretos (P5): las credenciales del servicio de correo siguen fuera del repositorio.
- Volumen: como mucho 250 recordatorios al día (CA11), dentro del límite de unos 500 correos diarios
  de la cuenta de envío (`docs/adr/0008-correo-a-usuarios-por-gmail.md`).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | brecha | ¿Por qué canal van los recordatorios? | Solo por correo; WhatsApp solo como botón «Escríbenos» en la página de inicio (spec 053) | usuario |
| 2026-11-20 | brecha | ¿Cuándo sale el recordatorio? | Uno solo, 24 h antes; con menos de 24 h de margen al agendar o reprogramar, en cuanto se pueda si la cita no ha empezado (CA1, CA2) | usuario |
| 2026-11-20 | brecha | ¿Qué puede hacer el paciente desde el correo? | Nada: es informativo, con un enlace a «Ver mis citas» que pide iniciar sesión (CA9) | usuario |
| 2026-11-20 | implícita | Hora con la que se calculan las 24 h y se escribe la fecha | La hora de la clínica | `docs/specs/050-hora-clinica/spec.md` |
| 2026-11-20 | implícita | Dirección y teléfono que lleva el correo | Los ficticios de la página de inicio; todos los datos del proyecto son ficticios | `docs/specs/038-pagina-inicio/spec.md` CA5 · `docs/constitution.md` Restricciones |
| 2026-11-20 | implícita | Servicio de envío y su límite diario | La cuenta de Gmail de envío (unos 500 al día) | `docs/adr/0008-correo-a-usuarios-por-gmail.md` |
| 2026-11-20 | implícita | Al reprogramar, la marca de «enviado» seguía valiendo para la fecha nueva | Se reinicia y sale un recordatorio nuevo (CA4) | roadmap 028/R11 |
| 2026-11-20 | supuesto | ¿Qué estados reciben recordatorio? | Solo agendada y confirmada (CA1, CA5) | /specify |
| 2026-11-20 | supuesto | ¿Cuentas desactivadas? | No reciben recordatorio (CA6), como no reciben el enlace de la spec 041 (CA11 de la 041) | /specify |
| 2026-11-20 | supuesto | Margen de entrega | Entre 24 h y 23 h 50 min antes (CA1); menos de 10 minutos en el caso de CA2 | /specify |
| 2026-11-20 | supuesto | Tope diario | 250 recordatorios al día; el exceso sale al día siguiente si la cita no ha empezado (CA11) | /specify |
| 2026-11-20 | supuesto | ¿Incluir el servicio aunque insinúe un tratamiento? | Sí, es el que eligió o le asignaron al paciente; nada del expediente ni notas (CA10) | /specify |
| 2026-11-20 | supuesto | Citas que ya están a menos de 24 h el día que se libera esta spec | Reciben su recordatorio según CA2 si no han empezado | /specify |

## Notas para /plan
- `citas.recordatorio_enviado` (tinyint) ya existe y se lee en `CitasController.php:38` y
  `resources/views/citas/show.blade.php:148`, pero nunca se escribe; quizá baste con él o haga falta
  guardar para qué fecha y hora se envió (CA3, CA4).
- Reprogramar: `app/Agenda/ReprogramacionCitas.php:119` (028/R11).
- Envío: Gmail por SMTP (`docs/adr/0008-correo-a-usuarios-por-gmail.md`), los correos de la 041 como
  referencia de plantilla y de versión de solo texto. Hora: `App\Agenda\RelojClinica` (spec 050).
- La revisión periódica: hoy hay un temporizador de systemd cada minuto (`dentissa-vigilar`, spec 032)
  y no hay colas (`docs/security.md`); valorar el planificador de Laravel o un temporizador nuevo.
- Spec 054 (alta de pacientes sin correo, en `feature/054-paciente-sin-correo`): habrá cuentas de
  paciente con `usuarios.email` nulo. El recordatorio debe saltárselas sin intentarlo ni contarlo
  como fallo (CA8), y sin dejar nada en el registro técnico (decisión del usuario, 2026-11-21).
- Interruptor de entorno como `RECUPERAR_CONTRASENA` de la 041, para liberar apagado.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-20 | `draft` → `approved` | aprobada por el usuario |
