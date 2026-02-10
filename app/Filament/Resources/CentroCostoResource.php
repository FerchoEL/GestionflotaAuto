<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CentroCostoResource\Pages;
use App\Filament\Resources\CentroCostoResource\RelationManagers;
use App\Models\CentroCosto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CentroCostoResource extends Resource
{
    protected static ?string $model = CentroCosto::class;

    
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Centros de costo';

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

            Forms\Components\Toggle::make('activo')
                ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable(),
                Tables\Columns\TextColumn::make('codigo')->searchable(),
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

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'activos', 'administracion']);
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
            'index' => Pages\ListCentroCostos::route('/'),
            'create' => Pages\CreateCentroCosto::route('/create'),
            'edit' => Pages\EditCentroCosto::route('/{record}/edit'),
        ];
    }
}
