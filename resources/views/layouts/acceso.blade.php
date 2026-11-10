{{-- Spec 041 (D12, D20): carcasa del acceso, la que usan el inicio de sesión y la recuperación de contraseña,
     con el aspecto del inicio de sesión anterior a la spec: panel de marca en el escritorio y formulario.
     No se indexa (SE2.a), sin JavaScript ni recursos de otros sitios. La sección «cabecera» va antes de
     cualquier recurso: ahí ponen `no-referrer` las vistas servidas en la URL del token (TM7).
     Spec 044 (CA1, CA2): «Volver al inicio» es lo primero del formulario a todos los anchos (por debajo de
     769 px el panel de marca no se ve) y el logo del panel lleva a la página de inicio; destinos fijos (TM1).
     D11: la recuperación de contraseña declara `@section('volver', 'login')` y su salida es «Volver a iniciar sesión». --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    @yield('cabecera')
    <title>Dentissa · @yield('titulo')</title>
    <link href="{{ asset('css/acceso.css') }}?v={{ filemtime(public_path('css/acceso.css')) }}" rel="stylesheet">
</head>
<body>
<aside class="panel-left">
    <div class="deco-circle deco-1" aria-hidden="true"></div>
    <div class="deco-circle deco-2" aria-hidden="true"></div>
    <div class="deco-circle deco-3" aria-hidden="true"></div>
    <div class="panel-illustration">
        <a class="logo-enlace" href="{{ route('inicio') }}" aria-label="Ir a la página de inicio"><img src="{{ asset('images/logo-main.png') }}" alt="" width="293" height="214" data-origen="propia"></a>
    </div>
    <div class="panel-copy">
        <p class="panel-tagline">Tu sonrisa,<br>nuestra <em>prioridad</em></p>
        <p class="panel-sub">Gestiona tus citas, consulta tu historial y mantén tu salud dental al día.</p>
    </div>
</aside>
<main class="panel-right">
    <div class="form-container">
        @if (trim($__env->yieldContent('volver')) === 'login')
            <a class="enlace-inicio" href="{{ route('login') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Volver a iniciar sesión</a>
        @else
            <a class="enlace-inicio" href="{{ route('inicio') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Volver al inicio</a>
        @endif
        @yield('contenido')
    </div>
</main>
</body>
</html>
