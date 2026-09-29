---
spec: 020-auditoria-base
plan: plan.md
status: approved
---

# Tareas · 020 Registro de auditoría base y correlación por petición

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable (no comparte archivos con otra tarea abierta ni depende de una pendiente).
- Versión anterior: `history/tasks.v1.md` (regenerada con `--redo` tras `/analyze` ronda 2 y `/plan --fix`). El número T020 queda retirado: su contenido (interfaz y excepción) pasa a T004.

## Constitution Check
| Principio | Resultado | Justificación |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 020 `approved`; estas tareas se aprueban antes de `/implement`. |
| P2 Test que falla antes y pasa después | ✅ | Cada tarea de implementación depende de la tarea de test que la verifica, y ningún criterio de hecho cita una tarea posterior. Los tests de triggers (T011) se ven fallar porque T001 crea la tabla sin triggers; los triggers llegan en T031. T001, T004 y T005 son andamiaje sin comportamiento que verificar por sí mismo (tabla, interfaz, excepciones, doble de test). |
| P3 Autorización en servidor | ✅ | T029 registra la ruta dentro del grupo `role:admin`; T014 prueba el caso permitido y el denegado para los cuatro roles. El alcance por recurso no aplica (plan, P3). |
| P4 Entrada validada y SQL con bindings | ✅ | T024 valida `X-Request-Id`; T029 valida los filtros (solo identificadores y fechas); T027 guarda el `id` de ruta solo con formato de identificador; T022 valida formatos y vocabulario; T031 usa SQL estático. |
| P5 Nada sensible en el repo | ✅ | Los tests generan eventos y usuarios sintéticos; IP e identificadores de prueba ficticios; ninguna tarea añade secretos. |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias (`TestHandler` es de Monolog, ya instalado; RS16 va en su propia spec). |
| P7 Auditoría de datos sensibles | ✅ | Es el objeto de la spec (T001, T021–T031). |
| P8 Correlación y logs limpios | ✅ | T024–T026 (correlación); T022 genera `request_id` fuera de HTTP y sanea los fallos del almacén (TM13); los logs `auditoria.fallo` y `auditoria.purga` no llevan datos personales. |

Ruta protegida: T040 modifica `docker-compose.yml` y requiere aprobación explícita del usuario al ejecutarla.

## Preparación
- [ ] T001 [P] Crear la migración de la tabla `auditoria` con sus columnas e índices según el plan, **sin triggers** (llegan en T031), y `down()` que la elimina (solo para desarrollo) — database/migrations/2026_09_28_000000_create_auditoria_table.php — hecho cuando: `php artisan migrate` crea la tabla con sus índices en local y `php artisan migrate:rollback --step=1` la elimina — cubre: CA1
- [ ] T002 Ampliar el arnés de la base de pruebas para aplicar las migraciones tras cargar el volcado — database/testing/bootstrap-test-db.sh — hecho cuando: tras ejecutarlo, `dentissa_test` tiene las tablas `auditoria` y `asistente_dentista` y la columna `pacientes.dentista_id`, y el script sigue siendo idempotente — depende: T001
- [ ] T003 [P] Crear el apoyo de captura de logs con `TestHandler` de Monolog — tests/Support/CapturaLogs.php — hecho cuando: la clase carga por `autoload-dev` y expone los registros capturados con su contexto
- [ ] T004 [P] Crear la interfaz `AlmacenAuditoria` (escribir, consultar paginado con filtros, purgar hasta una fecha) y las excepciones `AuditoriaNoDisponible` (sin excepción previa) y `AccesoDenegadoAuditado` (`HttpException` con estado 403 o 404), sin lógica de registro — app/Auditoria/AlmacenAuditoria.php, app/Auditoria/AuditoriaNoDisponible.php, app/Auditoria/AccesoDenegadoAuditado.php — hecho cuando: `php -l` sin errores en los tres archivos — cubre: CA4, CA11
- [ ] T005 Crear el doble `AlmacenAuditoriaQueFalla`: implementa `AlmacenAuditoria`, recibe por constructor un `AlmacenAuditoria` real y le delega `consultar()` y `purgar()`; solo `escribir()` lanza una `QueryException` real con SQL y bindings de prueba (una IP y un identificador ficticios) — tests/Support/AlmacenAuditoriaQueFalla.php — hecho cuando: la clase carga por `autoload-dev`, implementa la interfaz de T004, delega consulta y purga al almacén recibido y `escribir()` lanza la `QueryException` con esos bindings — depende: T004

