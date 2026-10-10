<?php

namespace App\Http\Controllers;

use App\Agenda\CambioDeEstadoNoPermitido;
use App\Agenda\CitaNoAgendable;
use App\Agenda\EstadosCita;
use App\Agenda\ReprogramacionCitas;
use App\Agenda\ReservaCitas;
use App\Autorizacion\AlcanceClinico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

    // ── INDEX ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->aplicarAlcanceRol($this->queryBase());

        // Filtros de fecha / dentista / búsqueda (aplican tanto a la tabla como al resumen)
        if ($request->filled('fecha_desde')) {
            $query->whereDate('citas.fecha_hora', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('citas.fecha_hora', '<=', $request->fecha_hasta);
        }
        if ($request->filled('dentista_id')) {
            $query->where('citas.dentista_id', $request->dentista_id);
        }
        if ($request->filled('buscar')) {
            $buscar = '%'.$request->buscar.'%';
            $query->where(function ($q) use ($buscar) {
                $q->whereRaw("CONCAT(up.nombre, ' ', up.apellido) LIKE ?", [$buscar])
                    ->orWhereRaw("CONCAT(ud.nombre, ' ', ud.apellido) LIKE ?", [$buscar])
                    ->orWhere('servicios.nombre', 'LIKE', $buscar)
                    ->orWhere('up.telefono', 'LIKE', $buscar);
            });
        }

        // Por defecto: citas del día actual si no hay filtros de fecha
        if (! $request->filled('fecha_desde') && ! $request->filled('fecha_hasta') && ! $request->filled('vista')) {
            $query->whereDate('citas.fecha_hora', '>=', Carbon::today())
                ->whereDate('citas.fecha_hora', '<=', Carbon::today()->addDays(7));
        }

        // Resumen del rango mostrado (antes de aplicar el filtro de estado y la paginación,
        // para que las tarjetas reflejen todos los estados del mismo rango de fechas visible)
        $resumenHoy = (clone $query)
            ->select(DB::raw("
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN citas.estado = 'completada' THEN 1 ELSE 0 END), 0) AS completadas,
                COALESCE(SUM(CASE WHEN citas.estado = 'agendada'   THEN 1 ELSE 0 END), 0) AS agendadas,
                COALESCE(SUM(CASE WHEN citas.estado = 'cancelada'  THEN 1 ELSE 0 END), 0) AS canceladas
            "))
            ->first();

        if ($request->filled('estado')) {
            $query->where('citas.estado', $request->estado);
        }

        $citas = $query->orderBy('citas.fecha_hora')->paginate(25)->withQueryString();

        // Datos para filtros del formulario (respetando el alcance del rol)
        $dentistasQuery = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"))
            ->orderBy('usuarios.nombre');

        if (auth()->user()->rol === 'asistente') {
            $dentistasQuery->whereIn('personal.id', $this->dentistaIdsAsistente());
        }

        $dentistas = $dentistasQuery->get();

        return view('citas.index', compact('citas', 'dentistas', 'resumenHoy'));
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

        // Preselección al venir desde el perfil de un paciente
        $pacienteSeleccionado = $request->get('paciente_id');
        $dentistaSeleccionado = $request->get('dentista');

        $fechaSugerida = $request->get('fecha');

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

                if ($proximaVisita && Carbon::parse($proximaVisita)->isFuture()) {
                    $fechaSugerida = Carbon::parse($proximaVisita)->format('Y-m-d');
                }
            }
        }

        $fechaSugerida = $fechaSugerida ?: now()->addDay()->format('Y-m-d');
        $horaSugerida = $request->get('hora', '09:00');

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

        return redirect()->route("$base.citas", [
            'fecha_desde' => $data['fecha'],
            'fecha_hasta' => $data['fecha'],
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
        // la cancelación exige marcar la casilla de confirmación (/review 028 R6).
        $data = $request->validate([
            'estado' => 'required|string|in:'.implode(',', ['agendada', ...array_keys(EstadosCita::TRANSICIONES)]),
            'motivo' => 'nullable|string|max:500',
            'formulario' => 'nullable|string|in:reprogramar,no-reagendar',
            'confirmar' => 'exclude_unless:formulario,no-reagendar|accepted',
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

        try {
            $estados->cambiar($id, $data['estado'], auth()->user(), $data['motivo'] ?? null);
        } catch (CambioDeEstadoNoPermitido $e) {
            // Se conserva el motivo escrito para no tener que volver a teclearlo.
            return back()->withInput()->withErrors(['estado' => $e->getMessage()]);
        }

        // Tras cancelar desde «No reagendar», la pantalla de reprogramar ya no aplica: al detalle (R1).
        if (($data['formulario'] ?? null) === 'no-reagendar') {
            return redirect()->route(auth()->user()->rol.'.citas.show', $id)->with('success', 'Estado actualizado correctamente.');
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    // ── UPDATE NOTAS ───────────────────────────────────────────────────
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

    public function updateNotas(Request $request, string $id)
    {
        $data = $request->validate([
            'notas_internas' => 'nullable|string|max:1000',
        ]);

        DB::table('citas')->where('id', $id)->update([
            'notas_internas' => $data['notas_internas'],
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
