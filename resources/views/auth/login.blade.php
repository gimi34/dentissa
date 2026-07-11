<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · Iniciar sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --p50:  #FDF5F8;
            --p100: #FBEAF0;
            --p200: #F4C0D1;
            --p300: #ED93B1;
            --p400: #D4537E;
            --p500: #B83C65;
            --p600: #993556;
            --p800: #72243E;
            --text:       #2C1A21;
            --text-muted: #8A6872;
            --border:     #F0DCE4;
            --white:      #FFFFFF;
            --red:        #A32D2D;
            --red-bg:     #FCEBEB;
        }

        html, body { height: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            display: flex;
            min-height: 100vh;
            background: var(--p50);
        }

        /* ── Panel izquierdo (decorativo) ─────────────────────── */
        .panel-left {
            width: 48%;
            background: linear-gradient(145deg, var(--p600) 0%, #7a1f3a 60%, #4B1528 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px;
            flex-shrink: 0;
        }

        /* Círculos decorativos de fondo */
        .panel-left::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.07);
            top: -160px; right: -160px;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            width: 340px; height: 340px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.05);
            bottom: -80px; left: -80px;
        }

        .deco-circle {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .brand { position: relative; z-index: 2; }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.15);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(10px);
        }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 600;
            color: white;
            letter-spacing: -0.3px;
        }

        .panel-illustration {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
        }

        .panel-copy {
            position: relative;
            z-index: 2;
        }
        .panel-tagline {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 500;
            color: white;
            line-height: 1.25;
            margin-bottom: 14px;
            letter-spacing: -0.3px;
        }
        .panel-tagline em {
            font-style: italic;
            color: var(--p200);
        }
        .panel-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
            line-height: 1.7;
            max-width: 340px;
        }

        /* Bullets de features */
        .features {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 24px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.75);
        }
        .feature-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--p300);
            flex-shrink: 0;
        }

        /* ── Panel derecho (formulario) ───────────────────────── */
        .panel-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            background: var(--white);
        }

        .form-container {
            width: 100%;
            max-width: 400px;
        }

        .form-header {
            margin-bottom: 32px;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .form-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Campos */
        .field-group { margin-bottom: 18px; }
        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 7px;
            letter-spacing: 0.02em;
        }

        .field-wrap { position: relative; }
        .field-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            color: var(--p300);
            pointer-events: none;
        }

        .field {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: var(--p50);
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .field:focus {
            border-color: var(--p300);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(212,83,126,.08);
        }
        .field.error { border-color: var(--red); background: var(--red-bg); }

        /* Toggle password */
        .toggle-pass {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: var(--p300);
            padding: 2px; display: flex;
        }
        .toggle-pass:hover { color: var(--p500); }

        .error-msg {
            font-size: 12px;
            color: var(--red);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Recordarme */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: var(--text-muted);
            cursor: pointer;
        }
        .remember-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--p400);
            cursor: pointer;
        }
        .forgot-link {
            font-size: 13px;
            color: var(--p500);
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-link:hover { color: var(--p600); text-decoration: underline; }

        /* Botón principal */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--p400);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            position: relative;
            overflow: hidden;
        }
        .btn-submit:hover { background: var(--p500); box-shadow: 0 4px 16px rgba(212,83,126,.3); }
        .btn-submit:active { transform: scale(.99); }

        /* Separador */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: var(--border);
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .divider span { font-size: 12px; color: var(--text-muted); white-space: nowrap; }

        /* Registro link */
        .register-link {
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }
        .register-link a {
            color: var(--p500);
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover { text-decoration: underline; }

        /* Alert general */
        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .alert-error { background: var(--red-bg); color: var(--red); border: 1px solid #F7C1C1; }

        /* Animación entrada */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .form-container { animation: fadeUp .45s ease both; }

        /* Responsive */
        @media (max-width: 768px) {
            .panel-left { display: none; }
            .panel-right { background: var(--p50); }
        }
    </style>
</head>
<body>

<!-- ── Panel izquierdo ─────────────────────────────────────────── -->
<div class="panel-left">
    <!-- Círculos decorativos extra -->
    <div class="deco-circle" style="width:220px;height:220px;top:30%;left:-60px"></div>
    <div class="deco-circle" style="width:120px;height:120px;top:20%;right:60px"></div>
    <div class="deco-circle" style="width:80px;height:80px;bottom:25%;right:40px"></div>

    <!-- Logo -->
    <div class="brand">
        <div class="brand-logo">
<div class="logo-icon" style="background:transparent;border-radius:0;width:44px;height:44px;">
    <img src="{{ asset('images/logo-icon1.png') }}"
         alt="Dentissa"
         style="width:70px;height:70px;object-fit:contain;">
</div>
            
        </div>
    </div>

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
        <div class="features">
            <div class="feature-item"><div class="feature-dot"></div>Agenda citas desde cualquier lugar</div>
            <div class="feature-item"><div class="feature-dot"></div>Historial dental completo</div>
            <div class="feature-item"><div class="feature-dot"></div>Recordatorios automáticos</div>
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