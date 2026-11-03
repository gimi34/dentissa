{{-- Spec 041 (D12): carcasa del acceso, la que usan el inicio de sesión y la recuperación de contraseña.
     Franja con el logo en el teléfono, panel de marca en el escritorio y carnet. No se indexa (SE2.a),
     sin JavaScript ni recursos de otros sitios. La sección «cabecera» va antes de cualquier recurso:
     ahí ponen `no-referrer` las vistas servidas en la URL del token (TM7). --}}
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
<div class="acceso">
    <header class="franja"><img src="{{ asset('images/logo-main.png') }}" alt="Melissa López N. · Odontología integral" width="293" height="214" data-origen="propia"></header>
    <aside class="panel">
        <img src="{{ asset('images/logo-main.png') }}" alt="Melissa López N. · Odontología integral" width="293" height="214" data-origen="propia">
        <p class="panel-texto">
            @hasSection('panel')
                @yield('panel')
            @else
                <b>Vuelve a entrar en un par de minutos</b>Te enviamos un enlace a tu correo para elegir una contraseña nueva. Tus citas y tu historial no cambian.
            @endif
        </p>
    </aside>
    <main class="lado">
        <div class="carnet">
            @yield('carnet')
        </div>
    </main>
</div>
</body>
</html>
