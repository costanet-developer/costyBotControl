<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->string('email'))->first();

        if ($user && $user->bloqueado) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está bloqueada por seguridad. Contacta al administrador.',
            ]);
        }

        if ($user && !$user->activo) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            if ($user) {
                $user->increment('intentos_fallidos');
                $restantes = 3 - $user->intentos_fallidos;
                if ($restantes <= 0) {
                    $user->update(['bloqueado' => true]);
                    $mensaje = 'Has agotado los intentos. Tu cuenta ha sido bloqueada. Contacta al administrador.';
                } else {
                    $mensaje = "Credenciales incorrectas. Te quedan {$restantes} de 3 intentos.";
                }
            } else {
                $mensaje = trans('auth.failed');
            }

            throw ValidationException::withMessages([
                'email' => $mensaje,
            ]);
        }

        $user = Auth::user();
        $user->update([
            'intentos_fallidos' => 0,
            'ultimo_acceso' => now(),
        ]);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
