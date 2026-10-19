@extends('layouts.app')
@section('title', 'Mi día')

@section('topbar-actions')
    <a href="/asistente/pacientes/crear" class="btn btn-outline btn-sm">Nuevo paciente</a>
    <a href="/asistente/citas/crear" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nueva cita
    </a>
@endsection

@section('content')

{{-- Bienvenida --}}
<div class="welcome-banner" style="margin-bottom:22px">
    <div style="z-index:1">
        <div class="welcome-title">Hola, {{ auth()->user()->nombre }} </div>
        <div class="welcome-sub">{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM') }} · {{ $citasHoy }} {{ $citasHoy == 1 ? 'cita programada' : 'citas programadas' }} hoy con tus doctores</div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid stats-grid-2">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--p100)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--p500)" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
        </div>
        <div class="stat-value">{{ $citasHoy }}</div>
        <div class="stat-label">Citas hoy</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.8">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <div class="stat-value">{{ $doctores->count() }}</div>
        <div class="stat-label">Doctores a mi cargo</div>
    </div>
</div>

<div class="grid-auto">
    {{-- Agenda del día (de todos mis doctores) --}}
    <div>
        <div class="section-head">
            <span class="section-title">Agenda de hoy</span>
            <a href="/asistente/citas" class="btn btn-ghost btn-sm">Ver todas →</a>
        </div>
        <div class="card card-0">
            @if($citasHoyLista->count() > 0)
                <div class="table-wrap">
                    <table class="tabla-tarjetas">
                        <thead>
                            <tr>
                                <th class="celda-cab">Hora</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th>Servicio</th>
                                <th class="celda-estado">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($citasHoyLista as $cita)
                            <tr>
                                <td class="celda-cab" data-label="Hora" style="font-weight:500;color:var(--p600);white-space:nowrap">
                                    {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}
                                </td>
                                <td data-label="Paciente">
                                    <div class="celda-valor">
                                        <div style="font-weight:500">{{ $cita->paciente }}</div>
                                        @if($cita->telefono)
                                            <div style="font-size:11.5px;color:var(--text-muted)">{{ $cita->telefono }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Doctor" style="color:var(--text-muted)">{{ $cita->dentista }}</td>
                                <td data-label="Servicio" style="color:var(--text-muted)">{{ $cita->servicio ?? '—' }}</td>
                                <td class="celda-estado" data-label="Estado">@include('partials.badge-estado', ['estado' => $cita->estado])</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <p>No hay citas programadas para hoy</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Panel derecho --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Doctores a los que auxilio --}}
        <div>
            <div class="section-head">
                <span class="section-title">Mis doctores</span>
            </div>
            <div class="card" style="padding:14px 16px">
                @if($doctores->count() > 0)
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @foreach($doctores as $doc)
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--p100);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:var(--p500);flex-shrink:0">
                                {{ strtoupper(substr($doc->nombre,0,1)) }}{{ strtoupper(substr($doc->apellido,0,1)) }}
                            </div>
                            <div style="min-width:0">
                                <div style="font-size:13px;font-weight:500;color:var(--text)">{{ $doc->nombre_completo }}</div>
                                @if($doc->especialidad)
                                    <div style="font-size:11.5px;color:var(--text-muted)">{{ $doc->especialidad }}</div>
                                @endif
                            </div>
                            @if(!$doc->activo)
                                <span class="badge badge-red" style="margin-left:auto">Inactivo</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <p style="font-size:13px;color:var(--text-light)">Aún no tienes doctores asignados. Contacta a un administrador.</p>
                @endif
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <div>
            <div class="section-head"><span class="section-title">Acciones</span></div>
            <div class="quick-actions">
                <a href="/asistente/citas/crear" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <span>Nueva cita</span>
                </a>
                <a href="/asistente/pacientes" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    <span>Pacientes</span>
                </a>
                <a href="/asistente/doctores" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Mis doctores</span>
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
