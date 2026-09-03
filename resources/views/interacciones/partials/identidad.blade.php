@php
    $kyc = $sesion->ultimaValidacionIdentidad;
    $ultimoOtp = $presentacion['ultimo_otp'];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="rounded-lg border border-dark-border bg-dark-card/40 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold text-dark-text">Validación de identidad</h4>
                <p class="text-xs text-dark-muted mt-0.5">Resultado del cruce OCR contra el titular consultado.</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full {{ $kyc?->estado_kyc === 'validada' ? 'bg-green-500/10 text-green-400' : 'bg-dark-panel text-dark-muted' }}">
                {{ \App\Support\InteraccionPresentador::estadoLegible($kyc?->estado_kyc) }}
            </span>
        </div>
        <dl class="mt-4 space-y-2 text-xs">
            <div class="flex justify-between gap-3"><dt class="text-dark-muted">Cédula consultada</dt><dd class="font-mono text-dark-text">{{ $kyc?->cedula ?? $sesion->cedula ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-dark-muted">Comparación OCR</dt><dd class="text-dark-text">{{ \App\Support\InteraccionPresentador::estadoLegible($kyc?->ocr_vs_sistema_resultado) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-dark-muted">Código dactilar</dt><dd class="{{ $kyc?->codigo_dactilar_validado === true ? 'text-green-400' : ($kyc?->codigo_dactilar_validado === false ? 'text-red-400' : 'text-dark-muted') }}">{{ $kyc?->codigo_dactilar_validado === true ? 'Validado' : ($kyc?->codigo_dactilar_validado === false ? 'No coincide' : 'No evaluado') }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-dark-muted">Correo</dt><dd class="font-mono text-dark-text text-right break-all">{{ $presentacion['correo_enmascarado'] ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-dark-muted">Correo verificado</dt><dd class="{{ $kyc?->correo_verificado ? 'text-green-400' : 'text-dark-muted' }}">{{ $kyc?->correo_verificado ? 'Sí' : 'No' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-dark-muted">Derivado a revisión</dt><dd class="{{ $kyc?->derivado_revision ? 'text-amber-400' : 'text-dark-muted' }}">{{ $kyc?->derivado_revision ? 'Sí' : 'No' }}</dd></div>
            @if($kyc?->actualizado_en)
                <div class="flex justify-between gap-3"><dt class="text-dark-muted">Última actualización</dt><dd class="font-mono text-dark-text">{{ $kyc->actualizado_en->format('d/m/Y H:i') }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="rounded-lg border border-dark-border bg-dark-card/40 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold text-dark-text">Verificación OTP</h4>
                <p class="text-xs text-dark-muted mt-0.5">Se muestra el resultado, nunca el código enviado o ingresado.</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full {{ $ultimoOtp?->resultado === 'validado' ? 'bg-green-500/10 text-green-400' : 'bg-dark-panel text-dark-muted' }}">
                {{ \App\Support\InteraccionPresentador::estadoLegible($ultimoOtp?->resultado) }}
            </span>
        </div>
        @if($ultimoOtp)
            <dl class="mt-4 space-y-2 text-xs">
                <div class="flex justify-between gap-3"><dt class="text-dark-muted">Correo destino</dt><dd class="font-mono text-dark-text text-right break-all">{{ \App\Support\InteraccionPresentador::enmascararCorreo($ultimoOtp->correo) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-dark-muted">Intentos utilizados</dt><dd class="font-mono text-dark-text">{{ $ultimoOtp->intentos ?? 0 }} de {{ $ultimoOtp->max_intentos ?? 3 }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-dark-muted">Generado</dt><dd class="font-mono text-dark-text">{{ $ultimoOtp->creado_en?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-dark-muted">Expiración</dt><dd class="font-mono text-dark-text">{{ $ultimoOtp->expira_en?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-dark-muted">Envíos registrados</dt><dd class="font-mono text-dark-text">{{ $sesion->otpVerificaciones->count() }}</dd></div>
            </dl>
        @else
            <div class="mt-4 text-sm text-dark-muted">No se generó OTP en esta sesión.</div>
        @endif
    </div>
</div>

<div class="mt-5">
    <div class="flex items-start justify-between gap-3 mb-3">
        <div>
            <h4 class="text-sm font-semibold text-dark-text">Documentos de identidad</h4>
            <p class="text-xs text-dark-muted mt-0.5">Anverso y reverso conservados como evidencia protegida.</p>
        </div>
        <span class="text-xs font-mono text-dark-muted">{{ $sesion->documentosIdentidad->count() }}</span>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        @forelse($sesion->documentosIdentidad as $documento)
            <article class="rounded-lg border border-dark-border overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-4 py-3 bg-dark-card/50 border-b border-dark-border">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-dark-text uppercase">{{ $documento->lado ?: 'Lado no identificado' }}</span>
                        @if($documento->ocr_valido === true)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-500/10 text-green-400">OCR válido</span>
                        @elseif($documento->ocr_valido === false)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/10 text-red-400">OCR inválido</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-mono text-dark-muted">{{ $documento->fecha_hora?->format('d/m/Y H:i') }}</span>
                </div>

                <div class="p-4">
                    @can('documentos_identidad.ver')
                        @if($documento->ruta_imagen)
                            <img src="{{ route('documentos-identidad.imagen', $documento) }}"
                                 alt="{{ ucfirst($documento->lado ?? 'Documento de identidad') }}"
                                 onclick="verImagen(this.src)"
                                 class="w-full h-44 object-contain bg-dark-card border border-dark-border rounded cursor-zoom-in hover:border-corp/50 transition-colors">
                            @can('documentos_identidad.descargar')
                                <div class="mt-2 text-right"><a href="{{ route('documentos-identidad.descargar', $documento) }}" class="text-xs text-info hover:text-dark-text">Descargar &darr;</a></div>
                            @endcan
                        @else
                            <div class="h-32 flex items-center justify-center rounded bg-dark-card border border-dark-border text-xs text-dark-muted">Sin imagen conservada</div>
                        @endif
                    @else
                        <div class="h-24 flex items-center justify-center rounded bg-dark-card border border-dark-border text-xs text-dark-muted text-center px-4">Imagen restringida según el rol del usuario.</div>
                    @endcan

                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div><dt class="text-dark-muted">Cédula detectada</dt><dd class="font-mono text-dark-text break-all">{{ $documento->cedula_ocr ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Coincide con consulta</dt><dd class="{{ $documento->coincide === true ? 'text-green-400' : ($documento->coincide === false ? 'text-red-400' : 'text-dark-muted') }}">{{ $documento->coincide === true ? 'Sí' : ($documento->coincide === false ? 'No' : 'No evaluado') }}</dd></div>
                        <div><dt class="text-dark-muted">Nombres</dt><dd class="text-dark-text break-words">{{ $documento->nombres ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Apellidos</dt><dd class="text-dark-text break-words">{{ $documento->apellidos ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Sexo</dt><dd class="text-dark-text">{{ $documento->sexo ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Estado civil</dt><dd class="text-dark-text">{{ $documento->estado_civil ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Código dactilar</dt><dd class="font-mono text-dark-text">{{ $documento->codigo_dactilar ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Tipo de cédula</dt><dd class="text-dark-text">{{ \App\Support\InteraccionPresentador::emisorDocumento($documento->emisor_documento) }}</dd></div>
                        <div><dt class="text-dark-muted">Nacimiento</dt><dd class="font-mono text-dark-text">{{ $documento->fecha_nacimiento ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Expiración</dt><dd class="font-mono text-dark-text">{{ $documento->fecha_expiracion ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Nacionalidad</dt><dd class="text-dark-text">{{ $documento->nacionalidad ?: '—' }}</dd></div>
                        <div><dt class="text-dark-muted">Documento</dt><dd class="text-dark-text">{{ $documento->tipo_documento ?: 'Cédula' }}</dd></div>
                    </dl>

                    @if($documento->observaciones)
                        <div class="mt-3 rounded border border-amber-500/20 bg-amber-500/5 p-2.5 text-xs text-amber-400">{{ $documento->observaciones }}</div>
                    @endif
                </div>
            </article>
        @empty
            <div class="xl:col-span-2 border border-dark-border rounded-lg p-8 text-center text-sm text-dark-muted">No se solicitaron documentos de identidad en esta sesión.</div>
        @endforelse
    </div>
</div>
