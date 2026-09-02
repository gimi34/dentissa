# Estructura del código — Dentissa

**Commit analizado**: `9fcff4c635d5134d9f252b6158812c4dd15e2657` (`main`, 2026-09-02)
**Organización**: paquete único (monolito Laravel), sin workspaces ni submódulos

---

## 1. Magnitud real del repositorio

| Métrica | Valor | Nota |
| --- | --- | --- |
| Archivos versionados | 132 | |
| Archivos `.php` versionados | 82 | 45 de código y 37 plantillas Blade |
| Líneas en `app/` | 2.545 | Toda la lógica de negocio |
| Líneas en `resources/views/` | 5.851 | 37 plantillas |
| Líneas en `public/css/` | 1.072 | CSS artesanal, el estilo real del sistema |
| Líneas en `routes/web.php` | 161 | Toda la superficie HTTP |
| Líneas en `database/dentissa.sql` | 671 | Línea base del esquema |
| Archivos `.php` en disco | 7.859 | `vendor/` está instalado pero **no** versionado |
| Commits | 25 | 4 autores |
| Archivo con más rotación | `routes/web.php` | Tocado en 7 commits |

La diferencia entre 82 archivos versionados y 7.859 en disco es enteramente `vendor/`. Cualquier recuento automático que no excluya ese directorio dará una imagen falsa del tamaño del proyecto.

---

## 2. Árbol del repositorio

```
dentissa/
├── app/                          Código de aplicación — 2.545 líneas
│   ├── Http/
│   │   ├── Controllers/          13 archivos — TODO el dominio vive aquí
│   │   └── Middleware/           2 archivos — autorización y cabeceras
│   ├── Models/                   3 archivos — Usuario, Servicio, User (muerto)
│   └── Providers/                1 archivo — register() y boot() vacíos
├── bootstrap/
│   ├── app.php                   Alias de middleware, grupo web, health en /up
│   ├── providers.php             Registro de AppServiceProvider
│   └── cache/                    Generado, ignorado
├── config/                       10 archivos — 4 personalizados, 6 por defecto
├── database/
│   ├── dentissa.sql              LÍNEA BASE REAL del esquema — 671 líneas
│   ├── migrations/               6 archivos — sólo el andamiaje y el delta de 2026-07
│   ├── seeders/                  1 archivo — siembra en la tabla equivocada
│   └── factories/                1 archivo — fábrica del modelo muerto
├── docker/
│   └── nginx/
│       ├── dentissa.conf         Servidor virtual, raíz en public/
│       └── php/local.ini         NUNCA se aplica — ruta de montaje incorrecta
├── public/
│   ├── index.php                 Punto de entrada único
│   ├── css/                      1.072 líneas — el estilo REAL de la aplicación
│   ├── images/                   Recursos estáticos
│   ├── storage                   Enlace simbólico a una ruta absoluta de contenedor
│   ├── .htaccess, robots.txt, favicon.ico
├── resources/
│   ├── views/                    37 plantillas Blade en 11 carpetas
│   ├── css/app.css               Cadena Tailwind — INERTE
│   └── js/                       app.js y bootstrap.js — INERTES
├── routes/
│   ├── web.php                   161 líneas — toda la superficie HTTP
│   └── console.php               Sólo el comando de ejemplo inspire
├── storage/                      Sólo archivos .gitignore de estructura
├── tests/
│   ├── TestCase.php
│   ├── Unit/ExampleTest.php      Andamiaje de Laravel
│   └── Feature/ExampleTest.php   Andamiaje de Laravel — FALLA hoy
├── composer.json / composer.lock  325 paquetes resueltos
├── package.json / package-lock.json
├── vite.config.js                Configura una cadena que nada consume
├── phpunit.xml                   Suites Unit y Feature, SQLite en memoria
├── Dockerfile                    php:8.4-fpm
├── docker-compose.yml            app, nginx, db, phpmyadmin
├── .editorconfig                 UTF-8, LF, 4 espacios
└── README.md                     Genérico de Laravel + credenciales en claro
```

---

## 3. Clasificación de archivos

