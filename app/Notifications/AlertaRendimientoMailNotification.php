<?php

namespace App\Notifications;

use App\Models\AlertaRendimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertaRendimientoMailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    
    

    public function __construct(public AlertaRendimiento $alerta) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->alerta;

        $placas = $a->vehiculo?->placas ?? 'N/A';

        return (new MailMessage)
            ->subject("🚨 Alerta de Rendimiento - {$placas}")
            ->greeting("Hola {$notifiable->name}")
            ->line("Se detectó una desviación de rendimiento en un vehículo.")
            ->line("Vehículo: {$placas}")
            ->line("Rendimiento detectado: {$a->rendimiento_detectado} km/L")
            ->line("Rendimiento óptimo: {$a->rendimiento_optimo} km/L")
            ->line("Umbral aplicado: {$a->umbral_aplicado} km/L")
            ->line("Estatus: {$a->estatus}")
            ->line("Fecha alerta: {$a->fecha_alerta}")
            ->line("Carga relacionada ID: {$a->carga_id}")
            ->line("Revisar en el sistema para auditoría/cierre.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
