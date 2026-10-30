<?php

namespace App\Http\Controllers;

use App\Auditoria\AlmacenAuditoria;
use App\Auditoria\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

/**
 * Consulta del registro de auditoría (spec 020, CA6, CA7, CA12), solo para el admin y solo de
 * lectura: no hay rutas de modificación (CA2).
 *
 * Los filtros se validan siempre, aunque lleguen de un selector (TM8, TM12): un filtro rechazado
 * vuelve a la consulta con su error, sin buscar nada y sin registrarse. Una consulta válida queda
 * registrada en modo crítico, antes de mostrar el registro, con los filtros validados.
 */
class AuditoriaController extends Controller
{
    private const POR_PAGINA = 25;

    private const FILTROS = ['actor', 'accion', 'recurso_tipo', 'recurso_id', 'desde', 'hasta'];

    public function index(Request $request, Auditoria $auditoria, AlmacenAuditoria $almacen)
    {
        $validados = $request->validate(self::reglasDeFiltro(), self::mensajesDeFiltro());
        $valores = $almacen->valoresRegistrados();

        // Vuelta de un filtro rechazado: el formulario con sus errores, sin lista ni registro (design.md).
        $errores = $request->session()->get('errors');
        if ($errores instanceof ViewErrorBag && $errores->hasAny([...self::FILTROS, 'page'])) {
            return view('auditoria.index', ['eventos' => null, 'valores' => $valores, 'filtros' => []]);
        }
        $filtros = array_filter(
            array_intersect_key($validados, array_flip(self::FILTROS)),
            fn ($valor) => $valor !== null && $valor !== '',
        );

        $auditoria->registrar('auditoria_consultada', 'exito', 'auditoria', detalle: $filtros, critico: true);

        return view('auditoria.index', [
            'eventos' => $almacen->consultar($filtros, self::POR_PAGINA),
            'valores' => $valores,
            'filtros' => $filtros,
        ]);
    }

    /** @return array<string, string> */
    private static function reglasDeFiltro(): array
    {
        $nombre = 'nullable|string|regex:/^[a-z_]{1,60}$/';

        return [
            'actor' => 'nullable|string|uuid',
            'accion' => $nombre,
            'recurso_tipo' => $nombre,
            'recurso_id' => 'nullable|string|regex:/^[A-Za-z0-9_-]{1,64}$/',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /** @return array<string, string> */
    private static function mensajesDeFiltro(): array
    {
        return [
            'actor.*' => 'Escribe el identificador del usuario, no su nombre ni su correo.',
            'accion.*' => 'Elige una acción de la lista.',
            'recurso_tipo.*' => 'Elige un tipo de recurso de la lista.',
            'recurso_id.*' => 'Escribe el identificador del recurso: letras, números, guiones y guiones bajos.',
            'desde.*' => 'Escribe la fecha como AAAA-MM-DD.',
            'hasta.date_format' => 'Escribe la fecha como AAAA-MM-DD.',
            'hasta.after_or_equal' => '«Hasta» no puede ser anterior a «Desde».',
            'page.*' => 'La página no existe.',
        ];
    }
}
