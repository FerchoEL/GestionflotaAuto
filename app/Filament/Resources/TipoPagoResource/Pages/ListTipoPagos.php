<?php

namespace App\Filament\Resources\TipoPagoResource\Pages;

use App\Filament\Resources\TipoPagoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoPagos extends ListRecords
{
    protected static string $resource = TipoPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
