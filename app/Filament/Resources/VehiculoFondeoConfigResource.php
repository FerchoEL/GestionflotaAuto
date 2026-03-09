<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoFondeoConfigResource\Pages;
use App\Models\VehiculoFondeoConfig;
use App\Models\Vehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VehiculoFondeoConfigResource extends Resource
{
    protected static ?string $model = VehiculoFondeoConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Asignación de Fondeo';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('vehiculo_id')
                ->label('Vehículo')
                ->options(Vehiculo::orderBy('placas')->pluck('placas', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('litros_asignados')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\Toggle::make('activo')
                ->default(true)
                ->label('Activo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Vehículo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('litros_asignados'),

                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculoFondeoConfigs::route('/'),
            'create' => Pages\CreateVehiculoFondeoConfig::route('/create'),
            'edit' => Pages\EditVehiculoFondeoConfig::route('/{record}/edit'),
        ];
    }
}
