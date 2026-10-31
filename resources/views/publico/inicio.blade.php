@extends('layouts.publico')

{{-- Spec 038: página de inicio pública (CA1, CA3–CA5), ronda 5 «La ventana del diente» (D19): la
     presentación con la ventana del diente, la escena de la foto, «Sobre nosotros», «Antes de cada
     tratamiento», el índice de categorías, la tarjeta de la agenda y la ubicación. Todo el texto del
     catálogo sale escapado (TM1); el contenido está completo sin JavaScript (CA8). --}}
@section('titulo', 'Dentissa · Tu sonrisa, nuestra prioridad')
@section('descripcion', 'Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita en línea.')
@section('canonica', rtrim(config('app.url'), '/').'/')

@php
    $horario = collect($contacto['horario'])->map(fn ($horas, $dias) => "{$dias} {$horas}")->implode(' · ');
    // Las filas de la semana con su data-day, el que marca publico.js como «hoy» (domingo = 0).
    $semana = ['Lunes a viernes' => '1-5', 'Sábados' => '6'];
    // El contorno del diente del logo (design/opcion-a-pagina.html), en el viewBox de 520 × 600.
    $diente = 'M260 70c-40-36-104-52-160-24C34 80 22 168 44 252c18 70 36 120 46 196 8 64 22 112 54 112 36 0 44-56 58-118 10-44 26-74 58-74s48 30 58 74c14 62 22 118 58 118 32 0 46-48 54-112 10-76 28-126 46-196 22-84 10-172-56-206-56-28-120-12-160 24z';
    $promesas = [
        ['Qué vamos a hacer', 'Lo vemos juntos, con palabras claras, antes de empezar.', 'M10 14h28M10 24h20M10 34h14'],
        ['Cuánto dura', 'Cuántas citas lleva y cuánto tiempo cada una, para que lo acomodes a tu semana.', null],
        ['Qué opciones tienes', 'Si hay más de un camino, te los mostramos para que decidas con calma.', 'M14 10v28M34 10v28M14 18h20M14 30h20'],
    ];
@endphp

@section('contenido')
<section class="pub-hero" aria-labelledby="titulo">
    <div class="pub-wrap pub-hero__grid">
        <div>
            {{-- M-3: cada línea sube desde su máscara (solo con la clase js y sin movimiento reducido). --}}
            <h1 id="titulo"><span class="pub-ln"><span>Tu sonrisa,</span></span> <span class="pub-ln"><span>nuestra</span></span> <span class="pub-ln"><span>prioridad.</span></span></h1>
            <p class="pub-hero__lead">Odontología integral para toda la familia, en el centro de Lázaro Cárdenas.</p>
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
        </div>
        {{-- La ventana del diente (D19, D20): la foto propia de la marca dentro del contorno del logo. --}}
        <figure class="pub-tooth">
            <svg class="pub-tooth__img" viewBox="0 0 520 600" role="img" aria-label="Revisión dental con espejo, en los tonos rosa de la marca">
                <defs><clipPath id="pub-tooth-clip"><path d="{{ $diente }}"/></clipPath></defs>
                <image href="{{ asset('images/brand/access-recorte.webp') }}" x="0" y="0" width="520" height="600" preserveAspectRatio="xMidYMid slice" clip-path="url(#pub-tooth-clip)"/>
            </svg>
            <svg class="pub-tooth__line" viewBox="0 0 520 600" aria-hidden="true" focusable="false"><path pathLength="1" d="M268 58c-44-40-116-56-174-24C22 72 8 170 34 258c20 70 38 120 48 198 8 66 24 120 62 120 42 0 50-60 64-124 10-44 24-68 52-68"/></svg>
            <figcaption class="pub-tooth__tag"><strong>Antes de empezar,</strong> te explicamos cada paso.</figcaption>
        </figure>
    </div>
</section>

{{-- M-1: la ventana se abre. Sin JavaScript o con movimiento reducido, la foto entera y sin velo. --}}
<section class="pub-open" data-scene="ventana" data-hold="1.4" aria-label="La consulta">
    <div class="pub-open__stage">
        <img class="pub-open__photo" src="{{ asset('images/brand/access.webp') }}" alt="Logotipo de Melissa López N., Odontología integral, sobre una foto de revisión dental" width="900" height="900" loading="lazy">
        <svg class="pub-open__veil" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <defs><mask id="pub-veil"><rect width="1000" height="1000" fill="white"/><g transform="translate(390 330) scale(.42)"><path class="pub-open__hole" fill="black" d="{{ $diente }}"/></g></mask></defs>
            <rect width="1000" height="1000" fill="currentColor" mask="url(#pub-veil)"/>
        </svg>
    </div>
