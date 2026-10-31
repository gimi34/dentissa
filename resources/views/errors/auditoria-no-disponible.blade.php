{{-- Spec 020 (CA11; TM7, A52): el registro de auditoría no pudo guardar una acción crítica y la acción
     se detuvo. Página 503 genérica según design.md (c4/error.html): documento propio, sin el layout
     autenticado, igual con y sin sesión, sin detalles internos aunque APP_DEBUG=true y solo con
     recursos propios. El teléfono sale de clinica.contacto, la clave que comparte con la 041 (D23). --}}
@php
    $peticion = (string) \Illuminate\Support\Facades\Context::get('request_id');
    $contacto = (array) config('clinica.contacto');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>No se realizó la acción · Dentissa</title>
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/errores.css') }}?v={{ filemtime(public_path('css/errores.css')) }}" rel="stylesheet">
</head>
<body>
<div class="acceso">
    <header class="franja"><img src="{{ asset('images/logo-main.png') }}" alt="Melissa López N. · Odontología integral" data-origen="propia"></header>
    <aside class="panel"><img src="{{ asset('images/logo-main.png') }}" alt="Melissa López N. · Odontología integral" data-origen="propia"><p class="panel-texto"><b>No se guardó nada a medias</b>La acción no llegó a hacerse: tus datos siguen como estaban.</p></aside>
    <main class="lado">
        <div class="carnet">
            <div class="carnet-cuerpo">
                <span class="etiqueta">Error 503 · servicio no disponible</span>
                <h1>No se realizó la acción</h1>
                <p class="sub">Tuvimos un problema de nuestro lado y no se hizo ningún cambio. Inténtalo de nuevo en unos minutos.</p>
                <div class="codigo">
                    <p class="codigo-etiqueta" id="codigo-etiqueta">Identificador de la petición</p>
                    <p class="codigo-valor">{{ $peticion }}</p>
                    <p class="codigo-ayuda">Si vuelve a pasar, da este identificador a soporte.</p>
                </div>
                <a class="btn btn-primario" href="/">Volver al inicio</a>
            </div>
            @if(! empty($contacto['telefono']))
                <dl class="talon"><dt>¿Es urgente?</dt><dd>Llama a la consulta: <a class="tel" href="{{ $contacto['telefono_enlace'] ?? '' }}">{{ $contacto['telefono'] }}</a></dd></dl>
            @endif
        </div>
    </main>
</div>
</body>
</html>
