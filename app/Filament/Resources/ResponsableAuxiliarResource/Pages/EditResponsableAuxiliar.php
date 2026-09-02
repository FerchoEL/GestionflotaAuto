<?php

namespace App\Filament\Resources\ResponsableAuxiliarResource\Pages;

use App\Filament\Resources\ResponsableAuxiliarResource;
use App\Models\ResponsableAuxiliar;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditResponsableAuxiliar extends EditRecord
{
    protected static string $resource = ResponsableAuxiliarResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        ResponsableAuxiliarResource::validateDistinctUsers($data);
        ResponsableAuxiliarResource::validateEligibleUsers($data);

        if (ResponsableAuxiliarResource::existingRelation($data, $record)) {
            throw ResponsableAuxiliarResource::duplicateException();
        }

        /** @var ResponsableAuxiliar $record */
        $wasInactive = ! $record->activo;
        $pairChanged = $record->responsable_user_id != $data['responsable_user_id']
            || $record->auxiliar_user_id != $data['auxiliar_user_id'];

        $record->forceFill([
            'responsable_user_id' => $data['responsable_user_id'],
            'auxiliar_user_id' => $data['auxiliar_user_id'],
            'activo' => (bool) ($data['activo'] ?? false),
        ]);

        if (($wasInactive && $record->activo) || $pairChanged) {
            $record->asignado_por_user_id = auth()->id();
        }

        $record->save();
        return $record->fresh();
    }
}
