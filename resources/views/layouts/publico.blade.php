@use('App\Http\Controllers\PaginasPublicasController')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Spec 038 (CA10): título, descripción y canónica propios de cada página. --}}
    <title>@yield('titulo')</title>
    <meta name="description" content="@yield('descripcion')">
    <link rel="canonical" href="@yield('canonica')">
    {{-- app.css por los tokens de :root; publico.css, Lexend autoalojada y los componentes públicos. Sin scripts (CA8). --}}
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/publico.css') }}?v={{ filemtime(public_path('css/publico.css')) }}" rel="stylesheet">
</head>
<body class="publico">

<header class="pub-cabecera">
    <div class="pub-bar">
        <a class="pub-inicio" href="{{ url('/') }}" aria-label="Inicio">
            <span class="logo" role="img" aria-label="Melissa López N. · Odontología integral"></span>
        </a>
        <nav class="pub-nav-d" aria-label="Secciones">
            <ul>
                <li><a href="{{ url('/servicios') }}">Servicios</a></li>
                <li><a href="{{ url('/') }}#ubicacion">Ubicación</a></li>
            </ul>
            @auth
                <a class="c-btn c-btn--secondary" href="{{ PaginasPublicasController::panelDelRol(auth()->user()) }}">Ir a mi panel</a>
            @else
                <a class="c-btn c-btn--secondary" href="{{ route('login') }}">Iniciar sesión</a>
            @endauth
        </nav>
        {{-- Menú del móvil por debajo de 900 px: <details> funciona con el teclado y sin JavaScript. --}}
        <details class="pub-menu">
            <summary>Menú</summary>
            <ul>
                <li><a href="{{ url('/servicios') }}">Servicios</a></li>
                <li><a href="{{ url('/') }}#ubicacion">Ubicación</a></li>
                @auth
                    <li><a href="{{ PaginasPublicasController::panelDelRol(auth()->user()) }}">Ir a mi panel</a></li>
                @else
                    <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
                    <li><a href="{{ route('register') }}">Crear cuenta</a></li>
                @endauth
            </ul>
        </details>
    </div>
</header>

<main>
    @yield('contenido')
</main>

<footer class="lb-pie">
    <div class="lb-wrap">
        <span class="logo" role="img" aria-label="Melissa López N. · Odontología integral"></span>
        <nav aria-label="Pie">
            <a class="c-link" href="{{ url('/servicios') }}">Servicios</a>
            @auth
                <a class="c-link" href="{{ PaginasPublicasController::panelDelRol(auth()->user()) }}">Ir a mi panel</a>
            @else
                <a class="c-link" href="{{ route('register') }}">Crear cuenta</a>
                <a class="c-link" href="{{ route('login') }}">Iniciar sesión</a>
            @endauth
        </nav>
        <p>© {{ now()->year }} Dentissa</p>
    </div>
</footer>

</body>
</html>
