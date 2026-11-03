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

];
