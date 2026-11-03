{{-- Spec 041 (CA5, CA10, CA11): enlace caducado, usado, sustituido, alterado o de una cuenta desactivada.
     Mismo mensaje en todos los casos. Se sirve en la URL del token: no-referrer (TM7). --}}
@extends('layouts.acceso')

@section('cabecera')
    <meta name="referrer" content="no-referrer">
@endsection

@section('titulo', 'Este enlace ya no sirve')

@section('carnet')
    <div class="carnet-cuerpo">
        <span class="etiqueta">Enlace vencido</span>
        <h1>Este enlace ya no sirve</h1>
        <div class="aviso aviso-error" role="alert"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span>Pasaron más de 60 minutos, ya se usó o pediste uno más nuevo.</span></div>
        <p class="sub">Tu contraseña no cambió.</p>
        <a class="btn btn-primario" href="{{ route('contrasena.solicitud') }}">Pedir un enlace nuevo</a>
        <a class="enlace" href="{{ route('login') }}">Volver a iniciar sesión</a>
    </div>
    <div class="troquel" aria-hidden="true"></div>
    <dl class="talon">
        <dt>¿Sin correo?</dt><dd>Llama a la consulta: <span class="tel">{{ config('clinica.contacto.telefono') }}</span></dd>
    </dl>
@endsection
