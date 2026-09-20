# Resultados de construcción y pruebas — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: build-and-test (Construction) · **Alcance**: bugfix · **Estrategia de pruebas**: Minimal
**Fecha de ejecución**: 2026-09-19 (reentrada tras el bucle de corrección 1)
**Entorno**: contenedor `dentissa_app`, PHP 8.4.23, PHPUnit 12.5.30; base de
pruebas `dentissa_test` en el contenedor `dentissa_db` (MariaDB 10.11)

> **Qué cambió respecto de la ejecución del 2026-09-08.** Aquella dejó cuatro
> objetivos fuera de `Met` y terminó en el peldaño 4, parar y preguntar. El
> humano eligió «Retry with fix» con el arreglo **A + C**, que se aplicó en
> `code-generation` (base MariaDB de pruebas, nueve regresiones de dominio
> nuevas, y `Tests\Feature\ExampleTest` reescrito para afirmar la redirección
> real). Esta ejecución verifica el resultado de ese bucle y cierra además
> NFR5 con un arreglo dentro de la etapa. Ver `## Loop-Back Log` al final, que
> es de solo anexo y se conserva íntegro.

---

## Estado de la construcción

| Comando | Dónde | Resultado |
|---|---|---|
| `npm run build` | host | **Éxito**, salida 0. 54 módulos transformados, construido en 4,17 s. Artefactos: `public/build/manifest.json` (0,33 kB), `assets/app-DiX2MDnn.css` (55,98 kB), `assets/app-CiZEzA8M.js` (36,20 kB) |
| `php -l` sobre los 7 archivos PHP tocados | host | **Éxito**. `No syntax errors detected` en `routes/web.php`, `UsuarioController.php`, `ServiciosController.php` y los cuatro archivos de prueba |
| `bash database/testing/bootstrap-test-db.sh` | host → contenedor | **Éxito**, salida 0. `dentissa_test` recreada y cargada desde `database/dentissa.sql` |

## Resultados de las pruebas

| Ejecución | Comando | Total | Pasadas | Fallidas | Omitidas |
|---|---|---|---|---|---|
| Acotada a esta corrección | `vendor/bin/phpunit tests/Feature/AdminUsuariosRutasTest.php tests/Feature/ServiciosPrecioTest.php tests/Feature/UsuariosAdminTest.php` | 13 | 13 | 0 | 0 |
| Comprobación de vistas añadida en esta etapa | `vendor/bin/phpunit tests/Feature/AdminVistasSmokeTest.php` | 7 | 7 | 0 | 0 |
| **Suite completa** | `vendor/bin/phpunit` | **22** | **22** | **0** | **0** |

Aserciones: 62 en la acotada, 43 en la de vistas, **108 en la suite completa**.
Salida literal de la suite completa: `OK (22 tests, 108 assertions)`.

**Cero fallos.** El fallo preexistente de `Tests\Feature\ExampleTest` que
bloqueaba TC-2 y TC-5 desapareció al reescribir esa prueba, en el bucle de
corrección, para que afirme lo que la aplicación realmente hace —un visitante
anónimo es redirigido a `/login`— en vez del 200 que esta aplicación nunca
devolvió. No es una rebaja de umbral: la aserción nueva describe el
comportamiento correcto y verificado del sistema.

### Arreglo dentro de la etapa — la comprobación de NFR5

Al entrar en esta ejecución, NFR5 («sin regresión funcional») seguía siendo el
único objetivo sin verificar: esta corrección tocó cuatro plantillas Blade y la
única verificación de esa capa era la lista manual de 19 puntos, que nadie había
ejecutado. Una lista sin ejecutar no es evidencia.

Se aplicó el **peldaño 1, arreglo dentro de la etapa**, en su forma explícita de
«comprobación ejecutable de un objetivo»: `tests/Feature/AdminVistasSmokeTest.php`,
siete pruebas de humo por HTTP sobre las vistas de administración. **No toca
código de producción**; solo añade la medición que faltaba. Cubre los puntos 1,
2, 5, 6, 8, 11, 13, 16, 17, 18 y 19 de la lista manual:

