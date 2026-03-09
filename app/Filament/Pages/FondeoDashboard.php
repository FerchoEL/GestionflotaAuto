<?php

namespace App\Filament\Pages;

use App\Models\Vehiculo;
use App\Models\CargaCombustible;
use App\Models\Fondeo;
use App\Models\VehiculoFondeoConfig;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class FondeoDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.fondeo-dashboard';
    protected static ?string $navigationLabel = 'Fondeo Semanal';

    public function table(Table $table): Table
    {
        return $table
            ->query(Vehiculo::query())
            ->columns([

                Tables\Columns\TextColumn::make('placas')
                    ->label('Vehículo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('asignado')
                    ->label('Asignado')
                    ->state(fn ($record) =>
                        optional(
                            VehiculoFondeoConfig::where('vehiculo_id', $record->id)
                                ->where('activo', true)
                                ->first()
                        )->litros_asignados ?? 0
                    ),

                Tables\Columns\TextColumn::make('consumido')
                    ->label('Consumido')
                    ->state(fn ($record) =>
                        CargaCombustible::where('vehiculo_id', $record->id)
                            ->whereBetween('fecha_carga', [now()->startOfWeek(), now()->endOfWeek()])
                            ->sum('litros')
                    ),

                Tables\Columns\TextColumn::make('fondeado')
                    ->label('Fondeado')
                    ->state(fn ($record) =>
                        Fondeo::where('vehiculo_id', $record->id)
                            ->whereBetween('fecha_fondeado', [now()->startOfWeek(), now()->endOfWeek()])
                            ->sum('litros_fondeados')
                    ),

                Tables\Columns\TextColumn::make('pendiente')
                    ->label('Pendiente de fondear')
                    ->state(fn ($record) =>
                        $this->calcularPendiente($record)
                    ),

                Tables\Columns\TextColumn::make('estimado')
                    ->label('$ Estimado')
                    ->state(fn ($record) =>
                        number_format(
                            $this->calcularPendiente($record) * $this->obtenerPrecioPromedio($record),
                            2
                        )
                    ),
            ])
            ->actions([

                Tables\Actions\Action::make('fondear')
                    ->label('Fondear')
                    ->visible(fn ($record) => $this->calcularPendiente($record) > 0)
                    ->form([
                        TextInput::make('litros_fondeados')
                            ->numeric()
                            ->required()
                            ->default(fn ($record) => $this->calcularPendiente($record)),

                        TextInput::make('importe_fondeado')
                            ->numeric()
                            ->required(),

                        Textarea::make('comentario')
                    ])
                    ->action(function ($record, $data) {

                        Fondeo::create([
                            'vehiculo_id' => $record->id,
                            'litros_fondeados' => $data['litros_fondeados'],
                            'importe_fondeado' => $data['importe_fondeado'],
                            'fecha_fondeado' => now(),
                            'fondeado_por_user_id' => auth()->id(),
                            'comentario' => $data['comentario'] ?? null,
                        ]);
                    }),

            ]);
    }

    /* =======================
       MÉTODOS AUXILIARES
    ========================*/

    protected function calcularPendiente($record)
    {
        $consumido = CargaCombustible::where('vehiculo_id', $record->id)
            ->whereBetween('fecha_carga', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('litros');

        $fondeado = Fondeo::where('vehiculo_id', $record->id)
            ->whereBetween('fecha_fondeado', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('litros_fondeados');

        return max($consumido - $fondeado, 0);
    }

    protected function obtenerPrecioPromedio($record)
    {
        $ultimaCarga = CargaCombustible::where('vehiculo_id', $record->id)
            ->whereBetween('fecha_carga', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotNull('importe')
            ->where('litros', '>', 0)
            ->latest()
            ->first();

        if (!$ultimaCarga) {
            return 0;
        }

        return $ultimaCarga->importe / $ultimaCarga->litros;
    }
}
