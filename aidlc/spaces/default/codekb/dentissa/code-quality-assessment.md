# Evaluación de calidad del código — Dentissa

**Commit analizado**: `9fcff4c635d5134d9f252b6158812c4dd15e2657` (`main`, 2026-09-02)
**Naturaleza de los hallazgos**: todos los que siguen están **verificados en el código**, con archivo y método identificados. Ninguno es una suposición.

> **Aviso sobre datos sensibles.** Este documento describe una exposición real de datos personales y credenciales en el repositorio. **No reproduce ningún valor**: ni contraseñas, ni hashes, ni correos, ni teléfonos, ni datos clínicos de paciente alguno. Se nombra el archivo y el tipo de dato, nunca el dato. El archivo `.env` no se abrió en ningún momento de este análisis.

---

## 1. Cuadro de mando

| Dimensión | Estado | Evidencia |
| --- | --- | --- |
| **Cobertura de pruebas del dominio** | **0%** | Ninguna de las 2.545 líneas de `app/` está ejercitada. Los únicos tres archivos de `tests/` son el andamiaje de Laravel |
| **Suite de pruebas** | **En rojo** | `tests/Feature/ExampleTest.php` espera `200` en `GET /`; la aplicación siempre devuelve `302` |
| **Configuración de cobertura** | **Ausente** | Sin umbral, sin informe, sin extensión de instrumentación declarada |
| **Formateo** | **Instalado, nunca ejecutado** | `laravel/pint` `v1.29.3` sin `pint.json` y sin guion que lo invoque |
| **Análisis estático** | **Ausente** | Sin PHPStan, Psalm ni Larastan |
| **Linting de frontend** | **Ausente** | Sin ESLint ni Prettier |
| **Integración continua** | **Ausente** | Sin `.github/`, `.gitlab-ci.yml`, `Jenkinsfile`, `.circleci/` ni `bitbucket-pipelines.yml`. El único YAML del repositorio es `docker-compose.yml` |
| **Entrega continua** | **Ausente** | Ningún entorno de staging ni de producción declarado en ninguna parte |
| **Hooks de pre-commit** | **Ausentes** | |
| **Documentación del proyecto** | **Ausente** | El `README.md` es el genérico de Laravel sin personalizar. Sin `CONTRIBUTING.md`, `CHANGELOG.md`, ADRs, diagramas ni carpeta `docs/` |
| **Norma de estilo activa** | `.editorconfig` únicamente | UTF-8, fin de línea LF, 4 espacios y 2 en YAML |
| **Defectos funcionales confirmados** | **3** | `DEF-1`, `DEF-2`, `DEF-3` |
| **Hallazgos de seguridad** | **3** | `SEC-1` (crítico), `SEC-2`, `SEC-3` |
| **Señales de deuda técnica** | **20** | `TD-1` a `TD-20` |

**No existe ninguna red de seguridad automatizada en este proyecto.** Ningún cambio es verificado por nada antes de llegar a `main`.

---

## 2. Defectos funcionales confirmados

Los tres rompen funcionalidad visible o declarada. Cada uno lleva su criterio de verificación, para que la corrección sea comprobable.

### DEF-1 — El alta de usuarios desde la administración responde `404`

**Severidad**: Alta — funcionalidad de administración inutilizable
**Ubicación**: `routes/web.php`, líneas 92 y 94
**Alias de deuda**: `TD-11`

**Qué ocurre.** `GET /admin/usuarios/{id}` se registra **antes** que `GET /admin/usuarios/crear`, y el parámetro `{id}` no lleva ninguna restricción de formato. Laravel resuelve las rutas por orden de registro, de modo que una petición a `/admin/usuarios/crear` es capturada por la primera y ejecuta `UsuarioController@show('crear')`. Ese método hace `Usuario::findOrFail('crear')`, que no encuentra nada y lanza la excepción de modelo no encontrado, traducida a **`404`**.

**Por qué importa.** El botón «Nuevo Usuario» de `resources/views/usuarios/index.blade.php` apunta exactamente a `route('admin.usuarios.create')`, es decir, a esa URI. **El alta de usuarios desde la interfaz de administración está rota.**

**Por qué sólo ocurre aquí.** El resto de módulos registra la ruta literal antes que la paramétrica. Por ejemplo, `citas` registra `crear` antes que `{id}`. `usuarios` es el único bloque que invierte ese orden — coherente con que sea el bloque de código más tardío y menos revisado del archivo de rutas.

