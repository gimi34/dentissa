@extends('layouts.app')
@section('title', 'Mis citas')

@section('topbar-actions')
    <a href="{{ route('cliente.agendar') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Agendar cita
    </a>
@endsection

@section('content')

{{-- Pestañas ──────────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:6px;margin-bottom:20px;border-bottom:1px solid var(--border)">
    <a href="{{ route('cliente.citas') }}"
       style="padding:10px 4px;margin-right:20px;font-size:13.5px;font-weight:500;text-decoration:none;color:{{ !$pasadas ? 'var(--p500)' : 'var(--text-muted)' }};border-bottom:2px solid {{ !$pasadas ? 'var(--p400)' : 'transparent' }}">
        Próximas
    </a>
    <a href="{{ route('cliente.citas', ['filtro' => 'pasadas']) }}"
       style="padding:10px 4px;font-size:13.5px;font-weight:500;text-decoration:none;color:{{ $pasadas ? 'var(--p500)' : 'var(--text-muted)' }};border-bottom:2px solid {{ $pasadas ? 'var(--p400)' : 'transparent' }}">
        Pasadas
    </a>
</div>

{{-- Tabla de citas ─────────────────────────────────────────────────────── --}}
<div class="card card-0">
    @if($citas->count() > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Servicio</th>
                        <th>Dentista</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($citas as $cita)
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--p600);font-size:14px">
                                {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }}
                            </div>
                            <div style="font-size:11.5px;color:var(--text-muted)">
                                {{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('ddd D MMM YYYY') }}
                            </div>
                        </td>
                        <td>{{ $cita->servicio ?? 'Consulta dental' }}</td>
                        <td style="color:var(--text-muted);font-size:13px">{{ $cita->dentista }}</td>
                        <td>@include('partials.badge-estado', ['estado' => $cita->estado])</td>
                        <td>
                            <div class="formulario-accesible">
                                <div id="acciones-{{ $cita->id }}" class="acciones-fila">
                                    <a href="{{ route('cliente.citas.show', $cita->id) }}" class="btn btn-outline btn-sm">Ver</a>
                                    @if($cita->puede_cancelar)
                                        <a href="{{ route('cliente.citas.reprogramar', $cita->id) }}" class="btn btn-outline btn-sm">Reprogramar</a>
                                        <form action="{{ route('cliente.citas.cancelar', $cita->id) }}" method="POST" class="form-en-linea">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Deseas cancelar esta cita?')">Cancelar</button>
                                        </form>
                                    @elseif($cita->aviso_plazo_corto)
                                        <p class="aviso-plazo">{{ $cita->aviso_plazo_corto }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

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
            <p>{{ $pasadas ? 'Aún no tienes citas anteriores' : 'No tienes citas próximas' }}</p>
            @unless($pasadas)
                <a href="{{ route('cliente.agendar') }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                    Agendar cita
                </a>
            @endunless
        </div>
    @endif
</div>

@push('styles')
<link href="{{ asset('css/citas.css') }}?v={{ filemtime(public_path('css/citas.css')) }}" rel="stylesheet">
@endpush

@endsection
