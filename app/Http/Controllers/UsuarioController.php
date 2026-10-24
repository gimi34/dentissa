<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Mensajes en español, siguiendo el patrón de PacientesController::store().
     */
    private function mensajesValidacion(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'Los apellidos son obligatorios.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Este correo ya está registrado en el sistema.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'rol.required' => 'El rol es obligatorio.',
            'rol.in' => 'El rol seleccionado no es válido.',
        ];
    }

    public function index(Request $request)
    {
        // 1. Empezamos la consulta, pero no la ejecutamos todavía
        $query = Usuario::query();

        // 2. ¿El usuario escribió algo en la cajita de búsqueda?
        if ($request->filled('buscar')) {
            $b = '%'.$request->buscar.'%';
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
            'admin' => Usuario::where('rol', 'admin')->count(),
            'dentista' => Usuario::where('rol', 'dentista')->count(),
            'cliente' => Usuario::where('rol', 'cliente')->count(),
        ];

        // 6. Retornamos la vista pasando las variables
        return view('usuarios.index', compact('usuarios', 'totales'));
    }

    public function show($id)
    {
        // Buscamos al usuario por su ID
        $usuario = Usuario::findOrFail($id);

        // Retornamos la nueva vista pasándole los datos
        return view('usuarios.show', compact('usuario'));
    }

    public function create()
    {
        // Solo devuelve la vista vacía, sin mandarle ninguna variable $usuario
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        // 1. Validamos en el servidor. El rol se comprueba contra los cuatro
        //    valores del enum de la columna usuarios.rol, de modo que una
        //    petición manipulada no pueda escribir otra cosa (FR3.1, FR3.3, NFR1).
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'rol' => 'required|in:cliente,asistente,dentista,admin',
        ], $this->mensajesValidacion());

        // 2. Usamos la contraseña escrita en el formulario; si viene vacía se
        //    genera una aleatoria, como promete el texto de la vista (FR3.6).
        $passwordPlain = ! empty($data['password']) ? $data['password'] : Str::random(10);

        // 3. Guardamos al usuario nuevo en la base de datos
        Usuario::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'rol' => $data['rol'],
            'activo' => $request->has('activo') ? 1 : 0,
            'password_hash' => Hash::make($passwordPlain),
        ]);

        // 4. Lo regresamos al panel principal
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

        // Misma validación que el alta, con la unicidad del correo ignorando el
        // propio registro que se está editando (FR3.2). El formulario de edición
        // no muestra el campo de contraseña, así que aquí no se valida ni se toca.
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('usuarios', 'email')->ignore($id)],
            'telefono' => 'nullable|string|max:20',
            'rol' => 'required|in:cliente,asistente,dentista,admin',
        ], $this->mensajesValidacion());

        // Actualizamos los campos con lo que el administrador escribió en el formulario
        $usuario->fill([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'rol' => $data['rol'],
            'activo' => $request->has('activo') ? 1 : 0, // Si el checkbox está marcado, es 1, si no, 0
        ]);

        // El modelo declara $timestamps = false y 'actualizado_en' no es asignable
        // en masa, así que la columna se escribe directamente sobre el atributo.
        $usuario->actualizado_en = now();
        $usuario->save();

        // Lo regresamos a la tabla principal
        return redirect()->route('admin.usuarios.index');
    }

    /**
     * Baja lógica: conmuta usuarios.activo entre 1 y 0 en lugar de borrar la
     * fila, que arrastraría en cascada la ficha de paciente y su expediente
     * (FR4.1, FR4.6, NFR2). Mismo patrón que PersonalController::toggleActivo().
     */
    public function toggleActivo(string $id)
    {
        $usuario = DB::table('usuarios')->where('id', $id)->first();
        if (! $usuario) {
            abort(404);
        }

        DB::table('usuarios')->where('id', $id)->update([
            'activo' => $usuario->activo ? 0 : 1,
            'actualizado_en' => now(),
        ]);

        $msg = $usuario->activo ? 'Usuario desactivado.' : 'Usuario reactivado.';

        return back()->with('success', $msg);
    }
}
