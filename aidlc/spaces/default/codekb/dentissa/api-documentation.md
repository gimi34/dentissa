# Documentación de interfaces — Dentissa

**Commit analizado**: `9fcff4c635d5134d9f252b6158812c4dd15e2657` (`main`, 2026-09-02)

---

## 1. Naturaleza de la superficie

**No existe ninguna API programática en este sistema.** El hecho está verificado por cuatro comprobaciones independientes:

1. No existe `routes/api.php`, y `bootstrap/app.php` no registra ningún archivo de rutas de API.
2. Ningún controlador devuelve JSON: no hay una sola llamada a `response()->json()`, ni `JsonResource`, ni `toJson()`.
3. El barrido exhaustivo de las 37 plantillas Blade no encontró ni una llamada `fetch()`, `axios` ni `XMLHttpRequest`.
4. `axios` está registrado como `window.axios` en `resources/js/bootstrap.js`, pero ese archivo pertenece a la cadena Vite, que ninguna vista alcanzable carga.

La aplicación es una **MPA renderizada íntegramente en servidor**. El «contrato» de cada operación es un formulario HTML con `@csrf` y una respuesta HTML completa o una redirección. Documentar esa superficie con precisión es lo que sustituye aquí a una especificación de API.

Las superficies existentes son cuatro:

| Superficie | Tipo | Dónde | Volumen |
| --- | --- | --- | --- |
| **HTTP HTML interna** | Entrante, síncrona, sesión por cookie | `routes/web.php` | **89 rutas registradas** |
| **OAuth 2.0 con Google** | Saliente, síncrona | `GoogleAuthController` con `laravel/socialite` | 2 endpoints locales |
| **Health check** | Entrante, sin autenticación | `bootstrap/app.php` declara `health: '/up'` | 1 endpoint |
| **Consola** | Local | `routes/console.php` | 1 comando (`inspire`), sin valor de negocio |

No hay tareas programadas (`Schedule::` no aparece en ninguna parte del proyecto) ni jobs en cola, pese a que `QUEUE_CONNECTION` está configurado como `database`.

### Recuento de rutas — corrección

El recuento verificado sobre `routes/web.php` es de **89 rutas registradas**: 82 registros de verbo único más las 7 que genera la única llamada a `Route::resource`. Ese total se confirma por dos caminos independientes — el recuento mecánico de los registros y la enumeración manual grupo por grupo de las tablas de la sección 3.

De esas 89, **86 son plenamente funcionales**. Las tres excepciones:

| Ruta | Estado | Causa |
| --- | --- | --- |
| `GET /admin/usuarios/crear` (`admin.usuarios.create`) | **Inalcanzable — responde `404`** | La ruta `GET /admin/usuarios/{id}` se registra antes y captura la URI. Defecto `DEF-1` |
| `GET /admin/usuarios` con nombre `admin.usuarios` | Nombre huérfano | La misma URI se registra dos veces; la segunda gana la resolución. El nombre sigue apuntando a una closure muerta |
| `GET /admin/servicios/{servicio}` (`admin.servicios.show`) | **Registrada sin implementación** | `Route::resource` la genera, pero `ServiciosController` **no tiene método `show()`** ni existe la vista `servicios/show.blade.php`. Nada enlaza a ella, de modo que hoy es inalcanzable desde la interfaz; invocarla directamente produciría un error del servidor |

---

## 2. Mecanismos transversales

| Mecanismo | Cómo funciona | Alcance |
| --- | --- | --- |
| **Autenticación** | Sesión por cookie. Guard `web`, proveedor `users` apuntando al modelo `App\Models\Usuario` sobre la tabla `usuarios`. La contraseña se redirige a `password_hash` con `getAuthPassword()` | Todas las rutas salvo las públicas |
| **Autorización** | `RoleMiddleware` (alias `role`) por grupo de rutas: redirige a `/login` si no hay sesión y responde `403` si el rol no está en la lista | Grupos `admin`, `dentista`, `asistente`. **No** el grupo `cliente` |
| **Protección CSRF** | Middleware `web` de Laravel. Verificado: **las 37 plantillas incluyen `@csrf` en todos sus formularios** | Todas las escrituras |
| **Suplantación de método** | `@method('PUT')` / `@method('PATCH')` / `@method('DELETE')` en formularios HTML | Todas las rutas no `GET`/`POST` |
| **Cabeceras anti-caché** | `PreventBackHistory` añadido a **todo** el grupo `web`, incluidas las páginas públicas: `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` | Todas las respuestas |
| **Escapado de salida** | Interpolación `{{ }}` en las 37 plantillas. **No hay ni una sola salida sin escapar** (`{!! !!}`) | Todas las vistas |
| **Formato de error de validación** | `back()->withInput()->withErrors([...])` con mensajes en español, renderizados por la plantilla | Todas las escrituras salvo `UsuarioController` |

