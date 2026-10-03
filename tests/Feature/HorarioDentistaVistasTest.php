<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Qué ve cada rol en la ficha del personal, accesibilidad de los errores y sistema de
 * diseño de las piezas nuevas (spec 027, CA2, CA4, CA6, TM8 y P9).
 */
class HorarioDentistaVistasTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    /** Reglas nuevas o movidas de `app.css` que solo pueden usar tokens. */
    private const REGLAS_NUEVAS = ['.lista-citas', '.horario-dia', '.horario-dia--activo', '.horario-dia .field', '.field-hint', '.field-hint-error', ':focus-visible'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Carbon::setTestNow(Carbon::parse('2026-10-05 17:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── CA6 ────────────────────────────────────────────────────────────

    public function test_la_ficha_solo_ofrece_horario_a_los_dentistas(): void
    {
        [, $asistente] = $this->crearPersonal('asistente', 'Aurora', 'Ayuda');
        $administrador = $this->crearAdministrador();

        foreach (['asistente' => $asistente, 'administrador' => $administrador] as $caso => $id) {
            $html = $this->ficha($id)->assertOk()->getContent();
            $this->assertStringNotContainsString('name="dias[', $html, "{$caso}: la ficha no ofrece el formulario de horario.");
            $this->assertNull($this->seccion($html, 'horario'), $caso);
        }

        $horario = $this->seccion($this->ficha($this->d1)->assertOk()->getContent(), 'horario');
        $this->assertNotNull($horario, 'La ficha de un dentista tiene la sección horario.');
        $this->assertStringContainsString('name="dias[1][hora_inicio]"', $horario);
    }

    // ── TM8 (P3) ───────────────────────────────────────────────────────

    public function test_la_ficha_de_un_dentista_solo_la_ve_la_administracion(): void
    {
        // D1 no tiene horario: la cita futura de Pablo queda fuera y saldría en la lista.
        $this->cita($this->d1, $this->p1, 'agendada', '2026-10-12 10:00');

        foreach (['dentista' => $this->dentista1, 'asistente' => $this->asistente, 'paciente' => $this->paciente1] as $rol => $usuario) {
            $respuesta = $this->actingAs($usuario)->get("/admin/personal/{$this->d1}");

            $respuesta->assertForbidden();
            $this->assertStringNotContainsString('Pablo', $respuesta->getContent(), "{$rol}: no ve pacientes de la ficha.");
        }
    }

    // ── CA2 y CA4: errores junto al día (A6) ───────────────────────────

    public function test_los_errores_de_un_dia_se_asocian_a_sus_campos(): void
    {
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $this->crearHorario($this->d1, 3, '09:00', '13:00');

        // Lunes invertido, martes (sin horario) marcado sin horas y miércoles desmarcado.
        $this->actingAs($this->admin)->post("/admin/personal/{$this->d1}/horarios", [
            'formulario' => 'horario',
            'dias' => [
                1 => ['activo' => '1', 'hora_inicio' => '18:00', 'hora_fin' => '09:00'],
                2 => ['activo' => '1'],
            ],
        ])->assertRedirect();

        $dom = $this->dom($this->ficha($this->d1)->assertOk()->getContent());
        $xpath = new DOMXPath($dom);

        // El campo con error apunta a su mensaje, que está en la fila del día.
        foreach (['dias[1][hora_fin]' => ['fila-1', 'La hora de fin debe ser posterior a la de inicio, el mismo día.'], 'dias[2][hora_inicio]' => ['fila-2', 'Indica la hora de inicio de este día.']] as $nombre => [$fila, $mensaje]) {
            $campo = $xpath->query("//*[@id='horario']//input[@name='{$nombre}']")->item(0);
            $this->assertInstanceOf(DOMElement::class, $campo, $nombre);
            $this->assertSame('true', $campo->getAttribute('aria-invalid'), $nombre);
            $error = $dom->getElementById($campo->getAttribute('aria-describedby'));
            $this->assertNotNull($error, "{$nombre}: aria-describedby apunta a un elemento que existe.");
            $this->assertSame($mensaje, trim($error->textContent), $nombre);
            $this->assertSame(1, $xpath->query("//*[@id='{$fila}']//*[@id='{$error->getAttribute('id')}']")->length, "{$nombre}: el mensaje está en la fila del día.");
        }

        $resumen = $xpath->query("//*[@id='horario']//*[@role='alert']")->item(0);
        $this->assertNotNull($resumen, 'Hay un resumen role="alert".');
        $this->assertStringContainsString('Revisa el horario de: Lunes, Martes.', preg_replace('/\s+/', ' ', $resumen->textContent));

        // Lo enviado se conserva: el lunes con sus horas y el martes marcado, visible y habilitado.
        $this->assertSame('18:00', $this->campo($xpath, 'dias[1][hora_inicio]')->getAttribute('value'));
        $this->assertTrue($this->campo($xpath, 'dias[2][activo]')->hasAttribute('checked'), 'El martes sigue marcado.');
        $this->assertStringNotContainsString('hidden-row', $xpath->query("//*[@id='horas-2']")->item(0)?->getAttribute('class') ?? 'hidden-row');
        $this->assertFalse($this->campo($xpath, 'dias[2][hora_inicio]')->hasAttribute('disabled'), 'Los campos del martes están habilitados.');
        // CA4: nunca se rellena una hora por defecto tras el rechazo (R6).
        $this->assertSame('', $this->campo($xpath, 'dias[2][hora_inicio]')->getAttribute('value'), 'El inicio del martes sale vacío.');
        $this->assertSame('', $this->campo($xpath, 'dias[2][hora_fin]')->getAttribute('value'), 'El fin del martes sale vacío.');
        // El miércoles, desmarcado en el envío, sigue desmarcado aunque tenga horario guardado.
        $this->assertFalse($this->campo($xpath, 'dias[3][activo]')->hasAttribute('checked'), 'El miércoles sigue desmarcado.');
    }

    // ── P9: solo tokens (A1, A2, A8, A24, A26) ─────────────────────────

    public function test_las_piezas_nuevas_solo_usan_tokens(): void
    {
        $parcial = resource_path('views/personal/partials/citas-fuera.blade.php');
        $this->assertFileExists($parcial);
        $this->assertStringNotContainsString('style=', file_get_contents($parcial));

        // Ficha con un error y con citas fuera: se pintan el resumen, los mensajes y la lista.
        $this->crearHorario($this->d1, 1, '09:00', '13:00');
        $this->cita($this->d1, $this->p1, 'agendada', '2026-10-13 10:00');
        $this->actingAs($this->admin)->post("/admin/personal/{$this->d1}/horarios", [
            'formulario' => 'horario',
            'dias' => [1 => ['activo' => '1', 'hora_inicio' => '18:00', 'hora_fin' => '09:00']],
        ]);
        $html = $this->ficha($this->d1)->assertOk()->getContent();
        $xpath = new DOMXPath($this->dom($html));

        $this->assertGreaterThan(0, $xpath->query("//*[@id='horario']//*[contains(@class,'field-hint')]")->length, 'Hay mensajes de error.');
        $this->assertGreaterThan(0, $xpath->query("//*[@id='citas-fuera']")->length, 'Hay lista de citas fuera.');
        $conEstilo = $xpath->query(
            "//*[@id='horario']//*[contains(@class,'field-hint') or @role='alert' or contains(@class,'horario-dia')][@style]"
            ." | //*[@id='horario']//*[contains(@class,'horario-dia')]//*[@style]"
            ." | //*[@id='citas-fuera']//*[@style] | //*[@id='citas-fuera'][@style]"
        );
        $this->assertSame(0, $conEstilo->length, 'Las piezas nuevas o modificadas no llevan style=.');

        $camposDeHora = $xpath->query("//*[@id='horario']//input[@type='time']");
        $this->assertSame(14, $camposDeHora->length, 'Dos campos de hora por día.');
        foreach ($camposDeHora as $campo) {
            $nombre = $campo->getAttribute('name');
            $this->assertContains('field', explode(' ', $campo->getAttribute('class')), "{$nombre} lleva la clase field.");
            $this->assertSame(1, $xpath->query("ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' horario-dia ')]", $campo)->length, "{$nombre} está en una fila horario-dia.");
        }

        $this->assertMatchesRegularExpression('/function toggleDia\b/', $html);
        preg_match('/function toggleDia\b.*?\n}/s', $html, $script);
        $this->assertStringNotContainsString('.style', $script[0] ?? '', 'toggleDia solo cambia clases, hidden y disabled.');
        $this->assertStringContainsString('.field-hint-error', $script[0] ?? '', 'toggleDia alterna también los mensajes de error del día (R10).');
        $this->assertMatchesRegularExpression('/\.hidden\s*=\s*!\s*checked/', $script[0] ?? '', 'Los mensajes se ocultan al desmarcar el día y vuelven al marcarlo.');
    }

    public function test_las_reglas_css_nuevas_solo_usan_tokens(): void
    {
        $reglas = $this->reglasCss(public_path('css/app.css'));

        foreach (self::REGLAS_NUEVAS as $selector) {
            $this->assertNotEmpty($this->declaraciones($reglas, $selector), "app.css debe tener la regla {$selector}.");
        }

        foreach ($reglas as [$selector, $cuerpo]) {
            if (! preg_match('/\.lista-citas|\.horario-dia|:focus-visible|\.field-hint/', $selector)) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('/\d(\.\d+)?px/', $cuerpo, "{$selector}: sin px.");
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b|rgba?\(/i', $cuerpo, "{$selector}: sin colores literales.");
            if (preg_match_all('/font-weight\s*:\s*([^;]+)/', $cuerpo, $pesos)) {
                foreach ($pesos[1] as $peso) {
                    $this->assertStringStartsWith('var(--weight-', trim($peso), "{$selector}: el peso es un token (A34).");
                }
            }
            if (preg_match_all('/(?<![-\w])color\s*:\s*([^;]+)/', $cuerpo, $colores)) {
                foreach ($colores[1] as $color) {
                    $this->assertDoesNotMatchRegularExpression('/--text-(body|small)\b/', $color, "{$selector}: un tamaño no es un color (A35).");
                }
            }
        }

        $this->assertStringContainsString('var(--text-small)', $this->declaraciones($reglas, '.field-hint'));
        $this->assertMatchesRegularExpression('/border-color\s*:\s*var\(--border-control\)/', $this->declaraciones($reglas, '.horario-dia .field'));
        $this->assertStringContainsString('var(--focus-ring)', $this->declaraciones($reglas, ':focus-visible'));
        $this->assertStringNotContainsString('.field-hint', file_get_contents(public_path('css/citas.css')), 'La ayuda de campo vive solo en app.css.');

        // R2: el borde del campo de hora es del sistema también con el foco, frente al `.field:focus`
        // previo de la vista, que tiene la misma especificidad que `.horario-dia .field`.
        $this->assertMatchesRegularExpression('/border-width\s*:\s*var\(--border-width\)/', $this->declaraciones($reglas, '.horario-dia .field'));
        $this->assertMatchesRegularExpression('/border-color\s*:\s*var\(--border-control\)/', $this->declaraciones($reglas, '.horario-dia .field:focus'), 'Falta la regla .horario-dia .field:focus con --border-control.');
    }

    // ── P3: el Gate por rol y tipo (R7) ────────────────────────────────

    public function test_el_gate_definir_horario_distingue_rol_y_tipo(): void
    {
        [, $asistenteId] = $this->crearPersonal('asistente', 'Aurora', 'Ayuda');
        $dentista = DB::table('personal')->where('id', $this->d1)->first();
        $asistente = DB::table('personal')->where('id', $asistenteId)->first();

        $this->assertTrue(Gate::forUser($this->admin)->inspect('definir-horario', $dentista)->allowed(), 'Admin sobre un dentista: permitido.');

        $sobreAsistente = Gate::forUser($this->admin)->inspect('definir-horario', $asistente);
        $this->assertTrue($sobreAsistente->denied(), 'Admin sobre un asistente: denegado.');
        $this->assertSame(404, $sobreAsistente->status(), 'Para otro tipo de personal el horario no existe.');

        foreach (['dentista' => $this->dentista1, 'asistente' => $this->asistente, 'paciente' => $this->paciente1] as $rol => $usuario) {
            $respuesta = Gate::forUser($usuario)->inspect('definir-horario', $dentista);
            $this->assertTrue($respuesta->denied(), "{$rol}: denegado.");
            $this->assertSame(403, $respuesta->status() ?? 403, "{$rol}: 403, no 404.");
        }
    }

    public function test_los_pares_de_contraste_cumplen_aa(): void
    {
        $tokens = $this->tokensDeColor(public_path('css/app.css'));

        $texto = [
            ['--text', '--white'], ['--text', '--p50'],
            ['--text-muted', '--white'], ['--text-muted', '--p50'],
            ['--red', '--white'], ['--red', '--p50'],
            ['--p600', '--white'], ['--p600', '--p50'],
            ['--p600', '--p100'], ['--blue', '--blue-bg'],
        ];
        $bordesYFoco = [
            ['--border-control', '--white'], ['--border-control', '--p50'],
            ['--focus-ring', '--white'], ['--focus-ring', '--p50'],
        ];

        foreach ([[$texto, 4.5], [$bordesYFoco, 3.0]] as [$pares, $minimo]) {
            foreach ($pares as [$frente, $fondo]) {
                $this->assertArrayHasKey($frente, $tokens, "Falta el token de color {$frente}.");
                $this->assertArrayHasKey($fondo, $tokens, "Falta el token de color {$fondo}.");
                $contraste = $this->contraste($tokens[$frente], $tokens[$fondo]);
                $this->assertGreaterThanOrEqual($minimo, $contraste, sprintf('%s sobre %s: %.2f:1 (mínimo %.1f:1)', $frente, $fondo, $contraste, $minimo));
            }
        }

        // R3: los colores que usan de verdad las reglas nuevas, sobre los fondos de la sección.
        // `.field-hint` (color previo, --text-light) se excluye: en las piezas nuevas siempre va con
        // `.field-hint-error`, que fija el rojo.
        $comprobados = 0;
        foreach ($this->reglasCss(public_path('css/app.css')) as [$selector, $cuerpo]) {
            if (! preg_match('/\.lista-citas|\.horario-dia|:focus-visible|\.field-hint/', $selector)) {
                continue;
            }
            $usos = [];
            if (trim($selector) !== '.field-hint' && preg_match_all('/(?<![-\w])color\s*:\s*([^;]+)/', $cuerpo, $m)) {
                $usos = array_merge($usos, array_map(fn ($v) => [$v, 4.5], $m[1]));
            }
            if (str_contains($selector, '.field') && preg_match_all('/border(?:-color)?\s*:\s*([^;]+)/', $cuerpo, $m)) {
                $usos = array_merge($usos, array_map(fn ($v) => [$v, 3.0], $m[1]));
            }
            if (preg_match_all('/outline(?:-color)?\s*:\s*([^;]+)/', $cuerpo, $m)) {
                $usos = array_merge($usos, array_map(fn ($v) => [$v, 3.0], $m[1]));
            }
            foreach ($usos as [$valor, $minimo]) {
                preg_match_all('/var\((--[\w-]+)\)/', $valor, $vars);
                foreach (array_intersect($vars[1], array_keys($tokens)) as $color) {
                    foreach (['--white', '--p50'] as $fondo) {
                        $contraste = $this->contraste($tokens[$color], $tokens[$fondo]);
                        $this->assertGreaterThanOrEqual($minimo, $contraste, sprintf('%s usa %s sobre %s: %.2f:1 (mínimo %.1f:1)', $selector, $color, $fondo, $contraste, $minimo));
                        $comprobados++;
                    }
                }
            }
        }
        $this->assertGreaterThanOrEqual(10, $comprobados, 'Deben comprobarse los colores de las reglas nuevas.');
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function ficha(string $personalId): TestResponse
    {
        return $this->actingAs($this->admin)->get("/admin/personal/{$personalId}");
    }

    private function crearHorario(string $personalId, int $dia, string $inicio, string $fin): void
    {
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(),
            'personal_id' => $personalId,
            'dia_semana' => $dia,
            'hora_inicio' => $inicio.':00',
            'hora_fin' => $fin.':00',
            'disponible' => 1,
        ]);
    }

    private function crearAdministrador(): string
    {
        $usuario = $this->crearUsuarioDePrueba('admin', 'Amparo', 'Gestiona');
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => 'administrador']);

        return $id;
    }

    private function dom(string $html): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return $dom;
    }

    private function campo(DOMXPath $xpath, string $nombre): DOMElement
    {
        $campo = $xpath->query("//*[@id='horario']//input[@name='{$nombre}']")->item(0);
        $this->assertInstanceOf(DOMElement::class, $campo, "Falta el campo {$nombre}.");

        return $campo;
    }

    /** @return list<array{0: string, 1: string}> reglas de primer nivel: [selector, declaraciones] */
    private function reglasCss(string $ruta): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents($ruta));
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $coincidencias, PREG_SET_ORDER);

        return array_map(fn ($m) => [trim($m[1]), $m[2]], $coincidencias);
    }

    /** Declaraciones de las reglas cuyo selector (o uno de su lista) es exactamente `$selector`. */
    private function declaraciones(array $reglas, string $selector): string
    {
        $cuerpos = [];
        foreach ($reglas as [$selectores, $cuerpo]) {
            $lista = array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores));
            if (in_array($selector, $lista, true) || ($selector === ':focus-visible' && str_contains($selectores, ':focus-visible'))) {
                $cuerpos[] = $cuerpo;
            }
        }

        return implode("\n", $cuerpos);
    }

    /** Tokens de color de `:root` (hex), resolviendo `var()`; los de tamaño se ignoran (A35). */
    private function tokensDeColor(string $ruta): array
    {
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents($ruta), $root);
        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $root[1] ?? '', $pares, PREG_SET_ORDER);
        $crudos = [];
        foreach ($pares as [, $nombre, $valor]) {
            $crudos[$nombre] = trim($valor);
        }

        $resolver = function (string $valor, int $profundidad = 0) use (&$resolver, $crudos): ?string {
            if (preg_match('/^var\((--[\w-]+)\)$/', $valor, $m) && $profundidad < 5) {
                return isset($crudos[$m[1]]) ? $resolver($crudos[$m[1]], $profundidad + 1) : null;
            }

            return preg_match('/^#[0-9a-f]{6}$/i', $valor) ? $valor : null;
        };

        return array_filter(array_map($resolver, $crudos));
    }

    /** Contraste WCAG 2.x entre dos colores hex. */
    private function contraste(string $a, string $b): float
    {
        $luminancia = function (string $hex): float {
            $canales = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
            $lineal = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $canales);

            return 0.2126 * $lineal[0] + 0.7152 * $lineal[1] + 0.0722 * $lineal[2];
        };
        [$claro, $oscuro] = [max($luminancia($a), $luminancia($b)), min($luminancia($a), $luminancia($b))];

        return ($claro + 0.05) / ($oscuro + 0.05);
    }
}
