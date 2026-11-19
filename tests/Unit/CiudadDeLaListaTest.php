<?php

namespace Tests\Unit;

use App\Rules\CiudadDeLaLista;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Spec 051 (CA5, CA6, CA10; TM1, TM2): qué ciudades acepta la regla. */
class CiudadDeLaListaTest extends TestCase
{
    /** @return list<string> mensajes de error de la regla */
    private function errores(mixed $valor, ?string $guardada = null): array
    {
        $errores = [];
        (new CiudadDeLaLista($guardada))->validate('ciudad', $valor, function (string $mensaje) use (&$errores): void {
            $errores[] = $mensaje;
        });

        return $errores;
    }

    public function test_acepta_un_nombre_de_la_lista(): void
    {
        $this->assertSame([], $this->errores('Lázaro Cárdenas'));
        $this->assertSame([], $this->errores('Zihuatanejo de Azueta'));
        $this->assertSame([], $this->errores('Otro lugar'));
    }

    /** @return array<string, array{mixed}> */
    public static function valoresFueraDeLaLista(): array
    {
        return [
            'otra ciudad' => ['Ciudad de México'],
            'sin tildes' => ['lazaro cardenas'],
            'texto largo' => [str_repeat('x', 101)],
            'marcado' => ['<b>x</b>'],
            'lista de valores' => [['Morelia']],
            'número' => [12],
        ];
    }

    #[DataProvider('valoresFueraDeLaLista')]
    public function test_rechaza_lo_que_no_esta_en_la_lista(mixed $valor): void
    {
        $this->assertSame(['Elige una ciudad de la lista'], $this->errores($valor));
    }

    public function test_acepta_la_ciudad_guardada_fuera_de_la_lista(): void
    {
        $this->assertSame([], $this->errores('LC', 'LC'));
    }

    public function test_acepta_la_guardada_con_espacios_sobrantes_que_llega_recortada(): void
    {
        $this->assertSame([], $this->errores('LC', ' LC '));
    }

    public function test_rechaza_otra_ciudad_fuera_de_la_lista_distinta_de_la_guardada(): void
    {
        $this->assertSame(['Elige una ciudad de la lista'], $this->errores('zihuayork', 'LC'));
    }

    public function test_la_guardada_se_compara_exacta(): void
    {
        $this->assertSame(['Elige una ciudad de la lista'], $this->errores('lc', 'LC'));
    }

    public function test_una_guardada_de_la_lista_no_abre_la_puerta_a_otros_valores(): void
    {
        $this->assertSame(['Elige una ciudad de la lista'], $this->errores('LC', 'Morelia'));
        $this->assertSame([], $this->errores('Uruapan', 'Morelia'));
    }
}