---

## 3. Superficie HTTP completa

### 3.1 Rutas públicas — sin middleware de autenticación

| Método | URI | Controlador y acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/` | Closure de despacho por rol | — |
| `GET` | `/login` | `AuthController@loginForm` | `login` |
| `POST` | `/login` | `AuthController@login` | — |
| `POST` | `/logout` | `AuthController@logout` | `logout` |
| `GET` | `/registro` | `RegisterController@showForm` | `register` |
| `POST` | `/registro` | `RegisterController@register` | — |
| `GET` | `/registro/contacto` | `RegisterController@contactoForm` | `register.contacto` |
| `POST` | `/registro/contacto` | `RegisterController@contactoStore` | — |
| `GET` | `/auth/google` | `GoogleAuthController@redirect` | `auth.google` |
| `GET` | `/auth/google/callback` | `GoogleAuthController@callback` | `auth.google.callback` |

**Nota sobre `POST /logout`**: está fuera de todo grupo de autenticación, de modo que responde también sin sesión activa.

**Nota sobre `GET /`**: la closure **siempre redirige** — al panel del rol si hay sesión, a `/login` si no la hay. Nunca devuelve `200`. Este comportamiento es la causa directa de que la única prueba existente falle (ver `code-quality-assessment.md`, `TD-13`).

### 3.2 Grupo `admin` — middleware `['auth','role:admin']`, prefijo `admin`, nombres `admin.*`

**Panel**

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/admin` | `DashboardController@admin` | `admin.dashboard` |

**Servicios** — generadas por `Route::resource('servicios', ServiciosController::class)`

| Método | URI | Acción | Nombre | Estado |
| --- | --- | --- | --- | --- |
| `GET` | `/admin/servicios` | `index` | `admin.servicios.index` | OK |
| `GET` | `/admin/servicios/create` | `create` | `admin.servicios.create` | OK — nótese `create` en inglés, a diferencia del resto del sistema, que usa `crear` |
| `POST` | `/admin/servicios` | `store` | `admin.servicios.store` | **Borra el precio** — `DEF-2` |
| `GET` | `/admin/servicios/{servicio}` | `show` | `admin.servicios.show` | **Método inexistente** |
| `GET` | `/admin/servicios/{servicio}/edit` | `edit` | `admin.servicios.edit` | OK |
| `PUT` `PATCH` | `/admin/servicios/{servicio}` | `update` | `admin.servicios.update` | **Borra el precio** — `DEF-2` |
| `DELETE` | `/admin/servicios/{servicio}` | `destroy` | `admin.servicios.destroy` | Borrado **físico** de la fila |

**Citas**

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/admin/citas` | `CitasController@index` | `admin.citas` |
| `GET` | `/admin/citas/crear` | `CitasController@create` | `admin.citas.create` |
| `POST` | `/admin/citas` | `CitasController@store` | `admin.citas.store` |
| `GET` | `/admin/citas/{id}` | `CitasController@show` | `admin.citas.show` |
| `PATCH` | `/admin/citas/{id}/estado` | `CitasController@updateEstado` | `admin.citas.estado` |
| `PATCH` | `/admin/citas/{id}/notas` | `CitasController@updateNotas` | `admin.citas.notas` |
| `DELETE` | `/admin/citas/{id}` | `CitasController@destroy` | `admin.citas.destroy` |

**Pacientes**

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/admin/pacientes` | `PacientesController@index` | `admin.pacientes` |
| `GET` | `/admin/pacientes/crear` | `PacientesController@create` | `admin.pacientes.create` |
| `POST` | `/admin/pacientes` | `PacientesController@store` | `admin.pacientes.store` |
| `GET` | `/admin/pacientes/{id}` | `PacientesController@show` | `admin.pacientes.show` |
| `GET` | `/admin/pacientes/{id}/edit` | `PacientesController@edit` | `admin.pacientes.edit` |
| `PUT` | `/admin/pacientes/{id}` | `PacientesController@update` | `admin.pacientes.update` |
| `PATCH` | `/admin/pacientes/{id}/toggle` | `PacientesController@toggleActivo` | `admin.pacientes.toggle` |

