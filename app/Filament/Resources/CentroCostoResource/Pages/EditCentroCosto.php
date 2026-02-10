<?php

namespace App\Filament\Resources\CentroCostoResource\Pages;

use App\Filament\Resources\CentroCostoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCentroCosto extends EditRecord
{
    protected static string $resource = CentroCostoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
