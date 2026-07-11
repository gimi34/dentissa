@extends('layouts.app')
@section('title', 'Citas')

@section('topbar-actions')
    <a href="{{ route('admin.citas.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nueva cita
    </a>
@endsection

@section('content')

{{-- Resumen del día ──────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px">
    @php
        $resumen = $resumenHoy ?? (object)['total'=>0,'completadas'=>0,'agendadas'=>0,'canceladas'=>0];
    @endphp
    <div class="stat-card">
        <div class="stat-value">{{ $resumen->total }}</div>
        <div class="stat-label">Total hoy</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--p500)">{{ $resumen->agendadas }}</div>
        <div class="stat-label">Agendadas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--green)">{{ $resumen->completadas }}</div>
        <div class="stat-label">Completadas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--red)">{{ $resumen->canceladas }}</div>
        <div class="stat-label">Canceladas</div>
    </div>
</div>

{{-- Filtros ────────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" action="{{ route('admin.citas') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">

        <div style="flex:1;min-width:180px">
            <label class="field-label">Buscar paciente / dentista</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                placeholder="Nombre, teléfono..." class="field">
        </div>

        <div style="min-width:130px">
            <label class="field-label">Desde</label>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde', now()->format('Y-m-d')) }}" class="field">
        </div>

        <div style="min-width:130px">
            <label class="field-label">Hasta</label>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="field">
        </div>

        <div style="min-width:150px">
            <label class="field-label">Dentista</label>
            <select name="dentista_id" class="field">
                <option value="">Todos</option>
                @foreach($dentistas as $d)
                    <option value="{{ $d->id }}" {{ request('dentista_id') == $d->id ? 'selected' : '' }}>
                        {{ $d->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width:140px">
            <label class="field-label">Estado</label>
            <select name="estado" class="field">
                <option value="">Todos</option>
                @foreach(['agendada','confirmada','en_curso','completada','cancelada','no_asistio'] as $e)
                    <option value="{{ $e }}" {{ request('estado') == $e ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_',' ',$e)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:0">Filtrar</button>
        <a href="{{ route('admin.citas') }}" class="btn btn-ghost">Limpiar</a>

        {{-- Acceso rápido a semanas --}}
        <div style="margin-left:auto;display:flex;gap:6px">
            <a href="{{ route('admin.citas', ['fecha_desde'=>now()->startOfWeek()->format('Y-m-d'),'fecha_hasta'=>now()->endOfWeek()->format('Y-m-d')]) }}"
               class="btn btn-outline btn-sm">Esta semana</a>
            <a href="{{ route('admin.citas', ['fecha_desde'=>now()->format('Y-m-d'),'fecha_hasta'=>now()->format('Y-m-d')]) }}"
               class="btn {{ !request()->has('fecha_desde') ? 'btn-primary' : 'btn-outline' }} btn-sm">Hoy</a>
        </div>
    </form>
</div>

{{-- Tabla de citas ─────────────────────────────────────────────────────── --}}
<div class="card card-0">
    @if($citas->count() > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Hora / Fecha</th>
                        <th>Paciente</th>
                        <th>Dentista</th>
                        <th>Servicio</th>
                        <th>Duración</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($citas as $cita)
                    @php
                        $esHoy   = \Carbon\Carbon::parse($cita->fecha_hora)->isToday();
                        $esFutura = \Carbon\Carbon::parse($cita->fecha_hora)->isFuture();
                    @endphp
                    <tr style="{{ $esHoy ? 'background:var(--p50)' : '' }}">
                        <td>
                            <div style="font-weight:600;color:var(--p600);font-size:14px">
                                {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}
                            </div>
                            <div style="font-size:11.5px;color:var(--text-muted)">
                                {{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('ddd D MMM') }}
                                @if($esHoy)
                                    <span style="color:var(--p500);font-weight:500"> · Hoy</span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <div style="font-weight:500">{{ $cita->paciente }}</div>
                            @if($cita->tel_paciente)
                                <div style="font-size:11.5px;color:var(--text-muted)">{{ $cita->tel_paciente }}</div>
                            @endif
                        </td>

                        <td style="color:var(--text-muted);font-size:13px">{{ $cita->dentista }}</td>

                        <td>
                            @if($cita->servicio)
                                <div style="font-size:13px">{{ $cita->servicio }}</div>
                                @if($cita->precio)
                                    <div style="font-size:11.5px;color:var(--text-muted)">${{ number_format($cita->precio, 0) }}</div>
                                @endif
                            @else
                                <span style="color:var(--text-light)">—</span>
                            @endif
                        </td>

                        <td style="color:var(--text-muted);font-size:13px">
                            {{ $cita->duracion_min ?? $cita->duracion_servicio ?? 30 }} min
                        </td>

                        <td>@include('partials.badge-estado', ['estado' => $cita->estado])</td>

                        <td>
                            <div style="display:flex;gap:4px;justify-content:flex-end">
                                <a href="{{ route('admin.citas.show', $cita->id) }}"
                                   class="btn btn-ghost btn-sm" title="Ver detalle">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                @if($cita->estado !== 'completada' && $cita->estado !== 'cancelada')
                                    <a href="{{ route('admin.citas.show', $cita->id) }}#estado"
                                       class="btn btn-outline btn-sm" title="Cambiar estado">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9 11 12 14 22 4"/>
                                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                        </svg>
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
        @if($citas->hasPages())
            <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                <div style="font-size:12.5px;color:var(--text-muted)">
                    {{ $citas->firstItem() }}–{{ $citas->lastItem() }} de {{ $citas->total() }} citas
                </div>
                <div style="display:flex;gap:6px">
                    @if($citas->onFirstPage())
                        <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">← Anterior</span>
                    @else
                        <a href="{{ $citas->previousPageUrl() }}" class="btn btn-outline btn-sm">← Anterior</a>
                    @endif
                    @if($citas->hasMorePages())
                        <a href="{{ $citas->nextPageUrl() }}" class="btn btn-outline btn-sm">Siguiente →</a>
                    @else
                        <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Siguiente →</span>
                    @endif
                </div>
            </div>
        @endif

    @else
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
            <p>No se encontraron citas con los filtros aplicados</p>
            <a href="{{ route('admin.citas.create') }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Agendar primera cita
            </a>
        </div>
    @endif
</div>

{{-- CSS extra para los campos del form de filtros --}}
<style>
.field-label { display:block; font-size:11px; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--text-light); margin-bottom:5px; }
.field {
    display:block; width:100%;
    padding:8px 12px;
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    font-size:13px;
    font-family:'DM Sans',sans-serif;
    color:var(--text);
    background:var(--white);
    outline:none;
    transition:border-color .15s;
}
.field:focus { border-color:var(--p300); }
select.field { cursor:pointer; }
</style>

@endsection