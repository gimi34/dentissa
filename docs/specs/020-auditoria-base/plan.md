---
spec: 020-auditoria-base
status: approved   # draft | approved | blocked
constitution_version: 1.0.0   # versión con la que se evaluó el Constitution Check (upgrade a AI-DD 1.11.2)
created: 2026-09-28
---

# Plan · 020 Registro de auditoría base y correlación por petición

## Enfoque técnico
Un módulo nuevo `app/Auditoria/` expone un único punto de registro, el servicio `Auditoria`, que
toma actor, IP y `request_id` del contexto de la petición (nunca de la entrada del usuario) y
escribe en una tabla `auditoria` de MariaDB. La tabla es solo-anexado: la aplicación no tiene
ninguna ruta de modificación y dos triggers rechazan `UPDATE` siempre y `DELETE` salvo durante la
purga y solo para eventos de más de 5 años. Un middleware global asigna el `X-Request-Id` (lo
reutiliza si es un UUID válido), lo pone en el `Context` de Laravel, y así llega a los logs, y lo
devuelve en la respuesta. Las denegaciones 403 se registran de forma central en `withExceptions`, salvo las que ya registra
quien rechaza la petición con `denegar()`.
El registro tiene un modo crítico con fallo cerrado para acciones sobre datos clínicos y para la
consulta del registro, que el admin hace en una pantalla nueva `/admin/auditoria`.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 020 `approved` el 2026-09-28. |
| P2 Test que falla antes y pasa después | ✅ | Todo CA y TM tiene test en Trazabilidad; ninguna de las capacidades existe hoy. |
| P3 Autorización en servidor, por rol y recurso | ✅ | `/admin/auditoria` bajo `auth` + `role:admin`; test del caso permitido (admin) y denegado (dentista, asistente, cliente). No hay rutas de modificación. El alcance por recurso (Policy o Gate) no aplica: el admin tiene alcance global sobre el registro. |
| P4 Entrada validada y SQL con bindings | ✅ | Filtros de la consulta validados: `actor` y `recurso_id` solo con formato de identificador, `accion` y `recurso_tipo` con `^[a-z_]{1,60}$`, fechas solo `AAAA-MM-DD`; el texto libre se rechaza y no llega al registro (TM12). `X-Request-Id` validado como UUID; el `id` de ruta solo se guarda si tiene formato de identificador. Los triggers se crean con SQL estático en la migración, sin entrada del usuario. |
| P5 Nada sensible en el repo | ✅ | Sin secretos; los tests generan eventos sintéticos. |
| P6 Sin vulnerabilidades críticas/altas en producción | ✅ | No añade dependencias. RS16 se atiende en su propia spec antes de cualquier release. |
| P7 Auditoría de datos sensibles | ✅ | Es la infraestructura que P7 exige: almacén solo-anexado, retención de 5 años, lectura solo admin auditada. |
| P8 Correlación y logs limpios | ✅ | `X-Request-Id` en cada respuesta y en el `Context` de logs y eventos; fuera de HTTP, un `request_id` generado por ejecución. Los únicos logs nuevos (`auditoria.fallo`, `auditoria.purga`) no llevan datos personales, y un fallo de escritura de auditoría nunca deja SQL, bindings ni valores del evento en el log (TM13). Residuo aceptado: si falla la consulta del registro o el borrado de la purga, el reporte por defecto deja el SQL con sus bindings (identificadores y fechas) en el log; se corrige en la spec 023 (OB4.a), que debe liberarse antes de producción (decisión del usuario, 2026-09-28). |
| P9 La interfaz usa solo el sistema de diseño | ➖ pendiente | Principio añadido en la constitución 1.1.0 (2026-10-02), después de aprobar este plan; se evalúa al reconstruir la 020 sobre `main` (pantalla de consulta de auditoría). |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `database/migrations/2026_09_28_000000_create_auditoria_table.php` (nuevo) | Tabla `auditoria` + triggers `auditoria_sin_update` y `auditoria_sin_delete`; `down()` los elimina (solo para desarrollo) | Medio: permisos para crear triggers en otros entornos |
| `app/Auditoria/Auditoria.php` (nuevo) | `registrar(accion, resultado, recursoTipo, recursoId, campos, detalle, critico, identificadorIntentado)` y `denegar(...)` (registra la denegación y lanza `AccesoDenegadoAuditado`); valida formatos y vocabulario (ver Contratos); actor y rol de `Auth::user()` en el momento de la llamada; genera `request_id` si el `Context` no lo tiene; captura los fallos del almacén sin encadenarlos ni registrar su mensaje | Medio: es el contrato que usan 014, 021 y 022 |
| `app/Auditoria/AlmacenAuditoria.php` (interfaz, nuevo) y `AlmacenAuditoriaBaseDatos.php` (nuevo) | Escritura, consulta paginada con filtros y `purgar(corte): int` (activa `@auditoria_purga`, borra lo anterior al corte, devuelve el recuento y desactiva el indicador en `finally`); es el único responsable del borrado y del indicador. La interfaz permite sustituir el almacén en tests (simular fallos) | Bajo |
| `app/Auditoria/AuditoriaNoDisponible.php` (nuevo) | Excepción del modo crítico, sin excepción previa | Bajo |
| `app/Auditoria/AccesoDenegadoAuditado.php` (nuevo) | `HttpException` (403 o 404) que lanza `denegar()`; el registro central de 403 la omite porque ya está registrada | Bajo |
| `app/Providers/AppServiceProvider.php` | Registrar `AlmacenAuditoria` → `AlmacenAuditoriaBaseDatos` y `Auditoria` como singleton; escuchar los eventos `Login` y `Logout` de Laravel para poner o quitar `actor_id` en el `Context` | Bajo |
| `app/Http/Middleware/IdentificadorPeticion.php` (nuevo) | Acepta `X-Request-Id` si es UUID de 36 caracteres, si no genera uno; `Context::add('request_id')`; cabecera en la respuesta | Bajo |
| `app/Http/Middleware/ContextoActor.php` (nuevo) | Al final del grupo `web`: `Context::add('actor_id')` si hay sesión y `Context::forget('actor_id')` si no la hay, para que una petición anónima nunca herede el actor de otra | Bajo |
| `bootstrap/app.php` | `prepend` global de `IdentificadorPeticion`; `appendToGroup('web', ContextoActor)`; en `withExceptions`: (a) render de `AuditoriaNoDisponible` → vista 503 genérica aunque `APP_DEBUG=true`, y `dontReport` de esa excepción; (b) en el callback `respond` (se ejecuta con la respuesta ya renderizada; `report` no sirve porque Laravel no reporta `HttpException` ni `AuthorizationException`), registrar `acceso_denegado` para toda respuesta 403 de un usuario autenticado cuya excepción no sea `AccesoDenegadoAuditado` | Medio: toca el manejo global de errores |
| `app/Http/Controllers/AuditoriaController.php` (nuevo) | `index`: valida filtros (solo formatos de identificador y fechas), registra `auditoria_consultada` en modo crítico con los filtros validados y pagina 50 eventos del más reciente al más antiguo (con nombre del actor) | Bajo |
| `routes/web.php` | `GET /admin/auditoria` → `admin.auditoria.index` dentro del grupo admin | Bajo |
| `routes/console.php` | Comando `auditoria:purgar`: calcula el corte (ahora en UTC − 5 años) y, dentro de `DB::transaction`, llama a `purgar()` del almacén y registra `auditoria_purgada` en modo crítico; si el registro falla, la transacción revierte el borrado. Y `Schedule::command('auditoria:purgar')->monthly()` | Bajo |
| `resources/views/auditoria/index.blade.php` (nuevo) | Formulario de filtros, tabla y paginación | Bajo |
| `resources/views/errors/auditoria-no-disponible.blade.php` (nuevo) | Página 503 genérica, sin detalles internos, con el `request_id` para soporte | Bajo |
| `resources/views/layouts/app.blade.php` | Entrada "Auditoría" en la sección "Análisis" del menú de admin | Bajo |
| `docker-compose.yml` (**ruta protegida**) | Servicio `scheduler` (misma imagen que `app`) con `php artisan schedule:work` (decisión del usuario, 2026-09-28) | Bajo; requiere aprobación explícita en la tarea |
| `database/testing/bootstrap-test-db.sh` | Tras cargar el volcado, `php artisan migrate --force` sobre `dentissa_test` (crea `auditoria` y las tablas de julio). Asumido desde el plan de la 014 (D3) | Bajo |
| `docs/deployment.md` | Servicio `scheduler` y requisito de permisos para crear triggers | Bajo |
| `tests/Feature/Auditoria/*Test.php` (nuevos) | Tests de la Trazabilidad, incluido `AuditoriaRendimientoTest` (grupo `rnf`); `tests/Support/AlmacenAuditoriaQueFalla.php` (nuevo, decora un `AlmacenAuditoria` real: delega consulta y purga y solo al escribir lanza una `QueryException` real con SQL y bindings de prueba) y `tests/Support/CapturaLogs.php` (nuevo, `TestHandler` de Monolog) | — |
| `phpunit.xml` | Excluir el grupo `rnf` de la suite por defecto (`php artisan test --group=rnf` lo ejecuta) | Bajo |