**Cómo verificar la corrección**: una petición `GET /admin/usuarios/crear` con sesión de rol `admin` debe responder `200` y renderizar el formulario de creación. Hoy responde `404`.

**Corrección**: mover el registro de `/usuarios/crear` por encima del de `/usuarios/{id}`, o restringir el parámetro con `->whereUuid('id')`. La segunda opción es preferible porque protege también contra futuras reordenaciones.

### DEF-2 — `ServiciosController` borra el precio en cada escritura

**Severidad**: Alta — pérdida silenciosa de datos de negocio
**Ubicación**: `app/Http/Controllers/ServiciosController.php`, línea 33 (`store`) y línea 58 (`update`)
**Alias de deuda**: `TD-8`

**Qué ocurre.** Ambos métodos fijan `$data['precio'] = 0;` de forma incondicional, después de leer los datos del formulario y antes de escribir. La columna `servicios.precio` es `decimal(10,2)` y la línea base versionada trae 15 servicios con precios reales.

**Por qué importa.** Cualquier edición de un servicio desde la administración —aunque sólo se cambie el nombre— **pone su precio a cero sin ningún aviso**. La pérdida se propaga: las dos vistas SQL `v_citas_hoy` y `v_historial_paciente` exponen `s.precio`, de modo que cualquier consumidor futuro de esas vistas heredará el dato destruido.

**Agravante en el mismo método.** `store()` fuerza además `color_agenda` al valor por defecto con el comentario «Valor por defecto oculto», anulando la paleta por servicio que la línea base sí diferencia. Es coherente con la hipótesis de que ambos campos se retiraron del formulario y se neutralizaron en el controlador en lugar de conservar el valor existente.

**Cómo verificar la corrección**: crear un servicio con precio distinto de cero, editar su nombre y guardar. El precio debe conservarse. Hoy queda a cero.

**Corrección**: eliminar ambas asignaciones y, si el precio no debe editarse desde ese formulario, conservar explícitamente el valor previo en `update()` en lugar de sobrescribirlo.

### DEF-3 — Ruta de servicios registrada sin implementación

**Severidad**: Baja — hoy inalcanzable desde la interfaz
**Ubicación**: `routes/web.php` (`Route::resource('servicios', ServiciosController::class)`) y `app/Http/Controllers/ServiciosController.php`

**Qué ocurre.** `Route::resource` genera las siete rutas del recurso, incluida `GET /admin/servicios/{servicio}` con nombre `admin.servicios.show`. Pero **`ServiciosController` no tiene método `show()`** — sus métodos públicos son `index`, `create`, `store`, `edit`, `update` y `destroy` — y tampoco existe la vista `resources/views/servicios/show.blade.php`.

**Por qué es de severidad baja.** Una búsqueda en todo el repositorio confirma que **nada enlaza a `admin.servicios.show`**, de modo que la ruta es hoy inalcanzable navegando. Invocar la URI directamente produciría un error del servidor.

**Cómo verificar la corrección**: o bien `GET /admin/servicios/{id}` responde `200` con una ficha, o bien la ruta deja de estar registrada.

**Corrección**: declarar el recurso con `->except(['show'])`, o implementar el método y su vista.

---

## 3. Hallazgos de seguridad

### SEC-1 — Datos personales sensibles y credenciales versionados

**Severidad**: **Crítica**
**Alias de deuda**: `TD-2`

Este es el riesgo más serio del proyecto. Hay **dos vías de exposición distintas**, y ambas están en la historia de git, de modo que **siguen siendo recuperables aunque se borren en un commit futuro**.

**Vía 1 — `database/dentissa.sql`.** El archivo no es un esquema: es un volcado **con datos**. Contiene, en filas de ejemplo:

| Tabla | Filas | Categorías de dato expuestas |
| --- | --- | --- |
| `usuarios` | 8 | Nombres y apellidos, direcciones de correo, números de teléfono y **hashes de contraseña bcrypt** |
| `pacientes` | 6 | Fecha de nacimiento, género, domicilio, ciudad, contacto de emergencia y **datos de salud: tipo de sangre y alergias** |
| `sessions` | 1 | Carga útil de sesión de un navegador identificable |

