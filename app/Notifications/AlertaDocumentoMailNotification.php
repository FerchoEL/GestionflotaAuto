<?php

namespace App\Notifications;

use App\Models\AlertaDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertaDocumentoMailNotification extends Notification
{
    use Queueable;

    public function __construct(public AlertaDocumento $alerta) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $alerta = $this->alerta;
        $tipo = match ($alerta->tipo) {
            'vencido' => 'Documento vencido',
            'por_vencer' => 'Documento por vencer',
            default => 'Alerta de documento',
        };

        return (new MailMessage)
            ->subject("📄 {$tipo} - " . ($alerta->vehiculo?->placas ?? 'N/A'))
            ->greeting("Hola {$notifiable->name}")
            ->line("Se detectó una alerta documental en un vehículo.")
            ->line("Tipo de alerta: {$tipo}")
            ->line("Vehículo: " . ($alerta->vehiculo?->display_name ?? 'N/A'))
            ->line("Documento: " . ($alerta->tipoDocumento?->nombre ?? 'N/A'))
            ->line("Descripción: " . ($alerta->descripcion ?? 'N/A'))
            ->line("Fecha alerta: {$alerta->fecha_alerta}")
            ->line("Revisar en el sistema para atención y cierre.");
    }
}
