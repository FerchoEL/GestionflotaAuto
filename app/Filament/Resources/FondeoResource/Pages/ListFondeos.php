<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFondeos extends ListRecords
{
    protected static string $resource = FondeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
