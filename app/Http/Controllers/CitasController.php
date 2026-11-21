<?php

namespace App\Http\Controllers;

use App\Agenda\CalendarioCitas;
use App\Agenda\CambioDeEstadoNoPermitido;
use App\Agenda\CitaNoAgendable;
use App\Agenda\EstadosCita;
use App\Agenda\RelojClinica;
use App\Agenda\ReprogramacionCitas;
use App\Agenda\ReservaCitas;
use App\Autorizacion\AlcanceClinico;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CitasController extends Controller
{
    // ── Helpers privados ───────────────────────────────────────────────
    private function queryBase()
    {
        return DB::table('citas')
            ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
            ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
            ->join('personal', 'citas.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->select(
                'citas.id',
                'citas.fecha_hora',
                'citas.duracion_min',
                'citas.estado',
                'citas.notas_paciente',
                'citas.notas_internas',
                'citas.recordatorio_enviado',
                'citas.creado_en',
                'pacientes.id AS paciente_id',
                DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                'up.telefono AS tel_paciente',
                'up.email AS email_paciente',
                'personal.id AS dentista_id',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'servicios.id AS servicio_id',
                'servicios.nombre AS servicio',
                'servicios.duracion_min AS duracion_servicio',
                'servicios.color_agenda'
            );
    }

    // ── Alcance por rol (misma regla que los Gates de la spec 026) ─────
    private function personalIdPropio(): ?string
    {
        return app(AlcanceClinico::class)->personalId(auth()->user());
    }

    // Solo se usa con el rol asistente: sus dentistas asignados.
    private function dentistaIdsAsistente(): array
    {
        return app(AlcanceClinico::class)->dentistasDelAlcance(auth()->user()) ?? [];
    }

    // Aplica la restricción de citas visibles según el rol autenticado
    private function aplicarAlcanceRol($query)
    {
        $rol = auth()->user()->rol;

        if ($rol === 'dentista') {
            $query->where('citas.dentista_id', $this->personalIdPropio());
        } elseif ($rol === 'asistente') {
            $query->whereIn('citas.dentista_id', $this->dentistaIdsAsistente());
        }

        return $query;
    }

    // ¿Puede el usuario autenticado ver/gestionar esta cita?
    private function autorizadoParaCita(string $dentistaId): bool
    {
        $rol = auth()->user()->rol;

        if ($rol === 'admin') {
            return true;
        }
        if ($rol === 'dentista') {
            return $dentistaId === $this->personalIdPropio();
        }
        if ($rol === 'asistente') {
            return in_array($dentistaId, $this->dentistaIdsAsistente());
        }

        return false;
    }

    // ── INDEX: calendario de citas (spec 049) ──────────────────────────
    public function index(Request $request)
    {
        $ahora = CarbonImmutable::now(config('clinica.zona_horaria'));

        // Parámetros (contrato del plan 049): lo que no es escalar o no tiene su formato se ignora.
        $vista = in_array($this->textoDe($request, 'vista'), ['semana', 'mes'], true) ? $this->textoDe($request, 'vista') : 'semana';
        $fecha = $this->fechaDe($request, 'fecha') ?? $this->fechaDe($request, 'fecha_desde') ?? $ahora->startOfDay();
        [$inicio, $fin] = CalendarioCitas::periodo($vista, $fecha);
        $dia = $this->fechaDe($request, 'dia');
        if ($dia !== null && ($dia->lessThan($inicio) || $dia->greaterThan($fin))) {
            $dia = null;
        }
        $dentistaId = Str::isUuid((string) $this->textoDe($request, 'dentista_id')) ? $this->textoDe($request, 'dentista_id') : null;
        $estado = in_array($this->textoDe($request, 'estado'), self::ESTADOS, true) ? $this->textoDe($request, 'estado') : null;
        $buscar = trim((string) $this->textoDe($request, 'buscar'));
        $buscar = $buscar !== '' && mb_strlen($buscar) <= 100 ? $buscar : null;

        $dentistas = $this->dentistasDelFiltro();

        // Una sola consulta del periodo con el alcance del rol y los filtros (D2). El filtro de
        // dentista se suma al alcance, nunca lo amplía (P3).
        $consulta = $this->aplicarAlcanceRol($this->queryBase())
            ->addSelect('ud.apellido AS dentista_apellido')
            ->whereBetween('citas.fecha_hora', [$inicio->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')]);
        if ($dentistaId !== null) {
            $consulta->where('citas.dentista_id', $dentistaId);
        }
        if ($buscar !== null) {
            $patron = '%'.$buscar.'%';
            $consulta->where(function ($q) use ($patron) {
                $q->whereRaw("CONCAT(up.nombre, ' ', up.apellido) LIKE ?", [$patron])
                    ->orWhereRaw("CONCAT(ud.nombre, ' ', ud.apellido) LIKE ?", [$patron])
                    ->orWhere('servicios.nombre', 'LIKE', $patron)
                    ->orWhere('up.telefono', 'LIKE', $patron);
            });
        }
        $delPeriodo = $consulta->orderBy('citas.fecha_hora')->orderBy('up.nombre')->orderBy('up.apellido')->get();

        // El resumen cuenta todos los estados del periodo (o del día elegido), antes del filtro de
        // estado, como hoy (D5).
        $paraResumen = $dia === null ? $delPeriodo : $delPeriodo->filter(fn ($c) => str_starts_with($c->fecha_hora, $dia->toDateString()));
        $resumen = (object) [
            'total' => $paraResumen->count(),
            'agendadas' => $paraResumen->where('estado', 'agendada')->count(),
            'completadas' => $paraResumen->where('estado', 'completada')->count(),
            'canceladas' => $paraResumen->where('estado', 'cancelada')->count(),
        ];

        $citas = $estado === null ? $delPeriodo : $delPeriodo->where('estado', $estado)->values();

        // Dentista que muestra el calendario cuando muestra una sola: la propia en la vista de la
        // dentista, o la del filtro si está en el desplegable del rol. Los huecos son sus tramos libres.
        $dentistaUnico = auth()->user()->rol === 'dentista'
            ? $this->personalIdPropio()
            : ($dentistas->contains('id', $dentistaId) ? $dentistaId : null);

        // Ocupación de los huecos (A28): mismo periodo y alcance, sin `buscar` ni `estado`, y solo de
        // la dentista que se muestra. Si la consulta del periodo ya es eso (sin buscador y con el
        // mismo filtro de dentista), sirve; si no, una segunda consulta, solo en la semana.
        $ocupacion = [];
        if ($vista === 'semana') {
            $ocupacion = $buscar === null && $dentistaUnico === $dentistaId
                ? $delPeriodo
                : $this->aplicarAlcanceRol(DB::table('citas'))
                    ->whereBetween('citas.fecha_hora', [$inicio->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')])
                    ->when($dentistaUnico !== null, fn ($q) => $q->where('citas.dentista_id', $dentistaUnico))
                    ->get(['citas.fecha_hora', 'citas.duracion_min', 'citas.estado', 'citas.dentista_id']);
        }

        $calendario = new CalendarioCitas(
            $vista, $fecha, $dia, $citas, $ocupacion,
            config('clinica.contacto.horario_semanal'), $ahora, $dentistaUnico,
        );
        $tituloResumen = $calendario->tituloResumen();
        $filtros = array_filter(['buscar' => $buscar, 'dentista_id' => $dentistaId, 'estado' => $estado], fn ($v) => $v !== null);

        return view('citas.index', compact('calendario', 'citas', 'dentistas', 'resumen', 'tituloResumen', 'filtros'));
    }

    /** Estados de una cita, para validar el filtro (spec 026). */
    private const ESTADOS = ['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];

    /** Valor de texto de un parámetro de la dirección; un array u otro tipo cuenta como ausente. */
    private function textoDe(Request $request, string $clave): ?string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? $valor : null;
    }

    /** Fecha `AAAA-MM-DD` válida de un parámetro de la dirección, en la zona de la clínica, o null. */
    private function fechaDe(Request $request, string $clave): ?CarbonImmutable
    {
        return RelojClinica::fechaDe($request->query($clave));
    }

    /** Dentistas del desplegable del filtro, respetando el alcance del rol (como hoy). */
    private function dentistasDelFiltro()
    {
        $dentistasQuery = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"))
            ->orderBy('usuarios.nombre');

        if (auth()->user()->rol === 'asistente') {
            $dentistasQuery->whereIn('personal.id', $this->dentistaIdsAsistente());
        }

        return $dentistasQuery->get();
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $pacientes = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.activo', 1)
            ->select('pacientes.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"), 'usuarios.telefono')
            ->orderBy('usuarios.nombre')
            ->get();

        $dentistasQuery = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"))
            ->orderBy('usuarios.nombre');

        $rol = auth()->user()->rol;
        if ($rol === 'dentista') {
            $dentistasQuery->where('personal.id', $this->personalIdPropio());
        } elseif ($rol === 'asistente') {
            $dentistasQuery->whereIn('personal.id', $this->dentistaIdsAsistente());
        }

        $dentistas = $dentistasQuery->get();

        $servicios = DB::table('servicios')
            ->where('activo', 1)
            ->orderBy('categoria')->orderBy('nombre')
            ->get();

        // Preselección al venir desde el perfil de un paciente o desde un hueco del calendario
        // (spec 049): solo se proponen valores de texto con su formato; lo demás se ignora (A13, A29).
        $uuid = fn (string $clave) => Str::isUuid((string) $this->textoDe($request, $clave)) ? $this->textoDe($request, $clave) : null;
        $pacienteSeleccionado = $uuid('paciente_id');
        $dentistaSeleccionado = $uuid('dentista');

        $fechaSugerida = $this->fechaDe($request, 'fecha')?->toDateString();

        if ($pacienteSeleccionado) {
            // Si el paciente tiene doctor asignado, preseleccionarlo
            if (! $dentistaSeleccionado) {
                $dentistaSeleccionado = DB::table('pacientes')
                    ->where('id', $pacienteSeleccionado)
                    ->value('dentista_id');
            }

            // Sugerir la fecha de próxima visita registrada en su expediente más reciente
            if (! $fechaSugerida) {
                $proximaVisita = DB::table('expedientes')
                    ->where('paciente_id', $pacienteSeleccionado)
                    ->whereNotNull('proxima_visita')
                    ->orderByDesc('creado_en')
                    ->value('proxima_visita');

                if ($proximaVisita && Carbon::parse($proximaVisita)->toDateString() > RelojClinica::fechaHoy()) {
                    $fechaSugerida = Carbon::parse($proximaVisita)->format('Y-m-d');
                }
            }
        }

        $fechaSugerida = $fechaSugerida ?: RelojClinica::hoy()->addDay()->toDateString();
        $hora = (string) $this->textoDe($request, 'hora');
        $horaSugerida = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora : '09:00';

        return view('citas.create', compact(
            'pacientes', 'dentistas', 'servicios', 'fechaSugerida', 'horaSugerida',
            'pacienteSeleccionado', 'dentistaSeleccionado'
        ));
    }

    // ── STORE ──────────────────────────────────────────────────────────
    public function store(Request $request, ReservaCitas $reservas)
    {
        $data = $request->validate(ReservaCitas::reglasDeEntrada() + [
            'paciente_id' => 'bail|required|string|exists:pacientes,id',
            'servicio_id' => 'bail|nullable|string|exists:servicios,id',
            'duracion_min' => 'required|integer|min:10|max:300',
            'notas_paciente' => 'nullable|string|max:1000',
            'notas_internas' => 'nullable|string|max:1000',
        ], ReservaCitas::mensajesDeEntrada() + [
            'paciente_id.string' => 'Elige un paciente válido.',
            'servicio_id.string' => 'Elige un servicio válido.',
        ]);

        if (! $this->autorizadoParaCita($data['dentista_id'])) {
            abort(403);
        }

        // La regla de agenda (horario, franja, solape y doble reserva) vive en ReservaCitas.
        try {
            $reservas->reservar([
                'paciente_id' => $data['paciente_id'],
                'dentista_id' => $data['dentista_id'],
                'servicio_id' => $data['servicio_id'] ?? null,
                'fecha' => $data['fecha'],
                'hora' => $data['hora'],
                'duracion_min' => (int) $data['duracion_min'],
                'notas_paciente' => $data['notas_paciente'] ?? null,
                'notas_internas' => $data['notas_internas'] ?? null,
                'creado_por' => auth()->id(),
            ]);
        } catch (CitaNoAgendable $rechazo) {
            return back()->withInput()->withErrors(['hora' => $rechazo->getMessage()]);
        }

        $base = match (auth()->user()->rol) {
            'dentista' => 'dentista',
            'asistente' => 'asistente',
            default => 'admin',
        };

        // Vuelve al calendario, en la semana y el día de la cita nueva (spec 049).
        return redirect()->route("$base.citas", [
            'vista' => 'semana',
            'fecha' => $data['fecha'],
            'dia' => $data['fecha'],
        ])->with('success', 'Cita agendada correctamente.');
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id, EstadosCita $estados)
    {
        $cita = $this->queryBase()->where('citas.id', $id)->first();

        if (! $cita) {
            abort(404);
        }
        if (! $this->autorizadoParaCita($cita->dentista_id)) {
            abort(403);
        }

        $historial = DB::table('historial_citas')
            ->leftJoin('usuarios', 'historial_citas.cambiado_por', '=', 'usuarios.id')
            ->where('cita_id', $id)
            ->select(
                'historial_citas.*',
                DB::raw("CONCAT(COALESCE(usuarios.nombre,'Sistema'), ' ', COALESCE(usuarios.apellido,'')) AS cambiado_por_nombre")
            )
            ->orderByDesc('fecha')
            ->get();

        // Solo las transiciones que este usuario puede hacer ahora (spec 026).
        $disponibles = $estados->disponibles(auth()->user(), $cita);
        $aviso = $this->avisoDeEstado($cita, $disponibles, $estados->haEmpezado($cita), $estados->inicio($cita));
        // «Reprogramar» solo si el rol puede y la cita aún se puede mover (spec 028).
        $cita->puede_reprogramar = Gate::allows('reprogramar-cita', $cita)
            && $this->motivoNoReprogramable($cita, $estados) === null;

        return view('citas.show', compact('cita', 'historial', 'disponibles', 'aviso'));
    }

    // Texto que sustituye al formulario de estado cuando no hay ningún cambio disponible.
    private function avisoDeEstado(object $cita, array $disponibles, bool $empezada, Carbon $inicio): ?string
    {
        if (in_array($cita->estado, EstadosCita::CERRADOS, true)) {
            return CambioDeEstadoNoPermitido::cerrada($cita->estado)->getMessage();
        }
        if ($disponibles !== []) {
            return null;
        }
        if (! $empezada) {
            return 'Podrás marcarla como en curso o no asistida a partir de las '
                .$inicio->format('H:i').' del '.$inicio->format('d/m/Y').'.';
        }
        if ($cita->estado === 'en_curso') {
            return 'Solo el dentista o la administración pueden completar esta cita.';
        }

        return 'No tienes cambios de estado disponibles para esta cita.';
    }

    // ── UPDATE ESTADO ──────────────────────────────────────────────────
    public function updateEstado(Request $request, string $id, EstadosCita $estados)
    {
        $cita = DB::table('citas')->where('id', $id)->first(['id', 'dentista_id', 'paciente_id', 'estado', 'fecha_hora']);
        if (! $cita) {
            abort(404);
        }
        // El alcance va antes de validar: fuera de alcance es 403 con cualquier entrada.
        Gate::authorize('gestionar-cita', $cita);

        // Las notas internas no se aceptan aquí (spec 026, CA11). Desde «No reagendar» (spec 028)
        // la cancelación exige marcar la casilla de confirmación (/review 028 R6, R23).
        $data = $request->validate([
            'estado' => 'required|string|in:'.implode(',', ['agendada', ...array_keys(EstadosCita::TRANSICIONES)]),
            'motivo' => 'nullable|string|max:500',
            'formulario' => 'nullable|string|in:reprogramar,no-reagendar',
            'confirmar' => 'exclude_unless:formulario,no-reagendar|exclude_unless:estado,cancelada|accepted',
        ], [
            'estado.required' => 'Elige un estado válido.',
            'estado.string' => 'Elige un estado válido.',
            'estado.in' => 'Elige un estado válido.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo puede tener como máximo 500 caracteres. Acórtalo y vuelve a intentarlo.',
            'formulario.string' => 'El formulario enviado no es válido.',
            'formulario.in' => 'El formulario enviado no es válido.',
            'confirmar.accepted' => 'Marca la casilla para confirmar que quieres cancelar la cita.',
        ]);

        Gate::authorize('cambiar-estado-cita', [$cita, $data['estado']]);
        $desdeNoReagendar = ($data['formulario'] ?? null) === 'no-reagendar' && $data['estado'] === 'cancelada';

        try {
            $estados->cambiar($id, $data['estado'], auth()->user(), $data['motivo'] ?? null);
        } catch (CambioDeEstadoNoPermitido $e) {
            // Desde «No reagendar» la sección puede ya no mostrarse: el rechazo va como aviso (R20).
            if ($desdeNoReagendar) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            // Se conserva el motivo escrito para no tener que volver a teclearlo.
            return back()->withInput()->withErrors(['estado' => $e->getMessage()]);
        }

        // Tras cancelar desde «No reagendar», la pantalla de reprogramar ya no aplica: al detalle (R1).
        if ($desdeNoReagendar) {
            return redirect()->route(auth()->user()->rol.'.citas.show', $id)->with('success', 'Estado actualizado correctamente.');
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    // ── REPROGRAMAR (spec 028) ─────────────────────────────────────────
    public function reprogramarForm(string $id, EstadosCita $estados)
    {
        $cita = $this->queryBase()->where('citas.id', $id)->first();
        if (! $cita) {
            abort(404);
        }
        Gate::authorize('reprogramar-cita', $cita);

        $rp = auth()->user()->rol;

        return view('citas.reprogramar', [
            'cita' => $cita,
            'motivoNoReprogramable' => $this->motivoNoReprogramable($cita, $estados),
            'rutaGuardar' => route("{$rp}.citas.reprogramar.update", $id),
            // La cancelación definitiva es el cambio de estado de la 026 (D5).
            'rutaCancelar' => route("{$rp}.citas.estado", $id),
            'camposCancelar' => ['estado' => 'cancelada'],
            'rutaDetalle' => route("{$rp}.citas.show", $id),
        ]);
    }

    public function reprogramar(Request $request, string $id, ReprogramacionCitas $reprogramacion)
    {
        $cita = DB::table('citas')->where('id', $id)->first(['id', 'dentista_id', 'paciente_id']);
        if (! $cita) {
            abort(404);
        }
        Gate::authorize('reprogramar-cita', $cita);

        // Solo fecha, hora y motivo: dentista, servicio, duración o estado enviados a mano se ignoran (CA9).
        $data = $request->validate(ReprogramacionCitas::reglasDeEntrada(), ReprogramacionCitas::mensajesDeEntrada());

        try {
            $reprogramacion->reprogramar($id, $data['fecha'], $data['hora'], auth()->user(), $data['motivo'] ?? null);
        } catch (CitaNoAgendable $rechazo) {
            // Junto al campo de la hora, como el agendado de la 025 (D14).
            return back()->withInput()->withErrors(['hora' => $rechazo->getMessage()]);
        } catch (CambioDeEstadoNoPermitido $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route(auth()->user()->rol.'.citas.show', $id)->with('success', 'Cita reprogramada correctamente.');
    }

    /** Motivo por el que la cita ya no se puede mover, o null si se puede (spec 028, A6). */
    private function motivoNoReprogramable(object $cita, EstadosCita $estados): ?string
    {
        try {
            $estados->comprobarReprogramable($cita, auth()->user());

            return null;
        } catch (CambioDeEstadoNoPermitido $e) {
            return $e->getMessage();
        }
    }

    // ── UPDATE NOTAS ───────────────────────────────────────────────────
    public function updateNotas(Request $request, string $id)
    {
        $data = $request->validate([
            'notas_internas' => 'nullable|string|max:1000',
        ]);

        DB::table('citas')->where('id', $id)->update([
            'notas_internas' => $data['notas_internas'] ?? null,
            'actualizado_en' => now(),
        ]);

        return back()->with('success', 'Notas guardadas.');
    }

    // ── DESTROY ────────────────────────────────────────────────────────
    // "Eliminar" es cancelar, con las mismas reglas que el cambio de estado (spec 026, CA6).
    public function destroy(string $id, EstadosCita $estados)
    {
        $cita = DB::table('citas')->where('id', $id)->first(['id', 'dentista_id', 'paciente_id', 'estado', 'fecha_hora']);
        if (! $cita) {
            abort(404);
        }
        Gate::authorize('gestionar-cita', $cita);
        Gate::authorize('cambiar-estado-cita', [$cita, 'cancelada']);

        try {
            $estados->cambiar($id, 'cancelada', auth()->user(), null);
        } catch (CambioDeEstadoNoPermitido $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return redirect()->route('admin.citas')
            ->with('success', 'Cita cancelada.');
    }
}
