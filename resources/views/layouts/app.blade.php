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
<body class="font-sans antialiased min-h-screen flex flex-col" style="background-color: var(--color-bg); color: var(--color-text);"
    @auth data-user-name="{{ auth()->user()->nombre }}" @endauth>
    @include('layouts.navigation')

    @isset($header)
        <header class="bg-dark-panel border-b border-dark-border shadow-sm">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-dark-border py-4 px-4 sm:px-6 lg:px-8 text-center text-xs text-dark-muted">
        <span class="font-semibold text-corp">COSTANET+</span>
        <span class="mx-1.5">·</span>
        <a href="https://www.costanetplus.net" target="_blank" rel="noopener noreferrer" class="hover:text-corp transition-colors">www.costanetplus.net</a>
        <span class="mx-1.5">·</span>
        <span>Internet · Cámaras · TV · Soporte 24/7</span>
        <span class="mx-1.5">·</span>
        <span>Asistente digital Costy</span>
    </footer>

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

    @if (session('error'))
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}',
        timer: 4000,
        timerProgressBar: true,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
    });
    </script>
    @endif

    @if (session('success'))
    <script>
    Swal.fire({
        icon: 'success',
        title: '{{ session('success') }}',
        timer: 3000,
        timerProgressBar: true,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
    });
    </script>
    @endif
</body>
</html>
