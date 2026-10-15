@extends('layouts.app')
@section('title', 'Servicios')

@section('topbar-actions')
    <a href="{{ route('admin.servicios.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nuevo servicio
    </a>
@endsection

@section('content')

{{-- ── Tarjetas de Estadísticas ────────────────────────────────── --}}
<div class="stats-grid stats-grid-2" style="max-width:600px">
    <div class="stat-card">
        <div class="stat-value">{{ $servicios->count() }}</div>
        <div class="stat-label">Servicios registrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $servicios->count() }}</div>
        <div class="stat-label">Resultados encontrados</div>
    </div>
</div>

{{-- ── Buscador ────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;padding:16px 20px">
    <form action="{{ route('admin.servicios.index') }}" method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div style="flex:1;min-width:220px">
            <label class="field-label" style="display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px">Buscar</label>
            <div style="position:relative">
                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--p300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Nombre, descripción..." class="form-control" style="display:block;width:100%;padding:9px 13px;padding-left:38px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text);background:var(--white);outline:none;">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="{{ route('admin.servicios.index') }}" class="btn btn-ghost">Limpiar</a>
    </form>
</div>
{{-- ── Tabla de Servicios ──────────────────────────────────────── --}}
<div class="card card-0">
    @if($servicios->count() > 0)
        <div class="table-wrap">
            <table class="tabla-tarjetas">
                <thead>
                    <tr>
                        <th class="celda-cab" style="width: 25%; text-align:center;">Nombre</th>
                        <th class="solo-escritorio" style="width: 35%; text-align:center;">Descripción</th>
                        <th class="solo-escritorio" style="width: 15%; text-align:center;">Categoría</th>
                        <th style="width: 10%; text-align:center;">Duración</th>
                        <th style="width: 5%; text-align:center;">Estado</th>
                        <th class="celda-acciones" style="width: 10%; text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicios as $servicio)
                    <tr>
                        <td class="celda-cab" data-label="Nombre" style="text-align:center;">
                            <div style="font-weight:500">{{ $servicio->nombre }}</div>
                        </td>
                        <td class="solo-escritorio" data-label="Descripción" style="color:var(--text-muted); font-size:13px; text-align:center;">
                            {{ $servicio->descripcion }}
                        </td>
                        <td class="solo-escritorio" data-label="Categoría" style="text-align:center; font-weight:500;">
                            {{ $servicio->categoria }}
                        </td>
                        <td data-label="Duración" style="text-align:center; color:var(--text-muted); font-size:13px;">
                            {{ $servicio->duracion_min }} min
                        </td>
                        <td data-label="Estado" style="text-align:center;">
                            @if($servicio->activo)
                                <span style="color:var(--green); font-size:12px; font-weight:bold;">Activo</span>
                            @else
                                <span style="color:var(--red); font-size:12px; font-weight:bold;">Inactivo</span>
                            @endif
                        </td>
                        <td class="celda-acciones" data-label="Acciones">
                            <div style="display:flex;gap:4px;justify-content:flex-end">
                                <a href="{{ route('admin.servicios.edit', $servicio->id) }}"
                                   class="btn btn-outline btn-sm" title="Editar" aria-label="Editar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    <span class="solo-movil">Editar</span>
                                </a>
                                <button type="button" class="btn btn-ghost btn-sm" title="Eliminar" aria-label="Eliminar" style="color:var(--red);"
                                        onclick="abrirModalEliminar('{{ route('admin.servicios.destroy', $servicio->id) }}', '{{ addslashes($servicio->nombre) }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                    <span class="solo-movil">Eliminar</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
    <!-- Resto del empty-state igual... -->
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <p>No se encontraron servicios registrados.</p>
            <a href="{{ route('admin.servicios.create') }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Registrar primer servicio
            </a>
        </div>
    @endif
</div>

{{-- ── Modal de Confirmación para Eliminar ─────────────────────── --}}
<div id="modalEliminar" class="modal-overlay">
    <div class="modal-box card">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <div style="width:36px; height:36px; border-radius:50%; background:var(--red-bg); display:flex; align-items:center; justify-content:center; color:var(--red);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                    <line x1="12" y1="5" x2="12" y2="15"></line>
                    <line x1="12" y1="19" x2="12.01" y2="19"></line>
                </svg>
            </div>
            <div style="font-size:16px; font-weight:600; color:var(--text);">Confirmar eliminación</div>
        </div>
        <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:22px; line-height:1.5;">
            ¿Estás seguro de que deseas eliminar el servicio <strong id="modalServicioNombre" style="color:var(--text);"></strong>? Esta acción es permanente y no se podrá deshacer.
        </p>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" class="btn btn-outline" onclick="cerrarModalEliminar()">Cancelar</button>
            <form id="formEliminar" method="POST" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary" style="background:var(--red); border:none;">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Estilos para el Modal */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 9999;
    display: none; /* Oculto por defecto */
    align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.2s ease;
}
.modal-overlay.show { display: flex; opacity: 1; }
.modal-box {
    width: 100%; max-width: 420px;
    transform: translateY(-20px); transition: transform 0.2s ease;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.modal-overlay.show .modal-box { transform: translateY(0); }
</style>
@endpush

@push('scripts')
<script>
// Función para abrir el modal
function abrirModalEliminar(url, nombreServicio) {
    // 1. Asignamos la ruta al formulario dentro del modal
    document.getElementById('formEliminar').action = url;
    // 2. Ponemos el nombre del servicio en el texto para que el usuario sepa qué va a borrar
    document.getElementById('modalServicioNombre').textContent = '"' + nombreServicio + '"';
    
    // 3. Mostramos el modal con animación
    const modal = document.getElementById('modalEliminar');
    modal.style.display = 'flex';
    setTimeout(() => { modal.classList.add('show'); }, 10);
}

// Función para cerrar el modal
function cerrarModalEliminar() {
    const modal = document.getElementById('modalEliminar');
    modal.classList.remove('show');
    // Esperamos a que termine la animación para ocultarlo del todo
    setTimeout(() => { modal.style.display = 'none'; }, 200);
}
</script>
@endpush