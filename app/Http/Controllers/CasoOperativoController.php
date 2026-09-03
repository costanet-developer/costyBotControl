<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\CasoOperativo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CasoOperativoController extends Controller
{
    public function tomar(CasoOperativo $caso)
    {
        $this->autorizar();

        DB::transaction(function () use ($caso) {
            $bloqueado = CasoOperativo::lockForUpdate()->findOrFail($caso->id);
            abort_if(in_array($bloqueado->estado, ['resuelto', 'descartado'], true), 422, 'El caso ya está cerrado.');

            $anteriores = $bloqueado->only(['estado', 'asignado_a', 'asignado_en']);
            $bloqueado->update([
                'estado' => 'en_revision',
                'asignado_a' => auth()->id(),
                'asignado_en' => now(),
            ]);
            $this->auditar($bloqueado, 'tomar_caso_operativo', $anteriores, $bloqueado->only(['estado', 'asignado_a', 'asignado_en']));
        });

        return back()->with('success', 'Caso asignado correctamente.');
    }

    public function cerrar(Request $request, CasoOperativo $caso)
    {
        $this->autorizar();
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['resuelto', 'descartado'])],
            'resolucion' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($caso, $datos) {
            $bloqueado = CasoOperativo::lockForUpdate()->findOrFail($caso->id);
            abort_if(in_array($bloqueado->estado, ['resuelto', 'descartado'], true), 422, 'El caso ya está cerrado.');

            $anteriores = $bloqueado->only(['estado', 'asignado_a', 'resuelto_por', 'resuelto_en', 'resolucion']);
            $bloqueado->update([
                'estado' => $datos['estado'],
                'asignado_a' => $bloqueado->asignado_a ?: auth()->id(),
                'asignado_en' => $bloqueado->asignado_en ?: now(),
                'resuelto_por' => auth()->id(),
                'resuelto_en' => now(),
                'resolucion' => trim($datos['resolucion']),
            ]);
            $this->auditar($bloqueado, 'cerrar_caso_operativo', $anteriores, $bloqueado->only(['estado', 'asignado_a', 'resuelto_por', 'resuelto_en', 'resolucion']));
        });

        return back()->with('success', $datos['estado'] === 'resuelto' ? 'Caso resuelto correctamente.' : 'Caso descartado con justificación.');
    }

    public function reabrir(CasoOperativo $caso)
    {
        $this->autorizar();

        DB::transaction(function () use ($caso) {
            $bloqueado = CasoOperativo::lockForUpdate()->findOrFail($caso->id);
            abort_unless(in_array($bloqueado->estado, ['resuelto', 'descartado'], true), 422, 'El caso todavía está abierto.');

            $anteriores = $bloqueado->only(['estado', 'asignado_a', 'resuelto_por', 'resuelto_en', 'resolucion']);
            $bloqueado->update([
                'estado' => 'en_revision',
                'asignado_a' => auth()->id(),
                'asignado_en' => now(),
                'resuelto_por' => null,
                'resuelto_en' => null,
                'resolucion' => null,
            ]);
            $this->auditar($bloqueado, 'reabrir_caso_operativo', $anteriores, $bloqueado->only(['estado', 'asignado_a', 'resuelto_por', 'resuelto_en', 'resolucion']));
        });

        return back()->with('success', 'Caso reabierto y asignado correctamente.');
    }

    private function autorizar(): void
    {
        abort_unless(auth()->user()->can('casos_operativos.gestionar'), 403);
    }

    private function auditar(CasoOperativo $caso, string $accion, array $anteriores, array $nuevos): void
    {
        AuditoriaLog::create([
            'usuario_id' => auth()->id(),
            'accion' => $accion,
            'modulo' => 'Casos operativos',
            'entidad' => 'CasoOperativo',
            'entidad_id' => $caso->id,
            'datos_anteriores' => $anteriores,
            'datos_nuevos' => $nuevos,
            'direccion_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => "Gestión del caso operativo {$caso->clave}.",
        ]);
    }
}
