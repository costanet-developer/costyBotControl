<?php

namespace App\Services;

use App\Models\AlertaOperativa;
use App\Models\AuditoriaLog;
use App\Models\CasoOperativo;
use App\Models\User;
use App\Notifications\CasoSlaNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ProcesarAlertasOperativas
{
    public function procesar(): array
    {
        $resultado = ['nuevas' => 0, 'notificaciones' => 0, 'emails' => 0, 'errores' => 0, 'por_tipo' => []];
        $casos = CasoOperativo::with('asignadoA')->whereIn('estado', ['pendiente', 'en_revision'])->get();

        foreach ($casos as $caso) {
            $tipo = $this->tipoAplicable($caso);
            if (! $tipo) {
                continue;
            }

            $alerta = AlertaOperativa::firstOrCreate(
                ['clave' => "caso:{$caso->id}:{$tipo}"],
                [
                    'caso_operativo_id' => $caso->id,
                    'tipo' => $tipo,
                    'nivel' => $this->nivel($tipo, $caso),
                    'estado' => 'pendiente',
                    'estado_email' => config('costybot.alertas.email_habilitado') ? 'pendiente' : 'deshabilitado',
                ]
            );

            $destinatarios = $this->destinatarios($caso, $tipo);
            if ($alerta->wasRecentlyCreated) {
                $this->notificarBase($alerta, $destinatarios, $resultado);
                $resultado['nuevas']++;
                $resultado['por_tipo'][$tipo] = ($resultado['por_tipo'][$tipo] ?? 0) + 1;
            }

            if (config('costybot.alertas.email_habilitado') && ! $alerta->email_enviado_en) {
                $this->notificarEmail($alerta, $destinatarios, $resultado);
            }
        }

        ksort($resultado['por_tipo']);

        return $resultado;
    }

    private function tipoAplicable(CasoOperativo $caso): ?string
    {
        if (! $caso->detectado_en) {
            return null;
        }

        $limiteHoras = max((int) config("costybot.sla_casos_horas.{$caso->prioridad}", 8), 1);
        $venceEn = $caso->detectado_en->copy()->addHours($limiteHoras);
        $escalarHoras = max((int) config('costybot.alertas.escalar_despues_horas', 24), 1);

        if (now()->greaterThanOrEqualTo($venceEn->copy()->addHours($escalarHoras))) {
            return 'escalado';
        }
        if (now()->greaterThanOrEqualTo($venceEn)) {
            return 'sla_vencido';
        }

        $transcurrido = $caso->detectado_en->diffInMinutes(now());
        $porcentaje = ($transcurrido / ($limiteHoras * 60)) * 100;
        if ($porcentaje >= (int) config('costybot.alertas.porcentaje_aviso', 80)) {
            return 'por_vencer';
        }

        return $caso->prioridad === 'alta' ? 'nuevo_alta' : null;
    }

    private function destinatarios(CasoOperativo $caso, string $tipo): Collection
    {
        $usuarios = collect();
        if ($caso->asignadoA?->activo) {
            $usuarios->push($caso->asignadoA);
        } else {
            $usuarios = $usuarios->merge(User::role('contabilidad')->where('activo', true)->where('bloqueado', false)->get());
        }

        if (in_array($tipo, ['sla_vencido', 'escalado'], true)) {
            $usuarios = $usuarios->merge(User::role(['administrador', 'superadministrador'])->where('activo', true)->where('bloqueado', false)->get());
        }

        if ($usuarios->isEmpty()) {
            $usuarios = User::role('superadministrador')->where('activo', true)->where('bloqueado', false)->get();
        }

        return $usuarios->unique('id')->values();
    }

    private function notificarBase(AlertaOperativa $alerta, Collection $destinatarios, array &$resultado): void
    {
        try {
            Notification::send($destinatarios, new CasoSlaNotification($alerta, true, false));
            $alerta->update([
                'estado' => 'notificada',
                'destinatarios' => $destinatarios->map(fn (User $usuario) => ['id' => $usuario->id, 'email' => $usuario->email])->all(),
                'notificada_en' => now(),
                'ultimo_error' => null,
            ]);
            $resultado['notificaciones'] += $destinatarios->count();
            AuditoriaLog::create([
                'accion' => 'generar_alerta_operativa',
                'modulo' => 'Alertas',
                'entidad' => 'CasoOperativo',
                'entidad_id' => $alerta->caso_operativo_id,
                'datos_nuevos' => ['tipo' => $alerta->tipo, 'nivel' => $alerta->nivel, 'destinatarios' => $destinatarios->pluck('id')->all()],
                'resultado' => 'exitoso',
                'descripcion' => 'Alerta interna generada automáticamente por cumplimiento de SLA.',
            ]);
        } catch (\Throwable $e) {
            $alerta->update(['estado' => 'error', 'ultimo_error' => mb_substr($e->getMessage(), 0, 1000)]);
            $resultado['errores']++;
        }
    }

    private function notificarEmail(AlertaOperativa $alerta, Collection $destinatarios, array &$resultado): void
    {
        try {
            $conCorreo = $destinatarios->filter(fn (User $usuario) => filter_var($usuario->email, FILTER_VALIDATE_EMAIL));
            Notification::send($conCorreo, new CasoSlaNotification($alerta, false, true));
            $alerta->update(['estado_email' => 'enviado', 'email_enviado_en' => now(), 'ultimo_error' => null]);
            $resultado['emails'] += $conCorreo->count();
        } catch (\Throwable $e) {
            $alerta->update(['estado_email' => 'error', 'ultimo_error' => mb_substr($e->getMessage(), 0, 1000)]);
            $resultado['errores']++;
        }
    }

    private function nivel(string $tipo, CasoOperativo $caso): string
    {
        return match ($tipo) {
            'escalado' => 'critica',
            'sla_vencido' => 'alta',
            'por_vencer' => 'media',
            default => $caso->prioridad,
        };
    }
}
