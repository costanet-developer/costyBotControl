<?php

namespace App\Services;

use App\Models\CasoOperativo;
use App\Models\Comprobante;
use App\Models\OtpVerificacion;
use App\Models\SaldoFavor;
use App\Models\Sesion;
use App\Models\ValidacionIdentidad;
use Illuminate\Support\Collection;

class CasoOperativoDetector
{
    public function detectar(): array
    {
        $resultado = ['nuevos' => 0, 'actualizados' => 0, 'por_tipo' => []];

        foreach ([
            $this->pagosSinEvidencia(),
            $this->transaccionesDuplicadas(),
            $this->creditosInconsistentes(),
            $this->montosNoConciliados(),
            $this->kycDerivados(),
            $this->otpAgotados(),
            $this->sesionesEstancadas(),
        ] as $casos) {
            foreach ($casos as $caso) {
                $this->guardar($caso, $resultado);
            }
        }

        ksort($resultado['por_tipo']);

        return $resultado;
    }

    private function pagosSinEvidencia(): Collection
    {
        return Sesion::pagoProcesado()->sinComprobanteRelacionado()->get()->map(fn (Sesion $sesion) => [
            'clave' => "pago_sin_evidencia:{$sesion->sesion_id}",
            'tipo' => 'pago_sin_evidencia',
            'prioridad' => 'alta',
            'sesion_id' => $sesion->sesion_id,
            'titulo' => 'Pago procesado sin comprobante enlazado',
            'detalle' => [
                'resultado_sesion' => $sesion->resultado,
                'cedula' => $sesion->cedula,
                'inicio' => $sesion->inicio?->toIso8601String(),
            ],
        ]);
    }

    private function transaccionesDuplicadas(): Collection
    {
        return Comprobante::whereNotNull('numero_transaccion')->where('numero_transaccion', '<>', '')
            ->get(['id', 'sesion_id', 'numero_transaccion', 'monto'])
            ->groupBy(fn (Comprobante $comprobante) => $this->normalizarTransaccion($comprobante->numero_transaccion))
            ->filter(fn (Collection $grupo, string $clave) => $clave !== '' && $grupo->count() > 1)
            ->map(function (Collection $grupo, string $clave) {
                $ultimo = $grupo->sortByDesc('id')->first();

                return [
                    'clave' => 'transaccion_duplicada:'.sha1($clave),
                    'tipo' => 'transaccion_duplicada',
                    'prioridad' => 'alta',
                    'sesion_id' => $ultimo->sesion_id,
                    'comprobante_id' => $ultimo->id,
                    'titulo' => 'Número de transacción repetido',
                    'detalle' => [
                        'transaccion_normalizada' => $clave,
                        'comprobantes' => $grupo->pluck('id')->values()->all(),
                        'cantidad' => $grupo->count(),
                    ],
                ];
            })->values();
    }

    private function creditosInconsistentes(): Collection
    {
        return SaldoFavor::all()->filter(function (SaldoFavor $saldo) {
            $esperado = max((float) $saldo->monto_pagado - (float) $saldo->monto_factura, 0);

            return (float) $saldo->excedente <= 0 || abs((float) $saldo->excedente - $esperado) > 0.01;
        })->map(function (SaldoFavor $saldo) {
            $esperado = max((float) $saldo->monto_pagado - (float) $saldo->monto_factura, 0);

            return [
                'clave' => "credito_inconsistente:{$saldo->id}",
                'tipo' => 'credito_inconsistente',
                'prioridad' => 'alta',
                'sesion_id' => $saldo->sesion_id,
                'comprobante_id' => $saldo->comprobante_id,
                'saldo_favor_id' => $saldo->id,
                'titulo' => 'Crédito no coincide con el excedente calculado',
                'detalle' => [
                    'monto_pagado' => (float) $saldo->monto_pagado,
                    'monto_factura' => (float) $saldo->monto_factura,
                    'excedente_registrado' => (float) $saldo->excedente,
                    'excedente_esperado' => $esperado,
                ],
            ];
        })->values();
    }

