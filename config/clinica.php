<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zona horaria de la clínica
    |--------------------------------------------------------------------------
    |
    | Las citas guardan su fecha y hora en la hora local de la clínica, mientras
    | que la aplicación trabaja en UTC (config/app.php). Esta zona se usa para
    | decidir si una cita ya empezó (spec 026). Debe ser un identificador válido
    | de zona horaria de PHP.
    |
    */

    'zona_horaria' => env('CLINICA_ZONA_HORARIA', 'America/Mexico_City'),

    /*
    |--------------------------------------------------------------------------
    | Contacto de la clínica
    |--------------------------------------------------------------------------
    |
    | El teléfono y el horario que publica la página de inicio, con la forma
    | de la spec 038 para que la consulta tenga una sola fuente. Los usa el
    | error genérico de la spec 020 y los correos de la 041 (D16 de la 041).
    |
    */

    'contacto' => [
        'telefono' => '753 162 5220',
        'telefono_enlace' => 'tel:+527531625220',
        'horario' => [
            'Lunes a viernes' => '9:00–20:00',
            'Sábados' => '9:00–14:00',
        ],
    ],

];
