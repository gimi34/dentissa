@extends('layouts.app')
@section('title', 'Pacientes')

@section('topbar-actions')
    <a href="{{ route(auth()->user()->rol === 'admin' ? 'admin.pacientes.create' : 'asistente.pacientes.create') }}"
       class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nuevo paciente
    </a>
@endsection

@section('content')

{{-- Stats ────────────────────────────────────────────────────────────── --}}
<div class="stats-grid stats-grid-3">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--p100)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--p500)" stroke-width="1.8">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-value">{{ $totalPacientes }}</div>
        <div class="stat-label">Pacientes registrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
        </div>
        <div class="stat-value">{{ $pacientes->total() }}</div>
        <div class="stat-label">Resultados encontrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.8">
                <path d="M17 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <div class="stat-value">{{ $ciudades->count() }}</div>
        <div class="stat-label">Ciudades distintas</div>
    </div>
</div>

{{-- Filtros ─────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div style="flex:1;min-width:220px">
            <label class="field-label">Buscar</label>
            <div style="position:relative">
                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--p300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Nombre, email, teléfono..." class="field" style="padding-left:38px">
            </div>
        </div>

        <div style="min-width:160px">
            <label class="field-label">Ciudad</label>
            <select name="ciudad" class="field">
                <option value="">Todas las ciudades</option>
                @foreach($ciudades as $ciudad)
                    <option value="{{ $ciudad }}" {{ request('ciudad') == $ciudad ? 'selected' : '' }}>
                        {{ $ciudad }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width:180px">
            <label class="field-label">Doctor asignado</label>
            <select name="dentista_id" class="field">
                <option value="">Todos</option>
                @foreach($dentistas as $d)
                    <option value="{{ $d->id }}" {{ request('dentista_id') == $d->id ? 'selected' : '' }}>
                        {{ $d->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="{{ request()->url() }}" class="btn btn-ghost">Limpiar</a>
    </form>
</div>

{{-- Tabla ────────────────────────────────────────────────────────────── --}}
<div class="card card-0">
    @if($pacientes->count() > 0)
        <div class="table-wrap">
            <table class="tabla-tarjetas">
                <thead>
                    <tr>
                        <th class="celda-cab">Paciente</th>
                        <th>Contacto</th>
                        <th class="solo-escritorio">Doctor</th>
                        <th class="solo-escritorio">Ciudad</th>
                        <th class="solo-escritorio">Tipo sangre</th>
                        <th>Alergias</th>
                        <th class="solo-escritorio">Registrado</th>
                        <th class="celda-acciones" style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pacientes as $p)
                    <tr>
                        <td class="celda-cab" data-label="Paciente">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:34px;height:34px;border-radius:50%;background:var(--p200);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:var(--p800);flex-shrink:0">
                                    {{ strtoupper(substr($p->nombre,0,1)) }}{{ strtoupper(substr($p->apellido,0,1)) }}
                                </div>
                                <div class="texto-partible">
                                    <div style="font-weight:500">{{ $p->nombre_completo }}</div>
                                    <div class="solo-escritorio" style="font-size:11.5px;color:var(--text-muted)">{{ $p->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Teléfono" style="color:var(--text-muted);font-size:13px">
                            {{ $p->telefono ?? '—' }}
                        </td>
                        <td class="solo-escritorio" data-label="Doctor" style="font-size:13px">
                            @if($p->dentista_id)
                                {{ trim($p->dentista_nombre) }}
                            @else
                                <span style="color:var(--text-light)">Sin asignar</span>
                            @endif
                        </td>
                        <td class="solo-escritorio" data-label="Ciudad" style="color:var(--text-muted);font-size:13px">
                            {{ $p->ciudad ?? '—' }}
                        </td>
                        <td class="solo-escritorio" data-label="Tipo sangre">
                            @if($p->tipo_sangre)
                                <span class="badge badge-pink">{{ $p->tipo_sangre }}</span>
                            @else
                                <span style="color:var(--text-light)">—</span>
                            @endif
                        </td>
                        <td data-label="Alergias">
                            @if($p->alergias)
                                <span style="font-size:12px;color:var(--red);background:var(--red-bg);padding:2px 8px;border-radius:20px">
                                    ⚠ Sí
                                </span>
                            @else
                                <span style="color:var(--text-light);font-size:12px">Ninguna</span>
                            @endif
                        </td>
                        <td class="solo-escritorio" data-label="Registrado" style="color:var(--text-muted);font-size:12.5px;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($p->creado_en)->format('d/m/Y') }}
                        </td>
                        <td class="celda-acciones" data-label="Acciones">
                            <div style="display:flex;gap:4px;justify-content:flex-end">
                                @php
                                    $base = auth()->user()->rol === 'admin' ? 'admin' : (auth()->user()->rol === 'dentista' ? 'dentista' : 'asistente');
                                @endphp
                                <a href="{{ route("{$base}.pacientes.show", $p->id) }}"
                                   class="btn btn-ghost btn-sm" title="Ver perfil" aria-label="Ver perfil">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <span class="solo-movil">Ver perfil</span>
                                </a>
                                @if(auth()->user()->rol === 'admin' || auth()->user()->rol === 'dentista')
                                    <a href="{{ route("{$base}.pacientes.edit", $p->id) }}"
                                       class="btn btn-outline btn-sm" title="Editar" aria-label="Editar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        <span class="solo-movil">Editar</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($pacientes->hasPages())
            <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                <div style="font-size:12.5px;color:var(--text-muted)">
                    {{ $pacientes->firstItem() }}–{{ $pacientes->lastItem() }} de {{ $pacientes->total() }} pacientes
                </div>
                <div style="display:flex;gap:6px">
                    @if($pacientes->onFirstPage())
                        <span class="btn btn-ghost btn-sm" style="opacity:.4;cursor:default">← Anterior</span>
                    @else
                        <a href="{{ $pacientes->previousPageUrl() }}" class="btn btn-outline btn-sm">← Anterior</a>
                    @endif
                    @if($pacientes->hasMorePages())
                        <a href="{{ $pacientes->nextPageUrl() }}" class="btn btn-outline btn-sm">Siguiente →</a>
                    @else
                        <span class="btn btn-ghost btn-sm" style="opacity:.4;cursor:default">Siguiente →</span>
                    @endif
                </div>
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
            </svg>
            <p>No se encontraron pacientes</p>
            <a href="{{ route('admin.pacientes.create') }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Registrar primer paciente
            </a>
        </div>
    @endif
</div>

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'Roboto',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300) }
select.field { cursor:pointer }
</style>
@endsection