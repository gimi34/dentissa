@extends('layouts.app')
@section('title', 'Mi perfil')

@section('content')

<div style="max-width:640px;display:flex;flex-direction:column;gap:20px">

    {{-- ── Datos personales y de contacto ───────────────────────────── --}}
    <div class="card">
        <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
            Datos personales
        </div>

        <form method="POST" action="{{ route('cliente.perfil.update') }}">
            @csrf @method('PUT')

            <div class="grid-2" style="margin-bottom:16px">
                <div>
                    <label class="field-label">Nombre <span style="color:var(--p500)">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}"
                           class="field {{ $errors->has('nombre') ? 'field-error' : '' }}" required>
                    @error('nombre')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Apellido <span style="color:var(--p500)">*</span></label>
                    <input type="text" name="apellido" value="{{ old('apellido', $user->apellido) }}"
                           class="field {{ $errors->has('apellido') ? 'field-error' : '' }}" required>
                    @error('apellido')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom:16px">
                <div>
                    <label class="field-label">Correo electrónico <span style="color:var(--p500)">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="field {{ $errors->has('email') ? 'field-error' : '' }}" required>
                    @error('email')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Teléfono celular</label>
                    <input type="tel" name="telefono" value="{{ old('telefono', $user->telefono) }}"
                           class="field {{ $errors->has('telefono') ? 'field-error' : '' }}" placeholder="55 1234 5678">
                    @error('telefono')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom:16px">
                <div>
                    <label class="field-label">Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento', optional($paciente)->fecha_nacimiento) }}"
                           class="field {{ $errors->has('fecha_nacimiento') ? 'field-error' : '' }}"
                           max="{{ now()->subYears(1)->format('Y-m-d') }}">
                    @error('fecha_nacimiento')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Género</label>
                    <select name="genero" class="field">
                        <option value="">Prefiero no especificar</option>
                        @php $genero = old('genero', optional($paciente)->genero); @endphp
                        <option value="femenino"  {{ $genero === 'femenino'  ? 'selected' : '' }}>Femenino</option>
                        <option value="masculino" {{ $genero === 'masculino' ? 'selected' : '' }}>Masculino</option>
                        <option value="otro"      {{ $genero === 'otro'      ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:16px">
                <label class="field-label">Dirección</label>
                <textarea name="direccion" class="field" rows="2" style="resize:none"
                    placeholder="Calle, número, colonia...">{{ old('direccion', optional($paciente)->direccion) }}</textarea>
            </div>

            <div style="margin-bottom:20px">
                <label class="field-label">Ciudad</label>
                <input type="text" name="ciudad" value="{{ old('ciudad', optional($paciente)->ciudad) }}"
                       class="field" placeholder="Ciudad de México">
            </div>

            <div style="margin-bottom:20px">
                <label class="field-label">Mi doctor</label>
                <div class="field" style="background:var(--p50);color:var(--text-muted);cursor:default">
                    @if(optional($paciente)->dentista_nombre)
                        Dr. {{ trim($paciente->dentista_nombre) }}{{ $paciente->dentista_especialidad ? ' · '.$paciente->dentista_especialidad : '' }}
                    @else
                        Aún no se te ha asignado un doctor
                    @endif
                </div>
                <div class="field-hint">Tu doctor asignado lo define la clínica. Si quieres que te atienda alguien más, contáctanos.</div>
            </div>

            <div style="display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>

    {{-- ── Contraseña ────────────────────────────────────────────────── --}}
    <div class="card">
        <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
            {{ $user->password_hash ? 'Cambiar contraseña' : 'Crear contraseña' }}
            @unless($user->password_hash)
                <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">
                    (tu cuenta se creó con Google, aún no tienes una)
                </span>
            @endunless
        </div>

        <form method="POST" action="{{ route('cliente.perfil.password') }}">
            @csrf @method('PUT')

            @if($user->password_hash)
                <div style="margin-bottom:16px">
                    <label class="field-label">Contraseña actual <span style="color:var(--p500)">*</span></label>
                    <input type="password" name="password_actual"
                           class="field {{ $errors->has('password_actual') ? 'field-error' : '' }}" required>
                    @error('password_actual')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
            @endif

            <div class="grid-2">
                <div>
                    <label class="field-label">Nueva contraseña <span style="color:var(--p500)">*</span></label>
                    <input type="password" name="password"
                           class="field {{ $errors->has('password') ? 'field-error' : '' }}"
                           placeholder="Mín. 8 caracteres" required>
                    @error('password')<div class="field-hint field-hint-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Confirmar contraseña <span style="color:var(--p500)">*</span></label>
                    <input type="password" name="password_confirmation" class="field"
                           placeholder="Repite la contraseña" required>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:20px">
                <button type="submit" class="btn btn-primary">
                    {{ $user->password_hash ? 'Actualizar contraseña' : 'Crear contraseña' }}
                </button>
            </div>
        </form>
    </div>

</div>

@push('styles')
<link href="{{ asset('css/citas.css') }}?v={{ filemtime(public_path('css/citas.css')) }}" rel="stylesheet">
@endpush

@endsection