## Contratos y datos
**Tabla `auditoria`** (migración nueva; expand, sin contract):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint autoincrement | orden de inserción |
| `ocurrido_en` | datetime(6) | índice |
| `actor_id` | char(36) null | usuario autenticado; sin FK (el evento sobrevive al usuario) |
| `actor_rol` | varchar(20) null | rol en el momento del evento |
| `identificador_intentado` | varchar(255) null | solo sin sesión (lo usará la 021) |
| `accion` | varchar(60) | índice; `^[a-z_]{1,60}$` |
| `recurso_tipo` | varchar(60) null | índice compuesto con `recurso_id`; `^[a-z_]{1,60}$` |
| `recurso_id` | varchar(64) null | solo formato de identificador `^[A-Za-z0-9_-]{1,64}$` |
| `resultado` | varchar(30) | vocabulario cerrado: `exito`, `denegado` (403), `denegado_404` (denegación mostrada como 404), `error`; `registrar` rechaza cualquier otro valor |
| `campos` | json null | lista de nombres de campo cambiados, nunca valores |
| `detalle` | json null | mapa de enteros, booleanos o cadenas con formato de identificador o fecha (`^[A-Za-z0-9_.:-]{1,64}$`): filtros validados, recuento de purga, acción solicitada; nunca texto libre |
| `ip` | varchar(45) null | |
| `request_id` | char(36) NOT NULL | índice; fuera de HTTP (consola, colas), uno generado por ejecución |

