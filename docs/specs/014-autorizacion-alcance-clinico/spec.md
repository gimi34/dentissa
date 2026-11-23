---
id: 014
slug: autorizacion-alcance-clinico
status: approved
created: 2026-09-28
extends: [003, 006, 007, 054]
---

# 014 · Autorización por alcance clínico

## Problema
Hoy cualquier dentista o asistente puede listar y abrir la ficha clínica de todos los pacientes de
la clínica, y cualquier dentista puede editarla. Además, un dentista puede leer y sobrescribir el
expediente clínico que firmó otro dentista, o crear expedientes a nombre de otro. Esto expone datos
de salud a personal que no atiende al paciente y permite alterar el registro clínico sin control,
en contra de la regla de negocio confirmada (cada profesional solo ve a sus pacientes y solo el
autor edita un expediente). Esta spec atiende RS1 por completo y parcialmente la brecha de auditoría de
lecturas de datos clínicos (OB1; ver «Cobertura de riesgos»).

## Historias de usuario
- Como dentista, quiero ver solo a los pacientes que atiendo, para trabajar sin exponer datos de salud de pacientes ajenos.
- Como dentista, quiero consultar todos los expedientes de mis pacientes, aunque los haya firmado otro colega, para dar continuidad a la atención.
- Como dentista, quiero que nadie más pueda modificar los expedientes que firmé, para que el registro clínico sea fiable.
- Como asistente, quiero ver y dar de alta pacientes solo de los dentistas a los que apoyo.
- Como admin, quiero seguir viendo y gestionando a todos los pacientes y expedientes.

## Criterios de aceptación
- [x] CA1 · Dado un dentista, cuando consulta el directorio de pacientes, entonces solo aparecen los pacientes de su alcance; si no tiene ninguno, ve la lista vacía.
- [x] CA2 · Dado un asistente, cuando consulta el directorio de pacientes, entonces solo aparecen los pacientes del alcance de sus dentistas asignados; un admin sigue viendo a todos.
- [x] CA3 (abuso) · Dado un dentista o asistente, cuando intenta abrir, editar o guardar la ficha de un paciente fuera de su alcance (p. ej. cambiando el identificador en la dirección), entonces se le deniega el acceso y la respuesta no revela ningún dato del paciente.
- [x] CA4 · Dado un dentista, cuando abre un expediente de un paciente de su alcance, entonces lo ve aunque lo haya firmado otro dentista; un asistente lo ve en solo lectura.
- [x] CA5 (abuso) · Dado un dentista o asistente, cuando intenta abrir un expediente de un paciente fuera de su alcance, entonces se le deniega el acceso sin revelar su contenido.
- [x] CA6 (abuso) · Dado un dentista, cuando intenta modificar un expediente que firmó otro dentista, entonces se rechaza y el expediente no cambia; un admin sí puede modificarlo.
- [x] CA7 (abuso) · Dado un dentista que registra un expediente, cuando indica a otro dentista como autor, una cita que no pertenece al paciente o un paciente fuera de su alcance, entonces se rechaza; si todo es válido, el expediente queda firmado por él.
- [x] CA8 · Dado un asistente que da de alta un paciente, cuando guarda el alta, entonces debe asignarlo a uno de sus dentistas; si no elige uno o elige un dentista que no tiene asignado, el alta se rechaza sin crear nada.
- [x] CA9 (abuso) · Dado un dentista o asistente que agenda una cita, cuando elige al paciente, entonces solo puede elegir pacientes de su alcance y solo ve la próxima visita sugerida de esos pacientes; si envía un paciente fuera de su alcance, la cita se rechaza. Un paciente sin dentista asignado y sin citas, o de otro dentista, solo lo agenda el admin (o el propio paciente desde su cuenta).
- [x] CA10 · Dado un paciente que cambia de dentista asignado, cuando el dentista anterior (sin citas con él) intenta abrir su ficha, entonces se le deniega; el nuevo dentista la ve de inmediato.
- [x] CA13 (abuso) · Dado un dentista o asistente, cuando abre el detalle de una cita de un dentista que no es el suyo ni uno de los que apoya, o intenta agendar una cita para uno de ellos, entonces se le deniega (como hoy en las citas, sin revelar el contenido de la cita); dentro de su alcance la ve y la agenda.
- [x] CA14 · Dado un aviso de posible paciente duplicado en el alta (spec 054), cuando el paciente coincidente está fuera del alcance de quien registra, entonces el aviso muestra su nombre pero no el enlace a su ficha; si está en su alcance (o es el admin), el aviso enlaza la ficha.
- [x] CA15 (abuso) · Dado un dentista que tiene a un paciente en su alcance solo por una cita (no es su dentista asignado), cuando intenta cambiarle el dentista asignado, entonces se le deniega y se registra; puede seguir editando el resto de la ficha. El admin y el dentista asignado sí pueden cambiarlo.

