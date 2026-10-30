<?php

namespace App\Http\Controllers;

use App\Agenda\CambioDeEstadoNoPermitido;
use App\Agenda\CitaNoAgendable;
use App\Agenda\EstadosCita;
use App\Agenda\ReprogramacionCitas;
use App\Agenda\ReservaCitas;
use App\Auditoria\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClienteCitasController extends Controller
{
    // Aviso del listado cuando faltan menos de 24 horas (spec 028, A13).
    public const AVISO_PLAZO_CORTO = 'Faltan menos de 24 h: llama a la clínica para cambiarla.';

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
                    'citas.dentista_id',
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
                ->through(function ($cita) use ($estados) {
                    $cita = $this->conPuedeCancelar($cita, $estados);
                    // Solo si aún no empieza: una cita ya empezada no lleva aviso en la lista (A40).
                    $cita->aviso_plazo_corto = $this->fueraDePlazo($cita) && ! $estados->haEmpezado($cita)
                        ? self::AVISO_PLAZO_CORTO
                        : null;

                    return $cita;
                });
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
                'citas.dentista_id',
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
            $this->noEncontrada($id);
        }

        $cita = $this->conPuedeCancelar($cita, $estados);
        // Fuera de plazo se explica por qué no hay botones en lugar de quitarlos sin más (A40).
        $cita->aviso_plazo = match (true) {
            ! $this->fueraDePlazo($cita) => null,
            $estados->haEmpezado($cita) => CambioDeEstadoNoPermitido::yaEmpezo()->getMessage(),
            default => CambioDeEstadoNoPermitido::fueraDePlazo()->getMessage(),
        };

        return view('citas.cliente-show', compact('cita'));
    }

    // ── CANCELAR ───────────────────────────────────────────────────────
    public function cancelar(Request $request, string $id, EstadosCita $estados)
    {
        $cita = DB::table('citas')->where('id', $id)->first(['id', 'dentista_id', 'paciente_id', 'estado', 'fecha_hora']);
        if (! $cita) {
            abort(404);
        }
        // La cita de otro paciente responde 404, como antes.
        Gate::authorize('cambiar-estado-cita', [$cita, 'cancelada']);

        // Desde «No reagendar» llega un motivo opcional (spec 028, CA6) y la casilla de
        // confirmación obligatoria (/review 028 R6).
        $data = $request->validate([
            'motivo' => 'nullable|string|max:500',
            'formulario' => 'nullable|string|in:reprogramar,no-reagendar',
            'confirmar' => 'exclude_unless:formulario,no-reagendar|accepted',
        ], [
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.',
            'formulario.string' => 'El formulario enviado no es válido.',
            'formulario.in' => 'El formulario enviado no es válido.',
            'confirmar.accepted' => 'Marca la casilla para confirmar que quieres cancelar la cita.',
        ]);

        try {
            $estados->cambiar($id, 'cancelada', auth()->user(), $data['motivo'] ?? null);
        } catch (CambioDeEstadoNoPermitido $e) {
            // El paciente no ve estados internos (spec 026, CA2 y D7).
            $mensaje = in_array($e->tipo, ['cerrada', 'transicion', 'vuelta'], true)
                ? 'Esta cita ya no se puede cancelar. Si necesitas ayuda, llama a la clínica.'
                : $e->getMessage();

            return back()->withInput()->with('error', $mensaje);
        }

        // Tras cancelar desde «No reagendar», la pantalla de reprogramar ya no aplica: al detalle (R1).
        if (($data['formulario'] ?? null) === 'no-reagendar') {
            return redirect()->route('cliente.citas.show', $id)->with('success', 'Tu cita fue cancelada.');
        }

        return back()->with('success', 'Tu cita fue cancelada.');
    }

    // ── REPROGRAMAR ────────────────────────────────────────────────────
    // Spec 028: el paciente mueve su cita con 24 horas o más de antelación (CA2).
    public function reprogramarForm(string $id, EstadosCita $estados)
    {
        $cita = $this->citaDelPaciente($id);
        Gate::authorize('reprogramar-cita', $cita);

        return view('citas.cliente-reprogramar', [
            'cita' => $cita,
            'motivoNoReprogramable' => $this->motivoNoReprogramable($cita, $estados),
            'rutaGuardar' => route('cliente.citas.reprogramar.update', $id),
            'rutaCancelar' => route('cliente.citas.cancelar', $id),
            'camposCancelar' => [],
            'rutaDetalle' => route('cliente.citas.show', $id),
        ]);
    }

    public function reprogramar(Request $request, string $id, ReprogramacionCitas $reprogramacion)
    {
        $cita = $this->citaDelPaciente($id);
        Gate::authorize('reprogramar-cita', $cita);

        // Solo fecha, hora y motivo: dentista, servicio, duración o estado enviados a mano se ignoran (CA9).
        $data = $request->validate(ReprogramacionCitas::reglasDeEntrada(), ReprogramacionCitas::mensajesDeEntrada());

        try {
            $reprogramacion->reprogramar($id, $data['fecha'], $data['hora'], auth()->user(), $data['motivo'] ?? null);
        } catch (CitaNoAgendable $rechazo) {
            // Junto al campo de la hora, como el agendado de la 025 (D14).
            return back()->withInput()->withErrors(['hora' => $rechazo->getMessage()]);
        } catch (CambioDeEstadoNoPermitido $e) {
            return back()->withInput()->with('error', $this->mensajeParaElPaciente($e));
        }

        return redirect()->route('cliente.citas.show', $id)->with('success', 'Tu cita fue reprogramada.');
    }

    /** La cita del paciente que ha iniciado sesión, o 404 si no es suya (TM2). */
    private function citaDelPaciente(string $id): object
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
                'citas.dentista_id',
                'citas.paciente_id',
                'citas.fecha_hora',
                'citas.duracion_min',
                'citas.estado',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'servicios.nombre AS servicio'
            )
            ->first();

        if (! $cita) {
            $this->noEncontrada($id);
        }

        return $cita;
    }

    /**
     * La cita no es del paciente: si existe, la oculta con el mismo 404 pero deja su evento
     * `denegado_404`; si no existe, es un 404 sin evento (spec 020, CA4; D4, A66).
     */
    private function noEncontrada(string $id): never
    {
        if (DB::table('citas')->where('id', $id)->exists()) {
            app(Auditoria::class)->denegar(request()->route()?->getName() ?? 'ruta_sin_nombre', 'citas', $id, 'denegado_404');
        }

        abort(404);
    }

    /** Motivo por el que el paciente no puede mover la cita, o null si puede (A6). */
    private function motivoNoReprogramable(object $cita, EstadosCita $estados): ?string
    {
        try {
            $estados->comprobarReprogramable($cita, auth()->user());

            return null;
        } catch (CambioDeEstadoNoPermitido $e) {
            return $this->mensajeParaElPaciente($e);
        }
    }

    // Una cita cerrada, en curso o ya empezada, o con el dentista inactivo o sin duración, se explica
    // sin estados internos (A7, A41; /review 028 R19).
    private function mensajeParaElPaciente(CambioDeEstadoNoPermitido $e): string
    {
        return in_array($e->tipo, ['cerrada', 'yaEmpezoPersonal', 'dentistaInactivo', 'sinDuracion'], true)
            ? 'Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.'
            : $e->getMessage();
    }

    // El paciente cancela o reprograma una cita agendada o confirmada con 24 horas o más (spec 028, CA2 y CA3).
    private function conPuedeCancelar(object $cita, EstadosCita $estados): object
    {
        $cita->puede_cancelar = in_array($cita->estado, ['agendada', 'confirmada'], true)
            && $estados->dentroDelPlazoDelPaciente($cita);
        // Reprogramar pide además lo que comprueba la pantalla: dentista activo y duración válida (R19).
        $cita->puede_reprogramar = $cita->puede_cancelar && $this->motivoNoReprogramable($cita, $estados) === null;

        return $cita;
    }

    // Agendada o confirmada, pero ya sin las 24 horas de antelación. Lee `puede_cancelar`, que
    // calcula antes `conPuedeCancelar`.
    private function fueraDePlazo(object $cita): bool
    {
        return in_array($cita->estado, ['agendada', 'confirmada'], true) && ! $cita->puede_cancelar;
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
