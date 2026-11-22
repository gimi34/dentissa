<?php

namespace Tests\Feature\Concerns;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Escenario de la spec 014 (alcance clínico), sobre el de la 026.
 *
 * De `EscenarioEstadosCita`: admin, dentistas D1 y D2, asistente A1 (solo de D1), P1 (de D1) y P2 (de D2).
 * Añade: P3 (de D2, con una cita confirmada con D1), P4 (de D2, con una cita cancelada con D1) y P5 (sin dentista ni
 * citas); y los expedientes E1 (P1, firmado por D1), E2 (P1, firmado por D2) y E3 (P2, firmado por D2).
 * Alcance esperado de D1 y de A1: P1 y P3. De D2: P2, P3 y P4. P5 solo lo ve el admin.
 */
trait EscenarioAlcanceClinico
{
    use EscenarioEstadosCita;

    protected Usuario $paciente3;

    protected string $p3;

    protected Usuario $paciente4;

    protected string $p4;

    protected Usuario $paciente5;

    protected string $p5;

    protected string $e1;

    protected string $e2;

    protected string $e3;

    protected function crearEscenarioAlcance(): void
    {
        $this->crearEscenario();

        [$this->paciente3, $this->p3] = $this->crearPacienteDePrueba($this->d2, 'Tere', 'Concita');
        [$this->paciente4, $this->p4] = $this->crearPacienteDePrueba($this->d2, 'Cata', 'Cancelada');
        [$this->paciente5, $this->p5] = $this->crearPacienteDePrueba($this->d2, 'Sole', 'Sindentista');
        DB::table('pacientes')->where('id', $this->p5)->update(['dentista_id' => null]);

        $this->cita($this->d1, $this->p3, 'confirmada', '2026-12-01 10:00');
        $this->cita($this->d1, $this->p4, 'cancelada', '2026-12-01 11:00');

        $this->e1 = $this->expediente($this->p1, $this->d1, 'Diagnóstico de E1');
        $this->e2 = $this->expediente($this->p1, $this->d2, 'Diagnóstico de E2');
        $this->e3 = $this->expediente($this->p2, $this->d2, 'Diagnóstico de E3');
    }

    /** Crea un expediente sin cita y devuelve su id. */
    protected function expediente(string $paciente, string $dentista, string $diagnostico): string
    {
        $id = (string) Str::uuid();
        DB::table('expedientes')->insert([
            'id' => $id,
            'paciente_id' => $paciente,
            'dentista_id' => $dentista,
            'diagnostico' => $diagnostico,
            'creado_en' => now(),
        ]);

        return $id;
    }

    /** El último `acceso_denegado` registrado, o null. */
    protected function ultimoDenegado(): ?object
    {
        return DB::table('auditoria')->where('accion', 'acceso_denegado')->orderByDesc('id')->first();
    }

    /** El último evento con esa acción sobre ese recurso, o null. */
    protected function ultimoEvento(string $accion, string $recursoId): ?object
    {
        return DB::table('auditoria')->where('accion', $accion)->where('recurso_id', $recursoId)->orderByDesc('id')->first();
    }
}
