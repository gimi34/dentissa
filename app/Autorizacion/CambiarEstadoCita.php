<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

/**
 * Quién puede cambiar el estado de una cita (spec 026, CA4–CA7).
 *
 * Respalda dos Gates: `gestionar-cita` (solo el alcance, antes de validar la entrada)
 * y `cambiar-estado-cita` (alcance y estados que cada rol puede pedir). El rol va
 * primero: un estado que el rol no puede pedir se deniega aunque la cita esté cerrada.
 * Si la transición existe o no, y si la cita ya empezó, lo decide App\Agenda\EstadosCita.
 */
class CambiarEstadoCita
{
    /** Estados que cada rol puede pedir. Los del admin son todos los destinos posibles. */
    private const PERMITIDOS = [
        'admin' => ['confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'],
        'dentista' => ['confirmada', 'en_curso', 'completada', 'no_asistio'],
        'asistente' => ['confirmada', 'cancelada', 'no_asistio'],
        'cliente' => ['cancelada'],
    ];

    private const MENSAJES = [
        'dentista' => 'Como dentista no puedes cancelar citas. Pide a tu asistente o a la administración que la cancelen.',
        'asistente' => 'Como asistente no puedes iniciar ni completar citas. Eso lo hace el dentista o la administración.',
        'cliente' => 'Solo puedes cancelar tus citas.',
    ];

    public function __construct(private AlcanceClinico $alcanceClinico) {}

    /**
     * Personal fuera de alcance → 403 (como antes de la spec 026); paciente con una
     * cita ajena → 404.
     */
    public function alcance(Usuario $usuario, object $cita): Response
    {
        if ($usuario->rol === 'cliente') {
            $pacienteId = DB::table('pacientes')->where('usuario_id', $usuario->id)->value('id');

            return $pacienteId !== null && $pacienteId === $cita->paciente_id
                ? Response::allow()
                : Response::denyAsNotFound();
        }

        $dentistas = $this->alcanceClinico->dentistasDelAlcance($usuario);

        return $dentistas === null || in_array($cita->dentista_id, $dentistas, true)
            ? Response::allow()
            : Response::deny('No tienes acceso a esta cita.');
    }

    public function autorizar(Usuario $usuario, object $cita, string $nuevo): Response
    {
        $alcance = $this->alcance($usuario, $cita);
        if ($alcance->denied()) {
            return $alcance;
        }

        // Un destino que no es de ninguna transición (agendada) lo rechaza el servicio.
        if (! in_array($nuevo, self::PERMITIDOS['admin'], true)) {
            return Response::allow();
        }

        return in_array($nuevo, self::PERMITIDOS[$usuario->rol] ?? [], true)
            ? Response::allow()
            : Response::deny(self::MENSAJES[$usuario->rol] ?? 'No tienes permiso para hacer este cambio de estado.');
    }
}
