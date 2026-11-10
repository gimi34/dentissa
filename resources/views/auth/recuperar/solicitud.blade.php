{{-- Spec 041 (CA1, CA9): pedir el enlace. En espera, el aviso manda y el botón queda desactivado. --}}
@extends('layouts.acceso')

@section('titulo', 'Recuperar acceso')
{{-- Spec 044 (CA1, D11): la salida de arriba es «Volver a iniciar sesión», sin repetirla abajo. --}}
@section('volver', 'login')

@section('contenido')
    <div class="form-header">
        <h1 class="form-title">¿Olvidaste tu contraseña?</h1>
        @unless($espera)
            <p class="form-subtitle">Escribe el correo de tu cuenta y te enviamos un enlace para elegir una nueva.</p>
        @endunless
    </div>
    @if($espera)
        <div class="alert alert-espera" role="alert">
        @include('auth.recuperar.partes.icono-espera')
        <span><strong>Demasiados intentos.</strong>Espera un minuto y vuelve a intentarlo.</span>
    </div>
    @endif
    <form method="POST" action="{{ route('contrasena.solicitar') }}">
        @csrf
        <div class="field-group">
            <label class="field-label" for="correo">Correo electrónico de tu cuenta</label>
            <div class="field-wrap">
                @include('auth.recuperar.partes.icono-correo')
                <input class="field @error('email') error @enderror" id="correo" name="email" type="email" autocomplete="email" placeholder="tu@correo.com" value="{{ old('email') }}" required @error('email') autofocus aria-invalid="true" aria-describedby="correo-error" @enderror>
            </div>
            @error('email')
                <div class="error-msg" id="correo-error">@include('auth.recuperar.partes.icono-error'){{ $message }}</div>
            @enderror
        </div>
        @if($espera)
            <button class="btn-submit" type="submit" disabled>Disponible en un minuto</button>
        @else
            <button class="btn-submit" type="submit">Enviar enlace</button>
        @endif
    </form>
    <dl class="talon">
        @unless($espera)
            <dt>El enlace</dt><dd>Vale 60 minutos desde que lo pidas, una sola vez.</dd>
        @endunless
        <dt>¿Sin correo?</dt><dd>Llama a la consulta: <span class="tel">{{ config('clinica.contacto.telefono') }}</span></dd>
    </dl>
@endsection
