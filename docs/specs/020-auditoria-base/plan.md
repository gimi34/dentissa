---
spec: 020-auditoria-base
status: approved   # draft | approved | blocked
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
devuelve en la respuesta. Las denegaciones 403 se registran de forma central en `withExceptions`.
El registro tiene un modo crítico con fallo cerrado para acciones sobre datos clínicos y para la
consulta del registro, que el admin hace en una pantalla nueva `/admin/auditoria`.

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 020 `approved` el 2026-09-28. |
| P2 Test que falla antes y pasa después | ✅ | Todo CA y TM tiene test en Trazabilidad; ninguna de las capacidades existe hoy. |
| P3 Autorización en servidor, por rol y recurso | ✅ | `/admin/auditoria` bajo `auth` + `role:admin`; test del caso permitido (admin) y denegado (dentista, asistente, cliente). No hay rutas de modificación. |
| P4 Entrada validada y SQL con bindings | ✅ | Filtros de la consulta validados (UUID, longitudes, fechas); `X-Request-Id` validado como UUID; los triggers se crean con SQL estático en la migración, sin entrada del usuario. |
| P5 Nada sensible en el repo | ✅ | Sin secretos; los tests generan eventos sintéticos. |
| P6 Sin vulnerabilidades críticas/altas en producción | ✅ | No añade dependencias. RS16 se atiende en su propia spec antes de cualquier release. |
| P7 Auditoría de datos sensibles | ✅ | Es la infraestructura que P7 exige: almacén solo-anexado, retención de 5 años, lectura solo admin auditada. |
| P8 Correlación y logs limpios | ✅ | `X-Request-Id` en cada respuesta y en el `Context` de logs y eventos; los únicos logs nuevos (`auditoria.fallo`, `auditoria.purga`) no llevan datos personales. |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `database/migrations/2026_09_28_000000_create_auditoria_table.php` (nuevo) | Tabla `auditoria` + triggers `auditoria_sin_update` y `auditoria_sin_delete`; `down()` los elimina (solo para desarrollo) | Medio: permisos para crear triggers en otros entornos |
| `app/Auditoria/Auditoria.php` (nuevo) | `registrar(accion, resultado, recursoTipo, recursoId, campos, detalle, critico, identificadorIntentado)`; `denegacion(...)` marca en el `Context` que la petición ya registró su denegación; valida `campos` (lista de nombres) y `detalle` (mapa de escalares) | Medio: es el contrato que usan 014, 021 y 022 |
| `app/Auditoria/AlmacenAuditoria.php` (interfaz, nuevo) y `AlmacenAuditoriaBaseDatos.php` (nuevo) | Escritura y consulta paginada con filtros; la interfaz permite sustituir el almacén en tests (simular fallos) | Bajo |
| `app/Auditoria/AuditoriaNoDisponible.php` (nuevo) | Excepción del modo crítico | Bajo |
| `app/Providers/AppServiceProvider.php` | Registrar `AlmacenAuditoria` → `AlmacenAuditoriaBaseDatos` y `Auditoria` como singleton | Bajo |
| `app/Http/Middleware/IdentificadorPeticion.php` (nuevo) | Acepta `X-Request-Id` si es UUID de 36 caracteres, si no genera uno; `Context::add('request_id')`; cabecera en la respuesta | Bajo |
| `app/Http/Middleware/ContextoActor.php` (nuevo) | Al final del grupo `web`: `Context::add('actor_id')` si hay sesión | Bajo |
| `bootstrap/app.php` | `prepend` global de `IdentificadorPeticion`; `appendToGroup('web', ContextoActor)`; en `withExceptions`: (a) render de `AuditoriaNoDisponible` → vista 503 genérica aunque `APP_DEBUG=true`, (b) registrar las respuestas 403 (`HttpException` 403 y `AuthorizationException`) de usuarios autenticados si la petición aún no registró su denegación | Medio: toca el manejo global de errores |
| `app/Http/Controllers/AuditoriaController.php` (nuevo) | `index`: valida filtros, registra `auditoria_consultada` en modo crítico con los filtros usados y pagina 50 eventos del más reciente al más antiguo (con nombre del actor) | Bajo |
| `routes/web.php` | `GET /admin/auditoria` → `admin.auditoria.index` dentro del grupo admin | Bajo |
| `routes/console.php` | Comando `auditoria:purgar` (activa `@auditoria_purga`, borra lo anterior a 5 años, lo desactiva en `finally`, registra `auditoria_purgada`) y `Schedule::command('auditoria:purgar')->monthly()` | Bajo |
| `resources/views/auditoria/index.blade.php` (nuevo) | Formulario de filtros, tabla y paginación | Bajo |
| `resources/views/errors/auditoria-no-disponible.blade.php` (nuevo) | Página 503 genérica, sin detalles internos, con el `request_id` para soporte | Bajo |
| `resources/views/layouts/app.blade.php` | Entrada "Auditoría" en la sección "Análisis" del menú de admin | Bajo |
| `docker-compose.yml` (**ruta protegida**) | Servicio `scheduler` (misma imagen que `app`) con `php artisan schedule:work` (decisión del usuario, 2026-09-28) | Bajo; requiere aprobación explícita en la tarea |
| `database/testing/bootstrap-test-db.sh` | Tras cargar el volcado, `php artisan migrate --force` sobre `dentissa_test` (crea `auditoria` y las tablas de julio). Asumido desde el plan de la 014 (D3) | Bajo |
| `docs/deployment.md` | Servicio `scheduler` y requisito de permisos para crear triggers | Bajo |
| `tests/Feature/Auditoria/*Test.php` (nuevos) | Tests de la Trazabilidad; `tests/Support/AlmacenAuditoriaQueFalla.php` (nuevo) | — |

