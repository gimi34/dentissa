<?php

namespace App\Http\Controllers;

use App\Agenda\CitaNoAgendable;
use App\Agenda\ReservaCitas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    // ── Alcance por rol ────────────────────────────────────────────────
    private function personalIdPropio(): ?string
    {
        return DB::table('personal')->where('usuario_id', auth()->id())->value('id');
    }

    private function dentistaIdsAsistente(): array
    {
        $personalId = $this->personalIdPropio();
        if (! $personalId) {
            return [];
        }

        return DB::table('asistente_dentista')
            ->where('asistente_id', $personalId)
            ->pluck('dentista_id')
            ->all();
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
            'paciente_id' => 'required|exists:pacientes,id',
            'servicio_id' => 'nullable|exists:servicios,id',
            'duracion_min' => 'required|integer|min:10|max:300',
            'notas_paciente' => 'nullable|string|max:1000',
            'notas_internas' => 'nullable|string|max:1000',
        ], ReservaCitas::mensajesDeEntrada());

        if (! $this->autorizadoParaCita($data['dentista_id'])) {
            abort(403);
        }

        // La regla de agenda (horario, franja, solape y doble reserva) vive en ReservaCitas.
        try {
            $reservas->reservar([
                'paciente_id' => $data['paciente_id'],
                'dentista_id' => $data['dentista_id'],
                'servicio_id' => $data['servicio_id'] ?: null,
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
    public function show(string $id)
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

        $estados = ['agendada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];

        return view('citas.show', compact('cita', 'historial', 'estados'));
    }

    // ── UPDATE ESTADO ──────────────────────────────────────────────────
    public function updateEstado(Request $request, string $id)
    {
        $dentistaId = DB::table('citas')->where('id', $id)->value('dentista_id');
        if (! $dentistaId) {
            abort(404);
        }
        if (! $this->autorizadoParaCita($dentistaId)) {
            abort(403);
        }

        $data = $request->validate([
            'estado' => 'required|in:agendada,confirmada,en_curso,completada,cancelada,no_asistio',
            'motivo' => 'nullable|string|max:500',
            'notas_internas' => 'nullable|string|max:1000',
        ]);

        $updates = [
            'estado' => $data['estado'],
            'actualizado_en' => now(),
        ];

        // Guardar nota interna si se envía
        if (! empty($data['notas_internas'])) {
            $updates['notas_internas'] = $data['notas_internas'];
        }

        DB::table('citas')->where('id', $id)->update($updates);

        // El trigger trg_historial_cita en MySQL registra el cambio automáticamente,
        // pero si quieres guardar el motivo manualmente también:
        if (! empty($data['motivo'])) {
            DB::table('historial_citas')->insert([
                'id' => (string) Str::uuid(),
                'cita_id' => $id,
                'cambiado_por' => auth()->id(),
                'estado_nuevo' => $data['estado'],
                'motivo' => $data['motivo'],
                'fecha' => now(),
            ]);
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    // ── UPDATE NOTAS ───────────────────────────────────────────────────
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
    public function destroy(string $id)
    {
        DB::table('citas')->where('id', $id)->update([
            'estado' => 'cancelada',
            'actualizado_en' => now(),
        ]);

        return redirect()->route('admin.citas')
            ->with('success', 'Cita cancelada.');
    }
}
