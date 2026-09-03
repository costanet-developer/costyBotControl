<?php

namespace App\Actions;

use App\Enums\EstadoAuditoria;
use App\Models\AuditoriaLog;
use App\Models\Comprobante;
use App\Models\RevisionComprobante;
use Illuminate\Support\Facades\Auth;

class CambiarEstadoComprobante
{
    public function execute(Comprobante $comprobante, EstadoAuditoria $nuevoEstado, ?string $observacion = null): Comprobante
    {
        $estadoActual = EstadoAuditoria::from($comprobante->estado_auditoria);

        if (!$estadoActual->puedeTransicionarA($nuevoEstado)) {
            abort(403, "No se puede cambiar de {$estadoActual->value} a {$nuevoEstado->value}.");
        }

        $user = Auth::user();
        if (!$user || !$user->can($estadoActual->permisoRequeridoPara($nuevoEstado))) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        $datosAnteriores = [
            'estado_auditoria' => $comprobante->estado_auditoria,
        ];

        $comprobante->estado_auditoria = $nuevoEstado->value;

        match ($nuevoEstado) {
            EstadoAuditoria::EN_REVISION => $comprobante->revisado_por = $user->id,
            EstadoAuditoria::APROBADO => $comprobante->aprobado_por = $user->id,
            EstadoAuditoria::RECHAZADO => $comprobante->rechazado_por = $user->id,
            default => null,
        };

        match ($nuevoEstado) {
            EstadoAuditoria::EN_REVISION => $comprobante->revisado_en = now(),
            EstadoAuditoria::APROBADO => $comprobante->aprobado_en = now(),
            EstadoAuditoria::RECHAZADO => $comprobante->rechazado_en = now(),
            default => null,
        };

        if ($nuevoEstado === EstadoAuditoria::RECHAZADO && $observacion) {
            $comprobante->motivo_rechazo = $observacion;
        }

        $comprobante->updated_by = $user->id;
        $comprobante->save();

        RevisionComprobante::create([
            'comprobante_id' => $comprobante->id,
            'usuario_id' => $user->id,
            'estado_anterior' => $datosAnteriores['estado_auditoria'],
            'estado_nuevo' => $nuevoEstado->value,
            'observacion' => $observacion,
        ]);

        AuditoriaLog::create([
            'usuario_id' => $user->id,
            'accion' => 'cambio_estado',
            'modulo' => 'Comprobante',
            'entidad' => 'Comprobante',
            'entidad_id' => $comprobante->id,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => ['estado_auditoria' => $nuevoEstado->value],
            'direccion_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => "Cambio de {$datosAnteriores['estado_auditoria']} a {$nuevoEstado->value}" . ($observacion ? ": {$observacion}" : ''),
        ]);

        return $comprobante;
    }
}
