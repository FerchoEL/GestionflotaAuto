<?php

namespace App\Filament\Resources\AsegoradoraResource\Pages;

use App\Filament\Resources\AsegoradoraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAseguradora extends EditRecord
{
    protected static string $resource = AsegoradoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
