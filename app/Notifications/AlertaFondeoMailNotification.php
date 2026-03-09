<?php

namespace App\Notifications;

use App\Models\AlertaFondeo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertaFondeoMailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    
    

    public function __construct(public AlertaFondeo $alerta) {}


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
        $fondeoId = $a->fondeo_id ?? 'N/A';

        return (new MailMessage)
            ->subject("⚠️ Alerta de Fondeo - {$placas}")
            ->greeting("Hola {$notifiable->name}")
            ->line("Se generó una alerta relacionada con fondeo.")
            ->line("Vehículo: {$placas}")
            ->line("Tipo: {$a->tipo}")
            ->line("Descripción: " . ($a->descripcion ?? 'N/A'))
            ->line("Fondeo ID: {$fondeoId}")
            ->line("Fecha: {$a->created_at}")
            ->line("Revisar en el sistema.");
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
