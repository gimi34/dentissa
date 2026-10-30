@extends('layouts.app')
@section('title', 'Auditoría')

{{-- Spec 020 (CA6, CA12): consulta del registro de auditoría según design.md (c4). $eventos es null
     cuando se vuelve de un filtro rechazado: entonces se muestra el formulario con sus errores, sin
     lista. Horas y fechas en la hora de la clínica (decisión del usuario, 2026-10-29). --}}
@php
    $zona = (string) config('clinica.zona_horaria');
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fecha = fn (\Carbon\CarbonInterface $momento) => $momento->format('j').' '.$meses[$momento->month - 1].' '.$momento->format('Y');
    $nombresDeFiltro = ['actor' => 'actor', 'accion' => 'acción', 'recurso_tipo' => 'tipo de recurso', 'recurso_id' => 'identificador de recurso', 'desde' => 'desde', 'hasta' => 'hasta'];
    $resultados = ['exito' => ['badge-green', 'Éxito'], 'denegado' => ['badge-red', 'Denegado'], 'denegado_404' => ['badge-red', 'Denegado · 404'], 'error' => ['badge-red', 'Error']];
    $valor = fn (string $campo) => old($campo, $filtros[$campo] ?? '');
    $aplicados = collect(array_keys($nombresDeFiltro))->filter(fn ($campo) => $valor($campo) !== '' && $valor($campo) !== null)->count();
    $conErrores = $eventos === null;
    $campos = [
        'actor' => ['f-actor', 'Actor', 'text', true],
        'accion' => ['f-accion', 'Acción', 'select', false],
        'recurso_tipo' => ['f-tipo', 'Tipo de recurso', 'select', false],
        'recurso_id' => ['f-recurso', 'Identificador de recurso', 'text', true],
        'desde' => ['f-desde', 'Desde', 'date', false],
        'hasta' => ['f-hasta', 'Hasta', 'date', false],
    ];
@endphp

@section('content')
<p class="intro-auditoria">Accesos, denegaciones y consultas, del más reciente al más antiguo. Es de solo lectura y tu consulta también queda registrada.</p>

@if($conErrores)
<div class="aviso-filtro" role="alert">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    <p>Revisa el filtro marcado: no se buscó nada.</p>
</div>
@endif

<div class="card filtro-tarjeta formulario-accesible">
    <form method="GET" action="/admin/auditoria" class="filtro-citas filtro-auditoria{{ $conErrores ? ' abierto' : '' }}" novalidate>
        <div class="filtro-atajos">
            <button type="button" class="btn btn-outline filtro-btn" aria-expanded="{{ $conErrores ? 'true' : 'false' }}" aria-controls="filtro-panel"
                @if($aplicados > 0) aria-label="Filtros, {{ $aplicados }} {{ $aplicados === 1 ? 'aplicado' : 'aplicados' }}" @endif>Filtros @if($aplicados > 0)<span class="filtro-contador" aria-hidden="true">{{ $aplicados }}</span>@endif</button>
        </div>
        <div id="filtro-panel" class="filtro-panel">
            @foreach($campos as $nombre => [$id, $etiqueta, $tipo, $ancho])
                @php
                    $error = $errors->first($nombre);
                    $describe = trim(($nombre === 'actor' ? $id.'-ayuda ' : '').($error ? $id.'-error' : ''));
                @endphp
                <div class="campo-filtro{{ $ancho ? ' campo-ancho' : '' }}{{ $error ? ' campo-con-error' : '' }}">
                    <label class="field-label" for="{{ $id }}">{{ $etiqueta }}</label>
                    @if($tipo === 'select')
                        <select id="{{ $id }}" name="{{ $nombre }}" class="field" @if($describe) aria-describedby="{{ $describe }}" @endif @if($error) aria-invalid="true" @endif>
                            <option value="">Todas</option>
                            @foreach($valores[$nombre] as $opcion)
                                <option value="{{ $opcion }}" @selected($valor($nombre) === $opcion)>{{ $opcion }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $tipo }}" id="{{ $id }}" name="{{ $nombre }}" value="{{ $valor($nombre) }}" class="field" @if($describe) aria-describedby="{{ $describe }}" @endif @if($error) aria-invalid="true" @endif>
                    @endif
                    @if($nombre === 'actor')
                        <p class="field-hint" id="{{ $id }}-ayuda">Identificador del usuario</p>
                    @endif
                    @if($error)
                        <p class="campo-error" id="{{ $id }}-error">{{ $error }}</p>
                    @endif
                </div>
            @endforeach
            <div class="filtro-acciones">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="/admin/auditoria" class="btn btn-outline">Limpiar</a>
            </div>
        </div>
    </form>
</div>

