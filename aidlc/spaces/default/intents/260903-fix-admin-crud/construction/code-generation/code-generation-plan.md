# Plan de generación de código — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: code-generation (Construction)
**Alcance**: bugfix · **Profundidad**: Minimal · **Estrategia de pruebas**: Minimal
**Tipo de proyecto**: Brownfield (Laravel 12 + MariaDB)
**Fuente de requisitos**: `aidlc/spaces/default/intents/260903-fix-admin-crud/inception/requirements-analysis/requirements.md`
**Reemitido**: 2026-09-08T14:15Z, en el intento reiniciado de esta etapa, sobre
el estado actual del repositorio. Verificado que `routes/web.php` mantiene el
closure muerto en la línea 88, `usuarios.index` en la 90, `usuarios.show` en la
92, `usuarios.crear` en la 94 y `usuarios.destroy` en la 100, y que el contrato
de pruebas resuelto sigue siendo el mismo (`contract_sha256` sin cambios). No hay
ningún cambio de código de aplicación desde el análisis.

**Revisado 2026-09-14T01:29:55Z — bucle de corrección 1 de 3.** Build and Test
cerró con cuatro objetivos fuera de `Met` y el humano eligió `Retry with fix`
con el arreglo **A + C**. Los pasos 1 a 12 quedan tal cual: su código ya está
aplicado, revisado y verde. Lo que este bucle añade son los pasos 13 a 17, que
cierran la brecha de cobertura que el plan anterior declaraba abierta. Ver
`../build-and-test/test-results.md` § "Loop-Back Log" para el diagnóstico
completo.

Sin Unidades de trabajo: el alcance `bugfix` omite Units Generation, así que esto
es una sola iteración de etapa sobre el repositorio existente.

---

## Testing Contract

```json
{
  "version": 1,
  "methodology": "test-after",
  "source": "org",
  "ordering": "implement each applicable testable layer, then write and run",
  "scope": "bugfix",
  "test_strategy": "minimal",
  "project_type": "brownfield",
  "applicable_notes": [
    {
      "layer": "org",
      "text": "We treat tests as a first-class deliverable in every Bolt. The specific\nmethodology (TDD, BDD, ATDD, or classic test-after) is affirmed at\npractices-discovery and recorded in `team.md` under this heading with explicit\n`Methodology` and `Ordering` fields; Code Generation resolves those fields\nindependently from coverage, tooling, and scope notes.\n\nWhen no posture has been affirmed, our default per scope is:\n- **Methodology**: test-after\n- **Ordering**: implement each applicable testable layer, then write and run\n  that layer's tests.\n- `mvp`, `enterprise`, `feature`, `infra`, `classic` add an 80% line-coverage\n  floor and CI execution before merge.\n- `bugfix`, `security-patch` add a targeted regression for the specific\n  bug/vulnerability and require the existing suite to remain green.\n- `express` uses the Minimal strategy: requirement-driven unit tests (one per\n  requirement, with a happy-path floor per component); existing tests remain\n  green.\n- `poc`, `refactor`, `workshop` add no extra new-test floor and require the\n  existing suite to remain green.\n\nThe active `Test Strategy` still applies in every scope and determines test\nvolume/types. Scope floors are additive; they never reduce or replace the\nselected strategy.\n\nBuild and Test verifies defined coverage floors and affirmed quality targets;\nthey may not be weakened to make a step pass.\n\nAffirm a stricter posture in `team.md` if the team commits to one."
    }
  ],
  "obligations": {
    "strategy": "minimal",
    "strategy_volume": [
      "One verifiable test per requirement at the narrowest effective level.",
      "At least one happy-path unit test per component.",
      "Unit tests are the default; a bugfix/security scope floor may require an integration or E2E regression when that is the narrowest level that reproduces the defect."
    ],
    "scope_floor": [
      "Include a targeted regression for the bug or vulnerability.",
      "Keep the existing test suite green."
    ],
    "combination_rule": "Apply every selected-strategy obligation and every scope-floor obligation; neither replaces the other, and a targeted scope regression may add the narrowest necessary test type beyond the strategy default."
  },
  "plan_profile": {
    "methodology": "test-after",
    "runner_step": "Verify the existing test runner/configuration and record the exact unit-scoped command.",
    "runner_ready_before_first_test": true,
    "testable_layers": [
      "Data model / database behavior",
      "Repository / data access",
      "Business logic",
      "API / endpoint",
      "Frontend behavior"
    ],
    "steps": [
      "Project structure and production configuration skeleton.",
      "Verify the existing test runner/configuration and record the exact unit-scoped command.",
      "Data model / database behavior - implement.",
      "Data model / database behavior - write and run its tests after implementation.",
      "Repository / data access - implement.",
      "Repository / data access - write and run its tests after implementation.",
      "Business logic - implement.",
      "Business logic - write and run its tests after implementation.",
      "API / endpoint - implement.",
      "API / endpoint - write and run its tests after implementation.",
      "Frontend behavior - implement.",
      "Frontend behavior - write and run its tests after implementation.",
      "Environment/build configuration.",
      "Documentation and traceability."
    ]
  },
  "input_sha256": "sha256:933f32e0a09112bec79e6ad9d9b07d8fa79b601a7b325fbe1f9a283f191ae54c",
  "contract_sha256": "sha256:99478f464d6c420b35c11685f3bcb32edde86a8c85b8236e74f7cf898a3796b8"
}
```

---

## Cobertura de pruebas — qué cambia en este bucle de corrección

