<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitasController;
use App\Http\Controllers\ClienteCitasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisenioAccesoController;
use App\Http\Controllers\DisenioAuditoriaController;
use App\Http\Controllers\ExpedientesController;
use App\Http\Controllers\FotosGaleriaController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\PaginasPublicasController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\RecuperarContrasenaController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\PreventBackHistory;

// ── Auth ────────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class,  'loginForm'])->name('login');
Route::post('/login', [AuthController::class,  'login']);
Route::post('/logout', [AuthController::class,  'logout'])->name('logout');
Route::get('/registro', [RegisterController::class, 'showForm'])->name('register');
Route::post('/registro', [RegisterController::class, 'register'])->middleware('throttle:registro');

Route::get('/registro/contacto', [RegisterController::class, 'contactoForm'])->name('register.contacto');
Route::post('/registro/contacto', [RegisterController::class, 'contactoStore'])->middleware('throttle:registro');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:google')->name('auth.google.callback');

// ── Recuperar la contraseña (spec 041) ──────────────────────────────────
// Solo con el interruptor encendido (D8). Grupo web: CSRF y no-store (TM10, TM11). Los limitadores
// cuentan los envíos; abrir el enlace no gasta intentos (D15).
if (config('acceso.recuperar_contrasena')) {
    Route::prefix('contrasena')->name('contrasena.')->controller(RecuperarContrasenaController::class)->group(function () {
        Route::get('/olvidada', 'solicitudForm')->name('solicitud');
        Route::post('/olvidada', 'solicitar')->middleware('throttle:solicitar-enlace')->name('solicitar');
        Route::get('/enviada', 'enviada')->name('enviada');
        Route::get('/nueva/{token}', 'nuevaForm')->middleware('throttle:abrir-enlace')->name('nueva');
        Route::post('/nueva', 'restablecer')->middleware('throttle:fijar-contrasena')->name('restablecer');
    });
}

// ── Páginas públicas (spec 038) ─────────────────────────────────────────
Route::get('/', [PaginasPublicasController::class, 'inicio'])->name('inicio');
Route::get('/servicios', [PaginasPublicasController::class, 'servicios'])->name('servicios.publicos');
// Spec 039: la galería y cada foto. La foto va sin PreventBackHistory, que pisaría su
// Cache-Control (plan → «Entrega de las fotos», /analyze A1).
Route::get('/galeria', [PaginasPublicasController::class, 'galeria'])->name('galeria.publica');
Route::get('/galeria/fotos/{foto}/{ancho}', [PaginasPublicasController::class, 'foto'])->name('galeria.foto')
    ->whereUuid('foto')->whereIn('ancho', ['800', '1600'])->withoutMiddleware(PreventBackHistory::class);

// ── Vista previa del diseño (spec 020, D19) ─────────────────────────────
// Solo en local (TM15): datos de muestra, sin sesión ni base, para medir con design/medir.mjs.
if (app()->environment('local')) {
    Route::get('/_disenio/020/{pantalla}', [DisenioAuditoriaController::class, 'show'])
        ->where('pantalla', implode('|', DisenioAuditoriaController::PANTALLAS))
        ->withoutMiddleware('web')
        ->name('disenio.020');

    // Spec 041 (D11): cada pantalla y cada correo con los datos de muestra de c7. Acepta el «.html» de medir.mjs.
    Route::get('/_disenio/041/{pantalla}', [DisenioAccesoController::class, 'show'])
        ->where('pantalla', '('.DisenioAccesoController::PANTALLAS.')(\.html)?')
        ->name('disenio.041');
}