| Prueba | Puntos que cierra | Qué fija |
|---|---|---|
| Las vistas de usuarios se renderizan | 1, 2, 6, 8, 11 | Listado, alta, ficha y edición responden 200; el desplegable ofrece los cuatro roles; para un cliente sigue oculto con su rol en campo oculto; el modal ya no promete un borrado irreversible |
| El listado sigue filtrando | 18 | El filtro por texto y por tipo muestra al buscado y oculta al que no encaja |
| Un filtro sin resultados | borde | La pantalla vacía responde 200, no un error |
| Un usuario inexistente | error, NFR3 | Un UUID bien formado sin fila detrás responde 404, no 500 |
| Las vistas de servicios guardan lo que se les manda | 13, 16, 19 | Nombre, categoría, duración, descripción y estado activo (casilla marcada y desmarcada) sobreviven a una edición; y un precio y un color **cambiados** sí se escriben |
| El precio se propaga a las vistas SQL | 17 | `v_citas_hoy` y `v_historial_paciente` muestran 18.000,00 tras editar solo la duración |
| Un usuario creado con contraseña puede iniciar sesión | 5 | La contraseña escrita por el administrador es la que queda guardada |

Con esto, **los 19 puntos de la lista manual tienen contrapartida automática**;
el punto 0 no es una comprobación sino preparación operativa (`view:clear`).
Quedaron dos intentos de arreglo dentro de la etapa disponibles y **se usó uno**.

### Comprobación de que las pruebas no son complacientes

Las siete se verificaron contra el código con el defecto, igual que se hizo con
las trece anteriores. Revirtiendo los archivos a su estado en `HEAD` y volviendo
a ejecutar:

| Qué se revirtió | Código corregido | Código con el defecto |
|---|---|---|
| `resources/views/usuarios/{create,index}.blade.php` | `OK (5 tests, 33 assertions)` | `Tests: 5, Assertions: 15, Failures: 3` |
| `app/Http/Controllers/{Servicios,Usuario}Controller.php` | las 2 de dominio en verde | `Tests: 2, Assertions: 5, Failures: 2` |

La segunda fila es la evidencia más directa del enunciado de la intención: contra
el controlador con el defecto, `v_citas_hoy` devuelve `0.00` donde el servicio
vale 18.000,00 — la propagación silenciosa de TD-8 a las vistas SQL, reproducida
y fijada.

Los archivos revertidos se restauraron íntegros y la suite completa se volvió a
ejecutar en verde después.

### Cobertura

Sin informe de cobertura de línea: el alcance `bugfix` no impone piso de
porcentaje y `phpunit.xml` no tiene configurado un controlador de cobertura. El
piso que sí impone el alcance —regresión dirigida al fallo— está cubierto para
los cuatro defectos y se documenta en la matriz de abajo.

## Comprobación de formato

`laravel/pint` está en `require-dev` sin archivo de configuración, de modo que
usa el preajuste `laravel` por defecto. **La deriva de estilo es preexistente y
generalizada**: cinco de seis archivos ajenos a esta corrección también fallan,
con las mismas familias de reglas (`class_attributes_separation`,
`single_space_around_construct`, `no_trailing_whitespace`, `line_ending`). Pint
nunca se ha ejecutado sobre este repositorio: no hay `pint.json` ni una línea
base formateada.

Se registra como deriva preexistente y **no** se ejecuta pint sobre el árbol:
reformatear convertiría el diff de esta corrección en uno de decenas de archivos
y excede el alcance de un `bugfix`. No se ha desactivado ninguna regla ni se ha
añadido configuración para silenciar las incidencias.

## Matriz de verificación de objetivos

