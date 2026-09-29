---
result: fail
round: 1
mode: full
constitution_version: 1.0.0
date: 2026-09-28
spec_sha: af94e7d31f14
plan_sha: bbb71893545c
tasks_sha: 1c65d817edb1
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **fail**: no hay CRÍTICOS, pero sí 6 ALTOS que el usuario decide corregir, porque
producirían código incorrecto o retrabajo en la 020 y en las specs que consumen su contrato (014,
021, 022). Se evaluaron los 8 principios de la constitución y no hay ninguna violación. Todos los
CA y TM tienen tareas. Conteo: CRÍTICA 0 · ALTA 6 · MEDIA 12 · BAJA 10.

Suposiciones del plan comprobadas contra el código y ciertas: `RoleMiddleware` hace `abort(403)` y
redirige sin sesión; `withExceptions`, `AppServiceProvider` y `routes/console.php` están vacíos; el
CI ya ejecuta `migrate` tras cargar el volcado; existe la sección "Análisis" del menú; no existe
`resources/views/errors/`; `docker-compose.yml` no tiene `scheduler`.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 10 / 10 (TM9 sin test del camino de fallo: A12) |
| Principios de la constitución evaluados | 8 / 8 |

Evaluación independiente de la constitución: P1 ✅ (tasks.md aún en `draft`) · P2 ✅ con reserva
(A05) · P3 ✅ (A28) · P4 ✅ con reserva (A11) · P5 ✅ · P6 ✅ (sin dependencias nuevas; RS16 en su
propia spec) · P7 ✅ con reserva (A01, A12) · P8 ✅ con reserva (A03, A04).

