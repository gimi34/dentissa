@extends('layouts.app')
@section('title', 'Editar servicio')

@section('content')
<div style="max-width:800px">

    {{-- Navegación superior --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
        <a href="{{ route('admin.servicios.index') }}" style="color:var(--text-muted);text-decoration:none">Servicios</a>
        <span>›</span>
        <span style="color:var(--text)">Editar: {{ $servicio->nombre }}</span>
    </div>

    {{-- El action ahora extrae obligatoriamente el ID --}}
    <form method="POST" action="{{ route('admin.servicios.update', $servicio->id) }}">
        @csrf
        @method('PUT')

        <div style="display:flex;flex-direction:column;gap:18px">

            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Datos del servicio
                </div>
                
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label">Nombre del servicio <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="nombre" class="field" value="{{ old('nombre', $servicio->nombre) }}" required>
                    </div>
                    
                    <div class="field-group">
                        <label class="field-label">Precio ($) <span style="color:var(--p500)">*</span></label>
                        <input type="number" step="0.01" name="precio" class="field" value="{{ old('precio', $servicio->precio) }}" required>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Descripción</label>
                    <textarea name="descripcion" class="field" rows="3" style="resize:vertical">{{ old('descripcion', $servicio->descripcion) }}</textarea>
                </div>
            </div>

            {{-- Botones de acción --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <a href="{{ route('admin.servicios.index') }}" class="btn btn-ghost">Cancelar</a>
                
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Guardar cambios
                </button>
            </div>

        </div>
    </form>
</div>

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300);box-shadow:0 0 0 3px rgba(212,83,126,.07) }
.field-group { margin-bottom:0 }
textarea.field { line-height:1.6 }
</style>
@endsection