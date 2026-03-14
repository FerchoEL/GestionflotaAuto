<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoChoferResource\Pages;
use App\Filament\Resources\VehiculoChoferResource\RelationManagers;
use App\Models\VehiculoChofer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoChoferResource extends Resource
{
    protected static ?string $model = VehiculoChofer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Asig. Vehiculo a operador';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehiculo_id')
                ->label('Vehículo')
                ->relationship('vehiculo', 'placas')
                ->searchable()
                ->required(),

                Forms\Components\Select::make('chofer_user_id')
                    ->label('Chofer')
                    ->relationship('chofer', 'name')
                    ->searchable()
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
                Tables\Columns\TextColumn::make('vehiculo.placas')->label('Vehículo'),
                Tables\Columns\TextColumn::make('chofer.name')->label('Chofer'),
                Tables\Columns\TextColumn::make('fecha_inicio')->date(),
                Tables\Columns\TextColumn::make('fecha_fin')->date(),
                Tables\Columns\BooleanColumn::make('activo'),
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

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos']);
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
            'index' => Pages\ListVehiculoChofers::route('/'),
            'create' => Pages\CreateVehiculoChofer::route('/create'),
            'edit' => Pages\EditVehiculoChofer::route('/{record}/edit'),
        ];
    }
}
