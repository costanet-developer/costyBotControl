<x-guest-layout>
    <x-auth-session-status class="mb-4 text-green-400" :status="session('status')" />

    <h2 class="font-sans text-xl font-semibold mb-6 text-center">Iniciar Sesión</h2>

    @if($errors->has('email') && str_contains($errors->first('email'), 'bloqueada'))
        <div class="mb-4 px-4 py-3 rounded bg-red-900/40 border border-red-700/50 text-red-300 text-sm text-center">{{ $errors->first('email') }}</div>
    @elseif($errors->has('email'))
        <div class="mb-4 px-4 py-3 rounded bg-amber-900/40 border border-amber-700/50 text-dark-text text-sm text-center">{{ $errors->first('email') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-dark-muted mb-1">Correo electrónico</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                class="w-full bg-dark-card border border-dark-border rounded px-3 py-2.5 text-sm text-dark-text placeholder-dark-muted focus:border-corp focus:ring-1 focus:ring-corp outline-none transition-colors"
                placeholder="usuario@costanet.ec">
        </div>

        <div class="mt-5">
            <label for="password" class="block text-sm font-medium text-dark-muted mb-1">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="w-full bg-dark-card border border-dark-border rounded px-3 py-2.5 text-sm text-dark-text placeholder-dark-muted focus:border-corp focus:ring-1 focus:ring-corp outline-none transition-colors"
                placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-400 text-xs" />
        </div>

        <div class="flex items-center justify-between mt-5">
            <label for="remember_me" class="flex items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded bg-dark-card border-dark-border text-corp focus:ring-corp focus:ring-offset-0">
                <span class="text-sm text-dark-muted">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-corp hover:text-corp-dim transition-colors">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <div class="mt-6">
            <button type="submit"
                class="w-full bg-corp hover:bg-corp-dim text-dark-bg font-semibold py-2.5 px-4 rounded transition-colors text-sm tracking-wide uppercase">
                Ingresar
            </button>
        </div>
    </form>
</x-guest-layout>