Los datos de salud (tipo de sangre, alergias, y las columnas previstas para enfermedades crónicas y medicamentos) son **categorías especiales de datos personales** en prácticamente cualquier marco de protección de datos. Su presencia en un repositorio de código, sin control de acceso y con historia completa, es la exposición de mayor impacto de este análisis.

**Vía 2 — `README.md`.** El archivo termina, después de la sección de licencia, con **tres contraseñas en texto plano** correspondientes a cuentas de prueba de los roles dentista, usuario y asistente.

**Agravante detectado**: una de esas tres credenciales corresponde al rol `asistente`, y la línea base versionada **no contiene ningún usuario con ese rol**. Es decir, el `README.md` documenta credenciales de una cuenta que el volcado no crea — señal de que las contraseñas se copiaron de un entorno real de trabajo, no de un juego de datos sintético.

**Lo que sí está bien**: `.env` está correctamente listado en `.gitignore` y no está versionado. Este análisis no lo abrió.

**Cómo verificar la corrección**: `git log -p -- database/dentissa.sql README.md` no debe mostrar en ninguna revisión hashes, correos reales, teléfonos ni datos clínicos.

**Remediación recomendada, en este orden**:

1. **Rotar de inmediato** todas las credenciales afectadas y cualquier secreto de Google que haya podido compartir entorno con ellas. La rotación es lo primero porque es lo único que reduce el riesgo mientras el resto se ejecuta.
2. **Sustituir el volcado por un esquema sin datos** más un seeder con datos sintéticos. Esto se resuelve de forma natural al abordar `TD-1`.
3. **Retirar las tres credenciales del `README.md`** y sustituirlas por instrucciones de creación de cuentas de prueba.
4. **Reescribir la historia de git** para purgar los archivos afectados, coordinando el forzado con todos los clones existentes.
5. **Documentar la prohibición** de versionar volcados con datos, y considerar un hook o una comprobación automática que lo impida.

### SEC-2 — El grupo `cliente` no verifica el rol, con efecto de escritura

**Severidad**: Media
**Ubicación**: `routes/web.php`, línea 148; `app/Http/Controllers/PerfilController.php`, método `update()`
**Alias de deuda**: `TD-6`

**Qué ocurre.** Los grupos `admin`, `dentista` y `asistente` declaran `Route::middleware(['auth','role:<rol>'])`. El grupo `cliente` declara únicamente `Route::middleware('auth')`. **Cualquier usuario autenticado, sea cual sea su rol, puede entrar a `/cliente/*`.**

**Por qué el impacto no es de lectura.** Los controladores del cliente filtran por `pacientes.usuario_id` contra el usuario autenticado y responden `404` cuando no encuentran fila de paciente. Un miembro del personal que entre a `/cliente/citas` no ve datos ajenos.

**Por qué el impacto sí es de escritura.** `PerfilController::update()` hace un `insert` en `pacientes` cuando no encuentra fila para el usuario actual. Es decir: **un `admin`, un `dentista` o un `asistente` que guarde el formulario de `/cliente/perfil` se crea a sí mismo un registro de paciente espurio**, con su propio nombre, dentro del padrón de pacientes de la clínica. Ese registro contaminaría los recuentos de los paneles y podría llegar a recibir una asignación de dentista.

**Cómo verificar la corrección**: iniciar sesión con rol `dentista` y solicitar `GET /cliente/perfil` debe responder `403`. Hoy responde `200`.

**Corrección**: añadir `role:cliente` al grupo, y sustituir la inserción implícita de `PerfilController::update()` por una comprobación explícita que responda `404` cuando no exista fila de paciente. Ambas correcciones son necesarias: la primera cierra el acceso, la segunda elimina el comportamiento sorprendente incluso para un cliente legítimo.

### SEC-3 — `UsuarioController` sin validación, con contraseña en código y borrado destructivo

**Severidad**: Media, con un componente de **elevación de privilegios**
**Ubicación**: `app/Http/Controllers/UsuarioController.php`, métodos `store()`, `update()` y `destroy()`
**Alias de deuda**: `TD-16`

Tres problemas en el mismo archivo:

**1. Ausencia total de validación.** Es el único controlador del proyecto que no llama a `validate()`. `store()` y `update()` leen `nombre`, `email`, `rol`, `telefono` y `activo` directamente de la petición. Faltan la unicidad del correo —que la base de datos sí impone, de modo que un correo repetido produce un error de servidor en lugar de un mensaje— y, sobre todo, **falta la restricción `in:` sobre `rol`**.

