<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiciosController extends Controller
{
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
        $request->validate([
            'nombre' => 'required|string',
            'categoria' => 'required|string',
            'duracion_min' => 'required|integer',
        ]);

        $data = $request->all();
        $data['id'] = (string) Str::uuid();
        $data['precio'] = 0;
        $data['color_agenda'] = '#F4C0D1'; // Valor por defecto oculto
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
        $request->validate([
            'nombre' => 'required|string',
            'categoria' => 'required|string',
            'duracion_min' => 'required|integer',
        ]);

        $data = $request->all();
        $data['precio'] = 0;
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