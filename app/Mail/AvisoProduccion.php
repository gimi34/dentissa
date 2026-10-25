<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Spec 032 (CA1, CA2, CA5, CA6; TM7; plan D9): aviso de producción por correo.
 *
 * Sale hacia un buzón externo, así que solo recibe el tipo de aviso, el recuento
 * y las horas; nunca una línea de log. Las horas se escriben en la hora de la
 * clínica. Texto plano en español.
 */
class AvisoProduccion extends Mailable
{
    /** Asunto de cada tipo de aviso. */
    private const TITULOS = [
        'errores' => 'Errores del servidor',
        'accesos' => 'Inicios de sesión fallidos',
        'formato' => 'El log de acceso tiene otro formato',
        'prueba' => 'Aviso de prueba',
    ];

    /**
     * @param  string  $tipo  errores, accesos, formato o prueba
     * @param  int  $recuento  eventos desde el fin del aviso anterior (o en la ventana, si no lo hay)
     * @param  CarbonImmutable|null  $anterior  momento del aviso anterior del mismo tipo
     */
    public function __construct(
        public readonly string $tipo,
        public readonly int $recuento,
        public readonly CarbonImmutable $desde,
        public readonly CarbonImmutable $hasta,
        public readonly ?CarbonImmutable $anterior = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Dentissa] '.(self::TITULOS[$this->tipo] ?? $this->tipo));
    }

    public function content(): Content
    {
        $zona = (string) config('clinica.zona_horaria');
        $hora = fn (?CarbonImmutable $momento) => $momento?->setTimezone($zona)->format('d/m/Y H:i');

        return new Content(
            text: 'correos.aviso',
            with: [
                'titulo' => self::TITULOS[$this->tipo] ?? $this->tipo,
                'desdeTexto' => $hora($this->desde),
                'hastaTexto' => $hora($this->hasta),
                'anteriorTexto' => $hora($this->anterior),
                'zona' => $zona,
                'dia' => $this->hasta->format('Y-m-d'),
            ],
        );
    }
}