// ── Admin ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');

    // Servicios
    Route::resource('servicios', ServiciosController::class);

    // Citas
    Route::get('/citas', [CitasController::class, 'index'])->name('citas');
    Route::get('/citas/crear', [CitasController::class, 'create'])->name('citas.create');
    Route::post('/citas', [CitasController::class, 'store'])->name('citas.store');
    Route::get('/citas/{id}', [CitasController::class, 'show'])->name('citas.show');
    Route::patch('/citas/{id}/estado', [CitasController::class, 'updateEstado'])->name('citas.estado');
    // Reprogramar la cita (spec 028); el dentista no tiene estas rutas.
    Route::get('/citas/{id}/reprogramar', [CitasController::class, 'reprogramarForm'])->name('citas.reprogramar');
    Route::patch('/citas/{id}/reprogramar', [CitasController::class, 'reprogramar'])->name('citas.reprogramar.update');
    Route::patch('/citas/{id}/notas', [CitasController::class, 'updateNotas'])->name('citas.notas');
    Route::delete('/citas/{id}', [CitasController::class, 'destroy'])->name('citas.destroy');

    // Pacientes
    Route::get('/pacientes', [PacientesController::class, 'index'])->name('pacientes');
    Route::get('/pacientes/crear', [PacientesController::class, 'create'])->name('pacientes.create');
    Route::post('/pacientes', [PacientesController::class, 'store'])->name('pacientes.store');
    Route::get('/pacientes/{id}', [PacientesController::class, 'show'])->name('pacientes.show');
    Route::get('/pacientes/{id}/edit', [PacientesController::class, 'edit'])->name('pacientes.edit');
    Route::put('/pacientes/{id}', [PacientesController::class, 'update'])->name('pacientes.update');
    Route::patch('/pacientes/{id}/toggle', [PacientesController::class, 'toggleActivo'])->name('pacientes.toggle');

    // Expedientes
    Route::get('/expedientes/crear', [ExpedientesController::class, 'create'])->name('expedientes.create');
    Route::post('/expedientes', [ExpedientesController::class, 'store'])->name('expedientes.store');
    Route::get('/expedientes/{id}', [ExpedientesController::class, 'show'])->name('expedientes.show');
    Route::put('/expedientes/{id}', [ExpedientesController::class, 'update'])->name('expedientes.update');

    // Personal
    Route::get('/personal', [PersonalController::class, 'index'])->name('personal');
    Route::get('/personal/crear', [PersonalController::class, 'create'])->name('personal.create');
    Route::post('/personal', [PersonalController::class, 'store'])->name('personal.store');
    Route::get('/personal/{id}', [PersonalController::class, 'show'])->name('personal.show');
    Route::get('/personal/{id}/edit', [PersonalController::class, 'edit'])->name('personal.edit');
    Route::put('/personal/{id}', [PersonalController::class, 'update'])->name('personal.update');
    Route::patch('/personal/{id}/toggle', [PersonalController::class, 'toggleActivo'])->name('personal.toggle');
    Route::post('/personal/{id}/horarios', [PersonalController::class, 'saveHorarios'])->name('personal.horarios');

    // Placeholders
    Route::get('/reportes', fn () => view('placeholder', ['titulo' => 'Reportes']))->name('reportes');

    // Registro de auditoría (spec 020): solo consulta, sin rutas de modificación (CA2).
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

    // Galería de trabajos (spec 039; CA8–CA13): además de role:admin, cada acción comprueba el
    // Gate gestionar-galeria. Quitar pide confirmación en una página (D7).
    Route::controller(FotosGaleriaController::class)->prefix('galeria')->name('galeria.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::patch('/{foto}/subir', 'subir')->name('subir')->whereUuid('foto');
        Route::patch('/{foto}/bajar', 'bajar')->name('bajar')->whereUuid('foto');
        Route::get('/{foto}/quitar', 'confirmarQuitar')->name('quitar')->whereUuid('foto');
        Route::delete('/{foto}', 'destroy')->name('destroy')->whereUuid('foto');
    });

    // Usuarios
    // El orden importa: Laravel resuelve por orden de registro, así que
    // /usuarios/crear se declara antes que /usuarios/{id} y {id} queda
    // restringido al formato UUID de la columna usuarios.id (FR1.1, FR1.2).
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->name('usuarios.show')->where('id', '[0-9a-fA-F-]{36}');
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit')->where('id', '[0-9a-fA-F-]{36}');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update')->where('id', '[0-9a-fA-F-]{36}');
    Route::patch('/usuarios/{id}/toggle', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggle');
});