El contrato de arriba impone dos obligaciones de piso para el alcance `bugfix`:
una **regresión dirigida al fallo** y que **la suite existente siga en verde**.
La versión anterior de este plan no podía cumplir ninguna de las dos por
completo, y lo declaraba abiertamente en lugar de rebajar el objetivo. Este
bucle las cierra.

**Lo que ya estaba cubierto (pasos 1-12, sin cambios).** Las regresiones de
TD-11 y TD-12 son de tabla de rutas: se comprueban resolviendo la URL contra el
enrutador de Laravel, sin tocar la base de datos. Están escritas en
`tests/Feature/AdminUsuariosRutasTest.php`, corren en verde, y se comprobó que
fallan (3 fallos) contra el código con el defecto.

**Lo que faltaba y este bucle añade.** Las regresiones de TD-8 y TD-16 necesitan
filas reales en `usuarios`, `servicios`, `pacientes` y `citas`, más el
comportamiento de las claves foráneas `ON DELETE CASCADE`. El esquema de esas
tablas vive únicamente en `database/dentissa.sql`, y `phpunit.xml` apuntaba a
SQLite en memoria, donde además las claves foráneas están desactivadas por
defecto. El paso 13 levanta una base MariaDB de pruebas cargada desde ese mismo
volcado; los pasos 14 y 15 escriben las regresiones contra ella.

**El fallo preexistente de la suite.** `Tests\Feature\ExampleTest` afirma que
`GET /` devuelve 200, pero la raíz de este proyecto siempre redirige por diseño
(`routes/web.php` línea 31). La prueba nunca fue válida aquí: es andamiaje que
trae Laravel y que este proyecto no adaptó. El paso 16 la reescribe para que
afirme el comportamiento real y verificado —un visitante anónimo es redirigido a
`/login`—. **Esto no es una rebaja de umbral**: la aserción nueva describe lo que
la aplicación hace, no una expectativa relajada. La distinción importa y por eso
la decisión la tomó el humano, no el agente.

### Decisiones anteriores que este bucle reabre — léelo antes de aprobar

Este arreglo contradice dos afirmaciones de `requirements.md` que fueron
aprobadas en su momento:

- **Restricción C2** — «Por decisión registrada (Q4 = D), esta corrección **no**
  añade pruebas automáticas: la verificación es la lista manual de FR5.1.»
- **§ 6 Fuera de alcance** — «Pruebas automáticas de funcionalidad sobre estos
  módulos — decisión Q4 = D.»

El humano ha reabierto esa decisión de forma explícita al elegir `Retry with fix`
con el arreglo A + C, sabiendo que eso es lo que hacía. Lo dejo escrito aquí en
vez de proceder en silencio: si prefieres que `requirements.md` se actualice para
reflejarlo antes de generar código, elige **Request Changes** y volvemos a
Requirements Analysis primero.

Lo que **no** cambia: la restricción **C1** («las correcciones no deben requerir
cambios de esquema») se respeta íntegra. No se escriben migraciones del dominio,
no se toca `database/dentissa.sql` ni `database/migrations/`, y el esquema de
pruebas es una copia cargada del volcado existente. La opción B (migraciones de
Laravel) se descartó precisamente por chocar con C1.

La lista de comprobación manual de 19 puntos **se conserva**: cubre la capa de
interfaz, que sigue sin ejecutor de pruebas, y sirve de verificación de extremo a
extremo por encima de las pruebas nuevas.

---

## Pasos de implementación

Orden `test-after`: se implementa cada capa aplicable y después se escriben y
ejecutan sus pruebas. Las capas *Repository / data access* y
*Environment/build configuration* del perfil no aplican a este repositorio y se
omiten explícitamente (los controladores usan Eloquent y el facade `DB`
directamente; no hay capa de repositorio ni cambios de configuración de
construcción).

### Paso 1 — Estructura del proyecto y configuración de producción

- [x] **No aplica.** Brownfield sin componentes nuevos: todos los archivos que se
  tocan ya existen. No se crea ninguna estructura ni configuración de producción.

### Paso 2 — Verificar el ejecutor de pruebas y registrar el comando

- [x] Confirmar que `vendor/bin/phpunit` es ejecutable y que la suite actual
  (`tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`) corre en verde
  **antes** de tocar nada. Esta es la línea base de regresión que exige el
  salvaguarda brownfield.
- [x] Registrar el comando exacto acotado a esta corrección en
  `unit-test-instructions.md` (ya escrito allí, se verifica que funcione).
- Trazabilidad: FR5.2, NFR5

> **Resultado observado (2026-09-08, ejecución de la etapa).** El paso se
> ejecutó, pero **la línea base no estaba en verde**. Con los cambios de esta
> corrección apartados (`git stash`), `vendor/bin/phpunit` dentro del contenedor
> `app` devolvió `Tests: 2, Assertions: 2, Failures: 1`:
> `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response`
> espera 200 y recibe 302. La causa es ajena a esta corrección: la ruta raíz
> (`routes/web.php`, línea 31) siempre redirige — al panel del rol si hay sesión
> y a `/login` si no la hay —, así que el ejemplo que trae Laravel lleva fallando
> desde que se escribió esa ruta. `tests/Unit/ExampleTest.php` sí pasa.
> No se ha tocado ese archivo de prueba: ajustarlo para que espere 302 haría
> pasar la suite bajando el listón, y queda fuera del alcance de esta corrección.
> El piso de `bugfix` se lee, por tanto, como «sin regresiones respecto a esta
> línea base», y así se ha verificado.
>
> Nota de entorno: `vendor/` está instalado para PHP >= 8.4 (la imagen del
> repositorio es `php:8.4-fpm`), y el PHP del host es 8.2.12, así que ni
> `vendor/bin/phpunit` ni `php artisan` arrancan fuera de Docker. Las tres
> ejecuciones se hicieron con `docker compose exec app`.

