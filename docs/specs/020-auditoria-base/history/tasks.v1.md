---
spec: 020-auditoria-base
plan: plan.md
status: draft
---

# Tareas · 020 Registro de auditoría base y correlación por petición

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 020 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ✅ | Las tareas de test (T010–T014) van antes y cada tarea de implementación depende de la de test que la verifica. |
| P3 Autorización en servidor | ✅ | T029 registra la ruta dentro del grupo `role:admin`; T014 prueba el caso permitido y el denegado para los cuatro roles. |
| P4 Entrada validada y SQL con bindings | ✅ | T024 valida `X-Request-Id`; T029 valida filtros; T001 usa SQL estático sin entrada del usuario. |
| P5 Nada sensible en el repo | ✅ | Los tests generan eventos y usuarios sintéticos; ninguna tarea añade secretos. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias (RS16 va en su propia spec). |
| P7 Auditoría de datos sensibles | ✅ | Es el objeto de la spec (T001, T020–T030). |
| P8 Correlación y logs limpios | ✅ | T024–T026; los logs `auditoria.fallo` y `auditoria.purga` (T022, T028) no llevan datos personales. |

Ruta protegida: T040 modifica `docker-compose.yml` y requiere aprobación explícita del usuario al ejecutarla.

## Preparación
- [ ] T001 [P] Crear la migración de la tabla `auditoria` con sus índices y los triggers `auditoria_sin_update` y `auditoria_sin_delete` (SQL estático del plan), con `down()` que los elimina — database/migrations/2026_09_28_000000_create_auditoria_table.php — hecho cuando: `php artisan migrate` crea tabla y triggers en local, un `UPDATE` manual falla con SQLSTATE 45000 y `php artisan migrate:rollback --step=1` los elimina — cubre: CA1, CA2, CA5
- [ ] T002 [P] Ampliar el arnés de la base de pruebas para aplicar las migraciones tras cargar el volcado — database/testing/bootstrap-test-db.sh — hecho cuando: tras ejecutarlo, `dentissa_test` tiene las tablas `auditoria` y `asistente_dentista` y la columna `pacientes.dentista_id`, y el script sigue siendo idempotente
- [ ] T003 [P] Crear los apoyos de test: almacén de auditoría que siempre falla y captura de logs con `TestHandler` — tests/Support/AlmacenAuditoriaQueFalla.php, tests/Support/CapturaLogs.php — hecho cuando: ambas clases cargan por `autoload-dev` y `AlmacenAuditoriaQueFalla` implementa la interfaz prevista en el plan (se completa junto con T020)

## Tests (antes de implementar)
- [ ] T010 Escribir `RegistroAuditoriaTest`: campos completos del evento, identificador intentado sin sesión, `campos` solo nombres, `detalle` solo escalares, actor tomado de la sesión, fallo cerrado en modo crítico y fallo abierto con log en modo no crítico (TM5, TM6, TM10) — tests/Feature/Auditoria/RegistroAuditoriaTest.php — hecho cuando: falla por la razón esperada (no existe `App\Auditoria\Auditoria`) — cubre: CA1, CA3, CA11 — depende: T001, T002, T003
- [ ] T011 Escribir `InmutabilidadAuditoriaTest`: un evento no se modifica ni se borra, la purga no borra eventos recientes aunque se active (TM1, TM2), la purga elimina solo lo de más de 5 años, está programada mensualmente y queda registrada (TM9) — tests/Feature/Auditoria/InmutabilidadAuditoriaTest.php — hecho cuando: los tests de triggers pasan tras T001 y los de purga fallan por la razón esperada (no existe `auditoria:purgar`) — cubre: CA2, CA5, CA12 — depende: T001, T002
- [ ] T012 Escribir `IdentificadorPeticionTest`: reutiliza un UUID válido, genera uno si no llega, lo devuelve también en respuestas de error, sustituye uno malformado o demasiado largo sin reflejarlo (TM3), y logs y eventos comparten `request_id` y actor — tests/Feature/Auditoria/IdentificadorPeticionTest.php — hecho cuando: falla por la razón esperada (no hay cabecera `X-Request-Id`) — cubre: CA8, CA9, CA10 — depende: T003
- [ ] T013 Escribir `DenegacionesAuditadasTest`: entrar al área de otro rol queda registrado, un `abort(403)` de controlador queda registrado, una denegación no se registra dos veces, un visitante sin sesión no genera evento — tests/Feature/Auditoria/DenegacionesAuditadasTest.php — hecho cuando: falla por la razón esperada (no se registra ningún evento) — cubre: CA4 — depende: T002
- [ ] T014 Escribir `ConsultaAuditoriaTest`: filtros por actor, acción, recurso y fechas; paginación del más reciente al más antiguo; menú solo para admin; no admin → 403 (TM4); filtros inválidos rechazados (TM8); consulta registrada con sus filtros; fallo cerrado con 503 genérico aunque `APP_DEBUG=true` (TM7); no existen rutas de modificación — tests/Feature/Auditoria/ConsultaAuditoriaTest.php — hecho cuando: falla por la razón esperada (no existe la ruta `admin.auditoria.index`) — cubre: CA2, CA6, CA7, CA11, CA12 — depende: T002, T003

