<?php

namespace App\Filament\Pages;

use App\Exports\ReporteDocumentosExport;
use App\Services\ReporteDocumentosService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReporteDocumentos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte Documentos';

    protected static string $view = 'filament.pages.reporte-documentos';

    public $vehiculo_id;
    public $departamento_id;
    public $localidad_id;
    public $tipo_documento_id;
    public $estado_vigencia;
    public $fecha_vencimiento_inicio;
    public $fecha_vencimiento_fin;

    protected ?Collection $documentosReporte = null;

    public function documentos(): Collection
    {
        return $this->documentosReporte ??= app(ReporteDocumentosService::class)
            ->documentos($this->filters());
    }

    public function resumen(): array
    {
        return app(ReporteDocumentosService::class)
            ->resumen($this->filters());
    }

    public function exportar()
    {
        abort_unless(auth()->user()?->can('reporte-documentos.export') ?? false, 403);

        return Excel::download(
            new ReporteDocumentosExport($this->filters()),
            'reporte_documentos.xlsx'
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pagina.reporte-documentos.view') ?? false;
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
            'tipo_documento' => $this->tipo_documento_id,
            'estado_vigencia' => $this->estado_vigencia,
            'vencimiento_inicio' => $this->fecha_vencimiento_inicio,
            'vencimiento_fin' => $this->fecha_vencimiento_fin,
        ];
    }
}