**Por qué eso es elevación de privilegios.** `rol` es un campo asignable en masa del modelo `Usuario`. Sin validación, un valor arbitrario llega directamente a la columna. La restricción de la base de datos (`enum` de cuatro valores) evita que se escriba un rol inventado, pero **no evita que se escriba `admin`**. Combinado con la ausencia de una comprobación de que quien edita no se está promoviendo a sí mismo, el formulario de edición de usuarios es la vía más directa de escalada del sistema.

**2. Contraseña genérica escrita en el código.** `store()` asigna una contraseña fija codificada en el propio archivo a todo usuario creado desde ese formulario. Es un valor conocido, idéntico para todas las altas, y contrasta con el patrón correcto que el propio proyecto ya usa en `PersonalController::store()` y `PacientesController::store()`, donde se genera una contraseña aleatoria y se muestra una única vez.

**3. Borrado físico con consecuencias en cascada.** `destroy()` borra la fila de `usuarios`. Por las claves foráneas de la línea base, eso propaga borrado en cascada a `pacientes` y a `personal` —perdiendo el expediente asociado— mientras que `citas.paciente_id` no declara acción de borrado, es decir, es restrictiva. El resultado combinado: **borrar un usuario cuyo paciente tiene citas lanza una excepción SQL no capturada y devuelve un error `500`** en lugar de un mensaje. Y borrar uno sin citas destruye su historia clínica en silencio.

**Cómo verificar la corrección**: (a) enviar `rol=admin` desde una sesión que no deba poder promover debe ser rechazado; (b) el alta debe generar una contraseña aleatoria distinta en cada uso; (c) la acción de baja debe desactivar, no eliminar.

**Corrección**: añadir validación con `unique:usuarios,email` e `in:cliente,asistente,dentista,admin`; sustituir la contraseña fija por el patrón `Str::random(10)` ya presente en el proyecto; y reemplazar `destroy()` por la desactivación lógica `toggleActivo` que el resto del sistema ya usa.

---

## 4. Cobertura de pruebas

### 4.1 Estado

| Elemento | Estado |
| --- | --- |
| Directorios | `tests/Unit/`, `tests/Feature/` |
| Archivos | 3: `TestCase.php`, `Unit/ExampleTest.php`, `Feature/ExampleTest.php` |
| Framework | PHPUnit `12.5.30`, suites `Unit` y `Feature` en `phpunit.xml` |
| Dobles de prueba | `mockery/mockery` instalado, **sin ningún uso** |
| Otros | Sin Pest, sin Dusk, sin pruebas de frontend |
| **Cobertura efectiva del dominio** | **Cero.** Ninguna de las 2.545 líneas de `app/` está ejercitada |
| Configuración de cobertura | **Ausente.** `phpunit.xml` delimita el ámbito de instrumentación a `app`, pero no hay umbral, ni informe, ni extensión (Xdebug o PCOV) declarada |

### 4.2 TD-13 — La única prueba existente falla hoy

`tests/Feature/ExampleTest.php` afirma que `GET /` responde `200`. La closure de la ruta raíz **siempre devuelve una redirección** (`302`): a `/login` si no hay sesión, o al panel del rol si la hay. El criterio de aprobación es determinista y no se cumple.

**Como no hay integración continua, nadie lo detecta.** El impacto directo es nulo —no hay nada que proteger— pero la señal es fuerte: indica que `composer test` no se ejecuta como parte del trabajo habitual.

### 4.3 Por qué la cobertura cero está bloqueada técnicamente

Este es el punto que conviene entender antes de proponer «escribir pruebas»:

`phpunit.xml` configura el entorno de prueba con `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:`. Pero **el esquema de dominio sólo existe en `database/dentissa.sql`**, un volcado de MariaDB con `DEFAULT uuid()`, `enum(...)` y un trigger — sintaxis que SQLite no acepta. Y **ninguna migración crea las 9 tablas de dominio**.

**Consecuencia**: hoy no hay forma de levantar una base de datos de prueba con el dominio. Escribir la primera prueba significativa de este proyecto exige, obligatoriamente, **escribir antes las migraciones que faltan** (`TD-1`). No es una cuestión de disciplina: es un bloqueo técnico.

---

## 5. Formateo, análisis estático e integración continua

### 5.1 Formateo

