<?php

namespace App\Auditoria;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Dónde se guardan y se consultan los eventos de auditoría (spec 020, ADR 0004).
 *
 * Solo hay una implementación real, `AlmacenAuditoriaBaseDatos`; la interfaz existe para que los
 * tests sustituyan el almacén por uno que falla (plan → Estrategia de pruebas).
 */
interface AlmacenAuditoria
{
    /**
     * Guarda un evento ya validado por `Auditoria`.
     *
     * @param  array<string, mixed>  $evento  columnas de la tabla `auditoria`, sin `id`
     */
    public function escribir(array $evento): void;

    /**
     * Eventos que cumplen los filtros, del más reciente al más antiguo.
     *
     * @param  array{actor?: string, accion?: string, recurso_tipo?: string, recurso_id?: string, desde?: string, hasta?: string}  $filtros  ya validados
     */
    public function consultar(array $filtros, int $porPagina = 25): LengthAwarePaginator;

    /**
     * Valores registrados de `accion` y `recurso_tipo`, ordenados, para los selectores del filtro.
     *
     * @return array{accion: list<string>, recurso_tipo: list<string>}
     */
    public function valoresRegistrados(): array;

    /**
     * Borra los eventos anteriores al corte, dentro de la transacción de quien llama, y devuelve
     * cuántos borró. Es el único responsable del borrado y del indicador de purga.
     */
    public function purgar(CarbonInterface $corte): int;
}
