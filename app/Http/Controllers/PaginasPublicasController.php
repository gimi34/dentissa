<?php

namespace App\Http\Controllers;

use App\Models\GaleriaFoto;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Páginas públicas (spec 038): la página de inicio y la lista de servicios.
 *
 * Ninguna recibe parámetros; las dos muestran solo el catálogo activo (nombre,
 * descripción y categoría) con una consulta (TM2, TM6).
 *
 * Spec 039: las fotos de la galería en el inicio y en `/galeria`, y la entrega de cada foto
 * (`foto()`, plan → «Entrega de las fotos»).
 */
class PaginasPublicasController extends Controller
{
    // ── Inicio ──────────────────────────────────────────────────────────────
    public function inicio()
    {
        // Spec 044 (CA5, reemplaza 038 CA2): con sesión se ve la página, con «Ir a mi panel» en lugar de los
        // enlaces de cuenta; el inicio de sesión sigue llevando al panel de cada rol.
        return view('publico.inicio', [
            'categorias' => Servicio::porCategoria(),
            'contacto' => config('clinica.contacto'),
            'fotos' => GaleriaFoto::ordenadas()->get(),
            'panel' => auth()->check() ? self::panelDelRol(auth()->user()) : null,
        ]);
    }

    // ── Servicios ───────────────────────────────────────────────────────────
    public function servicios()
    {
        return view('publico.servicios', ['categorias' => Servicio::porCategoria()]);
    }

    // ── Galería (spec 039) ───────────────────────────────────────────────────
    public function galeria()
    {
        return view('publico.galeria', ['fotos' => GaleriaFoto::ordenadas()->get()]);
    }

    /**
     * Una foto publicada, en la medida pedida (`800` o `1600`, ya restringida en la ruta). La ruta
     * del archivo sale del `id` de la fila (TM6). Se revalida en cada visita: `no-cache` (Symfony
     * añade `private`) con `ETag`, y 304 si no cambió; en cuanto se quita, 404 (CA10, TM8). Una
     * fila sin su archivo también es 404, sin error (/analyze A27).
     */
    public function foto(Request $request, GaleriaFoto $foto, string $ancho): Response
    {
        $nombre = $foto->archivo((int) $ancho);
        $disco = Storage::disk('galeria');
        $contenido = $disco->exists($nombre) ? $disco->get($nombre) : null;
        abort_if($contenido === null, 404);

        $etiqueta = '"'.$foto->id.'-'.$ancho.'"';
        $cabeceras = array_filter([
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-cache',
            'ETag' => $etiqueta,
            // D16: un buscador de imágenes no guarda las fotos con paciente.
            'X-Robots-Tag' => $foto->con_paciente ? 'noindex' : null,
        ]);

        if (in_array($etiqueta, $request->getETags(), true)) {
            return response('', 304, $cabeceras);
        }

        return response($contenido, 200, $cabeceras);
    }

    /** Panel de cada rol: adonde va `/` con sesión y «Ir a mi panel» del layout público. */
    public static function panelDelRol(Usuario $usuario): string
    {
        return route(match ($usuario->rol) {
            'admin' => 'admin.dashboard',
            'dentista' => 'dentista.dashboard',
            'asistente' => 'asistente.dashboard',
            default => 'cliente.dashboard',
        });
    }
}
