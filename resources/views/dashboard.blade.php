<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Panel de Auditoría</h2>
            <span class="text-xs text-dark-muted font-mono">Actualizado {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-dark-panel border border-dark-border rounded-lg p-5">
                    <p class="text-dark-muted text-xs uppercase tracking-wider">Sesiones Hoy</p>
                    <p class="text-3xl font-sans font-semibold text-corp mt-1">{{ $sesionesHoy }}</p>
                </div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-5">
                    <p class="text-dark-muted text-xs uppercase tracking-wider">Pendientes</p>
                    <p class="text-3xl font-sans font-semibold text-dark-text mt-1">{{ $pendientes }}</p>
                </div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-5">
                    <p class="text-dark-muted text-xs uppercase tracking-wider">Revisados</p>
                    <p class="text-3xl font-sans font-semibold text-green-400 mt-1">{{ $revisados }}</p>
                </div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-5">
                    <p class="text-dark-muted text-xs uppercase tracking-wider">Total Sesiones</p>
                    <p class="text-3xl font-sans font-semibold text-dark-text mt-1">{{ $totalSesiones }}</p>
                </div>
            </div>

            <div class="bg-dark-panel border border-dark-border rounded-lg">
                <div class="px-5 py-4 border-b border-dark-border flex items-center justify-between">
                    <h3 class="font-semibold text-sm text-dark-text">Últimas Interacciones</h3>
                    <a href="{{ route('interacciones.index') }}" class="text-xs text-corp hover:text-corp-dim transition-colors">Ver todas →</a>
                </div>
                <div class="divide-y divide-dark-border">
                    @forelse($ultimasSesiones as $sesion)
                        <a href="{{ route('interacciones.show', $sesion->sesion_id) }}" class="flex items-center justify-between px-5 py-3.5 hover:bg-dark-card transition-colors">
                            <div>
                                <span class="text-sm font-medium text-dark-text">{{ $sesion->cliente?->nombre ?? ($sesion->cedula ? 'Cliente ' . $sesion->cedula : 'Sin nombre') }}</span>
                                <span class="text-xs text-dark-muted ml-2 font-mono">{{ $sesion->numero_whatsapp }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-dark-muted font-mono">{{ $sesion->inicio?->format('d/m H:i') }}</span>
                                <span class="text-xs px-2 py-0.5 rounded bg-dark-card border border-dark-border text-dark-muted">{{ $sesion->estado_sesion }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-dark-muted text-sm">No hay interacciones registradas</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
