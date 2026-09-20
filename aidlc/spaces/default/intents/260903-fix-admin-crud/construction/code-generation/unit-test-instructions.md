# Instrucciones de prueba — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Estrategia de pruebas**: Minimal · **Alcance**: bugfix (piso aditivo: regresión
dirigida al fallo + suite existente en verde)
**Metodología**: `test-after` — se implementa cada capa aplicable y después se
escriben y ejecutan sus pruebas.
**Revisado**: 2026-09-14T01:29:55Z, bucle de corrección 1 de 3, junto con
`code-generation-plan.md`. Este bucle sí cambia la configuración de pruebas: se
levanta una base MariaDB de pruebas y `phpunit.xml` pasa a apuntar a ella, lo que
permite escribir por fin las regresiones de TD-8 y TD-16. Ver
`code-generation-plan.md` § "Cobertura de pruebas — qué cambia en este bucle de
corrección".

---

## Marco y configuración

El proyecto ya trae PHPUnit configurado. Este bucle de corrección cambia **una
sola cosa** de esa configuración: la base de datos contra la que corren las
pruebas.

- Ejecutor: `vendor/bin/phpunit` (PHPUnit vía `phpunit.xml` en la raíz).
- Clase base: `Tests\TestCase` (`tests/TestCase.php`).
- Suites declaradas: `Unit` (`tests/Unit`) y `Feature` (`tests/Feature`).
- Base de datos de pruebas: **`dentissa_test` en MariaDB**, servida por el
  contenedor `db` que ya existe (`mariadb:10.11`), cargada desde
  `database/dentissa.sql`. Sustituye a la SQLite en memoria anterior
  (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), que no tenía las tablas del
  dominio ni claves foráneas activas.
- Aislamiento entre pruebas: trait `DatabaseTransactions`. **Nunca
  `RefreshDatabase`**: ese ejecutaría las migraciones de Laravel y dejaría la
  base sin las tablas del dominio, que vienen del volcado y no de migraciones.

No se añade ninguna dependencia de Composer ni de npm.

---

## Prerrequisito: levantar la base de pruebas

Antes de la primera ejecución de las pruebas de dominio, con el entorno Docker
en marcha:

```bash
bash database/testing/bootstrap-test-db.sh
```

El script es idempotente: crea `dentissa_test` si no existe y recarga el volcado
limpio. Basta ejecutarlo una vez, y de nuevo cuando `database/dentissa.sql`
cambie.

Las cuatro pruebas de tabla de rutas **no** necesitan este paso: Laravel conecta
de forma perezosa y esas pruebas nunca consultan la base. Las de
`ServiciosPrecioTest` y `UsuariosAdminTest` sí lo necesitan.

---

## Comando acotado a esta corrección

Este es el comando exacto que ejecuta **solo** las pruebas que añade esta
corrección. Es el que Build and Test debe usar para esta etapa:

```bash
vendor/bin/phpunit tests/Feature/AdminUsuariosRutasTest.php tests/Feature/ServiciosPrecioTest.php tests/Feature/UsuariosAdminTest.php
```

Comprobación de no regresión (la suite completa, que debe quedar en verde):

```bash
vendor/bin/phpunit
```

En Windows con el entorno Docker del repositorio, prefija ambos comandos con
`docker compose exec app` (o el nombre del servicio PHP que use tu
`docker-compose.yml`).

---

## Alcance de las pruebas automáticas

Estrategia Minimal: una prueba verificable por requisito, al nivel más estrecho
que reproduzca el fallo. Tres archivos, **trece** pruebas.

> **Corrección al recuento (2026-09-13, tras ejecutar el bucle).** La versión
> anterior de este documento decía doce. Son trece: `ServiciosPrecioTest` lleva
> una cuarta prueba, `test_un_color_de_agenda_invalido_se_rechaza`, porque la
> guardarraíl de fase de Construction exige camino feliz más dos casos de error o
> borde en cada archivo de prueba, y con las tres planificadas solo había uno de
> error. Es una adición; no sustituye ni rebaja ninguna de las doce.

