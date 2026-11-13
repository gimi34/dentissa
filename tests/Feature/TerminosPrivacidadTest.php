<?php

namespace Tests\Feature;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 045: términos de uso y aviso de privacidad.
 *
 *  - Las dos páginas (T010): sus secciones en orden y la fecha (CA1, CA2), los datos de la clínica desde la
 *    configuración, el aviso cuadrado con los datos que pide el registro (CA3), el índice antes de las
 *    secciones con su estructura accesible (K3; /analyze A11), con y sin sesión iguales (CA7) y el texto de
 *    `<main>` igual al del candidato `c4` aprobado, que también prueba que llega en el HTML del servidor (CA8).
 *  - El registro (T011, T016): la casilla de los dos registros con «aviso de privacidad»; sus enlaces abren
 *    una ventana con el mismo texto que la página, sin salir del registro (CA4 refinado), y el rechazo sin
 *    la casilla, sin cuenta creada (CA5).
 *  - El pie (T012): «Términos de uso» y «Aviso de privacidad» en el pie de las cinco páginas públicas, con y
 *    sin sesión, en la misma pestaña (CA6).
 */
class TerminosPrivacidadTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const TERMINOS = '/terminos';

    private const PRIVACIDAD = '/aviso-de-privacidad';

    private const FECHA = 'Última actualización: 12 de noviembre de 2026';

    private const CANDIDATO = 'docs/specs/045-terminos-privacidad/design/candidatos/c4/';

    private const SECCIONES_TERMINOS = [
        'Quién ofrece este sitio', 'Tu cuenta', 'Citas en línea', 'Lo que no está permitido',
        'No sustituye la consulta', 'Cambios a estos términos', 'Contacto',
    ];

    private const SECCIONES_PRIVACIDAD = [
        'Quién es responsable', 'Qué datos recabamos', 'Para qué los usamos', 'Con quién los compartimos',
        'Cuánto los conservamos', 'Tus derechos', 'Cookies', 'Cambios a este aviso',
    ];

    /** CA3: cada campo de los formularios del registro y cada dato que se guarda de Google → su texto en el aviso. */
    private const DATO_EN_EL_AVISO = [
        'nombre' => 'nombre',
        'apellido' => 'apellidos',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'telefono' => 'teléfono',
        'fecha_nacimiento' => 'fecha de nacimiento',
        'genero' => 'género',
        'direccion' => 'dirección',
        'ciudad' => 'ciudad',
        'google_id' => 'identificador de Google',
        'foto_url' => 'foto de perfil',
    ];

    private const CASILLA = 'Acepto los términos de uso y el aviso de privacidad de Dentissa';

    private const MENSAJE_CASILLA = 'Debes aceptar los términos de uso y el aviso de privacidad.';

    private const GOOGLE = ['google_id' => 'google-045', 'nombre' => 'Persona', 'apellido' => 'De Pruebas', 'email' => 'google-045@example.test', 'foto_url' => null];

    /** Lo que no es un dato de la persona: la confirmación, el token y la propia casilla. */
    private const NO_SON_DATOS = ['_token', 'password_confirmation', 'terminos'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    private function texto(?\DOMNode $nodo): string
    {
        return trim(preg_replace('/\s+/u', ' ', $nodo?->textContent ?? ''));
    }

    /** @return list<string> títulos de las secciones del texto, sin su número */
    private function secciones(DOMXPath $xpath): array
    {
        $titulos = [];
        foreach ($xpath->query('//main//section[@id]/h2') as $h2) {
            $titulos[] = trim(preg_replace('/^\d+\s*/u', '', $this->texto($h2)));
        }

        return $titulos;
    }

    private function pagina(string $ruta): DOMXPath
    {
        return $this->xpath($this->get($ruta)->assertOk()->getContent());
    }

    public function test_terminos_tiene_sus_secciones_y_la_fecha(): void
    {
        $xpath = $this->pagina(self::TERMINOS);

        $this->assertSame('Términos de uso', $this->texto($xpath->query('//h1')->item(0)));
        $this->assertStringContainsString(self::FECHA, $this->texto($xpath->query('//main')->item(0)), 'La fecha de la última actualización.');
        $this->assertSame(self::SECCIONES_TERMINOS, $this->secciones($xpath), 'Las secciones de CA1, en orden.');
        $urgencias = $this->texto($xpath->query('//section[@id="citas"]')->item(0));
        $this->assertStringContainsString('El sitio no sirve para urgencias.', $urgencias, 'CA1: el sitio no sirve para urgencias.');
        $this->assertStringContainsString('desactivar la cuenta', $this->texto($xpath->query('//section[@id="no-permitido"]')->item(0)), 'CA1: qué puede hacer la clínica.');
    }

    public function test_el_aviso_tiene_sus_secciones_y_la_fecha(): void
    {
        $xpath = $this->pagina(self::PRIVACIDAD);

        $this->assertSame('Aviso de privacidad', $this->texto($xpath->query('//h1')->item(0)));
        $this->assertStringContainsString(self::FECHA, $this->texto($xpath->query('//main')->item(0)));
        $this->assertSame(self::SECCIONES_PRIVACIDAD, $this->secciones($xpath), 'Las secciones de CA2, en orden.');

        $datos = $this->texto($xpath->query('//section[@id="datos"]')->item(0));
        $this->assertMatchesRegularExpression('/Datos de salud.*datos sensibles/u', $datos, 'CA2: los de salud, como datos sensibles.');
        $this->assertStringContainsString('consentimiento', $datos, 'CA2: las fotos de la galería, con consentimiento.');
        $this->assertStringContainsString('No usamos tus datos para publicidad', $this->texto($xpath->query('//section[@id="usos"]')->item(0)));
        $this->assertStringContainsString('cinco años', $this->texto($xpath->query('//section[@id="conservacion"]')->item(0)), 'CA2: el expediente, al menos cinco años.');
        $derechos = $this->texto($xpath->query('//section[@id="derechos"]')->item(0));
        foreach (['acceso', 'rectificación', 'cancelación', 'oponerte', 'revocar tu consentimiento', '20 días hábiles'] as $parte) {
            $this->assertStringContainsString($parte, $derechos, "CA2: derechos ({$parte}).");
        }
        $this->assertStringContainsString('No usa cookies de analítica ni de publicidad', $this->texto($xpath->query('//section[@id="cookies"]')->item(0)));
    }

    public function test_los_datos_de_la_clinica_salen_de_la_configuracion(): void
    {
        $contacto = config('clinica.contacto');
        $horario = 'de lunes a viernes de 9:00 a 20:00 y los sábados de 9:00 a 14:00';
        $this->assertSame(['Lunes a viernes' => '9:00–20:00', 'Sábados' => '9:00–14:00'], $contacto['horario'], 'Control: el horario del texto aprobado.');

        foreach ([self::TERMINOS => 'quien', self::PRIVACIDAD => 'responsable'] as $ruta => $seccion) {
            $xpath = $this->pagina($ruta);
            $texto = $this->texto($xpath->query("//section[@id=\"{$seccion}\"]")->item(0));
            $this->assertStringContainsString($contacto['direccion'], $texto, "{$ruta}: la dirección de la clínica.");
            $telefono = $xpath->query("//section[@id=\"{$seccion}\"]//a[normalize-space()=\"{$contacto['telefono']}\"]");
            $this->assertSame(1, $telefono->length, "{$ruta}: el teléfono como enlace.");
            $this->assertSame($contacto['telefono_enlace'], $telefono->item(0)->getAttribute('href'));
        }
        $this->assertStringContainsString($horario, $this->texto($this->pagina(self::TERMINOS)->query('//section[@id="contacto"]')->item(0)), 'El horario en texto.');
        $this->assertStringContainsString($horario, $this->texto($this->pagina(self::PRIVACIDAD)->query('//section[@id="derechos"]')->item(0)));
    }

    public function test_el_aviso_nombra_cada_dato_del_registro(): void
    {
        $nombres = [];
        foreach ([
            $this->get('/registro')->assertOk()->getContent(),
            $this->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto')->assertOk()->getContent(),
        ] as $html) {
            foreach ($this->xpath($html)->query('//form//*[self::input or self::select or self::textarea][@name]') as $campo) {
                $nombres[] = $campo->getAttribute('name');
            }
        }

        // Lo que se guarda al registrarse con Google: las claves de `google_registro` en el controlador.
        $controlador = file_get_contents(app_path('Http/Controllers/GoogleAuthController.php'));
        $this->assertSame(1, preg_match("/put\('google_registro', \[(.*?)\]\);/s", $controlador, $bloque), 'Control: el controlador guarda google_registro.');
        preg_match_all("/'(\w+)' =>/", $bloque[1], $claves);
        $nombres = array_values(array_unique(array_diff(array_merge($nombres, $claves[1]), self::NO_SON_DATOS)));
        $this->assertContains('fecha_nacimiento', $nombres, 'Control: lee los campos del formulario.');
        $this->assertContains('google_id', $nombres, 'Control: lee lo que se guarda de Google.');

        $datos = $this->texto($this->pagina(self::PRIVACIDAD)->query('//section[@id="datos"]')->item(0));
        foreach ($nombres as $nombre) {
            $this->assertArrayHasKey($nombre, self::DATO_EN_EL_AVISO, "«{$nombre}» se pide o se guarda: el aviso tiene que nombrarlo (CA3).");
            $this->assertStringContainsString(self::DATO_EN_EL_AVISO[$nombre], $datos, "El aviso nombra «{$nombre}».");
        }
    }

    public function test_el_indice_va_antes_de_las_secciones(): void
    {
        foreach ([self::TERMINOS => self::SECCIONES_TERMINOS, self::PRIVACIDAD => self::SECCIONES_PRIVACIDAD] as $ruta => $titulos) {
            $xpath = $this->pagina($ruta);

            $indice = $xpath->query('//main//nav[@aria-labelledby]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $indice, "{$ruta}: el índice es un nav con título.");
            $titulo = $xpath->query('//*[@id="'.$indice->getAttribute('aria-labelledby').'"]')->item(0);
            $this->assertSame('En esta página', $this->texto($titulo), "{$ruta}: el índice se titula «En esta página».");

            $secciones = $xpath->query('//main//section[@id]');
            $this->assertCount(count($titulos), $secciones);
            $enlaces = $xpath->query('.//a', $indice);
            $this->assertSame($secciones->length, $enlaces->length, "{$ruta}: un enlace por sección (K3).");
            foreach ($secciones as $n => $seccion) {
                $this->assertSame('#'.$seccion->getAttribute('id'), $enlaces->item($n)->getAttribute('href'), "{$ruta}: el enlace {$n} lleva a su sección.");
                $this->assertLessThan($seccion->getLineNo(), $indice->getLineNo(), "{$ruta}: el índice va antes de las secciones.");
                // Estructura accesible (design.md → Accesibilidad; /analyze A11).
                $h2 = $xpath->query('h2', $seccion)->item(0);
                $this->assertSame($h2->getAttribute('id'), $seccion->getAttribute('aria-labelledby'), "{$ruta}: la sección se nombra con su título.");
                $this->assertSame('true', $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " lb-num ")]', $h2)->item(0)?->getAttribute('aria-hidden'), "{$ruta}: el número de sección no se lee.");
            }
            $resumen = $xpath->query('//main//aside[@aria-labelledby]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $resumen, "{$ruta}: «En pocas palabras» es un aside con título.");
            $this->assertSame('En pocas palabras', $this->texto($xpath->query('//*[@id="'.$resumen->getAttribute('aria-labelledby').'"]')->item(0)));
        }
    }

    public function test_con_y_sin_sesion_se_ven_igual(): void
    {
        $this->crearEscenario();

        foreach ([self::TERMINOS, self::PRIVACIDAD] as $ruta) {
            $sin = $this->pagina($ruta);
            $this->assertGreaterThan(0, $sin->query('//header//a[normalize-space()="Iniciar sesión"]')->length, "{$ruta}: sin sesión, «Iniciar sesión».");
            $this->assertGreaterThan(0, $sin->query('//header//a[normalize-space()="Crear cuenta"]')->length);
            $principal = $this->texto($sin->query('//main')->item(0));

            foreach (['admin' => $this->admin, 'dentista' => $this->dentista1, 'asistente' => $this->asistente, 'cliente' => $this->paciente1] as $rol => $usuario) {
                $respuesta = $this->actingAs($usuario)->get($ruta);
                $respuesta->assertOk();
                $con = $this->xpath($respuesta->getContent());
                $this->assertGreaterThan(0, $con->query('//header//a[normalize-space()="Ir a mi panel"]')->length, "{$ruta}, {$rol}: «Ir a mi panel».");
                $this->assertSame($principal, $this->texto($con->query('//main')->item(0)), "{$ruta}, {$rol}: el mismo contenido.");
            }
            $this->app['auth']->forgetGuards();
        }
    }

    public function test_el_texto_es_el_aprobado(): void
    {
        foreach ([self::TERMINOS => 'terminos.html', self::PRIVACIDAD => 'privacidad.html'] as $ruta => $archivo) {
            $aprobado = $this->xpath(file_get_contents(base_path(self::CANDIDATO.$archivo)));
            $this->assertSame(
                $this->texto($aprobado->query('//main')->item(0)),
                $this->texto($this->pagina($ruta)->query('//main')->item(0)),
                "{$ruta}: el texto de la página es el aprobado en candidatos/c4/{$archivo} (y llega en el HTML del servidor, CA8)."
            );
        }
    }

    public function test_la_casilla_del_registro(): void
    {
        foreach ($this->registros() as $pantalla => $xpath) {
            $etiqueta = $xpath->query('//label[.//input[@name="terminos"]]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $etiqueta, "{$pantalla}: la casilla tiene su etiqueta.");
            $this->assertSame(self::CASILLA, $this->texto($etiqueta), "{$pantalla}: el texto de la casilla (CA4).");

            $enlaces = $xpath->query('.//a', $etiqueta);
            $this->assertSame(2, $enlaces->length, "{$pantalla}: dos enlaces en la casilla.");
            foreach ([[url('/terminos'), 'términos de uso'], [url('/aviso-de-privacidad'), 'aviso de privacidad']] as $n => [$destino, $nombre]) {
                $enlace = $enlaces->item($n);
                $this->assertSame($nombre, $this->texto($enlace));
                $this->assertSame($destino, $enlace->getAttribute('href'), "{$pantalla}: sin JavaScript, «{$nombre}» lleva a su página.");
                $this->assertFalse($enlace->hasAttribute('target'), "{$pantalla}: «{$nombre}» en la misma pestaña (CA4 refinado; TM1 retirada).");
                $this->assertSame('dialog', $enlace->getAttribute('aria-haspopup'), "{$pantalla}: «{$nombre}» anuncia que abre una ventana.");
                $ventana = $xpath->query('//dialog[@id="'.$enlace->getAttribute('data-dialogo').'"]');
                $this->assertSame(1, $ventana->length, "{$pantalla}: «{$nombre}» abre una ventana que existe.");
                $this->assertFalse($enlace->hasAttribute('style'), "{$pantalla}: «{$nombre}» sin estilo en línea (P9).");
            }
        }
    }

    /** @return array<string, DOMXPath> los dos registros: el normal y el que se completa tras entrar con Google */
    private function registros(): array
    {
        return [
            'registro' => $this->xpath($this->get('/registro')->assertOk()->getContent()),
            'registro con Google' => $this->xpath($this->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto')->assertOk()->getContent()),
        ];
    }

    /** Texto normalizado de las secciones de un contenedor. */
    private function textoDeSecciones(DOMXPath $xpath, \DOMNode $contenedor): string
    {
        $partes = [];
        foreach ($xpath->query('.//section', $contenedor) as $seccion) {
            $partes[] = $this->texto($seccion);
        }

        return implode(' | ', $partes);
    }

    public function test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina(): void
    {
        $paginas = [
            'Términos de uso' => $this->pagina(self::TERMINOS),
            'Aviso de privacidad' => $this->pagina(self::PRIVACIDAD),
        ];
        foreach ($this->registros() as $pantalla => $xpath) {
            $ventanas = $xpath->query('//dialog[contains(concat(" ", normalize-space(@class), " "), " dialogo-legal ")]');
            $this->assertSame(2, $ventanas->length, "{$pantalla}: una ventana por texto.");
            foreach ($ventanas as $ventana) {
                $titulo = $xpath->query('//*[@id="'.$ventana->getAttribute('aria-labelledby').'"]')->item(0);
                $nombre = $this->texto($titulo);
                $this->assertArrayHasKey($nombre, $paginas, "{$pantalla}: la ventana se nombra con su título.");
                $this->assertStringContainsString(self::FECHA, $this->texto($ventana), "{$pantalla}, {$nombre}: con la fecha.");
                $this->assertSame(1, $xpath->query('.//button[@type="button"][@data-cerrar][normalize-space()="Cerrar"]', $ventana)->length, "{$pantalla}, {$nombre}: botón «Cerrar».");
                $pagina = $paginas[$nombre];
                $this->assertSame(
                    $this->textoDeSecciones($pagina, $pagina->query('//main')->item(0)),
                    $this->textoDeSecciones($xpath, $ventana),
                    "{$pantalla}, {$nombre}: el mismo texto que la página (D11)."
                );
            }
        }
    }

    public function test_la_ventana_del_registro_solo_usa_tokens(): void
    {
        // P9: las reglas nuevas de la ventana, solo con tokens: ni medidas con unidad ni colores literales. Se permiten
        // los porcentajes, 100dvh y los números sin unidad (interlineado y factores de calc() sobre tokens).
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path('css/auth.css')));
        preg_match_all('/([^{}]*\.dialogo-legal[^{}]*)\{([^}]*)\}/', $css, $reglas, PREG_SET_ORDER);
        $this->assertNotEmpty($reglas, 'auth.css tiene las reglas de la ventana.');
        foreach ($reglas as [, $selector, $cuerpo]) {
            $sinTokens = preg_replace('/var\(--[\w-]+\)|100dvh/', '', $cuerpo);
            $this->assertDoesNotMatchRegularExpression('/\d(px|rem|em|ch|vh|vw|ms|s)\b|#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(/i', $sinTokens, trim($selector).': solo tokens.');
        }
    }

    public function test_el_script_de_las_ventanas_abre_y_cierra(): void
    {
        // /analyze A17: la estructura del script de las ventanas (que el navegador lo ejecute así se comprueba a mano).
        $ruta = resource_path('views/auth/_dialogos-legales.blade.php');
        $this->assertFileExists($ruta, 'Las ventanas tienen su parcial.');
        $this->assertSame(1, preg_match('#<script>(.*?)</script>#s', file_get_contents($ruta), $script), 'El parcial lleva su script.');
        $js = $script[1];
        $this->assertStringContainsString("querySelectorAll('[data-dialogo]')", $js, 'Lo abren los enlaces de la casilla.');
        $this->assertMatchesRegularExpression("/typeof\s+destino\.showModal\s*!==\s*'function'\)\s*return;/", $js, 'Sin showModal, el enlace navega a la página.');
        $this->assertMatchesRegularExpression('/preventDefault\(\);[\s\S]*showModal\(\)/', $js, 'Con showModal, abre la ventana sin salir.');
        $this->assertMatchesRegularExpression("/querySelector\('\[data-cerrar\]'\)[\s\S]*?close\(\)/", $js, '«Cerrar» la cierra.');
        $this->assertMatchesRegularExpression('/target\s*===\s*d\)\s*d\.close\(\)/', $js, 'Pulsar fuera la cierra.');
        $this->assertMatchesRegularExpression("/addEventListener\('close'[\s\S]*?\.focus\(\)/", $js, 'Al cerrarse, el foco vuelve al enlace.');
    }

    public function test_el_registro_sin_la_casilla_se_rechaza(): void
    {
        $correo = 'casilla-045@example.test';

        $this->from('/registro')->post('/registro', [
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => $correo,
            'password' => 'secreto-de-pruebas',
            'password_confirmation' => 'secreto-de-pruebas',
        ])->assertRedirect('/registro')->assertSessionHasErrors(['terminos' => self::MENSAJE_CASILLA]);

        $this->assertDatabaseMissing('usuarios', ['email' => $correo]);
    }

    public function test_el_registro_con_google_sin_la_casilla_se_rechaza(): void
    {
        $this->withSession(['google_registro' => self::GOOGLE])
            ->from('/registro/contacto')
            ->post('/registro/contacto', ['telefono' => '555-0000'])
            ->assertRedirect('/registro/contacto')
            ->assertSessionHasErrors(['terminos' => self::MENSAJE_CASILLA]);

        $this->assertDatabaseMissing('usuarios', ['email' => self::GOOGLE['email']]);
    }

    public function test_el_pie_publico_enlaza_las_dos_paginas(): void
    {
        $this->crearEscenario();

        foreach (['/', '/servicios', '/galeria', self::TERMINOS, self::PRIVACIDAD] as $ruta) {
            foreach (['sin sesión' => null, 'con sesión' => $this->paciente1] as $caso => $usuario) {
                $respuesta = $usuario ? $this->actingAs($usuario)->get($ruta) : $this->get($ruta);
                $xpath = $this->xpath($respuesta->assertOk()->getContent());
                $pie = $xpath->query('//footer//ul[contains(concat(" ", normalize-space(@class), " "), " pub-foot__links ")]')->item(0);
                $this->assertInstanceOf(DOMElement::class, $pie, "{$ruta}, {$caso}: el pie público.");
                foreach ([url('/terminos') => 'Términos de uso', url('/aviso-de-privacidad') => 'Aviso de privacidad'] as $destino => $nombre) {
                    $enlaces = $xpath->query(".//a[normalize-space()=\"{$nombre}\"]", $pie);
                    $this->assertSame(1, $enlaces->length, "{$ruta}, {$caso}: «{$nombre}» en el pie (CA6).");
                    $this->assertSame($destino, $enlaces->item(0)->getAttribute('href'));
                    $this->assertFalse($enlaces->item(0)->hasAttribute('target'), "{$ruta}, {$caso}: «{$nombre}» en la misma pestaña.");
                }
            }
            $this->app['auth']->forgetGuards();
        }
    }
}
