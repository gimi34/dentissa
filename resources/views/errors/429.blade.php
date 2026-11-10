{{-- Spec 044 (CA10, /analyze A2): demasiados intentos. Preventiva: hoy los límites de la aplicación responden con su
     propio aviso; esta página sale con cualquier límite que use la respuesta de Laravel. No revela cuántos
     intentos quedan ni nada de la cuenta. --}}
@extends('errors.plantilla')

@section('titulo', 'Demasiados intentos')
@section('etiqueta', 'Error 429 · demasiados intentos')
@section('h1', 'Demasiados intentos')
@section('sub', 'Espera unos minutos y vuelve a probar.')

@section('acciones')
    <a class="btn btn-primario" href="{{ route('inicio') }}">Ir a la página de inicio</a>
@endsection
