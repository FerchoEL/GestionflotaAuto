<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteCombustibleExport;
use App\Services\ReporteCombustibleService;

class ReporteCombustible extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte Combustible';

    protected static string $view = 'filament.pages.reporte-combustible';

    public $vehiculo_id;
    public $cuenta_analitica_id;
    public $departamento_id;
    public $localidad_id;
    public $tipo_combustible;

    public $fecha_inicio;
    public $fecha_fin;

    protected ?Collection $cargasReporte = null;

    public function mount()
    {
        $rango = app(ReporteCombustibleService::class)->rangoPorDefecto();

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
        return $this->cargasReporte ??= app(ReporteCombustibleService::class)
            ->cargasConRendimientoReal($this->filters());
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN POR VEHICULO
    |--------------------------------------------------------------------------
    */

    public function resumenVehiculos(): Collection
    {
        return app(ReporteCombustibleService::class)
            ->resumenVehiculos($this->filters());
    }

    /*
    |--------------------------------------------------------------------------
    | TOTALES GENERALES
    |--------------------------------------------------------------------------
    */

    public function totalImporte()
    {
        return $this->cargas()->sum(fn ($carga) => (float) $carga->importe);
    }

    public function totalLitros()
    {
        return $this->cargas()->sum(fn ($carga) => (float) ($carga->litros_consumo_reporte ?? 0));
    }

    public function totalLitrosCargados()
    {
        return $this->cargas()->sum(fn ($carga) => (float) $carga->litros);
    }

    public function totalKm()
    {
        return $this->cargas()->sum(fn ($carga) => (float) ($carga->km_recorridos_reporte ?? 0));
    }

    public function rendimientoGlobal()
    {
        $km = $this->totalKm();
        $litros = $this->totalLitros();

        if ($litros == 0) {
            return 0;
        }

        return $km / $litros;
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
            new ReporteCombustibleExport([
                'vehiculo'=>$this->vehiculo_id,
                'departamento'=>$this->departamento_id,
                'localidad'=>$this->localidad_id,
                'cuenta'=>$this->cuenta_analitica_id,
                'tipo_combustible'=>$this->tipo_combustible,
                'inicio'=>$this->fecha_inicio,
                'fin'=>$this->fecha_fin
            ]),
            'reporte_combustible.xlsx'
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
