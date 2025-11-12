<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLogin() {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'username' => ['required','string','max:50'],
            'password' => ['required','string','min:4'],
        ], [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['username' => 'Credenciales inválidas.'])->onlyInput('username');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }
}
