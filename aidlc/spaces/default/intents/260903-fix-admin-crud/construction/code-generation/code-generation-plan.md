# Plan de generación de código — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: code-generation (Construction)
**Alcance**: bugfix · **Profundidad**: Minimal · **Estrategia de pruebas**: Minimal
**Tipo de proyecto**: Brownfield (Laravel 12 + MariaDB)
**Fuente de requisitos**: `aidlc/spaces/default/intents/260903-fix-admin-crud/inception/requirements-analysis/requirements.md`

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

## Brecha de cobertura de pruebas — lee esto antes de aprobar

El contrato de arriba impone dos obligaciones de piso para el alcance `bugfix`:
una **regresión dirigida al fallo** y que **la suite existente siga en verde**.
No puedo cumplir la primera por completo, y lo hago explícito en lugar de
rebajar el objetivo en silencio.

**Qué sí se puede automatizar hoy.** Las regresiones de TD-11 y TD-12 son de
tabla de rutas: se comprueban resolviendo la URL contra el enrutador de Laravel,
sin tocar la base de datos. Esas sí se escriben (Paso 10) y se ejecutan con la
configuración actual de `phpunit.xml`.

**Qué no se puede automatizar hoy.** Las regresiones de TD-8 y TD-16 necesitan
filas reales en `usuarios`, `servicios`, `pacientes` y `citas`, más el
comportamiento de las claves foráneas `ON DELETE CASCADE`. El esquema de esas
tablas vive únicamente en `database/dentissa.sql`; `database/migrations/` solo
tiene tablas auxiliares de Laravel, y `phpunit.xml` apunta a SQLite en memoria
(donde además las claves foráneas están desactivadas por defecto, así que ni
duplicando el esquema se reproduciría el fallo de cascada). Esta es exactamente
la restricción **C1** de los requisitos y la razón de la decisión **Q4 = D**.

**Cómo queda cubierto mientras tanto.** Con la lista de comprobación manual de
`unit-test-instructions.md` § "Verificación manual", que cubre cada uno de esos
casos paso a paso contra el entorno Docker existente.

**Qué desbloquearía el resto.** Levantar una base MariaDB de pruebas cargada
desde `database/dentissa.sql` y apuntar `phpunit.xml` a ella (opción Q4 = C), o
escribir migraciones del dominio (opción Q4 = B). Ambas quedan registradas como
**OQ2** para una intención posterior.

Si prefieres que esta corrección incluya ya esa cobertura, elige
**Request Changes** y lo replanteo.

---

## Pasos de implementación

Orden `test-after`: se implementa cada capa aplicable y después se escriben y
ejecutan sus pruebas. Las capas *Repository / data access* y
*Environment/build configuration* del perfil no aplican a este repositorio y se
omiten explícitamente (los controladores usan Eloquent y el facade `DB`
directamente; no hay capa de repositorio ni cambios de configuración de
construcción).

### Paso 1 — Estructura del proyecto y configuración de producción

- [ ] **No aplica.** Brownfield sin componentes nuevos: todos los archivos que se
  tocan ya existen. No se crea ninguna estructura ni configuración de producción.

### Paso 2 — Verificar el ejecutor de pruebas y registrar el comando

- [ ] Confirmar que `vendor/bin/phpunit` es ejecutable y que la suite actual
  (`tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`) corre en verde
  **antes** de tocar nada. Esta es la línea base de regresión que exige el
  salvaguarda brownfield.
- [ ] Registrar el comando exacto acotado a esta corrección en
  `unit-test-instructions.md` (ya escrito allí, se verifica que funcione).
- Trazabilidad: FR5.2, NFR5

### Paso 3 — Capa de modelo de datos

- [ ] **No aplica, verificado.** No hay cambios de esquema (restricción **C1**) y
  los modelos ya admiten los campos necesarios: `App\Models\Servicio::$fillable`
  incluye `precio` y `color_agenda`; `App\Models\Usuario::$fillable` incluye
  `rol` y `activo`. No se modifica ningún modelo.
- Trazabilidad: C1

### Paso 4 — Lógica de negocio: `ServiciosController` (TD-8)

