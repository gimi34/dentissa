# Resumen de construcción y pruebas — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: build-and-test (Construction) · **Alcance**: bugfix · **Estrategia de pruebas**: Minimal
**Fecha**: 2026-09-19 (reentrada tras el bucle de corrección 1)

Entradas consumidas: `code-generation-plan.md` (el contrato de pruebas aprobado
y su sección de revisión), `unit-test-instructions.md` (los comandos exactos y
la lista de verificación manual) y `code-summary.md` (el inventario de lo
generado y qué requisito cubre cada pieza).

---

## Estado general de la construcción

**Lista para construir.** Sin pasos bloqueados.

| Prerrequisito | Estado |
|---|---|
| Docker Desktop en marcha, cuatro servicios en `running` | Cumplido |
| `composer install` / `npm install` resueltos | Cumplido, sin dependencias nuevas |
| Base de pruebas `dentissa_test` cargada | Cumplido (`bash database/testing/bootstrap-test-db.sh`, salida 0) |
| `public/storage` como unión de Windows | Cumplido |

La construcción de front-end (`npm run build`) corre en el host; todo lo demás
—`artisan`, `composer`, `phpunit`— dentro del contenedor, porque el PHP del host
es 8.2 y `vendor/` está instalado para 8.4.

## Inventario de juegos de pruebas generados

La estrategia es **Minimal**, así que el cuerpo de la etapa no pide juegos de
integración, rendimiento ni seguridad. Lo que sí se generó:

| Juego | Archivo | Estado |
|---|---|---|
| Instrucciones de construcción | `build-instructions.md` | Generado en esta etapa |
| Pruebas unitarias / de regresión | `../code-generation/unit-test-instructions.md` | Generado en code-generation |
| Integración | — | No aplica con estrategia Minimal |
| Rendimiento | — | No aplica: no hay NFR de rendimiento en `requirements.md` |
| Seguridad | — | No aplica como juego separado: NFR1 (elevación de privilegios) tiene regresión automática propia dentro de las pruebas de dominio |

## Cobertura

Sin unidades de trabajo: el alcance `bugfix` saltó `units-generation`, así que
la cobertura es de ámbito de etapa, no por unidad.

| Ámbito | Pruebas | Aserciones |
|---|---|---|
| Rutas de administración (TD-11, TD-12) | 4 | 8 |
| Precio y color de servicios (TD-8) | 4 | 21 |
| Usuarios de administración (TD-16) | 5 | 33 |
| Vistas de administración (NFR5) | 7 | 43 |
| Ejemplos de Laravel preexistentes | 2 | 3 |
| **Suite completa** | **22** | **108** |

Sin umbral de cobertura de línea: el alcance `bugfix` no impone piso de
porcentaje. El piso que sí impone —regresión dirigida al fallo— está cubierto
para los cuatro defectos.

## Target Verification Matrix

