<?php

namespace Tests\Feature\Auditoria;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\ReglasCss;
use Tests\TestCase;

/**
 * Spec 020, P9 (CA6, CA11): las vistas y el CSS nuevos usan solo el sistema de diseño.
 *
 * Un caso por vista y por hoja (A82): la consulta los cierra en T030 y la página 503 en T036.
 */
class AuditoriaVistasTest extends TestCase
{
    use ReglasCss;

    /** @return array<string, array{0: string}> */
    public static function vistasNuevas(): array
    {
        return [
            'consulta' => ['resources/views/auditoria/index.blade.php'],
            'página 503' => ['resources/views/errors/auditoria-no-disponible.blade.php'],
            'entrada del menú' => ['resources/views/layouts/app.blade.php#auditoria'],
        ];
    }

    /** @return array<string, array{0: string}> hoja de `public/`, o `app.css#020` para el bloque de la 020 */
    public static function hojasNuevas(): array
    {
        return [
            'auditoria.css' => ['css/auditoria.css'],
            'errores.css' => ['css/errores.css'],
            'paginación de app.css' => ['css/app.css#020'],
        ];
    }

    private function vista(string $origen): string
    {
        [$archivo, $parte] = array_pad(explode('#', $origen, 2), 2, null);
        $this->assertFileExists(base_path($archivo));
        $contenido = file_get_contents(base_path($archivo));
        if ($parte === 'auditoria') {
            $this->assertSame(1, preg_match('#<a href="/admin/auditoria".*?</a>#s', $contenido, $m), 'El layout tiene la entrada «Auditoría».');

            return $m[0];
        }

        return $contenido;
    }

    private function hoja(string $origen): string
    {
        [$archivo, $parte] = array_pad(explode('#', $origen, 2), 2, null);
        $this->assertFileExists(public_path($archivo));
        $css = file_get_contents(public_path($archivo));
        if ($parte === '020') {
            $inicio = strpos($css, '/* Spec 020: paginación');
            $fin = strpos($css, '/* Fin spec 020 */');
            $this->assertNotFalse($inicio, 'app.css tiene el bloque de la 020.');
            $this->assertNotFalse($fin);

            return substr($css, $inicio, $fin - $inicio);
        }

        return $css;
    }

    #[DataProvider('vistasNuevas')]
    public function test_las_vistas_nuevas_no_tienen_estilos_en_linea(string $origen): void
    {
        $html = $this->vista($origen);

        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html, 'Sin style="".');
        $this->assertDoesNotMatchRegularExpression('#<style\b#i', $html, 'Sin <style> en la vista.');
        $this->assertDoesNotMatchRegularExpression('#<script\b(?![^>]*\bsrc=)[^>]*>#i', $html, 'Sin <script> con código.');
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $html, 'Sin manejadores en línea.');
        $this->assertStringNotContainsString('{!!', $html, 'Solo salida escapada (P4).');
    }

    #[DataProvider('hojasNuevas')]
    public function test_el_css_nuevo_solo_usa_tokens(string $origen): void
    {
        $this->assertCount(3, $this->literales('margin: 14px; color: #fff; font-weight: 600;'), 'Control positivo de literales().');
        $css = $this->hoja($origen);
        $sistema = file_get_contents(base_path('docs/design/system.md'));
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);

        $reglas = $this->analizarCss($css);
        $this->assertNotEmpty($reglas);
        foreach ($reglas as [$selector, $cuerpo]) {
            $this->assertNotSame(':root', trim($selector), 'Los tokens viven en app.css, no en la hoja nueva.');
            $this->assertSame([], $this->literales($cuerpo), "{$selector}: solo tokens.");
        }
        preg_match_all('/var\((--[\w-]+)\)/', $css, $usados);
        foreach (array_unique($usados[1]) as $token) {
            $this->assertMatchesRegularExpression('/'.preg_quote($token, '/').'\s*:/', $root[1] ?? '', "{$token} está en :root de app.css.");
            $this->assertStringContainsString('`'.$token, $sistema, "{$token} está documentado en system.md.");
        }
    }

    /** Texto ≥ 4.5:1; bordes de control y foco ≥ 3:1 (WCAG 1.4.3 y 1.4.11), con los colores de c4. */
    public function test_los_pares_de_color_cumplen_el_contraste(): void
    {
        $colores = $this->tokensDeColor();
        $texto = [
            ['--text', '--white'], ['--text', '--bg'], ['--text', '--p50'], ['--text', '--green-bg'],
            ['--p600', '--white'], ['--red', '--white'], ['--red', '--red-bg'], ['--white', '--p800'], ['--p600', '--p100'],
        ];
        $noTexto = [['--border-control', '--white'], ['--focus-ring', '--white'], ['--focus-ring', '--bg'], ['--red', '--white']];

        foreach ([[$texto, 4.5], [$noTexto, 3.0]] as [$pares, $minimo]) {
            foreach ($pares as [$delante, $detras]) {
                $this->assertArrayHasKey($delante, $colores);
                $this->assertArrayHasKey($detras, $colores);
                $this->assertGreaterThanOrEqual($minimo, $this->contraste($colores[$delante], $colores[$detras]), "{$delante} sobre {$detras}.");
            }
        }
    }
}
