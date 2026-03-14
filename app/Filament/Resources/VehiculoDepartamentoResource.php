<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoDepartamentoResource\Pages;
use App\Filament\Resources\VehiculoDepartamentoResource\RelationManagers;
use App\Models\VehiculoDepartamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoDepartamentoResource extends Resource
{
    protected static ?string $model = VehiculoDepartamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Asig. Vehículo a Departamento';
    protected static ?string $label = 'Asig. Vehículo a Departamento';
    protected static ?int $navigationSort = 7;

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehiculo_id')
                ->relationship('vehiculo', 'placas')
                ->required(),

                Forms\Components\Select::make('departamento_id')
                    ->relationship('departamento', 'nombre')
                    ->required(),

                Forms\Components\DatePicker::make('fecha_inicio')
                    ->required(),

                Forms\Components\DatePicker::make('fecha_fin'),

                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehiculo.placas'),
                Tables\Columns\TextColumn::make('departamento.nombre'),
                Tables\Columns\TextColumn::make('fecha_inicio'),
                Tables\Columns\TextColumn::make('fecha_fin'),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculoDepartamentos::route('/'),
            'create' => Pages\CreateVehiculoDepartamento::route('/create'),
            'edit' => Pages\EditVehiculoDepartamento::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin','activos']);
    }
}
