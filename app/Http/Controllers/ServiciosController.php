<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServiciosController extends Controller
{
    public function index(Request $request)
    {
        $query = Servicio::query();

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->buscar . '%');
        }

        $servicios = $query->get();
        return view('servicios.index', compact('servicios'));
    }

    public function create()
    {
        return view('servicios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['precio'] = 0; // Valor por defecto oculto para evitar errores en BD

        Servicio::create($data);
        return redirect()->route('admin.servicios.index')->with('success', 'Servicio registrado correctamente.');
    }

    public function edit($id)
    {
        $servicio = Servicio::findOrFail($id);
        return view('servicios.edit', compact('servicio'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['precio'] = 0; // Valor por defecto oculto

        $servicio = Servicio::findOrFail($id);
        $servicio->update($data);
        
        return redirect()->route('admin.servicios.index')->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->delete();
        return redirect()->route('admin.servicios.index')->with('success', 'Servicio eliminado correctamente.');
    }
}