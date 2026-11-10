{{-- Spec 044 (CA7, CA11; SE4.a, SE2.b; TM3): dirección inexistente. Nunca muestra el texto de la excepción, así
     que una cita de otro y una inexistente responden la misma página. La sesión se mira con `hasUser()` (D4): la
     ruta de reserva GET pasa por el grupo `web`, que ya resolvió el usuario (D3). --}}
@extends('errors.plantilla')

@section('titulo', 'No encontramos esta página')
@section('etiqueta', 'Error 404 · página no encontrada')
@section('h1', 'No encontramos esta página')
@section('sub', 'La dirección no existe o cambió. Puedes volver a la página de inicio.')

@section('acciones')
    <a class="btn btn-primario" href="{{ route('inicio') }}">Ir a la página de inicio</a>
    <p class="enlaces-secundarios">
        @if (auth()->hasUser())
            <a class="enlace-secundario" href="{{ \App\Http\Controllers\PaginasPublicasController::panelDelRol(auth()->user()) }}">Ir a mi panel</a>
        @else
            <a class="enlace-secundario" href="{{ route('login') }}">Iniciar sesión</a>
            <a class="enlace-secundario" href="{{ route('register') }}">Crear cuenta</a>
        @endif
    </p>
@endsection
