<?php

namespace App\Http\Controllers;

use App\Galeria\FotoNoProcesable;
use App\Galeria\Galeria;
use App\Models\GaleriaFoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gestión de la galería de trabajos (spec 039; CA8–CA13; TM1–TM3, TM9). Solo el administrador:
 * el grupo `admin` lleva `role:admin` y cada acción comprueba además el Gate `gestionar-galeria`
 * (ADR 0003). Toda la lógica vive en `App\Galeria\Galeria`; aquí, la validación y las respuestas.
 */
class FotosGaleriaController extends Controller
{
    public function __construct(private readonly Galeria $galeria) {}

    /** Reglas de la subida (plan → Contratos). `bail`: un solo motivo por campo. */
    private function reglasDeSubida(): array
    {
        return [
            // `mimes` mira el contenido (finfo), no la extensión; `dimensions` lee la cabecera
            // antes de decodificar (TM2, TM3).
            'foto' => ['bail', 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'descripcion' => ['bail', 'required', 'string', 'max:160'],
            'con_paciente' => ['bail', 'required', 'in:si,no'],
            'consentimiento' => ['accepted_if:con_paciente,si'],
        ];
    }

    /** Mensajes de CA11 y CA12 (literales de la spec y del plan). */
    private function mensajesDeSubida(): array
    {
        return [
            'foto.required' => 'Elige la foto que quieres publicar.',
            'foto.file' => 'Elige la foto que quieres publicar.',
            'foto.uploaded' => 'La foto pesa más de 5 MB.',
            'foto.mimes' => 'El archivo no es una imagen JPG, PNG o WebP.',
            'foto.max' => 'La foto pesa más de 5 MB.',
            'foto.dimensions' => 'La foto es demasiado grande: como mucho 6000 píxeles por lado.',
            'descripcion.required' => 'Escribe una descripción de la foto.',
            'descripcion.string' => 'Escribe una descripción de la foto.',
            'descripcion.max' => 'La descripción puede tener como mucho 160 caracteres.',
            'con_paciente.required' => 'Indica si en la foto aparece un paciente.',
            'con_paciente.in' => 'Indica si en la foto aparece un paciente.',
            'consentimiento.accepted_if' => 'Marca que tienes el consentimiento escrito del paciente.',
        ];
    }

    public function index(): View
    {
        Gate::authorize('gestionar-galeria');

        return $this->vista();
    }

    /** La lista con el diálogo «¿Quitar esta foto?» abierto: funciona sin JavaScript (D7). */
    public function confirmarQuitar(GaleriaFoto $foto): View
    {
        Gate::authorize('gestionar-galeria');

        return $this->vista($foto);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('gestionar-galeria');
        $datos = $request->validate($this->reglasDeSubida(), $this->mensajesDeSubida());

        try {
            $this->galeria->publicar($request->file('foto'), $datos['descripcion'], $datos['con_paciente'] === 'si', $request->user());
        } catch (FotoNoProcesable) {
            throw ValidationException::withMessages(['foto' => 'No se pudo leer la foto. Prueba con otro archivo.']);
        }

        return redirect()->route('admin.galeria.index')->with('success', 'Foto publicada.');
    }

    public function subir(GaleriaFoto $foto): RedirectResponse
    {
        Gate::authorize('gestionar-galeria');
        $this->galeria->mover($foto, -1);

        return redirect()->route('admin.galeria.index');
    }

    public function bajar(GaleriaFoto $foto): RedirectResponse
    {
        Gate::authorize('gestionar-galeria');
        $this->galeria->mover($foto, 1);

        return redirect()->route('admin.galeria.index');
    }

    public function destroy(GaleriaFoto $foto): RedirectResponse
    {
        Gate::authorize('gestionar-galeria');
        $this->galeria->quitar($foto);

        return redirect()->route('admin.galeria.index')->with('success', 'Foto quitada.');
    }

    private function vista(?GaleriaFoto $quitar = null): View
    {
        return view('galeria.index', [
            'fotos' => GaleriaFoto::ordenadas()->get(),
            'quitar' => $quitar,
            'limite' => Galeria::LIMITE,
        ]);
    }
}
