<?php

namespace App\Avisos;

use App\Mail\AvisoProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Spec 032 (CA1, CA4; TM12, TM14, TM15; plan D8): vigilante que lanza
 * `dentissa:vigilar` cada minuto.
 *
 * Lee lo nuevo del log de acceso de nginx desde el desplazamiento guardado y
 * cuenta, por minuto, las respuestas y los errores del servidor (5xx). Avisa por
 * correo si en la ventana pasan del umbral. Las rutas, los umbrales y el tope
 * salen de config/avisos.php y la hora, del reloj de la aplicación, así que los
 * tests los fijan.
 */
class Vigilante
{
    /**
     * Línea del formato `combined`. nginx escapa las comillas (`escape=default`),
     * así que la petición es la primera cadena entre comillas, el código es el
     * número que la sigue y la hora es el último `[…]` antes de ella, aunque el
     * usuario de la cabecera Basic lleve espacios o corchetes (/analyze A7).
     */
    private const COMBINED = '/^\S+ \S+ [^"]*\[([^\]"]+)\] "[^"]*" (\d{3}) /';

    /** Con al menos estas líneas en la ventana, más de la mitad sin el formato es un aviso (/analyze A36). */
    private const LINEAS_PARA_FORMATO = 20;

    /** Estas líneas seguidas sin el formato, sin ninguna buena en medio, también lo son (/analyze A49). */
    private const MALAS_SEGUIDAS = 5;

    /** Ejecuta una vuelta. Devuelve false si algún aviso no se pudo enviar. */
    public function ejecutar(): bool
    {
        $estado = Estado::cargar(config('avisos.estado'));

        if ($estado === null) {
            // Primera ejecución: lo que ya había en los logs no cuenta (/analyze A10).
            $estado = Estado::nuevo(config('avisos.estado'));
            $this->empezarAlFinal($estado);
            $estado->guardar();

            return true;
        }

        $ahora = CarbonImmutable::now();
        $nuevas = $this->leerAcceso($estado);
        $this->podar($estado, $ahora);

        $enviados = $this->avisoDeErrores($estado, $ahora, $nuevas['errores'])
            & $this->avisoDeFormato($estado, $ahora, $nuevas['malas']);

        $estado->guardar();

        return (bool) $enviados;
    }

    /**
     * Respuestas y errores del servidor en la ventana, según el estado guardado.
     *
     * @return array{peticiones: int, errores: int}
     */
    public function ventanaDeErrores(): array
    {
        $estado = Estado::cargar(config('avisos.estado')) ?? Estado::nuevo(config('avisos.estado'));
        $ventana = $this->minutosEnVentana($estado, CarbonImmutable::now());

        return [
            'peticiones' => array_sum(array_column($ventana, 'peticiones')),
            'errores' => array_sum(array_column($ventana, 'errores')),
        ];
    }

    private function empezarAlFinal(Estado $estado): void
    {
        $log = config('avisos.log_acceso');
        clearstatcache();
        $estado->datos['acceso'] = [
            'inodo' => is_file($log) ? fileinode($log) : null,
            'desplazamiento' => is_file($log) ? filesize($log) : 0,
            'malas_seguidas' => 0,
        ];
    }

    /**
     * Lee lo nuevo del log de acceso, con el tope de bytes por ejecución. Si el log
     * se truncó o se rotó (tamaño menor o inodo distinto), vuelve a empezar.
     *
     * @return array{errores: int, malas: int} errores y líneas sin formato leídos ahora
     */
    private function leerAcceso(Estado $estado): array
    {
        $log = config('avisos.log_acceso');
        $acceso = &$estado->datos['acceso'];
        $leidas = ['errores' => 0, 'malas' => 0];

        clearstatcache();
        if (! is_file($log)) {
            return $leidas;
        }

        $inodo = fileinode($log);
        if ($inodo !== $acceso['inodo'] || filesize($log) < $acceso['desplazamiento']) {
            $acceso['inodo'] = $inodo;
            $acceso['desplazamiento'] = 0;
        }

        foreach ($this->lineasNuevas($log, $acceso['desplazamiento']) as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            [$minuto, $codigo] = $this->interpretar($linea) ?? [null, null];
            if ($minuto === null) {
                $acceso['malas_seguidas']++;
                $leidas['malas']++;
                $this->sumar($estado, CarbonImmutable::now()->startOfMinute()->getTimestamp(), 'malas');

                continue;
            }

            $acceso['malas_seguidas'] = 0;
            $this->sumar($estado, $minuto, 'peticiones');
            if ($codigo >= 500) {
                $this->sumar($estado, $minuto, 'errores');
                $leidas['errores']++;
            }
        }

        return $leidas;
    }

