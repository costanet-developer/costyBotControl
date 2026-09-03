<?php

namespace App\Enums;

enum EstadoAuditoria: string
{
    case PENDIENTE = 'PENDIENTE';
    case EN_REVISION = 'EN_REVISION';
    case APROBADO = 'APROBADO';
    case RECHAZADO = 'RECHAZADO';
    case DUPLICADO = 'DUPLICADO';
    case CON_NOVEDAD = 'CON_NOVEDAD';
    case ESCALADO = 'ESCALADO';
    case ANULADO = 'ANULADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PENDIENTE => [self::EN_REVISION, self::DUPLICADO, self::ANULADO],
            self::EN_REVISION => [self::APROBADO, self::RECHAZADO, self::CON_NOVEDAD, self::ANULADO],
            self::APROBADO => [self::ANULADO],
            self::RECHAZADO => [self::ESCALADO, self::ANULADO],
            self::DUPLICADO => [self::ANULADO],
            self::CON_NOVEDAD => [self::EN_REVISION, self::ANULADO],
            self::ESCALADO => [self::EN_REVISION, self::APROBADO, self::RECHAZADO, self::ANULADO],
            self::ANULADO => [],
        };
    }

    public function permisoRequeridoPara(EstadoAuditoria $destino): string
    {
        return match ($destino) {
            self::EN_REVISION => 'comprobantes.revisar',
            self::APROBADO => 'comprobantes.aprobar',
            self::RECHAZADO => 'comprobantes.rechazar',
            self::ANULADO => 'configuracion.editar',
            default => 'comprobantes.revisar',
        };
    }

    public function puedeTransicionarA(EstadoAuditoria $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_REVISION => 'En Revisión',
            self::APROBADO => 'Aprobado',
            self::RECHAZADO => 'Rechazado',
            self::DUPLICADO => 'Duplicado',
            self::CON_NOVEDAD => 'Con Novedad',
            self::ESCALADO => 'Escalado',
            self::ANULADO => 'Anulado',
        };
    }
}
