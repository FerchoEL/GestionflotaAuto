<?php

namespace App\Console\Commands;

use App\Exports\CatalogoTarjetasCombustibleExport;
use App\Reports\CatalogoTarjetasCombustibleReport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportarCatalogoTarjetasCombustible extends Command
{
    protected $signature = 'tarjetas:exportar-catalogo {--filename= : Nombre opcional del archivo .xlsx}';

    protected $description = 'Exporta una fotografía de las tarjetas de combustible y sus asignaciones vigentes.';

    public function handle(CatalogoTarjetasCombustibleReport $reporte): int
    {
        $nombre = $this->nombreArchivo();
        $datos = $reporte->build();
        Excel::store(new CatalogoTarjetasCombustibleExport($datos), $nombre, 'reports');

        $resumen = $datos['resumen'];
        $this->info('Reporte generado: '.storage_path('app/reports/'.$nombre));
        $this->line("Tarjetas exportadas: {$resumen['total']}");
        $this->line("Tarjetas asignadas: {$resumen['asignadas']}");
        $this->line("Tarjetas sin asignación: {$resumen['sin_asignar']}");
        $this->line("Incidencias detectadas: {$resumen['incidencias']}");

        return self::SUCCESS;
    }

    private function nombreArchivo(): string
    {
        $nombre = basename((string) ($this->option('filename') ?: 'catalogo_tarjetas_combustible_'.now()->format('Ymd_His').'.xlsx'));

        return str_ends_with(strtolower($nombre), '.xlsx') ? $nombre : $nombre.'.xlsx';
    }
}
