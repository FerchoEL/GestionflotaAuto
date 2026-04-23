<?php

namespace App\Observers;

use App\Models\AlertaDocumento;
use App\Models\User;
use App\Notifications\AlertaDocumentoMailNotification;
use App\Support\EmailGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertaDocumentoObserver
{
    public function created(AlertaDocumento $alertaDocumento): void
    {
        if (($alertaDocumento->estatus ?? 'Abierta') !== 'Abierta') {
            return;
        }

        DB::afterCommit(function () use ($alertaDocumento) {
            $notification = new AlertaDocumentoMailNotification($alertaDocumento);

            $responsable = $alertaDocumento->responsable;
            if ($responsable && EmailGuard::canSend($responsable->email)) {
                $this->safeNotify($responsable, $notification, 'responsable', $alertaDocumento->id);
            } else {
                Log::warning('AlertaDocumento: email bloqueado/inválido (responsable)', [
                    'alerta_id' => $alertaDocumento->id,
                    'email' => $responsable?->email,
                ]);
            }

            User::role('activos')->whereNotNull('email')->get()
                ->each(function (User $user) use ($notification, $alertaDocumento) {
                    if (! EmailGuard::canSend($user->email)) {
                        return;
                    }

                    $this->safeNotify($user, $notification, 'activos', $alertaDocumento->id);
                });

            User::role('admin')->whereNotNull('email')->get()
                ->each(function (User $user) use ($notification, $alertaDocumento) {
                    if (! EmailGuard::canSend($user->email)) {
                        return;
                    }

                    $this->safeNotify($user, $notification, 'admin', $alertaDocumento->id);
                });
        });
    }

    private function safeNotify(User $user, AlertaDocumentoMailNotification $notification, string $grupo, int $alertaId): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::error('AlertaDocumento: fallo al notificar usuario', [
                'alerta_id' => $alertaId,
                'grupo' => $grupo,
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
