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
    | Contacto de la consulta
    |--------------------------------------------------------------------------
    |
    | Lo que muestra la página de inicio pública (spec 038, CA5): la dirección
    | (también la del mapa y la de «Cómo llegar»), el teléfono en texto y como
    | enlace `tel:`, y el horario. Son datos ficticios del proyecto académico.
    |
    */

    'contacto' => [
        'direccion' => 'Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950',
        'telefono' => '753 000 0000',
        'telefono_enlace' => 'tel:+527530000000',
        'horario' => ['Lunes a viernes 9:00–20:00', 'Sábados 9:00–14:00'],
    ],

];
