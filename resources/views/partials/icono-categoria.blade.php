{{-- Spec 038 (D16, /analyze A26): icono decorativo de la tarjeta de categoría, dibujado en código
     (24 × 24, trazo 1.6). La clave se normaliza como en Servicio::porCategoria(); las categorías sin
     icono propio llevan la muela. --}}
@php
    $clave = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim($categoria)));
    $icono = in_array($clave, ['cirugia', 'diagnostico', 'endodoncia', 'ortodoncia', 'preventivo', 'prostodoncia', 'restauracion'], true) ? $clave : 'muela';
    $muela = 'M9 3C6.6 3 5 5 5 7.6c0 3.2 1.1 9.4 2.8 12.4.5.9 1.7.7 2-.3l1-3.9c.3-1.1 1.9-1.1 2.2 0l1 3.9c.3 1 1.5 1.2 2 .3C17.9 17 19 10.8 19 7.6 19 5 17.4 3 15 3c-1.3 0-2.1.8-3 .8S10.3 3 9 3z';
@endphp
<svg class="icono" data-icono="{{ $icono }}" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    @switch($icono)
        @case('cirugia')
            <path d="M4 18l8.5-8.5 2.5 2.5L6.5 20.5z"/><path d="M12.5 9.5l5-5a2 2 0 0 1 3 3l-5 5"/>
            @break
        @case('diagnostico')
            <circle cx="10.5" cy="10.5" r="5.5"/><path d="M15 15l5 5"/>
            @break
        @case('endodoncia')
            <path d="{{ $muela }}"/><path d="M12 8v7"/>
            @break
        @case('ortodoncia')
            <path d="{{ $muela }}"/><path d="M4 10.5h16"/><path d="M10 9.5h4v2h-4z"/>
            @break
        @case('preventivo')
            <path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/>
            @break
        @case('prostodoncia')
            <path d="M4 9l3.5 3 4.5-6 4.5 6L20 9l-2 10H6z"/>
            @break
        @case('restauracion')
            <path d="{{ $muela }}"/><path d="M12 7.5v4M10 9.5h4"/>
            @break
        @default
            <path d="{{ $muela }}"/>
    @endswitch
</svg>
