<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\CasoOperativo;
use App\Models\SaldoFavor;
use App\Models\Sesion;
use App\Models\ValidacionIdentidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PendienteController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('interacciones.ver'), 403);

        $request->validate([
            'tipo' => ['nullable', Rule::in(['casos', 'sin_evidencia', 'auditoria_pendiente', 'en_revision', 'creditos', 'kyc'])],
            'estado' => ['nullable', Rule::in(['abiertos', 'pendiente', 'en_revision', 'resuelto', 'descartado', 'todos'])],
            'caso_id' => ['nullable', 'integer', 'exists:casos_operativos,id'],
        ]);
        $tipo = $request->input('tipo', 'sin_evidencia');

        $conteos = [
            'casos' => CasoOperativo::whereIn('estado', ['pendiente', 'en_revision'])->count(),
            'sin_evidencia' => Sesion::pagoProcesado()->sinComprobanteRelacionado()->count(),
            'auditoria_pendiente' => Comprobante::where('estado_auditoria', 'PENDIENTE')->count(),
            'en_revision' => Comprobante::where('estado_auditoria', 'EN_REVISION')->count(),
            'creditos' => SaldoFavor::where('estado', 'pendiente')->count(),
            'kyc' => ValidacionIdentidad::where('derivado_revision', true)->count(),
        ];

        $registros = match ($tipo) {
            'casos' => CasoOperativo::with(['sesion.cliente', 'asignadoA', 'resueltoPor'])
                ->when($request->filled('caso_id'), fn ($query) => $query->whereKey($request->integer('caso_id')))
                ->when($request->input('estado', 'abiertos') === 'abiertos', fn ($query) => $query->whereIn('estado', ['pendiente', 'en_revision']))
                ->when(in_array($request->input('estado'), ['pendiente', 'en_revision', 'resuelto', 'descartado'], true), fn ($query) => $query->where('estado', $request->input('estado')))
                ->orderByRaw("CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END")
                ->orderBy('detectado_en')
                ->paginate(20),
            'auditoria_pendiente' => Comprobante::with('sesion.cliente')
                ->where('estado_auditoria', 'PENDIENTE')->latest('id')->paginate(20),
            'en_revision' => Comprobante::with('sesion.cliente')
                ->where('estado_auditoria', 'EN_REVISION')->latest('id')->paginate(20),
            'creditos' => SaldoFavor::with(['sesion.cliente', 'comprobante'])
                ->where('estado', 'pendiente')->orderByDesc('fecha_registro')->orderByDesc('id')->paginate(20),
            'kyc' => ValidacionIdentidad::with('sesion.cliente')
                ->where('derivado_revision', true)->orderByDesc('actualizado_en')->orderByDesc('id')->paginate(20),
            default => Sesion::with('cliente')->pagoProcesado()->sinComprobanteRelacionado()
                ->latest('inicio')->paginate(20),
        };
        $registros->withQueryString();

        return view('pendientes.index', compact('tipo', 'conteos', 'registros'));
    }
}
