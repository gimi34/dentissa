{{-- Spec 044 (CA7–CA10; D2, D4; TM2, TM6): plantilla común de las páginas de error, sobre la carcasa del error
     genérico de la spec 020 (`errors/auditoria-no-disponible`, `errores.css`). Documento propio, sin el layout
     autenticado y sin consultas a la base: solo `config()`, `route()`, `asset()` y el identificador de la
     petición; la sesión la mira cada página con `auth()->hasUser()`. No se indexa (SE2.b, SE4.a). Ninguna página
     imprime el texto de la excepción salvo la 403, que muestra el de `Response::deny()` (D5). Destinos fijos (TM1). --}}
@php
    $contacto = (array) config('clinica.contacto');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('titulo') · Dentissa</title>
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/errores.css') }}?v={{ filemtime(public_path('css/errores.css')) }}" rel="stylesheet">
</head>
<body>
<div class="acceso">
    <header class="franja"><a class="logo-enlace" href="{{ route('inicio') }}" aria-label="Ir a la página de inicio"><img src="{{ asset('images/logo-main.png') }}" alt="" data-origen="propia"></a></header>
    <aside class="panel"><a class="logo-enlace" href="{{ route('inicio') }}" aria-label="Ir a la página de inicio"><img src="{{ asset('images/logo-main.png') }}" alt="" data-origen="propia"></a><p class="panel-texto"><b>Te ayudamos a seguir</b>Desde aquí puedes volver al inicio o seguir donde estabas.</p></aside>
    <main class="lado">
        <div class="carnet">
            <div class="carnet-cuerpo">
                <span class="etiqueta">@yield('etiqueta')</span>
                <h1>@yield('h1')</h1>
                <p class="sub">@yield('sub')</p>
                @hasSection('codigo')
                    <div class="codigo">
                        <p class="codigo-etiqueta">Identificador de la petición</p>
                        <p class="codigo-valor">{{ (string) \Illuminate\Support\Facades\Context::get('request_id') }}</p>
                        <p class="codigo-ayuda">Si vuelve a pasar, da este identificador a soporte.</p>
                    </div>
                @endif
                @yield('acciones')
            </div>
            @if (! empty($contacto['telefono']))
                <dl class="talon"><dt>¿Necesitas ayuda?</dt><dd>Llama a la consulta: <a class="tel" href="{{ $contacto['telefono_enlace'] ?? '' }}">{{ $contacto['telefono'] }}</a></dd></dl>
            @endif
        </div>
    </main>
</div>
</body>
</html>