| Clase | Archivos | Peso | Observación |
| --- | --- | --- | --- |
| **Lógica de negocio** | 13 controladores | 1.972 líneas | Concentra dominio, acceso a datos y validación |
| **Infraestructura HTTP** | 2 middleware, `bootstrap/app.php`, `public/index.php` | ~90 líneas | |
| **Modelos** | 3 | 91 líneas | Sólo 2 en uso; `User` no está referenciado en ninguna parte |
| **Enrutado** | `routes/web.php`, `routes/console.php` | 168 líneas | |
| **Presentación** | 37 plantillas Blade | 5.851 líneas | Más del doble que `app/` |
| **Estilo** | 3 hojas en `public/css/` | 1.072 líneas | Servidas directamente, sin construcción |
| **Esquema y datos** | `dentissa.sql`, 6 migraciones, 1 seeder, 1 factory | ~800 líneas | El SQL es la fuente de verdad |
| **Configuración** | 10 archivos en `config/` | — | 4 personalizados, 6 por defecto de Laravel |
| **Entorno** | `Dockerfile`, `docker-compose.yml`, 2 archivos en `docker/` | — | Sólo local |
| **Pruebas** | 3 archivos | ~40 líneas | Andamiaje sin valor de dominio |
| **Código muerto** | `App\Models\User`, `UserFactory`, `DatabaseSeeder`, `resources/js/`, `resources/css/`, `welcome.blade.php` | ~120 líneas | Ver sección 7 |

**Dato estructural revelador**: la presentación pesa **2,3 veces más** que toda la lógica de negocio. Es coherente con un sistema cuya complejidad está en las pantallas por rol, no en el cálculo.

---

## 4. Organización de `app/Http/Controllers/`

Los trece archivos, ordenados por tamaño:

| Archivo | Líneas | Responsabilidad |
| --- | --- | --- |
| `PersonalController.php` | 422 | Personal, horarios semanales y asignación de asistentes |
| `CitasController.php` | 398 | Agenda del personal: alcance por rol, disponibilidad y ciclo de vida |
| `PacientesController.php` | 337 | Alta, edición, baja lógica y ficha de pacientes |
| `DashboardController.php` | 232 | Cuatro paneles, uno por rol |
| `ClienteCitasController.php` | 204 | Agenda desde la perspectiva del cliente |
| `ExpedientesController.php` | 197 | Expediente clínico |
| `RegisterController.php` | 179 | Registro directo y segundo paso del registro por Google |
| `UsuarioController.php` | 116 | Administración de usuarios |
| `PerfilController.php` | 102 | Perfil y contraseña del cliente |
| `ServiciosController.php` | 71 | Catálogo de tratamientos |
| `GoogleAuthController.php` | 63 | OAuth 2.0 con Google |
| `AuthController.php` | 50 | Acceso local, salida y despacho por rol |
| `Controller.php` | 11 | Clase base abstracta de Laravel |

### 4.1 Anatomía de un controlador típico

Todos siguen la misma forma, ilustrada por `CitasController`:

```
class CitasController extends Controller
{
    // ── Helpers privados ──────────────────────────
    private function queryBase()              // consulta base con 5 joins y 2 DB::raw
    private function personalIdPropio()       // resuelve la fila de personal del usuario
    private function dentistaIdsAsistente()   // consulta la tabla puente
    private function aplicarAlcanceRol()      // filtro de filas por rol
    private function autorizadoParaCita()     // guarda puntual con abort(403)

    // ── Acciones HTTP ─────────────────────────────
    public function index()        // listado con filtros y resumen
    public function create()       // formulario, precarga proxima_visita
    public function store()        // validación + 3 comprobaciones + INSERT
    public function show()         // detalle con historial
    public function updateEstado() // transición de estado + historial
    public function updateNotas()  // notas internas
    public function destroy()      // borrado lógico: UPDATE a cancelada
}
```

Cada acción concentra cuatro responsabilidades que en una arquitectura por capas estarían separadas: **validación de entrada**, **autorización**, **regla de negocio** y **acceso a datos**. No existe ninguna capa intermedia.

