<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 050, CA7: las fechas que guarda el sistema en UTC (`creado_en`, el historial de la cita, el
 * consentimiento de la galería) se muestran en la hora de la clínica; las horas de una cita no se convierten.
 * Momento de alta: 03:50 UTC del 18/11/2026, las 21:50 del 17 en la clínica.
 */
class HoraClinicaFechasSistemaTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const ZONA = 'America/Mexico_City';

    private const ALTA_UTC = '2026-11-18 03:50:00';

    protected function setUp(): void
    {
        parent::setUp();
        config(['clinica.zona_horaria' => self::ZONA]);
        $this->travelTo(Carbon::parse('2026-11-18 05:00:00', 'UTC'));
        $this->crearEscenario();
    }

    public function test_agendada_el_y_el_historial_se_muestran_en_hora_de_la_clinica(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-20 10:00');
        DB::table('citas')->where('id', $cita)->update(['creado_en' => self::ALTA_UTC]);
        DB::table('historial_citas')->insert([
            'id' => (string) Str::uuid(), 'cita_id' => $cita, 'estado_anterior' => 'agendada', 'estado_nuevo' => 'confirmada',
            'cambiado_por' => $this->admin->id, 'fecha' => '2026-11-18 04:10:00',
        ]);

        $personal = $this->actingAs($this->admin)->get("/admin/citas/{$cita}")->assertOk()->getContent();
        $this->assertStringContainsString('17/11/2026 21:50', $personal, 'Detalle del personal: agendada en hora de la clínica.');
        $this->assertStringNotContainsString('18/11/2026 03:50', $personal);
        $historial = $this->seccion($personal, 'historial');
        $this->assertNotNull($historial, 'El detalle tiene el historial.');
        $this->assertStringContainsString('17/11 22:10', $historial, 'Historial en hora de la clínica.');

        $paciente = $this->actingAs($this->paciente1)->get("/cliente/citas/{$cita}")->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/Agendada el\s+17\/11\/2026 21:50/', $paciente, 'Detalle del paciente: agendada en hora de la clínica.');
    }

    public function test_las_fechas_de_alta_se_muestran_en_hora_de_la_clinica(): void
    {
        DB::table('pacientes')->where('id', $this->p1)->update(['creado_en' => self::ALTA_UTC]);
        DB::table('usuarios')->where('id', $this->paciente1->id)->update(['creado_en' => self::ALTA_UTC]);
        $expediente = (string) Str::uuid();
        DB::table('expedientes')->insert([
            'id' => $expediente, 'paciente_id' => $this->p1, 'dentista_id' => $this->d1,
            'diagnostico' => 'Diagnóstico de prueba', 'creado_en' => self::ALTA_UTC,
        ]);

        // Ficha del expediente: el encabezado y la fecha de alta (/analyze A17).
        $ficha = $this->actingAs($this->admin)->get("/admin/expedientes/{$expediente}")->assertOk()->getContent();
        $this->assertStringContainsString('martes 17 de noviembre de 2026', $ficha, 'Ficha del expediente: encabezado.');
        $this->assertStringContainsString('17/11/2026 21:50', $ficha, 'Ficha del expediente: fecha de alta.');

        // Lista de expedientes, lista y ficha de pacientes, ficha del usuario.
        $this->assertFilaConFecha($this->actingAs($this->admin)->get('/admin/expedientes')->assertOk()->getContent(), 'Diagnóstico de prueba', '17/11/2026', 'Lista de expedientes');
        $this->assertFilaConFecha($this->actingAs($this->admin)->get('/admin/pacientes?buscar=Pablo')->assertOk()->getContent(), 'Pablo Paciente', '17/11/2026', 'Lista de pacientes');
        $this->assertMatchesRegularExpression('/17 nov\.? 2026/u', $this->actingAs($this->admin)->get("/admin/pacientes/{$this->p1}")->assertOk()->getContent(), 'Ficha del paciente: alta del expediente.');
        $usuario = $this->actingAs($this->admin)->get("/admin/usuarios/{$this->paciente1->id}")->assertOk()->getContent();
        $this->assertStringContainsString('17/11/2026', $usuario, 'Ficha del usuario: fecha de alta.');
        $this->assertStringNotContainsString('18/11/2026', $usuario);

        // Consentimiento de una foto de la galería.
        DB::table('galeria_fotos')->insert([
            'id' => (string) Str::uuid(), 'descripcion' => 'Foto de prueba 050', 'posicion' => 900, 'con_paciente' => 1,
            'consentimiento_usuario_id' => $this->paciente1->id, 'consentimiento_nombre' => 'Pablo Paciente',
            'consentimiento_en' => self::ALTA_UTC, 'ancho' => 1200, 'alto' => 900, 'bytes' => 1000, 'bytes_pequena' => 500,
            'creado_en' => self::ALTA_UTC, 'actualizado_en' => self::ALTA_UTC,
        ]);
        $this->actingAs($this->admin)->get('/admin/galeria')->assertOk()
            ->assertSee('Consentimiento declarado por Pablo Paciente el 17 nov 2026');
    }

    public function test_las_fechas_de_reprogramacion_no_se_convierten(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'agendada', '2026-11-20 10:00');
        DB::table('historial_citas')->insert([
            'id' => (string) Str::uuid(), 'cita_id' => $cita, 'estado_anterior' => 'agendada', 'estado_nuevo' => 'agendada',
            'cambiado_por' => $this->admin->id, 'fecha' => '2026-11-18 04:10:00',
            'fecha_hora_anterior' => '2026-11-19 10:00:00', 'fecha_hora_nueva' => '2026-11-20 10:00:00',
        ]);

        $historial = $this->seccion($this->actingAs($this->admin)->get("/admin/citas/{$cita}")->assertOk()->getContent(), 'historial');

        $this->assertNotNull($historial);
        $this->assertMatchesRegularExpression('/Reprogramada del 19\/11\/2026 10:00 al 20\/11\/2026 10:00/', preg_replace('/\s+/', ' ', $historial));
    }

    /** La fila de la tabla que contiene ese texto lleva esa fecha. */
    private function assertFilaConFecha(string $html, string $texto, string $fecha, string $donde): void
    {
        $this->assertSame(1, preg_match('/<tr[^>]*>(?:(?!<\/tr>).)*'.preg_quote(e($texto), '/').'(?:(?!<\/tr>).)*<\/tr>/su', $html, $fila), "{$donde}: hay una fila con «{$texto}».");
        $this->assertStringContainsString($fecha, $fila[0], "{$donde}: la fila lleva la fecha de la clínica.");
    }
}
