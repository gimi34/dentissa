<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Spec 050, CA5 y CA10 (TM1, TM2): los formularios proponen fechas del día de la clínica y el servidor las
 * valida con ese mismo día. Reloj: 03:50 UTC del 18/11/2026, las 21:50 del martes 17 en la clínica.
 */
class HoraClinicaFormulariosTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const ZONA = 'America/Mexico_City';

    private const CITA_ANTERIOR = 'La fecha de la cita no puede ser anterior a hoy.';

    private const VISITA_POSTERIOR = 'La próxima visita debe ser posterior a hoy.';

    private const NACIMIENTO_ANTERIOR = 'La fecha de nacimiento debe ser anterior a hoy.';

    private const VISITA_FORMATO = 'La próxima visita debe tener el formato AAAA-MM-DD.';

    private const NACIMIENTO_FORMATO = 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.';

    private const GOOGLE = ['google_id' => 'google-050', 'nombre' => 'Persona', 'apellido' => 'De Pruebas', 'email' => 'google-050@example.test', 'foto_url' => null];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // el límite de intentos del registro
        config(['clinica.zona_horaria' => self::ZONA]);
        $this->travelTo(Carbon::parse('2026-11-18 03:50:00', 'UTC'));
        $this->crearEscenario();
    }

    private function relojEnLaClinica(string $momento): void
    {
        $this->travelTo(Carbon::parse($momento, self::ZONA));
    }

    /** Atributo del campo del formulario con ese `name`. */
    private function atributo(string $html, string $campo, string $atributo): ?string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $nodo = (new DOMXPath($dom))->query("//input[@name='{$campo}']")->item(0);
        $this->assertNotNull($nodo, "El formulario tiene el campo {$campo}.");

        return $nodo->hasAttribute($atributo) ? $nodo->getAttribute($atributo) : null;
    }

    private function expediente(?string $proximaVisita): string
    {
        $id = (string) Str::uuid();
        DB::table('expedientes')->insert([
            'id' => $id, 'paciente_id' => $this->p1, 'dentista_id' => $this->d1,
            'diagnostico' => 'Diagnóstico de prueba', 'proxima_visita' => $proximaVisita, 'creado_en' => now(),
        ]);

        return $id;
    }

    private function servicio(int $duracion = 15): string
    {
        $id = (string) Str::uuid();
        DB::table('servicios')->insert(['id' => $id, 'nombre' => 'Servicio de prueba', 'duracion_min' => $duracion, 'activo' => 1]);

        return $id;
    }

    private function paraLaFicha(string $expediente, ?string $proximaVisita, string $diagnostico = 'Diagnóstico de prueba'): TestResponse
    {
        return $this->actingAs($this->dentista1)->from("/dentista/expedientes/{$expediente}")
            ->put("/dentista/expedientes/{$expediente}", ['diagnostico' => $diagnostico, 'proxima_visita' => $proximaVisita]);
    }

    private function pacienteNuevo(string $nacimiento): array
    {
        return ['nombre' => 'Nuevo', 'apellido' => 'Paciente', 'email' => 'nuevo.'.Str::random(8).'@example.test', 'fecha_nacimiento' => $nacimiento];
    }

    private function perfil(string $nacimiento): array
    {
        return ['nombre' => $this->paciente1->nombre, 'apellido' => $this->paciente1->apellido, 'email' => $this->paciente1->email, 'fecha_nacimiento' => $nacimiento];
    }

    /** El registro sin la casilla: no crea la cuenta, pero valida la fecha de nacimiento. */
    private function registro(string $nacimiento): array
    {
        return ['nombre' => 'Persona', 'apellido' => 'De Pruebas', 'email' => 'registro.'.Str::random(8).'@example.test',
            'password' => 'secreto-de-pruebas', 'password_confirmation' => 'secreto-de-pruebas', 'fecha_nacimiento' => $nacimiento];
    }

    // ── CA5: lo que proponen los formularios ──────────────────────────────

    public function test_los_formularios_proponen_fechas_del_dia_de_la_clinica(): void
    {
        $nueva = $this->actingAs($this->admin)->get('/admin/citas/crear')->assertOk()->getContent();
        $this->assertSame('2026-11-17', $this->atributo($nueva, 'fecha', 'min'), '«Nueva cita» del personal: min.');
        $this->assertSame('2026-11-18', $this->atributo($nueva, 'fecha', 'value'), '«Nueva cita» del personal: fecha sugerida.');

        $agendar = $this->actingAs($this->paciente1)->get('/cliente/agendar')->assertOk()->getContent();
        $this->assertSame('2026-11-17', $this->atributo($agendar, 'fecha', 'min'), '«Agendar cita» del paciente: min.');
        $this->assertSame('2026-11-18', $this->atributo($agendar, 'fecha', 'value'), '«Agendar cita» del paciente: fecha sugerida.');

        // La fecha sugerida desde la próxima visita del expediente (/analyze A12).
        $expediente = $this->expediente('2026-11-18');
        $desdeVisita = $this->actingAs($this->admin)->get("/admin/citas/crear?paciente_id={$this->p1}")->assertOk()->getContent();
        $this->assertSame('2026-11-18', $this->atributo($desdeVisita, 'fecha', 'value'), 'Próxima visita del 18: se propone el 18.');
        DB::table('expedientes')->where('id', $expediente)->update(['proxima_visita' => '2026-11-20']);
        $desdeVisita = $this->actingAs($this->admin)->get("/admin/citas/crear?paciente_id={$this->p1}")->assertOk()->getContent();
        $this->assertSame('2026-11-20', $this->atributo($desdeVisita, 'fecha', 'value'), 'Próxima visita del 20: se propone el 20.');
        DB::table('expedientes')->where('id', $expediente)->update(['proxima_visita' => '2026-11-17']);
        $desdeVisita = $this->actingAs($this->admin)->get("/admin/citas/crear?paciente_id={$this->p1}")->assertOk()->getContent();
        $this->assertSame('2026-11-18', $this->atributo($desdeVisita, 'fecha', 'value'), 'Próxima visita de hoy: no se propone; mañana.');

        // Próxima visita: mañana como mínimo, de noche y de día (/analyze A11).
        foreach (['2026-11-17 21:50:00', '2026-11-17 10:00:00'] as $momento) {
            $this->relojEnLaClinica($momento);
            $alta = $this->actingAs($this->dentista1)->get("/dentista/expedientes/crear?paciente_id={$this->p1}")->assertOk()->getContent();
            $this->assertSame('2026-11-18', $this->atributo($alta, 'proxima_visita', 'min'), "Alta del expediente ({$momento}): min.");
            $ficha = $this->actingAs($this->dentista1)->get("/dentista/expedientes/{$expediente}")->assertOk()->getContent();
            $this->assertSame('2026-11-18', $this->atributo($ficha, 'proxima_visita', 'min'), "Ficha del expediente ({$momento}): min.");
        }
        $this->relojEnLaClinica('2026-11-17 21:50:00');

        // Nacimiento: como mucho hace un año, desde el día de la clínica (/analyze A7).
        $paginas = [
            'alta de paciente' => $this->actingAs($this->admin)->get('/admin/pacientes/crear')->assertOk()->getContent(),
            'perfil' => $this->actingAs($this->paciente1)->get('/cliente/perfil')->assertOk()->getContent(),
        ];
        auth()->logout();
        $paginas['registro'] = $this->get('/registro')->assertOk()->getContent();
        $paginas['registro con Google'] = $this->withSession(['google_registro' => self::GOOGLE])->get('/registro/contacto')->assertOk()->getContent();
        foreach ($paginas as $pagina => $html) {
            $this->assertSame('2025-11-17', $this->atributo($html, 'fecha_nacimiento', 'max'), "Nacimiento en {$pagina}: max.");
        }

        // El año del pie de las páginas públicas.
        $this->assertStringContainsString('© 2026 Dentissa', $this->get('/')->assertOk()->getContent());
        $this->relojEnLaClinica('2026-12-31 20:00:00');
        $this->assertStringContainsString('© 2026 Dentissa', $this->get('/')->assertOk()->getContent(), 'El 31 de diciembre a las 20:00 aún es 2026.');

        // El consejo del día del panel del paciente: el del martes 17 (en UTC ya es miércoles).
        $this->relojEnLaClinica('2026-11-17 21:50:00');
        $this->actingAs($this->paciente1)->get('/cliente')->assertOk()
            ->assertSee('Cambiar tu cepillo de dientes cada 3 meses mantiene una higiene óptima.');
    }

    // ── CA5 y CA10: lo que acepta y rechaza el servidor ───────────────────

    public function test_el_servidor_acepta_las_fechas_validas_segun_el_dia_de_la_clinica(): void
    {
        // Cita del paciente el 17 a las 23:30, con un horario de prueba que la cubre.
        DB::table('horarios_dentista')->insert([
            'id' => (string) Str::uuid(), 'personal_id' => $this->d1, 'dia_semana' => 2,
            'hora_inicio' => '09:00:00', 'hora_fin' => '23:59:00', 'disponible' => 1,
        ]);
        $this->actingAs($this->paciente1)->post('/cliente/agendar', [
            'dentista_id' => $this->d1, 'servicio_id' => $this->servicio(), 'fecha' => '2026-11-17', 'hora' => '23:30',
        ])->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
        $this->assertDatabaseHas('citas', ['paciente_id' => $this->p1, 'fecha_hora' => '2026-11-17 23:30:00']);

        // Próxima visita el 18, por el alta y por la ficha.
        $this->actingAs($this->dentista1)->post('/dentista/expedientes', [
            'paciente_id' => $this->p1, 'dentista_id' => $this->d1, 'cita_id' => '', 'diagnostico' => 'Diagnóstico aceptado', 'proxima_visita' => '2026-11-18',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('expedientes', ['paciente_id' => $this->p1, 'diagnostico' => 'Diagnóstico aceptado', 'proxima_visita' => '2026-11-18']);
        $this->paraLaFicha($this->expediente(null), '2026-11-18')->assertSessionDoesntHaveErrors('proxima_visita');

        // Una próxima visita ya guardada y vencida que llega sin cambios (/analyze A21).
        $vencida = $this->expediente('2026-11-10');
        $this->paraLaFicha($vencida, '2026-11-10', 'Diagnóstico corregido')->assertSessionDoesntHaveErrors('proxima_visita');
        $guardado = DB::table('expedientes')->where('id', $vencida)->first();
        $this->assertSame('Diagnóstico corregido', $guardado->diagnostico, 'La ficha se guarda con la fecha vencida sin cambios.');
        $this->assertSame('2026-11-10', substr((string) $guardado->proxima_visita, 0, 10));

        // Nacimiento el 16, en el alta y la edición de paciente, el perfil y los dos registros.
        $this->actingAs($this->admin)->post('/admin/pacientes', $this->pacienteNuevo('2026-11-16'))->assertSessionDoesntHaveErrors('fecha_nacimiento');
        $this->actingAs($this->admin)->put("/admin/pacientes/{$this->p1}", $this->perfil('2026-11-16'))->assertSessionDoesntHaveErrors('fecha_nacimiento');
        $this->actingAs($this->paciente1)->put('/cliente/perfil', $this->perfil('2026-11-16'))->assertSessionDoesntHaveErrors('fecha_nacimiento');
        auth()->logout();
        $this->post('/registro', $this->registro('2026-11-16'))->assertSessionDoesntHaveErrors('fecha_nacimiento');
        $this->withSession(['google_registro' => self::GOOGLE])->post('/registro/contacto', ['fecha_nacimiento' => '2026-11-16'])
            ->assertSessionDoesntHaveErrors('fecha_nacimiento');
    }

    public function test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica(): void
    {
        $this->actingAs($this->paciente1)->post('/cliente/agendar', [
            'dentista_id' => $this->d1, 'servicio_id' => $this->servicio(), 'fecha' => '2026-11-16', 'hora' => '10:00',
        ])->assertSessionHasErrors(['fecha' => self::CITA_ANTERIOR]);

        // Próxima visita el 17: por el alta, por la ficha y cambiando una ya guardada (/analyze A4, A21).
        $this->actingAs($this->dentista1)->post('/dentista/expedientes', [
            'paciente_id' => $this->p1, 'dentista_id' => $this->d1, 'diagnostico' => 'Diagnóstico', 'proxima_visita' => '2026-11-17',
        ])->assertSessionHasErrors(['proxima_visita' => self::VISITA_POSTERIOR]);
        $this->paraLaFicha($this->expediente(null), '2026-11-17')->assertSessionHasErrors(['proxima_visita' => self::VISITA_POSTERIOR]);
        $vencida = $this->expediente('2026-11-10');
        $this->paraLaFicha($vencida, '2026-11-12')->assertSessionHasErrors(['proxima_visita' => self::VISITA_POSTERIOR]);
        $this->assertSame('2026-11-10', substr((string) DB::table('expedientes')->where('id', $vencida)->value('proxima_visita'), 0, 10));

        // Nacimiento el 17 (/analyze A5).
        $this->actingAs($this->admin)->post('/admin/pacientes', $this->pacienteNuevo('2026-11-17'))
            ->assertSessionHasErrors(['fecha_nacimiento' => self::NACIMIENTO_ANTERIOR]);
        $this->actingAs($this->admin)->put("/admin/pacientes/{$this->p1}", $this->perfil('2026-11-17'))
            ->assertSessionHasErrors(['fecha_nacimiento' => self::NACIMIENTO_ANTERIOR]);
        $this->actingAs($this->paciente1)->put('/cliente/perfil', $this->perfil('2026-11-17'))->assertSessionHasErrors('fecha_nacimiento');
        auth()->logout();
        $this->post('/registro', $this->registro('2026-11-17'))->assertSessionHasErrors('fecha_nacimiento');
        $this->withSession(['google_registro' => self::GOOGLE])->post('/registro/contacto', ['fecha_nacimiento' => '2026-11-17'])
            ->assertSessionHasErrors('fecha_nacimiento');
    }

    public function test_el_servidor_rechaza_fechas_con_hora_o_zona(): void
    {
        // Una próxima visita de hoy con hora no entra por el alta ni por la ficha (/review R1).
        foreach (['2026-11-17 10:00', '2026-11-17T23:59:59'] as $valor) {
            $this->actingAs($this->dentista1)->post('/dentista/expedientes', [
                'paciente_id' => $this->p1, 'dentista_id' => $this->d1, 'cita_id' => '', 'diagnostico' => 'Diagnóstico', 'proxima_visita' => $valor,
            ])->assertSessionHasErrors(['proxima_visita' => self::VISITA_FORMATO]);
            $this->paraLaFicha($this->expediente(null), $valor)->assertSessionHasErrors(['proxima_visita' => self::VISITA_FORMATO]);
        }

        // El nacimiento tampoco admite zona ni expresiones relativas (/review R2).
        foreach (['2026-11-17T00:00:00+14:00', '2026-11-20 -10 days'] as $valor) {
            $error = ['fecha_nacimiento' => self::NACIMIENTO_FORMATO];
            $this->actingAs($this->admin)->post('/admin/pacientes', $this->pacienteNuevo($valor))->assertSessionHasErrors($error);
            $this->actingAs($this->admin)->put("/admin/pacientes/{$this->p1}", $this->perfil($valor))->assertSessionHasErrors($error);
            $this->actingAs($this->paciente1)->put('/cliente/perfil', $this->perfil($valor))->assertSessionHasErrors($error);
            auth()->logout();
            $this->post('/registro', $this->registro($valor))->assertSessionHasErrors($error);
            $this->withSession(['google_registro' => self::GOOGLE])->post('/registro/contacto', ['fecha_nacimiento' => $valor])
                ->assertSessionHasErrors($error);
        }
    }

    // ── TM2: la fecha sugerida de «Agendar cita» ──────────────────────────

    public function test_agendar_ignora_una_fecha_manipulada(): void
    {
        foreach (['fecha[]=x', 'fecha=ayer', 'fecha=2026-02-30'] as $consulta) {
            $html = $this->actingAs($this->paciente1)->get("/cliente/agendar?{$consulta}")->assertOk()->getContent();
            $this->assertSame('2026-11-18', $this->atributo($html, 'fecha', 'value'), "?{$consulta}: la fecha sugerida de siempre.");
        }

        $html = $this->actingAs($this->paciente1)->get('/cliente/agendar?fecha=2026-11-20')->assertOk()->getContent();
        $this->assertSame('2026-11-20', $this->atributo($html, 'fecha', 'value'), 'Una fecha válida se sigue proponiendo.');
    }

    // ── CA5: el aviso de la próxima visita ────────────────────────────────

    public function test_el_aviso_de_proxima_visita_cuenta_los_dias_de_la_clinica(): void
    {
        $aviso = fn (string $fecha) => $this->actingAs($this->dentista1)->get('/dentista/expedientes/'.$this->expediente($fecha))->assertOk();

        $aviso('2026-11-17')->assertDontSee('Fecha vencida');
        $aviso('2026-11-16')->assertSee('Fecha vencida');
        $aviso('2026-11-24')->assertSee('Próxima semana')->assertDontSee('Fecha vencida');
        $aviso('2026-11-25')->assertDontSee('Próxima semana')->assertDontSee('Fecha vencida');
    }
}
