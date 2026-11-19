<?php

namespace Tests\Unit;

use App\Pacientes\Ciudades;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Spec 051 (CA1, CA8): la lista cerrada de ciudades y la normalización de las ya guardadas. */
class CiudadesTest extends TestCase
{
    public function test_la_lista_tiene_113_de_michoacan_y_4_de_guerrero_en_orden(): void
    {
        $this->assertCount(113, Ciudades::MICHOACAN);
        $this->assertSame([
            'Coahuayutla de José María Izazaga',
            'La Unión de Isidoro Montes de Oca',
            'Petatlán',
            'Zihuatanejo de Azueta',
        ], Ciudades::GUERRERO);

        foreach (Ciudades::grupos() as $estado => $municipios) {
            $claves = array_map(fn (string $nombre) => Str::lower(Str::ascii($nombre)), $municipios);
            $ordenadas = $claves;
            sort($ordenadas, SORT_STRING);

            $this->assertSame($ordenadas, $claves, "{$estado}: en orden alfabético sin contar las tildes.");
        }
    }

    public function test_los_grupos_son_michoacan_y_guerrero(): void
    {
        $this->assertSame(['Michoacán' => Ciudades::MICHOACAN, 'Guerrero' => Ciudades::GUERRERO], Ciudades::grupos());
    }

    public function test_ningun_nombre_se_repite(): void
    {
        $claves = array_map(fn (string $nombre) => Str::lower(Str::ascii($nombre)), Ciudades::nombres());

        $this->assertSame(array_values(array_unique($claves)), $claves);
    }

    public function test_otro_lugar_va_al_final_de_los_nombres(): void
    {
        $nombres = Ciudades::nombres();

        $this->assertSame('Otro lugar', Ciudades::OTRO_LUGAR);
        $this->assertSame(Ciudades::OTRO_LUGAR, end($nombres));
        $this->assertCount(118, $nombres);
    }

    public function test_contiene_exige_el_nombre_exacto(): void
    {
        $this->assertTrue(Ciudades::contiene('Lázaro Cárdenas'));
        $this->assertTrue(Ciudades::contiene('Otro lugar'));
        $this->assertFalse(Ciudades::contiene('lazaro cardenas'));
        $this->assertFalse(Ciudades::contiene('Lázaro Cárdenas '));
        $this->assertFalse(Ciudades::contiene(''));
        $this->assertFalse(Ciudades::contiene(null));
    }

    /** @return array<string, array{string, string}> */
    public static function textosQueCoinciden(): array
    {
        return [
            'sin tildes ni mayúsculas' => ['lazaro cardenas', 'Lázaro Cárdenas'],
            'en mayúsculas con espacio final' => ['MORELIA ', 'Morelia'],
            'espacios dobles y al inicio' => ['  Los   Reyes', 'Los Reyes'],
            'eñe sin tilde' => ['brisenas', 'Briseñas'],
            'diéresis' => ['tinguindin', 'Tingüindín'],
            'nombre ya correcto' => ['Petatlán', 'Petatlán'],
            'alias Zihuatanejo' => ['Zihuatanejo', 'Zihuatanejo de Azueta'],
            'alias La Unión' => ['La Unión', 'La Unión de Isidoro Montes de Oca'],
            'alias Coahuayutla' => ['coahuayutla', 'Coahuayutla de José María Izazaga'],
            'alias Coalcomán' => ['Coalcoman', 'Coalcomán de Vázquez Pallares'],
            'alias Cojumatlán' => ['Cojumatlán', 'Cojumatlán de Régules'],
            'alias Tiquicheo' => ['TIQUICHEO', 'Tiquicheo de Nicolás Romero'],
            'alias Ciudad Hidalgo' => ['Ciudad Hidalgo', 'Hidalgo'],
            'alias Los Reyes de Salgado' => ['los reyes de salgado', 'Los Reyes'],
        ];
    }

    #[DataProvider('textosQueCoinciden')]
    public function test_normalizar_lleva_al_nombre_de_la_lista(string $texto, string $esperado): void
    {
        $this->assertSame($esperado, Ciudades::normalizar($texto));
        $this->assertSame($esperado, Ciudades::normalizar($esperado), 'Normalizar dos veces da lo mismo.');
    }

    /** @return array<string, array{string}> */
    public static function textosSinCoincidencia(): array
    {
        return [
            'sigla' => ['LC'],
            'inventada' => ['zihuayork'],
            'otro estado' => ['colima'],
            'nombre con coletilla' => ['Lázaro Cárdenas, Mich.'],
            'vacío' => ['   '],
        ];
    }

    #[DataProvider('textosSinCoincidencia')]
    public function test_normalizar_no_adivina(string $texto): void
    {
        $this->assertNull(Ciudades::normalizar($texto));
    }
}
