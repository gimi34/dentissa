<?php

namespace App\Http\Controllers;

use App\Agenda\CambioDeEstadoNoPermitido;
use App\Agenda\CitaNoAgendable;
use App\Agenda\EstadosCita;
use App\Agenda\ReservaCitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClienteCitasController extends Controller
{
    private function pacienteActual()
    {
        return DB::table('pacientes')->where('usuario_id', auth()->id())->first();
    }

    // ── INDEX ──────────────────────────────────────────────────────────
    public function index(Request $request, EstadosCita $estados)
    {
        $paciente = $this->pacienteActual();
        $pasadas = $request->get('filtro') === 'pasadas';
        $citas = collect();

        if ($paciente) {
            $query = DB::table('citas')
                ->join('personal', 'citas.dentista_id', '=', 'personal.id')
                ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
                ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
                ->where('citas.paciente_id', $paciente->id)
                ->select(
                    'citas.id',
                    'citas.fecha_hora',
                    'citas.duracion_min',
                    'citas.estado',
                    DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                    'servicios.nombre AS servicio',
                    'servicios.duracion_min AS duracion_servicio'
                );

            if ($pasadas) {
                $query->where('citas.fecha_hora', '<', now())->orderByDesc('citas.fecha_hora');
            } else {
                $query->where('citas.fecha_hora', '>=', now())
                    ->where('citas.estado', '!=', 'cancelada')
                    ->orderBy('citas.fecha_hora');
            }

            $citas = $query->paginate(10)->withQueryString()
                ->through(fn ($cita) => $this->conPuedeCancelar($cita, $estados));
        }

        return view('citas.cliente-index', compact('citas', 'pasadas'));
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id, EstadosCita $estados)
    {
        $paciente = $this->pacienteActual();
        abort_unless($paciente, 404);

        $cita = DB::table('citas')
            ->join('personal', 'citas.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->where('citas.id', $id)
            ->where('citas.paciente_id', $paciente->id)
            ->select(
                'citas.id',
                'citas.fecha_hora',
                'citas.duracion_min',
                'citas.estado',
                'citas.notas_paciente',
                'citas.creado_en',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'personal.especialidad',
                'servicios.nombre AS servicio'
            )
            ->first();

        if (! $cita) {
            abort(404);
        }

        $cita = $this->conPuedeCancelar($cita, $estados);

        return view('citas.cliente-show', compact('cita'));
    }

    // ── CANCELAR ───────────────────────────────────────────────────────
    public function cancelar(string $id, EstadosCita $estados)
    {
        $cita = DB::table('citas')->where('id', $id)->first(['id', 'dentista_id', 'paciente_id', 'estado', 'fecha_hora']);
        if (! $cita) {
            abort(404);
        }
        // La cita de otro paciente responde 404, como antes.
        Gate::authorize('cambiar-estado-cita', [$cita, 'cancelada']);

        try {
            $estados->cambiar($id, 'cancelada', auth()->user(), null);
        } catch (CambioDeEstadoNoPermitido $e) {
            // El paciente no ve estados internos (spec 026, CA2 y D7).
            $mensaje = in_array($e->tipo, ['cerrada', 'transicion', 'vuelta'], true)
                ? 'Esta cita ya no se puede cancelar. Si necesitas ayuda, llama a la clínica.'
                : $e->getMessage();

            return back()->with('error', $mensaje);
        }

        return back()->with('success', 'Tu cita fue cancelada.');
    }

    // El paciente puede cancelar una cita agendada o confirmada que aún no empieza (CA7).
    private function conPuedeCancelar(object $cita, EstadosCita $estados): object
    {
        $cita->puede_cancelar = in_array($cita->estado, ['agendada', 'confirmada'], true)
            && ! $estados->haEmpezado($cita);

        return $cita;
    }

    // ── AGENDAR (form) ────────────────────────────────────────────────
    public function agendarForm(Request $request)
    {
        $dentistas = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"), 'personal.especialidad')
            ->orderBy('usuarios.nombre')
            ->get();

        $servicios = DB::table('servicios')
            ->where('activo', 1)
            ->orderBy('categoria')->orderBy('nombre')
            ->get();

        $fechaSugerida = $request->get('fecha', now()->addDay()->format('Y-m-d'));

        return view('citas.cliente-agendar', compact('dentistas', 'servicios', 'fechaSugerida'));
    }

    // ── AGENDAR (store) ───────────────────────────────────────────────
    public function agendarStore(Request $request, ReservaCitas $reservas)
    {
        $paciente = $this->pacienteActual();
        abort_unless($paciente, 404);

        $reglas = ReservaCitas::reglasDeEntrada();
        $reglas['fecha'][] = 'after_or_equal:today';

        $data = $request->validate($reglas + [
            'servicio_id' => 'bail|required|string|exists:servicios,id',
            'notas_paciente' => 'nullable|string|max:1000',
        ], ReservaCitas::mensajesDeEntrada() + [
            'dentista_id.required' => 'Elige un dentista.',
            'servicio_id.required' => 'Elige un servicio.',
            'servicio_id.string' => 'Elige un servicio válido.',
        ]);

        $servicio = DB::table('servicios')->where('id', $data['servicio_id'])->first();

        // La regla de agenda (horario, franja, solape y doble reserva) vive en ReservaCitas.
        try {
            $reservas->reservar([
                'paciente_id' => $paciente->id,
                'dentista_id' => $data['dentista_id'],
                'servicio_id' => $data['servicio_id'],
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'duracion_min' => $servicio->duracion_min ?? 30,
                'notas_paciente' => $data['notas_paciente'] ?? null,
                'notas_internas' => null,
                'creado_por' => auth()->id(),
            ]);
        } catch (CitaNoAgendable $rechazo) {
            return back()->withInput()->withErrors(['hora' => $rechazo->getMessage()]);
        }

        return redirect()->route('cliente.dashboard')
            ->with('success', 'Tu cita fue agendada correctamente.');
    }
}
