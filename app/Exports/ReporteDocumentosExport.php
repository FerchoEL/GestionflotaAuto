<?php

namespace App\Exports;

use App\Services\ReporteDocumentosService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteDocumentosExport implements FromCollection, WithHeadings
{
    public function __construct(protected array $filters)
    {
    }

    public function collection()
    {
        return app(ReporteDocumentosService::class)
            ->documentos($this->filters)
            ->map(function ($documento) {
                return [
                    $documento->vehiculo?->numero_economico,
                    $documento->vehiculo?->placas,
                    $documento->vehiculo?->marca,
                    $documento->vehiculo?->modelo,
                    $documento->vehiculo?->localidadActiva?->localidad?->nombre,
                    $documento->vehiculo?->departamentoActivo?->departamento?->nombre,
                    $documento->vehiculo?->usuarios_asignados_texto,
                    $documento->vehiculo?->usuario_responsable_texto,
                    $documento->tipoDocumento?->nombre,
                    $documento->nombre,
                    $documento->fecha_emision?->format('Y-m-d'),
                    $documento->fecha_vencimiento?->format('Y-m-d'),
                    $this->estadoLabel($documento->estado_vigencia_reporte),
                    $documento->dias_para_vencer_reporte,
                    $documento->polizaSeguro?->aseguradora?->nombre,
                    $documento->polizaSeguro?->costo_poliza,
                    $documento->polizaSeguro?->tipoPago?->nombre,
                    $documento->archivo_path,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Numero Economico',
            'Placa',
            'Marca',
            'Modelo',
            'Localidad',
            'Departamento',
            'Usuarios asignados',
            'Usuario responsable',
            'Tipo de documento',
            'Nombre documento',
            'Fecha de emision',
            'Fecha de vencimiento',
            'Estado de vigencia',
            'Dias para vencer',
            'Aseguradora',
            'Costo poliza',
            'Tipo de pago',
            'Archivo',
        ];
    }

    private function estadoLabel(?string $estado): string
    {
        return match ($estado) {
            'vigente' => 'Vigente',
            'por_vencer' => 'Por vencer',
            'vencido' => 'Vencido',
            default => 'Sin vigencia / no aplica',
        };
    }
}
