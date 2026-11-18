<?php

namespace Tests\Feature;

use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 049, P9: `calendario.css` solo usa tokens del sistema y todos sus pares nuevos cumplen el
 * contraste AA (texto ≥ 4,5:1; bordes y foco ≥ 3:1). Lo que depende del navegador (toque real,
 * desbordes) lo mide `design/medir.mjs`.
 */
class CalendarioCitasCssTest extends TestCase
{
    use ReglasCss;

    private const HOJA = 'css/calendario.css';

    /**
     * Texto sobre fondo, por selector: [texto, fondo o fondos, declaraciones que lo fijan en la hoja].
     * El texto de la cita se hereda de `.cal-cita`; cada estado cambia solo el fondo. Por debajo de 900 px
     * la tarjeta y la lista son transparentes: lo que va en ellas se pinta también sobre `--bg`. Toda
     * regla de la hoja que declara `color:` tiene aquí su selector (R8).
     */
    private const TEXTO = [
        '.cal-cita' => ['--text', '--p100', ['color: var(--text)', 'background: var(--p100)']],
        '.cal-cita--confirmada' => ['--text', '--blue-bg', ['background: var(--blue-bg)']],
        '.cal-cita--en_curso' => ['--text', '--amber-bg', ['background: var(--amber-bg)']],
        '.cal-cita--completada' => ['--text', '--green-bg', ['background: var(--green-bg)']],
        '.cal-cita--cancelada' => ['--text', '--white', ['background: var(--white)']],
        '.cal-cita--no_asistio' => ['--text', '--white', ['background: var(--white)']],
        '.cal-vista-op' => ['--p600', '--white', ['color: var(--p600)', 'background: var(--white)']],
        '.cal-vista-op[aria-current="page"]' => ['--white', '--p600', ['color: var(--white)', 'background: var(--p600)']],
        '.cal-dia-cab' => ['--text', '--white', ['color: var(--text)', 'background: var(--white)']],
        '.cal-dia-cab--hoy' => ['--text', '--p50', ['background: var(--p50)']],
        '.cal-hoy-marca' => ['--p600', '--p50', ['color: var(--p600)']],
        '.cal-dia-cab--elegido' => ['--white', '--p600', ['color: var(--white)', 'background: var(--p600)']],
        '.tira-dia--elegido' => ['--white', '--p600', ['color: var(--white)', 'background: var(--p600)']],
        '.tira-total' => ['--p600', '--white', ['color: var(--p600)']],
        '.mes-total' => ['--p600', '--white', ['color: var(--p600)']],
        '.mes-dia--fuera' => ['--text', '--white', ['color: var(--text)']],
        '.cal-lista-hora' => ['--p600', '--white', ['color: var(--p600)']],
        '.cal-cerrado span' => ['--text', '--white', ['color: var(--text)', 'background: var(--white)']],
        '.cal-hueco' => ['--p600', '--p100', ['color: var(--p600)']],
        // Cifras del resumen, con su color de estado por clase en lugar de style="" (A7).
        '.cal-cifra--agendada' => ['--p500', '--white', ['color: var(--p500)']],
        '.cal-cifra--completada' => ['--green-texto', '--white', ['color: var(--green-texto)']],
        '.cal-cifra--cancelada' => ['--red', '--white', ['color: var(--red)']],
        // R8: el resto de textos de la hoja.
        '.cal-resumen-titulo' => ['--text', '--bg', ['color: var(--text)']],
        '.cal-titulo' => ['--text', ['--white', '--bg'], ['color: var(--text)']],
        '.cal-saltar' => ['--p600', '--white', ['color: var(--p600)']],
        '.cal-dia-cab--elegido .cal-hoy-marca' => ['--white', '--p600', ['color: var(--white)']],
        '.cal-hora' => ['--text', '--white', ['color: var(--text)']],
        '.tira-dia' => ['--text', '--white', ['color: var(--text)']],
        '.tira-dia--elegido .tira-total' => ['--white', '--p600', ['color: var(--white)']],
        '.mes-nombre' => ['--text', ['--white', '--bg'], ['color: var(--text)']],
        '.mes-dia' => ['--text', '--white', ['color: var(--text)', 'background: var(--white)']],
        '.mes-dia--cero .mes-total' => ['--text', '--white', ['color: var(--text)']],
        '.mes-hoy' => ['--p600', '--white', ['color: var(--p600)']],
        '.mes-dia--hoy .mes-num' => ['--p600', '--white', ['color: var(--p600)']],
        '.cal-lista-titulo' => ['--text', ['--white', '--bg'], ['color: var(--text)']],
        '.cal-lista-titulo-movil' => ['--text', ['--white', '--bg'], ['color: var(--text)']],
        '.cal-lista-dia' => ['--text', ['--white', '--bg'], ['color: var(--text)']],
        '.cal-lista-hoy' => ['--p600', ['--white', '--bg'], ['color: var(--p600)']],
        '.cal-lista-cita' => ['--text', '--white', ['color: var(--text)']],
    ];

