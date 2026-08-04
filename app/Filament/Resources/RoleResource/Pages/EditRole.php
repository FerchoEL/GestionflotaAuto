<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permission_groups'] = RoleResource::permissionGroupsForRole($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->name === 'admin') {
            $data['name'] = 'admin';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        RoleResource::syncPermissionsFor($this->record, $this->data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => RoleResource::canDelete($this->record)),
        ];
    }
}
