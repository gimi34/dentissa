<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        // 1. Empezamos la consulta, pero no la ejecutamos todavía
        $query = Usuario::query();

        // 2. ¿El usuario escribió algo en la cajita de búsqueda?
        if ($request->filled('buscar')) {
            $b = '%' . $request->buscar . '%';
            $query->where(function ($q) use ($b) {
                // Buscamos si el texto coincide con nombre, apellido, correo o teléfono
                $q->whereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", [$b])
                  ->orWhere('email', 'LIKE', $b)
                  ->orWhere('telefono', 'LIKE', $b);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('rol', $request->tipo);
        }

        // 4. Ejecutamos la consulta ordenándolos para que los grupos no salten
        // Los ordenamos por rol (alfabéticamente) y luego por nombre
        $usuarios = $query->orderBy('rol')->orderBy('nombre')->get();

        // 5. Creamos contadores para las tarjetas de estadísticas superiores (Estos no cambian con el filtro)
        $totales = [
            'admin'    => Usuario::where('rol', 'admin')->count(),
            'dentista' => Usuario::where('rol', 'dentista')->count(),
            'cliente'  => Usuario::where('rol', 'cliente')->count(),
        ];

        // 6. Retornamos la vista pasando las variables
        return view('usuarios.index', compact('usuarios', 'totales'));
    }

    public function create()
    {
        // Solo devuelve la vista vacía, sin mandarle ninguna variable $usuario
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        // 1. Guardamos al usuario nuevo en la base de datos
        Usuario::create([
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'email'    => $request->email,
            'telefono' => $request->telefono,
            'rol'      => $request->rol,
            'activo'   => $request->has('activo') ? 1 : 0,
            // Ponemos una contraseña temporal genérica para que el sistema no choque
            'password_hash' => bcrypt('password123'), 
        ]);

        // 2. Lo regresamos al panel principal
        return redirect()->route('admin.usuarios.index');
    }

    public function edit($id)
    {
        // Buscamos al usuario por su ID
        $usuario = Usuario::findOrFail($id);
        
        // Retornamos la vista pasándole los datos de ese usuario
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, $id)
    {
        // Buscamos al usuario
        $usuario = Usuario::findOrFail($id);
        
        // Actualizamos los campos con lo que el usuario escribió en el formulario
        $usuario->update([
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'email'    => $request->email,
            'telefono' => $request->telefono,
            'rol'      => $request->rol,
            'activo'   => $request->has('activo') ? 1 : 0, // Si el checkbox está marcado, es 1, si no, 0
        ]);

        

        // Lo regresamos a la tabla principal
        return redirect()->route('admin.usuarios.index');
    }

    public function destroy($id)
    {
        // Buscamos al usuario y lo borramos de la base de datos
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        // Regresamos a la tabla
        return redirect()->route('admin.usuarios.index');
    }
}