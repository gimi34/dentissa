@extends('layouts.app')
@section('title', 'Citas')

{{-- Calendario de citas del personal (spec 049, candidato c6 con los cinco refinados del acta).
     Todo son enlaces del servidor: el periodo y la vista van en la dirección y la página funciona
     igual sin JavaScript. Sin style="" (P9): la posición de cada cita va con clases de calendario.css. --}}
@php
    $rp = match(auth()->user()->rol) {
        'dentista'  => 'dentista',
        'asistente' => 'asistente',
        default     => 'admin',
    };

    $etiquetas = [
        'agendada' => 'Agendada', 'confirmada' => 'Confirmada', 'en_curso' => 'En curso',
        'completada' => 'Completada', 'cancelada' => 'Cancelada', 'no_asistio' => 'No asistió',
    ];

    // Enlace a «Citas» con el periodo y los filtros de ahora, cambiando lo que se pida (null quita).
    $enlace = function (array $cambios = []) use ($rp, $calendario, $filtros) {
        $parametros = array_merge(
            ['vista' => $calendario->vista, 'fecha' => $calendario->fechaPeriodo(), 'dia' => $calendario->dia?->toDateString()],
            $filtros,
            $cambios,
        );

        return route("$rp.citas", array_filter($parametros, fn ($valor) => $valor !== null));
    };
@endphp

@section('topbar-actions')
    <span class="formulario-accesible"><a href="{{ route("$rp.citas.create") }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nueva cita
    </a></span>
@endsection

@section('content')
<div class="formulario-accesible cal-pagina">

    {{-- Resumen del periodo o del día elegido, con un título que lo dice (refinado 5, D5). --}}
    <section class="cal-resumen" aria-labelledby="resumen-titulo">
        <h2 id="resumen-titulo" class="cal-resumen-titulo">{{ $tituloResumen }}</h2>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-value">{{ $resumen->total }}</div><div class="stat-label">Total</div></div>
            <div class="stat-card"><div class="stat-value cal-cifra--agendada">{{ $resumen->agendadas }}</div><div class="stat-label">Agendadas</div></div>
            <div class="stat-card"><div class="stat-value cal-cifra--completada">{{ $resumen->completadas }}</div><div class="stat-label">Completadas</div></div>
            <div class="stat-card"><div class="stat-value cal-cifra--cancelada">{{ $resumen->canceladas }}</div><div class="stat-label">Canceladas</div></div>
        </div>
    </section>

    <section class="card cal-tarjeta" id="calendario" aria-labelledby="cal-titulo">
        @include('citas.calendario.barra')

        {{-- Filtro de la 034 (D6) sin «Desde», «Hasta» ni atajos: el periodo lo lleva la barra y va oculto. --}}
        @php
            $aplicados = collect([isset($filtros['dentista_id']), isset($filtros['estado'])])->filter()->count();
        @endphp
        <div class="filtro-tarjeta cal-filtro">
            <form method="GET" action="{{ route("$rp.citas") }}" class="filtro-citas">
                <input type="hidden" name="vista" value="{{ $calendario->vista }}">
                <input type="hidden" name="fecha" value="{{ $calendario->fechaPeriodo() }}">
                @if($calendario->dia)
                    <input type="hidden" name="dia" value="{{ $calendario->dia->toDateString() }}">
                @endif

                <div class="filtro-buscar">
                    <label class="field-label" for="filtro-buscar">Buscar paciente / dentista</label>
                    <input type="text" id="filtro-buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
                        placeholder="Nombre, teléfono..." maxlength="100" class="field">
                    {{-- Con el panel plegado en el móvil, el buscador se envía con su botón (034, /review R2). --}}
                    <button type="submit" class="btn btn-primary solo-movil">Buscar</button>
                </div>

                <div class="filtro-atajos">
                    <button type="button" class="btn btn-outline filtro-btn" aria-expanded="false" aria-controls="filtro-panel"
                        @if($aplicados) aria-label="{{ $aplicados === 1 ? 'Filtros, 1 aplicado' : "Filtros, {$aplicados} aplicados" }}" @endif>Filtros @if($aplicados)<span class="filtro-contador">{{ $aplicados }}</span>@endif</button>
                </div>

                <div id="filtro-panel" class="filtro-panel">
                    @if($rp !== 'dentista')
                    <div class="filtro-dentista">
                        <label class="field-label" for="filtro-dentista">Dentista</label>
                        <select id="filtro-dentista" name="dentista_id" class="field">
                            <option value="">Todos</option>
                            @foreach($dentistas as $d)
                                <option value="{{ $d->id }}" @selected(($filtros['dentista_id'] ?? null) === $d->id)>{{ $d->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="filtro-estado">
                        <label class="field-label" for="filtro-estado">Estado</label>
                        <select id="filtro-estado" name="estado" class="field">
                            <option value="">Todos</option>
                            @foreach($etiquetas as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(($filtros['estado'] ?? null) === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route("$rp.citas", ['vista' => $calendario->vista, 'fecha' => $calendario->fechaPeriodo()]) }}" class="btn btn-ghost">Limpiar</a>
                </div>
            </form>
        </div>

        @if($calendario->vista === 'mes')
            @include('citas.calendario.mes')
        @else
            @include('citas.calendario.semana')
        @endif
    </section>

    {{-- Lista del periodo; la tira del móvil la añade la semana con @push('cal-tira'). --}}
    @include('citas.calendario.lista')
</div>
@endsection

@push('styles')
{{-- Sin citas.css (DS5): «Citas» usa los campos de app.css, como el candidato c6 que se midió. --}}
<link href="{{ asset('css/calendario.css') }}?v={{ is_file(public_path('css/calendario.css')) ? filemtime(public_path('css/calendario.css')) : 0 }}" rel="stylesheet">
{{-- Sin defer: marca <html> con filtros-js antes de pintar, sin salto de maquetación (spec 034, D6). --}}
<script src="{{ asset('js/filtros.js') }}?v={{ filemtime(public_path('js/filtros.js')) }}"></script>
@endpush