---

## 5. Patrones de código observados

### 5.1 Patrones aplicados con consistencia

| Patrón | Cómo se manifiesta | Dónde |
| --- | --- | --- |
| **Ayudantes privados por controlador** | Métodos `private` en la cabecera del archivo, antes de las acciones HTTP | `CitasController`, `PacientesController`, `PersonalController` |
| **Baja lógica con `toggleActivo`** | Acción `PATCH` que invierte la columna `activo` | Pacientes y personal |
| **Reescritura completa en lugar de diferencia** | Borrar todas las filas e insertar las nuevas | `saveHorarios()`, `sincronizarDoctoresAsignados()` |
| **Contraseña generada mostrada una sola vez** | `Str::random(10)` devuelto en sesión bajo `password_generada`, renderizado por el layout con aviso y botón de copiado | `PersonalController::store`, `PacientesController::store` |
| **Despacho por rol con `match`** | Expresión `match` sobre `usuarios.rol` que devuelve la ruta de destino | `AuthController`, `GoogleAuthController` y la closure de `/` — **tres copias** |
| **Prefijo de ruta como variable de vista** | Las plantillas reciben `$base` (`admin`, `dentista`, `asistente`) y construyen `route($base . '.citas.show')` | Todas las vistas compartidas entre roles |
| **Validación en línea con mensajes en español** | `$request->validate([...], [...])` dentro de la acción | Todos los controladores **salvo** `UsuarioController` |

### 5.2 Patrón de plantilla Blade

Todas las plantillas de la aplicación siguen la misma estructura:

```
@extends('layouts.app')

@section('titulo', '...')

@section('content')
    ... marcado con guardas por rol: @if($base === 'admin') ...
    <form method="POST" action="{{ route(...) }}">
        @csrf
        @method('PUT')   <-- cuando procede
    </form>
@endsection

@push('scripts')
    <script> ... JavaScript vanilla en línea ... </script>
@endpush
```

El barrido estructural de las 37 plantillas confirmó tres propiedades relevantes para la seguridad y el mantenimiento:

1. **Todos los formularios incluyen `@csrf`** y declaran su método con `@method` cuando no son `POST`.
2. **No hay ni una sola salida sin escapar** (`{!! !!}`) en las 37 plantillas — la interpolación siempre es `{{ }}`, con escapado automático.
3. **No hay ninguna llamada AJAX.** El JavaScript en línea de las 8 plantillas que lo tienen es enteramente vanilla y de interfaz: alternar la visibilidad de la contraseña, asistente de registro en dos pasos, modales de confirmación de borrado, cálculo de la duración del servicio y activación de días de horario.

### 5.3 Anti-patrones y desviaciones

| Anti-patrón | Manifestación | Coste |
| --- | --- | --- |
| **Regla de negocio duplicada** | Las tres comprobaciones de disponibilidad están escritas dos veces, en `CitasController::store()` y en `ClienteCitasController::agendarStore()`, y ya divergen en tres puntos | Cualquier cambio de política debe aplicarse dos veces |
| **Consulta base duplicada** | `queryBase()` de `CitasController` está reproducida casi íntegra en `DashboardController::admin()`, en `DashboardController::asistente()` y parcialmente en otros cinco lugares | Un cambio de esquema obliga a buscar todas las copias |
| **Ayudante copiado literalmente** | `dentistasDisponibles()` existe idéntico en `PacientesController` y en `PersonalController` | |
| **Anidación de layouts** | `pacientes/edit`, `personal/edit` y `usuarios/edit` son plantillas de 5 líneas que hacen `@extends('layouts.app')` y dentro incluyen la vista `create` correspondiente, que **también** hace `@extends` | HTML duplicado; el formulario compartido debería ser un partial sin `@extends` |
| **Registro didáctico en un solo archivo** | `UsuarioController` está comentado en estilo tutorial numerado, en contraste con el resto del código | Inconsistencia de registro; señal de autoría distinta |
| **Separadores decorativos** | Comentarios de tipo `// ── INDEX ───` en varios controladores | Ruido, aunque inofensivo |
| **Indentación colapsada** | Bloques enteros con la indentación llevada a la columna 0 dentro del cuerpo del método | El formateador está instalado pero nunca se ejecuta |