**Expedientes** — el administrador **no** tiene índice de expedientes; sólo el dentista lo tiene

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/admin/expedientes/crear` | `ExpedientesController@create` | `admin.expedientes.create` |
| `POST` | `/admin/expedientes` | `ExpedientesController@store` | `admin.expedientes.store` |
| `GET` | `/admin/expedientes/{id}` | `ExpedientesController@show` | `admin.expedientes.show` |
| `PUT` | `/admin/expedientes/{id}` | `ExpedientesController@update` | `admin.expedientes.update` |

**Personal**

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/admin/personal` | `PersonalController@index` | `admin.personal` |
| `GET` | `/admin/personal/crear` | `PersonalController@create` | `admin.personal.create` |
| `POST` | `/admin/personal` | `PersonalController@store` | `admin.personal.store` |
| `GET` | `/admin/personal/{id}` | `PersonalController@show` | `admin.personal.show` |
| `GET` | `/admin/personal/{id}/edit` | `PersonalController@edit` | `admin.personal.edit` |
| `PUT` | `/admin/personal/{id}` | `PersonalController@update` | `admin.personal.update` |
| `PATCH` | `/admin/personal/{id}/toggle` | `PersonalController@toggleActivo` | `admin.personal.toggle` |
| `POST` | `/admin/personal/{id}/horarios` | `PersonalController@saveHorarios` | `admin.personal.horarios` |

**Usuarios y marcadores de posición** — este bloque concentra los dos problemas de enrutado del sistema

| Método | URI | Acción | Nombre | Estado |
| --- | --- | --- | --- | --- |
| `GET` | `/admin/reportes` | Closure que devuelve `placeholder` | `admin.reportes` | Módulo declaradamente pendiente |
| `GET` | `/admin/usuarios` | Closure que devuelve `placeholder` | `admin.usuarios` | **Sombreada** por el registro siguiente |
| `GET` | `/admin/usuarios` | `UsuarioController@index` | `admin.usuarios.index` | Es la que efectivamente se sirve |
| `GET` | `/admin/usuarios/{id}` | `UsuarioController@show` | `admin.usuarios.show` | OK, pero **captura `/admin/usuarios/crear`** |
| `GET` | `/admin/usuarios/crear` | `UsuarioController@create` | `admin.usuarios.create` | **Inalcanzable — `404`.** Defecto `DEF-1` |
| `POST` | `/admin/usuarios` | `UsuarioController@store` | `admin.usuarios.store` | Sin validación alguna |
| `GET` | `/admin/usuarios/{id}/editar` | `UsuarioController@edit` | `admin.usuarios.edit` | OK |
| `PUT` | `/admin/usuarios/{id}` | `UsuarioController@update` | `admin.usuarios.update` | Sin validación alguna |
| `DELETE` | `/admin/usuarios/{id}` | `UsuarioController@destroy` | `admin.usuarios.destroy` | Borrado **físico** con cascada |

