<?php

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use App\Auditoria\AuditoriaNoDisponible;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Spec 020 (CA5, CA12; D13; TM9): purga los eventos de auditoría de más de 5 años. El borrado y su
// evento van en la misma transacción y el evento en modo crítico: si no se puede registrar, no se
// borra nada. El corte se mide en UTC, como `ocurrido_en` y el trigger, sin desbordar los años
// bisiestos (29 de febrero → 28) y truncado al segundo.
Artisan::command('auditoria:purgar', function (AlmacenAuditoria $almacen, Auditoria $auditoria) {
    $corte = CarbonImmutable::now('UTC')->subYearsNoOverflow(5)->startOfSecond();
    $fecha = $corte->format('Y-m-d\TH:i:s\Z');

    try {
        $purgados = DB::transaction(function () use ($almacen, $auditoria, $corte, $fecha) {
            $purgados = $almacen->purgar($corte);
            $auditoria->registrar('auditoria_purgada', 'exito', 'auditoria', detalle: [
                'eventos_purgados' => $purgados,
                'fecha_corte' => $fecha,
            ], critico: true);

            return $purgados;
        });
    } catch (AuditoriaNoDisponible) {
        $this->error('No se pudo registrar la purga en el registro de auditoría: no se borró ningún evento.');

        return 1;
    }

    Log::info('auditoria.purga', ['eventos_purgados' => $purgados, 'fecha_corte' => $fecha]);
    $this->info("Purgados {$purgados} eventos anteriores a {$fecha}.");

    return 0;
})->purpose('Purga los eventos de auditoría de más de 5 años (spec 020)');

// La única tarea programada: dentissa:vigilar sigue en su temporizador de systemd hasta su spec propia.
Schedule::command('auditoria:purgar')->monthlyOn(1, '03:00')->withoutOverlapping();
