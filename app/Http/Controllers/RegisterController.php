<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showForm()
    {
        // Si ya está logueado, redirigir a su panel
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            // Paso 1 — Cuenta
            'nombre'    => ['required', 'string', 'max:100'],
            'apellido'  => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'password'  => ['required', 'confirmed', Password::min(8)],

            // Paso 2 — Contacto
            'telefono'         => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero'           => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:500'],
            'ciudad'           => ['nullable', 'string', 'max:100'],

            // Paso 3 — Médico
            'tipo_sangre'          => ['nullable', 'string', 'max:5'],
            'alergias'             => ['nullable', 'string', 'max:1000'],
            'enfermedades_cronicas'=> ['nullable', 'string', 'max:1000'],
            'medicamentos_actuales'=> ['nullable', 'string', 'max:500'],
            'contacto_emergencia'  => ['nullable', 'string', 'max:200'],
            'telefono_emergencia'  => ['nullable', 'string', 'max:20'],
        ], [
            'nombre.required'    => 'El nombre es obligatorio.',
            'apellido.required'  => 'El apellido es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.unique'       => 'Este correo ya está registrado. ¿Quieres iniciar sesión?',
            'email.email'        => 'Ingresa un correo válido.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);

        // Crear usuario y paciente en una sola transacción
        DB::transaction(function () use ($data, $request) {
            $usuarioId = (string) Str::uuid();

            // 1. Insertar en usuarios
            DB::table('usuarios')->insert([
                'id'            => $usuarioId,
                'nombre'        => $data['nombre'],
                'apellido'      => $data['apellido'],
                'email'         => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'rol'           => 'cliente',
                'telefono'      => $data['telefono'] ?? null,
                'activo'        => 1,
                'creado_en'     => now(),
                'actualizado_en'=> now(),
            ]);

            // 2. Insertar en pacientes
            DB::table('pacientes')->insert([
                'id'                   => (string) Str::uuid(),
                'usuario_id'           => $usuarioId,
                'fecha_nacimiento'     => $data['fecha_nacimiento'] ?? null,
                'genero'               => $data['genero'] ?? null,
                'direccion'            => $data['direccion'] ?? null,
                'ciudad'               => $data['ciudad'] ?? null,
                'tipo_sangre'          => $data['tipo_sangre'] ?? null,
                'alergias'             => $data['alergias'] ?? null,
                'enfermedades_cronicas'=> $data['enfermedades_cronicas'] ?? null,
                'medicamentos_actuales'=> $data['medicamentos_actuales'] ?? null,
                'contacto_emergencia'  => $data['contacto_emergencia'] ?? null,
                'telefono_emergencia'  => $data['telefono_emergencia'] ?? null,
                'creado_en'            => now(),
            ]);
        });

        // Iniciar sesión automáticamente
        Auth::attempt([
            'email'    => $data['email'],
            'password' => $data['password'],
            'activo'   => 1,
        ]);

        $request->session()->regenerate();

        return redirect()->route('cliente.dashboard')
            ->with('success', '¡Bienvenido/a, ' . $data['nombre'] . '! Tu cuenta fue creada exitosamente.');
    }
}