## Fuera de alcance
- Restringir el portal de pacientes a su rol (spec 015).
- Cierre de sesiones al desactivar cuentas o cambiar credenciales (spec 016; el cambio de contraseña desde el perfil ya cierra las otras sesiones desde la spec 011, v0.24.0).
- Notas internas y cancelación de citas por el admin (el admin conserva acceso total).
- Construir el registro de auditoría (objetivo 3 del roadmap): esta spec solo declara qué debe registrarse.
- Cifrado de datos clínicos en reposo (spec 013).

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales y de salud de pacientes (ficha clínica y expedientes), según `docs/security.md`.
- Quién puede hacer qué:
  - Admin: ver, crear y editar cualquier paciente y expediente.
  - Dentista: ver y editar pacientes de su alcance; leer todos sus expedientes; crear expedientes firmados por él; editar solo los que firmó.
  - Asistente: ver pacientes del alcance de sus dentistas; darlos de alta asignados a uno de ellos; leer sus expedientes; no edita pacientes ni expedientes.
- Alcance de un dentista: pacientes asignados a él o con al menos una cita con él (decisión del usuario, 2026-09-28).
- Casos de abuso:
  - Como dentista curioso, cambio el identificador en la dirección para ver a un paciente ajeno → se deniega y se registra (CA3, CA11).
  - Como dentista, intento corregir el diagnóstico de un colega → se rechaza y se registra (CA6, CA11).
  - Como dentista, firmo un expediente a nombre de otro o sobre una cita ajena → se rechaza (CA7).
  - Como asistente, agendo o consulto a un paciente de un dentista que no apoyo → se deniega (CA5, CA9).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS1.a — limitar directorio, ficha y edición de pacientes al alcance del rol | dentro | CA1, CA2, CA3, CA10, CA15 |
| RS1.b — expedientes: lectura por alcance, edición solo del autor o admin | dentro | CA4, CA5, CA6 |
| RS1.c — alta de expediente firmada por el propio dentista y coherente con la cita | dentro | CA7 |
| RS1.d — alta de citas limitada a pacientes del alcance | dentro | CA9 |
| RS1.e — verificación por rol, caso permitido y denegado | dentro | CA1–CA10, CA13 (ver Requisitos no funcionales) |
| OB1.a — tabla de auditoría | fuera | ya mitigada por la 020 (v0.12.0) |
| OB1.b — eventos de autenticación | fuera | → spec 021 |
| OB1.c — accesos denegados | fuera | ya mitigada por la 020 (v0.12.0); esta spec la usa para CA11 |
| OB1.d — lecturas de ficha y expedientes | dentro | CA12: abrir la ficha, su edición y un expediente. Los listados (directorio con alergias y tipo de sangre; índice de expedientes con el diagnóstico) → spec 022 (decisión del usuario, 2026-11-22) |
| OB1.e — cambios de pacientes, expedientes, notas y estado de citas | fuera | → spec 022 |
| OB1.f — altas, cambios de rol, activación y contraseñas | fuera | → spec 021 |
| OB1.g — retención y purga | fuera | ya mitigada por la 020 (v0.12.0) |
| OB1.h — consulta del registro para el admin | fuera | ya mitigada por la 020 (v0.12.0) |

## Auditoría
Eventos que deben quedar registrados según `docs/observability.md`:
- [x] CA11 · Dado cualquier acceso denegado de esta spec (CA3, CA5, CA6, CA7, CA9, CA13, CA15), cuando ocurre, entonces queda registrado quién lo intentó, qué acción, sobre qué paciente o expediente, con resultado "denegado" y la fecha.
- [x] CA12 · Dada una lectura permitida de la ficha de un paciente (también al abrir su edición) o de un expediente, cuando ocurre, entonces queda registrado quién, qué recurso y la fecha; si no se puede registrar, la ficha o el expediente no se muestran.

## Requisitos no funcionales
- Cada criterio de acceso (CA1–CA10) se verifica para cada rol implicado, en el caso permitido y en el denegado (RS1.e, principio P3).
- Denegar el acceso no expone datos del recurso ni confirma si existe más allá de lo que ya ve el rol.

## Preguntas abiertas

