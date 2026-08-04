<?php

namespace App\Filament\Pages;

use App\Exports\ReporteCombustibleCopiaExport;
use App\Services\ReporteCombustibleCopiaService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReporteCombustibleCopia extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte Combustible Copia';

    protected static string $view = 'filament.pages.reporte-combustible-copia';

    public $vehiculo_id;
    public $cuenta_analitica_id;
    public $departamento_id;
    public $localidad_id;
    public $tipo_combustible;

    public $fecha_inicio;
    public $fecha_fin;

    protected ?Collection $cargasReporte = null;
    protected ?object $totalesReporte = null;

    public function mount()
    {
        $rango = app(ReporteCombustibleCopiaService::class)->rangoPorDefecto();

        $this->fecha_inicio = $rango['inicio'];
        $this->fecha_fin = $rango['fin'];
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLE CARGAS
    |--------------------------------------------------------------------------
    */

    public function cargas(): Collection
    {
        return $this->cargasReporte ??= app(ReporteCombustibleCopiaService::class)
            ->cargasConRendimientoReal($this->filters());
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'vehiculo_id',
            'cuenta_analitica_id',
            'departamento_id',
            'localidad_id',
            'tipo_combustible',
            'fecha_inicio',
            'fecha_fin',
        ], true)) {
            $this->cargasReporte = null;
            $this->totalesReporte = null;
        }
    }

    private function totalesReporte(): object
    {
        return $this->totalesReporte ??= app(ReporteCombustibleCopiaService::class)
            ->totalesReporte($this->filters());
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN POR VEHICULO
    |--------------------------------------------------------------------------
    */

    public function resumenVehiculos(): Collection
    {
        return app(ReporteCombustibleCopiaService::class)
            ->resumenVehiculos($this->filters());
    }

    /*
    |--------------------------------------------------------------------------
    | TOTALES GENERALES
    |--------------------------------------------------------------------------
    */

    public function totalImporte()
    {
        return $this->totalesReporte()->importe;
    }

    public function totalLitrosCargados()
    {
        return $this->totalesReporte()->litros;
    }

    public function totalKm()
    {
        return $this->totalesReporte()->km;
    }

    public function rendimientoGlobal()
    {
        return $this->totalesReporte()->rendimiento;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORTAR
    |--------------------------------------------------------------------------
    */

    public function exportar()
    {
        abort_unless(auth()->user()?->can('reporte-combustible.export') ?? false, 403);

        return Excel::download(
            new ReporteCombustibleCopiaExport([
                'vehiculo' => $this->vehiculo_id,
                'departamento' => $this->departamento_id,
                'localidad' => $this->localidad_id,
                'cuenta' => $this->cuenta_analitica_id,
                'tipo_combustible' => $this->tipo_combustible,
                'inicio' => $this->fecha_inicio,
                'fin' => $this->fecha_fin,
            ]),
            'reporte_combustible_copia.xlsx'
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pagina.reporte-combustible.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    private function filters(): array
    {
        return [
            'vehiculo' => $this->vehiculo_id,
            'departamento' => $this->departamento_id,
            'localidad' => $this->localidad_id,
            'cuenta' => $this->cuenta_analitica_id,
            'tipo_combustible' => $this->tipo_combustible,
            'inicio' => $this->fecha_inicio,
            'fin' => $this->fecha_fin,
        ];
    }
}
