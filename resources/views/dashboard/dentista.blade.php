@extends('layouts.app')
@section('title', 'Mi día')

@section('topbar-actions')
    <a href="/dentista/citas" class="btn btn-outline btn-sm">Ver agenda completa</a>
    <a href="/dentista/expedientes/nuevo" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nuevo expediente
    </a>
@endsection

@section('content')

{{-- Bienvenida --}}
<div class="welcome-banner" style="margin-bottom:22px">
    <div style="z-index:1">
        <div class="welcome-title">Buen día, Dr. {{ auth()->user()->nombre }} 🦷</div>
        <div class="welcome-sub">{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM') }} · {{ $citasHoy ?? 0 }} {{ ($citasHoy ?? 0) == 1 ? 'cita programada' : 'citas programadas' }} hoy</div>
    </div>
    <div class="welcome-actions">
        <a href="/dentista/citas" class="btn btn-white btn-sm">Ver agenda</a>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--p100)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--p500)" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
        </div>
        <div class="stat-value">{{ $citasHoy ?? 0 }}</div>
        <div class="stat-label">Citas hoy</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.8">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-value">{{ $pacientesAtendidos ?? 0 }}</div>
        <div class="stat-label">Atendidos este mes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.8">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        @php
            $proximaCita = isset($citasHoyLista) ? $citasHoyLista->where('estado', '!=', 'completada')->where('estado', '!=', 'cancelada')->first() : null;
        @endphp
        <div class="stat-value">
            {{ $proximaCita ? \Carbon\Carbon::parse($proximaCita->fecha_hora)->format('H:i') : '—' }}
        </div>
        <div class="stat-label">Próxima cita</div>
    </div>
</div>

{{-- Agenda del día --}}
<div class="grid-auto">
    <div>
        <div class="section-head">
            <span class="section-title">Agenda de hoy</span>
            <span style="font-size:12px;color:var(--text-muted)">{{ now()->format('d/m/Y') }}</span>
        </div>
        <div class="card card-0">
            @if(isset($citasHoyLista) && $citasHoyLista->count() > 0)
                <div class="timeline" style="padding:8px 20px">
                    @foreach($citasHoyLista as $cita)
                    @php
                        $dotColor = match($cita->estado) {
                            'completada'  => 'var(--green)',
                            'en_curso'    => 'var(--p400)',
                            'cancelada'   => 'var(--red)',
                            'no_asistio'  => 'var(--text-light)',
                            'confirmada'  => 'var(--blue)',
                            default       => 'var(--p200)',
                        };
                    @endphp
                    <div class="timeline-item">
                        <div class="timeline-time">{{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}</div>
                        <div class="timeline-dot" style="background:{{ $dotColor }}"></div>
                        <div class="timeline-content">
                            <div class="timeline-title">{{ $cita->paciente }}</div>
                            <div class="timeline-sub">
                                {{ $cita->servicio ?? 'Sin servicio asignado' }}
                                @if($cita->duracion_min) · {{ $cita->duracion_min }} min @endif
                            </div>
                            @if($cita->telefono)
                                <div style="font-size:11.5px;color:var(--text-light);margin-top:2px">{{ $cita->telefono }}</div>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            @include('partials.badge-estado', ['estado' => $cita->estado])
                            <a href="/dentista/citas/{{ $cita->id }}" class="btn btn-ghost btn-sm" style="padding:4px 8px">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <p>No tienes citas programadas para hoy</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Panel derecho --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Próximo paciente --}}
        @if($proximaCita)
        <div>
            <div class="section-head"><span class="section-title">Próximo paciente</span></div>
            <div class="card" style="border-left:3px solid var(--p400)">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--p200);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:15px;color:var(--p800)">
                        {{ strtoupper(substr($proximaCita->paciente, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:500;font-size:14px">{{ $proximaCita->paciente }}</div>
                        <div style="font-size:12px;color:var(--text-muted)">{{ $proximaCita->telefono ?? 'Sin teléfono' }}</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12.5px">
                    <div style="background:var(--p50);border-radius:6px;padding:8px 10px">
                        <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">HORA</div>
                        <div style="font-weight:500;color:var(--p600)">{{ \Carbon\Carbon::parse($proximaCita->fecha_hora)->format('H:i') }}</div>
                    </div>
                    <div style="background:var(--p50);border-radius:6px;padding:8px 10px">
                        <div style="color:var(--text-muted);font-size:11px;margin-bottom:2px">DURACIÓN</div>
                        <div style="font-weight:500;color:var(--p600)">{{ $proximaCita->duracion_min ?? 30 }} min</div>
                    </div>
                </div>
                @if($proximaCita->servicio)
                    <div style="margin-top:10px;font-size:12.5px;color:var(--text-muted)">
                        <span style="font-weight:500;color:var(--text)">Servicio:</span> {{ $proximaCita->servicio }}
                    </div>
                @endif
                <a href="/dentista/citas/{{ $proximaCita->id }}" class="btn btn-outline btn-sm" style="margin-top:14px;width:100%;justify-content:center">
                    Ver detalles
                </a>
            </div>
        </div>
        @endif

        {{-- Accesos rápidos --}}
        <div>
            <div class="section-head"><span class="section-title">Acciones</span></div>
            <div class="quick-actions">
                <a href="/dentista/pacientes" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    <span>Pacientes</span>
                </a>
                <a href="/dentista/expedientes" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <span>Expedientes</span>
                </a>
            </div>
        </div>

        {{-- Notas del día --}}
        <div>
            <div class="section-head"><span class="section-title">Notas del día</span></div>
            <div class="card" style="padding:14px 16px">
                <textarea
                    placeholder="Escribe aquí tus notas personales para hoy..."
                    style="width:100%;resize:none;border:none;outline:none;font-family:'Roboto',sans-serif;font-size:13px;color:var(--text);background:transparent;height:100px;line-height:1.6"
                ></textarea>
            </div>
        </div>

    </div>
</div>

@endsection