## Tests (antes de implementar)
- [ ] T010 Escribir `RegistroAuditoriaTest`: campos completos del evento; identificador intentado sin sesión; `resultado` fuera del vocabulario rechazado; `campos` solo nombres; `detalle` sin valores no escalares ni texto libre (TM5, TM12); actor autenticado y actor tras autenticarse en la misma petición (TM10); `request_id` generado fuera de HTTP; fallo cerrado en modo crítico (TM6); escritura crítica fallida sin cambios (una escritura sintética en `servicios` dentro de una transacción); fallo abierto en modo no crítico; un fallo del almacén no deja SQL, bindings, IP ni identificador en el log y `auditoria.fallo` lleva exactamente `accion`, `critico` y `error` (TM13) — tests/Feature/Auditoria/RegistroAuditoriaTest.php — hecho cuando: falla por la razón esperada (no existe `App\Auditoria\Auditoria`) — cubre: CA1, CA3, CA10, CA11 — depende: T002, T003, T005
- [ ] T011 Escribir `InmutabilidadAuditoriaTest` con la conexión de la propia aplicación: un evento no se modifica, no se borra, y un borrado sin condición se rechaza sin que cambie ningún evento (TM1); la purga no borra eventos recientes aunque se active el indicador (TM2); la purga elimina solo lo de más de 5 años; con el trigger activo, en una misma ejecución purga un evento en corte − 1 s y conserva uno en corte + 1 s, medido en UTC (`test_la_purga_respeta_el_corte_al_segundo`); está programada mensualmente; queda registrada ejecutando el comando real, con un `request_id` válido (TM9); y con eventos de más de 5 años y `AlmacenAuditoriaQueFalla` envolviendo al almacén real, el `DELETE` real se ejecuta, el comando termina con error y el recuento de eventos antiguos no cambia (TM9) — tests/Feature/Auditoria/InmutabilidadAuditoriaTest.php — hecho cuando: los tests de inmutabilidad fallan por la razón esperada (el `UPDATE` y los `DELETE` se aceptan porque aún no hay triggers) y los de purga fallan porque no existe `auditoria:purgar` — cubre: CA2, CA5, CA12 — depende: T002, T005
- [ ] T012 Escribir `IdentificadorPeticionTest`, con rutas de prueba registradas en `setUp` que escriben un log y un evento con el servicio `Auditoria` (y una que autentica o cierra sesión antes de escribirlos), de modo que el test solo dependa de T022–T026: reutiliza un UUID válido; genera uno si no llega; lo devuelve también en respuestas de error; sustituye uno malformado o demasiado largo (TM3) y ese valor no aparece ni en la respuesta, ni en los logs capturados, ni en `auditoria` (con la petición escribiendo al menos un evento); logs y eventos comparten `request_id` y actor; tras autenticarse o cerrar sesión dentro de la petición, logs y eventos posteriores llevan el mismo actor (ninguno tras cerrar sesión); una petición anónima tras otra autenticada no lleva `actor_id` en sus logs; un identificador repetido por dos actores distintos da dos eventos, cada uno con su actor, y el de un admin reutilizado sin sesión no da actor ni acceso (TM11) — tests/Feature/Auditoria/IdentificadorPeticionTest.php — hecho cuando: falla por la razón esperada (no hay cabecera `X-Request-Id`) — cubre: CA8, CA9, CA10, CA13 — depende: T002, T003
- [ ] T013 Escribir `DenegacionesAuditadasTest`, con rutas de prueba (`web` + `auth`) y un Gate de prueba registrados en `setUp`: entrar al área de otro rol deja un `acceso_denegado` con `detalle.accion_solicitada`, `recurso_tipo` y `resultado` exactos; un `abort(403)` de controlador deja el `recurso_id` de la ruta; una denegación por Gate (`AuthorizationException`) queda registrada; `denegar()` registra una sola vez; una comprobación denegada que no rechaza seguida de un `abort(403)` deja exactamente un evento, el del 403; un `id` de ruta con formato inválido no se guarda (TM12); un visitante sin sesión no genera evento — tests/Feature/Auditoria/DenegacionesAuditadasTest.php — hecho cuando: falla por la razón esperada (no se registra ningún evento y `denegar()` no existe) — cubre: CA3, CA4 — depende: T002, T004
- [ ] T014 Escribir `ConsultaAuditoriaTest`: filtros por actor, acción, recurso y fechas; el filtro por actor solo admite identificadores; un filtro con texto libre se rechaza y no se registra (TM12); `desde` o `hasta` con hora o con zona dan error de validación, sin 500 y sin `auditoria_consultada` (TM8, TM12); un evento del día `hasta` aparece en los resultados; paginación del más reciente al más antiguo; menú solo para admin; un no admin recibe 403 (TM4) y su intento deja un `acceso_denegado` con `detalle.accion_solicitada` = `admin.auditoria.index`; filtros inválidos rechazados (TM8); consulta registrada con sus filtros; fallo cerrado con 503 genérico aunque `APP_DEBUG=true` (TM7); no existen rutas de modificación — tests/Feature/Auditoria/ConsultaAuditoriaTest.php — hecho cuando: falla por la razón esperada (no existe la ruta `admin.auditoria.index`) — cubre: CA2, CA4, CA6, CA7, CA11, CA12 — depende: T002, T005
- [ ] T015 Escribir `AuditoriaRendimientoTest` en el grupo `rnf` (100.000 eventos sintéticos dentro de la transacción del test; p95 de 200 llamadas a `registrar` < 50 ms; p95 de 20 consultas con filtros distintos < 2 s) y excluir el grupo `rnf` de la suite por defecto — tests/Feature/Auditoria/AuditoriaRendimientoTest.php, phpunit.xml — hecho cuando: `php artisan test --group=rnf` falla por la razón esperada (no existe `App\Auditoria\Auditoria`) y `php artisan test` no lo ejecuta — depende: T002

