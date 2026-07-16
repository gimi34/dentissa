@extends('layouts.app')
@section('title', 'Control de Usuarios')

@section('topbar-actions')
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Agregar usuario
    </a>
@endsection

@section('content')

{{-- Tarjetas de Estadísticas --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.8">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
        </div>
        <div class="stat-value">{{ $totales['admin'] }}</div>
        <div class="stat-label">Administradores</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:var(--p100)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--p500)" stroke-width="1.8">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
                <path d="M12 11v2M11 13h2"/>
            </svg>
        </div>
        <div class="stat-value">{{ $totales['dentista'] }}</div>
        <div class="stat-label">Dentistas</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.8">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-value">{{ $totales['cliente'] }}</div>
        <div class="stat-label">Pacientes / Clientes</div>
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:20px;padding:14px 18px">
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <label class="field-label">Buscar</label>
            <div style="position:relative">
                <svg style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--p300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Nombre, correo electrónico..." class="field" style="padding-left:34px">
            </div>
        </div>
        <div style="min-width:160px">
            <label class="field-label">Rol</label>
            <select name="tipo" class="field">
                <option value="">Todos</option>
                <option value="admin"    {{ request('tipo') === 'admin'    ? 'selected' : '' }}>Administradores</option>
                <option value="dentista" {{ request('tipo') === 'dentista' ? 'selected' : '' }}>Dentistas</option>
                <option value="cliente"  {{ request('tipo') === 'cliente'  ? 'selected' : '' }}>Pacientes</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-ghost">Limpiar</a>
    </form>
</div>

{{-- Grupos de Usuarios --}}
@php
    // Agrupamos la variable $usuarios que viene del controlador por su rol
    $grupos = $usuarios->groupBy('rol');
    
    // Configuramos colores y etiquetas según los roles de tu tabla usuarios
    $tipoLabels = ['admin' => 'Administradores', 'dentista' => 'Dentistas', 'cliente' => 'Pacientes / Clientes'];
    $tipoColors = ['admin' => 'var(--green)', 'dentista' => 'var(--p500)', 'cliente' => 'var(--blue)'];
    $tipoBg     = ['admin' => 'var(--green-bg)', 'dentista' => 'var(--p100)', 'cliente' => 'var(--blue-bg)'];
@endphp

@forelse($grupos as $tipo => $miembros)
    <div style="margin-bottom:28px">
        <div class="section-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $tipoColors[$tipo] ?? 'var(--text-muted)' }};display:inline-block"></span>
                <span class="section-title" style="font-weight:600; font-size:16px;">{{ $tipoLabels[$tipo] ?? ucfirst($tipo) }}</span>
                <span style="font-size:12px;color:var(--text-muted);background:var(--p50);padding:2px 8px;border-radius:20px">{{ $miembros->count() }}</span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
            @foreach($miembros as $m)
            <div class="card" style="padding:18px 20px;{{ !$m->activo ? 'opacity:.6' : '' }}">
                
                {{-- Sección Superior: Avatar, Info principal y Ojito --}}
                <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px">
                    {{-- Avatar con iniciales --}}
                    <div style="width:46px;height:46px;border-radius:50%;background:{{ $tipoBg[$tipo] ?? 'var(--p100)' }};display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:600;color:{{ $tipoColors[$tipo] ?? 'var(--p600)' }};flex-shrink:0">
                        {{ strtoupper(substr($m->nombre,0,1)) }}{{ strtoupper(substr($m->apellido,0,1)) }}
                    </div>
                    
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:500;font-size:14px;color:var(--text)">{{ $m->nombre }} {{ $m->apellido }}</div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:1px">Rol: {{ ucfirst($m->rol) }}</div>
                        @if(!$m->activo)
                            <span class="badge badge-red" style="margin-top:4px">Inactivo</span>
                        @endif
                    </div>

                    {{-- Botón de ver perfil (Ojito) --}}
                    <a href="#" class="btn btn-ghost btn-sm" style="flex-shrink:0;padding:4px 8px" title="Ver perfil">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </a>
                </div>

                {{-- Sección Inferior: Contacto a la izquierda, Botones a la derecha --}}
                <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px;">
                    
                    {{-- Info de contacto (Le agregamos flex:1 y min-width:0 para que no empuje los botones) --}}
                    <div style="display:flex;flex-direction:column;gap:5px;font-size:12.5px;color:var(--text-muted); flex:1; min-width:0;">
                        @if($m->email)
                            <div style="display:flex;align-items:center;gap:6px;overflow:hidden">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2" style="flex-shrink:0">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $m->email }}</span>
                            </div>
                        @endif
                        
                        @if($m->telefono)
                            <div style="display:flex;align-items:center;gap:6px">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2" style="flex-shrink:0">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                                </svg>
                                {{ $m->telefono }}
                            </div>
                        @endif
                    </div>

                    {{-- Botones de Editar y Eliminar (Le agregamos flex-shrink:0 para que no se aplasten) --}}
                    <div style="display:flex; gap:6px; flex-shrink:0;">
                        <a href="{{ route('admin.usuarios.edit', $m->id) }}" class="btn btn-outline btn-sm" style="padding:5px 8px; color:var(--text-muted); border-color:var(--border);" title="Editar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>
                        
                        <button type="button" onclick="abrirModalEliminar('{{ $m->id }}', '{{ $m->nombre }} {{ $m->apellido }}')" class="btn btn-outline btn-sm" style="padding:5px 8px; color:var(--red); border-color:#fbc4c4; background:var(--red-bg);" title="Eliminar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                    </div>

                </div>

            </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="card">
        <div class="empty-state" style="text-align:center; padding: 40px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="width:40px;height:40px;margin-bottom:10px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <p style="color:var(--text-muted); font-size:15px;">No hay usuarios registrados aún</p>
            <a href="#" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Agregar primer usuario
            </a>
        </div>
    </div>
@endforelse

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300) }
select.field { cursor:pointer }
</style>

{{-- Modal de Eliminación (Fondo oscuro) --}}
<div id="modalEliminar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
    
    {{-- Tarjeta Blanca del Modal --}}
    <div class="card" style="width:100%; max-width:400px; padding:24px; animation: modalIn 0.2s ease-out;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="background:var(--red-bg); color:var(--red); padding:10px; border-radius:50%;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <h3 style="font-size:18px; font-weight:600; margin:0; color:var(--text);">Eliminar Usuario</h3>
        </div>
        
        <p style="font-size:14px; color:var(--text-muted); margin-bottom:24px; line-height:1.5;">
            ¿Estás seguro de que deseas eliminar a <strong id="nombreUsuarioEliminar" style="color:var(--text);"></strong>? Esta acción no se puede deshacer.
        </p>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="cerrarModalEliminar()" class="btn btn-ghost">Cancelar</button>
            <form id="formEliminar" method="POST" action="">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary" style="background:var(--red); border-color:var(--red);">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalEliminar(id, nombre) {
        // 1. Ponemos el nombre del usuario en el texto
        document.getElementById('nombreUsuarioEliminar').innerText = nombre;
        
        // 2. Preparamos la URL a donde se mandará el formulario
        let urlBase = "{{ route('admin.usuarios.destroy', 'ID_PLACEHOLDER') }}";
        document.getElementById('formEliminar').action = urlBase.replace('ID_PLACEHOLDER', id);
        
        // 3. Mostramos el modal
        document.getElementById('modalEliminar').style.display = 'flex';
    }

    function cerrarModalEliminar() {
        document.getElementById('modalEliminar').style.display = 'none';
    }
</script>

<style>
    /* Efecto para que el modal entre suavecito */
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>
@endsection