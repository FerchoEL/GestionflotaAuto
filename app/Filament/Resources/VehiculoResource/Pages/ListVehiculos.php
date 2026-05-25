<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Exports\VehiculosExport;
use App\Filament\Resources\VehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListVehiculos extends ListRecords
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('exportar')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $vehiculos = VehiculoResource::getEloquentQuery()->get();

                    return Excel::download(
                        new VehiculosExport($vehiculos),
                        'vehiculos_' . now()->format('Ymd_His') . '.xlsx'
                    );
                }),
        ];
    }
}
