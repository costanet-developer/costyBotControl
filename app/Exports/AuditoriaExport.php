<?php

namespace App\Exports;

use App\Support\AuditoriaPresentador;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditoriaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $registros)
    {
    }

    public function collection(): Collection
    {
        return $this->registros;
    }

    public function headings(): array
    {
        return ['Fecha y hora', 'Usuario', 'Correo', 'Acción', 'Módulo', 'Entidad', 'ID entidad', 'Resultado', 'Descripción', 'IP', 'Datos anteriores', 'Datos nuevos'];
    }

    public function map($log): array
    {
        return [
            $log->fecha_hora?->format('d/m/Y H:i:s'),
            $log->usuario?->name ?? 'Sistema / n8n',
            $log->usuario?->email ?? '—',
            AuditoriaPresentador::etiqueta((string) ($log->accion ?: 'desconocido')),
            $log->modulo ?: '—',
            $log->entidad ?? '—',
            $log->entidad_id,
            AuditoriaPresentador::etiqueta((string) ($log->resultado ?: 'desconocido')),
            $log->descripcion,
            $log->direccion_ip,
            AuditoriaPresentador::textoSeguro($log->datos_anteriores),
            AuditoriaPresentador::textoSeguro($log->datos_nuevos),
        ];
    }
}