### 5.4 Comentarios de calidad genuina

No todo el comentario es ruido. Tres ejemplos aportan conocimiento que no se deduce del código:

- La explicación del balanceo de dentistas en `RegisterController` («Asigna el dentista activo con menos pacientes a su cargo»).
- La justificación de por qué el resumen de citas se calcula **antes** del filtro de estado en `CitasController`.
- El comentario de `CitasController::updateEstado()` que **documenta la interacción con el trigger de MySQL** — es la única mención en todo el repositorio de que existe lógica de negocio en la base de datos.

En cambio, **no hay ni un solo bloque PHPDoc con tipos** en ningún controlador, y los tipos de retorno declarados aparecen sólo en los dos middleware y en `RegisterController::asignarDentistaAutomatico(): ?string`.

---

## 6. Organización de `resources/views/`

37 plantillas en 11 carpetas:

| Carpeta | Plantillas | Contenido |
| --- | --- | --- |
| `auth/` | 3 | Acceso, registro y segundo paso de registro por Google |
| `citas/` | 6 | Índice, creación y detalle para personal; tres vistas dedicadas al cliente |
| `cliente/` | 1 | Perfil del cliente |
| `dashboard/` | 4 | Un panel por rol |
| `expedientes/` | 3 | Índice, creación y detalle |
| `layouts/` | 1 | `app.blade.php`, 260 líneas: menú por rol, panel de contraseña generada, enlace al CSS |
| `pacientes/` | 4 | Índice, creación, edición y ficha |
| `partials/` | 1 | `badge-estado.blade.php`, la única pieza realmente reutilizada |
| `personal/` | 4 | Índice, creación, edición, ficha y «mis doctores» |
| `servicios/` | 3 | Índice, creación y edición |
| `usuarios/` | 4 | Índice, creación, edición y ficha |
| raíz | 2 | `placeholder.blade.php` y `welcome.blade.php` (inalcanzable) |

`layouts/app.blade.php` es la pieza central de la presentación: construye el menú lateral según el rol de la sesión, renderiza el panel de contraseña generada cuando la sesión lo trae, y enlaza el CSS con una cadena de invalidación de caché calculada por `filemtime()` en cada render.

---

## 7. Código muerto identificado

Nada de lo siguiente participa en ningún flujo alcanzable. Se documenta para que no se confunda con funcionalidad.

| Elemento | Evidencia de que está muerto |
| --- | --- |
| `app/Models/User.php` | Búsqueda exhaustiva: **ningún** archivo de `app/`, `routes/`, `config/` ni `resources/` lo referencia. El proveedor de autenticación apunta a `App\Models\Usuario` |
| `database/factories/UserFactory.php` | Fábrica del modelo anterior |
| `database/seeders/DatabaseSeeder.php` | Siembra un `User` de prueba en la tabla `users`, que la aplicación no usa |
| Tabla `users` | Presente en el volcado y creada por migración; ninguna consulta la toca |
| `resources/js/app.js` y `resources/js/bootstrap.js` | Registran `window.axios`, que ninguna plantilla invoca |
| `resources/css/app.css` | Cadena Tailwind; sólo la consume `welcome.blade.php` |
| `resources/views/welcome.blade.php` | Única plantilla con `@vite`; **ninguna ruta la renderiza** — la ruta raíz siempre redirige |
| Closure de `admin.usuarios` en `routes/web.php` | Registrada, pero una ruta posterior con la misma URI gana la resolución |
| Vistas SQL `v_citas_hoy` y `v_historial_paciente` | Definidas en el volcado; ninguna línea de PHP las consulta |
| `routes/console.php` | Sólo el comando de ejemplo `inspire` |

---

## 8. Convenciones de nomenclatura

