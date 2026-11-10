{{-- Spec 044 (CA9): formulario caducado (la sesión venció o el formulario estuvo abierto demasiado tiempo).
     Destinos fijos (TM1); no depende de la sesión, que aquí aún no está resuelta. --}}
@extends('errors.plantilla')

@section('titulo', 'La página caducó')
@section('etiqueta', 'Error 419 · página caducada')
@section('h1', 'La página caducó')
@section('sub', 'Pasó demasiado tiempo desde que abriste el formulario. Vuelve a iniciar sesión e inténtalo de nuevo.')

@section('acciones')
    <a class="btn btn-primario" href="{{ route('login') }}">Volver a iniciar sesión</a>
    <p class="enlaces-secundarios"><a class="enlace-secundario" href="{{ route('inicio') }}">Ir a la página de inicio</a></p>
@endsection
