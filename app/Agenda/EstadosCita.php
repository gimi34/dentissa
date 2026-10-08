<?php

namespace App\Agenda;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Regla de estados de la cita (spec 026): qué transiciones existen, cuándo una cita
 * está cerrada, cuándo ya empezó y cómo se guarda el cambio con su historial.
 *
 * Quién puede pedir cada estado lo deciden los Gates `gestionar-cita` y
 * `cambiar-estado-cita` (App\Autorizacion\CambiarEstadoCita) antes de llamar aquí.
 */
class EstadosCita
{
    /** Estado nuevo => estados de origen desde los que se permite (CA1). */
    public const TRANSICIONES = [
        'confirmada' => ['agendada'],
        'en_curso' => ['confirmada'],
        'completada' => ['en_curso'],
        'cancelada' => ['agendada', 'confirmada'],
        'no_asistio' => ['agendada', 'confirmada'],
    ];

    /** Estados de los que ya no se sale (CA2). */
    public const CERRADOS = ['completada', 'cancelada', 'no_asistio'];

    /** Estados que solo se pueden marcar cuando la cita ya empezó (CA8). */
    public const REQUIEREN_INICIO = ['en_curso', 'no_asistio'];

    private const ERRORES_DE_BLOQUEO = [1205, 1213];

    public function __construct(private ConnectionInterface $db) {}

    /**
     * Cambia el estado de la cita y deja una sola entrada de historial con su autor.
     *
     * Bloquea la fila de la cita antes de leer su estado, así que de dos cambios
     * simultáneos el segundo se evalúa contra el estado que dejó el primero (CA10).
     * No debe llamarse dentro de otra transacción: ahí un interbloqueo llega como
     * DeadlockException y se convertiría en "ocupada" sin que la transacción exterior
     * lo sepa (igual que ReservaCitas, 025/R9).
     *
     * @throws CambioDeEstadoNoPermitido
     */
    public function cambiar(string $citaId, string $nuevo, Usuario $actor, ?string $motivo): void
    {
        try {
            $this->db->transaction(fn () => $this->cambiarConBloqueo($citaId, $nuevo, $actor, $motivo));
        } catch (DeadlockException) {
            throw CambioDeEstadoNoPermitido::ocupada();
        } catch (QueryException $e) {
            if (in_array($e->errorInfo[1] ?? null, self::ERRORES_DE_BLOQUEO, true)) {
                throw CambioDeEstadoNoPermitido::ocupada();
            }

            throw $e;
        }
    }

    /**
     * Estados nuevos que el usuario puede pedir ahora para la cita, en el orden de la
     * tabla: los que pasan el Gate y la regla de estado y de tiempo (para las vistas).
     *
     * @return string[]
     */
    public function disponibles(Usuario $usuario, object $cita): array
    {
        $gate = Gate::forUser($usuario);

        return array_values(array_filter(
            array_keys(self::TRANSICIONES),
            function (string $nuevo) use ($usuario, $cita, $gate) {
                if (! $gate->allows('cambiar-estado-cita', [$cita, $nuevo])) {
                    return false;
                }

                try {
                    $this->comprobar($cita, $nuevo, $usuario);

                    return true;
                } catch (CambioDeEstadoNoPermitido) {
                    return false;
                }
            },
        ));
    }

    /** La cita ya empezó si su hora de inicio, en la zona de la clínica, ya llegó (borde incluido). */
    public function haEmpezado(object $cita): bool
    {
        return $this->inicio($cita)->lessThanOrEqualTo(Carbon::now());
    }

    /** Hora de inicio de la cita en la zona horaria de la clínica. */
    public function inicio(object $cita): Carbon
    {
        return Carbon::parse($cita->fecha_hora, config('clinica.zona_horaria'));
    }

    private function cambiarConBloqueo(string $citaId, string $nuevo, Usuario $actor, ?string $motivo): void
    {
        $cita = $this->db->table('citas')
            ->where('id', $citaId)
            ->lockForUpdate()
            ->first(['id', 'estado', 'fecha_hora']);

        if ($cita === null) {
            throw new NotFoundHttpException;
        }

        $this->comprobar($cita, $nuevo, $actor);

        $ahora = Carbon::now();
        $this->db->table('citas')->where('id', $citaId)->update([
            'estado' => $nuevo,
            'actualizado_en' => $ahora,
        ]);
        $this->db->table('historial_citas')->insert([
            'id' => (string) Str::uuid(),
            'cita_id' => $citaId,
            'cambiado_por' => $actor->id,
            'estado_anterior' => $cita->estado,
            'estado_nuevo' => $nuevo,
            'motivo' => ($motivo === null || $motivo === '') ? null : $motivo,
            'fecha' => $ahora,
        ]);
    }

    /** @throws CambioDeEstadoNoPermitido */
    private function comprobar(object $cita, string $nuevo, Usuario $actor): void
    {
        if (in_array($cita->estado, self::CERRADOS, true)) {
            throw CambioDeEstadoNoPermitido::cerrada($cita->estado);
        }

        if (! array_key_exists($nuevo, self::TRANSICIONES)) {
            throw CambioDeEstadoNoPermitido::vuelta($actor->rol);
        }

        if (! in_array($cita->estado, self::TRANSICIONES[$nuevo], true)) {
            throw CambioDeEstadoNoPermitido::transicion($nuevo, self::TRANSICIONES[$nuevo]);
        }

        if (in_array($nuevo, self::REQUIEREN_INICIO, true) && ! $this->haEmpezado($cita)) {
            throw CambioDeEstadoNoPermitido::antesDeTiempo($nuevo, $this->inicio($cita));
        }

        if ($actor->rol === 'cliente' && $nuevo === 'cancelada' && $this->haEmpezado($cita)) {
            throw CambioDeEstadoNoPermitido::yaEmpezo();
        }
    }
}