## Contratos y datos
**Tabla `auditoria`** (migración nueva; expand, sin contract):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint autoincrement | orden de inserción |
| `ocurrido_en` | datetime(6) | índice |
| `actor_id` | char(36) null | usuario autenticado; sin FK (el evento sobrevive al usuario) |
| `actor_rol` | varchar(20) null | rol en el momento del evento |
| `identificador_intentado` | varchar(255) null | solo sin sesión (lo usará la 021) |
| `accion` | varchar(60) | índice; `snake_case` |
| `recurso_tipo` | varchar(60) null | índice compuesto con `recurso_id` |
| `recurso_id` | varchar(64) null | |
| `resultado` | varchar(30) | `exito`, `denegado`, `denegado_404`, `error` |
| `campos` | json null | lista de nombres de campo cambiados, nunca valores |
| `detalle` | json null | mapa de escalares no sensibles (filtros, recuento de purga) |
| `ip` | varchar(45) null | |
| `request_id` | char(36) | índice |

Triggers (SQL estático):
```sql
CREATE TRIGGER auditoria_sin_update BEFORE UPDATE ON auditoria FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: solo anexado';
CREATE TRIGGER auditoria_sin_delete BEFORE DELETE ON auditoria FOR EACH ROW
  IF COALESCE(@auditoria_purga, 0) <> 1 OR OLD.ocurrido_en >= NOW() - INTERVAL 5 YEAR THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria: borrado no permitido';
  END IF;
```

**API de registro** (contrato para 014, 021 y 022):
```php
// App\Auditoria\Auditoria
public function registrar(
    string $accion, string $resultado,
    ?string $recursoTipo = null, ?string $recursoId = null,
    array $campos = [],          // list<string> de nombres de campo (^[a-z_]+$)
    array $detalle = [],         // array<string, scalar> no sensible
    bool $critico = false,       // true → lanza AuditoriaNoDisponible si no puede escribir
    ?string $identificadorIntentado = null,
): void;
public function denegacion(string $accion, ?string $recursoTipo, ?string $recursoId, string $resultado = 'denegado'): void;
```
El actor, su rol, la IP y el `request_id` los toma del contexto. En modo no crítico, un fallo de
escritura se registra como `auditoria.fallo` (nivel `error`, solo `accion` y `request_id`) y la
acción continúa.

**Rutas:**
| Ruta | Rol | Respuesta |
|---|---|---|
| `GET /admin/auditoria?actor=&accion=&recurso_tipo=&recurso_id=&desde=&hasta=&page=` | admin | 200 con 50 eventos; filtros inválidos → vuelve con errores de validación; 503 genérico si no puede auditar la consulta |
| `GET /admin/auditoria` | dentista, asistente, cliente | 403 (registrado como `acceso_denegado`) |
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
  probar el fallo cerrado y el no crítico.
- **Logs:** un canal de prueba con `TestHandler` de Monolog para comprobar que los registros
  técnicos llevan `request_id` y `actor_id` en sus metadatos de contexto.
- **Triggers:** se prueban contra MariaDB real (la base de pruebas), no con dobles.
- **Rendimiento (RNF):** verificación manual en local (registro < 50 ms; consulta con 100.000
  eventos sintéticos < 2 s) anotada en `/review`.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: modificar o borrar eventos desde la app o la BD | A09:2025 | tabla `auditoria` | sin rutas de escritura; triggers que rechazan `UPDATE`/`DELETE` | `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar` |
