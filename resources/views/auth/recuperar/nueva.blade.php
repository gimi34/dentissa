{{-- Spec 041 (CA3, CA4, CA9): elegir la contraseña nueva, con los errores junto a su campo. En espera,
     el aviso y el botón de «Demasiados intentos» (D15). La URL lleva el token: no-referrer (TM7). --}}
@extends('layouts.acceso')

@section('cabecera')
    <meta name="referrer" content="no-referrer">
@endsection

@section('titulo', 'Contraseña nueva')

@section('carnet')
    <form class="carnet-cuerpo" method="POST" action="{{ route('contrasena.restablecer') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="cuenta" value="{{ $cuenta }}">
        <span class="etiqueta">Contraseña nueva</span>
        <h1>Elige tu contraseña nueva</h1>
        @if($espera)
            <div class="aviso aviso-espera" role="alert"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7v5l3 2"/></svg><span><strong>Demasiados intentos.</strong>Espera un minuto y vuelve a intentarlo.</span></div>
        @endif
        <div class="campo @error('password') error @enderror">
            <label for="clave">Contraseña nueva</label>
            <input id="clave" name="password" type="password" autocomplete="new-password" required aria-describedby="clave-ayuda @error('password') clave-error @enderror" @error('password') aria-invalid="true" @enderror>
            <span class="ayuda" id="clave-ayuda">Al menos 8 caracteres.</span>
            @error('password')
                <span class="msg-error" id="clave-error"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>{{ $message }}</span>
            @enderror
        </div>
        <div class="campo @error('password_confirmation') error @enderror">
            <label for="clave2">Repite la contraseña</label>
            <input id="clave2" name="password_confirmation" type="password" autocomplete="new-password" required @error('password_confirmation') aria-invalid="true" aria-describedby="clave2-error" @enderror>
            @error('password_confirmation')
                <span class="msg-error" id="clave2-error"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>{{ $message }}</span>
            @enderror
        </div>
        @if($espera)
            <button class="btn btn-espera" type="submit" disabled>Disponible en un minuto</button>
        @else
            <button class="btn btn-primario" type="submit">Guardar contraseña</button>
        @endif
    </form>
    <div class="troquel" aria-hidden="true"></div>
    <dl class="talon">
        <dt>Cuenta</dt><dd>{{ $correo }}</dd>
        <dt>Vale hasta</dt><dd>Hasta las {{ $hora }}.</dd>
        <dt>Después</dt><dd>Cerramos tus sesiones abiertas y te avisamos por correo.</dd>
    </dl>
@endsection
