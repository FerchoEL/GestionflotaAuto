<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehiculo;

class ResolverCuentaAnaliticaCarga
{
    public function resolver(
        ?User $usuario,
        Vehiculo $vehiculo,
        mixed $cuentaAnaliticaSeleccionada = null,
    ): ?int {
        if ($usuario?->esChoferEstricto()) {
            return $vehiculo->cuentaAnaliticaActiva?->cuenta_analitica_id;
        }

        if ($usuario?->hasAnyRole(['admin', 'responsable', 'auxiliar_responsable'])
            && filled($cuentaAnaliticaSeleccionada)) {
            return (int) $cuentaAnaliticaSeleccionada;
        }

        return $vehiculo->cuentaAnaliticaActiva?->cuenta_analitica_id;
    }
}
