@extends('layouts.app')
@section('title', isset($miembro) ? 'Editar personal' : 'Nuevo miembro')

@section('content')
<div style="max-width:760px">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
        <a href="{{ route('admin.personal') }}" style="color:var(--text-muted);text-decoration:none">Personal</a>
        <span>›</span>
        <span style="color:var(--text)">{{ isset($miembro) ? 'Editar: '.$miembro->nombre.' '.$miembro->apellido : 'Nuevo miembro' }}</span>
    </div>

    <form method="POST"
          action="{{ isset($miembro) ? route('admin.personal.update', $miembro->id) : route('admin.personal.store') }}">
        @csrf
        @if(isset($miembro)) @method('PUT') @endif

        <div style="display:flex;flex-direction:column;gap:18px">

            {{-- Datos personales --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Datos personales
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Nombre <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="nombre" class="field {{ $errors->has('nombre') ? 'field-error' : '' }}"
                            value="{{ old('nombre', $miembro->nombre ?? '') }}" required placeholder="Nombre">
                        @error('nombre')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label">Apellido <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="apellido" class="field {{ $errors->has('apellido') ? 'field-error' : '' }}"
                            value="{{ old('apellido', $miembro->apellido ?? '') }}" required placeholder="Apellido">
                        @error('apellido')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Correo <span style="color:var(--p500)">*</span></label>
                        <input type="email" name="email" class="field {{ $errors->has('email') ? 'field-error' : '' }}"
                            value="{{ old('email', $miembro->email ?? '') }}" required placeholder="correo@clinica.com">
                        @error('email')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label">Teléfono</label>
                        <input type="tel" name="telefono" class="field"
                            value="{{ old('telefono', $miembro->telefono ?? '') }}" placeholder="55 1234 5678">
                    </div>
                </div>
                @if(!isset($miembro))
                    <div class="field-group">
                        <label class="field-label">Contraseña temporal</label>
                        <input type="text" name="password" class="field"
                            value="{{ old('password') }}" placeholder="Dejar vacío para generar automáticamente">
                        <div class="field-hint">Si se deja vacío se genera una contraseña aleatoria.</div>
                    </div>
                @endif
            </div>

            {{-- Tipo y rol --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Rol en la clínica
                </div>

                {{-- Selector de tipo visual --}}
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:4px" id="tipo-selector">
                    @foreach(['dentista' => ['Dentista','var(--p100)','var(--p500)'], 'asistente' => ['Asistente','var(--blue-bg)','var(--blue)'], 'administrador' => ['Administrador','var(--green-bg)','var(--green)']] as $val => [$label, $bg, $color])
                    <label style="cursor:pointer">
                        <input type="radio" name="tipo" value="{{ $val }}"
                               {{ old('tipo', $miembro->tipo ?? '') === $val ? 'checked' : '' }}
                               style="display:none" class="tipo-radio" onchange="selectTipo('{{ $val }}')">
                        <div class="tipo-card" id="tipo-{{ $val }}"
                             style="padding:14px;border-radius:var(--radius-sm);border:2px solid {{ old('tipo', $miembro->tipo ?? '') === $val ? $color : 'var(--border)' }};background:{{ old('tipo', $miembro->tipo ?? '') === $val ? $bg : 'var(--white)' }};text-align:center;transition:all .15s">
                            <div style="font-size:13.5px;font-weight:500;color:{{ old('tipo', $miembro->tipo ?? '') === $val ? $color : 'var(--text-muted)' }}">{{ $label }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('tipo')<div class="error-msg">{{ $message }}</div>@enderror
            </div>

            {{-- Datos profesionales --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Información profesional
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Especialidad</label>
                        <input type="text" name="especialidad" class="field"
                            value="{{ old('especialidad', $miembro->especialidad ?? '') }}"
                            placeholder="Ej: Ortodoncia, Endodoncia...">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Número de cédula</label>
                        <input type="text" name="numero_cedula" class="field"
                            value="{{ old('numero_cedula', $miembro->numero_cedula ?? '') }}"
                            placeholder="Cédula profesional">
                    </div>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Universidad</label>
                        <input type="text" name="universidad" class="field"
                            value="{{ old('universidad', $miembro->universidad ?? '') }}"
                            placeholder="UNAM, UAM, IPN...">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Años de experiencia</label>
                        <input type="number" name="anios_experiencia" class="field"
                            value="{{ old('anios_experiencia', $miembro->anios_experiencia ?? 0) }}"
                            min="0" max="60" placeholder="0">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Biografía / Descripción</label>
                    <textarea name="biografia" class="field" rows="3" style="resize:vertical"
                        placeholder="Breve descripción del profesional para el perfil...">{{ old('biografia', $miembro->biografia ?? '') }}</textarea>
                </div>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                @if(isset($miembro))
                    <a href="{{ route('admin.personal.show', $miembro->id) }}" class="btn btn-ghost">Cancelar</a>
                @else
                    <a href="{{ route('admin.personal') }}" class="btn btn-ghost">Cancelar</a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    {{ isset($miembro) ? 'Guardar cambios' : 'Registrar miembro' }}
                </button>
            </div>

        </div>
    </form>
</div>

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300);box-shadow:0 0 0 3px rgba(212,83,126,.07) }
.field-error { border-color:var(--red)!important }
.field-hint { font-size:11.5px;color:var(--text-light);margin-top:4px }
.error-msg { font-size:12px;color:var(--red);margin-top:4px }
.field-group { margin-bottom:0 }
textarea.field { line-height:1.6 }
</style>

@push('scripts')
<script>
const tipoStyles = {
    dentista:      { border: 'var(--p500)',   bg: 'var(--p100)',    color: 'var(--p500)'   },
    asistente:     { border: 'var(--blue)',   bg: 'var(--blue-bg)', color: 'var(--blue)'   },
    administrador: { border: 'var(--green)',  bg: 'var(--green-bg)',color: 'var(--green)'  },
};

function selectTipo(val) {
    Object.keys(tipoStyles).forEach(t => {
        const card = document.getElementById('tipo-' + t);
        const s    = tipoStyles[t];
        const txt  = card.querySelector('div');
        if (t === val) {
            card.style.borderColor = s.border;
            card.style.background  = s.bg;
            txt.style.color        = s.color;
        } else {
            card.style.borderColor = 'var(--border)';
            card.style.background  = 'var(--white)';
            txt.style.color        = 'var(--text-muted)';
        }
    });
}
</script>
@endpush
@endsection