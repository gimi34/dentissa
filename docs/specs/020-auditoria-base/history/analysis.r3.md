---
result: fail
round: 3
mode: full
constitution_version: 1.0.0
date: 2026-09-28
spec_sha: 00ed2dc6f63e
plan_sha: 9087ac427a15
tasks_sha: 0cf845f8059b
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **fail** por un ALTO nuevo, A32: el test del camino de fallo de la purga (TM9) no prueba
lo que dice. Es una ronda completa porque la spec ganó CA13 y `tasks.md` se regeneró. Las
correcciones de la ronda anterior cierran todos los hallazgos de la 020 salvo tres, cuyo efecto
nuevo recoge esta ronda (A11 → A35, A12 → A32 y A33, A18 → A36). Sigue pendiente la parte de la
014 de A06, A09, A15 y A26, que no bloquea el `/implement` de la 020 pero sí el `/tasks` de la 014.
Abiertos en la 020: CRÍTICA 0 · ALTA 1 · MEDIA 4 · BAJA 3.

Suposiciones del plan comprobadas contra el código y Laravel 12.62 y ciertas:
- `$exceptions->respond()` recibe la excepción ya preparada: `AuthorizationException` llega como 403 y una subclase de `HttpException` pasa intacta.
- `Context` es un servicio `scoped`.
- `migrate:refresh --path=` actúa solo sobre ese archivo.
- La transacción anidada dentro de `DatabaseTransactions` usa un savepoint.
- Un `SIGNAL` en un trigger `BEFORE DELETE` revierte el `DELETE` multifila entero.
- Todos los nombres de ruta cumplen `^[a-z_.]+$`.

