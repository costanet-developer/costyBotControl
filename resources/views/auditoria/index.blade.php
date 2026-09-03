<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div><h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Auditoría y tiempos de atención</h2><p class="text-xs text-dark-muted mt-1">Trazabilidad administrativa, responsables y cumplimiento de SLA.</p></div>
            <span class="text-xs text-dark-muted font-mono">Actualizado {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </x-slot>

    @php
        $duracion = fn ($minutos) => \App\Services\SeguimientoOperativo::duracion($minutos);
        $filtrosExport = ['desde', 'hasta', 'usuario_id', 'modulo', 'accion', 'resultado', 'buscar'];
    @endphp

    <div class="py-6"><div class="max-w-[1700px] mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        <section>
            <div class="flex flex-wrap items-end justify-between gap-2 mb-3"><div><h3 class="text-sm font-semibold text-dark-text">Seguimiento operativo</h3><p class="text-xs text-dark-muted mt-0.5">SLA en horas corridas: alta {{ $sla['limites']['alta'] }} h, media {{ $sla['limites']['media'] }} h y baja {{ $sla['limites']['baja'] }} h.</p></div><a href="{{ route('pendientes.index', ['tipo' => 'casos']) }}" class="text-xs text-info hover:text-dark-text">Abrir bandeja de casos →</a></div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-dark-panel border border-dark-border rounded-lg p-4"><p class="text-[10px] uppercase tracking-wider text-dark-muted">Casos abiertos</p><p class="text-2xl font-semibold text-dark-text mt-1">{{ number_format($sla['abiertos']) }}</p></div>
                <div class="bg-dark-panel border {{ $sla['sin_asignar'] ? 'border-amber-500/40' : 'border-dark-border' }} rounded-lg p-4"><p class="text-[10px] uppercase tracking-wider text-dark-muted">Sin asignar</p><p class="text-2xl font-semibold {{ $sla['sin_asignar'] ? 'text-amber-400' : 'text-green-400' }} mt-1">{{ number_format($sla['sin_asignar']) }}</p></div>
                <div class="bg-dark-panel border {{ $sla['vencidos'] ? 'border-red-500/40' : 'border-dark-border' }} rounded-lg p-4"><p class="text-[10px] uppercase tracking-wider text-dark-muted">SLA vencido</p><p class="text-2xl font-semibold {{ $sla['vencidos'] ? 'text-red-400' : 'text-green-400' }} mt-1">{{ number_format($sla['vencidos']) }}</p></div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-4"><p class="text-[10px] uppercase tracking-wider text-dark-muted">Promedio de resolución</p><p class="text-2xl font-semibold text-blue-400 mt-1">{{ $duracion($sla['promedio_resolucion_minutos']) }}</p><p class="text-[10px] text-dark-muted mt-1">Toma: {{ $duracion($sla['promedio_toma_minutos']) }}</p></div>
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="xl:col-span-2 bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Casos próximos o fuera de SLA</h3><p class="text-xs text-dark-muted mt-0.5">Ordenados por vencimiento y prioridad.</p></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-xs"><thead><tr class="bg-dark-card/60 text-dark-muted uppercase tracking-wider text-[10px]"><th class="text-left px-4 py-3">Caso</th><th class="text-left px-3 py-3">Prioridad</th><th class="text-left px-3 py-3">Responsable</th><th class="text-left px-3 py-3">Detectado</th><th class="text-left px-4 py-3">SLA</th></tr></thead><tbody>
                    @forelse($sla['criticos'] as $item)
                        @php $caso = $item['caso']; @endphp
                        <tr class="border-t border-dark-border/60 hover:bg-dark-card/40"><td class="px-4 py-3"><a href="{{ route('pendientes.index', ['tipo' => 'casos', 'estado' => 'todos', 'caso_id' => $caso->id]) }}" class="font-medium text-info hover:text-dark-text">{{ $caso->titulo }}</a><span class="block text-[10px] text-dark-muted font-mono mt-1">#{{ $caso->id }} · {{ $caso->sesion_id ?: 'Sin sesión' }}</span></td><td class="px-3 py-3"><span class="px-2 py-1 rounded-full {{ $caso->prioridad === 'alta' ? 'bg-red-500/10 text-red-400' : ($caso->prioridad === 'media' ? 'bg-amber-500/10 text-amber-400' : 'bg-blue-500/10 text-blue-400') }}">{{ strtoupper($caso->prioridad) }}</span></td><td class="px-3 py-3 text-dark-text">{{ $caso->asignadoA?->name ?? 'Sin asignar' }}</td><td class="px-3 py-3 text-dark-muted font-mono">{{ $caso->detectado_en?->format('d/m H:i') }}</td><td class="px-4 py-3"><span class="font-semibold {{ $item['vencido'] ? 'text-red-400' : 'text-green-400' }}">{{ $item['vencido'] ? 'Vencido' : 'Dentro del SLA' }}</span><span class="block text-[10px] text-dark-muted mt-1">Límite {{ $item['vence_en']?->format('d/m H:i') }}</span></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-green-400">No existen casos abiertos.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
            <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden"><div class="px-5 py-4 border-b border-dark-border"><h3 class="text-sm font-semibold text-dark-text">Atención por responsable</h3></div><div class="divide-y divide-dark-border">
                @forelse($sla['responsables'] as $responsable)
                    <div class="px-5 py-3 flex items-center justify-between gap-3"><div><p class="text-xs font-medium text-dark-text">{{ $responsable['usuario']?->name ?? 'Usuario eliminado' }}</p><p class="text-[10px] text-dark-muted mt-1">{{ $responsable['asignados'] }} asignados · {{ $responsable['cerrados'] }} cerrados</p></div><div class="text-right"><p class="text-sm font-semibold {{ $responsable['abiertos'] ? 'text-amber-400' : 'text-green-400' }}">{{ $responsable['abiertos'] }} abiertos</p><p class="text-[10px] text-dark-muted mt-1">{{ $duracion($responsable['promedio_resolucion_minutos']) }}</p></div></div>
                @empty
                    <div class="px-5 py-10 text-center text-dark-muted text-xs">Todavía no hay casos asignados.</div>
                @endforelse
            </div></div>
        </section>

        <section>
            <div class="mb-3"><h3 class="text-sm font-semibold text-dark-text">Historial administrativo</h3><p class="text-xs text-dark-muted mt-0.5">Los datos sensibles y contenidos extensos se ocultan automáticamente.</p></div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="bg-dark-panel border border-dark-border rounded-lg p-3"><p class="text-[10px] uppercase text-dark-muted">Registros filtrados</p><p class="text-xl font-semibold text-dark-text mt-1">{{ number_format($resumen['total']) }}</p></div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-3"><p class="text-[10px] uppercase text-dark-muted">Últimas 24 horas</p><p class="text-xl font-semibold text-blue-400 mt-1">{{ number_format($resumen['ultimas_24h']) }}</p></div>
                <div class="bg-dark-panel border border-dark-border rounded-lg p-3"><p class="text-[10px] uppercase text-dark-muted">Usuarios</p><p class="text-xl font-semibold text-dark-text mt-1">{{ number_format($resumen['usuarios']) }}</p></div>
                <div class="bg-dark-panel border {{ $resumen['fallidos'] ? 'border-red-500/40' : 'border-dark-border' }} rounded-lg p-3"><p class="text-[10px] uppercase text-dark-muted">Resultados no exitosos</p><p class="text-xl font-semibold {{ $resumen['fallidos'] ? 'text-red-400' : 'text-green-400' }} mt-1">{{ number_format($resumen['fallidos']) }}</p></div>
            </div>

            <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden mb-4">
                <form method="GET" action="{{ route('auditoria.index') }}" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3 items-end">
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Desde</label><input type="date" name="desde" value="{{ request('desde') }}" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text"></div>
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Hasta</label><input type="date" name="hasta" value="{{ request('hasta') }}" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text"></div>
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Usuario</label><select name="usuario_id" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text"><option value="">Todos</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>{{ $usuario->name }}</option>@endforeach</select></div>
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Módulo</label><select name="modulo" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text"><option value="">Todos</option>@foreach($modulos as $modulo)<option value="{{ $modulo }}" @selected(request('modulo') === $modulo)>{{ $modulo }}</option>@endforeach</select></div>
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Acción</label><select name="accion" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text"><option value="">Todas</option>@foreach($acciones as $accion)<option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ \App\Support\AuditoriaPresentador::etiqueta($accion) }}</option>@endforeach</select></div>
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Resultado</label><select name="resultado" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text"><option value="">Todos</option>@foreach($resultados as $resultado)<option value="{{ $resultado }}" @selected(request('resultado') === $resultado)>{{ \App\Support\AuditoriaPresentador::etiqueta($resultado) }}</option>@endforeach</select></div>
                    <div><label class="block text-[10px] uppercase text-dark-muted mb-1">Filas</label><select name="por_pagina" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text">@foreach([20, 50, 100] as $cantidad)<option value="{{ $cantidad }}" @selected((int) request('por_pagina', 20) === $cantidad)>{{ $cantidad }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2 xl:col-span-5"><label class="block text-[10px] uppercase text-dark-muted mb-1">Buscar</label><input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Usuario, correo, acción, módulo, entidad o descripción" class="w-full bg-dark-card border-dark-border rounded text-xs text-dark-text placeholder-dark-muted"></div>
                    <div class="sm:col-span-2 xl:col-span-3 flex gap-2"><button class="flex-1 bg-corp text-dark-bg font-medium px-4 py-2 rounded text-xs">Aplicar filtros</button><a href="{{ route('auditoria.index') }}" class="px-4 py-2 border border-dark-border rounded text-xs text-dark-muted hover:text-dark-text">Limpiar</a></div>
                </form>
                @can('auditoria.exportar')<div class="px-4 py-3 border-t border-dark-border flex flex-wrap items-center justify-between gap-3 bg-dark-card/30"><p class="text-xs text-dark-muted">La exportación respeta los filtros y nunca incluye contraseñas, tokens, códigos OTP ni contenido binario.</p><form method="POST" action="{{ route('auditoria.export') }}">@csrf @foreach($filtrosExport as $filtro)<input type="hidden" name="{{ $filtro }}" value="{{ request($filtro) }}">@endforeach<button class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded text-xs">Descargar Excel</button></form></div>@endcan
            </div>

            <div class="bg-dark-panel border border-dark-border rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-dark-border flex items-center justify-between"><h3 class="text-sm font-semibold text-dark-text">Eventos</h3><span class="text-xs text-dark-muted">{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} de {{ number_format($logs->total()) }}</span></div>
                <div class="overflow-x-auto scroll-bonito"><table class="w-full min-w-[1250px] text-xs"><thead><tr class="bg-dark-card/60 text-[10px] uppercase tracking-wider text-dark-muted"><th class="text-left px-4 py-3">Fecha</th><th class="text-left px-3 py-3">Usuario</th><th class="text-left px-3 py-3">Acción</th><th class="text-left px-3 py-3">Entidad</th><th class="text-left px-3 py-3">Resultado</th><th class="text-left px-3 py-3">Descripción</th><th class="text-left px-4 py-3">Detalle</th></tr></thead><tbody>
                    @forelse($logs as $log)
                        <tr class="border-t border-dark-border/60 hover:bg-dark-card/40 align-top"><td class="px-4 py-3 whitespace-nowrap font-mono text-dark-muted">{{ $log->fecha_hora?->format('d/m/Y H:i:s') }}</td><td class="px-3 py-3 min-w-44"><span class="font-medium text-dark-text">{{ $log->usuario?->name ?? 'Sistema / n8n' }}</span><span class="block text-[10px] text-dark-muted mt-1">{{ $log->usuario?->email ?? $log->direccion_ip ?? '—' }}</span></td><td class="px-3 py-3"><span class="text-dark-text">{{ \App\Support\AuditoriaPresentador::etiqueta($log->accion) }}</span><span class="block text-[10px] text-dark-muted mt-1">{{ $log->modulo }}</span></td><td class="px-3 py-3 whitespace-nowrap">@if($log->enlace_relacionado)<a href="{{ $log->enlace_relacionado }}" class="text-info hover:text-dark-text">{{ $log->entidad ?: 'Registro' }} #{{ $log->entidad_id }}</a>@else<span class="text-dark-muted">{{ $log->entidad ? $log->entidad.' #'.$log->entidad_id : '—' }}</span>@endif</td><td class="px-3 py-3"><span class="px-2 py-1 rounded-full {{ $log->resultado === 'exitoso' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' }}">{{ \App\Support\AuditoriaPresentador::etiqueta($log->resultado) }}</span></td><td class="px-3 py-3 min-w-64 text-dark-muted">{{ $log->descripcion ?: 'Sin descripción' }}</td><td class="px-4 py-3 min-w-56"><details><summary class="cursor-pointer text-info hover:text-dark-text">Ver cambios</summary><div class="grid grid-cols-1 gap-2 mt-2"><div><p class="text-[10px] uppercase text-dark-muted mb-1">Antes</p><pre class="max-h-48 overflow-auto whitespace-pre-wrap break-all bg-dark-bg border border-dark-border rounded p-2 text-[10px] text-dark-muted">{{ $log->datos_anteriores_seguros }}</pre></div><div><p class="text-[10px] uppercase text-dark-muted mb-1">Después</p><pre class="max-h-48 overflow-auto whitespace-pre-wrap break-all bg-dark-bg border border-dark-border rounded p-2 text-[10px] text-dark-muted">{{ $log->datos_nuevos_seguros }}</pre></div></div></details></td></tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-dark-muted">No existen eventos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody></table></div><div class="px-5 py-4 border-t border-dark-border">{{ $logs->links() }}</div>
            </div>
        </section>
    </div></div>
</x-app-layout>
