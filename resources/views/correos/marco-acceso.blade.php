{{-- Spec 041 (D9, D20): marco común de los correos del acceso, con el estilo del inicio de sesión.
     Tablas, porque es lo que entienden los clientes de correo. Sin estilos en la plantilla: la hoja
     correos/acceso.css se pasa a estilos en línea al enviar. El logo se pide a APP_URL, nunca a la
     petición (TM12). --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('asunto')</title>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr><td class="correo-exterior" align="center">
        <table role="presentation" class="correo" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td class="correo-cab"><img class="correo-logo" src="{{ rtrim(config('app.url'), '/') }}/images/logo-main.png" alt="Melissa López N. · Odontología integral" width="140" height="102" data-origen="propia"><p class="correo-lema">Tu sonrisa,<br>nuestra <em>prioridad</em></p></td></tr>
            <tr><td class="correo-cuerpo">
                @yield('cuerpo')
            </td></tr>
            <tr><td class="linea-celda"><div class="linea" aria-hidden="true"></div></td></tr>
            <tr><td class="talon-celda"><dl class="talon">
                @yield('talon')
            </dl></td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
