{{-- Spec 001, en el layout de acceso desde la spec 041 (SE2.a), con su aspecto de siempre (D20): un solo
     h1 y el lema como párrafo, sin estilos en línea ni Google Fonts. El comportamiento no cambia y conserva
     el botón para ver la contraseña (D19). El enlace de recuperar la contraseña solo se muestra con el
     interruptor (D8). --}}
@extends('layouts.acceso')

@section('cabecera')
    {{-- D19: solo el botón para ver la contraseña; sin él, la página funciona igual. --}}
    <script src="{{ asset('js/acceso.js') }}?v={{ filemtime(public_path('js/acceso.js')) }}" defer></script>
@endsection

@section('titulo', 'Iniciar sesión')

@section('contenido')
    @if(session('contrasena_cambiada'))
        <div class="alert alert-exito" role="status">
            @include('auth.recuperar.partes.icono-exito')
            <span><strong>Tu contraseña cambió.</strong>Inicia sesión con la nueva. Cerramos tus otras sesiones abiertas.</span>
        </div>
    @endif

    <div class="form-header">
        <h1 class="form-title">Bienvenido de vuelta</h1>
        <p class="form-subtitle">Ingresa tus datos para continuar</p>
    </div>

    @if($errors->any())
        {{-- /review R10: si el error es de un campo, el foco ya lo lee enlazado a ese campo. --}}
        <div class="alert alert-error" @if($errors->has('email') || $errors->has('password')) aria-hidden="true" @else role="alert" @endif>
            @include('auth.recuperar.partes.icono-error')
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="/login">
        @csrf

        <div class="field-group">
            <label class="field-label" for="email">Correo electrónico</label>
            <div class="field-wrap">
                @include('auth.recuperar.partes.icono-correo')
                <input class="field @error('email') error @enderror" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tu@correo.com" autocomplete="email" @unless($errors->has('password') && ! $errors->has('email')) autofocus @endunless @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')
                <div class="error-msg" id="email-error">@include('auth.recuperar.partes.icono-error'){{ $message }}</div>
            @enderror
        </div>

        <div class="field-group">
            <label class="field-label" for="password">Contraseña</label>
            <div class="field-wrap">
                @include('auth.recuperar.partes.icono-clave')
                <input class="field field-con-ojo @error('password') error @enderror" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" @if($errors->has('password') && ! $errors->has('email')) autofocus @endif @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button class="ver-clave" type="button" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="password" hidden>
                    <svg class="ojo-abierto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="ojo-cerrado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/></svg>
                </button>
            </div>
            @error('password')
                <div class="error-msg" id="password-error">@include('auth.recuperar.partes.icono-error'){{ $message }}</div>
            @enderror
        </div>

        <div class="remember-row">
            <label class="remember-label"><input type="checkbox" name="remember"> Recordarme</label>
            @if(config('acceso.recuperar_contrasena'))
                <a class="forgot-link" href="{{ route('contrasena.solicitud') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button class="btn-submit" type="submit">Iniciar sesión</button>
    </form>

    <div class="divider"><span>o continúa con</span></div>

    <a class="btn-google" href="{{ route('auth.google') }}">
        {{-- Los cuatro colores del logo son los de la marca de Google, no del sistema (D20). --}}
        <svg viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
            <path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
            <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
        </svg>
        Continuar con Google
    </a>

    <div class="divider"><span>¿Eres paciente nuevo?</span></div>

    <p class="register-link"><a href="/registro">Crear una cuenta</a> y agenda tu primera cita</p>
@endsection
