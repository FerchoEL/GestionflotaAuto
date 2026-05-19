<?php

namespace App\Services;

use App\Models\VehiculoDepartamento;
use App\Models\VehiculoLocalidad;
use App\Models\VehiculoResponsable;
use App\Models\VehiculoTarjeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VehiculoAsignacionActivaService
{
    public function guardarDepartamento(array $data, ?VehiculoDepartamento $record = null): VehiculoDepartamento
    {
        return $this->guardarAsignacion(
            modelClass: VehiculoDepartamento::class,
            record: $record,
            data: $data,
            scopeColumns: [
                'vehiculo_id' => (int) $data['vehiculo_id'],
            ],
            activePayload: [
                'vehiculo_id' => (int) $data['vehiculo_id'],
                'departamento_id' => (int) $data['departamento_id'],
                'asignado_por_user_id' => $data['asignado_por_user_id'] ?? auth()->id(),
            ],
        );
    }

    public function guardarLocalidad(array $data, ?VehiculoLocalidad $record = null): VehiculoLocalidad
    {
        return $this->guardarAsignacion(
            modelClass: VehiculoLocalidad::class,
            record: $record,
            data: $data,
            scopeColumns: [
                'vehiculo_id' => (int) $data['vehiculo_id'],
            ],
            activePayload: [
                'vehiculo_id' => (int) $data['vehiculo_id'],
                'localidad_id' => (int) $data['localidad_id'],
                'asignado_por_user_id' => $data['asignado_por_user_id'] ?? auth()->id(),
            ],
        );
    }

    public function guardarResponsable(array $data, ?VehiculoResponsable $record = null): VehiculoResponsable
    {
        return $this->guardarAsignacion(
            modelClass: VehiculoResponsable::class,
            record: $record,
            data: $data,
            scopeColumns: [
                'vehiculo_id' => (int) $data['vehiculo_id'],
            ],
            activePayload: [
                'vehiculo_id' => (int) $data['vehiculo_id'],
                'responsable_user_id' => (int) $data['responsable_user_id'],
            ],
        );
    }

    public function guardarTarjeta(array $data, ?VehiculoTarjeta $record = null): VehiculoTarjeta
    {
        $vehiculoId = (int) $data['vehiculo_id'];
        $tarjetaId = (int) $data['tarjeta_combustible_id'];

        return DB::transaction(function () use ($data, $record, $vehiculoId, $tarjetaId): VehiculoTarjeta {
            $fechaInicio = $this->parseDate($data['fecha_inicio'] ?? now());
            $fechaFin = $this->parseNullableDate($data['fecha_fin'] ?? null);
            $isActive = $this->shouldRemainActive($data);

            if (! $isActive) {
                $payload = [
                    'vehiculo_id' => $vehiculoId,
                    'tarjeta_combustible_id' => $tarjetaId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'activo' => false,
                ];

                return $this->persistRecord(VehiculoTarjeta::class, $payload, $record);
            }

            VehiculoTarjeta::query()
                ->where(function ($query) use ($vehiculoId, $tarjetaId): void {
                    $query->where('vehiculo_id', $vehiculoId)
                        ->orWhere('tarjeta_combustible_id', $tarjetaId);
                })
                ->where('activo', true)
                ->when($record?->exists, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->lockForUpdate()
                ->get()
                ->each(function (VehiculoTarjeta $activeRecord) use ($fechaInicio): void {
                    $activeRecord->forceFill([
                        'activo' => false,
                        'fecha_fin' => $activeRecord->fecha_fin ?: $fechaInicio,
                    ])->saveQuietly();
                });

            $payload = [
                'vehiculo_id' => $vehiculoId,
                'tarjeta_combustible_id' => $tarjetaId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => null,
                'activo' => true,
            ];

            return $this->persistRecord(VehiculoTarjeta::class, $payload, $record);
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function guardarAsignacion(
        string $modelClass,
        ?Model $record,
        array $data,
        array $scopeColumns,
        array $activePayload,
    ): Model {
        return DB::transaction(function () use ($modelClass, $record, $data, $scopeColumns, $activePayload): Model {
            $fechaInicio = $this->parseDate($data['fecha_inicio'] ?? now());
            $fechaFin = $this->parseNullableDate($data['fecha_fin'] ?? null);
            $isActive = $this->shouldRemainActive($data);

            if (! $isActive) {
                $payload = array_merge($activePayload, [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'activo' => false,
                ]);

                return $this->persistRecord($modelClass, $payload, $record);
            }

            $query = $modelClass::query()
                ->where($scopeColumns)
                ->where('activo', true);

            if ($record?->exists) {
                $query->whereKeyNot($record->getKey());
            }

            $query
                ->lockForUpdate()
                ->get()
                ->each(function (Model $activeRecord) use ($fechaInicio): void {
                    $activeRecord->forceFill([
                        'activo' => false,
                        'fecha_fin' => $activeRecord->fecha_fin ?: $fechaInicio,
                    ])->saveQuietly();
                });

            $payload = array_merge($activePayload, [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => null,
                'activo' => true,
            ]);

            return $this->persistRecord($modelClass, $payload, $record);
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function persistRecord(string $modelClass, array $payload, ?Model $record = null): Model
    {
        if ($record?->exists) {
            $record->forceFill($payload)->saveQuietly();

            return $record->fresh();
        }

        /** @var Model $created */
        $created = $modelClass::query()->create($payload);

        return $created;
    }

    private function shouldRemainActive(array $data): bool
    {
        return (bool) ($data['activo'] ?? true) && blank($data['fecha_fin'] ?? null);
    }

    private function parseDate(mixed $value): string
    {
        return Carbon::parse($value)->toDateString();
    }

    private function parseNullableDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }
}
