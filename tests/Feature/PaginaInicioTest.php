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
 * Ronda 5 (plan D19–D21, «La ventana del diente»): la presentación con la
 * ventana del diente sustituye a la portada a sangre; la escena de la foto, las
 * tres promesas, el índice de categorías y la tarjeta de la agenda sustituyen a
 * la hoja, las tarjetas y la franja (que se retira con su pausa, D21). Cada
 * caso de la ronda 3 pasa a su equivalente:
 * - la portada con el horario y el teléfono → la presentación;
 * - la hoja de «Sobre nosotros» → `.pub-about`;
 * - las tarjetas con «N tratamientos» → el índice de categorías;
 * - el logo decorativo de la portada → la marca anunciada solo en la cabecera;
 * - la franja que se puede pausar → sin equivalente (D21).
 */
class PaginaInicioTest extends TestCase
{
    use DatabaseTransactions;

    private const DIRECCION = 'Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950';

    private const NOSOTROS = [
        'Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.',
        'Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.',
    ];

    /** Las tres promesas de «Antes de cada tratamiento» (design.md de la ronda 5), en orden. */
    private const PROMESAS = [
        'Qué vamos a hacer' => 'Lo vemos juntos, con palabras claras, antes de empezar.',
        'Cuánto dura' => 'Cuántas citas lleva y cuánto tiempo cada una, para que lo acomodes a tu semana.',
        'Qué opciones tienes' => 'Si hay más de un camino, te los mostramos para que decidas con calma.',
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
        // Ronda 5 (CA1): presentación → escena → «Sobre nosotros» → «Antes de cada tratamiento» →
        // servicios → agenda → ubicación → pie, en el orden del HTML.
        $respuesta->assertSeeInOrder([
            '<section class="pub-hero',
            'Agendar mi primera cita',
            '<section class="pub-open',
            'id="nosotros"',
            'Antes de cada tratamiento',
            'id="servicios"',
            '<section class="pub-book',
            'id="ubicacion"',
            '<footer class="pub-foot',
        ], false);

        $xpath = $this->xpath($respuesta->getContent());
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]')->length, '«Agendar mi primera cita» lleva al registro.');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Iniciar sesión"][@href="'.route('login').'"]')->length, 'Hay un enlace visible a «Iniciar sesión».');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Crear cuenta"][@href="'.route('register').'"]')->length, 'Hay un enlace a crear una cuenta.');

        // Ronda 5 (D21): sin la placa, la portada a sangre, la hoja, las tarjetas ni la franja.
        foreach (['lb-placa', 'lb-aviso', 'portada', 'hoja', 'tarjeta', 'marquesina'] as $clase) {
            $this->assertNull($this->porClase($xpath, $clase), "Sin «.{$clase}» de las rondas anteriores.");
        }
    }

    public function test_la_presentacion_lleva_el_horario_y_el_telefono(): void
    {
        // Sustituye a «la portada lleva el horario y el teléfono» (/review R3): junto a «Agendar mi
        // primera cita» (CA1); la ubicación y el pie los repiten.
        $xpath = $this->xpath($this->get('/')->getContent());
        $presentacion = $this->porClase($xpath, 'pub-hero');
        $this->assertNotNull($presentacion, 'Hay una presentación.');

        $h1 = $xpath->query('//h1');
        $this->assertSame(1, $h1->length, 'Un solo titular en la página.');
        $this->assertSame(1, $xpath->query('.//h1', $presentacion)->length, 'El titular está en la presentación.');
        $this->assertSame('Tu sonrisa, nuestra prioridad.', $this->textoDe($h1->item(0)));
        $this->assertSame(1, $xpath->query('.//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]', $presentacion)->length);

        $this->assertStringContainsString('Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00', $this->textoDe($presentacion), 'El horario completo, también sin JavaScript.');
        $this->assertSame(1, $xpath->query('.//a[@href="tel:+527530000000"][contains(normalize-space(), "753 000 0000")]', $presentacion)->length, 'La presentación ofrece llamar.');
    }

    public function test_la_ventana_del_diente_lleva_la_foto_propia(): void
    {
        // D20: la foto de la marca, propia de la clínica: recortada en la ventana y entera en la escena.
        $xpath = $this->xpath($this->get('/')->getContent());
        $ventana = $this->porClase($xpath, 'pub-tooth', $this->porClase($xpath, 'pub-hero'));
        $this->assertNotNull($ventana, 'La ventana del diente está en la presentación.');

        $recorte = $xpath->query('.//*[local-name()="image" or local-name()="img"][contains(@href, "/images/brand/access-recorte.webp") or contains(@src, "/images/brand/access-recorte.webp")]', $ventana);
        $this->assertSame(1, $recorte->length, 'La ventana enmarca el recorte de la foto de la marca.');
        $this->assertSame('Antes de empezar, te explicamos cada paso.', $this->textoDe($this->porClase($xpath, 'pub-tooth__tag', $ventana)));

        $escena = $this->porClase($xpath, 'pub-open');
        $this->assertNotNull($escena, 'Hay una escena de la foto.');
        $foto = $xpath->query('.//img[contains(concat(" ", normalize-space(@class), " "), " pub-open__photo ")]', $escena)->item(0);
        $this->assertNotNull($foto);
        $this->assertStringEndsWith('/images/brand/access.webp', $foto->getAttribute('src'));
        $this->assertNotSame('', trim($foto->getAttribute('alt')), 'La foto es de la clínica y lleva su texto alternativo.');
        $this->assertSame('900', $foto->getAttribute('width'));
        $this->assertSame('900', $foto->getAttribute('height'));
    }

    public function test_la_pastilla_toma_el_horario_y_la_zona_de_la_configuracion(): void
    {
        // /analyze A25: la pastilla la escribe el script con estos datos; sin él, vacía.
        $xpath = $this->xpath($this->get('/')->getContent());
        $vivo = $xpath->query('//*[@data-live]');

        $this->assertSame(1, $vivo->length, 'Un elemento vivo para la pastilla.');
        $pastilla = $vivo->item(0);
        $this->assertStringContainsString(' pub-status ', ' '.$pastilla->getAttribute('class').' ');
        $this->assertSame(config('clinica.contacto.horario_semanal'), json_decode($pastilla->getAttribute('data-horario'), true));
        $this->assertSame(config('clinica.zona_horaria'), $pastilla->getAttribute('data-zona'));

        $texto = $this->porClase($xpath, 'pub-status__text', $pastilla);
        $this->assertNotNull($texto);
        $this->assertSame('polite', $texto->getAttribute('aria-live'));
        $this->assertSame('', $this->textoDe($pastilla), 'Sin JavaScript, la pastilla no dice nada (el horario completo va al lado).');
        $this->assertSame('true', $this->porClase($xpath, 'pub-status__dot', $pastilla)->getAttribute('aria-hidden'));
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

        // Sustituye a «la hoja de Sobre nosotros» (A31): el texto de CA3, dentro de su sección.
        $xpath = $this->xpath($html);
        $seccion = $xpath->query('//section[@id="nosotros"]')->item(0);
        $this->assertNotNull($seccion);
        $this->assertStringContainsString(' pub-about ', ' '.$seccion->getAttribute('class').' ');
        $this->assertSame(1, $xpath->query('.//h2[normalize-space()="Sobre nosotros"]', $seccion)->length);

        $cuerpo = $this->porClase($xpath, 'pub-about__body', $seccion);
        $this->assertNotNull($cuerpo);
        foreach (self::NOSOTROS as $parrafo) {
            $this->assertStringContainsString($parrafo, $this->textoDe($cuerpo));
        }
    }

    public function test_las_tres_promesas_con_su_texto(): void
    {
        $xpath = $this->xpath($this->get('/')->getContent());
        $seccion = $this->porClase($xpath, 'pub-promise');
        $this->assertNotNull($seccion, 'Hay una sección «Antes de cada tratamiento».');
        $this->assertSame(1, $xpath->query('.//h2[normalize-space()="Antes de cada tratamiento"]', $seccion)->length);

        $items = $xpath->query('.//ol/li[contains(concat(" ", normalize-space(@class), " "), " pub-promise__item ")]', $seccion);
        $this->assertSame(3, $items->length, 'Tres promesas, en una lista ordenada.');
        $i = 0;
        foreach (self::PROMESAS as $titulo => $texto) {
            $item = $items->item($i++);
            $this->assertSame($titulo, $this->textoDe($xpath->query('.//h3', $item)->item(0)));
            $this->assertSame($texto, $this->textoDe($xpath->query('.//p', $item)->item(0)));
            $this->assertSame(0, $xpath->query('.//*[local-name()="svg"][not(@aria-hidden="true")]', $item)->length, 'El icono de la promesa es decorativo.');
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

    public function test_el_indice_cuenta_los_tratamientos_de_cada_categoria(): void
    {
        // Sustituye a «cada tarjeta cuenta sus tratamientos» (A26): las filas del índice.
        $this->crearServicio('Servicio038 Dos A', 'Categoria038 Dos');
        $this->crearServicio('Servicio038 Dos B', 'Categoria038 Dos');
        $this->crearServicio('Servicio038 Uno', 'Categoria038 Uno');
        $this->crearServicio('Servicio038 Uno Inactivo', 'Categoria038 Uno', activo: false);

        $xpath = $this->xpath($this->get('/')->getContent());
        $servicios = $this->xpath($this->get('/servicios')->getContent());
        $indice = $this->porClase($xpath, 'pub-index');
        $this->assertNotNull($indice, 'Hay un índice de categorías.');
        $fila = fn (string $categoria) => $xpath->query('.//li/a[.//*[contains(concat(" ", normalize-space(@class), " "), " pub-index__name ")][normalize-space()="'.$categoria.'"]]', $indice)->item(0);

        foreach (['Categoria038 Dos' => '2 tratamientos', 'Categoria038 Uno' => '1 tratamiento'] as $categoria => $cuenta) {
            $enlace = $fila($categoria);
            $this->assertNotNull($enlace, "Hay una fila para {$categoria}.");
            $this->assertSame("{$categoria} {$cuenta}", $this->textoDe($enlace), 'Solo el nombre y el número de tratamientos activos, sin servicios sueltos.');
            $this->assertSame($cuenta, $this->textoDe($this->porClase($xpath, 'pub-index__count', $enlace)));
            $this->assertSame('true', $this->porClase($xpath, 'pub-index__go', $enlace)->getAttribute('aria-hidden'), 'La flecha es decorativa.');

            // /analyze A26: el ancla de la fila existe en /servicios y es la de su categoría.
            $href = $enlace->getAttribute('href');
            $this->assertMatchesRegularExpression('~^'.preg_quote(url('/servicios'), '~').'#categoria-\d+$~', $href);
            $seccion = $servicios->query('//*[@id="'.substr($href, strpos($href, '#') + 1).'"]')->item(0);
            $this->assertNotNull($seccion, "/servicios tiene el ancla de {$categoria}.");
            $this->assertSame($categoria, $this->textoDe($servicios->query('.//h2', $seccion)->item(0)));
        }
    }

    public function test_la_tarjeta_de_la_agenda_lleva_al_registro(): void
    {
        $xpath = $this->xpath($this->get('/')->getContent());
        $tarjeta = $this->porClase($xpath, 'pub-book__card');

        $this->assertNotNull($tarjeta, 'Hay una tarjeta de la agenda.');
        $this->assertSame(1, $xpath->query('.//h2[normalize-space()="Agenda sin llamar."]', $tarjeta)->length);
        $this->assertStringContainsString('Crea tu cuenta y elige el día de tu cita en línea', $this->textoDe($tarjeta));
        $this->assertSame(1, $xpath->query('.//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]', $tarjeta)->length);
        $this->assertSame('true', $this->porClase($xpath, 'pub-book__mark', $tarjeta)->getAttribute('aria-hidden'), 'La marca de agua del diente es decorativa.');
    }

    public function test_la_semana_marca_lunes_a_viernes_sabados_y_domingo_cerrado(): void
    {
        $xpath = $this->xpath($this->get('/')->getContent());
        $semana = $xpath->query('//*[@id="ubicacion"]//dl[@data-semana]')->item(0);
        $this->assertNotNull($semana, 'La ubicación lleva la semana.');

        $filas = [];
        foreach ($xpath->query('./div', $semana) as $fila) {
            $filas[$fila->getAttribute('data-day')] = $this->textoDe($xpath->query('./dt', $fila)->item(0)).' '.$this->textoDe($xpath->query('./dd', $fila)->item(0));
        }
        $this->assertSame(['1-5' => 'Lunes a viernes 9:00–20:00', '6' => 'Sábados 9:00–14:00', '0' => 'Domingos Cerrado'], $filas);
    }

    public function test_sin_servicios_activos_no_hay_avance(): void
    {
        DB::table('servicios')->update(['activo' => 0]);

        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertDontSee('Nuestros servicios');
        $respuesta->assertDontSee('Ver todos los servicios');
        $this->assertNull($this->porClase($this->xpath($respuesta->getContent()), 'pub-index'), 'Sin índice de categorías.');
    }

    public function test_contacto_exacto_con_mapa_y_como_llegar(): void
    {
        $respuesta = $this->get('/');
        $xpath = $this->xpath($respuesta->getContent());

        // /review R3: la dirección, el horario, el teléfono, el mapa y «Cómo llegar», dentro de la ubicación.
        $ubicacion = $xpath->query('//*[@id="ubicacion"]')->item(0);
        $this->assertNotNull($ubicacion);
        $texto = $this->textoDe($ubicacion);
        $this->assertStringContainsString(self::DIRECCION, $texto);
        $this->assertStringContainsString('Lunes a viernes 9:00–20:00', $texto);
        $this->assertStringContainsString('Sábados 9:00–14:00', $texto);
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
        $this->assertGreaterThan(0, $xpath->query('//header//a[normalize-space()="Ubicación"][substring(@href, string-length(@href) - 9) = "#ubicacion"]')->length, 'La cabecera lleva a la sección de ubicación.');
        $this->assertSame(1, $xpath->query('//*[@id="ubicacion"]')->length);
    }

    public function test_la_marca_se_anuncia_solo_en_la_cabecera(): void
    {
        // Sustituye a «el logo de la portada es decorativo» (/review R21): la ventana del diente y la
        // marca de agua no anuncian la marca otra vez; el nombre del pie tampoco.
        $xpath = $this->xpath($this->get('/')->getContent());

        $marcas = $xpath->query('//*[@role="img"][@aria-label="Melissa López N. · Odontología integral"]');
        $this->assertSame(1, $marcas->length, 'La marca se anuncia una vez.');
        $this->assertSame(1, $xpath->query('ancestor::header', $marcas->item(0))->length, 'Y es en la cabecera.');
        $this->assertSame(0, $xpath->query('.//*[@role="img"]', $this->porClase($xpath, 'pub-hero'))->length, 'La presentación no repite la marca.');
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
