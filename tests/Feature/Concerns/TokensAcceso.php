<?php

namespace Tests\Feature\Concerns;

/**
 * Spec 041 (P9, plan D9 y D17): qué valores admite el acceso. Lo usan los tests de la hoja de los
 * correos (`CorreosRestablecimientoTest`) y de `acceso.css` (`RecuperarContrasenaVistasTest`).
 *
 * Los tokens salen de `:root` en `app.css`, más los cuatro que `system.md` 1.11.0 añade solo para
 * el acceso, que los comprueba `test_los_tokens_del_acceso_son_los_de_system_md`.
 */
trait TokensAcceso
{
    /** Los tokens de `system.md` 1.11.0 que `app.css` no declara: solo los usa el acceso. */
    private const TOKENS_SOLO_DEL_ACCESO = [
        '--font-acceso' => 'Roboto, system-ui, sans-serif',
        '--text-acceso-small' => '12px',
        '--duration-respuesta' => '150ms',
        '--ease-clinico' => 'cubic-bezier(0.2, 0.7, 0.2, 1)',
    ];

    /** D17: propiedades revisadas (fuera de `:root`). Los atributos HTML de las tablas no son CSS. */
    private const PROPIEDADES_REVISADAS = '/^(color|background(-color)?|border(-(top|right|bottom|left))?(-(color|width))?|font-family|font-size|font-weight|line-height|margin(-\w+)?|padding(-\w+)?|gap|row-gap|column-gap|border-radius|box-shadow|max-width|transition(-duration)?)$/i';

    /** D17: valores exentos; `solid` y `dashed` son el estilo del borde, no un valor medible. */
    private const VALOR_EXENTO = '/^(0|auto|none|inherit|currentcolor|transparent|\d+(\.\d+)?%|\d+(\.\d+)?fr|solid|dashed)$/i';

    /**
     * Tokens del sistema con su valor, resolviendo `var()`.
     *
     * @return array<string, string>
     */
    private function tokensDelSistema(): array
    {
        preg_match('/:root\s*\{(.*?)\n\}/s', file_get_contents(public_path('css/app.css')), $root);
        $sinComentarios = preg_replace('#/\*.*?\*/#s', '', $root[1] ?? '');
        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $sinComentarios, $pares, PREG_SET_ORDER);

        $crudos = self::TOKENS_SOLO_DEL_ACCESO;
        foreach ($pares as [, $nombre, $valor]) {
            $crudos[$nombre] = trim(preg_replace('/\s+/', ' ', $valor));
        }

        $resolver = function (string $valor, int $profundidad = 0) use (&$resolver, $crudos): string {
            if (preg_match('/^var\((--[\w-]+)\)$/', $valor, $m) && isset($crudos[$m[1]]) && $profundidad < 5) {
                return $resolver($crudos[$m[1]], $profundidad + 1);
            }

            return $valor;
        };

        return array_map($resolver, $crudos);
    }

    /**
     * Declaraciones de las propiedades revisadas de un cuerpo CSS o de un atributo `style`.
     *
     * @return list<array{0: string, 1: string}> `[propiedad, valor]`
     */
    private function declaracionesRevisadas(string $cuerpo): array
    {
        $revisadas = [];
        foreach (explode(';', $cuerpo) as $declaracion) {
            if (! str_contains($declaracion, ':')) {
                continue;
            }
            [$propiedad, $valor] = array_map('trim', explode(':', $declaracion, 2));
            if (preg_match(self::PROPIEDADES_REVISADAS, $propiedad)) {
                $revisadas[] = [$propiedad, $valor];
            }
        }

        return $revisadas;
    }

    /** Hoja o estilo en línea de un correo: cada parte del valor es el de un token o está exenta (D9). */
    private function esValorDeToken(string $valor): bool
    {
        $valor = trim(preg_replace('/\s+/', ' ', $valor));
        $valores = array_map('strtolower', array_values($this->tokensDelSistema()));
        if (in_array(strtolower($valor), $valores, true)) {
            return true;
        }

        foreach (preg_split('/\s+(?![^(]*\))/', $valor) as $parte) {
            if (! preg_match(self::VALOR_EXENTO, $parte) && ! in_array(strtolower($parte), $valores, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * `acceso.css`, fuera de `:root`: nada que no sea `var(--…)`, una exención de D17 o la cuenta o
     * la función que los combina (`calc(var(--a) * 2.5)`, `linear-gradient(145deg, var(--a), var(--b))`).
     */
    private function usaSoloTokens(string $valor): bool
    {
        $resto = preg_replace('/var\(--[\w-]+\)/', '', $valor);
        $resto = preg_replace('/\b(calc|linear-gradient)\(|\)|,|\*|\/|(?<![\w.])-?\d*\.?\d+(deg)?(?![\w.%])|\b(center|no-repeat)\b/', ' ', $resto);

        foreach (preg_split('/\s+/', trim($resto), -1, PREG_SPLIT_NO_EMPTY) as $parte) {
            if (! preg_match(self::VALOR_EXENTO, $parte) && ! preg_match('/^(background|color|transform)$/', $parte)) {
                return false;
            }
        }

        return true;
    }
}