`laravel/pint` `v1.29.3` está instalado como dependencia de desarrollo, pero **no hay `pint.json`** —se aplicaría el preset `laravel` por defecto— y **ningún guion de `composer.json` lo invoca**.

**Evidencia directa de que no se ejecuta**: hay bloques enteros con la **indentación colapsada a la columna 0** dentro del cuerpo de métodos, en al menos tres controladores (`CitasController`, `PacientesController` y `AuthController`). Un formateador ejecutado alguna vez habría eliminado esa anomalía.

**Nota práctica para la corrección**: ejecutar Pint hoy reformatearía una parte sustancial del código y produciría un diff enorme. Conviene hacerlo en un **commit aislado y exclusivo**, antes de cualquier otro cambio, para que los diffs posteriores sigan siendo revisables.

### 5.2 Análisis estático

Ausente por completo. No hay PHPStan, Psalm, Larastan, ESLint ni Prettier. **Un análisis estático habría detectado `DEF-3`** —la ruta que apunta a un método inexistente— sin ninguna intervención humana.

### 5.3 Integración continua

**Inexistente.** No hay `.github/`, ni `.gitlab-ci.yml`, ni `Jenkinsfile`, ni `.circleci/`, ni `bitbucket-pipelines.yml`. Tampoco hay hooks de pre-commit. Los badges de construcción que aparecen en el `README.md` son los del framework Laravel y apuntan al repositorio de Laravel, **no a este proyecto** — es decir, muestran un estado verde que no dice nada sobre Dentissa.

### 5.4 Documentación

El `README.md` es el **genérico de Laravel sin personalizar**: describe el framework, no el sistema dental. No documenta el dominio, ni los roles, ni cómo levantar el proyecto con Docker, ni —lo más grave— el hecho de que el esquema vive en `database/dentissa.sql` y de que `composer setup` no funciona.

No existe ningún otro documento: ni `CONTRIBUTING.md`, ni `CHANGELOG.md`, ni ADRs, ni diagramas, ni carpeta `docs/`.

**Calidad de los comentarios en el código**: densidad moderada y desigual. Hay ruido (separadores decorativos por sección) y hay un archivo comentado en registro didáctico numerado que desentona con el resto. Pero también hay tres comentarios genuinamente valiosos, que documentan conocimiento no deducible del código: la explicación del balanceo de dentistas, la justificación de por qué el resumen de citas se calcula antes del filtro de estado, y —el más importante— **el único lugar de todo el repositorio que menciona que existe un trigger de base de datos**. En cambio, no hay ni un solo bloque de documentación con tipos en ningún controlador.

---

## 6. Registro de deuda técnica

Las señales `TD-2`, `TD-6`, `TD-8`, `TD-11` y `TD-16` están desarrolladas arriba como `SEC-1`, `SEC-2`, `DEF-2`, `DEF-1` y `SEC-3` respectivamente; aquí se listan por completitud del registro.

