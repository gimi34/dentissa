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
        {{-- Menú del móvil por debajo de 900 px: <details> funciona con el teclado y sin JavaScript.
             Solo el icono de hamburguesa; el nombre «Menú» lo lleva aria-label (D15). --}}
        <details class="pub-menu">
            <summary aria-label="Menú"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></summary>
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

{{-- Pie en tres columnas, como el de la landing del colaborador (D15). --}}
<footer class="lb-pie">
    <div class="lb-wrap">
        <div class="lb-pie-cols">
            <div>
                <span class="logo" role="img" aria-label="Melissa López N. · Odontología integral"></span>
                <p>Odontología integral para toda la familia en el centro de Lázaro Cárdenas.</p>
            </div>
            <div>
                <h2>Horarios</h2>
                <dl>
                    @foreach (config('clinica.contacto.horario') as $dias => $horas)
                        <dt>{{ $dias }}</dt>
                        <dd>{{ $horas }}</dd>
                    @endforeach
                </dl>
            </div>
            <div>
                <h2>Contacto</h2>
                <a class="c-link" href="{{ config('clinica.contacto.telefono_enlace') }}">{{ config('clinica.contacto.telefono') }}</a>
                <nav aria-label="Pie">
                    <a class="c-link" href="{{ url('/servicios') }}">Servicios</a>
                    @auth
                        <a class="c-link" href="{{ PaginasPublicasController::panelDelRol(auth()->user()) }}">Ir a mi panel</a>
                    @else
                        <a class="c-link" href="{{ route('register') }}">Crear cuenta</a>
                        <a class="c-link" href="{{ route('login') }}">Iniciar sesión</a>
                    @endauth
                </nav>
            </div>
        </div>
        <p class="lb-pie-legal">© {{ now()->year }} Dentissa</p>
    </div>
</footer>

</body>
</html>
