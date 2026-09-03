<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Bandeja operativa</h2>
            <p class="text-xs text-dark-muted mt-1">Excepciones y pendientes que requieren seguimiento interno.</p>
        </div>
    </x-slot>

    @php
        $tipos = [
            'casos' => ['Casos automáticos', 'Alertas detectadas por las reglas de conciliación', 'red'],
            'sin_evidencia' => ['Pagos sin evidencia', 'Reactivaciones exitosas sin comprobante enlazado', 'red'],
            'auditoria_pendiente' => ['Auditoría pendiente', 'Comprobantes que aún no han sido revisados', 'amber'],
            'en_revision' => ['En revisión', 'Comprobantes tomados por un auditor', 'blue'],
            'creditos' => ['Créditos pendientes', 'Excedentes registrados pendientes de gestión', 'blue'],
            'kyc' => ['KYC derivado', 'Validaciones enviadas a revisión manual', 'amber'],
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-5">
                @foreach($tipos as $clave => [$titulo, $descripcion, $color])
                    <a href="{{ route('pendientes.index', ['tipo' => $clave]) }}"
                       class="rounded-lg border p-3 transition-colors {{ $tipo === $clave ? 'border-corp bg-corp/5' : 'border-dark-border bg-dark-panel hover:border-corp/40' }}">
                        <span class="block text-[10px] uppercase tracking-wider text-dark-muted">{{ $titulo }}</span>
                        <span class="block text-xl font-semibold mt-1 {{ $color === 'red' ? 'text-red-400' : ($color === 'amber' ? 'text-amber-400' : 'text-blue-400') }}">{{ number_format($conteos[$clave]) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="bg-dark-panel border border-dark-border rounded-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-dark-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-dark-text">{{ $tipos[$tipo][0] }}</h3>
                        <p class="text-xs text-dark-muted mt-0.5">{{ $tipos[$tipo][1] }}</p>
                    </div>
                    <span class="text-xs text-dark-muted">{{ $registros->firstItem() ?? 0 }}–{{ $registros->lastItem() ?? 0 }} de {{ number_format($registros->total()) }}</span>
                </div>

                @if($tipo === 'casos')
                    <div class="px-5 py-3 border-b border-dark-border flex flex-wrap gap-2">
                        @foreach(['abiertos' => 'Abiertos', 'pendiente' => 'Pendientes', 'en_revision' => 'En revisión', 'resuelto' => 'Resueltos', 'descartado' => 'Descartados', 'todos' => 'Todos'] as $estadoClave => $estadoTitulo)
                            <a href="{{ route('pendientes.index', ['tipo' => 'casos', 'estado' => $estadoClave]) }}" class="text-[11px] px-2.5 py-1 rounded-full border {{ request('estado', 'abiertos') === $estadoClave ? 'border-corp bg-corp/10 text-dark-text' : 'border-dark-border text-dark-muted hover:text-dark-text' }}">{{ $estadoTitulo }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="divide-y divide-dark-border">
                    @forelse($registros as $registro)
                        @if($tipo === 'casos')
                            @php
                                $coloresPrioridad = ['alta' => 'text-red-400 bg-red-500/10', 'media' => 'text-amber-400 bg-amber-500/10', 'baja' => 'text-blue-400 bg-blue-500/10'];
                                $coloresEstado = ['pendiente' => 'text-amber-400', 'en_revision' => 'text-blue-400', 'resuelto' => 'text-green-400', 'descartado' => 'text-dark-muted'];
                                $sesion = $registro->sesion;
                            @endphp
                            <div class="px-5 py-4 hover:bg-dark-card transition-colors">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-semibold text-dark-text">{{ $registro->titulo }}</span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ $coloresPrioridad[$registro->prioridad] ?? $coloresPrioridad['media'] }}">{{ strtoupper($registro->prioridad) }}</span>
                                            <span class="text-[10px] {{ $coloresEstado[$registro->estado] ?? 'text-dark-muted' }}">{{ strtoupper(str_replace('_', ' ', $registro->estado)) }}</span>
                                        </div>
                                        <div class="text-xs text-dark-muted mt-1">
                                            {{ $sesion?->cliente?->nombre ?? ($sesion?->cedula ? 'Cliente '.$sesion->cedula : 'Caso #'.$registro->id) }}
                                            @if($registro->sesion_id) · <a class="font-mono text-info hover:text-dark-text" href="{{ route('interacciones.show', $registro->sesion_id) }}">{{ $registro->sesion_id }}</a>@endif
                                        </div>
                                        @if($registro->detalle)
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                                                @foreach($registro->detalle as $clave => $valor)
                                                    <span class="text-[10px] text-dark-muted"><span class="text-dark-text">{{ ucfirst(str_replace('_', ' ', $clave)) }}:</span> {{ is_array($valor) ? implode(', ', $valor) : ($valor ?? '—') }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-dark-muted mt-2">
                                            Detectado {{ $registro->detectado_en?->format('d/m/Y H:i') }}
                                            @if($registro->asignadoA) · Asignado a {{ $registro->asignadoA->nombre }} {{ $registro->asignadoA->apellido }}@endif
                                            @if($registro->resueltoPor) · Cerrado por {{ $registro->resueltoPor->nombre }} {{ $registro->resueltoPor->apellido }}@endif
                                        </div>
                                        @if($registro->resolucion)<p class="mt-2 text-xs text-green-400">Resolución: {{ $registro->resolucion }}</p>@endif
                                    </div>
                                    @can('casos_operativos.gestionar')
                                        <div class="lg:w-80 shrink-0">
                                            @if(in_array($registro->estado, ['pendiente', 'en_revision'], true))
                                                <div class="flex justify-end mb-2">
                                                    <form method="POST" action="{{ route('casos-operativos.tomar', $registro) }}">@csrf @method('PATCH')<button class="text-[11px] px-3 py-1.5 rounded border border-blue-500/40 text-blue-400 hover:bg-blue-500/10">{{ $registro->asignado_a === auth()->id() ? 'Asignado a mí' : 'Tomar caso' }}</button></form>
                                                </div>
                                                <form method="POST" action="{{ route('casos-operativos.cerrar', $registro) }}" class="flex gap-2">@csrf @method('PATCH')
                                                    <input name="resolucion" required minlength="5" maxlength="1000" placeholder="Resolución o justificación" class="min-w-0 flex-1 rounded border-dark-border bg-dark-bg text-xs text-dark-text">
                                                    <button name="estado" value="resuelto" class="text-[11px] px-2.5 rounded bg-green-600 text-white hover:bg-green-500">Resolver</button>
                                                    <button name="estado" value="descartado" class="text-[11px] px-2.5 rounded border border-dark-border text-dark-muted hover:text-dark-text">Descartar</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('casos-operativos.reabrir', $registro) }}" class="flex justify-end">@csrf @method('PATCH')<button class="text-[11px] px-3 py-1.5 rounded border border-amber-500/40 text-amber-400 hover:bg-amber-500/10">Reabrir caso</button></form>
                                            @endif
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @elseif($tipo === 'sin_evidencia')
                            <a href="{{ route('interacciones.show', $registro->sesion_id) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-5 py-4 hover:bg-dark-card transition-colors">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2"><span class="text-sm font-medium text-dark-text">{{ $registro->cliente?->nombre ?? ($registro->cedula ? 'Cliente '.$registro->cedula : 'Sin identificar') }}</span><span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/10 text-red-400">Sin evidencia</span></div>
                                    <div class="text-xs text-dark-muted font-mono mt-1">{{ $registro->numero_whatsapp }} · {{ $registro->sesion_id }}</div>
                                </div>
                                <div class="sm:text-right shrink-0"><span class="block text-xs text-dark-text">{{ $registro->resultado }}</span><span class="block text-[10px] text-dark-muted font-mono mt-1">{{ $registro->inicio?->format('d/m/Y H:i') }}</span></div>
                            </a>
                        @elseif(in_array($tipo, ['auditoria_pendiente', 'en_revision'], true))
                            @php $sesion = $registro->sesion; @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 hover:bg-dark-card transition-colors">
                                <div class="min-w-0">
                                    @if($sesion)
                                        <a href="{{ route('interacciones.show', $sesion->sesion_id) }}" class="text-sm font-medium text-info hover:text-dark-text">{{ $sesion->cliente?->nombre ?? ($registro->cedula ? 'Cliente '.$registro->cedula : 'Interacción '.$sesion->sesion_id) }}</a>
                                    @else
                                        <span class="text-sm font-medium text-red-400">Comprobante sin sesión asociada</span>
                                    @endif
                                    <div class="text-xs text-dark-muted mt-1"><span class="font-mono">#{{ $registro->numero_transaccion ?: 'Sin transacción' }}</span> · {{ $registro->banco ?: 'Banco no identificado' }}</div>
                                    <div class="text-[10px] text-dark-muted font-mono mt-1">Comprobante #{{ $registro->id }} · {{ $registro->fecha_hora?->format('d/m/Y H:i') ?? $registro->created_at?->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="sm:text-right shrink-0"><span class="block text-sm font-semibold text-green-400">${{ number_format((float) ($registro->monto ?? 0), 2) }}</span><span class="inline-block mt-1 text-[10px] px-2 py-0.5 rounded bg-dark-card border border-dark-border">{{ $registro->estado_auditoria }}</span></div>
                            </div>
                        @elseif($tipo === 'creditos')
                            @php $sesion = $registro->sesion; @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 hover:bg-dark-card transition-colors">
                                <div class="min-w-0">
                                    @if($sesion)<a href="{{ route('interacciones.show', $sesion->sesion_id) }}" class="text-sm font-medium text-info hover:text-dark-text">{{ $sesion->cliente?->nombre ?? 'Cliente '.$registro->cedula }}</a>@else<span class="text-sm font-medium text-dark-text">Cliente {{ $registro->cedula ?: 'sin identificar' }}</span>@endif
                                    <div class="text-xs text-dark-muted font-mono mt-1">{{ $registro->numero_transaccion ?: 'Sin transacción' }} · {{ $registro->fecha_registro?->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="grid grid-cols-3 gap-4 text-right shrink-0 text-xs"><div><span class="block text-dark-muted">Pagado</span><span class="font-mono">${{ number_format((float) $registro->monto_pagado, 2) }}</span></div><div><span class="block text-dark-muted">Factura</span><span class="font-mono">${{ number_format((float) $registro->monto_factura, 2) }}</span></div><div><span class="block text-dark-muted">Crédito</span><span class="font-mono font-semibold text-blue-400">${{ number_format((float) $registro->excedente, 2) }}</span></div></div>
                            </div>
                        @else
                            @php $sesion = $registro->sesion; @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 hover:bg-dark-card transition-colors">
                                <div class="min-w-0">
                                    @if($sesion)<a href="{{ route('interacciones.show', $sesion->sesion_id) }}" class="text-sm font-medium text-info hover:text-dark-text">{{ $sesion->cliente?->nombre ?? 'Cliente '.$registro->cedula }}</a>@else<span class="text-sm font-medium text-dark-text">Cliente {{ $registro->cedula }}</span>@endif
                                    <div class="text-xs text-dark-muted font-mono mt-1">{{ $registro->cedula }} · {{ \App\Support\InteraccionPresentador::enmascararCorreo($registro->correo) }}</div>
                                </div>
                                <div class="sm:text-right shrink-0"><span class="block text-xs text-amber-400">{{ \App\Support\InteraccionPresentador::estadoLegible($registro->estado_kyc) }}</span><span class="block text-[10px] text-dark-muted mt-1">{{ $registro->actualizado_en?->format('d/m/Y H:i') }}</span></div>
                            </div>
                        @endif
                    @empty
                        <div class="px-5 py-12 text-center"><p class="text-sm text-green-400">No hay casos pendientes en esta categoría.</p></div>
                    @endforelse
                </div>

                <div class="px-5 py-4 border-t border-dark-border">{{ $registros->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