### Paso 3 — Capa de modelo de datos

- [x] **No aplica, verificado.** No hay cambios de esquema (restricción **C1**) y
  los modelos ya admiten los campos necesarios: `App\Models\Servicio::$fillable`
  incluye `precio` y `color_agenda`; `App\Models\Usuario::$fillable` incluye
  `rol` y `activo`. No se modifica ningún modelo.
- Trazabilidad: C1

### Paso 4 — Lógica de negocio: `ServiciosController` (TD-8)

En `app/Http/Controllers/ServiciosController.php`:

- [x] En `store()`: añadir `'precio' => 'required|numeric|min:0|max:99999999.99'` y
  `'color_agenda' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/'` a la llamada
  `$request->validate()` existente.
- [x] En `store()`: sustituir `$data = $request->all()` por el array validado, y
  **eliminar** las líneas `$data['precio'] = 0;` y
  `$data['color_agenda'] = '#F4C0D1';`. Conservar `$data['id']`,
  `$data['activo'] = 1` y `$data['creado_en'] = now()`.
- [x] En `update()`: añadir las mismas dos reglas de validación.
- [x] En `update()`: sustituir `$data = $request->all()` por el array validado y
  **eliminar** la línea `$data['precio'] = 0;`. Conservar el manejo del checkbox
  `$data['activo'] = $request->has('activo') ? 1 : 0;`.
- [x] Añadir mensajes de validación en español coherentes con el resto del
  sistema (`PacientesController::store()` es el modelo a seguir).
- Trazabilidad: FR2.4, FR2.5, FR2.6, FR2.7, NFR2, NFR3

### Paso 5 — Lógica de negocio: `UsuarioController`, validación y contraseña (TD-16)

En `app/Http/Controllers/UsuarioController.php`:

- [x] En `store()`: añadir `$request->validate()` con
  `'nombre' => 'required|string|max:100'`,
  `'apellido' => 'required|string|max:100'`,
  `'email' => 'required|email|unique:usuarios,email'`,
  `'telefono' => 'nullable|string|max:20'` (el patrón único ya usado en
  `PacientesController`, `PersonalController`, `PerfilController` y
  `RegisterController`),
  `'password' => 'nullable|string|min:6'` y
  `'rol' => 'required|in:cliente,asistente,dentista,admin'`.
  Mensaje personalizado `'email.unique' => 'Este correo ya está registrado en el sistema.'`,
  copiado literalmente de `PacientesController::store()` línea 106.
- [x] En `store()`: sustituir `bcrypt('password123')` por el patrón ya presente en
  `PacientesController::store()` líneas 110 y 122 — usar la contraseña enviada si
  viene informada, y `Str::random(10)` si viene vacía, hasheada con `Hash::make()`.
  Añadir los `use` correspondientes.
- [x] En `update()`: añadir la misma validación, con
  `'email' => ['required','email', Rule::unique('usuarios','email')->ignore($id)]`
  para no chocar con el propio registro.
- [x] En `update()`: escribir también `'actualizado_en' => now()`, ya que
  `App\Models\Usuario` declara `public $timestamps = false` y hoy la columna no
  se actualiza nunca.
- Trazabilidad: FR3.1, FR3.2, FR3.3, FR3.6, NFR1, NFR3, NFR4

### Paso 6 — Lógica de negocio: baja lógica de usuarios (TD-16)

En `app/Http/Controllers/UsuarioController.php`:

- [x] Añadir `toggleActivo(string $id)` siguiendo literalmente el patrón de
  `PersonalController::toggleActivo()` (líneas 318-330): buscar el usuario,
  `abort(404)` si no existe, invertir `activo`, escribir `actualizado_en`, y
  `return back()->with('success', ...)` con mensaje «Usuario desactivado.» /
  «Usuario reactivado.».
- [x] **Eliminar** el método `destroy()` por completo.
- Trazabilidad: FR4.1, FR4.6, FR4.5, NFR2, NFR3, NFR4

### Paso 7 — Pruebas de la capa de lógica de negocio

- [x] **Cobertura automática no disponible en esta corrección.** Los tres cambios
  anteriores solo son observables contra filas reales de `usuarios` y
  `servicios`. Ver § "Brecha de cobertura de pruebas". Quedan cubiertos por los
  puntos 2 a 7 de la lista de comprobación manual.
- Trazabilidad: FR5.1, OQ2

### Paso 8 — Capa de rutas (TD-11, TD-12, y ruta de alternancia)

En `routes/web.php`, dentro del grupo `admin`:

- [x] **Eliminar** la línea 88, el closure muerto
  `Route::get('/usuarios', fn() => view('placeholder', ...))->name('usuarios')`.
  Verificado: ninguna vista ni controlador usa la ruta nombrada `admin.usuarios`
  a secas; todas las referencias son `admin.usuarios.index`, `.create`, `.show`,
  `.edit`, `.store`, `.update` o `.destroy`. (La línea 87, `admin.reportes`, es un
  placeholder distinto y **no** se toca.)
- [x] **Reordenar**: registrar `GET /usuarios/crear` **antes** que
  `GET /usuarios/{id}`.
