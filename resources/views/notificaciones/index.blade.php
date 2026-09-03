<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div><h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Notificaciones</h2><p class="text-xs text-dark-muted mt-1">Alertas personales y escalaciones generadas por CostyBot Control.</p></div>
            @if(auth()->user()->unreadNotifications()->count())
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">@csrf @method('PATCH')<button class="px-4 py-2 rounded border border-dark-border text-xs text-dark-muted hover:text-dark-text hover:border-corp">Marcar todas como leídas</button></form>
            @endif
        </div>
    </x-slot>

    <div class="py-6"><div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-dark-border flex items-center justify-between"><h3 class="text-sm font-semibold text-dark-text">Historial</h3><span class="text-xs text-dark-muted">{{ $notificaciones->firstItem() ?? 0 }}–{{ $notificaciones->lastItem() ?? 0 }} de {{ number_format($notificaciones->total()) }}</span></div>
            <div class="divide-y divide-dark-border">
                @forelse($notificaciones as $notificacion)
                    @php
                        $nivel = $notificacion->data['nivel'] ?? 'media';
                        $sinLeer = $notificacion->read_at === null;
                    @endphp
                    <a href="{{ route('notificaciones.abrir', $notificacion->id) }}" class="block px-5 py-4 {{ $sinLeer ? 'bg-corp/5' : '' }} hover:bg-dark-card transition-colors">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 w-2.5 h-2.5 rounded-full shrink-0 {{ $nivel === 'critica' || $nivel === 'alta' ? 'bg-red-400' : ($nivel === 'media' ? 'bg-amber-400' : 'bg-blue-400') }}"></span>
                            <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><p class="text-sm font-semibold text-dark-text">{{ $notificacion->data['titulo'] ?? 'Alerta operativa' }}</p>@if($sinLeer)<span class="text-[9px] px-2 py-0.5 rounded-full bg-corp/10 text-corp">NUEVA</span>@endif</div><p class="text-xs text-dark-muted mt-1">{{ $notificacion->data['mensaje'] ?? 'Existe un caso que requiere revisión.' }}</p><p class="text-[10px] text-dark-muted font-mono mt-2">{{ $notificacion->created_at?->format('d/m/Y H:i') }} · Caso #{{ $notificacion->data['caso_id'] ?? '—' }}</p></div>
                            <span class="text-info text-xs shrink-0">Revisar →</span>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-14 text-center"><p class="text-sm text-green-400">No tienes notificaciones pendientes.</p></div>
                @endforelse
            </div>
            <div class="px-5 py-4 border-t border-dark-border">{{ $notificaciones->links() }}</div>
        </div>
    </div></div>
</x-app-layout>
