<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · Crear cuenta</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
</head>
<body class="page-register">

<!-- ── Panel izquierdo ─────────────────────────────────────────── -->
<div class="panel-left">
    <div class="brand">
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
        </div>
    </div>
</div>

<!-- ── Panel derecho ───────────────────────────────────────────── -->
<div class="panel-right">
    <div class="form-wrapper">
        <div class="form-container">

            <!-- Barra de progreso -->
            <div class="progress-bar-wrap">
                <div class="progress-bar" id="progress-bar" style="width:50%"></div>
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

            <a href="{{ route('auth.google') }}" class="btn-google" style="margin-bottom:18px">
                <svg width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                    <path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                </svg>
                Registrarme con Google
            </a>

            <div class="divider" style="display:flex;align-items:center;gap:12px;margin-bottom:18px;color:var(--border)">
                <span style="flex:1;height:1px;background:var(--border)"></span>
                <span style="font-size:12px;color:var(--text-muted);white-space:nowrap">o completa el formulario</span>
                <span style="flex:1;height:1px;background:var(--border)"></span>
            </div>

            <form method="POST" action="/registro" id="register-form">
                @csrf

                {{-- ── PASO 1: Datos personales ─────────────────── --}}
                <div class="step active" id="step-1">
                    <div class="form-header">
                        <div class="step-indicator">Paso 1 de 2</div>
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
                        <button type="button" class="btn-next" onclick="nextStep(2)">
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
                        <div class="step-indicator">Paso 2 de 2</div>
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

                    <!-- Términos -->
                    <div class="field-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terminos" id="terminos" required>
                            Acepto los <a href="#" style="color:var(--p500)">términos de uso</a> y la
                            <a href="#" style="color:var(--p500)">política de privacidad</a> de Dentissa
                        </label>
                    </div>

                    <div class="form-nav">
                        <button type="button" class="btn-back" onclick="goTo(1)">← Atrás</button>
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
const totalSteps = 2;
const progress = [50, 100];

// Si hay errores de servidor, ir al paso correcto
@if($errors->has('nombre') || $errors->has('apellido') || $errors->has('email') || $errors->has('password'))
    currentStep = 1;
@elseif($errors->has('telefono') || $errors->has('fecha_nacimiento'))
    currentStep = 2;
@else
    currentStep = 1;
@endif

// Valida los campos obligatorios del paso indicado antes de avanzar
function validateStep(step) {
    const stepEl = document.getElementById('step-' + step);
    const fields = stepEl.querySelectorAll('input, select, textarea');
    for (const field of fields) {
        if (!field.checkValidity()) {
            field.reportValidity();
            return false;
        }
    }
    if (step === 1) {
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        if (password.value !== confirmation.value) {
            confirmation.setCustomValidity('Las contraseñas no coinciden');
            confirmation.reportValidity();
            confirmation.setCustomValidity('');
            return false;
        }
    }
    return true;
}

function nextStep(step) {
    if (!validateStep(currentStep)) return;
    goTo(step);
}

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