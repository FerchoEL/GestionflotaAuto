<?php

namespace App\Filament\Resources\PolizaSeguroResource\Pages;

use App\Filament\Resources\PolizaSeguroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolizaSeguro extends EditRecord
{
    protected static string $resource = PolizaSeguroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
