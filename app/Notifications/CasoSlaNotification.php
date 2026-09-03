<?php

namespace App\Notifications;

use App\Models\AlertaOperativa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CasoSlaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AlertaOperativa $alerta,
        private readonly bool $usarDatabase = true,
        private readonly bool $usarMail = false,
    ) {
        $this->alerta->loadMissing('caso');
    }

    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            $this->usarDatabase ? 'database' : null,
            $this->usarMail ? 'mail' : null,
        ]));
    }

    public function toArray(object $notifiable): array
    {
        $caso = $this->alerta->caso;

        return [
            'alerta_id' => $this->alerta->id,
            'caso_id' => $caso->id,
            'tipo' => $this->alerta->tipo,
            'nivel' => $this->alerta->nivel,
            'titulo' => $this->titulo(),
            'mensaje' => $this->mensaje(),
            'url' => route('pendientes.index', ['tipo' => 'casos', 'estado' => 'todos', 'caso_id' => $caso->id]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[CostyBot] '.$this->titulo())
            ->greeting('Hola '.$notifiable->name.',')
            ->line($this->mensaje())
            ->line('Prioridad: '.strtoupper($this->alerta->caso->prioridad))
            ->action('Revisar caso', route('pendientes.index', ['tipo' => 'casos', 'estado' => 'todos', 'caso_id' => $this->alerta->caso->id]))
            ->line('Este aviso fue generado automáticamente por CostyBot Control.');
    }

    private function titulo(): string
    {
        return match ($this->alerta->tipo) {
            'nuevo_alta' => 'Nuevo caso de prioridad alta',
            'por_vencer' => 'Caso próximo a vencer su SLA',
            'sla_vencido' => 'Caso fuera de SLA',
            'escalado' => 'Caso escalado por demora',
            default => 'Alerta operativa',
        };
    }

    private function mensaje(): string
    {
        $caso = $this->alerta->caso;

        return "{$caso->titulo} (caso #{$caso->id}) requiere atención. Estado actual: ".str_replace('_', ' ', $caso->estado).'.';
    }
}
