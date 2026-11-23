<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recordatorio de cita por correo (spec 052)
    |--------------------------------------------------------------------------
    |
    | Interruptor (plan D6, decisión del usuario): apagado, la revisión periódica
    | `dentissa:recordatorios` no envía nada. Sale apagado en producción y se
    | enciende en app.env con `deploy/aplicar-entorno.sh app` tras comprobarlo
    | con una cita de prueba (Rollout de la 052).
    |
    */

    'activo' => (bool) env('RECORDATORIOS_CORREO', false),

    /*
    |--------------------------------------------------------------------------
    | Tope diario y antelación
    |--------------------------------------------------------------------------
    |
    | CA11: como mucho 250 recordatorios por día de la clínica, para no agotar la
    | cuota de la cuenta de Gmail que también envía los enlaces de la 041 y los
    | avisos de la 032. CA1: el recordatorio sale cuando faltan 24 h o menos.
    |
    */

    'tope_diario' => 250,

    'antelacion_horas' => 24,

];
