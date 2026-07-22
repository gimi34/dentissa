<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PacientesController extends Controller
{
    
    public function index(Request $request)
    {
        $query = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->leftJoin('personal', 'pacientes.dentista_id', '=', 'personal.id')
            ->leftJoin('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->where('usuarios.activo', 1)
            ->select(
                'pacientes.id',
                'pacientes.usuario_id',
                'pacientes.dentista_id',
                'pacientes.fecha_nacimiento',
                'pacientes.tipo_sangre',
                'pacientes.alergias',
                'pacientes.ciudad',
                'pacientes.creado_en',
                'usuarios.nombre',
                'usuarios.apellido',
                'usuarios.email',
                'usuarios.telefono',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"),
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista_nombre")
            );

        if ($request->filled('buscar')) {
            $b = '%' . $request->buscar . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) LIKE ?", [$b])
                  ->orWhere('usuarios.email',    'LIKE', $b)
                  ->orWhere('usuarios.telefono', 'LIKE', $b)
                  ->orWhere('pacientes.ciudad',  'LIKE', $b);
            });
        }

        if ($request->filled('ciudad')) {
            $query->where('pacientes.ciudad', $request->ciudad);
        }

        if ($request->filled('dentista_id')) {
            $query->where('pacientes.dentista_id', $request->dentista_id);
        }

        $pacientes = $query->orderBy('usuarios.nombre')->paginate(20)->withQueryString();

        // Total y ciudades para filtros
        $totalPacientes = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.activo', 1)->count();

        $ciudades = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.activo', 1)
            ->whereNotNull('pacientes.ciudad')
            ->where('pacientes.ciudad', '!=', '')
            ->distinct()->pluck('pacientes.ciudad')->sort()->values();

        $dentistas = $this->dentistasDisponibles();

        return view('pacientes.index', compact('pacientes', 'totalPacientes', 'ciudades', 'dentistas'));
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create()
    {
        $dentistas = $this->dentistasDisponibles();

        return view('pacientes.create', compact('dentistas'));
    }

    // ── STORE ──────────────────────────────────────────────────────────
