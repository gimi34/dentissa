<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PersonalController extends Controller
{
    // ── INDEX ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->select(
                'personal.id',
                'personal.usuario_id',
                'personal.tipo',
                'personal.especialidad',
                'personal.numero_cedula',
                'personal.anios_experiencia',
                'usuarios.nombre',
                'usuarios.apellido',
                'usuarios.email',
                'usuarios.telefono',
                'usuarios.activo',
                'usuarios.foto_url',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo")
            );

        if ($request->filled('buscar')) {
            $b = '%' . $request->buscar . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) LIKE ?", [$b])
                  ->orWhere('usuarios.email',       'LIKE', $b)
                  ->orWhere('personal.especialidad','LIKE', $b);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('personal.tipo', $request->tipo);
        }

        $personal = $query->orderBy('personal.tipo')->orderBy('usuarios.nombre')->get();

        // Agrupar por tipo para mostrar secciones
        $grupos = $personal->groupBy('tipo');

        $totales = [
            'dentista'       => $personal->where('tipo', 'dentista')->count(),
            'asistente'      => $personal->where('tipo', 'asistente')->count(),
            'administrador'  => $personal->where('tipo', 'administrador')->count(),
        ];

        return view('personal.index', compact('personal', 'grupos', 'totales'));
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create()
    {
        return view('personal.create');
    }

    // ── STORE ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'email'            => 'required|email|unique:usuarios,email',
            'telefono'         => 'nullable|string|max:20',
            'password'         => 'nullable|string|min:6',
            'tipo'             => 'required|in:dentista,asistente,administrador',
            'especialidad'     => 'nullable|string|max:150',
            'numero_cedula'    => 'nullable|string|max:50',
            'universidad'      => 'nullable|string|max:150',
            'anios_experiencia'=> 'nullable|integer|min:0|max:60',
            'biografia'        => 'nullable|string|max:1000',
        ], [
            'email.unique' => 'Este correo ya está registrado.',
            'tipo.required'=> 'Selecciona el tipo de personal.',
        ]);

        $passwordPlain    = !empty($data['password']) ? $data['password'] : Str::random(10);
        $passwordGenerada = empty($data['password']);

        // El rol en usuarios corresponde al tipo de personal
        $rol = match($data['tipo']) {
            'dentista'      => 'dentista',
            'asistente'     => 'asistente',
            'administrador' => 'admin',
        };

        DB::transaction(function () use ($data, $passwordPlain, $rol) {
            $usuarioId = (string) Str::uuid();

            DB::table('usuarios')->insert([
                'id'             => $usuarioId,
                'nombre'         => $data['nombre'],
                'apellido'       => $data['apellido'],
                'email'          => $data['email'],
                'password_hash'  => Hash::make($passwordPlain),
                'rol'            => $rol,
                'telefono'       => $data['telefono'] ?? null,
                'activo'         => 1,
                'creado_en'      => now(),
                'actualizado_en' => now(),
            ]);

            DB::table('personal')->insert([
                'id'                => (string) Str::uuid(),
                'usuario_id'        => $usuarioId,
                'tipo'              => $data['tipo'],
                'especialidad'      => $data['especialidad'] ?? null,
                'numero_cedula'     => $data['numero_cedula'] ?? null,
                'universidad'       => $data['universidad'] ?? null,
                'anios_experiencia' => $data['anios_experiencia'] ?? 0,
                'biografia'         => $data['biografia'] ?? null,
                'creado_en'         => now(),
            ]);
        });

        if ($passwordGenerada) {
            return redirect()->route('admin.personal')
                ->with('password_generada', [
                    'nombre'   => $data['nombre'] . ' ' . $data['apellido'],
                    'email'    => $data['email'],
                    'password' => $passwordPlain,
                ]);
        }

        return redirect()->route('admin.personal')
            ->with('success', 'Personal registrado correctamente.');
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id)
    {
        $miembro = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.id', $id)
            ->select(
                'personal.*',
                'usuarios.nombre', 'usuarios.apellido', 'usuarios.email',
                'usuarios.telefono', 'usuarios.activo', 'usuarios.foto_url',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo")
            )
            ->first();

        if (!$miembro) abort(404);

        // Horarios
        $horarios = DB::table('horarios_dentista')
            ->where('personal_id', $id)
            ->orderBy('dia_semana')
            ->get()
            ->keyBy('dia_semana');

        // Días de la semana
        $dias = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        ];

        // Citas recientes (solo para dentistas)
        $citasRecientes = collect();
        $totalCitas     = 0;
        $citasEsteMes   = 0;

        if ($miembro->tipo === 'dentista') {
            $citasRecientes = DB::table('citas')
                ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
                ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
                ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
                ->where('citas.dentista_id', $id)
                ->select(
                    'citas.id', 'citas.fecha_hora', 'citas.estado',
                    DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                    'servicios.nombre AS servicio'
                )
                ->orderByDesc('citas.fecha_hora')
                ->limit(8)
                ->get();

            $totalCitas   = DB::table('citas')->where('dentista_id', $id)->count();
            $citasEsteMes = DB::table('citas')
                ->where('dentista_id', $id)
                ->whereMonth('fecha_hora', now()->month)
                ->whereYear('fecha_hora', now()->year)
                ->count();
        }

        return view('personal.show', compact(
            'miembro', 'horarios', 'dias',
            'citasRecientes', 'totalCitas', 'citasEsteMes'
        ));
    }

    // ── EDIT ───────────────────────────────────────────────────────────
    public function edit(string $id)
    {
        $miembro = DB::table('personal')
            ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
            ->where('personal.id', $id)
            ->select('personal.*', 'usuarios.nombre', 'usuarios.apellido',
                     'usuarios.email', 'usuarios.telefono')
            ->first();

        if (!$miembro) abort(404);

        return view('personal.edit', compact('miembro'));
    }

    // ── UPDATE ─────────────────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        $miembro = DB::table('personal')->where('id', $id)->first();
        if (!$miembro) abort(404);

        $data = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'email'            => "required|email|unique:usuarios,email,{$miembro->usuario_id},id",
            'telefono'         => 'nullable|string|max:20',
            'tipo'             => 'required|in:dentista,asistente,administrador',
            'especialidad'     => 'nullable|string|max:150',
            'numero_cedula'    => 'nullable|string|max:50',
            'universidad'      => 'nullable|string|max:150',
            'anios_experiencia'=> 'nullable|integer|min:0|max:60',
            'biografia'        => 'nullable|string|max:1000',
        ]);

        $rol = match($data['tipo']) {
            'dentista'      => 'dentista',
            'asistente'     => 'asistente',
            'administrador' => 'admin',
        };

        DB::transaction(function () use ($data, $miembro, $rol) {
            DB::table('usuarios')->where('id', $miembro->usuario_id)->update([
                'nombre'         => $data['nombre'],
                'apellido'       => $data['apellido'],
                'email'          => $data['email'],
                'telefono'       => $data['telefono'] ?? null,
                'rol'            => $rol,
                'actualizado_en' => now(),
            ]);

            DB::table('personal')->where('id', $miembro->id)->update([
                'tipo'              => $data['tipo'],
                'especialidad'      => $data['especialidad'] ?? null,
                'numero_cedula'     => $data['numero_cedula'] ?? null,
                'universidad'       => $data['universidad'] ?? null,
                'anios_experiencia' => $data['anios_experiencia'] ?? 0,
                'biografia'         => $data['biografia'] ?? null,
            ]);
        });

        return redirect()->route('admin.personal.show', $id)
            ->with('success', 'Datos actualizados correctamente.');
    }

    // ── TOGGLE ACTIVO ──────────────────────────────────────────────────
    public function toggleActivo(string $id)
    {
        $miembro = DB::table('personal')->where('id', $id)->first();
        if (!$miembro) abort(404);

        $activo = DB::table('usuarios')->where('id', $miembro->usuario_id)->value('activo');
        DB::table('usuarios')->where('id', $miembro->usuario_id)->update([
            'activo'         => $activo ? 0 : 1,
            'actualizado_en' => now(),
        ]);

        return back()->with('success', $activo ? 'Miembro desactivado.' : 'Miembro reactivado.');
    }

    // ── GUARDAR HORARIOS ───────────────────────────────────────────────
    public function saveHorarios(Request $request, string $id)
    {
        $miembro = DB::table('personal')->where('id', $id)->first();
        if (!$miembro) abort(404);

        $dias = $request->input('dias', []);

        DB::transaction(function () use ($id, $dias) {
            // Borrar horarios actuales
            DB::table('horarios_dentista')->where('personal_id', $id)->delete();

            foreach ($dias as $dia => $info) {
                if (!isset($info['activo'])) continue;

                DB::table('horarios_dentista')->insert([
                    'id'          => (string) Str::uuid(),
                    'personal_id' => $id,
                    'dia_semana'  => (int) $dia,
                    'hora_inicio' => $info['hora_inicio'] ?? '09:00',
                    'hora_fin'    => $info['hora_fin']    ?? '18:00',
                    'disponible'  => 1,
                ]);
            }
        });

        return back()->with('success', 'Horarios guardados correctamente.');
    }
}