{{-- Spec 041 (CA3, CA4, CA9): elegir la contraseña nueva, con los errores junto a su campo. En espera,
     el aviso y el botón de «Demasiados intentos» (D15). La URL lleva el token: no-referrer (TM7). --}}
@extends('layouts.acceso')

@section('cabecera')
    <meta name="referrer" content="no-referrer">
@endsection

@section('titulo', 'Contraseña nueva')
{{-- Spec 044 (CA1, CA3, D11): la salida de arriba es «Volver a iniciar sesión». --}}
@section('volver', 'login')

@section('contenido')
    <div class="form-header">
        <h1 class="form-title">Elige tu contraseña nueva</h1>
    </div>
    @if($espera)
        <div class="alert alert-espera" role="alert">
        @include('auth.recuperar.partes.icono-espera')
        <span><strong>Demasiados intentos.</strong>Espera un minuto y vuelve a intentarlo.</span>
    </div>
    @endif
    <form method="POST" action="{{ route('contrasena.restablecer') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="cuenta" value="{{ $cuenta }}">
        <div class="field-group">
            <label class="field-label" for="clave">Contraseña nueva</label>
            <div class="field-wrap">
                @include('auth.recuperar.partes.icono-clave')
                <input class="field @error('password') error @enderror" id="clave" name="password" type="password" autocomplete="new-password" required aria-describedby="clave-ayuda @error('password') clave-error @enderror" @error('password') autofocus aria-invalid="true" @enderror>
            </div>
            <p class="field-ayuda" id="clave-ayuda">Al menos 8 caracteres.</p>
            @error('password')
                <div class="error-msg" id="clave-error">@include('auth.recuperar.partes.icono-error'){{ $message }}</div>
            @enderror
        </div>
        <div class="field-group">
            <label class="field-label" for="clave2">Repite la contraseña</label>
            <div class="field-wrap">
                @include('auth.recuperar.partes.icono-clave')
                <input class="field @error('password_confirmation') error @enderror" id="clave2" name="password_confirmation" type="password" autocomplete="new-password" required @if($errors->has('password_confirmation') && ! $errors->has('password')) autofocus @endif @error('password_confirmation') aria-invalid="true" aria-describedby="clave2-error" @enderror>
            </div>
            @error('password_confirmation')
                <div class="error-msg" id="clave2-error">@include('auth.recuperar.partes.icono-error'){{ $message }}</div>
            @enderror
        </div>
        @if($espera)
            <button class="btn-submit" type="submit" disabled>Disponible en un minuto</button>
        @else
            <button class="btn-submit" type="submit">Guardar contraseña</button>
        @endif
    </form>
    <dl class="talon">
        <dt>Cuenta</dt><dd>{{ $correo }}</dd>
        <dt>Vale hasta</dt><dd>Hasta las {{ $hora }}.</dd>
        <dt>Después</dt><dd>Cerramos tus sesiones abiertas y te avisamos por correo.</dd>
    </dl>
@endsection
