@extends('layouts.publico')

{{-- Spec 038: página de inicio pública (CA1, CA3–CA5). Todo el texto del catálogo sale escapado (TM1). --}}
@section('titulo', 'Dentissa · Tu sonrisa, nuestra prioridad')
@section('descripcion', 'Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita en línea.')
@section('canonica', rtrim(config('app.url'), '/').'/')

@section('contenido')
<div class="lb-wrap">
    <div class="lb-placa">
        <div class="lb-placa-texto">
            {{-- Decorativo: la marca ya la nombra la cabecera (/review R21). --}}
            <span class="logo logo--grande" aria-hidden="true"></span>
            <h1 class="t-h1">Tu sonrisa, nuestra prioridad.</h1>
            <p class="t-body">Gestiona tus citas, consulta tu historial y mantén tu salud dental al día con nuestra plataforma moderna.</p>
            <div class="lb-acciones">
                <a class="c-btn c-btn--primary" href="{{ route('register') }}">Agendar mi primera cita</a>
            </div>
        </div>
        <div class="lb-aviso">
            <h2>Aviso de la puerta</h2>
            <dl>
                @foreach ($contacto['horario'] as $dias => $horas)
                    <dt>{{ $dias }}</dt>
                    <dd>{{ $horas }}</dd>
                @endforeach
            </dl>
            <a class="c-btn c-btn--secondary lb-llamar" href="{{ $contacto['telefono_enlace'] }}">Llamar al {{ $contacto['telefono'] }}</a>
            {{-- «Cómo llegar» solo va en la placa de la dirección (/analyze A15). --}}
            <a class="c-link" href="#ubicacion">Ver la ubicación</a>
        </div>
    </div>
</div>

<section class="lb-seccion" aria-labelledby="titulo-nosotros">
    <div class="lb-wrap lb-nosotros">
        <h2 class="t-h2" id="titulo-nosotros">Sobre nosotros</h2>
        <div>
            <p>Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.</p>
            <p>Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.</p>
        </div>
    </div>
</section>

{{-- Avance de servicios: solo los nombres de las categorías activas; sin activos, no se muestra (CA4). --}}
@if ($categorias->isNotEmpty())
    <section class="lb-seccion lb-seccion--blanca" id="servicios" aria-labelledby="titulo-servicios">
        <div class="lb-wrap">
            <div class="lb-cabeza lb-cabeza-fila">
                <h2 class="t-h2" id="titulo-servicios">Nuestros servicios</h2>
                <a class="c-link" href="{{ url('/servicios') }}">Ver todos los servicios</a>
            </div>
            <ul class="lb-puertas">
                @foreach ($categorias->keys() as $categoria)
                    <li><a href="{{ url('/servicios') }}#categoria-{{ $loop->index }}">{{ $categoria }}</a></li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

<section class="lb-seccion" id="ubicacion" aria-labelledby="titulo-ubicacion">
    <div class="lb-wrap">
        <div class="lb-cabeza">
            <h2 class="t-h2" id="titulo-ubicacion">Encuéntranos fácilmente</h2>
        </div>
        <div class="lb-calle">
            {{-- Spec 038 (D6; TM4): solo se carga al acercarse y solo envía el dominio a Google. --}}
            <iframe class="mapa"
                    src="https://www.google.com/maps?q={{ rawurlencode($contacto['direccion']) }}&amp;output=embed"
                    title="Mapa de Google con la ubicación de la consulta"
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
            <div class="lb-placa-dir">
                <address>{{ $contacto['direccion'] }}</address>
                <a class="c-link" href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>
                {{-- TM5: la pestaña nueva no puede tocar esta página. --}}
                <a class="c-link" href="https://www.google.com/maps/dir/?api=1&amp;destination={{ rawurlencode($contacto['direccion']) }}" target="_blank" rel="noopener noreferrer" aria-label="Cómo llegar (se abre en una pestaña nueva)">Cómo llegar</a>
            </div>
        </div>
        <p class="nota-mapa">El mapa lo sirve Google.</p>
    </div>
</section>
@endsection
