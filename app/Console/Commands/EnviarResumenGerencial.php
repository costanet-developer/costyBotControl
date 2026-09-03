<?php

namespace App\Console\Commands;

use App\Models\AuditoriaLog;
use App\Models\User;
use App\Notifications\ResumenGerencialNotification;
use App\Services\ResumenGerencialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class EnviarResumenGerencial extends Command
{
    protected $signature = 'costy:enviar-resumen-gerencial {tipo=diario : diario o semanal}';

    protected $description = 'Envía el resumen gerencial a los responsables cuando el canal de correo está habilitado';

    public function handle(ResumenGerencialService $service): int
    {
        $tipo = strtolower((string) $this->argument('tipo'));
        if (! in_array($tipo, ['diario', 'semanal'], true)) {
            $this->error('El tipo debe ser diario o semanal.');
            return self::INVALID;
        }
        if (! config('costybot.alertas.email_habilitado')) {
            $this->info('Correo deshabilitado: no se enviaron resúmenes.');
            return self::SUCCESS;
        }

        $clave = 'costy:resumen-gerencial:'.$tipo.':'.now()->format('Y-m-d');
        if (! Cache::add($clave, true, now()->addDays(8))) {
            $this->info('El resumen de este periodo ya fue enviado.');
            return self::SUCCESS;
        }

        try {
            [$inicio, $fin] = $tipo === 'semanal'
                ? [now()->subDays(7)->startOfDay(), now()->subDay()->endOfDay()]
                : [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()];
            $resumen = $service->generar($inicio, $fin);
            $destinatarios = User::role(['administrador', 'superadministrador'])->where('activo', true)->where('bloqueado', false)->get();
            Notification::send($destinatarios, new ResumenGerencialNotification($resumen, $tipo));
            AuditoriaLog::create([
                'accion' => 'enviar_resumen_gerencial', 'modulo' => 'Resumen gerencial', 'entidad' => 'Reporte',
                'datos_nuevos' => ['tipo' => $tipo, 'destinatarios' => $destinatarios->pluck('id')->all()],
                'resultado' => 'exitoso', 'descripcion' => 'Resumen gerencial enviado automáticamente.',
            ]);
            $this->info('Resumen enviado a '.$destinatarios->count().' destinatarios.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Cache::forget($clave);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
