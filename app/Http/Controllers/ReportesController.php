<?php

namespace App\Http\Controllers;

use App\Reportes\ReporteCitas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

/**
 * Reportes de la administración (spec 047): solo lectura y solo para el admin.
 *
 * El periodo se valida siempre (TM2, TM3). Uno no válido vuelve a «Reportes» con su error, venga
 * la petición de donde venga, y esa página no calcula ni pinta cifras (plan D3).
 */
class ReportesController extends Controller
{
    private const RUTA = '/admin/reportes';

    public function index(Request $request, ReporteCitas $reportes)
    {
        Gate::authorize('ver-reportes');

        try {
            $validados = $request->validate(ReporteCitas::reglasDePeriodo(), ReporteCitas::mensajesDePeriodo());
        } catch (ValidationException $rechazo) {
            throw $rechazo->redirectTo(self::RUTA);
        }

        $atajos = ReporteCitas::atajos();
        $generado = Carbon::now((string) config('clinica.zona_horaria'));

        // Vuelta de un periodo rechazado: el filtro con sus errores, sin cifras.
        $errores = $request->session()->get('errors');
        if ($errores instanceof ViewErrorBag && $errores->hasAny(['desde', 'hasta'])) {
            return view('reportes.index', ['periodo' => null, 'reporte' => null, 'atajos' => $atajos, 'generado' => $generado]);
        }

        $periodo = isset($validados['desde'], $validados['hasta'])
            ? ['desde' => $validados['desde'], 'hasta' => $validados['hasta']]
            : ReporteCitas::periodoPorDefecto();

        return view('reportes.index', [
            'periodo' => $periodo,
            'reporte' => $reportes->generar($periodo['desde'], $periodo['hasta']),
            'atajos' => $atajos,
            'generado' => $generado,
        ]);
    }
}
