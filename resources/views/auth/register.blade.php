<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · Crear cuenta</title>
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
            --text-light: #C0A0AC;
            --border:     #F0DCE4;
            --white:      #FFFFFF;
            --green:      #1D9E75;
            --green-bg:   #E1F5EE;
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

        /* ── Panel izquierdo ──────────────────────────────────── */
        .panel-left {
            width: 38%;
            background: linear-gradient(145deg, var(--p600) 0%, #7a1f3a 60%, #4B1528 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 44px;
            flex-shrink: 0;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.07);
            top: -140px; right: -140px;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            width: 260px; height: 260px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.05);
            bottom: -60px; left: -60px;
        }

        .brand { position: relative; z-index: 2; }
        .brand-logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(10px);
        }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 600;
            color: white;
        }

        .panel-illustration {
            position: relative; z-index: 2;
            flex: 1; display: flex;
            align-items: center; justify-content: center;
            padding: 20px 0;
        }

        .panel-copy { position: relative; z-index: 2; }
        .panel-tagline {
            font-family: 'Playfair Display', serif;
            font-size: 26px; font-weight: 500;
            color: white;
            line-height: 1.3; margin-bottom: 12px;
        }
        .panel-tagline em { font-style: italic; color: var(--p200); }
        .panel-sub { font-size: 13.5px; color: rgba(255,255,255,0.6); line-height: 1.7; }

        /* Pasos de progreso en panel */
        .steps-preview {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 24px;
        }
        .step-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: background .3s;
        }
        .step-preview.active { background: rgba(255,255,255,0.2); }
        .step-num {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600; color: white;
            flex-shrink: 0;
            transition: background .3s, color .3s;
        }
        .step-preview.active .step-num { background: var(--p300); color: white; }
        .step-preview.done .step-num { background: var(--green); color: white; }
        .step-label { font-size: 13px; color: rgba(255,255,255,0.75); font-weight: 500; }
        .step-preview.active .step-label { color: white; }

        /* ── Panel derecho (formulario) ───────────────────────── */
        .panel-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--white);
            overflow-y: auto;
        }

        .form-wrapper {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 48px 32px;
        }

        .form-container {
            width: 100%;
            max-width: 480px;
        }

        /* Barra de progreso superior */
        .progress-bar-wrap {
            background: var(--p100);
            height: 4px;
            border-radius: 2px;
            margin-bottom: 32px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: var(--p400);
            border-radius: 2px;
            transition: width .4s ease;
        }

        .form-header { margin-bottom: 28px; }
        .step-indicator {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--p400);
            margin-bottom: 6px;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px; font-weight: 600;
            color: var(--text); letter-spacing: -.3px;
            margin-bottom: 4px;
        }
        .form-subtitle { font-size: 13.5px; color: var(--text-muted); }

        /* Pasos */
        .step { display: none; }
        .step.active { display: block; animation: fadeUp .35s ease both; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Campos */
        .field-group { margin-bottom: 16px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .field-label {
            display: block;
            font-size: 12px; font-weight: 500;
            color: var(--text-muted); margin-bottom: 6px;
        }
        .required { color: var(--p400); margin-left: 2px; }

        .field-wrap { position: relative; }
        .field-icon {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            width: 15px; height: 15px;
            color: var(--p300); pointer-events: none;
        }
        .field-icon-right {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
        }

        .field {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: var(--p50);
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
            -webkit-appearance: none;
        }
        .field.no-icon { padding-left: 14px; }
        .field:focus {
            border-color: var(--p300);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(212,83,126,.07);
        }
        .field.field-error { border-color: var(--red); background: var(--red-bg); }
        select.field { cursor: pointer; padding-right: 36px; }

        .toggle-pass {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--p300); padding: 2px; display: flex;
        }
        .toggle-pass:hover { color: var(--p500); }

        .field-hint { font-size: 11.5px; color: var(--text-light); margin-top: 4px; }
        .error-msg { font-size: 12px; color: var(--red); margin-top: 4px; }

        /* Checkboxes opcionales */
        .checkbox-label {
            display: flex; align-items: flex-start; gap: 10px;
            font-size: 13.5px; color: var(--text-muted);
            cursor: pointer; line-height: 1.5;
        }
        .checkbox-label input { margin-top: 2px; accent-color: var(--p400); flex-shrink: 0; }

        /* Tipo de sangre chips */
        .blood-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
        .blood-chip {
            padding: 6px 14px;
            border: 1.5px solid var(--border);
            border-radius: 20px;
            font-size: 13px; color: var(--text-muted);
            cursor: pointer; background: var(--p50);
            transition: all .15s; user-select: none;
        }
        .blood-chip.selected { border-color: var(--p400); background: var(--p100); color: var(--p600); font-weight: 500; }
        #tipo_sangre { display: none; }

        /* Botones de navegación */
        .form-nav {
            display: flex;
            gap: 10px;
            margin-top: 28px;
            align-items: center;
        }
        .btn-next, .btn-submit {
            flex: 1;
            padding: 13px;
            border: none; border-radius: 10px;
            font-size: 14.5px; font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-next { background: var(--p400); color: white; }
        .btn-next:hover { background: var(--p500); box-shadow: 0 4px 16px rgba(212,83,126,.25); }
        .btn-back {
            padding: 13px 18px;
            background: transparent; color: var(--text-muted);
            border: 1.5px solid var(--border); border-radius: 10px;
            font-size: 14px; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: all .2s;
        }
        .btn-back:hover { background: var(--p100); color: var(--p600); border-color: var(--p200); }
        .btn-submit { background: var(--p400); color: white; }
        .btn-submit:hover { background: var(--p500); box-shadow: 0 4px 16px rgba(212,83,126,.3); }

        /* Alert de error global */
        .alert {
            padding: 12px 14px; border-radius: 9px;
            font-size: 13.5px; margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 9px;
        }
        .alert-error { background: var(--red-bg); color: var(--red); border: 1px solid #F7C1C1; }
        .alert-error svg { flex-shrink: 0; margin-top: 1px; }

        /* Footer del form */
        .form-footer {
            text-align: center;
            padding: 20px 0 0;
            font-size: 13.5px;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            margin-top: 20px;
        }
        .form-footer a { color: var(--p500); text-decoration: none; font-weight: 500; }
        .form-footer a:hover { text-decoration: underline; }

        /* Responsive */
        @media (max-width: 860px) { .panel-left { display: none; } }
        @media (max-width: 520px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<!-- ── Panel izquierdo ─────────────────────────────────────────── -->
<div class="panel-left">
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
    <div class="panel-copy">
        <h1 class="panel-tagline">Únete a<br>nuestra <em>comunidad</em></h1>
        <p class="panel-sub">Registra tu cuenta en menos de 2 minutos y empieza a cuidar tu sonrisa.</p>

        <div class="steps-preview" id="steps-preview">
            <div class="step-preview active" id="prev-1">
                <div class="step-num">1</div>
                <span class="step-label">Datos personales</span>
            </div>
            <div class="step-preview" id="prev-2">
                <div class="step-num">2</div>
                <span class="step-label">Datos de contacto</span>
            </div>
            <div class="step-preview" id="prev-3">
                <div class="step-num">3</div>
                <span class="step-label">Información médica</span>
            </div>
        </div>
    </div>
</div>

<!-- ── Panel derecho ───────────────────────────────────────────── -->
<div class="panel-right">
    <div class="form-wrapper">
        <div class="form-container">

            <!-- Barra de progreso -->
            <div class="progress-bar-wrap">
                <div class="progress-bar" id="progress-bar" style="width:33%"></div>
            </div>

            @if($errors->any())
                <div class="alert alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="/registro" id="register-form">
                @csrf

                {{-- ── PASO 1: Datos personales ─────────────────── --}}
                <div class="step active" id="step-1">
                    <div class="form-header">
                        <div class="step-indicator">Paso 1 de 3</div>
                        <h2 class="form-title">¿Cómo te llamas?</h2>
                        <p class="form-subtitle">Necesitamos tu nombre para identificar tu cuenta</p>
                    </div>

                    <div class="grid-2">
                        <div class="field-group">
                            <label class="field-label" for="nombre">Nombre <span class="required">*</span></label>
                            <div class="field-wrap">
                                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="nombre" name="nombre"
                                    class="field {{ $errors->has('nombre') ? 'field-error' : '' }}"
                                    value="{{ old('nombre') }}"
                                    placeholder="María" required>
                            </div>
                            @error('nombre')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label" for="apellido">Apellido <span class="required">*</span></label>
                            <div class="field-wrap">
                                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="apellido" name="apellido"
                                    class="field {{ $errors->has('apellido') ? 'field-error' : '' }}"
                                    value="{{ old('apellido') }}"
                                    placeholder="García" required>
                            </div>
                            @error('apellido')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="email">Correo electrónico <span class="required">*</span></label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" name="email"
                                class="field {{ $errors->has('email') ? 'field-error' : '' }}"
                                value="{{ old('email') }}"
                                placeholder="maria@correo.com" required>
                        </div>
                        @error('email')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid-2">
                        <div class="field-group">
                            <label class="field-label" for="password">Contraseña <span class="required">*</span></label>
                            <div class="field-wrap">
                                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <input type="password" id="password" name="password"
                                    class="field {{ $errors->has('password') ? 'field-error' : '' }}"
                                    placeholder="Mín. 8 caracteres" required
                                    oninput="checkStrength(this.value)">
                                <button type="button" class="toggle-pass" onclick="togglePass('password')">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <!-- Indicador de fortaleza -->
                            <div style="display:flex;gap:4px;margin-top:7px" id="strength-bars">
                                <div style="height:3px;flex:1;border-radius:2px;background:var(--border);transition:background .3s" id="sb1"></div>
                                <div style="height:3px;flex:1;border-radius:2px;background:var(--border);transition:background .3s" id="sb2"></div>
                                <div style="height:3px;flex:1;border-radius:2px;background:var(--border);transition:background .3s" id="sb3"></div>
                                <div style="height:3px;flex:1;border-radius:2px;background:var(--border);transition:background .3s" id="sb4"></div>
                            </div>
                            <div class="field-hint" id="strength-label" style="min-height:16px"></div>
                            @error('password')<div class="error-msg">{{ $message }}</div>@enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label" for="password_confirmation">Confirmar contraseña <span class="required">*</span></label>
                            <div class="field-wrap">
                                <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="field" placeholder="Repite la contraseña" required>
                                <button type="button" class="toggle-pass" onclick="togglePass('password_confirmation')">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn-next" onclick="goTo(2)">
                            Continuar
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ── PASO 2: Contacto ─────────────────────────── --}}
                <div class="step" id="step-2">
                    <div class="form-header">
                        <div class="step-indicator">Paso 2 de 3</div>
                        <h2 class="form-title">¿Cómo contactarte?</h2>
                        <p class="form-subtitle">Para recordatorios y confirmaciones de citas</p>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="telefono">Teléfono celular</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                            </svg>
                            <input type="tel" id="telefono" name="telefono"
                                class="field {{ $errors->has('telefono') ? 'field-error' : '' }}"
                                value="{{ old('telefono') }}"
                                placeholder="55 1234 5678">
                        </div>
                        @error('telefono')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="fecha_nacimiento">Fecha de nacimiento</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                class="field {{ $errors->has('fecha_nacimiento') ? 'field-error' : '' }}"
                                value="{{ old('fecha_nacimiento') }}"
                                max="{{ now()->subYears(1)->format('Y-m-d') }}">
                        </div>
                        @error('fecha_nacimiento')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="genero">Género</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="4"/>
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                            </svg>
                            <select id="genero" name="genero" class="field">
                                <option value="">Prefiero no especificar</option>
                                <option value="femenino"   {{ old('genero') === 'femenino' ? 'selected' : '' }}>Femenino</option>
                                <option value="masculino"  {{ old('genero') === 'masculino' ? 'selected' : '' }}>Masculino</option>
                                <option value="otro"       {{ old('genero') === 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="direccion">Dirección</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="top:14px;transform:none">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <textarea id="direccion" name="direccion"
                                class="field" style="resize:none;height:72px;padding-top:10px"
                                placeholder="Calle, número, colonia...">{{ old('direccion') }}</textarea>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="ciudad">Ciudad</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            <input type="text" id="ciudad" name="ciudad"
                                class="field" value="{{ old('ciudad') }}"
                                placeholder="Ciudad de México">
                        </div>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn-back" onclick="goTo(1)">← Atrás</button>
                        <button type="button" class="btn-next" onclick="goTo(3)">
                            Continuar
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ── PASO 3: Info médica ──────────────────────── --}}
                <div class="step" id="step-3">
                    <div class="form-header">
                        <div class="step-indicator">Paso 3 de 3</div>
                        <h2 class="form-title">Información médica</h2>
                        <p class="form-subtitle">Opcional, pero nos ayuda a atenderte mejor</p>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Tipo de sangre</label>
                        <input type="hidden" id="tipo_sangre" name="tipo_sangre" value="{{ old('tipo_sangre') }}">
                        <div class="blood-chips">
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts)
                                <div class="blood-chip {{ old('tipo_sangre') === $ts ? 'selected' : '' }}"
                                     onclick="selectBlood('{{ $ts }}')">{{ $ts }}</div>
                            @endforeach
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="alergias">Alergias conocidas</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="top:12px;transform:none">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <textarea id="alergias" name="alergias"
                                class="field" rows="2" style="resize:none;padding-top:10px"
                                placeholder="Ej: Penicilina, látex, anestesia...">{{ old('alergias') }}</textarea>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="enfermedades_cronicas">Enfermedades crónicas</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="top:12px;transform:none">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                            <textarea id="enfermedades_cronicas" name="enfermedades_cronicas"
                                class="field" rows="2" style="resize:none;padding-top:10px"
                                placeholder="Ej: Diabetes, hipertensión, cardiopatía...">{{ old('enfermedades_cronicas') }}</textarea>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="medicamentos_actuales">Medicamentos actuales</label>
                        <div class="field-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M10.5 20H4a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h3.93a2 2 0 0 1 1.66.9l.82 1.2a2 2 0 0 0 1.66.9H20a2 2 0 0 1 2 2v3"/>
                                <circle cx="18" cy="18" r="4"/>
                                <path d="m15.5 15.5 5 5"/>
                            </svg>
                            <input type="text" id="medicamentos_actuales" name="medicamentos_actuales"
                                class="field" value="{{ old('medicamentos_actuales') }}"
                                placeholder="Ej: Metformina 500mg, Losartán 50mg...">
                        </div>
                    </div>

                    <!-- Contacto de emergencia -->
                    <div style="padding:14px 16px;background:var(--p50);border-radius:10px;border:1px solid var(--border);margin-bottom:16px">
                        <div style="font-size:12.5px;font-weight:500;color:var(--text-muted);margin-bottom:12px">
                            Contacto de emergencia (opcional)
                        </div>
                        <div class="grid-2">
                            <div class="field-group" style="margin-bottom:0">
                                <label class="field-label" for="contacto_emergencia">Nombre</label>
                                <div class="field-wrap">
                                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <input type="text" id="contacto_emergencia" name="contacto_emergencia"
                                        class="field" value="{{ old('contacto_emergencia') }}"
                                        placeholder="Nombre completo">
                                </div>
                            </div>
                            <div class="field-group" style="margin-bottom:0">
                                <label class="field-label" for="telefono_emergencia">Teléfono</label>
                                <div class="field-wrap">
                                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                                    </svg>
                                    <input type="tel" id="telefono_emergencia" name="telefono_emergencia"
                                        class="field" value="{{ old('telefono_emergencia') }}"
                                        placeholder="55 1234 5678">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Términos -->
                    <div class="field-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terminos" id="terminos" required>
                            Acepto los <a href="#" style="color:var(--p500)">términos de uso</a> y la
                            <a href="#" style="color:var(--p500)">política de privacidad</a> de Dentissa
                        </label>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn-back" onclick="goTo(2)">← Atrás</button>
                        <button type="submit" class="btn-submit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Crear mi cuenta
                        </button>
                    </div>
                </div>

            </form>

            <div class="form-footer">
                ¿Ya tienes cuenta? <a href="/login">Inicia sesión aquí</a>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 3;
const progress = [33, 66, 100];

// Si hay errores de servidor, ir al paso correcto
@if($errors->has('nombre') || $errors->has('apellido') || $errors->has('email') || $errors->has('password'))
    currentStep = 1;
@elseif($errors->has('telefono') || $errors->has('fecha_nacimiento'))
    currentStep = 2;
@else
    currentStep = 1;
@endif

function goTo(step) {
    // Ocultar paso actual
    document.getElementById('step-' + currentStep).classList.remove('active');
    document.getElementById('prev-' + currentStep).classList.remove('active');
    document.getElementById('prev-' + currentStep).classList.add('done');

    currentStep = step;

    // Mostrar nuevo paso
    document.getElementById('step-' + step).classList.add('active');

    // Actualizar indicadores del panel
    document.querySelectorAll('.step-preview').forEach((el, i) => {
        el.classList.remove('active', 'done');
        if (i + 1 < step) el.classList.add('done');
        if (i + 1 === step) el.classList.add('active');
    });

    // Actualizar barra de progreso
    document.getElementById('progress-bar').style.width = progress[step - 1] + '%';

    // Scroll al inicio
    document.querySelector('.panel-right').scrollTop = 0;
}

// Indicador de fortaleza de contraseña
function checkStrength(val) {
    let score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const colors = ['', '#E24B4A', '#EF9F27', '#1D9E75', '#0F6E56'];
    const labels = ['', 'Débil', 'Regular', 'Buena', 'Excelente'];

    [1,2,3,4].forEach(i => {
        const bar = document.getElementById('sb' + i);
        bar.style.background = i <= score ? colors[score] : 'var(--border)';
    });
    const lbl = document.getElementById('strength-label');
    lbl.textContent = val.length > 0 ? labels[score] : '';
    lbl.style.color = colors[score];
}

// Toggle visibilidad contraseña
function togglePass(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Tipo de sangre
function selectBlood(val) {
    document.querySelectorAll('.blood-chip').forEach(c => c.classList.remove('selected'));
    event.target.classList.add('selected');
    document.getElementById('tipo_sangre').value = val;
}

// Inicializar con el paso correcto si hay errores
document.addEventListener('DOMContentLoaded', () => {
    @if($errors->any())
        // Resetear y mostrar solo el paso 1 si hay errores
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step-1').classList.add('active');
    @endif
});
</script>
</body>
</html>