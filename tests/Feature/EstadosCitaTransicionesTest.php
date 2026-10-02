<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\EscenarioEstadosCita;
use Tests\TestCase;

/**
 * Transiciones de estado de la cita (spec 026, CA1, CA2, CA3, CA8 y CA11).
 *
 * Solo usa los flujos del personal. La cita de referencia empieza el lunes
 * 2026-10-05 a las 10:00 en hora de la clínica (America/Mexico_City, UTC-6),
 * es decir, a las 16:00 UTC; el reloj del test se fija en UTC.
 */
class EstadosCitaTransicionesTest extends TestCase
{
    use DatabaseTransactions, EscenarioEstadosCita;

    private const ESTADOS = ['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];

    /** Transiciones de CA1: estado nuevo => estados de origen. */
    private const PERMITIDAS = [
        'confirmada' => ['agendada'],
        'en_curso' => ['confirmada'],
        'completada' => ['en_curso'],
        'cancelada' => ['agendada', 'confirmada'],
        'no_asistio' => ['agendada', 'confirmada'],
    ];

    private const INICIO = '2026-10-05 10:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        // 11:00 en la clínica: la cita de las 10:00 ya empezó.
        Carbon::setTestNow(Carbon::parse('2026-10-05 17:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_solo_se_aceptan_las_transiciones_de_la_tabla(): void
    {
        $aceptadas = [];

        foreach (self::ESTADOS as $origen) {
            foreach (self::ESTADOS as $destino) {
                $cita = $this->cita($this->d1, $this->p1, $origen, self::INICIO);

                $respuesta = $this->cambiar($this->admin, $cita, $destino);

                $this->assertNotEquals(500, $respuesta->status(), "{$origen} → {$destino} dio 500");
                if ($this->estadoDe($cita) === $destino && $origen !== $destino) {
                    $aceptadas[] = "{$origen} → {$destino}";
                } else {
                    $this->assertSame($origen, $this->estadoDe($cita), "{$origen} → {$destino} cambió la cita");
                    $this->assertNotSame('', $this->errorDe('estado'), "{$origen} → {$destino} se rechazó sin mensaje");
                }
            }
        }

        $esperadas = [];
        foreach (self::PERMITIDAS as $destino => $origenes) {
            foreach ($origenes as $origen) {
                $esperadas[] = "{$origen} → {$destino}";
            }
        }
        sort($aceptadas);
        sort($esperadas);
        $this->assertSame($esperadas, $aceptadas);
    }

    public function test_una_cita_cerrada_no_cambia_de_estado(): void
    {
        $etiquetas = [
            'completada' => 'completada',
            'cancelada' => 'cancelada',
            'no_asistio' => 'marcada como no asistida',
        ];

        foreach ($etiquetas as $cerrado => $etiqueta) {
            foreach (['confirmada', 'cancelada', 'en_curso'] as $destino) {
                $cita = $this->cita($this->d1, $this->p1, $cerrado, self::INICIO);

                $this->cambiar($this->admin, $cita, $destino)->assertStatus(302);

                $this->assertSame($cerrado, $this->estadoDe($cita));
                $this->assertSame(
                    "Esta cita ya está {$etiqueta} y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva.",
                    $this->errorDe('estado'),
                );
            }
        }
    }

    public function test_una_transicion_no_permitida_indica_desde_que_estados_se_permite(): void
    {
        $agendada = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->admin, $agendada, 'completada')->assertStatus(302);
        $this->assertSame('agendada', $this->estadoDe($agendada));
        $this->assertSame('Solo se puede pasar a «Completada» desde «En curso».', $this->errorDe('estado'));

        $enCurso = $this->cita($this->d1, $this->p1, 'en_curso', self::INICIO);
        $this->cambiar($this->admin, $enCurso, 'cancelada')->assertStatus(302);
        $this->assertSame('en_curso', $this->estadoDe($enCurso));
        $this->assertSame('Solo se puede pasar a «Cancelada» desde «Agendada» o «Confirmada».', $this->errorDe('estado'));
    }

    public function test_en_curso_o_no_asistio_antes_de_la_hora_se_rechaza(): void
    {
        // 09:00 en la clínica: la cita de las 10:00 aún no empieza.
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:00:00', 'UTC'));

        $confirmada = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);
        $this->cambiar($this->dentista1, $confirmada, 'en_curso')->assertStatus(302);
        $this->assertSame('confirmada', $this->estadoDe($confirmada));
        $this->assertSame(
            'La cita aún no empieza. Podrás marcarla como «En curso» a partir de las 10:00 del 05/10/2026.',
            $this->errorDe('estado'),
        );

        $agendada = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);
        $this->cambiar($this->admin, $agendada, 'no_asistio')->assertStatus(302);
        $this->assertSame('agendada', $this->estadoDe($agendada));
        $this->assertSame(
            'La cita aún no empieza. Podrás marcarla como «No asistió» a partir de las 10:00 del 05/10/2026.',
            $this->errorDe('estado'),
        );
    }

