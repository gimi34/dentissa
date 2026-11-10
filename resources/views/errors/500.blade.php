{{-- Spec 044 (CA10; TM2, TM6): fallo del servidor. Laravel solo la usa con APP_DEBUG=false, como en producción.
     No muestra nada de la excepción; sí el identificador de la petición (P8), como el error genérico. --}}
@extends('errors.plantilla')

@section('titulo', 'Algo salió mal')
@section('etiqueta', 'Error 500 · fallo del servidor')
@section('h1', 'Algo salió mal')
@section('sub', 'Tuvimos un problema de nuestro lado. Vuelve a intentarlo más tarde.')
@section('codigo', 'sí')

@section('acciones')
    <a class="btn btn-primario" href="{{ route('inicio') }}">Ir a la página de inicio</a>
@endsection
