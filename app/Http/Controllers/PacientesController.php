<?php

namespace App\Http\Controllers;

use App\Agenda\RelojClinica;
use App\Auditoria\Auditoria;
use App\Pacientes\Ciudades;
use App\Pacientes\PosiblesDuplicados;
use App\Rules\CiudadDeLaLista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PacientesController extends Controller
{
    /** Spec 054 (CA1): confirmación de un alta sin correo. */
    public const ALTA_SIN_ACCESO = 'Paciente registrado sin correo: no tiene acceso a la aplicación.';

    /** Spec 054 (CA2, CA5): sin correo, el teléfono es el único contacto. */
    public const TELEFONO_OBLIGATORIO = 'Sin correo, el teléfono es obligatorio para poder contactarle.';

    /** Spec 054 (CA5): a quien ya tiene correo no se le puede quitar, porque es su acceso. */
    public const CORREO_OBLIGATORIO = 'El correo es obligatorio: es el acceso del paciente a la aplicación.';

    /** Spec 054 (/review R4): con ese correo, la recuperación de la 041 daría la cuenta del personal. */
    public const CORREO_DEL_PERSONAL = 'El correo de una cuenta del personal no se cambia desde Pacientes.';

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
            $b = '%'.$request->buscar.'%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) LIKE ?", [$b])
                    ->orWhere('usuarios.email', 'LIKE', $b)
                    ->orWhere('usuarios.telefono', 'LIKE', $b)
                    ->orWhere('pacientes.ciudad', 'LIKE', $b);
            });
        }

        // Spec 051 (D6): el filtro solo admite ciudades de la lista; las antiguas se encuentran con «Buscar».
        if (is_string($request->ciudad) && Ciudades::contiene($request->ciudad)) {
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

        // Spec 051 (CA7): se compara en PHP porque la columna no distingue mayúsculas ni tildes.
        $guardadas = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.activo', 1)
            ->whereNotNull('pacientes.ciudad')
            ->pluck('pacientes.ciudad');
        $ciudades = collect(Ciudades::nombres())->intersect($guardadas)->values();
        $totalCiudades = $ciudades->reject(fn (string $ciudad) => $ciudad === Ciudades::OTRO_LUGAR)->count();

        $dentistas = $this->dentistasDisponibles();

        return view('pacientes.index', compact('pacientes', 'totalPacientes', 'ciudades', 'totalCiudades', 'dentistas'));
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create()
    {
        $dentistas = $this->dentistasDisponibles();
        [$posiblesDuplicados, $masDuplicados] = $this->avisoDeDuplicados();

        return view('pacientes.create', compact('dentistas', 'posiblesDuplicados', 'masDuplicados'));
    }

    /**
     * Spec 054 (CA3, CA9, D7): nombres y ficha de los posibles duplicados que dejó `store`. Solo el
     * nombre completo y la ruta de ficha del rol de quien registra; nunca otros datos del paciente.
     *
     * @return array{0: list<array{nombre: string, url: string}>, 1: int}
     */
    private function avisoDeDuplicados(): array
    {
        $aviso = session('posibles_duplicados');
        if (! is_array($aviso) || empty($aviso['ids'])) {
            return [[], 0];
        }

        $base = auth()->user()->rol === 'admin' ? 'admin' : 'asistente';
        $filas = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->whereIn('pacientes.id', $aviso['ids'])
            ->get(['pacientes.id', 'usuarios.nombre', 'usuarios.apellido'])
            ->keyBy('id');

        $duplicados = [];
        foreach ($aviso['ids'] as $id) {
            if ($fila = $filas->get($id)) {
                $duplicados[] = [
                    'nombre' => $fila->nombre.' '.$fila->apellido,
                    'url' => route("{$base}.pacientes.show", $id),
                ];
            }
        }

        return [$duplicados, max(0, (int) $aviso['total'] - count($duplicados))];
    }

    // ── STORE ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            // Spec 054: el correo es opcional; sin él no hay contraseña (D4, /analyze A6) y el teléfono es obligatorio (CA2).
            'email' => ['nullable', 'email', 'max:255', 'unique:usuarios,email'],
            'telefono' => ['required_without:email', 'nullable', 'string', 'max:20'],
            'password' => ['exclude_without:email', 'nullable', 'string', 'min:6'],
            'confirmar_duplicado' => ['nullable', 'boolean'],

            'fecha_nacimiento' => 'nullable|date_format:Y-m-d|before:'.RelojClinica::fechaHoy(),
            'genero' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => ['nullable', new CiudadDeLaLista],
            'tipo_sangre' => 'nullable|string|max:5',
            'alergias' => 'nullable|string|max:1000',
            'enfermedades_cronicas' => 'nullable|string|max:1000',
            'medicamentos_actuales' => 'nullable|string|max:500',
            'notas_medicas' => 'nullable|string|max:2000',
            'contacto_emergencia' => 'nullable|string|max:200',
            'telefono_emergencia' => 'nullable|string|max:20',
            'dentista_id' => 'nullable|exists:personal,id',
        ], [
            'email.unique' => 'Este correo ya está registrado en el sistema.',
            'telefono.required_without' => self::TELEFONO_OBLIGATORIO,
            'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);

        // Spec 054 (CA3, D6): con posibles duplicados y sin confirmar, vuelve al formulario con lo escrito
        // salvo la contraseña, que nunca llega a la sesión (/analyze A2, TM10). Solo van ids en el aviso.
        $duplicados = PosiblesDuplicados::buscar(
            $data['nombre'], $data['apellido'], $data['telefono'] ?? null, $data['fecha_nacimiento'] ?? null,
        );
        if ($duplicados['total'] > 0 && empty($data['confirmar_duplicado'])) {
            return back()->withInput($request->except('password'))->with('posibles_duplicados', $duplicados);
        }

        // Sin correo no hay acceso: ni correo ni contraseña (D1, D2). Con correo, como hoy.
        $conAcceso = ! empty($data['email']);
        $passwordPlain = $conAcceso ? (! empty($data['password']) ? $data['password'] : Str::random(10)) : null;
        $passwordGenerada = $conAcceso && empty($data['password']);
        $pacienteId = (string) Str::uuid();

        DB::transaction(function () use ($data, $passwordPlain, $conAcceso, $pacienteId, $duplicados) {
            $usuarioId = (string) Str::uuid();

            // 1. Crear usuario
            DB::table('usuarios')->insert([
                'id' => $usuarioId,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $conAcceso ? $data['email'] : null,
                'password_hash' => $conAcceso ? Hash::make($passwordPlain) : null,
                'rol' => 'cliente',
                'telefono' => $data['telefono'] ?? null,
                'activo' => 1,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);

            // 2. Crear perfil de paciente
            DB::table('pacientes')->insert([
                'id' => $pacienteId,
                'usuario_id' => $usuarioId,
                'dentista_id' => $data['dentista_id'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'genero' => $data['genero'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
                'tipo_sangre' => $data['tipo_sangre'] ?? null,
                'alergias' => $data['alergias'] ?? null,
                'enfermedades_cronicas' => $data['enfermedades_cronicas'] ?? null,
                'medicamentos_actuales' => $data['medicamentos_actuales'] ?? null,
                'notas_medicas' => $data['notas_medicas'] ?? null,
                'contacto_emergencia' => $data['contacto_emergencia'] ?? null,
                'telefono_emergencia' => $data['telefono_emergencia'] ?? null,
                'creado_en' => now(),
            ]);

            // Spec 054 (CA10, TM5–TM7): sin evento no hay alta. Solo booleanos calculados por el servidor.
            app(Auditoria::class)->registrar('paciente_alta', 'exito', 'pacientes', $pacienteId, detalle: [
                'con_acceso' => $conAcceso,
                'posible_duplicado' => $duplicados['total'] > 0,
            ], critico: true);
        });

        $rutaListado = auth()->user()->rol === 'admin' ? 'admin.pacientes' : 'asistente.pacientes';

        if ($passwordGenerada) {
            return redirect()->route($rutaListado)
                ->with('password_generada', [
                    'nombre' => $data['nombre'].' '.$data['apellido'],
                    'email' => $data['email'],
                    'password' => $passwordPlain,
                ]);
        }

        return redirect()->route($rutaListado)
            ->with('success', $conAcceso ? 'Paciente registrado correctamente.' : self::ALTA_SIN_ACCESO);
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

        if (! $paciente) {
            abort(404);
        }

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
        $totalCitas = DB::table('citas')->where('paciente_id', $id)->count();
        $citasCompletadas = DB::table('citas')->where('paciente_id', $id)->where('estado', 'completada')->count();
        $proximaCita = DB::table('citas')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->where('citas.paciente_id', $id)
            ->where('citas.fecha_hora', '>=', RelojClinica::momentoLocal())
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
        if (auth()->user()->rol === 'asistente') {
            abort(403);
        }

        $paciente = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('pacientes.id', $id)
            ->select('pacientes.*', 'usuarios.nombre', 'usuarios.apellido',
                'usuarios.email', 'usuarios.telefono')
            ->first();

        if (! $paciente) {
            abort(404);
        }

        $dentistas = $this->dentistasDisponibles();

        return view('pacientes.edit', compact('paciente', 'dentistas'));
    }

    // ── UPDATE ─────────────────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        if (auth()->user()->rol === 'asistente') {
            abort(403);
        }

        $paciente = DB::table('pacientes')->where('id', $id)->first();
        if (! $paciente) {
            abort(404);
        }

        // Spec 054 (CA5, CA6): el correo se puede añadir a quien no lo tiene, nunca quitar a quien lo tiene.
        $cuenta = DB::table('usuarios')->where('id', $paciente->usuario_id)->first(['email', 'rol']);
        $correoActual = $cuenta->email;

        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => [$correoActual ? 'required' : 'nullable', 'email', 'max:255', "unique:usuarios,email,{$paciente->usuario_id},id"],
            'telefono' => ['required_without:email', 'nullable', 'string', 'max:20'],

            'fecha_nacimiento' => 'nullable|date_format:Y-m-d|before:'.RelojClinica::fechaHoy(),
            'genero' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => ['nullable', new CiudadDeLaLista($paciente->ciudad)],
            'tipo_sangre' => 'nullable|string|max:5',
            'alergias' => 'nullable|string|max:1000',
            'enfermedades_cronicas' => 'nullable|string|max:1000',
            'medicamentos_actuales' => 'nullable|string|max:500',
            'notas_medicas' => 'nullable|string|max:2000',
            'contacto_emergencia' => 'nullable|string|max:200',
            'telefono_emergencia' => 'nullable|string|max:20',
            'dentista_id' => 'nullable|exists:personal,id',
        ], [
            'email.required' => self::CORREO_OBLIGATORIO,
            'email.unique' => 'Este correo ya está registrado en el sistema.',
            'telefono.required_without' => self::TELEFONO_OBLIGATORIO,
            'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);

        // Spec 054 (/review R4): el correo de una cuenta del personal con ficha de paciente solo se cambia
        // desde «Usuarios»; desde aquí, se rechaza antes de guardar nada.
        if ($cuenta->rol !== 'cliente' && Str::lower((string) ($data['email'] ?? '')) !== Str::lower((string) $correoActual)) {
            throw ValidationException::withMessages(['email' => self::CORREO_DEL_PERSONAL]);
        }

        $correoAnadido = $correoActual === null && ! empty($data['email']);

        DB::transaction(function () use ($data, $paciente, $correoAnadido) {
            DB::table('usuarios')->where('id', $paciente->usuario_id)->update([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'email' => $data['email'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'actualizado_en' => now(),
            ]);

            DB::table('pacientes')->where('id', $paciente->id)->update([
                'dentista_id' => $data['dentista_id'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'genero' => $data['genero'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
                'tipo_sangre' => $data['tipo_sangre'] ?? null,
                'alergias' => $data['alergias'] ?? null,
                'enfermedades_cronicas' => $data['enfermedades_cronicas'] ?? null,
                'medicamentos_actuales' => $data['medicamentos_actuales'] ?? null,
                'notas_medicas' => $data['notas_medicas'] ?? null,
                'contacto_emergencia' => $data['contacto_emergencia'] ?? null,
                'telefono_emergencia' => $data['telefono_emergencia'] ?? null,
            ]);

            // Spec 054 (CA11, TM6, TM7): el paciente pasa a poder tener acceso; sin el correo en el evento.
            if ($correoAnadido) {
                app(Auditoria::class)->registrar('paciente_correo_anadido', 'exito', 'pacientes', $paciente->id,
                    campos: ['email'], detalle: ['acceso_posible' => true], critico: true);
            }
        });

        $base = auth()->user()->rol === 'admin' ? 'admin' : 'dentista';

        return redirect()->route("{$base}.pacientes.show", $id)
            ->with('success', 'Datos del paciente actualizados.');
    }

    // ── TOGGLE ACTIVO ──────────────────────────────────────────────────
    public function toggleActivo(string $id)
    {
        $paciente = DB::table('pacientes')->where('id', $id)->first();
        if (! $paciente) {
            abort(404);
        }

        $activo = DB::table('usuarios')->where('id', $paciente->usuario_id)->value('activo');
        // Un admin con ficha de paciente tampoco puede desactivarse desde aquí (RS19.a).
        if ($activo && $this->esLaPropiaCuenta($paciente->usuario_id)) {
            return back()->with('error', self::NO_DESACTIVARSE);
        }

        DB::table('usuarios')->where('id', $paciente->usuario_id)->update([
            'activo' => $activo ? 0 : 1,
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
