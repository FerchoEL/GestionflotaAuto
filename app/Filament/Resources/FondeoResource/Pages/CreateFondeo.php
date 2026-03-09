<?php

namespace App\Filament\Resources\FondeoResource\Pages;

use App\Filament\Resources\FondeoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateFondeo extends CreateRecord
{
    protected static string $resource = FondeoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fondeado_por_user_id'] = Auth::id();
        return $data;
    }
}
