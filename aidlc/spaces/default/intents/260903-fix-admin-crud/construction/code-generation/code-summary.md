# Resumen de código — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: code-generation (Construction) · **Alcance**: bugfix · **Fecha**: 2026-09-08
**Metodología**: `test-after` (contrato `sha256:99478f46…`, sin cambios respecto al plan aprobado)

Los 12 pasos del plan quedaron ejecutados y sus 34 casillas marcadas. Ningún
archivo se duplicó ni se renombró: todo se modificó en el sitio, como exige el
trabajo brownfield.

> **Actualizado el 2026-09-13 — bucle de corrección 1 de 3.** Build and Test
> devolvió la etapa con cuatro objetivos fuera de `Met` y el humano eligió
> `Retry with fix` con el arreglo **A + C**. Los pasos 1 a 12 no se han tocado:
> su código sigue tal cual, revisado y en verde. Lo que añade este bucle son los
> pasos 13 a 17, que cierran la brecha de cobertura automática que la versión
> anterior de este resumen declaraba abierta bajo **OQ2** y el fallo preexistente
> de la suite bajo **GAP-BASELINE**. Ambas brechas quedan cerradas; la sección
> "Brechas conocidas" de más abajo se ha reescrito en consecuencia. Todo lo
> añadido está en la sección **"Bucle de corrección 1"** al final del documento.

---

## Archivos creados o modificados

| Archivo | Acción | Qué cambió |
|---|---|---|
| `routes/web.php` | Modificar | Closure muerto eliminado; `/usuarios/crear` registrada antes que `/usuarios/{id}`; restricción UUID en `show`, `edit` y `update`; `PATCH /usuarios/{id}/toggle` añadida; `DELETE /usuarios/{id}` eliminada |
| `app/Http/Controllers/UsuarioController.php` | Modificar | Validación de servidor en `store()` y `update()`; `rol` contra el enum; email único con `Rule::unique(...)->ignore($id)`; contraseña enviada o `Str::random(10)` con `Hash::make()`; `actualizado_en` escrito; `toggleActivo()` añadido; `destroy()` eliminado |
| `app/Http/Controllers/ServiciosController.php` | Modificar | Eliminadas las asignaciones incondicionales `$data['precio'] = 0` y `$data['color_agenda'] = '#F4C0D1'`; `$request->all()` sustituido por el array validado en ambos métodos |
| `resources/views/usuarios/create.blade.php` | Modificar | Opción `asistente` con su ternario `selected` (`data-testid="rol-asistente"`); bloque de errores de validación |
| `resources/views/usuarios/index.blade.php` | Modificar | Botón Desactivar/Activar (`data-testid="usuario-toggle-activo"`); modal y JavaScript reescritos a `@method('PATCH')` sobre `admin.usuarios.toggle` |
| `resources/views/servicios/create.blade.php` | Modificar | Campos de precio y color de agenda (`data-testid="servicio-precio"`, `servicio-color-agenda`); bloque de errores |
| `resources/views/servicios/edit.blade.php` | Modificar | Los mismos dos campos, precargados; bloque de errores |
| `tests/Feature/AdminUsuariosRutasTest.php` | Crear | 4 pruebas, 8 aserciones sobre la tabla de rutas |

`database/dentissa.sql`, `database/migrations/` y las vistas SQL `v_citas_hoy` /
`v_historial_paciente` no se tocaron (restricciones C1 y C6). `phpunit.xml`
tampoco se tocó **en los pasos 1 a 12**; el bucle de corrección 1 sí lo modifica,
y esa desviación está autorizada por la elección humana del arreglo A (ver abajo).

---

## Verificación ejecutada

Las tres ejecuciones se hicieron con `docker compose exec app`. El PHP del host
es 8.2.12 y `vendor/` está instalado para PHP >= 8.4 (`Dockerfile` usa
`php:8.4-fpm`), así que ni `phpunit` ni `artisan` arrancan fuera del contenedor.

| Ejecución | Comando | Resultado |
|---|---|---|
| Línea base (cambios apartados con `git stash`) | `docker compose exec app vendor/bin/phpunit` | `Tests: 2, Assertions: 2, Failures: 1` |
| Prueba acotada a esta corrección | `docker compose exec app vendor/bin/phpunit tests/Feature/AdminUsuariosRutasTest.php` | `OK (4 tests, 8 assertions)` |
| Suite completa tras los cambios | `docker compose exec app vendor/bin/phpunit` | `Tests: 6, Assertions: 10, Failures: 1` |

**Cero regresiones nuevas.** El único fallo es el mismo en las tres columnas.

