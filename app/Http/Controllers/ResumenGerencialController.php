<?php

namespace App\Http\Controllers;

use App\Exports\ResumenGerencialExport;
use App\Models\AuditoriaLog;
use App\Services\ResumenGerencialService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ResumenGerencialController extends Controller
{
    public function index(Request $request, ResumenGerencialService $service)
    {
        abort_unless(auth()->user()->can('auditoria.ver'), 403);
        [$inicio, $fin, $periodo] = $this->periodo($request);
        $resumen = $service->generar($inicio, $fin);

        return view('resumen-gerencial.index', compact('resumen', 'periodo'));
    }

    public function export(Request $request, ResumenGerencialService $service)
    {
        abort_unless(auth()->user()->can('auditoria.exportar'), 403);
        [$inicio, $fin, $periodo] = $this->periodo($request);
        $resumen = $service->generar($inicio, $fin);

        AuditoriaLog::create([
            'usuario_id' => auth()->id(),
            'accion' => 'exportar_resumen_gerencial',
            'modulo' => 'Resumen gerencial',
            'entidad' => 'Reporte',
            'datos_nuevos' => ['periodo' => $periodo, 'desde' => $inicio->toIso8601String(), 'hasta' => $fin->toIso8601String()],
            'direccion_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => 'Exportación controlada del resumen gerencial.',
        ]);

        return Excel::download(new ResumenGerencialExport($resumen), 'resumen_gerencial_'.$inicio->format('Ymd').'_'.$fin->format('Ymd').'.xlsx');
    }

    private function periodo(Request $request): array
    {
        $datos = Validator::make($request->all(), [
            'periodo' => ['nullable', Rule::in(['hoy', 'ayer', '7_dias', '30_dias', 'personalizado'])],
            'desde' => ['nullable', 'required_if:periodo,personalizado', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'required_if:periodo,personalizado', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ])->validate();
        $periodo = $datos['periodo'] ?? '7_dias';

        [$inicio, $fin] = match ($periodo) {
            'hoy' => [CarbonImmutable::now()->startOfDay(), CarbonImmutable::now()->endOfDay()],
            'ayer' => [CarbonImmutable::now()->subDay()->startOfDay(), CarbonImmutable::now()->subDay()->endOfDay()],
            '30_dias' => [CarbonImmutable::now()->subDays(29)->startOfDay(), CarbonImmutable::now()->endOfDay()],
            'personalizado' => [CarbonImmutable::parse($datos['desde'])->startOfDay(), CarbonImmutable::parse($datos['hasta'])->endOfDay()],
            default => [CarbonImmutable::now()->subDays(6)->startOfDay(), CarbonImmutable::now()->endOfDay()],
        };

        if ($inicio->diffInDays($fin) > 366) {
            abort(422, 'El resumen no puede abarcar más de 366 días.');
        }

        return [$inicio, $fin, $periodo];
    }
}