- [x] Añadir `->where('id', '[0-9a-fA-F-]{36}')` a las tres rutas con `{id}`
  (`usuarios.show`, `usuarios.edit`, `usuarios.update`) para que ningún segmento
  literal futuro vuelva a caer en el parámetro.
- [x] Añadir `Route::patch('/usuarios/{id}/toggle', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle')`,
  con la misma forma y nombre que `admin.personal.toggle` (línea 83).
- [x] **Eliminar** `Route::delete('/usuarios/{id}', ...)->name('usuarios.destroy')`.
- Trazabilidad: FR1.1, FR1.2, FR1.3, FR1.4, FR4.2, FR4.5

### Paso 9 — Interfaz: vistas de usuarios y servicios

- [x] `resources/views/usuarios/create.blade.php`: añadir
  `<option value="asistente">Asistente</option>` al selector de rol
  (líneas 71-77), con el mismo ternario `selected` que las otras tres. Esto
  arregla a la vez el alta y la edición, porque
  `resources/views/usuarios/edit.blade.php` es un `@include('usuarios.create')`
  de cinco líneas.
- [x] `resources/views/usuarios/index.blade.php`: sustituir el botón de papelera
  (línea 163) por un botón de alternancia que muestre «Desactivar» o «Activar»
  según `$m->activo`.
- [x] `resources/views/usuarios/index.blade.php`: reescribir el modal
  (líneas 202-229) — título, texto («Esta acción no se puede deshacer» ya no es
  cierta), botón, y el `@method('DELETE')` pasa a `@method('PATCH')` apuntando a
  `admin.usuarios.toggle`. Ajustar el JavaScript de las líneas 231-245 en
  consecuencia.
- [x] `resources/views/servicios/create.blade.php`: añadir campo
  `<input type="number" name="precio" step="0.01" min="0" required>` y
  `<input type="color" name="color_agenda" value="#F4C0D1">`, con las clases
  `field` / `field-label` que ya usa la vista.
- [x] `resources/views/servicios/edit.blade.php`: los mismos dos campos,
  precargados con `{{ $servicio->precio }}` y `{{ $servicio->color_agenda }}`.
- [x] Añadir `data-testid` a los elementos interactivos nuevos (botón de
  alternancia, campo de precio, selector de color, opción de rol).
- Trazabilidad: FR2.1, FR2.2, FR2.3, FR3.4, FR3.5, FR3.7, FR4.3, FR4.4

### Paso 10 — Pruebas de la capa de rutas (regresión dirigida)

- [x] Crear `tests/Feature/AdminUsuariosRutasTest.php` con las regresiones que sí
  son ejecutables hoy:
  - `/admin/usuarios/crear` resuelve a `UsuarioController@create` y **no** a
    `@show` — la regresión directa de TD-11.
  - `/admin/usuarios` resuelve a `UsuarioController@index` y **no** al closure
    del placeholder — la regresión directa de TD-12.
  - La ruta nombrada `admin.usuarios.destroy` ya no existe y
    `admin.usuarios.toggle` sí — comprueba FR4.2 y FR4.5.
  - Caso límite: un identificador que no tiene forma de UUID no resuelve a
    `@show` — comprueba FR1.2.
  Estas pruebas inspeccionan la tabla de rutas de Laravel; no tocan la base de
  datos y corren con `phpunit.xml` tal como está.
- [x] Ejecutar el comando acotado de `unit-test-instructions.md` y dejarlo en
  verde.
- [x] Ejecutar la suite completa y confirmar que los dos ejemplos de Laravel
  siguen pasando.
- Trazabilidad: FR1.1, FR1.2, FR1.3, FR4.2, FR4.5, FR5.2, NFR5

> **Resultado observado (2026-09-08, ejecución de la etapa).**
>
> - Comando acotado, con la corrección aplicada: `OK (4 tests, 8 assertions)`.
> - Suite completa: `Tests: 6, Assertions: 10, Failures: 1` — el único fallo es
>   el `Tests\Feature\ExampleTest` que ya fallaba en la línea base (ver Paso 2).
>   `Tests\Unit\ExampleTest` sigue pasando. Cero regresiones nuevas.
> - Comprobación de que las pruebas no son complacientes: con `routes/web.php`
>   revertido a su estado con el fallo, el archivo devuelve
>   `Tests: 4, Assertions: 6, Failures: 3`.
>
> **Corrección a este plan.** Tres de las cuatro pruebas fallan contra el código
> con el fallo, como se esperaba. La cuarta, la del listado, **no** — y por eso
> deja de describirse arriba como «la regresión directa de TD-12». Motivo
> verificado empíricamente: el closure muerto y `UsuarioController@index`
> compartían método y URI; Laravel indexa las rutas por método+URI y descarta la
> anterior, y tras el arranque reconstruye la lista de nombres a partir de ese
> índice, de modo que el closure ya no era alcanzable **ni por URL ni por
> nombre** (`Route::has('admin.usuarios')` ya devolvía `false` antes del
> cambio). TD-12 era código muerto y confuso, no una ruta que se impusiera al
> controlador; la pantalla de marcador de posición nunca llegaba a servirse.
> Eliminarlo sigue siendo lo correcto por FR1.3, pero su regresión no es
> observable desde el enrutador. La prueba se conserva como fijación del estado
> correcto y se le añadieron dos aserciones de nombre (ausencia de
> `admin.usuarios`, presencia de `admin.usuarios.index`), que cubren FR1.4.

### Paso 11 — Pruebas de la capa de interfaz

