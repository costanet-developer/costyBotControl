<?php

namespace App\Exports;

use App\Models\Comprobante;
use App\Models\Sesion;
use App\Support\InteraccionPresentador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InteraccionesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
{
    public function __construct(protected Request $request)
    {
    }

    public function query(): Builder
    {
        $query = Sesion::with([
            'cliente',
            'comprobantePrincipal',
            'comprobantes' => fn ($comprobantes) => $comprobantes->orderBy('fecha_hora')->orderBy('id'),
            'eventos' => fn ($eventos) => $eventos->orderBy('fecha_evento'),
            'ultimaValidacionIdentidad',
            'otpVerificaciones' => fn ($otp) => $otp->orderBy('creado_en'),
            'saldosFavor',
        ])->withExists([
            'eventos as pago_exitoso_por_evento' => fn ($eventos) => $eventos->where('paso', 'reactivacion_exitosa'),
            'comprobantes as comprobante_directo_existe',
            'comprobantes as comprobante_exitoso_existe' => fn ($comprobantes) => $comprobantes->where('estado', 'reactivacion_exitosa'),
        ]);

        match ($this->tipoReporte()) {
            'procesado' => $query->pagoProcesado(),
            'procesado_sin_comprobante' => $query->pagoProcesado()->sinComprobanteRelacionado(),
            'recibido_no_procesado' => $query->recibidoSinProcesar(),
            'sin_comprobante' => $query->sinPagoNiComprobante(),
            default => null,
        };

        if ($this->request->filled('bot')) {
            $query->where('bot', $this->request->input('bot'));
        }

        if ($this->request->filled('desde')) {
            $query->whereDate('inicio', '>=', $this->request->input('desde'));
        }

        if ($this->request->filled('hasta')) {
            $query->whereDate('inicio', '<=', $this->request->input('hasta'));
        }

        if ($this->request->filled('banco')) {
            $banco = $this->request->input('banco');
            $query->where(function (Builder $query) use ($banco) {
                $query->whereHas('comprobantes', fn (Builder $comprobantes) => $comprobantes->where('banco', $banco))
                    ->orWhereHas('comprobantePrincipal', fn (Builder $comprobante) => $comprobante->where('banco', $banco));
            });
        }

        if ($this->request->filled('estado_auditoria')) {
            $estado = $this->request->input('estado_auditoria');
            $query->where(function (Builder $query) use ($estado) {
                $query->whereHas('comprobantes', fn (Builder $comprobantes) => $comprobantes->where('estado_auditoria', $estado))
                    ->orWhereHas('comprobantePrincipal', fn (Builder $comprobante) => $comprobante->where('estado_auditoria', $estado));
            });
        }

        if ($this->request->filled('buscar')) {
            $buscar = trim((string) $this->request->input('buscar'));
            $query->where(function (Builder $query) use ($buscar) {
                $query->where('sesion_id', 'ilike', "%{$buscar}%")
                    ->orWhere('numero_whatsapp', 'ilike', "%{$buscar}%")
                    ->orWhere('cedula', 'ilike', "%{$buscar}%")
                    ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->where('nombre', 'ilike', "%{$buscar}%"))
                    ->orWhereHas('comprobantes', function (Builder $comprobantes) use ($buscar) {
                        $comprobantes->where('numero_transaccion', 'ilike', "%{$buscar}%")
                            ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
                    })
                    ->orWhereHas('comprobantePrincipal', function (Builder $comprobante) use ($buscar) {
                        $comprobante->where('numero_transaccion', 'ilike', "%{$buscar}%")
                            ->orWhere('numero_documento', 'ilike', "%{$buscar}%");
                    });
            });
        }

        return $query;
    }

    public function collection(): Collection
    {
        return $this->query()->latest('inicio')->get()->map(fn (Sesion $sesion) => $this->fila($sesion));
    }

    public function fila(Sesion $sesion): array
    {
        return [
            'sesion' => $sesion,
            'comprobante' => $this->comprobanteDelPago($sesion),
        ];
    }

    public function comprobanteDelPago(Sesion $sesion): ?Comprobante
    {
        if ($sesion->comprobantePrincipal) {
            return $sesion->comprobantePrincipal;
        }

        $referencia = $sesion->eventos->firstWhere('paso', 'reactivacion_exitosa')?->fecha_evento ?? $sesion->fin;
        $ordenados = $sesion->comprobantes->sortBy(fn ($comprobante) => $comprobante->fecha_hora?->timestamp ?? $comprobante->id);

        if ($referencia) {
            $previos = $ordenados->filter(fn ($comprobante) => $comprobante->fecha_hora && $comprobante->fecha_hora <= $referencia);
            if ($previos->isNotEmpty()) {
                return $previos->last();
            }
        }

        return $ordenados->last();
    }

    public function resumen(): array
    {
        $sesiones = $this->query()->get();
        $comprobantes = $sesiones->map(fn (Sesion $sesion) => $this->comprobanteDelPago($sesion));

        return [
            'total' => $sesiones->count(),
            'monto' => $comprobantes->filter()->sum(fn (Comprobante $comprobante) => (float) $comprobante->monto),
            'credito' => $sesiones->sum(fn (Sesion $sesion) => $sesion->saldosFavor->sum(fn ($saldo) => (float) $saldo->excedente)),
            'sin_evidencia' => $comprobantes->filter(fn ($comprobante) => $comprobante === null)->count(),
        ];
    }

    public function tipoReporte(): string
    {
        return (string) $this->request->input('tipo', 'procesado');
    }

    public function headings(): array
    {
        return [
            'Fecha de interacción',
            'ID de sesión',
            'WhatsApp',
            'Cédula/RUC',
            'Cliente',
            'Resultado de sesión',
            'Estado del pago',
            'Fecha del comprobante',
            'N° transacción / Control',
            'N° documento',
            'Banco',
            'Valor recibido',
            'Titular de origen',
            'Cuenta de origen',
            'Beneficiario',
            'Cuenta destino',
            'Estado de auditoría',
            'Crédito generado',
            'Estado KYC',
            'Resultado OTP',
        ];
    }

    public function map($fila): array
    {
        /** @var Sesion $sesion */
        $sesion = $fila['sesion'];
        /** @var Comprobante|null $comprobante */
        $comprobante = $fila['comprobante'];
        $ultimoOtp = $sesion->otpVerificaciones->last();

        return [
            $sesion->inicio?->format('d/m/Y H:i'),
            $sesion->sesion_id,
            $sesion->numero_whatsapp,
            $this->cedulaDe($sesion, $comprobante),
            $sesion->cliente?->nombre ?? '—',
            $sesion->resultado ?: 'En curso',
            InteraccionPresentador::etiquetaPago(InteraccionPresentador::estadoPago($sesion)),
            $comprobante?->fecha_comprobante ?? $comprobante?->fecha_hora?->format('d/m/Y') ?? '—',
            $comprobante?->numero_transaccion ?? '—',
            $comprobante?->numero_documento ?? '—',
            $comprobante?->banco ?? '—',
            $comprobante ? (float) $comprobante->monto : 0,
            $comprobante?->titular_origen ?? '—',
            $comprobante?->cuenta_origen ?? '—',
            $comprobante?->titular ?? '—',
            $comprobante?->cuenta_destino ?? '—',
            $comprobante?->estado_auditoria ?? 'SIN EVIDENCIA',
            (float) $sesion->saldosFavor->sum(fn ($saldo) => (float) $saldo->excedente),
            InteraccionPresentador::estadoLegible($sesion->ultimaValidacionIdentidad?->estado_kyc),
            InteraccionPresentador::estadoLegible($ultimoOtp?->resultado),
        ];
    }

    public function cedulaDe(Sesion $sesion, ?Comprobante $comprobante): string
    {
        if ($sesion->cedula) {
            return $sesion->cedula;
        }

        $evento = $sesion->eventos->first(fn ($evento) => $evento->cedula);

        return $evento?->cedula ?? $comprobante?->cedula ?? '—';
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'N' => NumberFormat::FORMAT_TEXT,
            'P' => NumberFormat::FORMAT_TEXT,
            'R' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }
}
