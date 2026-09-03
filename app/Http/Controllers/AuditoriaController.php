<?php

namespace App\Http\Controllers;

use App\Exports\AuditoriaExport;
use App\Models\AuditoriaLog;
use App\Models\CasoOperativo;
use App\Models\Comprobante;
use App\Models\DocumentoIdentidad;
use App\Models\User;
use App\Services\AuditoriaFiltro;
use App\Services\SeguimientoOperativo;
use App\Support\AuditoriaPresentador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AuditoriaController extends Controller
{
    public function index(Request $request, AuditoriaFiltro $filtro, SeguimientoOperativo $seguimiento)
    {
        abort_unless(auth()->user()->can('auditoria.ver'), 403);
        $this->validar($request);

        $base = $filtro->query($request);
        $resumen = [
            'total' => (clone $base)->count(),
            'fallidos' => (clone $base)->where('resultado', '<>', 'exitoso')->count(),
            'usuarios' => (clone $base)->whereNotNull('usuario_id')->distinct('usuario_id')->count('usuario_id'),
            'ultimas_24h' => (clone $base)->where('fecha_hora', '>=', now()->subDay())->count(),
        ];

        $porPagina = in_array($request->integer('por_pagina'), [20, 50, 100], true) ? $request->integer('por_pagina') : 20;
        $logs = $base->latest('fecha_hora')->latest('id')->paginate($porPagina)->withQueryString();
        $this->prepararRegistros($logs->getCollection());

        $usuarios = User::whereHas('auditoriaLogs')->orderBy('nombre')->orderBy('apellido')->get(['id', 'nombre', 'apellido', 'email']);
        $modulos = AuditoriaLog::whereNotNull('modulo')->distinct()->orderBy('modulo')->pluck('modulo');
        $acciones = AuditoriaLog::whereNotNull('accion')->distinct()->orderBy('accion')->pluck('accion');
        $resultados = AuditoriaLog::whereNotNull('resultado')->distinct()->orderBy('resultado')->pluck('resultado');
        $sla = $seguimiento->resumen();

        return view('auditoria.index', compact('logs', 'resumen', 'usuarios', 'modulos', 'acciones', 'resultados', 'sla'));
    }

    public function export(Request $request, AuditoriaFiltro $filtro)
    {
        abort_unless(auth()->user()->can('auditoria.exportar'), 403);
        $this->validar($request);

        $registros = $filtro->query($request)->latest('fecha_hora')->latest('id')->limit(50000)->get();
        AuditoriaLog::create([
            'usuario_id' => auth()->id(),
            'accion' => 'exportar_auditoria',
            'modulo' => 'Auditoría',
            'entidad' => 'AuditoriaLog',
            'datos_nuevos' => ['filtros' => $request->only(['desde', 'hasta', 'usuario_id', 'modulo', 'accion', 'resultado', 'buscar']), 'registros' => $registros->count()],
            'direccion_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => 'Exportación controlada del historial de auditoría.',
        ]);

        return Excel::download(new AuditoriaExport($registros), 'auditoria_costy_'.now()->format('Ymd_His').'.xlsx');
    }

    private function validar(Request $request): void
    {
        $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'modulo' => ['nullable', 'string', 'max:50'],
            'accion' => ['nullable', 'string', 'max:50'],
            'resultado' => ['nullable', 'string', 'max:20'],
            'buscar' => ['nullable', 'string', 'max:150'],
            'por_pagina' => ['nullable', Rule::in([20, 50, 100, '20', '50', '100'])],
        ]);
    }

    private function prepararRegistros($logs): void
    {
        $casos = CasoOperativo::whereIn('id', $logs->where('entidad', 'CasoOperativo')->pluck('entidad_id'))->pluck('id')->flip();
        $comprobantes = Comprobante::whereIn('id', $logs->whereIn('entidad', ['Comprobante'])->pluck('entidad_id'))->pluck('sesion_id', 'id');
        $documentos = DocumentoIdentidad::whereIn('id', $logs->where('entidad', 'DocumentoIdentidad')->pluck('entidad_id'))->pluck('sesion_id', 'id');

        foreach ($logs as $log) {
            $log->datos_anteriores_seguros = AuditoriaPresentador::textoSeguro($log->datos_anteriores);
            $log->datos_nuevos_seguros = AuditoriaPresentador::textoSeguro($log->datos_nuevos);
            $log->enlace_relacionado = match ($log->entidad) {
                'CasoOperativo' => $casos->has($log->entidad_id) ? route('pendientes.index', ['tipo' => 'casos', 'estado' => 'todos', 'caso_id' => $log->entidad_id]) : null,
                'Comprobante' => $comprobantes->get($log->entidad_id) ? route('interacciones.show', $comprobantes->get($log->entidad_id)) : null,
                'DocumentoIdentidad' => $documentos->get($log->entidad_id) ? route('interacciones.show', $documentos->get($log->entidad_id)) : null,
                'User' => auth()->user()->can('usuarios.ver') && $log->entidad_id ? route('usuarios.index', ['usuario_id' => $log->entidad_id]) : null,
                default => null,
            };
        }
    }
}
