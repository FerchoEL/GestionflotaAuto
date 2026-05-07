<?php

namespace App\Observers;

use App\Models\AlertaRendimiento;
use App\Models\User;
use App\Notifications\AlertaRendimientoMailNotification;
use App\Support\EmailGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AlertaRendimientoObserver
{
    public function created(AlertaRendimiento $alertaRendimiento): void
    {
        if (($alertaRendimiento->estatus ?? 'Abierta') !== 'Abierta') {
            return;
        }

        DB::afterCommit(function () use ($alertaRendimiento) {

            $notification = new AlertaRendimientoMailNotification($alertaRendimiento);
            $usuariosNotificados = collect();

            // 1) Responsable directo
            $responsable = $alertaRendimiento->responsable;
            if ($responsable && EmailGuard::canSend($responsable->email)) {
                $this->safeNotify($responsable, $notification, 'responsable', $alertaRendimiento->id, $usuariosNotificados);
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

                    $this->safeNotify($u, $notification, 'activos', $alertaRendimiento->id, $usuariosNotificados);
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

                    $this->safeNotify($u, $notification, 'admin', $alertaRendimiento->id, $usuariosNotificados);
                });
        });
    }

    private function safeNotify(
        User $user,
        AlertaRendimientoMailNotification $notification,
        string $grupo,
        int $alertaId,
        Collection $usuariosNotificados
    ): void
    {
        $email = mb_strtolower(trim((string) $user->email));

        if ($usuariosNotificados->contains(fn (array $destinatario) => $destinatario['user_id'] === $user->id || $destinatario['email'] === $email)) {
            Log::info('AlertaRendimiento: notificacion duplicada omitida', [
                'alerta_id' => $alertaId,
                'grupo' => $grupo,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return;
        }

        try {
            $user->notify($notification);
            $usuariosNotificados->push([
                'user_id' => $user->id,
                'email' => $email,
            ]);
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
