{{-- Spec 041 (D12, D20): carcasa del acceso, la que usan el inicio de sesión y la recuperación de contraseña,
     con el aspecto del inicio de sesión anterior a la spec: panel de marca en el escritorio y formulario.
     No se indexa (SE2.a), sin JavaScript ni recursos de otros sitios. La sección «cabecera» va antes de
     cualquier recurso: ahí ponen `no-referrer` las vistas servidas en la URL del token (TM7). --}}
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
        <img src="{{ asset('images/logo-main.png') }}" alt="Melissa López N. · Odontología integral" width="293" height="214" data-origen="propia">
    </div>
    <div class="panel-copy">
        <p class="panel-tagline">Tu sonrisa,<br>nuestra <em>prioridad</em></p>
        <p class="panel-sub">Gestiona tus citas, consulta tu historial y mantén tu salud dental al día.</p>
    </div>
</aside>
<main class="panel-right">
    <div class="form-container">
        @yield('contenido')
    </div>
</main>
</body>
</html>
