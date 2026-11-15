<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Navegación interna (spec 046): migas de pan en las 18 pantallas de segundo nivel del panel, con un
 * caso por pantalla y rol. Los proveedores solo dan la clave del caso; la dirección y el camino
 * esperado se resuelven con los datos del escenario (`caso()`).
 *
 * El aspecto en un navegador real y la comparación del escritorio los comprueba
 * `docs/design/capturar.mjs --medir` (plan, Estrategia de pruebas).
 */
class NavegacionInternaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita, ReglasCss;

    /** Pantallas de segundo nivel y los roles que las abren (spec, «Pantallas y caminos»; `routes/web.php`). */
    private const PANTALLAS = [
        'nueva cita' => ['admin', 'dentista', 'asistente'],
        'detalle de cita' => ['admin', 'dentista', 'asistente'],
        'reprogramar cita' => ['admin', 'asistente'],
        'nuevo paciente' => ['admin', 'asistente'],
        'ficha del paciente' => ['admin', 'dentista', 'asistente'],
        'editar paciente' => ['admin', 'dentista'],
        'nuevo expediente' => ['admin', 'dentista'],
        'expediente' => ['admin', 'dentista', 'asistente'],
        'nuevo miembro' => ['admin'],
        'ficha del personal' => ['admin'],
        'editar personal' => ['admin'],
        'nuevo usuario' => ['admin'],
        'ficha del usuario' => ['admin'],
        'editar usuario' => ['admin'],
        'nuevo servicio' => ['admin'],
        'editar servicio' => ['admin'],
        'detalle de mi cita' => ['cliente'],
        'reprogramar mi cita' => ['cliente'],
    ];

    /** Pantallas a las que se llega desde el menú: sin migas (CA1). */
    private const DEL_MENU = [
        'admin' => ['/admin', '/admin/citas', '/admin/pacientes', '/admin/personal', '/admin/servicios', '/admin/galeria',
            '/admin/reportes', '/admin/usuarios', '/admin/auditoria'],
        'dentista' => ['/dentista', '/dentista/citas', '/dentista/pacientes', '/dentista/expedientes'],
        'asistente' => ['/asistente', '/asistente/doctores', '/asistente/citas', '/asistente/pacientes'],
        'cliente' => ['/cliente', '/cliente/citas', '/cliente/agendar', '/cliente/perfil'],
    ];

    /** Los diez formularios de CA6. */
    private const FORMULARIOS = ['nueva cita', 'nuevo paciente', 'editar paciente', 'nuevo expediente', 'nuevo miembro',
        'editar personal', 'nuevo usuario', 'editar usuario', 'nuevo servicio', 'editar servicio'];

    private const CODIGO = '<script>alert(1)</script>';

    private string $cita;

    private string $citaEmpezada;

    private string $expediente;

    private string $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        // Reloj fijo: la cita futura se puede reprogramar y la de la madrugada ya empezó.
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));
        $this->crearEscenario();

        $this->cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-20 09:00');
        $this->citaEmpezada = $this->cita($this->d1, $this->p1, 'agendada', '2026-10-14 01:00');
        $this->expediente = (string) Str::uuid();
        DB::table('expedientes')->insert([
            'id' => $this->expediente, 'paciente_id' => $this->p1, 'dentista_id' => $this->d1,
            'diagnostico' => 'Diagnóstico de prueba', 'tratamiento' => 'Tratamiento de prueba', 'creado_en' => now(),
        ]);
        $this->servicio = (string) Str::uuid();
        DB::table('servicios')->insert([
            'id' => $this->servicio, 'nombre' => 'Servicio 046 '.Str::random(6), 'descripcion' => 'Fila de NavegacionInternaTest.',
            'categoria' => 'Preventiva', 'duracion_min' => 30, 'precio' => 500.00, 'color_agenda' => '#ED93B1', 'activo' => 1,
        ]);
    }

    // ── Proveedores: solo claves ────────────────────────────────────────────────────────────────

    public static function pantallas(): array
    {
        $casos = [];
        foreach (self::PANTALLAS as $pantalla => $roles) {
            foreach ($roles as $rol) {
                $casos["{$rol} · {$pantalla}"] = [$rol, $pantalla];
            }
        }

        return $casos;
    }

    public static function pantallasDelMenu(): array
    {
        $casos = [];
        foreach (self::DEL_MENU as $rol => $direcciones) {
            foreach ($direcciones as $direccion) {
                $casos["{$rol} · {$direccion}"] = [$rol, $direccion];
            }
        }

        return $casos;
    }

    public static function reprogramaciones(): array
    {
        $casos = [];
        foreach (['admin' => 'reprogramar cita', 'asistente' => 'reprogramar cita', 'cliente' => 'reprogramar mi cita'] as $rol => $pantalla) {
            $casos["{$rol} · se puede"] = [$rol, $pantalla, true];
            $casos["{$rol} · no se puede"] = [$rol, $pantalla, false];
        }

        return $casos;
    }

    public static function formularios(): array
    {
        return array_combine(self::FORMULARIOS, array_map(fn ($f) => [$f], self::FORMULARIOS));
    }

    public static function expedientes(): array
    {
        return ['admin' => ['admin'], 'dentista' => ['dentista'], 'asistente' => ['asistente']];
    }

    public static function pantallasConNombre(): array
    {
        return [
            'admin · detalle de cita' => ['admin', 'detalle de cita'],
            'admin · reprogramar cita' => ['admin', 'reprogramar cita'],
            'admin · ficha del paciente' => ['admin', 'ficha del paciente'],
            'admin · editar paciente' => ['admin', 'editar paciente'],
            'dentista · nuevo expediente' => ['dentista', 'nuevo expediente'],
            'asistente · expediente' => ['asistente', 'expediente'],
            'admin · ficha del personal' => ['admin', 'ficha del personal'],
            'admin · editar personal' => ['admin', 'editar personal'],
            'admin · ficha del usuario' => ['admin', 'ficha del usuario'],
            'admin · editar usuario' => ['admin', 'editar usuario'],
        ];
    }

    public static function pantallasDeOtroRol(): array
    {
        return [
            'paciente · ficha del paciente del personal' => ['paciente', 'ficha del paciente', 403],
            'paciente · detalle de cita del personal' => ['paciente', 'detalle de cita', 403],
            'paciente · expediente' => ['paciente', 'expediente', 403],
            'asistente · ficha del usuario' => ['asistente', 'ficha del usuario', 403],
            'dentista · ficha del personal' => ['dentista', 'ficha del personal', 403],
            'dentista · detalle de cita del paciente' => ['dentista', 'detalle de mi cita', 404],
            'asistente · detalle de cita del paciente' => ['asistente', 'detalle de mi cita', 404],
            'otro paciente · detalle de cita del paciente' => ['otro paciente', 'detalle de mi cita', 404],
        ];
    }

    // ── CA1, CA2 (T010) ─────────────────────────────────────────────────────────────────────────

    #[DataProvider('pantallas')]
    public function test_cada_pantalla_de_segundo_nivel_lleva_sus_migas(string $rol, string $pantalla): void
    {
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla);
        $xpath = $this->xpath($this->actingAs($usuario)->get($direccion)->assertOk()->getContent());

        $bloques = $xpath->query("//*[@id='migas'] | //*[contains(concat(' ', normalize-space(@class), ' '), ' migas ')]");
        $this->assertSame(1, $bloques->length, 'Un solo bloque de migas.');
        $migas = $bloques->item(0);
        $this->assertSame('nav', $migas->nodeName, 'Las migas son una navegación.');
        $this->assertSame('migas', $migas->getAttribute('id'));
        $this->assertContains('migas', $this->clases($migas), 'Usa el componente .migas.');
        $this->assertSame(0, $xpath->query('descendant-or-self::*[@style]', $migas)->length, 'Migas sin style="".');
        $this->assertSame(1, $xpath->query(".//main//*[@id='migas'] | //*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]//*[@id='migas']")->length,
            'Las migas van en el contenido de la pantalla.');

        $this->assertSame(array_column($camino, 0), $this->textos($xpath, $migas), 'Camino de la tabla de la spec.');
    }

    #[DataProvider('pantallasDelMenu')]
    public function test_las_pantallas_del_menu_no_llevan_migas(string $rol, string $direccion): void
    {
        $xpath = $this->xpath($this->actingAs($this->usuario($rol))->get($direccion)->assertOk()->getContent());

        $this->assertSame(0, $xpath->query("//*[@id='migas'] | //*[contains(concat(' ', normalize-space(@class), ' '), ' migas ')]")->length,
            'Una pantalla del menú no lleva migas.');
    }

    #[DataProvider('pantallas')]
    public function test_los_enlaces_de_las_migas_llevan_a_destinos_fijos(string $rol, string $pantalla): void
    {
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla);
        $xpath = $this->xpath($this->actingAs($usuario)->get($direccion)->assertOk()->getContent());

        $this->assertSame(array_column($camino, 1), $this->destinos($xpath), 'Cada elemento lleva a su pantalla fija; el último no es un enlace.');
    }

    // ── CA3, CA4, CA10 (T011) ───────────────────────────────────────────────────────────────────

    #[DataProvider('pantallas')]
    public function test_las_migas_son_una_navegacion_accesible(string $rol, string $pantalla): void
    {
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla);
        $xpath = $this->xpath($this->actingAs($usuario)->get($direccion)->assertOk()->getContent());

        $nav = $xpath->query("//nav[@id='migas']");
        $this->assertSame(1, $nav->length, 'Una navegación de migas.');
        $nav = $nav->item(0);
        $this->assertSame('Migas de pan', $nav->getAttribute('aria-label'));

        $listas = $xpath->query('./ol', $nav);
        $this->assertSame(1, $listas->length, 'Una lista ordenada.');
        $this->assertSame(0, $xpath->query('./*[not(self::ol)]', $nav)->length, 'La navegación solo contiene la lista.');
        $elementos = $xpath->query('./li', $listas->item(0));
        $this->assertSame(count($camino), $elementos->length, 'Un elemento de lista por elemento del camino.');
        $this->assertSame(0, $xpath->query('./*[not(self::li)]', $listas->item(0))->length, 'Todos los hijos de la lista son <li>.');

        $actuales = $xpath->query(".//*[@aria-current='page']", $nav);
        $this->assertSame(1, $actuales->length, 'Una sola página actual.');
        $ultimo = $elementos->item($elementos->length - 1);
        $this->assertTrue($actuales->item(0)->isSameNode($ultimo), 'La página actual es el último elemento.');
        $this->assertContains('migas-actual', $this->clases($ultimo));

        $separadores = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' migas-sep ')]", $nav);
        $this->assertSame(count($camino) - 1, $separadores->length, 'n−1 separadores.');
        foreach ($separadores as $separador) {
            $this->assertSame('true', $separador->getAttribute('aria-hidden'), 'El separador no se lee.');
            $this->assertSame('li', $separador->parentNode->nodeName, 'El separador va dentro de su elemento.');
        }
        $this->assertSame(0, $xpath->query("./span[contains(@class, 'migas-sep')]", $elementos->item(0))->length, 'El primer elemento no lleva separador.');
        foreach (range(1, $elementos->length - 1) as $i) {
            $primero = $xpath->query('./*[1]', $elementos->item($i))->item(0);
            $this->assertContains('migas-sep', $this->clases($primero), 'El separador va delante del texto.');
        }
    }

    public function test_las_reglas_de_las_migas_distinguen_el_enlace(): void
    {
        $this->assertSame(['color: var(--p600)', 'text-decoration: underline'], $this->declaradas('.migas a'), 'Enlace en --p600 y subrayado.');
        $this->assertSame(['color: var(--p800)'], $this->declaradas('.migas a:hover'), 'Al pasar el puntero, --p800.');
        $this->assertSame(['color: var(--text)'], $this->declaradas('.migas-actual'), 'Elemento actual en --text, sin subrayar.');
        $this->assertSame(['color: var(--text)', 'font-size: var(--text-meta)', 'margin-bottom: var(--space-7)'], $this->declaradas('.migas'));
        $this->assertStringContainsString('outline: var(--focus-ring-width) solid var(--focus-ring)', $this->declaraciones('.migas a:focus-visible'),
            'El enlace conserva el foco del sistema.');
    }

    public function test_el_contraste_de_las_migas_es_aa(): void
    {
        $tokens = $this->tokensDeColor();
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--p600'], $tokens['--bg']), 'Enlace sobre el fondo.');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--p800'], $tokens['--bg']), 'Enlace con el puntero encima.');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens['--text'], $tokens['--bg']), 'Elemento actual y separador.');
    }

    public function test_las_migas_pueden_pasar_de_linea(): void
    {
        $this->assertSame(['align-items: center', 'display: flex', 'flex-wrap: wrap', 'gap: var(--space-3)', 'list-style: none', 'margin: 0', 'padding: 0'],
            $this->declaradas('.migas ol'), 'La lista es una fila que pasa de línea a todos los anchos.');
        $this->assertSame(['align-items: center', 'display: flex', 'gap: var(--space-3)', 'min-width: 0', 'overflow-wrap: anywhere'],
            $this->declaradas('.migas li'), 'Cada elemento puede partir un nombre largo.');

        $movil = $this->declaraciones('.migas a', 'css/movil.css');
        $this->assertStringContainsString('min-height: var(--touch-min)', $movil, 'En el móvil, el enlace mide al menos 44 px de alto.');
        $this->assertStringContainsString('min-width: var(--touch-min)', $movil);
    }

    // ── CA5 a CA9 (T012) ────────────────────────────────────────────────────────────────────────

    #[DataProvider('reprogramaciones')]
    public function test_reprogramar_lleva_migas_se_pueda_o_no(string $rol, string $pantalla, bool $sePuede): void
    {
        $cita = $sePuede ? $this->cita : $this->citaEmpezada;
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla, $cita);
        $html = $this->actingAs($usuario)->get($direccion)->assertOk()->getContent();
        $xpath = $this->xpath($html);

        // El caso es el que dice ser: con la cita ya empezada no hay formulario de fecha.
        $this->assertSame($sePuede ? 1 : 0, $xpath->query("//input[@name='fecha']")->length, 'Formulario de reprogramar solo si se puede.');

        $migas = $xpath->query("//nav[@id='migas']");
        $this->assertSame(1, $migas->length, 'Reprogramar lleva migas.');
        $this->assertSame(array_column($camino, 0), $this->textos($xpath, $migas->item(0)));
        $this->assertSame(array_column($camino, 1), $this->destinos($xpath), 'Migas hasta el detalle de esa cita.');
    }

    #[DataProvider('formularios')]
    public function test_cancelar_lleva_al_mismo_sitio_que_las_migas(string $pantalla): void
    {
        // Contra el destino fijo del penúltimo elemento según la tabla de la spec, no contra las migas
        // pintadas (/analyze A20): que las migas pintan ese destino lo prueba el test de CA2.
        [$usuario, $direccion, $camino] = $this->caso('admin', $pantalla);
        $xpath = $this->xpath($this->actingAs($usuario)->get($direccion)->assertOk()->getContent());

        $cancelar = $xpath->query("//a[normalize-space()='Cancelar']");
        $this->assertSame(1, $cancelar->length, 'Un «Cancelar».');
        $this->assertSame($camino[count($camino) - 2][1], $this->ruta($cancelar->item(0)->getAttribute('href')),
            '«Cancelar» lleva al penúltimo elemento del camino.');
    }

    #[DataProvider('expedientes')]
    public function test_el_expediente_ya_no_tiene_volver_al_paciente(string $rol): void
    {
        [$usuario, $direccion] = $this->caso($rol, 'expediente');
        $html = $this->actingAs($usuario)->get($direccion)->assertOk()->getContent();

        $this->assertStringNotContainsString('Volver al paciente', $html);
    }

    #[DataProvider('pantallas')]
    public function test_ninguna_pantalla_usa_el_titulo_por_defecto(string $rol, string $pantalla): void
    {
        [$usuario, $direccion] = $this->caso($rol, $pantalla);
        $xpath = $this->xpath($this->actingAs($usuario)->get($direccion)->assertOk()->getContent());

        $barra = trim($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' topbar-title ')]")->item(0)?->textContent ?? '');
        $pestana = trim($xpath->query('//title')->item(0)?->textContent ?? '');
        $this->assertNotSame('', $barra, 'La barra superior tiene título.');
        $this->assertNotSame('Dashboard', $barra, 'La barra superior no usa el título por defecto.');
        $this->assertSame("Dentissa · {$barra}", $pestana, 'La pestaña lleva el mismo título.');

        $fijos = ['nuevo servicio' => 'Nuevo servicio', 'editar servicio' => 'Editar servicio'];
        if (isset($fijos[$pantalla])) {
            $this->assertSame($fijos[$pantalla], $barra);
        }
    }

    #[DataProvider('pantallas')]
    public function test_el_menu_marca_la_seccion_de_la_pantalla(string $rol, string $pantalla): void
    {
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla);
        $xpath = $this->xpath($this->actingAs($usuario)->get($direccion)->assertOk()->getContent());

        $marcadas = $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ')][@aria-current='page']");
        $this->assertSame(1, $marcadas->length, 'Una sola entrada del menú marcada.');
        $marcada = $marcadas->item(0);
        $this->assertContains('active', $this->clases($marcada));
        // El menú usa direcciones relativas y las migas, absolutas: se compara por la ruta (/analyze A24).
        $this->assertSame($camino[0][0], $this->texto($marcada), 'La entrada marcada tiene el texto del primer elemento de las migas.');
        $this->assertSame($camino[0][1], $this->ruta($marcada->getAttribute('href')), 'Y lleva al mismo sitio.');
        $this->assertSame(1, $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ')][contains(concat(' ', normalize-space(@class), ' '), ' active ')]")->length,
            'Una sola entrada con .active.');
    }

    // ── CA11: abuso (T013) ──────────────────────────────────────────────────────────────────────

    /** TM1: nada lee la dirección ni el Referer. */
    #[DataProvider('pantallas')]
    public function test_las_migas_ignoran_un_destino_de_vuelta_en_la_direccion(string $rol, string $pantalla): void
    {
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla);
        $extra = http_build_query([
            'volver' => 'https://malo.example/', 'return' => '/admin/usuarios', 'redirect' => '//malo.example',
            'next' => '/admin', 'url' => 'https://malo.example/', 'back' => '/dentista/pacientes',
        ]);
        $direccion .= (str_contains($direccion, '?') ? '&' : '?').$extra;
        $html = $this->actingAs($usuario)->withHeader('Referer', 'https://malo.example/origen')->get($direccion)->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame(array_column($camino, 1), $this->destinos($xpath), 'Los destinos son los fijos de la tabla.');
        foreach ($xpath->query("//nav[@id='migas']//a") as $enlace) {
            $href = $enlace->getAttribute('href');
            $this->assertStringNotContainsString('malo.example', $href);
            $this->assertStringNotContainsString('?', $href, 'El enlace no arrastra parámetros de la dirección.');
            $this->assertStringStartsWith("/{$rol}", $this->ruta($href), 'El enlace queda en el área del propio rol.');
        }
    }

    /** TM2: el nombre se pinta como texto. */
    #[DataProvider('pantallasConNombre')]
    public function test_el_nombre_con_codigo_se_pinta_como_texto(string $rol, string $pantalla): void
    {
        foreach ([$this->paciente1, $this->dentista1, $this->paciente2] as $persona) {
            DB::table('usuarios')->where('id', $persona->id)->update(['nombre' => self::CODIGO]);
        }
        [$usuario, $direccion, $camino] = $this->caso($rol, $pantalla);
        $html = $this->actingAs($usuario)->get($direccion)->assertOk()->getContent();

        $migas = $this->seccion($html, 'migas');
        $this->assertNotNull($migas, 'La pantalla lleva migas.');
        $this->assertStringContainsString(e(self::CODIGO), $migas, 'El nombre va escapado dentro de las migas.');
        $this->assertStringNotContainsString('<script', $migas, 'Las migas no contienen un script.');
        $xpath = $this->xpath($html);
        $this->assertSame(1, $xpath->query("//nav[@id='migas']")->length, 'Las migas son la navegación del parcial.');
        $this->assertSame(0, $xpath->query("//nav[@id='migas']//script")->length);
        $this->assertContains(true, array_map(fn ($t) => str_contains($t, self::CODIGO), $this->textos($xpath, $xpath->query("//nav[@id='migas']")->item(0))),
            'El código se lee como texto.');
    }

    /** TM3 (caracterización): la respuesta de acceso denegado o no encontrada sale antes de pintar la vista. */
    #[DataProvider('pantallasDeOtroRol')]
    public function test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas(string $quien, string $pantalla, int $estado): void
    {
        $rolDeLaPantalla = self::PANTALLAS[$pantalla][0];
        [, $direccion] = $this->caso($rolDeLaPantalla, $pantalla);
        $usuario = match ($quien) {
            'paciente' => $this->paciente1,
            'otro paciente' => $this->paciente2,
            default => $this->usuario($quien),
        };

        $respuesta = $this->actingAs($usuario)->get($direccion);

        $respuesta->assertStatus($estado);
        $html = $respuesta->getContent();
        $this->assertSame(0, $this->xpath($html)->query("//*[@id='migas']")->length, 'Sin migas.');
        foreach ([$this->paciente1, $this->dentista1] as $persona) {
            $this->assertStringNotContainsString("{$persona->nombre} {$persona->apellido}", $html, 'Sin nombres.');
        }
    }

    // ── Escenario ───────────────────────────────────────────────────────────────────────────────

    private function usuario(string $rol): Usuario
    {
        return match ($rol) {
            'admin' => $this->admin,
            'dentista' => $this->dentista1,
            'asistente' => $this->asistente,
            'cliente' => $this->paciente1,
        };
    }

    /**
     * Usuario, dirección y camino esperado `[[texto, ruta|null], …]` de una pantalla para un rol
     * (spec, «Pantallas y caminos»). El primer elemento lleva el texto del menú de cada rol.
     *
     * @return array{0: Usuario, 1: string, 2: list<array{0: string, 1: ?string}>}
     */
    private function caso(string $rol, string $pantalla, ?string $cita = null): array
    {
        $cita ??= $this->cita;
        $paciente = $this->nombre($this->paciente1);
        $miembro = $this->nombre($this->dentista1);
        $cuenta = $this->nombre($this->paciente2);

        $citas = [in_array($rol, ['dentista', 'cliente'], true) ? 'Mis Citas' : 'Citas', "/{$rol}/citas"];
        $pacientes = ['Pacientes', "/{$rol}/pacientes"];
        $ficha = [$paciente, "/{$rol}/pacientes/{$this->p1}"];
        $personal = ['Personal', '/admin/personal'];
        $usuarios = ['Usuarios', '/admin/usuarios'];
        $servicios = ['Servicios', '/admin/servicios'];

        [$direccion, $camino] = match ($pantalla) {
            'nueva cita' => ["/{$rol}/citas/crear", [$citas, ['Nueva cita', null]]],
            'detalle de cita' => ["/{$rol}/citas/{$cita}", [$citas, [$paciente, null]]],
            'reprogramar cita' => ["/{$rol}/citas/{$cita}/reprogramar", [$citas, [$paciente, "/{$rol}/citas/{$cita}"], ['Reprogramar', null]]],
            'nuevo paciente' => ["/{$rol}/pacientes/crear", [$pacientes, ['Nuevo paciente', null]]],
            'ficha del paciente' => ["/{$rol}/pacientes/{$this->p1}", [$pacientes, [$paciente, null]]],
            'editar paciente' => ["/{$rol}/pacientes/{$this->p1}/edit", [$pacientes, $ficha, ['Editar', null]]],
            'nuevo expediente' => ["/{$rol}/expedientes/crear?paciente_id={$this->p1}", [$pacientes, $ficha, ['Nuevo expediente', null]]],
            'expediente' => ["/{$rol}/expedientes/{$this->expediente}", [$pacientes, $ficha, ['Expediente', null]]],
            'nuevo miembro' => ['/admin/personal/crear', [$personal, ['Nuevo miembro', null]]],
            'ficha del personal' => ["/admin/personal/{$this->d1}", [$personal, [$miembro, null]]],
            'editar personal' => ["/admin/personal/{$this->d1}/edit", [$personal, [$miembro, "/admin/personal/{$this->d1}"], ['Editar', null]]],
            'nuevo usuario' => ['/admin/usuarios/crear', [$usuarios, ['Nuevo usuario', null]]],
            'ficha del usuario' => ["/admin/usuarios/{$this->paciente2->id}", [$usuarios, [$cuenta, null]]],
            'editar usuario' => ["/admin/usuarios/{$this->paciente2->id}/editar", [$usuarios, [$cuenta, "/admin/usuarios/{$this->paciente2->id}"], ['Editar', null]]],
            'nuevo servicio' => ['/admin/servicios/create', [$servicios, ['Nuevo servicio', null]]],
            'editar servicio' => ["/admin/servicios/{$this->servicio}/edit", [$servicios, ['Editar servicio', null]]],
            'detalle de mi cita' => ["/cliente/citas/{$cita}", [$citas, ['Detalle', null]]],
            'reprogramar mi cita' => ["/cliente/citas/{$cita}/reprogramar", [$citas, ['Detalle', "/cliente/citas/{$cita}"], ['Reprogramar', null]]],
        };

        return [$this->usuario($rol), $direccion, $camino];
    }

    /** Nombre como está ahora en la base (un test puede haberlo cambiado). */
    private function nombre(Usuario $usuario): string
    {
        $fila = DB::table('usuarios')->where('id', $usuario->id)->first(['nombre', 'apellido']);

        return "{$fila->nombre} {$fila->apellido}";
    }

    // ── Lectura del HTML y del CSS ──────────────────────────────────────────────────────────────

    /** Texto de cada elemento del camino, sin su separador. */
    private function textos(DOMXPath $xpath, DOMElement $migas): array
    {
        $textos = [];
        foreach ($xpath->query('./ol/li', $migas) as $elemento) {
            $texto = '';
            foreach ($elemento->childNodes as $hijo) {
                if ($hijo instanceof DOMElement && in_array('migas-sep', $this->clases($hijo), true)) {
                    continue;
                }
                $texto .= $hijo->textContent;
            }
            $textos[] = trim(preg_replace('/\s+/u', ' ', $texto));
        }

        return $textos;
    }

    /** Ruta del enlace de cada elemento del camino (null si no es un enlace). */
    private function destinos(DOMXPath $xpath): array
    {
        $destinos = [];
        foreach ($xpath->query("//nav[@id='migas']/ol/li") as $elemento) {
            $enlaces = $xpath->query('.//a', $elemento);
            $this->assertLessThanOrEqual(1, $enlaces->length, 'Como mucho un enlace por elemento.');
            $destinos[] = $enlaces->length ? $this->ruta($enlaces->item(0)->getAttribute('href')) : null;
        }

        return $destinos;
    }

    /** Ruta de una dirección, sin esquema, dominio ni parámetros. */
    private function ruta(string $href): string
    {
        return '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');
    }

    private function texto(DOMElement $elemento): string
    {
        return trim(preg_replace('/\s+/u', ' ', $elemento->textContent));
    }

    /** Declaraciones de un selector de `app.css`, ordenadas. */
    private function declaradas(string $selector): array
    {
        $declaraciones = array_values(array_filter(array_map(
            fn ($d) => preg_replace('/\s+/', ' ', trim($d)),
            explode(';', $this->declaraciones($selector)),
        )));
        sort($declaraciones);

        return $declaraciones;
    }

    private function clases(DOMElement $elemento): array
    {
        return preg_split('/\s+/', trim($elemento->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