## Implementación
- [ ] T020 Crear la interfaz `AlmacenAuditoria` (escribir, consultar paginado con filtros, purgar hasta una fecha) y la excepción `AuditoriaNoDisponible` — app/Auditoria/AlmacenAuditoria.php, app/Auditoria/AuditoriaNoDisponible.php — hecho cuando: `php -l` sin errores y `AlmacenAuditoriaQueFalla` (T003) la implementa — cubre: CA11 — depende: T003
- [ ] T021 Implementar `AlmacenAuditoriaBaseDatos`: inserción, consulta filtrada con nombre del actor (join a `usuarios`) paginada de 50 en orden descendente, y purga que activa `@auditoria_purga`, borra lo anterior a la fecha de corte y lo desactiva en `finally` — app/Auditoria/AlmacenAuditoriaBaseDatos.php — hecho cuando: los tests de persistencia de T010 y los de purga de T011 que usan el almacén pasan — cubre: CA1, CA5, CA6 — depende: T001, T020, T010
- [ ] T022 Implementar el servicio `Auditoria` (`registrar` y `denegacion`): actor, rol, IP y `request_id` desde el contexto; validación de `campos` (`^[a-z_]+$`) y `detalle` (solo escalares) (control TM5, TM10); modo crítico que lanza `AuditoriaNoDisponible` (control TM6) y modo no crítico que registra `auditoria.fallo`; marca en el `Context` al registrar una denegación — app/Auditoria/Auditoria.php — hecho cuando: T010 pasa completo — cubre: CA1, CA3, CA11 — depende: T010, T021
- [ ] T023 Registrar `AlmacenAuditoria` → `AlmacenAuditoriaBaseDatos` y `Auditoria` como singleton — app/Providers/AppServiceProvider.php — hecho cuando: `app(App\Auditoria\Auditoria::class)` devuelve siempre la misma instancia y los tests pueden sustituir el almacén por `AlmacenAuditoriaQueFalla` — cubre: CA1 — depende: T022
- [ ] T024 Implementar el middleware `IdentificadorPeticion`: acepta `X-Request-Id` solo si es UUID de 36 caracteres, si no genera uno, `Context::add('request_id')` y cabecera en la respuesta (control TM3) — app/Http/Middleware/IdentificadorPeticion.php — hecho cuando: los casos de identificador de T012 pasan al registrarlo en T026 — cubre: CA8, CA9, CA10 — depende: T012
- [ ] T025 Implementar el middleware `ContextoActor`: `Context::add('actor_id')` si hay sesión — app/Http/Middleware/ContextoActor.php — hecho cuando: el caso de actor de T012 pasa al registrarlo en T026 — cubre: CA10 — depende: T012
- [ ] T026 Registrar los middlewares: `prepend` global de `IdentificadorPeticion` y `appendToGroup('web', ContextoActor)` — bootstrap/app.php — hecho cuando: T012 pasa completo — cubre: CA8, CA9, CA10 — depende: T024, T025
- [ ] T027 Configurar `withExceptions`: render de `AuditoriaNoDisponible` como vista 503 genérica con el `request_id` aunque `APP_DEBUG=true` (control TM7), y registro central de 403 (`HttpException` 403 y `AuthorizationException`) de usuarios autenticados si la petición no registró ya su denegación — bootstrap/app.php, resources/views/errors/auditoria-no-disponible.blade.php — hecho cuando: T013 pasa completo — cubre: CA4, CA11 — depende: T013, T023, T026
- [ ] T028 Crear el comando `auditoria:purgar` (corte a 5 años, log `auditoria.purga`, evento `auditoria_purgada` con recuento y fecha de corte, control TM9) y programarlo con `Schedule::command(...)->monthly()` — routes/console.php — hecho cuando: T011 pasa completo — cubre: CA5, CA12 — depende: T011, T023
- [ ] T029 Crear `AuditoriaController@index` (validación de filtros, control TM8; registro crítico de `auditoria_consultada` con los filtros usados; paginación) y la ruta `GET /admin/auditoria` dentro del grupo `role:admin` (control TM4), sin rutas de modificación — app/Http/Controllers/AuditoriaController.php, routes/web.php — hecho cuando: los casos de consulta, permisos, filtros inválidos, registro de la consulta y fallo cerrado de T014 pasan — cubre: CA2, CA6, CA7, CA11, CA12 — depende: T014, T023, T027
- [ ] T030 Crear la vista de consulta (filtros, tabla, paginación, estado vacío "No hay eventos con estos filtros") y la entrada "Auditoría" en la sección "Análisis" del menú de admin — resources/views/auditoria/index.blade.php, resources/views/layouts/app.blade.php — hecho cuando: T014 pasa completo, incluido el menú solo para admin — cubre: CA6 — depende: T029

