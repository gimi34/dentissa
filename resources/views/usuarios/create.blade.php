@extends('layouts.app')
@section('title', isset($usuario) ? 'Editar Usuario' : 'Nuevo Usuario')

@section('content')
<div style="max-width:800px">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
        <a href="{{ route('admin.usuarios.index') }}" style="color:var(--text-muted);text-decoration:none">Usuarios</a>
        <span>›</span>
        <span style="color:var(--text)">{{ isset($usuario) ? 'Editar: '.$usuario->nombre.' '.$usuario->apellido : 'Nuevo Usuario' }}</span>
    </div>

    <form method="POST"
          action="{{ isset($usuario) ? route('admin.usuarios.update', $usuario->id) : route('admin.usuarios.store') }}">
        @csrf
        @if(isset($usuario)) @method('PUT') @endif

        <div style="display:flex;flex-direction:column;gap:18px">

            {{-- Sección 1: Datos Generales --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Datos del usuario
                </div>
                
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Nombre(s) <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="nombre" class="field" value="{{ old('nombre', $usuario->nombre ?? '') }}" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Apellidos <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="apellido" class="field" value="{{ old('apellido', $usuario->apellido ?? '') }}" required>
                    </div>
                </div>

                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Correo electrónico <span style="color:var(--p500)">*</span></label>
                        <input type="email" name="email" class="field" value="{{ old('email', $usuario->email ?? '') }}" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Teléfono</label>
                        <input type="text" name="telefono" class="field" value="{{ old('telefono', $usuario->telefono ?? '') }}">
                    </div>
                </div>

                @if(!isset($usuario))
                    <div class="field-group">
                        <label class="field-label">Contraseña temporal</label>
                        <input type="text" name="password" class="field" placeholder="Dejar vacío para generar automáticamente">
                        <div class="field-hint">Si se deja vacío se genera una contraseña aleatoria.</div>
                    </div>
                @endif
            </div>

            {{-- Sección 2: Rol y Estado --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Rol y acceso al sistema
                </div>
            

                <div class="grid-2" style="margin-bottom:14px">
                    
                    {{-- Y si si no es paciente se muestra el selector --}}
                    @if(!isset($usuario) || $usuario->rol !== 'cliente')
                        <div class="field-group">
                            <label class="field-label">Rol Asignado</label>
                            <select name="rol" class="field">
                                <option value="admin" {{ old('rol', $usuario->rol ?? '') === 'admin' ? 'selected' : '' }}>Administrador</option>
                                <option value="dentista" {{ old('rol', $usuario->rol ?? '') === 'dentista' ? 'selected' : '' }}>Dentista</option>
                                <option value="cliente" {{ old('rol', $usuario->rol ?? '') === 'cliente' ? 'selected' : '' }}>Paciente / Cliente</option>
                            </select>
                        </div>
                    @endif

                    {{-- Y si si es paciente no se muestra el selector --}}
                @if(isset($usuario) && $usuario->rol === 'cliente')
                    <input type="hidden" name="rol" value="cliente">
                @endif
                    
                    {{-- Estado de la cuenta --}}
                    <div class="field-group">
                        <label class="field-label">Estado de la cuenta</label>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-top:8px;">
                            <input type="checkbox" name="activo" value="1" 
                                {{ old('activo', $usuario->activo ?? 1) ? 'checked' : '' }} 
                                style="width:16px; height:16px; accent-color:var(--p500);">
                            <span style="font-size:13.5px; color:var(--text);">Usuario activo en el sistema</span>
                        </label>
                    </div>

                </div>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    {{ isset($usuario) ? 'Guardar cambios' : 'Registrar usuario' }}
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