Las pruebas nuevas se comprobaron contra el código con el fallo (revirtiendo
`routes/web.php`): devuelven `Tests: 4, Assertions: 6, Failures: 3`. No son
complacientes.

---

## Decisiones de implementación

- **`actualizado_en` sin tocar el modelo.** `App\Models\Usuario` declara
  `$timestamps = false` y no incluye `actualizado_en` en `$fillable`, así que un
  `update([...])` lo habría descartado en silencio. Se usa `fill()` más
  asignación directa del atributo y `save()`. El Paso 3 del plan dice «no se
  modifica ningún modelo» y se respetó.
- **`password` no se valida en `update()`.** La vista no renderiza ese campo al
  editar (`@if(!isset($usuario))`); validar un campo que nunca llega sería un
  no-op.
- **Escapado del nombre en el JavaScript del modal.** El `onclick="fn('{{ $m->nombre }}')"`
  se sustituyó por `@js()` y el nombre se escribe con `innerText`, no
  `innerHTML`. Un apellido con apóstrofo rompía el JavaScript, y el valor viene
  de la base de datos.

---

## Desviaciones del plan

**1. Bloque de errores de validación añadido a tres formularios (no estaba en el Paso 9).**

Ninguna vista del proyecto renderiza errores de validación. Sin ese bloque, la
validación nueva redirige de vuelta sin decir nada, y quedarían incumplidos NFR3
(«los fallos previsibles se resuelven con un mensaje entendible en la propia
pantalla») y la regla de fase de Construction sobre fallos silenciosos. Se añadió
`@if ($errors->any())` a `usuarios/create.blade.php`,
`servicios/create.blade.php` y `servicios/edit.blade.php`.

**2. El Paso 10 afirmaba que la prueba del listado sería una regresión que falla primero. No lo es.**

Comprobado ejecutándola contra el código con el fallo: pasa. El closure muerto y
`UsuarioController@index` compartían método y URI; Laravel indexa por método más
URI y descarta el anterior, y tras el arranque reconstruye la lista de nombres
desde ese índice — de modo que `Route::has('admin.usuarios')` **ya devolvía
`false`** antes del cambio. TD-12 era código muerto y confuso, no una ruta que se
impusiera: la pantalla de marcador de posición nunca llegaba a servirse.
Eliminarlo sigue siendo correcto por FR1.3, pero su regresión no es observable
desde el enrutador. La prueba se conservó como fijación de estado, se le añadieron
dos aserciones de nombre que cubren FR1.4, y se corrigió el docblock que afirmaba
lo contrario. Anotado también en el plan.

---

## Brechas conocidas

**OQ2 — CERRADA por el bucle de corrección 1.** Las regresiones de TD-8 y TD-16
ya están automatizadas. El paso 13 levantó una base MariaDB de pruebas cargada
desde el mismo volcado que sirve el esquema del dominio, con sus claves foráneas
reales, y los pasos 14 y 15 escribieron las nueve pruebas que faltaban. Detalle
en la sección "Bucle de corrección 1".

**GAP-BASELINE — CERRADA por el bucle de corrección 1.** `Tests\Feature\ExampleTest`
ya no falla. El paso 16 reescribió la aserción para que describa lo que esta
aplicación hace —`GET /` redirige siempre, con sesión o sin ella— en vez de un 200
que nunca devolvió. La decisión de reabrir esto la tomó el humano al elegir el
arreglo C, no el agente, precisamente porque «corregir una prueba equivocada» y
«bajar el listón» se parecen desde fuera. Aquí es lo primero: la aserción nueva
falla si la aplicación deja de redirigir, mientras que la vieja no describía
ninguna versión de este código. FR5.2 se cumple ahora en su forma literal: la
suite completa queda en verde.

**FR2.4 y los dos decimales.** El requisito pide «dos decimales como máximo». Se
aplicó la regla literal del plan (`required|numeric|min:0|max:99999999.99`); el
redondeo lo impone la columna `decimal(10,2)`, no una regla de validación.
Cerrarlo en la capa de aplicación sería añadir `decimal:0,2`.

**Rol `asistente` en el listado.** Un usuario con ese rol sale con etiqueta y
color de reserva (`$tipoLabels` y `$tipoColors` no tienen esa clave) y el filtro
«Rol» no ofrece la opción. No rompe nada y está fuera del alcance del plan.

---

## Estado del entorno

Docker Desktop quedó arrancado y los contenedores `dentissa_app`,
`dentissa_nginx` y `dentissa_db` levantados. Las cachés de vistas y rutas están
limpias, así que el punto 0 de la lista de comprobación manual ya está hecho.

