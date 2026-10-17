<?php

namespace App\Http\Controllers;

<<<<<<< Updated upstream
use App\Agenda\HorarioDentista;
use App\Agenda\HorarioNoGuardado;
use App\Rules\HoraFinPosterior;
=======
>>>>>>> Stashed changes
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
            $b = '%'.$request->buscar.'%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) LIKE ?", [$b])
                    ->orWhere('usuarios.email', 'LIKE', $b)
                    ->orWhere('personal.especialidad', 'LIKE', $b);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('personal.tipo', $request->tipo);
        }

        $personal = $query->orderBy('personal.tipo')->orderBy('usuarios.nombre')->get();

        // Agrupar por tipo para mostrar secciones
        $grupos = $personal->groupBy('tipo');

        $totales = [
            'dentista' => $personal->where('tipo', 'dentista')->count(),
            'asistente' => $personal->where('tipo', 'asistente')->count(),
            'administrador' => $personal->where('tipo', 'administrador')->count(),
        ];

        return view('personal.index', compact('personal', 'grupos', 'totales'));
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create()
    {
        $dentistas = $this->dentistasDisponibles();

        return view('personal.create', compact('dentistas'));
    }

    // ── STORE ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'tipo' => 'required|in:dentista,asistente,administrador',
            'especialidad' => 'nullable|string|max:150',
            'numero_cedula' => 'nullable|string|max:50',
            'universidad' => 'nullable|string|max:150',
            'anios_experiencia' => 'nullable|integer|min:0|max:60',
            'biografia' => 'nullable|string|max:1000',
            'dentistas' => 'nullable|array',
            'dentistas.*' => 'exists:personal,id',
        ], [
            'email.unique' => 'Este correo ya está registrado.',
            'tipo.required' => 'Selecciona el tipo de personal.',
        ]);

        $passwordPlain = ! empty($data['password']) ? $data['password'] : Str::random(10);
        $passwordGenerada = empty($data['password']);

        // El rol en usuarios corresponde al tipo de personal
        $rol = match ($data['tipo']) {
            'dentista' => 'dentista',
            'asistente' => 'asistente',
            'administrador' => 'admin',
        };

        DB::transaction(function () use ($data, $passwordPlain, $rol) {
            $usuarioId = (string) Str::uuid();

            DB::table('usuarios')->insert([
                'id' => $usuarioId,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $data['email'],
                'password_hash' => Hash::make($passwordPlain),
                'rol' => $rol,
                'telefono' => $data['telefono'] ?? null,
                'activo' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            $personalId = (string) Str::uuid();

            DB::table('personal')->insert([
                'id' => $personalId,
                'usuario_id' => $usuarioId,
                'tipo' => $data['tipo'],
                'especialidad' => $data['especialidad'] ?? null,
                'numero_cedula' => $data['numero_cedula'] ?? null,
                'universidad' => $data['universidad'] ?? null,
                'anios_experiencia' => $data['anios_experiencia'] ?? 0,
                'biografia' => $data['biografia'] ?? null,
                'creado_en' => now(),
            ]);

            if ($data['tipo'] === 'asistente') {
                $this->sincronizarDoctoresAsignados($personalId, $data['dentistas'] ?? []);
            }
        });

        if ($passwordGenerada) {
            return redirect()->route('admin.personal')
                ->with('password_generada', [
                    'nombre' => $data['nombre'].' '.$data['apellido'],
                    'email' => $data['email'],
                    'password' => $passwordPlain,
                ]);
        }

        return redirect()->route('admin.personal')
            ->with('success', 'Personal registrado correctamente.');
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id, HorarioDentista $horarioDentista)
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

        if (! $miembro) {
            abort(404);
        }

        // Horarios
        $horarios = DB::table('horarios_dentista')
            ->where('personal_id', $id)
            ->orderBy('dia_semana')
            ->get()
            ->keyBy('dia_semana');

        // Días de la semana
        $dias = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
        ];

        // Citas recientes (solo para dentistas)
        $citasRecientes = collect();
        $totalCitas = 0;
        $citasEsteMes = 0;

        // Doctores que auxilia (solo para asistentes) / Asistentes a cargo (solo para dentistas)
        $doctoresAsignados = collect();
        $asistentesAsignados = collect();

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

            $totalCitas = DB::table('citas')->where('dentista_id', $id)->count();
            $citasEsteMes = DB::table('citas')
                ->where('dentista_id', $id)
                ->whereMonth('fecha_hora', now()->month)
                ->whereYear('fecha_hora', now()->year)
                ->count();

            $asistentesAsignados = DB::table('asistente_dentista')
                ->join('personal', 'asistente_dentista.asistente_id', '=', 'personal.id')
                ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
                ->where('asistente_dentista.dentista_id', $id)
                ->select('personal.id', 'usuarios.nombre', 'usuarios.apellido', 'usuarios.activo',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
                ->orderBy('usuarios.nombre')
                ->get();
        }

        if ($miembro->tipo === 'asistente') {
            $doctoresAsignados = DB::table('asistente_dentista')
                ->join('personal', 'asistente_dentista.dentista_id', '=', 'personal.id')
                ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
                ->where('asistente_dentista.asistente_id', $id)
                ->select('personal.id', 'personal.especialidad', 'usuarios.nombre', 'usuarios.apellido', 'usuarios.activo',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
                ->orderBy('usuarios.nombre')
                ->get();
        }

        // Horario y citas fuera de él: solo si la administración puede definirlo (spec 027).
        $puedeDefinirHorario = Gate::allows('definir-horario', $miembro);
        $citasFuera = $puedeDefinirHorario ? $horarioDentista->citasFuera($id) : collect();

        return view('personal.show', compact(
            'miembro', 'horarios', 'dias', 'puedeDefinirHorario', 'citasFuera',
            'citasRecientes', 'totalCitas', 'citasEsteMes',
            'doctoresAsignados', 'asistentesAsignados'
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

        if (! $miembro) {
            abort(404);
        }

        $dentistas = $this->dentistasDisponibles();

        $dentistasAsignados = DB::table('asistente_dentista')
            ->where('asistente_id', $id)
            ->pluck('dentista_id')
            ->all();

        return view('personal.edit', compact('miembro', 'dentistas', 'dentistasAsignados'));
    }

    // ── UPDATE ─────────────────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        $miembro = DB::table('personal')->where('id', $id)->first();
        if (! $miembro) {
            abort(404);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => "required|email|unique:usuarios,email,{$miembro->usuario_id},id",
            'telefono' => 'nullable|string|max:20',
            'tipo' => 'required|in:dentista,asistente,administrador',
            'especialidad' => 'nullable|string|max:150',
            'numero_cedula' => 'nullable|string|max:50',
            'universidad' => 'nullable|string|max:150',
            'anios_experiencia' => 'nullable|integer|min:0|max:60',
            'biografia' => 'nullable|string|max:1000',
            'dentistas' => 'nullable|array',
            'dentistas.*' => 'exists:personal,id',
        ]);

        $rol = match ($data['tipo']) {
            'dentista' => 'dentista',
            'asistente' => 'asistente',
            'administrador' => 'admin',
        };

        DB::transaction(function () use ($data, $miembro, $rol) {
            DB::table('usuarios')->where('id', $miembro->usuario_id)->update([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'rol' => $rol,
                'actualizado_en' => now(),
            ]);

            DB::table('personal')->where('id', $miembro->id)->update([
                'tipo' => $data['tipo'],
                'especialidad' => $data['especialidad'] ?? null,
                'numero_cedula' => $data['numero_cedula'] ?? null,
                'universidad' => $data['universidad'] ?? null,
                'anios_experiencia' => $data['anios_experiencia'] ?? 0,
                'biografia' => $data['biografia'] ?? null,
            ]);

            if ($data['tipo'] === 'asistente') {
                $this->sincronizarDoctoresAsignados($miembro->id, $data['dentistas'] ?? []);
            } else {
                // Si deja de ser asistente, se liberan las asignaciones que tenía
                DB::table('asistente_dentista')->where('asistente_id', $miembro->id)->delete();
            }
        });

        return redirect()->route('admin.personal.show', $id)
            ->with('success', 'Datos actualizados correctamente.');
    }

    // ── TOGGLE ACTIVO ──────────────────────────────────────────────────
    public function toggleActivo(string $id)
    {
        $miembro = DB::table('personal')->where('id', $id)->first();
        if (! $miembro) {
            abort(404);
        }

        $activo = DB::table('usuarios')->where('id', $miembro->usuario_id)->value('activo');
        DB::table('usuarios')->where('id', $miembro->usuario_id)->update([
            'activo' => $activo ? 0 : 1,
            'actualizado_en' => now(),
        ]);

        return back()->with('success', $activo ? 'Miembro desactivado.' : 'Miembro reactivado.');
    }

    // ── GUARDAR HORARIOS ───────────────────────────────────────────────
    public function saveHorarios(Request $request, string $id, HorarioDentista $horarioDentista)
    {
        $miembro = DB::table('personal')->where('id', $id)->first();
        if (! $miembro) {
            abort(404);
        }

        // Solo la administración, y solo sobre un dentista (otro tipo → 404).
        Gate::authorize('definir-horario', $miembro);

        $validados = $request->validate(self::reglasDeHorario(), self::mensajesDeHorario());

<<<<<<< Updated upstream
        $franjas = [];
        foreach ($validados['dias'] ?? [] as $dia => $info) {
            if (isset($info['activo'])) {
                $franjas[(int) $dia] = ['inicio' => $info['hora_inicio'], 'fin' => $info['hora_fin']];
=======
            foreach ($dias as $dia => $info) {
                if (! isset($info['activo'])) {
                    continue;
                }

                DB::table('horarios_dentista')->insert([
                    'id' => (string) Str::uuid(),
                    'personal_id' => $id,
                    'dia_semana' => (int) $dia,
                    'hora_inicio' => $info['hora_inicio'] ?? '09:00',
                    'hora_fin' => $info['hora_fin'] ?? '18:00',
                    'disponible' => 1,
                ]);
>>>>>>> Stashed changes
            }
        }

        try {
            $fuera = $horarioDentista->guardar($id, $franjas);
        } catch (HorarioNoGuardado $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $this->mensajeDeHorarioGuardado($franjas !== [], $fuera));
    }

    /**
     * Reglas del horario semanal (spec 027): días 1–7, solo los campos del
     * formulario, horas H:i obligatorias en los días marcados y fin posterior al
     * inicio. El orden de las horas lo comprueba HoraFinPosterior, no `after:` (A5).
     */
    private static function reglasDeHorario(): array
    {
        return [
            'dias' => ['nullable', 'array:1,2,3,4,5,6,7'],
            'dias.*' => ['array:activo,hora_inicio,hora_fin'],
            'dias.*.activo' => ['nullable', 'in:1'],
            'dias.*.hora_inicio' => ['nullable', 'required_with:dias.*.activo', 'date_format:H:i'],
            'dias.*.hora_fin' => ['nullable', 'required_with:dias.*.activo', 'date_format:H:i', new HoraFinPosterior],
        ];
    }

    private static function mensajesDeHorario(): array
    {
        $noValido = 'El horario enviado no es válido.';
        $formato = 'Escribe la hora con el formato HH:MM.';

        return [
            'dias.array' => $noValido,
            'dias.*.array' => $noValido,
            'dias.*.activo.in' => $noValido,
            'dias.*.hora_inicio.required_with' => 'Indica la hora de inicio de este día.',
            'dias.*.hora_inicio.date_format' => $formato,
            'dias.*.hora_fin.required_with' => 'Indica la hora de fin de este día.',
            'dias.*.hora_fin.date_format' => $formato,
        ];
    }

    /** Mensaje tras guardar: base (con o sin días) y, si hay citas fuera, su recuento. */
    private function mensajeDeHorarioGuardado(bool $conDias, int $fuera): string
    {
        $mensaje = $conDias
            ? 'Horario guardado.'
            : 'Horario guardado sin días de atención: no se podrán agendar citas con este dentista hasta que tenga horario.';

        if ($fuera === 1) {
            $mensaje .= ' 1 cita quedó fuera del horario: revísala en «Citas fuera del horario».';
        } elseif ($fuera > 1) {
            $mensaje .= " {$fuera} citas quedaron fuera del horario: revísalas en «Citas fuera del horario».";
        }

        return $mensaje;
    }

    // ── MIS DOCTORES (vista del asistente) ────────────────────────────
    public function misDoctores()
    {
        $personal = DB::table('personal')
            ->where('usuario_id', auth()->id())
            ->first();

        $doctores = collect();

        if ($personal) {
            $doctores = DB::table('asistente_dentista')
                ->join('personal', 'asistente_dentista.dentista_id', '=', 'personal.id')
                ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
                ->where('asistente_dentista.asistente_id', $personal->id)
                ->select(
                    'personal.id', 'personal.especialidad', 'personal.numero_cedula',
                    'usuarios.nombre', 'usuarios.apellido', 'usuarios.email',
                    'usuarios.telefono', 'usuarios.activo',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo")
                )
                ->orderBy('usuarios.nombre')
                ->get();

            $hoy = Carbon::today();
            foreach ($doctores as $doc) {
                $doc->citas_hoy = DB::table('citas')
                    ->where('dentista_id', $doc->id)
                    ->whereDate('fecha_hora', $hoy)
                    ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                    ->count();
            }
        }

        return view('personal.mis-doctores', compact('doctores'));
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

    private function sincronizarDoctoresAsignados(string $asistenteId, array $dentistaIds): void
    {
        DB::table('asistente_dentista')->where('asistente_id', $asistenteId)->delete();

        foreach (array_unique($dentistaIds) as $dentistaId) {
            DB::table('asistente_dentista')->insert([
                'id' => (string) Str::uuid(),
                'asistente_id' => $asistenteId,
                'dentista_id' => $dentistaId,
                'creado_en' => now(),
            ]);
        }
    }
}
