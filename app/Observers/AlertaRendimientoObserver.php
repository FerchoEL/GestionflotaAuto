<?php

namespace App\Observers;

use App\Models\AlertaRendimiento;
use App\Models\User;
use App\Notifications\AlertaRendimientoMailNotification;
use App\Support\EmailGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertaRendimientoObserver
{
    public function created(AlertaRendimiento $alertaRendimiento): void
    {
        if (($alertaRendimiento->estatus ?? 'Abierta') !== 'Abierta') {
            return;
        }

        DB::afterCommit(function () use ($alertaRendimiento) {

            $notification = new AlertaRendimientoMailNotification($alertaRendimiento);

            // 1) Responsable directo
            $responsable = $alertaRendimiento->responsable;
            if ($responsable && EmailGuard::canSend($responsable->email)) {
                $this->safeNotify($responsable, $notification, 'responsable', $alertaRendimiento->id);
            } else {
                Log::warning('AlertaRendimiento: email bloqueado/ inválido (responsable)', [
                    'alerta_id' => $alertaRendimiento->id,
                    'email' => $responsable?->email,
                ]);
            }

            // 2) Rol activos
            User::role('activos')->whereNotNull('email')->get()
                ->each(function ($u) use ($notification, $alertaRendimiento) {
                    if (! EmailGuard::canSend($u->email)) {
                        Log::warning('AlertaRendimiento: email bloqueado/ inválido (activos)', [
                            'alerta_id' => $alertaRendimiento->id,
                            'user_id' => $u->id,
                            'email' => $u->email,
                        ]);
                        return;
                    }

                    $this->safeNotify($u, $notification, 'activos', $alertaRendimiento->id);
                });

            // 3) Rol admin
            User::role('admin')->whereNotNull('email')->get()
                ->each(function ($u) use ($notification, $alertaRendimiento) {
                    if (! EmailGuard::canSend($u->email)) {
                        Log::warning('AlertaRendimiento: email bloqueado/ inválido (admin)', [
                            'alerta_id' => $alertaRendimiento->id,
                            'user_id' => $u->id,
                            'email' => $u->email,
                        ]);
                        return;
                    }

                    $this->safeNotify($u, $notification, 'admin', $alertaRendimiento->id);
                });
        });
    }

    private function safeNotify(User $user, AlertaRendimientoMailNotification $notification, string $grupo, int $alertaId): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::error('AlertaRendimiento: fallo al notificar usuario', [
                'alerta_id' => $alertaId,
                'grupo' => $grupo,
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}