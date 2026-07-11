<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                'servicios.precio',
                'servicios.duracion_min AS duracion_servicio',
                'servicios.color_agenda'
            );
    }

    // ── INDEX ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = $this->queryBase();

        // Filtros
        if ($request->filled('estado')) {
            $query->where('citas.estado', $request->estado);
        }
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
            $buscar = '%' . $request->buscar . '%';
            $query->where(function($q) use ($buscar) {
                $q->whereRaw("CONCAT(up.nombre, ' ', up.apellido) LIKE ?", [$buscar])
                  ->orWhereRaw("CONCAT(ud.nombre, ' ', ud.apellido) LIKE ?", [$buscar])
                  ->orWhere('servicios.nombre', 'LIKE', $buscar)
                  ->orWhere('up.telefono', 'LIKE', $buscar);
            });
        }

        // Por defecto: citas del día actual si no hay filtros de fecha
        if (!$request->filled('fecha_desde') && !$request->filled('fecha_hasta') && !$request->filled('vista')) {
    $query->whereDate('citas.fecha_hora', '>=', Carbon::today())
          ->whereDate('citas.fecha_hora', '<=', Carbon::today()->addDays(7));
}


        $citas = $query->orderBy('citas.fecha_hora')->paginate(25)->withQueryString();

        // Datos para filtros del formulario
        $dentistas = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"))
            ->orderBy('usuarios.nombre')
            ->get();

        // Resumen de hoy para las tarjetas superiores
        $resumenHoy = DB::table('citas')
            ->whereDate('fecha_hora', Carbon::today())
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'completada'  THEN 1 ELSE 0 END) AS completadas,
                SUM(CASE WHEN estado = 'agendada'    THEN 1 ELSE 0 END) AS agendadas,
                SUM(CASE WHEN estado = 'cancelada'   THEN 1 ELSE 0 END) AS canceladas
            ")
            ->first();

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

        $dentistas = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"))
            ->orderBy('usuarios.nombre')
            ->get();

        $servicios = DB::table('servicios')
            ->where('activo', 1)
            ->orderBy('categoria')->orderBy('nombre')
            ->get();

        $fechaSugerida = $request->get('fecha', now()->addDay()->format('Y-m-d'));
        $horaSugerida  = $request->get('hora', '09:00');

        return view('citas.create', compact('pacientes', 'dentistas', 'servicios', 'fechaSugerida', 'horaSugerida'));
    }

    // ── STORE ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'paciente_id'  => 'required|exists:pacientes,id',
            'dentista_id'  => 'required|exists:personal,id',
            'servicio_id'  => 'nullable|exists:servicios,id',
            'fecha'        => 'required|date',
            'hora'         => 'required',
            'duracion_min' => 'required|integer|min:10|max:300',
            'notas_paciente'  => 'nullable|string|max:1000',
            'notas_internas'  => 'nullable|string|max:1000',
        ]);

$fechaHora = Carbon::parse($data['fecha'] . ' ' . $data['hora']);


$duracion = (int) $data['duracion_min'];
$horaFinCita = (clone $fechaHora)->addMinutes($duracion);

// Disponibilidad del dentista según su horario semanal
$diaSemana = $fechaHora->isoWeekday(); // 1=Lunes ... 7=Domingo

$horario = DB::table('horarios_dentista')
    ->where('personal_id', $data['dentista_id'])
    ->where('dia_semana', $diaSemana)
    ->first();

if (!$horario || !$horario->disponible) {
    return back()->withInput()->withErrors([
        'hora' => 'El dentista no tiene horario disponible ese día de la semana.'
    ]);
}

$horaInicio = Carbon::parse($data['fecha'] . ' ' . $horario->hora_inicio);
$horaFinDisponible = Carbon::parse($data['fecha'] . ' ' . $horario->hora_fin);

if ($fechaHora->lt($horaInicio) || $horaFinCita->gt($horaFinDisponible)) {
    return back()->withInput()->withErrors([
        'hora' => 'El horario elegido está fuera del horario de atención del dentista ('
            . $horaInicio->format('H:i') . ' - ' . $horaFinDisponible->format('H:i') . ').'
    ]);
}

