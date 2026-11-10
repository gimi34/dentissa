{{-- Spec 044 (CA8; D5; TM2, TM7): acceso denegado por rol o por permiso. Muestra la explicación de
     `Response::deny()` cuando la hay (solo de una `AuthorizationException`, /review R5) (p. ej. «Como dentista no puedes reprogramar citas…»), nunca el genérico de
     Laravel. El registro del acceso denegado no depende de esta vista (spec 020). --}}
@php
    // /review R5: solo la explicación de un permiso, es decir, el mensaje de una AuthorizationException
    // (Response::deny) que Laravel convierte en 403; nunca el texto de otra excepción 403 (p. ej. abort(403, …)).
    $permiso = $exception?->getPrevious();
    $explicacion = $permiso instanceof \Illuminate\Auth\Access\AuthorizationException ? trim((string) $permiso->getMessage()) : '';
    if ($explicacion === 'This action is unauthorized.') {
        $explicacion = '';
    }
@endphp
@extends('errors.plantilla')

@section('titulo', 'No tienes acceso a esta página')
@section('etiqueta', 'Error 403 · acceso denegado')
@section('h1', 'No tienes acceso a esta página')
@section('sub', $explicacion !== '' ? $explicacion : 'Tu cuenta no tiene permiso para ver esta página. Vuelve a tu panel para seguir.')

@section('acciones')
    @if (auth()->hasUser())
        <a class="btn btn-primario" href="{{ \App\Http\Controllers\PaginasPublicasController::panelDelRol(auth()->user()) }}">Ir a mi panel</a>
        <p class="enlaces-secundarios"><a class="enlace-secundario" href="{{ route('inicio') }}">Ir a la página de inicio</a></p>
    @else
        <a class="btn btn-primario" href="{{ route('inicio') }}">Ir a la página de inicio</a>
    @endif
@endsection