Tras el bucle de corrección 1 hay además una base `dentissa_test` cargada en ese
mismo contenedor `dentissa_db`, creada por
`database/testing/bootstrap-test-db.sh`. Es el prerrequisito de las nueve pruebas
de dominio nuevas.

---

# Bucle de corrección 1 — arreglo A + C (2026-09-13)

Pasos 13 a 17. Cierra las dos brechas que la primera pasada declaró abiertas: la
cobertura automática de TD-8 y TD-16 (**OQ2**) y el fallo preexistente de la
suite (**GAP-BASELINE**).

## Archivos creados o modificados

| Archivo | Acción | Qué cambió |
|---|---|---|
| `database/testing/bootstrap-test-db.sh` | Crear | Script idempotente que recrea `dentissa_test` en el contenedor `db` existente y la carga desde `database/dentissa.sql`; comprueba Docker, el servicio, la disponibilidad de MariaDB y las seis tablas del dominio, y falla con mensaje entendible en cada borde |
| `phpunit.xml` | Modificar | `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` sustituidos por la conexión `mariadb` contra `dentissa_test` (`DB_HOST=db`, `DB_PORT=3306`, `DB_USERNAME=root`, `DB_PASSWORD=root`) |
| `tests/Feature/ServiciosPrecioTest.php` | Crear | 4 pruebas, 21 aserciones: regresión directa de TD-8, alta con precio y color, precio negativo, color inválido |
| `tests/Feature/UsuariosAdminTest.php` | Crear | 5 pruebas, 33 aserciones: elevación de privilegios, correo duplicado, contraseña aleatoria, baja lógica sin cascada, reactivación |
| `tests/Feature/ExampleTest.php` | Modificar | `test_the_application_returns_a_successful_response` (esperaba 200) reescrita como `test_anonymous_visitor_is_redirected_to_login` (afirma la redirección a `/login`) |

No se tocó `docker-compose.yml`, `database/dentissa.sql`, `database/migrations/`,
`tests/Unit/ExampleTest.php`, ni ningún archivo de los pasos 1 a 12.

## Verificación ejecutada

Todo con `docker compose exec app` (el PHP del host es 8.2.12 y `vendor/` está
instalado para PHP >= 8.4).

| Ejecución | Resultado |
|---|---|
| `bash database/testing/bootstrap-test-db.sh`, dos veces seguidas | código 0 las dos veces; 19 tablas y 2 vistas en `dentissa_test` |
| Comando acotado (`AdminUsuariosRutasTest` + `ServiciosPrecioTest` + `UsuariosAdminTest`) | `OK (13 tests, 62 assertions)` |
| Suite completa | `OK (15 tests, 65 assertions)` |
| Suite completa, controladores revertidos al estado con el defecto | `Tests: 15, Assertions: 33, Failures: 9` |
| `ServiciosPrecioTest` contra el código con el defecto | `Tests: 4, Assertions: 11, Failures: 4` |
| `UsuariosAdminTest` contra el código con el defecto | `Tests: 5, Assertions: 11, Failures: 5` |
| `AdminUsuariosRutasTest` con la base inalcanzable (`DB_HOST` falso) | `OK (4 tests, 8 assertions)` — la conexión perezosa se confirma |
| `ServiciosPrecioTest` con la base inalcanzable | `Tests: 4, Errors: 4` — contraprueba de que la sobreescritura del host surtió efecto |
| `php -l` sobre los archivos PHP nuevos o modificados y los dos controladores restaurados | sin errores de sintaxis |

La suite completa pasó de `Tests: 6, Failures: 1` a `OK (15 tests, 65 assertions)`.

## Decisiones de implementación

- **Una base más, no un contenedor más.** `dentissa_test` vive en el
  `mariadb:10.11` que ya declara `docker-compose.yml`. Mantiene el arreglo A en
  «una base adicional» en lugar de «infraestructura nueva», y evita un segundo
  servicio que habría que mantener.
- **La carga entra por `stdin` al contenedor `db`, no al de la aplicación.** El
  repositorio no está montado en `db`, así que el volcado se canaliza con
  `docker compose exec -T db mariadb ... < database/dentissa.sql`. Hay un segundo
  motivo, menos obvio: esa conexión es local dentro del contenedor y resuelve a
  `root@localhost`, que es exactamente el `DEFINER` que declaran `v_citas_hoy` y
  `v_historial_paciente`. Cargarlas desde el contenedor `app` habría creado las
  vistas con otro definidor.
