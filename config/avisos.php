<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Avisos de producción (spec 032)
    |--------------------------------------------------------------------------
    |
    | El comando dentissa:vigilar, que un temporizador de systemd lanza cada
    | minuto, cuenta los errores del servidor en el log de acceso de nginx y los
    | fallos de inicio de sesión del canal de log `seguridad`, y avisa por correo
    | al responsable. Los umbrales son los de la spec 032 (CA1, CA2, CA4).
    |
    */

    // Correo de siempre del responsable; los avisos salen de la cuenta de
    // envío de MAIL_FROM_ADDRESS (plan D9).
    'destino' => env('AVISOS_CORREO'),

    // Más del 1 % de respuestas 5xx en la ventana (CA1).
    'umbral_errores' => 0.01,

    // Más de 10 fallos de inicio de sesión en la ventana por origen o por
    // cuenta (CA2).
    'umbral_fallos' => 10,

    'ventana_minutos' => 5,

    // Como mucho un correo por tipo de aviso en este tiempo (CA4).
    'silencio_minutos' => 30,

    // Log de acceso de nginx, montado en solo lectura en app (plan D10).
    'log_acceso' => '/var/log/nginx/dentissa-access.log',

    // Ruta base del canal `seguridad` (config/logging.php); el canal diario
    // escribe seguridad-AAAA-MM-DD.log junto a ella.
    'log_seguridad' => storage_path('logs/seguridad.log'),

    'estado' => storage_path('app/avisos/estado.json'),

    // Bytes del log de acceso que se leen como mucho en cada ejecución; lo que
    // no cabe se lee en la siguiente (plan D8, TM12).
    'tope_bytes' => 50 * 1024 * 1024,

];