    /** Selectores con `color:` que no necesitan par propio, con el motivo (R8). Hoy, ninguno. */
    private const SIN_PAR = [];

    /** Bordes que distinguen algo: [borde, fondo, selector que lo declara]. */
    private const BORDES = [
        ['--text-light', '--white', '.cal-cita--cancelada'],
        ['--border-control', '--white', '.cal-vista'],
        ['--p400', '--white', '.tira-dia--hoy'],
        ['--p400', '--white', '.mes-dia--hoy'],
    ];

    /** Fondos sobre los que puede caer el anillo de foco (fuera o dentro del elemento). */
    private const FONDOS_FOCO = ['--white', '--bg', '--p50', '--p100', '--blue-bg', '--amber-bg', '--green-bg'];

    public function test_el_detector_de_literales_funciona(): void
    {
        // Control positivo: el patrón detecta un literal de cada tipo.
        $this->assertNotSame([], $this->literales('padding: 12px;'));
        $this->assertNotSame([], $this->literales('color: #993556;'));
        $this->assertNotSame([], $this->literales('font-weight: 600;'));
        $this->assertSame([], $this->literales('padding: var(--space-4); color: var(--p600); font-weight: var(--weight-strong);'));
    }

    public function test_calendario_css_solo_usa_tokens(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        $reglas = $this->reglasCss(self::HOJA);
        $this->assertNotSame([], $reglas, 'calendario.css tiene reglas.');

        foreach ($reglas as [$selector, $cuerpo]) {
            $this->assertSame([], $this->literales($cuerpo), "«{$selector}» usa valores literales en lugar de tokens.");
        }
    }

