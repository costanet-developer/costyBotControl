<?php

namespace App\Services;

use App\Models\AuditoriaLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditoriaFiltro
{
    public function query(Request $request): Builder
    {
        $query = AuditoriaLog::query()->with('usuario');

        if ($request->filled('desde')) {
            $query->whereDate('fecha_hora', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->input('hasta'));
        }
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->integer('usuario_id'));
        }
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->input('modulo'));
        }
        if ($request->filled('accion')) {
            $query->where('accion', $request->input('accion'));
        }
        if ($request->filled('resultado')) {
            $query->where('resultado', $request->input('resultado'));
        }
        if ($request->filled('buscar')) {
            $buscar = mb_strtolower(trim((string) $request->input('buscar')));
            $like = "%{$buscar}%";
            $query->where(function (Builder $query) use ($like) {
                $query->whereRaw('LOWER(COALESCE(descripcion, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(entidad, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(accion, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(modulo, ?)) LIKE ?', ['', $like])
                    ->orWhereHas('usuario', function (Builder $usuario) use ($like) {
                        $usuario->whereRaw("LOWER(COALESCE(nombre, '') || ' ' || COALESCE(apellido, '')) LIKE ?", [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                    });
            });
        }

        return $query;
    }
}
