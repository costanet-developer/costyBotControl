<?php

namespace App\Support;

use App\Models\Sesion;
use Illuminate\Support\Str;

class InteraccionPresentador
{
    public static function construir(Sesion $sesion): array
    {
        $validacion = $sesion->ultimaValidacionIdentidad;
        $ultimoOtp = $sesion->otpVerificaciones->last();
        $correo = $validacion?->correo ?: $ultimoOtp?->correo;

        return [
            'comprobante_principal_id' => $sesion->comprobante_id,
            'estado_pago' => self::estadoPago($sesion),
            'servicios' => self::servicios($sesion),
            'correo_enmascarado' => self::enmascararCorreo($correo),
            'ultimo_otp' => $ultimoOtp,
            'total_credito' => $sesion->saldosFavor->sum(fn ($saldo) => (float) $saldo->excedente),
        ];
    }

    public static function estadoPago(Sesion $sesion): string
    {
        $procesado = $sesion->resultado === 'reactivado'
            || (bool) ($sesion->pago_exitoso_por_evento ?? false)
            || (bool) ($sesion->comprobante_exitoso_existe ?? false)
            || $sesion->comprobantePrincipal?->estado === 'reactivacion_exitosa'
            || ($sesion->relationLoaded('eventos') && $sesion->eventos->contains('paso', 'reactivacion_exitosa'))
            || ($sesion->relationLoaded('comprobantes') && $sesion->comprobantes->contains('estado', 'reactivacion_exitosa'));

        $tieneComprobante = (bool) ($sesion->comprobante_directo_existe ?? false)
            || $sesion->comprobantePrincipal !== null
            || ($sesion->relationLoaded('comprobantes') && $sesion->comprobantes->isNotEmpty());

        if ($procesado) {
            return $tieneComprobante ? 'procesado' : 'procesado_sin_comprobante';
        }

        return $tieneComprobante ? 'recibido_no_procesado' : 'sin_comprobante';
    }

    public static function etiquetaPago(string $estado): string
    {
        return [
            'procesado' => 'Pago procesado',
            'procesado_sin_comprobante' => 'Procesado sin evidencia',
            'recibido_no_procesado' => 'Comprobante recibido',
            'sin_comprobante' => 'Sin comprobante',
        ][$estado] ?? Str::headline($estado);
    }

    public static function etiquetaPaso(?string $paso): string
    {
        return [
            'mensaje_recibido' => 'Mensaje recibido',
            'menu_principal_mostrado' => 'Menú principal mostrado',
            'menu_reactivar_seleccionado' => 'Reactivación seleccionada',
            'cedula_valida' => 'Cédula encontrada',
            'cedula_invalida' => 'Cédula no encontrada',
            'comprobante_recibido' => 'Comprobante recibido',
            'comprobante_no_duplicado' => 'Comprobante disponible',
            'comprobante_duplicado' => 'Comprobante duplicado',
            'ocr_legible' => 'Comprobante leído por OCR',
            'monto_ok' => 'Monto validado',
            'monto_no_coincide' => 'Monto no coincide',
            'menu_servicios_mostrado' => 'Servicios disponibles mostrados',
            'servicio_seleccionado' => 'Servicio seleccionado',
            'reactivacion_exitosa' => 'Reactivación exitosa',
            'reintento_comprobante' => 'Nuevo intento de comprobante',
            'mensaje_seguro_kyc' => 'Validación de identidad iniciada',
            'interaccion_finalizada' => 'Interacción finalizada',
        ][$paso] ?? Str::headline((string) $paso);
    }

    public static function enmascararCorreo(?string $correo): ?string
    {
        if (! $correo || ! str_contains($correo, '@')) {
            return $correo ?: null;
        }

        [$usuario, $dominio] = explode('@', $correo, 2);
        $visible = mb_substr($usuario, 0, min(2, mb_strlen($usuario)));

        return $visible.str_repeat('•', max(3, min(8, mb_strlen($usuario) - mb_strlen($visible)))).'@'.$dominio;
    }

    public static function estadoLegible(?string $estado): string
    {
        if (! $estado) {
            return 'No registrado';
        }

        return [
            'validada' => 'Validada',
            'validado' => 'Validado',
            'coincide' => 'Coincide',
            'aplicado' => 'Aplicado',
            'pendiente' => 'Pendiente',
            'cancelado' => 'Cancelado',
            'expirado' => 'Expirado',
            'fallido' => 'Fallido',
            'reiniciada_prueba' => 'Reiniciada para prueba',
        ][mb_strtolower($estado)] ?? Str::headline($estado);
    }

    public static function emisorDocumento(?string $emisor): string
    {
        return [
            'registro_civil_gobierno' => 'Registro Civil (Gobierno)',
            'gobierno' => 'Registro Civil (Gobierno)',
            'municipio_guayaquil' => 'Cédula municipal de Guayaquil',
            'municipal_guayaquil' => 'Cédula municipal de Guayaquil',
        ][mb_strtolower((string) $emisor)] ?? ($emisor ? Str::headline($emisor) : 'No identificado');
    }

    private static function servicios(Sesion $sesion): array
    {
        $contratos = is_array($sesion->servicios_disponibles) ? $sesion->servicios_disponibles : [];

        return collect($contratos)->map(function ($contrato) use ($sesion) {
            $codigo = (string) ($contrato['codigo'] ?? '');
            $internet = collect($contrato['servicios'] ?? [])->map(fn ($servicio) => [
                'nombre' => $servicio['perfil'] ?? Str::headline((string) ($servicio['tiposervicio'] ?? 'Internet')),
                'tipo' => Str::headline((string) ($servicio['tiposervicio'] ?? 'internet')),
                'costo' => $servicio['costo'] ?? null,
                'estado' => $servicio['status_user'] ?? null,
                'direccion' => $servicio['direccion'] ?? null,
            ])->values()->all();

            $recurrentes = collect(data_get($contrato, 'otros_servicios.recurrentes', []))->map(fn ($servicio) => [
                'nombre' => $servicio['nombre'] ?? $servicio['descripcion'] ?? $servicio['producto'] ?? $servicio['tipo'] ?? 'Servicio adicional',
                'tipo' => 'Servicio adicional',
                'costo' => $servicio['monto'] ?? null,
                'estado' => $servicio['state'] ?? null,
                'direccion' => null,
            ])->values()->all();

            return [
                'codigo' => $codigo,
                'nombre' => $contrato['nombre'] ?? 'Contrato sin nombre',
                'estado' => $contrato['estado'] ?? null,
                'deuda' => data_get($contrato, 'facturacion.total_facturas'),
                'facturas' => data_get($contrato, 'facturacion.facturas_nopagadas'),
                'direccion' => $contrato['direccion_principal'] ?? null,
                'seleccionado' => $codigo !== '' && $codigo === (string) $sesion->codigo_servicio_elegido,
                'items' => array_merge($internet, $recurrentes),
            ];
        })->values()->all();
    }
}
