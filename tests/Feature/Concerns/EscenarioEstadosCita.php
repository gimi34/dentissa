<?php

namespace Tests\Feature\Concerns;

use App\Models\Usuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;

/**
 * Escenario común de la spec 026 (estados de la cita).
 *
 * Crea, dentro de la transacción del test: un admin; los dentistas D1 y D2;
 * el asistente A1, asignado solo a D1; y los pacientes P1 (de D1) y P2 (de D2),
 * cada uno con su usuario cliente. Los nombres son distintos entre sí para que
 * las aserciones sobre el historial no coincidan por accidente.
 */
trait EscenarioEstadosCita
{
    protected Usuario $admin;

    protected Usuario $dentista1;

    protected string $d1;

    protected Usuario $dentista2;

    protected string $d2;

    protected Usuario $asistente;

    protected Usuario $paciente1;

    protected string $p1;

    protected Usuario $paciente2;

    protected string $p2;

    protected function crearEscenario(): void
    {
        $this->admin = $this->crearUsuarioDePrueba('admin', 'Adela', 'Administra');
        [$this->dentista1, $this->d1] = $this->crearPersonal('dentista', 'Diego', 'Primero');
        [$this->dentista2, $this->d2] = $this->crearPersonal('dentista', 'Dora', 'Segunda');

        [$this->asistente, $asistenteId] = $this->crearPersonal('asistente', 'Alba', 'Asiste');
        DB::table('asistente_dentista')->insert([
            'id' => (string) Str::uuid(), 'asistente_id' => $asistenteId, 'dentista_id' => $this->d1,
        ]);

        [$this->paciente1, $this->p1] = $this->crearPacienteDePrueba($this->d1, 'Pablo', 'Paciente');
        [$this->paciente2, $this->p2] = $this->crearPacienteDePrueba($this->d2, 'Paula', 'Otra');
    }

    /** Crea una cita y devuelve su id. `$inicio` es la hora local de la clínica ('Y-m-d H:i'). */
    protected function cita(string $dentista, string $paciente, string $estado, string $inicio): string
    {
        $id = (string) Str::uuid();
        DB::table('citas')->insert([
            'id' => $id,
            'paciente_id' => $paciente,
            'dentista_id' => $dentista,
            'fecha_hora' => $inicio.':00',
            'duracion_min' => 30,
            'estado' => $estado,
            'notas_internas' => 'Nota interna original',
        ]);

        return $id;
    }

    /** Cambio de estado del personal por su propia ruta (`PATCH /{rol}/citas/{id}/estado`). */
    protected function cambiar(Usuario $usuario, string $cita, string $estado, ?string $motivo = null, array $extra = []): TestResponse
    {
        $prefijo = $usuario->rol === 'cliente' ? 'admin' : $usuario->rol;

        return $this->actingAs($usuario)->patch("/{$prefijo}/citas/{$cita}/estado", array_merge(
            ['estado' => $estado, 'motivo' => $motivo],
            $extra,
        ));
    }

    protected function estadoDe(string $cita): ?string
    {
        return DB::table('citas')->where('id', $cita)->value('estado');
    }

    /**
     * Primer error de validación del campo en la sesión. Con `session.serialization = json`
     * llegan como array; en otro caso, como ViewErrorBag.
     */
    protected function errorDe(string $campo): string
    {
        $errores = app('session.store')->get('errors');

        if ($errores instanceof ViewErrorBag) {
            return $errores->first($campo);
        }

        return (string) ($errores['default']['messages'][$campo][0] ?? '');
    }

    protected function entradasDeHistorial(string $cita): Collection
    {
        return DB::table('historial_citas')->where('cita_id', $cita)->orderBy('fecha')->get();
    }

    /** HTML del elemento con ese `id`, o null si la página no lo tiene. */
    protected function seccion(string $html, string $id): ?string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $nodo = (new DOMXPath($dom))->query("//*[@id='{$id}']")->item(0);

        return $nodo === null ? null : $dom->saveHTML($nodo);
    }

    /** @return array{0: Usuario, 1: string} usuario y id de `personal` */
    private function crearPersonal(string $tipo, string $nombre, string $apellido): array
    {
        $usuario = $this->crearUsuarioDePrueba($tipo, $nombre, $apellido);
        $id = (string) Str::uuid();
        DB::table('personal')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'tipo' => $tipo]);

        return [$usuario, $id];
    }

    /** @return array{0: Usuario, 1: string} usuario cliente e id de `pacientes` */
    private function crearPacienteDePrueba(string $dentista, string $nombre, string $apellido): array
    {
        $usuario = $this->crearUsuarioDePrueba('cliente', $nombre, $apellido);
        $id = (string) Str::uuid();
        DB::table('pacientes')->insert(['id' => $id, 'usuario_id' => $usuario->id, 'dentista_id' => $dentista]);

        return [$usuario, $id];
    }

    private function crearUsuarioDePrueba(string $rol, string $nombre, string $apellido): Usuario
    {
        return Usuario::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => 'estados.'.Str::random(10).'@example.test',
            'rol' => $rol,
            'activo' => 1,
            'password_hash' => Hash::make('secreto-de-pruebas'),
        ]);
    }
}
