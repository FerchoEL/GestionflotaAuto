<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarcaVehiculoResource\Pages;
use App\Models\MarcaVehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MarcaVehiculoResource extends Resource
{
    protected static ?string $model = MarcaVehiculo::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Marcas de vehículo';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Marca')
                    ->required()
                    ->maxLength(100)
                    ->unique(
                        table: MarcaVehiculo::class,
                        column: 'nombre',
                        ignoreRecord: true
                    ),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean()
                    ->label('Activo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarcaVehiculos::route('/'),
            'create' => Pages\CreateMarcaVehiculo::route('/create'),
            'edit' => Pages\EditMarcaVehiculo::route('/{record}/edit'),
        ];
    }
}
