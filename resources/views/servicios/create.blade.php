@extends('layouts.app')
@section('content')
<div style="max-width:800px">
    <form method="POST" action="{{ route('admin.servicios.store') }}">
        @csrf
        <div class="card">
            <div class="grid-2">
                <div class="field-group"><label class="field-label">Nombre</label><input type="text" name="nombre" class="field" required></div>
                <div class="field-group">
                    <label class="field-label">Categoría</label>
                    <select name="categoria" class="field" required>
                        @foreach($categorias as $cat) <option value="{{ $cat }}">{{ $cat }}</option> @endforeach
                    </select>
                </div>
            </div>
            <div class="field-group" style="margin-top:15px">
                <label class="field-label">Duración (minutos)</label>
                <input type="number" name="duracion_min" class="field" required>
            </div>
            <div class="field-group" style="margin-top:15px">
                <label class="field-label">Descripción</label>
                <textarea name="descripcion" class="field" rows="3"></textarea>
            </div>
        </div>
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
            <a href="{{ route('admin.servicios.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Registrar</button>
        </div>
    </form>
</div>
<style>
    .field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:5px; }
    .field-label { font-size:11px; font-weight:600; text-transform:uppercase; color:var(--text-light); }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
</style>
@endsection