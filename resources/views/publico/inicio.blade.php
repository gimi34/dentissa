@extends('layouts.publico')

{{-- Spec 038: página de inicio pública (CA1, CA3–CA5), ronda 6 de diseño (D23, design/candidatos/c5.html):
     la presentación con la foto propia en la arcada, «Sobre nosotros» con la cita y los pasos, la escena
     «consulta» (la foto que se abre), el índice de categorías, la agenda con la semana y la ubicación.
     Todo el texto del catálogo sale escapado (TM1); el contenido está completo sin JavaScript (CA8). --}}
@section('titulo', 'Dentissa · Tu sonrisa, nuestra prioridad')
@section('descripcion', 'Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita en línea.')
@section('canonica', rtrim(config('app.url'), '/').'/')

@php
    $horario = collect($contacto['horario'])->map(fn ($horas, $dias) => "{$dias} {$horas}")->implode(' · ');
    // Las filas de la semana de la ubicación con su data-day, el que marca publico.js como «hoy» (domingo = 0).
    $filas = ['Lunes a viernes' => '1-5', 'Sábados' => '6'];
    // La semana de la agenda, día por día, desde el horario semanal de la configuración (1 = lunes … 7 = domingo).
    $dias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
    $pasos = [
        ['Qué vamos a hacer', 'Lo vemos juntos, con palabras claras, antes de empezar.'],
        ['Cuánto dura', 'Cuántas citas lleva y cuánto tiempo cada una, para que lo acomodes a tu semana.'],
        ['Qué opciones tienes', 'Si hay más de un camino, te los mostramos para que decidas con calma.'],
    ];
@endphp

@section('contenido')
<section class="pub-hero" aria-labelledby="titulo">
    {{-- Arcos dibujados de fondo (M-2): decorativos. --}}
    <svg class="pub-arco" viewBox="0 0 1000 600" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false"><path d="M60 600 C80 220 300 60 500 60 C700 60 920 220 940 600"/><path d="M150 600 C170 300 340 160 500 160 C660 160 830 300 850 600"/><path d="M240 600 C260 380 380 260 500 260 C620 260 740 380 760 600"/></svg>
    <div class="pub-wrap pub-hero__grid">
        <div>
            <span class="pub-etiqueta">Odontología integral · Lázaro Cárdenas</span>
            {{-- M-2: cada línea sube desde su máscara (solo con la clase js y sin movimiento reducido). --}}
            <h1 id="titulo"><span class="pub-ln"><span>Tu sonrisa,</span></span> <span class="pub-ln"><span>nuestra</span></span> <span class="pub-ln"><span><em>prioridad.</em></span></span></h1>
            <p class="pub-hero__lead">Para toda la familia, en el centro de Lázaro Cárdenas. Crea tu cuenta y agenda en línea.</p>
            <div class="pub-hero__actions">
                <a class="c-btn c-btn--primary c-btn--lg" href="{{ route('register') }}">Agendar mi primera cita <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                <a class="c-btn c-btn--secondary c-btn--lg" href="{{ url('/servicios') }}">Ver servicios</a>
            </div>
            {{-- Cosa viva: publico.js escribe «¿Está abierto ahora?» con este horario y la hora de la consulta;
                 sin JavaScript no se ve y se lee el horario completo y el teléfono (CA1). --}}
            <ul class="pub-hero__facts">
                <li><span class="pub-status" data-live data-horario="{{ json_encode($contacto['horario_semanal']) }}" data-zona="{{ config('clinica.zona_horaria') }}"><span class="pub-status__dot" aria-hidden="true"></span><span class="pub-status__text" aria-live="polite"></span></span></li>
                <li>{{ $horario }}</li>
                <li><a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a></li>
            </ul>
            <p class="pub-acceso"><a href="{{ route('login') }}">Iniciar sesión</a><a href="{{ route('register') }}">Crear cuenta</a></p>
        </div>
        {{-- La foto propia de la marca (D20) en el marco de la arcada (D23). --}}
        <figure class="pub-arcada">
            <div class="pub-arcada__marco"><img src="{{ asset('images/brand/access.webp') }}" data-origen="propia" width="900" height="900" fetchpriority="high" alt="Revisión dental con espejo, teñida de rosa, con el logo de Melissa López N., Odontología integral"></div>
        </figure>
    </div>
</section>

<section class="pub-sec pub-about" id="nosotros" aria-labelledby="t-nosotros">
    <div class="pub-wrap">
        <h2 id="t-nosotros">Sobre nosotros</h2>
        {{-- CA3: la primera frase como cita destacada y el resto en dos párrafos; el texto no cambia (A44). --}}
        <p class="pub-cita" data-reveal="mask">Somos una consulta de odontología integral en el centro de Lázaro Cárdenas.</p>
        <div class="pub-about__body">
            <p data-reveal>Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.</p>
            <p data-reveal>Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.</p>
        </div>
        <ol class="pub-pasos" role="list" aria-label="Antes de cada tratamiento">
            @foreach ($pasos as [$titulo, $texto])
                <li class="pub-paso" data-reveal><span class="pub-paso__n" aria-hidden="true">{{ $loop->iteration }}</span><h3>{{ $titulo }}</h3><p>{{ $texto }}</p></li>
            @endforeach
        </ol>
        <a class="c-btn c-btn--ink c-btn--lg" href="{{ route('register') }}" data-reveal>Crear mi cuenta</a>
    </div>
