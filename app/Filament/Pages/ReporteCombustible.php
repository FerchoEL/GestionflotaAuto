<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\CargaCombustible;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteCombustibleExport;

class ReporteCombustible extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte Combustible';

    protected static string $view = 'filament.pages.reporte-combustible';

    public $vehiculo_id;
    public $cuenta_analitica_id;
    public $departamento_id;
    public $tipo_combustible;

    public $fecha_inicio;
    public $fecha_fin;

    public function mount()
    {
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin = now()->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY BASE CON FILTROS
    |--------------------------------------------------------------------------
    */

    private function queryBase()
    {
        $query = CargaCombustible::query()
            ->with([
                'vehiculo',
                'vehiculo.tarjetaActiva.tarjeta',
                'vehiculo.departamentoActivo.departamento',
                'cuentaAnalitica',
                'rendimiento'
            ]);

        if ($this->vehiculo_id) {
            $query->where('vehiculo_id', $this->vehiculo_id);
        }

        if ($this->cuenta_analitica_id) {
            $query->where('cuenta_analitica_id', $this->cuenta_analitica_id);
        }

        if ($this->tipo_combustible) {
            $query->whereHas('vehiculo', function ($q) {
                $q->where('tipo_combustible', $this->tipo_combustible);
            });
        }

        if ($this->departamento_id) {
            $query->whereHas('vehiculo.departamentoActivo', function ($q) {
                $q->where('departamento_id', $this->departamento_id);
            });
        }

        if ($this->fecha_inicio) {
            $query->whereDate('fecha_carga', '>=', $this->fecha_inicio);
        }

        if ($this->fecha_fin) {
            $query->whereDate('fecha_carga', '<=', $this->fecha_fin);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLE CARGAS
    |--------------------------------------------------------------------------
    */

    public function cargas(): Collection
    {
        return $this->queryBase()
            ->orderByDesc('fecha_carga')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN POR VEHICULO
    |--------------------------------------------------------------------------
    */

    public function resumenVehiculos(): Collection
    {
        return $this->queryBase()
            ->join('vehiculos', 'vehiculos.id', '=', 'carga_combustibles.vehiculo_id')
            ->leftJoin('rendimientos', 'rendimientos.carga_id', '=', 'carga_combustibles.id')
            ->selectRaw('
                vehiculos.id as vehiculo_id,
                vehiculos.placas,
                vehiculos.numero_economico,
                SUM(rendimientos.km_recorridos) as km_recorridos,
                SUM(carga_combustibles.litros) as litros,
                SUM(carga_combustibles.importe) as importe
            ')
            ->groupBy(
                'vehiculos.id',
                'vehiculos.placas',
                'vehiculos.numero_economico'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | TOTALES GENERALES
    |--------------------------------------------------------------------------
    */

    public function totalImporte()
    {
        return $this->queryBase()->sum('importe');
    }

    public function totalLitros()
    {
        return $this->queryBase()->sum('litros');
    }

   
    public function totalKm()
{
    $query = CargaCombustible::query()
        ->leftJoin('rendimientos','rendimientos.carga_id','=','carga_combustibles.id');

    if ($this->vehiculo_id) {

        $query->where('carga_combustibles.vehiculo_id',$this->vehiculo_id);

    }

    if ($this->cuenta_analitica_id) {

        $query->where('carga_combustibles.cuenta_analitica_id',$this->cuenta_analitica_id);

    }

    if ($this->fecha_inicio) {

        $query->whereDate('carga_combustibles.fecha_carga','>=',$this->fecha_inicio);

    }

    if ($this->fecha_fin) {

        $query->whereDate('carga_combustibles.fecha_carga','<=',$this->fecha_fin);

    }

    return $query->sum('rendimientos.km_recorridos');
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
        return Excel::download(
            new ReporteCombustibleExport([
                'vehiculo'=>$this->vehiculo_id,
                'cuenta'=>$this->cuenta_analitica_id,
                'inicio'=>$this->fecha_inicio,
                'fin'=>$this->fecha_fin
            ]),
            'reporte_combustible.xlsx'
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}