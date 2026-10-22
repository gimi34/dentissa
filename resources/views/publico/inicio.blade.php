@extends('layouts.publico')

{{-- Spec 038: página de inicio pública (CA1, CA3–CA5), rediseño de la ronda 3 (D16): portada a sangre,
     hoja de «Sobre nosotros», tarjetas de categoría, franja del horario, ubicación 3:2. Todo el texto
     del catálogo sale escapado (TM1); el contenido está completo sin JavaScript (CA8). --}}
@section('titulo', 'Dentissa · Tu sonrisa, nuestra prioridad')
@section('descripcion', 'Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita en línea.')
@section('canonica', rtrim(config('app.url'), '/').'/')

@php
    $horario = collect($contacto['horario'])->map(fn ($horas, $dias) => "{$dias} {$horas}")->implode(' · ');
@endphp

@section('contenido')
{{-- M-1: la portada queda fija una pantalla y «Sobre nosotros» sube encima (solo CSS). --}}
<div class="hoja" data-escena="hoja-sobre-imagen">
    <section class="portada" aria-labelledby="titulo-portada">
        {{-- Foto libre acreditada en system.md → «Imágenes» (D17); decorativa y en la primera pantalla. --}}
        <img class="portada-foto"
             src="{{ asset('images/portada-1600.webp') }}"
             srcset="{{ asset('images/portada-800.webp') }} 800w, {{ asset('images/portada-1600.webp') }} 1600w"
             sizes="100vw" width="1600" height="1600" alt="" fetchpriority="high">
        <div class="portada-velo" aria-hidden="true"></div>
        <div class="lb-wrap portada-texto">
            {{-- Decorativo: la marca ya la nombra la cabecera (/review R21). --}}
            <span class="logo" aria-hidden="true"></span>
            <h1 class="t-portada" id="titulo-portada">Tu sonrisa,<br> nuestra <em>prioridad.</em></h1>
            <p class="portada-lead">Gestiona tus citas, consulta tu historial y mantén tu salud dental al día con nuestra plataforma moderna.</p>
            <p><a class="c-btn c-btn--primary" href="{{ route('register') }}">Agendar mi primera cita</a></p>
            {{-- Cosa viva (M-5): publico.js escribe «¿Abierto ahora?» con este horario y la hora de la
                 consulta; sin JavaScript se lee el horario completo y el teléfono (CA1). --}}
            <p class="estado" data-live data-horario="{{ json_encode($contacto['horario_semanal']) }}" data-zona="{{ config('clinica.zona_horaria') }}">
                <span class="estado-ahora" aria-live="polite"></span>
                <span>{{ $horario }}</span>
                <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>
            </p>
        </div>
    </section>

    <section class="lb-seccion hoja-capitulo" aria-labelledby="titulo-nosotros">
        <div class="lb-wrap lb-nosotros">
            <h2 class="t-h2" id="titulo-nosotros" data-reveal="line">Sobre nosotros</h2>
            <p data-reveal>Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.</p>
            <p data-reveal>Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.</p>
        </div>
    </section>
</div>

{{-- Avance de servicios: una tarjeta por categoría activa con su número de tratamientos, sin
     servicios sueltos ni precios; sin activos, no se muestra (CA4). Cada tarjeta lleva a su sección
     de /servicios, con el mismo índice de porCategoria() (/analyze A26). --}}
@if ($categorias->isNotEmpty())
    <section class="lb-seccion lb-seccion--blanca" id="servicios" aria-labelledby="titulo-servicios">
        <div class="lb-wrap">
            <div class="lb-cabeza-centro">
                <h2 class="t-h2" id="titulo-servicios" data-reveal="line">Nuestros servicios</h2>
                <p data-reveal>Todo lo que atendemos en la consulta, por tipo de tratamiento.</p>
            </div>
            <ul class="tarjetas">
                @foreach ($categorias as $categoria => $servicios)
                    <li data-reveal>
                        <a class="tarjeta" href="{{ url('/servicios') }}#categoria-{{ $loop->index }}">
                            @include('partials.icono-categoria', ['categoria' => $categoria])
                            <h3>{{ $categoria }}</h3>
                            <p>{{ $servicios->count() }} {{ $servicios->count() === 1 ? 'tratamiento' : 'tratamientos' }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="centro" data-reveal><a class="c-btn c-btn--secondary" href="{{ url('/servicios') }}">Ver todos los servicios</a></p>
        </div>
    </section>
@endif

{{-- M-2: la franja del horario, decorativa (el horario está en la portada, la ubicación y el pie).
     Solo se mueve con JavaScript, que muestra su botón de pausa (WCAG 2.2.2, /analyze A23). --}}
<div class="marquesina-zona">
    <div class="marquesina" aria-hidden="true">
        <div class="marquesina-pista">
            <span>{{ $horario }} · Agenda en línea a cualquier hora ·</span>
            <span>{{ $horario }} · Agenda en línea a cualquier hora ·</span>
        </div>
    </div>
    <button class="marquesina-pausa" type="button" aria-label="Pausar la franja del horario" aria-pressed="false" hidden>
        <svg class="icono-pausar" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5v14M16 5v14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <svg class="icono-reanudar" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5l11 7-11 7z" fill="currentColor"/></svg>
    </button>
</div>

<section class="lb-seccion ubicacion-sec" id="ubicacion" aria-labelledby="titulo-ubicacion">
    <div class="lb-wrap ubicacion">
        <div class="ubicacion-datos">
            <h2 class="t-h2" id="titulo-ubicacion" data-reveal="line">Encuéntranos fácilmente</h2>
            <p class="ubicacion-sub" data-reveal>Estamos en el centro de Lázaro Cárdenas.</p>
            <ul class="datos">
                <li data-reveal>
                    <svg class="dato-icono" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    <address>{{ $contacto['direccion'] }}</address>
                </li>
                <li data-reveal>
                    <svg class="dato-icono" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h3.3l1.6 4.4-2 1.3a11 11 0 0 0 6.4 6.4l1.3-2 4.4 1.6V19a1.6 1.6 0 0 1-1.7 1.6C10.4 20.1 3.9 13.6 3.4 5.7A1.6 1.6 0 0 1 5 4z"/></svg>
                    <a class="c-link" href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>
                </li>
                <li data-reveal>
                    <svg class="dato-icono" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>
                    <span>{{ $horario }}</span>
                </li>
            </ul>
            {{-- TM5: la pestaña nueva no puede tocar esta página. --}}
            <p data-reveal><a class="c-btn c-btn--secondary" href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($contacto['direccion']) }}" target="_blank" rel="noopener noreferrer" aria-label="Cómo llegar (se abre en una pestaña nueva)">Cómo llegar</a></p>
        </div>
        <div class="ubicacion-mapa" data-reveal="mask">
            {{-- Spec 038 (D6; TM4): solo se carga al acercarse y solo envía el dominio a Google. --}}
            <iframe class="mapa"
                    src="https://www.google.com/maps?q={{ rawurlencode($contacto['direccion']) }}&amp;output=embed"
                    title="Mapa de Google con la ubicación de la consulta"
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
            <p class="nota-mapa">El mapa lo sirve Google.</p>
        </div>
    </div>
</section>
@endsection
