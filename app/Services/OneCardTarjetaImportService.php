<?php

namespace App\Services;

use App\Models\TarjetaCombustible;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OneCardTarjetaImportService
{
    public function importarDesdeArchivo(string $filePath): array
    {
        $sheetRows = $this->leerHojaBd($filePath);

        $rowsByNumero = [];
        $duplicadosArchivo = [];
        $invalidos = [];

        foreach ($sheetRows as $index => $row) {
            $excelRow = $index + 2;
            $numero = TarjetaCombustible::normalizarNumero($row['tarjeta'] ?? '');

            if ($numero === '') {
                $invalidos[] = "Fila {$excelRow}: tarjeta vacia.";
                continue;
            }

            if (isset($rowsByNumero[$numero])) {
                $duplicadosArchivo[] = "Fila {$excelRow}: tarjeta {$numero} duplicada en el archivo.";
                continue;
            }

            $rowsByNumero[$numero] = [
                'numero' => $numero,
                'descripcion' => $row['nombre'] ?: $numero,
                'empleado_one_card' => $row['empleado'] ?: null,
                'convenio_id_one_card' => $row['convenio_id'] ?: null,
                'convenio_one_card' => $row['convenio'] ?: null,
                'sucursal_one_card' => $row['sucursal'] ?: null,
                'nombre_one_card' => $row['nombre'] ?: null,
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        $numeros = array_keys($rowsByNumero);

        if ($numeros === []) {
            return [
                'leidas' => count($sheetRows),
                'candidatas' => 0,
                'insertadas' => 0,
                'existentes' => [],
                'duplicados_archivo' => $duplicadosArchivo,
                'invalidos' => $invalidos,
            ];
        }

        $existentes = TarjetaCombustible::query()
            ->whereIn('numero', $numeros)
            ->pluck('numero')
            ->map(fn ($numero) => TarjetaCombustible::normalizarNumero($numero))
            ->all();

        $existentesLookup = array_fill_keys($existentes, true);

        $nuevas = [];

        foreach ($rowsByNumero as $numero => $payload) {
            if (isset($existentesLookup[$numero])) {
                continue;
            }

            $nuevas[] = $payload;
        }

        $insertadas = 0;

        if ($nuevas !== []) {
            $insertadas = TarjetaCombustible::query()->insertOrIgnore($nuevas);
        }

        return [
            'leidas' => count($sheetRows),
            'candidatas' => count($rowsByNumero),
            'insertadas' => $insertadas,
            'existentes' => array_values($existentes),
            'duplicados_archivo' => $duplicadosArchivo,
            'invalidos' => $invalidos,
        ];
    }

    protected function leerHojaBd(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('BD');

        if (! $sheet) {
            throw new \RuntimeException('El archivo no contiene la hoja BD.');
        }

        $highestRow = $sheet->getHighestDataRow();
        $rows = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $tarjeta = $this->value($sheet->getCell("C{$row}")->getFormattedValue());

            if ($tarjeta === '') {
                continue;
            }

            $rows[] = [
                'empleado' => $this->value($sheet->getCell("A{$row}")->getFormattedValue()),
                'convenio_id' => $this->value($sheet->getCell("B{$row}")->getFormattedValue()),
                'tarjeta' => $tarjeta,
                'convenio' => $this->value($sheet->getCell("D{$row}")->getFormattedValue()),
                'sucursal' => $this->value($sheet->getCell("E{$row}")->getFormattedValue()),
                'nombre' => $this->value($sheet->getCell("F{$row}")->getFormattedValue()),
            ];
        }

        return $rows;
    }

    protected function value(mixed $value): string
    {
        return trim((string) $value);
    }
}