- [x] **No aplica.** El proyecto no tiene ejecutor de pruebas de interfaz
  (`package.json` solo trae Vite; no hay Vitest, Jest ni Dusk). Introducir uno
  excede el alcance de esta corrección. Los cambios de vista quedan cubiertos por
  la lista de comprobación manual, puntos 1 a 8.
- Trazabilidad: FR5.1

### Paso 12 — Documentación y trazabilidad

- [x] Escribir `source-manifest.json` con todas las rutas de código que esta
  etapa crea, modifica o borra.
- [x] Escribir `code-summary.md`: archivos tocados, decisiones, cobertura, y
  cualquier desviación de este plan.
- [x] Escribir `traceability.json` mapeando cada `FR` y `NFR` a su archivo real.
- Trazabilidad: todas

---

## Pasos del bucle de corrección 1 (arreglo A + C)

Misma metodología `test-after`: primero queda la capa implementada (pasos 4-9,
ya aplicados y revisados), después se escriben y ejecutan sus pruebas. El paso 13
es el paso de «preparar el ejecutor» que el perfil del contrato exige **antes**
del primer paso de prueba ejecutable: sin él, los pasos 14 y 15 no tienen dónde
correr.

### Paso 13 — Base MariaDB de pruebas (arreglo A, infraestructura del ejecutor)

- [x] Crear `database/testing/bootstrap-test-db.sh`: un script idempotente que
  cree la base `dentissa_test` en el contenedor `db` ya existente y cargue
  `database/dentissa.sql` dentro de ella. **No** se añade un servicio nuevo a
  `docker-compose.yml`: el contenedor `mariadb:10.11` actual sirve las dos bases,
  lo que evita un contenedor más y mantiene el arreglo en «una base adicional»
  en vez de «infraestructura nueva». El script debe poder ejecutarse dos veces
  seguidas sin fallar (`CREATE DATABASE IF NOT EXISTS` más recarga limpia).
- [x] Modificar `phpunit.xml`: sustituir las dos líneas de SQLite en memoria
  (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) por la conexión `mariadb`
  apuntando a `dentissa_test` (`DB_HOST=db`, `DB_PORT=3306`,
  `DB_DATABASE=dentissa_test`, `DB_USERNAME=root`, `DB_PASSWORD=root`). Laravel
  conecta de forma perezosa, así que las cuatro pruebas de tabla de rutas del
  paso 10 siguen pasando aunque la base no esté levantada.
- [x] **Usar el trait `DatabaseTransactions`, nunca `RefreshDatabase`.** El
  segundo ejecutaría las migraciones de Laravel y dejaría la base sin las tablas
  del dominio, que no vienen de migraciones sino del volcado.
  `DatabaseTransactions` envuelve cada prueba en una transacción y la revierte,
  de modo que el esquema y los datos cargados sobreviven intactos entre pruebas.
- [x] **No se toca `database/dentissa.sql` ni `database/migrations/`.** La
  restricción **C1** queda intacta: no hay cambios de esquema, solo una copia
  cargada del esquema que ya existe.
- [x] Documentar el arranque en `unit-test-instructions.md`, incluido el hecho de
  que `dentissa_test` es un prerrequisito de las pruebas de dominio.
- Trazabilidad: FR5.2, OQ2, C1

> **Resultado observado (2026-09-13, bucle de corrección 1).** El script se
> ejecutó dos veces seguidas contra el contenedor `db` en marcha y terminó con
> código 0 en ambas: `dentissa_test` queda con las 19 tablas y las dos vistas
> del volcado. La idempotencia se logra con `DROP DATABASE IF EXISTS` seguido de
> `CREATE DATABASE IF NOT EXISTS` —el volcado declara sus tablas sin
> `IF NOT EXISTS`, así que recargarlo sobre una base poblada fallaría—, más una
> comprobación final de que las seis tablas del dominio responden a una consulta.
> La carga se hace con el cliente **dentro** del contenedor `db`: el repositorio
> no está montado ahí, así que el volcado entra por `stdin`, y la conexión local
> resuelve a `root@localhost`, que es el `DEFINER` que las dos vistas declaran.
>
> La conexión perezosa se verificó explícitamente: con la base inalcanzable
> (`docker compose exec -e DB_HOST=base-inexistente app`),
> `AdminUsuariosRutasTest` devuelve `OK (4 tests, 8 assertions)` mientras que
> `ServiciosPrecioTest` devuelve `Tests: 4, Errors: 4`. Las pruebas de tabla de
> rutas siguen sin necesitar Docker; las de dominio sí lo necesitan.


### Paso 14 — Pruebas de dominio de TD-8 (precio y color de servicios)

Crear `tests/Feature/ServiciosPrecioTest.php`, con `DatabaseTransactions` y
fixtures creados dentro de la propia prueba (no se depende de filas concretas del
volcado, solo de su esquema):

- [x] **Regresión directa de TD-8**: crear un servicio con precio conocido,
  editarlo cambiando **solo la duración**, y afirmar que el precio sobrevive.
  Contra el código con el defecto esta prueba debe fallar.
- [x] El alta guarda el precio y el `color_agenda` escritos, no `0` ni `#F4C0D1`.
- [x] Un precio negativo se rechaza con error de validación y el servicio no se
  modifica.
- [x] Ejecutar el archivo y dejarlo en verde; después revertir temporalmente
  `ServiciosController` a su estado con el defecto y comprobar que las pruebas
  fallan (que no son complacientes), como se hizo en el paso 10.
- Trazabilidad: FR2.4, FR2.5, FR2.6, FR2.7, NFR3

