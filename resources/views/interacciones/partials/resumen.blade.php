@php
    $principal = $sesion->comprobantePrincipal;
    $kyc = $sesion->ultimaValidacionIdentidad;
    $otp = $presentacion['ultimo_otp'];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
    <div class="rounded-lg border border-dark-border bg-dark-card/50 p-3">
        <div class="text-[10px] uppercase tracking-wider text-dark-muted">Sesión</div>
        <div class="mt-2 text-sm font-medium text-dark-text">{{ $sesion->inicio?->format('d/m/Y H:i') ?? 'Sin fecha' }}</div>
        <div class="text-xs text-dark-muted mt-1">
            @if($sesion->fin)
                Finalizó {{ $sesion->fin->format('d/m/Y H:i') }}
            @elseif($sesion->ultima_actividad)
                Última actividad {{ \Illuminate\Support\Carbon::createFromTimestampMs($sesion->ultima_actividad)->format('d/m/Y H:i') }}
            @else
                Sin actividad final registrada
            @endif
        </div>
    </div>

    <div class="rounded-lg border border-dark-border bg-dark-card/50 p-3">
        <div class="text-[10px] uppercase tracking-wider text-dark-muted">Pago principal</div>
        @if($principal)
            <div class="mt-2 text-sm font-semibold text-dark-text">${{ number_format($principal->monto ?? 0, 2) }}</div>
            <div class="text-xs text-dark-muted mt-1 font-mono break-all">{{ $principal->numero_transaccion ?: 'Sin número de transacción' }}</div>
        @else
            <div class="mt-2 text-sm text-dark-muted">No asociado</div>
        @endif
    </div>

    <div class="rounded-lg border border-dark-border bg-dark-card/50 p-3">
        <div class="text-[10px] uppercase tracking-wider text-dark-muted">Identidad</div>
        <div class="mt-2 text-sm font-medium {{ $kyc?->estado_kyc === 'validada' ? 'text-green-400' : 'text-dark-text' }}">
            {{ \App\Support\InteraccionPresentador::estadoLegible($kyc?->estado_kyc) }}
        </div>
        <div class="text-xs text-dark-muted mt-1">{{ $sesion->documentosIdentidad->count() }} documento(s) conservado(s)</div>
    </div>

    <div class="rounded-lg border border-dark-border bg-dark-card/50 p-3">
        <div class="text-[10px] uppercase tracking-wider text-dark-muted">Correo / OTP</div>
        <div class="mt-2 text-sm font-medium {{ $otp?->resultado === 'validado' ? 'text-green-400' : 'text-dark-text' }}">
            {{ \App\Support\InteraccionPresentador::estadoLegible($otp?->resultado) }}
        </div>
        <div class="text-xs text-dark-muted mt-1 break-all">{{ $presentacion['correo_enmascarado'] ?? 'Correo no registrado' }}</div>
    </div>
</div>

@if($presentacion['estado_pago'] === 'procesado_sin_comprobante')
    <div class="mt-4 rounded-lg border border-red-500/30 bg-red-500/5 p-4">
        <div class="text-sm font-semibold text-red-400">Pago procesado sin comprobante enlazado</div>
        <p class="text-xs text-dark-muted mt-1">La sesión registra una reactivación exitosa, pero no existe evidencia asociada en la base de datos. Requiere revisión interna.</p>
    </div>
@endif

<div class="mt-5 rounded-lg border border-dark-border overflow-hidden">
    <div class="flex items-center justify-between gap-3 px-4 py-3 bg-dark-card/50 border-b border-dark-border">
        <div>
            <h4 class="text-sm font-semibold text-dark-text">Servicios consultados</h4>
            <p class="text-xs text-dark-muted mt-0.5">Contratos y perfiles devueltos por Costanet durante esta sesión.</p>
        </div>
        <span class="text-xs font-mono text-dark-muted">{{ count($presentacion['servicios']) }}</span>
    </div>

    @forelse($presentacion['servicios'] as $servicio)
        <div class="p-4 border-b border-dark-border last:border-b-0 {{ $servicio['seleccionado'] ? 'bg-corp/5' : '' }}">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-dark-text">{{ $servicio['nombre'] }}</span>
                        @if($servicio['seleccionado'])
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-corp/10 text-corp uppercase font-semibold">Seleccionado</span>
                        @endif
                        @if($servicio['estado'])
                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ strtoupper($servicio['estado']) === 'ACTIVO' ? 'bg-green-500/10 text-green-400' : 'bg-amber-500/10 text-amber-400' }}">{{ $servicio['estado'] }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-dark-muted font-mono mt-1">Código: {{ $servicio['codigo'] ?: '—' }}</div>
                    @if($servicio['direccion'])
                        <div class="text-xs text-dark-muted mt-1">{{ $servicio['direccion'] }}</div>
                    @endif
                </div>
                <div class="sm:text-right shrink-0">
                    <div class="text-sm font-semibold text-dark-text">${{ number_format((float) ($servicio['deuda'] ?? 0), 2) }}</div>
                    <div class="text-[10px] uppercase tracking-wider text-dark-muted">{{ (int) ($servicio['facturas'] ?? 0) }} factura(s) pendiente(s)</div>
                </div>
            </div>

            @if($servicio['items'])
                <div class="mt-3 grid grid-cols-1 xl:grid-cols-2 gap-2">
                    @foreach($servicio['items'] as $item)
                        <div class="rounded border border-dark-border bg-dark-panel/50 px-3 py-2.5">
                            <div class="flex justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-xs font-medium text-dark-text">{{ $item['nombre'] }}</div>
                                    <div class="text-[10px] text-dark-muted uppercase tracking-wider mt-0.5">{{ $item['tipo'] }}@if($item['estado']) · {{ $item['estado'] }}@endif</div>
                                </div>
                                @if($item['costo'] !== null && $item['costo'] !== '')
                                    <span class="text-xs font-mono text-dark-text shrink-0">${{ number_format((float) $item['costo'], 2) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="p-6 text-center text-sm text-dark-muted">Esta sesión no registró una consulta de servicios.</div>
    @endforelse
</div>

@if($sesion->saldosFavor->isNotEmpty())
    <div class="mt-5 rounded-lg border border-blue-500/30 bg-blue-500/5 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold text-blue-400">Crédito generado</h4>
                <p class="text-xs text-dark-muted mt-0.5">Excedente registrado después de cubrir la factura.</p>
            </div>
            <span class="text-lg font-semibold text-blue-400">${{ number_format($presentacion['total_credito'], 2) }}</span>
        </div>
        <div class="mt-3 space-y-2">
            @foreach($sesion->saldosFavor as $saldo)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs border-t border-blue-500/20 pt-2">
                    <div><span class="block text-dark-muted">Pagado</span><span class="font-mono text-dark-text">${{ number_format((float) $saldo->monto_pagado, 2) }}</span></div>
                    <div><span class="block text-dark-muted">Factura</span><span class="font-mono text-dark-text">${{ number_format((float) $saldo->monto_factura, 2) }}</span></div>
                    <div><span class="block text-dark-muted">Excedente</span><span class="font-mono text-blue-400">${{ number_format((float) $saldo->excedente, 2) }}</span></div>
                    <div><span class="block text-dark-muted">Estado</span><span class="text-dark-text">{{ \App\Support\InteraccionPresentador::estadoLegible($saldo->estado) }}</span></div>
                </div>
            @endforeach
        </div>
    </div>
@endif
