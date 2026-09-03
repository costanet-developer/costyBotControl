<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Panel de control</h2>
                <p class="text-xs text-dark-muted mt-1">Estado operativo de interacciones, pagos y validaciones.</p>
            </div>
            <span class="text-xs text-dark-muted font-mono">Actualizado {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
                <a href="{{ route('interacciones.index', ['desde' => now()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}" class="bg-dark-panel border border-dark-border rounded-lg p-4 hover:border-corp/50 transition-colors">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Interacciones hoy</p>
                    <p class="text-2xl font-semibold text-dark-text mt-1">{{ number_format($sesionesHoy) }}</p>
                </a>
                <a href="{{ route('interacciones.index', ['pago' => 'procesado', 'desde' => now()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}" class="bg-dark-panel border border-dark-border rounded-lg p-4 hover:border-green-500/50 transition-colors">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Pagos hoy</p>
                    <p class="text-2xl font-semibold text-green-400 mt-1">{{ number_format($pagosHoy) }}</p>
                </a>
                <a href="{{ route('interacciones.index', ['pago' => 'procesado']) }}" class="bg-dark-panel border border-dark-border rounded-lg p-4 hover:border-green-500/50 transition-colors">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Total procesados</p>
                    <p class="text-2xl font-semibold text-green-400 mt-1">{{ number_format($pagosProcesados) }}</p>
                </a>
                <a href="{{ route('interacciones.index', ['estado' => 'PENDIENTE']) }}" class="bg-dark-panel border border-dark-border rounded-lg p-4 hover:border-amber-500/50 transition-colors">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Auditoría pendiente</p>
                    <p class="text-2xl font-semibold text-amber-400 mt-1">{{ number_format($pendientes) }}</p>
                </a>
                <a href="{{ route('reportes.index', ['tipo' => 'procesado']) }}" class="bg-dark-panel border border-dark-border rounded-lg p-4 hover:border-blue-500/50 transition-colors">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Créditos pendientes</p>
                    <p class="text-2xl font-semibold text-blue-400 mt-1">${{ number_format($valorCreditosPendientes, 2) }}</p>
                    <p class="text-[10px] text-dark-muted mt-1">{{ $creditosPendientes }} registros</p>
                </a>
                <a href="{{ route('pendientes.index', ['tipo' => 'sin_evidencia']) }}" class="bg-dark-panel border {{ $procesadosSinEvidencia ? 'border-red-500/40' : 'border-dark-border' }} rounded-lg p-4 hover:border-red-500/60 transition-colors">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Sin evidencia</p>
                    <p class="text-2xl font-semibold {{ $procesadosSinEvidencia ? 'text-red-400' : 'text-green-400' }} mt-1">{{ number_format($procesadosSinEvidencia) }}</p>
                </a>
            </div>

            <section class="bg-dark-panel border border-dark-border rounded-lg overflow-hidden mb-6" aria-labelledby="kpi-title">
                <div class="px-5 py-4 border-b border-dark-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 id="kpi-title" class="font-semibold text-sm text-dark-text">KPI de experiencia y resolución</h3>
                        <p class="text-xs text-dark-muted mt-0.5">Comparativo por opción del menú · {{ $desdeKpi->format('d/m/Y') }} al {{ $hastaKpi->format('d/m/Y') }}</p>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <label for="periodo_kpi" class="text-xs text-dark-muted">Periodo</label>
                        <select id="periodo_kpi" name="periodo_kpi" onchange="this.form.submit()" class="rounded-md border-dark-border bg-dark-card text-xs text-dark-text focus:border-corp focus:ring-corp">
                            @foreach([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $dias => $etiqueta)
                                <option value="{{ $dias }}" @selected($periodoKpi === $dias)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-dark-border">
                    @foreach([
                        'reactivacion' => ['titulo' => 'Reactivación', 'descripcion' => 'Reactivar servicio'],
                        'saldo_pagar' => ['titulo' => 'Saldo a pagar', 'descripcion' => 'Valores a pagar'],
                    ] as $clave => $opcion)
                        @php
                            $indicador = $kpis[$clave];
                        @endphp
                        <article class="p-5">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-dark-text">{{ $opcion['titulo'] }}</h4>
                                    <p class="text-[10px] uppercase tracking-wider text-dark-muted mt-0.5">{{ $opcion['descripcion'] }}</p>
                                </div>
                                <span class="text-[10px] px-2 py-1 rounded-full bg-corp/10 text-corp">{{ number_format($indicador['fcr_total']) }} sesiones</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg border border-green-500/20 bg-green-500/5 p-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] uppercase tracking-wider text-green-400">FCR</span>
                                        <span title="Resoluciones automáticas en la misma sesión, sin transferencia manual." class="text-[10px] text-dark-muted cursor-help" aria-label="Ayuda sobre FCR">ⓘ</span>
                                    </div>
                                    @if($indicador['fcr'] !== null)
                                        <p class="text-2xl font-semibold text-green-400 mt-1">{{ number_format($indicador['fcr'], 1) }}%</p>
                                        <p class="text-[10px] text-dark-muted mt-1">{{ $indicador['fcr_resueltas'] }} de {{ $indicador['fcr_total'] }} resueltas</p>
                                    @else
                                        <p class="text-sm font-medium text-dark-muted mt-2">Sin sesiones</p>
                                        <p class="text-[10px] text-dark-muted mt-1">No hay base para calcular</p>
                                    @endif
                                </div>

                                <div class="rounded-lg border border-blue-500/20 bg-blue-500/5 p-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] uppercase tracking-wider text-blue-400">CES</span>
                                        <span title="Promedio de facilidad reportada por el cliente: 1 es muy difícil y 7 muy fácil." class="text-[10px] text-dark-muted cursor-help" aria-label="Ayuda sobre CES">ⓘ</span>
                                    </div>
                                    @if($indicador['ces'] !== null)
                                        <p class="text-2xl font-semibold text-blue-400 mt-1">{{ number_format($indicador['ces'], 1) }}<span class="text-sm text-dark-muted">/7</span></p>
                                        <p class="text-[10px] text-dark-muted mt-1">{{ $indicador['ces_respuestas'] }} {{ $indicador['ces_respuestas'] === 1 ? 'respuesta' : 'respuestas' }} · {{ number_format($indicador['ces_favorable'], 1) }}% favorable</p>
                                    @else
                                        <p class="text-sm font-medium text-dark-muted mt-2">Sin respuestas</p>
                                        <p class="text-[10px] text-dark-muted mt-1">{{ $indicador['ces_enviadas'] }} enviadas; esperando respuesta</p>
                                    @endif
                                    <p class="text-[10px] text-blue-300 mt-2">
                                        @if($indicador['ces_tasa_respuesta'] !== null)
                                            {{ number_format($indicador['ces_tasa_respuesta'], 1) }}% tasa de respuesta
                                        @else
                                            Sin envíos en el periodo
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mt-3" aria-label="Embudo de encuestas CES de {{ $opcion['titulo'] }}">
                                <div class="rounded border border-dark-border bg-dark-card/50 px-3 py-2">
                                    <span class="block text-[9px] uppercase tracking-wider text-dark-muted">Programadas</span>
                                    <span class="block text-sm font-semibold text-dark-text mt-0.5">{{ number_format($indicador['ces_programadas']) }}</span>
                                </div>
                                <div class="rounded border border-blue-500/20 bg-blue-500/5 px-3 py-2">
                                    <span class="block text-[9px] uppercase tracking-wider text-blue-300">Enviadas</span>
                                    <span class="block text-sm font-semibold text-blue-400 mt-0.5">{{ number_format($indicador['ces_enviadas']) }}</span>
                                </div>
                                <div class="rounded border border-amber-500/20 bg-amber-500/5 px-3 py-2">
                                    <span class="block text-[9px] uppercase tracking-wider text-amber-300">Pendientes</span>
                                    <span class="block text-sm font-semibold text-amber-400 mt-0.5">{{ number_format($indicador['ces_pendientes']) }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="px-5 py-3 border-t border-dark-border bg-dark-card/40 text-[10px] text-dark-muted">
                    FCR excluye derivaciones al área de Pagos. CES usa la pregunta de facilidad en escala de 1 a 7; una respuesta de 5 a 7 se considera favorable.
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-dark-panel border border-dark-border rounded-lg overflow-hidden">
                    <div class="px-5 py-4 border-b border-dark-border flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-sm text-dark-text">Últimos pagos procesados</h3>
                            <p class="text-xs text-dark-muted mt-0.5">Incluye reactivaciones detectadas por resultado, evento o comprobante.</p>
                        </div>
                        <a href="{{ route('interacciones.index', ['pago' => 'procesado']) }}" class="text-xs text-corp hover:text-corp-dim transition-colors shrink-0">Ver todos →</a>
                    </div>
                    <div class="divide-y divide-dark-border">
                        @forelse($ultimosPagos as $sesion)
                            @php
                                $comp = $sesion->comprobantePrincipal ?: $sesion->comprobantes->sortByDesc('id')->first();
                                $estadoPago = \App\Support\InteraccionPresentador::estadoPago($sesion);
                                $credito = $sesion->saldosFavor->sum(fn ($saldo) => (float) $saldo->excedente);
                            @endphp
                            <a href="{{ route('interacciones.show', $sesion->sesion_id) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-3.5 hover:bg-dark-card transition-colors">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium text-dark-text truncate">{{ $sesion->cliente?->nombre ?? ($sesion->cedula ? 'Cliente '.$sesion->cedula : 'Sin identificar') }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $estadoPago === 'procesado_sin_comprobante' ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400' }}">{{ \App\Support\InteraccionPresentador::etiquetaPago($estadoPago) }}</span>
                                    </div>
                                    <div class="text-[10px] text-dark-muted font-mono mt-1 truncate">{{ $sesion->numero_whatsapp }} · {{ $comp?->numero_transaccion ?: 'Sin transacción enlazada' }}</div>
                                </div>
                                <div class="flex items-center gap-4 shrink-0 sm:text-right">
                                    @if($credito > 0)<span class="text-xs text-blue-400">Crédito ${{ number_format($credito, 2) }}</span>@endif
                                    <div>
                                        <span class="block text-sm font-semibold {{ $comp ? 'text-green-400' : 'text-red-400' }}">${{ number_format((float) ($comp?->monto ?? 0), 2) }}</span>
                                        <span class="block text-[10px] text-dark-muted font-mono">{{ $sesion->inicio?->format('d/m H:i') }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-10 text-center text-dark-muted text-sm">No hay pagos procesados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-dark-panel border border-dark-border rounded-lg overflow-hidden">
                    <div class="px-5 py-4 border-b border-dark-border">
                        <h3 class="font-semibold text-sm text-dark-text">Prioridades operativas</h3>
                        <p class="text-xs text-dark-muted mt-0.5">Casos que requieren seguimiento interno.</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <a href="{{ route('pendientes.index', ['tipo' => 'sin_evidencia']) }}" class="flex items-center justify-between gap-3 rounded border border-red-500/20 bg-red-500/5 p-3 hover:border-red-500/40 transition-colors">
                            <div><span class="block text-xs font-medium text-red-400">Pagos sin evidencia</span><span class="block text-[10px] text-dark-muted mt-0.5">Reactivación exitosa sin comprobante asociado</span></div>
                            <span class="text-lg font-semibold text-red-400">{{ $procesadosSinEvidencia }}</span>
                        </a>
                        <a href="{{ route('pendientes.index', ['tipo' => 'en_revision']) }}" class="flex items-center justify-between gap-3 rounded border border-blue-500/20 bg-blue-500/5 p-3 hover:border-blue-500/40 transition-colors">
                            <div><span class="block text-xs font-medium text-blue-400">Comprobantes en revisión</span><span class="block text-[10px] text-dark-muted mt-0.5">Tomados por personal de auditoría</span></div>
                            <span class="text-lg font-semibold text-blue-400">{{ $enRevision }}</span>
                        </a>
                        <div class="flex items-center justify-between gap-3 rounded border border-amber-500/20 bg-amber-500/5 p-3">
                            <div><span class="block text-xs font-medium text-amber-400">KYC derivado</span><span class="block text-[10px] text-dark-muted mt-0.5">Validaciones que requieren revisión manual</span></div>
                            <span class="text-lg font-semibold text-amber-400">{{ $kycEnRevision }}</span>
                        </div>
                        <div class="pt-2 border-t border-dark-border text-xs text-dark-muted flex justify-between gap-3">
                            <span>Total de interacciones</span><span class="font-mono text-dark-text">{{ number_format($totalSesiones) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
