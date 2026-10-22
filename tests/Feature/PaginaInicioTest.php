<?php

namespace Tests\Feature;

use App\Models\Usuario;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 038 (CA1–CA5; TM2, TM4, TM5): la página de inicio pública.
 *
 * Sin sesión, `/` muestra la presentación de la clínica (ya no redirige al
 * inicio de sesión: reemplaza 001 CA4); con sesión, cada rol sigue yendo a su
 * panel. Los servicios salen del catálogo: solo los nombres de las categorías
 * activas, sin servicios sueltos ni precios.
 *
 * Los servicios de prueba usan nombres y categorías propios del test; los
 * casos «sin activos» desactivan dentro de la transacción todos los servicios,
 * también los del volcado (/analyze A12).
 *
 * Rediseño de la ronda 3 (plan D16): la placa y el aviso de la puerta dejan su
 * lugar a la portada (horario y teléfono dentro de ella), las puertas a las
 * tarjetas con «N tratamientos», y aparecen la hoja de «Sobre nosotros», la
 * franja del horario con su botón de pausa (A23) y los datos de la pastilla
 * «¿Abierto ahora?» (A25, A26).
 */
class PaginaInicioTest extends TestCase
{
    use DatabaseTransactions;

    private const DIRECCION = 'Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950';

    private const NOSOTROS = [
        'Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.',
        'Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.',
    ];

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => "inicio.{$rol}.".Str::random(8).'@example.test',
            'rol' => $rol,
            'telefono' => '555-0000',
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }

    private function crearServicio(string $nombre, ?string $categoria, bool $activo = true, float $precio = 777.77): void
    {
        DB::table('servicios')->insert([
            'id' => (string) Str::uuid(),
            'nombre' => $nombre,
            'descripcion' => "Descripción de {$nombre}",
            'categoria' => $categoria,
            'duracion_min' => 30,
            'precio' => $precio,
            'activo' => $activo ? 1 : 0,
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    /** El primer elemento con esa clase, en todo el documento o dentro de $dentro. */
    private function porClase(DOMXPath $xpath, string $clase, ?DOMNode $dentro = null): ?DOMElement
    {
        $consulta = ($dentro ? './/' : '//').'*[contains(concat(" ", normalize-space(@class), " "), " '.$clase.' ")]';

        return $xpath->query($consulta, $dentro)->item(0);
    }

    private function textoDe(DOMNode $nodo): string
    {
        return trim(preg_replace('/\s+/u', ' ', $nodo->textContent));
    }

    /** Texto visible de la página, con los espacios normalizados. */
    private function texto(string $html): string
    {
        $xpath = $this->xpath($html);
        foreach (iterator_to_array($xpath->query('//script|//style|//head')) as $nodo) {
            $nodo->parentNode->removeChild($nodo);
        }

        return trim(preg_replace('/\s+/u', ' ', $xpath->document->textContent));
    }

    public function test_sin_sesion_muestra_la_pagina_en_orden(): void
    {
        // /review R6: un servicio propio, para que «Nuestros servicios» no dependa del volcado.
        $this->crearServicio('Servicio038 Orden', 'Categoria038 Orden');
        $respuesta = $this->get('/');

        $respuesta->assertOk();
        // Rediseño (D16): portada (titular, «Agendar», horario y teléfono), hoja de «Sobre nosotros»,
        // tarjetas, franja, ubicación y pie, en el orden del HTML.
        $respuesta->assertSeeInOrder([
            'class="portada"',
            'Agendar mi primera cita',
            'Lunes a viernes',
            'id="titulo-nosotros"',
            'id="servicios"',
            'class="marquesina"',
            'id="ubicacion"',
            'class="lb-pie"',
        ], false);

        $xpath = $this->xpath($respuesta->getContent());
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]')->length, '«Agendar mi primera cita» lleva al registro.');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Iniciar sesión"][@href="'.route('login').'"]')->length, 'Hay un enlace visible a «Iniciar sesión».');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Crear cuenta"][@href="'.route('register').'"]')->length, 'Hay un enlace a crear una cuenta.');

        // Rediseño (D16): sin la placa ni el aviso de la puerta.
        $this->assertNull($this->porClase($xpath, 'lb-placa'), 'Sin la placa de la opción B.');
        $this->assertNull($this->porClase($xpath, 'lb-aviso'), 'Sin el aviso de la puerta.');
    }

    public function test_la_portada_lleva_el_horario_y_el_telefono(): void
    {
        // Sustituye a «el horario y el teléfono dentro del aviso» (/review R3): ahora van en la
        // portada, junto a «Agendar mi primera cita» (CA1); la ubicación y el pie los repiten.
        $xpath = $this->xpath($this->get('/')->getContent());
        $portada = $this->porClase($xpath, 'portada');
        $this->assertNotNull($portada, 'Hay una portada.');

        $h1 = $xpath->query('.//h1', $portada);
        $this->assertSame(1, $h1->length, 'El titular de la página está en la portada.');
        $this->assertSame('Tu sonrisa, nuestra prioridad.', $this->textoDe($h1->item(0)));
        $this->assertSame(1, $xpath->query('.//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]', $portada)->length);

        $textoPortada = $this->textoDe($portada);
        $this->assertStringContainsString('Lunes a viernes 9:00–20:00', $textoPortada);
        $this->assertStringContainsString('Sábados 9:00–14:00', $textoPortada);
        $this->assertSame(1, $xpath->query('.//a[@href="tel:+527530000000"][contains(normalize-space(), "753 000 0000")]', $portada)->length, 'La portada ofrece llamar.');
    }

    public function test_la_pastilla_toma_el_horario_y_la_zona_de_la_configuracion(): void
    {
        // /analyze A25: la pastilla «¿Abierto ahora?» la escribe el script con estos datos; sin él, vacía.
        $xpath = $this->xpath($this->get('/')->getContent());
        $vivo = $xpath->query('//*[@data-live]')->item(0);

        $this->assertNotNull($vivo, 'Hay un elemento vivo para la pastilla.');
        $this->assertSame(config('clinica.contacto.horario_semanal'), json_decode($vivo->getAttribute('data-horario'), true));
        $this->assertSame(config('clinica.zona_horaria'), $vivo->getAttribute('data-zona'));

        $pastilla = $this->porClase($xpath, 'estado-ahora', $vivo);
        $this->assertNotNull($pastilla);
        $this->assertSame('polite', $pastilla->getAttribute('aria-live'));
        $this->assertSame('', $this->textoDe($pastilla), 'Sin JavaScript, la pastilla no dice nada (el horario completo va al lado).');
    }

    public function test_el_horario_en_texto_coincide_con_el_semanal(): void
    {
        // /analyze A25: el horario en texto (CA5) y el semanal de la pastilla no pueden separarse.
        $texto = config('clinica.contacto.horario');
        $semanal = config('clinica.contacto.horario_semanal');

        $this->assertIsArray($semanal, 'Hay un horario semanal.');
        $this->assertSame(['Lunes a viernes', 'Sábados'], array_keys($texto));
        foreach (range(1, 5) as $dia) {
            $this->assertSame($texto['Lunes a viernes'], implode('–', $semanal[$dia]), "El día {$dia} abre como «Lunes a viernes».");
        }
        $this->assertSame($texto['Sábados'], implode('–', $semanal[6]));
        $this->assertSame([1, 2, 3, 4, 5, 6], array_keys($semanal), 'El domingo (7) está cerrado.');
    }

    public function test_con_sesion_cada_rol_va_a_su_panel(): void
    {
        foreach (['admin', 'dentista', 'asistente', 'cliente'] as $rol) {
            $this->actingAs($this->crearUsuario($rol))
                ->get('/')
                ->assertRedirect(route("{$rol}.dashboard"));
        }
    }

    public function test_sobre_nosotros_con_el_texto_aprobado(): void
    {
        $html = $this->get('/')->getContent();
        $texto = $this->texto($html);

        foreach (self::NOSOTROS as $parrafo) {
            $this->assertStringContainsString($parrafo, $texto);
        }

        // Rediseño (D16, A31): «Sobre nosotros» es la hoja que sube sobre la portada.
        $xpath = $this->xpath($html);
        $hoja = $this->porClase($xpath, 'hoja');
        $this->assertNotNull($hoja, 'La portada y «Sobre nosotros» van en la hoja.');
        $this->assertNotNull($this->porClase($xpath, 'portada', $hoja));
        $capitulo = $this->porClase($xpath, 'hoja-capitulo', $hoja);
        $this->assertNotNull($capitulo);
        $this->assertSame(1, $xpath->query('.//h2[@id="titulo-nosotros"][normalize-space()="Sobre nosotros"]', $capitulo)->length);
        foreach (self::NOSOTROS as $parrafo) {
            $this->assertStringContainsString($parrafo, $this->textoDe($capitulo));
        }
    }

    public function test_solo_categorias_activas_sin_servicios_sueltos(): void
    {
        $this->crearServicio('Servicio038 Visible', 'Categoria038 Activa');
        $this->crearServicio('Servicio038 Oculto', 'Categoria038 Inactiva', activo: false);

        $respuesta = $this->get('/');
        $texto = $this->texto($respuesta->getContent());

        $this->assertStringContainsString('Categoria038 Activa', $texto);
        $this->assertStringNotContainsString('Categoria038 Inactiva', $texto, 'Una categoría sin servicios activos no aparece.');
        $this->assertStringNotContainsString('Servicio038 Visible', $texto, 'El avance muestra categorías, no servicios sueltos.');
        $this->assertStringNotContainsString('777.77', $texto, 'Sin precios.');
        $this->assertStringNotContainsString('777,77', $texto, 'Sin precios.');

        $xpath = $this->xpath($respuesta->getContent());
        $this->assertSame(1, $xpath->query('//a[normalize-space()="Ver todos los servicios"][@href="'.url('/servicios').'"]')->length, '«Ver todos los servicios» lleva a /servicios.');
    }

    public function test_cada_tarjeta_cuenta_sus_tratamientos(): void
    {
        $this->crearServicio('Servicio038 Dos A', 'Categoria038 Dos');
        $this->crearServicio('Servicio038 Dos B', 'Categoria038 Dos');
        $this->crearServicio('Servicio038 Uno', 'Categoria038 Uno');
        $this->crearServicio('Servicio038 Uno Inactivo', 'Categoria038 Uno', activo: false);
        $this->crearServicio('Servicio038 Orto', 'Ortodoncia');

        $xpath = $this->xpath($this->get('/')->getContent());
        $servicios = $this->xpath($this->get('/servicios')->getContent());
        $tarjeta = fn (string $categoria) => $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " tarjeta ")][.//h3[normalize-space()="'.$categoria.'"]]')->item(0);

        foreach (['Categoria038 Dos' => '2 tratamientos', 'Categoria038 Uno' => '1 tratamiento'] as $categoria => $cuenta) {
            $enlace = $tarjeta($categoria);
            $this->assertNotNull($enlace, "Hay una tarjeta para {$categoria}.");
            $this->assertSame("{$categoria} {$cuenta}", $this->textoDe($enlace), 'Solo el nombre y el número de tratamientos activos, sin servicios sueltos.');

            // /analyze A26: el ancla de la tarjeta existe en /servicios y es la de su categoría.
            $href = $enlace->getAttribute('href');
            $this->assertMatchesRegularExpression('~^'.preg_quote(url('/servicios'), '~').'#categoria-\d+$~', $href);
            $seccion = $servicios->query('//*[@id="'.substr($href, strpos($href, '#') + 1).'"]')->item(0);
            $this->assertNotNull($seccion, "/servicios tiene el ancla de {$categoria}.");
            $this->assertSame($categoria, $this->textoDe($servicios->query('.//h2', $seccion)->item(0)));

            $icono = $xpath->query('.//*[local-name()="svg"]', $enlace)->item(0);
            $this->assertNotNull($icono, 'La tarjeta lleva su icono.');
            $this->assertSame('true', $icono->getAttribute('aria-hidden'));
        }

        // /analyze A26: icono propio para las categorías conocidas; la muela para las demás.
        $this->assertSame('ortodoncia', $xpath->query('.//*[local-name()="svg"]', $tarjeta('Ortodoncia'))->item(0)->getAttribute('data-icono'));
        $this->assertSame('muela', $xpath->query('.//*[local-name()="svg"]', $tarjeta('Categoria038 Uno'))->item(0)->getAttribute('data-icono'));
    }

    public function test_la_franja_repite_el_horario_y_se_puede_pausar(): void
    {
        $xpath = $this->xpath($this->get('/')->getContent());
        $franja = $this->porClase($xpath, 'marquesina');

        $this->assertNotNull($franja, 'Hay una franja del horario.');
        $this->assertSame('true', $franja->getAttribute('aria-hidden'), 'Es decorativa: el horario ya está en el texto.');
        $this->assertStringContainsString('Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00', $this->textoDe($franja));

        // /analyze A23 (WCAG 2.2.2): un botón la detiene; fuera de la zona oculta y oculto hasta que lo muestra el script.
        $pausa = $xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " marquesina-pausa ")]');
        $this->assertSame(1, $pausa->length, 'Un botón de pausa.');
        $boton = $pausa->item(0);
        $this->assertSame('button', $boton->getAttribute('type'));
        $this->assertSame('Pausar la franja del horario', $boton->getAttribute('aria-label'));
        $this->assertSame('false', $boton->getAttribute('aria-pressed'));
        $this->assertTrue($boton->hasAttribute('hidden'), 'Sin JavaScript no se muestra (la franja no se mueve).');
        $this->assertSame(0, $xpath->query('ancestor-or-self::*[@aria-hidden="true"]', $boton)->length, 'No está dentro de la zona aria-hidden.');
    }

    public function test_sin_servicios_activos_no_hay_avance(): void
    {
        DB::table('servicios')->update(['activo' => 0]);

        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertDontSee('Nuestros servicios');
        $respuesta->assertDontSee('Ver todos los servicios');
    }

    public function test_contacto_exacto_con_mapa_y_como_llegar(): void
    {
        $respuesta = $this->get('/');
        $texto = $this->texto($respuesta->getContent());
        $xpath = $this->xpath($respuesta->getContent());

        $this->assertStringContainsString('Lunes a viernes 9:00–20:00', $texto);
        $this->assertStringContainsString('Sábados 9:00–14:00', $texto);

        // /review R3: la dirección, el teléfono, el mapa y «Cómo llegar», dentro de la sección de ubicación.
        $ubicacion = $xpath->query('//*[@id="ubicacion"]')->item(0);
        $this->assertNotNull($ubicacion);
        $this->assertStringContainsString(self::DIRECCION, preg_replace('/\s+/u', ' ', $ubicacion->textContent));
        $this->assertSame(1, $xpath->query('.//a[@href="tel:+527530000000"][contains(normalize-space(), "753 000 0000")]', $ubicacion)->length, 'El teléfono se marca con un toque.');

        $mapa = $xpath->query('.//iframe[starts-with(@src, "https://www.google.com/maps?")]', $ubicacion);
        $this->assertSame(1, $mapa->length, 'Un mapa de Google incrustado.');
        $this->assertSame('https://www.google.com/maps?q='.rawurlencode(self::DIRECCION).'&output=embed', $mapa->item(0)->getAttribute('src'), 'El mapa marca la dirección de contacto.');

        // TM5: el mapa aparte se abre sin dar acceso a la página de origen.
        $llegar = $xpath->query('.//a[normalize-space()="Cómo llegar"]', $ubicacion);
        $this->assertSame(1, $xpath->query('//a[normalize-space()="Cómo llegar"]')->length, 'Ningún otro «Cómo llegar» en la página.');
        $this->assertSame(1, $llegar->length, 'Un solo «Cómo llegar», en la ubicación (A15).');
        $this->assertSame('https://www.google.com/maps/dir/?api=1&destination='.rawurlencode(self::DIRECCION), $llegar->item(0)->getAttribute('href'));
        $this->assertSame('_blank', $llegar->item(0)->getAttribute('target'));
        // /review R23: el nombre accesible avisa de la pestaña nueva y contiene el texto visible.
        $this->assertSame('Cómo llegar (se abre en una pestaña nueva)', $llegar->item(0)->getAttribute('aria-label'));
        $this->assertSame('noopener noreferrer', $llegar->item(0)->getAttribute('rel'));
        // Rediseño (D16): sin la placa («Ver la ubicación»), a la ubicación se llega por «Ubicación» de la cabecera.
        $this->assertGreaterThan(0, $xpath->query('//header//a[normalize-space()="Ubicación"][substring(@href, string-length(@href) - 9) = "#ubicacion"]')->length, 'La cabecera lleva a la sección de ubicación.');
        $this->assertSame(1, $xpath->query('//*[@id="ubicacion"]')->length);
    }

    public function test_el_logo_de_la_portada_es_decorativo(): void
    {
        // /review R21 (antes, el logo de la placa): la marca ya la nombra la cabecera; el logo grande
        // de la portada no se anuncia otra vez.
        $xpath = $this->xpath($this->get('/')->getContent());
        $portada = $this->porClase($xpath, 'portada');
        $this->assertNotNull($portada, 'Hay una portada.');
        $logo = $this->porClase($xpath, 'logo', $portada);

        $this->assertNotNull($logo, 'La portada lleva el logo.');
        $this->assertSame('true', $logo->getAttribute('aria-hidden'));
        $this->assertFalse($logo->hasAttribute('role'), 'Sin role="img" ni nombre.');
        $this->assertSame(2, $xpath->query('//*[@role="img"][@aria-label="Melissa López N. · Odontología integral"]')->length, 'La marca se anuncia en la cabecera y en el pie.');
    }

    public function test_el_mapa_es_perezoso_y_avisa_de_google(): void
    {
        $respuesta = $this->get('/');
        $xpath = $this->xpath($respuesta->getContent());
        $mapa = $xpath->query('//iframe')->item(0);

        // TM4: solo se carga al acercarse y solo envía el dominio a Google.
        $this->assertNotNull($mapa, 'Hay un mapa incrustado.');
        $this->assertSame('lazy', $mapa->getAttribute('loading'));
        $this->assertSame('strict-origin-when-cross-origin', $mapa->getAttribute('referrerpolicy'));
        $this->assertSame('Mapa de Google con la ubicación de la consulta', $mapa->getAttribute('title'));
        $this->assertStringContainsString('El mapa lo sirve Google.', $this->texto($respuesta->getContent()));
    }
}
