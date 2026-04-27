<?php

namespace App\Filament\Resources\AseguradoraResource\Pages;

use App\Filament\Resources\AseguradoraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAseguradora extends EditRecord
{
    protected static string $resource = AseguradoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
