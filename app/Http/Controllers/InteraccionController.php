<?php

namespace App\Http\Controllers;

use App\Models\Sesion;
use App\Support\InteraccionPresentador;
use Illuminate\Http\Request;

class InteraccionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('interacciones.ver'), 403);

        $query = Sesion::with('cliente', 'comprobantes', 'comprobantePrincipal', 'ultimaValidacionIdentidad')
            ->withExists([
                'eventos as pago_exitoso_por_evento' => fn ($eventos) => $eventos->where('paso', 'reactivacion_exitosa'),
                'comprobantes as comprobante_directo_existe',
                'comprobantes as comprobante_exitoso_existe' => fn ($comprobantes) => $comprobantes->where('estado', 'reactivacion_exitosa'),
            ])
            ->withCount('documentosIdentidad')
            ->withSum('saldosFavor as credito_total', 'excedente');

        if ($request->filled('bot')) {
            $query->where('bot', $request->bot);
        }

        if ($request->filled('resultado')) {
            $query->where('resultado', $request->resultado);
        }

        if ($request->filled('intencion')) {
            $query->where('intencion', $request->intencion);
        }

        match ($request->input('pago')) {
            'procesado' => $query->pagoProcesado(),
            'procesado_sin_comprobante' => $query->pagoProcesado()->sinComprobanteRelacionado(),
            'recibido_no_procesado' => $query->recibidoSinProcesar(),
            'sin_comprobante' => $query->sinPagoNiComprobante(),
            default => null,
        };

        if ($request->filled('desde')) {
            $query->whereDate('inicio', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('inicio', '<=', $request->input('hasta'));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('sesion_id', 'ilike', "%{$s}%")
                    ->orWhere('cedula', 'ilike', "%{$s}%")
                    ->orWhere('numero_whatsapp', 'ilike', "%{$s}%")
                    ->orWhereHas('cliente', function ($cq) use ($s) {
                        $cq->where('nombre', 'ilike', "%{$s}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where(function ($query) use ($request) {
                $query->whereHas('comprobantes', function ($comprobantes) use ($request) {
                    $comprobantes->where('estado_auditoria', $request->estado);
                })->orWhereHas('comprobantePrincipal', function ($comprobante) use ($request) {
                    $comprobante->where('estado_auditoria', $request->estado);
                });
            });
        }

        $porPagina = in_array((int) $request->input('por_pagina'), [15, 30, 50], true)
            ? (int) $request->input('por_pagina')
            : 15;
        $sesiones = $query->latest('inicio')->paginate($porPagina)->withQueryString();
        $sesiones->getCollection()->each(fn (Sesion $sesion) => $this->unirComprobantesRelacionados($sesion));

        $resumenPagos = [
            'total' => Sesion::count(),
            'procesados' => Sesion::pagoProcesado()->count(),
            'procesados_sin_comprobante' => Sesion::pagoProcesado()->sinComprobanteRelacionado()->count(),
            'recibidos' => Sesion::recibidoSinProcesar()->count(),
        ];

        $bots = Sesion::select('bot')->distinct()->pluck('bot');
        $resultados = Sesion::select('resultado')->distinct()->whereNotNull('resultado')->pluck('resultado');
        $intenciones = Sesion::select('intencion')->distinct()->whereNotNull('intencion')->pluck('intencion');

        return view('interacciones.index', compact('sesiones', 'bots', 'resultados', 'intenciones', 'resumenPagos'));
    }

    public function show($sesionId)
    {
        abort_unless(auth()->user()->can('interacciones.ver'), 403);

        $sesion = $this->cargarSesion($sesionId);
        $presentacion = InteraccionPresentador::construir($sesion);

        return view('interacciones.show', compact('sesion', 'presentacion'));
    }

    public function detalle($sesionId)
    {
        abort_unless(auth()->user()->can('interacciones.ver'), 403);

        $sesion = $this->cargarSesion($sesionId);
        $presentacion = InteraccionPresentador::construir($sesion);

        return view('interacciones.partials.detalle', compact('sesion', 'presentacion'));
    }

    private function cargarSesion($sesionId)
    {
        $sesion = Sesion::with([
            'cliente',
            'eventos' => fn ($q) => $q->orderBy('fecha_evento'),
            'comprobantes' => fn ($q) => $q->with('observaciones.usuario', 'revisiones.usuario'),
            'documentosIdentidad' => fn ($q) => $q->orderBy('fecha_hora')->orderBy('id'),
            'ultimaValidacionIdentidad',
            'otpVerificaciones' => fn ($q) => $q->orderBy('creado_en'),
            'saldosFavor' => fn ($q) => $q->orderBy('fecha_registro'),
            'comprobantePrincipal' => fn ($q) => $q->with('observaciones.usuario', 'revisiones.usuario'),
        ])->where('sesion_id', $sesionId)->firstOrFail();

        return $this->unirComprobantesRelacionados($sesion);
    }

    private function unirComprobantesRelacionados(Sesion $sesion): Sesion
    {
        $comprobantes = $sesion->comprobantes;

        if ($sesion->comprobantePrincipal && ! $comprobantes->contains('id', $sesion->comprobantePrincipal->id)) {
            $comprobantes->push($sesion->comprobantePrincipal);
        }

        $sesion->setRelation('comprobantes', $comprobantes->unique('id')->sortByDesc('id')->values());

        return $sesion;
    }
}
