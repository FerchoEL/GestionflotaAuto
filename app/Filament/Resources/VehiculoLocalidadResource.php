<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoLocalidadResource\Pages;
use App\Filament\Resources\VehiculoLocalidadResource\RelationManagers;
use App\Models\VehiculoLocalidad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class VehiculoLocalidadResource extends Resource
{
    protected static ?string $model = VehiculoLocalidad::class;

     protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Asig. Vehículo a Localidad';
    protected static ?string $label = 'Asig. Vehículo a Localidad';
    protected static ?int $navigationSort = 6;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehiculo_id')
                ->relationship('vehiculo', 'placas')
                ->required(),

            Forms\Components\Select::make('localidad_id')
                ->relationship('localidad', 'nombre')
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
            Tables\Columns\TextColumn::make('localidad.nombre'),
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
            'index' => Pages\ListVehiculoLocalidads::route('/'),
            'create' => Pages\CreateVehiculoLocalidad::route('/create'),
            'edit' => Pages\EditVehiculoLocalidad::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
