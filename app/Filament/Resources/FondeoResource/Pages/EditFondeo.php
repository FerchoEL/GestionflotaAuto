<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFondeo extends EditRecord
{
    protected static string $resource = FondeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
