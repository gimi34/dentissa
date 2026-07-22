<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // ── ADMIN ──────────────────────────────────────────────────────────────
    public function admin()
    {
        $hoy = Carbon::today();

        // Estadísticas principales
        $citasHoy = DB::table('citas')
            ->whereDate('fecha_hora', $hoy)
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->count();

        $totalPacientes = DB::table('usuarios')
            ->where('rol', 'cliente')
            ->where('activo', 1)
            ->count();

        // Lista de citas de hoy (con JOIN)
        $citasHoyLista = DB::table('citas')
            ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
            ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
            ->join('personal', 'citas.dentista_id', '=', 'personal.id')
            ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
            ->whereDate('citas.fecha_hora', $hoy)
            ->select(
                'citas.id',
                'citas.fecha_hora',
                'citas.estado',
                DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                'up.telefono',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                'servicios.nombre AS servicio'
            )
            ->orderBy('citas.fecha_hora')
            ->get();

        return view('dashboard.admin', compact(
            'citasHoy',
            'totalPacientes',
            'citasHoyLista'
        ));
    }

    // ── DENTISTA ───────────────────────────────────────────────────────────
    public function dentista()
    {
        $user = auth()->user();
        $hoy  = Carbon::today();

        // Obtener el registro de personal del dentista autenticado
        $personal = DB::table('personal')
            ->where('usuario_id', $user->id)
            ->first();

        $citasHoy         = 0;
        $citasHoyLista    = collect();
        $pacientesAtendidos = 0;

        if ($personal) {
            $citasHoy = DB::table('citas')
                ->where('dentista_id', $personal->id)
                ->whereDate('fecha_hora', $hoy)
                ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                ->count();

            $pacientesAtendidos = DB::table('citas')
                ->where('dentista_id', $personal->id)
                ->where('estado', 'completada')
                ->whereMonth('fecha_hora', $hoy->month)
                ->whereYear('fecha_hora', $hoy->year)
                ->count();

            $citasHoyLista = DB::table('citas')
                ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
                ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
                ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
                ->where('citas.dentista_id', $personal->id)
                ->whereDate('citas.fecha_hora', $hoy)
                ->select(
                    'citas.id',
                    'citas.fecha_hora',
                    'citas.estado',
                    'citas.notas_paciente',
                    DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                    'up.telefono',
                    'servicios.nombre AS servicio',
                    'servicios.duracion_min'
                )
                ->orderBy('citas.fecha_hora')
                ->get();
        }

        // Próxima cita pendiente del día
        $proximaCita = $citasHoyLista
            ->whereNotIn('estado', ['completada', 'cancelada', 'no_asistio'])
            ->first();

        return view('dashboard.dentista', compact(
            'citasHoy',
            'citasHoyLista',
            'pacientesAtendidos',
            'proximaCita'
        ));
    }

    // ── ASISTENTE ──────────────────────────────────────────────────────────
    public function asistente()
    {
        $user = auth()->user();
        $hoy  = Carbon::today();

        $personal = DB::table('personal')
            ->where('usuario_id', $user->id)
            ->first();

        $doctores      = collect();
        $citasHoy      = 0;
        $citasHoyLista = collect();

        if ($personal) {
            $doctorIds = DB::table('asistente_dentista')
                ->where('asistente_id', $personal->id)
                ->pluck('dentista_id');

            $doctores = DB::table('personal')
                ->join('usuarios', 'personal.usuario_id', '=', 'usuarios.id')
                ->whereIn('personal.id', $doctorIds)
                ->select('personal.id', 'personal.especialidad', 'usuarios.nombre', 'usuarios.apellido', 'usuarios.activo',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) AS nombre_completo"))
                ->orderBy('usuarios.nombre')
                ->get();

            if ($doctorIds->isNotEmpty()) {
                $citasHoy = DB::table('citas')
                    ->whereIn('dentista_id', $doctorIds)
                    ->whereDate('fecha_hora', $hoy)
                    ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                    ->count();

                $citasHoyLista = DB::table('citas')
                    ->join('pacientes', 'citas.paciente_id', '=', 'pacientes.id')
                    ->join('usuarios as up', 'pacientes.usuario_id', '=', 'up.id')
                    ->join('personal', 'citas.dentista_id', '=', 'personal.id')
                    ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
                    ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
                    ->whereIn('citas.dentista_id', $doctorIds)
                    ->whereDate('citas.fecha_hora', $hoy)
                    ->select(
                        'citas.id',
                        'citas.fecha_hora',
                        'citas.estado',
                        DB::raw("CONCAT(up.nombre, ' ', up.apellido) AS paciente"),
                        'up.telefono',
                        DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                        'servicios.nombre AS servicio'
                    )
                    ->orderBy('citas.fecha_hora')
                    ->get();
            }
        }

        return view('dashboard.asistente', compact(
            'doctores',
            'citasHoy',
            'citasHoyLista'
        ));
    }

    // ── CLIENTE ────────────────────────────────────────────────────────────
    public function cliente()
    {
        $user = auth()->user();

        $paciente = DB::table('pacientes')
            ->where('usuario_id', $user->id)
            ->first();

        $proximaCita  = null;
        $citasPasadas = collect();

        if ($paciente) {
            $proximaCita = DB::table('citas')
                ->join('personal', 'citas.dentista_id', '=', 'personal.id')
                ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
                ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
                ->where('citas.paciente_id', $paciente->id)
                ->where('citas.fecha_hora', '>=', now())
                ->whereNotIn('citas.estado', ['cancelada', 'no_asistio'])
                ->select(
                    'citas.id',
                    'citas.fecha_hora',
                    'citas.estado',
                    DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                    'servicios.nombre AS servicio',
                    'servicios.duracion_min'
                )
                ->orderBy('citas.fecha_hora')
                ->first();

            $citasPasadas = DB::table('citas')
                ->join('personal', 'citas.dentista_id', '=', 'personal.id')
                ->join('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
                ->leftJoin('servicios', 'citas.servicio_id', '=', 'servicios.id')
                ->where('citas.paciente_id', $paciente->id)
                ->where('citas.fecha_hora', '<', now())
                ->select(
                    'citas.id',
                    'citas.fecha_hora',
                    'citas.estado',
                    DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista"),
                    'servicios.nombre AS servicio'
                )
                ->orderByDesc('citas.fecha_hora')
                ->limit(5)
                ->get();
        }

        return view('dashboard.cliente', compact(
            'proximaCita',
            'citasPasadas',
            'paciente'
        ));
    }
}