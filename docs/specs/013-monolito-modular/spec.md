---
id: 013
slug: monolito-modular
status: draft
created: 2026-09-28
extends: [003, 004, 005]
---

# 013 · Monolito modular con esquema reproducible

## Problema
El sistema es un monolito sin estructura interna: 13 controladores concentran toda la lógica,
solo hay 2 modelos para 9 tablas de dominio y la regla más crítica, la que impide la doble
reserva de un dentista, está copiada en dos controladores sin pruebas ni transacción. Cambiarla
exige acordarse de tocar dos sitios y nada avisa si no se hace. Además, el esquema del dominio no
se puede recrear: vive en un volcado SQL desfasado respecto a las migraciones, así que ni un clon
limpio arranca con `composer setup` ni la base de pruebas tiene todas las tablas.

El equipo necesita (1) poder recrear la base desde cero con datos sintéticos, (2) tener cada regla
de negocio en un solo sitio con pruebas propias y (3) fronteras de módulo (Agenda, Pacientes,
Clínica, Identidad) que se puedan vigilar automáticamente, sin pasar a microservicios.

Atiende RD2 por completo, RS12 por completo y RS15 **parcialmente** (el cifrado de disco y
backups queda en el objetivo 5 del roadmap). Viene de la intención AI-DLC `260920-monolito-modular`,
en pausa (ver `referencias/`).

## Historias de usuario
- Como integrante del equipo, quiero levantar la base completa con un solo comando sobre un clon limpio, para no depender de un volcado.
- Como integrante del equipo, quiero que la regla de agenda viva en un solo sitio con pruebas, para cambiarla sin romper otro flujo.
- Como paciente o personal, quiero que dos personas no puedan reservar el mismo hueco de un dentista a la vez.
- Como integrante del equipo, quiero que una prueba falle si un módulo accede a las tablas de otro, para que la estructura no se degrade.

## Criterios de aceptación
- [ ] CA1 · Dado un clon limpio y una base vacía, cuando se ejecutan las migraciones y el seeder, entonces existen todas las tablas de dominio, las vistas que se conserven y las columnas de las 3 migraciones de julio, sin cargar `database/dentissa.sql`.
- [ ] CA2 · Dada la base recién sembrada, cuando se inspeccionan los datos, entonces todos son sintéticos e incluyen al menos 2 dentistas con horario, 1 asistente asignado a un dentista, pacientes, servicios y citas.
- [ ] CA3 · Dada la suite de pruebas, cuando se prepara su base, entonces se crea con migraciones y seeder, no con el volcado, y las pruebas existentes siguen pasando.
- [ ] CA4 · Dado el agendado por el personal o por el paciente, cuando se valida horario, encaje y solape, entonces ambos flujos usan la misma regla, que tiene pruebas propias de cada caso.
- [ ] CA5 (abuso) · Dadas dos peticiones simultáneas para el mismo hueco del mismo dentista, cuando se procesan, entonces solo una cita queda guardada.
- [ ] CA6 · Dada una cita que empieza exactamente cuando termina otra, cuando se agenda, entonces el resultado (conflicto o no) es el que se decida en la primera pregunta abierta y está cubierto por una prueba.
- [ ] CA7 (abuso) · Dada una hora mal formada, un día de horario inválido o un id de dentista que no es dentista, cuando se envía, entonces se rechaza con error de validación y nunca con error 500.
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
  - Como dos usuarios concurrentes, intento reservar el mismo hueco → solo uno lo consigue (CA5).
  - Como usuario, envío entradas mal formadas → se rechazan sin error 500 (CA7).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RD2.a — migraciones de todas las tablas de dominio, vistas y trigger | dentro | CA1 |
| RD2.b — seeder sintético | dentro | CA2 |
| RD2.c — base de tests con migrate + seeder | dentro | CA3 |
| RD2.d — retirar el volcado de initdb | dentro | CA1 |
| RS12.a — validar `hora` con formato | dentro | CA7 |
| RS12.b — validar `dias[*]` en horarios | dentro | CA7 |
| RS12.c — `exists` con `tipo=dentista` | dentro | CA7 |
| RS15.a — cast `encrypted` en campos clínicos | dentro | CA9 |
| RS15.b — cifrado de disco y backups | fuera | depende de la plataforma de staging/prod → roadmap objetivo 5 |

## Auditoría
Esta spec no añade eventos de auditoría (roadmap objetivo 3). Si el plan retira el trigger
`trg_historial_cita`, debe conservar al menos el registro actual de cambios de estado.

## Requisitos no funcionales
- Las pruebas de la regla de agenda corren en menos de 10 s en local.
- El diff se divide en specs o entregas pequeñas (ver Notas para /plan).

## Preguntas abiertas
- [NECESITA ACLARACIÓN] ¿Una cita que empieza justo cuando termina otra es conflicto? Hoy el código lo trata como conflicto por un `whereBetween` inclusivo que parece accidental.
- [NECESITA ACLARACIÓN] ¿El trigger `trg_historial_cita` se conserva en las migraciones o la escritura del historial pasa a la aplicación?
- [NECESITA ACLARACIÓN] ¿Las vistas SQL `v_citas_hoy` y `v_historial_paciente` se conservan (el código no las usa y exponen datos clínicos con `SQL SECURITY DEFINER`) o se eliminan?

## Supuestos
- Las entradas divergentes de las dos copias de la regla (duración del formulario 10–300 frente a derivada del servicio; fecha pasada permitida para el personal; servicio opcional frente a obligatorio) se mantienen como diferencias de los flujos, no de la regla.

## Notas para /plan
- La spec supera el tamaño recomendado: propón dividirla en al menos tres specs (esquema + seeder; regla de agenda + concurrencia; capa de dominio + módulos + cifrado).
- La migración de `asistente_dentista` debe reordenarse detrás de la de `personal`.
- La doble reserva no se cierra solo con unificar la regla: hace falta transacción con bloqueo o restricción en el almacén.
- Hallazgos detallados, cifras y rutas: `referencias/developer-scan.md` (sección "Handoff Summary").
- Originales sin modificar en `referencias/`: `project-description.json`, `developer-scan.md`, `memory.md`, `aidlc-state.md`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Migrada desde la intención AI-DLC `260920-monolito-modular` (en pausa) | /init, decisión del usuario |