## Integración y documentación
- [ ] T040 Añadir el servicio `scheduler` (misma imagen que `app`, `php artisan schedule:work`) a Docker Compose — **ruta protegida: requiere aprobación explícita del usuario** — docker-compose.yml — hecho cuando: `docker compose up -d` levanta `scheduler` y `docker compose exec scheduler php artisan schedule:list` muestra `auditoria:purgar` mensual — cubre: CA5 — depende: T028
- [ ] T041 Verificar a mano los requisitos no funcionales (registro < 50 ms; consulta con 100.000 eventos sintéticos < 2 s) y anotar las mediciones como nota bajo esta tarea — docs/specs/020-auditoria-base/tasks.md — hecho cuando: la nota con ambas mediciones existe y cumple los umbrales — depende: T030
- [ ] T042 Ejecutar la suite completa y Pint sobre los archivos tocados — (sin archivos) — hecho cuando: `php artisan test` en verde contra `dentissa_test` y `vendor/bin/pint --test` limpio sobre los archivos de esta spec — depende: T030, T040
- [ ] T090 Actualizar `docs/architecture.md`: módulo "Auditoría", tabla `auditoria` y triggers en Datos, correlación en Backend → Observabilidad
- [ ] T091 Actualizar `docs/deployment.md` (servicio `scheduler`, permisos para crear triggers, verificación post-deploy con `X-Request-Id`) y `docs/observability.md` (Resumen: correlación y registro de auditoría "en uso"; OB1.a, OB1.c, OB1.g, OB1.h, OB2.a y OB2.b siguen "en curso (spec 020)" hasta `/release`, por corrección)
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | T001, T021, T022, T023 |
| CA2 | T011, T014 | T001, T029 |
| CA3 | T010 | T022 |
| CA4 | T013 | T027 |
| CA5 | T011 | T001, T021, T028, T040 |
| CA6 | T014 | T021, T029, T030 |
| CA7 | T014 | T029 |
| CA8 | T012 | T024, T026 |
| CA9 | T012 | T024, T026 |
| CA10 | T012 | T024, T025, T026 |
| CA11 | T010, T014 | T020, T022, T027, T029 |
| CA12 | T011, T014 | T028, T029 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T001 | T011 |
| TM2 | T001, T021 | T011 |
| TM3 | T024 | T012 |
| TM4 | T029 | T014 |
| TM5 | T022 | T010 |
| TM6 | T022 | T010 |
| TM7 | T027 | T014 |
| TM8 | T029 | T014 |
| TM9 | T028 | T011 |
| TM10 | T022 | T010 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| database/migrations/2026_09_28_000000_create_auditoria_table.php | T001 |
| app/Auditoria/Auditoria.php | T022 |
| app/Auditoria/AlmacenAuditoria.php, AlmacenAuditoriaBaseDatos.php | T020, T021 |
| app/Auditoria/AuditoriaNoDisponible.php | T020 |
| app/Providers/AppServiceProvider.php | T023 |
| app/Http/Middleware/IdentificadorPeticion.php | T024 |
| app/Http/Middleware/ContextoActor.php | T025 |
| bootstrap/app.php | T026, T027 |
| app/Http/Controllers/AuditoriaController.php | T029 |
| routes/web.php | T029 |
| routes/console.php | T028 |
| resources/views/auditoria/index.blade.php | T030 |
| resources/views/errors/auditoria-no-disponible.blade.php | T027 |
| resources/views/layouts/app.blade.php | T030 |
| docker-compose.yml | T040 |
| database/testing/bootstrap-test-db.sh | T002 |
| docs/deployment.md | T091 |
| tests/Feature/Auditoria/*Test.php, tests/Support/* | T003, T010–T014 |
