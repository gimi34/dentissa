<?php

namespace App\Auditoria;

use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Único punto de registro del registro de auditoría (spec 020; plan → Contratos, API de registro).
 *
 * El actor sale de la sesión o de un `Usuario` comprobado por el servidor (D15), nunca de la
 * entrada; la IP y el `request_id`, de la petición y del `Context`. Solo guarda identificadores y
 * nombres de campo (CA3). Si el almacén falla, nunca encadena ni registra la excepción, que lleva
 * SQL y bindings (TM13): deja una línea `auditoria.fallo` en el canal `seguridad` y, en modo
 * crítico, detiene la acción con `AuditoriaNoDisponible` (CA11).
 *
 * Se obtiene por inyección o con `app(Auditoria::class)`; no hay fachada.
 */
class Auditoria
{
    public const RESULTADOS = ['exito', 'denegado', 'denegado_404', 'error'];

    private const NOMBRE = '/^[a-z_]{1,60}$/';

    private const IDENTIFICADOR = '/^[A-Za-z0-9_-]{1,64}$/';

    private const VALOR_DE_DETALLE = '/^[A-Za-z0-9_.:-]{1,64}$/';

    public function __construct(private readonly AlmacenAuditoria $almacen) {}

    /**
     * @param  list<string>  $campos  nombres de los campos cambiados, nunca sus valores
     * @param  array<string, int|bool|string>  $detalle  solo enteros, booleanos o cadenas con formato de identificador o fecha
     * @param  bool  $critico  si el evento no se puede escribir, lanza `AuditoriaNoDisponible`
     * @param  Usuario|null  $actor  cuenta comprobada por otra credencial (D15); sin él, el usuario de la sesión
     *
     * @throws InvalidArgumentException si un valor no tiene el formato o el vocabulario (error de programación)
     * @throws AuditoriaNoDisponible en modo crítico, si el almacén falla
     */
    public function registrar(
        string $accion,
        string $resultado,
        ?string $recursoTipo = null,
        ?string $recursoId = null,
        array $campos = [],
        array $detalle = [],
        bool $critico = false,
        ?string $identificadorIntentado = null,
        ?Usuario $actor = null,
    ): void {
        $this->validar($accion, $resultado, $recursoTipo, $recursoId, $campos, $detalle, $identificadorIntentado, $actor);

        $usuario = $actor ?? Auth::user();
        $evento = [
            'ocurrido_en' => CarbonImmutable::now('UTC')->format('Y-m-d H:i:s.u'),
            'actor_id' => $usuario?->getKey(),
            'actor_rol' => $usuario?->rol,
            // Solo sin sesión y sin actor: con un actor, el identificador intentado sobra.
            'identificador_intentado' => $usuario === null ? $identificadorIntentado : null,
            'accion' => $accion,
            'recurso_tipo' => $recursoTipo,
            'recurso_id' => $recursoId,
            'resultado' => $resultado,
            'campos' => $campos,
            'detalle' => $detalle,
            'ip' => $this->ip(),
            'request_id' => $this->requestId(),
        ];

        try {
            $this->almacen->escribir($evento);
        } catch (Throwable $fallo) {
            Log::channel('seguridad')->error('auditoria.fallo', [
                'accion' => $accion,
                'critico' => $critico,
                'error' => $this->describir($fallo),
            ]);

            if ($critico) {
                throw new AuditoriaNoDisponible;
            }
        }
    }

    /**
     * Registra `acceso_denegado` (modo no crítico) y rechaza con 403, o con 404 si el recurso se
     * oculta (`denegado_404`). Para quien rechaza con una comprobación propia (D4).
     */
    public function denegar(string $accionSolicitada, ?string $recursoTipo, ?string $recursoId, string $resultado = 'denegado'): never
    {
        if (! in_array($resultado, ['denegado', 'denegado_404'], true)) {
            throw new InvalidArgumentException('Una denegación es «denegado» o «denegado_404».');
        }

        $this->registrar('acceso_denegado', $resultado, $recursoTipo, $recursoId, detalle: ['accion_solicitada' => $accionSolicitada]);

        throw new AccesoDenegadoAuditado($resultado === 'denegado_404' ? 404 : 403);
    }

    private function validar(
        string $accion,
        string $resultado,
        ?string $recursoTipo,
        ?string $recursoId,
        array $campos,
        array $detalle,
        ?string $identificadorIntentado,
        ?Usuario $actor,
    ): void {
        $exigir = function (bool $condicion, string $mensaje): void {
            if (! $condicion) {
                throw new InvalidArgumentException($mensaje);
            }
        };

        $exigir(preg_match(self::NOMBRE, $accion) === 1, 'La acción no tiene el formato de un nombre.');
        $exigir(in_array($resultado, self::RESULTADOS, true), 'El resultado no está en el vocabulario.');
        $exigir($recursoTipo === null || preg_match(self::NOMBRE, $recursoTipo) === 1, 'El tipo de recurso no tiene el formato de un nombre.');
        $exigir($recursoId === null || preg_match(self::IDENTIFICADOR, $recursoId) === 1, 'El recurso no tiene el formato de un identificador.');

        $exigir(array_is_list($campos), 'Los campos son una lista de nombres, sin valores.');
        foreach ($campos as $campo) {
            $exigir(is_string($campo) && preg_match('/^[a-z_]{1,60}$/', $campo) === 1, 'Un campo no tiene el formato de un nombre.');
        }

        foreach ($detalle as $clave => $valor) {
            $exigir(is_string($clave) && preg_match(self::NOMBRE, $clave) === 1, 'Una clave del detalle no tiene el formato de un nombre.');
            $exigir(is_int($valor) || is_bool($valor) || (is_string($valor) && preg_match(self::VALOR_DE_DETALLE, $valor) === 1),
                'El detalle solo admite enteros, booleanos o identificadores, nunca texto libre.');
        }

        $exigir($identificadorIntentado === null || mb_strlen($identificadorIntentado) <= 255, 'El identificador intentado es demasiado largo.');
        $exigir($actor === null || $identificadorIntentado === null, 'Un evento con actor no lleva identificador intentado.');
        $exigir($actor === null || $actor->exists, 'El actor tiene que ser un usuario guardado.');
    }

    /** Dirección de origen de la petición en curso; fuera de HTTP (consola, colas), ninguna. */
    private function ip(): ?string
    {
        $peticion = request();

        // En consola Laravel crea una petición sin ruta con 127.0.0.1 de relleno.
        return $peticion->route() !== null ? $peticion->ip() : null;
    }

    /** El de la petición; fuera de HTTP, uno por ejecución, que pasa también a los logs. */
    private function requestId(): string
    {
        $id = Context::get('request_id');
        if (! is_string($id) || $id === '') {
            $id = (string) Str::uuid();
            Context::add('request_id', $id);
        }

        return $id;
    }

    /** Clase corta de la excepción y su SQLSTATE, si lo tiene: nunca el mensaje. */
    private function describir(Throwable $fallo): string
    {
        $estado = property_exists($fallo, 'errorInfo') && is_array($fallo->errorInfo) ? ($fallo->errorInfo[0] ?? null) : null;

        return class_basename($fallo).(is_string($estado) && $estado !== '' ? ' '.$estado : '');
    }
}
