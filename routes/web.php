<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CitasController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\ExpedientesController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ServiciosController;


// ── Auth ────────────────────────────────────────────────────────────────
Route::get ('/login',   [AuthController::class,  'loginForm'])->name('login');
Route::post('/login',   [AuthController::class,  'login']);
Route::post('/logout',  [AuthController::class,  'logout'])->name('logout');
Route::get ('/registro',[App\Http\Controllers\RegisterController::class, 'showForm'])->name('register');
Route::post('/registro',[App\Http\Controllers\RegisterController::class, 'register']);


// ── Raíz ────────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->rol) {
            'admin'     => redirect()->route('admin.dashboard'),
            'dentista'  => redirect()->route('dentista.dashboard'),
            'asistente' => redirect()->route('asistente.dashboard'),
            default     => redirect()->route('cliente.dashboard'),
        };
    }
    return redirect('/login');
});

// ── Admin ───────────────────────────────────────────────────────────────
Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');

    // Servicios 
    Route::resource('servicios', ServiciosController::class);
    

    // Citas
    Route::get   ('/citas',             [CitasController::class, 'index']       )->name('citas');
    Route::get   ('/citas/crear',       [CitasController::class, 'create']      )->name('citas.create');
    Route::post  ('/citas',             [CitasController::class, 'store']       )->name('citas.store');
    Route::get   ('/citas/{id}',        [CitasController::class, 'show']        )->name('citas.show');
    Route::patch ('/citas/{id}/estado', [CitasController::class, 'updateEstado'])->name('citas.estado');
    Route::patch ('/citas/{id}/notas',  [CitasController::class, 'updateNotas'] )->name('citas.notas');
    Route::delete('/citas/{id}',        [CitasController::class, 'destroy']     )->name('citas.destroy');

    // Pacientes
    Route::get   ('/pacientes',             [PacientesController::class, 'index']      )->name('pacientes');
    Route::get   ('/pacientes/crear',       [PacientesController::class, 'create']     )->name('pacientes.create');
    Route::post  ('/pacientes',             [PacientesController::class, 'store']      )->name('pacientes.store');
    Route::get   ('/pacientes/{id}',        [PacientesController::class, 'show']       )->name('pacientes.show');
    Route::get   ('/pacientes/{id}/edit',   [PacientesController::class, 'edit']       )->name('pacientes.edit');
    Route::put   ('/pacientes/{id}',        [PacientesController::class, 'update']     )->name('pacientes.update');
    Route::patch ('/pacientes/{id}/toggle', [PacientesController::class,'toggleActivo'])->name('pacientes.toggle');

    // Expedientes
    Route::get ('/expedientes/crear', [ExpedientesController::class, 'create'])->name('expedientes.create');
    Route::post('/expedientes',       [ExpedientesController::class, 'store'] )->name('expedientes.store');
    Route::get ('/expedientes/{id}',  [ExpedientesController::class, 'show']  )->name('expedientes.show');
    Route::put ('/expedientes/{id}',  [ExpedientesController::class, 'update'])->name('expedientes.update');

    // Personal
    Route::get   ('/personal',                  [PersonalController::class, 'index']      )->name('personal');
    Route::get   ('/personal/crear',            [PersonalController::class, 'create']     )->name('personal.create');
    Route::post  ('/personal',                  [PersonalController::class, 'store']      )->name('personal.store');
    Route::get   ('/personal/{id}',             [PersonalController::class, 'show']       )->name('personal.show');
    Route::get   ('/personal/{id}/edit',        [PersonalController::class, 'edit']       )->name('personal.edit');
    Route::put   ('/personal/{id}',             [PersonalController::class, 'update']     )->name('personal.update');
    Route::patch ('/personal/{id}/toggle',      [PersonalController::class, 'toggleActivo'])->name('personal.toggle');
    Route::post  ('/personal/{id}/horarios',    [PersonalController::class, 'saveHorarios'])->name('personal.horarios');

    // Placeholders
    Route::get('/reportes',   fn() => view('placeholder', ['titulo' => 'Reportes'])              )->name('reportes');
    Route::get('/usuarios',   fn() => view('placeholder', ['titulo' => 'Usuarios'])              )->name('usuarios');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');

    Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');

    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
});

// ── Dentista ────────────────────────────────────────────────────────────
Route::middleware(['auth','role:dentista'])->prefix('dentista')->name('dentista.')->group(function () {

    Route::get('/', [DashboardController::class, 'dentista'])->name('dashboard');

    Route::get  ('/citas',             [CitasController::class, 'index']       )->name('citas');
    Route::get  ('/citas/{id}',        [CitasController::class, 'show']        )->name('citas.show');
    Route::patch('/citas/{id}/estado', [CitasController::class, 'updateEstado'])->name('citas.estado');

    Route::get('/pacientes',      [PacientesController::class, 'index'])->name('pacientes');
    Route::get('/pacientes/{id}', [PacientesController::class, 'show'] )->name('pacientes.show');

    Route::get ('/expedientes/crear', [ExpedientesController::class, 'create'])->name('expedientes.create');
    Route::post('/expedientes',       [ExpedientesController::class, 'store'] )->name('expedientes.store');
    Route::get ('/expedientes/{id}',  [ExpedientesController::class, 'show']  )->name('expedientes.show');
    Route::put ('/expedientes/{id}',  [ExpedientesController::class, 'update'])->name('expedientes.update');
});

// ── Asistente ───────────────────────────────────────────────────────────
Route::middleware(['auth','role:asistente'])->prefix('asistente')->name('asistente.')->group(function () {

    Route::get('/', fn() => view('placeholder', ['titulo' => 'Panel Asistente']))->name('dashboard');

    Route::get ('/pacientes',       [PacientesController::class, 'index'] )->name('pacientes');
    Route::get ('/pacientes/crear', [PacientesController::class, 'create'])->name('pacientes.create');
    Route::post('/pacientes',       [PacientesController::class, 'store'] )->name('pacientes.store');
    Route::get ('/pacientes/{id}',  [PacientesController::class, 'show']  )->name('pacientes.show');

    Route::get ('/expedientes/crear', [ExpedientesController::class, 'create'])->name('expedientes.create');
    Route::post('/expedientes',       [ExpedientesController::class, 'store'] )->name('expedientes.store');
    Route::get ('/expedientes/{id}',  [ExpedientesController::class, 'show']  )->name('expedientes.show');
});

// ── Cliente ─────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('cliente')->name('cliente.')->group(function () {
    Route::get  ('/',                    [DashboardController::class, 'cliente'])->name('dashboard');
    Route::get  ('/citas',               fn() => view('placeholder', ['titulo' => 'Mis Citas'])      )->name('citas');
    Route::get  ('/citas/{id}',          fn() => view('placeholder', ['titulo' => 'Detalle de Cita']))->name('citas.show');
    Route::patch('/citas/{id}/cancelar', fn($id) => redirect()->back()                               )->name('citas.cancelar');
    Route::get  ('/agendar',             fn() => view('placeholder', ['titulo' => 'Agendar Cita'])   )->name('agendar');
    Route::get  ('/perfil',              fn() => view('placeholder', ['titulo' => 'Mi Perfil'])       )->name('perfil');
});