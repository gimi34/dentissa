---
id: 013
slug: monolito-modular
status: draft
created: 2026-09-28
extends: [006, 007, 008]
---

# 013 · Monolito modular con esquema reproducible

## Problema
El sistema es un monolito sin estructura interna: 13 controladores concentran toda la lógica y
solo hay 2 modelos para 9 tablas de dominio. La regla de agenda, que impide la doble reserva de
un dentista, se trata aparte en la spec 025. Además, el esquema del dominio no
se puede recrear: vive en un volcado SQL desfasado respecto a las migraciones, así que ni un clon
limpio arranca con `composer setup` ni la base de pruebas tiene todas las tablas.

El equipo necesita (1) poder recrear la base desde cero con datos sintéticos, (2) tener la
validación de entrada y el acceso a datos fuera de los controladores y (3) fronteras de módulo
(Agenda, Pacientes, Clínica, Identidad) que se puedan vigilar automáticamente, sin pasar a
microservicios.

Atiende RD2 por completo, RS12 **parcialmente** (RS12.a y la parte de RS12.c del agendado van a
la spec 025, y RS12.b a la 027) y RS15 **parcialmente** (el cifrado de disco y backups queda en
el objetivo 5 del roadmap). Viene de la intención AI-DLC `260920-monolito-modular`,
en pausa (ver `referencias/`).

## Historias de usuario
- Como integrante del equipo, quiero levantar la base completa con un solo comando sobre un clon limpio, para no depender de un volcado.
- Como integrante del equipo, quiero que una prueba falle si un módulo accede a las tablas de otro, para que la estructura no se degrade.

## Criterios de aceptación
- [ ] CA1 · Dado un clon limpio y una base vacía, cuando se ejecutan las migraciones y el seeder, entonces existen todas las tablas de dominio, las vistas que se conserven y las columnas de las 3 migraciones de julio, sin cargar `database/dentissa.sql`.
- [ ] CA2 · Dada la base recién sembrada, cuando se inspeccionan los datos, entonces todos son sintéticos e incluyen al menos 2 dentistas con horario, 1 asistente asignado a un dentista, pacientes, servicios y citas.
- [ ] CA3 · Dada la suite de pruebas, cuando se prepara su base, entonces se crea con migraciones y seeder, no con el volcado, y las pruebas existentes siguen pasando.
<!-- CA4, CA5 y CA6 pasaron a la spec 025 (CA1, CA8 y CA5); no se renumera para conservar las referencias. -->
- [ ] CA7 (abuso) · Dado un identificador de dentista que no es dentista al asignar el dentista de un paciente, al crear un expediente o al asignar dentistas a un asistente, cuando se envía, entonces se rechaza con error de validación y nunca con error 500.
- [ ] CA8 · Dadas las tablas de dominio, cuando se accede a ellas desde la aplicación, entonces cada una tiene su modelo con relaciones y la validación de entrada vive fuera de los controladores.
- [ ] CA9 · Dados los campos clínicos de pacientes, expedientes y notas de citas, cuando se guardan, entonces quedan cifrados en reposo y se leen en claro solo a través de la aplicación.
- [ ] CA10 · Dados los módulos Agenda, Pacientes, Clínica e Identidad, cuando un módulo consulta tablas de otro sin pasar por su interfaz pública, entonces una prueba de arquitectura falla.

## Fuera de alcance
- Microservicios o bases separadas por módulo.
- Autorización por alcance con Policies (roadmap objetivo 2), salvo que el plan decida integrarla.
- Auditoría y correlación (roadmap objetivo 3).
- Purga del historial de git del volcado.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales y de salud (el seeder los sustituye por sintéticos; CA9 los cifra).
- Quién puede hacer qué: sin cambios de permisos respecto a las specs 003–008.
- Casos de abuso:
  - Como usuario, envío como dentista a alguien que no lo es → se rechaza sin error 500 (CA7).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RD2.a — migraciones de todas las tablas de dominio, vistas y trigger | dentro | CA1 |
| RD2.b — seeder sintético | dentro | CA2 |
| RD2.c — base de tests con migrate + seeder | dentro | CA3 |
| RD2.d — retirar el volcado de initdb | dentro | CA1 |
| RS12.a — validar `hora` con formato | fuera | es entrada de la regla de agenda → spec 025 (CA9) |
| RS12.b — validar `dias[*]` en horarios | fuera | se edita con el horario del dentista → spec 027 horarios-reprogramar |
| RS12.c — `exists` con `tipo=dentista`, en el agendado de citas | fuera | es entrada de la regla de agenda → spec 025 (CA10) |
| RS12.c — `exists` con `tipo=dentista`, en pacientes, expedientes y asignación de asistentes | dentro | CA7 |
| RS15.a — cast `encrypted` en campos clínicos | dentro | CA9 |
| RS15.b — cifrado de disco y backups | fuera | depende de la plataforma de staging/prod → roadmap objetivo 5 |

## Auditoría
Esta spec no añade eventos de auditoría (roadmap objetivo 3). Si el plan retira el trigger
`trg_historial_cita`, debe conservar al menos el registro actual de cambios de estado.

## Requisitos no funcionales
- El diff se divide en specs o entregas pequeñas (ver Notas para /plan).

## Preguntas abiertas
- [NECESITA ACLARACIÓN] ¿El trigger `trg_historial_cita` se conserva en las migraciones o la escritura del historial pasa a la aplicación?
- [NECESITA ACLARACIÓN] ¿Las vistas SQL `v_citas_hoy` y `v_historial_paciente` se conservan (el código no las usa y exponen datos clínicos con `SQL SECURITY DEFINER`) o se eliminan?

## Supuestos
- La regla de agenda (horario, encaje, solape, citas seguidas y doble reserva) y sus diferencias por flujo las define la spec 025 (decisión del usuario, 2026-09-29).

## Notas para /plan
- La spec supera el tamaño recomendado: propón dividirla en al menos dos specs (esquema + seeder; capa de dominio + módulos + cifrado). La regla de agenda y la concurrencia ya son la spec 025.
- Cuando se extraiga la capa de dominio, la regla de agenda de la 025 pasa al módulo Agenda sin cambiar su comportamiento ni sus pruebas.
- La migración de `asistente_dentista` debe reordenarse detrás de la de `personal`.
- Hallazgos detallados, cifras y rutas: `referencias/developer-scan.md` (sección "Handoff Summary").
- Originales sin modificar en `referencias/`: `project-description.json`, `developer-scan.md`, `memory.md`, `aidlc-state.md`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Migrada desde la intención AI-DLC `260920-monolito-modular` (en pausa) | /init, decisión del usuario |
| 2026-09-29 | Retirados CA4, CA5 y CA6, las historias de agenda y la pregunta de citas seguidas (pasan a la spec 025); CA7 queda solo para pacientes, expedientes y asignación de asistentes (RS12.a y RS12.c del agendado → 025, RS12.b → 027); `extends` pasa a [006, 007, 008] | Corte de la lógica de citas en 025–027 (decisión del usuario, 2026-09-29) |
