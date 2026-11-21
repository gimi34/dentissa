<?php

namespace App\Mail;

use App\Agenda\RelojClinica;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Spec 052 (CA1, CA9, CA10; D7, D8): el recordatorio de cita que el paciente recibe 24 h antes.
 *
 * Recibe solo los datos de CA1 (TM3): el nombre del paciente, la fecha y hora de la cita en la hora de
 * la clínica, el dentista (nombre y apellido, sin título: /analyze A1) y el servicio si lo hay. La
 * dirección, el teléfono y el horario salen de `config('clinica.contacto')`. Un solo enlace, «Ver mis
 * citas», armado con `config('app.url')` y sin parámetros (CA9, TM2). Diseño: `c2` de la spec 052.
 */
class RecordatorioCita extends Mailable
{
    /** Iniciales de la semana de la agenda, de lunes (1) a domingo (7). */
    private const DIAS = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

    public function __construct(
        public readonly string $nombre,
        public readonly string $fechaHora,
        public readonly string $dentista,
        public readonly ?string $servicio = null,
    ) {}

    public function envelope(): Envelope
    {
        $datos = $this->datos();

        return new Envelope(subject: "Tu cita es {$datos['cuando']}, {$datos['dia']} {$datos['fechaCorta']}, a las {$datos['hora']}");
    }

    public function content(): Content
    {
        $datos = $this->datos();
        $html = view('correos.recordatorio-cita', $datos)->render();
        // T022: la hoja del recordatorio se suma a la de los correos del acceso.
        $hoja = file_get_contents(resource_path('views/correos/acceso.css'))
            .PHP_EOL.file_get_contents(resource_path('views/correos/recordatorio.css'));

        return new Content(
            text: 'correos.recordatorio-cita-texto',
            with: $datos,
            htmlString: (new CssToInlineStyles)->convert($html, $hoja),
        );
    }

    /** @return array<string, mixed> los datos que usan el asunto y las dos plantillas */
    private function datos(): array
    {
        $inicio = RelojClinica::deCita($this->fechaHora)->locale('es');
        $abiertos = config('clinica.contacto.horario_semanal');

        $semana = [];
        foreach (self::DIAS as $numero => $letra) {
            $semana[] = ['letra' => $letra, 'hoy' => $numero === $inicio->dayOfWeekIso, 'cerrado' => ! isset($abiertos[$numero])];
        }

        return [
            'nombre' => $this->nombre,
            'dentista' => $this->dentista,
            'servicio' => $this->servicio,
            // D7: «hoy» si la cita es el día de la clínica en curso; si no, «mañana» (faltan 24 h o menos).
            'cuando' => $inicio->isSameDay(RelojClinica::ahora()) ? 'hoy' : 'mañana',
            'dia' => $inicio->translatedFormat('l'),
            'fechaCorta' => $inicio->translatedFormat('j \d\e F'),
            'anio' => $inicio->format('Y'),
            'hora' => $inicio->format('H:i'),
            'semana' => $semana,
            'enlace' => rtrim(config('app.url'), '/').route('cliente.citas', [], false),
            'contacto' => config('clinica.contacto'),
            'horario' => collect(config('clinica.contacto.horario'))->map(fn ($horas, $dias) => "{$dias} {$horas}")->implode(' · '),
        ];
    }
}
