<?php

namespace App\Http\Controllers;

use App\Exports\InteraccionesExport;
use App\Models\Comprobante;
use App\Models\Sesion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);
        $this->validarFiltros($request);

        $bots = Sesion::select('bot')->distinct()->orderBy('bot')->pluck('bot');
        $bancos = Comprobante::whereNotNull('banco')->where('banco', '<>', '')
            ->select('banco')->distinct()->orderBy('banco')->pluck('banco');

        $export = new InteraccionesExport($request);
        $porPagina = in_array((int) $request->input('por_pagina'), [15, 30, 50], true)
            ? (int) $request->input('por_pagina')
            : 15;
        $sesiones = $export->query()->latest('inicio')->paginate($porPagina)->withQueryString();
        $resumen = $export->resumen();

        return view('reportes.index', compact('bots', 'bancos', 'sesiones', 'export', 'resumen'));
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.exportar'), 403);
        $this->validarFiltros($request);

        return Excel::download(
            new InteraccionesExport($request),
            'control_operativo_'.$request->input('tipo', 'procesado').'_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function validarFiltros(Request $request): void
    {
        $request->validate([
            'tipo' => ['nullable', Rule::in(['procesado', 'procesado_sin_comprobante', 'recibido_no_procesado', 'sin_comprobante', 'todos'])],
            'bot' => ['nullable', 'string', 'max:50'],
            'banco' => ['nullable', 'string', 'max:100'],
            'estado_auditoria' => ['nullable', Rule::in(['PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'ANULADO'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'buscar' => ['nullable', 'string', 'max:150'],
            'por_pagina' => ['nullable', Rule::in([15, 30, 50, '15', '30', '50'])],
        ]);
    }
}