| Target ID | Origen | Esperado | Real | Evidencia | Etapa responsable | Veredicto |
|---|---|---|---|---|---|---|
| TC-1a | `code-generation-plan.md` § Testing Contract → `obligations.scope_floor[0]` | Regresión dirigida al fallo, para TD-11 y TD-12 | 4 pruebas de tabla de rutas, 8 aserciones, en verde. Fallan (3 fallos) contra el código con el defecto | `tests/Feature/AdminUsuariosRutasTest.php` | build-and-test | **Met** |
| TC-1b | `code-generation-plan.md` § Testing Contract → `obligations.scope_floor[0]` | Regresión dirigida al fallo, para TD-8 y TD-16 | 9 pruebas de dominio contra MariaDB real, en verde. Fallan (4 y 5 fallos) contra los controladores con el defecto | `tests/Feature/ServiciosPrecioTest.php`, `tests/Feature/UsuariosAdminTest.php`; `database/testing/bootstrap-test-db.sh` | build-and-test | **Met** |
| TC-2 | `code-generation-plan.md` § Testing Contract → `obligations.scope_floor[1]` | La suite existente sigue en verde | `OK (22 tests, 108 assertions)`. Cero fallos | Salida de `vendor/bin/phpunit` | build-and-test | **Met** |
| TC-3 | `code-generation-plan.md` § Testing Contract → `obligations.strategy_volume` | Una prueba verificable por requisito al nivel más estrecho, con piso de camino feliz por componente | 20 pruebas propias de esta corrección sobre 31 requisitos; las restantes son requisitos de comportamiento cubiertos por composición y registrados en `traceability.json` | `traceability.json`; `cross-unit-traceability.md` | build-and-test | **Met** |
| TC-4 | `requirements.md` FR5.1 | Lista de comprobación manual ejecutable contra el entorno Docker | Entregada: 19 puntos, **los 19 con contrapartida automática** tras esta etapa (el punto 0 es preparación operativa, no una comprobación) | `unit-test-instructions.md` § «Verificación manual»; tabla de cobertura de arriba | build-and-test (entrega); el humano la ejecuta | **Met** |
| TC-5 | `requirements.md` FR5.2 | La suite PHPUnit existente sigue en verde | Idéntico a TC-2: 22 de 22 | Ver TC-2 | build-and-test | **Met** |
| TC-6a | `requirements.md` NFR1 | Sin elevación de privilegios | `test_el_rol_asistente_sobrevive_a_una_edicion_parcial`, en verde y verificada como no complaciente | `tests/Feature/UsuariosAdminTest.php` | build-and-test | **Met** |
| TC-6b | `requirements.md` NFR2 | Sin pérdida de datos clínicos | `test_la_baja_logica_conserva_el_expediente`: paciente, cita y expediente siguen existiendo tras la baja | `tests/Feature/UsuariosAdminTest.php` | build-and-test | **Met** |
| TC-6c | `requirements.md` NFR3 | Sin errores 500 | Las rutas de validación devuelven redirección con errores de sesión; un UUID inexistente responde 404 | `UsuariosAdminTest`, `ServiciosPrecioTest`, `AdminVistasSmokeTest` | build-and-test | **Met** |
| TC-6d | `requirements.md` NFR4 | Consistencia con los patrones existentes | Verificado línea por línea por la revisión de arquitectura contra `PersonalController` y `PacientesController` | Sección `## Review` de `code-generation-plan.md` | code-generation | **Met** |
| TC-6e | `requirements.md` NFR5 | Sin regresión funcional | 7 pruebas de humo por HTTP sobre las vistas de administración, en verde; 3 de ellas fallan contra las vistas con el defecto | `tests/Feature/AdminVistasSmokeTest.php` | build-and-test | **Met** |

**Sin objetivos `Pending`.** Ningún objetivo aplicable usa `N/A`. Ningún objetivo
queda `Unverified` ni diferido.

## Veredicto de la etapa

**Superada.** Todos los comandos ejecutados terminaron en verde y los once
objetivos aplicables están en `Met`.

No se ha rebajado, relajado ni desactivado ningún umbral, regla de linter u
objetivo de calidad para llegar aquí. Los dos veredictos que en la ejecución
anterior estaban en `Not Met` se cerraron corrigiendo una prueba que afirmaba
algo falso sobre la aplicación, no ajustándola a lo que devolvía; los dos que
estaban en `Unverified` se cerraron construyendo la medición que faltaba.

**Lo que sigue siendo responsabilidad del humano.** La lista manual de 19 puntos
sigue entregada y conviene recorrerla una vez contra el entorno Docker. Las
pruebas de humo comprueban que las pantallas responden y que los datos
sobreviven; lo que no comprueban —y ninguna prueba HTTP puede— es la apariencia
y el comportamiento de navegador: que el modal se abra, que el desplegable se
vea, que el filtro reaccione al teclear. Para eso hace falta el recorrido manual.

En particular el punto 0 —`php artisan view:clear` y `route:clear`— es
preparación obligatoria antes de abrir la aplicación, porque las vistas
compiladas todavía referencian la ruta `admin.usuarios.destroy` que esta
corrección elimina.

---

## Loop-Back Log

