{{-- Lista del periodo (spec 049, CA4, CA8, CA9): un grupo por día con citas, en orden de hora; con un día
     elegido, solo ese día y «Ver toda la semana / todo el mes». En el móvil se ve solo el día del móvil
     (el elegido, hoy si está en el periodo o el primero), con un título que lo nombra (refinado 1); la
     tira de días la añade la semana. Cada fila es un solo enlace a su detalle (variante de la 048). --}}
@php
    $diaMovil = $calendario->diaMovil()->toDateString();
    $grupos = array_values(array_filter(
        $calendario->dias(),
        fn ($dia) => $dia['total'] > 0 && ($calendario->dia === null || $dia['elegido']),
    ));
    $hayDelMovil = collect($grupos)->contains('iso', $diaMovil);
@endphp
<section class="card cal-lista @if($calendario->dia) cal-lista--dia @endif" id="lista" aria-labelledby="lista-titulo" data-dia-movil="{{ $diaMovil }}">
    <div class="cal-lista-cabecera">
        <h2 id="lista-titulo" class="cal-lista-titulo">{{ $calendario->tituloLista() }}</h2>
        <p class="cal-lista-titulo-movil" role="heading" aria-level="2">{{ $calendario->tituloMovil() }}</p>
        @if($calendario->dia)
            <a class="btn btn-outline btn-sm cal-lista-ver-todo" href="{{ $enlace(['dia' => null]) }}">{{ $calendario->verTodo() }}</a>
        @endif
    </div>

    @stack('cal-tira')

    @if($grupos === [])
        <div class="empty-state">
            <p>{{ $calendario->sinCitas() }}</p>
            <a class="btn btn-primary" href="{{ route("$rp.citas.create") }}">Agendar una cita</a>
        </div>
    @else
        @unless($hayDelMovil)
            <p class="cal-lista-vacia-movil">No hay citas el {{ $calendario->nombreDia($calendario->diaMovil(), false) }}.</p>
        @endunless
        <div class="cal-lista-grupos @if(count($grupos) === 1) cal-lista-grupos--uno @endif">
            @foreach($grupos as $grupo)
                <section class="cal-lista-grupo @if($grupo['iso'] === $diaMovil) cal-lista-grupo--movil @endif" data-dia="{{ $grupo['iso'] }}" aria-labelledby="dia-{{ $grupo['iso'] }}">
                    <h3 id="dia-{{ $grupo['iso'] }}" class="cal-lista-dia">{{ $grupo['nombre'] }} @if($grupo['hoy'])<span class="cal-lista-hoy">Hoy</span>@endif</h3>
                    <ol class="cal-lista-citas">
                        @foreach($grupo['citas'] as $posicion)
                            @php
                                $cita = $posicion['cita'];
                                $hora = \Carbon\Carbon::parse($cita->fecha_hora)->format('G:i');
                                $estado = $etiquetas[$cita->estado] ?? ucfirst($cita->estado);
                            @endphp
                            <li><a class="cal-lista-cita cal-lista-cita--{{ $cita->estado }}" href="{{ route("$rp.citas.show", $cita->id) }}"
                                   aria-label="{{ $hora }}, {{ $cita->paciente }}, {{ $cita->dentista }}, {{ $estado }}">
                                <span class="cal-lista-hora">{{ $hora }}</span>
                                <span class="cal-lista-paciente">{{ $cita->paciente }}</span>
                                <span class="cal-lista-dentista">{{ $cita->dentista }}</span>
                                @include('partials.badge-estado', ['estado' => $cita->estado])
                            </a></li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>
    @endif
</section>
