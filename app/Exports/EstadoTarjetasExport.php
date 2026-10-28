<?php

namespace App\Exports;

use App\Reports\EstadoTarjetasReport;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

// FromQuery procesa por lotes; todos los valores se escriben como texto literal.
class EstadoTarjetasExport extends StringValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(private Builder $consulta) {}

    public function query(): Builder
    {
        return clone $this->consulta;
    }

    public function headings(): array
    {
        return ['No. Económico', 'Placa', 'Marca', 'Modelo', 'Localidad', 'Tarjeta', 'Fecha inicio', 'Activo', 'Cuenta analítica', 'Estado de asignación'];
    }

    public function map($row): array
    {
        return EstadoTarjetasReport::fila($row);
    }
}
