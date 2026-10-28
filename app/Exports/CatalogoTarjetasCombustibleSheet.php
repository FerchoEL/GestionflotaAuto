<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CatalogoTarjetasCombustibleSheet extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithEvents, WithHeadings, WithTitle
{
    public function __construct(private readonly string $titulo, private readonly array $filas) {}

    public function array(): array
    {
        return array_map(static fn (array $fila): array => array_values($fila), $this->filas);
    }

    public function headings(): array
    {
        return ['ID tarjeta', 'Número de tarjeta', 'Estado tarjeta', 'Fecha de registro', 'Última actualización', 'Indicador de asignación', 'Clasificación', 'Unidades involucradas', 'Número de unidad', 'Marca', 'Modelo', 'Año', 'Placas', 'VIN / número de serie', 'Departamento / gerencia', 'Localidad', 'Responsable actual', 'Cuenta analítica', 'Estatus actual de la unidad', 'Incidencias'];
    }

    public function title(): string
    {
        return $this->titulo;
    }

    public function columnFormats(): array
    {
        return ['B' => NumberFormat::FORMAT_TEXT];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'B' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $dimension = $sheet->calculateWorksheetDimension();
            $sheet->freezePane('A2');
            $sheet->setAutoFilter($dimension);
            $sheet->getStyle('A1:T1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1:T1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F4E78');
            $sheet->getStyle($dimension)->getAlignment()->setVertical('top')->setWrapText(true);
        }];
    }
}
