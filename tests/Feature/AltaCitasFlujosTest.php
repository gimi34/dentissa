<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Diferencias entre el alta del personal y la del paciente que la spec 025
 * mantiene (Fuera de alcance): duración, servicio, fechas pasadas y columnas
 * guardadas. Protegen que mover el insert a la regla única no las cambie.
 */
class AltaCitasFlujosTest extends TestCase
{
    use DatabaseTransactions;

    private string $dentista;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = $this->crearUsuario('dentista');
        $this->dentista = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $this->dentista, 'usuario_id' => $usuario->id, 'tipo' => 'dentista']);
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(), 'personal_id' => $this->dentista, 'dia_semana' => 1,
            'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00', 'disponible' => 1,
        ]);
    }

    public function test_la_cita_del_personal_guarda_duracion_notas_y_creador(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearPaciente($this->crearUsuario('cliente'));
        $lunes = $this->lunes(2);

        $this->actingAs($admin)->post('/admin/citas', [
            'paciente_id' => $paciente,
            'dentista_id' => $this->dentista,
            'servicio_id' => '',
            'fecha' => $lunes,
            'hora' => '10:00',
            'duracion_min' => 45,
            'notas_paciente' => 'Nota del paciente',
            'notas_internas' => 'Nota interna',
        ])->assertSessionHasNoErrors();

        $cita = DB::table('citas')->where('paciente_id', $paciente)->first();
        $this->assertNotNull($cita);
        $this->assertSame(45, (int) $cita->duracion_min);
        $this->assertNull($cita->servicio_id);
        $this->assertSame('Nota del paciente', $cita->notas_paciente);
        $this->assertSame('Nota interna', $cita->notas_internas);
        $this->assertSame($admin->id, $cita->creado_por);
        $this->assertSame('agendada', $cita->estado);
        $this->assertSame($lunes.' 10:00:00', $cita->fecha_hora);
    }

    public function test_la_cita_del_paciente_toma_la_duracion_del_servicio(): void
    {
        $cliente = $this->crearUsuario('cliente');
        $paciente = $this->crearPaciente($cliente);
        $servicio = $this->crearServicio(60);

        $this->actingAs($cliente)->post('/cliente/agendar', [
            'dentista_id' => $this->dentista,
            'servicio_id' => $servicio,
            'fecha' => $this->lunes(2),
            'hora' => '10:00',
            'notas_paciente' => 'Me duele una muela',
        ])->assertSessionHasNoErrors();

        $cita = DB::table('citas')->where('paciente_id', $paciente)->first();
        $this->assertNotNull($cita);
        $this->assertSame(60, (int) $cita->duracion_min);
        $this->assertSame($servicio, $cita->servicio_id);
        $this->assertSame('Me duele una muela', $cita->notas_paciente);
        $this->assertSame($cliente->id, $cita->creado_por);
    }

    public function test_el_paciente_debe_elegir_servicio(): void
    {
        $cliente = $this->crearUsuario('cliente');
        $paciente = $this->crearPaciente($cliente);

        $this->actingAs($cliente)->post('/cliente/agendar', [
            'dentista_id' => $this->dentista,
            'fecha' => $this->lunes(2),
            'hora' => '10:00',
        ])->assertSessionHasErrors(['servicio_id' => 'Elige un servicio.']);

        $this->assertSame(0, DB::table('citas')->where('paciente_id', $paciente)->count());
    }

    public function test_solo_el_personal_puede_agendar_en_fecha_pasada(): void
    {
        $lunesPasado = $this->lunes(-2);
        $cliente = $this->crearUsuario('cliente');
        $paciente = $this->crearPaciente($cliente);

        $this->actingAs($cliente)->post('/cliente/agendar', [
            'dentista_id' => $this->dentista,
            'servicio_id' => $this->crearServicio(30),
            'fecha' => $lunesPasado,
            'hora' => '10:00',
        ])->assertSessionHasErrors('fecha');
        $this->assertSame(0, DB::table('citas')->where('paciente_id', $paciente)->count());

        $this->flushSession();
        $this->actingAs($this->crearUsuario('admin'))->post('/admin/citas', [
            'paciente_id' => $paciente,
            'dentista_id' => $this->dentista,
            'servicio_id' => '',
            'fecha' => $lunesPasado,
            'hora' => '10:00',
            'duracion_min' => 30,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('citas')->where('paciente_id', $paciente)->count());
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    /** Un lunes a `$semanas` semanas de hoy (negativo: en el pasado). */
    private function lunes(int $semanas): string
    {
        return Carbon::now()->addWeeks($semanas)->startOfWeek()->format('Y-m-d');
    }

    private function crearPaciente(Usuario $usuario): string
    {
        $id = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $id, 'usuario_id' => $usuario->id]);

        return $id;
    }

    private function crearServicio(int $duracion): string
    {
        $id = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $id, 'nombre' => 'Servicio de prueba', 'duracion_min' => $duracion, 'activo' => 1]);

        return $id;
    }

    private function crearUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'flujos.'.Str::random(10).'@example.test',
            'rol' => $rol,
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }
}