Ronda anterior: [history/analysis.r2.md](../history/analysis.r2.md).

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 13 / 13 |
| Amenazas con control y test | 13 / 13 (el test del camino de fallo de TM9 no prueba nada: A32) |
| Principios de la constitución evaluados | 8 / 8 (P2, P4 y P8 con reserva: A32, A34, A35, A36, A38; ninguna violación) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A01 | ALTA | resuelto | La fila CA2 de Trazabilidad delimita lo que cubre el criterio y lo que queda fuera; nuevo `test_un_borrado_masivo_se_rechaza`. |
| A02 | ALTA | resuelto | Tabla "Forma del evento de denegación"; T013 aserta los campos exactos. |
| A03 | ALTA | resuelto | Contratos: los fallos del almacén no se encadenan ni se registran; TM13 con 2 tests; el doble lanza una `QueryException` real. Queda un residuo en lecturas y purga, en A38. |
| A04 | ALTA | resuelto | Fuera de HTTP, `Auditoria` genera un `request_id`; `test_la_purga_queda_registrada` ejecuta el comando real. |
| A05 | ALTA | resuelto | T001 crea la tabla sin triggers, T011 falla porque aún no los hay y T031 los añade. |
| A06 | ALTA | 020: resuelto · 014: abierto → `/plan 014 --fix` | Plan 020: "no hay ninguna marca que valga para toda la petición", con su test. Plan 014, l.36 y l.155: sigue con `Gate::after` sobre todas las comprobaciones y la marca en el `Context`. |
| A07 | MEDIA | resuelto | T020 retirada; interfaz en T004 y doble en T005. |
| A08 | MEDIA | resuelto | Las dependencias son reales y ningún criterio de hecho cita una tarea posterior (el de T026 lo trata A34). |
| A09 | MEDIA | 020: resuelto · 014: abierto → `/plan 014 --fix` | D12 del plan 020. El plan 014 sigue con `denegado_403`, y la spec 014 CA11 pide "denegado" también para las denegaciones mostradas como 404. |
| A10 | MEDIA | resuelto | TM11, CA13 y sus 2 tests. |
| A11 | MEDIA | resuelto con efecto nuevo (→ A35) | Filtros solo con formato de identificador; el formato de las fechas quedó sin fijar. |
| A12 | MEDIA | resuelto con efecto nuevo (→ A32, A33) | D13 hace la purga transaccional, pero el test no la ejercita y el responsable del borrado difiere entre plan y tareas. |
| A13 | MEDIA | resuelto | "Garantía del modo crítico" y `test_en_modo_critico_una_escritura_fallida_no_deja_cambios`. |
| A14 | MEDIA | resuelto | Gate y rutas de prueba; callback `respond`; T013. |
| A15 | MEDIA | abierto → `/specify --edit 014` | La Cobertura de riesgos de la spec 014 sigue solo con RS1.a–e; `observability.md` sigue con "OB1.d … pendiente". |
| A16 | MEDIA | resuelto | T091 excluye la migración del paso `migrate:rollback`. |
| A17 | MEDIA | resuelto | Test del grupo `rnf` en una transacción que se revierte, con p95 definido; nota en T041. |
| A18 | MEDIA | resuelto con efecto nuevo (→ A36) | El actor del evento sale de `Auth::user()`, pero el de los logs no se actualiza. |
| A19 | BAJA | resuelto | T012 cubre respuesta, logs y `auditoria`. |
| A20 | BAJA | resuelto | Contratos y Observabilidad coinciden en las claves. |
| A21 | BAJA | resuelto | D1 dice (accepted). |
| A22 | BAJA | resuelto | El riesgo sobre la 014 está actualizado. |
| A23 | BAJA | resuelto | D11 declara la excepción de nombres. |
| A24 | BAJA | resuelto | `CapturaLogs` figura en Cambios por módulo; las dependencias son reales. |
| A25 | BAJA | resuelto | T014 aserta el `acceso_denegado` del no admin. |
| A26 | BAJA | 020: resuelto · 014: abierto → `/plan 014 --fix` | El plan 020 define la obtención por inyección; el plan 014 sigue llamando a `Auditoria::denegacion`, que no existe (el método es `denegar`). |
| A27 | BAJA | resuelto (ronda 2) | — |
| A28 | BAJA | resuelto | P3 justifica que el alcance por recurso no aplica. |
| A29 | MEDIA | resuelto | CA2, TM1 y T011 incluyen las credenciales de la app y el borrado masivo. |
| A30 | MEDIA | resuelto | CA13 concreta la propiedad; T012 la prueba. |
| A31 | BAJA | resuelto | CA13 tiene su propia fila en Trazabilidad y en Cobertura. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A32 | Cobertura (TM9, CA12) | ALTA | T005 (el doble "lanza … en `escribir()`"); T004 (la interfaz incluye "purgar hasta una fecha"); T011; plan TM9 | El doble sustituye al almacén entero, purga incluida, así que en `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` el borrado real nunca ocurre. El test pasaría igual con una purga que borra y después registra en modo no crítico: no prueba D13. | Con eventos de más de 5 años en la base, el borrado real se ejecuta, solo falla la escritura de `auditoria_purgada`, y aun así el recuento de eventos antiguos no cambia. El test debe fallar contra una purga que borre y registre en modo no crítico (comprobación al cerrar T028). → `/plan 020 --fix`, `/tasks 020 --fix` |
| A33 | Inconsistencia | MEDIA | `plan.md` Cambios por módulo, `AlmacenAuditoriaBaseDatos` ("Escritura y consulta paginada") y `routes/console.php` (el comando activa el indicador y borra) vs T004 y T021 (la purga vive en el almacén) | El plan pone el borrado y el indicador en el comando y las tareas en el almacén. Eso decide dónde puede fallar el doble (A32). | Un único responsable del borrado y del indicador, el mismo en el plan y en T004, T021 y T028. Tests: `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `test_la_purga_elimina_solo_eventos_de_mas_de_5_anios` y el de A32. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A34 | Inconsistencia (dependencias) | MEDIA | T012 (no dice cómo producen eventos sus peticiones); T026 ("hecho cuando: T012 pasa completo", depende de T023–T025) | Al cerrar T026 ninguna ruta real escribe eventos: el 403 central llega en T027 y la consulta en T029. Si T012 usa rutas reales, el criterio de T026 no se puede verificar; si no escribe eventos, "no aparece en `auditoria`" pasa sin probar nada. | T012 debe pasar completo solo con T022–T026, y cada petición que aserta sobre `auditoria` debe escribir al menos un evento (test: ruta de prueba que registra con el servicio). → `/tasks 020 --fix` |
| A35 | Ambigüedad / P4 | MEDIA | `plan.md` Rutas ("fechas válidas"), D7, patrón de `detalle`; T029 | El formato de `desde` y `hasta` no está fijado. Una fecha con hora o zona que la validación genérica acepta no cumple el patrón de `detalle`: `registrar` lanzaría `InvalidArgumentException` y el usuario vería un 500. Tampoco se dice si `hasta` incluye ese día. | `desde` y `hasta` admiten un solo formato declarado; cualquier otro da error de validación, sin 500 y sin evento; el rango incluye los dos extremos. Tests en `ConsultaAuditoriaTest`: una fecha con hora y otra con zona dan error de validación sin `auditoria_consultada`, y un evento del día `hasta` aparece en los resultados. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A36 | Inconsistencia (CA10) | MEDIA | `plan.md` `ContextoActor` ("`Context::add('actor_id')` si hay sesión") y Contratos (actor del evento desde `Auth::user()`); Trazabilidad de CA10 | Los logs toman el actor del `Context`, fijado antes del controlador, y los eventos de `Auth::user()` al registrar. Tras un login o un logout dentro de la petición (la 021), logs y eventos divergen, contra CA10. Además, el `Context` no se vacía entre peticiones de un mismo test, así que una petición anónima puede heredar el `actor_id` de la anterior. | Tras un cambio de autenticación dentro de una petición, los logs y los eventos escritos después llevan el mismo actor, y ninguno tras un logout. Una petición sin sesión nunca lleva `actor_id` en sus logs. Tests en `IdentificadorPeticionTest`: una ruta que autentica y después escribe un log y un evento da el mismo `actor_id` en ambos; una petición autenticada seguida de una anónima deja sin `actor_id` los logs de la segunda. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A37 | Ambigüedad | BAJA | Triggers (`NOW() - INTERVAL 5 YEAR`); el comando calcula el corte; `ocurrido_en` sin origen definido | No se dice qué reloj escribe `ocurrido_en` ni que la app y la sesión de BD compartan zona y hora. Con un desfase, un evento dentro de él hace abortar el `DELETE` entero y la purga mensual falla siempre. | Un solo reloj y una sola zona para `ocurrido_en` y para el corte, declarados como requisito en `deployment.md`. Test: con el trigger activo, en una misma ejecución se purga un evento en corte − 1 s y se conserva uno en corte + 1 s. → `/plan 020 --fix`, `/tasks 020 --fix` |
| A38 | Constitución P8 | BAJA | `plan.md` Constitution Check P8; la consulta y la purga; spec, Fuera de alcance ("redacción de errores de base de datos" → 023) | Solo se sanean los fallos de escritura. Si falla la consulta del registro o el `DELETE` de la purga, el reporte por defecto deja en el log el SQL con sus bindings (identificadores y fechas). La spec lo aplaza a la 023, pero P8 no declara el residuo. | Decidido: residuo aceptado hasta la 023. La fila P8 lo declara con su destino (023, OB4.a) y con la condición de que la 023 se libere antes de producción. → `/plan 020 --fix` |
| A39 | Ambigüedad | BAJA | `spec.md` CA2 y Supuestos; `plan.md` TM1 y Riesgos | Las exclusiones de CA2 solo nombran vaciar el almacén y desactivar su protección. Un `ALTER TABLE` o un `RENAME` con las credenciales de la app altera eventos sin disparar los triggers y no queda excluido de forma explícita. | Decidido: la exclusión de CA2 cubre cualquier operación de estructura (DDL) con privilegios totales, en línea con el ADR 0004, sin test. → `/specify --edit 020`, luego `/plan 020 --fix` (TM1, Riesgos) |

## Decisiones pendientes del usuario
Resueltas en esta ronda:
- **A38:** los bindings que dejan en el log los fallos de la consulta y de la purga se aceptan como residuo hasta la spec 023 (OB4.a). Se declara en la fila P8 y la 023 debe liberarse antes de producción (decisión del usuario, 2026-09-28).
- **A39:** la exclusión de CA2 se amplía a cualquier operación de estructura (DDL) hecha con privilegios totales (decisión del usuario, 2026-09-28).
- **A32–A37:** se corrigen en la misma ronda de `--fix`, no como notas de tarea (decisión del usuario, 2026-09-28).

## Aceptados
Ninguno: todos los hallazgos de la 020 se corrigen antes de `/implement`. Las partes de la 014 de
A06, A09 y A26 van a `/plan 014 --fix`, y A15 a `/specify --edit 014`. No bloquean la 020.
