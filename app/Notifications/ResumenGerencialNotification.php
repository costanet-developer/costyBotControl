<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResumenGerencialNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $resumen, private readonly string $etiqueta)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $metricas = $this->resumen['actual']['metricas'];
        $sla = $this->resumen['sla'];

        return (new MailMessage)
            ->subject('[CostyBot] Resumen gerencial '.$this->etiqueta)
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Este es el resumen '.$this->etiqueta.' de CostyBot Control.')
            ->line('Interacciones: '.number_format($metricas['interacciones']).' | Pagos procesados: '.number_format($metricas['pagos']))
            ->line('Valor recibido: $'.number_format($metricas['monto'], 2).' | Créditos: $'.number_format($metricas['creditos'], 2))
            ->line('Casos abiertos: '.number_format($sla['abiertos']).' | Fuera de SLA: '.number_format($sla['vencidos']))
            ->action('Abrir resumen gerencial', route('resumen-gerencial.index', ['periodo' => $this->etiqueta === 'semanal' ? '7_dias' : 'ayer']))
            ->line('El detalle y la exportación están disponibles dentro del backoffice.');
    }
}
