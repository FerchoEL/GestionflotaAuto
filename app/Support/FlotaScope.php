<?php

namespace App\Support;

use App\Models\Vehiculo;
use App\Models\User;
use App\Models\ResponsableAuxiliar;
use Illuminate\Database\Eloquent\Builder;

class FlotaScope
{
    public static function vehiculosUsuario(?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (!$user) {
            return Vehiculo::query()->whereRaw('1=0');
        }

        // Admin / activos / fondeo ven todo
        if ($user->hasAnyRole(['admin','activos','fondeo'])) {
            return Vehiculo::query()->where('activo', true);
        }

        $query = Vehiculo::query();

        $query->where(function (Builder $subQuery) use ($user): void {
            if ($user->hasAnyRole(['chofer', 'responsable'])) {
                // Ambos perfiles pueden tener asignaciones como chofer y responsable.
                $subQuery->whereHas('choferes', function (Builder $q) use ($user): void {
                    $q->where('chofer_user_id', $user->id)
                        ->where('activo', true);
                })->orWhereHas('responsableActivo', function (Builder $q) use ($user): void {
                    $q->where('responsable_user_id', $user->id);
                });
            } else {
                $subQuery->whereRaw('1=0');
            }

            $subQuery->orWhereHas('responsableActivo', function (Builder $q) use ($user): void {
                $q->whereIn(
                    'responsable_user_id',
                    ResponsableAuxiliar::query()
                        ->where('auxiliar_user_id', $user->id)
                        ->where('activo', true)
                        ->select('responsable_user_id')
                );
            });
        });

        return $query;
    }

    public static function idsVehiculosUsuario()
    {
        return self::vehiculosUsuario()->pluck('id');
    }
}
