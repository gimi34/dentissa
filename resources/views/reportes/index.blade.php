@extends('layouts.app')
@section('title', 'Reportes')

{{-- Spec 047: reportes de la administración según design.md. $reporte y $periodo son null cuando se
     vuelve de un periodo rechazado: entonces se muestra el filtro con sus errores, sin cifras (D3).
     Solo totales: aquí no llega ningún dato de pacientes ni de una cita concreta (CA10). --}}
@php
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $dia = fn (\Carbon\CarbonInterface $f) => $f->format('j');
    $mes = fn (\Carbon\CarbonInterface $f) => $meses[$f->month - 1];
    $fechaLarga = fn (\Carbon\CarbonInterface $f) => $dia($f).' de '.$mes($f).' de '.$f->format('Y');
    $conErrores = $reporte === null;

    $frase = null;
    if ($periodo !== null) {
        $desde = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $periodo['desde']);
        $hasta = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $periodo['hasta']);
        $frase = match (true) {
            $desde->equalTo($hasta) => 'El '.$fechaLarga($desde),
            $desde->format('Y-m') === $hasta->format('Y-m') => 'Del '.$dia($desde).' al '.$fechaLarga($hasta),
            $desde->year === $hasta->year => 'Del '.$dia($desde).' de '.$mes($desde).' al '.$fechaLarga($hasta),
            default => 'Del '.$fechaLarga($desde).' al '.$fechaLarga($hasta),
        };
    }

    // Un valor enviado como lista vuelve con su error; en el campo solo se repone texto.
    $valor = fn (string $campo) => is_string($anterior = old($campo, $periodo[$campo] ?? '')) ? $anterior : '';
    $porcentaje = fn (float $numero) => number_format($numero, 1, ',', '').' %';
    $deCitas = fn (int $parte, int $total) => $parte.' de '.$total.' '.($total === 1 ? 'cita' : 'citas');
    $campos = ['desde' => ['reporte-desde', 'Desde'], 'hasta' => ['reporte-hasta', 'Hasta']];
@endphp

@push('styles')
    <link href="{{ asset('css/reportes.css') }}?v={{ filemtime(public_path('css/reportes.css')) }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/reportes.js') }}?v={{ filemtime(public_path('js/reportes.js')) }}" defer></script>
@endpush

