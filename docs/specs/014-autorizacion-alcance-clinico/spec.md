---
id: 014
slug: autorizacion-alcance-clinico
status: approved
created: 2026-09-28
extends: [003, 006, 007]
---

# 014 · Autorización por alcance clínico

## Problema
Hoy cualquier dentista o asistente puede listar y abrir la ficha clínica de todos los pacientes de
la clínica, y cualquier dentista puede editarla. Además, un dentista puede leer y sobrescribir el
expediente clínico que firmó otro dentista, o crear expedientes a nombre de otro. Esto expone datos
de salud a personal que no atiende al paciente y permite alterar el registro clínico sin control,
en contra de la regla de negocio confirmada (cada profesional solo ve a sus pacientes y solo el
autor edita un expediente). Esta spec atiende RS1 por completo.

## Historias de usuario
- Como dentista, quiero ver solo a los pacientes que atiendo, para trabajar sin exponer datos de salud de pacientes ajenos.
- Como dentista, quiero consultar todos los expedientes de mis pacientes, aunque los haya firmado otro colega, para dar continuidad a la atención.
- Como dentista, quiero que nadie más pueda modificar los expedientes que firmé, para que el registro clínico sea fiable.
- Como asistente, quiero ver y dar de alta pacientes solo de los dentistas a los que apoyo.
- Como admin, quiero seguir viendo y gestionando a todos los pacientes y expedientes.

## Criterios de aceptación
- [ ] CA1 · Dado un dentista, cuando consulta el directorio de pacientes, entonces solo aparecen los pacientes de su alcance; si no tiene ninguno, ve la lista vacía.
- [ ] CA2 · Dado un asistente, cuando consulta el directorio de pacientes, entonces solo aparecen los pacientes del alcance de sus dentistas asignados; un admin sigue viendo a todos.
- [ ] CA3 (abuso) · Dado un dentista o asistente, cuando intenta abrir, editar o guardar la ficha de un paciente fuera de su alcance (p. ej. cambiando el identificador en la dirección), entonces se le deniega el acceso y la respuesta no revela ningún dato del paciente.
- [ ] CA4 · Dado un dentista, cuando abre un expediente de un paciente de su alcance, entonces lo ve aunque lo haya firmado otro dentista; un asistente lo ve en solo lectura.
- [ ] CA5 (abuso) · Dado un dentista o asistente, cuando intenta abrir un expediente de un paciente fuera de su alcance, entonces se le deniega el acceso sin revelar su contenido.
- [ ] CA6 (abuso) · Dado un dentista, cuando intenta modificar un expediente que firmó otro dentista, entonces se rechaza y el expediente no cambia; un admin sí puede modificarlo.
- [ ] CA7 (abuso) · Dado un dentista que registra un expediente, cuando indica a otro dentista como autor, una cita que no pertenece al paciente o un paciente fuera de su alcance, entonces se rechaza; si todo es válido, el expediente queda firmado por él.
- [ ] CA8 · Dado un asistente que da de alta un paciente, cuando guarda el alta, entonces debe asignarlo a uno de sus dentistas; si no elige uno o elige un dentista que no tiene asignado, el alta se rechaza sin crear nada.
- [ ] CA9 (abuso) · Dado un dentista o asistente que agenda una cita, cuando elige al paciente, entonces solo puede elegir pacientes de su alcance y solo ve la próxima visita sugerida de esos pacientes; si envía un paciente fuera de su alcance, la cita se rechaza.
- [ ] CA10 · Dado un paciente que cambia de dentista asignado, cuando el dentista anterior (sin citas con él) intenta abrir su ficha, entonces se le deniega; el nuevo dentista la ve de inmediato.

## Fuera de alcance
- Restringir el portal de pacientes a su rol (spec 015).
- Cierre de sesiones al desactivar cuentas o cambiar credenciales (spec 016).
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
| RS1.a — limitar directorio, ficha y edición de pacientes al alcance del rol | dentro | CA1, CA2, CA3, CA10 |
| RS1.b — expedientes: lectura por alcance, edición solo del autor o admin | dentro | CA4, CA5, CA6 |
| RS1.c — alta de expediente firmada por el propio dentista y coherente con la cita | dentro | CA7 |
| RS1.d — alta de citas limitada a pacientes del alcance | dentro | CA9 |
| RS1.e — verificación por rol, caso permitido y denegado | dentro | CA1–CA10 (ver Requisitos no funcionales) |

## Auditoría
Eventos que deben quedar registrados según `docs/observability.md`:
- [ ] CA11 · Dado cualquier acceso denegado de esta spec (CA3, CA5, CA6, CA7, CA9), cuando ocurre, entonces queda registrado quién lo intentó, qué acción, sobre qué paciente o expediente, con resultado "denegado" y la fecha.
- [ ] CA12 · Dada una lectura permitida de la ficha de un paciente o de un expediente, cuando ocurre, entonces queda registrado quién, qué recurso y la fecha.

## Requisitos no funcionales
- Cada criterio de acceso (CA1–CA10) se verifica para cada rol implicado, en el caso permitido y en el denegado (RS1.e, principio P3).
- Denegar el acceso no expone datos del recurso ni confirma si existe más allá de lo que ya ve el rol.

## Preguntas abiertas

## Supuestos
- Para el alcance cuenta cualquier cita del paciente con el dentista, pasada o futura, excepto las canceladas.
- El alcance se evalúa en cada acceso: una reasignación surte efecto de inmediato (CA10).
- Un paciente sin dentista asignado y sin citas solo lo ve el admin.
- El objetivo 2 se divide en las specs 014–019 y esta se crea primero (decisión del usuario, 2026-09-28).
- La implementación de esta spec se ordena después de la spec base de auditoría del objetivo 3, para cumplir CA11 y CA12 sin excepción al principio P7 (decisión del usuario, 2026-09-28).

## Notas para /plan
- Criterios de specs inferidas que esta spec reemplaza: 006 CA1, CA4, CA6, CA7 (directorio, edición y alcance); 007 CA3, CA4, CA6, CA7, CA8 (edición, lectura y alta de expedientes); 003 CA10 (pacientes en el alta de citas).
- Código afectado según la exploración: `PacientesController` (index/show/edit/update/store), `ExpedientesController` (show/update/store), `CitasController::create/store`. El usuario aceptó Policies de Laravel como mecanismo (principio P3).
- `asistente_dentista` y `pacientes.dentista_id` no existen en la base de pruebas cargada desde el volcado (riesgo de esquema no reproducible en `docs/deployment.md`): los tests de esta spec necesitan que la base de tests aplique las migraciones de julio o que antes se cumpla la parte 1 de la spec 013.
- Respuesta de denegación actual en el sistema: 403.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Creación | /specify, objetivo 2 del roadmap (RS1) |
| 2026-09-28 | Aprobada por el usuario | /specify |