- **Idempotencia por `DROP` más `CREATE`.** El volcado declara sus tablas sin
  `IF NOT EXISTS`, así que recargarlo sobre una base ya poblada falla. El script
  la borra y la recrea; dos ejecuciones seguidas terminan en el mismo estado.
- **Fixtures propios, nunca filas del volcado.** Cada prueba de dominio inserta
  con `DB::table()` las filas que necesita y `DatabaseTransactions` las revierte.
  Así, regenerar `database/dentissa.sql` con otros datos no rompe las pruebas: se
  depende del esquema, no del contenido. Los inserts van por el constructor de
  consultas y no por los controladores, para que el fixture no dependa del código
  bajo prueba.
- **`Usuario` sirve de administrador de sesión.** `config/auth.php` declara
  `App\Models\Usuario` como proveedor del guard `web`, así que un usuario con rol
  `admin` creado en la propia prueba y pasado a `actingAs()` atraviesa `auth` y
  `role:admin` sin ningún doble de prueba.

## Desviaciones del plan

**1. `ServiciosPrecioTest` tiene cuatro pruebas, no las tres planificadas.**

Se añadió `test_un_color_de_agenda_invalido_se_rechaza`. La guardarraíl de fase de
Construction exige «camino feliz más al menos dos casos de error o borde» en cada
archivo de prueba, y con las tres viñetas del paso 14 solo había un caso de error
(el precio negativo). La adición es estrictamente aditiva: no sustituye ni rebaja
ninguna de las tres. `unit-test-instructions.md` se corrigió para decir trece
pruebas en vez de doce.

**2. La prueba de elevación de privilegios comprueba dos cosas, no una.**

El paso 15 pedía «actualizar enviando solo un teléfono nuevo y afirmar que el rol
sigue siendo `asistente`». Escrita así y nada más, **habría sido complaciente**, y
se comprobó empíricamente: contra el código con el defecto esa petición revienta
contra la base (`Column 'nombre' cannot be null`) y, justamente por reventar, deja
el rol intacto. La prueba habría pasado en los dos estados. La aserción que sí
discrimina es `assertSessionHasErrors`, porque el código corregido rechaza la
petición en la validación y el defectuoso no valida nada.

Se añadió además un segundo escenario en la misma prueba: que el desplegable de
rol del formulario de edición ofrezca `asistente` y lo traiga preseleccionado. Ese
era el vector real de la escalada —sin esa opción el navegador seleccionaba la
primera del desplegable, `admin`, y la enviaba al guardar—, y con él FR3.4 y FR3.5
pasan de verificación manual a cobertura automática. La aserción del rol se
conserva porque es el enunciado literal del requisito, pero no es la que hace de
red.

**3. `phpunit.xml` se modifica, contra lo que decía el plan original.**

Desviación deliberada y ya prevista en el plan revisado: es la esencia del arreglo
A y la autorizó la elección humana. El cambio es global a toda la suite, así que
cualquier prueba futura hereda `dentissa_test`.

## Riesgos que este bucle introduce

- **El volcado pasa a ser dependencia de las pruebas.** `database/dentissa.sql`
  deja de ser solo la semilla de desarrollo y es ahora la fuente del esquema de
  pruebas. Si alguien cambia el esquema sin regenerar el volcado, las pruebas de
  dominio dejan de reflejar la realidad. Es mantenimiento, no producción, y es el
  precio conocido de la opción A.
- **Las pruebas de dominio exigen Docker levantado.** Verificado: sin base
  alcanzable, las nueve fallan por conexión y las cuatro de tabla de rutas siguen
  pasando. El prerrequisito está documentado en `unit-test-instructions.md`.
- **`DatabaseTransactions` frente a DDL.** El trait revierte por transacción; una
  sentencia DDL dentro de una prueba provocaría un commit implícito en MariaDB y
  dejaría residuos. Las pruebas escritas solo hacen DML. Conviene no introducir
  DDL en ellas más adelante.

## Lo que sigue sin cobertura automática

La lista de comprobación manual de `unit-test-instructions.md` **se conserva
íntegra** y sigue siendo la cobertura de FR5.1. Ya no sustituye a las regresiones
de TD-8 y TD-16 —ahora existen— sino que cubre la capa de interfaz, que sigue sin
ejecutor de pruebas (`package.json` solo trae Vite; no hay Vitest, Jest ni Dusk),
y sirve de comprobación de extremo a extremo por encima de las automáticas.
Siguen sin cobertura automática FR2.1, FR2.2, FR2.3, FR3.7, FR4.3 y FR4.4, todos
de capa de vista.