    private function montosNoConciliados(): Collection
    {
        return Sesion::whereHas('eventos', fn ($eventos) => $eventos->where('paso', 'monto_no_coincide'))
            ->where(function ($sesiones) {
                $sesiones->whereNull('resultado')->orWhere('resultado', '<>', 'reactivado');
            })
            ->whereDoesntHave('eventos', fn ($eventos) => $eventos->where('paso', 'reactivacion_exitosa'))
            ->whereDoesntHave('comprobantes', fn ($comprobantes) => $comprobantes->where('estado', 'reactivacion_exitosa'))
            ->whereDoesntHave('comprobantePrincipal', fn ($comprobante) => $comprobante->where('estado', 'reactivacion_exitosa'))
            ->with(['eventos' => fn ($eventos) => $eventos->where('paso', 'monto_no_coincide')->latest('fecha_evento')])
            ->get()->map(function (Sesion $sesion) {
                $evento = $sesion->eventos->first();

                return [
                    'clave' => "monto_no_conciliado:{$sesion->sesion_id}",
                    'tipo' => 'monto_no_conciliado',
                    'prioridad' => 'media',
                    'sesion_id' => $sesion->sesion_id,
                    'titulo' => 'Monto de comprobante no conciliado',
                    'detalle' => [
                        'monto_comprobante' => $evento?->monto_esperado !== null ? (float) $evento->monto_esperado : null,
                        'deuda_total' => $evento?->deuda_total !== null ? (float) $evento->deuda_total : null,
                        'fecha_evento' => $evento?->fecha_evento?->toIso8601String(),
                    ],
                ];
            });
    }

    private function kycDerivados(): Collection
    {
        return ValidacionIdentidad::where('derivado_revision', true)->get()->map(fn (ValidacionIdentidad $validacion) => [
            'clave' => "kyc_revision:{$validacion->id}",
            'tipo' => 'kyc_revision',
            'prioridad' => 'alta',
            'sesion_id' => $validacion->sesion_id,
            'validacion_identidad_id' => $validacion->id,
            'titulo' => 'Validación de identidad derivada a revisión',
            'detalle' => [
                'cedula' => $validacion->cedula,
                'estado_kyc' => $validacion->estado_kyc,
                'resultado_comparacion' => $validacion->ocr_vs_sistema_resultado,
            ],
        ]);
    }

    private function otpAgotados(): Collection
    {
        return OtpVerificacion::whereColumn('intentos', '>=', 'max_intentos')
            ->where(function ($otp) {
                $otp->whereNull('resultado')->orWhere('resultado', '<>', 'validado');
            })->get()->map(fn (OtpVerificacion $otp) => [
                'clave' => "otp_agotado:{$otp->id}",
                'tipo' => 'otp_agotado',
                'prioridad' => 'media',
                'sesion_id' => $otp->sesion_id,
                'otp_verificacion_id' => $otp->id,
                'titulo' => 'Verificación OTP agotó sus intentos',
                'detalle' => [
                    'cedula' => $otp->cedula,
                    'resultado' => $otp->resultado,
                    'intentos' => $otp->intentos,
                    'max_intentos' => $otp->max_intentos,
                ],
            ]);
    }

    private function sesionesEstancadas(): Collection
    {
        return Sesion::where('estado_sesion', 'activa')
            ->where(function ($sesiones) {
                $sesiones->whereNull('resultado')->orWhere('resultado', '');
            })
            ->whereBetween('inicio', [now()->subDay(), now()->subMinutes(30)])
            ->get()->map(fn (Sesion $sesion) => [
                'clave' => "sesion_estancada:{$sesion->sesion_id}",
                'tipo' => 'sesion_estancada',
                'prioridad' => 'baja',
                'sesion_id' => $sesion->sesion_id,
                'titulo' => 'Sesión reciente sin cierre ni resultado',
                'detalle' => [
                    'inicio' => $sesion->inicio?->toIso8601String(),
                    'estado_sesion' => $sesion->estado_sesion,
                    'intencion' => $sesion->intencion,
                ],
            ]);
    }

    private function guardar(array $datos, array &$resultado): void
    {
        $caso = CasoOperativo::firstOrNew(['clave' => $datos['clave']]);
        $nuevo = ! $caso->exists;

        if ($nuevo) {
            $caso->estado = 'pendiente';
            $caso->detectado_en = now();
        }

        $caso->fill($datos);
        $caso->ultima_deteccion_en = now();
        $caso->save();

        $resultado[$nuevo ? 'nuevos' : 'actualizados']++;
        $resultado['por_tipo'][$datos['tipo']] = ($resultado['por_tipo'][$datos['tipo']] ?? 0) + 1;
    }

    private function normalizarTransaccion(?string $transaccion): string
    {
        $normalizada = strtoupper((string) preg_replace('/[\s.\-]+/', '', trim((string) $transaccion)));

        return preg_replace('/^0+(?=\d)/', '', $normalizada) ?? '';
    }
}
