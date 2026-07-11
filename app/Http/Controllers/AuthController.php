<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

if (Auth::attempt([
    'email' => $credentials['email'],
    'password' => $credentials['password'],
    'activo' => 1
])) {
    $request->session()->regenerate();

    $user = auth()->user();

    return match ($user->rol) {
        'admin' => redirect('/admin'),
        'dentista' => redirect('/dentista'),
        'asistente' => redirect('/asistente'),
        default => redirect('/cliente'),
    };
}

        return back()->withErrors([
            'email' => 'Credenciales incorrectas'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}