### `tests/Feature/AdminUsuariosRutasTest.php` (paso 10, sin cambios)

| Prueba | Qué comprueba | Requisito | Fallo que regresa |
|---|---|---|---|
| El alta de usuarios resuelve a `create` | `/admin/usuarios/crear` resuelve a `UsuarioController@create`, no a `@show` | FR1.1 | TD-11 |
| El listado resuelve al controlador | `/admin/usuarios` resuelve a `UsuarioController@index`, no al closure del placeholder | FR1.3 | TD-12 |
| Un identificador no-UUID no resuelve a `show` | La restricción `where` sobre `{id}` rechaza un segmento literal | FR1.2 | TD-11 (defensa) |
| El borrado ya no existe y la alternancia sí | `admin.usuarios.destroy` ausente, `admin.usuarios.toggle` presente | FR4.2, FR4.5 | TD-16 |

Estas cuatro inspeccionan la tabla de rutas y no tocan la base de datos.

### `tests/Feature/ServiciosPrecioTest.php` (paso 14, nuevo)

| Prueba | Qué comprueba | Requisito | Fallo que regresa |
|---|---|---|---|
| El precio sobrevive a una edición parcial | Editar solo la duración de un servicio conserva su precio | FR2.4, FR2.5 | **TD-8, regresión directa** |
| El alta guarda precio y color escritos | `store()` persiste los valores enviados, no `0` ni `#F4C0D1` | FR2.6, FR2.7 | TD-8 |
| Un precio negativo se rechaza | La validación devuelve error y el servicio no se modifica | FR2.6, NFR3 | TD-8 (defensa) |
| Un color de agenda inválido se rechaza | La validación devuelve error y el color no se modifica | FR2.6, NFR3 | TD-8 (defensa) |

### `tests/Feature/UsuariosAdminTest.php` (paso 15, nuevo)

| Prueba | Qué comprueba | Requisito | Fallo que regresa |
|---|---|---|---|
| El rol `asistente` sobrevive a una edición parcial | El desplegable ofrece y preselecciona `asistente`; una edición que omite el rol se rechaza en validación y no lo toca | FR3.3, FR3.4, FR3.5, NFR1 | **TD-16, elevación de privilegios** |
| Un correo duplicado se rechaza | La validación `unique` impide el segundo usuario | FR3.2, NFR3 | TD-16 |
| El alta sin contraseña genera una aleatoria | El hash no es el de `password123` y difiere entre altas | FR3.6 | TD-16 |
| La baja lógica conserva el expediente | `toggleActivo` pone `activo` a 0 sin borrar paciente, cita ni expediente | FR4.1, FR4.6, NFR2 | **TD-16, pérdida de datos** |
| La reactivación funciona | Un segundo `toggleActivo` devuelve `activo` a 1 | FR4.5 | TD-16 |

Todas usan `DatabaseTransactions` y crean sus propios fixtures dentro de la
prueba. No dependen de filas concretas del volcado, solo de su esquema, así que
regenerar `database/dentissa.sql` con otros datos no las rompe.

### Comprobación de que las pruebas no son complacientes

Cada archivo nuevo debe verificarse contra el código con el defecto, igual que se
hizo con el de rutas: revertir temporalmente el controlador correspondiente y
confirmar que las pruebas fallan. Una prueba que pasa en ambos estados no
demuestra nada.

**Ejecutado el 2026-09-13.** Revirtiendo `ServiciosController.php` y
`UsuarioController.php` a su estado en `HEAD` —las rutas y las vistas se dejaron
corregidas— y volviendo a ejecutar:

| Archivo | Código corregido | Código con el defecto |
|---|---|---|
| `tests/Feature/AdminUsuariosRutasTest.php` | `OK (4 tests, 8 assertions)` | `OK (4 tests, 8 assertions)` (ya comprobado en el paso 10 contra `routes/web.php` revertido: `Failures: 3`) |
| `tests/Feature/ServiciosPrecioTest.php` | `OK (4 tests, 21 assertions)` | `Tests: 4, Assertions: 11, Failures: 4` |
| `tests/Feature/UsuariosAdminTest.php` | `OK (5 tests, 33 assertions)` | `Tests: 5, Assertions: 11, Failures: 5` |
| Suite completa | `OK (15 tests, 65 assertions)` | `Tests: 15, Assertions: 33, Failures: 9` |

**Un aviso sobre la prueba de elevación de privilegios**, por si alguien la
reescribe más adelante. Afirmar únicamente «el rol sigue siendo `asistente`» tras
enviar solo el teléfono **no** demuestra nada: contra el código con el defecto esa
petición revienta contra la base (`Column 'nombre' cannot be null`) y, por
reventar, deja el rol intacto; la aserción pasaría en los dos estados. Lo que
discrimina es `assertSessionHasErrors`, porque el código corregido rechaza la
petición en la validación y el defectuoso no valida nada. La aserción del rol se
conserva —es el enunciado del requisito— pero no es la que hace de red.

## Objetivos de cobertura

- **Suite existente**: debe quedar en verde por completo, incluidos los dos
  ejemplos de Laravel. `tests/Unit/ExampleTest.php` ya pasa y no se toca;
  `tests/Feature/ExampleTest.php` se corrige en el paso 16 para que afirme la
  redirección real en vez de un 200 que esta aplicación nunca devolvió (FR5.2).
- **Pruebas nuevas**: las trece de arriba en verde.
- **Regresión dirigida**: cubierta ya para los cuatro defectos — TD-11 y TD-12
  por tabla de rutas, TD-8 y TD-16 por las pruebas de dominio nuevas. El piso del
  alcance `bugfix` queda satisfecho.
- **Umbral de cobertura de línea**: ninguno. El alcance `bugfix` no impone piso
  de porcentaje; el piso que impone es la regresión dirigida, tratada arriba.

No se rebaja, desactiva ni relaja ningún umbral existente para hacer pasar un
paso.

## Simulación y datos de prueba

- **Sin dobles de prueba.** Las de rutas consultan el enrutador real a través de
  `Illuminate\Support\Facades\Route`. Las de dominio ejercitan los controladores
  reales contra MariaDB real: el fallo de cascada de TD-16 solo es observable con
  claves foráneas de verdad, que es justo lo que SQLite no daba.
- **Fixtures, no datos del volcado.** Cada prueba de dominio crea las filas que
  necesita (usuario, servicio, paciente, cita, expediente) dentro de su propia
  transacción y las revierte al terminar. Se depende del esquema cargado, nunca
  de una fila concreta.
- **Autenticación.** `App\Models\Usuario` es el proveedor del guard `web`
  (`config/auth.php`), así que `actingAs($usuario)` con un usuario de rol `admin`
  creado en la prueba basta para atravesar `auth` y `role:admin`.
- Los datos para la verificación manual siguen siendo los que trae
  `database/dentissa.sql`: 8 usuarios y 15 servicios con precios de 200,00 a
  18.000,00.

---

## Verificación manual

Esta es la cobertura de FR5.1 y **se conserva íntegra** tras el bucle de
corrección 1. Ya no es el sustituto de las regresiones automáticas de TD-8 y
TD-16 —esas ahora existen, en los pasos 14 y 15— sino la verificación de la capa
de interfaz, que sigue sin ejecutor de pruebas, y una comprobación de extremo a
extremo por encima de ellas. Ejecútala contra el entorno Docker del repositorio,
con sesión iniciada como administrador (`admin@clinica.com`).

**Preparación**