| ID | Señal | Riesgo | Ubicación |
| --- | --- | --- | --- |
| `TD-1` | **El esquema de dominio no es reproducible por migraciones.** Ninguna migración crea las 9 tablas de dominio; dos alteran tablas que nadie crea. `composer setup` aborta contra una base de datos vacía. El único arranque que funciona es Docker, por una secuencia no documentada | **Alto** | `database/migrations/`, `composer.json`, `docker-compose.yml` |
| `TD-2` | Datos personales sensibles y credenciales versionados → **`SEC-1`** | **Alto** | `database/dentissa.sql`, `README.md` |
| `TD-3` | **Dos tablas de usuarios coexistentes.** El proveedor apunta a `App\Models\Usuario` sobre `usuarios`, pero sobreviven la tabla `users`, el modelo `App\Models\User`, `UserFactory` y `DatabaseSeeder`. Verificado: **ningún archivo referencia `App\Models\User`** | Medio | `config/auth.php`, `app/Models/`, `database/` |
| `TD-4` | **Defecto latente en el almacenamiento de sesiones.** `config/session.php` resuelve el `driver` a `database` por defecto; `.env.example` lo fija a `file`, lo que hoy enmascara el problema. Sin esa variable, Laravel escribiría en `sessions`, cuya columna de usuario es un entero heredado mientras que `usuarios.id` es un UUID. Con el modo estricto activado, la escritura falla | Medio | `config/session.php`, `config/database.php` |
| `TD-5` | **Acceso a datos por consultas crudas en los controladores.** 111 invocaciones de query builder y 85 cruces, sin repositorios, servicios de dominio, Form Requests, Policies, eventos ni jobs. Duplicación medida: la consulta base de citas reproducida en 4 lugares, el ayudante de dentistas disponibles copiado literalmente en 2, y **la regla de agenda completa duplicada** en `CitasController` y `ClienteCitasController` | Medio | Todo `app/Http/Controllers/` |
| `TD-6` | El grupo `cliente` no verifica el rol → **`SEC-2`** | Medio | `routes/web.php` |
| `TD-7` | **Doble escritura del historial de citas.** El trigger inserta una fila con la transición pero sin autor ni motivo; el controlador inserta otra con autor y motivo pero sin estado anterior. **Ninguna de las dos es completa**, y la vista de detalle renderiza ambas. El propio código reconoce la duplicidad en un comentario | Medio | `database/dentissa.sql`, `CitasController::updateEstado` |
| `TD-8` | `ServiciosController` destruye el precio → **`DEF-2`** | Medio | `ServiciosController` |
| `TD-9` | **Cadena de assets muerta.** `@vite` aparece una sola vez, en una plantilla que ninguna ruta renderiza. Vite, Tailwind y axios están instalados sin producir nada que la aplicación consuma. Es la causa de que la detección automática de la pila reportara «Vite» en lugar de Laravel | Bajo, pero desorienta el diagnóstico | `package.json`, `vite.config.js`, `resources/` |
| `TD-10` | **Anidación de layouts.** Tres plantillas de edición extienden el layout y, dentro de su sección de contenido, incluyen la vista `create` correspondiente, que **también** lo extiende. El formulario compartido debería ser un partial sin extensión | Bajo | `pacientes/edit`, `personal/edit`, `usuarios/edit` |
| `TD-11` | Orden de rutas que rompe el alta de usuarios → **`DEF-1`** | Medio | `routes/web.php` |
| `TD-12` | **Ruta duplicada y closure muerta.** `GET /admin/usuarios` se registra dos veces: como closure que devuelve un marcador de posición y como acción del controlador. El registro posterior gana la resolución, de modo que la funcionalidad servida es la correcta, pero el nombre sigue apuntando a la closure muerta | Bajo | `routes/web.php` |
| `TD-13` | **La única prueba existente falla.** Espera `200` en `GET /`, la aplicación devuelve `302`. Sin integración continua, nadie lo detecta | Bajo en impacto, **alto en señal** | `tests/Feature/ExampleTest.php` |
| `TD-14` | **Montaje de configuración PHP roto en Docker.** Se monta `./docker/php/local.ini`, pero el archivo real está en `docker/nginx/php/local.ini`. Docker crea un directorio vacío y los ajustes previstos —límite de subida, límite de memoria y **zona horaria**— **nunca se aplican**. Para un sistema de agenda, la zona horaria es especialmente relevante | Bajo en apariencia, **relevante para la agenda** | `docker-compose.yml` |
| `TD-15` | **Credenciales de base de datos incoherentes.** `.env.example` declara un usuario que el servicio de base de datos no crea. Además, se permite contraseña vacía, el puerto de base de datos está publicado al anfitrión y phpMyAdmin queda expuesto sin restricción de red | Bajo en local, **alto si se replica fuera** | `.env.example`, `docker-compose.yml` |
| `TD-16` | `UsuarioController` sin validación y con borrado destructivo → **`SEC-3`** | Medio | `UsuarioController` |
| `TD-17` | **Funcionalidad declarada en el esquema pero no implementada.** `citas.recordatorio_enviado` sólo se lee, nunca se escribe, y no hay ningún job, mailable, notification ni tarea programada en todo `app/`. `expedientes.archivos_url` se inserta siempre nulo, es decir, la carga de radiografías está pendiente. Las dos vistas SQL no las consulta ninguna línea de PHP | Bajo | `database/dentissa.sql`, `CitasController`, `ExpedientesController` |
| `TD-18` | **Higiene menor.** Un `</div>` sin abrir en el bloque del logo del layout. El layout invoca `filemtime()` en cada render, un acceso al sistema de ficheros por petición. `PreventBackHistory` se aplica a **todo** el grupo `web`, incluidas las páginas públicas. `public/storage` es un enlace simbólico a una ruta absoluta de contenedor, inutilizable fuera de Docker. `.dockerignore` tiene una sola línea, de modo que la imagen copia `node_modules/` y `vendor/` del anfitrión si están presentes | Bajo | Varios |
| `TD-19` | **Sin transacción ni bloqueo en la creación de citas.** Entre la comprobación de solape y la inserción no hay transacción, ni bloqueo de fila, ni restricción de exclusión en el esquema. **Dos peticiones concurrentes para el mismo hueco pueden crear dos citas solapadas**, violando la regla de negocio `BRN-6` | Medio | `CitasController::store`, `ClienteCitasController::agendarStore` |
| `TD-20` | **La línea base no siembra ninguna fila de `horarios_dentista`.** Verificado sobre el volcado: cero filas en esa tabla y cero en `expedientes`. Como la comprobación 1 de la regla de agenda exige una fila de horario para el día solicitado, **tras un arranque limpio no puede agendarse ninguna cita**. El sistema arranca, permite iniciar sesión y muestra los paneles con normalidad; sólo falla al agendar, con un mensaje que parece un problema de configuración del dentista y no un estado inicial vacío. La línea base tampoco siembra ningún usuario con rol `asistente`, de modo que ese rol no puede ejercitarse sin crearlo antes | Medio | `database/dentissa.sql` |