| Target ID | Source | Expected | Actual | Evidence | Owning Stage | Verdict |
|---|---|---|---|---|---|---|
| TC-1a | `code-generation-plan.md` § Testing Contract → `obligations.scope_floor[0]` | Regresión dirigida para TD-11 y TD-12 | 4 pruebas, 8 aserciones, verde; 3 fallos contra el código con el defecto | `tests/Feature/AdminUsuariosRutasTest.php` | build-and-test | **Met** |
| TC-1b | `code-generation-plan.md` § Testing Contract → `obligations.scope_floor[0]` | Regresión dirigida para TD-8 y TD-16 | 9 pruebas de dominio contra MariaDB real, verde; 4 y 5 fallos contra los controladores con el defecto | `tests/Feature/ServiciosPrecioTest.php`, `tests/Feature/UsuariosAdminTest.php` | build-and-test | **Met** |
| TC-2 | `code-generation-plan.md` § Testing Contract → `obligations.scope_floor[1]` | La suite existente sigue en verde | `OK (22 tests, 108 assertions)` | Salida de `vendor/bin/phpunit` | build-and-test | **Met** |
| TC-3 | `code-generation-plan.md` § Testing Contract → `obligations.strategy_volume` | Una prueba por requisito al nivel más estrecho, con piso de camino feliz | 20 pruebas propias; 31 requisitos con destino verificado en `traceability.json` | `traceability.json`; `cross-unit-traceability.md` | build-and-test | **Met** |
| TC-4 | `requirements.md` FR5.1 | Lista de comprobación manual ejecutable | 19 puntos entregados, los 19 con contrapartida automática | `unit-test-instructions.md` § «Verificación manual» | build-and-test (entrega) | **Met** |
| TC-5 | `requirements.md` FR5.2 | La suite PHPUnit existente sigue en verde | 22 de 22 | Ver TC-2 | build-and-test | **Met** |
| TC-6a | `requirements.md` NFR1 | Sin elevación de privilegios | Regresión automática verde y no complaciente | `tests/Feature/UsuariosAdminTest.php` | build-and-test | **Met** |
| TC-6b | `requirements.md` NFR2 | Sin pérdida de datos clínicos | Paciente, cita y expediente sobreviven a la baja lógica | `tests/Feature/UsuariosAdminTest.php` | build-and-test | **Met** |
| TC-6c | `requirements.md` NFR3 | Sin errores 500 | Validación devuelve redirección con errores; UUID inexistente devuelve 404 | `UsuariosAdminTest`, `ServiciosPrecioTest`, `AdminVistasSmokeTest` | build-and-test | **Met** |
| TC-6d | `requirements.md` NFR4 | Consistencia con los patrones existentes | Verificado línea por línea contra `PersonalController` y `PacientesController` | `code-generation-plan.md` § `## Review` | code-generation | **Met** |
| TC-6e | `requirements.md` NFR5 | Sin regresión funcional | 7 pruebas de humo HTTP sobre las vistas, verde; 3 fallan contra las vistas con el defecto | `tests/Feature/AdminVistasSmokeTest.php` | build-and-test | **Met** |

**Sin `Pending`. Sin `N/A`. Sin `Unverified`. Sin objetivos diferidos.**

## Evaluación de preparación

| Dimensión | Veredicto | Sustento |
|---|---|---|
| **Listo para construir** | Sí | `npm run build` salida 0; `php -l` limpio en los 7 archivos PHP tocados |
| **Listo para probar** | Sí | `bootstrap-test-db.sh` salida 0; suite completa `OK (22 tests, 108 assertions)` |
| **Listo para desplegar** | Sí, con una preparación obligatoria | Hay que ejecutar `php artisan view:clear` y `route:clear` antes de servir: las vistas compiladas todavía referencian `admin.usuarios.destroy`, ruta que esta corrección elimina. Está documentado en `build-instructions.md` y es el punto 0 de la lista manual |

## Limitaciones conocidas y puntos abiertos

1. **La capa de navegador no está cubierta, y no puede estarlo por HTTP.** Las
   pruebas de humo verifican que las pantallas responden, que los filtros
   filtran y que los datos sobreviven; no verifican apariencia ni interacción
   (que el modal se abra, que el desplegable se despliegue). El recorrido manual
   de 19 puntos sigue siendo lo que cubre eso y conviene hacerlo una vez.
2. **Deriva de estilo preexistente.** `laravel/pint` falla en los archivos
   tocados y también en cinco de seis ajenos a esta corrección: nunca se ha
   ejecutado sobre este repositorio. No se ejecutó ni se desactivó ninguna
   regla; reformatear excede el alcance de un `bugfix`. Queda como candidato
   para una intención propia.
3. **`database/dentissa.sql` es ahora un artefacto del que dependen las
   pruebas.** Si alguien cambia el esquema sin regenerar el volcado, las pruebas
   de dominio se desincronizan. Es el riesgo de mantenimiento que el bucle de
   corrección 1 aceptó de forma explícita.
4. **Sin informe de cobertura de línea.** `phpunit.xml` no tiene configurado un
   controlador de cobertura y el alcance no impone piso de porcentaje.
