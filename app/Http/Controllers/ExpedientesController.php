<?php

namespace App\Http\Controllers;

use App\Agenda\RelojClinica;
use App\Auditoria\Auditoria;
use App\Autorizacion\AlcanceClinico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpedientesController extends Controller
{
    /** Spec 014 (D5). */
    public const AUTOR_NO_DENTISTA = 'El expediente lo firma un dentista.';

    public const CITA_DE_OTRO_PACIENTE = 'La cita elegida no es de este paciente.';

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

        // El dentista ve los que él mismo registró, de pacientes de su alcance (spec 014, D7).
        if (auth()->user()->rol === 'dentista') {
            $alcance = app(AlcanceClinico::class);
            $query->where('expedientes.dentista_id', $alcance->personalId(auth()->user()));
            $alcance->aplicarAPacientes($query, auth()->user());
        }

        if ($request->filled('buscar')) {
            $b = '%'.$request->buscar.'%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw("CONCAT(up.nombre, ' ', up.apellido) LIKE ?", [$b])
                    ->orWhere('expedientes.diagnostico', 'LIKE', $b);
            });
        }

        $expedientes = $query->orderByDesc('expedientes.creado_en')->paginate(20)->withQueryString();

        $base = match (auth()->user()->rol) {
            'dentista' => 'dentista',
            'asistente' => 'asistente',
            default => 'admin',
        };

        return view('expedientes.index', compact('expedientes', 'base'));
    }

    // ── CREATE ─────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        // Spec 014 (D16, TM13): sin un UUID no hay paciente que buscar; 404 sin consultar, nunca un 500.
        $pacienteId = $request->query('paciente_id');
        if (! is_string($pacienteId) || ! Str::isUuid($pacienteId)) {
            abort(404);
        }

        // Fuera del alcance, 404 auditado con el paciente; el asistente no registra expedientes (403).
        $this->autorizarAlta($pacienteId);

        $paciente = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('pacientes.id', $pacienteId)
            ->select('pacientes.id', DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
            ->first();

        if (! $paciente) {
            abort(404);
        }

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
        $usuario = auth()->user();
        $pacienteId = $request->input('paciente_id');

        // Spec 014 (D16, D17): para el personal no admin, un UUID y el Gate sobre el paciente antes de validar, para
        // que un paciente ajeno y uno inexistente respondan igual. El admin conserva la validación de siempre.
        if ($usuario->rol !== 'admin') {
            if (! is_string($pacienteId) || ! Str::isUuid($pacienteId)) {
                abort(404);
            }
            $this->autorizarAlta($pacienteId);

            // D5, D19: el dentista solo firma como él mismo; otro autor es un 403 auditado sobre el paciente.
            if ($request->input('dentista_id') !== app(AlcanceClinico::class)->personalId($usuario)) {
                app(Auditoria::class)->denegar($this->accionSolicitada(), 'pacientes', $pacienteId);
            }
        }

        $data = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            // D5: el autor es un dentista y la cita, del mismo paciente.
            'dentista_id' => ['required', Rule::exists('personal', 'id')->where('tipo', 'dentista')],
            'cita_id' => ['nullable', Rule::exists('citas', 'id')->where('paciente_id', is_string($pacienteId) ? $pacienteId : '')],
            'diagnostico' => 'required|string|max:2000',
            'tratamiento' => 'nullable|string|max:2000',
            'receta' => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:2000',
            'proxima_visita' => 'nullable|date_format:Y-m-d|after:'.RelojClinica::fechaHoy(),
        ], [
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
            'dentista_id.exists' => self::AUTOR_NO_DENTISTA,
            'cita_id.exists' => self::CITA_DE_OTRO_PACIENTE,
            'proxima_visita.date_format' => 'La próxima visita debe tener el formato AAAA-MM-DD.',
            'proxima_visita.after' => 'La próxima visita debe ser posterior a hoy.',
        ]);

        $id = (string) Str::uuid();

        DB::table('expedientes')->insert([
            'id' => $id,
            'paciente_id' => $data['paciente_id'],
            'dentista_id' => $data['dentista_id'],
            'cita_id' => $data['cita_id'] ?? null,
            'diagnostico' => $data['diagnostico'],
            'tratamiento' => $data['tratamiento'] ?? null,
            'receta' => $data['receta'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'proxima_visita' => $data['proxima_visita'] ?? null,
            'archivos_url' => null,
            'creado_en' => now(),
        ]);

        // Redirigir al rol correcto
        $base = match (auth()->user()->rol) {
            'dentista' => 'dentista',
            'asistente' => 'asistente',
            default => 'admin',
        };

        return redirect()->route("{$base}.pacientes.show", $data['paciente_id'])
            ->with('success', 'Expediente registrado correctamente.');
    }

    // ── SHOW ───────────────────────────────────────────────────────────
    public function show(string $id)
    {
        // Spec 014 (CA4, CA5): fuera del alcance del paciente, 404 auditado; dentro, cualquier autor.
        Gate::authorize('ver-expediente', $this->expedienteOFallar($id));

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

        if (! $expediente) {
            abort(404);
        }

        // Spec 014 (CA12, D3): lectura del expediente, en modo crítico (sin registro no se muestra).
        app(Auditoria::class)->registrar('expediente_leido', 'exito', 'expedientes', $id, critico: true);

        $base = match (auth()->user()->rol) {
            'dentista' => 'dentista',
            'asistente' => 'asistente',
            default => 'admin',
        };

        return view('expedientes.show', compact('expediente', 'base'));
    }

    // ── UPDATE ─────────────────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        // Spec 014 (CA6, D17): solo su autor o el admin, y antes de validar; fuera del alcance, 404.
        Gate::authorize('editar-expediente', $this->expedienteOFallar($id));

        // Una próxima visita ya guardada que llega sin cambios se acepta aunque haya vencido (spec 050, D10).
        $guardada = DB::table('expedientes')->where('id', $id)->value('proxima_visita');
        $sinCambios = $guardada !== null && $request->input('proxima_visita') === substr((string) $guardada, 0, 10);

        $data = $request->validate([
            'diagnostico' => 'required|string|max:2000',
            'tratamiento' => 'nullable|string|max:2000',
            'receta' => 'nullable|string|max:2000',
            'observaciones' => 'nullable|string|max:2000',
            'proxima_visita' => $sinCambios ? 'nullable|date_format:Y-m-d' : 'nullable|date_format:Y-m-d|after:'.RelojClinica::fechaHoy(),
        ], [
            'proxima_visita.date_format' => 'La próxima visita debe tener el formato AAAA-MM-DD.',
            'proxima_visita.after' => 'La próxima visita debe ser posterior a hoy.',
        ]);

        DB::table('expedientes')->where('id', $id)->update($data);

        return back()->with('success', 'Expediente actualizado.');
    }

    // ── HELPERS ────────────────────────────────────────────────────────

    /** El expediente (paciente y autor) para su Gate, o 404 si no existe. */
    private function expedienteOFallar(string $id): object
    {
        $expediente = DB::table('expedientes')->where('id', $id)->select('id', 'paciente_id', 'dentista_id')->first();
        if (! $expediente) {
            abort(404);
        }

        return $expediente;
    }

    /**
     * Spec 014 (CA7, D4, D6): `crear-expediente` sobre el paciente. Estas rutas no llevan `{id}`, así que la denegación
     * se audita aquí, con el paciente como recurso: 404 fuera del alcance, 403 dentro sin permiso.
     */
    private function autorizarAlta(string $pacienteId): void
    {
        $acceso = Gate::inspect('crear-expediente', [$pacienteId]);
        if ($acceso->denied()) {
            app(Auditoria::class)->denegar($this->accionSolicitada(), 'pacientes', $pacienteId,
                $acceso->status() === 404 ? 'denegado_404' : 'denegado');
        }
    }

    private function accionSolicitada(): string
    {
        return request()->route()?->getName() ?? 'ruta_sin_nombre';
    }
}