En `app/Http/Controllers/ServiciosController.php`:

- [ ] En `store()`: añadir `'precio' => 'required|numeric|min:0|max:99999999.99'` y
  `'color_agenda' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/'` a la llamada
  `$request->validate()` existente.
- [ ] En `store()`: sustituir `$data = $request->all()` por el array validado, y
  **eliminar** las líneas `$data['precio'] = 0;` y
  `$data['color_agenda'] = '#F4C0D1';`. Conservar `$data['id']`,
  `$data['activo'] = 1` y `$data['creado_en'] = now()`.
- [ ] En `update()`: añadir las mismas dos reglas de validación.
- [ ] En `update()`: sustituir `$data = $request->all()` por el array validado y
  **eliminar** la línea `$data['precio'] = 0;`. Conservar el manejo del checkbox
  `$data['activo'] = $request->has('activo') ? 1 : 0;`.
- [ ] Añadir mensajes de validación en español coherentes con el resto del
  sistema (`PacientesController::store()` es el modelo a seguir).
- Trazabilidad: FR2.4, FR2.5, FR2.6, FR2.7, NFR2, NFR3

### Paso 5 — Lógica de negocio: `UsuarioController`, validación y contraseña (TD-16)

En `app/Http/Controllers/UsuarioController.php`:

- [ ] En `store()`: añadir `$request->validate()` con
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
- [ ] En `store()`: sustituir `bcrypt('password123')` por el patrón ya presente en
  `PacientesController::store()` líneas 110 y 122 — usar la contraseña enviada si
  viene informada, y `Str::random(10)` si viene vacía, hasheada con `Hash::make()`.
  Añadir los `use` correspondientes.
- [ ] En `update()`: añadir la misma validación, con
  `'email' => ['required','email', Rule::unique('usuarios','email')->ignore($id)]`
  para no chocar con el propio registro.
- [ ] En `update()`: escribir también `'actualizado_en' => now()`, ya que
  `App\Models\Usuario` declara `public $timestamps = false` y hoy la columna no
  se actualiza nunca.
- Trazabilidad: FR3.1, FR3.2, FR3.3, FR3.6, NFR1, NFR3, NFR4

### Paso 6 — Lógica de negocio: baja lógica de usuarios (TD-16)

En `app/Http/Controllers/UsuarioController.php`:

- [ ] Añadir `toggleActivo(string $id)` siguiendo literalmente el patrón de
  `PersonalController::toggleActivo()` (líneas 318-330): buscar el usuario,
  `abort(404)` si no existe, invertir `activo`, escribir `actualizado_en`, y
  `return back()->with('success', ...)` con mensaje «Usuario desactivado.» /
  «Usuario reactivado.».
- [ ] **Eliminar** el método `destroy()` por completo.
- Trazabilidad: FR4.1, FR4.6, FR4.5, NFR2, NFR3, NFR4

### Paso 7 — Pruebas de la capa de lógica de negocio

- [ ] **Cobertura automática no disponible en esta corrección.** Los tres cambios
  anteriores solo son observables contra filas reales de `usuarios` y
  `servicios`. Ver § "Brecha de cobertura de pruebas". Quedan cubiertos por los
  puntos 2 a 7 de la lista de comprobación manual.
- Trazabilidad: FR5.1, OQ2

### Paso 8 — Capa de rutas (TD-11, TD-12, y ruta de alternancia)

En `routes/web.php`, dentro del grupo `admin`:

- [ ] **Eliminar** la línea 88, el closure muerto
  `Route::get('/usuarios', fn() => view('placeholder', ...))->name('usuarios')`.
  Verificado: ninguna vista ni controlador usa la ruta nombrada `admin.usuarios`
  a secas; todas las referencias son `admin.usuarios.index`, `.create`, `.show`,
  `.edit`, `.store`, `.update` o `.destroy`. (La línea 87, `admin.reportes`, es un
  placeholder distinto y **no** se toca.)
- [ ] **Reordenar**: registrar `GET /usuarios/crear` **antes** que
  `GET /usuarios/{id}`.
