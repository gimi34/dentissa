<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * Vista previa del diseño de la spec 020 (D19; A70, A73): las vistas reales con los datos de muestra
 * del candidato aceptado (`design/candidatos/c4/`), para medirlas con `design/medir.mjs` sin sesión.
 *
 * Solo existe con `APP_ENV=local` (TM15) y no lleva `auth` ni `role` (exención de P3 aceptada por el
 * usuario, 2026-10-28): por eso no lee la base. Usa una sesión en memoria y un admin de prueba sin
 * guardar, solo para esta petición, y renderiza como si la petición fuera a `/admin/auditoria`,
 * para que el layout marque «Auditoría» con su propia regla.
 *
 * - `menu`: la consulta con el menú del teléfono abierto, el estado que pone `menu.js`.
 * - `?sin-barra=1`: la barra lateral fuera de la medición (`aria-hidden`), como la copia que
 *   `medir.mjs` hace con las carpetas, para comprobar la excepción de la barra (DS1) sobre la URL.
 */
class DisenioAuditoriaController extends Controller
{
    public const PANTALLAS = ['consulta', 'vacia', 'filtro', 'menu', 'error'];

    /** Total y página de la muestra de `c4`: «Mostrando 1–25 de 1.284 · página 1 de 52». */
    private const TOTAL = 1284;

    private const PETICION_DEL_ERROR = '7c1e5b0a-3f2d-4e8b-9a61-0d4c2b7e9f13';

    private const VALORES = [
        'accion' => ['acceso_denegado', 'auditoria_consultada', 'auditoria_purgada'],
        'recurso_tipo' => ['auditoria', 'cita'],
    ];

    private const FILTROS_DE_LA_VACIA = ['actor' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'desde' => '2026-10-01', 'hasta' => '2026-10-07'];

