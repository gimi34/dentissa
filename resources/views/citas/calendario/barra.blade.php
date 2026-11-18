{{-- Barra del periodo (spec 049): título, «Anterior», «Hoy», «Siguiente» y el interruptor «Semana · Mes».
     Cambiar de periodo o de vista quita el día elegido y conserva los filtros (A11). Las flechas no se
     leen y el nombre dice qué se recorre («Anterior semana»; /review R14). --}}
@php
    $enSemana = $calendario->vista === 'semana';
    $periodo = $enSemana ? 'semana' : 'mes';
@endphp
<div class="cal-barra">
    <h2 class="cal-titulo" id="cal-titulo">{{ $calendario->titulo() }}</h2>
    <div class="cal-controles">
        <div class="cal-nav" role="group" aria-label="Periodo">
            <a class="btn btn-outline btn-sm cal-control" href="{{ $enlace(['fecha' => $calendario->fechaAnterior(), 'dia' => null]) }}" aria-label="Anterior {{ $periodo }}"><span aria-hidden="true">‹</span> Anterior</a>
            <a class="btn btn-outline btn-sm cal-control" href="{{ $enlace(['fecha' => $calendario->fechaHoy(), 'dia' => null]) }}">Hoy</a>
            <a class="btn btn-outline btn-sm cal-control" href="{{ $enlace(['fecha' => $calendario->fechaSiguiente(), 'dia' => null]) }}" aria-label="Siguiente {{ $periodo }}">Siguiente <span aria-hidden="true">›</span></a>
        </div>
        <div class="cal-vista" role="group" aria-label="Vista">
            <a class="cal-vista-op" href="{{ $enSemana ? $enlace(['dia' => null]) : $enlace(['vista' => 'semana', 'fecha' => $calendario->fechaOtraVista(), 'dia' => null]) }}" @if($enSemana) aria-current="page" @endif>Semana</a>
            <a class="cal-vista-op" href="{{ $enSemana ? $enlace(['vista' => 'mes', 'fecha' => $calendario->fechaOtraVista(), 'dia' => null]) : $enlace(['dia' => null]) }}" @unless($enSemana) aria-current="page" @endunless>Mes</a>
        </div>
    </div>
</div>