Triggers (SQL estático):
```sql
CREATE TRIGGER auditoria_sin_update BEFORE UPDATE ON auditoria FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: solo anexado';
CREATE TRIGGER auditoria_sin_delete BEFORE DELETE ON auditoria FOR EACH ROW
  IF COALESCE(@auditoria_purga, 0) <> 1 OR OLD.ocurrido_en >= UTC_TIMESTAMP() - INTERVAL 5 YEAR THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: borrado no permitido';
  END IF;
```

**API de registro** (contrato para 014, 021 y 022):
```php
// App\Auditoria\Auditoria
public function registrar(
    string $accion,              // ^[a-z_]{1,60}$
    string $resultado,           // exito | denegado | denegado_404 | error
    ?string $recursoTipo = null, // ^[a-z_]{1,60}$
    ?string $recursoId = null,   // ^[A-Za-z0-9_-]{1,64}$
    array $campos = [],          // list<string> de nombres de campo (^[a-z_]+$)
    array $detalle = [],         // array<string, int|bool|string>; cadenas ^[A-Za-z0-9_.:-]{1,64}$
    bool $critico = false,       // true → lanza AuditoriaNoDisponible si no puede escribir
    ?string $identificadorIntentado = null,
): void;
/** Registra `acceso_denegado` (modo no crítico) y lanza AccesoDenegadoAuditado (403, o 404 con denegado_404). */
public function denegar(string $accionSolicitada, ?string $recursoTipo, ?string $recursoId, string $resultado = 'denegado'): never;
```
- **Cómo se obtiene:** por inyección del contenedor (constructor o método) o `app(Auditoria::class)`;
  no hay fachada. En otros planes, `Auditoria::registrar(...)` denota una llamada al servicio
  inyectado.
- **Actor, rol, IP y `request_id`:** el actor y su rol salen de `Auth::user()` en el momento de la
  llamada, no del `Context`: un evento registrado tras autenticarse en la misma petición lleva el
  nuevo actor. El `actor_id` de los logs sigue al mismo usuario: `ContextoActor` lo fija (o lo
  quita) al empezar cada petición, y los eventos `Login` y `Logout` lo actualizan dentro de ella.
  `ocurrido_en` lo escribe la app en UTC (`app.timezone`). La IP sale de la petición (null fuera de HTTP). El `request_id` sale del `Context`;
  si no hay (consola, colas), `Auditoria` genera un UUID, lo añade al `Context` y lo reutiliza
  durante esa ejecución.
- **Valores inválidos** (formato o vocabulario) → `InvalidArgumentException`: es un error de
  programación, no una entrada del usuario.
- **Fallos del almacén:** `Auditoria` captura cualquier excepción del almacén y nunca la encadena
  ni registra su mensaje (una `QueryException` lleva SQL y bindings). Escribe `auditoria.fallo`
  (nivel `error`) con exactamente las claves `accion`, `critico` y `error` (clase corta de la
  excepción y SQLSTATE si lo hay); `request_id` y `actor_id` llegan por el `Context`. En modo no
  crítico la acción continúa; en modo crítico lanza `AuditoriaNoDisponible` sin excepción previa.
- **Garantía del modo crítico:** en una lectura, el evento se registra antes de mostrar el recurso.
  En una escritura, el llamador hace el cambio y registra el evento dentro de la misma transacción;
  si el registro falla, `AuditoriaNoDisponible` revierte el cambio. La purga de esta spec y la 022
  siguen esta regla.

**Forma del evento de denegación** (la usan esta spec y la 014):

| Campo | Valor |
|---|---|
| `accion` | `acceso_denegado` |
| `resultado` | `denegado` (403) o `denegado_404` (solo desde `denegar()`) |
| `detalle.accion_solicitada` | la que pase `denegar()` (p. ej. la habilidad del Gate); en el 403 central, el nombre de la ruta (`admin.auditoria.index`) o `ruta_sin_nombre` |
| `recurso_tipo` | el que pase `denegar()`; en el 403 central, el segundo segmento del nombre de la ruta (`<rol>.<recurso>[.<acción>]` → `auditoria`), o null |
| `recurso_id` | el que pase `denegar()`; en el 403 central, el parámetro de ruta `id` solo si tiene formato de identificador; si no, null (nunca texto de la URL tal cual) |

Sin duplicados y sin pérdidas: solo el componente que rechaza la petición registra con
`denegar()`, y el registro central omite exactamente la excepción que esta lanza. Una comprobación
que no rechaza (`@can`, `Gate::allows`) no registra nada, y no hay ninguna marca que valga para toda
la petición: cualquier otro 403 posterior se registra.

