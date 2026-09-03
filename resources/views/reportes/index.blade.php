<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Control operativo</h2>
                <p class="text-xs text-dark-muted mt-1">Pagos, comprobantes, créditos y validaciones de identidad.</p>
            </div>
            <span class="text-xs text-dark-muted font-mono">Actualizado {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </x-slot>

    @php
        $tipoActual = request('tipo', 'procesado');
        $titulos = [
            'procesado' => 'Pagos procesados',
            'procesado_sin_comprobante' => 'Procesados sin evidencia',
            'recibido_no_procesado' => 'Comprobantes recibidos sin procesar',
            'sin_comprobante' => 'Interacciones sin comprobante',
            'todos' => 'Todas las interacciones',
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-[1700px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                <div class="bg-dark-panel border border-dark-border rounded-lg p-4">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Registros filtrados</p>
                    <p class="text-2xl font-semibold text-dark-text mt-1">{{ number_format($resumen['total']) }}</p>
                </div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-4">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Valor recibido</p>
                    <p class="text-2xl font-semibold text-green-400 mt-1">${{ number_format($resumen['monto'], 2) }}</p>
                </div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-4">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Crédito generado</p>
                    <p class="text-2xl font-semibold text-blue-400 mt-1">${{ number_format($resumen['credito'], 2) }}</p>
                </div>
                <div class="bg-dark-panel border {{ $resumen['sin_evidencia'] ? 'border-red-500/40' : 'border-dark-border' }} rounded-lg p-4">
                    <p class="text-[10px] uppercase tracking-wider text-dark-muted">Sin evidencia enlazada</p>
                    <p class="text-2xl font-semibold {{ $resumen['sin_evidencia'] ? 'text-red-400' : 'text-green-400' }} mt-1">{{ number_format($resumen['sin_evidencia']) }}</p>
                </div>
            </div>

            <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden mb-5">
                <form method="GET" action="{{ route('reportes.index') }}" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3 items-end">
                    <div class="xl:col-span-2">
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Tipo de reporte</label>
                        <select name="tipo" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            @foreach($titulos as $valor => $titulo)
                                <option value="{{ $valor }}" @selected($tipoActual === $valor)>{{ $titulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Desde</label>
                        <input type="date" name="desde" value="{{ request('desde') }}" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Hasta</label>
                        <input type="date" name="hasta" value="{{ request('hasta') }}" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Bot</label>
                        <select name="bot" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Todos</option>
                            @foreach($bots as $bot)
                                <option value="{{ $bot }}" @selected(request('bot') === $bot)>{{ $bot }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Banco</label>
                        <select name="banco" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Todos</option>
                            @foreach($bancos as $banco)
                                <option value="{{ $banco }}" @selected(request('banco') === $banco)>{{ $banco }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Auditoría</label>
                        <select name="estado_auditoria" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Todos</option>
                            @foreach(['PENDIENTE' => 'Pendiente', 'EN_REVISION' => 'En revisión', 'APROBADO' => 'Aprobado', 'RECHAZADO' => 'Rechazado', 'ANULADO' => 'Anulado'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected(request('estado_auditoria') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Filas</label>
                        <select name="por_pagina" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            @foreach([15, 30, 50] as $cantidad)
                                <option value="{{ $cantidad }}" @selected((int) request('por_pagina', 15) === $cantidad)>{{ $cantidad }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 xl:col-span-6">
                        <label class="block text-[10px] uppercase tracking-wider text-dark-muted mb-1">Buscar</label>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Cédula, WhatsApp, cliente, transacción o documento" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-xs text-dark-text placeholder-dark-muted focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div class="sm:col-span-2 xl:col-span-2 flex gap-2">
                        <button type="submit" class="flex-1 bg-corp hover:bg-corp-dim text-dark-bg font-medium px-4 py-2 rounded text-xs transition-colors">Aplicar filtros</button>
                        <a href="{{ route('reportes.index') }}" class="px-4 py-2 rounded border border-dark-border text-xs text-dark-muted hover:text-dark-text hover:border-corp transition-colors">Limpiar</a>
                    </div>
                </form>

                @can('reportes.exportar')
                    <div class="px-4 py-3 border-t border-dark-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-dark-card/30">
                        <p class="text-xs text-dark-muted">El Excel incluye origen y destino del pago, Control, Documento, crédito, KYC y resultado OTP. Nunca incluye códigos OTP.</p>
                        <form method="POST" action="{{ route('reportes.export') }}" class="shrink-0">
                            @csrf
                            @foreach(['tipo', 'desde', 'hasta', 'bot', 'banco', 'estado_auditoria', 'buscar'] as $filtro)
                                <input type="hidden" name="{{ $filtro }}" value="{{ request($filtro, $filtro === 'tipo' ? 'procesado' : '') }}">
                            @endforeach
                            <button type="submit" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded text-xs transition-colors">
                                Descargar Excel
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

            <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-dark-border flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold text-sm text-dark-text">{{ $titulos[$tipoActual] ?? 'Control operativo' }}</h3>
                    <span class="text-xs text-dark-muted">{{ $sesiones->firstItem() ?? 0 }}–{{ $sesiones->lastItem() ?? 0 }} de {{ number_format($sesiones->total()) }}</span>
                </div>

                <div class="overflow-x-auto scroll-bonito">
                    <table class="w-full min-w-[1500px] text-xs text-dark-text border-collapse">
                        <thead>
                            <tr class="bg-dark-card/60">
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-4">Interacción</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">Cliente</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">Estado del pago</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">Comprobante</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">Banco / valor</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">Origen</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">Crédito</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-3">KYC / OTP</th>
                                <th class="text-left font-semibold uppercase tracking-wider text-dark-muted py-3 px-4">Auditoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sesiones as $sesion)
                                @php
                                    $comp = $export->comprobanteDelPago($sesion);
                                    $estadoPago = \App\Support\InteraccionPresentador::estadoPago($sesion);
                                    $ultimoOtp = $sesion->otpVerificaciones->last();
                                    $credito = $sesion->saldosFavor->sum(fn ($saldo) => (float) $saldo->excedente);
                                @endphp
                                <tr class="border-b border-dark-border/60 hover:bg-dark-card/40 transition-colors align-top">
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <a href="{{ route('interacciones.show', $sesion->sesion_id) }}" class="text-info hover:text-dark-text font-medium">{{ $sesion->inicio?->format('d/m/Y H:i') ?? 'Sin fecha' }}</a>
                                        <span class="block font-mono text-[10px] text-dark-muted mt-1">{{ $sesion->sesion_id }}</span>
                                    </td>
                                    <td class="py-3 px-3 min-w-52">
                                        <span class="font-medium">{{ $sesion->cliente?->nombre ?? 'Sin identificar' }}</span>
                                        <span class="block font-mono text-dark-muted mt-1">{{ $export->cedulaDe($sesion, $comp) }}</span>
                                        <span class="block font-mono text-[10px] text-dark-muted">{{ $sesion->numero_whatsapp }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-block px-2 py-1 rounded-full font-semibold whitespace-nowrap
                                            @if($estadoPago === 'procesado') bg-green-500/10 text-green-400
                                            @elseif($estadoPago === 'procesado_sin_comprobante') bg-red-500/10 text-red-400
                                            @elseif($estadoPago === 'recibido_no_procesado') bg-amber-500/10 text-amber-400
                                            @else bg-dark-card text-dark-muted @endif">
                                            {{ \App\Support\InteraccionPresentador::etiquetaPago($estadoPago) }}
                                        </span>
                                        <span class="block text-[10px] text-dark-muted mt-1">{{ $sesion->resultado ?: 'En curso' }}</span>
                                    </td>
                                    <td class="py-3 px-3 min-w-48">
                                        @if($comp)
                                            <span class="block font-mono">Control: {{ $comp->numero_transaccion ?: '—' }}</span>
                                            <span class="block font-mono text-dark-muted mt-1">Documento: {{ $comp->numero_documento ?: '—' }}</span>
                                            <span class="block text-dark-muted mt-1">{{ $comp->fecha_comprobante ?: $comp->fecha_hora?->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-red-400">Sin evidencia enlazada</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <span class="block">{{ $comp?->banco ?? '—' }}</span>
                                        <span class="block font-mono font-semibold text-green-400 mt-1">${{ number_format((float) ($comp?->monto ?? 0), 2) }}</span>
                                    </td>
                                    <td class="py-3 px-3 min-w-48">
                                        <span class="block">{{ $comp?->titular_origen ?: 'No visible' }}</span>
                                        <span class="block font-mono text-dark-muted mt-1">{{ $comp?->cuenta_origen ?: '—' }}</span>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <span class="font-mono {{ $credito > 0 ? 'text-blue-400 font-semibold' : 'text-dark-muted' }}">${{ number_format($credito, 2) }}</span>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <span class="block">KYC: {{ \App\Support\InteraccionPresentador::estadoLegible($sesion->ultimaValidacionIdentidad?->estado_kyc) }}</span>
                                        <span class="block text-dark-muted mt-1">OTP: {{ \App\Support\InteraccionPresentador::estadoLegible($ultimoOtp?->resultado) }}</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="inline-block px-2 py-1 rounded bg-dark-card border border-dark-border">{{ $comp?->estado_auditoria ?? 'SIN EVIDENCIA' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-12 text-center text-dark-muted">No hay registros para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-dark-border">{{ $sesiones->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
