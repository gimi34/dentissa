---
status: inferred
updated: 2026-09-28
---

# Arquitectura de Dentissa

## Resumen
Monolito Laravel 12 con MVC clásico que renderiza HTML en el servidor con Blade (aplicación
multipágina, sin API ni SPA). Casi toda la lógica de negocio y el acceso a datos viven dentro de
13 controladores. Las piezas de servicio están en `app/Agenda/`: `ReservaCitas.php` (spec 025), con la
regla de agenda, `EstadosCita.php` (spec 026), con los estados de la cita, y `HorarioDentista.php`
(spec 027), con el horario del dentista. Los Gates de citas y de personal viven en
`app/Autorizacion/` (specs 026 y 027), y la regla de validación `HoraFinPosterior`, en `app/Rules/`; no hay repositorios, Policies, Form Requests, eventos ni jobs.
El acceso a datos es casi siempre Query Builder (`DB::table` con joins manuales); solo `usuarios`
y `servicios` tienen modelo Eloquent. La autorización combina un middleware propio `role:` con
comprobaciones en cada controlador y, para los cambios de estado de las citas, los Gates
`gestionar-cita` y `cambiar-estado-cita`. El esquema del dominio no está en migraciones: vive
en el volcado `database/dentissa.sql`, que Docker carga al inicializar la base.

## Diagrama de componentes
```mermaid
flowchart LR
  B[Navegador<br/>HTML + JS inline] -->|HTTP :8000 formularios @csrf| NX[nginx:alpine]
  B -->|fuente Roboto| GF[Google Fonts]
  NX -->|estáticos| PUB[public/css, images]
  NX -->|FastCGI :9000| APP[php-fpm 8.4 · Laravel 12]
  subgraph APP_INT[Monolito Laravel]
    R[routes/web.php] --> MW[web + PreventBackHistory<br/>auth + role:]
    MW --> C[Controladores<br/>Auth · Register · Google · Dashboard<br/>Citas · ClienteCitas · Pacientes<br/>Expedientes · Personal · Servicios<br/>Usuario · Perfil]
    C --> V[Vistas Blade por módulo]
    C --> QB[DB::table Query Builder]
    C --> M[Eloquent: Usuario, Servicio]
  end
  APP --- APP_INT
  QB --> DB[(MariaDB 10.11)]
  M --> DB
  C -->|Socialite OAuth| G[Google OAuth]
  DUMP[database/dentissa.sql] -->|initdb una sola vez| DB
  MIG[database/migrations] -->|artisan migrate| DB
  PMA[phpMyAdmin :8080] --> DB
```