    public function test_a_la_hora_exacta_de_inicio_ya_se_puede_iniciar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:00:00', 'UTC'));
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);

        $this->cambiar($this->dentista1, $cita, 'en_curso')->assertStatus(302);

        $this->assertSame('en_curso', $this->estadoDe($cita));
        $this->assertSame('', $this->errorDe('estado'));
    }

    public function test_la_hora_de_inicio_se_compara_en_la_zona_de_la_clinica(): void
    {
        // 15:30 UTC son las 09:30 en la clínica: la cita de las 10:00 aún no empieza,
        // aunque en UTC "10:00" ya pasó.
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:30:00', 'UTC'));
        $temprano = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);
        $this->cambiar($this->dentista1, $temprano, 'en_curso');
        $this->assertSame('confirmada', $this->estadoDe($temprano));

        // 16:00 UTC son las 10:00 en la clínica: ya empezó.
        Carbon::setTestNow(Carbon::parse('2026-10-05 16:00:00', 'UTC'));
        $aTiempo = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);
        $this->cambiar($this->dentista1, $aTiempo, 'en_curso');
        $this->assertSame('en_curso', $this->estadoDe($aTiempo));
    }

    public function test_un_estado_inexistente_se_rechaza_sin_error_500(): void
    {
        foreach (['reabierta', '', 'AGENDADA'] as $estado) {
            $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);

            $this->cambiar($this->admin, $cita, $estado)->assertStatus(302);

            $this->assertSame('agendada', $this->estadoDe($cita));
            $this->assertNotSame('', $this->errorDe('estado'));
        }
    }

    public function test_reabrir_una_cita_cancelada_se_rechaza(): void
    {
        foreach (['agendada', 'confirmada'] as $destino) {
            $cita = $this->cita($this->d1, $this->p1, 'cancelada', self::INICIO);

            $this->cambiar($this->admin, $cita, $destino)->assertStatus(302);

            $this->assertSame('cancelada', $this->estadoDe($cita));
            $this->assertSame(
                'Esta cita ya está cancelada y no puede cambiar de estado. Para volver a atender al paciente, agenda una cita nueva.',
                $this->errorDe('estado'),
            );
        }
    }

    public function test_volver_a_agendada_nunca_se_permite(): void
    {
        $cita = $this->cita($this->d1, $this->p1, 'confirmada', self::INICIO);

        $this->cambiar($this->admin, $cita, 'agendada')->assertStatus(302);

        $this->assertSame('confirmada', $this->estadoDe($cita));
        $this->assertSame(
            'Una cita no puede volver a «Agendada». Si hay que moverla, cancélala y agenda una nueva.',
            $this->errorDe('estado'),
        );
    }

    public function test_las_notas_internas_enviadas_con_el_estado_no_cambian(): void
    {
        foreach ([$this->admin, $this->dentista1, $this->asistente] as $usuario) {
            $cita = $this->cita($this->d1, $this->p1, 'agendada', self::INICIO);

            $this->cambiar($usuario, $cita, 'confirmada', null, ['notas_internas' => 'Sobrescrita'])->assertStatus(302);

            $this->assertSame('confirmada', $this->estadoDe($cita), "{$usuario->rol} no pudo confirmar");
            $this->assertSame(
                'Nota interna original',
                DB::table('citas')->where('id', $cita)->value('notas_internas'),
                "{$usuario->rol} sobrescribió las notas internas",
            );
        }
    }
}
