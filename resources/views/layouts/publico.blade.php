@use('App\Http\Controllers\PaginasPublicasController')
@php
    $contacto = config('clinica.contacto');
    $horario = collect($contacto['horario'])->map(fn ($horas, $dias) => "{$dias} {$horas}")->implode(' · ');
    $panel = auth()->check() ? PaginasPublicasController::panelDelRol(auth()->user()) : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Spec 038 (CA10): título, descripción y canónica propios de cada página. --}}
    <title>@yield('titulo')</title>
    <meta name="description" content="@yield('descripcion')">
    <link rel="canonical" href="@yield('canonica')">
    {{-- app.css por los tokens de :root; publico.css, las fuentes autoalojadas y la superficie «Sitio público». --}}
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/publico.css') }}?v={{ filemtime(public_path('css/publico.css')) }}" rel="stylesheet">
    {{-- Un solo script propio, con defer y sin código en línea: la página está completa sin él (CA8, D16). --}}
    <script src="{{ asset('js/publico.js') }}?v={{ filemtime(public_path('js/publico.js')) }}" defer></script>
</head>
<body class="publico">

{{-- Ronda 5 (D19): cabecera fija en --p50 con el filete al bajar; «Menú» por debajo de 900 px. --}}
<header class="pub-bar" id="top">
    <div class="pub-wrap pub-bar__in">
        <a class="pub-brand" href="{{ url('/') }}" aria-label="Inicio">
            <span class="logo" role="img" aria-label="Melissa López N. · Odontología integral"></span>
        </a>
        <nav class="pub-nav" aria-label="Secciones">
            <a href="{{ url('/servicios') }}">Servicios</a>
            <a href="{{ url('/') }}#ubicacion">Ubicación</a>
            @if ($panel)
                <a class="c-btn c-btn--ink" href="{{ $panel }}">Ir a mi panel</a>
            @else
                <a href="{{ route('login') }}">Iniciar sesión</a>
                <a class="c-btn c-btn--ink" href="{{ route('register') }}">Crear cuenta</a>
            @endif
        </nav>
        {{-- <details> funciona con el teclado y sin JavaScript; con él, Escape lo cierra (publico.js).
             El panel es la nav «Secciones» del móvil (/review R18). --}}
        <details class="pub-menu">
            <summary>Menú <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 8h16M4 16h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></summary>
            <nav class="pub-menu__panel" aria-label="Secciones">
                <a href="{{ url('/') }}#nosotros">Sobre nosotros</a>
                <a href="{{ url('/servicios') }}">Servicios</a>
                <a href="{{ url('/') }}#ubicacion">Ubicación y contacto</a>
                @if ($panel)
                    <a class="c-btn c-btn--primary" href="{{ $panel }}">Ir a mi panel</a>
                @else
                    <a href="{{ route('login') }}">Iniciar sesión</a>
                    <a class="c-btn c-btn--primary" href="{{ route('register') }}">Crear cuenta</a>
                @endif
            </nav>
        </details>
    </div>
</header>

<main>
    @yield('contenido')
</main>

{{-- Pie de la ronda 5: enlaces, horario, teléfono, dirección y el nombre a todo el ancho (decorativo). --}}
<footer class="pub-foot">
    <div class="pub-wrap">
        <nav aria-label="Pie">
            <ul class="pub-foot__links">
                <li><a href="{{ url('/') }}#nosotros">Sobre nosotros</a></li>
                <li><a href="{{ url('/servicios') }}">Servicios</a></li>
                <li><a href="{{ url('/') }}#ubicacion">Ubicación</a></li>
                @if ($panel)
                    <li><a href="{{ $panel }}">Ir a mi panel</a></li>
                @else
                    <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
                    <li><a href="{{ route('register') }}">Crear cuenta</a></li>
                @endif
            </ul>
        </nav>
        <div class="pub-foot__info">
            <span>{{ $horario }}</span>
            <a href="{{ $contacto['telefono_enlace'] }}">{{ $contacto['telefono'] }}</a>
            <span>{{ $contacto['direccion'] }}</span>
        </div>
        <p class="pub-foot__word" aria-hidden="true">Dentissa</p>
        <div class="pub-foot__small">
            <span>Melissa López N. · Odontología integral</span>
            <span>© {{ now()->year }} Dentissa</span>
        </div>
    </div>
</footer>

</body>
</html>
