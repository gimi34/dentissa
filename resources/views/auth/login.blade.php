<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · Iniciar sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
</head>
<body class="page-login">

<!-- ── Panel izquierdo ─────────────────────────────────────────── -->
<div class="panel-left">
    <!-- Círculos decorativos extra -->
    <div class="deco-circle" style="width:220px;height:220px;top:30%;left:-60px"></div>
    <div class="deco-circle" style="width:120px;height:120px;top:20%;right:60px"></div>
    <div class="deco-circle" style="width:80px;height:80px;bottom:25%;right:40px"></div>

    <!-- Ilustración central -->
<div class="panel-illustration">
    <img src="{{ asset('images/logo-main.png') }}"
         alt="Dentissa"
         style="
             display: block;
             margin: 0 auto;
             width: 310px;
             height: 310px;
             object-fit: contain;
             object-position: center;
             position: relative;
             z-index: 2;
         ">
</div>

    <!-- Copy -->
    <div class="panel-copy">
        <h1 class="panel-tagline">Tu sonrisa,<br>nuestra <em>prioridad</em></h1>
        <p class="panel-sub">Gestiona tus citas, consulta tu historial y mantén tu salud dental al día.</p>
        </div>
    </div>
</div>

<!-- ── Panel derecho (formulario) ─────────────────────────────── -->
<div class="panel-right">
    <div class="form-container">

        <div class="form-header">
            <h2 class="form-title">Bienvenido de vuelta</h2>
            <p class="form-subtitle">Ingresa tus datos para continuar</p>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="/login">
            @csrf

            <!-- Email -->
            <div class="field-group">
                <label class="field-label" for="email">Correo electrónico</label>
                <div class="field-wrap">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="field {{ $errors->has('email') ? 'error' : '' }}"
                        value="{{ old('email') }}"
                        placeholder="tu@correo.com"
                        autocomplete="email"
                        autofocus
                    >
                </div>
                @error('email')
                    <div class="error-msg">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Contraseña -->
            <div class="field-group">
                <label class="field-label" for="password">Contraseña</label>
                <div class="field-wrap">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field {{ $errors->has('password') ? 'error' : '' }}"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-pass" onclick="togglePass()" id="toggle-btn" title="Mostrar contraseña">
                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div class="error-msg">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Recordarme + olvidé -->
            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember"> Recordarme
                </label>
                <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit">Iniciar sesión</button>
        </form>

        <div class="divider"><span>o continúa con</span></div>

        <a href="{{ route('auth.google') }}" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                <path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
            </svg>
            Continuar con Google
        </a>

        <div class="divider"><span>¿Eres paciente nuevo?</span></div>

        <div class="register-link">
            <a href="/registro">Crear una cuenta</a>
            &nbsp;y agenda tu primera cita
        </div>

    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>`;
    }
}
</script>
</body>
</html>