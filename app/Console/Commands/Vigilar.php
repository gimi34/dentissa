<?php

namespace App\Console\Commands;

use App\Avisos\Vigilante;
use App\Mail\AvisoProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Spec 032 (CA1, CA2, CA4, CA6; TM10; plan D8, D11): avisos de producción.
 *
 * El temporizador dentissa-vigilar.timer lo lanza cada minuto. Sale con código
 * distinto de 0 si algún aviso no se pudo enviar, para que el fallo quede en el
 * journal del temporizador. Con --prueba envía un aviso de prueba a
 * AVISOS_CORREO y termina (procedimiento de la guía, CA6).
 */
class Vigilar extends Command
{
    protected $signature = 'dentissa:vigilar
        {--prueba : Envía un aviso de prueba a AVISOS_CORREO y termina}';

    protected $description = 'Cuenta los errores del servidor y los fallos de acceso y avisa por correo';

    public function handle(Vigilante $vigilante): int
    {
        if ($this->option('prueba')) {
            return $this->enviarPrueba();
        }

        if (! $vigilante->ejecutar()) {
            $this->error('Algún aviso no se pudo enviar; se reintenta en la próxima ejecución (storage/logs/laravel.log).');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function enviarPrueba(): int
    {
        $ahora = CarbonImmutable::now();

        try {
            Mail::to(config('avisos.destino'))->send(new AvisoProduccion('prueba', 0, $ahora, $ahora));
        } catch (Throwable) {
            // Sin el mensaje: puede llevar el usuario del SMTP.
            Log::warning('aviso no enviado', ['tipo' => 'prueba']);
            $this->error('No se pudo enviar el aviso de prueba (revisa las variables de correo de app.env).');

            return self::FAILURE;
        }

        $this->info('Aviso de prueba enviado.');

        return self::SUCCESS;
    }
}
