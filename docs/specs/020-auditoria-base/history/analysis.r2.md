---
result: fail
round: 2
mode: delta
constitution_version: 1.0.0
date: 2026-09-28
spec_sha: 783cfc1af1cf
plan_sha: bbb71893545c
tasks_sha: 1c65d817edb1
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **fail**. Desde la ronda 1 solo cambió la spec (`/specify --edit`); `plan.md` y
`tasks.md` son idénticos, así que los 6 ALTOS siguen abiertos. La edición resuelve A27 y la parte
de spec de A01, A10, A11 y A25, pero introduce 3 hallazgos nuevos en la propia spec (A29–A31).
No hay cambio estructural: ni módulo ni contrato nuevos. La decisión de A31 añadirá un criterio
(CA13), así que la próxima ronda será completa. Abiertos: CRÍTICA 0 · ALTA 6 · MEDIA 14 · BAJA 10.
Resueltos: 1.

Ronda anterior: [history/analysis.r1.md](../history/analysis.r1.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 12 (a la parte nueva de CA9, el identificador válido repetido, le faltan test y tarea: A10, A31) |
| Amenazas con control y test | 10 / 10 (TM9 sin test del camino de fallo: A12; falta la TM del identificador repetido: A10) |
| Principios de la constitución evaluados | 8 / 8 (sin cambios respecto a la ronda 1; ninguna violación nueva) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A01 | ALTA | abierto · resuelto con efecto nuevo en su parte de spec (→ A29) | La spec acota CA2 y declara la limitación en Supuestos. Pendiente en el plan: la fila de CA2 en Trazabilidad dice "nadie modifica ni borra eventos, salvo la purga" y ningún test delimita lo que CA2 cubre. |
| A02 | ALTA | abierto | Plan sin cambios: `denegacion(string $accion, …)`. |
| A03 | ALTA | abierto | Plan y tareas sin cambios. |
| A04 | ALTA | abierto | Plan sin cambios. |
| A05 | ALTA | abierto | T011 sigue diciendo "los tests de triggers pasan tras T001". |
| A06 | ALTA | abierto | Planes 020 y 014 sin cambios. |
| A07 | MEDIA | abierto | Tareas sin cambios. |
| A08 | MEDIA | abierto | T024 y T025 siguen diciendo "pasan al registrarlo en T026". |
| A09 | MEDIA | abierto | Plan sin cambios. |
| A10 | MEDIA | abierto · resuelto con efecto nuevo en su parte de spec (→ A30, A31) | CA9, los casos de abuso y los Supuestos están editados. Pendiente en el plan: no hay TM del identificador válido repetido; TM3, la trazabilidad de CA9 y T012 solo cubren el identificador malformado o largo. |
| A11 | MEDIA | abierto | Los Supuestos fijan el filtro por actor solo por identificador. Pendiente en el plan: el Constitution Check P4 sigue en "UUID, longitudes, fechas", D7 no restringe `detalle` ni `recurso_id`, y el recurso de un 403 tomado de la URL no tiene TM. |
| A12 | MEDIA | abierto | Plan sin cambios. |
| A13 | MEDIA | abierto | Plan sin cambios. |
| A14 | MEDIA | abierto | Plan y tareas sin cambios. |
| A15 | MEDIA | abierto | Spec 014 y `observability.md` (OB1.d "pendiente") sin cambios. |
| A16 | MEDIA | abierto | Tareas sin cambios. |
| A17 | MEDIA | abierto | Plan y tareas sin cambios. |
| A18 | MEDIA | abierto | El plan sigue diciendo "El actor, su rol, la IP y el `request_id` los toma del contexto". |
| A19 | BAJA | abierto | T012 sigue diciendo "sin reflejarlo". |
| A20 | BAJA | abierto | Plan sin cambios. |
| A21 | BAJA | abierto | Plan sin cambios. |
| A22 | BAJA | abierto | Plan sin cambios. |
| A23 | BAJA | abierto | Plan sin cambios. |
| A24 | BAJA | abierto | Plan y tareas sin cambios. |
| A25 | BAJA | abierto | El caso de abuso cita "(CA7, CA4)". Pendiente en las tareas: T014 no aserta `acceso_denegado` en `test_un_no_admin_no_consulta_el_registro`. |
| A26 | BAJA | abierto | Plan sin cambios. |
| A27 | BAJA | resuelto | CA3 exceptúa la dirección de origen y el identificador intentado; los Supuestos los declaran datos personales y remiten su tratamiento a la 021. |
| A28 | BAJA | abierto | Plan sin cambios. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A29 | Inconsistencia | MEDIA | `spec.md` CA2 ("el borrado de cualquier evento hecho fuera de la aplicación"; "Vaciar el almacén entero … queda fuera"); Seguridad y privacidad, "Quién puede hacer qué" ("Nadie lo modifica ni lo borra") | La edición de A01 dejó CA2 más estrecho que lo decidido, que el ADR 0004 y que TM1: no queda claro si cubre los cambios hechos con la conexión de la propia app. Además, la exclusión puede leerse como si incluyera un borrado masivo de filas. "Quién puede hacer qué" no remite a la limitación. | Decidido: CA2 cubre la modificación y el borrado de cualquier evento, incluidos los hechos con la conexión de la app y el borrado masivo de filas, salvo la purga. Solo queda fuera vaciar el almacén o desactivar su protección con privilegios totales. "Quién puede hacer qué" debe remitir a la limitación. Test (`InmutabilidadAuditoriaTest`): con la conexión de la app se rechazan la modificación de un evento, su borrado y un borrado sin condición, y ningún evento cambia. → `/specify --edit 020`, luego `/plan 020 --fix` |
| A30 | Ambigüedad | MEDIA | `spec.md` CA9 ("los eventos de ambas peticiones siguen siendo distinguibles entre sí") y el caso de abuso del identificador repetido | "Distinguibles" no se puede verificar: o se cumple siempre, o promete atribuir cada evento a su petición, que es justo lo que se acepta como riesgo residual. | Decidido: cada evento conserva su identidad, actor, fecha y dirección de origen, y un identificador repetido nunca hace que un evento reciba el actor ni el acceso de otra petición. Test: dos peticiones de actores distintos con el mismo identificador válido dan dos eventos separados, cada uno con su actor, y una petición sin sesión que reutiliza el identificador de un admin no obtiene ni su actor ni su acceso. → `/specify --edit 020`, luego `/plan 020 --fix` |
| A31 | Cobertura | BAJA | `spec.md` CA9 y los dos casos de abuso que lo citan; `plan.md` Trazabilidad de CA9; T012; tabla CA → tareas de `tasks.md` | CA9 junta dos comportamientos opuestos (descartar el malformado y aceptar el válido repetido), así que aparece cubierto aunque su parte nueva no tiene test. | Decidido: separar el identificador válido repetido en un criterio propio, CA13 (abuso), con su test en `IdentificadorPeticionTest` (el de A30). → `/specify --edit 020`, luego `/plan 020 --fix` y `/tasks 020 --fix` |

Observaciones menores para el mismo `/specify --edit`, sin ID: el Historial usa "(decisiones del
usuario, 2026-09-28)" en plural (la convención es `(decisión del usuario, AAAA-MM-DD)`), y "Datos
sensibles involucrados" no menciona el identificador intentado.

## Decisiones pendientes del usuario
Resueltas en esta ronda:
- **A29:** CA2 cubre la modificación y el borrado de cualquier evento, incluidos los hechos con la conexión de la app y el borrado masivo de filas, salvo la purga. Solo queda fuera vaciar el almacén o desactivar su protección con privilegios totales (decisión del usuario, 2026-09-28).
- **A30:** "distinguibles" significa que cada evento conserva su identidad, actor, fecha y dirección de origen, y que un identificador repetido nunca transfiere el actor ni el acceso de otra petición (decisión del usuario, 2026-09-28).
- **A31:** el identificador válido repetido va en un criterio propio, CA13 (abuso); la próxima ronda de `/analyze` será completa (decisión del usuario, 2026-09-28).

## Aceptados
Ninguno: todos los hallazgos se corrigen antes de `/implement`.
