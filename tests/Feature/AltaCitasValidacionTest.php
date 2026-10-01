<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Validación de entrada del alta de citas (spec 025, CA9 y CA10; TM3 y TM4).
 *
 * Cada caso se ejecuta por los dos flujos (data set `personal` y `paciente`).
 * Se comprueba el texto exacto en español y que el formulario conserva la
 * entrada, y que nunca hay un error del servidor.
 */
class AltaCitasValidacionTest extends TestCase
{
    use DatabaseTransactions;

    private const MENSAJE_HORA = 'La hora debe tener el formato HH:MM.';

    private const MENSAJE_FECHA = 'La fecha debe tener el formato AAAA-MM-DD.';

    private const MENSAJE_DENTISTA = 'Elige un dentista válido.';

    private Usuario $admin;

    private Usuario $usuarioPaciente;

    private string $pacienteId;

    private string $servicioId;

    private string $dentistaValido;

    private string $lunes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->crearUsuario('admin');
        $this->usuarioPaciente = $this->crearUsuario('cliente');
        $this->pacienteId = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $this->pacienteId, 'usuario_id' => $this->usuarioPaciente->id]);
        $this->servicioId = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $this->servicioId, 'nombre' => 'Limpieza dental', 'duracion_min' => 30, 'activo' => 1]);
        $this->dentistaValido = $this->crearPersonal('dentista');
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(), 'personal_id' => $this->dentistaValido, 'dia_semana' => 1,
            'hora_inicio' => '09:00:00', 'hora_fin' => '13:00:00', 'disponible' => 1,
        ]);
        $this->lunes = Carbon::now()->addWeeks(2)->startOfWeek()->format('Y-m-d');
    }

    public static function flujos(): array
    {
        return ['personal' => ['personal'], 'paciente' => ['paciente']];
    }

    // ── CA9 ────────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_una_hora_mal_formada_se_rechaza_sin_error_500(string $flujo): void
    {
        foreach (['25:99', 'abc'] as $hora) {
            $respuesta = $this->agendar($flujo, ['hora' => $hora]);

            $this->assertNotSame(500, $respuesta->status(), "La hora «{$hora}» no debe dar un error del servidor.");
            $respuesta->assertSessionHasErrors(['hora' => self::MENSAJE_HORA])
                ->assertSessionHasInput('hora', $hora)
                ->assertSessionHasInput('fecha', $this->lunes)
                ->assertSessionHasInput('dentista_id', $this->dentistaValido);
        }

        $this->assertSame(0, $this->citasNuevas());
    }

    #[DataProvider('flujos')]
    public function test_una_fecha_mal_formada_se_rechaza_sin_error_500(string $flujo): void
    {
        foreach (['2026-10-05 10:00', 'abc'] as $fecha) {
            $respuesta = $this->agendar($flujo, ['fecha' => $fecha]);

            $this->assertNotSame(500, $respuesta->status(), "La fecha «{$fecha}» no debe dar un error del servidor.");
            $respuesta->assertSessionHasErrors(['fecha' => self::MENSAJE_FECHA])
                ->assertSessionHasInput('fecha', $fecha)
                ->assertSessionHasInput('hora', '10:00')
                ->assertSessionHasInput('dentista_id', $this->dentistaValido);
        }

        $this->assertSame(0, $this->citasNuevas());
    }

    // ── CA10 ───────────────────────────────────────────────────────────

    #[DataProvider('flujos')]
    public function test_un_dentista_de_otro_tipo_inexistente_o_desactivado_se_rechaza(string $flujo): void
    {
        $invalidos = [
            'asistente' => $this->crearPersonal('asistente'),
            'administrador' => $this->crearPersonal('administrador'),
            'dentista desactivado' => $this->crearPersonal('dentista', activo: 0),
            'inexistente' => (string) Str::uuid(),
        ];

        foreach ($invalidos as $caso => $id) {
            $respuesta = $this->agendar($flujo, ['dentista_id' => $id]);

            $this->assertNotSame(500, $respuesta->status(), "Caso {$caso}: no debe dar un error del servidor.");
            $respuesta->assertSessionHasErrors(['dentista_id' => self::MENSAJE_DENTISTA])
                ->assertSessionHasInput('dentista_id', $id)
                ->assertSessionHasInput('fecha', $this->lunes)
                ->assertSessionHasInput('hora', '10:00');
        }

        $this->assertSame(0, $this->citasNuevas());
    }

    // ── R2 y R3: identificadores como array y servicio ausente ─────────

    #[DataProvider('flujos')]
    public function test_los_identificadores_enviados_como_array_se_rechazan_sin_error_500(string $flujo): void
    {
        $campos = ['dentista_id' => $this->dentistaValido, 'servicio_id' => $this->servicioId];
        if ($flujo === 'personal') {
            $campos['paciente_id'] = $this->pacienteId;
        }

        foreach ($campos as $campo => $valor) {
            $respuesta = $this->agendar($flujo, [$campo => [$valor]]);

            $this->assertNotSame(500, $respuesta->status(), "{$campo} como array no debe dar un error del servidor.");
            $respuesta->assertSessionHasErrors($campo);
        }

        $this->agendar($flujo, ['dentista_id' => [$this->dentistaValido]])
            ->assertSessionHasErrors(['dentista_id' => self::MENSAJE_DENTISTA]);

        $this->assertSame(0, $this->citasNuevas());
    }

    public function test_el_personal_puede_agendar_sin_enviar_servicio(): void
    {
        $this->flushSession();
        $respuesta = $this->actingAs($this->admin)->post('/admin/citas', [
            'paciente_id' => $this->pacienteId,
            'dentista_id' => $this->dentistaValido,
            'fecha' => $this->lunes,
            'hora' => '10:00',
            'duracion_min' => 30,
        ]);

        $this->assertNotSame(500, $respuesta->status(), 'Sin servicio_id no debe dar un error del servidor.');
        $respuesta->assertSessionHasNoErrors();
        $this->assertNull(DB::table('citas')->where('paciente_id', $this->pacienteId)->value('servicio_id'));
        $this->assertSame(1, $this->citasNuevas());
    }

    // ── Apoyo ──────────────────────────────────────────────────────────

    private function agendar(string $flujo, array $cambios): TestResponse
    {
        $this->flushSession();
        $datos = array_merge([
            'dentista_id' => $this->dentistaValido,
            'servicio_id' => $this->servicioId,
            'fecha' => $this->lunes,
            'hora' => '10:00',
        ], $cambios);

        if ($flujo === 'personal') {
            return $this->actingAs($this->admin)->post('/admin/citas', $datos + [
                'paciente_id' => $this->pacienteId,
                'duracion_min' => 30,
            ]);
        }

        return $this->actingAs($this->usuarioPaciente)->post('/cliente/agendar', $datos);
    }

    private function citasNuevas(): int
    {
        return DB::table('citas')->where('paciente_id', $this->pacienteId)->count();
    }

    private function crearPersonal(string $tipo, int $activo = 1): string
    {
        $rol = ['dentista' => 'dentista', 'asistente' => 'asistente', 'administrador' => 'admin'][$tipo];
        $usuario = $this->crearUsuario($rol, $activo);
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => $tipo]);

        return $id;
    }

    private function crearUsuario(string $rol, int $activo = 1): Usuario
    {
        return Usuario::create([
            'nombre' => 'Persona',
            'apellido' => 'De Pruebas',
            'email' => 'validacion.'.Str::random(10).'@example.test',
            'rol' => $rol,
            'activo' => $activo,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }
}