</section>

{{-- M-1: la consulta se abre desde la arcada. Sin JavaScript o con movimiento reducido, la foto entera. --}}
<section class="pub-consulta" data-scene="consulta" aria-label="La consulta">
    <div class="pub-consulta__stage">
        <figure>
            <div class="pub-consulta__foto"><img src="{{ asset('images/brand/access.webp') }}" data-origen="propia" width="900" height="900" loading="lazy" alt="La consulta de Melissa López N.: revisión dental con espejo, teñida de rosa, con el logo encima"></div>
            <figcaption>La consulta de Melissa López N., en el centro de Lázaro Cárdenas.</figcaption>
        </figure>
    </div>
</section>

{{-- CA4: solo las categorías con algún servicio activo, sin servicios sueltos ni precios. --}}
@if ($categorias->isNotEmpty())
    <section class="pub-sec pub-services" id="servicios" aria-labelledby="t-servicios">
        <div class="pub-wrap">
            <div class="pub-services__head">
                <div>
                    <span class="pub-etiqueta">Por tipo de tratamiento</span>
                    <h2 id="t-servicios" data-reveal="mask">Nuestros servicios</h2>
                    <p data-reveal>Lo que atendemos en la consulta.</p>
                </div>
                <a class="c-btn c-btn--secondary" href="{{ url('/servicios') }}" data-reveal>Ver todos los servicios <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
            <ul class="pub-index" role="list">
                @foreach ($categorias as $categoria => $servicios)
                    <li data-reveal>
                        <a href="{{ url('/servicios') }}#categoria-{{ $loop->index }}">
                            <span class="pub-index__n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="pub-index__name">{{ $categoria }}</span>
                            <span class="pub-index__count">{{ $servicios->count() }} {{ $servicios->count() === 1 ? 'tratamiento' : 'tratamientos' }}</span>
                            <span class="pub-index__go" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M9 7h8v8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

<section class="pub-book" aria-labelledby="t-agenda">
    <div class="pub-wrap">
        <div class="pub-book__card" data-reveal>
            <svg class="pub-book__arco" viewBox="0 0 600 600" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M40 600 C60 200 220 40 300 40 C380 40 540 200 560 600"/><path d="M130 600 C150 280 250 150 300 150 C350 150 450 280 470 600"/></svg>
            <div class="pub-book__texto">
                <h2 id="t-agenda">Agenda sin llamar.</h2>
                <p>Crea tu cuenta y elige el día de tu cita en línea, a la hora que te quede, aunque la consulta esté cerrada.</p>
            </div>
            {{-- La semana real de la consulta, del horario semanal de la configuración. --}}
            <ul class="pub-semana" role="list" aria-label="Horario de la semana">
                @foreach ($dias as $numero => $dia)
                    @php($tramo = $contacto['horario_semanal'][$numero] ?? null)
                    <li @class(['pub-semana__cerrado' => ! $tramo])><b>{{ $dia }}</b> {{ $tramo ? implode('–', $tramo) : 'Cerrado' }}</li>
                @endforeach
            </ul>
            <div class="pub-book__pie">
                <span>Tu cita queda en tu cuenta y puedes cambiarla desde ahí.</span>
                <a class="c-btn c-btn--primary c-btn--lg" href="{{ route('register') }}">Agendar mi primera cita <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
        </div>
    </div>
</section>

{{-- CA5: la dirección, la semana, el teléfono, «Cómo llegar» y el mapa de esa misma dirección. --}}
<section class="pub-sec pub-visit" id="ubicacion" aria-labelledby="t-ubicacion">
    <div class="pub-wrap">
        <span class="pub-etiqueta">Visítanos</span>
        <h2 id="t-ubicacion" data-reveal="mask">Encuéntranos fácilmente</h2>
        <div class="pub-visit__grid">
            <div data-reveal>
                <address class="pub-visit__addr">{{ $contacto['direccion'] }}</address>
                {{-- CA5 (/review R33): el horario tal cual; la tabla de abajo lo detalla día por día. --}}
                <p class="pub-visit__horario">{{ $horario }}</p>
                <dl data-semana>
                    @foreach ($contacto['horario'] as $nombre => $horas)
                        <div data-day="{{ $filas[$nombre] ?? '' }}">
                            <dt>{{ $nombre }}</dt>
                            <dd>{{ $horas }}</dd>
                        </div>
                    @endforeach
                    <div data-day="0">
                        <dt>Domingos</dt>
                        <dd>Cerrado</dd>
                    </div>
                </dl>
                <a class="pub-visit__tel" href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>
                <div>
                    {{-- TM5: el mapa aparte se abre en otra pestaña sin acceso a esta página. --}}
                    <a class="c-btn c-btn--ink" href="https://www.google.com/maps/dir/?api=1&destination={{ rawurlencode($contacto['direccion']) }}" target="_blank" rel="noopener noreferrer" aria-label="Cómo llegar (se abre en una pestaña nueva)">Cómo llegar <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 17 17 7M9 7h8v8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                </div>
            </div>
            {{-- TM4: perezoso y solo con el dominio como referente. --}}
            <div class="pub-map" data-reveal>
                <iframe src="https://www.google.com/maps?q={{ rawurlencode($contacto['direccion']) }}&output=embed" title="Mapa de Google con la ubicación de la consulta" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                <p>El mapa lo sirve Google.</p>
            </div>
        </div>
    </div>
</section>
@endsection
