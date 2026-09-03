<?php

namespace App\Observers;

use App\Models\AuditoriaLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditoriaObserver
{
    public function created(Model $model): void
    {
        $this->log('create', $model);
    }

    public function updated(Model $model): void
    {
        $this->log('update', $model);
    }

    public function deleted(Model $model): void
    {
        $this->log('delete', $model);
    }

    public function restored(Model $model): void
    {
        $this->log('restore', $model);
    }

    private function log(string $accion, Model $model): void
    {
        $modulosAuditables = ['Comprobante', 'User', 'ObservacionInteraccion'];

        if (!in_array(class_basename($model), $modulosAuditables)) {
            return;
        }

        if ($accion === 'update') {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            $dirty = array_filter($changes, fn($value, $key) =>
                array_key_exists($key, $original) && $original[$key] !== $value,
                ARRAY_FILTER_USE_BOTH
            );

            if (empty($dirty)) {
                return;
            }

            $datosAnteriores = array_intersect_key($original, $dirty);
            $datosNuevos = $dirty;
        } elseif ($accion === 'create') {
            $datosAnteriores = null;
            $datosNuevos = $model->toArray();
        } else {
            $datosAnteriores = $model->toArray();
            $datosNuevos = null;
        }

        AuditoriaLog::create([
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'modulo' => class_basename($model),
            'entidad' => class_basename($model),
            'entidad_id' => $model->id,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'direccion_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resultado' => 'exitoso',
        ]);
    }
}
