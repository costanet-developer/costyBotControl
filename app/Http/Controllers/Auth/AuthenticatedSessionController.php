<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditoriaLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        AuditoriaLog::create([
            'usuario_id' => Auth::id(),
            'accion' => 'login',
            'modulo' => 'Auth',
            'entidad' => 'User',
            'entidad_id' => Auth::id(),
            'direccion_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => 'Inicio de sesión exitoso',
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuditoriaLog::create([
            'usuario_id' => Auth::id(),
            'accion' => 'logout',
            'modulo' => 'Auth',
            'entidad' => 'User',
            'entidad_id' => Auth::id(),
            'direccion_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => 'Cierre de sesión',
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