---

## 7. Plan de remediación priorizado

El orden responde a dos criterios: **primero lo que reduce riesgo irreversible**, después **lo que desbloquea todo lo demás**.

### Prioridad 0 — Inmediato, no admite espera

| Acción | Resuelve | Por qué es lo primero |
| --- | --- | --- |
| **Rotar todas las credenciales expuestas**, incluidas las de las tres cuentas del `README.md` y cualquier secreto que haya compartido entorno con ellas | `SEC-1` | Es la única acción que reduce el riesgo mientras el resto del plan se ejecuta. Los datos ya están en la historia de git y no dejarán de estarlo por sí solos |

### Prioridad 1 — Desbloquea el resto del trabajo

| Acción | Resuelve | Efecto en cadena |
| --- | --- | --- |
| **Escribir las migraciones de las 9 tablas de dominio**, tomando como especificación el modelo de datos ya reconstruido en `architecture.md` | `TD-1` | Desbloquea a la vez: (a) que `composer setup` funcione, (b) que se pueda levantar un esquema de prueba y por tanto **escribir la primera prueba de dominio**, y (c) que el volcado deje de ser necesario como línea base |
| **Reducir `database/dentissa.sql` a datos sintéticos**, o eliminarlo en favor de un seeder | `SEC-1`, `TD-20` | Retira los datos personales del repositorio y permite sembrar `horarios_dentista` y una cuenta `asistente`, de modo que el sistema sea operable tras un arranque limpio |
| **Retirar las credenciales del `README.md` y escribir uno real** que documente el dominio, los roles y el arranque | `SEC-1` | Es el documento que todo el mundo lee primero |
| **Purgar la historia de git** de los archivos afectados, coordinando el forzado con todos los clones | `SEC-1` | Cierra definitivamente la exposición |

### Prioridad 2 — Defectos y huecos de seguridad, correcciones acotadas

| Acción | Resuelve | Tamaño estimado |
| --- | --- | --- |
| Restringir el parámetro de la ruta de usuarios, o reordenar los registros | `DEF-1` | Una línea |
| Eliminar las dos asignaciones que borran el precio | `DEF-2` | Dos líneas |
| Añadir `role:cliente` al grupo y sustituir la inserción implícita del perfil por una comprobación explícita | `SEC-2` | Pocas líneas |
| Añadir validación a `UsuarioController`, sustituir la contraseña en código por generación aleatoria y cambiar el borrado físico por desactivación lógica | `SEC-3` | Un archivo |
| Declarar el recurso de servicios sin la ruta de ficha, o implementarla | `DEF-3` | Una línea |
| Corregir la ruta de montaje de la configuración PHP en Docker | `TD-14` | Una línea, y devuelve la zona horaria correcta a la agenda |

### Prioridad 3 — Red de seguridad

