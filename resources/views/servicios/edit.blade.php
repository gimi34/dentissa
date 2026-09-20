@extends('layouts.app')
@section('content')
<div style="max-width:800px">
    @if ($errors->any())
        <div class="alerta-errores" data-testid="servicio-errores">
            <strong>Revisa los datos del formulario:</strong>
            <ul style="margin:6px 0 0 18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.servicios.update', $servicio->id) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="grid-2">
                <div class="field-group"><label class="field-label">Nombre</label><input type="text" name="nombre" class="field" value="{{ $servicio->nombre }}" required></div>
                <div class="field-group">
                    <label class="field-label">Categoría</label>
                    <select name="categoria" class="field" required>
                        @foreach($categorias as $cat) <option value="{{ $cat }}" {{ $servicio->categoria == $cat ? 'selected' : '' }}>{{ $cat }}</option> @endforeach
                    </select>
                </div>
            </div>
            <div class="grid-2" style="margin-top:15px">
                <div class="field-group">
                    <label class="field-label">Duración (minutos)</label>
                    <input type="number" name="duracion_min" class="field" value="{{ old('duracion_min', $servicio->duracion_min) }}" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Precio</label>
                    <input type="number" name="precio" class="field" data-testid="servicio-precio"
                           step="0.01" min="0" value="{{ old('precio', $servicio->precio) }}" required>
                </div>
            </div>
            <div class="field-group" style="margin-top:15px">
                <label class="field-label">Color de agenda</label>
                <input type="color" name="color_agenda" class="field field-color" data-testid="servicio-color-agenda"
                       value="{{ old('color_agenda', $servicio->color_agenda ?? '#F4C0D1') }}" required>
            </div>
            <div class="field-group" style="margin-top:15px">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="activo" value="1" {{ $servicio->activo ? 'checked' : '' }}>
                    Activo
                </label>
            </div>
            <div class="field-group" style="margin-top:15px">
                <label class="field-label">Descripción</label>
                <textarea name="descripcion" class="field" rows="3">{{ $servicio->descripcion }}</textarea>
            </div>
        </div>
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
            <a href="{{ route('admin.servicios.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
<style>
    .field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:5px; }
    .field-label { font-size:11px; font-weight:600; text-transform:uppercase; color:var(--text-light); }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
    .field-color { padding:4px; height:38px; cursor:pointer; }
    .alerta-errores { margin-bottom:16px; padding:12px 14px; border:1.5px solid var(--red, #d14343); border-radius:5px; background:var(--red-bg, #fdecec); color:var(--red, #d14343); font-size:13px; }
</style>
@endsection