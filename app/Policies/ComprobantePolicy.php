<?php

namespace App\Policies;

use App\Models\Comprobante;
use App\Models\User;

class ComprobantePolicy
{
    public function view(User $user, Comprobante $comprobante): bool
    {
        return $user->can('comprobantes.ver');
    }

    public function revisar(User $user, Comprobante $comprobante): bool
    {
        return $user->can('comprobantes.revisar')
            && in_array($comprobante->estado_auditoria, ['PENDIENTE', 'CON_NOVEDAD', 'ESCALADO']);
    }

    public function aprobar(User $user, Comprobante $comprobante): bool
    {
        return $user->can('comprobantes.aprobar')
            && $comprobante->estado_auditoria === 'EN_REVISION';
    }

    public function rechazar(User $user, Comprobante $comprobante): bool
    {
        return $user->can('comprobantes.rechazar')
            && $comprobante->estado_auditoria === 'EN_REVISION';
    }

    public function anular(User $user, Comprobante $comprobante): bool
    {
        return $user->can('configuracion.editar')
            && $comprobante->estado_auditoria !== 'ANULADO';
    }
}
