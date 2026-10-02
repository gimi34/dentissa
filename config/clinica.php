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

];
