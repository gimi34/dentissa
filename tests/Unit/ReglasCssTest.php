<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Concerns\ReglasCss;

/**
 * El lector de reglas CSS de los tests de diseño (spec 033, T003): cuenta niveles de llaves y
 * devuelve cada regla con las `@media` que la envuelven, de fuera a dentro (/analyze 033 A17).
 */
class ReglasCssTest extends TestCase
{
    use ReglasCss;

    private const CSS = <<<'CSS'
        /* Un comentario { con llaves } que no cuenta. */
        .suelta, .otra { color: var(--text); }
        @media (max-width: 899px) {
            .main { margin-left: 0; }
            @media (prefers-reduced-motion: no-preference) {
                html.menu-js .sidebar { transition: transform var(--duration-fast); }
            }
            .grid-2 { grid-template-columns: 1fr; }
        }
        .ultima { display: none; }
        CSS;

    public function test_devuelve_cada_regla_con_sus_condiciones_de_fuera_a_dentro(): void
    {
        $reglas = $this->analizarCss(self::CSS);

        $this->assertSame([
            ['.suelta, .otra', []],
            ['.main', ['@media (max-width: 899px)']],
            ['html.menu-js .sidebar', ['@media (max-width: 899px)', '@media (prefers-reduced-motion: no-preference)']],
            ['.grid-2', ['@media (max-width: 899px)']],
            ['.ultima', []],
        ], array_map(fn ($r) => [$r[0], $r[2]], $reglas));
        $this->assertStringContainsString('transition: transform var(--duration-fast)', $reglas[2][1]);
    }

    public function test_los_dos_primeros_elementos_siguen_siendo_selector_y_cuerpo(): void
    {
        // El test de la 028 destructura [$selector, $cuerpo]: no debe cambiar de resultado.
        [$selector, $cuerpo] = $this->analizarCss('.a { gap: var(--space-3); }')[0];

        $this->assertSame('.a', $selector);
        $this->assertSame('gap: var(--space-3);', trim($cuerpo));
    }

    public function test_detecta_los_literales(): void
    {
        // Control positivo de literales(): px, colores, pesos y familias.
        $this->assertCount(4, $this->literales('padding: 6px; color: #fff; background: rgb(0,0,0); font-weight: 600;'));
        $this->assertSame([], $this->literales('font-family: inherit; gap: var(--space-3);'));
    }

    public function test_calcula_el_contraste_wcag(): void
    {
        $this->assertEqualsWithDelta(21.0, $this->contraste('#000000', '#FFFFFF'), 0.01);
        $this->assertEqualsWithDelta(7.0, $this->contraste('#993556', '#FFFFFF'), 0.01);
    }
}