## Seguimiento de rondas anteriores
Primera ronda: no hay hallazgos anteriores.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A01 | Inconsistencia | ALTA | `spec.md` CA2 ("el almacén rechaza cualquier modificación o borrado directo") vs `plan.md` Riesgos y mitigaciones ("`TRUNCATE` o eliminar los triggers → limitación aceptada") | La spec promete un rechazo absoluto. El plan acepta `TRUNCATE`, `DROP TRIGGER` y `DROP TABLE` con las mismas credenciales que usa la app. La limitación figura en el ADR 0004, pero no en la spec, y ningún test la delimita. | Decidido: declarar la limitación. CA2 debe hablar del rechazo de `UPDATE` y `DELETE` de filas, y los Supuestos deben recoger la limitación aceptada. `InmutabilidadAuditoriaTest` debe probar exactamente lo que diga CA2. → `/specify --edit 020`, luego `/plan 020 --fix` |
| A02 | Ambigüedad | ALTA | `plan.md` Contratos (`denegacion(string $accion, …)`), Rutas ("403 (registrado como `acceso_denegado`)") y Observabilidad; plan 014 Observabilidad ("habilidad como acción") | No se define qué va en `accion` en una denegación (el literal `acceso_denegado` o la acción solicitada), ni de dónde saca el manejador central la acción y el recurso de un `abort(403)` genérico. La 014 y la 020 escribirían eventos con formas distintas, y el filtro de CA6 daría resultados incoherentes. | El contrato debe fijar la forma exacta del evento de denegación y el origen de cada campo en los 403 centrales. Test: `DenegacionesAuditadasTest` aserta los valores exactos de acción y recurso para `RoleMiddleware` y para el `abort(403)` de un controlador. → `/plan 020 --fix` |
| A03 | Constitución P8 / Seguridad | ALTA | `plan.md` Contratos ("true → lanza AuditoriaNoDisponible si no puede escribir"); `bootstrap/app.php` `withExceptions` (a), que solo define el render | Un INSERT fallido lanza una `QueryException` con los valores enlazados (IP, `actor_id` y, desde la 021, `identificador_intentado`). El plan no garantiza que esa excepción o su previa no acaben en el log, y el doble `AlmacenAuditoriaQueFalla` no ejercita una excepción de BD real. | Debe cumplirse que ningún log de una escritura de auditoría fallida, crítica o no, contenga SQL, bindings ni valores del evento. Test: el almacén falla con una excepción de BD que lleva bindings, y `CapturaLogs` comprueba que no aparecen la IP ni el identificador. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A04 | Inconsistencia | ALTA | `plan.md` tabla `auditoria` (`request_id` \| char(36) \| índice, NOT NULL) vs `routes/console.php` `auditoria:purgar` | La purga corre fuera de HTTP y el `Context` no tiene `request_id`: el evento `auditoria_purgada` (CA12, TM9) violaría el NOT NULL o llevaría un valor indefinido. Afecta también a los eventos de la 021 fuera de HTTP. | El contrato debe definir el `request_id` de los eventos sin petición HTTP. Test: `test_la_purga_queda_registrada` ejecuta el comando real y aserta un `request_id` válido. → `/plan 020 --fix` |
| A05 | Constitución P2 / Inconsistencia | ALTA | `tasks.md` T001 (sin `depende`) y T011 ("los tests de triggers pasan tras T001"); Constitution Check de tasks, P2 ("cada tarea de implementación depende de la de test que la verifica") | Los tests de los triggers (CA2, TM1, TM2) se escriben después de los triggers y nunca se ven en rojo. El Constitution Check de tasks es inexacto para T001, T020 y T023. | Los tests de triggers deben verse fallar por la razón esperada (UPDATE y DELETE aceptados) antes de la tarea que crea los triggers, y el Constitution Check debe reflejar el orden real. → `/tasks 020 --fix` |
| A06 | Duplicación y conflicto (014) | ALTA | plan 014 `AppServiceProvider` ("`Gate::after` que, para esas habilidades, llama a `Auditoria::denegacion`") y `expedientes/show.blade.php` (`@can('editar-expediente')`); plan 020 `Auditoria.php` ("`denegacion(...)` marca en el `Context` que la petición ya registró su denegación") | `Gate::after` se ejecuta en toda comprobación, también en `@can`. Un asistente que solo lee un expediente generaría `acceso_denegado` y activaría una marca que vale para toda la petición: un 403 real posterior no se registraría y se incumple CA4. | En la 020, la marca solo debe evitar el duplicado de la misma denegación que termina en 403. Test en `DenegacionesAuditadasTest`: una comprobación denegada que no lanza, seguida de un `abort(403)`, produce un evento para el 403. Después, `/plan 014 --fix`: solo se audita la denegación que rechaza la petición (test: un asistente que ve un expediente no genera `acceso_denegado`). → `/plan 020 --fix`, luego `/plan 014 --fix` |
| A07 | Inconsistencia (dependencias) | MEDIA | T003 ("implementa la interfaz … (se completa junto con T020)") vs T020 ("depende: T003") | Dependencia circular: el criterio de hecho de T003 requiere la interfaz de T020, y T020 depende de T003. | La interfaz va antes que el doble, y cada criterio de hecho debe poder verificarse al cerrar su tarea. → `/tasks 020 --fix` |
| A08 | Inconsistencia (dependencias) | MEDIA | T002 `[P]` (exige la tabla `auditoria` sin `depende: T001`); T012, T013 y T014 (necesitan la tabla); T021 (cita tests de T011 y T010 que requieren T028 y T022); T024 y T025 ("pasan al registrarlo en T026") | Hay dependencias sin declarar o imposibles: algunos tests fallarían por la razón equivocada y varios criterios de hecho solo se pueden verificar tras tareas posteriores. | Cada `depende` debe listar lo que su criterio necesita, ningún criterio de hecho debe citar una tarea posterior, y `[P]` solo debe ir en tareas sin dependencias pendientes. → `/tasks 020 --fix` |
| A09 | Duplicación y conflicto (014) | MEDIA | plan 014 (`denegado_404` o `denegado_403`); spec 014 CA11 ('resultado "denegado"'); plan 020 (`exito`, `denegado`, `denegado_404`, `error`) | Vocabulario de `resultado` distinto: `denegado_403` no existe en la 020. | Un único vocabulario en el contrato de la 020, y tests de ambas specs que asertan valores de ese conjunto. → `/plan 020 --fix`, luego `/plan 014 --fix` |
| A10 | Seguridad | MEDIA | `spec.md` Casos de abuso ("identificadores de petición manipulados para … confundir la trazabilidad → se descartan (CA9)") vs CA8 y TM3 (solo malformados) | Un UUID válido elegido por el atacante se acepta, así que el caso de abuso no queda cubierto y no tiene TM. | Decidido: aceptar el residuo, porque el `request_id` es solo de correlación y nunca de identidad. Hay que reescribir el caso de abuso y añadir un TM con su test: dos peticiones con el mismo id de cliente siguen siendo distinguibles en `auditoria`. → `/specify --edit 020`, `/plan 020 --fix` |
| A11 | Seguridad | MEDIA | `plan.md` filtros validados por "longitudes" (Constitution Check P4); D7 (la consulta guarda sus filtros en `detalle`); CA3 | El texto libre de un filtro puede ser un nombre y acabaría en `detalle`, contra CA3. En el 403 central, el recurso puede salir de la URL, que controla el usuario, y no tiene TM. | Decidido: filtro por actor solo por ID. A `detalle` y `recurso_id` solo llegan valores con formato de identificador. Test: un filtro o una ruta con texto libre o demasiado largo no se guarda tal cual. → `/plan 020 --fix` |
| A12 | Cobertura (TM9) | MEDIA | `plan.md` `routes/console.php` ("borra …, lo desactiva en `finally`, registra `auditoria_purgada`") | El evento se escribe después del borrado y en modo no crítico: si falla, se habrá purgado sin dejar rastro. No hay test de ese camino. | Una purga nunca debe borrar sin que su registro persista. Test con un almacén que falla: no se elimina nada. → `/plan 020 --fix` |
| A13 | Ambigüedad | MEDIA | `spec.md` CA11 ("la acción no se realiza"); `plan.md` Contratos (`critico`) | En las escrituras clínicas (022), "no se realiza" exige atomicidad entre el cambio y el evento, o bien registrar primero. El contrato no lo fija y el test de la 020 solo prueba que se lanza la excepción. | El contrato debe fijar la garantía para escrituras. Test: un evento crítico fallido no deja ningún cambio de datos. → `/plan 020 --fix` |
| A14 | Cobertura | MEDIA | `plan.md` `withExceptions` (b) (`AuthorizationException`); T013 (solo "área de otro rol" y "`abort(403)`") | No hay test de denegación por Gate o Policy en la 020. Laravel no reporta por defecto `HttpException` ni `AuthorizationException` y convierte la segunda antes del render, así que una implementación con `report` no registraría nada. Además, el caso "no duplicar" necesita un Gate que no existe hasta la 014. | T013 debe incluir un caso con un Gate de prueba y un escenario propio para "no duplicar". → `/plan 020 --fix`, `/tasks 020 --fix` |
| A15 | Cobertura de riesgos | MEDIA | `spec.md` Cobertura de riesgos (OB1.d "fuera \| spec 014 (CA12) y spec 022"); spec 014, Cobertura de riesgos (solo RS1.a–e); `observability.md` (OB1.d "pendiente") | El destino "014" no aparece en la tabla de cobertura de la 014, y el estado de OB1.d no lo refleja. | La 014 debe declarar que atiende OB1.d parcialmente (CA12), y `observability.md` debe mostrar el estado por corrección. → `/specify --edit 014` |
| A16 | Cobertura de riesgos | MEDIA | `deployment.md` Rollback paso 3 (`migrate:rollback --step=N`) y RD1.c; `plan.md` Rollout ("sin revertir la migración"); T091 | El rollback genérico borraría la tabla de auditoría, que es evidencia. La excepción del plan no llega a `deployment.md` ni a T091. | T091 debe documentar que esta migración queda excluida del rollback (se verifica en `/review`). → `/tasks 020 --fix` |
| A17 | Ambigüedad | MEDIA | T041; `plan.md` Estrategia de pruebas (RNF) | No se dice en qué base se insertan los 100.000 eventos, que en desarrollo no podrían borrarse (triggers), ni con qué método se mide. | Base desechable y método de medición definido. La nota de T041 registra ambos. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A18 | Ambigüedad | MEDIA | `plan.md` Contratos ("El actor, su rol, la IP y el `request_id` los toma del contexto"); `ContextoActor` (solo `actor_id`); TM10 | "Contexto" es ambiguo: puede ser el `Context` fijado antes del controlador o `auth` en el momento del registro. En el login (021) el actor cambia a mitad de la petición, y el rol no está en el `Context`. | El contrato debe fijar el origen del actor y del rol. Test: un evento registrado tras autenticarse en la misma petición lleva el actor esperado. → `/plan 020 --fix` |
| A19 | Ambigüedad | BAJA | T012 ("sin reflejarlo") vs CA9 ("nunca se refleja ni se registra tal cual") | El test no aserta la parte "no se registra". | El test comprueba que el valor malformado no aparece ni en la respuesta, ni en los logs capturados, ni en `auditoria`. → `/tasks 020 --fix` |
| A20 | Inconsistencia | BAJA | `plan.md` Contratos (`auditoria.fallo` "solo `accion` y `request_id`") vs Observabilidad ("`accion`, `critico`") | Los campos del log no coinciden entre secciones. | Una sola definición y un test que aserta las claves exactas. → `/plan 020 --fix` |
| A21 | Inconsistencia | BAJA | `plan.md` D1 ("ADR 0004 … (proposed)") vs `adr/0004` (`status: accepted`) | El estado citado está desactualizado. | Alinear con el ADR. → `/plan 020 --fix` |
| A22 | Inconsistencia | BAJA | `plan.md` Riesgos ("El plan de la 014 queda desalineado hasta aplicar `/plan 014 --fix` (D8)") vs plan 014 D3 | La 014 ya está alineada, así que el riesgo es obsoleto. | Actualizar el riesgo. → `/plan 020 --fix` |
| A23 | Inconsistencia (convención) | BAJA | `AGENTS.md` ("Tablas en plural snake_case", "Controladores `<Plural>Controller`") vs `auditoria` y `AuditoriaController` | Hay una excepción de nombres no declarada: D6 solo cubre el `id`. | Declarar la excepción o renombrar. → `/plan 020 --fix` |
| A24 | Cobertura (formato) | BAJA | T003 (`tests/Support/CapturaLogs.php`, ausente de Cambios por módulo); T041 y T042 sin `cubre`; T001–T003 sin `depende` | El formato de las tareas está incompleto. | Completar los campos y añadir el archivo al plan. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A25 | Ambigüedad | BAJA | `spec.md` Casos de abuso ("… se deniega y queda registrado (CA7, CA12)"); T014 | El registro del intento de un no admin corresponde a CA4, no a CA12, y `test_un_no_admin_no_consulta_el_registro` no aserta el evento. | Corregir la referencia y asertar el `acceso_denegado` en ese test. → `/specify --edit 020`, `/tasks 020 --fix` |
| A26 | Inconsistencia | BAJA | plan 014 (`Auditoria::denegacion(...)`, llamadas estáticas) vs plan 020 (métodos de instancia en un singleton, sin fachada) | No se define cómo obtienen el servicio las specs consumidoras. | El contrato debe decirlo. → `/plan 020 --fix` |
| A27 | Ambigüedad | BAJA | `spec.md` CA3 ("solo identificadores") vs `identificador_intentado varchar(255)` y `observability.md` ("email intentado") | El email intentado es un dato personal que ni CA3 ni los Supuestos exceptúan. | Declararlo en los Supuestos (su tratamiento lo detalla la 021 con su test). → `/specify --edit 020` |
| A28 | Constitución P3 | BAJA | `plan.md` Constitution Check P3 | No justifica la parte de alcance por recurso para `/admin/auditoria`. | Indicar que no aplica: el admin tiene alcance global. → `/plan 020 --fix` |

## Decisiones pendientes del usuario
Resueltas en esta ronda:
- **A01:** declarar la limitación en la spec. CA2 cubre el rechazo de `UPDATE` y `DELETE` de filas; los Supuestos recogen que `TRUNCATE` y el DDL con privilegios de BD son una limitación aceptada, coherente con el ADR 0004 (decisión del usuario, 2026-09-28).
- **A10:** aceptar como riesgo residual que un cliente reenvíe un `X-Request-Id` válido: es solo de correlación y nunca de identidad. Se reescribe el caso de abuso y se añade un TM con su test (decisión del usuario, 2026-09-28).
- **A11:** el filtro por actor es solo por ID (UUID), coherente con CA3 (decisión del usuario, 2026-09-28).
- **MEDIOS y BAJOS:** se corrigen en la misma ronda de `--fix`, no como notas de tarea (decisión del usuario, 2026-09-28).

## Aceptados
Ninguno: todos los hallazgos se corrigen antes de `/implement`.
