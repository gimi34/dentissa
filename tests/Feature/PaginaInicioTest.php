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
 *
 * Ronda 6 (plan D23, `design.md` con `c5`): la presentación lleva la foto propia en la arcada
 * (sustituye a la ventana del diente); «Sobre nosotros» es una cita destacada con los dos párrafos y
 * los tres pasos en línea (sustituye a la sección «Antes de cada tratamiento» con las promesas en
 * tarjetas, /analyze A45); la escena es «consulta», la foto que se abre (sustituye a la escena
 * `ventana`); la agenda muestra la semana (sustituye a la tarjeta con el diente); el índice lleva su
 * número. El texto de CA3 se compara uniendo la cita y los párrafos (A44).
 */
class PaginaInicioTest extends TestCase
{
    use DatabaseTransactions;

    private const DIRECCION = 'Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950';

    private const NOSOTROS = [
        'Somos una consulta de odontología integral en el centro de Lázaro Cárdenas. Atendemos a toda la familia, desde la primera revisión de los niños hasta la ortodoncia y las prótesis de los adultos, en un mismo lugar y con el mismo equipo.',
        'Antes de cada tratamiento te explicamos qué vamos a hacer, cuánto dura y qué opciones tienes, para que decidas con calma. Y para que no tengas que llamar en horario de trabajo, puedes crear tu cuenta y agendar tus citas en línea.',
    ];

    /** Las tres promesas de «Antes de cada tratamiento» (design.md de las rondas 5 y 6: los pasos de `c5`), en orden. */
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
        // Ronda 6 (CA1, A45): presentación → «Sobre nosotros» → escena «consulta» → servicios → agenda →
        // ubicación → pie, en el orden del HTML.
        $respuesta->assertSeeInOrder([
            '<section class="pub-hero',
            'Agendar mi primera cita',
            'id="nosotros"',
            '<section class="pub-consulta',
            'id="servicios"',
            '<section class="pub-book',
            'id="ubicacion"',
            '<footer class="pub-foot',
        ], false);

