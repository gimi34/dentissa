@extends('correos.marco-acceso')

@section('asunto', 'Tu contraseña de Dentissa cambió')

@section('cuerpo')
    <h1>Tu contraseña cambió</h1>
    <p>El {{ $cuando }} se eligió una contraseña nueva para tu cuenta y cerramos todas tus sesiones abiertas.</p>
    <p>Si fuiste tú, no tienes que hacer nada.</p>
@endsection

@section('talon')
    <dt>¿No fuiste tú?</dt><dd>Llama a la consulta: <span class="tel">{{ $contacto['telefono'] }}</span></dd>
    <dt>Horario</dt><dd>@foreach($contacto['horario'] as $dias => $horas)@if(! $loop->first) · @endif<span class="rango">{{ $dias }} {{ $horas }}</span>@endforeach</dd>
@endsection
