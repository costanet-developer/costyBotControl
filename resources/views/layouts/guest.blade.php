<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CostyBO') }} — Panel de Auditoría</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|ibm+plex+mono:400,500" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased" style="background-color: var(--color-bg); color: var(--color-text);">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">
        <button onclick="toggleTheme()" title="Cambiar tema" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded border text-sm transition-colors" style="border-color: var(--color-border); color: var(--color-muted); background-color: var(--color-card);">
            <svg class="w-4 h-4 dark-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg class="w-4 h-4 light-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>

        <div class="mb-2 text-center">
            <img src="{{ asset('costy_backoffice.png') }}" alt="Costy" class="w-[170px] h-[170px] mx-auto object-contain">
        </div>

        <div class="w-full sm:max-w-md px-8 py-8 bg-dark-panel border border-dark-border rounded-lg shadow-2xl">
            {{ $slot }}
        </div>

        <footer class="mt-8 text-center text-xs text-dark-muted">
            <span class="font-semibold text-corp">COSTANET+</span>
            <span class="mx-1.5">·</span>
            <a href="https://www.costanetplus.net" target="_blank" rel="noopener noreferrer" class="hover:text-corp transition-colors">www.costanetplus.net</a>
        </footer>
    </div>

    <script>
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') === 'dark';
        if (isDark) {
            html.removeAttribute('data-theme');
        } else {
            html.setAttribute('data-theme', 'dark');
        }
        localStorage.setItem('theme', isDark ? 'light' : 'dark');
        document.querySelectorAll('.dark-icon, .light-icon').forEach(el => el.classList.toggle('hidden'));
    }
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.querySelectorAll('.dark-icon').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.light-icon').forEach(el => el.classList.remove('hidden'));
    }
    </script>
</body>
</html>