        $xpath = $this->xpath($respuesta->getContent());
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Agendar mi primera cita"][@href="'.route('register').'"]')->length, '«Agendar mi primera cita» lleva al registro.');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Iniciar sesión"][@href="'.route('login').'"]')->length, 'Hay un enlace visible a «Iniciar sesión».');
        $this->assertGreaterThan(0, $xpath->query('//a[normalize-space()="Crear cuenta"][@href="'.route('register').'"]')->length, 'Hay un enlace a crear una cuenta.');

        // Rondas anteriores (D21, D23): sin la placa, la portada, la hoja, las tarjetas, la franja, la
        // ventana del diente, su escena ni las promesas en tarjetas.
        foreach (['lb-placa', 'lb-aviso', 'portada', 'hoja', 'tarjeta', 'marquesina', 'pub-tooth', 'pub-open', 'pub-promise'] as $clase) {
            $this->assertNull($this->porClase($xpath, $clase), "Sin «.{$clase}» de las rondas anteriores.");
        }
    }

    public function test_la_presentacion_lleva_el_horario_y_el_telefono(): void
    {
        // Sustituye a «la portada lleva el horario y el teléfono» (/review R3): junto a «Agendar mi
        // primera cita» (CA1); la ubicación y el pie los repiten. Ronda 6: además, «Iniciar sesión» y
        // «Crear cuenta» a la vista (en el móvil, sin abrir el menú).
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

        $acceso = $this->porClase($xpath, 'pub-acceso', $presentacion);
        $this->assertNotNull($acceso, 'La presentación lleva el acceso.');
        $this->assertSame(1, $xpath->query('.//a[normalize-space()="Iniciar sesión"][@href="'.route('login').'"]', $acceso)->length);
        $this->assertSame(1, $xpath->query('.//a[normalize-space()="Crear cuenta"][@href="'.route('register').'"]', $acceso)->length);
    }

    public function test_la_arcada_y_la_escena_llevan_la_foto_propia(): void
    {
        // Sustituye a «la ventana del diente lleva la foto propia» (D20, D23): la foto de la marca va en
        // el marco de la arcada en la presentación y se abre entera en la escena «consulta».
        $xpath = $this->xpath($this->get('/')->getContent());
        $arcada = $this->porClase($xpath, 'pub-arcada', $this->porClase($xpath, 'pub-hero'));
        $this->assertNotNull($arcada, 'La arcada está en la presentación.');
        $foto = $xpath->query('.//img', $arcada);
        $this->assertSame(1, $foto->length, 'La arcada lleva una foto.');
        $this->assertStringEndsWith('/images/brand/access.webp', $foto->item(0)->getAttribute('src'));
        $this->assertNotSame('', trim($foto->item(0)->getAttribute('alt')), 'La foto es de la clínica y lleva su texto alternativo.');
        $this->assertNotSame('lazy', $foto->item(0)->getAttribute('loading'), 'Primera pantalla: no es perezosa.');

        $escena = $xpath->query('//section[@data-scene="consulta"]')->item(0);
        $this->assertNotNull($escena, 'Hay una escena «consulta».');
        $this->assertStringContainsString(' pub-consulta ', ' '.$escena->getAttribute('class').' ');
        $img = $xpath->query('.//img', $escena)->item(0);
        $this->assertNotNull($img);
        $this->assertStringEndsWith('/images/brand/access.webp', $img->getAttribute('src'));
        $this->assertNotSame('', trim($img->getAttribute('alt')));
        $this->assertSame('900', $img->getAttribute('width'));
        $this->assertSame('900', $img->getAttribute('height'));
        $this->assertSame('La consulta de Melissa López N., en el centro de Lázaro Cárdenas.', $this->textoDe($xpath->query('.//figcaption', $escena)->item(0)));
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

    /** 038 CA2, reemplazado por 044 CA5: con sesión se ve la página, con «Ir a mi panel» hacia el panel de cada rol. */
    public function test_con_sesion_cada_rol_ve_la_pagina_con_ir_a_mi_panel(): void
    {
        foreach (['admin', 'dentista', 'asistente', 'cliente'] as $rol) {
            $this->actingAs($this->crearUsuario($rol))
                ->get('/')
                ->assertOk()
                ->assertSee('Ir a mi panel')
                ->assertSee('href="'.route("{$rol}.dashboard").'"', false);
        }
    }

    public function test_sobre_nosotros_con_el_texto_aprobado(): void
    {
        // Ronda 6 (A44): la primera frase de CA3 es la cita destacada y el resto va en dos párrafos; unidos,
        // en orden, son el texto exacto de la spec.
        $xpath = $this->xpath($this->get('/')->getContent());
        $seccion = $xpath->query('//section[@id="nosotros"]')->item(0);
        $this->assertNotNull($seccion);
        $this->assertStringContainsString(' pub-about ', ' '.$seccion->getAttribute('class').' ');
        $this->assertSame(1, $xpath->query('.//h2[normalize-space()="Sobre nosotros"]', $seccion)->length);

        $cita = $this->porClase($xpath, 'pub-cita', $seccion);
        $this->assertNotNull($cita, 'Hay una cita destacada.');
        $this->assertSame('Somos una consulta de odontología integral en el centro de Lázaro Cárdenas.', $this->textoDe($cita));

        $partes = [$this->textoDe($cita)];
        foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " pub-about__body ")]/p', $seccion) as $parrafo) {
            $partes[] = $this->textoDe($parrafo);
        }
        $this->assertSame(implode(' ', self::NOSOTROS), implode(' ', $partes), 'La cita y los párrafos, en orden, son el texto de CA3.');
    }

    public function test_los_tres_pasos_con_su_texto(): void
    {
        // Sustituye a «las tres promesas con su texto» (A45): los pasos en línea dentro de «Sobre nosotros».
        $xpath = $this->xpath($this->get('/')->getContent());
        $pasos = $xpath->query('//section[@id="nosotros"]//ol[contains(concat(" ", normalize-space(@class), " "), " pub-pasos ")]')->item(0);
        $this->assertNotNull($pasos, 'Los pasos están en «Sobre nosotros».');
        $this->assertSame('Antes de cada tratamiento', $pasos->getAttribute('aria-label'));

        $items = $xpath->query('./li[contains(concat(" ", normalize-space(@class), " "), " pub-paso ")]', $pasos);
        $this->assertSame(3, $items->length, 'Tres pasos, en una lista ordenada.');
        $i = 0;
        foreach (self::PROMESAS as $titulo => $texto) {
            $item = $items->item($i++);
            $this->assertSame($titulo, $this->textoDe($xpath->query('.//h3', $item)->item(0)));
            $this->assertSame($texto, $this->textoDe($xpath->query('.//p', $item)->item(0)));
            $this->assertSame('true', $this->porClase($xpath, 'pub-paso__n', $item)->getAttribute('aria-hidden'), 'El número es decorativo (lo da la lista).');
        }
        $this->assertSame(1, $xpath->query('//section[@id="nosotros"]//a[normalize-space()="Crear mi cuenta"][@href="'.route('register').'"]')->length);
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
            $this->assertMatchesRegularExpression('/^0\d '.preg_quote("{$categoria} {$cuenta}", '/').'$/u', $this->textoDe($enlace), 'Solo el número de la fila, el nombre y el número de tratamientos activos, sin servicios sueltos.');
            $this->assertSame($cuenta, $this->textoDe($this->porClase($xpath, 'pub-index__count', $enlace)));
            $this->assertMatchesRegularExpression('/^0\d$/', $this->textoDe($this->porClase($xpath, 'pub-index__n', $enlace)), 'Ronda 6: cada fila lleva su número.');
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
        $this->assertSame('true', $this->porClase($xpath, 'pub-book__arco', $tarjeta)->getAttribute('aria-hidden'), 'Los arcos de fondo son decorativos.');

        // Ronda 6 (D23): la semana real de la consulta, sacada del horario semanal de la configuración.
        $semana = $xpath->query('.//ul[contains(concat(" ", normalize-space(@class), " "), " pub-semana ")]/li', $tarjeta);
        $this->assertSame(7, $semana->length, 'Los siete días.');
        $dias = array_map(fn ($li) => $this->textoDe($li), iterator_to_array($semana));
        $this->assertSame(['Lun 9:00–20:00', 'Mar 9:00–20:00', 'Mié 9:00–20:00', 'Jue 9:00–20:00', 'Vie 9:00–20:00', 'Sáb 9:00–14:00', 'Dom Cerrado'], $dias);
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
        // /review R34: el horario de CA5, tal cual, en la sección de contacto (no solo sus partes).
        $this->assertStringContainsString('Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00', $texto);
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
        // Sustituye a «el logo de la portada es decorativo» (/review R21): la foto de la arcada y la
        // marca de agua no anuncian la marca otra vez; el nombre del pie tampoco.
        $xpath = $this->xpath($this->get('/')->getContent());

        $marcas = $xpath->query('//*[@role="img"][@aria-label="Melissa López N. · Odontología integral"]');
        $this->assertSame(1, $marcas->length, 'La marca se anuncia una vez.');
        $this->assertSame(1, $xpath->query('ancestor::header', $marcas->item(0))->length, 'Y es en la cabecera.');
        // La foto de la arcada sí es una imagen con nombre (la de la clínica, D20, D23), pero no el de la marca.
        $this->assertSame(0, $xpath->query('.//*[@role="img"][@aria-label="Melissa López N. · Odontología integral"]', $this->porClase($xpath, 'pub-hero'))->length, 'La presentación no repite la marca.');
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

    // ── Spec 053: «Escríbenos por WhatsApp» en la primera pantalla y en contacto ──────────────────

    /** Los elementos hijos de un nodo, sin textos ni comentarios. */
    private function hijos(DOMNode $nodo): array
    {
        return array_values(array_filter(iterator_to_array($nodo->childNodes), fn ($hijo) => $hijo instanceof DOMElement));
    }

    private function tieneClase(DOMElement $elemento, string $clase): bool
    {
        return in_array($clase, preg_split('/\s+/', trim($elemento->getAttribute('class'))), true);
    }

    /** `/` como visitante (null) o con la sesión de un rol. */
    private function inicioComo(?string $rol): string
    {
        $peticion = $rol === null ? $this : $this->actingAs($this->crearUsuario($rol));

        return $peticion->get('/')->assertOk()->getContent();
    }

    /** CA1 (amplía 038 CA1): junto al horario y el teléfono, con y sin sesión. */
    public function test_whatsapp_en_la_primera_pantalla_junto_al_telefono(): void
    {
        foreach ([null, 'admin', 'dentista', 'asistente', 'cliente'] as $rol) {
            $quien = $rol ?? 'visitante';
            $xpath = $this->xpath($this->inicioComo($rol));
            $presentacion = $this->porClase($xpath, 'pub-hero');
            $datos = $this->porClase($xpath, 'pub-hero__facts', $presentacion);
            $this->assertNotNull($datos, "{$quien}: la lista del horario y el teléfono.");

            // El horario sigue en su propio elemento de la lista; el teléfono y WhatsApp forman un grupo.
            $this->assertSame(1, $xpath->query('./li[normalize-space()="Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00"]', $datos)->length, "{$quien}: el horario en la misma lista.");
            $grupo = $xpath->query('./li[contains(concat(" ", normalize-space(@class), " "), " pub-hero__contacto ")]', $datos);
            $this->assertSame(1, $grupo->length, "{$quien}: un grupo de contacto en la lista.");

            $hijos = $this->hijos($grupo->item(0));
            $this->assertCount(2, $hijos, "{$quien}: el teléfono y WhatsApp, nada más.");
            $this->assertSame('tel:+527530000000', $hijos[0]->getAttribute('href'), "{$quien}: primero el teléfono.");
            $this->assertSame('753 000 0000', $this->textoDe($hijos[0]));
            $this->assertTrue($this->tieneClase($hijos[1], 'pub-wa'), "{$quien}: después, el botón de WhatsApp.");
            $this->assertSame('Escríbenos por WhatsApp', $this->textoDe($hijos[1]));

            $this->assertSame(1, $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " pub-wa ")]', $presentacion)->length, "{$quien}: un solo WhatsApp en la presentación.");
        }
    }

    /** CA2 (amplía 038 CA5): en contacto, después del teléfono y antes de «Cómo llegar». */
    public function test_whatsapp_en_contacto_junto_al_telefono(): void
    {
        $xpath = $this->xpath($this->inicioComo(null));
        $ubicacion = $xpath->query('//*[@id="ubicacion"]')->item(0);
        $telefono = $this->porClase($xpath, 'pub-visit__tel', $ubicacion);
        $this->assertNotNull($telefono, 'El teléfono de contacto.');

        $acciones = $telefono->nextElementSibling;
        $this->assertNotNull($acciones, 'Algo sigue al teléfono.');
        $this->assertTrue($this->tieneClase($acciones, 'pub-visit__acciones'), 'Justo después del teléfono, el grupo de acciones.');

        $hijos = $this->hijos($acciones);
        $this->assertCount(2, $hijos, 'WhatsApp y «Cómo llegar», nada más.');
        $this->assertTrue($this->tieneClase($hijos[0], 'pub-wa'), 'Primero WhatsApp.');
        $this->assertSame('Escríbenos por WhatsApp', $this->textoDe($hijos[0]));
        $this->assertSame('Cómo llegar', $this->textoDe($hijos[1]), 'Después, «Cómo llegar».');

        $this->assertSame(2, $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " pub-wa ")]')->length, 'Dos botones en la página: la presentación y el contacto.');
    }

    /** El enlace de los dos botones (plan «Contratos y datos»). */
    private const WHATSAPP = 'https://wa.me/527530000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20agendar%20una%20cita.';

    /** Los dos botones de WhatsApp de la página, en el orden del documento. */
    private function botonesWhatsapp(DOMXPath $xpath): array
    {
        return iterator_to_array($xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " pub-wa ")]'));
    }

    /** CA3, TM1, TM4: el número del teléfono de la página, el mensaje escrito y la página sigue abierta. */
    public function test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje(): void
    {
        $botones = $this->botonesWhatsapp($this->xpath($this->inicioComo(null)));
        $this->assertCount(2, $botones);

        $digitos = preg_replace('/\D/', '', config('clinica.contacto.telefono_enlace'));
        $this->assertSame('527530000000', $digitos, 'El teléfono de la página, con la clave de México.');

        foreach ($botones as $i => $boton) {
            $href = $boton->getAttribute('href');
            $this->assertSame(self::WHATSAPP, $href, "Botón {$i}: el enlace exacto.");
            $this->assertSame("https://wa.me/{$digitos}", strtok($href, '?'), "Botón {$i}: el mismo número que el teléfono de la página.");
            parse_str((string) parse_url($href, PHP_URL_QUERY), $consulta);
            $this->assertSame(['text' => 'Hola, quiero información para agendar una cita.'], $consulta, "Botón {$i}: solo el mensaje escrito.");

            // En un ordenador, WhatsApp Web se abre en otra pestaña sin acceso a esta (TM1).
            $this->assertSame('_blank', $boton->getAttribute('target'), "Botón {$i}: en otra pestaña.");
            $rel = preg_split('/\s+/', trim($boton->getAttribute('rel')));
            $this->assertContains('noopener', $rel, "Botón {$i}: noopener.");
            $this->assertContains('noreferrer', $rel, "Botón {$i}: noreferrer.");
        }
    }

    /** CA4: un enlace del HTML del servidor, sin nada que dependa de JavaScript. */
    public function test_el_boton_no_depende_de_javascript(): void
    {
        $botones = $this->botonesWhatsapp($this->xpath($this->inicioComo(null)));
        $this->assertCount(2, $botones, 'Los dos botones llegan en el HTML del servidor.');

        foreach ($botones as $i => $boton) {
            $this->assertSame('a', $boton->tagName, "Botón {$i}: un enlace.");
            $this->assertStringStartsWith('https://wa.me/', $boton->getAttribute('href'), "Botón {$i}: con su destino en el HTML.");
            foreach (iterator_to_array($boton->attributes) as $atributo) {
                $this->assertDoesNotMatchRegularExpression('/^(on|data-)/i', $atributo->name, "Botón {$i}: sin manejadores ni datos para publico.js ({$atributo->name}).");
            }
        }
    }

    /** CA5 (abuso), TM2: el mismo enlace para todos, sin datos de la sesión ni de la visita. */
    public function test_el_enlace_es_el_mismo_para_todos(): void
    {
        $visitas = ['visitante' => $this->get('/')->assertOk()->getContent()];
        $datos = [];
        foreach (['admin', 'dentista', 'asistente', 'cliente'] as $i => $rol) {
            $usuario = Usuario::create([
                'nombre' => "Nombre053{$rol}",
                'apellido' => "Apellido053{$rol}",
                'email' => "wa053.{$rol}.".Str::random(8).'@example.test',
                'rol' => $rol,
                'telefono' => "55512340{$i}",
                'activo' => 1,
                'password_hash' => Hash::make('secreto-de-pruebas'),
            ]);
            $datos[$rol] = [$usuario->nombre, $usuario->apellido, $usuario->email, $usuario->telefono];
            $visitas[$rol] = $this->actingAs($usuario)->get('/')->assertOk()->getContent();
        }
        // /analyze A6: los datos de la visita tampoco entran en el enlace.
        auth()->logout();
        $visitas['visita con parámetros'] = $this->withHeader('Referer', 'https://otro.example.test/?nombre=Referido053')
            ->get('/?utm_source=Campana053&nombre=Visita053')->assertOk()->getContent();
        $datos['visita con parámetros'] = ['Campana053', 'Visita053', 'Referido053', 'otro.example.test'];

        foreach ($visitas as $quien => $html) {
            $hrefs = array_map(fn ($boton) => $boton->getAttribute('href'), $this->botonesWhatsapp($this->xpath($html)));
            $this->assertSame([self::WHATSAPP, self::WHATSAPP], $hrefs, "{$quien}: el mismo enlace en los dos botones.");
            foreach ($datos[$quien] ?? [] as $dato) {
                foreach ($hrefs as $href) {
                    $this->assertStringNotContainsStringIgnoringCase($dato, rawurldecode($href), "{$quien}: el enlace no lleva «{$dato}».");
                    $this->assertStringNotContainsStringIgnoringCase(rawurlencode($dato), $href, "{$quien}: el enlace no lleva «{$dato}» codificado.");
                }
            }
        }
    }

    /** TM3 y RNF de privacidad: nada de WhatsApp ni de Meta se carga al abrir la página (por host, A5). */
    public function test_no_carga_nada_de_whatsapp_ni_de_meta(): void
    {
        $xpath = $this->xpath($this->inicioComo(null));
        $terceros = ['wa.me', 'whatsapp.com', 'whatsapp.net', 'facebook.com', 'fbcdn.net', 'meta.com'];
        $deTerceros = function (string $url) use ($terceros): bool {
            $host = strtolower((string) parse_url(trim($url), PHP_URL_HOST));
            foreach ($terceros as $dominio) {
                if ($host === $dominio || str_ends_with($host, '.'.$dominio)) {
                    return true;
                }
            }

            return false;
        };

        $recursos = [];
        foreach ($xpath->query('//*[@src]/@src | //link[@href]/@href | //*[local-name()="image" or local-name()="use"]/@href | //object[@data]/@data | //*[@poster]/@poster') as $atributo) {
            $recursos[] = $atributo->value;
        }
        foreach ($xpath->query('//*[@srcset]/@srcset') as $atributo) {
            foreach (explode(',', $atributo->value) as $candidato) {
                $recursos[] = strtok(trim($candidato), ' ');
            }
        }
        $this->assertNotEmpty($recursos, 'La página carga sus propios recursos.');
        foreach ($recursos as $url) {
            $this->assertFalse($deTerceros($url), "No se carga «{$url}» de WhatsApp ni de Meta.");
        }

        // Solo los botones apuntan a WhatsApp: se contacta con WhatsApp al pulsarlos, no antes.
        foreach ($xpath->query('//a[@href]') as $enlace) {
            if ($deTerceros($enlace->getAttribute('href'))) {
                $this->assertTrue($this->tieneClase($enlace, 'pub-wa'), 'Solo «Escríbenos por WhatsApp» enlaza con WhatsApp: '.$enlace->getAttribute('href'));
                $this->assertSame('wa.me', parse_url($enlace->getAttribute('href'), PHP_URL_HOST));
            }
        }
    }

    /** RNF de accesibilidad: el nombre empieza por el texto visible y avisa de que abre WhatsApp (A9). */
    public function test_el_boton_avisa_de_que_abre_whatsapp(): void
    {
        $xpath = $this->xpath($this->inicioComo(null));
        $botones = $this->botonesWhatsapp($xpath);
        $this->assertCount(2, $botones);

        foreach ($botones as $i => $boton) {
            $nombre = $boton->getAttribute('aria-label');
            $this->assertSame('Escríbenos por WhatsApp (se abre WhatsApp)', $nombre, "Botón {$i}: el nombre accesible.");
            $this->assertStringStartsWith($this->textoDe($boton), $nombre, "Botón {$i}: empieza por el texto visible (WCAG 2.5.3).");

            $iconos = $xpath->query('.//svg', $boton);
            $this->assertSame(1, $iconos->length, "Botón {$i}: con el logotipo.");
            $this->assertSame('true', $iconos->item(0)->getAttribute('aria-hidden'), "Botón {$i}: el logotipo no se anuncia.");
            $this->assertSame('false', $iconos->item(0)->getAttribute('focusable'), "Botón {$i}: ni recibe el foco.");
        }
    }
}
