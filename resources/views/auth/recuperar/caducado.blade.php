{{-- Spec 041 (CA5, CA10, CA11): enlace caducado, usado, sustituido, alterado o de una cuenta desactivada.
     Mismo mensaje en todos los casos. Se sirve en la URL del token: no-referrer (TM7). --}}
@extends('layouts.acceso')

@section('cabecera')
    <meta name="referrer" content="no-referrer">
@endsection

@section('titulo', 'Este enlace ya no sirve')

@section('contenido')
    <div class="form-header">
        <h1 class="form-title">Este enlace ya no sirve</h1>
        <p class="form-subtitle">Tu contraseña no cambió.</p>
    </div>
    <div class="alert alert-error" role="alert">
        @include('auth.recuperar.partes.icono-error')
        <span>Pasaron más de 60 minutos, ya se usó o pediste uno más nuevo.</span>
    </div>
    <a class="btn-submit" href="{{ route('contrasena.solicitud') }}">Pedir un enlace nuevo</a>
    <p class="enlace-volver"><a class="forgot-link" href="{{ route('login') }}">Volver a iniciar sesión</a></p>
    <dl class="talon">
        <dt>¿Sin correo?</dt><dd>Llama a la consulta: <span class="tel">{{ config('clinica.contacto.telefono') }}</span></dd>
    </dl>
@endsection
