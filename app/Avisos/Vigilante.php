<?php

namespace App\Avisos;

use App\Mail\AvisoProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Spec 032 (CA1, CA2, CA4; TM5, TM6, TM10, TM12, TM14, TM15; plan D8):
 * vigilante que lanza `dentissa:vigilar` cada minuto.
 *
 * Lee lo nuevo del log de acceso de nginx y del canal `seguridad` desde los
 * desplazamientos guardados, cuenta en una ventana deslizante y avisa por correo
 * si pasa del umbral. Cada tipo de aviso tiene su silencio: lo que pasa durante
 * él se acumula y se envía al acabar, con cada evento contado una sola vez. Las
 * rutas, los umbrales y el tope salen de config/avisos.php y la hora, del reloj de
 * la aplicación, así que los tests los fijan.
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
        $fallos = $this->leerSeguridad($estado, $ahora);
        $this->podar($estado, $ahora);

        $enviados = $this->avisoDeErrores($estado, $ahora, $nuevas['errores'])
            & $this->avisoDeFormato($estado, $ahora, $nuevas['malas'])
            & $this->avisoDeAccesos($estado, $ahora, $fallos);

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

        foreach ($this->archivosDeSeguridad(CarbonImmutable::now()) as $nombre => $archivo) {
            $estado->datos['seguridad']['desplazamientos'][$nombre] = is_file($archivo) ? filesize($archivo) : 0;
        }
    }

    /**
     * Archivos diarios del canal `seguridad` que pueden tener fallos de la ventana:
     * el de ayer y el de hoy.
     *
     * @return array<string, string> nombre → ruta
     */
    private function archivosDeSeguridad(CarbonImmutable $ahora): array
    {
        $base = config('avisos.log_seguridad');
        $carpeta = dirname($base);
        $prefijo = pathinfo($base, PATHINFO_FILENAME);
        $archivos = [];
        foreach ([$ahora->subDay(), $ahora] as $dia) {
            $nombre = $prefijo.'-'.$dia->format('Y-m-d').'.log';
            $archivos[$nombre] = $carpeta.'/'.$nombre;
        }

        return $archivos;
    }

    /**
     * Lee lo nuevo del canal `seguridad`, con el mismo tope que el log de acceso
     * (/analyze A44). De cada línea solo se guardan el momento y las huellas.
     *
     * @return int fallos leídos ahora
     */
    private function leerSeguridad(Estado $estado, CarbonImmutable $ahora): int
    {
        $seguridad = &$estado->datos['seguridad'];
        $desplazamientos = [];
        $leidos = 0;

        clearstatcache();
        foreach ($this->archivosDeSeguridad($ahora) as $nombre => $archivo) {
            $desplazamiento = $seguridad['desplazamientos'][$nombre] ?? 0;
            if (! is_file($archivo)) {
                continue;
            }
            if (filesize($archivo) < $desplazamiento) {
                $desplazamiento = 0;
            }

            foreach ($this->lineasNuevas($archivo, $desplazamiento) as $linea) {
                $fallo = $this->interpretarFallo($linea);
                if ($fallo !== null) {
                    $seguridad['fallos'][] = $fallo;
                    $leidos++;
                }
            }
            $desplazamientos[$nombre] = $desplazamiento;
        }
        $seguridad['desplazamientos'] = $desplazamientos;

        return $leidos;
    }

    /**
     * Línea del canal `seguridad` (JsonFormatter): se leen `datetime` y `context`
     * y se ignora `extra`, donde la 020 añadirá su contexto (/analyze A11).
     *
     * @return array{0: int, 1: string|null, 2: string|null}|null momento, origen y cuenta
     */
    private function interpretarFallo(string $linea): ?array
    {
        $datos = json_decode($linea, true);
        if (! is_array($datos) || ! is_string($datos['datetime'] ?? null) || ! is_array($datos['context'] ?? null)) {
            return null;
        }

        try {
            $momento = CarbonImmutable::parse($datos['datetime']);
        } catch (Throwable) {
            return null;
        }
        $huella = fn ($valor) => is_string($valor) ? $valor : null;

        return [$momento->getTimestamp(), $huella($datos['context']['origen'] ?? null), $huella($datos['context']['cuenta'] ?? null)];
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

        // Carbon lanza una excepción si la hora no tiene el formato de nginx: la línea cuenta como
        // sin formato y no tumba la ejecución (/review R1).
        try {
            $momento = CarbonImmutable::createFromFormat('d/M/Y:H:i:s O', $m[1]);
        } catch (Throwable) {
            return null;
        }

        return $momento ? [$momento->startOfMinute()->getTimestamp(), (int) $m[2]] : null;
    }

    private function sumar(Estado $estado, int $minuto, string $campo): void
    {
        $estado->datos['minutos'][$minuto] ??= ['peticiones' => 0, 'errores' => 0, 'malas' => 0];
        $estado->datos['minutos'][$minuto][$campo]++;
    }

    /** Quita los minutos y los fallos que ya no están en la ventana. */
    private function podar(Estado $estado, CarbonImmutable $ahora): void
    {
        $inicio = $this->inicioDeVentana($ahora)->getTimestamp();
        $estado->datos['minutos'] = $this->minutosEnVentana($estado, $ahora);
        $estado->datos['seguridad']['fallos'] = array_values(array_filter(
            $estado->datos['seguridad']['fallos'],
            fn (array $fallo) => $fallo[0] >= $inicio,
        ));
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

    /** Más del umbral de fallos de acceso en la ventana desde un origen o contra una cuenta (CA2). */
    private function avisoDeAccesos(Estado $estado, CarbonImmutable $ahora, int $nuevos): bool
    {
        $fallos = $estado->datos['seguridad']['fallos'];
        $mayor = 0;
        foreach ([1, 2] as $campo) {
            $grupos = array_count_values(array_filter(array_column($fallos, $campo), 'is_string'));
            $mayor = max($mayor, $grupos === [] ? 0 : max($grupos));
        }

        return $this->avisar($estado, 'accesos', $mayor > (int) config('avisos.umbral_fallos'), count($fallos), $nuevos, $ahora);
    }

    /**
     * Aviso de un tipo con su silencio (CA4, TM6). Como mucho un correo por tipo en
     * el silencio; lo que pasa durante él se acumula (solo los eventos nuevos, cada
     * uno una vez: /analyze A9) y, si la condición se cumplió con eventos nuevos, se
     * envía al acabar aunque ya no se cumpla. Un envío fallido no cuenta como
     * enviado y se reintenta en la ejecución siguiente (TM10).
     *
     * @return bool false si el envío falló
     */
    private function avisar(Estado $estado, string $tipo, bool $condicion, int $enVentana, int $nuevos, CarbonImmutable $ahora): bool
    {
        $aviso = $estado->aviso($tipo);
        $anterior = $aviso['ultimo_envio'] === null ? null : CarbonImmutable::createFromTimestamp($aviso['ultimo_envio']);

        if ($anterior !== null && $ahora->lt($anterior->addMinutes((int) config('avisos.silencio_minutos')))) {
            $aviso['acumulado'] += $nuevos;
            $aviso['pendiente'] = $aviso['pendiente'] || ($condicion && $nuevos > 0);
            $estado->guardarAviso($tipo, $aviso);

            return true;
        }

        if ($anterior !== null && $aviso['pendiente']) {
            $aviso['acumulado'] += $nuevos;
            $correo = new AvisoProduccion($tipo, $aviso['acumulado'], $anterior, $ahora, $anterior);
        } elseif ($condicion) {
            $correo = new AvisoProduccion($tipo, $enVentana, $this->inicioDeVentana($ahora), $ahora);
        } else {
            $estado->guardarAviso($tipo, ['ultimo_envio' => $aviso['ultimo_envio'], 'pendiente' => false, 'acumulado' => 0]);

            return true;
        }

        if (! $this->enviar($correo)) {
            $estado->guardarAviso($tipo, $aviso);

            return false;
        }

        $estado->guardarAviso($tipo, ['ultimo_envio' => $ahora->getTimestamp(), 'pendiente' => false, 'acumulado' => 0]);

        return true;
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