- [ ] Añadir `->where('id', '[0-9a-fA-F-]{36}')` a las tres rutas con `{id}`
  (`usuarios.show`, `usuarios.edit`, `usuarios.update`) para que ningún segmento
  literal futuro vuelva a caer en el parámetro.
- [ ] Añadir `Route::patch('/usuarios/{id}/toggle', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle')`,
  con la misma forma y nombre que `admin.personal.toggle` (línea 83).
- [ ] **Eliminar** `Route::delete('/usuarios/{id}', ...)->name('usuarios.destroy')`.
- Trazabilidad: FR1.1, FR1.2, FR1.3, FR1.4, FR4.2, FR4.5

### Paso 9 — Interfaz: vistas de usuarios y servicios

- [ ] `resources/views/usuarios/create.blade.php`: añadir
  `<option value="asistente">Asistente</option>` al selector de rol
  (líneas 71-77), con el mismo ternario `selected` que las otras tres. Esto
  arregla a la vez el alta y la edición, porque
  `resources/views/usuarios/edit.blade.php` es un `@include('usuarios.create')`
  de cinco líneas.
- [ ] `resources/views/usuarios/index.blade.php`: sustituir el botón de papelera
  (línea 163) por un botón de alternancia que muestre «Desactivar» o «Activar»
  según `$m->activo`.
- [ ] `resources/views/usuarios/index.blade.php`: reescribir el modal
  (líneas 202-229) — título, texto («Esta acción no se puede deshacer» ya no es
  cierta), botón, y el `@method('DELETE')` pasa a `@method('PATCH')` apuntando a
  `admin.usuarios.toggle`. Ajustar el JavaScript de las líneas 231-245 en
  consecuencia.
- [ ] `resources/views/servicios/create.blade.php`: añadir campo
  `<input type="number" name="precio" step="0.01" min="0" required>` y
  `<input type="color" name="color_agenda" value="#F4C0D1">`, con las clases
  `field` / `field-label` que ya usa la vista.
- [ ] `resources/views/servicios/edit.blade.php`: los mismos dos campos,
  precargados con `{{ $servicio->precio }}` y `{{ $servicio->color_agenda }}`.
- [ ] Añadir `data-testid` a los elementos interactivos nuevos (botón de
  alternancia, campo de precio, selector de color, opción de rol).
- Trazabilidad: FR2.1, FR2.2, FR2.3, FR3.4, FR3.5, FR3.7, FR4.3, FR4.4

### Paso 10 — Pruebas de la capa de rutas (regresión dirigida)

- [ ] Crear `tests/Feature/AdminUsuariosRutasTest.php` con las regresiones que sí
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
- [ ] Ejecutar el comando acotado de `unit-test-instructions.md` y dejarlo en
  verde.
- [ ] Ejecutar la suite completa y confirmar que los dos ejemplos de Laravel
  siguen pasando.
- Trazabilidad: FR1.1, FR1.2, FR1.3, FR4.2, FR4.5, FR5.2, NFR5

### Paso 11 — Pruebas de la capa de interfaz

- [ ] **No aplica.** El proyecto no tiene ejecutor de pruebas de interfaz
  (`package.json` solo trae Vite; no hay Vitest, Jest ni Dusk). Introducir uno
  excede el alcance de esta corrección. Los cambios de vista quedan cubiertos por
  la lista de comprobación manual, puntos 1 a 8.
- Trazabilidad: FR5.1

### Paso 12 — Documentación y trazabilidad

- [ ] Escribir `source-manifest.json` con todas las rutas de código que esta
  etapa crea, modifica o borra.
- [ ] Escribir `code-summary.md`: archivos tocados, decisiones, cobertura, y
  cualquier desviación de este plan.
- [ ] Escribir `traceability.json` mapeando cada `FR` y `NFR` a su archivo real.
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

Ningún archivo se duplica ni se renombra: todo se modifica en el sitio.
No se toca `database/dentissa.sql`, `database/migrations/`, `phpunit.xml`, ni las
vistas SQL `v_citas_hoy` y `v_historial_paciente` (restricciones C1 y C6).

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
