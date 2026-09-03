<?php

namespace App\Http\Controllers;

use App\Actions\CambiarEstadoComprobante;
use App\Enums\EstadoAuditoria;
use App\Models\Comprobante;
use App\Models\ObservacionInteraccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComprobanteController extends Controller
{
    public function cambiarEstado(Request $request, Comprobante $comprobante)
    {
        $request->validate([
            'estado' => ['required', 'string'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        $nuevoEstado = EstadoAuditoria::tryFrom($request->estado);
        if (!$nuevoEstado) {
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => 'Estado inválido.'], 422)
                : back()->withErrors(['estado' => 'Estado inválido.']);
        }

        try {
            $action = app(CambiarEstadoComprobante::class);
            $action->execute($comprobante, $nuevoEstado, $request->observacion);
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => $e->getMessage()], 422)
                : back()->withErrors(['error' => $e->getMessage()]);
        }

        // Si viene con observación, la guardamos también en la tabla de observaciones
        if ($request->filled('observacion')) {
            ObservacionInteraccion::create([
                'comprobante_id' => $comprobante->id,
                'sesion_id' => $comprobante->sesion_id,
                'usuario_id' => Auth::id(),
                'observacion' => $request->observacion,
            ]);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'estado' => $nuevoEstado->label()]);
        }

        return back()->with('success', "Comprobante actualizado a {$nuevoEstado->label()}.");
    }
}
