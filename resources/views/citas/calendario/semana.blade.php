{{-- Semana por horas (spec 049, ≥ 900 px) y, para el móvil, la tira de días, que la lista pinta bajo
     su título (@stack('cal-tira')). Cada cita es un solo enlace a su detalle (variante de la 048); cada
     hueco, un enlace a «Nueva cita» con el día y la hora (H:i, con cero) y, con una sola dentista, ella. --}}
@php
    $dias = $calendario->dias();
@endphp
<a class="cal-saltar" href="#lista-titulo">Saltar a la lista de citas</a>
<div class="cal-semana cal-filas-{{ $calendario->numeroDeFilas() }}">
    <div class="cal-col cal-col--horas" aria-hidden="true">
        <div class="cal-esquina"></div>
        <div class="cal-horas">
            @foreach($calendario->filas() as $fila)
                @if($fila['etiqueta'])<span class="cal-hora cal-r{{ $fila['fila'] }}">{{ $fila['etiqueta'] }}</span>@endif
            @endforeach
        </div>
    </div>
    @foreach($dias as $dia)
        <div class="cal-col @if($dia['carriles'] > 1) cal-col--carriles-{{ min($dia['carriles'], 8) }} @endif">
            <a class="cal-dia-cab @if($dia['hoy']) cal-dia-cab--hoy @endif @if($dia['elegido']) cal-dia-cab--elegido @endif"
               data-dia="{{ $dia['iso'] }}" href="{{ $enlace(['dia' => $dia['iso']]) }}" @if($dia['elegido']) aria-current="date" @endif>
                <span class="cal-dia-nombre">{{ $dia['corto'] }} <b>{{ $dia['numero'] }}</b></span>
                @if($dia['hoy'])<span class="cal-hoy-marca">Hoy</span>@endif
                <span class="cal-dia-total">{{ $dia['total'] }} {{ $dia['total'] === 1 ? 'cita' : 'citas' }}</span>
            </a>
            <div class="cal-dia cal-carriles-{{ min($dia['carriles'], 8) }} @if($dia['hoy']) cal-dia--hoy @endif @if($dia['elegido']) cal-dia--elegido @endif"
                 data-dia="{{ $dia['iso'] }}" role="group" aria-label="{{ $dia['nombre'] }}">
                @foreach($dia['cerrados'] as $cerrado)
                    <div class="cal-cerrado cal-r{{ $cerrado['fila'] }} cal-s{{ min($cerrado['tramos'], 48) }}"><span>{{ $cerrado['texto'] }}</span></div>
                @endforeach
                @foreach($dia['citas'] as $posicion)
                    @php
                        $cita = $posicion['cita'];
                        $hora = \Carbon\Carbon::parse($cita->fecha_hora)->format('G:i');
                        $estado = $etiquetas[$cita->estado] ?? ucfirst($cita->estado);
                    @endphp
                    <a class="cal-cita cal-cita--{{ $cita->estado }} cal-r{{ $posicion['fila'] }} cal-s{{ min($posicion['tramos'], 24) }} @if($posicion['carril']) cal-carril-{{ min($posicion['carril'], 8) }} @endif"
                       href="{{ route("$rp.citas.show", $cita->id) }}"
                       aria-label="{{ $hora }}, {{ $cita->paciente }}, {{ $cita->dentista }}, {{ $estado }}">
                        <span class="cal-cita-linea"><span class="cal-cita-hora">{{ $hora }}</span> <span class="cal-cita-paciente">{{ $cita->paciente }}</span></span>
                        <span class="cal-cita-meta">{{ $cita->dentista_apellido ?? $cita->dentista }}</span>
                        <span class="cal-cita-estado">{{ $estado }}</span>
                    </a>
                @endforeach
                @foreach($dia['huecos'] as $hueco)
                    <a class="cal-hueco cal-r{{ $hueco['fila'] }} @if($hueco['carril']) cal-carril-{{ min($hueco['carril'], 8) }} @endif"
                       href="{{ route("$rp.citas.create", array_filter(['fecha' => $hueco['fecha'], 'hora' => $hueco['hora'], 'dentista' => $calendario->dentista])) }}"
                       aria-label="Agendar el {{ mb_strtolower(strtok($dia['nombre'], ' ')) }} {{ $dia['numero'] }} a las {{ $hueco['hora'] }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="12" y1="6" x2="12" y2="18"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@push('cal-tira')
<ol class="cal-tira" aria-label="Días de la semana">
    @php $diaMovil = $calendario->diaMovil()->toDateString(); @endphp
    @foreach($dias as $dia)
        <li><a class="tira-dia @if($dia['iso'] === $diaMovil) tira-dia--elegido @endif @if($dia['hoy']) tira-dia--hoy @endif"
               data-dia="{{ $dia['iso'] }}" href="{{ $enlace(['dia' => $dia['iso']]) }}" @if($dia['iso'] === $diaMovil) aria-current="date" @endif
               aria-label="{{ $dia['corto'] }} {{ $dia['numero'] }}, {{ $dia['total'] }} {{ $dia['total'] === 1 ? 'cita' : 'citas' }}{{ $dia['hoy'] ? ', hoy' : '' }}">
            <span class="tira-nombre">{{ $dia['corto'] }} <b>{{ $dia['numero'] }}</b></span>
            {{-- Hoy, también sin color: en el nombre y con borde grueso (/review R12). --}}
            <span class="tira-total">{{ $dia['total'] }}<span class="cal-oculto"> {{ $dia['total'] === 1 ? 'cita' : 'citas' }}</span></span>
        </a></li>
    @endforeach
</ol>
@endpush
