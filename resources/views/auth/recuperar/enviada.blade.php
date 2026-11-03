{{-- Spec 041 (CA2, CA8): el mismo mensaje haya cuenta o no. --}}
@extends('layouts.acceso')

@section('titulo', 'Revisa tu correo')

@section('contenido')
    <div class="form-header">
        <h1 class="form-title">Revisa tu correo</h1>
        <p class="form-subtitle" role="status">Si <b>{{ $correo }}</b> es de una cuenta activa, te llegará un enlace en unos minutos.</p>
    </div>
    <div class="vale-bloque">
        <span class="vale-etiqueta">Vale hasta las</span>
        <span class="vale-hora">{{ $hora }}</span>
        <p>Abre el correo de Dentissa y pulsa «Elegir contraseña nueva».</p>
    </div>
    <p class="enlace-volver"><a class="forgot-link" href="{{ route('login') }}">Volver a iniciar sesión</a></p>
    <dl class="talon">
        <dt>¿No llegó?</dt><dd><p>Revisa la carpeta de spam o <a class="en-texto" href="{{ route('contrasena.solicitud') }}">pide otro enlace</a>.</p></dd>
        <dt>¿Sin correo?</dt><dd>Llama a la consulta: <span class="tel">{{ config('clinica.contacto.telefono') }}</span></dd>
    </dl>
@endsection
