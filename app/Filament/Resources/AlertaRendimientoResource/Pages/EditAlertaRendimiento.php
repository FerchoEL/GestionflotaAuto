<?php

namespace App\Filament\Resources\AlertaRendimientoResource\Pages;

use App\Filament\Resources\AlertaRendimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlertaRendimiento extends EditRecord
{
    protected static string $resource = AlertaRendimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
