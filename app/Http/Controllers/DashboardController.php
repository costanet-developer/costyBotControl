<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\SaldoFavor;
use App\Models\Sesion;
use App\Models\ValidacionIdentidad;
use App\Services\IndicadoresKpiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, IndicadoresKpiService $indicadoresKpi)
    {
        abort_unless(auth()->user()->can('interacciones.ver'), 403);

        $hoy = Carbon::today();
        $periodoKpi = in_array((int) $request->integer('periodo_kpi', 30), [7, 30, 90], true)
            ? (int) $request->integer('periodo_kpi', 30)
            : 30;
        $desdeKpi = now()->subDays($periodoKpi - 1)->startOfDay();
        $hastaKpi = now()->endOfDay();
        $kpis = $indicadoresKpi->resumen($desdeKpi, $hastaKpi);

        $sesionesHoy = Sesion::whereDate('inicio', $hoy)->count();
        $pagosHoy = Sesion::pagoProcesado()->whereDate('inicio', $hoy)->count();
        $pagosProcesados = Sesion::pagoProcesado()->count();
        $procesadosSinEvidencia = Sesion::pagoProcesado()->sinComprobanteRelacionado()->count();
        $pendientes = Comprobante::where('estado_auditoria', 'PENDIENTE')->count();
        $enRevision = Comprobante::where('estado_auditoria', 'EN_REVISION')->count();
        $totalSesiones = Sesion::count();
        $creditosPendientes = SaldoFavor::where('estado', 'pendiente')->count();
        $valorCreditosPendientes = (float) SaldoFavor::where('estado', 'pendiente')->sum('excedente');
        $kycEnRevision = ValidacionIdentidad::where('derivado_revision', true)->count();

        $ultimosPagos = Sesion::with(['cliente', 'comprobantePrincipal', 'comprobantes', 'saldosFavor'])
            ->withExists([
                'eventos as pago_exitoso_por_evento' => fn ($eventos) => $eventos->where('paso', 'reactivacion_exitosa'),
                'comprobantes as comprobante_directo_existe',
                'comprobantes as comprobante_exitoso_existe' => fn ($comprobantes) => $comprobantes->where('estado', 'reactivacion_exitosa'),
            ])
            ->pagoProcesado()
            ->latest('inicio')
            ->take(8)
            ->get();

        return view('dashboard-control', compact(
            'sesionesHoy', 'pagosHoy', 'pagosProcesados', 'procesadosSinEvidencia',
            'pendientes', 'enRevision', 'totalSesiones', 'creditosPendientes',
            'valorCreditosPendientes', 'kycEnRevision', 'ultimosPagos', 'kpis',
            'periodoKpi', 'desdeKpi', 'hastaKpi'
        ));
    }
}
