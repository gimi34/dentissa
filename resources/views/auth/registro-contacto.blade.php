<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · Completa tu registro</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
</head>
<body class="page-register">

<!-- ── Panel izquierdo ─────────────────────────────────────────── -->
<div class="panel-left">
    <div class="brand"></div>

    <div class="panel-illustration">
        <img src="{{ asset('images/logo-main.png') }}"
             alt="Dentissa"
             style="display:block;margin:0 auto;width:310px;height:310px;object-fit:contain;object-position:center;position:relative;z-index:2;">
    </div>
    <div class="panel-copy">
        <h1 class="panel-tagline">Ya casi<br>estás <em>lista</em></h1>
        <p class="panel-sub">Solo necesitamos tus datos de contacto para poder agendar tu primera cita.</p>
    </div>
</div>

<!-- ── Panel derecho ───────────────────────────────────────────── -->
<div class="panel-right">
    <div class="form-wrapper">
        <div class="form-container">

            <div class="progress-bar-wrap">
                <div class="progress-bar" style="width:100%"></div>
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

            <form method="POST" action="{{ route('register.contacto') }}">
                @csrf

                <div class="form-header">
                    <div class="step-indicator">Último paso</div>
                    <h2 class="form-title">¿Cómo contactarte, {{ $google['nombre'] }}?</h2>
                    <p class="form-subtitle">Para recordatorios y confirmaciones de citas de {{ $google['email'] }}</p>
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

                <div class="field-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terminos" id="terminos" required>
                        Acepto los <a href="#" style="color:var(--p500)">términos de uso</a> y la
                        <a href="#" style="color:var(--p500)">política de privacidad</a> de Dentissa
                    </label>
                </div>

                <div class="form-nav">
                    <button type="submit" class="btn-submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Crear mi cuenta
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
</body>
</html>
