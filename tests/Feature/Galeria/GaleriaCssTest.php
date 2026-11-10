<?php

namespace Tests\Feature\Galeria;

use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 039 (T017; CA5; P9): los estilos de la galería solo usan tokens del sistema, y el
 * movimiento del carrusel solo corre sin movimiento reducido (M-1 de `design.md`).
 */
class GaleriaCssTest extends TestCase
{
    use ReglasCss;

    private const SIN_MOVIMIENTO = '@media (prefers-reduced-motion: no-preference)';

    /** Componentes públicos (`publico.css`) y de la gestión (`app.css`) de `design.md`. */
    private const PUBLICOS = ['.pub-carrusel', '.pub-trabajo', '.pub-mosaico', '.pub-galeria__vacia', '.pub-solo-lector'];

    private const GESTION = ['.foto-tarjeta', '.campo-paciente', '.opcion', '.consentimiento', '.campo-archivo', '.aviso-error'];

    /** Reglas de la hoja cuyo selector nombra el componente (`.pub-carrusel`, `.pub-carrusel__lista li`…). */
    private function reglasDe(string $componente, string $hoja): array
    {
        $patron = '/'.preg_quote($componente, '/').'(?![\w-]*[a-z])|'.preg_quote($componente, '/').'(__|--)[\w-]+/';

        return array_values(array_filter($this->reglasCss($hoja), fn ($r) => preg_match($patron, $r[0]) === 1));
    }

    public function test_las_reglas_publicas_existen_y_solo_usan_tokens(): void
    {
        foreach (self::PUBLICOS as $componente) {
            $reglas = $this->reglasDe($componente, 'css/publico.css');
            $this->assertNotEmpty($reglas, "{$componente} tiene estilos en publico.css.");
            foreach ($reglas as [$selector, $cuerpo]) {
                $this->assertSame([], $this->literalesPublicos($cuerpo), "{$selector}: solo tokens.");
            }
        }
    }

    public function test_las_reglas_de_la_gestion_existen_y_solo_usan_tokens(): void
    {
        foreach (self::GESTION as $componente) {
            $reglas = $this->reglasDe($componente, 'css/app.css');
            $this->assertNotEmpty($reglas, "{$componente} tiene estilos en app.css.");
            foreach ($reglas as [$selector, $cuerpo]) {
                $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
                $this->assertStringContainsString('.formulario-accesible', $selector, "{$selector}: dentro de .formulario-accesible.");
            }
        }
    }

    public function test_el_movimiento_solo_sin_movimiento_reducido(): void
    {
        $conMovimiento = 0;
        foreach ($this->reglasDe('.pub-carrusel', 'css/publico.css') as [$selector, $cuerpo, $envolventes]) {
            if (preg_match('/\b(transition|animation)(-[\w-]+)?\s*:|scroll-behavior\s*:\s*smooth|transform\s*:/', $cuerpo)) {
                $this->assertContains(self::SIN_MOVIMIENTO, $envolventes, "{$selector}: el movimiento solo sin prefers-reduced-motion.");
                $conMovimiento++;
            }
        }
        $this->assertGreaterThan(0, $conMovimiento, 'El carrusel tiene su movimiento (M-1).');
    }

    public function test_los_tokens_nuevos_estan_en_el_sistema_y_en_las_hojas(): void
    {
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        foreach (['--foto-ratio', '--carrusel-foto-w', '--thumb-w'] as $token) {
            $this->assertStringContainsString("`{$token}`", $sistema, "{$token} documentado en system.md.");
        }

        $publico = file_get_contents(public_path('css/publico.css'));
        $this->assertMatchesRegularExpression('/--foto-ratio\s*:\s*4\s*\/\s*3\s*;/', $publico);
        $this->assertMatchesRegularExpression('/--carrusel-foto-w\s*:\s*86%\s*;/', $publico, 'Valor por debajo de 560 px.');
        $this->assertMatchesRegularExpression('/--carrusel-foto-w\s*:\s*72%\s*;/', $publico, 'Desde 560 px.');
        $this->assertMatchesRegularExpression('/--carrusel-foto-w\s*:\s*90%\s*;/', $publico, 'Desde 900 px.');
        $this->assertMatchesRegularExpression('/--thumb-w\s*:\s*120px\s*;/', file_get_contents(public_path('css/app.css')));
    }
}
