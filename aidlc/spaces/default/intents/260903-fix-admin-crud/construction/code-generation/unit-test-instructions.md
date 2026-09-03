# Instrucciones de prueba — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Estrategia de pruebas**: Minimal · **Alcance**: bugfix (piso aditivo: regresión
dirigida al fallo + suite existente en verde)
**Metodología**: `test-after` — se implementa cada capa aplicable y después se
escriben y ejecutan sus pruebas.

---

## Marco y configuración

El proyecto ya trae PHPUnit configurado; **no se cambia nada** de esa
configuración.

- Ejecutor: `vendor/bin/phpunit` (PHPUnit vía `phpunit.xml` en la raíz).
- Clase base: `Tests\TestCase` (`tests/TestCase.php`).
- Suites declaradas: `Unit` (`tests/Unit`) y `Feature` (`tests/Feature`).
- Base de datos de pruebas: SQLite en memoria (`DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`, líneas 26-27 de `phpunit.xml`).
- Suite existente al empezar: `tests/Unit/ExampleTest.php` y
  `tests/Feature/ExampleTest.php`, los dos ejemplos que trae Laravel.

No se añade ninguna dependencia y no se modifica `phpunit.xml`.

---

## Comando acotado a esta corrección

Este es el comando exacto que ejecuta **solo** las pruebas que añade esta
corrección. Es el que Build and Test debe usar para esta etapa:

```bash
vendor/bin/phpunit tests/Feature/AdminUsuariosRutasTest.php
```

Comprobación de línea base y de no regresión (la suite completa, que debe seguir
en verde):

```bash
vendor/bin/phpunit
```

En Windows con el entorno Docker del repositorio, prefija ambos comandos con
`docker compose exec app` (o el nombre del servicio PHP que use tu
`docker-compose.yml`).

---

## Alcance de las pruebas automáticas

Estrategia Minimal: una prueba verificable por requisito, al nivel más estrecho
que reproduzca el fallo. Se añade **un** archivo con cuatro pruebas.

`tests/Feature/AdminUsuariosRutasTest.php`

| Prueba | Qué comprueba | Requisito | Fallo que regresa |
|---|---|---|---|
| El alta de usuarios resuelve a `create` | `/admin/usuarios/crear` resuelve a `UsuarioController@create`, no a `@show` | FR1.1 | TD-11 |
| El listado resuelve al controlador | `/admin/usuarios` resuelve a `UsuarioController@index`, no al closure del placeholder | FR1.3 | TD-12 |
| Un identificador no-UUID no resuelve a `show` | La restricción `where` sobre `{id}` rechaza un segmento literal | FR1.2 | TD-11 (defensa) |
| El borrado ya no existe y la alternancia sí | `admin.usuarios.destroy` ausente, `admin.usuarios.toggle` presente | FR4.2, FR4.5 | TD-16 |

**Por qué solo estas cuatro.** Son las que se pueden ejecutar contra la
configuración actual: inspeccionan la tabla de rutas de Laravel y no necesitan
filas en base de datos. Las regresiones de TD-8 y TD-16 (precio que sobrevive,
baja lógica que conserva el expediente, rol de asistente que no se pierde)
necesitan las tablas `usuarios`, `servicios`, `pacientes` y `citas`, que solo
existen en `database/dentissa.sql` y no en migraciones. Ver
`code-generation-plan.md` § "Brecha de cobertura de pruebas". Esas quedan
cubiertas por la verificación manual de abajo, conforme a la decisión Q4 = D.

## Objetivos de cobertura

- **Suite existente**: debe seguir en verde. Cero regresiones — es la mitad del
  piso del alcance `bugfix` que sí aplica aquí (FR5.2).
- **Pruebas nuevas**: las cuatro de arriba en verde.
- **Umbral de cobertura de línea**: ninguno. El alcance `bugfix` no impone piso
  de porcentaje; el piso que impone es la regresión dirigida, tratada arriba.

No se rebaja, desactiva ni relaja ningún umbral existente para hacer pasar un
paso.

## Simulación y datos de prueba

- **Sin dobles de prueba.** Las cuatro pruebas consultan el enrutador real de
  Laravel a través de `Illuminate\Support\Facades\Route`; no hay colaboradores
  que simular.
- **Sin datos de prueba.** Ninguna prueba escribe ni lee la base de datos, así
  que no hace falta `RefreshDatabase` ni fixtures.
- Los datos para la verificación manual son los que ya trae
  `database/dentissa.sql`: 8 usuarios y 15 servicios con precios de 200,00 a
  18.000,00.

---

## Verificación manual

Esta es la cobertura de FR5.1 y el sustituto acordado (Q4 = D) de las
regresiones automáticas que hoy no se pueden escribir. Ejecútala contra el
entorno Docker del repositorio, con sesión iniciada como administrador
(`admin@clinica.com`).

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
