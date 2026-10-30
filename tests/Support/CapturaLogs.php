<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

/**
 * Spec 020 (plan → Estrategia de pruebas): captura lo que se escribe en los canales de log
 * durante un test, con su contexto y su `extra`, donde Laravel pone el `Context`
 * (`request_id`, `actor_id`). Añade un `TestHandler` de Monolog a cada canal sin quitar los
 * suyos; los procesadores del canal se aplican antes, así que el `extra` llega completo.
 *
 * Uso: `$logs = CapturaLogs::instalar();` y después `$logs->registros('seguridad')`.
 */
final class CapturaLogs
{
    /** @var array<string, TestHandler> */
    private array $manejadores = [];

    /** @param  list<string|null>  $canales  null es el canal por defecto */
    public static function instalar(array $canales = [null, 'seguridad']): self
    {
        $captura = new self;
        foreach ($canales as $canal) {
            $manejador = new TestHandler;
            Log::channel($canal)->getLogger()->pushHandler($manejador);
            $captura->manejadores[$canal ?? 'defecto'] = $manejador;
        }

        return $captura;
    }

    /**
     * @param  string|null  $canal  null es el canal por defecto
     * @return list<array{mensaje: string, nivel: string, contexto: array<string, mixed>, extra: array<string, mixed>}>
     */
    public function registros(?string $canal = null): array
    {
        $manejador = $this->manejadores[$canal ?? 'defecto'] ?? null;
        if ($manejador === null) {
            throw new \LogicException('El canal «'.($canal ?? 'defecto').'» no se está capturando.');
        }

        return array_map(fn (LogRecord $registro) => [
            'mensaje' => $registro->message,
            'nivel' => strtolower($registro->level->getName()),
            'contexto' => $registro->context,
            'extra' => $registro->extra,
        ], $manejador->getRecords());
    }

    /** Todo lo capturado, de todos los canales, como texto: para comprobar que algo no aparece. */
    public function comoTexto(): string
    {
        $texto = '';
        foreach (array_keys($this->manejadores) as $canal) {
            foreach ($this->registros($canal === 'defecto' ? null : $canal) as $registro) {
                $texto .= json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)."\n";
            }
        }

        return $texto;
    }
}