| Acción | Resuelve |
| --- | --- |
| Arreglar o retirar la prueba de la ruta raíz, para que la suite quede en verde | `TD-13` |
| Ejecutar el formateador en un **commit aislado y exclusivo**, y añadir un guion que lo invoque | Formateo |
| Añadir integración continua que ejecute, como mínimo, el formateador en modo comprobación y la suite de pruebas | Integración continua |
| Añadir análisis estático — habría detectado `DEF-3` sin intervención humana | Análisis estático |
| Escribir las primeras pruebas de dominio, empezando por **las tres comprobaciones de la regla de agenda**, que es la lógica de mayor valor y mayor riesgo | Cobertura |

### Prioridad 4 — Deuda estructural

| Acción | Resuelve |
| --- | --- |
| Extraer la regla de agenda a un **único** servicio de dominio invocado por los dos controladores, y envolver la comprobación y la inserción en una transacción | `TD-5`, `TD-19` |
| Elegir un único dueño para la bitácora de estados y eliminar el otro camino de escritura | `TD-7` |
| Eliminar el andamiaje muerto: el modelo `User`, su fábrica, el seeder y la tabla `users` | `TD-3`, `TD-4` |
| Retirar las cinco dependencias de npm inertes, o adoptar la cadena de assets de verdad | `TD-9` |
| Extraer los formularios compartidos a partials sin extensión de layout | `TD-10` |
| Introducir repositorios o modelos Eloquent completos para retirar las 111 llamadas de query builder de los controladores | `TD-5` |

**Nota sobre el orden**: la prioridad 4 debe ir **después** de la prioridad 3. Refactorizar 111 accesos a datos sin ninguna prueba que proteja el comportamiento es la forma más eficaz de introducir regresiones invisibles en este proyecto.

---

## 8. Alcance de esta evaluación

Este documento afirma únicamente lo que se verificó directamente.

**Verificado en profundidad**: los 19 archivos de `app/`, ambos archivos de `routes/`, `bootstrap/`, las 6 migraciones, el volcado SQL completo con sus bloques de datos, los 3 archivos de `tests/`, los cuatro manifiestos de construcción con sus lockfiles, toda la configuración de Docker y 4 de los 10 archivos de `config/`. De las 37 plantillas Blade, 6 se leyeron íntegras y las 37 se verificaron mediante un barrido estructural exhaustivo (rutas generadas, formularios, `@csrf`, bloques de script, ausencia de salida sin escapar y ausencia de llamadas asíncronas).

**No verificado línea a línea, y por tanto no afirmado aquí**:

- Las **30 plantillas Blade** restantes. Este documento **no hace ninguna afirmación sobre maquetación, accesibilidad ni consistencia visual**; esa evaluación requiere una segunda pasada.
- Los **6 archivos de `config/`** que son los valores por defecto de Laravel 12. Se inventariaron, pero no puede afirmarse que estén intactos.
- Las **1.072 líneas de `public/css/`**. Se verificó su existencia, su tamaño y el hecho de que el layout las carga; el detalle de las reglas no se analizó.
- `vendor/` y `node_modules/`, dependencias de terceros no versionadas. Sus versiones se leyeron de los lockfiles.
- El archivo `.env`, que **no se abrió** de forma deliberada.

---

## Sources

- `app/Http/Controllers/` — los 13 archivos leídos íntegros; `ServiciosController.php` (líneas de asignación de precio), `UsuarioController.php` (métodos sin validación y borrado), `PerfilController.php` (inserción implícita), `CitasController.php` (secuencia de comprobación e inserción)
- `routes/web.php` — orden de registro de las rutas de usuarios verificado directamente
- `resources/views/servicios/` — ausencia de plantilla de ficha; búsqueda de referencias a la ruta de ficha sin resultados
- `database/dentissa.sql` — bloques `INSERT` contados por tabla; ausencia verificada de filas en `horarios_dentista` y `expedientes`; distribución de roles en las filas de usuarios
- `README.md` — presencia de credenciales en texto plano al final del archivo, **sin reproducir ningún valor**
- `database/migrations/` — las 6 migraciones; `composer.json` (guion de arranque)
- `phpunit.xml`, `tests/`, `config/session.php`, `config/database.php`, `config/auth.php`
- `docker-compose.yml`, `Dockerfile`, `.dockerignore`, `.editorconfig`, `.gitignore`
- Búsqueda exhaustiva de archivos de integración continua en la raíz del repositorio: sin resultados
- Escaneo del desarrollador: `aidlc/spaces/default/intents/260902-docs-sistema-dental/inception/reverse-engineering/developer-scan.md`
