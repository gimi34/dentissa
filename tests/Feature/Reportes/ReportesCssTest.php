<?php

namespace Tests\Feature\Reportes;

use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 047, CA8 y P9: `reportes.css` solo usa tokens del sistema, sus controles miden
 * `--touch-min`, sus colores cumplen el contraste AA y trae la hoja de impresión.
 */
class ReportesCssTest extends TestCase
{
    use ReglasCss;

    private const HOJA = 'css/reportes.css';

    private const VISTA = 'views/reportes/index.blade.php';

    /** Fondos sobre los que se pinta el texto de «Reportes»: las tarjetas y la página. */
    private const FONDOS = ['--white', '--bg'];

    private function impresion(): array
    {
        return array_values(array_filter(
            $this->reglasCss(self::HOJA),
            fn ($regla) => in_array('@media print', $regla[2], true),
        ));
    }

    /** Cuerpo de las reglas de impresión cuyo selector incluye el dado. */
    private function alImprimir(string $selector): string
    {
        $cuerpos = [];
        foreach ($this->impresion() as [$selectores, $cuerpo]) {
            $lista = array_map('trim', explode(',', $selectores));
            if (in_array($selector, $lista, true)) {
                $cuerpos[] = $cuerpo;
            }
        }

        return implode("\n", $cuerpos);
    }

    public function test_el_detector_de_literales_funciona(): void
    {
        // Control positivo: el patrón detecta un literal de cada tipo.
        $this->assertNotSame([], $this->literales('padding: 12px;'));
        $this->assertNotSame([], $this->literales('color: #993556;'));
        $this->assertNotSame([], $this->literales('font-weight: 600;'));
        $this->assertSame([], $this->literales('padding: var(--space-4); color: var(--p600); font-weight: var(--weight-strong);'));
    }

    public function test_reportes_css_solo_usa_tokens(): void
    {
        $reglas = $this->reglasCss(self::HOJA);
        $this->assertNotSame([], $reglas, 'reportes.css tiene reglas.');

        foreach ($reglas as [$selector, $cuerpo]) {
            $this->assertSame([], $this->literales($cuerpo), "«{$selector}» usa valores literales en lugar de tokens.");
        }
    }

    public function test_los_controles_miden_el_minimo_de_toque_a_todos_los_anchos(): void
    {
        foreach (['.reporte .btn', '.reporte .field'] as $selector) {
            $medidos = false;
            foreach ($this->reglasCss(self::HOJA) as [$selectores, $cuerpo, $condiciones]) {
                $lista = array_map('trim', explode(',', $selectores));
                if (in_array($selector, $lista, true) && $condiciones === [] && str_contains($cuerpo, 'min-height: var(--touch-min)')) {
                    $medidos = true;
                }
            }
            $this->assertTrue($medidos, "«{$selector}» declara min-height: var(--touch-min) fuera de cualquier @media.");
        }
    }

    public function test_los_controles_tienen_foco_visible(): void
    {
        $foco = $this->declaraciones('.reporte .btn:focus-visible', self::HOJA);

        $this->assertStringContainsString('outline: var(--focus-ring-width) solid var(--focus-ring)', $foco);
        $this->assertStringContainsString('outline-offset: var(--focus-ring-offset)', $foco);
    }

    public function test_la_vista_no_tiene_estilos_en_linea(): void
    {
        $vista = file_get_contents(resource_path(self::VISTA));

        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/', $vista);
        $this->assertStringNotContainsString('<style', $vista);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/', $vista, 'Sin manejadores en línea.');
        $this->assertStringNotContainsString('{!!', $vista);
    }

    public function test_los_colores_de_texto_cumplen_el_contraste(): void
    {
        $tokens = $this->tokensDeColor();
        $usados = [];
        foreach ($this->reglasCss(self::HOJA) as [, $cuerpo]) {
            // Solo `color:`, no `background-color:` ni `border-color:`.
            if (preg_match_all('/(?<![\w-])color\s*:\s*var\((--[\w-]+)\)/', $cuerpo, $m)) {
                $usados = array_merge($usados, $m[1]);
            }
        }
        $usados = array_values(array_unique($usados));
        $this->assertNotSame([], $usados, 'reportes.css declara colores de texto.');

        foreach ($usados as $token) {
            $this->assertArrayHasKey($token, $tokens, "{$token} es un token de color del sistema.");
            foreach (self::FONDOS as $fondo) {
                $this->assertGreaterThanOrEqual(4.5, $this->contraste($tokens[$token], $tokens[$fondo]),
                    "{$token} sobre {$fondo} no llega a 4,5:1.");
            }
        }
    }

    public function test_los_bordes_de_control_y_el_foco_cumplen_el_contraste(): void
    {
        $tokens = $this->tokensDeColor();
        $hoja = file_get_contents(public_path(self::HOJA));

        $this->assertStringContainsString('var(--border-control)', $hoja, 'Los controles usan el borde de control.');
        foreach (['--border-control', '--focus-ring'] as $token) {
            foreach (self::FONDOS as $fondo) {
                $this->assertGreaterThanOrEqual(3.0, $this->contraste($tokens[$token], $tokens[$fondo]), "{$token} sobre {$fondo} no llega a 3:1.");
            }
        }
    }

    public function test_al_imprimir_se_ocultan_el_menu_el_filtro_y_los_botones(): void
    {
        $this->assertNotSame([], $this->impresion(), 'reportes.css tiene reglas @media print.');

        foreach (['.sidebar', '.topbar', '.reporte-filtro', '.reporte-atajos', '.reporte-acciones'] as $selector) {
            $this->assertStringContainsString('display: none', $this->alImprimir($selector), "Al imprimir, «{$selector}» no se muestra.");
        }

        // La cabecera impresa solo existe en el papel.
        $this->assertStringContainsString('display: none', $this->declaraciones('.solo-impresion', self::HOJA));
        $this->assertStringContainsString('display: block', $this->alImprimir('.solo-impresion'));
    }

    public function test_el_papel_va_en_negro_sobre_blanco(): void
    {
        $papel = $this->alImprimir('.reporte');

        $this->assertStringContainsString('color: var(--text)', $papel);
        $this->assertStringContainsString('background: var(--white)', $papel);
        $this->assertSame('#000000', strtoupper($this->tokensDeColor()['--text']));
        $this->assertSame('#FFFFFF', strtoupper($this->tokensDeColor()['--white']));
    }

    public function test_las_filas_no_se_parten_entre_paginas(): void
    {
        $this->assertStringContainsString('break-inside: avoid', $this->alImprimir('.reporte tr'));
        $this->assertStringContainsString('break-inside: avoid', $this->alImprimir('.reporte .stat-card'));
    }
}
