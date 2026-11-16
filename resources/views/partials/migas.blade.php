{{--
    Migas de pan de las pantallas de segundo nivel del panel (spec 046; sistema de diseño, «Migas de pan»).

    Recibe $migas: lista de ['texto' => …, 'url' => …]; el último elemento, la página actual, va sin 'url'.
    Las direcciones salen siempre de route() en la vista: nada se lee de la petición (CA11).
--}}
<nav id="migas" class="migas" aria-label="Migas de pan">
    <ol>
        @foreach($migas as $miga)
            @if($loop->last)
                <li class="migas-actual" aria-current="page">@unless($loop->first)<span class="migas-sep" aria-hidden="true">›</span>@endunless{{ $miga['texto'] }}</li>
            @else
                <li>@unless($loop->first)<span class="migas-sep" aria-hidden="true">›</span>@endunless<a href="{{ $miga['url'] }}">{{ $miga['texto'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
