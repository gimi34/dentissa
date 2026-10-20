<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiciosController extends Controller
{
    /**
     * Reglas de validación compartidas por el alta y la edición de servicios.
     * El precio y el color de agenda se validan aquí para que dejen de asignarse
     * de forma incondicional en el controlador (FR2.4, FR2.5, FR2.6).
     */
    private function reglasValidacion(): array
    {
        return [
            'nombre' => 'required|string',
            'categoria' => 'required|string',
            'duracion_min' => 'required|integer',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0|max:99999999.99',
            'color_agenda' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    /**
     * Mensajes en español, coherentes con el resto del sistema
     * (mismo patrón que PacientesController::store()).
     */
    private function mensajesValidacion(): array
    {
        return [
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'categoria.required' => 'La categoría es obligatoria.',
            'duracion_min.required' => 'La duración es obligatoria.',
            'duracion_min.integer' => 'La duración debe ser un número entero de minutos.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un valor numérico.',
            'precio.min' => 'El precio no puede ser negativo.',
            'precio.max' => 'El precio supera el máximo permitido.',
            'color_agenda.required' => 'El color de agenda es obligatorio.',
            'color_agenda.regex' => 'El color de agenda debe tener el formato #RRGGBB.',
        ];
    }

    public function index(Request $request)
    {
        $servicios = Servicio::all();

        return view('servicios.index', compact('servicios'));
    }

    public function create()
    {
        $categorias = Servicio::whereNotNull('categoria')->distinct()->pluck('categoria');

        return view('servicios.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->reglasValidacion(), $this->mensajesValidacion());

        $data['id'] = (string) Str::uuid();
        $data['activo'] = 1;
        $data['creado_en'] = now();

        Servicio::create($data);

        return redirect()->route('admin.servicios.index');
    }

    public function edit($id)
    {
        $servicio = Servicio::findOrFail($id);
        $categorias = Servicio::whereNotNull('categoria')->distinct()->pluck('categoria');

        return view('servicios.edit', compact('servicio', 'categorias'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate($this->reglasValidacion(), $this->mensajesValidacion());

        // El checkbox de estado solo viaja en la petición cuando está marcado.
        $data['activo'] = $request->has('activo') ? 1 : 0;

        $servicio = Servicio::findOrFail($id);
        $servicio->update($data);

        return redirect()->route('admin.servicios.index');
    }

    public function destroy($id)
    {
        Servicio::findOrFail($id)->delete();

        return redirect()->route('admin.servicios.index');
    }
}