**Rutas:**
| Ruta | Rol | Respuesta |
|---|---|---|
| `GET /admin/auditoria?actor=&accion=&recurso_tipo=&recurso_id=&desde=&hasta=&page=` | admin | 200 con 50 eventos; `actor` y `recurso_id` con formato de identificador, `accion` y `recurso_tipo` con `^[a-z_]{1,60}$`, `desde` y `hasta` solo `AAAA-MM-DD` con `hasta >= desde` (el rango incluye el día `hasta` completo); filtros inválidos (incluidos texto libre y fechas con hora o zona) → vuelve con errores de validación, sin 500 y sin registrarse; 503 genérico si no puede auditar la consulta |
| `GET /admin/auditoria` | dentista, asistente, cliente | 403; evento `acceso_denegado` con `detalle.accion_solicitada` = `admin.auditoria.index` y `recurso_tipo` = `auditoria` |
| Todas las respuestas | — | cabecera `X-Request-Id` |

**Qué ve cada rol**

| Pantalla | Admin | Dentista | Asistente | Cliente | Test |
|---|---|---|---|---|---|
| Menú: entrada "Auditoría" | sí, en "Análisis" | no | no | no | `ConsultaAuditoriaTest::test_el_menu_de_auditoria_solo_para_admin` |
| `/admin/auditoria` | filtros, tabla (fecha, actor, rol, acción, recurso, resultado, IP, request_id) y paginación; estado vacío "No hay eventos con estos filtros" | 403 | 403 | 403 | `test_un_no_admin_no_consulta_el_registro` |
| Error de auditoría no disponible | página 503 genérica con el `request_id` | ídem | ídem | ídem | `test_la_consulta_falla_cerrada_con_error_generico` |

Revisar con la skill `design` antes de `/implement` (pantalla de auditoría y página 503).

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, `dentissa_test` con migraciones):**
  `RegistroAuditoriaTest`, `InmutabilidadAuditoriaTest`, `IdentificadorPeticionTest`,
  `DenegacionesAuditadasTest`, `ConsultaAuditoriaTest`.
- **Fallos simulados:** `AlmacenAuditoriaQueFalla` sustituye al almacén en el contenedor para
  probar el fallo cerrado y el no crítico. Decora un almacén real que recibe por constructor:
  delega la consulta y la purga, y solo al escribir lanza una `QueryException` real con SQL y
  bindings de prueba (IP e identificador ficticios), para comprobar que no llegan al log. Así, en
  la purga fallida el `DELETE` real se ejecuta y solo falla el registro.
- **Denegaciones sin depender de la 014:** los tests registran en `setUp` rutas de prueba (grupo
  `web` + `auth`) y un Gate de prueba (`Gate::define`) para cubrir `AuthorizationException`,
  `abort(403)`, `denegar()` y la no duplicación.
- **Logs:** un canal de prueba con `TestHandler` de Monolog para comprobar que los registros
  técnicos llevan `request_id` y `actor_id` en sus metadatos de contexto.
- **Triggers:** se prueban contra MariaDB real (la base de pruebas) y con la conexión de la propia
  aplicación, no con dobles: modificación, borrado de un evento y borrado sin condición.
- **Rendimiento (RNF):** `AuditoriaRendimientoTest`, en el grupo `rnf`, excluido de la suite por
  defecto y ejecutado con `php artisan test --group=rnf` contra `dentissa_test`. Los 100.000
  eventos sintéticos se insertan dentro de la transacción del test (`DatabaseTransactions`), que se
  revierte al final: no quedan en ninguna base. Medición: p95 de 200 llamadas a `registrar`
  < 50 ms, y p95 de 20 consultas a `/admin/auditoria` con filtros distintos < 2 s. Las mediciones
  se anotan como nota de su tarea.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: modificar o borrar eventos, uno o muchos, desde la app o con sus credenciales | A09:2025 | tabla `auditoria` | sin rutas de escritura; triggers que rechazan `UPDATE`/`DELETE` fila a fila. Vaciar la tabla, eliminar los triggers o cambiar su estructura (`ALTER`, `RENAME`, `DROP`) con privilegios totales queda fuera (limitación aceptada en la spec) | `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_un_borrado_masivo_se_rechaza` |
