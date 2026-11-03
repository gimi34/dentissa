{{-- Spec 001, vista pasada al layout de acceso en la spec 041 (c7, SE2.a): un solo h1 y el lema como
     párrafo, sin estilos en línea ni Google Fonts. El comportamiento no cambia. El enlace de recuperar
     la contraseña solo se muestra con el interruptor encendido (D8). --}}
@extends('layouts.acceso')

@section('titulo', 'Iniciar sesión')

@section('panel')
    <b>Tu sonrisa, nuestra prioridad</b>Gestiona tus citas, consulta tu historial y mantén tu salud dental al día.
@endsection

@section('carnet')
    <form class="carnet-cuerpo" method="POST" action="/login">
        @csrf

        @if(session('contrasena_cambiada'))
            <div class="aviso aviso-exito" role="status"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg><span><strong>Tu contraseña cambió.</strong>Inicia sesión con la nueva. Cerramos tus otras sesiones abiertas.</span></div>
        @endif

        <div class="encabezado">
            <h1>Bienvenido de vuelta</h1>
            <p class="sub">Ingresa tus datos para continuar</p>
        </div>

        @if($errors->any())
            <div class="aviso aviso-error" role="alert"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span>{{ $errors->first() }}</span></div>
        @endif

        <div class="campo @error('email') error @enderror">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tu@correo.com" autocomplete="email" autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span class="msg-error" id="email-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="campo @error('password') error @enderror">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <span class="msg-error" id="password-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="fila">
            <label><input type="checkbox" name="remember"> Recordarme</label>
            @if(config('acceso.recuperar_contrasena'))
                <a class="enlace" href="{{ route('contrasena.solicitud') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button class="btn btn-primario" type="submit">Iniciar sesión</button>

        <p class="separador">o continúa con</p>
        <a class="btn btn-secundario" href="{{ route('auth.google') }}">Continuar con Google</a>

        <p class="separador">¿Eres paciente nuevo?</p>
        <p class="pie"><a class="en-texto" href="/registro">Crear una cuenta</a> y agenda tu primera cita</p>
    </form>
@endsection
