<?php

namespace App\Mail;

use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Spec 041 (CA7, D16): aviso de que la contraseña cambió. No lleva enlace de restablecimiento; si no
 * fue la persona, le da el teléfono y el horario de la clínica.
 */
class ContrasenaCambiada extends Mailable
{
    public readonly string $cuando;

    public function __construct(Usuario $usuario, ?CarbonInterface $momento = null)
    {
        $this->cuando = ($momento ?? now())
            ->copy()
            ->timezone(config('clinica.zona_horaria'))
            ->locale('es')
            ->translatedFormat('j \d\e F \a \l\a\s H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu contraseña de Dentissa cambió');
    }

    public function content(): Content
    {
        $datos = ['cuando' => $this->cuando, 'contacto' => config('clinica.contacto')];
        $html = view('correos.contrasena-cambiada', $datos)->render();
        $hoja = file_get_contents(resource_path('views/correos/acceso.css'));

        return new Content(
            text: 'correos.contrasena-cambiada-texto',
            with: $datos,
            htmlString: (new CssToInlineStyles)->convert($html, $hoja),
        );
    }
}