| TM2 | Tampering: abusar de la purga para borrar eventos recientes | A09:2025 | trigger `auditoria_sin_delete` | solo borra con el indicador de purga **y** antigüedad > 5 años, medida con `UTC_TIMESTAMP()` | `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `test_la_purga_respeta_el_corte_al_segundo` |
| TM3 | Injection: cabecera `X-Request-Id` manipulada (logs, cabeceras) | A05:2025 | `IdentificadorPeticion` | solo UUID de 36 caracteres; si no, uno generado | `IdentificadorPeticionTest::test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye`, `test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria` |
| TM4 | Information disclosure: personal no admin lee el registro | A01:2025 | `/admin/auditoria` | `role:admin` | `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` |
| TM5 | Information disclosure: datos personales o de salud dentro de un evento | A09:2025 | `Auditoria::registrar` | `campos` solo nombres; `detalle` solo enteros, booleanos o cadenas con formato de identificador o fecha; revisión de cada llamada | `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares`, `test_el_detalle_rechaza_texto_libre` |
| TM6 | Repudiation: acceder a datos clínicos sin dejar rastro provocando fallos del registro | A09:2025 | modo crítico | `AuditoriaNoDisponible` detiene la acción; en escrituras, revierte el cambio | `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_critico_una_escritura_fallida_no_deja_cambios` |
| TM7 | Information disclosure: la página de error revela detalles internos | A10:2025 | `withExceptions` | vista 503 genérica aunque `APP_DEBUG=true` | `ConsultaAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico` |
| TM8 | DoS: filtros de consulta costosos o malformados | A06:2025 | `AuditoriaController@index` | validación (fechas solo `AAAA-MM-DD`), índices, paginación fija de 50 | `test_filtros_invalidos_se_rechazan`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar` |
| TM9 | Repudiation: purga sin rastro | A09:2025 | `auditoria:purgar` | evento `auditoria_purgada` con recuento y fecha de corte, en la misma transacción que el borrado y en modo crítico | `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada`, `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` (con eventos de más de 5 años y el doble envolviendo al almacén real: el `DELETE` real se ejecuta, el comando termina con error y el recuento de eventos antiguos no cambia; debe fallar contra una purga que borre y registre en modo no crítico) |
| TM10 | Spoofing: un evento atribuido a otro actor | A09:2025 | `Auditoria` | actor tomado de `Auth::user()` en el momento del registro, nunca de la petición | `RegistroAuditoriaTest::test_el_actor_es_el_usuario_autenticado`, `test_un_evento_tras_autenticarse_en_la_misma_peticion_lleva_ese_actor` |
| TM11 | Spoofing / Repudiation: reenviar un `X-Request-Id` válido (copiado de otra respuesta) para confundir la trazabilidad o heredar el actor o el acceso de otra petición | A09:2025 | `IdentificadorPeticion`, `Auditoria` | el `request_id` solo correlaciona: el actor sale de `Auth::user()`, el acceso de `auth` y `role`, y cada evento tiene su propio `id`, fecha e IP | `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor`, `test_un_identificador_de_admin_reutilizado_no_da_acceso` |
| TM12 | Information disclosure: texto libre controlado por el usuario (filtros, segmentos de URL) guardado en el registro | A09:2025 | `AuditoriaController`, registro central de 403, `Auditoria` | solo valores con formato de identificador o fecha llegan a `recurso_id` y `detalle`; los filtros inválidos se rechazan | `ConsultaAuditoriaTest::test_un_filtro_con_texto_libre_se_rechaza_y_no_se_registra`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`, `DenegacionesAuditadasTest::test_un_id_de_ruta_con_formato_invalido_no_se_guarda`, `RegistroAuditoriaTest::test_el_detalle_rechaza_texto_libre` |
| TM13 | Information disclosure: SQL, bindings o valores del evento (IP, identificador intentado) en el log cuando falla una escritura de auditoría | A09:2025 | `Auditoria` | la excepción del almacén nunca se encadena ni se registra; `auditoria.fallo` con claves fijas | `RegistroAuditoriaTest::test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`, `test_el_log_de_fallo_solo_lleva_las_claves_previstas` |

Casos de abuso de la spec → amenazas: CA2 → TM1, TM2; CA7 → TM4; CA9 → TM3; CA11 → TM6, TM7, TM13; CA13 → TM11.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: evento con actor, acción, recurso, resultado, fecha, IP y request_id | migración, `Auditoria`, `AlmacenAuditoriaBaseDatos` | Feature `RegistroAuditoriaTest::test_un_evento_guarda_todos_los_campos`, `test_sin_sesion_se_guarda_el_identificador_intentado`, `test_un_resultado_fuera_del_vocabulario_se_rechaza`, `test_fuera_de_http_se_genera_un_request_id` |
| CA2 (abuso): ningún evento se modifica ni se borra, uno o muchos, tampoco con las credenciales de la app, salvo la purga. Fuera del criterio (Supuestos de la spec): vaciar la tabla, eliminar los triggers o cambiar su estructura con privilegios totales, sin test | triggers, ausencia de rutas | Feature `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_un_borrado_masivo_se_rechaza` (con la conexión de la app; ningún evento cambia), `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `ConsultaAuditoriaTest::test_no_existen_rutas_de_modificacion` |
| CA3: sin datos personales ni de salud, salvo IP e identificador intentado; campos sin valores | validación de `campos`, `detalle` y `recurso_id` | Feature `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares`, `test_el_detalle_rechaza_texto_libre`, `DenegacionesAuditadasTest::test_un_id_de_ruta_con_formato_invalido_no_se_guarda` |
| CA4: denegaciones de usuarios autenticados registradas con la acción y el recurso solicitados | `withExceptions` (`respond`, 403), `denegar()`, `AccesoDenegadoAuditado` | Feature `DenegacionesAuditadasTest::test_entrar_al_area_de_otro_rol_queda_registrado` (aserta `accion`, `detalle.accion_solicitada`, `recurso_tipo`, `resultado` exactos), `test_un_abort_403_de_controlador_queda_registrado` (aserta `recurso_id` de la ruta), `test_una_denegacion_por_gate_queda_registrada`, `test_una_denegacion_con_denegar_se_registra_una_sola_vez`, `test_una_comprobacion_denegada_que_no_rechaza_no_impide_registrar_el_403_posterior`, `test_un_visitante_sin_sesion_no_genera_evento` |
| CA5: la purga elimina solo lo anterior a 5 años | `auditoria:purgar`, trigger | Feature `InmutabilidadAuditoriaTest::test_la_purga_elimina_solo_eventos_de_mas_de_5_anios`, `test_la_purga_respeta_el_corte_al_segundo` (con el trigger activo, se purga un evento en corte − 1 s y se conserva uno en corte + 1 s), `test_la_purga_esta_programada_mensualmente` |
| CA6: consulta con filtros, paginada y ordenada | `AuditoriaController`, vista | Feature `ConsultaAuditoriaTest::test_el_admin_filtra_por_actor_accion_recurso_y_fechas`, `test_el_filtro_por_actor_solo_admite_identificadores`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`, `test_el_rango_incluye_el_dia_hasta`, `test_los_resultados_se_paginan_del_mas_reciente_al_mas_antiguo`, `test_el_menu_de_auditoria_solo_para_admin` |
| CA7 (abuso): no admin no consulta | `role:admin` | Feature `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` (dentista, asistente, cliente; aserta también su evento `acceso_denegado`) |
| CA8: request_id reutilizado si es válido o generado, y devuelto | `IdentificadorPeticion` | Feature `IdentificadorPeticionTest::test_se_reutiliza_un_identificador_valido`, `test_se_genera_uno_si_no_llega`, `test_las_respuestas_de_error_tambien_lo_llevan` |
| CA9 (abuso): identificador malformado o largo descartado y nunca reflejado ni registrado | `IdentificadorPeticion` | Feature `test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye`, `test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria` |
| CA10: logs y eventos de una petición comparten request_id y actor | `Context` en `IdentificadorPeticion` y `ContextoActor`; `Login`/`Logout` en `AppServiceProvider` | Feature `IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor`, `test_tras_autenticarse_o_cerrar_sesion_logs_y_eventos_llevan_el_mismo_actor`, `test_una_peticion_anonima_no_hereda_el_actor_de_la_anterior`, `RegistroAuditoriaTest::test_un_evento_tras_autenticarse_en_la_misma_peticion_lleva_ese_actor` |
| CA11: fallo cerrado en acciones clínicas y en la consulta | modo crítico (transacción en escrituras), render 503, fallos saneados | Feature `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_critico_una_escritura_fallida_no_deja_cambios`, `test_en_modo_no_critico_la_accion_continua_y_se_registra_el_fallo`, `test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`, `ConsultaAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico` |
| CA12: consulta y purga quedan registradas | `AuditoriaController`, `auditoria:purgar` | Feature `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros`, `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada` (ejecuta el comando real y aserta un `request_id` válido), `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` (el `DELETE` real se ejecuta y solo falla el registro) |
| CA13 (abuso): un identificador válido repetido se acepta sin transferir actor ni acceso; cada evento conserva identidad, actor, fecha e IP | `IdentificadorPeticion`, `Auditoria` (actor de `Auth::user()`) | Feature `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor` (dos peticiones de actores distintos con el mismo identificador → dos eventos, cada uno con su actor), `test_un_identificador_de_admin_reutilizado_no_da_acceso` (sin sesión → sin actor ni acceso) |
| TM1 | triggers | `test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_un_borrado_masivo_se_rechaza` |
| TM2 | trigger de borrado con `UTC_TIMESTAMP()` | `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `test_la_purga_respeta_el_corte_al_segundo` |
| TM3 | validación de cabecera | `test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye`, `test_un_identificador_malformado_no_aparece_en_logs_ni_en_auditoria` |
| TM4 | `role:admin` | `test_un_no_admin_no_consulta_el_registro` |
| TM5 | validación de `campos`/`detalle` | `test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares`, `test_el_detalle_rechaza_texto_libre` |
| TM6 | modo crítico | `test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_critico_una_escritura_fallida_no_deja_cambios` |
| TM7 | vista 503 genérica | `test_la_consulta_falla_cerrada_con_error_generico` |
| TM8 | validación de filtros | `test_filtros_invalidos_se_rechazan`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar` |
| TM9 | evento de purga en la transacción del borrado (el borrado lo hace el almacén; la transacción, el comando) | `test_la_purga_queda_registrada`, `test_si_no_se_puede_registrar_la_purga_no_se_borra_nada` |
| TM10 | actor de `Auth::user()` al registrar | `test_el_actor_es_el_usuario_autenticado`, `test_un_evento_tras_autenticarse_en_la_misma_peticion_lleva_ese_actor` |
| TM11 | `request_id` solo para correlación | `test_un_identificador_repetido_no_transfiere_el_actor`, `test_un_identificador_de_admin_reutilizado_no_da_acceso` |
| TM12 | solo valores con formato de identificador o fecha | `test_un_filtro_con_texto_libre_se_rechaza_y_no_se_registra`, `test_una_fecha_con_hora_o_zona_se_rechaza_sin_registrar`, `test_un_id_de_ruta_con_formato_invalido_no_se_guarda`, `test_el_detalle_rechaza_texto_libre` |
| TM13 | fallos del almacén saneados | `test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`, `test_el_log_de_fallo_solo_lleva_las_claves_previstas` |

## Observabilidad
- Logs nuevos (sin datos personales; `request_id` y `actor_id` llegan por el `Context`):
  - `auditoria.fallo` → `error` → exactamente `accion`, `critico` y `error` (clase corta y SQLSTATE), cuando no se puede escribir un evento → `RegistroAuditoriaTest::test_el_log_de_fallo_solo_lleva_las_claves_previstas`.
  - `auditoria.purga` → `info` → `eventos_purgados`, `fecha_corte`.
- Eventos de auditoría:
  - `acceso_denegado` (forma en Contratos: `detalle.accion_solicitada`, recurso, `resultado` `denegado` o `denegado_404`) → `withExceptions` (`respond`, 403) y `denegar()` → `DenegacionesAuditadasTest::test_entrar_al_area_de_otro_rol_queda_registrado` (CA4).
  - `auditoria_consultada` (detalle: filtros usados) → `AuditoriaController@index` → `test_la_consulta_queda_registrada_con_sus_filtros` (CA12).
  - `auditoria_purgada` (detalle: recuento y fecha de corte; actor nulo = sistema; `request_id` generado por ejecución) → `auditoria:purgar` → `test_la_purga_queda_registrada` (CA12).
- Métricas y alertas: ninguna nueva. Un aumento de `auditoria.fallo` es la señal a vigilar.
- Cómo se verifica tras el deploy: enviar una petición con `X-Request-Id` válido, comprobar la
  cabecera en la respuesta; entrar como dentista a `/admin` y buscar en `/admin/auditoria` el
  `acceso_denegado` con ese `request_id` y `detalle.accion_solicitada` = `admin.dashboard`; comprobar que `docker compose ps` muestra `scheduler`.

## Rollout
- Feature flag: no aplica. El registro de auditoría es un control de seguridad que no debe poder
  apagarse (mismo criterio que la decisión D5 de la 014, aceptada por el usuario el 2026-09-28).
- Orden de despliegue: (1) migración (tabla y triggers); (2) código (middlewares, servicio,
  pantalla); (3) servicio `scheduler`. Antes de desplegar la 014.
- Compatibilidad: la migración solo añade (expand); la versión anterior ignora la tabla. Las
  respuestas ganan una cabecera; ningún cliente depende de su ausencia.
- Rollback: redeploy del tag anterior **sin** revertir la migración; los eventos ya escritos son
  evidencia y la tabla no molesta a la versión anterior. `down()` solo se usa en desarrollo.
- Métricas a vigilar tras el deploy: aparición de `auditoria.fallo` en el log, número de
  `acceso_denegado` por día (un pico tras el deploy de la 014 indicaría un alcance mal calculado)
  y crecimiento de la tabla.

## Decisiones (→ ADR si son arquitectónicas)
- D1 · Almacén: tabla `auditoria` en la misma MariaDB, con triggers de inmutabilidad y excepción
  de purga solo para eventos de más de 5 años (decisión del usuario, 2026-09-28). → [ADR 0004](../../adr/0004-registro-auditoria-mariadb.md) (accepted). Cubre OB1.a y OB1.g.
- D2 · Purga: comando `auditoria:purgar` programado cada mes; en Docker, nuevo servicio `scheduler`
  con `schedule:work` (decisión del usuario, 2026-09-28). Cubre OB1.g.
- D3 · API única `Auditoria::registrar` con modo crítico (fallo cerrado) y no crítico. Se obtiene
  por inyección, sin fachada; el actor y el rol salen de `Auth::user()` al registrar; fuera de
  HTTP se genera un `request_id` por ejecución; los fallos del almacén nunca se encadenan ni se
  registran con su mensaje; en escrituras críticas el evento va en la misma transacción que el
  cambio. La 014 deberá usar modo crítico en sus lecturas (CA12 de la 014).
- D4 · Denegaciones: quien rechaza una petición con una comprobación propia (Gates de la 014)
  usa `denegar()`, que registra y lanza `AccesoDenegadoAuditado`; el callback `respond` de
  `withExceptions` registra cualquier otra respuesta 403 de usuario autenticado y omite solo esa
  excepción. Se descartan la marca en el `Context` (valía para toda la petición y hacía perder 403
  reales tras un `@can` denegado) y `report` (Laravel no reporta los 403). Forma del evento en
  Contratos. Cubre OB1.c.
- D5 · Correlación con el `Context` de Laravel, que añade `request_id` y `actor_id` a los
  metadatos de cada log. `ContextoActor` fija o quita el actor al empezar cada petición y los
  eventos `Login` y `Logout` lo actualizan dentro de ella, de modo que logs y eventos llevan el
  mismo actor también tras un cambio de sesión. Cubre OB2.a y OB2.b.
- D6 · `id` autoincremental en lugar de UUID (excepción a la convención del proyecto): la tabla es
  un registro ordenado de solo inserción.
- D7 · La consulta audita sus filtros en `detalle` (CA12). Solo llegan filtros validados: actor y
  recurso por identificador, acción y tipo con `^[a-z_]{1,60}$`, fechas solo `AAAA-MM-DD` con el
  día `hasta` incluido; el filtro por actor es solo por identificador (decisión del usuario,
  2026-09-28).
- D8 · Base de pruebas con migraciones: el cambio de `bootstrap-test-db.sh` pasa del plan de la
  014 a este (decisión del usuario, 2026-09-28); la 014 ya lo refleja en su D3.
- D9 · Sin feature flag (ver Rollout).
- D11 · Nombres `auditoria` (tabla) y `AuditoriaController` en singular, como excepción a la
  convención de `AGENTS.md` (tablas en plural, `<Plural>Controller`): "auditoría" nombra el
  registro en su conjunto, no una colección de entidades, y así lo usan ya el ADR 0004,
  `docs/observability.md` y el plan de la 014.
- D12 · Vocabulario cerrado de `resultado` (`exito`, `denegado`, `denegado_404`, `error`),
  validado por `registrar`; `denegado_403` no existe: un 403 es `denegado`.
- D13 · Purga transaccional: borrado y evento `auditoria_purgada` (modo crítico) en la misma
  transacción; si el evento no se escribe, no se borra nada. El almacén (`purgar()`) es el único
  responsable del borrado y del indicador; el comando abre la transacción y registra el evento.
  El corte se mide en UTC, igual que `ocurrido_en` y el trigger (`UTC_TIMESTAMP()`).
- D14 · Requisitos no funcionales medidos con un test del grupo `rnf`, fuera de la suite por
  defecto, con datos sintéticos dentro de una transacción que se revierte.
- D10 · Cobertura de riesgos: OB1.a, OB1.c, OB1.g, OB1.h, OB2.a y OB2.b quedan dentro; OB1.b,
  OB1.d, OB1.e y OB1.f siguen pendientes (specs 021, 022 y 014). Esta spec atiende OB1
  parcialmente y OB2 por completo; los estados se actualizan por corrección tras `/release`.

## Impacto en arquitectura
- `docs/architecture.md → Módulos`: añadir "Auditoría" (`app/Auditoria/`, middlewares de correlación y contexto).
- `docs/architecture.md → Datos`: tabla `auditoria` y sus triggers (primera tabla de dominio creada por migración).
- `docs/architecture.md → Backend → Observabilidad` y `docs/observability.md → Resumen`: correlación y registro de auditoría pasan a "en uso"; estado de OB1.a, OB1.c, OB1.g, OB1.h, OB2.a y OB2.b.
- `docs/deployment.md`: servicio `scheduler` y permisos para crear triggers.

## Riesgos y mitigaciones
- Crear triggers puede requerir privilegios especiales en la BD de staging/prod (binlog y
  `log_bin_trust_function_creators`) → anotarlo en `docs/deployment.md` y verificarlo al elegir
  plataforma (objetivo 5).
- Con privilegios totales sobre la BD, incluidas las credenciales de la app, se puede vaciar la
  tabla (`TRUNCATE`), eliminar los triggers o cambiar su estructura (`ALTER`, `RENAME`, `DROP`) →
  limitación aceptada en la spec (Supuestos, decisión del usuario, 2026-09-28) y en el ADR 0004;
  queda fuera de CA2 y no tiene test. Modificar o borrar
  filas, una o muchas, sí está cubierto por CA2 y sus tests.
- Registrar dos veces una denegación, o perder un 403 real → solo registra quien rechaza, con
  `denegar()`, y el registro central omite exactamente esa excepción; tests
  `test_una_denegacion_con_denegar_se_registra_una_sola_vez` y
  `test_una_comprobacion_denegada_que_no_rechaza_no_impide_registrar_el_403_posterior`.
- El modo crítico convierte un fallo de BD en un 503 al abrir un expediente → comportamiento
  exigido por la spec; página genérica con `request_id` para soporte.
- Crecimiento de la tabla en 5 años → índices por fecha, acción, recurso y `request_id`; volumen
  académico pequeño.
- Relojes de la app y de la BD desincronizados → un evento dentro del desfase haría abortar el
  `DELETE` entero y la purga fallaría siempre; todo se mide en UTC y el requisito de reloj
  sincronizado (NTP) se declara en `docs/deployment.md` (T091); test
  `test_la_purga_respeta_el_corte_al_segundo`.
- Si falla la consulta del registro o el borrado de la purga, el reporte por defecto deja el SQL
  con sus bindings (identificadores y fechas) en el log → residuo aceptado hasta la spec 023
  (OB4.a), que debe liberarse antes de producción (decisión del usuario, 2026-09-28).
- El plan de la 014 usa `Gate::after` para todas las comprobaciones, `denegado_403`, la marca en el
  `Context` y llamadas estáticas → debe adoptar `denegar()` solo en la denegación que rechaza la
  petición, el vocabulario de D12 y la inyección de D3: `/plan 014 --fix` tras aprobar este plan.