$conflicto = DB::table('citas')
    ->where('dentista_id', $data['dentista_id'])
    ->whereNotIn('estado', ['cancelada', 'no_asistio'])
    ->where(function($q) use ($fechaHora, $horaFinCita) {
        $q->whereBetween('fecha_hora', [$fechaHora, $horaFinCita])
          ->orWhereRaw('DATE_ADD(fecha_hora, INTERVAL duracion_min MINUTE) > ? AND fecha_hora < ?',
              [$fechaHora, $horaFinCita]);
    })
    ->exists();

        if ($conflicto) {
            return back()->withInput()->withErrors([
                'hora' => 'El dentista ya tiene una cita en ese horario. Por favor elige otro horario.'
            ]);
        }

        DB::table('citas')->insert([
            'id'           => (string) \Illuminate\Support\Str::uuid(),
            'paciente_id'  => $data['paciente_id'],
            'dentista_id'  => $data['dentista_id'],
            'servicio_id'  => $data['servicio_id'] ?: null,
            'fecha_hora'   => $fechaHora,
            'duracion_min' => (int) $data['duracion_min'],
            'estado'       => 'agendada',
            'notas_paciente'  => $data['notas_paciente'] ?? null,
            'notas_internas'  => $data['notas_internas'] ?? null,
            'creado_por'   => auth()->id(),
            'creado_en'    => now(),
            'actualizado_en' => now(),
        ]);

        return redirect()->route('admin.citas', [
    'fecha_desde' => $fechaHora->format('Y-m-d'),
    'fecha_hasta' => $fechaHora->format('Y-m-d'),
])->with('success', 'Cita agendada correctamente.');
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id)
    {
        $cita = $this->queryBase()->where('citas.id', $id)->first();

        if (!$cita) abort(404);

        $historial = DB::table('historial_citas')
            ->leftJoin('usuarios', 'historial_citas.cambiado_por', '=', 'usuarios.id')
            ->where('cita_id', $id)
            ->select(
                'historial_citas.*',
                DB::raw("CONCAT(COALESCE(usuarios.nombre,'Sistema'), ' ', COALESCE(usuarios.apellido,'')) AS cambiado_por_nombre")
            )
            ->orderByDesc('fecha')
            ->get();

        $estados = ['agendada','confirmada','en_curso','completada','cancelada','no_asistio'];

        return view('citas.show', compact('cita', 'historial', 'estados'));
    }

    // ── UPDATE ESTADO ──────────────────────────────────────────────────
    public function updateEstado(Request $request, string $id)
    {
        $data = $request->validate([
            'estado'          => 'required|in:agendada,confirmada,en_curso,completada,cancelada,no_asistio',
            'motivo'          => 'nullable|string|max:500',
            'notas_internas'  => 'nullable|string|max:1000',
            'cobrado'         => 'nullable|boolean',
        ]);

        $updates = [
            'estado'         => $data['estado'],
            'actualizado_en' => now(),
        ];

        // Guardar nota interna si se envía (incluyendo info de cobro)
        if (!empty($data['notas_internas'])) {
            $updates['notas_internas'] = $data['notas_internas'];
        }

        DB::table('citas')->where('id', $id)->update($updates);

        // El trigger trg_historial_cita en MySQL registra el cambio automáticamente,
        // pero si quieres guardar el motivo manualmente también:
        if (!empty($data['motivo'])) {
            DB::table('historial_citas')->insert([
                'id'        => (string) \Illuminate\Support\Str::uuid(),
                'cita_id'   => $id,
                'cambiado_por' => auth()->id(),
                'estado_nuevo' => $data['estado'],
                'motivo'    => $data['motivo'],
                'fecha'     => now(),
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
            'estado'         => 'cancelada',
            'actualizado_en' => now(),
        ]);

        return redirect()->route('admin.citas')
            ->with('success', 'Cita cancelada.');
    }
}