## Implementación
- [ ] T021 Implementar `AlmacenAuditoriaBaseDatos`, único responsable del borrado y del indicador de purga: inserción; consulta filtrada con nombre del actor (join a `usuarios`), paginada de 50 en orden descendente; y `purgar(corte): int`, que, dentro de la transacción del llamador, activa `@auditoria_purga`, borra lo anterior al corte, devuelve el recuento y desactiva el indicador en `finally` — app/Auditoria/AlmacenAuditoriaBaseDatos.php — hecho cuando: implementa la interfaz de T004 y `php -l` sin errores — cubre: CA1, CA5, CA6 — depende: T004, T010, T011, T014
- [ ] T022 Implementar el servicio `Auditoria` según el contrato del plan: `registrar` con validación de formatos, vocabulario cerrado de `resultado`, `campos` y `detalle` (control TM5, TM12); actor y rol de `Auth::user()` al registrar (control TM10, TM11); `request_id` del `Context` o generado y añadido si no hay; fallos del almacén capturados sin encadenar ni registrar su mensaje y `auditoria.fallo` con exactamente `accion`, `critico` y `error` (control TM13); modo crítico que lanza `AuditoriaNoDisponible` (control TM6); y `denegar()`, que registra `acceso_denegado` en modo no crítico y lanza `AccesoDenegadoAuditado` — app/Auditoria/Auditoria.php — hecho cuando: `php -l` sin errores — cubre: CA1, CA3, CA4, CA10, CA11, CA13 — depende: T010, T013, T021
- [ ] T023 Registrar `AlmacenAuditoria` → `AlmacenAuditoriaBaseDatos` y `Auditoria` como singleton, y escuchar los eventos `Login` y `Logout` de Laravel para poner o quitar `actor_id` en el `Context` — app/Providers/AppServiceProvider.php — hecho cuando: T010 pasa completo, `app(App\Auditoria\Auditoria::class)` devuelve siempre la misma instancia y los tests sustituyen el almacén por `AlmacenAuditoriaQueFalla` — cubre: CA1, CA3, CA10, CA11 — depende: T022
- [ ] T024 Implementar el middleware `IdentificadorPeticion`: acepta `X-Request-Id` solo si es UUID de 36 caracteres, si no genera uno; `Context::add('request_id')` y cabecera en la respuesta (control TM3, TM11) — app/Http/Middleware/IdentificadorPeticion.php — hecho cuando: `php -l` sin errores — cubre: CA8, CA9, CA10, CA13 — depende: T012
- [ ] T025 Implementar el middleware `ContextoActor`: al empezar cada petición, `Context::add('actor_id')` si hay sesión y `Context::forget('actor_id')` si no la hay — app/Http/Middleware/ContextoActor.php — hecho cuando: `php -l` sin errores — cubre: CA10 — depende: T012
- [ ] T026 Registrar los middlewares: `prepend` global de `IdentificadorPeticion` y `appendToGroup('web', ContextoActor)` — bootstrap/app.php — hecho cuando: T012 pasa completo — cubre: CA8, CA9, CA10, CA13 — depende: T023, T024, T025
- [ ] T027 Configurar `withExceptions`: render de `AuditoriaNoDisponible` como vista 503 genérica con el `request_id` aunque `APP_DEBUG=true`, y `dontReport` de esa excepción (control TM7); callback `respond` que registra `acceso_denegado` para toda respuesta 403 de usuario autenticado cuya excepción no sea `AccesoDenegadoAuditado`, con la forma del plan (acción solicitada = nombre de ruta, `recurso_tipo` del nombre, `recurso_id` solo con formato de identificador; control TM12) — bootstrap/app.php, resources/views/errors/auditoria-no-disponible.blade.php — hecho cuando: T013 pasa completo — cubre: CA3, CA4, CA11 — depende: T013, T023, T026
- [ ] T028 Crear el comando `auditoria:purgar` y programarlo con `Schedule::command(...)->monthly()`: calcula el corte (ahora en UTC − 5 años) y, dentro de `DB::transaction`, llama a `purgar()` del almacén y registra `auditoria_purgada` en modo crítico con recuento y fecha de corte (si el registro falla, la transacción revierte el borrado; control TM9); log `auditoria.purga` — routes/console.php — hecho cuando: pasan los tests de purga de T011 (solo borra lo de más de 5 años, respeta el corte al segundo, programada mensualmente, registrada con `request_id` válido, sin borrado si el registro falla) y, como comprobación, el test de purga fallida falla si el evento se registra temporalmente en modo no crítico — cubre: CA5, CA12 — depende: T011, T023
- [ ] T029 Crear `AuditoriaController@index` (filtros validados: `actor` y `recurso_id` solo identificadores, `accion` y `recurso_tipo` con `^[a-z_]{1,60}$`, `desde` y `hasta` solo `AAAA-MM-DD` con `hasta >= desde` y el día `hasta` incluido; cualquier otro valor da error de validación sin 500; control TM8, TM12; registro crítico de `auditoria_consultada` solo con los filtros validados; paginación) y la ruta `GET /admin/auditoria` dentro del grupo `role:admin` (control TM4), sin rutas de modificación — app/Http/Controllers/AuditoriaController.php, routes/web.php — hecho cuando: pasan los casos de consulta, filtros (incluidas las fechas), permisos y su evento, registro de la consulta y fallo cerrado de T014 — cubre: CA2, CA4, CA6, CA7, CA11, CA12 — depende: T014, T023, T027
- [ ] T030 Crear la vista de consulta (filtros, tabla, paginación, estado vacío "No hay eventos con estos filtros") y la entrada "Auditoría" en la sección "Análisis" del menú de admin — resources/views/auditoria/index.blade.php, resources/views/layouts/app.blade.php — hecho cuando: T014 pasa completo, incluido el menú solo para admin — cubre: CA6 — depende: T029
- [ ] T031 Añadir a la migración de T001 los triggers `auditoria_sin_update` y `auditoria_sin_delete` (SQL estático del plan, con `UTC_TIMESTAMP()` en el corte) y su eliminación en `down()`, y reaplicarla en local y en `dentissa_test` con `php artisan migrate:refresh --path=database/migrations/2026_09_28_000000_create_auditoria_table.php` (control TM1, TM2) — database/migrations/2026_09_28_000000_create_auditoria_table.php — hecho cuando: T011 pasa completo, incluidos el borrado sin condición, la purga activada sobre eventos recientes y el corte al segundo con el trigger activo — cubre: CA2, CA5 — depende: T011, T028

