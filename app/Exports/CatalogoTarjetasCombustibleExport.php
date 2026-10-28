<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CatalogoTarjetasCombustibleExport implements WithMultipleSheets
{
    public function __construct(private readonly array $datos) {}

    public function sheets(): array
    {
        return [
            new CatalogoTarjetasCombustibleSheet('Catálogo completo', $this->datos['todas']->all()),
            new CatalogoTarjetasCombustibleSheet('Tarjetas asignadas', $this->datos['asignadas']->all()),
            new CatalogoTarjetasCombustibleSheet('Sin asignación e incidencias', $this->datos['incidencias']->all()),
        ];
    }
}