    /**
     * Líneas completas desde `$desplazamiento`, sin pasar del tope; deja el
     * desplazamiento justo detrás de la última línea leída.
     *
     * @return list<string>
     */
    private function lineasNuevas(string $archivo, int &$desplazamiento): array
    {
        $tope = (int) config('avisos.tope_bytes');
        $manejador = fopen($archivo, 'rb');
        fseek($manejador, $desplazamiento);
        $trozo = (string) fread($manejador, $tope);
        fclose($manejador);

        $fin = strrpos($trozo, "\n");
        if ($fin === false) {
            // Una sola línea más larga que el tope: se salta entera.
            if (strlen($trozo) >= $tope) {
                $desplazamiento += strlen($trozo);

                return [''];
            }

            return [];
        }

        $desplazamiento += $fin + 1;

        return explode("\n", substr($trozo, 0, $fin));
    }

    /** @return array{0: int, 1: int}|null minuto (inicio, en segundos) y código, o null si no tiene el formato */
    private function interpretar(string $linea): ?array
    {
        if (! preg_match(self::COMBINED, $linea, $m)) {
            return null;
        }

        $momento = CarbonImmutable::createFromFormat('d/M/Y:H:i:s O', $m[1]);

        return $momento === null ? null : [$momento->startOfMinute()->getTimestamp(), (int) $m[2]];
    }

    private function sumar(Estado $estado, int $minuto, string $campo): void
    {
        $estado->datos['minutos'][$minuto] ??= ['peticiones' => 0, 'errores' => 0, 'malas' => 0];
        $estado->datos['minutos'][$minuto][$campo]++;
    }

    /** Quita los minutos que ya no están en la ventana. */
    private function podar(Estado $estado, CarbonImmutable $ahora): void
    {
        $estado->datos['minutos'] = $this->minutosEnVentana($estado, $ahora);
    }

    /** @return array<int, array{peticiones: int, errores: int, malas: int}> */
    private function minutosEnVentana(Estado $estado, CarbonImmutable $ahora): array
    {
        $inicio = $this->inicioDeVentana($ahora)->getTimestamp();

        return array_filter($estado->datos['minutos'], fn (int $minuto) => $minuto >= $inicio, ARRAY_FILTER_USE_KEY);
    }

    private function inicioDeVentana(CarbonImmutable $ahora): CarbonImmutable
    {
        return $ahora->subMinutes((int) config('avisos.ventana_minutos'));
    }

    /** Más del umbral de errores del servidor en la ventana (CA1). */
    private function avisoDeErrores(Estado $estado, CarbonImmutable $ahora, int $nuevos): bool
    {
        $ventana = $this->minutosEnVentana($estado, $ahora);
        $peticiones = array_sum(array_column($ventana, 'peticiones'));
        $errores = array_sum(array_column($ventana, 'errores'));
        $condicion = $peticiones > 0 && $errores / $peticiones > (float) config('avisos.umbral_errores');

        return $this->avisar($estado, 'errores', $condicion, $errores, $nuevos, $ahora);
    }

    /**
     * Log de acceso con otro formato: con suficiente tráfico, más de la mitad de
     * las líneas de la ventana; con poco, varias seguidas sin ninguna buena.
     */
    private function avisoDeFormato(Estado $estado, CarbonImmutable $ahora, int $nuevas): bool
    {
        $ventana = $this->minutosEnVentana($estado, $ahora);
        $malas = array_sum(array_column($ventana, 'malas'));
        $lineas = $malas + array_sum(array_column($ventana, 'peticiones'));
        $seguidas = $estado->datos['acceso']['malas_seguidas'];

        $condicion = ($lineas >= self::LINEAS_PARA_FORMATO && $malas * 2 > $lineas) || $seguidas >= self::MALAS_SEGUIDAS;

        return $this->avisar($estado, 'formato', $condicion, max($malas, $seguidas), $nuevas, $ahora);
    }

    /**
     * Envía el aviso de un tipo si se cumple su condición. Devuelve false si el
     * envío falla.
     */
    private function avisar(Estado $estado, string $tipo, bool $condicion, int $enVentana, int $nuevos, CarbonImmutable $ahora): bool
    {
        if (! $condicion) {
            return true;
        }

        return $this->enviar(new AvisoProduccion($tipo, $enVentana, $this->inicioDeVentana($ahora), $ahora));
    }

    private function enviar(AvisoProduccion $aviso): bool
    {
        try {
            Mail::to(config('avisos.destino'))->send($aviso);

            return true;
        } catch (Throwable) {
            // Sin el mensaje: puede llevar el usuario del SMTP (plan, Observabilidad).
            Log::warning('aviso no enviado', ['tipo' => $aviso->tipo]);

            return false;
        }
    }
}