## Integración y documentación
- [ ] T040 Añadir el servicio `scheduler` (misma imagen que `app`, `php artisan schedule:work`) a Docker Compose — **ruta protegida: requiere aprobación explícita del usuario** — docker-compose.yml — hecho cuando: `docker compose up -d` levanta `scheduler` y `docker compose exec scheduler php artisan schedule:list` muestra `auditoria:purgar` mensual — cubre: CA5 — depende: T028
- [ ] T041 Ejecutar `php artisan test --group=rnf` contra `dentissa_test` y anotar como nota bajo esta tarea los p95 medidos (registro y consulta) y el equipo donde se midió — docs/specs/020-auditoria-base/tasks.md — hecho cuando: el grupo `rnf` pasa (p95 de registro < 50 ms y de consulta < 2 s) y la nota existe — depende: T015, T030
- [ ] T042 Ejecutar la suite completa y Pint sobre los archivos tocados — (sin archivos) — hecho cuando: `php artisan test` en verde contra `dentissa_test` y `vendor/bin/pint --test` limpio sobre los archivos de esta spec — depende: T031, T040, T041
- [ ] T090 Actualizar `docs/architecture.md`: módulo "Auditoría", tabla `auditoria` y triggers en Datos, correlación en Backend → Observabilidad
- [ ] T091 Actualizar `docs/deployment.md` (servicio `scheduler`; permisos para crear triggers; relojes de la app y de la BD sincronizados (NTP) y todo medido en UTC, porque un desfase hace fallar la purga; verificación post-deploy con `X-Request-Id`; en Rollback, la migración `create_auditoria_table` queda excluida del paso `migrate:rollback` porque su tabla es evidencia: el rollback es redeploy del tag anterior sin revertirla) y `docs/observability.md` (Resumen: correlación y registro de auditoría "en uso"; OB1.a, OB1.c, OB1.g, OB1.h, OB2.a y OB2.b siguen "en curso (spec 020)" hasta `/release`, por corrección)
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
| CA2 | T011, T014 | T029, T031 |
| CA3 | T010, T013 | T022, T023, T027 |
| CA4 | T013, T014 | T004, T022, T027, T029 |
| CA5 | T011 | T021, T028, T031, T040 |
| CA6 | T014 | T021, T029, T030 |
| CA7 | T014 | T029 |
| CA8 | T012 | T024, T026 |
| CA9 | T012 | T024, T026 |
| CA10 | T010, T012 | T022, T023, T024, T025, T026 |
| CA11 | T010, T014 | T004, T022, T023, T027, T029 |
| CA12 | T011, T014 | T028, T029 |
| CA13 | T012 | T022, T024, T026 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T031 | T011 |
| TM2 | T021, T031 | T011 |
| TM3 | T024 | T012 |
| TM4 | T029 | T014 |
| TM5 | T022 | T010 |
| TM6 | T022 | T010 |
| TM7 | T027 | T014 |
| TM8 | T029 | T014 |
| TM9 | T028 | T011 |
| TM10 | T022 | T010 |
| TM11 | T022, T024 | T012 |
| TM12 | T022, T027, T029 | T010, T013, T014 |
| TM13 | T022 | T010 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| database/migrations/2026_09_28_000000_create_auditoria_table.php | T001, T031 |
| app/Auditoria/Auditoria.php | T022 |
| app/Auditoria/AlmacenAuditoria.php, AlmacenAuditoriaBaseDatos.php | T004, T021 |
| app/Auditoria/AuditoriaNoDisponible.php | T004 |
| app/Auditoria/AccesoDenegadoAuditado.php | T004 |
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
| tests/Feature/Auditoria/*Test.php, tests/Support/* | T003, T005, T010–T015 |
| phpunit.xml | T015 |