| Ámbito | Convención | Idioma |
| --- | --- | --- |
| Tablas y columnas | `snake_case` | Español (`usuarios`, `fecha_hora`, `duracion_min`) |
| Marcas de tiempo | `creado_en` / `actualizado_en` | Español — **no** las de Laravel |
| Clases | `PascalCase` con sufijo `Controller` o `Middleware` | Mezcla: `CitasController` (español), `AuthController` (inglés) |
| Métodos de acción | `camelCase`, verbos REST de Laravel | Inglés para los estándar (`index`, `store`, `update`), español para los propios (`updateEstado`, `toggleActivo`, `misDoctores`, `agendarStore`) |
| Rutas | `kebab-case` en la URI, puntos en el nombre | Español (`/admin/citas/crear`, `admin.citas.estado`) |
| Vistas | `kebab-case.blade.php` en carpetas por recurso | Español |
| Variables | `camelCase` | Español (`$fechaHora`, `$horaFinCita`, `$diaSemana`) |

**La frontera entre español e inglés es exactamente el par `users` / `usuarios`.** Todo lo que el equipo escribió está en español; todo lo que quedó del esqueleto de Laravel está en inglés. Reconocer esa frontera es la clave para orientarse rápido en el código.

---

## 9. Configuración de estilo y formato

| Herramienta | Estado | Evidencia |
| --- | --- | --- |
| `.editorconfig` | **Presente y es la única norma activa** | UTF-8, fin de línea LF, indentación de 4 espacios y 2 en YAML |
| `laravel/pint` `v1.29.3` | **Instalado pero nunca ejecutado** | No hay `pint.json` (se aplicaría el preset `laravel`) y ningún guion de `composer.json` lo invoca |
| ESLint / Prettier | Ausentes | |
| PHPStan / Psalm / Larastan | Ausentes | |

La ausencia de ejecución del formateador es directamente observable en el código: hay bloques con la indentación colapsada a la columna 0 dentro del cuerpo de métodos en al menos tres controladores. Ejecutar Pint hoy reformatearía una parte sustancial del código, lo que a su vez produciría un diff enorme y difícil de revisar — razón por la que conviene hacerlo en un commit aislado y dedicado.

---

## 10. Puntos de entrada para quien llegue nuevo

Orden de lectura recomendado para entender el sistema en el menor tiempo posible:

1. **`database/dentissa.sql`** — el esquema es la mejor descripción del dominio que existe en el repositorio. Leer las definiciones de `usuarios`, `pacientes`, `personal` y `citas` explica el 80% del modelo.
2. **`routes/web.php`** — 161 líneas que muestran la superficie completa y los cuatro grupos de autorización.
3. **`app/Http/Middleware/RoleMiddleware.php`** — 28 líneas que explican todo el control de acceso de entrada.
4. **`app/Http/Controllers/CitasController.php`**, cabecera con los cinco ayudantes privados — explica el alcance de datos por rol.
5. **`app/Http/Controllers/CitasController.php`**, método `store()` — contiene la regla de negocio más elaborada del sistema.
6. **`resources/views/layouts/app.blade.php`** — el menú por rol muestra el mapa mental de la aplicación tal como lo ve cada usuario.

---

## Sources

- Recuentos de archivos y líneas: `git ls-files`, `wc -l` sobre `app/`, `resources/views/`, `public/css/`, `routes/`
- `app/Http/Controllers/` — los 13 archivos leídos íntegros; `app/Http/Middleware/`, `app/Models/`, `app/Providers/`
- `routes/web.php`, `routes/console.php`, `bootstrap/app.php`, `bootstrap/providers.php`, `public/index.php`
- `resources/views/` — barrido estructural de las 37 plantillas y lectura íntegra de `layouts/app.blade.php`, `partials/badge-estado.blade.php`, `placeholder.blade.php`, `pacientes/edit.blade.php`, `personal/edit.blade.php`, `usuarios/edit.blade.php`
- `database/dentissa.sql`, `database/migrations/`, `database/seeders/DatabaseSeeder.php`, `database/factories/UserFactory.php`
- `.editorconfig`, `composer.json` (ausencia de guion de formateo)
- Escaneo del desarrollador: `aidlc/spaces/default/intents/260902-docs-sistema-dental/inception/reverse-engineering/developer-scan.md`