## Módulos
| Módulo | Ruta | Responsabilidad | Depende de |
|---|---|---|---|
| Autenticación | `app/Http/Controllers/AuthController.php`, `GoogleAuthController.php` | Login con correo, logout, OAuth con Google | `usuarios`, Socialite |
| Registro | `app/Http/Controllers/RegisterController.php` | Autorregistro de pacientes en dos pasos y asignación automática de dentista | `usuarios`, `pacientes`, `personal` |
| Paneles | `app/Http/Controllers/DashboardController.php` | Un panel por rol | `citas`, `pacientes`, `asistente_dentista` |
| Agenda (personal) | `app/Http/Controllers/CitasController.php` | Listado, alta, detalle, estado, notas y cancelación de citas con alcance por rol (vía `app/Autorizacion/AlcanceClinico.php`); el alta delega la regla de agenda en `app/Agenda/ReservaCitas.php`, y el cambio de estado y "eliminar" en `app/Agenda/EstadosCita.php` | `citas`, `horarios_dentista`, `historial_citas`, `servicios`, `expedientes` |
| Agenda (cliente) | `app/Http/Controllers/ClienteCitasController.php` | Mis citas, agendar y cancelar; agendar delega la regla de agenda en `app/Agenda/ReservaCitas.php`, y cancelar en `app/Agenda/EstadosCita.php` | `citas`, `horarios_dentista`, `servicios` |
| Agenda (regla) | `app/Agenda/ReservaCitas.php`, `app/Agenda/CitaNoAgendable.php` | Regla de agenda única (horario del día, encaje en la franja, solape estricto) y guardado de la cita en una transacción que bloquea primero la fila del dentista (spec 025) | `personal` (bloqueo), `horarios_dentista`, `citas` |
| Agenda (estados) | `app/Agenda/EstadosCita.php`, `app/Agenda/CambioDeEstadoNoPermitido.php` | Transiciones de estado, estados cerrados, regla "ya empezó" en la zona de la clínica (`config/clinica.php`) y guardado del cambio con una sola entrada de historial con autor, en una transacción que bloquea primero la fila de la cita (spec 026) | `citas` (bloqueo), `historial_citas` |
| Agenda (horario) | `app/Agenda/HorarioDentista.php`, `app/Agenda/HorarioNoGuardado.php`, `app/Rules/HoraFinPosterior.php` | Guardado del horario semanal del dentista en una transacción que bloquea primero su fila de `personal` (el mismo bloqueo que la reserva), cálculo de las citas futuras agendadas o confirmadas que quedan fuera (misma regla de franja que `ReservaCitas`) y validación de que la hora de fin sea posterior a la de inicio sin error 500 con entradas manipuladas (spec 027) | `personal` (bloqueo), `horarios_dentista`, `citas`, `pacientes`, `usuarios` |
| Autorización de citas | `app/Autorizacion/AlcanceClinico.php`, `app/Autorizacion/CambiarEstadoCita.php` | Alcance del personal sobre las citas (ADR 0003) y Gates `gestionar-cita` y `cambiar-estado-cita`, registrados en `AppServiceProvider` (spec 026) | `personal`, `asistente_dentista`, `pacientes` |
| Autorización de personal | `app/Autorizacion/GestionPersonal.php` | Gate `definir-horario`, registrado en `AppServiceProvider`: solo la administración, y solo sobre un dentista (otro tipo de personal → 404) (spec 027) | `personal` |
| Pacientes | `app/Http/Controllers/PacientesController.php` | CRUD de pacientes y alta de su usuario | `usuarios`, `pacientes`, `expedientes`, `citas` |
| Expedientes | `app/Http/Controllers/ExpedientesController.php` | Expediente clínico ligado a una cita | `expedientes`, `citas` |
| Personal | `app/Http/Controllers/PersonalController.php` | CRUD de personal, asignación asistente↔dentista y horarios: valida el horario y lo guarda con `app/Agenda/HorarioDentista.php`, que también da la lista de citas fuera del horario de la ficha (spec 027) | `personal`, `usuarios`, `horarios_dentista`, `asistente_dentista` |
| Servicios | `app/Http/Controllers/ServiciosController.php` | Catálogo (Eloquent + `Route::resource`) | `servicios` |
| Usuarios | `app/Http/Controllers/UsuarioController.php` | Administración de cuentas y roles | `usuarios` |
| Perfil | `app/Http/Controllers/PerfilController.php` | Perfil y contraseña del cliente | `usuarios`, `pacientes` |
| Middleware | `app/Http/Middleware/RoleMiddleware.php`, `PreventBackHistory.php` | Rol por grupo de rutas; cabeceras no-cache | `usuarios.rol` |

Los controladores no se llaman entre sí; se acoplan a través del esquema compartido. Los mismos
controladores sirven a varios roles bajo prefijos `/admin`, `/dentista`, `/asistente`; `/cliente`
tiene su propio grupo.

## Flujo principal
Ejemplo: el personal agenda una cita.
1. El navegador envía `POST /admin/citas` con `@csrf` a nginx (puerto 8000).
2. nginx lo pasa por FastCGI a php-fpm (`app:9000`).
3. Laravel aplica el grupo `web` (incluye `PreventBackHistory`), luego `auth` y `role:admin`.
4. `CitasController@store` valida la entrada (fecha, hora y dentista activo, con las reglas
   comunes de `ReservaCitas`), comprueba el alcance por rol y llama a `ReservaCitas::reservar`. En
   una transacción, el servicio bloquea la fila del dentista, revisa `horarios_dentista` y los
   solapes, e inserta la cita. Un rechazo vuelve al formulario como error del campo hora.
5. Responde `redirect()` con flash `success`, que muestra `layouts/app.blade.php`.
Al cambiar el estado de una cita, `EstadosCita` escribe una sola fila en `historial_citas`, con
autor, estado anterior y nuevo, y motivo, en la misma transacción que el cambio (spec 026).

## Datos
- Motor: MariaDB 10.11 en Docker (el volcado se generó con 10.4.32). PKs UUID `char(36)`.
  Columnas `creado_en`/`actualizado_en` escritas a mano (sin timestamps de Laravel).
- Acceso: PDO con Query Builder (≈157 usos de `DB::` en controladores) y Eloquent solo para
  `Usuario` (modelo de autenticación, tabla `usuarios`) y `Servicio`.
- Entidades: `usuarios` (rol enum cliente/asistente/dentista/admin), `pacientes`, `personal`
  (tipo dentista/asistente/administrador), `asistente_dentista`, `horarios_dentista`, `servicios`,
  `citas` (estado enum de 6 valores), `historial_citas`, `expedientes`. Vistas SQL `v_citas_hoy` y
  `v_historial_paciente` (no las usa el código PHP).
