<?php

namespace App\Services;

use App\Models\VehiculoDocumento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReporteDocumentosService
{
    public function documentos(array $filters): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(function (VehiculoDocumento $documento) {
                $estado = $this->resolverEstadoVigencia($documento);
                $dias = $documento->fecha_vencimiento
                    ? now()->startOfDay()->diffInDays($documento->fecha_vencimiento->startOfDay(), false)
                    : null;

                $documento->setAttribute('estado_vigencia_reporte', $estado);
                $documento->setAttribute('dias_para_vencer_reporte', $dias);

                return $documento;
            })
            ->when(filled($filters['estado_vigencia'] ?? null), function (Collection $documentos) use ($filters) {
                return $documentos
                    ->filter(fn (VehiculoDocumento $documento) => $documento->estado_vigencia_reporte === $filters['estado_vigencia'])
                    ->values();
            });
    }

    public function resumen(array $filters): array
    {
        $documentos = $this->documentos($filters);

        return [
            'total' => $documentos->count(),
            'vigentes' => $documentos->where('estado_vigencia_reporte', 'vigente')->count(),
            'por_vencer' => $documentos->where('estado_vigencia_reporte', 'por_vencer')->count(),
            'vencidos' => $documentos->where('estado_vigencia_reporte', 'vencido')->count(),
            'sin_vigencia' => $documentos->where('estado_vigencia_reporte', 'sin_vigencia')->count(),
        ];
    }

    private function query(array $filters): Builder
    {
        $query = VehiculoDocumento::query()
            ->with([
                'vehiculo.departamentoActivo.departamento',
                'vehiculo.localidadActiva.localidad',
                'tipoDocumento',
                'polizaSeguro.aseguradora',
                'polizaSeguro.tipoPago',
            ])
            ->orderByRaw('fecha_vencimiento is null')
            ->orderBy('fecha_vencimiento')
            ->orderBy('vehiculo_id')
            ->orderByDesc('id');

        $this->applyAccessScope($query);

        if ($filters['vehiculo'] ?? null) {
            $query->where('vehiculo_id', $filters['vehiculo']);
        }

        if ($filters['tipo_documento'] ?? null) {
            $query->where('tipo_documento_id', $filters['tipo_documento']);
        }

        if ($filters['departamento'] ?? null) {
            $query->whereHas('vehiculo.departamentoActivo', function (Builder $subQuery) use ($filters) {
                $subQuery->where('departamento_id', $filters['departamento']);
            });
        }

        if ($filters['localidad'] ?? null) {
            $query->whereHas('vehiculo.localidadActiva', function (Builder $subQuery) use ($filters) {
                $subQuery->where('localidad_id', $filters['localidad']);
            });
        }

        if ($filters['vencimiento_inicio'] ?? null) {
            $query->whereDate('fecha_vencimiento', '>=', $filters['vencimiento_inicio']);
        }

        if ($filters['vencimiento_fin'] ?? null) {
            $query->whereDate('fecha_vencimiento', '<=', $filters['vencimiento_fin']);
        }

        return $query;
    }

    private function applyAccessScope(Builder $query): void
    {
        $user = auth()->user();

        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasAnyRole(['admin', 'activos'])) {
            return;
        }

        if ($user->hasRole('chofer')) {
            $query->whereHas('vehiculo.choferes', function (Builder $subQuery) use ($user) {
                $subQuery
                    ->where('chofer_user_id', $user->id)
                    ->where('activo', true)
                    ->where(function (Builder $sub) {
                        $sub->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', now());
                    });
            });

            return;
        }

        if ($user->hasRole('responsable')) {
            $query->whereHas('vehiculo.responsableActivo', function (Builder $subQuery) use ($user) {
                $subQuery->where('responsable_user_id', $user->id);
            });

            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function resolverEstadoVigencia(VehiculoDocumento $documento): string
    {
        if (! $documento->requiereVigencia() || ! $documento->fecha_vencimiento) {
            return 'sin_vigencia';
        }

        $estadoAlerta = $documento->estadoAlertaDocumento();

        return match ($estadoAlerta) {
            'vencido' => 'vencido',
            'por_vencer' => 'por_vencer',
            default => 'vigente',
        };
    }
}
