<?php

namespace Tests\Support;

use App\Auditoria\AlmacenAuditoria;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use PDOException;

/**
 * Spec 020 (plan → Estrategia de pruebas): almacén de auditoría que falla solo al escribir.
 *
 * Decora un almacén real: la consulta, los valores registrados y la purga llegan a la base (en la
 * purga fallida, el `DELETE` real se ejecuta y solo falla el registro). `escribir()` lanza una
 * `QueryException` real con SQL y bindings de prueba, para comprobar que nada de eso llega al log.
 */
final class AlmacenAuditoriaQueFalla implements AlmacenAuditoria
{
    /** Valores ficticios de los bindings: ningún log ni respuesta debe contenerlos. */
    public const IP_DE_PRUEBA = '203.0.113.77';

    public const IDENTIFICADOR_DE_PRUEBA = 'correo-de-prueba-que-falla@dentissa.test';

    public const SQLSTATE = 'HY000';

    public function __construct(private readonly AlmacenAuditoria $real) {}

    public function escribir(array $evento): void
    {
        $origen = new PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away');
        $origen->errorInfo = [self::SQLSTATE, 2006, 'MySQL server has gone away'];

        throw new QueryException(
            'mysql',
            'insert into `auditoria` (`ip`, `identificador_intentado`) values (?, ?)',
            [self::IP_DE_PRUEBA, self::IDENTIFICADOR_DE_PRUEBA],
            $origen,
        );
    }

    public function consultar(array $filtros, int $porPagina = 25): LengthAwarePaginator
    {
        return $this->real->consultar($filtros, $porPagina);
    }

    public function valoresRegistrados(): array
    {
        return $this->real->valoresRegistrados();
    }

    public function purgar(CarbonInterface $corte): int
    {
        return $this->real->purgar($corte);
    }
}
