<?php

namespace App\Http\Controllers;

use App\Mail\ContrasenaCambiada;
use App\Mail\EnlaceRestablecimiento;
use App\Models\Usuario;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * Vista previa del diseño de la 041 (D11, D15). Solo se registra en local: cada pantalla y cada
 * correo con los datos de muestra de `c7`, para medir con `design/medir.mjs` los estados que
 * dependen de la sesión (enviado, espera, éxito) o de un token.
 */
class DisenioAccesoController extends Controller
{
    public const PANTALLAS = 'pedir|enviado|nueva|nueva-espera|caducado|espera|exito|correo-enlace|correo-aviso';

    private const CORREO = 'paciente@correo.com';

    private const TOKEN = 'token-de-muestra';

    private const CUENTA = '00000000-0000-4000-8000-000000000000';

    public function show(string $pantalla)
    {
        return match (str_replace('.html', '', $pantalla)) {
            'pedir' => view('auth.recuperar.solicitud', ['espera' => false]),
            'espera' => $this->pedirEnEspera(),
            'enviado' => view('auth.recuperar.enviada', ['correo' => self::CORREO, 'hora' => '15:35']),
            'nueva' => view('auth.recuperar.nueva', $this->datosDeNueva() + [
                'espera' => false,
                'errors' => (new ViewErrorBag)->put('default', new MessageBag([
                    'password_confirmation' => 'Las dos contraseñas no coinciden.',
                ])),
            ]),
            'nueva-espera' => view('auth.recuperar.nueva', $this->datosDeNueva() + ['espera' => true]),
            'caducado' => view('auth.recuperar.caducado'),
            'exito' => $this->exito(),
            'correo-enlace' => $this->correo(new EnlaceRestablecimiento($this->cuenta(), self::TOKEN)),
            'correo-aviso' => $this->correo(new ContrasenaCambiada($this->cuenta())),
        };
    }

    private function datosDeNueva(): array
    {
        return ['token' => self::TOKEN, 'cuenta' => self::CUENTA, 'correo' => self::CORREO, 'hora' => '15:35'];
    }

    private function pedirEnEspera()
    {
        session()->now('_old_input', ['email' => self::CORREO]);

        return view('auth.recuperar.solicitud', ['espera' => true]);
    }

    private function exito()
    {
        session()->now('contrasena_cambiada', true);
        session()->now('_old_input', ['email' => self::CORREO]);

        return view('auth.login');
    }

    private function cuenta(): Usuario
    {
        return (new Usuario)->forceFill(['id' => self::CUENTA, 'email' => self::CORREO]);
    }

    /**
     * El correo tal como se envía, servido en local. La letra se añade solo aquí (K12 mide que carga):
     * el correo real no pide letras a otro sitio y cae en la de reemplazo de su cliente.
     */
    private function correo(object $correo)
    {
        config(['app.url' => request()->root()]);
        $fuente = '<style>@font-face { font-family: Roboto; font-weight: 100 900; font-display: swap; src: url('
            .asset('fonts/roboto/roboto-latin-wght-normal.woff2').") format('woff2'); }</style>";

        return response(str_replace('</head>', $fuente.'</head>', $correo->render()));
    }
}
