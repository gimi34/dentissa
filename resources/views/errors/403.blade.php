{{-- Spec 044 (CA8; D5; TM2, TM7): acceso denegado por rol o por permiso. Muestra la explicación de
     `Response::deny()` cuando la hay (p. ej. «Como dentista no puedes reprogramar citas…»), nunca el genérico de
     Laravel. El registro del acceso denegado no depende de esta vista (spec 020). --}}
@php
    $explicacion = trim((string) ($exception?->getMessage() ?? ''));
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