@section('content')
<div id="reporte-contenido" class="reporte formulario-accesible">

    @if($frase !== null)
    <div id="reporte-cabecera-impresa" class="solo-impresion reporte-cabecera-impresa">
        <p class="reporte-cabecera-clinica">{{ config('clinica.nombre') }}</p>
        <p class="reporte-cabecera-titulo">Reportes</p>
        <p>{{ $frase }}</p>
        <p>Generado el {{ $fechaLarga($generado) }}</p>
    </div>
    @endif

    @if($conErrores)
    <div class="reporte-aviso" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <p>Revisa el periodo marcado: no se calculó ningún reporte.</p>
    </div>
    @endif

    <div class="card reporte-filtro">
        <form id="reporte-filtro" class="reporte-formulario" method="GET" action="/admin/reportes" novalidate>
            @foreach($campos as $nombre => [$id, $etiqueta])
                @php $error = $errors->first($nombre); @endphp
                <div class="reporte-campo{{ $error ? ' reporte-campo-con-error' : '' }}">
                    <label class="field-label" for="{{ $id }}">{{ $etiqueta }}</label>
                    <input type="date" id="{{ $id }}" name="{{ $nombre }}" value="{{ $valor($nombre) }}" class="field" @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
                    @if($error)
                        <p class="reporte-error" id="{{ $id }}-error">{{ $error }}</p>
                    @endif
                </div>
            @endforeach
            <button type="submit" class="btn btn-primary">Ver reporte</button>
        </form>

        <div class="reporte-barra">
            <nav id="reporte-atajos" class="reporte-atajos" aria-label="Periodos rápidos">
                @foreach($atajos as $atajo)
                    @php $enPantalla = $periodo !== null && $periodo['desde'] === $atajo['desde'] && $periodo['hasta'] === $atajo['hasta']; @endphp
                    <a class="btn btn-outline" href="/admin/reportes?desde={{ $atajo['desde'] }}&amp;hasta={{ $atajo['hasta'] }}" @if($enPantalla) aria-current="true" @endif>{{ $atajo['etiqueta'] }}</a>
                @endforeach
            </nav>
            <div class="reporte-acciones">
                {{-- Nace oculto: reportes.js lo muestra. Sin JavaScript, la página se imprime desde el navegador. --}}
                <button type="button" id="reporte-imprimir" class="btn btn-outline" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    @unless($conErrores)
    <p id="reporte-periodo" class="reporte-periodo">{{ $frase }}</p>

    @if($reporte['total'] === 0)
    <div id="reporte-vacio" class="card empty-state reporte-vacio" role="status">
        <p>No hay citas en este periodo</p>
    </div>
    @else
    <div id="reporte-resumen" class="stats-grid stats-grid-3 reporte-resumen">
        <div id="resumen-total" class="stat-card reporte-total">
            <p class="stat-value" id="resumen-total-cifra">{{ $reporte['total'] }}</p>
            <p class="stat-label">Citas en el periodo</p>
        </div>
        <div class="reporte-tasas">
            @foreach(['cancelacion' => 'Cancelación', 'inasistencia' => 'Inasistencia'] as $clave => $etiqueta)
            <div id="resumen-{{ $clave }}" class="stat-card">
                <p class="stat-value">{{ $porcentaje($reporte[$clave]['porcentaje']) }}</p>
                <p class="stat-label">{{ $etiqueta }}</p>
                <p class="reporte-tasa-detalle">{{ $deCitas($reporte[$clave]['citas'], $reporte['total']) }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="reporte-rejilla">
        <section id="reporte-estados" class="card card-0 reporte-seccion reporte-seccion-estados" aria-labelledby="titulo-estados">
            <h2 class="section-title reporte-titulo" id="titulo-estados">Citas por estado</h2>
            <table class="tabla-tarjetas reporte-tabla">
                <caption>Citas del periodo según su estado</caption>
                <thead>
                    <tr>
                        <th scope="col">Estado</th>
                        <th scope="col" class="cifra">Citas</th>
                        <th scope="col" class="cifra">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reporte['porEstado'] as $fila)
                    <tr>
                        <th scope="row">@include('partials.badge-estado', ['estado' => $fila['estado']])</th>
                        <td class="cifra" data-label="Citas">{{ $fila['citas'] }}</td>
                        <td class="cifra" data-label="Porcentaje">{{ $porcentaje($fila['porcentaje']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section id="reporte-dentistas" class="card card-0 reporte-seccion reporte-seccion-dentistas" aria-labelledby="titulo-dentistas">
            <h2 class="section-title reporte-titulo" id="titulo-dentistas">Citas por dentista</h2>
            <table class="tabla-tarjetas reporte-tabla">
                <caption>Citas del periodo de cada dentista, de más a menos</caption>
                <thead>
                    <tr>
                        <th scope="col">Dentista</th>
                        <th scope="col" class="cifra">Total</th>
                        <th scope="col" class="cifra">Completadas</th>
                        <th scope="col" class="cifra">Canceladas</th>
                        <th scope="col" class="cifra">No asistió</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reporte['porDentista']['filas'] as $fila)
                    <tr>
                        <th scope="row">{{ $fila['nombre'] }}</th>
                        <td class="cifra" data-label="Total">{{ $fila['total'] }}</td>
                        <td class="cifra" data-label="Completadas">{{ $fila['completadas'] }}</td>
                        <td class="cifra" data-label="Canceladas">{{ $fila['canceladas'] }}</td>
                        <td class="cifra" data-label="No asistió">{{ $fila['no_asistio'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row">Total</th>
                        <td class="cifra" data-label="Total">{{ $reporte['porDentista']['totales']['total'] }}</td>
                        <td class="cifra" data-label="Completadas">{{ $reporte['porDentista']['totales']['completadas'] }}</td>
                        <td class="cifra" data-label="Canceladas">{{ $reporte['porDentista']['totales']['canceladas'] }}</td>
                        <td class="cifra" data-label="No asistió">{{ $reporte['porDentista']['totales']['no_asistio'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section id="reporte-servicios" class="card card-0 reporte-seccion reporte-seccion-servicios" aria-labelledby="titulo-servicios">
            <h2 class="section-title reporte-titulo" id="titulo-servicios">Servicios más solicitados</h2>
            @if($reporte['porServicio']['filas'] === [])
            <p class="reporte-nota">Todas las citas del periodo están canceladas.</p>
            @else
            <table class="tabla-tarjetas reporte-tabla">
                <caption>Citas no canceladas del periodo por servicio, de más a menos</caption>
                <thead>
                    <tr>
                        <th scope="col" class="cifra">Posición</th>
                        <th scope="col">Servicio</th>
                        <th scope="col" class="cifra">Citas</th>
                        <th scope="col" class="cifra">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reporte['porServicio']['filas'] as $fila)
                    <tr>
                        <td class="cifra" data-label="Posición">{{ $loop->iteration }}</td>
                        <th scope="row">{{ $fila['nombre'] }}</th>
                        <td class="cifra" data-label="Citas">{{ $fila['citas'] }}</td>
                        <td class="cifra" data-label="Porcentaje">{{ $porcentaje($fila['porcentaje']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </section>
    </div>
    @endif
    @endunless
</div>
@endsection