> **Resultado observado (2026-09-13, bucle de corrección 1).** `OK (4 tests, 21
> assertions)`. Son **cuatro** pruebas, no las tres que enumeran las viñetas: se
> añadió `test_un_color_de_agenda_invalido_se_rechaza` como segundo caso de
> error, porque la guardarraíl de fase de Construction exige camino feliz más dos
> casos de error o borde por archivo de prueba. Es una desviación estrictamente
> aditiva; no sustituye ni rebaja ninguna de las tres planificadas.
>
> Comprobación de que no son complacientes: con `ServiciosController` revertido a
> su estado con el defecto, el archivo devuelve
> `Tests: 4, Assertions: 11, Failures: 4`. Fallan las cuatro.


### Paso 15 — Pruebas de dominio de TD-16 (validación, rol y baja lógica)

Crear `tests/Feature/UsuariosAdminTest.php`, mismas condiciones:

- [x] **Regresión de elevación de privilegios**: crear un usuario con rol
  `asistente`, actualizarlo enviando **solo un teléfono nuevo**, y afirmar que su
  rol sigue siendo `asistente`. Este es el fallo de NFR1.
- [x] Un alta con un correo que ya existe se rechaza con error de validación y no
  crea un segundo usuario.
- [x] Un alta sin contraseña genera un hash que **no** es el de `password123`, y
  dos altas sin contraseña generan hashes distintos entre sí.
- [x] **Regresión de pérdida de datos clínicos**: crear un usuario `cliente` con
  ficha de paciente, una cita y un expediente; invocar `toggleActivo`; afirmar
  que responde sin error, que `usuarios.activo` queda en 0, y que las filas de
  `pacientes`, `citas` y `expedientes` siguen existiendo. Invocarlo otra vez y
  afirmar que `activo` vuelve a 1. Este es el fallo de NFR2.
- [x] Ejecutar el archivo, dejarlo en verde, y comprobar contra el código con el
  defecto que falla.
- Trazabilidad: FR3.2, FR3.3, FR3.6, FR4.1, FR4.5, FR4.6, NFR1, NFR2, NFR3

> **Resultado observado (2026-09-13, bucle de corrección 1).** `OK (5 tests, 33
> assertions)`.
>
> Una precisión sobre la primera prueba, porque cambia lo que demuestra. Afirmar
> solo «el rol sigue siendo `asistente`» tras enviar únicamente el teléfono
> **habría sido complaciente**: contra el código con el defecto esa petición
> revienta contra la base (`Column 'nombre' cannot be null`) y, justamente por
> reventar, deja el rol intacto. La prueba pasaría en los dos estados. La
> aserción que sí discrimina es `assertSessionHasErrors`: el código corregido
> rechaza la petición en la validación, el defectuoso no valida nada. Se añadió
> además un segundo escenario que reproduce el vector real de escalada —que el
> desplegable de rol ofrezca `asistente` y lo traiga preseleccionado—, porque el
> defecto se disparaba en la vista: sin esa opción el navegador enviaba la
> primera del desplegable, `admin`.
>
> Comprobación de que no son complacientes: con `UsuarioController` revertido a
> su estado con el defecto, el archivo devuelve
> `Tests: 5, Assertions: 11, Failures: 5`. Fallan las cinco.


### Paso 16 — Corregir la prueba de ejemplo de Laravel (arreglo C)

- [x] Reescribir `test_the_application_returns_a_successful_response` en
  `tests/Feature/ExampleTest.php` para que afirme el comportamiento real y
  verificado: una petición anónima a `/` recibe una redirección a `/login`.
  Renombrar el método a algo que describa eso
  (`test_anonymous_visitor_is_redirected_to_login`).
- [x] **No se relaja ningún umbral.** La aserción anterior (200) nunca describió
  esta aplicación: `routes/web.php` línea 31 redirige siempre, con sesión o sin
  ella. Cambiar la aserción a «redirige a `/login`» es corregir una prueba
  equivocada, no bajar el listón; la diferencia es que la aserción nueva falla si
  la aplicación deja de redirigir.
- [x] No se toca `tests/Unit/ExampleTest.php`, que pasa y es correcto.
- Trazabilidad: FR5.2, NFR5

> **Resultado observado (2026-09-13, bucle de corrección 1).** La suite completa
> queda en `OK (15 tests, 65 assertions)`, sin el fallo de `ExampleTest` que el
> proyecto arrastraba. El comando acotado de `unit-test-instructions.md` devuelve
> `OK (13 tests, 62 assertions)`.
>
> Contraprueba con los dos controladores revertidos a su estado con el defecto:
> `Tests: 15, Assertions: 33, Failures: 9`. Las nueve pruebas de dominio nuevas
> fallan; las cuatro de tabla de rutas y las dos de ejemplo siguen pasando,
> porque `routes/web.php` y las vistas no se revirtieron.


### Paso 17 — Actualizar documentación y trazabilidad

- [x] Regenerar `source-manifest.json` con las rutas nuevas (`phpunit.xml`,
  `database/testing/bootstrap-test-db.sh`, los dos archivos de prueba nuevos y
  `tests/Feature/ExampleTest.php`) además de las ocho ya declaradas.
- [x] Actualizar `code-summary.md` con lo que añade este bucle y cualquier
  desviación.
- [x] Actualizar `traceability.json`: los requisitos que hasta ahora apuntaban a
  la lista manual pasan a apuntar a su archivo de prueba real.
- Trazabilidad: todas

---

## Archivos que se van a tocar