    public function test_cada_texto_nuevo_cumple_el_contraste_sobre_su_fondo(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        $tokens = $this->tokensDeColor();

        foreach (self::TEXTO as $selector => [$texto, $fondos, $declaraciones]) {
            $cuerpo = $this->declaraciones($selector, self::HOJA);
            foreach ($declaraciones as $declaracion) {
                $this->assertMatchesRegularExpression('/(?<![\w-])'.preg_quote($declaracion, '/').'/', $cuerpo, "«{$selector}» declara {$declaracion}.");
            }
            foreach ((array) $fondos as $fondo) {
                $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens[$texto], $tokens[$fondo]), "{$selector}: {$texto} sobre {$fondo} no llega a 4,5:1.");
            }
        }
    }

    /** R8: ninguna regla de la hoja declara un color de texto sin su par en TEXTO (o en SIN_PAR). */
    public function test_todo_color_de_texto_de_la_hoja_tiene_su_par_de_contraste(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        $sinPar = [];
        foreach ($this->reglasCss(self::HOJA) as [$selectores, $cuerpo]) {
            if (! preg_match('/(?<![\w-])color\s*:/', $cuerpo)) {
                continue;
            }
            foreach (array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores)) as $selector) {
                if (! isset(self::TEXTO[$selector]) && ! isset(self::SIN_PAR[$selector])) {
                    $sinPar[] = $selector;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($sinPar)), 'Selectores con color de texto sin par de contraste en la lista.');

        // Control positivo: una regla nueva con color se detecta.
        $control = $this->analizarCss('.cal-nueva { color: var(--p500); }');
        $this->assertArrayNotHasKey($control[0][0], self::TEXTO);
    }

    /** R10, R11: el foco de la vista y de las cabeceras se dibuja por dentro (sus cajas recortan). */
    public function test_el_foco_de_la_vista_y_de_las_cabeceras_va_por_dentro(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        foreach (['.cal-vista-op:focus-visible', '.cal-dia-cab:focus-visible'] as $selector) {
            preg_match_all('/outline-offset\s*:\s*([^;]+);/', $this->declaraciones($selector, self::HOJA), $m);
            $this->assertNotSame([], $m[1], "{$selector} declara outline-offset.");
            $this->assertSame('calc(-1 * var(--focus-ring-width))', trim(end($m[1])), "{$selector}: el último outline-offset va por dentro.");
        }
    }

    /** R12 y R1: «hoy» en la tira con un borde más grueso (no solo color); carriles hasta 8. */
    public function test_hoy_en_la_tira_lleva_borde_grueso_y_hay_carriles_hasta_8(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        $this->assertStringContainsString('var(--border-width-strong)', $this->declaraciones('.tira-dia--hoy', self::HOJA));

        foreach (range(2, 8) as $n) {
            $this->assertStringContainsString("repeat({$n}, minmax(0, 1fr))", $this->declaraciones(".cal-carriles-{$n}", self::HOJA), "Rejilla de {$n} carriles.");
            $this->assertStringContainsString("grid-column: {$n}", $this->declaraciones(".cal-carril-{$n}", self::HOJA), "Carril {$n}.");
            $this->assertStringContainsString("flex-grow: {$n}", $this->declaraciones(".cal-col--carriles-{$n}", self::HOJA), "Ancho del día con {$n} carriles.");
        }
    }

    public function test_los_bordes_y_el_foco_cumplen_el_contraste(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        $tokens = $this->tokensDeColor();

        foreach (self::BORDES as [$borde, $fondo, $selector]) {
            $this->assertStringContainsString("var({$borde})", $this->declaraciones($selector, self::HOJA), "«{$selector}» usa {$borde}.");
            $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens[$borde], $tokens[$fondo]), "{$borde} sobre {$fondo} no llega a 3:1.");
        }

        foreach (['.cal-cita:focus-visible', '.cal-hueco:focus-visible', '.cal-lista-cita:focus-visible', '.tira-dia:focus-visible', '.mes-dia:focus-visible', '.cal-vista-op:focus-visible', '.cal-dia-cab:focus-visible'] as $selector) {
            $this->assertStringContainsString('outline: var(--focus-ring-width) solid var(--focus-ring)', $this->declaraciones($selector, self::HOJA), "{$selector} con el anillo del sistema.");
        }
        foreach (self::FONDOS_FOCO as $fondo) {
            $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens['--focus-ring'], $tokens[$fondo]), "El foco sobre {$fondo} no llega a 3:1.");
        }
    }

    public function test_en_el_movil_cada_dia_cita_y_control_mide_el_minimo_de_toque(): void
    {
        $this->assertFileExists(public_path(self::HOJA));
        $movil = array_filter($this->reglasCss(self::HOJA), fn ($regla) => in_array('@media (max-width: 899px)', $regla[2], true));
        $declarado = function (string $selector) use ($movil): string {
            $cuerpos = [];
            foreach ($movil as [$selectores, $cuerpo]) {
                if (in_array($selector, array_map('trim', explode(',', $selectores)), true)) {
                    $cuerpos[] = $cuerpo;
                }
            }

            return implode("\n", $cuerpos);
        };

        foreach (['.cal-control', '.cal-vista-op', '.tira-dia', '.mes-dia'] as $selector) {
            $this->assertMatchesRegularExpression('/min-height: (var\(--touch-min\)|calc\(var\(--touch-min\) \+ var\(--space-\d\)\))/', $declarado($selector), "{$selector} mide al menos --touch-min en el móvil.");
        }
        $this->assertStringContainsString('min-height: var(--touch-min)', $this->declaraciones('.cal-lista-cita', self::HOJA), 'Cada fila de la lista mide al menos --touch-min.');
    }
}
