<?php

namespace Tests\Feature;

use App\Models\Usuario;
use DOMDocument;
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
        $respuesta->assertSeeInOrder([
            'Tu sonrisa, nuestra prioridad.',
            'Agendar mi primera cita',
            'Aviso de la puerta',
            'Sobre nosotros',
            'Nuestros servicios',
            'Encuéntranos fácilmente',
        ]);

        $xpath = $this->xpath($respuesta->getContent());
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]')->length, '«Agendar mi primera cita» lleva al registro.');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Iniciar sesión"][@href="'.route('login').'"]')->length, 'Hay un enlace visible a «Iniciar sesión».');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Crear cuenta"][@href="'.route('register').'"]')->length, 'Hay un enlace a crear una cuenta.');

        // /review R3: el horario y el teléfono del aviso de la puerta, dentro del aviso (el pie los repite).
        $aviso = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lb-aviso ")]')->item(0);
        $this->assertNotNull($aviso, 'Hay un aviso de la puerta.');
        $textoAviso = preg_replace('/\s+/u', ' ', $aviso->textContent);
        $this->assertStringContainsString('Lunes a viernes 9:00–20:00', $textoAviso);
        $this->assertStringContainsString('Sábados 9:00–14:00', $textoAviso);
        $this->assertSame(1, $xpath->query('.//a[@href="tel:+527530000000"][contains(normalize-space(), "753 000 0000")]', $aviso)->length, 'El aviso ofrece llamar.');
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
        $texto = $this->texto($this->get('/')->getContent());

        foreach (self::NOSOTROS as $parrafo) {
            $this->assertStringContainsString($parrafo, $texto);
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
        $this->assertSame(1, $llegar->length, 'Un solo «Cómo llegar» (el de la placa dice «Ver la ubicación», A15).');
        $this->assertSame('https://www.google.com/maps/dir/?api=1&destination='.rawurlencode(self::DIRECCION), $llegar->item(0)->getAttribute('href'));
        $this->assertSame('_blank', $llegar->item(0)->getAttribute('target'));
        $this->assertSame('noopener noreferrer', $llegar->item(0)->getAttribute('rel'));
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Ver la ubicación"][@href="#ubicacion"]')->length, 'La placa lleva a la sección de ubicación.');
        $this->assertSame(1, $xpath->query('//*[@id="ubicacion"]')->length);
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
