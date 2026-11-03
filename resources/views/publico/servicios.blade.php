@extends('layouts.publico')

{{-- Spec 038 (CA6): los servicios activos por categoría, sin precio ni duración. El texto del catálogo sale escapado (TM1).
     Ronda 5 (D19) y ronda 6 (D23): la misma estructura con la etiqueta, la tipografía y los botones de la
     superficie «Sitio público». --}}
@section('titulo', 'Servicios · Dentissa')
@section('descripcion', 'Todos los tratamientos de la consulta Dentissa en Lázaro Cárdenas, de la revisión general a la ortodoncia y las prótesis.')
@section('canonica', rtrim(config('app.url'), '/').'/servicios')

@section('contenido')
<div class="lb-seccion">
    <div class="lb-wrap">
        <div class="lb-cabeza">
            <span class="pub-etiqueta">Catálogo</span>
            <h1>Nuestros servicios</h1>
            <p>Todo lo que atendemos en la consulta, por tipo de tratamiento.</p>
        </div>

        @if ($categorias->isEmpty())
            <p>Pronto publicaremos nuestros servicios.</p>
        @else
            <div class="lb-servicios">
                {{-- Índice en píldoras con el número de servicios de cada categoría (/analyze A14). --}}
                <nav class="lb-indice" aria-label="Categorías">
                    @foreach ($categorias as $categoria => $servicios)
                        <a href="#categoria-{{ $loop->index }}">{{ $categoria }} <small>{{ $servicios->count() }}</small></a>
                    @endforeach
                </nav>
                <div class="lb-lista">
                    @foreach ($categorias as $categoria => $servicios)
                        <section id="categoria-{{ $loop->index }}" aria-labelledby="titulo-categoria-{{ $loop->index }}">
                            <h2 id="titulo-categoria-{{ $loop->index }}">{{ $categoria }}</h2>
                            <dl>
                                @foreach ($servicios as $servicio)
                                    <dt>{{ $servicio->nombre }}</dt>
                                    <dd>{{ $servicio->descripcion }}</dd>
                                @endforeach
                            </dl>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="lb-volver"><a class="c-btn c-btn--secondary" href="{{ url('/') }}">Volver al inicio</a></p>
    </div>
</div>
@endsection
