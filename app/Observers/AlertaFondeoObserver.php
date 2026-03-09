<?php

namespace App\Observers;

use App\Models\AlertaFondeo;
use App\Models\User;
use App\Notifications\AlertaFondeoMailNotification;
use App\Support\EmailGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertaFondeoObserver
{
    public function created(AlertaFondeo $alertaFondeo): void
    {
        DB::afterCommit(function () use ($alertaFondeo) {

            $notification = new AlertaFondeoMailNotification($alertaFondeo);

            // 1) Creador del fondeo
            $creador = $alertaFondeo->fondeo?->usuario;
            if ($creador && EmailGuard::canSend($creador->email)) {
                $this->safeNotify($creador, $notification, 'creador_fondeo', $alertaFondeo->id);
            } else {
                Log::warning('AlertaFondeo: email bloqueado/ inválido (creador)', [
                    'alerta_id' => $alertaFondeo->id,
                    'email' => $creador?->email,
                ]);
            }

            // 2) Admins
            User::role('admin')->whereNotNull('email')->get()
                ->each(function ($u) use ($notification, $alertaFondeo) {
                    if (! EmailGuard::canSend($u->email)) {
                        Log::warning('AlertaFondeo: email bloqueado/ inválido (admin)', [
                            'alerta_id' => $alertaFondeo->id,
                            'user_id' => $u->id,
                            'email' => $u->email,
                        ]);
                        return;
                    }

                    $this->safeNotify($u, $notification, 'admin', $alertaFondeo->id);
                });
        });
    }

    private function safeNotify(User $user, AlertaFondeoMailNotification $notification, string $grupo, int $alertaId): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::error('AlertaFondeo: fallo al notificar usuario', [
                'alerta_id' => $alertaId,
                'grupo' => $grupo,
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}