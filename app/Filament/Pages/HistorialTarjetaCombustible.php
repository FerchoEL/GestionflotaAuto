<?php

namespace App\Filament\Pages;

use App\Models\TarjetaCombustible;
use App\Models\VehiculoTarjeta;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Attributes\Locked;

class HistorialTarjetaCombustible extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Historial de asignaciones de tarjeta';

    protected static string $view = 'filament.pages.historial-tarjeta-combustible';

    #[Locked]
    public int $tarjeta;

    public string $numero = '';

    #[Locked]
    public bool $conflicto = false;

    public static function canAccess(): bool
    {
        return ReporteTarjetasCombustible::canAccess();
    }

    public function mount(): void
    {
        $record = TarjetaCombustible::findOrFail(request()->integer('tarjeta'));
        $this->tarjeta = $record->id;
        $this->numero = $record->numero;
        $this->conflicto = \App\Reports\EstadoTarjetasReport::conflicto(TarjetaCombustible::query())->whereKey($record->id)->exists();
    }

    public function table(Table $table): Table
    {
        return $table->query(VehiculoTarjeta::where('tarjeta_combustible_id', $this->tarjeta)
            ->with(['vehiculo.localidadActiva.localidad', 'vehiculo.cuentasAnaliticas.cuentaAnalitica']))
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.numero_economico')->label('No. Económico')->searchable()->sortable()->placeholder('SIN INFORMACIÓN'),
                Tables\Columns\TextColumn::make('vehiculo.placas')->label('Placa')->searchable(),
                Tables\Columns\TextColumn::make('vehiculo.marca')->label('Marca'),
                Tables\Columns\TextColumn::make('vehiculo.modelo')->label('Modelo'),
                Tables\Columns\TextColumn::make('fecha_inicio')->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('fecha_fin')->date('Y-m-d')->sortable(),
                Tables\Columns\IconColumn::make('activo')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('cuenta')->label('Cuenta al inicio del periodo')->getStateUsing(function (VehiculoTarjeta $record) {
                    $cuentas = $record->vehiculo?->cuentasAnaliticas->filter(fn ($c) => $c->fecha_inicio <= $record->fecha_inicio
                        && ($c->fecha_fin === null || $c->fecha_fin > $record->fecha_inicio));

                    return $cuentas?->count() === 1 ? ($cuentas->first()->cuentaAnalitica?->nombre ?? 'SIN INFORMACIÓN') : 'SIN INFORMACIÓN';
                }),
                Tables\Columns\TextColumn::make('estado')->label('Estado de asignación')->badge()
                    ->getStateUsing(fn (VehiculoTarjeta $record) => ! $record->activo ? 'ASIGNACIÓN HISTÓRICA' : (($this->conflicto) ? 'POSIBLE DUPLICIDAD' : 'ASIGNADA')),
            ])->defaultSort('fecha_inicio', 'desc')->paginated([25, 50, 100]);
    }
}