</section>

<section class="pub-sec pub-about" id="nosotros" aria-labelledby="t-nosotros">
    <div class="pub-wrap pub-about__grid">
        <div><span class="pub-kicker-line" aria-hidden="true"></span><h2 id="t-nosotros" data-reveal="mask">Sobre nosotros</h2></div>
        {{-- CA3: el texto aprobado, sin cambios. --}}
        <div class="pub-about__body">
            <p data-reveal>Somos una consulta de odontología integral en el centro de Lázaro Cárdenas.</p>
            <p data-reveal>Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.</p>
            <p data-reveal>Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.</p>
            <a class="c-btn c-btn--ink c-btn--lg" href="{{ route('register') }}" data-reveal>Crear mi cuenta</a>
        </div>
    </div>
</section>

<section class="pub-sec pub-promise" aria-labelledby="t-antes">
    <div class="pub-wrap pub-promise__grid">
        <div class="pub-promise__head">
            <span class="pub-kicker-line" aria-hidden="true"></span>
            <h2 id="t-antes" data-reveal="mask">Antes de cada tratamiento</h2>
            <p data-reveal>Tres cosas que sabrás siempre antes de sentarte en el sillón.</p>
        </div>
        <ol class="pub-promise__list">
            @foreach ($promesas as [$titulo, $texto, $trazo])
                <li class="pub-promise__item" data-reveal>
                    <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                        @if ($trazo)
                            <path d="{{ $trazo }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        @else
                            <circle cx="24" cy="24" r="16" fill="none" stroke="currentColor" stroke-width="3"/><path d="M24 14v10l7 5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        @endif
                    </svg>
                    <h3>{{ $titulo }}</h3>
                    <p>{{ $texto }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- CA4: solo las categorías con algún servicio activo, sin servicios sueltos ni precios. --}}
@if ($categorias->isNotEmpty())
    <section class="pub-sec pub-services" id="servicios" aria-labelledby="t-servicios">
        <div class="pub-wrap">
            <div class="pub-services__head">
                <div>
                    <span class="pub-kicker-line" aria-hidden="true"></span>
                    <h2 id="t-servicios" data-reveal="mask">Nuestros servicios</h2>
                    <p data-reveal>Lo que atendemos, por tipo de tratamiento.</p>
                </div>
                <a class="c-btn c-btn--secondary" href="{{ url('/servicios') }}" data-reveal>Ver todos los servicios <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
            <ul class="pub-index">
                @foreach ($categorias as $categoria => $servicios)
                    <li data-reveal>
                        <a href="{{ url('/servicios') }}#categoria-{{ $loop->index }}">
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
            <svg class="pub-book__mark" viewBox="0 0 520 600" aria-hidden="true" focusable="false"><path d="{{ $diente }}"/></svg>
            <div>
                <h2 id="t-agenda">Agenda sin llamar.</h2>
                <p>Crea tu cuenta y elige el día de tu cita en línea, a la hora que te quede, aunque la consulta esté cerrada.</p>
            </div>
            <a class="c-btn c-btn--primary c-btn--lg" href="{{ route('register') }}">Agendar mi primera cita <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
    </div>
</section>

{{-- CA5: la dirección, la semana, el teléfono, «Cómo llegar» y el mapa de esa misma dirección. --}}
<section class="pub-sec pub-visit" id="ubicacion" aria-labelledby="t-ubicacion">
    <div class="pub-wrap">
        <span class="pub-kicker-line" aria-hidden="true"></span>
        <h2 id="t-ubicacion" data-reveal="mask">Encuéntranos fácilmente</h2>
        <div class="pub-visit__grid">
            <div data-reveal>
                <address class="pub-visit__addr">{{ $contacto['direccion'] }}</address>
                <dl data-semana>
                    @foreach ($contacto['horario'] as $dias => $horas)
                        <div data-day="{{ $semana[$dias] ?? '' }}">
                            <dt>{{ $dias }}</dt>
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