- [ ] 0. Ejecutar `php artisan view:clear` y `php artisan route:clear`. Las vistas
  compiladas en `storage/framework/views/` todavía referencian la ruta
  `admin.usuarios.destroy`, que esta corrección elimina.

**TD-11 y TD-12 — acceso al módulo de usuarios**

- [ ] 1. Ir a `/admin/usuarios`. Debe salir el listado real de usuarios, no la
  pantalla de marcador de posición.
- [ ] 2. Pulsar «Nuevo usuario» (o ir a `/admin/usuarios/crear`). Debe salir el
  formulario de alta, no un 404.

**TD-16 — validación de usuarios**

- [ ] 3. En el alta, enviar el formulario con el correo `admin@clinica.com`, que
  ya existe. Debe rechazarse con el mensaje «Este correo ya está registrado en el
  sistema.» y no crearse un segundo usuario.
- [ ] 4. Crear un usuario dejando el campo «Contraseña temporal» vacío. Debe
  crearse. Comprobar en base de datos que su `password_hash` **no** es el hash de
  `password123` y difiere del de otro usuario creado igual.
- [ ] 5. Crear un usuario escribiendo una contraseña concreta y comprobar que
  puede iniciar sesión con ella.

**TD-16 — el rol asistente**

- [ ] 6. Editar un usuario existente y comprobar que el selector de rol ofrece
  cuatro opciones: Administrador, Dentista, Asistente y Paciente / Cliente.
- [ ] 7. Cambiar un usuario a rol `asistente` y guardar. Volver a abrir su ficha
  de edición, cambiar **solo el teléfono** y guardar. Comprobar en base de datos
  que su `rol` sigue siendo `asistente` y no ha pasado a `admin`. **Este es el
  fallo de elevación de privilegios**; si el rol cambia, la corrección no está
  completa.
- [ ] 8. Editar un usuario con rol `cliente` y comprobar que el selector sigue
  oculto y su rol no cambia (comportamiento existente que debe conservarse).

**TD-16 — baja lógica**

- [ ] 9. Localizar un usuario `cliente` que tenga ficha de paciente y citas
  registradas. Pulsar «Desactivar». Debe funcionar sin error 500, `usuarios.activo`
  debe quedar en 0, y su ficha de paciente, sus citas y su expediente deben seguir
  existiendo en base de datos.
- [ ] 10. Pulsar «Activar» sobre ese mismo usuario. `activo` vuelve a 1.
- [ ] 11. Comprobar que el modal ya no dice «Esta acción no se puede deshacer».

**TD-8 — precio y color de servicios**

- [ ] 12. Ir a `/admin/servicios` y abrir la edición de «Colocación de brackets»
  (precio 18.000,00). Cambiar **solo la duración** y guardar. Volver a abrirlo:
  el precio debe seguir siendo 18.000,00. **Este es el fallo de pérdida
  silenciosa**; si el precio pasa a 0, la corrección no está completa.
- [ ] 13. Editar el precio de un servicio a un valor nuevo y comprobar que se
  guarda.
- [ ] 14. Intentar guardar un servicio con precio `-50`. Debe rechazarse con
  mensaje de validación.
- [ ] 15. Crear un servicio nuevo con precio y color de agenda. Comprobar en base
  de datos que ambos se guardaron con los valores escritos, no con 0 y `#F4C0D1`.
- [ ] 16. Cambiar el color de agenda de un servicio existente y comprobar que
  persiste.
- [ ] 17. Consultar `SELECT servicio, precio FROM v_citas_hoy` y
  `SELECT servicio, precio FROM v_historial_paciente` y confirmar que muestran
  los precios correctos.

**No regresión**

- [ ] 18. Comprobar que el listado de usuarios sigue filtrando por texto y por
  tipo, y que la ficha de detalle y la edición siguen funcionando.
- [ ] 19. Comprobar que el alta y la edición de servicios siguen guardando
  correctamente nombre, categoría, duración, descripción y estado activo.