// ── Dentista ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:dentista'])->prefix('dentista')->name('dentista.')->group(function () {

    Route::get('/', [DashboardController::class, 'dentista'])->name('dashboard');

    Route::get('/citas', [CitasController::class, 'index'])->name('citas');
    Route::get('/citas/crear', [CitasController::class, 'create'])->name('citas.create');
    Route::post('/citas', [CitasController::class, 'store'])->name('citas.store');
    Route::get('/citas/{id}', [CitasController::class, 'show'])->name('citas.show');
    Route::patch('/citas/{id}/estado', [CitasController::class, 'updateEstado'])->name('citas.estado');

    Route::get('/pacientes', [PacientesController::class, 'index'])->name('pacientes');
    Route::get('/pacientes/{id}', [PacientesController::class, 'show'])->name('pacientes.show');
    Route::get('/pacientes/{id}/edit', [PacientesController::class, 'edit'])->name('pacientes.edit');
    Route::put('/pacientes/{id}', [PacientesController::class, 'update'])->name('pacientes.update');

    Route::get('/expedientes', [ExpedientesController::class, 'index'])->name('expedientes');
    Route::get('/expedientes/crear', [ExpedientesController::class, 'create'])->name('expedientes.create');
    Route::post('/expedientes', [ExpedientesController::class, 'store'])->name('expedientes.store');
    Route::get('/expedientes/{id}', [ExpedientesController::class, 'show'])->name('expedientes.show');
    Route::put('/expedientes/{id}', [ExpedientesController::class, 'update'])->name('expedientes.update');
});

// ── Asistente ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:asistente'])->prefix('asistente')->name('asistente.')->group(function () {

    Route::get('/', [DashboardController::class, 'asistente'])->name('dashboard');

    Route::get('/doctores', [PersonalController::class, 'misDoctores'])->name('doctores');

    Route::get('/citas', [CitasController::class, 'index'])->name('citas');
    Route::get('/citas/crear', [CitasController::class, 'create'])->name('citas.create');
    Route::post('/citas', [CitasController::class, 'store'])->name('citas.store');
    Route::get('/citas/{id}', [CitasController::class, 'show'])->name('citas.show');
    Route::patch('/citas/{id}/estado', [CitasController::class, 'updateEstado'])->name('citas.estado');
    // Reprogramar la cita (spec 028); el dentista no tiene estas rutas.
    Route::get('/citas/{id}/reprogramar', [CitasController::class, 'reprogramarForm'])->name('citas.reprogramar');
    Route::patch('/citas/{id}/reprogramar', [CitasController::class, 'reprogramar'])->name('citas.reprogramar.update');

    Route::get('/pacientes', [PacientesController::class, 'index'])->name('pacientes');
    Route::get('/pacientes/crear', [PacientesController::class, 'create'])->name('pacientes.create');
    Route::post('/pacientes', [PacientesController::class, 'store'])->name('pacientes.store');
    Route::get('/pacientes/{id}', [PacientesController::class, 'show'])->name('pacientes.show');

    Route::get('/expedientes/{id}', [ExpedientesController::class, 'show'])->name('expedientes.show');
});

// ── Cliente ─────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/', [DashboardController::class, 'cliente'])->name('dashboard');

    Route::get('/citas', [ClienteCitasController::class, 'index'])->name('citas');
    Route::get('/citas/{id}', [ClienteCitasController::class, 'show'])->name('citas.show');
    Route::patch('/citas/{id}/cancelar', [ClienteCitasController::class, 'cancelar'])->middleware('role:cliente')->name('citas.cancelar');
    Route::get('/citas/{id}/reprogramar', [ClienteCitasController::class, 'reprogramarForm'])->middleware('role:cliente')->name('citas.reprogramar');
    Route::patch('/citas/{id}/reprogramar', [ClienteCitasController::class, 'reprogramar'])->middleware('role:cliente')->name('citas.reprogramar.update');

    Route::get('/agendar', [ClienteCitasController::class, 'agendarForm'])->name('agendar');
    Route::post('/agendar', [ClienteCitasController::class, 'agendarStore'])->name('agendar.store');

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    // Spec 043 (D8, CA8): solo el paciente cambia su contraseña aquí; el resto del grupo → spec 015 (RS2.a).
    Route::put('/perfil/password', [PerfilController::class, 'updatePassword'])->middleware('role:cliente')->name('perfil.password');
});

// ── Rutas de reserva (spec 044, D3; /analyze A1, A16, A17) ─────────────────
// Se resuelven siempre después de todas las demás. GET y HEAD van por el grupo `web` para que la 404
// propia sepa si hay sesión; el resto de verbos, sin él (sin sesión ni comprobación CSRF, que daría 419).
// Así ningún verbo cambia de 404 a 405, y un verbo equivocado sobre una ruta que existe también es 404.
Route::fallback(fn () => abort(404));
Route::match(['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '{reserva}', fn () => abort(404))
    ->where('reserva', '.*')
    ->fallback()
    ->withoutMiddleware('web');
