<?php

namespace App\Filament\Resources\ResponsableAuxiliarResource\Pages;

use App\Filament\Resources\ResponsableAuxiliarResource;
use App\Models\ResponsableAuxiliar;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateResponsableAuxiliar extends CreateRecord
{
    protected static string $resource = ResponsableAuxiliarResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        ResponsableAuxiliarResource::validateDistinctUsers($data);
        ResponsableAuxiliarResource::validateEligibleUsers($data);

        $existing = ResponsableAuxiliarResource::existingRelation($data);

        if ($existing?->activo) {
            throw ResponsableAuxiliarResource::duplicateException();
        }

        if ($existing) {
            $existing->forceFill([
                'activo' => true,
                'asignado_por_user_id' => auth()->id(),
            ])->save();

            return $existing->fresh();
        }

        $record = ResponsableAuxiliar::query()->create([
            'responsable_user_id' => $data['responsable_user_id'],
            'auxiliar_user_id' => $data['auxiliar_user_id'],
            'activo' => (bool) ($data['activo'] ?? true),
            'asignado_por_user_id' => auth()->id(),
        ]);

        return $record;
    }
}
