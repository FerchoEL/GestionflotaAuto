<?php

namespace App\Filament\Resources\ResponsableAuxiliarResource\Pages;

use App\Filament\Resources\ResponsableAuxiliarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResponsableAuxiliares extends ListRecords
{
    protected static string $resource = ResponsableAuxiliarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