public function store(Request $request)
{
    $data = $request->validate([
        'nombre'    => 'required|string|max:100',
        'apellido'  => 'required|string|max:100',
        'email'     => 'required|email|unique:usuarios,email',
        'telefono'  => 'nullable|string|max:20',
        'password'  => 'nullable|string|min:6',

        'fecha_nacimiento'      => 'nullable|date|before:today',
        'genero'                => 'nullable|string|max:20',
        'direccion'             => 'nullable|string|max:500',
        'ciudad'                => 'nullable|string|max:100',
        'tipo_sangre'           => 'nullable|string|max:5',
        'alergias'              => 'nullable|string|max:1000',
        'enfermedades_cronicas' => 'nullable|string|max:1000',
        'medicamentos_actuales' => 'nullable|string|max:500',
        'notas_medicas'         => 'nullable|string|max:2000',
        'contacto_emergencia'   => 'nullable|string|max:200',
        'telefono_emergencia'   => 'nullable|string|max:20',
        'dentista_id'           => 'nullable|exists:personal,id',
    ], [
        'email.unique' => 'Este correo ya está registrado en el sistema.',
    ]);

    // Genera o usa la contraseña ingresada
    $passwordPlain    = !empty($data['password']) ? $data['password'] : Str::random(10);
    $passwordGenerada = empty($data['password']);

    DB::transaction(function () use ($data, $passwordPlain) {
        $usuarioId = (string) Str::uuid();

        // 1. Crear usuario
        DB::table('usuarios')->insert([
            'id'             => $usuarioId,
            'nombre'         => $data['nombre'],
            'apellido'       => $data['apellido'],
            'email'          => $data['email'],
            'password_hash'  => Hash::make($passwordPlain),
            'rol'            => 'cliente',
            'telefono'       => $data['telefono'] ?? null,
            'activo'         => 1,
            'creado_en'      => now(),
            'actualizado_en' => now(),
        ]);

        // 2. Crear perfil de paciente
        DB::table('pacientes')->insert([
            'id'                    => (string) Str::uuid(),
            'usuario_id'            => $usuarioId,
            'dentista_id'           => $data['dentista_id'] ?? null,
            'fecha_nacimiento'      => $data['fecha_nacimiento'] ?? null,
            'genero'                => $data['genero'] ?? null,
            'direccion'             => $data['direccion'] ?? null,
            'ciudad'                => $data['ciudad'] ?? null,
            'tipo_sangre'           => $data['tipo_sangre'] ?? null,
            'alergias'              => $data['alergias'] ?? null,
            'enfermedades_cronicas' => $data['enfermedades_cronicas'] ?? null,
            'medicamentos_actuales' => $data['medicamentos_actuales'] ?? null,
            'notas_medicas'         => $data['notas_medicas'] ?? null,
            'contacto_emergencia'   => $data['contacto_emergencia'] ?? null,
            'telefono_emergencia'   => $data['telefono_emergencia'] ?? null,
            'creado_en'             => now(),
        ]);
    });

    $rutaListado = auth()->user()->rol === 'admin' ? 'admin.pacientes' : 'asistente.pacientes';

    if ($passwordGenerada) {
        return redirect()->route($rutaListado)
            ->with('password_generada', [
                'nombre'   => $data['nombre'] . ' ' . $data['apellido'],
                'email'    => $data['email'],
                'password' => $passwordPlain,
            ]);
    }

    return redirect()->route($rutaListado)
        ->with('success', 'Paciente registrado correctamente.');
}

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id)
    {
        $paciente = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->leftJoin('personal', 'pacientes.dentista_id', '=', 'personal.id')
            ->leftJoin('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->where('pacientes.id', $id)
            ->select('pacientes.*', 'usuarios.nombre', 'usuarios.apellido',
                     'usuarios.email', 'usuarios.telefono', 'usuarios.activo',
                     DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"),
                     DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista_nombre"))
            ->first();

        if (!$paciente) abort(404);

        // Expedientes del paciente
        $expedientes = DB::table('expedientes')
            ->join('personal', 'expedientes.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('citas', 'expedientes.cita_id', '=', 'citas.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->where('expedientes.paciente_id', $id)
            ->select(
                'expedientes.*',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'servicios.nombre AS servicio',
                'citas.fecha_hora AS fecha_cita'
            )
            ->orderByDesc('expedientes.creado_en')
            ->get();

        // Citas del paciente
        $citas = DB::table('citas')
            ->join('personal', 'citas.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->where('citas.paciente_id', $id)
            ->select(
                'citas.id', 'citas.fecha_hora', 'citas.estado', 'citas.duracion_min',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'servicios.nombre AS servicio'
            )
            ->orderByDesc('citas.fecha_hora')
            ->limit(10)
            ->get();

        // Stats rápidas
        $totalCitas       = DB::table('citas')->where('paciente_id', $id)->count();
        $citasCompletadas = DB::table('citas')->where('paciente_id', $id)->where('estado', 'completada')->count();
        $proximaCita      = DB::table('citas')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->where('citas.paciente_id', $id)
            ->where('citas.fecha_hora', '>=', now())
            ->whereNotIn('citas.estado', ['cancelada', 'no_asistio'])
            ->select('citas.fecha_hora', 'servicios.nombre AS servicio')
            ->orderBy('citas.fecha_hora')
            ->first();

        return view('pacientes.show', compact(
            'paciente', 'expedientes', 'citas',
            'totalCitas', 'citasCompletadas', 'proximaCita'
        ));
    }

    // ── EDIT ───────────────────────────────────────────────────────────
    public function edit(string $id)
    {
        // El asistente no puede modificar la información del paciente
        if (auth()->user()->rol === 'asistente') abort(403);

        $paciente = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('pacientes.id', $id)
            ->select('pacientes.*', 'usuarios.nombre', 'usuarios.apellido',
                     'usuarios.email', 'usuarios.telefono')
            ->first();

        if (!$paciente) abort(404);

        $dentistas = $this->dentistasDisponibles();

        return view('pacientes.edit', compact('paciente', 'dentistas'));
    }

    // ── UPDATE ─────────────────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        if (auth()->user()->rol === 'asistente') abort(403);

        $paciente = DB::table('pacientes')->where('id', $id)->first();
        if (!$paciente) abort(404);

        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'email'     => "required|email|unique:usuarios,email,{$paciente->usuario_id},id",
            'telefono'  => 'nullable|string|max:20',

            'fecha_nacimiento'      => 'nullable|date|before:today',
            'genero'                => 'nullable|string|max:20',
            'direccion'             => 'nullable|string|max:500',
            'ciudad'                => 'nullable|string|max:100',
            'tipo_sangre'           => 'nullable|string|max:5',
            'alergias'              => 'nullable|string|max:1000',
            'enfermedades_cronicas' => 'nullable|string|max:1000',
            'medicamentos_actuales' => 'nullable|string|max:500',
            'notas_medicas'         => 'nullable|string|max:2000',
            'contacto_emergencia'   => 'nullable|string|max:200',
            'telefono_emergencia'   => 'nullable|string|max:20',
            'dentista_id'           => 'nullable|exists:personal,id',
        ]);

        DB::transaction(function () use ($data, $paciente) {
            DB::table('usuarios')->where('id', $paciente->usuario_id)->update([
                'nombre'         => $data['nombre'],
                'apellido'       => $data['apellido'],
                'email'          => $data['email'],
                'telefono'       => $data['telefono'] ?? null,
                'actualizado_en' => now(),
            ]);

            DB::table('pacientes')->where('id', $paciente->id)->update([
                'dentista_id'           => $data['dentista_id'] ?? null,
                'fecha_nacimiento'      => $data['fecha_nacimiento'] ?? null,
                'genero'                => $data['genero'] ?? null,
                'direccion'             => $data['direccion'] ?? null,
                'ciudad'                => $data['ciudad'] ?? null,
                'tipo_sangre'           => $data['tipo_sangre'] ?? null,
                'alergias'              => $data['alergias'] ?? null,
                'enfermedades_cronicas' => $data['enfermedades_cronicas'] ?? null,
                'medicamentos_actuales' => $data['medicamentos_actuales'] ?? null,
                'notas_medicas'         => $data['notas_medicas'] ?? null,
                'contacto_emergencia'   => $data['contacto_emergencia'] ?? null,
                'telefono_emergencia'   => $data['telefono_emergencia'] ?? null,
            ]);
        });

        $base = auth()->user()->rol === 'admin' ? 'admin' : 'dentista';

        return redirect()->route("{$base}.pacientes.show", $id)
            ->with('success', 'Datos del paciente actualizados.');
    }

    // ── TOGGLE ACTIVO ──────────────────────────────────────────────────
    public function toggleActivo(string $id)
    {
        $paciente = DB::table('pacientes')->where('id', $id)->first();
        if (!$paciente) abort(404);

        $activo = DB::table('usuarios')->where('id', $paciente->usuario_id)->value('activo');

        DB::table('usuarios')->where('id', $paciente->usuario_id)->update([
            'activo'         => $activo ? 0 : 1,
            'actualizado_en' => now(),
        ]);

        $msg = $activo ? 'Paciente desactivado.' : 'Paciente reactivado.';
        return back()->with('success', $msg);
    }

    // ── HELPERS ────────────────────────────────────────────────────────
    private function dentistasDisponibles()
    {
        return DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.tipo', 'dentista')
            ->where('usuarios.activo', 1)
            ->select('personal.id', 'personal.especialidad',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
            ->orderBy('usuarios.nombre')
            ->get();
    }
}