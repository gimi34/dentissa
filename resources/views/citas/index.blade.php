@extends('layouts.app')
@section('title', 'Citas')

@php
    $rp = match(auth()->user()->rol) {
        'dentista'  => 'dentista',
        'asistente' => 'asistente',
        default     => 'admin',
    };
@endphp

@section('topbar-actions')
    <a href="{{ route("$rp.citas.create") }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nueva cita
        </a>
@endsection

@section('content')

{{-- Resumen del día ──────────────────────────────────────────────────────── --}}
<div class="stats-grid">
    @php
        $resumen = $resumenHoy ?? (object)['total'=>0,'completadas'=>0,'agendadas'=>0,'canceladas'=>0];
    @endphp
    <div class="stat-card">
        <div class="stat-value">{{ $resumen->total }}</div>
        <div class="stat-label">Total</div>
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

{{-- Filtros (spec 034, D6): buscador, atajos con «Filtros» y el panel que filtros.js pliega en el móvil.
     En el escritorio se ven como antes: el panel no es una caja y los atajos van al final. --}}
@php
    $aplicados = collect([
        request()->filled('fecha_desde') && request('fecha_desde') !== now()->format('Y-m-d'),
        request()->filled('fecha_hasta'),
        request()->filled('dentista_id'),
        request()->filled('estado'),
    ])->filter()->count();
@endphp
<div class="card filtro-tarjeta">
    <form method="GET" action="{{ route("$rp.citas") }}" class="filtro-citas">

        <div class="filtro-buscar">
            <label class="field-label" for="filtro-buscar">Buscar paciente / dentista</label>
            <input type="text" id="filtro-buscar" name="buscar" value="{{ request('buscar') }}"
                placeholder="Nombre, teléfono..." class="field">
        </div>

        {{-- Acceso rápido a semanas y botón del panel --}}
        <div class="filtro-atajos">
            <a href="{{ route("$rp.citas", ['fecha_desde'=>now()->startOfWeek()->format('Y-m-d'),'fecha_hasta'=>now()->endOfWeek()->format('Y-m-d')]) }}"
               class="btn btn-outline btn-sm">Esta semana</a>
            <a href="{{ route("$rp.citas", ['fecha_desde'=>now()->format('Y-m-d'),'fecha_hasta'=>now()->format('Y-m-d')]) }}"
               class="btn {{ !request()->has('fecha_desde') ? 'btn-primary' : 'btn-outline' }} btn-sm">Hoy</a>
            <button type="button" class="btn btn-outline filtro-btn" aria-expanded="false" aria-controls="filtro-panel"
                @if($aplicados) aria-label="{{ $aplicados === 1 ? 'Filtros, 1 aplicado' : "Filtros, {$aplicados} aplicados" }}" @endif>Filtros @if($aplicados)<span class="filtro-contador">{{ $aplicados }}</span>@endif</button>
        </div>

        <div id="filtro-panel" class="filtro-panel">
            <div class="filtro-fecha">
                <label class="field-label" for="filtro-desde">Desde</label>
                <input type="date" id="filtro-desde" name="fecha_desde" value="{{ request('fecha_desde', now()->format('Y-m-d')) }}" class="field">
            </div>

            <div class="filtro-fecha">
                <label class="field-label" for="filtro-hasta">Hasta</label>
                <input type="date" id="filtro-hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="field">
            </div>

            @if($rp !== 'dentista')
            <div class="filtro-dentista">
                <label class="field-label" for="filtro-dentista">Dentista</label>
                <select id="filtro-dentista" name="dentista_id" class="field">
                    <option value="">Todos</option>
                    @foreach($dentistas as $d)
                        <option value="{{ $d->id }}" {{ request('dentista_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="filtro-estado">
                <label class="field-label" for="filtro-estado">Estado</label>
                <select id="filtro-estado" name="estado" class="field">
                    <option value="">Todos</option>
                    @foreach(['agendada','confirmada','en_curso','completada','cancelada','no_asistio'] as $e)
                        <option value="{{ $e }}" {{ request('estado') == $e ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$e)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:0">Filtrar</button>
            <a href="{{ route("$rp.citas") }}" class="btn btn-ghost">Limpiar</a>
        </div>
    </form>
</div>

{{-- Tabla de citas ─────────────────────────────────────────────────────── --}}
<div class="card card-0">
    @if($citas->count() > 0)
        <div class="table-wrap">
            <table class="tabla-tarjetas">
                <thead>
                    <tr>
                        <th class="celda-cab">Hora / Fecha</th>
                        <th>Paciente</th>
                        <th>Dentista</th>
                        <th>Servicio</th>
                        <th>Duración</th>
                        <th class="celda-estado">Estado</th>
                        <th class="celda-acciones" style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($citas as $cita)
                    @php
                        $esHoy   = \Carbon\Carbon::parse($cita->fecha_hora)->isToday();
                        $esFutura = \Carbon\Carbon::parse($cita->fecha_hora)->isFuture();
                    @endphp
                    <tr @class(['fila-hoy' => $esHoy])>
                        <td class="celda-cab" data-label="Hora / Fecha">
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

                        <td data-label="Paciente">
                            <div class="celda-valor">
                                <div style="font-weight:500">{{ $cita->paciente }}</div>
                                @if($cita->tel_paciente)
                                    <div style="font-size:11.5px;color:var(--text-muted)">{{ $cita->tel_paciente }}</div>
                                @endif
                            </div>
                        </td>

                        <td data-label="Dentista" style="color:var(--text-muted);font-size:13px">{{ $cita->dentista }}</td>

                        <td data-label="Servicio">
                            @if($cita->servicio)
                                <div style="font-size:13px">{{ $cita->servicio }}</div>
                            @else
                                <span style="color:var(--text-light)">—</span>
                            @endif
                        </td>

                        <td data-label="Duración" style="color:var(--text-muted);font-size:13px">
                            {{ $cita->duracion_min ?? $cita->duracion_servicio ?? 30 }} min
                        </td>

                        <td class="celda-estado" data-label="Estado">@include('partials.badge-estado', ['estado' => $cita->estado])</td>

                        <td class="celda-acciones" data-label="Acciones">
                            <div style="display:flex;justify-content:flex-end">
                                <a href="{{ route("$rp.citas.show", $cita->id) }}"
                                   class="btn btn-ghost btn-sm" title="Ver detalle" aria-label="Ver detalle">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <span class="solo-movil">Ver detalle</span>
                                </a>
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
            <a href="{{ route("$rp.citas.create") }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Agendar primera cita
            </a>
        </div>
    @endif
</div>

@push('styles')
<link href="{{ asset('css/citas.css') }}?v={{ filemtime(public_path('css/citas.css')) }}" rel="stylesheet">
{{-- Sin defer: marca <html> con filtros-js antes de pintar, sin salto de maquetación (spec 034, D6). --}}
<script src="{{ asset('js/filtros.js') }}?v={{ filemtime(public_path('js/filtros.js')) }}"></script>
@endpush

@endsection