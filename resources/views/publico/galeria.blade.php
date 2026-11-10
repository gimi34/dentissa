@extends('layouts.publico')

{{-- Spec 039 (CA2, CA6, CA7; design.md `c3`): todas las fotos de trabajos en orden, con la estructura de
     `/servicios`. Igual con o sin sesión. Las tres primeras cargan sin esperar y el resto en diferido; los
     `sizes` siguen los cortes del mosaico (plan → «Entrega de las fotos»). --}}
@section('titulo', 'Nuestros trabajos · Dentissa')
@section('descripcion', 'Fotos de tratamientos hechos en la consulta Dentissa en Lázaro Cárdenas.')
@section('canonica', rtrim(config('app.url'), '/').'/galeria')

@section('contenido')
<div class="lb-seccion pub-galeria">
    <div class="lb-wrap">
        <div class="lb-cabeza">
            <span class="pub-etiqueta">Galería</span>
            <h1>Nuestros trabajos</h1>
            <p>Fotos de tratamientos hechos en la consulta.</p>
        </div>

        @if ($fotos->isEmpty())
            <div class="pub-galeria__vacia">
                <p>Aún no hay fotos de nuestros trabajos.</p>
                <a class="c-btn c-btn--secondary" href="{{ url('/') }}">Volver al inicio</a>
            </div>
        @else
            <ol class="pub-mosaico" role="list">
                @foreach ($fotos as $foto)
                    <li>@include('publico._trabajo', [
                        'foto' => $foto,
                        'total' => $fotos->count(),
                        'sizes' => $loop->first
                            ? '(min-width: 1200px) 800px, (min-width: 900px) 66vw, (min-width: 560px) 50vw, 100vw'
                            : '(min-width: 900px) 33vw, (min-width: 560px) 50vw, 100vw',
                        'diferida' => $loop->index >= 3,
                    ])</li>
                @endforeach
            </ol>
            <p class="lb-volver"><a class="c-btn c-btn--secondary" href="{{ url('/') }}">Volver al inicio</a></p>
        @endif
    </div>
</div>
@endsection
