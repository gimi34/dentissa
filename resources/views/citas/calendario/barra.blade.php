{{-- Barra del periodo (spec 049): título, «Anterior», «Hoy», «Siguiente» y el interruptor «Semana · Mes».
     Cambiar de periodo o de vista quita el día elegido y conserva los filtros (A11). --}}
@php
    $enSemana = $calendario->vista === 'semana';
@endphp
<div class="cal-barra">
    <h2 class="cal-titulo" id="cal-titulo">{{ $calendario->titulo() }}</h2>
    <div class="cal-controles">
        <div class="cal-nav" role="group" aria-label="Periodo">
            <a class="btn btn-outline btn-sm cal-control" href="{{ $enlace(['fecha' => $calendario->fechaAnterior(), 'dia' => null]) }}">‹ Anterior</a>
            <a class="btn btn-outline btn-sm cal-control" href="{{ $enlace(['fecha' => $calendario->fechaHoy(), 'dia' => null]) }}">Hoy</a>
            <a class="btn btn-outline btn-sm cal-control" href="{{ $enlace(['fecha' => $calendario->fechaSiguiente(), 'dia' => null]) }}">Siguiente ›</a>
        </div>
        <div class="cal-vista" role="group" aria-label="Vista">
            <a class="cal-vista-op" href="{{ $enSemana ? $enlace(['dia' => null]) : $enlace(['vista' => 'semana', 'fecha' => $calendario->fechaOtraVista(), 'dia' => null]) }}" @if($enSemana) aria-current="page" @endif>Semana</a>
            <a class="cal-vista-op" href="{{ $enSemana ? $enlace(['vista' => 'mes', 'fecha' => $calendario->fechaOtraVista(), 'dia' => null]) : $enlace(['dia' => null]) }}" @unless($enSemana) aria-current="page" @endunless>Mes</a>
        </div>
    </div>
</div>