@unless($conErrores)
<section class="card card-0 lista-auditoria" aria-labelledby="titulo-eventos">
    <div class="lista-cab">
        <h2 class="section-title" id="titulo-eventos">Eventos</h2>
        <p class="lista-recuento">
            @if($eventos->total() === 0)
                0 eventos
            @else
                {{ number_format($eventos->total(), 0, ',', '.') }} {{ $eventos->total() === 1 ? 'evento' : 'eventos' }} · página {{ $eventos->currentPage() }} de {{ $eventos->lastPage() }} · del más reciente al más antiguo
            @endif
        </p>
    </div>

    @if($eventos->total() === 0)
        <div class="empty-state vacio-auditoria" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12h6"/></svg>
            <p class="vacio-titulo">No hay eventos con estos filtros</p>
            <p class="vacio-texto">Prueba con un rango de fechas más amplio o quita algún filtro.</p>
            <a href="/admin/auditoria" class="btn btn-outline">Quitar los filtros</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="tabla-tarjetas tabla-auditoria">
                <thead>
                    <tr>
                        <th class="celda-cab" scope="col">Fecha y hora</th>
                        <th scope="col">Acción</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Recurso</th>
                        <th class="celda-estado" scope="col">Resultado</th>
                        <th scope="col">Origen y petición</th>
                        <th scope="col" class="celda-peticion-movil">Petición</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eventos as $evento)
                        @php
                            $momento = \Carbon\CarbonImmutable::parse($evento->ocurrido_en, 'UTC')->setTimezone($zona);
                            $detalle = json_decode((string) $evento->detalle, true) ?: [];
                            $texto = match ($evento->accion) {
                                'acceso_denegado' => isset($detalle['accion_solicitada']) ? 'Solicitó '.$detalle['accion_solicitada'] : null,
                                'auditoria_consultada' => $detalle === [] ? 'Sin filtros' : 'Filtros: '.implode(', ', array_map(fn ($clave) => $nombresDeFiltro[$clave] ?? $clave, array_keys($detalle))),
                                'auditoria_purgada' => 'Purgó '.($detalle['eventos_purgados'] ?? 0).' eventos hasta '.(isset($detalle['fecha_corte']) ? $fecha(\Carbon\CarbonImmutable::parse($detalle['fecha_corte'])->setTimezone($zona)) : '—'),
                                default => null,
                            };
                            [$insignia, $resultado] = $resultados[$evento->resultado] ?? ['badge-red', $evento->resultado];
                        @endphp
                        <tr>
                            <td class="celda-cab" data-label="Fecha y hora"><span class="evento-hora">{{ $momento->format('H:i:s') }}</span><span class="evento-fecha">{{ $fecha($momento) }}</span></td>
                            <td data-label="Acción"><div class="celda-valor"><span class="evento-accion">{{ $evento->accion }}</span>@if($texto)<span class="evento-detalle">{{ $texto }}</span>@endif</div></td>
                            <td data-label="Actor"><div class="celda-valor">
                                @if($evento->actor_id)
                                    <span class="dato-rol">{{ $evento->actor_rol }}</span><span class="dato-id">{{ $evento->actor_id }}</span>
                                @elseif($evento->identificador_intentado)
                                    <span class="dato-rol">sin sesión</span><span class="dato-id">{{ $evento->identificador_intentado }}</span>
                                @else
                                    <span class="dato-rol">sistema</span>
                                @endif
                            </div></td>
                            <td data-label="Recurso"><div class="celda-valor">
                                <span class="dato-rol">{{ $evento->recurso_tipo ?? '—' }}</span>@if($evento->recurso_id)<span class="dato-id">{{ $evento->recurso_id }}</span>@endif
                            </div></td>
                            <td class="celda-estado" data-label="Resultado"><span class="badge {{ $insignia }}">{{ $resultado }}</span></td>
                            <td data-label="Origen" class="celda-origen"><div class="celda-valor"><span class="dato-ip">{{ $evento->ip ?? '—' }}</span><span class="dato-id dato-peticion">{{ $evento->request_id }}</span></div></td>
                            <td data-label="Petición" class="celda-peticion-movil"><span class="dato-id">{{ $evento->request_id }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <nav class="paginacion" aria-label="Paginación de eventos">
            <p class="paginacion-texto">Mostrando {{ $eventos->firstItem() }}–{{ $eventos->lastItem() }} de {{ number_format($eventos->total(), 0, ',', '.') }} · página {{ $eventos->currentPage() }} de {{ $eventos->lastPage() }}</p>
            @if($eventos->hasPages())
                <div class="paginacion-botones">
                    @unless($eventos->onFirstPage())
                        <a class="btn btn-outline" href="{{ $eventos->previousPageUrl() }}" rel="prev">Anterior</a>
                    @endunless
                    @if($eventos->hasMorePages())
                        <a class="btn btn-outline" href="{{ $eventos->nextPageUrl() }}" rel="next">Siguiente</a>
                    @endif
                </div>
            @endif
        </nav>
    @endif
</section>
@endunless

@push('styles')
<link href="{{ asset('css/auditoria.css') }}?v={{ filemtime(public_path('css/auditoria.css')) }}" rel="stylesheet">
{{-- Sin defer: marca <html> con filtros-js antes de pintar, como la lista de citas (spec 034, D6). --}}
<script src="{{ asset('js/filtros.js') }}?v={{ filemtime(public_path('js/filtros.js')) }}"></script>
@endpush
@endsection
