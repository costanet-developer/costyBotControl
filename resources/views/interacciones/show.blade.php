<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('interacciones.index') }}" class="text-xs text-dark-muted hover:text-corp transition-colors">&larr; Volver</a>
                <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight mt-1">Detalle de Interacción</h2>
            </div>
            <span class="text-xs font-mono text-dark-muted">{{ $sesion->sesion_id }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-900/40 border border-green-700/50 text-green-300 text-sm">{{ session('success') }}</div>
            @endif
            @if(isset($errors) && $errors->any())
                <div class="mb-4 px-4 py-3 rounded bg-red-900/40 border border-red-700/50 text-red-300 text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="bg-dark-panel border border-dark-border rounded-lg">
                @include('interacciones.partials.detalle', ['sesion' => $sesion])
            </div>
        </div>
    </div>

    <div id="lightbox" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 sm:p-8 hidden" onclick="if(event.target===this)cerrarImagen()">
        <button type="button" onclick="cerrarImagen()" class="absolute top-4 right-4 text-dark-muted hover:text-white text-3xl leading-none w-10 h-10" aria-label="Cerrar">&times;</button>
        <img id="lightbox-img" src="" alt="Comprobante" class="max-w-full max-h-full object-contain rounded">
    </div>

    @include('interacciones.partials.scripts')
</x-app-layout>