| Archivo | Acción | Requisitos |
|---|---|---|
| `routes/web.php` | Modificar | FR1.1–FR1.4, FR4.2, FR4.5 |
| `app/Http/Controllers/UsuarioController.php` | Modificar | FR3.1–FR3.3, FR3.6, FR4.1, FR4.5, FR4.6 |
| `app/Http/Controllers/ServiciosController.php` | Modificar | FR2.4–FR2.7 |
| `resources/views/usuarios/create.blade.php` | Modificar | FR3.4, FR3.5, FR3.7 |
| `resources/views/usuarios/index.blade.php` | Modificar | FR4.3, FR4.4 |
| `resources/views/servicios/create.blade.php` | Modificar | FR2.1, FR2.3 |
| `resources/views/servicios/edit.blade.php` | Modificar | FR2.2, FR2.3 |
| `tests/Feature/AdminUsuariosRutasTest.php` | Crear | FR1.1–FR1.3, FR4.2, FR4.5 |

Añadidos por el bucle de corrección 1:

| Archivo | Acción | Requisitos |
|---|---|---|
| `phpunit.xml` | Modificar | FR5.2, OQ2 |
| `database/testing/bootstrap-test-db.sh` | Crear | FR5.2, OQ2 |
| `tests/Feature/ServiciosPrecioTest.php` | Crear | FR2.4–FR2.7, NFR3 |
| `tests/Feature/UsuariosAdminTest.php` | Crear | FR3.2, FR3.3, FR3.6, FR4.1, FR4.5, FR4.6, NFR1, NFR2 |
| `tests/Feature/ExampleTest.php` | Modificar | FR5.2, NFR5 |

Ningún archivo se duplica ni se renombra: todo se modifica en el sitio.
No se toca `database/dentissa.sql`, `database/migrations/`,
`tests/Unit/ExampleTest.php`, ni las vistas SQL `v_citas_hoy` y
`v_historial_paciente` (restricciones C1 y C6). `phpunit.xml` sí se modifica en
este bucle, y esa es una desviación deliberada respecto al plan original,
autorizada por la elección humana del arreglo A.

---

## Riesgos

- **Vistas compiladas en caché.** `storage/framework/views/` contiene versiones
  compiladas de `usuarios/index.blade.php` que aún referencian
  `admin.usuarios.destroy`. Tras eliminar esa ruta hay que limpiar la caché
  (`php artisan view:clear`), o la primera carga fallará. Se incluye en la lista
  de comprobación manual.
- **Servicios con precio 0 ya guardados.** Si alguien editó un servicio antes de
  esta corrección, su precio ya se perdió. Este cambio detiene la pérdida pero no
  restaura valores anteriores; los precios originales están en
  `database/dentissa.sql` si hiciera falta recuperarlos a mano.
- **`color_agenda` obligatorio en el alta.** Al hacerlo `required` en `store()`,
  un envío del formulario sin ese campo (por ejemplo desde una petición
  construida a mano) será rechazado. El formulario siempre lo enviará porque el
  `<input type="color">` tiene valor por defecto.

Riesgos añadidos por el bucle de corrección 1:

- **El volcado pasa a ser dependencia de las pruebas.** `database/dentissa.sql`
  deja de ser solo la semilla de desarrollo y se convierte en la fuente del
  esquema de pruebas. Si alguien cambia el esquema sin regenerar el volcado, las
  pruebas de dominio dejan de reflejar la realidad. Es el riesgo principal de la
  opción A y es de mantenimiento, no de producción.
- **Las pruebas de dominio exigen Docker levantado.** Tras el paso 13, ejecutar
  la suite completa sin el contenedor `db` en marcha hace fallar las pruebas
  nuevas por error de conexión. Las cuatro pruebas de tabla de rutas siguen
  pasando sin base de datos porque Laravel conecta de forma perezosa. El
  prerrequisito queda documentado en `unit-test-instructions.md`.
- **`DatabaseTransactions` frente a DDL.** El trait revierte por transacción; una
  sentencia DDL dentro de una prueba provocaría un commit implícito en MariaDB y
  dejaría residuos. Las pruebas planificadas solo hacen DML, así que no aplica,
  pero conviene no introducir DDL en ellas más adelante.
- **`phpunit.xml` afecta a toda la suite.** El cambio de conexión es global, no
  por archivo. Es intencionado —es lo que permite escribir pruebas de dominio—
  pero significa que cualquier prueba futura hereda la base `dentissa_test`.

## Review

**Verdict:** READY
**Reviewer:** aidlc-architecture-reviewer-agent
**Iteration:** 1

### Hallazgos

| ID | Severity | Finding | Evidence | Status |
|---|---|---|---|---|
| R-01 | Minor | El plan reabre explícitamente la decisión Q4=D / restricción C2 de `requirements.md` (que decía que esta corrección no añadiría pruebas automáticas), pero `requirements.md` en sí no se ha tocado: sigue diciendo, sin matizar, que la verificación es solo la lista manual de FR5.1 y que las pruebas automáticas están «fuera de alcance». | `requirements.md` líneas 273-278 y 312-316 (§4 C2, §6 Fuera de alcance) frente a `code-generation-plan.md` § "Decisiones anteriores que este bucle reabre" (líneas 124-148), que declara la divergencia y ofrece expresamente `Request Changes` para volver a Requirements Analysis primero. | New |
| R-02 | Minor | `traceability.json` no lista `phpunit.xml`, `database/testing/bootstrap-test-db.sh` ni los tres archivos de prueba nuevos/modificados como `upstream_ids` propios, aunque `source-manifest.json` sí los declara como rutas de código de esta etapa; solo aparecen como `target`/`tests` de otros requisitos. No es un hallazgo de exactitud (cada entrada de `coverage` que cité resolvió a código real que hace lo que dice), sino de que la trazabilidad no deja un registro explícito de por qué esos cinco archivos entran al manifiesto sin requisito propio (son infraestructura de prueba, no funcionalidad). | `traceability.json` completo comparado con `source-manifest.json`. | New |

