<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recuperar contraseña (spec 041)
    |--------------------------------------------------------------------------
    |
    | Interruptor del flujo «¿Olvidaste tu contraseña?» (plan D8). Apagado, las
    | rutas de /contrasena no se registran (404) y el inicio de sesión no muestra
    | el enlace. Depende del correo SMTP de producción: se enciende en app.env
    | después de comprobar que el correo llega (Rollout de la 041).
    |
    */

    'recuperar_contrasena' => (bool) env('RECUPERAR_CONTRASENA', false),

    /*
    |--------------------------------------------------------------------------
    | Topes de enlaces de restablecimiento
    |--------------------------------------------------------------------------
    |
    | Spec 041 (CA14, plan D22). Además del límite por minuto: protegen un buzón
    | de que lo bombardeen y la cuota de la cuenta de Gmail, que también envía los
    | avisos de producción. El exceso recibe el mismo mensaje genérico, sin correo.
    |
    */

    'enlaces_por_cuenta_por_hora' => 5,

    'enlaces_por_dia' => 200,

];
