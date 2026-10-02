<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Autorización del alta de citas por rol (spec 025, P3): caso permitido y
 * denegado de cada rol. La 025 no cambia permisos; estos tests protegen que la
 * unificación de la regla no los altere.
 *
 * Los pacientes están dentro del alcance del dentista que agenda, para que el
 * 403 dependa solo de `autorizadoParaCita` y no del alcance de paciente de la 014.
 */
class AltaCitasRolesTest extends TestCase
{
    use DatabaseTransactions;

    private string $lunes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lunes = Carbon::now()->addWeeks(2)->startOfWeek()->format('Y-m-d');
    }

    public function test_el_admin_puede_agendar(): void
    {
        [, $dentista] = $this->crearDentista();
        $paciente = $this->crearPaciente($dentista);

        $this->agendarComoPersonal($this->crearUsuario('admin'), 'admin', $dentista, $paciente)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, $this->citasDe($dentista));
    }

    public function test_un_dentista_puede_agendar_para_si_mismo(): void
    {
        [$usuario, $dentista] = $this->crearDentista();
        $paciente = $this->crearPaciente($dentista);

        $this->agendarComoPersonal($usuario, 'dentista', $dentista, $paciente)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, $this->citasDe($dentista));
    }

    public function test_un_dentista_no_puede_agendar_para_otro_dentista(): void
    {
        [$usuario, $dentista] = $this->crearDentista();
        [, $otroDentista] = $this->crearDentista();
        $paciente = $this->crearPaciente($dentista);

        $this->agendarComoPersonal($usuario, 'dentista', $otroDentista, $paciente)->assertStatus(403);

        $this->assertSame(0, $this->citasDe($otroDentista));
    }

    public function test_un_asistente_puede_agendar_para_un_dentista_asignado(): void
    {
        [, $dentista] = $this->crearDentista();
        $asistente = $this->crearAsistente($dentista);
        $paciente = $this->crearPaciente($dentista);

        $this->agendarComoPersonal($asistente, 'asistente', $dentista, $paciente)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, $this->citasDe($dentista));
    }

    public function test_un_asistente_no_puede_agendar_para_un_dentista_no_asignado(): void
    {
        [, $dentista] = $this->crearDentista();
        [, $noAsignado] = $this->crearDentista();
        $asistente = $this->crearAsistente($dentista);
        $paciente = $this->crearPaciente($dentista);

        $this->agendarComoPersonal($asistente, 'asistente', $noAsignado, $paciente)->assertStatus(403);

        $this->assertSame(0, $this->citasDe($noAsignado));
    }

    public function test_un_cliente_no_puede_usar_el_alta_del_personal(): void
    {
        [, $dentista] = $this->crearDentista();
        $cliente = $this->crearUsuario('cliente');
        $paciente = $this->crearPaciente($dentista, $cliente);

        $this->agendarComoPersonal($cliente, 'admin', $dentista, $paciente)->assertStatus(403);

        $this->assertSame(0, $this->citasDe($dentista));
    }

    public function test_un_cliente_puede_agendar_su_cita(): void
    {
        [, $dentista] = $this->crearDentista();
        $cliente = $this->crearUsuario('cliente');
        $paciente = $this->crearPaciente($dentista, $cliente);

        $this->agendarComoPaciente($cliente, $dentista)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, DB::table('citas')->where('paciente_id', $paciente)->count());
    }

    public function test_un_cliente_no_puede_agendar_para_otro_paciente(): void
    {
        [, $dentista] = $this->crearDentista();
        $cliente = $this->crearUsuario('cliente');
        $propio = $this->crearPaciente($dentista, $cliente);
        $ajeno = $this->crearPaciente($dentista);

        // Un paciente_id ajeno inyectado se ignora: la cita queda a nombre del propio paciente.
        $this->agendarComoPaciente($cliente, $dentista, ['paciente_id' => $ajeno])->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('citas')->where('paciente_id', $propio)->count());
        $this->assertSame(0, DB::table('citas')->where('paciente_id', $ajeno)->count());
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function agendarComoPersonal(Usuario $usuario, string $prefijo, string $dentista, string $paciente): TestResponse
    {
        return $this->actingAs($usuario)->post("/{$prefijo}/citas", [
            'paciente_id' => $paciente,
            'dentista_id' => $dentista,
            'servicio_id' => '',
            'fecha' => $this->lunes,
            'hora' => '10:00',
            'duracion_min' => 30,
        ]);
    }

    private function agendarComoPaciente(Usuario $cliente, string $dentista, array $extra = []): TestResponse
    {
        $servicio = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $servicio, 'nombre' => 'Limpieza dental', 'duracion_min' => 30, 'activo' => 1]);

        return $this->actingAs($cliente)->post('/cliente/agendar', [
            'dentista_id' => $dentista,
            'servicio_id' => $servicio,
            'fecha' => $this->lunes,
            'hora' => '10:00',
        ] + $extra);
    }

    private function citasDe(string $dentista): int
    {
        return DB::table('citas')->where('dentista_id', $dentista)->count();
    }

    /** @return array{0: Usuario, 1: string} usuario y id de `personal` del dentista */
    private function crearDentista(): array
    {
        $usuario = $this->crearUsuario('dentista');
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => 'dentista']);
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(), 'personal_id' => $id, 'dia_semana' => 1,
            'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00', 'disponible' => 1,
        ]);

        return [$usuario, $id];
    }

    private function crearAsistente(string $dentistaAsignado): Usuario
    {
        $usuario = $this->crearUsuario('asistente');
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => 'asistente']);
        DB::table('asistente_dentista')->insert([
            'id' => (string) Str::uuid(), 'asistente_id' => $id, 'dentista_id' => $dentistaAsignado,
        ]);

        return $usuario;
    }

    private function crearPaciente(string $dentista, ?Usuario $usuario = null): string
    {
        $usuario ??= $this->crearUsuario('cliente');
        $id = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'dentista_id' => $dentista]);

        return $id;
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'roles.'.Str::random(10).'@example.test',
            'rol' => $rol,
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }
}
