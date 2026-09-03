<?php

namespace App\Http\Controllers;

use App\Models\CasoOperativo;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $request->user()->notifications()->latest()->paginate(30);

        return view('notificaciones.index', compact('notificaciones'));
    }

    public function abrir(Request $request, string $notificacion)
    {
        $registro = $request->user()->notifications()->findOrFail($notificacion);
        $registro->markAsRead();
        $casoId = filter_var($registro->data['caso_id'] ?? null, FILTER_VALIDATE_INT);

        if ($casoId && CasoOperativo::whereKey($casoId)->exists() && $request->user()->can('interacciones.ver')) {
            return redirect()->route('pendientes.index', ['tipo' => 'casos', 'estado' => 'todos', 'caso_id' => $casoId]);
        }

        return redirect()->route('notificaciones.index');
    }

    public function marcarTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
