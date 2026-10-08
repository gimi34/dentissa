<?php

namespace App\Agenda;

use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Rechazo de un cambio de estado de una cita por la regla de estados (spec 026).
 *
 * El `tipo` permite a cada flujo traducir el mensaje: el paciente no ve estados internos.
 */
class CambioDeEstadoNoPermitido extends RuntimeException
{
    private const ETIQUETAS = [
        'agendada' => 'Agendada',
        'confirmada' => 'Confirmada',
        'en_curso' => 'En curso',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
    ];

    private const CERRADAS = [
        'completada' => 'completada',
        'cancelada' => 'cancelada',
        'no_asistio' => 'marcada como no asistida',
    ];

    public function __construct(public readonly string $tipo, string $mensaje)
    {
        parent::__construct($mensaje);
    }

    public static function cerrada(string $estado): self
    {
        $como = self::CERRADAS[$estado] ?? $estado;

        return new self('cerrada', "Esta cita ya está {$como} y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva.");
    }

    public static function vuelta(): self
    {
        return new self('vuelta', 'Una cita no puede volver a «Agendada». Si hay que moverla, cancélala y agenda una nueva.');
    }

    /** @param  string[]  $desde  estados de origen permitidos para `$nuevo` */
    public static function transicion(string $nuevo, array $desde): self
    {
        $origenes = implode(' o ', array_map(fn (string $estado) => '«'.self::etiqueta($estado).'»', $desde));

        return new self('transicion', 'Solo se puede pasar a «'.self::etiqueta($nuevo)."» desde {$origenes}.");
    }

    public static function antesDeTiempo(string $nuevo, CarbonInterface $inicio): self
    {
        return new self(
            'antesDeTiempo',
            'La cita aún no empieza. Podrás marcarla como «'.self::etiqueta($nuevo).'» a partir de las '
                .$inicio->format('H:i').' del '.$inicio->format('d/m/Y').'.',
        );
    }

    public static function yaEmpezo(): self
    {
        return new self('yaEmpezo', 'Tu cita ya empezó y no se puede cancelar. Si necesitas ayuda, llama a la clínica.');
    }

    /** Spec 028: el paciente cancela o reprograma con menos de 24 horas de antelación. */
    public static function fueraDePlazo(): self
    {
        return new self('fueraDePlazo', 'Faltan menos de 24 horas para tu cita: ya no puedes cambiarla ni cancelarla desde aquí. Llama a la clínica.');
    }

    /** Spec 028: el personal intenta reprogramar una cita en curso o que ya empezó. */
    public static function yaEmpezoPersonal(): self
    {
        return new self('yaEmpezoPersonal', 'Esta cita ya empezó y no se puede reprogramar.');
    }

    public static function ocupada(): self
    {
        return new self('ocupada', 'Otra persona está cambiando esta cita en este momento. Recarga la página y vuelve a intentarlo.');
    }

    public static function etiqueta(string $estado): string
    {
        return self::ETIQUETAS[$estado] ?? $estado;
    }
}