- Esquema: las tablas de dominio, las vistas y el trigger `trg_historial_cita` solo existen en
  `database/dentissa.sql`. Las migraciones crean tablas de infraestructura (`users`, `sessions`,
  `cache`, `jobs`…) y `asistente_dentista`, alteran `usuarios` y `pacientes`, que ninguna migración
  crea, y retiran el trigger (spec 026). El
  esquema no se puede recrear desde migraciones sobre una base vacía.

## Integraciones externas
- Google OAuth mediante `laravel/socialite` (`/auth/google`, `/auth/google/callback`); variables
  `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.
- Google Fonts (Roboto) desde el navegador.
- No hay correo, colas, almacenamiento de archivos ni APIs HTTP salientes en uso.

## Frontend
- Componentes y organización: vistas Blade por módulo en `resources/views/<modulo>/`, archivos en
  kebab-case, un único layout `layouts/app.blade.php`, parciales en `partials/`. Sin componentes
  `<x-…>`. `edit` reutiliza `create` con `@include`.
- Estado: sin estado de cliente; JS inline mínimo (`@push('scripts')`) para modales y rellenar
  campos.
- Routing: rutas con nombre en `routes/web.php`; hay 50 `href="/..."` escritos a mano en vistas.
- Design tokens / sistema de diseño: CSS propio en `public/css/app.css`, `auth.css`, `citas.css`
  con variables (`--p50…--p800`, `--text`, `--border`), mucho `style=""` inline. Vite + Tailwind 4
  están declarados pero solo los usa `welcome.blade.php`, que ninguna ruta sirve.
- Accesibilidad: WCAG 2.1 AA (decisión del usuario, 2026-10-02); principio P9 y deuda DS1–DS11 en [design/system.md](design/system.md).

## Backend
- Contratos de API: no hay API; el contrato es formulario HTML ↔ `$request->validate()`.
- Autenticación y autorización: sesión de Laravel (guard `web`) sobre `App\Models\Usuario`;
  middleware `role:` por grupo de rutas; comprobaciones de fila en controladores
  (`aplicarAlcanceRol`, `autorizadoParaCita`, que delegan en `AlcanceClinico`), y Gates para los
  cambios de estado de las citas (spec 026). Detalle en [security.md](security.md).
- Observabilidad: sin logs propios, sin correlación, sin auditoría; ver [observability.md](observability.md).
- Seguridad: CSRF por defecto, consultas con bindings, sin `{!! !!}`; riesgos en [security.md](security.md).

## Contrato entre capas
- Fuente de verdad: nombres de campo de los formularios Blade frente a las reglas de validación de
  cada controlador. No hay tipos ni esquemas compartidos.
- Versionado: no aplica (sin API pública).
- Cómo se regenera: no aplica.

## Despliegue
Solo existe un entorno local con Docker Compose (`app` php-fpm, `nginx`, `db` MariaDB,
`phpmyadmin`). Se planean staging y producción con despliegue manual, aún sin plataforma elegida.
Detalle en [deployment.md](deployment.md).

## Evidencia (solo si status=inferred)
| Afirmación | Archivos | Confianza |
|---|---|---|
| MVC sin capa de dominio | `app/Http/Controllers/*`, `app/Providers/AppServiceProvider.php` | alta |
| Solo 2 modelos de dominio | `app/Models/Usuario.php`, `app/Models/Servicio.php` | alta |
| Autorización por rol + checks inline | `bootstrap/app.php`, `app/Http/Middleware/RoleMiddleware.php`, `CitasController.php` | alta |
| Esquema solo en volcado | `database/dentissa.sql`, `database/migrations/*`, `database/testing/bootstrap-test-db.sh` | alta |
| Vite/Tailwind sin uso real | `vite.config.js`, `resources/views/welcome.blade.php`, `layouts/app.blade.php` | alta |
| Integración Google OAuth | `GoogleAuthController.php`, `config/services.php` | alta |
| Entorno solo local | `docker-compose.yml`, `Dockerfile`, `docker/nginx/dentissa.conf` | alta |

## Deuda técnica y riesgos observados
- Esquema del dominio no reproducible desde migraciones; el volcado está desfasado respecto a las
  3 migraciones de julio (→ roadmap objetivo 1, spec 013).
- Ayudante de dentistas disponibles copiado en 2 sitios y consulta base de citas en 4.
- Restos del esqueleto: `App\Models\User`, tabla `users`, `DatabaseSeeder`, `welcome.blade.php`,
  nombre `laravel/laravel` en `composer.json`, README genérico.
- `Route::resource('servicios')` registra `show`, que el controlador no implementa.
- Convenciones inconsistentes de rutas (`/crear` vs `/create`, `/edit` vs `/editar`, nombres con y
  sin `.index`).
- `docker-compose.yml` monta `./docker/php/local.ini`, pero el archivo está en `docker/nginx/php/local.ini`.
