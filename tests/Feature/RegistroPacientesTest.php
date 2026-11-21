<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Spec 002: autorregistro de pacientes.
 *
 * CA1–CA6 caracterizan lo que ya funciona, cada uno comprobado rompiendo el código que protege. CA8 cambia
 * comportamiento: el rechazo de un correo ya registrado deja de decir que tiene cuenta (plan, Estrategia de pruebas).
 */
class RegistroPacientesTest extends TestCase
{
    use DatabaseTransactions;

    private const CONTRASENA = 'registro-de-pruebas';

    // ── CA1 · el alta crea la cuenta y la ficha ───────────────────────────────────────

    public function test_el_alta_crea_la_cuenta_y_la_ficha_y_entra(): void
    {
        $datos = $this->datosDelAlta();

        $this->post('/registro', $datos)->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));

        $cuenta = Usuario::where('email', $datos['email'])->sole();
        $this->assertSame(['cliente', 1], [$cuenta->rol, (int) $cuenta->activo]);
        $this->assertTrue(Hash::check(self::CONTRASENA, $cuenta->password_hash), 'La contraseña se guarda cifrada.');
        $this->assertNotSame(self::CONTRASENA, $cuenta->password_hash);
        $this->assertSame(1, DB::table('pacientes')->where('usuario_id', $cuenta->id)->count(), 'Tiene su ficha de paciente.');
        $this->assertAuthenticatedAs($cuenta);
    }

    /** CA1 (/analyze A8): la cuenta y la ficha van en una transacción; si la ficha falla, no queda la cuenta. */
    public function test_si_falla_la_ficha_no_queda_la_cuenta(): void
    {
        $datos = $this->datosDelAlta();
        DB::beforeExecuting(function (string $sql) {
            if (str_starts_with($sql, 'insert into `pacientes`')) {
                throw new RuntimeException('Fallo simulado de la ficha de paciente.');
            }
        });

        $this->post('/registro', $datos)->assertStatus(500);

        $this->assertDatabaseMissing('usuarios', ['email' => $datos['email']]);
        $this->assertGuest();
    }

    // ── CA2 · el dentista asignado ────────────────────────────────────────────────────
    // Los `id` y las fechas se fijan para que el resultado no dependa del orden de MariaDB en un empate (/analyze A1).

    public function test_se_asigna_el_dentista_activo_con_menos_pacientes(): void
    {
        $this->desactivarDentistasDelVolcado();
        $this->crearDentista('30000000-0000-4000-8000-000000000003', '2020-01-01 09:00:00', activo: false);
        $d1 = $this->crearDentista('10000000-0000-4000-8000-000000000001', '2021-01-01 09:00:00');
        $d2 = $this->crearDentista('20000000-0000-4000-8000-000000000002', '2022-01-01 09:00:00');
        $this->crearPacienteDe($d1);

        $this->assertSame($d2, $this->dentistaAsignado(), 'El activo con menos pacientes; nunca el inactivo.');
    }

    /**
     * Sin el orden por antigüedad, un empate en SQL no tiene orden definido y MariaDB elige uno cualquiera: con seis
     * empatados, el más antiguo solo ganaría por azar una vez de cada seis (/analyze N2).
     */
    public function test_a_igualdad_el_dentista_mas_antiguo(): void
    {
        $this->desactivarDentistasDelVolcado();
        foreach (['0', '3', '6', '9', 'c'] as $i => $prefijo) {
            $this->crearDentista($prefijo.'0000000-0000-4000-8000-00000000000'.$i, '2022-0'.($i + 1).'-01 09:00:00');
        }
        $antiguo = $this->crearDentista('f0000000-0000-4000-8000-00000000000f', '2021-01-01 09:00:00');

        $this->assertSame($antiguo, $this->dentistaAsignado(), 'A igualdad de pacientes, el más antiguo.');
    }

    /** CA2 (/analyze A7): sin ningún dentista activo, el alta se completa y la ficha queda sin dentista. */
    public function test_sin_dentistas_activos_el_alta_queda_sin_dentista(): void
    {
        $this->desactivarDentistasDelVolcado();
        $this->crearDentista('30000000-0000-4000-8000-000000000003', '2020-01-01 09:00:00', activo: false);

        $this->assertNull($this->dentistaAsignado());
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────────────

    /** Da de alta a alguien nuevo y devuelve el dentista de su ficha. */
    private function dentistaAsignado(): ?string
    {
        $datos = $this->datosDelAlta();
        $this->post('/registro', $datos)->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
        $cuenta = Usuario::where('email', $datos['email'])->sole();

        return DB::table('pacientes')->where('usuario_id', $cuenta->id)->sole()->dentista_id;
    }

    /** Dentro de la transacción del test: solo cuentan los dentistas que crea cada caso. */
    private function desactivarDentistasDelVolcado(): void
    {
        DB::table('usuarios')
            ->whereIn('id', DB::table('personal')->where('tipo', 'dentista')->pluck('usuario_id'))
            ->update(['activo' => 0]);
    }

    /** El `id` y el correo de la cuenta siguen el orden del `id` de `personal`, para que ningún recorrido de MariaDB lo invierta. */
    private function crearDentista(string $id, string $creadoEn, bool $activo = true): string
    {
        $usuarioId = substr($id, 0, 9).'1111'.substr($id, 13);
        DB::table('usuarios')->insert([
            'id' => $usuarioId, 'nombre' => 'Dentista', 'apellido' => substr($id, 0, 8),
            'email' => 'dentista.'.$id.'@example.test', 'password_hash' => Hash::make(self::CONTRASENA),
            'rol' => 'dentista', 'activo' => $activo ? 1 : 0,
        ]);
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuarioId, 'tipo' => 'dentista', 'creado_en' => $creadoEn]);

        return $id;
    }

    private function crearPacienteDe(string $dentista): void
    {
        $usuarioId = (string) Str::uuid();
        DB::table('usuarios')->insert([
            'id' => $usuarioId, 'nombre' => 'Paciente', 'apellido' => 'Asignado',
            'email' => 'paciente.'.Str::lower(Str::random(10)).'@example.test', 'password_hash' => Hash::make(self::CONTRASENA),
            'rol' => 'cliente', 'activo' => 1,
        ]);
        DB::table('pacientes')->insert(['id' => (string) Str::uuid(), 'usuario_id' => $usuarioId, 'dentista_id' => $dentista]);
    }

    /** Un alta válida (/analyze A9): con la confirmación y los términos; la ciudad, de la lista. */
    private function datosDelAlta(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Rita',
            'apellido' => 'Registro',
            'email' => 'registro.'.Str::lower(Str::random(10)).'@example.test',
            'password' => self::CONTRASENA,
            'password_confirmation' => self::CONTRASENA,
            'telefono' => '443-555-0303',
            'fecha_nacimiento' => '1992-03-14',
            'genero' => 'femenino',
            'direccion' => 'Calle del Registro 7',
            'ciudad' => 'Ario',
            'terminos' => '1',
        ], $cambios);
    }
}