## Supuestos
- Para el alcance cuenta cualquier cita del paciente con el dentista, pasada o futura, excepto las canceladas.
- El alcance se evalúa en cada acceso: una reasignación surte efecto de inmediato (CA10).
- El objetivo 2 se divide en las specs 014–019 y esta se crea primero (decisión del usuario, 2026-09-28).
- La implementación de esta spec se ordena después de la spec base de auditoría del objetivo 3, para cumplir CA11 y CA12 sin excepción al principio P7 (decisión del usuario, 2026-09-28).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-09-28 | brecha | ¿Qué es el alcance de un dentista? | Pacientes asignados a él o con al menos una cita con él | usuario |
| 2026-11-21 | brecha | ¿Partir la spec (más de 10 criterios)? | No: una sola entrega | usuario (`/plan --redo`) |
| 2026-11-22 | contradicción | La 006 CA6, la constitución P3 y RS1.e dicen «403»; esta spec pide no confirmar que el paciente existe | Fuera del alcance, 404 (como si no existiera) en ficha, edición y expediente; dentro del alcance sin permiso, 403. Reemplaza el 403 de 006 CA6 y 007 CA7 para estas rutas, y para ellas precisa el «denegado (403)» de la constitución P3 y el «permitido y 403» de RS1.e (el caso denegado fuera del alcance es un 404). Las citas conservan su 403 (spec 026) | usuario (`/plan`) |
| 2026-11-22 | implícita | ¿Policies o Gates? | Gates sobre clases de `app/Autorizacion/`, la convención del proyecto desde ADR 0003 (no hay modelos de paciente ni de expediente) | `docs/adr/0003*`, `AGENTS.md` |
| 2026-11-22 | brecha | ¿Qué lecturas audita esta spec? | Abrir la ficha, su edición y un expediente, en modo crítico; los listados → spec 022 | usuario (`/plan`, `/analyze` A1, A5) |
| 2026-11-22 | brecha | El Gate del detalle de cita del personal, que la 026, la 028 y la 050 dejaron a la 014 | Dentro de esta spec (CA13) | usuario (`/analyze` A2) |
| 2026-11-22 | brecha | ¿Quién agenda la primera cita de un paciente sin dentista o de otro dentista? | Solo el admin (o el propio paciente); antes era un supuesto de visibilidad | usuario (`/analyze` A15) |
| 2026-11-22 | brecha | ¿Quién cambia el dentista asignado de un paciente? Quien lo tiene en su alcance solo por una cita podía reasignárselo y dejar sin acceso al asignado | Solo el admin o el dentista asignado; el resto de la ficha la sigue editando quien la ve (CA15) | usuario (`/review` R5) |
| 2026-11-21 | brecha | El aviso de duplicado de la 054 con un paciente fuera del alcance | El nombre se muestra siempre (excepción aceptada); el enlace a la ficha solo si se puede ver (CA14) | usuario (054 `/analyze` A4) |

## Notas para /plan
- Criterios de specs inferidas que esta spec reemplaza: 006 CA1, CA4, CA6, CA7 (directorio, edición y alcance); 007 CA3, CA4, CA6, CA7, CA8 (edición, lectura y alta de expedientes); 003 CA10 (pacientes en el alta de citas).
- Código afectado según la exploración: `PacientesController` (index/show/edit/update/store), `ExpedientesController` (show/update/store), `CitasController::create/store`. Mecanismo: Gates sobre clases de `app/Autorizacion/` (ADR 0003), que sustituyen a las Policies que se aceptaron en 2026-09 (ver Decisiones).
- Respuesta de denegación actual en el sistema: 403 (fuera del alcance pasa a 404, ver Decisiones).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Creación | /specify, objetivo 2 del roadmap (RS1) |
| 2026-09-28 | Aprobada por el usuario | /specify |
| 2026-11-21 | «Fuera de alcance»: el cierre de sesiones al cambiar la contraseña desde el perfil lo hace ya la 011 (v0.24.0); sin cambios de criterios | /release 011 |
| 2026-11-22 | `/analyze` r1: CA9 (quién agenda), CA12 (también la edición, modo crítico), CA13 (detalle de cita) y CA14 (aviso de duplicado) nuevos o precisados; «Decisiones»; OB1 en «Cobertura de riesgos»; `extends` 054; nota caducada de la base de tests quitada | `/analyze` A1–A5, A15, A20 |
| 2026-11-22 | CA15 nuevo (quién cambia el dentista asignado) y CA11 lo incluye; aprobado por el usuario el 2026-11-22 | `/review` r1 R5 (decisión del usuario) |
