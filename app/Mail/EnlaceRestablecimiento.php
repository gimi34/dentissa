<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Spec 041 (CA2; TM12): correo con el enlace personal para elegir una contraseña nueva.
 *
 * El enlace se arma con `config('app.url')`, nunca con la petición, y lleva el identificador de la
 * cuenta, no su correo (P8). La hora de caducidad va en la hora de la clínica.
 */
class EnlaceRestablecimiento extends Mailable
{
    public readonly string $enlace;

    public readonly string $hora;

    public function __construct(Usuario $usuario, string $token)
    {
        $this->enlace = rtrim(config('app.url'), '/').'/contrasena/nueva/'.$token.'?cuenta='.$usuario->id;
        $this->hora = now()
            ->addMinutes((int) config('auth.passwords.users.expire'))
            ->timezone(config('clinica.zona_horaria'))
            ->format('H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Elige tu contraseña nueva de Dentissa');
    }

    public function content(): Content
    {
        $datos = ['enlace' => $this->enlace, 'hora' => $this->hora, 'contacto' => config('clinica.contacto')];
        $html = view('correos.enlace-restablecimiento', $datos)->render();
        $hoja = file_get_contents(resource_path('views/correos/acceso.css'));

        return new Content(
            text: 'correos.enlace-restablecimiento-texto',
            with: $datos,
            htmlString: (new CssToInlineStyles)->convert($html, $hoja),
        );
    }
}
