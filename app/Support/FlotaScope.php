<?php

namespace App\Support;

use App\Models\Vehiculo;

class FlotaScope
{
    public static function vehiculosUsuario()
    {
        $user = auth()->user();

        if (!$user) {
            return Vehiculo::query()->whereRaw('1=0');
        }

        // Admin / activos / fondeo ven todo
        if ($user->hasAnyRole(['admin','activos','fondeo'])) {
            return Vehiculo::query()->where('activo', true);
        }

        // Chofer → solo vehículos asignados
        if ($user->hasRole('chofer')) {
            return Vehiculo::whereHas('choferes', function ($q) use ($user) {
                $q->where('chofer_user_id', $user->id)
                  ->where('activo', true);
            });
        }

        // Responsable → vehículos que supervisa
        if ($user->hasRole('responsable')) {
            return Vehiculo::whereHas('responsableActivo', function ($q) use ($user) {
                $q->where('responsable_user_id', $user->id);
            });
        }

        return Vehiculo::query()->whereRaw('1=0');
    }

    public static function idsVehiculosUsuario()
    {
        return self::vehiculosUsuario()->pluck('id');
    }
}