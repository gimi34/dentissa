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

    /** El mensaje dice cómo mover la cita según quién lo intenta (spec 028, CA5). */
    public static function vuelta(string $rol): self
    {
        return new self('vuelta', $rol === 'dentista'
            ? 'Una cita no puede volver a «Agendada». Pide a tu asistente o a la administración que la reprogramen.'
            : 'Una cita no puede volver a «Agendada». Si hay que moverla, usa «Reprogramar».');
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

    // Al reprogramar, como al agendar: el dentista debe seguir activo (/review 028 R19).
    public static function dentistaInactivo(): self
    {
        return new self('dentistaInactivo', 'El dentista de esta cita ya no está activo y no se puede reprogramar. Cancélala o agenda una cita nueva con otro dentista.');
    }

    // Una cita sin duración no se puede colocar en otro hueco (/review 028 R19).
    public static function sinDuracion(): self
    {
        return new self('sinDuracion', 'Esta cita no tiene una duración válida y no se puede reprogramar. Cancélala o agenda una cita nueva.');
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
