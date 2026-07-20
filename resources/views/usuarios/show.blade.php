@extends('layouts.app')
@section('title', $usuario->nombre . ' ' . $usuario->apellido)

@section('topbar-actions')
    {{-- Botón principal para editar desde la vista del perfil --}}
    <a href="{{ route('admin.usuarios.edit', $usuario->id) }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:6px;">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        Editar usuario
    </a>
@endsection

@section('content')

{{-- Breadcrumb[cite: 1] --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
    <a href="{{ route('admin.usuarios.index') }}" style="color:var(--text-muted);text-decoration:none">Usuarios</a>
    <span>›</span>
    <span style="color:var(--text)">{{ $usuario->nombre }} {{ $usuario->apellido }}</span>
</div>

{{-- Cuadrícula principal[cite: 1] --}}
<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start">

    {{-- ── Columna izquierda: perfil ────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Tarjeta de identidad[cite: 1] --}}
        <div class="card" style="text-align:center;padding:28px 22px">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--p200);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:600;color:var(--p800);margin:0 auto 14px">
                {{ strtoupper(substr($usuario->nombre,0,1)) }}{{ strtoupper(substr($usuario->apellido,0,1)) }}
            </div>
            <div style="font-family:'Roboto',sans-serif;font-size:20px;font-weight:500;color:var(--text)">
                {{ $usuario->nombre }} {{ $usuario->apellido }}
            </div>
            
            {{-- Badge de Rol[cite: 1] --}}
            <span class="badge badge-pink" style="margin-top:10px">{{ ucfirst($usuario->rol) }}</span>

            <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px;text-align:left">
                @if($usuario->email)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $usuario->email }}</span>
                    </div>
                @endif
                @if($usuario->telefono)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                        </svg>
                        {{ $usuario->telefono }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Columna derecha: Detalles de cuenta ─────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Info de Sistema (Adaptado para Usuarios Generales) --}}
        <div>
            <div class="section-head" style="margin-bottom: 12px;">
                <span class="section-title">Información de la Cuenta</span>
            </div>
            
            <div class="card" style="padding:18px 20px">
                <div style="display:flex;flex-direction:column;gap:12px;font-size:13px">
                    
                    <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:8px;">
                        <span style="font-weight:500;color:var(--text)">Estado del acceso:</span>
                        @if($usuario->activo)
                            <span style="color:var(--green); font-weight:600;">Activo</span>
                        @else
                            <span style="color:var(--red); font-weight:600;">Inactivo</span>
                        @endif
                    </div>

                    <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:8px;">
                        <span style="font-weight:500;color:var(--text)">Rol en el sistema:</span>
                        <span style="color:var(--text-muted)">{{ ucfirst($usuario->rol) }}</span>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:8px;">
                        <span style="font-weight:500;color:var(--text)">Registrado el:</span>
                        <span style="color:var(--text-muted)">{{ $usuario->creado_en ? \Carbon\Carbon::parse($usuario->creado_en)->format('d/m/Y') : 'N/A' }}</span>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection