<?php

namespace App\Filament\Resources\AsegoradoraResource\Pages;

use App\Filament\Resources\AsegoradoraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAsegoradores extends ListRecords
{
    protected static string $resource = AsegoradoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
