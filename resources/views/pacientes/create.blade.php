@extends('layouts.app')
@section('title', isset($paciente) ? 'Editar paciente' : 'Nuevo paciente')

@section('content')
<div style="max-width:800px">

    {{-- Breadcrumb --}}
    @php $base = auth()->user()->rol === 'admin' ? 'admin' : 'asistente'; @endphp
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
        <a href="{{ route("{$base}.pacientes") }}" style="color:var(--text-muted);text-decoration:none">Pacientes</a>
        <span>›</span>
        <span style="color:var(--text)">{{ isset($paciente) ? 'Editar: '.$paciente->nombre.' '.$paciente->apellido : 'Nuevo paciente' }}</span>
    </div>

    <form method="POST"
          action="{{ isset($paciente) ? route('admin.pacientes.update', $paciente->id) : route('admin.pacientes.store') }}">
        @csrf
        @if(isset($paciente)) @method('PUT') @endif

        <div style="display:flex;flex-direction:column;gap:18px">

            {{-- Sección 1: Datos personales --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Datos personales
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Nombre <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="nombre" class="field {{ $errors->has('nombre') ? 'field-error' : '' }}"
                            value="{{ old('nombre', $paciente->nombre ?? '') }}" required placeholder="María">
                        @error('nombre')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label">Apellido <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="apellido" class="field {{ $errors->has('apellido') ? 'field-error' : '' }}"
                            value="{{ old('apellido', $paciente->apellido ?? '') }}" required placeholder="García">
                        @error('apellido')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Correo electrónico <span style="color:var(--p500)">*</span></label>
                        <input type="email" name="email" class="field {{ $errors->has('email') ? 'field-error' : '' }}"
                            value="{{ old('email', $paciente->email ?? '') }}" required placeholder="correo@ejemplo.com">
                        @error('email')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label">Teléfono</label>
                        <input type="tel" name="telefono" class="field"
                            value="{{ old('telefono', $paciente->telefono ?? '') }}" placeholder="55 1234 5678">
                    </div>
                </div>
                @if(!isset($paciente))
                    <div class="field-group">
                        <label class="field-label">Contraseña temporal</label>
                        <input type="text" name="password" class="field"
                            value="{{ old('password') }}" placeholder="Mín. 6 caracteres (dejar vacío para generar automáticamente)">
                        <div class="field-hint">Si se deja vacío se genera una contraseña aleatoria. El paciente deberá cambiarla.</div>
                    </div>
                @endif
                <div class="grid-3">
                    <div class="field-group">
                        <label class="field-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="field"
                            value="{{ old('fecha_nacimiento', isset($paciente->fecha_nacimiento) ? \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('Y-m-d') : '') }}"
                            max="{{ now()->subYear()->format('Y-m-d') }}">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Género</label>
                        <select name="genero" class="field">
                            <option value="">No especificar</option>
                            @foreach(['femenino','masculino','otro'] as $g)
                                <option value="{{ $g }}" {{ old('genero', $paciente->genero ?? '') == $g ? 'selected' : '' }}>
                                    {{ ucfirst($g) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Tipo de sangre</label>
                        <select name="tipo_sangre" class="field">
                            <option value="">Desconocido</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts)
                                <option value="{{ $ts }}" {{ old('tipo_sangre', $paciente->tipo_sangre ?? '') == $ts ? 'selected' : '' }}>
                                    {{ $ts }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Sección 2: Contacto --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Dirección y contacto
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Dirección</label>
                        <input type="text" name="direccion" class="field"
                            value="{{ old('direccion', $paciente->direccion ?? '') }}"
                            placeholder="Calle, número, colonia...">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Ciudad</label>
                        <input type="text" name="ciudad" class="field"
                            value="{{ old('ciudad', $paciente->ciudad ?? '') }}"
                            placeholder="Ciudad de México">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field-group">
                        <label class="field-label">Contacto de emergencia</label>
                        <input type="text" name="contacto_emergencia" class="field"
                            value="{{ old('contacto_emergencia', $paciente->contacto_emergencia ?? '') }}"
                            placeholder="Nombre completo">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Teléfono de emergencia</label>
                        <input type="tel" name="telefono_emergencia" class="field"
                            value="{{ old('telefono_emergencia', $paciente->telefono_emergencia ?? '') }}"
                            placeholder="55 1234 5678">
                    </div>
                </div>
            </div>

            {{-- Sección 3: Info médica --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Información médica
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Alergias conocidas</label>
                        <textarea name="alergias" class="field" rows="3" style="resize:vertical"
                            placeholder="Ej: Penicilina, látex, anestesia...">{{ old('alergias', $paciente->alergias ?? '') }}</textarea>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Enfermedades crónicas</label>
                        <textarea name="enfermedades_cronicas" class="field" rows="3" style="resize:vertical"
                            placeholder="Ej: Diabetes tipo 2, hipertensión...">{{ old('enfermedades_cronicas', $paciente->enfermedades_cronicas ?? '') }}</textarea>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field-group">
                        <label class="field-label">Medicamentos actuales</label>
                        <input type="text" name="medicamentos_actuales" class="field"
                            value="{{ old('medicamentos_actuales', $paciente->medicamentos_actuales ?? '') }}"
                            placeholder="Ej: Metformina 500mg...">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Notas médicas generales</label>
                        <input type="text" name="notas_medicas" class="field"
                            value="{{ old('notas_medicas', $paciente->notas_medicas ?? '') }}"
                            placeholder="Observaciones relevantes...">
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                @if(isset($paciente))
                    <a href="{{ route("{$base}.pacientes.show", $paciente->id) }}" class="btn btn-ghost">Cancelar</a>
                @else
                    <a href="{{ route("{$base}.pacientes") }}" class="btn btn-ghost">Cancelar</a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    {{ isset($paciente) ? 'Guardar cambios' : 'Registrar paciente' }}
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
select.field { cursor:pointer }
</style>
@endsection