### Resultados de comprobaciones

**Ejecutado por este revisor** (solo lectura, sin escritura al árbol de trabajo):
- `git diff` sobre las 8 rutas de código de aplicación del manifiesto original (`routes/web.php`, `UsuarioController.php`, `ServiciosController.php`, las cuatro vistas Blade) — confirmado: el diff real coincide exactamente con lo que el plan describe en los pasos 4-9, sin cambios fuera de manifiesto.
- Lectura completa de `bootstrap-test-db.sh`: usa variables `TEST_DB_NAME` con valor por defecto `dentissa_test`, hace `DROP DATABASE IF EXISTS`/`CREATE DATABASE IF NOT EXISTS` solo sobre esa variable — no hay ninguna ruta de código que pueda apuntar a `dentissa` salvo que alguien sobrescriba explícitamente `TEST_DB_NAME=dentissa`, y eso está documentado como opción, no como comportamiento por defecto.
- Lectura de `phpunit.xml`: la conexión `mariadb`/`dentissa_test` solo se activa bajo `APP_ENV=testing`; no afecta a la base de desarrollo.
- Lectura completa de `UsuariosAdminTest.php` y `ServiciosPrecioTest.php`: verificado que `test_la_baja_logica_conserva_el_expediente` crea de verdad `pacientes`, `citas` y `expedientes` vía `DB::table(...)->insert()` (no fixtures del volcado) y afirma con `assertSame(1, ...->count())` sobre las tres tablas tras `toggleActivo`, más una segunda invocación que confirma la reactivación — la prueba comprueba lo que dice.
- Lectura de `UsuarioController::update()`: confirmado el razonamiento del desarrollador sobre la prueba de elevación de privilegios — `update()` valida `nombre`, `apellido`, `email` y `rol` como `required`; una petición que solo envía `telefono` falla la validación de servidor y Laravel nunca llega a tocar la fila, así que una aserción que solo comprobara "el rol sigue siendo `asistente`" pasaría igual de complaciente contra el código con el defecto (que revienta contra la base antes de escribir). `assertSessionHasErrors(['nombre','apellido','email','rol'])` es la aserción que de verdad discrimina, y la prueba la incluye.
- Lectura de `AdminUsuariosRutasTest.php`: las cuatro pruebas de tabla de rutas coinciden con lo documentado; la advertencia sobre TD-12 (código muerto no observable por enrutador) está registrada de forma correcta y no complaciente — se sustituyó por aserciones de nombre de ruta.
- Comparación cruzada `source-manifest.json` (13 rutas) contra `git status`/`git diff`: las 8 rutas de aplicación y los 5 archivos de prueba/infraestructura del bucle de corrección coinciden exactamente con los archivos modificados/nuevos; ningún cambio real fuera de manifiesto, ninguna ruta reclamada sin cambio real.
- Cotejo de `traceability.json` contra `requirements.md` §2-3: los 25 FR y 5 NFR del manifiesto de requisitos aparecen todos en `coverage`, cada `target` señala un archivo real que sí implementa lo reclamado (verificado por lectura directa de cada controlador/vista citado).
- Verificación de la restricción C1: ningún archivo de `database/migrations/` ni `database/dentissa.sql` aparece en el diff ni en el manifiesto; el script de arranque de pruebas solo carga una copia del volcado existente.

**Citado como evidencia ya recogida por el conductor** (no repetido):
- `bash database/testing/bootstrap-test-db.sh` — código de salida 0, `dentissa_test` cargada.
- `docker compose exec -T app vendor/bin/phpunit` — `OK (15 tests, 65 assertions)`.
- Comando acotado (3 archivos de esta corrección) — `OK (13 tests, 62 assertions)`.
- Contraprueba con los dos controladores revertidos al estado con el defecto — `Tests: 15, Assertions: 33, Failures: 9`, con el error real `toggleActivo does not exist` confirmando que las nueve pruebas de dominio nuevas fallan de verdad contra el código defectuoso.

### Resumen

Los pasos 13-17 (trabajo nuevo de este bucle) están bien construidos: la base MariaDB de pruebas es idempotente y no arriesga la base de desarrollo, las nueve pruebas de dominio comprueban de verdad el comportamiento que dicen —incluida la prueba de baja lógica, que sí verifica que `pacientes`, `citas` y `expedientes` sobreviven, y la de elevación de privilegios, que usa `assertSessionHasErrors` en vez de una aserción complaciente—, y la reescritura de `ExampleTest` describe el comportamiento real de la aplicación sin rebajar ningún umbral. El manifiesto de código coincide exactamente con los cambios reales y `traceability.json` resuelve a destinos que de verdad implementan o prueban cada requisito. La única brecha de fondo —que `requirements.md` no refleja todavía la reapertura de Q4=D/C2— está documentada de forma explícita en el propio plan, con la opción de volver a Requirements Analysis puesta sobre la mesa para el humano; no es una divergencia oculta, así que no bloquea el veredicto por sí sola. Ningún hallazgo alcanza severidad Critical ni supera el límite de Major.
