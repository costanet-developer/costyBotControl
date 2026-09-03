<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <h4 class="text-sm font-semibold text-dark-text">Comprobantes y pagos</h4>
        <p class="text-xs text-dark-muted mt-0.5">El estado del bot y la revisión humana se presentan por separado.</p>
    </div>
    <span class="text-xs font-mono text-dark-muted shrink-0">{{ $sesion->comprobantes->count() }}</span>
</div>

@forelse($sesion->comprobantes as $comp)
    @php
        $esPrincipal = (string) $presentacion['comprobante_principal_id'] === (string) $comp->id;
        $creditos = $sesion->saldosFavor->where('comprobante_id', $comp->id);
        $estadoActual = \App\Enums\EstadoAuditoria::tryFrom($comp->estado_auditoria);
    @endphp
    <article class="mb-5 last:mb-0 border {{ $esPrincipal ? 'border-corp/50' : 'border-dark-border' }} rounded-lg bg-dark-panel/50 overflow-hidden" id="comp-{{ $comp->id }}">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3 border-b border-dark-border bg-dark-card/40">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs uppercase tracking-wider text-dark-muted">Comprobante #{{ $comp->id }}</span>
                @if($esPrincipal)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-corp/10 text-corp font-semibold uppercase">Usado en la sesión</span>
                @endif
                <span class="text-[10px] px-2 py-0.5 rounded-full border border-dark-border text-dark-muted">Bot: {{ \Illuminate\Support\Str::headline($comp->estado ?? 'sin estado') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold uppercase
                    @switch($comp->estado_auditoria)
                        @case('PENDIENTE') bg-amber-400/10 text-amber-400 @break
                        @case('EN_REVISION') bg-blue-400/10 text-blue-400 @break
                        @case('APROBADO') bg-green-400/10 text-green-400 @break
                        @case('RECHAZADO') bg-red-400/10 text-red-400 @break
                        @default bg-dark-muted/10 text-dark-muted
                    @endswitch">Auditoría: {{ $comp->estado_auditoria ?? 'Sin estado' }}</span>
            </div>
            <span class="text-xs text-dark-muted font-mono">{{ $comp->fecha_hora?->format('d/m/Y H:i') ?? $comp->created_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 p-4">
            <div class="xl:col-span-2">
                @if($comp->ruta_imagen)
                    <img src="{{ route('comprobantes.imagen', $comp) }}"
                         alt="Comprobante {{ $comp->numero_transaccion ?? $comp->id }}"
                         onclick="verImagen(this.src)"
                         class="w-full max-h-80 object-contain bg-dark-card border border-dark-border rounded cursor-zoom-in hover:border-corp/50 transition-colors">
                    <div class="mt-2 flex justify-between items-center gap-2">
                        <span class="text-[10px] text-dark-muted uppercase tracking-wider">Evidencia original</span>
                        @can('comprobantes.descargar')
                            <a href="{{ route('comprobantes.descargar', $comp) }}" class="text-xs text-info hover:text-dark-text transition-colors">Descargar &darr;</a>
                        @endcan
                    </div>
                @else
                    <div class="w-full h-48 flex items-center justify-center bg-dark-card border border-dark-border rounded text-dark-muted text-xs">Sin imagen conservada</div>
                @endif
            </div>

            <div class="xl:col-span-3 space-y-4 min-w-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-2 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-dark-muted shrink-0">Banco</span><span class="font-mono text-right break-all">{{ $comp->banco ?? '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-dark-muted shrink-0">Valor recibido</span><span class="font-mono text-right font-semibold">${{ number_format((float) ($comp->monto ?? 0), 2) }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-dark-muted shrink-0">Fecha del pago</span><span class="font-mono text-right">{{ $comp->fecha_comprobante ?? '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-dark-muted shrink-0">N° transacción / Control</span><span class="font-mono text-right break-all">{{ $comp->numero_transaccion ?? '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-dark-muted shrink-0">N° documento</span><span class="font-mono text-right break-all">{{ $comp->numero_documento ?? '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-dark-muted shrink-0">Origen del registro</span><span class="text-right">{{ \Illuminate\Support\Str::headline($comp->origen ?? '—') }}</span></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded border border-dark-border bg-dark-card/50 p-3">
                        <div class="text-[10px] uppercase tracking-wider text-dark-muted mb-2">Cuenta de origen</div>
                        <div class="text-xs text-dark-text break-all">{{ $comp->titular_origen ?: 'Titular no visible' }}</div>
                        <div class="text-xs font-mono text-dark-muted mt-1 break-all">{{ $comp->cuenta_origen ?: 'Cuenta no visible' }}</div>
                    </div>
                    <div class="rounded border border-dark-border bg-dark-card/50 p-3">
                        <div class="text-[10px] uppercase tracking-wider text-dark-muted mb-2">Cuenta beneficiaria</div>
                        <div class="text-xs text-dark-text break-all">{{ $comp->titular ?: 'Titular no visible' }}</div>
                        <div class="text-xs font-mono text-dark-muted mt-1 break-all">{{ $comp->cuenta_destino ?: 'Cuenta no visible' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    @foreach([
                        ['Banco', $comp->banco_valido],
                        ['Cuenta', $comp->cuenta_valida],
                        ['Titular', $comp->titular_valido],
                    ] as [$etiqueta, $valor])
                        <div class="bg-dark-card rounded px-2 py-2.5 text-xs text-center border border-dark-border">
                            <span class="text-dark-muted">{{ $etiqueta }}</span>
                            @if($valor === true)
                                <span class="block font-semibold mt-1 text-green-400">✓ Coincide</span>
                            @elseif($valor === false)
                                <span class="block font-semibold mt-1 text-red-400">✕ No coincide</span>
                            @else
                                <span class="block font-semibold mt-1 text-dark-muted">— No evaluado</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($creditos->isNotEmpty())
                    <div class="rounded border border-blue-500/30 bg-blue-500/5 p-3 text-xs">
                        <div class="flex justify-between gap-3">
                            <span class="font-semibold text-blue-400">Crédito asociado a este comprobante</span>
                            <span class="font-mono font-semibold text-blue-400">${{ number_format($creditos->sum(fn ($saldo) => (float) $saldo->excedente), 2) }}</span>
                        </div>
                    </div>
                @endif

                @if($comp->score_confianza !== null || $comp->riesgo_visual || $comp->riesgo_ia_generativa)
                    <details class="rounded border border-dark-border bg-dark-card/40 p-3 text-xs">
                        <summary class="cursor-pointer text-dark-muted hover:text-dark-text">Análisis técnico del OCR</summary>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-3">
                            <div><span class="block text-dark-muted">Confianza</span><span class="font-mono text-dark-text">{{ $comp->score_confianza !== null ? $comp->score_confianza.'/100' : '—' }}</span></div>
                            <div><span class="block text-dark-muted">Riesgo visual</span><span class="text-dark-text">{{ $comp->riesgo_visual ?? '—' }}</span></div>
                            <div><span class="block text-dark-muted">Probabilidad IA</span><span class="font-mono text-dark-text">{{ $comp->probabilidad_ia_generativa !== null ? $comp->probabilidad_ia_generativa.'%' : '—' }}</span></div>
                        </div>
                        @if(!empty($comp->alertas) || !empty($comp->alertas_ia_generativa))
                            <div class="mt-2 space-y-1 text-amber-400">
                                @foreach(array_merge($comp->alertas ?? [], $comp->alertas_ia_generativa ?? []) as $alerta)
                                    <div>⚠ {{ is_string($alerta) ? \Illuminate\Support\Str::headline($alerta) : json_encode($alerta, JSON_UNESCAPED_UNICODE) }}</div>
                                @endforeach
                            </div>
                        @endif
                    </details>
                @endif
            </div>
        </div>

        <div class="px-4 pb-4">
            <form method="POST" action="{{ route('comprobantes.estado', $comp) }}" data-ajax="1" class="flex flex-wrap gap-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="estado" value="">
                <input type="hidden" name="observacion" value="">

                @if($estadoActual && $estadoActual->puedeTransicionarA(\App\Enums\EstadoAuditoria::EN_REVISION) && Auth::user()->can('comprobantes.revisar'))
                    <button type="button" onclick="cambiarEstado(this, 'EN_REVISION')" class="text-xs px-3 py-1.5 rounded bg-blue-500/20 text-blue-400 border border-blue-500/30 hover:bg-blue-500/30 transition-colors">Tomar en revisión</button>
                @endif
                @if($estadoActual && $estadoActual->puedeTransicionarA(\App\Enums\EstadoAuditoria::APROBADO) && Auth::user()->can('comprobantes.aprobar'))
                    <button type="button" onclick="cambiarEstado(this, 'APROBADO')" class="text-xs px-3 py-1.5 rounded bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30 transition-colors">Aprobar</button>
                @endif
                @if($estadoActual && $estadoActual->puedeTransicionarA(\App\Enums\EstadoAuditoria::RECHAZADO) && Auth::user()->can('comprobantes.rechazar'))
                    <button type="button" onclick="cambiarEstadoConObs(this, 'RECHAZADO')" class="text-xs px-3 py-1.5 rounded bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors">Rechazar</button>
                @endif
            </form>

            @if($comp->revisiones->isNotEmpty())
                <details class="mt-3">
                    <summary class="text-xs text-dark-muted cursor-pointer hover:text-dark-text">Historial de auditoría ({{ $comp->revisiones->count() }})</summary>
                    <div class="mt-2 space-y-1.5">
                        @foreach($comp->revisiones as $rev)
                            <div class="text-xs text-dark-muted font-mono border-l-2 border-dark-border pl-3 py-1">
                                <span class="text-dark-text">{{ $rev->estado_anterior ?? '—' }}</span> &rarr; <span class="text-dark-text">{{ $rev->estado_nuevo }}</span>
                                <span class="block">{{ $rev->usuario?->nombre }} · {{ $rev->fecha_revision?->format('d/m H:i') }}</span>
                                @if($rev->observacion)<span class="block italic">“{{ $rev->observacion }}”</span>@endif
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            @if($comp->observaciones->isNotEmpty())
                <div class="mt-4">
                    <div class="text-xs text-dark-muted uppercase tracking-wider mb-2">Observaciones</div>
                    @foreach($comp->observaciones as $obs)
                        <div class="bg-dark-card rounded p-3 mb-2 text-xs">
                            <div class="text-dark-muted">{{ $obs->usuario?->nombre }} · {{ $obs->created_at?->format('d/m H:i') }}</div>
                            <div class="text-dark-text mt-0.5">{{ $obs->observacion }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="border border-dark-border rounded-lg p-8 text-center">
        <p class="text-dark-muted text-sm">Esta sesión no tiene comprobantes asociados.</p>
    </div>
@endforelse
