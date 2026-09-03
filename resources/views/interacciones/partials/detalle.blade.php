<div class="p-5 sm:p-6" x-data="{ tab: 'resumen' }">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-sans text-lg font-semibold text-dark-text">{{ $sesion->cliente?->nombre ?? ($sesion->cedula ? 'Cliente ' . $sesion->cedula : 'Sin identificar') }}</h3>
                @if($sesion->estado_sesion)
                    <span class="text-[10px] px-2 py-0.5 rounded-full border border-dark-border text-dark-muted uppercase tracking-wider">{{ $sesion->estado_sesion }}</span>
                @endif
            </div>
            <p class="text-sm text-dark-muted font-mono mt-1 break-all">{{ $sesion->numero_whatsapp }}</p>
            <p class="text-xs text-dark-muted font-mono mt-0.5 break-all">Sesión: {{ $sesion->sesion_id }}</p>
        </div>
        <div class="flex flex-wrap sm:flex-col sm:items-end gap-2 shrink-0">
            @php $resultado = $sesion->resultado; @endphp
            <span class="text-xs px-3 py-1 rounded border font-medium uppercase tracking-wider
                @if($resultado === 'reactivado') border-green-600/40 text-green-400 bg-green-400/5
                @elseif($resultado && str_contains($resultado, 'abandonado')) border-red-600/40 text-red-400 bg-red-400/5
                @else border-dark-border text-dark-muted bg-dark-card @endif">
                {{ $resultado ? \Illuminate\Support\Str::headline($resultado) : 'En curso' }}
            </span>
            <span class="text-xs px-3 py-1 rounded border
                @if($presentacion['estado_pago'] === 'procesado') border-green-500/30 text-green-400 bg-green-500/5
                @elseif($presentacion['estado_pago'] === 'procesado_sin_comprobante') border-red-500/30 text-red-400 bg-red-500/5
                @elseif($presentacion['estado_pago'] === 'recibido_no_procesado') border-amber-500/30 text-amber-400 bg-amber-500/5
                @else border-dark-border text-dark-muted bg-dark-card @endif">
                {{ \App\Support\InteraccionPresentador::etiquetaPago($presentacion['estado_pago']) }}
            </span>
            @if($presentacion['total_credito'] > 0)
                <span class="text-xs px-3 py-1 rounded border border-blue-500/30 text-blue-400 bg-blue-400/5">Crédito ${{ number_format($presentacion['total_credito'], 2) }}</span>
            @endif
        </div>
    </div>

    <div class="flex gap-1 overflow-x-auto border-b border-dark-border mb-5" role="tablist" aria-label="Detalle de la interacción">
        @foreach([
            'resumen' => ['Resumen', null],
            'recorrido' => ['Recorrido', $sesion->eventos->count()],
            'pagos' => ['Pagos', $sesion->comprobantes->count()],
            'identidad' => ['Identidad y OTP', $sesion->documentosIdentidad->count()],
        ] as $clave => [$texto, $cantidad])
            <button type="button" @click="tab = '{{ $clave }}'"
                    :class="tab === '{{ $clave }}' ? 'border-corp text-dark-text' : 'border-transparent text-dark-muted hover:text-dark-text'"
                    class="shrink-0 px-3 py-2.5 border-b-2 text-xs font-medium transition-colors">
                {{ $texto }}@if($cantidad !== null) <span class="ml-1 text-[10px] opacity-75">{{ $cantidad }}</span>@endif
            </button>
        @endforeach
    </div>

    <section x-show="tab === 'resumen'" x-cloak>
        @include('interacciones.partials.resumen')
    </section>
    <section x-show="tab === 'recorrido'" x-cloak>
        @include('interacciones.partials.recorrido')
    </section>
    <section x-show="tab === 'pagos'" x-cloak>
        @include('interacciones.partials.pagos')
    </section>
    <section x-show="tab === 'identidad'" x-cloak>
        @include('interacciones.partials.identidad')
    </section>
</div>
