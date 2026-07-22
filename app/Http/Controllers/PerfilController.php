<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    public function edit()
    {
        $user     = auth()->user();
        $paciente = DB::table('pacientes')
            ->leftJoin('personal', 'pacientes.dentista_id', '=', 'personal.id')
            ->leftJoin('usuarios as ud', 'personal.usuario_id', '=', 'ud.id')
            ->where('pacientes.usuario_id', $user->id)
            ->select('pacientes.*', 'personal.especialidad AS dentista_especialidad',
                DB::raw("CONCAT(ud.nombre, ' ', ud.apellido) AS dentista_nombre"))
            ->first();

        return view('cliente.perfil', compact('user', 'paciente'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'nombre'           => ['required', 'string', 'max:100'],
            'apellido'         => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'max:255', "unique:usuarios,email,{$user->id},id"],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero'           => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:500'],
            'ciudad'           => ['nullable', 'string', 'max:100'],
        ], [
            'email.unique'             => 'Este correo ya está en uso por otra cuenta.',
            'fecha_nacimiento.before'  => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);

        DB::table('usuarios')->where('id', $user->id)->update([
            'nombre'         => $data['nombre'],
            'apellido'       => $data['apellido'],
            'email'          => $data['email'],
            'telefono'       => $data['telefono'] ?? null,
            'actualizado_en' => now(),
        ]);

        $paciente = DB::table('pacientes')->where('usuario_id', $user->id)->first();
        $datosPaciente = [
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'genero'           => $data['genero'] ?? null,
            'direccion'        => $data['direccion'] ?? null,
            'ciudad'           => $data['ciudad'] ?? null,
        ];

        if ($paciente) {
            DB::table('pacientes')->where('id', $paciente->id)->update($datosPaciente);
        } else {
            DB::table('pacientes')->insert(array_merge($datosPaciente, [
                'id'         => (string) Str::uuid(),
                'usuario_id' => $user->id,
                'creado_en'  => now(),
            ]));
        }

        return back()->with('success', 'Tu perfil fue actualizado correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
        if ($user->password_hash) {
            $rules['password_actual'] = ['required'];
        }

        $data = $request->validate($rules, [
            'password_actual.required' => 'Ingresa tu contraseña actual.',
            'password.confirmed'       => 'Las contraseñas no coinciden.',
            'password.min'             => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if ($user->password_hash && ! Hash::check($data['password_actual'], $user->password_hash)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        DB::table('usuarios')->where('id', $user->id)->update([
            'password_hash'  => Hash::make($data['password']),
            'actualizado_en' => now(),
        ]);

        return back()->with('success', 'Tu contraseña fue actualizada.');
    }
}
