<?php

namespace Tests\Unit;

use App\Pacientes\PosiblesDuplicados;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec 054 (CA3, D5; /analyze A5): quién cuenta como posible duplicado de un alta nueva. Los
 * pacientes se crean con un apellido que no existe en el volcado, para no chocar con sus datos.
 */
class PosiblesDuplicadosTest extends TestCase
{
    use DatabaseTransactions;

    private const APELLIDO = 'Quetzalcóatl Pruebas';

    public function test_coincide_sin_distinguir_mayusculas_tildes_ni_espacios_con_el_telefono_en_otro_formato(): void
    {
        $id = $this->paciente('José', self::APELLIDO, telefono: '5512345678');

        $resultado = PosiblesDuplicados::buscar(' jose ', 'QUETZALCOATL   pruebas', '55 1234 5678', null);

        $this->assertSame(['ids' => [$id], 'total' => 1], $resultado);
    }

    public function test_coincide_con_la_fecha_de_nacimiento(): void
    {
        $id = $this->paciente('Lucía', self::APELLIDO, fecha: '1990-04-15');

        $this->assertSame(['ids' => [$id], 'total' => 1], PosiblesDuplicados::buscar('Lucia', self::APELLIDO, null, '1990-04-15'));
    }

    public function test_no_coincide_si_solo_coincide_el_nombre(): void
    {
        $this->paciente('Mario', self::APELLIDO, telefono: '5511112222', fecha: '1980-01-01');

        $this->assertSame(['ids' => [], 'total' => 0], PosiblesDuplicados::buscar('Mario', self::APELLIDO, '5599998888', '1981-02-02'));
    }

    public function test_no_coincide_si_el_apellido_es_otro_aunque_coincida_el_telefono(): void
    {
        $this->paciente('Mario', self::APELLIDO, telefono: '5511112222');

        $this->assertSame(['ids' => [], 'total' => 0], PosiblesDuplicados::buscar('Mario', 'Quetzalcóatl Distinto', '5511112222', null));
    }

    public function test_sin_telefono_ni_fecha_en_la_entrada_no_hay_coincidencia(): void
    {
        $this->paciente('Rosa', self::APELLIDO, telefono: '5511112222', fecha: '1970-07-07');

        $this->assertSame(['ids' => [], 'total' => 0], PosiblesDuplicados::buscar('Rosa', self::APELLIDO, null, null));
    }

    public function test_un_telefono_guardado_vacio_no_coincide_con_otro_vacio(): void
    {
        $this->paciente('Rosa', self::APELLIDO);

        $this->assertSame(['ids' => [], 'total' => 0], PosiblesDuplicados::buscar('Rosa', self::APELLIDO, ' - ', null));
    }

    public function test_incluye_pacientes_desactivados_y_fichas_de_cuentas_del_personal(): void
    {
        $desactivado = $this->paciente('Elena', self::APELLIDO, telefono: '5533334444', activo: 0);
        $delPersonal = $this->paciente('Elena', self::APELLIDO, telefono: '5533334444', rol: 'admin');

        $resultado = PosiblesDuplicados::buscar('Elena', self::APELLIDO, '5533334444', null);

        $this->assertSame(2, $resultado['total']);
        $this->assertEqualsCanonicalizing([$desactivado, $delPersonal], $resultado['ids']);
    }

    public function test_devuelve_como_mucho_tres_ids_estables_con_el_total_real(): void
    {
        $ids = [];
        foreach (['Ana', 'ANA', 'Ána', 'ana', 'Aná'] as $nombre) {
            $ids[] = $this->paciente($nombre, self::APELLIDO, fecha: '2000-01-01');
        }

        $resultado = PosiblesDuplicados::buscar('Ana', self::APELLIDO, null, '2000-01-01');

        $this->assertSame(5, $resultado['total']);
        $this->assertCount(3, $resultado['ids']);
        $this->assertSame([], array_diff($resultado['ids'], $ids), 'Solo ids de las coincidencias.');
        $this->assertSame($resultado, PosiblesDuplicados::buscar('Ana', self::APELLIDO, null, '2000-01-01'), 'El orden es estable.');
    }

    private function paciente(string $nombre, string $apellido, ?string $telefono = null, ?string $fecha = null, int $activo = 1, string $rol = 'cliente'): string
    {
        $usuario = (string) Str::uuid();
        DB::table('usuarios')->insert([
            'id' => $usuario, 'nombre' => $nombre, 'apellido' => $apellido,
            'email' => 'dup-'.Str::random(10).'@example.test', 'password_hash' => null,
            'rol' => $rol, 'telefono' => $telefono, 'activo' => $activo,
        ]);

        $paciente = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $paciente, 'usuario_id' => $usuario, 'fecha_nacimiento' => $fecha]);

        return $paciente;
    }
}
