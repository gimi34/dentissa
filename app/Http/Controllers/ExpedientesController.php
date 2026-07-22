<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpedientesController extends Controller
{
    // ── INDEX ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = DB::table('expedientes')
            ->join('pacientes', 'expedientes.paciente_id', '=', 'pacientes.id')
            ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
            ->join('personal', 'expedientes.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->select(
                'expedientes.id',
                'expedientes.diagnostico',
                'expedientes.proxima_visita',
                'expedientes.creado_en',
                'pacientes.id AS paciente_id',
                DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista")
            );

        // El dentista solo ve los expedientes que él mismo registró
        if (auth()->user()->rol === 'dentista') {
            $personal = DB::table('personal')->where('usuario_id', auth()->id())->first();
            $query->where('expedientes.dentista_id', $personal->id ?? null);
        }

        if ($request->filled('buscar')) {
            $b = '%' . $request->buscar . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw("CONCAT(up.nombre, ' ', up.apellido) LIKE ?", [$b])
                  ->orWhere('expedientes.diagnostico', 'LIKE', $b);
            });
        }

        $expedientes = $query->orderByDesc('expedientes.creado_en')->paginate(20)->withQueryString();

        $base = match(auth()->user()->rol) {
            'dentista'  => 'dentista',
            'asistente' => 'asistente',
            default     => 'admin',
        };

        return view('expedientes.index', compact('expedientes', 'base'));
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        // Solo el doctor (o el admin) puede registrar el diagnóstico clínico
        if (auth()->user()->rol === 'asistente') abort(403);

        $pacienteId = $request->get('paciente_id');

        $paciente = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('pacientes.id', $pacienteId)
            ->select('pacientes.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
            ->first();

        if (!$paciente) abort(404);

        // Citas completadas del paciente sin expediente aún
        $citas = DB::table('citas')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->leftJoin('expedientes', 'expedientes.cita_id', '=', 'citas.id')
            ->where('citas.paciente_id', $pacienteId)
            ->whereIn('citas.estado', ['completada', 'en_curso', 'confirmada'])
            ->whereNull('expedientes.id')
            ->select('citas.id', 'citas.fecha_hora', 'servicios.nombre AS servicio')
            ->orderByDesc('citas.fecha_hora')
            ->get();

        // Dentistas disponibles
        $dentistas = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre"))
            ->orderBy('usuarios.nombre')
            ->get();

        // Si el usuario es dentista, preseleccionar
        $dentistaPreseleccionado = null;
        if (auth()->user()->rol === 'dentista') {
            $dentistaPreseleccionado = DB::table('personal')
                ->where('usuario_id', auth()->id())
                ->value('id');
        }

        return view('expedientes.create', compact('paciente', 'citas', 'dentistas', 'dentistaPreseleccionado'));
    }

    // ── STORE ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        if (auth()->user()->rol === 'asistente') abort(403);

        $data = $request->validate([
            'paciente_id'    => 'required|exists:pacientes,id',
            'dentista_id'    => 'required|exists:personal,id',
            'cita_id'        => 'nullable|exists:citas,id',
            'diagnostico'    => 'required|string|max:2000',
            'tratamiento'    => 'nullable|string|max:2000',
            'receta'         => 'nullable|string|max:2000',
            'observaciones'  => 'nullable|string|max:2000',
            'proxima_visita' => 'nullable|date|after:today',
        ], [
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
        ]);

        $id = (string) Str::uuid();

        DB::table('expedientes')->insert([
            'id'             => $id,
            'paciente_id'    => $data['paciente_id'],
            'dentista_id'    => $data['dentista_id'],
            'cita_id'        => $data['cita_id'] ?: null,
            'diagnostico'    => $data['diagnostico'],
            'tratamiento'    => $data['tratamiento'] ?? null,
            'receta'         => $data['receta'] ?? null,
            'observaciones'  => $data['observaciones'] ?? null,
            'proxima_visita' => $data['proxima_visita'] ?? null,
            'archivos_url'   => null,
            'creado_en'      => now(),
        ]);

        // Redirigir al rol correcto
        $base = match(auth()->user()->rol) {
            'dentista'  => 'dentista',
            'asistente' => 'asistente',
            default     => 'admin',
        };

        return redirect()->route("{$base}.pacientes.show", $data['paciente_id'])
            ->with('success', 'Expediente registrado correctamente.');
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id)
    {
        $expediente = DB::table('expedientes')
            ->join('pacientes', 'expedientes.paciente_id', '=', 'pacientes.id')
            ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
            ->join('personal', 'expedientes.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('citas', 'expedientes.cita_id', '=', 'citas.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->where('expedientes.id', $id)
            ->select(
                'expedientes.*',
                DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                'up.telefono AS tel_paciente',
                'pacientes.id AS paciente_id',
                'pacientes.alergias',
                'pacientes.medicamentos_actuales',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'servicios.nombre AS servicio',
                'citas.fecha_hora AS fecha_cita'
            )
            ->first();

        if (!$expediente) abort(404);

        $base = match(auth()->user()->rol) {
            'dentista'  => 'dentista',
            'asistente' => 'asistente',
            default     => 'admin',
        };

        return view('expedientes.show', compact('expediente', 'base'));
    }

    // ── UPDATE ─────────────────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        if (auth()->user()->rol === 'asistente') abort(403);

        $data = $request->validate([
            'diagnostico'    => 'required|string|max:2000',
            'tratamiento'    => 'nullable|string|max:2000',
            'receta'         => 'nullable|string|max:2000',
            'observaciones'  => 'nullable|string|max:2000',
            'proxima_visita' => 'nullable|date',
        ]);

        DB::table('expedientes')->where('id', $id)->update($data);

        return back()->with('success', 'Expediente actualizado.');
    }
}