<?php

namespace App\Exports;

use App\Models\TarjetaCombustible;
use App\Services\TarjetaSaldoService;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SolicitudRecargaExport
{
    public function download(): StreamedResponse
    {
        $templatePath = storage_path('app/templates/onecard-solicitud-recarga.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $this->buildRows();
        $startRow = 4;

        foreach ($rows as $index => $row) {
            $currentRow = $startRow + $index;
            $sheet->setCellValue('B' . $currentRow, $row['empleado']);
            $sheet->setCellValue('E' . $currentRow, $row['importe_de_carga']);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'solicitud_recarga.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function buildRows(): array
    {
        $saldoService = app(TarjetaSaldoService::class);

        return TarjetaCombustible::query()
            ->with('vehiculoActivo.vehiculo')
            ->where('activo', true)
            ->whereHas('vehiculoActivo')
            ->get()
            ->map(function (TarjetaCombustible $tarjeta) use ($saldoService) {
                return [
                    'empleado' => $tarjeta->empleado_one_card,
                    'importe_de_carga' => $saldoService->obtenerMontoReposicionPesosTarjeta($tarjeta),
                ];
            })
            ->all();
    }
}