### 3.3 Grupo `dentista` — middleware `['auth','role:dentista']`, prefijo `dentista`, nombres `dentista.*`

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/dentista` | `DashboardController@dentista` | `dentista.dashboard` |
| `GET` | `/dentista/citas` | `CitasController@index` | `dentista.citas` |
| `GET` | `/dentista/citas/crear` | `CitasController@create` | `dentista.citas.create` |
| `POST` | `/dentista/citas` | `CitasController@store` | `dentista.citas.store` |
| `GET` | `/dentista/citas/{id}` | `CitasController@show` | `dentista.citas.show` |
| `PATCH` | `/dentista/citas/{id}/estado` | `CitasController@updateEstado` | `dentista.citas.estado` |
| `GET` | `/dentista/pacientes` | `PacientesController@index` | `dentista.pacientes` |
| `GET` | `/dentista/pacientes/{id}` | `PacientesController@show` | `dentista.pacientes.show` |
| `GET` | `/dentista/pacientes/{id}/edit` | `PacientesController@edit` | `dentista.pacientes.edit` |
| `PUT` | `/dentista/pacientes/{id}` | `PacientesController@update` | `dentista.pacientes.update` |
| `GET` | `/dentista/expedientes` | `ExpedientesController@index` | `dentista.expedientes` |
| `GET` | `/dentista/expedientes/crear` | `ExpedientesController@create` | `dentista.expedientes.create` |
| `POST` | `/dentista/expedientes` | `ExpedientesController@store` | `dentista.expedientes.store` |
| `GET` | `/dentista/expedientes/{id}` | `ExpedientesController@show` | `dentista.expedientes.show` |
| `PUT` | `/dentista/expedientes/{id}` | `ExpedientesController@update` | `dentista.expedientes.update` |

**El dentista es el único rol con índice de expedientes** (`dentista.expedientes`), y sólo ve en él los expedientes que él mismo firmó. Todas sus consultas de citas quedan acotadas a su propia fila de `personal` por `aplicarAlcanceRol()`. **No** puede crear pacientes ni eliminar citas.

### 3.4 Grupo `asistente` — middleware `['auth','role:asistente']`, prefijo `asistente`, nombres `asistente.*`

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/asistente` | `DashboardController@asistente` | `asistente.dashboard` |
| `GET` | `/asistente/doctores` | `PersonalController@misDoctores` | `asistente.doctores` |
| `GET` | `/asistente/citas` | `CitasController@index` | `asistente.citas` |
| `GET` | `/asistente/citas/crear` | `CitasController@create` | `asistente.citas.create` |
| `POST` | `/asistente/citas` | `CitasController@store` | `asistente.citas.store` |
| `GET` | `/asistente/citas/{id}` | `CitasController@show` | `asistente.citas.show` |
| `PATCH` | `/asistente/citas/{id}/estado` | `CitasController@updateEstado` | `asistente.citas.estado` |
| `GET` | `/asistente/pacientes` | `PacientesController@index` | `asistente.pacientes` |
| `GET` | `/asistente/pacientes/crear` | `PacientesController@create` | `asistente.pacientes.create` |
| `POST` | `/asistente/pacientes` | `PacientesController@store` | `asistente.pacientes.store` |
| `GET` | `/asistente/pacientes/{id}` | `PacientesController@show` | `asistente.pacientes.show` |
| `GET` | `/asistente/expedientes/{id}` | `ExpedientesController@show` | `asistente.expedientes.show` |

El asistente **no tiene** `edit` ni `update` de pacientes, ni creación ni edición de expedientes. Los controladores refuerzan esa exclusión con `abort(403)` explícito, de modo que la restricción no depende sólo de la ausencia de la ruta. Todo su alcance de agenda deriva de la tabla puente `asistente_dentista`; **sin filas en esa tabla, todas sus listas salen vacías**.

### 3.5 Grupo `cliente` — middleware **sólo `auth`**, prefijo `cliente`, nombres `cliente.*`

| Método | URI | Acción | Nombre |
| --- | --- | --- | --- |
| `GET` | `/cliente` | `DashboardController@cliente` | `cliente.dashboard` |
| `GET` | `/cliente/citas` | `ClienteCitasController@index` | `cliente.citas` |
| `GET` | `/cliente/citas/{id}` | `ClienteCitasController@show` | `cliente.citas.show` |
| `PATCH` | `/cliente/citas/{id}/cancelar` | `ClienteCitasController@cancelar` | `cliente.citas.cancelar` |
| `GET` | `/cliente/agendar` | `ClienteCitasController@agendarForm` | `cliente.agendar` |
| `POST` | `/cliente/agendar` | `ClienteCitasController@agendarStore` | `cliente.agendar.store` |
| `GET` | `/cliente/perfil` | `PerfilController@edit` | `cliente.perfil` |
| `PUT` | `/cliente/perfil` | `PerfilController@update` | `cliente.perfil.update` |
| `PUT` | `/cliente/perfil/password` | `PerfilController@updatePassword` | `cliente.perfil.password` |

**Este grupo es el único de los cuatro que no aplica `role:`.** Cualquier usuario autenticado, incluidos `admin`, `dentista` y `asistente`, puede entrar. Los controladores mitigan la lectura filtrando por `pacientes.usuario_id` y respondiendo `404` cuando no hay fila de paciente, pero **la escritura no está mitigada**: `PerfilController::update()` inserta una fila de `pacientes` cuando no encuentra una para el usuario actual. Ver `code-quality-assessment.md`, hallazgo `SEC-2`.

---

## 4. Contratos de formulario de las operaciones críticas

Estos son los «esquemas de petición» reales del sistema. Todos se validan en línea dentro de la acción del controlador; **no existe ningún Form Request**.

### 4.1 `POST /registro` — registro directo del paciente

