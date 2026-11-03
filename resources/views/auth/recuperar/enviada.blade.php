{{-- Spec 041 (CA2, CA8): el mismo mensaje haya cuenta o no. --}}
@extends('layouts.acceso')

@section('titulo', 'Revisa tu correo')

@section('carnet')
    <div class="carnet-cuerpo">
        <span class="etiqueta">Enlace pedido</span>
        <h1>Revisa tu correo</h1>
        <p class="sub" role="status">Si <b>{{ $correo }}</b> es de una cuenta activa, te llegará un enlace en unos minutos.</p>
        <div class="vale-bloque">
            <span class="etiqueta">Vale hasta las</span>
            <span class="hora">{{ $hora }}</span>
            <p>Abre el correo de Dentissa y pulsa «Elegir contraseña nueva».</p>
        </div>
        <a class="enlace" href="{{ route('login') }}">Volver a iniciar sesión</a>
    </div>
    <div class="troquel" aria-hidden="true"></div>
    <dl class="talon">
        <dt>¿No llegó?</dt><dd>Revisa la carpeta de spam o <a class="en-texto" href="{{ route('contrasena.solicitud') }}">pide otro enlace</a>.</dd>
        <dt>¿Sin correo?</dt><dd>Llama a la consulta: <span class="tel">{{ config('clinica.contacto.telefono') }}</span></dd>
    </dl>
@endsection
