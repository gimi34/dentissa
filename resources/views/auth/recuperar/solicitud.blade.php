{{-- Spec 041 (CA1, CA9): pedir el enlace. En espera, el aviso manda y el botón queda desactivado. --}}
@extends('layouts.acceso')

@section('titulo', 'Recuperar acceso')

@section('carnet')
    <form class="carnet-cuerpo" method="POST" action="{{ route('contrasena.solicitar') }}">
        @csrf
        <span class="etiqueta">Recuperar acceso</span>
        <h1>¿Olvidaste tu contraseña?</h1>
        @if($espera)
            <div class="aviso aviso-espera" role="alert"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7v5l3 2"/></svg><span><strong>Demasiados intentos.</strong>Espera un minuto y vuelve a intentarlo.</span></div>
        @else
            <p class="sub">Escribe el correo de tu cuenta y te enviamos un enlace para elegir una nueva.</p>
        @endif
        <div class="campo @error('email') error @enderror">
            <label for="correo">Correo electrónico de tu cuenta</label>
            <input id="correo" name="email" type="email" autocomplete="email" placeholder="tu@correo.com" value="{{ old('email') }}" required @error('email') aria-invalid="true" aria-describedby="correo-error" @enderror>
            @error('email')
                <span class="msg-error" id="correo-error"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>{{ $message }}</span>
            @enderror
        </div>
        @if($espera)
            <button class="btn btn-espera" type="submit" disabled>Disponible en un minuto</button>
        @else
            <button class="btn btn-primario" type="submit">Enviar enlace</button>
        @endif
        <a class="enlace" href="{{ route('login') }}">Volver a iniciar sesión</a>
    </form>
    <div class="troquel" aria-hidden="true"></div>
    <dl class="talon">
        @unless($espera)
            <dt>El enlace</dt><dd>Vale 60 minutos desde que lo pidas, una sola vez.</dd>
        @endunless
        <dt>¿Sin correo?</dt><dd>Llama a la consulta: <span class="tel">{{ config('clinica.contacto.telefono') }}</span></dd>
    </dl>
@endsection
