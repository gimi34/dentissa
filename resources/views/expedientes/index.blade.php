@extends('layouts.app')
@section('title', 'Expedientes')

@section('content')

{{-- Buscador ───────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;padding:16px 20px">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div style="flex:1;min-width:220px">
            <label class="field-label" for="campo-buscar">Buscar</label>
            <input type="text" name="buscar" id="campo-buscar" value="{{ request('buscar') }}"
                placeholder="Paciente, diagnóstico..." class="field">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:0">Buscar</button>
        @if(request('buscar'))
            <a href="{{ route("$base.expedientes") }}" class="btn btn-ghost">Limpiar</a>
        @endif
    </form>
</div>

{{-- Tabla de expedientes ───────────────────────────────────────────────── --}}
<div class="card card-0">
    @if($expedientes->count() > 0)
        <div class="table-wrap">
            <table class="tabla-tarjetas">
                <thead>
                    <tr>
                        <th class="celda-cab">Fecha</th>
                        <th>Paciente</th>
                        <th>Diagnóstico</th>
                        <th>Próxima visita</th>
                        <th class="celda-acciones" style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expedientes as $exp)
                    <tr>
                        <td class="celda-cab" data-label="Fecha" style="white-space:nowrap;color:var(--text-muted);font-size:13px">
                            {{ \Carbon\Carbon::parse($exp->creado_en)->format('d/m/Y') }}
                        </td>
                        <td data-label="Paciente" style="font-weight:500">{{ $exp->paciente }}</td>
                        <td data-label="Diagnóstico" style="font-size:13px;color:var(--text-muted);max-width:360px">
                            {{ \Illuminate\Support\Str::limit($exp->diagnostico, 80) }}
                        </td>
                        <td data-label="Próxima visita" style="font-size:13px;color:var(--text-muted)">
                            {{ $exp->proxima_visita ? \Carbon\Carbon::parse($exp->proxima_visita)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="celda-acciones" data-label="Acciones">
                            <div style="display:flex;gap:4px;justify-content:flex-end">
                                <a href="{{ route("$base.expedientes.show", $exp->id) }}" class="btn btn-outline btn-sm">Ver</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($expedientes->hasPages())
            <div class="paginacion">
                <div style="font-size:12.5px;color:var(--text-muted)">
                    {{ $expedientes->firstItem() }}–{{ $expedientes->lastItem() }} de {{ $expedientes->total() }} expedientes
                </div>
                <div style="display:flex;gap:6px">
                    @if($expedientes->onFirstPage())
                        <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">← Anterior</span>
                    @else
                        <a href="{{ $expedientes->previousPageUrl() }}" class="btn btn-outline btn-sm">← Anterior</a>
                    @endif
                    @if($expedientes->hasMorePages())
                        <a href="{{ $expedientes->nextPageUrl() }}" class="btn btn-outline btn-sm">Siguiente →</a>
                    @else
                        <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Siguiente →</span>
                    @endif
                </div>
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <p>{{ request('buscar') ? 'No se encontraron expedientes con esa búsqueda' : 'Aún no hay expedientes clínicos registrados' }}</p>
            <a href="{{ route("$base.pacientes") }}" class="btn btn-primary" style="margin-top:12px;display:inline-flex">
                Ir a pacientes
            </a>
        </div>
    @endif
</div>

@push('styles')
<link href="{{ asset('css/citas.css') }}?v={{ filemtime(public_path('css/citas.css')) }}" rel="stylesheet">
@endpush

@endsection
