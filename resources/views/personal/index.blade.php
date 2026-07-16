@extends('layouts.app')
@section('title', 'Personal')

@section('topbar-actions')
    <a href="{{ route('admin.personal.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Agregar personal
    </a>
@endsection

@section('content')

{{-- Stats ────────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px">
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
        <div class="stat-value">{{ $totales['asistente'] }}</div>
        <div class="stat-label">Asistentes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.8">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
        </div>
        <div class="stat-value">{{ $totales['administrador'] }}</div>
        <div class="stat-label">Administradores</div>
    </div>
</div>

{{-- Filtros ─────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;padding:14px 18px">
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <label class="field-label">Buscar</label>
            <div style="position:relative">
                <svg style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--p300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Nombre, email, especialidad..." class="field" style="padding-left:34px">
            </div>
        </div>
        <div style="min-width:160px">
            <label class="field-label">Tipo</label>
            <select name="tipo" class="field">
                <option value="">Todos</option>
                <option value="dentista"      {{ request('tipo') === 'dentista'      ? 'selected' : '' }}>Dentistas</option>
                <option value="asistente"     {{ request('tipo') === 'asistente'     ? 'selected' : '' }}>Asistentes</option>
                <option value="administrador" {{ request('tipo') === 'administrador' ? 'selected' : '' }}>Administradores</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="{{ route('admin.personal') }}" class="btn btn-ghost">Limpiar</a>
    </form>
</div>

{{-- Grupos de personal ───────────────────────────────────────────────── --}}
@php
    $tipoLabels = ['dentista' => 'Dentistas', 'asistente' => 'Asistentes', 'administrador' => 'Administradores'];
    $tipoColors = ['dentista' => 'var(--p500)', 'asistente' => 'var(--blue)', 'administrador' => 'var(--green)'];
    $tipoBg     = ['dentista' => 'var(--p100)', 'asistente' => 'var(--blue-bg)', 'administrador' => 'var(--green-bg)'];
@endphp

@forelse($grupos as $tipo => $miembros)
    <div style="margin-bottom:28px">
        <div class="section-head">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $tipoColors[$tipo] ?? 'var(--text-muted)' }};display:inline-block"></span>
                <span class="section-title">{{ $tipoLabels[$tipo] ?? ucfirst($tipo) }}</span>
                <span style="font-size:12px;color:var(--text-muted);background:var(--p50);padding:2px 8px;border-radius:20px">{{ $miembros->count() }}</span>
            </div>
            <a href="{{ route('admin.personal.create') }}?tipo={{ $tipo }}" class="btn btn-ghost btn-sm">+ Agregar</a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
            @foreach($miembros as $m)
            <div class="card" style="padding:18px 20px;{{ !$m->activo ? 'opacity:.6' : '' }}">
                <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px">
                    {{-- Avatar --}}
                    <div style="width:46px;height:46px;border-radius:50%;background:{{ $tipoBg[$tipo] ?? 'var(--p100)' }};display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:600;color:{{ $tipoColors[$tipo] ?? 'var(--p600)' }};flex-shrink:0">
                        {{ strtoupper(substr($m->nombre,0,1)) }}{{ strtoupper(substr($m->apellido,0,1)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:500;font-size:14px;color:var(--text)">{{ $m->nombre_completo }}</div>
                        @if($m->especialidad)
                            <div style="font-size:12px;color:var(--text-muted);margin-top:1px">{{ $m->especialidad }}</div>
                        @endif
                        @if(!$m->activo)
                            <span class="badge badge-red" style="margin-top:4px">Inactivo</span>
                        @endif
                    </div>
                    <a href="{{ route('admin.personal.show', $m->id) }}"
                       class="btn btn-ghost btn-sm" style="flex-shrink:0;padding:4px 8px">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </a>
                </div>

                <div style="display:flex;flex-direction:column;gap:5px;font-size:12.5px;color:var(--text-muted)">
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
                    @if($m->anios_experiencia)
                        <div style="display:flex;align-items:center;gap:6px">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2" style="flex-shrink:0">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            {{ $m->anios_experiencia }} años de experiencia
                        </div>
                    @endif
                    @if($m->numero_cedula)
                        <div style="display:flex;align-items:center;gap:6px">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2" style="flex-shrink:0">
                                <rect x="2" y="7" width="20" height="14" rx="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                            Cédula: {{ $m->numero_cedula }}
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <p>No hay personal registrado aún</p>
            <a href="{{ route('admin.personal.create') }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Agregar primer miembro
            </a>
        </div>
    </div>
@endforelse

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'Roboto',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300) }
select.field { cursor:pointer }
</style>
@endsection