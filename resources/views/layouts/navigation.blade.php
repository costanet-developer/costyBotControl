@php $notificacionesSinLeer = auth()->user()->unreadNotifications()->count(); @endphp
<nav x-data="{ open: false }" class="bg-dark-panel border-b border-dark-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="{{ asset('costy_banner.png') }}" alt="CostyBO" class="w-[150px] h-auto object-contain">
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @can('interacciones.ver')
                        <x-nav-link :href="route('interacciones.index')" :active="request()->routeIs('interacciones.*')">
                            Interacciones
                        </x-nav-link>
                        <x-nav-link :href="route('pendientes.index')" :active="request()->routeIs('pendientes.*')">
                            Pendientes
                        </x-nav-link>
                    @endcan
                    @can('usuarios.ver')
                        <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                            Usuarios
                        </x-nav-link>
                    @endcan
                    @can('reportes.ver')
                        <x-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.*')">
                            Reportes
                        </x-nav-link>
                    @endcan
                    @can('auditoria.ver')
                        <x-nav-link :href="route('resumen-gerencial.index')" :active="request()->routeIs('resumen-gerencial.*')">
                            Gerencial
                        </x-nav-link>
                        <x-nav-link :href="route('auditoria.index')" :active="request()->routeIs('auditoria.*')">
                            Auditoría
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <a href="{{ route('notificaciones.index') }}" title="Notificaciones" class="relative mr-3 w-9 h-9 flex items-center justify-center rounded border border-dark-border text-dark-muted bg-dark-card hover:text-dark-text hover:border-corp transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if($notificacionesSinLeer)
                        <span class="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[9px] font-bold">{{ min($notificacionesSinLeer, 99) }}</span>
                    @endif
                </a>
                <button onclick="toggleTheme()" title="Cambiar tema" class="mr-3 w-8 h-8 flex items-center justify-center rounded border text-sm transition-colors" style="border-color: var(--color-border); color: var(--color-muted); background-color: var(--color-card);">
                    <svg class="w-4 h-4 dark-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg class="w-4 h-4 light-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-dark-border text-sm leading-4 font-medium rounded-md text-dark-muted bg-dark-card hover:text-dark-text focus:outline-none transition-colors duration-150">
                            <div>{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar Sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-dark-muted hover:text-dark-text hover:bg-dark-card focus:outline-none focus:bg-dark-card focus:text-dark-text transition duration-150">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    @can('interacciones.ver')
                        <x-responsive-nav-link :href="route('interacciones.index')" :active="request()->routeIs('interacciones.*')">
                            Interacciones
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('pendientes.index')" :active="request()->routeIs('pendientes.*')">
                            Pendientes
                        </x-responsive-nav-link>
                    @endcan
                    @can('usuarios.ver')
                        <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                            Usuarios
                        </x-responsive-nav-link>
                    @endcan
                    @can('reportes.ver')
                        <x-responsive-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.*')">
                            Reportes
                        </x-responsive-nav-link>
                    @endcan
                    @can('auditoria.ver')
                        <x-responsive-nav-link :href="route('resumen-gerencial.index')" :active="request()->routeIs('resumen-gerencial.*')">
                            Resumen gerencial
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('auditoria.index')" :active="request()->routeIs('auditoria.*')">
                            Auditoría
                        </x-responsive-nav-link>
                    @endcan
                    <x-responsive-nav-link :href="route('notificaciones.index')" :active="request()->routeIs('notificaciones.*')">
                        Notificaciones @if($notificacionesSinLeer) ({{ $notificacionesSinLeer }}) @endif
                    </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-dark-border">
            <div class="px-4">
                <div class="font-medium text-base text-dark-text">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</div>
                <div class="font-medium text-sm text-dark-muted">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Perfil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar Sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
