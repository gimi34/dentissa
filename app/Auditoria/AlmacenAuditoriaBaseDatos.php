<?php

namespace App\Auditoria;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * La tabla `auditoria` de MariaDB (spec 020, ADR 0004), solo de anexado: los triggers rechazan
 * `UPDATE` y `DELETE` salvo la purga de eventos de más de 5 años.
 *
 * Las fechas se guardan en UTC. Los días de los filtros «desde» y «hasta» son días de la clínica
 * (`clinica.zona_horaria`; decisión del usuario, 2026-10-29).
 */
class AlmacenAuditoriaBaseDatos implements AlmacenAuditoria
{
    private const TABLA = 'auditoria';

    public function escribir(array $evento): void
    {
        foreach (['campos', 'detalle'] as $json) {
            $evento[$json] = ($evento[$json] ?? []) === [] ? null : json_encode($evento[$json], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        DB::table(self::TABLA)->insert($evento);
    }

    public function consultar(array $filtros, int $porPagina = 25): LengthAwarePaginator
    {
        $zona = (string) config('clinica.zona_horaria');
        $utc = fn (string $dia, int $diasMas = 0) => CarbonImmutable::parse($dia, $zona)->startOfDay()->addDays($diasMas)->utc()->format('Y-m-d H:i:s.u');

        // Sin unir `usuarios`: el registro solo muestra identificadores (CA3).
        return DB::table(self::TABLA)
            ->when($filtros['actor'] ?? null, fn ($consulta, $actor) => $consulta->where('actor_id', $actor))
            ->when($filtros['accion'] ?? null, fn ($consulta, $accion) => $consulta->where('accion', $accion))
            ->when($filtros['recurso_tipo'] ?? null, fn ($consulta, $tipo) => $consulta->where('recurso_tipo', $tipo))
            ->when($filtros['recurso_id'] ?? null, fn ($consulta, $id) => $consulta->where('recurso_id', $id))
            ->when($filtros['desde'] ?? null, fn ($consulta, $desde) => $consulta->where('ocurrido_en', '>=', $utc($desde)))
            // «hasta» incluye el día entero: hasta el comienzo del día siguiente de la clínica.
            ->when($filtros['hasta'] ?? null, fn ($consulta, $hasta) => $consulta->where('ocurrido_en', '<', $utc($hasta, 1)))
            ->orderByDesc('ocurrido_en')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function valoresRegistrados(): array
    {
        $distintos = fn (string $columna) => DB::table(self::TABLA)->whereNotNull($columna)->distinct()->orderBy($columna)->pluck($columna)->all();

        return ['accion' => $distintos('accion'), 'recurso_tipo' => $distintos('recurso_tipo')];
    }

    public function purgar(CarbonInterface $corte): int
    {
        // El trigger solo deja borrar con el indicador activo y eventos de más de 5 años.
        DB::statement('SET @auditoria_purga = 1');
        try {
            return DB::table(self::TABLA)->where('ocurrido_en', '<', $corte->copy()->utc()->format('Y-m-d H:i:s.u'))->delete();
        } finally {
            DB::statement('SET @auditoria_purga = NULL');
        }
    }
}
