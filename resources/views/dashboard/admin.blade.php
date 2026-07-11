@extends('layouts.app')
@section('title', 'Dashboard')

@section('topbar-actions')
    <a href="/admin/citas/crear" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nueva cita
    </a>
@endsection

@section('content')

{{-- ── Stats ───────────────────────────────────────────────── --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--p100)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--p500)" stroke-width="1.8">
                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
        </div>
        <div class="stat-value">{{ $citasHoy ?? 0 }}</div>
        <div class="stat-label">Citas hoy</div>
        <span class="stat-badge badge-pink">
            {{ now()->format('d M') }}
        </span>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.8">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-value">{{ $totalPacientes ?? 0 }}</div>
        <div class="stat-label">Pacientes activos</div>
        <span class="stat-badge badge-blue">Registrados</span>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.8">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-value">${{ number_format($ingresosEsteMes ?? 0, 0) }}</div>
        <div class="stat-label">Ingresos este mes</div>
        <span class="stat-badge badge-green">Citas completadas</span>
    </div>

</div>

{{-- ── Main grid ───────────────────────────────────────────── --}}
<div class="grid-auto">

    {{-- Citas de hoy --}}
    <div>
        <div class="section-head">
            <span class="section-title">Citas de hoy</span>
            <a href="/admin/citas" class="btn btn-ghost btn-sm">Ver todas →</a>
        </div>
        <div class="card card-0">
            @if(isset($citasHoyLista) && $citasHoyLista->count() > 0)
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Dentista</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($citasHoyLista as $cita)
                            <tr>
                                <td style="font-weight:500;color:var(--p600);white-space:nowrap">
                                    {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}
                                </td>
                                <td>
                                    <div style="font-weight:500">{{ $cita->paciente }}</div>
                                    @if($cita->telefono)
                                        <div style="font-size:11.5px;color:var(--text-muted)">{{ $cita->telefono }}</div>
                                    @endif
                                </td>
                                <td style="color:var(--text-muted)">{{ $cita->dentista }}</td>
                                <td style="color:var(--text-muted)">{{ $cita->servicio ?? '—' }}</td>
                                <td>@include('partials.badge-estado', ['estado' => $cita->estado])</td>
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
                    <a href="/admin/citas/crear" class="btn btn-outline btn-sm" style="margin-top:12px">Agendar cita</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Sidebar derecho --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Acceso rápido --}}
        <div>
            <div class="section-head">
                <span class="section-title">Acceso rápido</span>
            </div>
            <div class="quick-actions">
                <a href="/admin/pacientes/crear" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/>
                    </svg>
                    <span>Nuevo paciente</span>
                </a>
                <a href="/admin/citas/crear" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                        <line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/>
                    </svg>
                    <span>Nueva cita</span>
                </a>
                <a href="/admin/reportes" class="quick-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M18 20V10M12 20V4M6 20v-6"/>
                    </svg>
                    <span>Reportes</span>
                </a>
            </div>
        </div>

</div>

@endsection

@push('scripts')
@endpush