| Campo | Reglas | Obligatorio |
| --- | --- | --- |
| `nombre` | `string`, `max:100` | Sí |
| `apellido` | `string`, `max:100` | Sí |
| `email` | `email`, `max:255`, `unique:usuarios,email` | Sí |
| `password` | `confirmed`, `Password::min(8)` | Sí |
| `telefono` | `string`, `max:20` | No |
| `fecha_nacimiento` | `date`, `before:today` | No |
| `genero` | `string`, `max:20` | No |
| `direccion` | `string`, `max:500` | No |
| `ciudad` | `string`, `max:100` | No |

**Efecto**: transacción que inserta en `usuarios` (rol `cliente`, contraseña con `Hash::make`) y en `pacientes`, con dentista asignado automáticamente. Inicia sesión y redirige al panel del cliente.

### 4.2 `POST /registro/contacto` — segundo paso del registro por Google

| Campo | Reglas | Obligatorio |
| --- | --- | --- |
| `telefono` | `string`, `max:20` | No |
| `fecha_nacimiento` | `date`, `before:today` | No |
| `genero` | `string`, `max:20` | No |
| `direccion` | `string`, `max:500` | No |
| `ciudad` | `string`, `max:100` | No |
| `terminos` | `accepted` | **Sí** |

**Precondición**: la clave `google_registro` debe existir en la sesión, puesta por el callback de OAuth.
**Efecto**: inserta en `usuarios` con `password_hash` **nulo** y `google_id`, más la fila de `pacientes` con dentista asignado.

### 4.3 `POST /{rol}/citas` — crear cita desde el personal

| Campo | Reglas | Obligatorio |
| --- | --- | --- |
| `paciente_id` | `exists:pacientes,id` | Sí |
| `dentista_id` | `exists:personal,id` | Sí |
| `servicio_id` | `exists:servicios,id` | **No** |
| `fecha` | `date` — **sin fecha mínima** | Sí |
| `hora` | — | Sí |
| `duracion_min` | `integer`, `min:10`, `max:300` | Sí |
| `notas_paciente` | `string`, `max:1000` | No |
| `notas_internas` | `string`, `max:1000` | No |

**Comprobaciones posteriores a la validación**, en orden, cada una con su mensaje de error:

1. `autorizadoParaCita($dentista_id)` — `403` si el rol no alcanza a ese dentista.
2. Existe fila en `horarios_dentista` para ese `personal_id` y ese día ISO, con `disponible` verdadero.
3. El intervalo de la cita cabe entre `hora_inicio` y `hora_fin`; el mensaje devuelve el horario real.
4. No hay solape con otra cita del mismo dentista, excluyendo `cancelada` y `no_asistio`.

**Efecto**: `INSERT` en `citas` con UUID generado, estado `agendada` y `creado_por` igual al usuario autenticado.

### 4.4 `POST /cliente/agendar` — crear cita desde el cliente

| Campo | Reglas | Obligatorio |
| --- | --- | --- |
| `dentista_id` | `exists:personal,id` | Sí |
| `servicio_id` | `exists:servicios,id` | **Sí** — a diferencia del personal |
| `fecha` | `date`, **`after_or_equal:today`** | Sí |
| `hora` | — | Sí |
| `notas_paciente` | `string`, `max:1000` | No |

**Diferencias contractuales frente al formulario del personal**, todas deliberadas:

- El servicio es obligatorio y **determina la duración**; el cliente no puede elegirla.
- La fecha no puede ser anterior a hoy.
- El paciente **no viaja en el formulario**: se resuelve desde la sesión, y la acción responde `404` si el usuario autenticado no tiene fila de paciente.
- No admite `notas_internas`.

Las tres comprobaciones de disponibilidad son idénticas, pero están implementadas en un segundo lugar del código.

### 4.5 `PATCH /{rol}/citas/{id}/estado` — cambio de estado

| Campo | Reglas | Obligatorio |
| --- | --- | --- |
| `estado` | `in:agendada,confirmada,en_curso,completada,cancelada,no_asistio` | Sí |
| `motivo` | `string`, `max:500` | No |
| `notas_internas` | `string`, `max:1000` | No |

**No hay grafo de transiciones válidas**: cualquiera de los seis estados es aceptado desde cualquier otro.

**Efecto colateral no evidente**: el `UPDATE` dispara el trigger `trg_historial_cita`, que inserta una fila de bitácora con la transición pero sin autor ni motivo. Si además se aporta `motivo`, el controlador inserta una **segunda** fila con autor y motivo pero sin estado anterior.

