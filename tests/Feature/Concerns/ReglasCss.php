<?php

namespace Tests\Feature\Concerns;

/**
 * Lectura de las hojas de estilo para los tests de diseño (P9): reglas con sus `@media`,
 * declaraciones de un selector, literales prohibidos, tokens de color y contraste WCAG.
 *
 * Sacado de `ReprogramarCitaVistasTest` (spec 028) en la spec 033 (T003, /analyze A17).
 */
trait ReglasCss
{
    /**
     * Reglas de una hoja de `public/` como `[selector, cuerpo, condiciones]`. Las condiciones son
     * las `@media` que envuelven la regla, de fuera a dentro (`[]` si no hay ninguna).
     */
    private function reglasCss(string $archivo = 'css/app.css'): array
    {
        return $this->analizarCss(file_get_contents(public_path($archivo)));
    }

    /** Recorre el CSS contando niveles de llaves; los comentarios no cuentan. */
    private function analizarCss(string $css): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $reglas = [];
        $pila = [];
        $texto = '';

        foreach (str_split($css) as $caracter) {
            if ($caracter === '{') {
                $pila[] = trim($texto);
                $texto = '';
            } elseif ($caracter === '}') {
                $prelude = array_pop($pila);
                if ($prelude !== null && ! str_starts_with($prelude, '@')) {
                    $condiciones = array_values(array_filter($pila, fn ($p) => str_starts_with($p, '@')));
                    $reglas[] = [preg_replace('/\s+/', ' ', $prelude), $texto, $condiciones];
                }
                $texto = '';
            } else {
                $texto .= $caracter;
            }
        }

        return $reglas;
    }

    /** Declaraciones de un selector exacto (dentro de una lista de selectores) en `app.css`. */
    private function declaraciones(string $selector, string $archivo = 'css/app.css'): string
    {
        $cuerpos = [];
        foreach ($this->reglasCss($archivo) as [$selectores, $cuerpo]) {
            $lista = array_map(fn ($s) => preg_replace('/\s+/', ' ', trim($s)), explode(',', $selectores));
            if (in_array($selector, $lista, true)) {
                $cuerpos[] = $cuerpo;
            }
        }

        return implode("\n", $cuerpos);
    }

    /** Valores literales que P9 prohíbe en reglas nuevas: px, colores, pesos y familias (salvo un token de familia). */
    private function literales(string $cuerpo): array
    {
        $hallados = [];
        if (preg_match_all('/\d(\.\d+)?px/', $cuerpo, $m)) {
            $hallados = array_merge($hallados, $m[0]);
        }
        if (preg_match_all('/#[0-9a-f]{3,8}\b|rgba?\(/i', $cuerpo, $m)) {
            $hallados = array_merge($hallados, $m[0]);
        }
        if (preg_match_all('/font-weight\s*:\s*([^;]+)/', $cuerpo, $m)) {
            $hallados = array_merge($hallados, array_filter($m[1], fn ($v) => ! str_starts_with(trim($v), 'var(--weight-')));
        }
        if (preg_match_all('/font-family\s*:\s*([^;]+)/', $cuerpo, $m)) {
            $hallados = array_merge($hallados, array_filter($m[1], fn ($v) => trim($v) !== 'inherit' && ! str_starts_with(trim($v), 'var(--font-')));
        }

        return $hallados;
    }

    /**
     * Literales de las páginas públicas (spec 038, /analyze A5): los de `literales()` más
     * duraciones, `rem`/`em` y curvas `cubic-bezier(`, que allí también van con token; y
     * (`/review 038` R1) `line-height` y `aspect-ratio` con números en lugar de token.
     */
    private function literalesPublicos(string $cuerpo): array
    {
        $hallados = $this->literales($cuerpo);
        foreach (['/(?<![\w-])\d+(\.\d+)?m?s\b/', '/(?<![\w-])\d*\.?\d+r?em\b/', '/cubic-bezier\(/', '/line-height\s*:\s*[\d.]+/', '/aspect-ratio\s*:\s*\d[\d.\s\/]*/'] as $patron) {
            if (preg_match_all($patron, $cuerpo, $m)) {
                $hallados = array_merge($hallados, $m[0]);
            }
        }

        return $hallados;
    }

    /** Tokens de color de `:root` (hex), resolviendo `var()`. */
    private function tokensDeColor(): array
    {
        preg_match('/:root\s*\{([^}]*)\}/', file_get_contents(public_path('css/app.css')), $root);
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