| TM2 | Tampering: abusar de la purga para borrar eventos recientes | A09:2025 | trigger `auditoria_sin_delete` | solo borra con el indicador de purga **y** antigüedad > 5 años | `test_la_purga_no_borra_eventos_recientes_aunque_se_active` |
| TM3 | Injection: cabecera `X-Request-Id` manipulada (logs, cabeceras) | A05:2025 | `IdentificadorPeticion` | solo UUID de 36 caracteres; si no, uno generado | `IdentificadorPeticionTest::test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye` |
| TM4 | Information disclosure: personal no admin lee el registro | A01:2025 | `/admin/auditoria` | `role:admin` | `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` |
| TM5 | Information disclosure: datos personales o de salud dentro de un evento | A09:2025 | `Auditoria::registrar` | `campos` solo nombres; `detalle` solo escalares; revisión de cada llamada | `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares` |
| TM6 | Repudiation: acceder a datos clínicos sin dejar rastro provocando fallos del registro | A09:2025 | modo crítico | `AuditoriaNoDisponible` detiene la acción | `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion` |
| TM7 | Information disclosure: la página de error revela detalles internos | A10:2025 | `withExceptions` | vista 503 genérica aunque `APP_DEBUG=true` | `ConsultaAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico` |
| TM8 | DoS: filtros de consulta costosos o malformados | A06:2025 | `AuditoriaController@index` | validación, índices, paginación fija de 50 | `test_filtros_invalidos_se_rechazan` |
| TM9 | Repudiation: purga sin rastro | A09:2025 | `auditoria:purgar` | evento `auditoria_purgada` con recuento y fecha de corte | `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada` |
| TM10 | Spoofing: un evento atribuido a otro actor | A09:2025 | `Auditoria` | actor tomado de la sesión, nunca de la petición | `RegistroAuditoriaTest::test_el_actor_es_el_usuario_autenticado` |

Casos de abuso de la spec → amenazas: CA2 → TM1, TM2; CA7 → TM4; CA9 → TM3; CA11 → TM6, TM7.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: evento con actor, acción, recurso, resultado, fecha, IP y request_id | migración, `Auditoria`, `AlmacenAuditoriaBaseDatos` | Feature `RegistroAuditoriaTest::test_un_evento_guarda_todos_los_campos`, `test_sin_sesion_se_guarda_el_identificador_intentado` |
| CA2 (abuso): nadie modifica ni borra eventos, salvo la purga | triggers, ausencia de rutas | Feature `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar`, `test_la_purga_no_borra_eventos_recientes_aunque_se_active`, `ConsultaAuditoriaTest::test_no_existen_rutas_de_modificacion` |
| CA3: sin datos personales ni de salud; campos sin valores | validación de `campos`/`detalle` | Feature `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares` |
| CA4: denegaciones de usuarios autenticados registradas | `withExceptions` (403), `Auditoria::denegacion` | Feature `DenegacionesAuditadasTest::test_entrar_al_area_de_otro_rol_queda_registrado`, `test_un_abort_403_de_controlador_queda_registrado`, `test_una_denegacion_no_se_registra_dos_veces`, `test_un_visitante_sin_sesion_no_genera_evento` |
| CA5: la purga elimina solo lo anterior a 5 años | `auditoria:purgar`, trigger | Feature `InmutabilidadAuditoriaTest::test_la_purga_elimina_solo_eventos_de_mas_de_5_anios`, `test_la_purga_esta_programada_mensualmente` |
| CA6: consulta con filtros, paginada y ordenada | `AuditoriaController`, vista | Feature `ConsultaAuditoriaTest::test_el_admin_filtra_por_actor_accion_recurso_y_fechas`, `test_los_resultados_se_paginan_del_mas_reciente_al_mas_antiguo`, `test_el_menu_de_auditoria_solo_para_admin` |
| CA7 (abuso): no admin no consulta | `role:admin` | Feature `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` (dentista, asistente, cliente) |
| CA8: request_id reutilizado si es válido o generado, y devuelto | `IdentificadorPeticion` | Feature `IdentificadorPeticionTest::test_se_reutiliza_un_identificador_valido`, `test_se_genera_uno_si_no_llega`, `test_las_respuestas_de_error_tambien_lo_llevan` |
| CA9 (abuso): identificador malformado o largo descartado y nunca reflejado | `IdentificadorPeticion` | Feature `test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye` |
| CA10: logs y eventos de una petición comparten request_id y actor | `Context` en `IdentificadorPeticion` y `ContextoActor` | Feature `IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor` |
| CA11: fallo cerrado en acciones clínicas y en la consulta | modo crítico, render 503 | Feature `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion`, `test_en_modo_no_critico_la_accion_continua_y_se_registra_el_fallo`, `ConsultaAuditoriaTest::test_la_consulta_falla_cerrada_con_error_generico` |
| CA12: consulta y purga quedan registradas | `AuditoriaController`, `auditoria:purgar` | Feature `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros`, `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada` |
| TM1 | triggers | `test_un_evento_no_se_puede_modificar`, `test_un_evento_no_se_puede_borrar` |
| TM2 | trigger de borrado | `test_la_purga_no_borra_eventos_recientes_aunque_se_active` |
| TM3 | validación de cabecera | `test_un_identificador_malformado_se_sustituye`, `test_un_identificador_demasiado_largo_se_sustituye` |
| TM4 | `role:admin` | `test_un_no_admin_no_consulta_el_registro` |
| TM5 | validación de `campos`/`detalle` | `test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_valores_no_escalares` |
| TM6 | modo crítico | `test_en_modo_critico_un_fallo_detiene_la_accion` |
| TM7 | vista 503 genérica | `test_la_consulta_falla_cerrada_con_error_generico` |
| TM8 | validación de filtros | `test_filtros_invalidos_se_rechazan` |
| TM9 | evento de purga | `test_la_purga_queda_registrada` |
| TM10 | actor desde la sesión | `test_el_actor_es_el_usuario_autenticado` |

