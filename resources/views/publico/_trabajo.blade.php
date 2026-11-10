{{--
    Foto de trabajo (spec 039, design.md `c3`): la misma en el carrusel de `/` y en el mosaico de
    `/galeria`. Recibe $foto (GaleriaFoto), $total, $sizes y $diferida. La descripción es el `alt` y
    el pie, siempre escapada (CA15); el numeral es decorativo. `src` es el WebP de 800 px y `srcset`
    ofrece los dos (plan → «Entrega de las fotos»); `width` y `height`, los del grande, evitan saltos.
--}}
{{-- Si la foto no pasa de 800 px, el grande y el pequeño miden lo mismo: un solo candidato (/review R9). --}}
@php
    $srcset = route('galeria.foto', [$foto, 800], false).' '.$foto->anchoPequena().'w';
    if ($foto->ancho > $foto->anchoPequena()) {
        $srcset .= ', '.route('galeria.foto', [$foto, 1600], false).' '.$foto->ancho.'w';
    }
@endphp
<figure class="pub-trabajo">
    <div class="pub-trabajo__foto"><img src="{{ route('galeria.foto', [$foto, 800], false) }}" srcset="{{ $srcset }}" sizes="{{ $sizes }}" width="{{ $foto->ancho }}" height="{{ $foto->alto }}" @if($diferida) loading="lazy" @endif alt="{{ $foto->descripcion }}" data-origen="propia"></div>
    <figcaption><span class="pub-trabajo__num" aria-hidden="true">{{ sprintf('%02d', $foto->posicion) }}</span><span class="pub-trabajo__n">Foto {{ $foto->posicion }} de {{ $total }}</span> <span class="pub-trabajo__desc">{{ $foto->descripcion }}</span></figcaption>
</figure>