<!-- Seccion de solo anexo. Nunca se reescribe ni se borra: sobrevive al salto
     hacia atras y se lee en la puerta final. Elegir siempre Modify, nunca Redo,
     al reentrar en esta etapa. -->

### Loop-back 1 — 2026-09-14T01:29:55Z

**Diagnostico**: Cuatro objetivos aplicables fuera de `Met`, por dos causas
distintas y ninguna de ellas un defecto del codigo entregado.

- TC-1b y TC-6 quedan `Unverified` porque la infraestructura de pruebas del
  proyecto no permite observar el comportamiento del dominio: `phpunit.xml`
  (lineas 26-27) apunta a SQLite en memoria y `database/migrations/` no contiene
  las tablas del dominio. El precio que sobrevive a una edicion (TD-8), el rol
  `asistente` que se conserva y la baja logica sin cascada (TD-16) solo son
  observables contra filas reales de `usuarios`, `servicios`, `pacientes` y
  `citas`.
- TC-2 y TC-5 quedan `Not Met` por `Tests\Feature\ExampleTest`, andamiaje que
  trae Laravel y que este proyecto nunca adapto: afirma que `GET /` devuelve 200
  cuando la raiz siempre redirige por diseno. Verificado como preexistente con
  `git status`, `git show HEAD:routes/web.php` y `git diff -U0 routes/web.php`.

**Etapa de la causa raiz**: `code-generation`. El arreglo consiste en codigo de
pruebas nuevo y en la configuracion de pruebas que lo sostiene, ambos dentro del
remit de generacion de codigo. Reabre ademas la decision humana Q4 = D y la
restriccion C2 («esta correccion no anade pruebas automaticas»), que el humano ha
reabierto de forma explicita al elegir `Retry with fix` con el arreglo A + C.

**Arreglo planificado** (eleccion humana: «A + C»):

1. **A — Base MariaDB de pruebas.** Anadir una base `dentissa_test` (servicio
   nuevo en `docker-compose.yml` o base adicional en el contenedor existente),
   cargada desde `database/dentissa.sql`; apuntar `phpunit.xml` a ella con
   limpieza entre pruebas por transaccion o recarga; escribir las pruebas de
   TD-8 (el precio sobrevive a `ServiciosController::update()`) y TD-16
   (validacion de `store()`/`update()`, `email` unico, `rol` no asignable
   libremente, baja logica sin borrado fisico en cascada). Cierra TC-1b y TC-6.
2. **C — Corregir o retirar `Tests\Feature\ExampleTest`.** Reescribirlo para que
   afirme el comportamiento real y verificado del sistema —un visitante anonimo
   es redirigido a `/login`— o retirarlo por ser andamiaje nunca adaptado. No es
   una rebaja de umbral: la asercion nueva describe lo que la aplicacion hace,
   no una expectativa relajada. Cierra TC-2 y TC-5.

Ningun umbral, regla de linter ni objetivo de calidad se desactiva o se rebaja.

**Impacto estimado**:

- **Esfuerzo**: medio. Media jornada a una para A (el volcado ya existe y es
  cargable; el tiempo se va en la limpieza entre pruebas y en escribir 6-8
  pruebas nuevas) mas minutos para C.
- **Coste economico**: nulo en licencias. Un contenedor MariaDB adicional en
  local y en CI: en una maquina de desarrollo es memoria, no dinero.
- **Riesgo**: bajo para el codigo de produccion, que no se toca. El riesgo real
  es de mantenimiento: `database/dentissa.sql` pasa a ser un artefacto del que
  dependen las pruebas y se desincroniza si alguien cambia el esquema sin
  regenerarlo. Riesgo secundario: C toca un archivo ajeno al alcance original de
  la intencion, con aprobacion humana explicita.

**Resultado, verificado el 2026-09-19**: cerrado. El arreglo A produjo
`database/testing/bootstrap-test-db.sh`, el cambio de `phpunit.xml` a
`dentissa_test`, y nueve regresiones de dominio (`ServiciosPrecioTest`,
`UsuariosAdminTest`) verificadas como no complacientes. El arreglo C reescribio
`Tests\Feature\ExampleTest`. TC-1b, TC-2, TC-5, TC-6a y TC-6b quedan en `Met`.
No hubo bucle de corrección 2: el unico objetivo que seguia abierto tras este
—NFR5, la capa de vistas— se cerro con un arreglo dentro de la etapa (peldano 1)
que no toca codigo de produccion.