## Observabilidad
- Logs nuevos (sin datos personales; `request_id` y `actor_id` llegan por el `Context`):
  - `auditoria.fallo` → `error` → `accion`, `critico` (cuando no se puede escribir un evento).
  - `auditoria.purga` → `info` → `eventos_purgados`, `fecha_corte`.
- Eventos de auditoría:
  - `acceso_denegado` → `withExceptions` (403) → `DenegacionesAuditadasTest::test_entrar_al_area_de_otro_rol_queda_registrado` (CA4).
  - `auditoria_consultada` (detalle: filtros usados) → `AuditoriaController@index` → `test_la_consulta_queda_registrada_con_sus_filtros` (CA12).
  - `auditoria_purgada` (detalle: recuento y fecha de corte; actor nulo = sistema) → `auditoria:purgar` → `test_la_purga_queda_registrada` (CA12).
- Métricas y alertas: ninguna nueva. Un aumento de `auditoria.fallo` es la señal a vigilar.
- Cómo se verifica tras el deploy: enviar una petición con `X-Request-Id` válido, comprobar la
  cabecera en la respuesta; entrar como dentista a `/admin` y buscar en `/admin/auditoria` el
  `acceso_denegado` con ese `request_id`; comprobar que `docker compose ps` muestra `scheduler`.

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
  de purga solo para eventos de más de 5 años (decisión del usuario, 2026-09-28). → [ADR 0004](../../adr/0004-registro-auditoria-mariadb.md) (proposed). Cubre OB1.a y OB1.g.
- D2 · Purga: comando `auditoria:purgar` programado cada mes; en Docker, nuevo servicio `scheduler`
  con `schedule:work` (decisión del usuario, 2026-09-28). Cubre OB1.g.
- D3 · API única `Auditoria::registrar` con modo crítico (fallo cerrado) y no crítico; el actor
  sale del contexto. La 014 deberá usar modo crítico en sus lecturas (CA12 de la 014).
- D4 · Denegaciones centralizadas en `withExceptions` para todo 403 de usuario autenticado, con
  marca en el `Context` para no duplicar las que ya registre un Gate (014). Cubre OB1.c.
- D5 · Correlación con el `Context` de Laravel, que añade `request_id` y `actor_id` a los
  metadatos de cada log. Cubre OB2.a y OB2.b.
- D6 · `id` autoincremental en lugar de UUID (excepción a la convención del proyecto): la tabla es
  un registro ordenado de solo inserción.
- D7 · La consulta audita sus filtros (identificadores y fechas) en `detalle` (CA12).
- D8 · Base de pruebas con migraciones: el cambio de `bootstrap-test-db.sh` pasa del plan de la
  014 a este; la 014 se corregirá con `/plan 014 --fix` tras aprobar este plan (decisión del
  usuario, 2026-09-28).
- D9 · Sin feature flag (ver Rollout).
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
- Un usuario con privilegios de BD puede ejecutar `TRUNCATE` o eliminar los triggers → limitación
  aceptada al elegir triggers frente a un usuario de BD con permisos mínimos; se documenta en el ADR.
- Registrar dos veces una misma denegación (Gate de la 014 y `withExceptions`) → marca en el
  `Context` y test `test_una_denegacion_no_se_registra_dos_veces`.
- El modo crítico convierte un fallo de BD en un 503 al abrir un expediente → comportamiento
  exigido por la spec; página genérica con `request_id` para soporte.
- Crecimiento de la tabla en 5 años → índices por fecha, acción, recurso y `request_id`; volumen
  académico pequeño.
- El plan de la 014 queda desalineado hasta aplicar `/plan 014 --fix` (D8).