### 4.6 `POST /admin/usuarios` y `PUT /admin/usuarios/{id}` — la excepción

**Estas dos acciones no validan absolutamente nada.** Leen `nombre`, `email`, `rol`, `telefono` y `activo` directamente de la petición, sin regla de unicidad sobre el correo y **sin restricción `in:` sobre `rol`**, siendo `rol` un campo asignable en masa del modelo `Usuario`. Además, `store()` asigna una contraseña genérica escrita en el propio código. Ver `code-quality-assessment.md`, hallazgo `SEC-3`.

---

## 5. Integración saliente: OAuth 2.0 con Google

| Elemento | Valor |
| --- | --- |
| Librería | `laravel/socialite` `v5.28.0` |
| Cliente HTTP subyacente | `guzzlehttp/guzzle` `7.13.2` |
| Proveedor configurado | `google` — el único |
| Configuración | `config/services.php`, leída de `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` |
| Endpoint de inicio | `GET /auth/google` — redirige al proveedor |
| Endpoint de retorno | `GET /auth/google/callback` |

**Campos consumidos del perfil de Google**: identificador (`getId()`), correo (`getEmail()`), nombre completo (`getName()`), avatar (`getAvatar()`), y del bloque bruto `given_name` y `family_name`.

**Contrato de resolución de identidad**, en este orden estricto:

1. Buscar en `usuarios` por `google_id`. Si existe, es la cuenta.
2. Si no, buscar por `email`. Si existe, **vincular** escribiendo el `google_id` en la cuenta local.
3. Si no existe ninguna, **no crear el usuario**: descomponer el nombre, guardar el perfil en la sesión bajo la clave `google_registro` y redirigir al segundo paso de registro.

**Modos de fallo no cubiertos**: el callback no captura ninguna excepción. Un usuario que cancele el consentimiento, un `code` caducado o una indisponibilidad de Google producen una excepción no gestionada y una página de error genérica, no un mensaje de negocio.

---

## 6. Endpoints de infraestructura

| Endpoint | Origen | Autenticación | Uso |
| --- | --- | --- | --- |
| `GET /up` | `bootstrap/app.php` declara `health: '/up'` | Ninguna | Sonda de salud estándar de Laravel. **No está cableada a ninguna comprobación de contenedor**: `docker-compose.yml` no declara `healthcheck` para el servicio `app` |
| `php artisan inspire` | `routes/console.php` | Local | Comando de ejemplo del framework, sin valor de negocio |

---

## 7. Códigos de respuesta observados

| Código | Cuándo se produce |
| --- | --- |
| `200` | Cualquier `GET` de vista con sesión y rol válidos |
| `302` | **Toda** operación de escritura correcta, la ruta raíz siempre, y el acceso sin sesión |
| `403` | `RoleMiddleware` con rol no permitido; `autorizadoParaCita()` fuera de alcance; exclusiones explícitas del asistente en `ExpedientesController` |
| `404` | `findOrFail` sin coincidencia; `abort_unless($paciente, 404)` en los controladores del cliente; **y `GET /admin/usuarios/crear` por el defecto `DEF-1`** |
| `419` | Token CSRF caducado — comportamiento estándar de Laravel |
| `500` | Sin manejador declarado (`withExceptions` está vacío). Se produce, entre otros casos, al borrar un usuario cuyo paciente tiene citas, por la clave foránea restrictiva |

---

## Sources

- `routes/web.php` — 161 líneas, leídas íntegras; recuento de registros verificado mecánicamente
- `routes/console.php`, `bootstrap/app.php`
- `app/Http/Controllers/AuthController.php`, `GoogleAuthController.php`, `RegisterController.php`, `CitasController.php`, `ClienteCitasController.php`, `ServiciosController.php`, `UsuarioController.php`, `PerfilController.php`, `ExpedientesController.php`, `PacientesController.php`, `PersonalController.php`, `DashboardController.php`
- `app/Http/Middleware/RoleMiddleware.php`, `PreventBackHistory.php`
- `config/auth.php`, `config/services.php`
- `resources/views/servicios/` — verificada la ausencia de `show.blade.php`; búsqueda de referencias a `admin.servicios.show` sin resultados
- `resources/views/` — barrido estructural de los formularios, métodos y `@csrf` de las 37 plantillas
- Escaneo del desarrollador: `aidlc/spaces/default/intents/260902-docs-sistema-dental/inception/reverse-engineering/developer-scan.md`
