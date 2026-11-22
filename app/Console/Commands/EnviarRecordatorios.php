<?php

namespace App\Console\Commands;

use App\Agenda\RecordatoriosCitas;
use Illuminate\Console\Command;

/**
 * Spec 052 (plan D1): la revisión periódica de los recordatorios de cita. La programa `routes/console.php`
 * cada minuto en el `scheduler` (spec 020). Sale con código distinto de 0 si algún envío falló, para que
 * quede en la salida del planificador; el detalle, sin datos personales, está en el log.
 */
class EnviarRecordatorios extends Command
{
    protected $signature = 'dentissa:recordatorios';

    protected $description = 'Envía por correo el recordatorio de las citas que empiezan en las próximas 24 h (spec 052)';

    public function handle(RecordatoriosCitas $recordatorios): int
    {
        $cuenta = $recordatorios->enviarPendientes();

        return $cuenta['fallidos'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
