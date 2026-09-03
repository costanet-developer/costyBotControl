<?php

namespace App\Exports;

use App\Services\SeguimientoOperativo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumenGerencialExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly array $resumen)
    {
    }

    public function array(): array
    {
        $actual = $this->resumen['actual'];
        $anterior = $this->resumen['anterior'];
        $filas = [
            ['RESUMEN GERENCIAL COSTYBOT'],
            ['Periodo', $this->resumen['inicio']->format('d/m/Y H:i'), $this->resumen['fin']->format('d/m/Y H:i')],
            ['Periodo anterior', $this->resumen['inicio_anterior']->format('d/m/Y H:i'), $this->resumen['fin_anterior']->format('d/m/Y H:i')],
            [],
            ['INDICADOR', 'ACTUAL', 'ANTERIOR', 'VARIACIÓN'],
        ];
        foreach ($this->indicadores() as $clave => $titulo) {
            $filas[] = [$titulo, $actual['metricas'][$clave], $anterior['metricas'][$clave], $this->variacion($this->resumen['variaciones'][$clave] ?? null)];
        }

        $filas[] = [];
        $filas[] = ['EVOLUCIÓN DIARIA'];
        $filas[] = ['Fecha', 'Interacciones', 'Pagos', 'Valor recibido', 'Créditos', 'Casos'];
        foreach ($actual['serie'] as $dia) {
            $filas[] = [$dia['fecha']->format('d/m/Y'), $dia['interacciones'], $dia['pagos'], $dia['monto'], $dia['creditos'], $dia['casos']];
        }

        $filas[] = [];
        $filas[] = ['BANCOS'];
        $filas[] = ['Banco', 'Pagos', 'Monto'];
        foreach ($actual['bancos'] as $banco) {
            $filas[] = [$banco['banco'], $banco['cantidad'], $banco['monto']];
        }

        $filas[] = [];
        $filas[] = ['CASOS Y SLA'];
        $filas[] = ['Abiertos', $this->resumen['sla']['abiertos']];
        $filas[] = ['Sin asignar', $this->resumen['sla']['sin_asignar']];
        $filas[] = ['Vencidos', $this->resumen['sla']['vencidos']];
        $filas[] = ['Promedio de toma', SeguimientoOperativo::duracion($this->resumen['sla']['promedio_toma_minutos'])];
        $filas[] = ['Promedio de resolución', SeguimientoOperativo::duracion($this->resumen['sla']['promedio_resolucion_minutos'])];

        return $filas;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setSize(14);
        $sheet->freezePane('A5');

        return [5 => ['font' => ['bold' => true]]];
    }

    private function indicadores(): array
    {
        return [
            'interacciones' => 'Interacciones', 'clientes' => 'Clientes únicos', 'pagos' => 'Pagos procesados',
            'monto' => 'Valor recibido', 'creditos' => 'Créditos generados', 'sin_evidencia' => 'Pagos sin evidencia',
            'casos_detectados' => 'Casos detectados', 'casos_resueltos' => 'Casos resueltos',
        ];
    }

    private function variacion(?float $valor): string
    {
        return $valor === null ? 'Nuevo' : ($valor > 0 ? '+' : '').number_format($valor, 1).'%';
    }
}
