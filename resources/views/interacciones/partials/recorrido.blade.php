<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <h4 class="text-sm font-semibold text-dark-text">Recorrido de la sesión</h4>
        <p class="text-xs text-dark-muted mt-0.5">Eventos ordenados cronológicamente desde el primer contacto hasta el cierre.</p>
    </div>
    <span class="text-xs font-mono text-dark-muted shrink-0">{{ $sesion->eventos->count() }} eventos</span>
</div>

<div class="relative pl-6">
    <div class="absolute left-[7px] top-1 bottom-1 w-px bg-dark-border"></div>
    @forelse($sesion->eventos as $evento)
        @php
            $detalles = [];
            if ($evento->estado_conversacion) $detalles['Estado'] = $evento->estado_conversacion;
            if ($evento->intentos_comprobante !== null) $detalles['Intentos'] = $evento->intentos_comprobante;
            if ($evento->cedula) $detalles['Cédula'] = $evento->cedula;
            if ($evento->tipo_comprobante) $detalles['Tipo comprobante'] = $evento->tipo_comprobante;
            if ($evento->duplicado !== null) $detalles['Duplicado'] = $evento->duplicado ? 'Sí' : 'No';
            if ($evento->opcion_ocr) $detalles['Opción OCR'] = $evento->opcion_ocr;
            if ($evento->monto_esperado !== null) $detalles['Monto'] = '$' . number_format((float) $evento->monto_esperado, 2);
            if ($evento->deuda_total !== null) $detalles['Deuda'] = '$' . number_format((float) $evento->deuda_total, 2);

            $datos = is_array($evento->datos_adicionales) ? $evento->datos_adicionales : (json_decode((string) $evento->datos_adicionales, true) ?: []);
            $etiquetas = ['mensaje' => 'Mensaje', 'boton_id' => 'ID botón', 'boton_titulo' => 'Título botón', 'tipo_mensaje' => 'Tipo mensaje', 'nombre_contacto' => 'Contacto'];
            foreach ($datos as $k => $v) {
                if ($v === null || $v === '' || $v === []) continue;
                $clave = mb_strtolower((string) $k);
                if (preg_match('/(^|_)(otp|codigo_enviado|codigo_ingresado|password|token|ppppass)($|_)/', $clave)) continue;
                if (in_array($clave, ['correo', 'email'], true)) $v = \App\Support\InteraccionPresentador::enmascararCorreo((string) $v);
                $detalles[$etiquetas[$k] ?? \Illuminate\Support\Str::headline((string) $k)] = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            }

            $paso = (string) $evento->paso;
            $esExito = str_contains($paso, 'exitoso') || str_contains($paso, 'valida') || in_array($paso, ['monto_ok', 'ocr_legible', 'comprobante_no_duplicado'], true);
            $esAlerta = str_contains($paso, 'invalida') || str_contains($paso, 'duplicado') || str_contains($paso, 'no_coincide') || str_contains($paso, 'fallido') || str_contains($paso, 'sin_resolucion');
        @endphp
        <div class="relative pb-5 last:pb-0" x-data="{ abierto: false }">
            <div class="absolute -left-6 top-1.5 w-3.5 h-3.5 rounded-full border-[3px] border-dark-panel {{ $esExito ? 'bg-green-500' : ($esAlerta ? 'bg-red-500' : 'bg-dark-muted') }}"></div>
            <button type="button" @click="abierto = ! abierto" class="w-full text-left group">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span class="text-sm font-medium text-dark-text">{{ \App\Support\InteraccionPresentador::etiquetaPaso($evento->paso) }}</span>
                            <span class="font-mono text-[10px] text-dark-muted">{{ $evento->fecha_evento?->format('d/m/Y H:i:s') }}</span>
                        </div>
                        @if($evento->monto_esperado !== null || $evento->deuda_total !== null)
                            <div class="text-xs text-dark-muted mt-1">
                                @if($evento->monto_esperado !== null) Monto: ${{ number_format((float) $evento->monto_esperado, 2) }} @endif
                                @if($evento->deuda_total !== null) · Deuda: ${{ number_format((float) $evento->deuda_total, 2) }} @endif
                            </div>
                        @endif
                    </div>
                    @if($detalles)
                        <svg class="w-3.5 h-3.5 text-dark-muted shrink-0 mt-1 transition-transform duration-200" :class="{ 'rotate-180': abierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    @endif
                </div>
            </button>
            @if($detalles)
                <div x-show="abierto" x-cloak class="mt-2 rounded border border-dark-border bg-dark-card/50 p-3">
                    <dl class="space-y-1.5">
                        @foreach($detalles as $k => $v)
                            <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 sm:gap-3 text-xs">
                                <dt class="text-dark-muted shrink-0">{{ $k }}</dt>
                                <dd class="font-mono text-dark-text sm:text-right break-all">{{ $v }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>
    @empty
        <p class="text-dark-muted text-sm">Sin eventos registrados.</p>
    @endforelse
</div>