    /** Los 25 eventos de `c4/consulta.html`, con `ocurrido_en` en UTC (la clínica está en UTC−6). */
    private const EVENTOS = [
        ['ocurrido_en' => '2026-10-28 20:32:07.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => ['accion' => 'acceso_denegado', 'desde' => '2026-10-01'], 'ip' => '192.168.1.24', 'request_id' => 'c963cfe0-afae-4a3b-b909-6a04e7d80068'],
        ['ocurrido_en' => '2026-10-28 20:27:13.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.auditoria.index'], 'ip' => '192.168.1.31', 'request_id' => 'd96e5adf-a2be-4e31-ac8b-e7d742840d2b'],
        ['ocurrido_en' => '2026-10-28 20:17:02.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'cita', 'recurso_id' => '10f4291d-df6f-412d-897f-9fd48ccb887c', 'resultado' => 'denegado_404', 'detalle' => ['accion_solicitada' => 'dentista.citas.show'], 'ip' => '192.168.1.31', 'request_id' => 'fe6c2b03-6820-412c-a959-935406e82a01'],
        ['ocurrido_en' => '2026-10-28 20:13:53.000000', 'actor_id' => '23cd914b-27af-46e0-9cdf-77d4a4664c16', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.60', 'request_id' => '730bed9c-94a6-4f00-b335-c3577972a36d'],
        ['ocurrido_en' => '2026-10-28 20:07:22.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => [], 'ip' => '192.168.1.24', 'request_id' => 'e89c5bc7-a018-4b4d-9120-9e8f332726d0'],
        ['ocurrido_en' => '2026-10-28 19:56:34.000000', 'actor_id' => '50e338a4-b07b-4cac-bd14-9379baaa865b', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.74', 'request_id' => 'ce3ad2b2-8491-4abe-a0af-e35617bcc74d'],
        ['ocurrido_en' => '2026-10-28 19:40:36.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.auditoria.index'], 'ip' => '192.168.1.31', 'request_id' => '92bde31c-34d2-4a16-94da-f46767a9b05c'],
        ['ocurrido_en' => '2026-10-28 19:39:16.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'cita', 'recurso_id' => '10f4291d-df6f-412d-897f-9fd48ccb887c', 'resultado' => 'denegado_404', 'detalle' => ['accion_solicitada' => 'dentista.citas.show'], 'ip' => '192.168.1.31', 'request_id' => '40e05299-30b9-4609-9570-bc621832c9e2'],
        ['ocurrido_en' => '2026-10-28 19:14:27.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => ['accion' => 'acceso_denegado', 'desde' => '2026-10-01'], 'ip' => '192.168.1.24', 'request_id' => '41bc858e-b0b4-462e-8e18-a3634891a61b'],
        ['ocurrido_en' => '2026-10-28 18:55:12.000000', 'actor_id' => '23cd914b-27af-46e0-9cdf-77d4a4664c16', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.22', 'request_id' => 'dbeece42-3be4-478b-b4ae-3dd3447e6046'],
        ['ocurrido_en' => '2026-10-28 18:47:14.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => ['accion' => 'acceso_denegado', 'desde' => '2026-10-01'], 'ip' => '192.168.1.24', 'request_id' => '0352a9bf-0e56-4581-bcd1-58af92ed2607'],
        ['ocurrido_en' => '2026-10-28 18:26:18.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.auditoria.index'], 'ip' => '192.168.1.31', 'request_id' => '230a102c-4786-4228-8cfd-b1e79e1fcc46'],
        ['ocurrido_en' => '2026-10-28 18:05:29.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'cita', 'recurso_id' => 'ed72428a-c0c7-4def-8703-9c68b504ee3a', 'resultado' => 'denegado_404', 'detalle' => ['accion_solicitada' => 'dentista.citas.show'], 'ip' => '192.168.1.31', 'request_id' => 'e810b08a-7288-4e4a-b8a6-acd699882356'],
        ['ocurrido_en' => '2026-10-28 17:45:15.000000', 'actor_id' => '50e338a4-b07b-4cac-bd14-9379baaa865b', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.75', 'request_id' => 'edc81441-67e0-42a6-aa27-ead5281f772f'],
        ['ocurrido_en' => '2026-10-28 17:41:25.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => [], 'ip' => '192.168.1.24', 'request_id' => 'c79f25ee-fefa-4243-a00c-54e9b096ebf5'],
        ['ocurrido_en' => '2026-10-28 17:27:43.000000', 'actor_id' => '50e338a4-b07b-4cac-bd14-9379baaa865b', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.48', 'request_id' => '0d44edc5-9b91-4a48-a824-58191dc90357'],
        ['ocurrido_en' => '2026-10-28 17:21:05.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.auditoria.index'], 'ip' => '192.168.1.31', 'request_id' => '2c1d8f47-fe1d-415b-ac1d-87286fba579b'],
        ['ocurrido_en' => '2026-10-28 17:18:07.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'cita', 'recurso_id' => '10f4291d-df6f-412d-897f-9fd48ccb887c', 'resultado' => 'denegado_404', 'detalle' => ['accion_solicitada' => 'dentista.citas.show'], 'ip' => '192.168.1.31', 'request_id' => '00830f1b-4b21-4f91-a25b-d3abc8b05024'],
        ['ocurrido_en' => '2026-10-28 16:57:11.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => ['accion' => 'acceso_denegado', 'desde' => '2026-10-01'], 'ip' => '192.168.1.24', 'request_id' => 'bad32fc0-ac19-40e8-90b9-4098cc61175d'],
        ['ocurrido_en' => '2026-10-28 16:42:41.000000', 'actor_id' => '85f42207-a41c-4389-9498-79884c50453d', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.26', 'request_id' => '1e177c0b-2a02-47c8-aad7-1c97d5f8822f'],
        ['ocurrido_en' => '2026-10-28 16:25:07.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => ['accion' => 'acceso_denegado', 'desde' => '2026-10-01'], 'ip' => '192.168.1.24', 'request_id' => '92e62e72-12b6-4519-833e-86fd556205aa'],
        ['ocurrido_en' => '2026-10-28 16:21:15.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.auditoria.index'], 'ip' => '192.168.1.31', 'request_id' => 'a487c242-41df-4a81-9531-1d2417e00e58'],
        ['ocurrido_en' => '2026-10-28 16:00:05.000000', 'actor_id' => '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'actor_rol' => 'dentista', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => 'cita', 'recurso_id' => 'ed72428a-c0c7-4def-8703-9c68b504ee3a', 'resultado' => 'denegado_404', 'detalle' => ['accion_solicitada' => 'dentista.citas.show'], 'ip' => '192.168.1.31', 'request_id' => '06f2f0d0-c9a2-4e34-8b58-9fd6a9e493f0'],
        ['ocurrido_en' => '2026-10-28 15:52:48.000000', 'actor_id' => '85f42207-a41c-4389-9498-79884c50453d', 'actor_rol' => 'cliente', 'identificador_intentado' => null, 'accion' => 'acceso_denegado', 'recurso_tipo' => null, 'recurso_id' => null, 'resultado' => 'denegado', 'detalle' => ['accion_solicitada' => 'admin.dashboard'], 'ip' => '10.0.0.84', 'request_id' => '0616c7bc-2814-406d-831c-7c21b3664327'],
        ['ocurrido_en' => '2026-10-28 15:29:01.000000', 'actor_id' => '3800c172-24e3-11f1-8967-0045e2e7d554', 'actor_rol' => 'admin', 'identificador_intentado' => null, 'accion' => 'auditoria_consultada', 'recurso_tipo' => 'auditoria', 'recurso_id' => null, 'resultado' => 'exito', 'detalle' => [], 'ip' => '192.168.1.24', 'request_id' => 'e454625d-2297-4e54-a570-d89a86fd07ef'],
    ];

    public function show(Request $request, string $pantalla)
    {
        abort_unless(in_array($pantalla, self::PANTALLAS, true), 404);

        $html = $pantalla === 'error' ? $this->error() : $this->consulta($request, $pantalla);

        if ($pantalla === 'menu') {
            $html = str_replace('<html lang="es">', '<html lang="es" class="menu-abierto">', $html);
            $html = str_replace('class="menu-btn" aria-label="Menú" aria-expanded="false"', 'class="menu-btn" aria-label="Menú" aria-expanded="true"', $html);
        }
        if ($request->query('sin-barra') === '1') {
            $html = str_replace('<aside class="sidebar"', '<aside aria-hidden="true" class="sidebar"', $html);
        }

        return response($html);
    }

    private function consulta(Request $request, string $pantalla): string
    {
        $filtros = $pantalla === 'vacia' ? self::FILTROS_DE_LA_VACIA : [];
        $sesion = app('session')->driver('array');
        $errores = new ViewErrorBag;
        if ($pantalla === 'filtro') {
            $sesion->put('_old_input', ['actor' => 'melissa lopez']);
            $errores->put('default', new MessageBag(['actor' => ['Escribe el identificador del usuario, no su nombre ni su correo.']]));
        }

        // La petición de la consulta, con el mismo origen, para que el layout y los enlaces sean los reales.
        $falsa = Request::create($request->getSchemeAndHttpHost().'/admin/auditoria', 'GET', $filtros);
        $falsa->setLaravelSession($sesion);
        app()->instance('request', $falsa);
        Facade::clearResolvedInstance('request');
        Auth::setUser(new Usuario(['nombre' => 'Admin', 'apellido' => 'Sistema', 'rol' => 'admin']));
        view()->share('errors', $errores);

        $eventos = match ($pantalla) {
            'filtro' => null,
            'vacia' => new LengthAwarePaginator([], 0, 25, 1, ['path' => '/admin/auditoria']),
            default => new LengthAwarePaginator(
                array_map(fn (array $evento) => (object) array_merge($evento, [
                    'campos' => null,
                    'detalle' => $evento['detalle'] === [] ? null : json_encode($evento['detalle']),
                ]), self::EVENTOS),
                self::TOTAL, 25, 1, ['path' => '/admin/auditoria'],
            ),
        };

        return view('auditoria.index', ['eventos' => $eventos, 'valores' => self::VALORES, 'filtros' => $filtros])->render();
    }

    private function error(): string
    {
        Context::add('request_id', self::PETICION_DEL_ERROR);

        return view('errors.auditoria-no-disponible')->render();
    }
}
