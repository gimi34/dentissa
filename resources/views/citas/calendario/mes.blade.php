{{-- Mes (spec 049, CA3): cuadrícula de lunes a sábado con «N citas» por día; cada día del mes es un
     enlace que deja en la lista solo sus citas. Los días de otro mes no son enlaces ni se anuncian. --}}
<div class="cal-mes">
    @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $nombre)
        <span class="mes-nombre" aria-hidden="true">{{ $nombre }}</span>
    @endforeach
    @foreach($calendario->celdasMes() as $celda)
        @if($celda['fuera'])
            <span class="mes-dia mes-dia--fuera" aria-hidden="true"><span class="mes-num">{{ $celda['numero'] }}</span></span>
        @else
            <a class="mes-dia @if($celda['hoy']) mes-dia--hoy @endif @if($celda['total'] === 0) mes-dia--cero @endif @if($celda['elegido']) mes-dia--elegido @endif"
               data-dia="{{ $celda['iso'] }}" href="{{ $enlace(['dia' => $celda['iso']]) }}"
               aria-label="{{ $celda['nombre'] }}{{ $celda['hoy'] ? ', hoy' : '' }}, {{ $celda['total'] }} {{ $celda['total'] === 1 ? 'cita' : 'citas' }}"
               @if($celda['elegido']) aria-current="date" @endif>
                <span class="mes-num">{{ $celda['numero'] }}</span>
                @if($celda['hoy'])<span class="mes-hoy">Hoy</span>@endif
                <span class="mes-total">{{ $celda['total'] }} <span class="mes-citas">{{ $celda['total'] === 1 ? 'cita' : 'citas' }}</span></span>
            </a>
        @endif
    @endforeach
</div>
