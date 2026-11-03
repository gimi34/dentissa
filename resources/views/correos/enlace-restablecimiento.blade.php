@extends('correos.marco-acceso')

@section('asunto', 'Elige tu contraseña nueva de Dentissa')

@section('cuerpo')
    <h1>Elige tu contraseña nueva</h1>
    <p>Pediste entrar de nuevo a tu cuenta de Dentissa. Pulsa el botón y escribe tu contraseña nueva.</p>
    <p class="correo-boton"><a class="btn-primario" href="{{ $enlace }}">Elegir contraseña nueva</a></p>
    <p class="url">Si el botón no funciona, copia esta dirección: {{ $enlace }}</p>
    <p>Si no lo pediste, ignora este correo: tu contraseña no cambia.</p>
@endsection

@section('talon')
    <dt>Vale hasta</dt><dd>Las {{ $hora }} (60 minutos), una sola vez.</dd>
    <dt>Consulta</dt><dd>{{ $contacto['direccion'] }} · <span class="tel">{{ $contacto['telefono'] }}</span></dd>
@endsection
