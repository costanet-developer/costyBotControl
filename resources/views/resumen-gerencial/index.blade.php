<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"><div><h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Resumen gerencial</h2><p class="text-xs text-dark-muted mt-1">Resultados operativos y comparación con el periodo anterior.</p></div><span class="text-xs text-dark-muted font-mono">{{ $resumen['inicio']->format('d/m/Y') }} – {{ $resumen['fin']->format('d/m/Y') }}</span></div>
    </x-slot>

    @php
        $m = $resumen['actual']['metricas'];
        $prev = $resumen['anterior']['metricas'];
        $variacionTexto = fn ($clave) => $resumen['variaciones'][$clave] === null ? 'Nuevo' : (($resumen['variaciones'][$clave] > 0 ? '+' : '').number_format($resumen['variaciones'][$clave], 1).'%');
        $variacionClase = fn ($clave, $inversa = false) => $resumen['variaciones'][$clave] === null ? 'text-blue-400' : (($resumen['variaciones'][$clave] == 0) ? 'text-dark-muted' : ((($resumen['variaciones'][$clave] > 0) xor $inversa) ? 'text-green-400' : 'text-red-400'));
        $maxSerie = max(1, (int) $resumen['actual']['serie']->max('interacciones'));
        $duracion = fn ($minutos) => \App\Services\SeguimientoOperativo::duracion($minutos);
    @endphp

    <div class="py-6"><div class="max-w-[1700px] mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
            <form method="GET" action="{{ route('resumen-gerencial.index') }}" class="p-4 flex flex-wrap items-end gap-3">
                <div class="min-w-48"><label class="block text-[10px] uppercase text-dark-muted mb-1">Periodo</label><select name="periodo" onchange="document.getElementById('fechas-personalizadas').classList.toggle('hidden', this.value !== 'personalizado')" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text">@foreach(['hoy' => 'Hoy', 'ayer' => 'Ayer', '7_dias' => 'Últimos 7 días', '30_dias' => 'Últimos 30 días', 'personalizado' => 'Personalizado'] as $valor => $texto)<option value="{{ $valor }}" @selected($periodo === $valor)>{{ $texto }}</option>@endforeach</select></div>
                <div id="fechas-personalizadas" class="flex flex-wrap gap-3 {{ $periodo === 'personalizado' ? '' : 'hidden' }}"><div><label class="block text-[10px] uppercase text-dark-muted mb-1">Desde</label><input type="date" name="desde" value="{{ request('desde', $resumen['inicio']->format('Y-m-d')) }}" class="bg-dark-card border-dark-border rounded text-xs text-dark-text"></div><div><label class="block text-[10px] uppercase text-dark-muted mb-1">Hasta</label><input type="date" name="hasta" value="{{ request('hasta', $resumen['fin']->format('Y-m-d')) }}" class="bg-dark-card border-dark-border rounded text-xs text-dark-text"></div></div>
                <button class="bg-corp text-dark-bg font-medium px-4 py-2 rounded text-xs">Actualizar</button>
                @can('auditoria.exportar')<div class="sm:ml-auto"><button type="submit" form="exportar-resumen" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded text-xs">Descargar Excel</button></div>@endcan
            </form>
            @can('auditoria.exportar')<form id="exportar-resumen" method="POST" action="{{ route('resumen-gerencial.export') }}">@csrf<input type="hidden" name="periodo" value="{{ $periodo }}"><input type="hidden" name="desde" value="{{ request('desde') }}"><input type="hidden" name="hasta" value="{{ request('hasta') }}"></form>@endcan
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach([
                ['interacciones', 'Interacciones', number_format($m['interacciones']), false],
                ['clientes', 'Clientes únicos', number_format($m['clientes']), false],
                ['pagos', 'Pagos procesados', number_format($m['pagos']), false],
                ['monto', 'Valor recibido', '$'.number_format($m['monto'], 2), false],
                ['creditos', 'Crédito generado', '$'.number_format($m['creditos'], 2), false],
                ['sin_evidencia', 'Pagos sin evidencia', number_format($m['sin_evidencia']), true],
            ] as [$clave, $titulo, $valor, $inversa])
                <div class="bg-dark-panel border {{ $clave === 'sin_evidencia' && $m[$clave] ? 'border-red-500/40' : 'border-dark-border' }} rounded-lg p-4"><p class="text-[10px] uppercase tracking-wider text-dark-muted">{{ $titulo }}</p><p class="text-2xl font-semibold {{ $clave === 'monto' ? 'text-green-400' : ($clave === 'creditos' ? 'text-blue-400' : 'text-dark-text') }} mt-1">{{ $valor }}</p><p class="text-[10px] mt-1 {{ $variacionClase($clave, $inversa) }}">{{ $variacionTexto($clave) }} vs. periodo anterior</p></div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <section class="xl:col-span-2 bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Evolución diaria</h3><p class="text-xs text-dark-muted mt-0.5">Interacciones, pagos, valores y novedades por fecha.</p></div>
                <div class="overflow-x-auto scroll-bonito"><table class="w-full min-w-[760px] text-xs"><thead><tr class="bg-dark-card/60 text-[10px] uppercase tracking-wider text-dark-muted"><th class="text-left px-4 py-3">Fecha</th><th class="text-left px-3 py-3 w-64">Actividad</th><th class="text-right px-3 py-3">Pagos</th><th class="text-right px-3 py-3">Valor</th><th class="text-right px-3 py-3">Créditos</th><th class="text-right px-4 py-3">Casos</th></tr></thead><tbody>
                    @foreach($resumen['actual']['serie'] as $dia)
                        <tr class="border-t border-dark-border/60"><td class="px-4 py-3 font-mono text-dark-muted">{{ $dia['fecha']->format('d/m/Y') }}</td><td class="px-3 py-3"><div class="flex items-center gap-2"><div class="h-2 rounded bg-corp" style="width: {{ max(2, ($dia['interacciones'] / $maxSerie) * 100) }}%"></div><span class="shrink-0 text-dark-text">{{ $dia['interacciones'] }}</span></div></td><td class="px-3 py-3 text-right">{{ $dia['pagos'] }}</td><td class="px-3 py-3 text-right font-mono text-green-400">${{ number_format($dia['monto'], 2) }}</td><td class="px-3 py-3 text-right font-mono text-blue-400">${{ number_format($dia['creditos'], 2) }}</td><td class="px-4 py-3 text-right {{ $dia['casos'] ? 'text-amber-400' : 'text-dark-muted' }}">{{ $dia['casos'] }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>

            <section class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Estado ejecutivo</h3></div>
                <div class="divide-y divide-dark-border">
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">Conversión a pago</span><span class="text-sm font-semibold text-green-400">{{ number_format($m['tasa_pago'], 1) }}%</span></div>
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">KYC procesados</span><span class="text-sm font-semibold text-dark-text">{{ number_format($m['kyc']) }}</span></div>
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">KYC a revisión</span><span class="text-sm font-semibold {{ $m['kyc_revision'] ? 'text-amber-400' : 'text-green-400' }}">{{ number_format($m['kyc_revision']) }}</span></div>
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">Correos verificados</span><span class="text-sm font-semibold text-blue-400">{{ number_format($m['correos_verificados']) }}</span></div>
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">Casos detectados</span><span class="text-sm font-semibold text-amber-400">{{ number_format($m['casos_detectados']) }}</span></div>
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">Casos resueltos</span><span class="text-sm font-semibold text-green-400">{{ number_format($m['casos_resueltos']) }}</span></div>
                    <div class="px-5 py-4 flex justify-between"><span class="text-xs text-dark-muted">Acciones administrativas</span><span class="text-sm font-semibold text-dark-text">{{ number_format($m['acciones_administrativas']) }}</span></div>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-5">
            <section class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden"><div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Valores por banco</h3></div><div class="divide-y divide-dark-border">@forelse($resumen['actual']['bancos'] as $banco)<div class="px-5 py-3 flex justify-between gap-3"><div><p class="text-xs text-dark-text">{{ $banco['banco'] }}</p><p class="text-[10px] text-dark-muted mt-1">{{ $banco['cantidad'] }} pagos</p></div><span class="text-xs font-mono font-semibold text-green-400">${{ number_format($banco['monto'], 2) }}</span></div>@empty<div class="px-5 py-10 text-center text-xs text-dark-muted">Sin pagos en el periodo.</div>@endforelse</div></section>
            <section class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden"><div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Tipos de caso</h3></div><div class="divide-y divide-dark-border">@forelse($resumen['actual']['tipos_caso'] as $tipo)<div class="px-5 py-3 flex justify-between gap-3"><span class="text-xs text-dark-text">{{ \App\Support\AuditoriaPresentador::etiqueta($tipo['tipo']) }}</span><span class="text-sm font-semibold text-amber-400">{{ $tipo['cantidad'] }}</span></div>@empty<div class="px-5 py-10 text-center text-xs text-green-400">Sin casos nuevos.</div>@endforelse</div></section>
            <section class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden"><div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Cumplimiento SLA actual</h3></div><div class="divide-y divide-dark-border"><div class="px-5 py-3 flex justify-between"><span class="text-xs text-dark-muted">Abiertos</span><span class="text-sm font-semibold">{{ $resumen['sla']['abiertos'] }}</span></div><div class="px-5 py-3 flex justify-between"><span class="text-xs text-dark-muted">Sin asignar</span><span class="text-sm font-semibold text-amber-400">{{ $resumen['sla']['sin_asignar'] }}</span></div><div class="px-5 py-3 flex justify-between"><span class="text-xs text-dark-muted">Vencidos</span><span class="text-sm font-semibold {{ $resumen['sla']['vencidos'] ? 'text-red-400' : 'text-green-400' }}">{{ $resumen['sla']['vencidos'] }}</span></div><div class="px-5 py-3"><p class="text-[10px] text-dark-muted">Promedio de resolución</p><p class="text-sm font-semibold text-blue-400 mt-1">{{ $duracion($resumen['sla']['promedio_resolucion_minutos']) }}</p></div></div></section>
            <section class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden"><div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Desempeño por responsable</h3></div><div class="divide-y divide-dark-border">@forelse($resumen['actual']['responsables'] as $responsable)<div class="px-5 py-3 flex justify-between gap-3"><div><p class="text-xs text-dark-text">{{ $responsable['usuario']?->name ?? 'Usuario eliminado' }}</p><p class="text-[10px] text-dark-muted mt-1">Promedio {{ $duracion($responsable['promedio_minutos']) }}</p></div><span class="text-sm font-semibold text-green-400">{{ $responsable['resueltos'] }}</span></div>@empty<div class="px-5 py-10 text-center text-xs text-dark-muted">Sin casos resueltos en el periodo.</div>@endforelse</div></section>
        </div>

        <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden"><div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Comparación completa</h3><p class="text-xs text-dark-muted mt-0.5">Periodo anterior: {{ $resumen['inicio_anterior']->format('d/m/Y') }} – {{ $resumen['fin_anterior']->format('d/m/Y') }}</p></div><div class="overflow-x-auto"><table class="w-full min-w-[620px] text-xs"><thead><tr class="bg-dark-card/60 text-[10px] uppercase text-dark-muted"><th class="text-left px-5 py-3">Indicador</th><th class="text-right px-3 py-3">Actual</th><th class="text-right px-3 py-3">Anterior</th><th class="text-right px-5 py-3">Variación</th></tr></thead><tbody>@foreach(['interacciones'=>'Interacciones','clientes'=>'Clientes únicos','pagos'=>'Pagos procesados','monto'=>'Valor recibido','creditos'=>'Créditos','sin_evidencia'=>'Sin evidencia','casos_detectados'=>'Casos detectados','casos_resueltos'=>'Casos resueltos'] as $clave=>$titulo)<tr class="border-t border-dark-border/60"><td class="px-5 py-3 text-dark-text">{{ $titulo }}</td><td class="px-3 py-3 text-right font-mono">{{ in_array($clave,['monto','creditos']) ? '$'.number_format($m[$clave],2) : number_format($m[$clave]) }}</td><td class="px-3 py-3 text-right font-mono text-dark-muted">{{ in_array($clave,['monto','creditos']) ? '$'.number_format($prev[$clave],2) : number_format($prev[$clave]) }}</td><td class="px-5 py-3 text-right font-semibold {{ $variacionClase($clave, in_array($clave,['sin_evidencia','casos_detectados'])) }}">{{ $variacionTexto($clave) }}</td></tr>@endforeach</tbody></table></div></div>

        <div class="px-4 py-3 rounded-lg border border-blue-500/30 bg-blue-500/5 text-xs text-blue-300">El envío diario y semanal está programado, pero continuará sin enviar correos mientras <span class="font-mono">COSTY_ALERTAS_EMAIL</span> permanezca deshabilitado.</div>
    </div></div>
</x-app-layout>
