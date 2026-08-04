<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuentaConcentradoraResource\Pages;
use App\Models\CuentaConcentradora;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CuentaConcentradoraResource extends Resource
{
    protected static ?string $model = CuentaConcentradora::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Cuentas Concentradoras';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('codigo')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('institucion')
                    ->maxLength(150),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('codigo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('institucion')->searchable(),
                Tables\Columns\IconColumn::make('activo')->boolean(),
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
        return auth()->user()?->can('cuenta-concentradora.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('cuenta-concentradora.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('cuenta-concentradora.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('cuenta-concentradora.update') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCuentaConcentradoras::route('/'),
            'create' => Pages\CreateCuentaConcentradora::route('/create'),
            'edit' => Pages\EditCuentaConcentradora::route('/{record}/edit'),
        ];
    }
}
