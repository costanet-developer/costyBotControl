<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Interacciones</h2>
            <span class="text-xs text-dark-muted">Selecciona un cliente para ver su interacción</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                <a href="{{ route('interacciones.index') }}" class="rounded-lg border {{ !request()->filled('pago') ? 'border-corp/50 bg-corp/5' : 'border-dark-border bg-dark-panel' }} p-3 hover:border-corp/50 transition-colors">
                    <span class="block text-[10px] uppercase tracking-wider text-dark-muted">Todas las interacciones</span>
                    <span class="block text-xl font-semibold text-dark-text mt-1">{{ number_format($resumenPagos['total']) }}</span>
                </a>
                <a href="{{ route('interacciones.index', ['pago' => 'procesado']) }}" class="rounded-lg border {{ request('pago') === 'procesado' ? 'border-green-500/50 bg-green-500/5' : 'border-dark-border bg-dark-panel' }} p-3 hover:border-green-500/50 transition-colors">
                    <span class="block text-[10px] uppercase tracking-wider text-dark-muted">Pagos procesados</span>
                    <span class="block text-xl font-semibold text-green-400 mt-1">{{ number_format($resumenPagos['procesados']) }}</span>
                </a>
                <a href="{{ route('interacciones.index', ['pago' => 'recibido_no_procesado']) }}" class="rounded-lg border {{ request('pago') === 'recibido_no_procesado' ? 'border-amber-500/50 bg-amber-500/5' : 'border-dark-border bg-dark-panel' }} p-3 hover:border-amber-500/50 transition-colors">
                    <span class="block text-[10px] uppercase tracking-wider text-dark-muted">Comprobante recibido</span>
                    <span class="block text-xl font-semibold text-amber-400 mt-1">{{ number_format($resumenPagos['recibidos']) }}</span>
                </a>
                <a href="{{ route('interacciones.index', ['pago' => 'procesado_sin_comprobante']) }}" class="rounded-lg border {{ request('pago') === 'procesado_sin_comprobante' ? 'border-red-500/50 bg-red-500/5' : 'border-dark-border bg-dark-panel' }} p-3 hover:border-red-500/50 transition-colors">
                    <span class="block text-[10px] uppercase tracking-wider text-dark-muted">Procesados sin evidencia</span>
                    <span class="block text-xl font-semibold {{ $resumenPagos['procesados_sin_comprobante'] ? 'text-red-400' : 'text-green-400' }} mt-1">{{ number_format($resumenPagos['procesados_sin_comprobante']) }}</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 lg:h-[calc(100vh-230px)] lg:overflow-hidden">

                {{-- Panel izquierdo: filtros + listado --}}
                <div class="lg:col-span-2 bg-dark-panel border border-dark-border rounded-lg flex flex-col lg:overflow-hidden">
                    <form method="GET" class="p-3 border-b border-dark-border grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <select name="pago" class="sm:col-span-2 bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Pago: todos los estados</option>
                            <option value="procesado" @selected(request('pago') === 'procesado')>Pago procesado</option>
                            <option value="recibido_no_procesado" @selected(request('pago') === 'recibido_no_procesado')>Comprobante recibido, no procesado</option>
                            <option value="procesado_sin_comprobante" @selected(request('pago') === 'procesado_sin_comprobante')>Procesado sin comprobante enlazado</option>
                            <option value="sin_comprobante" @selected(request('pago') === 'sin_comprobante')>Sin comprobante ni pago procesado</option>
                        </select>
                        <select name="bot" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Bot: todos</option>
                            @foreach($bots as $b)
                                <option value="{{ $b }}" @selected(request('bot') === $b)>{{ $b }}</option>
                            @endforeach
                        </select>
                        <select name="resultado" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Resultado: todos</option>
                            @foreach($resultados as $r)
                                <option value="{{ $r }}" @selected(request('resultado') === $r)>{{ $r }}</option>
                            @endforeach
                        </select>
                        <select name="intencion" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Intención: todas</option>
                            @foreach($intenciones as $intencion)
                                <option value="{{ $intencion }}" @selected(request('intencion') === $intencion)>{{ \Illuminate\Support\Str::headline($intencion) }}</option>
                            @endforeach
                        </select>
                        <select name="estado" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Estado auditoría: todos</option>
                            <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>Pendiente</option>
                            <option value="EN_REVISION" @selected(request('estado') === 'EN_REVISION')>En Revisión</option>
                            <option value="APROBADO" @selected(request('estado') === 'APROBADO')>Aprobado</option>
                            <option value="RECHAZADO" @selected(request('estado') === 'RECHAZADO')>Rechazado</option>
                        </select>
                        <input type="date" name="desde" value="{{ request('desde') }}" title="Fecha desde" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                        <input type="date" name="hasta" value="{{ request('hasta') }}" title="Fecha hasta" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                        <select name="por_pagina" class="bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            @foreach([15, 30, 50] as $cantidad)
                                <option value="{{ $cantidad }}" @selected((int) request('por_pagina', 15) === $cantidad)>{{ $cantidad }} por página</option>
                            @endforeach
                        </select>
                        <div class="flex gap-2 sm:col-span-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cédula, teléfono, nombre..." class="flex-1 min-w-0 bg-dark-card border border-dark-border rounded px-2.5 py-1.5 text-xs text-dark-text placeholder-dark-muted focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <button type="submit" class="bg-corp hover:bg-corp-dim text-dark-bg font-medium px-3 py-1.5 rounded text-xs transition-colors shrink-0">Filtrar</button>
                            @if(request()->query())
                                <a href="{{ route('interacciones.index') }}" class="border border-dark-border hover:border-corp text-dark-muted hover:text-dark-text px-3 py-1.5 rounded text-xs transition-colors shrink-0">Limpiar</a>
                            @endif
                        </div>
                    </form>

                    <div class="px-4 py-2 border-b border-dark-border text-[10px] uppercase tracking-wider text-dark-muted">
                        {{ number_format($sesiones->total()) }} resultado(s)
                        @if($sesiones->total()) · mostrando {{ $sesiones->firstItem() }}–{{ $sesiones->lastItem() }} @endif
                    </div>

                    <div class="flex-1 overflow-y-auto scroll-bonito divide-y divide-dark-border" id="lista">
                        @forelse($sesiones as $sesion)
                            @php
                                $estadoPago = \App\Support\InteraccionPresentador::estadoPago($sesion);
                                $compResumen = $sesion->comprobantePrincipal ?: $sesion->comprobantes->first();
                            @endphp
                            <button type="button"
                                    data-sesion-id="{{ $sesion->sesion_id }}"
                                    onclick="cargarDetalle('{{ $sesion->sesion_id }}')"
                                    class="w-full text-left px-4 py-3 hover:bg-dark-card transition-colors border-l-2 border-transparent {{ $loop->first ? 'bg-dark-card border-corp' : '' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-dark-text truncate">
                                        {{ $sesion->cliente?->nombre ?? ($sesion->cedula ? 'Cliente ' . $sesion->cedula : 'Sin identificar') }}
                                    </span>
                                    @foreach($sesion->comprobantes->pluck('estado_auditoria')->unique() as $est)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold uppercase
                                            @switch($est)
                                                @case('PENDIENTE') bg-amber-400/10 text-dark-text @break
                                                @case('EN_REVISION') bg-blue-400/10 text-blue-400 @break
                                                @case('APROBADO') bg-green-400/10 text-green-400 @break
                                                @case('RECHAZADO') bg-red-400/10 text-red-400 @break
                                                @default bg-dark-muted/10 text-dark-muted
                                            @endswitch
                                        ">{{ $est }}</span>
                                    @endforeach
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold
                                        @if($estadoPago === 'procesado') bg-green-500/10 text-green-400
                                        @elseif($estadoPago === 'procesado_sin_comprobante') bg-red-500/10 text-red-400
                                        @elseif($estadoPago === 'recibido_no_procesado') bg-amber-500/10 text-amber-400
                                        @else bg-dark-card text-dark-muted border border-dark-border @endif">
                                        {{ \App\Support\InteraccionPresentador::etiquetaPago($estadoPago) }}
                                    </span>
                                    @if($compResumen?->monto !== null)
                                        <span class="text-[10px] font-mono text-dark-text">${{ number_format((float) $compResumen->monto, 2) }}</span>
                                    @endif
                                    @if($compResumen?->numero_transaccion)
                                        <span class="text-[10px] font-mono text-dark-muted truncate max-w-36">#{{ $compResumen->numero_transaccion }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-dark-muted font-mono">
                                    {{ $sesion->numero_whatsapp }} · {{ $sesion->inicio?->format('d/m H:i') }}
                                    @if($sesion->comprobantes->count() > 1)
                                        <span class="text-dark-text">· {{ $sesion->comprobantes->count() }} comprobantes</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between mt-0.5">
                                    <span class="text-xs text-dark-muted truncate">{{ $sesion->intencion ? \Illuminate\Support\Str::headline($sesion->intencion) : $sesion->bot }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-dark-card border border-dark-border text-dark-muted shrink-0 ml-2">
                                        {{ $sesion->resultado ?? 'En curso' }}
                                    </span>
                                </div>
                                @if($sesion->documentos_identidad_count || $sesion->ultimaValidacionIdentidad || (float) $sesion->credito_total > 0)
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @if($sesion->documentos_identidad_count)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-dark-card text-dark-muted border border-dark-border">ID {{ $sesion->documentos_identidad_count }}/2</span>
                                        @endif
                                        @if($sesion->ultimaValidacionIdentidad)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $sesion->ultimaValidacionIdentidad->estado_kyc === 'validada' ? 'bg-green-500/10 text-green-400' : 'bg-dark-card text-dark-muted' }}">KYC {{ \App\Support\InteraccionPresentador::estadoLegible($sesion->ultimaValidacionIdentidad->estado_kyc) }}</span>
                                        @endif
                                        @if((float) $sesion->credito_total > 0)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-400">Crédito ${{ number_format((float) $sesion->credito_total, 2) }}</span>
                                        @endif
                                    </div>
                                @endif
                            </button>
                        @empty
                            <p class="px-5 py-10 text-center text-dark-muted text-sm">No se encontraron interacciones</p>
                        @endforelse
                    </div>

                    <div class="p-3 border-t border-dark-border">
                        {{ $sesiones->links() }}
                    </div>
                </div>

                {{-- Panel derecho: detalle --}}
                <div class="lg:col-span-3 bg-dark-panel border border-dark-border rounded-lg lg:overflow-y-auto scroll-bonito" id="detalle-container">
                    <div class="p-8 text-center text-dark-muted text-sm">Selecciona una interacción para ver el detalle</div>
                </div>

            </div>
        </div>
    </div>

    <div id="lightbox" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 sm:p-8 hidden" onclick="if(event.target===this)cerrarImagen()">
        <button type="button" onclick="cerrarImagen()" class="absolute top-4 right-4 text-dark-muted hover:text-white text-3xl leading-none w-10 h-10" aria-label="Cerrar">&times;</button>
        <img id="lightbox-img" src="" alt="Comprobante" class="max-w-full max-h-full object-contain rounded">
    </div>

    @include('interacciones.partials.scripts')

    <script>
    window.sesionActual = null;
    const detalleContainer = document.getElementById('detalle-container');

    function marcarActiva(id) {
        document.querySelectorAll('[data-sesion-id]').forEach((el) => {
            const activa = el.dataset.sesionId === id;
            el.classList.toggle('bg-dark-card', activa);
            el.classList.toggle('border-corp', activa);
        });
    }

    function initAlpineContenido() {
        if (window.Alpine && window.Alpine.initTree) {
            window.Alpine.initTree(detalleContainer);
        } else {
            setTimeout(initAlpineContenido, 100);
        }
    }

    window.cargarDetalle = function (id) {
        window.sesionActual = id;
        marcarActiva(id);
        history.replaceState(null, '', '#sesion-' + encodeURIComponent(id));
        detalleContainer.innerHTML = '<div class="p-8 text-center text-dark-muted text-sm">Cargando interacción...</div>';

        fetch('/interacciones/' + encodeURIComponent(id) + '/detalle')
            .then((r) => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then((html) => {
                detalleContainer.innerHTML = html;
                initAlpineContenido();
                if (window.innerWidth < 1024) {
                    detalleContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            })
            .catch(() => {
                detalleContainer.innerHTML = '<div class="p-8 text-center text-red-400 text-sm">No se pudo cargar el detalle de la interacción.</div>';
            });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const hash = decodeURIComponent(location.hash || '').match(/^#sesion-(.+)$/);
        if (hash) {
            const fila = document.querySelector('[data-sesion-id="' + hash[1] + '"]');
            if (fila) {
                cargarDetalle(hash[1]);
                return;
            }
        }
        const primera = document.querySelector('[data-sesion-id]');
        if (primera) cargarDetalle(primera.dataset.sesionId);
    });
    </script>
</x-app-layout>
